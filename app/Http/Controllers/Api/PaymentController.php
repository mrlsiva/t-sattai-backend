<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Cart;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Stripe\Stripe;
use Stripe\PaymentIntent;
use Stripe\Exception\ApiErrorException;

class PaymentController extends Controller
{
    public function __construct()
    {
        // Set your secret key. Remember to switch to your live secret key in production.
        Stripe::setApiKey(config('services.stripe.secret'));
    }

    /**
     * Create a payment intent for checkout
     */
    public function createPaymentIntent(Request $request)
    {
        // Add debug logging
        \Log::info('Payment intent request received', [
            'user_id' => $request->user()?->id,
            'amount' => $request->amount,
            'currency' => $request->currency
        ]);

        $validator = Validator::make($request->all(), [
            'amount' => 'required|numeric|min:0.50',
            'currency' => 'string|in:usd,eur,gbp',
            // Shipping address validation is now optional for PaymentIntent creation
            // It will be required later during order confirmation
        ]);

        if ($validator->fails()) {
            \Log::warning('Payment intent validation failed', $validator->errors()->toArray());
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            // Get user's cart items to verify amount
            $cartItems = Cart::with(['product'])
                ->where('user_id', $request->user()->id)
                ->get();

            if ($cartItems->isEmpty()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cart is empty'
                ], 400);
            }

            // Calculate total amount from cart
            $calculatedAmount = 0;
            foreach ($cartItems as $item) {
                $price = $item->product->sale_price ?: $item->product->price;
                $calculatedAmount += $price * $item->quantity;
            }

            // Verify the amount matches (convert to cents for Stripe)
            $stripeAmount = intval($calculatedAmount * 100);
            $requestAmount = intval($request->amount * 100);

            if (abs($stripeAmount - $requestAmount) > 1) { // Allow 1 cent difference for rounding
                return response()->json([
                    'success' => false,
                    'message' => 'Amount mismatch'
                ], 400);
            }

            // Create payment intent
            $paymentIntent = PaymentIntent::create([
                'amount' => $stripeAmount,
                'currency' => $request->currency ?? 'usd',
                'metadata' => [
                    'user_id' => $request->user()->id,
                    'order_type' => 'cart_checkout'
                ],
                // Remove shipping from PaymentIntent - handle it during order creation instead
            ]);

            return response()->json([
                'success' => true,
                'data' => [
                    'client_secret' => $paymentIntent->client_secret,
                    'payment_intent_id' => $paymentIntent->id,
                    'amount' => $calculatedAmount,
                    'currency' => $paymentIntent->currency,
                ]
            ]);

        } catch (ApiErrorException $e) {
            \Log::error('Stripe API error in payment intent creation', [
                'message' => $e->getMessage(),
                'user_id' => $request->user()?->id
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Payment processing error: ' . $e->getMessage()
            ], 500);
        } catch (\Exception $e) {
            \Log::error('General error in payment intent creation', [
                'message' => $e->getMessage(),
                'user_id' => $request->user()?->id,
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while processing your request'
            ], 500);
        }
    }

    /**
     * Confirm payment and create order
     */
    public function confirmPayment(Request $request)
    {
        // Add debug logging
        \Log::info('Payment confirmation request received', [
            'user_id' => $request->user()?->id,
            'payment_intent_id' => $request->payment_intent_id,
            'has_shipping_address' => !empty($request->shipping_address)
        ]);

        $validator = Validator::make($request->all(), [
            'payment_intent_id' => 'required|string',
            'shipping_address' => 'required|array',
            'shipping_address.name' => 'required|string',
            'shipping_address.line1' => 'required|string',
            'shipping_address.line2' => 'nullable|string',
            'shipping_address.city' => 'required|string',
            'shipping_address.state' => 'nullable|string',
            'shipping_address.postal_code' => 'required|string',
            'shipping_address.country' => 'required|string|size:2',
        ]);

        if ($validator->fails()) {
            \Log::warning('Payment confirmation validation failed', $validator->errors()->toArray());
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        DB::beginTransaction();

        try {
            // Add logging before Stripe API call
            \Log::info('Attempting to retrieve payment intent from Stripe', [
                'payment_intent_id' => $request->payment_intent_id,
                'user_id' => $request->user()->id
            ]);

            // Retrieve the payment intent from Stripe
            $paymentIntent = PaymentIntent::retrieve($request->payment_intent_id);

            \Log::info('Payment intent retrieved successfully', [
                'payment_intent_id' => $paymentIntent->id,
                'status' => $paymentIntent->status,
                'amount' => $paymentIntent->amount,
                'currency' => $paymentIntent->currency
            ]);

            if ($paymentIntent->status !== 'succeeded') {
                DB::rollBack();
                \Log::warning('Payment intent not in succeeded status', [
                    'payment_intent_id' => $paymentIntent->id,
                    'current_status' => $paymentIntent->status,
                    'expected_status' => 'succeeded'
                ]);
                return response()->json([
                    'success' => false,
                    'message' => "Payment not completed. Status: {$paymentIntent->status}"
                ], 400);
            }

            // Verify the payment belongs to this user
            if ($paymentIntent->metadata->user_id != $request->user()->id) {
                DB::rollBack();
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized'
                ], 403);
            }

            // Check if order already exists for this payment intent
            $existingOrder = Order::where('payment_reference', $paymentIntent->id)->first();
            if ($existingOrder) {
                \Log::info('Order already exists for this payment intent', [
                    'payment_intent_id' => $paymentIntent->id,
                    'existing_order_id' => $existingOrder->id,
                    'order_number' => $existingOrder->order_number
                ]);
                
                // Load order with relationships for response
                $existingOrder->load(['orderItems.product', 'user']);
                
                return response()->json([
                    'success' => true,
                    'message' => 'Order already exists',
                    'data' => [
                        'order' => $existingOrder,
                        'order_number' => $existingOrder->order_number,
                    ]
                ]);
            }

            // Get user's cart items
            $cartItems = Cart::with(['product'])
                ->where('user_id', $request->user()->id)
                ->get();

            \Log::info('Cart items retrieved for order creation', [
                'user_id' => $request->user()->id,
                'cart_items_count' => $cartItems->count()
            ]);

            if ($cartItems->isEmpty()) {
                DB::rollBack();
                return response()->json([
                    'success' => false,
                    'message' => 'Cart is empty'
                ], 400);
            }

            // Calculate order totals
            $subtotal = 0;
            foreach ($cartItems as $item) {
                $price = $item->product->sale_price ?: $item->product->price;
                $subtotal += $price * $item->quantity;
            }

            $tax = $subtotal * 0.08; // 8% tax (you can make this configurable)
            $shipping = 10.00; // Fixed shipping (you can make this dynamic)
            $total = $subtotal + $tax + $shipping;

            \Log::info('Order totals calculated', [
                'subtotal' => $subtotal,
                'tax' => $tax,
                'shipping' => $shipping,
                'total' => $total
            ]);

            // Create order
            \Log::info('Attempting to create order', [
                'user_id' => $request->user()->id,
                'total_amount' => $total
            ]);

            $order = Order::create([
                'user_id' => $request->user()->id,
                'order_number' => 'ORD-' . strtoupper(uniqid()),
                'status' => 'processing',
                'subtotal' => $subtotal,
                'tax_amount' => $tax,
                'shipping_amount' => $shipping,
                'shipping_cost' => $shipping, // Keep both for compatibility
                'total_amount' => $total,
                'currency' => $paymentIntent->currency,
                'payment_status' => 'paid',
                'payment_method' => 'stripe',
                'payment_id' => $paymentIntent->id,
                'payment_reference' => $paymentIntent->id,
                'shipping_address' => json_encode($request->shipping_address),
                'billing_address' => json_encode($request->shipping_address), // Same as shipping for now
            ]);

            \Log::info('Order created successfully', [
                'order_id' => $order->id,
                'order_number' => $order->order_number
            ]);

            // Create order items
            \Log::info('Creating order items', ['order_id' => $order->id]);

            foreach ($cartItems as $cartItem) {
                $price = $cartItem->product->sale_price ?: $cartItem->product->price;
                
                \Log::info('Creating order item', [
                    'product_id' => $cartItem->product_id,
                    'product_name' => $cartItem->product->name,
                    'quantity' => $cartItem->quantity,
                    'price' => $price
                ]);
                
                // Ensure we have all required product data
                if (!$cartItem->product || !$cartItem->product->name) {
                    throw new \Exception("Product data missing for product ID: {$cartItem->product_id}");
                }
                
                OrderItem::create([
                    'order_id' => $order->id,
                    'product_id' => $cartItem->product_id,
                    'product_name' => $cartItem->product->name,
                    'product_sku' => $cartItem->product->sku ?? '',
                    'product_image' => $cartItem->product->images ? (is_array($cartItem->product->images) ? ($cartItem->product->images[0] ?? '') : $cartItem->product->images) : '',
                    'quantity' => $cartItem->quantity,
                    'price' => $price,
                    'total' => $price * $cartItem->quantity,
                    'product_options' => null, // Add this if you have product variants
                ]);

                \Log::info('Order item created successfully', [
                    'product_id' => $cartItem->product_id,
                    'product_name' => $cartItem->product->name
                ]);

                \Log::info('Updating product stock', [
                    'product_id' => $cartItem->product_id,
                    'current_stock' => $cartItem->product->stock,
                    'decrement_by' => $cartItem->quantity
                ]);

                // Update product stock
                $cartItem->product->decrement('stock', $cartItem->quantity);
            }

            \Log::info('All order items created, clearing cart');

            // Clear the cart
            Cart::where('user_id', $request->user()->id)->delete();

            DB::commit();

            // Load order with relationships for response
            $order->load(['orderItems.product', 'user']);

            return response()->json([
                'success' => true,
                'message' => 'Order created successfully',
                'data' => [
                    'order' => $order,
                    'order_number' => $order->order_number,
                ]
            ]);

        } catch (ApiErrorException $e) {
            DB::rollBack();
            \Log::error('Stripe API error in payment confirmation', [
                'message' => $e->getMessage(),
                'payment_intent_id' => $request->payment_intent_id,
                'user_id' => $request->user()->id,
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Payment verification error: ' . $e->getMessage()
            ], 500);
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('General error in payment confirmation', [
                'message' => $e->getMessage(),
                'payment_intent_id' => $request->payment_intent_id,
                'user_id' => $request->user()->id,
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while creating your order: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get payment methods for a customer
     */
    public function getPaymentMethods(Request $request)
    {
        try {
            // This would typically retrieve saved payment methods for a customer
            // For now, we'll return an empty array as we're not storing payment methods
            return response()->json([
                'success' => true,
                'data' => []
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error retrieving payment methods'
            ], 500);
        }
    }

    /**
     * Webhook endpoint for Stripe events
     */
    public function webhook(Request $request)
    {
        $payload = $request->getContent();
        $sig_header = $request->header('stripe-signature');
        $endpoint_secret = config('services.stripe.webhook_secret');

        try {
            $event = \Stripe\Webhook::constructEvent(
                $payload, $sig_header, $endpoint_secret
            );
        } catch(\UnexpectedValueException $e) {
            // Invalid payload
            return response('Invalid payload', 400);
        } catch(\Stripe\Exception\SignatureVerificationException $e) {
            // Invalid signature
            return response('Invalid signature', 400);
        }

        // Handle the event
        switch ($event->type) {
            case 'payment_intent.succeeded':
                $paymentIntent = $event->data->object;
                // Handle successful payment
                \Log::info('Payment succeeded: ' . $paymentIntent->id);
                break;
            case 'payment_intent.payment_failed':
                $paymentIntent = $event->data->object;
                // Handle failed payment
                \Log::info('Payment failed: ' . $paymentIntent->id);
                break;
            default:
                \Log::info('Received unknown event type: ' . $event->type);
        }

        return response('Webhook handled', 200);
    }
}

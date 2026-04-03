<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Cart;
use App\Models\Coupon;
use App\Http\Controllers\Api\CheckoutController;
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
     * Debug payment calculation differences
     */
    public function debugPaymentCalculation(Request $request)
    {
        \Log::info('Debug payment calculation request', [
            'user_id' => $request->user()?->id,
            'request_data' => $request->all()
        ]);

        try {
            // Get user's cart items
            $cartItems = Cart::with(['product'])
                ->where('user_id', $request->user()->id)
                ->get();

            if ($cartItems->isEmpty()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cart is empty',
                    'debug' => [
                        'cart_items_count' => 0,
                        'user_id' => $request->user()->id
                    ]
                ], 400);
            }

            // Calculate subtotal manually
            $manualSubtotal = 0;
            $cartDetails = [];
            foreach ($cartItems as $item) {
                $price = $item->product->sale_price ?: $item->product->price;
                $lineTotal = $price * $item->quantity;
                $manualSubtotal += $lineTotal;
                
                $cartDetails[] = [
                    'product_id' => $item->product->id,
                    'product_name' => $item->product->name,
                    'price' => $price,
                    'sale_price' => $item->product->sale_price,
                    'regular_price' => $item->product->price,
                    'quantity' => $item->quantity,
                    'line_total' => $lineTotal
                ];
            }

            // Use CheckoutController to calculate totals
            $checkoutController = new CheckoutController();
            $mockRequest = new Request();
            
            // Set the authenticated user
            $mockRequest->setUserResolver(function () use ($request) {
                return $request->user();
            });
            
            // Add shipping address and method if provided
            if ($request->has('shipping_address')) {
                $mockRequest->merge(['shipping_address' => $request->shipping_address]);
            }
            if ($request->has('shipping_method')) {
                $mockRequest->merge(['shipping_method' => $request->shipping_method]);
            }

            // Calculate totals using checkout system
            $totalsResponse = $checkoutController->calculateTotals($mockRequest);
            $totalsData = $totalsResponse->getData(true);

            // Prepare comprehensive debug response
            $debugResponse = [
                'success' => true,
                'debug' => [
                    'manual_calculation' => [
                        'subtotal' => $manualSubtotal,
                        'cart_items_count' => $cartItems->count(),
                        'cart_details' => $cartDetails
                    ],
                    'checkout_controller_calculation' => $totalsData,
                    'request_parameters' => [
                        'requested_amount' => $request->amount ?? null,
                        'shipping_address' => $request->shipping_address ?? null,
                        'shipping_method' => $request->shipping_method ?? null,
                        'currency' => $request->currency ?? 'usd'
                    ],
                    'comparison' => [
                        'manual_subtotal' => $manualSubtotal,
                        'checkout_subtotal' => $totalsData['success'] ? $totalsData['data']['subtotal'] : 'N/A',
                        'checkout_total' => $totalsData['success'] ? $totalsData['data']['total'] : 'N/A',
                        'requested_amount' => $request->amount ?? null,
                        'difference_from_checkout' => ($request->amount && $totalsData['success']) ? 
                            ($request->amount - $totalsData['data']['total']) : 'N/A'
                    ],
                    'user_info' => [
                        'user_id' => $request->user()->id,
                        'user_email' => $request->user()->email
                    ],
                    'timestamp' => now()->toISOString()
                ]
            ];

            return response()->json($debugResponse);

        } catch (\Exception $e) {
            \Log::error('Error in debug payment calculation', [
                'message' => $e->getMessage(),
                'user_id' => $request->user()?->id,
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error in debug calculation: ' . $e->getMessage(),
                'debug' => [
                    'error' => $e->getMessage(),
                    'user_id' => $request->user()->id
                ]
            ], 500);
        }
    }

    /**
     * Create a payment intent for checkout - simplified version that trusts frontend calculation
     */
    public function createPaymentIntentSimple(Request $request)
    {
        \Log::info('Simple payment intent request received', [
            'user_id' => $request->user()?->id,
            'amount' => $request->amount,
            'currency' => $request->currency
        ]);

        $validator = Validator::make($request->all(), [
            'amount' => 'required|numeric|min:0.50',
            'currency' => 'string|in:usd,inr',
            'shipping_address' => 'nullable|array',
            'shipping_method' => 'nullable|string|in:standard,express,overnight',
        ]);

        if ($validator->fails()) {
            \Log::warning('Simple payment intent validation failed', $validator->errors()->toArray());
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            // Convert to cents for Stripe
            $stripeAmount = intval($request->amount * 100);

            \Log::info('Creating Stripe payment intent with trusted amount', [
                'amount_dollars' => $request->amount,
                'amount_cents' => $stripeAmount,
                'currency' => $request->currency ?? 'usd',
                'user_id' => $request->user()->id
            ]);

            // Create payment intent
            $paymentIntent = PaymentIntent::create([
                'amount' => $stripeAmount,
                'currency' => $request->currency ?? 'inr',
                'metadata' => [
                    'user_id' => $request->user()->id,
                    'order_type' => 'cart_checkout_simple',
                    'calculated_by_frontend' => 'true'
                ],
            ]);

            return response()->json([
                'success' => true,
                'data' => [
                    'client_secret' => $paymentIntent->client_secret,
                    'payment_intent_id' => $paymentIntent->id,
                    'amount' => $request->amount,
                    'currency' => $paymentIntent->currency,
                ],
                'message' => 'Payment intent created successfully'
            ]);

        } catch (ApiErrorException $e) {
            \Log::error('Stripe API error in simple payment intent creation', [
                'message' => $e->getMessage(),
                'user_id' => $request->user()?->id
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Payment processing error: ' . $e->getMessage()
            ], 500);
        } catch (\Exception $e) {
            \Log::error('General error in simple payment intent creation', [
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
            'currency' => 'string|in:usd,inr',
            // Shipping address and method for accurate tax and shipping calculation
            'shipping_address' => 'nullable|array',
            'shipping_address.country' => 'required_with:shipping_address|string|size:2',
            'shipping_address.state' => 'nullable|string',
            'shipping_address.city' => 'nullable|string',
            'shipping_address.postal_code' => 'nullable|string',
            'shipping_method' => 'nullable|string|in:standard,express,overnight',
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

            // Use CheckoutController to calculate the complete total including tax and shipping
            $checkoutController = new CheckoutController();
            $mockRequest = new Request();
            
            // Set the authenticated user for the checkout calculation
            $mockRequest->setUserResolver(function () use ($request) {
                return $request->user();
            });
            
            // Add shipping address and method if provided
            if ($request->has('shipping_address')) {
                $mockRequest->merge(['shipping_address' => $request->shipping_address]);
            }
            if ($request->has('shipping_method')) {
                $mockRequest->merge(['shipping_method' => $request->shipping_method]);
            }

            // Calculate complete totals using the checkout system
            $totalsResponse = $checkoutController->calculateTotals($mockRequest);
            $totalsData = $totalsResponse->getData(true);

            if (!$totalsData['success']) {
                \Log::warning('Checkout calculation failed during payment intent', [
                    'error' => $totalsData['message'],
                    'user_id' => $request->user()->id
                ]);
                
                // Fallback to basic calculation if checkout calculation fails
                $calculatedAmount = 0;
                foreach ($cartItems as $item) {
                    $price = $item->product->sale_price ?: $item->product->price;
                    $calculatedAmount += $price * $item->quantity;
                }
                
                \Log::info('Using fallback calculation', [
                    'subtotal' => $calculatedAmount,
                    'user_id' => $request->user()->id
                ]);
            } else {
                // Use the complete total from checkout calculation
                $calculatedAmount = $totalsData['data']['total'];
                
                \Log::info('Using dynamic checkout calculation', [
                    'subtotal' => $totalsData['data']['subtotal'],
                    'tax' => $totalsData['data']['tax']['amount'],
                    'shipping' => $totalsData['data']['shipping']['cost'],
                    'total' => $calculatedAmount,
                    'user_id' => $request->user()->id
                ]);
            }

            // Verify the amount matches (convert to cents for Stripe)
            $stripeAmount = intval($calculatedAmount * 100);
            $requestAmount = intval($request->amount * 100);

            \Log::info('Amount verification', [
                'calculated_amount' => $calculatedAmount,
                'request_amount' => $request->amount,
                'stripe_amount' => $stripeAmount,
                'request_amount_cents' => $requestAmount,
                'difference' => abs($stripeAmount - $requestAmount),
                'user_id' => $request->user()->id
            ]);

            // Allow more flexibility in amount matching - use the higher amount for security
            $amountDifference = abs($stripeAmount - $requestAmount);
            $percentageDifference = $calculatedAmount > 0 ? (abs($calculatedAmount - $request->amount) / $calculatedAmount) * 100 : 0;
            
            \Log::info('Amount difference analysis', [
                'amount_difference_cents' => $amountDifference,
                'amount_difference_dollars' => $amountDifference / 100,
                'percentage_difference' => $percentageDifference,
                'calculated_amount' => $calculatedAmount,
                'request_amount' => $request->amount,
                'user_id' => $request->user()->id
            ]);
            
            // More intelligent amount verification:
            // Check if amount verification is disabled (for development/testing)
            $skipAmountVerification = config('app.skip_payment_amount_verification', false);
            
            if ($skipAmountVerification) {
                \Log::info('Amount verification skipped (disabled in config)', [
                    'calculated_amount' => $calculatedAmount,
                    'request_amount' => $request->amount,
                    'user_id' => $request->user()->id
                ]);
                $finalAmount = $requestAmount; // Use frontend amount when verification is disabled
            } else {
                // Normal verification logic
                // 1. Allow small absolute differences (up to $10)
                // 2. Allow small percentage differences (up to 10%)
                // 3. For large differences, provide detailed debugging
                if ($amountDifference > 1000 && $percentageDifference > 10) { // $10 OR 10% difference
                    
                    // Provide detailed debugging for large differences
                    $debugInfo = [
                        'calculated_amount' => $calculatedAmount,
                        'request_amount' => $request->amount,
                        'difference' => ($requestAmount - $stripeAmount) / 100,
                        'percentage_difference' => round($percentageDifference, 2) . '%',
                        'note' => 'Significant amount mismatch detected',
                        'suggestions' => [
                            'Use the simple payment intent endpoint: /payments/create-intent-simple',
                            'Ensure cart contents are the same between calculation and payment',
                            'Check if shipping address/method are identical',
                            'Verify tax rates are consistent',
                            'Use the debug endpoint: /payments/debug/calculation'
                        ]
                    ];
                    
                    // Log detailed information for debugging
                    \Log::warning('Large payment amount mismatch', array_merge($debugInfo, [
                        'user_id' => $request->user()->id,
                        'cart_items_count' => $cartItems->count(),
                        'shipping_address' => $request->shipping_address,
                        'shipping_method' => $request->shipping_method
                    ]));
                    
                    return response()->json([
                        'success' => false,
                        'message' => 'Amount mismatch - significant difference detected',
                        'debug' => $debugInfo
                    ], 400);
                } else if ($amountDifference > 500) {
                    // Medium difference: Log warning but proceed with higher amount
                    \Log::warning('Medium payment amount difference - proceeding with higher amount', [
                        'calculated_amount' => $calculatedAmount,
                        'request_amount' => $request->amount,
                        'difference_dollars' => $amountDifference / 100,
                        'percentage_difference' => $percentageDifference,
                        'user_id' => $request->user()->id
                    ]);
                }
                
                // Use the higher amount for security (protect against underpayment)
                $finalAmount = max($stripeAmount, $requestAmount);
            }
            
            \Log::info('Using final payment amount', [
                'calculated_amount' => $calculatedAmount,
                'request_amount' => $request->amount,
                'final_amount_cents' => $finalAmount,
                'final_amount_dollars' => $finalAmount / 100,
                'user_id' => $request->user()->id
            ]);

            // Create payment intent
            $paymentIntent = PaymentIntent::create([
                'amount' => $finalAmount,
                'currency' => $request->currency ?? 'inr',
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
                    'amount' => $finalAmount / 100,
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
            'shipping_address.country' => 'required|string',
            'coupon_code' => 'nullable|string',
            'cart_items' => 'nullable|array|min:1',
            'cart_items.*.product_id' => 'required_with:cart_items|integer|exists:products,id',
            'cart_items.*.quantity' => 'required_with:cart_items|integer|min:1',
            'cart_items.*.price' => 'required_with:cart_items|numeric|min:0',
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

            // ── Resolve cart items ────────────────────────────────────────────
            // Prefer cart_items sent directly in the request body; fall back to
            // the user's DB cart so the old flow still works.
            $useRequestItems = $request->has('cart_items') && !empty($request->cart_items);

            if ($useRequestItems) {
                // ── Path A: frontend sent cart_items in the request body ──────
                $requestItems = collect($request->cart_items);

                // Load products in one query for names / sku / images / stock
                $products = \App\Models\Product::whereIn('id', $requestItems->pluck('product_id'))
                    ->get()->keyBy('id');

                $subtotal = $requestItems->sum(fn($i) => $i['price'] * $i['quantity']);
                $tax      = 0;
                $shipping = 0;
                $total    = $subtotal;

                \Log::info('Using cart_items from request body', [
                    'user_id'    => $request->user()->id,
                    'item_count' => $requestItems->count(),
                    'subtotal'   => $subtotal,
                ]);

            } else {
                // ── Path B: fetch from DB cart ────────────────────────────────
                $dbCartItems = Cart::with(['product'])
                    ->where('user_id', $request->user()->id)
                    ->get();

                \Log::info('Using DB cart', [
                    'user_id'    => $request->user()->id,
                    'item_count' => $dbCartItems->count(),
                ]);

                if ($dbCartItems->isEmpty()) {
                    DB::rollBack();
                    return response()->json([
                        'success' => false,
                        'message' => 'Cart is empty. Please provide cart_items in the request or ensure your cart is not empty.',
                    ], 400);
                }

                // Calculate totals via CheckoutController
                $checkoutController = new \App\Http\Controllers\Api\CheckoutController();
                $totalsRequest = new \Illuminate\Http\Request();
                $totalsRequest->setUserResolver(fn() => $request->user());

                $totalsResponse = $checkoutController->calculateTotals($totalsRequest);
                $totalsData     = $totalsResponse->getData(true);

                if (!$totalsData['success']) {
                    DB::rollBack();
                    return response()->json([
                        'success' => false,
                        'message' => 'Error calculating order totals: ' . $totalsData['message'],
                    ], 400);
                }

                $subtotal = $totalsData['data']['subtotal'];
                $tax      = $totalsData['data']['tax']['amount'];
                $shipping = $totalsData['data']['shipping']['cost'];
                $total    = $totalsData['data']['total'];
            }

            // ── Apply coupon ──────────────────────────────────────────────────
            $discountAmount = 0;
            $couponCode     = null;
            if ($request->coupon_code) {
                $coupon = Coupon::where('code', strtoupper($request->coupon_code))->first();
                if ($coupon && $coupon->isUsable($request->user()->id)) {
                    if (!$coupon->minimum_amount || $subtotal >= $coupon->minimum_amount) {
                        $discountAmount = $coupon->calculateDiscount($subtotal);
                        $couponCode     = $coupon->code;
                        $total          = max(0, $total - $discountAmount);
                    }
                }
            }

            \Log::info('Order totals resolved', [
                'subtotal' => $subtotal,
                'tax'      => $tax,
                'shipping' => $shipping,
                'total'    => $total,
                'discount' => $discountAmount,
            ]);

            // ── Create order ──────────────────────────────────────────────────
            \Log::info('Attempting to create order', [
                'user_id'      => $request->user()->id,
                'total_amount' => $total,
            ]);

            $order = Order::create([
                'user_id'          => $request->user()->id,
                'order_number'     => 'ORD-' . strtoupper(uniqid()),
                'status'           => 'confirmed',
                'subtotal'         => $subtotal,
                'tax_amount'       => $tax,
                'shipping_amount'  => $shipping,
                'shipping_cost'    => $shipping,
                'total_amount'     => $total,
                'currency'         => $paymentIntent->currency,
                'payment_status'   => 'paid',
                'payment_method'   => 'stripe',
                'payment_id'       => $paymentIntent->id,
                'payment_reference'=> $paymentIntent->id,
                'shipping_address' => json_encode($request->shipping_address),
                'billing_address'  => json_encode($request->shipping_address),
                'discount_amount'  => $discountAmount,
                'coupon_code'      => $couponCode,
            ]);

            \Log::info('Order created successfully', [
                'order_id'     => $order->id,
                'order_number' => $order->order_number,
            ]);

            // ── Create order items ────────────────────────────────────────────
            \Log::info('Creating order items', ['order_id' => $order->id]);

            if ($useRequestItems) {
                foreach ($requestItems as $item) {
                    $product = $products->get($item['product_id']);
                    if (!$product) {
                        throw new \Exception("Product not found for ID: {$item['product_id']}");
                    }

                    OrderItem::create([
                        'order_id'      => $order->id,
                        'product_id'    => $item['product_id'],
                        'product_name'  => $product->name,
                        'product_sku'   => $product->sku ?? '',
                        'product_image' => $product->images
                            ? (is_array($product->images) ? ($product->images[0] ?? '') : $product->images)
                            : '',
                        'quantity'      => $item['quantity'],
                        'price'         => $item['price'],
                        'total'         => $item['price'] * $item['quantity'],
                        'product_options' => null,
                    ]);

                    // Decrement stock
                    $product->decrement('stock', $item['quantity']);
                }
            } else {
                foreach ($dbCartItems as $cartItem) {
                    if (!$cartItem->product || !$cartItem->product->name) {
                        throw new \Exception("Product data missing for product ID: {$cartItem->product_id}");
                    }

                    $price = $cartItem->product->sale_price ?: $cartItem->product->price;

                    OrderItem::create([
                        'order_id'      => $order->id,
                        'product_id'    => $cartItem->product_id,
                        'product_name'  => $cartItem->product->name,
                        'product_sku'   => $cartItem->product->sku ?? '',
                        'product_image' => $cartItem->product->images
                            ? (is_array($cartItem->product->images) ? ($cartItem->product->images[0] ?? '') : $cartItem->product->images)
                            : '',
                        'quantity'      => $cartItem->quantity,
                        'price'         => $price,
                        'total'         => $price * $cartItem->quantity,
                        'product_options' => null,
                    ]);

                    $cartItem->product->decrement('stock', $cartItem->quantity);
                }
            }

            \Log::info('All order items created, clearing cart');

            // Clear the DB cart regardless of which path was used
            Cart::where('user_id', $request->user()->id)->delete();

            // Increment coupon usage count
            if ($couponCode) {
                Coupon::where('code', $couponCode)->increment('used_count');
            }

            DB::commit();

            // Load order with relationships for response
            $order->load(['orderItems.product', 'user']);

            return response()->json([
                'success' => true,
                'message' => 'Order created successfully',
                'data' => [
                    'order'        => $order,
                    'order_number' => $order->order_number,
                    'order_id'     => $order->id,
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

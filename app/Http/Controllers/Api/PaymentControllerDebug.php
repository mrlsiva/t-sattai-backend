<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Cart;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Stripe\Stripe;
use Stripe\PaymentIntent;
use Stripe\Exception\ApiErrorException;

class PaymentControllerDebug extends Controller
{
    public function __construct()
    {
        // Set your secret key. Remember to switch to your live secret key in production.
        Stripe::setApiKey(config('services.stripe.secret'));
    }

    /**
     * Debug endpoint to show payment requirements and current state
     */
    public function debugPaymentRequirements(Request $request)
    {
        try {
            $user = $request->user();
            
            // Get user's cart
            $cartItems = Cart::with(['product'])
                ->where('user_id', $user->id)
                ->get();

            // Calculate cart totals
            $subtotal = 0;
            $cartDetails = [];
            
            foreach ($cartItems as $item) {
                $price = $item->product->sale_price ?: $item->product->price;
                $itemTotal = $price * $item->quantity;
                $subtotal += $itemTotal;
                
                $cartDetails[] = [
                    'product_id' => $item->product_id,
                    'product_name' => $item->product->name,
                    'quantity' => $item->quantity,
                    'unit_price' => $price,
                    'line_total' => $itemTotal,
                    'stock_available' => $item->product->stock,
                    'has_sufficient_stock' => $item->product->stock >= $item->quantity
                ];
            }

            $tax = $subtotal * 0.08; // 8% tax
            $shipping = 10.00; // Fixed shipping
            $total = $subtotal + $tax + $shipping;

            // Check Stripe configuration
            $stripeConfigured = !empty(config('services.stripe.secret')) && !empty(config('services.stripe.key'));

            return response()->json([
                'success' => true,
                'debug_info' => [
                    'user' => [
                        'id' => $user->id,
                        'name' => $user->name,
                        'email' => $user->email
                    ],
                    'cart' => [
                        'items_count' => $cartItems->count(),
                        'is_empty' => $cartItems->isEmpty(),
                        'items' => $cartDetails,
                        'totals' => [
                            'subtotal' => $subtotal,
                            'tax' => $tax,
                            'shipping' => $shipping,
                            'total' => $total,
                            'stripe_amount_cents' => intval($total * 100)
                        ]
                    ],
                    'configuration' => [
                        'stripe_configured' => $stripeConfigured,
                        'stripe_key_present' => !empty(config('services.stripe.key')),
                        'stripe_secret_present' => !empty(config('services.stripe.secret')),
                        'webhook_secret_present' => !empty(config('services.stripe.webhook_secret'))
                    ],
                    'payment_intent_requirements' => [
                        'amount' => [
                            'required' => true,
                            'type' => 'numeric',
                            'minimum' => 0.50,
                            'note' => 'Must match cart total within 1 cent'
                        ],
                        'currency' => [
                            'required' => false,
                            'type' => 'string',
                            'allowed_values' => ['usd', 'eur', 'gbp'],
                            'default' => 'usd'
                        ]
                    ],
                    'payment_confirmation_requirements' => [
                        'payment_intent_id' => [
                            'required' => true,
                            'type' => 'string',
                            'note' => 'Must be in succeeded status'
                        ],
                        'shipping_address' => [
                            'required' => true,
                            'type' => 'object',
                            'fields' => [
                                'name' => 'required|string',
                                'line1' => 'required|string',
                                'line2' => 'nullable|string',
                                'city' => 'required|string',
                                'state' => 'nullable|string',
                                'postal_code' => 'required|string',
                                'country' => 'required|string|size:2'
                            ]
                        ]
                    ]
                ],
                'message' => 'Payment requirements and current state retrieved successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error retrieving debug information',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Create a payment intent with enhanced debugging
     */
    public function createPaymentIntentDebug(Request $request)
    {
        // Enhanced debug logging
        \Log::info('=== PAYMENT INTENT CREATION DEBUG ===', [
            'timestamp' => now()->toISOString(),
            'user_id' => $request->user()?->id,
            'request_data' => $request->all(),
            'headers' => [
                'content_type' => $request->header('Content-Type'),
                'authorization' => $request->header('Authorization') ? 'Bearer ***' : 'Missing'
            ]
        ]);

        // Step 1: Validate input
        $validator = Validator::make($request->all(), [
            'amount' => 'required|numeric|min:0.50',
            'currency' => 'string|in:usd,eur,gbp,inr,cad,aud',
        ]);

        if ($validator->fails()) {
            \Log::warning('Payment intent validation failed', [
                'errors' => $validator->errors()->toArray(),
                'input' => $request->all()
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors(),
                'debug' => [
                    'step' => 'input_validation',
                    'input_received' => $request->all()
                ]
            ], 422);
        }

        try {
            // Step 2: Check Stripe configuration
            $stripeSecret = config('services.stripe.secret');
            if (empty($stripeSecret)) {
                \Log::error('Stripe secret key not configured');
                return response()->json([
                    'success' => false,
                    'message' => 'Payment system not configured',
                    'debug' => [
                        'step' => 'stripe_configuration',
                        'issue' => 'stripe_secret_missing'
                    ]
                ], 500);
            }

            // Step 3: Get and validate cart
            $cartItems = Cart::with(['product'])
                ->where('user_id', $request->user()->id)
                ->get();

            \Log::info('Cart items retrieved', [
                'user_id' => $request->user()->id,
                'cart_items_count' => $cartItems->count(),
                'cart_items' => $cartItems->map(function($item) {
                    return [
                        'id' => $item->id,
                        'product_id' => $item->product_id,
                        'product_name' => $item->product->name ?? 'Unknown',
                        'quantity' => $item->quantity,
                        'product_price' => $item->product->price ?? 0,
                        'product_sale_price' => $item->product->sale_price ?? null,
                        'product_stock' => $item->product->stock ?? 0
                    ];
                })
            ]);

            if ($cartItems->isEmpty()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cart is empty',
                    'debug' => [
                        'step' => 'cart_validation',
                        'user_id' => $request->user()->id,
                        'cart_items_count' => 0
                    ]
                ], 400);
            }

            // Step 4: Calculate and verify amount
            $calculatedAmount = 0;
            $stockIssues = [];
            
            foreach ($cartItems as $item) {
                if (!$item->product) {
                    return response()->json([
                        'success' => false,
                        'message' => "Product not found for cart item {$item->id}",
                        'debug' => [
                            'step' => 'product_validation',
                            'cart_item_id' => $item->id,
                            'product_id' => $item->product_id
                        ]
                    ], 400);
                }

                $price = $item->product->sale_price ?: $item->product->price;
                $calculatedAmount += $price * $item->quantity;

                // Check stock
                if ($item->product->stock < $item->quantity) {
                    $stockIssues[] = [
                        'product_id' => $item->product_id,
                        'product_name' => $item->product->name,
                        'requested' => $item->quantity,
                        'available' => $item->product->stock
                    ];
                }
            }

            if (!empty($stockIssues)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Insufficient stock for some items',
                    'debug' => [
                        'step' => 'stock_validation',
                        'stock_issues' => $stockIssues
                    ]
                ], 400);
            }

            // Step 5: Verify amount matches
            $stripeAmount = intval($calculatedAmount * 100);
            $requestAmount = intval($request->amount * 100);
            $amountDifference = abs($stripeAmount - $requestAmount);

            \Log::info('Amount verification', [
                'calculated_amount' => $calculatedAmount,
                'request_amount' => $request->amount,
                'stripe_amount_cents' => $stripeAmount,
                'request_amount_cents' => $requestAmount,
                'difference_cents' => $amountDifference
            ]);

            if ($amountDifference > 1) { // Allow 1 cent difference for rounding
                return response()->json([
                    'success' => false,
                    'message' => 'Amount mismatch between cart and request',
                    'debug' => [
                        'step' => 'amount_verification',
                        'calculated_amount' => $calculatedAmount,
                        'request_amount' => $request->amount,
                        'difference' => abs($calculatedAmount - $request->amount)
                    ]
                ], 400);
            }

            // Step 6: Create payment intent
            \Log::info('Creating Stripe payment intent', [
                'amount_cents' => $stripeAmount,
                'currency' => $request->currency ?? 'usd',
                'user_id' => $request->user()->id
            ]);

            $paymentIntent = PaymentIntent::create([
                'amount' => $stripeAmount,
                'currency' => $request->currency ?? 'usd',
                'metadata' => [
                    'user_id' => $request->user()->id,
                    'order_type' => 'cart_checkout',
                    'cart_items_count' => $cartItems->count()
                ],
            ]);

            \Log::info('Payment intent created successfully', [
                'payment_intent_id' => $paymentIntent->id,
                'status' => $paymentIntent->status,
                'amount' => $paymentIntent->amount,
                'currency' => $paymentIntent->currency
            ]);

            return response()->json([
                'success' => true,
                'data' => [
                    'client_secret' => $paymentIntent->client_secret,
                    'payment_intent_id' => $paymentIntent->id,
                    'amount' => $calculatedAmount,
                    'currency' => $paymentIntent->currency,
                    'status' => $paymentIntent->status
                ],
                'debug' => [
                    'cart_items_count' => $cartItems->count(),
                    'calculated_amount' => $calculatedAmount,
                    'stripe_amount_cents' => $stripeAmount
                ]
            ]);

        } catch (ApiErrorException $e) {
            \Log::error('Stripe API error in payment intent creation', [
                'message' => $e->getMessage(),
                'user_id' => $request->user()?->id,
                'stripe_error_type' => $e->getError()->type ?? 'unknown',
                'stripe_error_code' => $e->getError()->code ?? 'unknown'
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Payment processing error: ' . $e->getMessage(),
                'debug' => [
                    'step' => 'stripe_api_call',
                    'error_type' => 'stripe_api_error',
                    'stripe_error_type' => $e->getError()->type ?? 'unknown'
                ]
            ], 500);
        } catch (\Exception $e) {
            \Log::error('General error in payment intent creation', [
                'message' => $e->getMessage(),
                'user_id' => $request->user()?->id,
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while processing your request',
                'debug' => [
                    'step' => 'unexpected_error',
                    'error_message' => $e->getMessage(),
                    'file' => basename($e->getFile()),
                    'line' => $e->getLine()
                ]
            ], 500);
        }
    }

    /**
     * Test endpoint to simulate payment intent status
     */
    public function simulatePaymentIntent(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'payment_intent_id' => 'required|string',
            'status' => 'required|in:requires_payment_method,requires_confirmation,requires_action,processing,requires_capture,canceled,succeeded'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        // This is a simulation for testing - in real implementation, you'd check Stripe
        return response()->json([
            'success' => true,
            'data' => [
                'payment_intent_id' => $request->payment_intent_id,
                'status' => $request->status,
                'amount' => 15998, // Example amount in cents
                'currency' => 'usd',
                'metadata' => [
                    'user_id' => $request->user()->id,
                    'order_type' => 'cart_checkout'
                ]
            ],
            'message' => 'Payment intent status simulated'
        ]);
    }
}
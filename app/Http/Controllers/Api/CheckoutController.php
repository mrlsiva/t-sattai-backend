<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Cart;
use App\Models\Address;
use App\Models\Product;
use App\Models\TaxRate;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class CheckoutController extends Controller
{
    /**
     * Calculate checkout totals including tax and shipping
     */
    public function calculateTotals(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'shipping_address' => 'nullable|array',
            'shipping_address.country' => 'nullable|string|size:2',
            'shipping_address.state' => 'nullable|string',
            'shipping_address.postal_code' => 'nullable|string',
            'shipping_address.city' => 'nullable|string',
            'shipping_method' => 'nullable|string|in:standard,express,overnight',
            'cart_items' => 'nullable|array', // Optional: use specific cart items instead of user's cart
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $user = $request->user();
            
            // Get cart items (either from request or user's cart)
            if ($request->has('cart_items')) {
                $cartItems = collect($request->cart_items);
                $products = Product::whereIn('id', $cartItems->pluck('product_id'))->get()->keyBy('id');
                
                $cartData = $cartItems->map(function ($item) use ($products) {
                    $product = $products->get($item['product_id']);
                    if (!$product) {
                        throw new \Exception("Product not found: {$item['product_id']}");
                    }
                    
                    return (object) [
                        'product_id' => $product->id,
                        'quantity' => $item['quantity'],
                        'product' => $product
                    ];
                });
            } else {
                $cartData = Cart::with(['product'])
                    ->where('user_id', $user->id)
                    ->get();
            }

            if ($cartData->isEmpty()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cart is empty'
                ], 400);
            }

            // Calculate subtotal and get cart details
            $subtotal = 0;
            $totalWeight = 0;
            $cartDetails = [];
            
            foreach ($cartData as $item) {
                $product = $item->product;
                $price = $product->sale_price ?: $product->price;
                $lineTotal = $price * $item->quantity;
                $subtotal += $lineTotal;
                
                // Add weight for shipping calculation
                $weight = $product->weight ?? 0;
                $totalWeight += $weight * $item->quantity;
                
                $cartDetails[] = [
                    'product_id' => $product->id,
                    'name' => $product->name,
                    'price' => $price,
                    'quantity' => $item->quantity,
                    'weight' => $weight,
                    'line_total' => $lineTotal
                ];
            }

            // Get shipping address (from request, user's default address, or null)
            $shippingAddress = $this->getShippingAddress($request, $user);
            
            // Calculate tax
            $taxInfo = $this->calculateTax($subtotal, $shippingAddress);
            
            // Calculate shipping
            $shippingInfo = $this->calculateShipping(
                $cartData, 
                $totalWeight, 
                $shippingAddress, 
                $request->shipping_method ?? 'standard'
            );
            
            // Calculate total
            $total = $subtotal + $taxInfo['amount'] + $shippingInfo['cost'];

            return response()->json([
                'success' => true,
                'data' => [
                    'subtotal' => round($subtotal, 2),
                    'tax' => [
                        'amount' => round($taxInfo['amount'], 2),
                        'rate' => $taxInfo['rate'],
                        'description' => $taxInfo['description']
                    ],
                    'shipping' => [
                        'cost' => round($shippingInfo['cost'], 2),
                        'method' => $shippingInfo['method'],
                        'description' => $shippingInfo['description'],
                        'estimated_days' => $shippingInfo['estimated_days']
                    ],
                    'total' => round($total, 2),
                    'currency' => 'USD',
                    'breakdown' => [
                        'cart_items' => $cartDetails,
                        'items_count' => $cartData->count(),
                        'total_weight' => $totalWeight,
                        'shipping_address' => $shippingAddress
                    ]
                ],
                'message' => 'Checkout totals calculated successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error calculating checkout totals: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get available shipping methods for the cart
     */
    public function getShippingMethods(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'shipping_address' => 'nullable|array',
            'shipping_address.country' => 'nullable|string|size:2',
            'shipping_address.state' => 'nullable|string',
            'shipping_address.postal_code' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $user = $request->user();
            $cartItems = Cart::with(['product'])->where('user_id', $user->id)->get();
            
            if ($cartItems->isEmpty()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cart is empty'
                ], 400);
            }

            // Calculate total weight
            $totalWeight = 0;
            foreach ($cartItems as $item) {
                $weight = $item->product->weight ?? 0;
                $totalWeight += $weight * $item->quantity;
            }

            $shippingAddress = $this->getShippingAddress($request, $user);
            $methods = $this->getAvailableShippingMethods($cartItems, $totalWeight, $shippingAddress);

            return response()->json([
                'success' => true,
                'data' => $methods,
                'message' => 'Shipping methods retrieved successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error retrieving shipping methods: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get tax rates for a location
     */
    public function getTaxRates(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'country' => 'required|string|size:2',
            'state' => 'nullable|string',
            'postal_code' => 'nullable|string',
            'city' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $address = $request->only(['country', 'state', 'postal_code', 'city']);
            $taxInfo = $this->getTaxRateForLocation($address);

            return response()->json([
                'success' => true,
                'data' => [
                    'rate' => $taxInfo['rate'],
                    'description' => $taxInfo['description'],
                    'location' => $address
                ],
                'message' => 'Tax rates retrieved successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error retrieving tax rates: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Calculate tax based on location
     */
    private function calculateTax($subtotal, $shippingAddress)
    {
        if (!$shippingAddress) {
            // Default tax rate if no address provided
            return [
                'amount' => $subtotal * 0.08,
                'rate' => 0.08,
                'description' => 'Default tax rate (8%)'
            ];
        }

        $taxInfo = $this->getTaxRateForLocation($shippingAddress);
        
        return [
            'amount' => $subtotal * $taxInfo['rate'],
            'rate' => $taxInfo['rate'],
            'description' => $taxInfo['description']
        ];
    }

    /**
     * Get tax rate for a specific location using database
     */
    private function getTaxRateForLocation($address)
    {
        try {
            $country = $address['country'] ?? '';
            $state = $address['state'] ?? '';
            $city = $address['city'] ?? '';
            $postalCode = $address['postal_code'] ?? '';

            // Use the TaxRate model to find the best matching rate
            $taxRate = TaxRate::findForLocation($country, $state, $city, $postalCode);

            if ($taxRate && $taxRate->isCurrentlyEffective()) {
                return [
                    'rate' => $taxRate->rate,
                    'description' => $taxRate->description ?: $taxRate->name,
                    'type' => $taxRate->type,
                    'name' => $taxRate->name,
                    'tax_rate_id' => $taxRate->id
                ];
            }

            // Fallback: No tax if no rate found
            return [
                'rate' => 0.00,
                'description' => 'No tax applicable',
                'type' => 'none',
                'name' => 'No Tax',
                'tax_rate_id' => null
            ];

        } catch (\Exception $e) {
            // Log error and return no tax as fallback
            \Log::error('Tax calculation error: ' . $e->getMessage(), [
                'address' => $address
            ]);

            return [
                'rate' => 0.00,
                'description' => 'Tax calculation error - no tax applied',
                'type' => 'error',
                'name' => 'Error',
                'tax_rate_id' => null
            ];
        }
    }

    /**
     * Calculate shipping based on weight, location, and method
     */
    private function calculateShipping($cartItems, $totalWeight, $shippingAddress, $method)
    {
        $shippingMethods = $this->getAvailableShippingMethods($cartItems, $totalWeight, $shippingAddress);
        
        $selectedMethod = collect($shippingMethods)->firstWhere('id', $method);
        
        if (!$selectedMethod) {
            // Default to standard shipping if method not found
            $selectedMethod = collect($shippingMethods)->firstWhere('id', 'standard');
        }

        return [
            'cost' => $selectedMethod['cost'],
            'method' => $selectedMethod['id'],
            'description' => $selectedMethod['name'],
            'estimated_days' => $selectedMethod['estimated_days']
        ];
    }

    /**
     * Get available shipping methods
     */
    private function getAvailableShippingMethods($cartItems, $totalWeight, $shippingAddress)
    {
        $country = $shippingAddress['country'] ?? 'US';
        $isInternational = !in_array(strtoupper($country), ['US', 'USA']);
        
        // Base shipping cost calculation
        $baseCost = 5.00; // Base shipping
        $weightCost = max(0, ($totalWeight - 1) * 2.00); // $2 per lb over 1 lb
        
        $methods = [
            [
                'id' => 'standard',
                'name' => $isInternational ? 'International Standard' : 'Standard Shipping',
                'cost' => $baseCost + $weightCost + ($isInternational ? 15.00 : 0),
                'estimated_days' => $isInternational ? '7-14' : '3-7',
                'description' => $isInternational ? 'International delivery' : 'Standard ground shipping'
            ],
            [
                'id' => 'express',
                'name' => $isInternational ? 'International Express' : 'Express Shipping',
                'cost' => ($baseCost + $weightCost) * 2 + ($isInternational ? 25.00 : 5.00),
                'estimated_days' => $isInternational ? '3-7' : '1-3',
                'description' => $isInternational ? 'Fast international delivery' : 'Express delivery'
            ]
        ];

        // Add overnight only for domestic US shipments
        if (!$isInternational) {
            $methods[] = [
                'id' => 'overnight',
                'name' => 'Overnight Shipping',
                'cost' => ($baseCost + $weightCost) * 3 + 15.00,
                'estimated_days' => '1',
                'description' => 'Next business day delivery'
            ];
        }

        return $methods;
    }

    /**
     * Get shipping address from request or user's default
     */
    private function getShippingAddress(Request $request, $user)
    {
        if ($request->has('shipping_address')) {
            return $request->shipping_address;
        }

        // Try to get user's default shipping address
        $defaultAddress = $user->addresses()
            ->where(function($query) {
                $query->where('type', 'shipping')
                      ->orWhere('type', 'both');
            })
            ->where('is_default', true)
            ->first();

        if ($defaultAddress) {
            return [
                'country' => $defaultAddress->country,
                'state' => $defaultAddress->state,
                'postal_code' => $defaultAddress->postal_code,
                'city' => $defaultAddress->city
            ];
        }

        return null;
    }
}
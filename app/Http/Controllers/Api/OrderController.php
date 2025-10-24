<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;

class OrderController extends Controller
{
    /**
     * Get orders for authenticated user
     */
    public function index(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'page' => 'integer|min:1',
            'limit' => 'integer|min:1|max:100',
            'status' => 'string|in:pending,confirmed,processing,shipped,delivered,cancelled',
            'search' => 'string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        $query = Order::with(['orderItems.product', 'user'])
            ->where('user_id', $request->user()->id);

        // Apply filters
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('order_number', 'like', "%{$search}%")
                  ->orWhere('payment_reference', 'like', "%{$search}%");
            });
        }

        // Pagination
        $limit = $request->get('limit', 10);
        $orders = $query->orderBy('created_at', 'desc')->paginate($limit);

        // Transform the data to match frontend expectations
        $transformedOrders = $orders->getCollection()->map(function ($order) {
            return [
                'id' => $order->order_number,
                'user' => [
                    'id' => $order->user->id,
                    'name' => $order->user->name,
                    'email' => $order->user->email,
                ],
                'items' => $order->orderItems->map(function ($item) {
                    return [
                        'id' => $item->id,
                        'product_id' => $item->product_id,
                        'product_name' => $item->product_name,
                        'quantity' => $item->quantity,
                        'price' => (float) $item->price,
                        'total' => (float) $item->total,
                        'product' => $item->product ? [
                            'id' => $item->product->id,
                            'name' => $item->product->name,
                            'images' => $item->product->images,
                        ] : null
                    ];
                }),
                'orderStatus' => $order->status,
                'paymentStatus' => $order->payment_status,
                'total' => (float) $order->total_amount,
                'subtotal' => (float) $order->subtotal,
                'tax' => (float) $order->tax_amount,
                'shipping' => (float) ($order->shipping_amount ?? $order->shipping_cost),
                'createdAt' => $order->created_at->toISOString(),
                'updatedAt' => $order->updated_at->toISOString(),
                'shippingAddress' => $order->shipping_address,
                'paymentReference' => $order->payment_reference,
            ];
        });

        return response()->json([
            'success' => true,
            'data' => $transformedOrders,
            'pagination' => [
                'current_page' => $orders->currentPage(),
                'per_page' => $orders->perPage(),
                'total' => $orders->total(),
                'last_page' => $orders->lastPage(),
            ],
            'message' => 'Orders retrieved successfully'
        ]);
    }

    /**
     * Get specific order for authenticated user
     */
    public function show(Request $request, $orderNumber)
    {
        $order = Order::with(['orderItems.product', 'user'])
            ->where('user_id', $request->user()->id)
            ->where('order_number', $orderNumber)
            ->first();

        if (!$order) {
            return response()->json([
                'success' => false,
                'message' => 'Order not found'
            ], 404);
        }

        $transformedOrder = [
            'id' => $order->order_number,
            'user' => [
                'id' => $order->user->id,
                'name' => $order->user->name,
                'email' => $order->user->email,
            ],
            'items' => $order->orderItems->map(function ($item) {
                return [
                    'id' => $item->id,
                    'product_id' => $item->product_id,
                    'product_name' => $item->product_name,
                    'quantity' => $item->quantity,
                    'price' => (float) $item->price,
                    'total' => (float) $item->total,
                    'product' => $item->product ? [
                        'id' => $item->product->id,
                        'name' => $item->product->name,
                        'images' => $item->product->images,
                    ] : null
                ];
            }),
            'orderStatus' => $order->status,
            'paymentStatus' => $order->payment_status,
            'total' => (float) $order->total_amount,
            'subtotal' => (float) $order->subtotal,
            'tax' => (float) $order->tax_amount,
            'shipping' => (float) ($order->shipping_amount ?? $order->shipping_cost),
            'createdAt' => $order->created_at->toISOString(),
            'updatedAt' => $order->updated_at->toISOString(),
            'shippingAddress' => $order->shipping_address,
            'paymentReference' => $order->payment_reference,
        ];

        return response()->json([
            'success' => true,
            'data' => $transformedOrder,
            'message' => 'Order retrieved successfully'
        ]);
    }
}

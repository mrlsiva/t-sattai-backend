<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;

class OrderController extends Controller
{
    /**
     * Get all orders for admin
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

        $query = Order::with(['orderItems.product', 'user']);

        // Apply filters
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('order_number', 'like', "%{$search}%")
                  ->orWhere('payment_reference', 'like', "%{$search}%")
                  ->orWhereHas('user', function ($userQuery) use ($search) {
                      $userQuery->where('name', 'like', "%{$search}%")
                               ->orWhere('email', 'like', "%{$search}%");
                  });
            });
        }

        // Pagination
        $limit = $request->get('limit', 15);
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
     * Get order statistics for admin dashboard
     */
    public function stats()
    {
        $stats = [
            'total' => Order::count(),
            'pending' => Order::where('status', 'pending')->count(),
            'confirmed' => Order::where('status', 'confirmed')->count(),
            'processing' => Order::where('status', 'processing')->count(),
            'shipped' => Order::where('status', 'shipped')->count(),
            'delivered' => Order::where('status', 'delivered')->count(),
            'cancelled' => Order::where('status', 'cancelled')->count(),
            'totalValue' => (float) Order::where('payment_status', 'paid')->sum('total_amount'),
        ];

        return response()->json([
            'success' => true,
            'data' => $stats,
            'message' => 'Order statistics retrieved successfully'
        ]);
    }

    /**
     * Update order status
     */
    public function updateStatus(Request $request, $orderNumber)
    {
        $validator = Validator::make($request->all(), [
            'status' => 'required|string|in:pending,confirmed,processing,shipped,delivered,cancelled',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        $order = Order::with(['orderItems.product', 'user'])
            ->where('order_number', $orderNumber)
            ->first();

        if (!$order) {
            return response()->json([
                'success' => false,
                'message' => 'Order not found'
            ], 404);
        }

        $oldStatus = $order->status;
        $order->update(['status' => $request->status]);

        // Log the status change
        \Log::info('Order status updated', [
            'order_number' => $order->order_number,
            'old_status' => $oldStatus,
            'new_status' => $request->status,
            'updated_by' => $request->user()->id
        ]);

        // Transform the updated order
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
            'message' => 'Order status updated successfully'
        ]);
    }

    /**
     * Get specific order for admin
     */
    public function show($orderNumber)
    {
        $order = Order::with(['orderItems.product', 'user'])
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

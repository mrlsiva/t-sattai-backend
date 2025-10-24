<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\User;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    /**
     * Get comprehensive dashboard statistics
     */
    public function getStats()
    {
        // Product statistics
        $totalProducts = Product::count();
        $inStockProducts = Product::where('stock', '>', 0)->count();
        $outOfStockProducts = Product::where('stock', '<=', 0)->count();
        $lowStockProducts = Product::where('stock', '>', 0)->where('stock', '<=', 10)->count();

        // Order statistics
        $totalOrders = Order::count();
        $todayOrders = Order::whereDate('created_at', today())->count();
        $totalRevenue = (float) Order::where('payment_status', 'paid')->sum('total_amount');
        $todayRevenue = (float) Order::whereDate('created_at', today())
            ->where('payment_status', 'paid')
            ->sum('total_amount');

        // User statistics
        $totalUsers = User::count();
        $activeUsersCount = User::where('is_active', true)->count();
        $recentOrdersCount = Order::where('created_at', '>=', now()->subDays(7))->count();

        return response()->json([
            'success' => true,
            'data' => [
                'totalProducts' => $totalProducts,
                'totalOrders' => $totalOrders,
                'totalUsers' => $totalUsers,
                'totalRevenue' => $totalRevenue,
                'todayOrders' => $todayOrders,
                'todayRevenue' => $todayRevenue,
                'recentOrdersCount' => $recentOrdersCount,
                'activeUsersCount' => $activeUsersCount,
                'productStats' => [
                    'total' => $totalProducts,
                    'inStock' => $inStockProducts,
                    'outOfStock' => $outOfStockProducts,
                    'lowStock' => $lowStockProducts,
                ],
            ],
            'message' => 'Dashboard statistics retrieved successfully'
        ]);
    }

    /**
     * Get recent orders for dashboard display
     */
    public function getRecentOrders(Request $request)
    {
        $limit = $request->get('limit', 10);

        $recentOrders = Order::with(['user'])
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get()
            ->map(function ($order) {
                return [
                    'id' => $order->order_number,
                    'user' => [
                        'name' => $order->user->name,
                        'email' => $order->user->email,
                    ],
                    'total' => (float) $order->total_amount,
                    'orderStatus' => $order->status,
                    'paymentStatus' => $order->payment_status,
                    'createdAt' => $order->created_at->toISOString(),
                ];
            });

        return response()->json([
            'success' => true,
            'data' => $recentOrders,
            'message' => 'Recent orders retrieved successfully'
        ]);
    }

    /**
     * Get product inventory statistics
     */
    public function getProductStats()
    {
        $total = Product::count();
        $inStock = Product::where('stock', '>', 0)->count();
        $outOfStock = Product::where('stock', '<=', 0)->count();
        $lowStock = Product::where('stock', '>', 0)->where('stock', '<=', 10)->count();

        // Top selling products (based on order items)
        $topProducts = DB::table('order_items')
            ->select('product_id', 'product_name')
            ->selectRaw('SUM(quantity) as total_sold')
            ->selectRaw('SUM(total) as total_revenue')
            ->groupBy('product_id', 'product_name')
            ->orderBy('total_sold', 'desc')
            ->limit(5)
            ->get()
            ->map(function ($item) {
                return [
                    'product_id' => $item->product_id,
                    'product_name' => $item->product_name,
                    'total_sold' => $item->total_sold,
                    'total_revenue' => (float) $item->total_revenue,
                ];
            });

        return response()->json([
            'success' => true,
            'data' => [
                'total' => $total,
                'inStock' => $inStock,
                'outOfStock' => $outOfStock,
                'lowStock' => $lowStock,
                'topProducts' => $topProducts,
            ],
            'message' => 'Product statistics retrieved successfully'
        ]);
    }
}

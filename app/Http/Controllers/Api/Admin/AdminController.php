<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;

class AdminController extends Controller
{
    /**
     * Get all users with pagination and filtering
     */
    public function getUsers(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'page' => 'integer|min:1',
            'limit' => 'integer|min:1|max:100',
            'search' => 'string|max:255',
            'status' => 'string|in:active,inactive',
            'role' => 'string|in:admin,customer',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        $query = User::withCount(['orders'])
            ->with(['orders' => function($query) {
                $query->where('payment_status', 'paid')->select(['user_id', 'total_amount']);
            }]);

        // Apply filters
        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        if ($request->has('status')) {
            $isActive = $request->status === 'active';
            $query->where('is_active', $isActive);
        }

        if ($request->has('role')) {
            $isAdmin = $request->role === 'admin';
            $query->where('is_admin', $isAdmin);
        }

        // Pagination
        $limit = $request->get('limit', 15);
        $users = $query->orderBy('created_at', 'desc')->paginate($limit);

        // Transform the data
        $transformedUsers = $users->getCollection()->map(function ($user) {
            $totalSpent = $user->orders->sum('total_amount');
            
            return [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'phone' => $user->phone,
                'role' => $user->is_admin ? 'admin' : 'customer',
                'status' => $user->is_active ? 'active' : 'inactive',
                'ordersCount' => $user->orders_count,
                'totalSpent' => (float) $totalSpent,
                'lastLogin' => $user->last_login_at ? $user->last_login_at->toISOString() : null,
                'createdAt' => $user->created_at->toISOString(),
                'updatedAt' => $user->updated_at->toISOString(),
                'dateOfBirth' => $user->date_of_birth,
                'gender' => $user->gender,
                'emailVerifiedAt' => $user->email_verified_at ? $user->email_verified_at->toISOString() : null,
            ];
        });

        return response()->json([
            'success' => true,
            'data' => $transformedUsers,
            'pagination' => [
                'current_page' => $users->currentPage(),
                'per_page' => $users->perPage(),
                'total' => $users->total(),
                'last_page' => $users->lastPage(),
            ],
            'message' => 'Users retrieved successfully'
        ]);
    }

    /**
     * Get user statistics for admin dashboard
     */
    public function getUserStats()
    {
        $totalUsers = User::count();
        $activeUsers = User::where('is_active', true)->count();
        $inactiveUsers = User::where('is_active', false)->count();
        $adminUsers = User::where('is_admin', true)->count();
        $regularUsers = User::where('is_admin', false)->count();
        
        // Users registered in the last 30 days
        $newUsers = User::where('created_at', '>=', now()->subDays(30))->count();
        
        // Users who have placed orders
        $usersWithOrders = User::whereHas('orders')->count();
        
        // Average orders per user
        $avgOrdersPerUser = $totalUsers > 0 ? round(Order::count() / $totalUsers, 2) : 0;
        
        // Top spending users
        $topSpenders = User::select('users.id', 'users.name', 'users.email')
            ->selectRaw('COALESCE(SUM(orders.total_amount), 0) as total_spent')
            ->selectRaw('COUNT(orders.id) as orders_count')
            ->leftJoin('orders', 'users.id', '=', 'orders.user_id')
            ->where('orders.payment_status', 'paid')
            ->groupBy('users.id', 'users.name', 'users.email')
            ->orderBy('total_spent', 'desc')
            ->limit(5)
            ->get()
            ->map(function ($user) {
                return [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'totalSpent' => (float) $user->total_spent,
                    'ordersCount' => $user->orders_count,
                ];
            });

        return response()->json([
            'success' => true,
            'data' => [
                'total' => $totalUsers,
                'active' => $activeUsers,
                'inactive' => $inactiveUsers,
                'admins' => $adminUsers,
                'customers' => $regularUsers,
                'newThisMonth' => $newUsers,
                'usersWithOrders' => $usersWithOrders,
                'avgOrdersPerUser' => $avgOrdersPerUser,
                'topSpenders' => $topSpenders,
            ],
            'message' => 'User statistics retrieved successfully'
        ]);
    }

    /**
     * Update user status (active/inactive)
     */
    public function updateUserStatus(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'status' => 'required|string|in:active,inactive',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        $user = User::find($id);

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'User not found'
            ], 404);
        }

        // Prevent self-deactivation
        if ($user->id === $request->user()->id && $request->status === 'inactive') {
            return response()->json([
                'success' => false,
                'message' => 'You cannot deactivate your own account'
            ], 403);
        }

        $oldStatus = $user->is_active ? 'active' : 'inactive';
        $newStatus = $request->status === 'active';
        
        $user->update(['is_active' => $newStatus]);

        // Log the status change
        \Log::info('User status updated', [
            'user_id' => $user->id,
            'user_email' => $user->email,
            'old_status' => $oldStatus,
            'new_status' => $request->status,
            'updated_by' => $request->user()->id
        ]);

        // If deactivating, revoke all tokens
        if (!$newStatus) {
            $user->tokens()->delete();
        }

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'status' => $user->is_active ? 'active' : 'inactive',
                'role' => $user->is_admin ? 'admin' : 'customer',
            ],
            'message' => 'User status updated successfully'
        ]);
    }

    /**
     * Update user role (admin/user)
     */
    public function updateUserRole(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'role' => 'required|string|in:admin,customer',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        $user = User::find($id);

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'User not found'
            ], 404);
        }

        // Prevent self-demotion from admin
        if ($user->id === $request->user()->id && $request->role === 'customer') {
            return response()->json([
                'success' => false,
                'message' => 'You cannot remove admin privileges from your own account'
            ], 403);
        }

        $oldRole = $user->is_admin ? 'admin' : 'customer';
        $newRole = $request->role === 'admin';
        
        $user->update(['is_admin' => $newRole]);

        // Log the role change
        \Log::info('User role updated', [
            'user_id' => $user->id,
            'user_email' => $user->email,
            'old_role' => $oldRole,
            'new_role' => $request->role,
            'updated_by' => $request->user()->id
        ]);

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'status' => $user->is_active ? 'active' : 'inactive',
                'role' => $user->is_admin ? 'admin' : 'customer',
            ],
            'message' => 'User role updated successfully'
        ]);
    }

    /**
     * Delete user (soft delete or hard delete based on requirements)
     */
    public function deleteUser(Request $request, $id)
    {
        $user = User::find($id);

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'User not found'
            ], 404);
        }

        // Prevent self-deletion
        if ($user->id === $request->user()->id) {
            return response()->json([
                'success' => false,
                'message' => 'You cannot delete your own account'
            ], 403);
        }

        // Check if user has orders
        $hasOrders = $user->orders()->exists();
        
        if ($hasOrders) {
            // Instead of deleting, deactivate the user to preserve order history
            $user->update([
                'is_active' => false,
                'email' => $user->email . '_deleted_' . time(), // Unique constraint handling
            ]);
            
            // Revoke all tokens
            $user->tokens()->delete();
            
            \Log::info('User deactivated instead of deleted (has orders)', [
                'user_id' => $user->id,
                'user_email' => $user->email,
                'deleted_by' => $request->user()->id
            ]);

            return response()->json([
                'success' => true,
                'message' => 'User deactivated successfully (preserved due to existing orders)'
            ]);
        } else {
            // Safe to delete - no orders exist
            $userEmail = $user->email;
            $user->tokens()->delete(); // Revoke all tokens first
            $user->delete();
            
            \Log::info('User deleted', [
                'user_id' => $id,
                'user_email' => $userEmail,
                'deleted_by' => $request->user()->id
            ]);

            return response()->json([
                'success' => true,
                'message' => 'User deleted successfully'
            ]);
        }
    }

    /**
     * Get specific user details for admin
     */
    public function getUser($id)
    {
        $user = User::withCount(['orders'])
            ->with(['orders' => function($query) {
                $query->latest()->limit(5)->select(['id', 'user_id', 'order_number', 'total_amount', 'status', 'created_at']);
            }])
            ->find($id);

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'User not found'
            ], 404);
        }

        $totalSpent = $user->orders()->where('payment_status', 'paid')->sum('total_amount');

        $transformedUser = [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'phone' => $user->phone,
            'role' => $user->is_admin ? 'admin' : 'customer',
            'status' => $user->is_active ? 'active' : 'inactive',
            'ordersCount' => $user->orders_count,
            'totalSpent' => (float) $totalSpent,
            'lastLogin' => $user->last_login_at ? $user->last_login_at->toISOString() : null,
            'createdAt' => $user->created_at->toISOString(),
            'updatedAt' => $user->updated_at->toISOString(),
            'dateOfBirth' => $user->date_of_birth,
            'gender' => $user->gender,
            'emailVerifiedAt' => $user->email_verified_at ? $user->email_verified_at->toISOString() : null,
            'recentOrders' => $user->orders->map(function ($order) {
                return [
                    'id' => $order->order_number,
                    'total' => (float) $order->total_amount,
                    'status' => $order->status,
                    'createdAt' => $order->created_at->toISOString(),
                ];
            }),
        ];

        return response()->json([
            'success' => true,
            'data' => $transformedUser,
            'message' => 'User retrieved successfully'
        ]);
    }
}

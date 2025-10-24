<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Password;
use App\Models\User;

class AdminProfileController extends Controller
{
    /**
     * Get Admin Profile
     * GET /api/admin/profile
     */
    public function show()
    {
        try {
            $admin = Auth::user();
            
            // Calculate admin statistics
            $stats = [
                'orders_managed' => \App\Models\Order::count(),
                'users_supervised' => \App\Models\User::where('role', 'user')->count(),
                'products_added' => \App\Models\Product::count(),
                'login_sessions' => 1 // You can implement session tracking later
            ];

            // Get notification preferences (using JSON column or separate table)
            $notifications = [
                'email_notifications' => $admin->email_notifications ?? true,
                'order_notifications' => $admin->order_notifications ?? true,
                'user_notifications' => $admin->user_notifications ?? true,
                'system_notifications' => $admin->system_notifications ?? false,
                'marketing_emails' => $admin->marketing_emails ?? false
            ];

            return response()->json([
                'success' => true,
                'data' => [
                    'id' => $admin->id,
                    'name' => $admin->name,
                    'email' => $admin->email,
                    'phone' => $admin->phone,
                    'bio' => $admin->bio,
                    'avatar' => $admin->avatar ? Storage::url($admin->avatar) : null,
                    'role' => ucfirst($admin->role),
                    'permissions' => ['all'], // Implement proper permissions if needed
                    'last_login' => $admin->last_login_at,
                    'created_at' => $admin->created_at,
                    'updated_at' => $admin->updated_at,
                    'stats' => $stats,
                    'notifications' => $notifications
                ],
                'message' => 'Profile retrieved successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve profile',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update Admin Profile
     * PUT /api/admin/profile
     */
    public function update(Request $request)
    {
        try {
            $admin = Auth::user();

            $validator = Validator::make($request->all(), [
                'name' => 'required|string|max:255',
                'email' => 'required|email|unique:users,email,' . $admin->id,
                'phone' => 'nullable|string|max:20',
                'bio' => 'nullable|string|max:1000'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation error',
                    'errors' => $validator->errors()
                ], 422);
            }

            $admin->update($request->only(['name', 'email', 'phone', 'bio']));

            return response()->json([
                'success' => true,
                'data' => [
                    'id' => $admin->id,
                    'name' => $admin->name,
                    'email' => $admin->email,
                    'phone' => $admin->phone,
                    'bio' => $admin->bio,
                    'updated_at' => $admin->updated_at
                ],
                'message' => 'Profile updated successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update profile',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Upload Avatar - PRIORITY ENDPOINT
     * POST /api/admin/profile/avatar
     */
    public function uploadAvatar(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'avatar' => 'required|image|mimes:jpeg,jpg,png,gif,webp|max:5120' // 5MB max
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid file type or size',
                    'errors' => $validator->errors()
                ], 422);
            }

            $admin = Auth::user();

            // Delete old avatar if exists
            if ($admin->avatar && Storage::exists($admin->avatar)) {
                Storage::delete($admin->avatar);
            }

            // Store new avatar
            $avatarPath = $request->file('avatar')->store('avatars', 'public');

            // Update user record
            $admin->update(['avatar' => $avatarPath]);

            return response()->json([
                'success' => true,
                'data' => [
                    'avatar_url' => Storage::url($avatarPath)
                ],
                'message' => 'Avatar uploaded successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to upload avatar',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Change Password
     * PUT /api/admin/profile/password
     */
    public function changePassword(Request $request)
    {
        try {
            $admin = Auth::user();

            $validator = Validator::make($request->all(), [
                'current_password' => 'required',
                'new_password' => ['required', 'min:8', 'confirmed'],
                'new_password_confirmation' => 'required'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation error',
                    'errors' => $validator->errors()
                ], 422);
            }

            // Verify current password
            if (!Hash::check($request->current_password, $admin->password)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Current password is incorrect',
                    'errors' => [
                        'current_password' => ['Current password is incorrect']
                    ]
                ], 422);
            }

            // Update password
            $admin->update([
                'password' => Hash::make($request->new_password)
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Password changed successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to change password',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update Notification Preferences
     * PUT /api/admin/profile/notifications
     */
    public function updateNotifications(Request $request)
    {
        try {
            $admin = Auth::user();

            $validator = Validator::make($request->all(), [
                'email_notifications' => 'boolean',
                'order_notifications' => 'boolean',
                'user_notifications' => 'boolean',
                'system_notifications' => 'boolean',
                'marketing_emails' => 'boolean'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation error',
                    'errors' => $validator->errors()
                ], 422);
            }

            // Update notification preferences
            $admin->update($request->only([
                'email_notifications',
                'order_notifications', 
                'user_notifications',
                'system_notifications',
                'marketing_emails'
            ]));

            return response()->json([
                'success' => true,
                'data' => [
                    'email_notifications' => $admin->email_notifications,
                    'order_notifications' => $admin->order_notifications,
                    'user_notifications' => $admin->user_notifications,
                    'system_notifications' => $admin->system_notifications,
                    'marketing_emails' => $admin->marketing_emails
                ],
                'message' => 'Notification preferences updated'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update notifications',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get Admin Statistics
     * GET /api/admin/profile/stats
     */
    public function getStats()
    {
        try {
            $stats = [
                'orders_managed' => \App\Models\Order::count(),
                'users_supervised' => \App\Models\User::where('role', 'user')->count(),
                'products_added' => \App\Models\Product::count(),
                'login_sessions' => 1,
                'recent_activities' => [
                    [
                        'action' => 'user_management',
                        'description' => 'Updated user permissions',
                        'timestamp' => now()->subHours(2)->toISOString()
                    ],
                    [
                        'action' => 'order_management',
                        'description' => 'Processed order updates', 
                        'timestamp' => now()->subHours(4)->toISOString()
                    ]
                ]
            ];

            return response()->json([
                'success' => true,
                'data' => $stats,
                'message' => 'Statistics retrieved successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve statistics',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Delete Account (Soft Delete)
     * DELETE /api/admin/profile
     */
    public function deleteAccount(Request $request)
    {
        try {
            $admin = Auth::user();

            $validator = Validator::make($request->all(), [
                'password' => 'required'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Password is required',
                    'errors' => $validator->errors()
                ], 422);
            }

            // Verify password
            if (!Hash::check($request->password, $admin->password)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Password is incorrect',
                    'errors' => [
                        'password' => ['Password is incorrect']
                    ]
                ], 422);
            }

            // Soft delete the account
            $admin->update([
                'deleted_at' => now(),
                'email' => $admin->email . '_deleted_' . time()
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Account deletion request submitted'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete account',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
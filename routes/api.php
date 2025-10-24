<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ProductController;
use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\Api\CartController;
use App\Http\Controllers\Api\PaymentController;
use App\Http\Controllers\Api\ReviewController;
use App\Http\Controllers\Api\WishlistController;
use App\Http\Controllers\Api\OrderController;
use App\Http\Controllers\Api\Admin\AdminController;
use App\Http\Controllers\Api\Admin\OrderController as AdminOrderController;
use App\Http\Controllers\Api\Admin\DashboardController;
use App\Http\Controllers\Api\UserController;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

// CORS test route
Route::get('/cors-test', function () {
    return response()->json([
        'success' => true,
        'message' => 'CORS is working correctly!',
        'timestamp' => now()->toISOString(),
        'origin' => request()->header('Origin'),
        'headers' => [
            'Access-Control-Allow-Origin' => response()->headers->get('Access-Control-Allow-Origin'),
            'Access-Control-Allow-Methods' => response()->headers->get('Access-Control-Allow-Methods'),
            'Access-Control-Allow-Headers' => response()->headers->get('Access-Control-Allow-Headers'),
        ]
    ]);
});

// Quick health check
Route::get('/health', function () {
    return response()->json([
        'status' => 'OK',
        'laravel' => app()->version(),
        'timestamp' => now()->toISOString()
    ]);
});

// Public routes
Route::group(['prefix' => 'auth'], function () {
    Route::post('register', [AuthController::class, 'register']);
    Route::post('login', [AuthController::class, 'login']);
});

// Public product and category routes
Route::group(['prefix' => 'products'], function () {
    Route::get('/', [ProductController::class, 'index']);
    Route::get('/featured', [ProductController::class, 'featured']);
    Route::get('/{product}', [ProductController::class, 'show']);
});

Route::group(['prefix' => 'categories'], function () {
    Route::get('/', [CategoryController::class, 'index']);
    Route::get('/{category}', [CategoryController::class, 'show']);
});

// Stripe webhook (public route)
Route::post('webhooks/stripe', [PaymentController::class, 'webhook']);

// Protected routes
Route::middleware('auth:sanctum')->group(function () {
    // Auth routes
    Route::group(['prefix' => 'auth'], function () {
        Route::get('profile', [AuthController::class, 'profile']);
        Route::put('profile', [AuthController::class, 'updateProfile']);
        Route::put('change-password', [AuthController::class, 'changePassword']);
        Route::post('logout', [AuthController::class, 'logout']);
        Route::post('logout-all', [AuthController::class, 'logoutAll']);
    });

    // Cart routes
    Route::group(['prefix' => 'cart'], function () {
        Route::get('/', [CartController::class, 'index']);
        Route::post('/', [CartController::class, 'store']);
        Route::put('/{cart}', [CartController::class, 'update']);
        Route::delete('/{cart}', [CartController::class, 'destroy']);
        Route::delete('/', [CartController::class, 'clear']);
        
        // Debug endpoint
        Route::get('debug', function (Request $request) {
            $cartItems = \App\Models\Cart::with(['product'])
                ->where('user_id', $request->user()->id)
                ->get();
                
            $total = 0;
            foreach ($cartItems as $item) {
                $price = $item->product->sale_price ?: $item->product->price;
                $total += $price * $item->quantity;
            }
            
            return response()->json([
                'success' => true,
                'cart_items_count' => $cartItems->count(),
                'cart_total' => $total,
                'items' => $cartItems
            ]);
        });
    });

    // Payment routes
    Route::group(['prefix' => 'payments'], function () {
        Route::post('create-intent', [PaymentController::class, 'createPaymentIntent']);
        Route::post('confirm', [PaymentController::class, 'confirmPayment']);
        Route::get('methods', [PaymentController::class, 'getPaymentMethods']);
        
        // Test endpoint for debugging auth
        Route::get('test-auth', function (Request $request) {
            return response()->json([
                'success' => true,
                'message' => 'Authentication working!',
                'user' => $request->user()->only(['id', 'name', 'email']),
                'timestamp' => now()
            ]);
        });
    });

    // Order routes for users
    Route::group(['prefix' => 'orders'], function () {
        Route::get('/', [OrderController::class, 'index']);
        Route::get('/{orderNumber}', [OrderController::class, 'show']);
    });

    // Fallback users endpoint (requires admin access)
    Route::get('users', [UserController::class, 'index']);

    // Review routes
    Route::group(['prefix' => 'products'], function () {
        Route::get('/{product}/reviews', [ReviewController::class, 'index']);
        Route::post('/{product}/reviews', [ReviewController::class, 'store']);
    });
    Route::group(['prefix' => 'reviews'], function () {
        Route::get('/{review}', [ReviewController::class, 'show']);
        Route::put('/{review}', [ReviewController::class, 'update']);
        Route::delete('/{review}', [ReviewController::class, 'destroy']);
    });
    
    // Wishlist routes
    Route::group(['prefix' => 'wishlist'], function () {
        Route::get('/', [WishlistController::class, 'index']);
        Route::post('/{product}', [WishlistController::class, 'store']);
        Route::delete('/{product}', [WishlistController::class, 'destroy']);
        Route::get('/check/{product}', [WishlistController::class, 'check']);
        Route::delete('/', [WishlistController::class, 'clear']);
    });

    // Admin routes
    Route::middleware('admin')->group(function () {
        // Product management
        Route::group(['prefix' => 'admin/products'], function () {
            Route::get('/', [ProductController::class, 'adminIndex']);
            Route::post('/', [ProductController::class, 'store']);
            Route::put('/{product}', [ProductController::class, 'update']);
            Route::delete('/{product}', [ProductController::class, 'destroy']);
        });

        // Category management
        Route::group(['prefix' => 'admin/categories'], function () {
            Route::get('/', [\App\Http\Controllers\Admin\CategoryController::class, 'index']);
            Route::get('/stats', [\App\Http\Controllers\Admin\CategoryController::class, 'stats']);
            Route::post('/', [\App\Http\Controllers\Admin\CategoryController::class, 'store']);
            Route::get('/{category}', [\App\Http\Controllers\Admin\CategoryController::class, 'show']);
            Route::put('/{category}', [\App\Http\Controllers\Admin\CategoryController::class, 'update']);
            Route::delete('/{category}', [\App\Http\Controllers\Admin\CategoryController::class, 'destroy']);
            Route::post('/{category}/image', [\App\Http\Controllers\Admin\CategoryController::class, 'updateImage']);
            Route::delete('/{category}/image', [\App\Http\Controllers\Admin\CategoryController::class, 'removeImage']);
        });

        // Order management
        Route::group(['prefix' => 'admin/orders'], function () {
            Route::get('/', [AdminOrderController::class, 'index']);
            Route::get('/stats', [AdminOrderController::class, 'stats']);
            Route::get('/{orderNumber}', [AdminOrderController::class, 'show']);
            Route::put('/{orderNumber}/status', [AdminOrderController::class, 'updateStatus']);
        });

        // User management
        Route::group(['prefix' => 'admin/users'], function () {
            Route::get('/', [AdminController::class, 'getUsers']);
            Route::get('/stats', [AdminController::class, 'getUserStats']);
            Route::get('/{id}', [AdminController::class, 'getUser']);
            Route::put('/{id}/status', [AdminController::class, 'updateUserStatus']);
            Route::put('/{id}/role', [AdminController::class, 'updateUserRole']);
            Route::delete('/{id}', [AdminController::class, 'deleteUser']);
        });

        // Dashboard endpoints
        Route::group(['prefix' => 'admin/dashboard'], function () {
            Route::get('/stats', [DashboardController::class, 'getStats']);
            Route::get('/recent-orders', [DashboardController::class, 'getRecentOrders']);
            Route::get('/product-stats', [DashboardController::class, 'getProductStats']);
        });
    });
});
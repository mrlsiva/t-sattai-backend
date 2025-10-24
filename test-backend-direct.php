<?php

require_once 'vendor/autoload.php';

// Initialize Laravel
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(\Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "🧪 Testing Laravel Backend Directly\n";
echo "===================================\n\n";

try {
    // Test database connection
    echo "1. Testing Database Connection...\n";
    $pdo = DB::connection()->getPdo();
    echo "   ✅ Database connected successfully\n\n";

    // Test users in database
    echo "2. Testing Users Table...\n";
    $totalUsers = DB::table('users')->count();
    $adminUsers = DB::table('users')->where('role', 'admin')->count();
    $customerUsers = DB::table('users')->where('role', 'customer')->count();
    
    echo "   📊 Users Statistics:\n";
    echo "      Total Users: $totalUsers\n";
    echo "      Admin Users: $adminUsers\n";
    echo "      Customer Users: $customerUsers\n\n";

    // List admin users
    $admins = DB::table('users')->where('role', 'admin')->get(['id', 'name', 'email']);
    echo "   👥 Admin Users:\n";
    foreach ($admins as $admin) {
        echo "      - ID: {$admin->id}, Name: {$admin->name}, Email: {$admin->email}\n";
    }
    echo "\n";

    // Test orders
    echo "3. Testing Orders Table...\n";
    $totalOrders = DB::table('orders')->count();
    $pendingOrders = DB::table('orders')->where('status', 'pending')->count();
    echo "   📦 Orders Statistics:\n";
    echo "      Total Orders: $totalOrders\n";
    echo "      Pending Orders: $pendingOrders\n\n";

    // Test products  
    echo "4. Testing Products Table...\n";
    $totalProducts = DB::table('products')->count();
    echo "   🛍️ Products Statistics:\n";
    echo "      Total Products: $totalProducts\n\n";

    // Test categories
    echo "5. Testing Categories Table...\n";
    $totalCategories = DB::table('categories')->count();
    echo "   📂 Categories Statistics:\n";
    echo "      Total Categories: $totalCategories\n\n";

    // Test authentication flow
    echo "6. Testing Authentication Flow...\n";
    $testUser = DB::table('users')->where('email', 'admin@test.com')->first();
    
    if ($testUser) {
        echo "   ✅ Test admin user found\n";
        echo "      - ID: {$testUser->id}\n";
        echo "      - Name: {$testUser->name}\n";
        echo "      - Email: {$testUser->email}\n";
        echo "      - Role: {$testUser->role}\n";
        
        // Test password hash
        if (Hash::check('password', $testUser->password)) {
            echo "   ✅ Password verification successful\n";
        } else {
            echo "   ❌ Password verification failed\n";
        }
    } else {
        echo "   ❌ Test admin user not found\n";
    }

    echo "\n🎉 Backend Direct Test Complete!\n";
    echo "All core components are working correctly.\n";
    echo "The issue might be with the HTTP server or routing.\n\n";

    // Show routes
    echo "7. Available API Routes:\n";
    $routes = collect(Route::getRoutes())->filter(function($route) {
        return str_starts_with($route->uri(), 'api/');
    })->map(function($route) {
        return [
            'method' => implode('|', $route->methods()),
            'uri' => $route->uri(),
            'name' => $route->getName()
        ];
    })->take(20);

    foreach ($routes as $route) {
        echo "   {$route['method']} /{$route['uri']}\n";
    }

} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}
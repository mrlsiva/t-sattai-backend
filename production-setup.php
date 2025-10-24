<?php
/**
 * Production Setup Script
 * Run this file once after uploading to your live server
 * Access via: https://yourdomain.com/production-setup.php
 */

set_time_limit(300); // 5 minutes

echo "<!DOCTYPE html>";
echo "<html><head><title>Production Setup</title>";
echo "<style>body{font-family:Arial;margin:40px;background:#f5f5f5;} .container{background:white;padding:30px;border-radius:8px;box-shadow:0 2px 10px rgba(0,0,0,0.1);} .success{color:#28a745;} .error{color:#dc3545;} .info{color:#17a2b8;} pre{background:#f8f9fa;padding:15px;border-radius:4px;overflow-x:auto;}</style>";
echo "</head><body><div class='container'>";

echo "<h1>🚀 Laravel E-commerce Production Setup</h1>";
echo "<hr>";

try {
    // Check if Laravel is accessible
    if (!file_exists('vendor/autoload.php')) {
        throw new Exception("Vendor folder not found. Please run 'composer install' first.");
    }

    require_once 'vendor/autoload.php';

    // Check if we can bootstrap Laravel
    if (!file_exists('bootstrap/app.php')) {
        throw new Exception("Laravel bootstrap file not found. Ensure all Laravel files are uploaded.");
    }

    $app = require_once 'bootstrap/app.php';
    $kernel = $app->make(\Illuminate\Contracts\Console\Kernel::class);
    $kernel->bootstrap();

    echo "<div class='success'>✅ Laravel framework loaded successfully</div><br>";

    // Test database connection
    echo "<h3>1. Testing Database Connection</h3>";
    try {
        $pdo = DB::connection()->getPdo();
        echo "<div class='success'>✅ Database connection successful</div>";
        echo "<div class='info'>Database: " . config('database.connections.mysql.database') . "</div>";
    } catch (Exception $e) {
        echo "<div class='error'>❌ Database connection failed: " . $e->getMessage() . "</div>";
        echo "<div class='info'>Please check your .env database configuration</div>";
        throw $e;
    }

    // Run migrations
    echo "<h3>2. Running Database Migrations</h3>";
    Artisan::call('migrate', ['--force' => true]);
    $output = Artisan::output();
    echo "<pre>" . htmlspecialchars($output) . "</pre>";
    echo "<div class='success'>✅ Migrations completed</div>";

    // Create storage link
    echo "<h3>3. Creating Storage Symbolic Link</h3>";
    try {
        if (file_exists(public_path('storage'))) {
            echo "<div class='info'>Storage link already exists</div>";
        } else {
            Artisan::call('storage:link');
            echo "<div class='success'>✅ Storage link created</div>";
        }
    } catch (Exception $e) {
        echo "<div class='error'>⚠️ Could not create storage link: " . $e->getMessage() . "</div>";
        echo "<div class='info'>You may need to create this manually in cPanel</div>";
    }

    // Create admin user
    echo "<h3>4. Setting Up Admin User</h3>";
    $adminEmail = 'admin@' . parse_url(config('app.url'), PHP_URL_HOST);
    $adminPassword = 'Admin123!@#';
    
    $admin = \App\Models\User::updateOrCreate([
        'email' => $adminEmail
    ], [
        'name' => 'Admin User',
        'password' => Hash::make($adminPassword),
        'role' => 'admin',
        'is_active' => true,
        'email_verified_at' => now()
    ]);

    echo "<div class='success'>✅ Admin user created/updated</div>";
    echo "<div class='info'><strong>Email:</strong> " . $adminEmail . "</div>";
    echo "<div class='info'><strong>Password:</strong> " . $adminPassword . "</div>";
    echo "<div style='background:#fff3cd;border:1px solid #ffeaa7;padding:10px;border-radius:4px;margin:10px 0;'>
            <strong>⚠️ IMPORTANT:</strong> Change this password immediately after first login!
          </div>";

    // Create sample categories
    echo "<h3>5. Creating Sample Categories</h3>";
    $categories = [
        [
            'name' => 'Electronics',
            'description' => 'Latest electronic gadgets and devices',
            'sort_order' => 1,
            'is_active' => true,
            'meta_title' => 'Electronics - Latest Gadgets',
            'meta_description' => 'Shop the latest electronic gadgets, smartphones, laptops and more'
        ],
        [
            'name' => 'Fashion',
            'description' => 'Trendy clothing and accessories for all occasions',
            'sort_order' => 2,
            'is_active' => true,
            'meta_title' => 'Fashion - Clothing & Accessories',
            'meta_description' => 'Discover the latest fashion trends in clothing and accessories'
        ],
        [
            'name' => 'Home & Garden',
            'description' => 'Everything you need for your home and garden',
            'sort_order' => 3,
            'is_active' => true,
            'meta_title' => 'Home & Garden - Home Improvement',
            'meta_description' => 'Transform your home and garden with our quality products'
        ]
    ];

    $categoryCount = 0;
    foreach ($categories as $categoryData) {
        $category = \App\Models\Category::updateOrCreate(
            ['name' => $categoryData['name']],
            $categoryData
        );
        $categoryCount++;
        echo "<div class='info'>• " . $category->name . " (Placeholder: " . $category->display_image . ")</div>";
    }
    echo "<div class='success'>✅ {$categoryCount} categories created with placeholder images</div>";

    // Clear and cache config
    echo "<h3>6. Optimizing Application</h3>";
    Artisan::call('config:clear');
    echo "<div class='info'>Config cache cleared</div>";
    
    Artisan::call('config:cache');
    echo "<div class='info'>Config cached</div>";
    
    Artisan::call('route:clear');
    echo "<div class='info'>Route cache cleared</div>";
    
    try {
        Artisan::call('route:cache');
        echo "<div class='info'>Routes cached</div>";
    } catch (Exception $e) {
        echo "<div class='error'>⚠️ Could not cache routes: " . $e->getMessage() . "</div>";
    }

    echo "<div class='success'>✅ Application optimized for production</div>";

    // Test API endpoints
    echo "<h3>7. Testing API Endpoints</h3>";
    $baseUrl = config('app.url') . '/api';
    
    // Test categories endpoint
    try {
        $categoriesUrl = $baseUrl . '/categories';
        $context = stream_context_create([
            'http' => [
                'method' => 'GET',
                'header' => "Accept: application/json\r\n"
            ]
        ]);
        
        $response = @file_get_contents($categoriesUrl, false, $context);
        if ($response) {
            $data = json_decode($response, true);
            if (isset($data['success']) && $data['success']) {
                echo "<div class='success'>✅ Categories API endpoint working</div>";
            } else {
                echo "<div class='error'>⚠️ Categories API returned error</div>";
            }
        } else {
            echo "<div class='error'>⚠️ Could not reach categories API endpoint</div>";
        }
    } catch (Exception $e) {
        echo "<div class='error'>⚠️ API test failed: " . $e->getMessage() . "</div>";
    }

    // Show important information
    echo "<h3>🎉 Setup Complete!</h3>";
    echo "<div style='background:#d4edda;border:1px solid #c3e6cb;padding:20px;border-radius:4px;margin:20px 0;'>";
    echo "<h4>📋 Important Information:</h4>";
    echo "<p><strong>Admin Login:</strong><br>";
    echo "Email: " . $adminEmail . "<br>";
    echo "Password: " . $adminPassword . "</p>";
    
    echo "<p><strong>API Base URL:</strong><br>";
    echo "<a href='" . $baseUrl . "' target='_blank'>" . $baseUrl . "</a></p>";
    
    echo "<p><strong>Test Endpoints:</strong><br>";
    echo "• <a href='" . $baseUrl . "/categories' target='_blank'>Categories</a><br>";
    echo "• <a href='" . $baseUrl . "/products' target='_blank'>Products</a><br>";
    echo "• <a href='" . $baseUrl . "/auth/login' target='_blank'>Admin Login</a> (POST)</p>";
    
    echo "<p><strong>Admin Panel URLs:</strong><br>";
    echo "• <a href='" . $baseUrl . "/admin/dashboard/stats' target='_blank'>Dashboard Stats</a><br>";
    echo "• <a href='" . $baseUrl . "/admin/categories/stats' target='_blank'>Category Stats</a><br>";
    echo "• <a href='" . $baseUrl . "/admin/users/stats' target='_blank'>User Stats</a></p>";
    echo "</div>";

    echo "<div style='background:#fff3cd;border:1px solid #ffeaa7;padding:15px;border-radius:4px;margin:20px 0;'>";
    echo "<h4>🔒 Security Reminders:</h4>";
    echo "<ul>";
    echo "<li>Change the admin password immediately</li>";
    echo "<li>Update Stripe keys to live keys</li>";
    echo "<li>Configure email settings in .env</li>";
    echo "<li>Set up SSL certificate</li>";
    echo "<li>Delete this setup file after completion</li>";
    echo "<li>Secure your .env file</li>";
    echo "</ul>";
    echo "</div>";

    echo "<div style='background:#f8d7da;border:1px solid #f5c6cb;padding:15px;border-radius:4px;margin:20px 0;'>";
    echo "<h4>🗑️ Next Steps:</h4>";
    echo "<ol>";
    echo "<li>Test admin login</li>";
    echo "<li>Update admin password</li>";
    echo "<li>Configure Stripe live keys</li>";
    echo "<li>Test payment processing</li>";
    echo "<li>Upload category images</li>";
    echo "<li>Add your products</li>";
    echo "<li><strong>Delete this setup file for security</strong></li>";
    echo "</ol>";
    echo "</div>";

} catch (Exception $e) {
    echo "<div class='error'>";
    echo "<h3>❌ Setup Failed</h3>";
    echo "<p><strong>Error:</strong> " . $e->getMessage() . "</p>";
    echo "<p><strong>File:</strong> " . $e->getFile() . " (Line: " . $e->getLine() . ")</p>";
    echo "<h4>Common Solutions:</h4>";
    echo "<ul>";
    echo "<li>Check your .env file database configuration</li>";
    echo "<li>Ensure all Laravel files are uploaded</li>";
    echo "<li>Verify database exists and user has privileges</li>";
    echo "<li>Check PHP version (requires 8.1+)</li>";
    echo "<li>Ensure composer install was run</li>";
    echo "</ul>";
    echo "</div>";
}

echo "<hr>";
echo "<p style='text-align:center;color:#666;'>";
echo "Laravel E-commerce Backend - Production Setup Complete<br>";
echo "Generated on: " . date('Y-m-d H:i:s T');
echo "</p>";

echo "</div></body></html>";
?>
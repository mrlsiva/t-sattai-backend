<?php

use Illuminate\Support\Facades\Hash;
use App\Models\User;

require_once 'vendor/autoload.php';

// Initialize Laravel
$app = require_once 'bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "🔧 Setting up test admin user...\n";

try {
    // Create or update admin user
    $admin = User::updateOrCreate([
        'email' => 'admin@test.com'
    ], [
        'name' => 'Test Admin',
        'password' => Hash::make('password'),
        'role' => 'admin',
        'email_verified_at' => now()
    ]);

    echo "✅ Admin user created/updated:\n";
    echo "   Email: admin@test.com\n";
    echo "   Password: password\n";
    echo "   Role: admin\n";
    echo "   ID: {$admin->id}\n\n";

    // Create a regular customer for testing
    $customer = User::updateOrCreate([
        'email' => 'customer@test.com'
    ], [
        'name' => 'Test Customer',
        'password' => Hash::make('password'),
        'role' => 'customer',
        'email_verified_at' => now()
    ]);

    echo "✅ Customer user created/updated:\n";
    echo "   Email: customer@test.com\n";
    echo "   Password: password\n";
    echo "   Role: customer\n";
    echo "   ID: {$customer->id}\n\n";

    echo "🎉 Setup complete! You can now run the API tests.\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    exit(1);
}
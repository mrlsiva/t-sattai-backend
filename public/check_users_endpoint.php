<?php

header('Content-Type: text/plain');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');
header('Access-Control-Allow-Headers: Content-Type');

require __DIR__ . '/../vendor/autoload.php';

try {
    $app = require_once __DIR__ . '/../bootstrap/app.php';
    $app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

    echo "=== User Database Check ===\n\n";

    $users = App\Models\User::take(10)->get(['id', 'name', 'email', 'is_admin', 'role', 'is_active']);
    
    if ($users->count() > 0) {
        echo "Found " . $users->count() . " users:\n\n";
        
        foreach ($users as $user) {
            echo "ID: {$user->id}\n";
            echo "Name: {$user->name}\n";
            echo "Email: {$user->email}\n";
            echo "Is Admin: " . ($user->is_admin ? 'YES' : 'NO') . "\n";
            echo "Role: " . ($user->role ?? 'N/A') . "\n";
            echo "Active: " . ($user->is_active ? 'YES' : 'NO') . "\n";
            
            // Test the new methods if they exist
            if (method_exists($user, 'isAdmin')) {
                echo "isAdmin(): " . ($user->isAdmin() ? 'YES' : 'NO') . "\n";
            }
            if (method_exists($user, 'getRedirectPath')) {
                echo "Redirect Path: " . $user->getRedirectPath() . "\n";
            }
            
            echo "---\n";
        }
    } else {
        echo "No users found in database.\n";
        echo "You may need to run migrations and seeders.\n";
    }

} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    echo "Make sure your database is connected and migrations are run.\n";
}
?>
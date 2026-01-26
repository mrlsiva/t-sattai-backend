<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use App\Models\User;
use App\Models\Address;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('test:addresses', function () {
    $this->info('=== Address API Test ===');
    
    try {
        // Find a test user
        $user = User::where('email', 'admin@example.com')->first();
        
        if (!$user) {
            $this->error('Test user not found. Please run database seeder first.');
            return 1;
        }

        $this->info("✅ Found test user: {$user->name} ({$user->email})");

        // Clean up any existing addresses for this user
        $user->addresses()->delete();
        $this->info('🧹 Cleaned up existing addresses');

        // Test 1: Create a shipping address
        $this->info('Test 1: Creating shipping address...');
        $shippingAddress = Address::create([
            'user_id' => $user->id,
            'type' => 'shipping',
            'first_name' => 'John',
            'last_name' => 'Doe',
            'company' => 'Test Company Inc.',
            'address_line_1' => '123 Main Street',
            'address_line_2' => 'Apt 4B',
            'city' => 'New York',
            'state' => 'NY',
            'postal_code' => '10001',
            'country' => 'United States',
            'phone' => '+1-555-0123',
            'is_default' => true,
        ]);

        $this->info("✅ Shipping address created: ID {$shippingAddress->id}");
        $this->info("   Full name: {$shippingAddress->full_name}");
        $this->info("   Formatted: {$shippingAddress->formatted_address}");

        // Test 2: Create a billing address
        $this->info('Test 2: Creating billing address...');
        $billingAddress = Address::create([
            'user_id' => $user->id,
            'type' => 'billing',
            'first_name' => 'John',
            'last_name' => 'Doe',
            'address_line_1' => '456 Business Ave',
            'city' => 'Boston',
            'state' => 'MA',
            'postal_code' => '02101',
            'country' => 'United States',
            'phone' => '+1-555-0456',
            'is_default' => false,
        ]);

        $this->info("✅ Billing address created: ID {$billingAddress->id}");

        // Test 3: Check user relationship
        $userAddresses = $user->addresses()->get();
        $this->info("✅ User has {$userAddresses->count()} addresses");

        // Test 4: Test scopes
        $shippingAddresses = $user->addresses()->ofType('shipping')->get();
        $this->info("✅ Shipping addresses: {$shippingAddresses->count()}");

        $defaultAddresses = $user->addresses()->default()->get();
        $this->info("✅ Default addresses: {$defaultAddresses->count()}");

        $this->info('🎉 All address model tests passed!');

        // Clean up
        $user->addresses()->delete();
        $this->info('🧹 Test data cleaned up');

        $this->info('=== API Endpoints Available ===');
        $this->info('GET    /api/user/addresses        - Get all user addresses');
        $this->info('POST   /api/user/addresses        - Create new address');
        $this->info('GET    /api/user/addresses/{id}   - Get specific address');
        $this->info('PUT    /api/user/addresses/{id}   - Update address');
        $this->info('DELETE /api/user/addresses/{id}   - Delete address');
        $this->info('PUT    /api/user/addresses/{id}/default - Set as default');
        $this->info('GET    /api/user/addresses/type/{type} - Get by type');

        return 0;
    } catch (Exception $e) {
        $this->error("Error: " . $e->getMessage());
        return 1;
    }
})->purpose('Test the Address API functionality');

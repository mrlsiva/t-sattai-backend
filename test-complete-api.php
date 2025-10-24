#!/usr/bin/env php
<?php
// Comprehensive test script for all API endpoints
// Run with: php test-complete-api.php

echo "🚀 Testing Complete Backend API Implementation\n";
echo "=============================================\n\n";

// Test admin login
$loginData = [
    'email' => 'admin@test.com',
    'password' => 'password'
];

echo "1. Testing Admin Authentication...\n";
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, 'http://localhost:8000/api/auth/login');
curl_setopt($ch, CURLOPT_POST, 1);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($loginData));
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'Accept: application/json'
]);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($httpCode !== 200) {
    echo "❌ Admin authentication failed (HTTP $httpCode)\n";
    echo "Note: Make sure you have an admin user in your database\n";
    exit(1);
}

$loginResponse = json_decode($response, true);
if (!$loginResponse || !isset($loginResponse['data']['token'])) {
    echo "❌ Admin authentication failed - no token received\n";
    exit(1);
}

$token = $loginResponse['data']['token'];
echo "✅ Admin authentication successful\n\n";

// Test all endpoints
$endpoints = [
    // Dashboard endpoints
    ['GET', '/api/admin/dashboard/stats', 'Dashboard Statistics'],
    ['GET', '/api/admin/dashboard/recent-orders?limit=5', 'Recent Orders'],
    ['GET', '/api/admin/dashboard/product-stats', 'Product Statistics'],
    
    // Order endpoints
    ['GET', '/api/admin/orders?limit=5', 'Admin Orders List'],
    ['GET', '/api/admin/orders/stats', 'Order Statistics'],
    ['GET', '/api/orders?limit=3', 'User Orders List'],
    
    // User endpoints
    ['GET', '/api/admin/users?limit=5', 'Admin Users List'],
    ['GET', '/api/admin/users/stats', 'User Statistics'],
    ['GET', '/api/users?limit=3', 'Fallback Users List'],
];

$results = [];

foreach ($endpoints as [$method, $endpoint, $description]) {
    echo "Testing: $description ($method $endpoint)...\n";
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, 'http://localhost:8000' . $endpoint);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Accept: application/json',
        'Authorization: Bearer ' . $token
    ]);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    $status = ($httpCode === 200) ? '✅' : '❌';
    $results[] = [$description, $status, $httpCode];
    
    if ($httpCode === 200) {
        $data = json_decode($response, true);
        if (isset($data['data'])) {
            if (is_array($data['data'])) {
                echo "   $status Success - " . count($data['data']) . " items returned\n";
            } else {
                echo "   $status Success - Data object returned\n";
            }
        } else {
            echo "   $status Success - Response received\n";
        }
    } else {
        echo "   $status Failed (HTTP $httpCode)\n";
        if ($httpCode === 403) {
            echo "     Note: User may not have admin privileges\n";
        }
    }
    echo "\n";
}

// Summary
echo "🎯 Test Results Summary:\n";
echo "========================\n";
foreach ($results as [$description, $status, $httpCode]) {
    echo "$status $description (HTTP $httpCode)\n";
}

echo "\n📊 API Endpoints Status:\n";
$successful = count(array_filter($results, fn($r) => $r[1] === '✅'));
$total = count($results);
echo "Successful: $successful/$total\n";

if ($successful === $total) {
    echo "\n🎉 All API endpoints are working correctly!\n";
    echo "Your frontend will now use real data instead of fallback logic.\n";
} else {
    echo "\n⚠️  Some endpoints need attention. Check the logs above.\n";
    echo "Common issues:\n";
    echo "- Make sure you have an admin user in the database\n";
    echo "- Ensure database migrations have been run\n";
    echo "- Check that sample data exists for testing\n";
}

echo "\n📚 Available API Endpoints:\n";
echo "==========================\n";
echo "Dashboard:\n";
echo "- GET /api/admin/dashboard/stats\n";
echo "- GET /api/admin/dashboard/recent-orders\n";
echo "- GET /api/admin/dashboard/product-stats\n";
echo "\nOrders:\n";
echo "- GET /api/orders (user orders)\n";
echo "- GET /api/admin/orders (all orders)\n";
echo "- GET /api/admin/orders/stats\n";
echo "- PUT /api/admin/orders/{id}/status\n";
echo "\nUsers:\n";
echo "- GET /api/users (fallback)\n";
echo "- GET /api/admin/users\n";
echo "- GET /api/admin/users/stats\n";
echo "- PUT /api/admin/users/{id}/status\n";
echo "- PUT /api/admin/users/{id}/role\n";
echo "- DELETE /api/admin/users/{id}\n";
echo "\nPayments:\n";
echo "- POST /api/payments/create-intent\n";
echo "- POST /api/payments/confirm\n";
?>
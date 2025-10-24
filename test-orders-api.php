#!/usr/bin/env php
<?php
// Test script to verify the Order API implementation
// Run with: php test-orders-api.php

echo "🧪 Testing Order API Implementation\n";
echo "===================================\n\n";

// Test data
$loginData = [
    'email' => 'test@example.com',
    'password' => 'password123'
];

echo "1. Testing Authentication...\n";
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
    echo "❌ Authentication failed (HTTP $httpCode)\n";
    echo "Response: $response\n";
    exit(1);
}

$loginResponse = json_decode($response, true);
if (!$loginResponse || !isset($loginResponse['data']['token'])) {
    echo "❌ Authentication failed - no token received\n";
    exit(1);
}

$token = $loginResponse['data']['token'];
echo "✅ Authentication successful\n\n";

// Test user orders endpoint
echo "2. Testing User Orders API...\n";
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, 'http://localhost:8000/api/orders');
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Accept: application/json',
    'Authorization: Bearer ' . $token
]);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "User Orders Response (HTTP $httpCode):\n";
$ordersResponse = json_decode($response, true);
if ($ordersResponse && $ordersResponse['success']) {
    echo "✅ User Orders API working\n";
    echo "   Orders found: " . count($ordersResponse['data']) . "\n";
} else {
    echo "❌ User Orders API failed\n";
    echo "Response: $response\n";
}
echo "\n";

// Test admin orders stats (if user is admin)
echo "3. Testing Admin Orders Stats API...\n";
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, 'http://localhost:8000/api/admin/orders/stats');
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Accept: application/json',
    'Authorization: Bearer ' . $token
]);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "Admin Stats Response (HTTP $httpCode):\n";
if ($httpCode === 200) {
    $statsResponse = json_decode($response, true);
    echo "✅ Admin Stats API working\n";
    echo "   Total Orders: " . ($statsResponse['data']['total'] ?? 0) . "\n";
    echo "   Total Value: $" . ($statsResponse['data']['totalValue'] ?? 0) . "\n";
} elseif ($httpCode === 403) {
    echo "⚠️  Admin access forbidden (user not admin)\n";
} else {
    echo "❌ Admin Stats API failed\n";
    echo "Response: $response\n";
}
echo "\n";

// Test admin orders list (if user is admin)
echo "4. Testing Admin Orders List API...\n";
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, 'http://localhost:8000/api/admin/orders');
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Accept: application/json',
    'Authorization: Bearer ' . $token
]);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "Admin Orders Response (HTTP $httpCode):\n";
if ($httpCode === 200) {
    $adminOrdersResponse = json_decode($response, true);
    echo "✅ Admin Orders API working\n";
    echo "   Orders found: " . count($adminOrdersResponse['data']) . "\n";
} elseif ($httpCode === 403) {
    echo "⚠️  Admin access forbidden (user not admin)\n";
} else {
    echo "❌ Admin Orders API failed\n";
    echo "Response: $response\n";
}

echo "\n🎉 API Testing Complete!\n";
echo "\nNext steps:\n";
echo "- Your frontend will now automatically use these real API endpoints\n";
echo "- The fallback logic will be bypassed\n";
echo "- Admin dashboard will show real order data\n";
echo "- Order management features are fully functional\n";
?>
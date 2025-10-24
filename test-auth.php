<?php
// Test script to verify authentication
// Run with: php test-auth.php

// Test login
$loginData = [
    'email' => 'test@example.com', // Replace with actual test user
    'password' => 'password123'    // Replace with actual password
];

echo "Testing login...\n";
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

echo "Login Response (HTTP $httpCode):\n";
echo $response . "\n\n";

$loginResponse = json_decode($response, true);

if ($loginResponse && isset($loginResponse['data']['token'])) {
    $token = $loginResponse['data']['token'];
    echo "Got token: " . substr($token, 0, 20) . "...\n\n";
    
    // Test authenticated request
    echo "Testing payment endpoint with token...\n";
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, 'http://localhost:8000/api/payments/create-intent');
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
        'amount' => 10.00,
        'currency' => 'usd',
        'shipping_address' => [
            'name' => 'Test User',
            'line1' => '123 Test St',
            'city' => 'Test City',
            'postal_code' => '12345',
            'country' => 'US'
        ]
    ]));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Accept: application/json',
        'Authorization: Bearer ' . $token
    ]);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    echo "Payment Response (HTTP $httpCode):\n";
    echo $response . "\n";
} else {
    echo "Login failed - no token received\n";
}
?>
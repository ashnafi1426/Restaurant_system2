<?php

$token = '165|X1QMKONpYQlJduwLcj6TMhXPLl5AZPXGlPDVEtEH905e15dc';
$apiUrl = 'http://127.0.0.1:8000/api/manager/restaurant-tables';

echo "=== API AUTHENTICATION DEBUG ===\n\n";

// Test 1: Call with token
echo "1. Making API request with token...\n";
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $apiUrl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Accept: application/json',
    'Content-Type: application/json',
    'Authorization: Bearer ' . $token
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "   HTTP Status: {$httpCode}\n";

$data = json_decode($response, true);
if ($data) {
    echo "   Success: " . ($data['success'] ?? 'N/A') . "\n";
    if (isset($data['data'])) {
        $tableData = $data['data'];
        echo "   Total tables: " . ($tableData['total'] ?? 'N/A') . "\n";
        echo "   Current page: " . ($tableData['current_page'] ?? 'N/A') . "\n";
        echo "   Per page: " . ($tableData['per_page'] ?? 'N/A') . "\n";
        echo "   Data array count: " . count($tableData['data'] ?? []) . "\n";
    }
    if (isset($data['message'])) {
        echo "   Message: {$data['message']}\n";
    }
} else {
    echo "   Raw Response: " . substr($response, 0, 500) . "\n";
}

// Test 2: Check user from token
echo "\n2. Checking user associated with token...\n";
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Laravel\Sanctum\PersonalAccessToken;

$tokenParts = explode('|', $token);
$tokenId = $tokenParts[0] ?? null;
$tokenString = $tokenParts[1] ?? null;

if ($tokenId && $tokenString) {
    $accessToken = PersonalAccessToken::find($tokenId);
    if ($accessToken) {
        $user = $accessToken->tokenable;
        echo "   User ID: {$user->id}\n";
        echo "   User Name: {$user->name}\n";
        echo "   User Email: {$user->email}\n";
        echo "   User Role: {$user->role}\n";
        
        // Check middleware
        echo "\n3. Checking if user matches 'role:manager' middleware...\n";
        $isManager = $user->role === 'manager';
        echo "   Is Manager: " . ($isManager ? 'YES' : 'NO') . "\n";
    } else {
        echo "   Token not found in database!\n";
    }
} else {
    echo "   Invalid token format!\n";
}

echo "\n=== END DEBUG ===\n";

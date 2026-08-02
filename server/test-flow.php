<?php
// Complete flow test

echo "\n=== QR MENU SYSTEM - COMPLETE FLOW TEST ===\n\n";

// Get a real token
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$token = DB::table('restaurant_tables')->value('qr_token');
echo "1. Got walk-in QR token: $token\n";

// Test menu items endpoint  
echo "\n2. Testing /api/walk-in/menu/items endpoint...\n";
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, "http://127.0.0.1:8000/api/walk-in/menu/items");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Accept: application/json', 'Content-Type: application/json']);
$response = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);
echo "   Status: $http_code\n";
$data = json_decode($response, true);
$items_count = count($data['data'] ?? []);
echo "   Menu categories loaded: " . ($items_count > 0 ? "✓ YES ($items_count)" : "✗ NO") . "\n";

// Test walk-in session initialization
echo "\n3. Testing /api/walk-in/session/initialize endpoint...\n";
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, "http://127.0.0.1:8000/api/walk-in/session/initialize");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode(['qr_token' => $token]));
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Accept: application/json', 'Content-Type: application/json']);
$response = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);
echo "   Status: $http_code\n";
$data = json_decode($response, true);
if ($data['data']['id'] ?? null) {
    echo "   Session created: ✓ YES (ID: " . substr($data['data']['id'], 0, 8) . "...)\n";
} else {
    echo "   Session created: ✗ NO\n";
    echo "   Error: " . ($data['message'] ?? 'Unknown') . "\n";
}

// Test menu items for guest endpoint
echo "\n4. Testing /api/guest/menu/{token}/items endpoint...\n";
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, "http://127.0.0.1:8000/api/guest/menu/$token/items");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Accept: application/json', 'Content-Type: application/json']);
$response = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);
echo "   Status: $http_code\n";
if ($http_code === 200) {
    $data = json_decode($response, true);
    $items = $data['data'] ?? [];
    $count = 0;
    foreach ($items as $cat) {
        $count += count($cat['items'] ?? []);
    }
    echo "   Menu items loaded: ✓ YES ($count items)\n";
} else {
    echo "   Menu items loaded: ✗ NO\n";
}

// Test categories endpoint
echo "\n5. Testing /api/categories endpoint...\n";
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, "http://127.0.0.1:8000/api/categories?is_active=true");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Accept: application/json', 'Content-Type: application/json']);
$response = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);
echo "   Status: $http_code\n";
if ($http_code === 200) {
    $data = json_decode($response, true);
    $count = count($data['data'] ?? []);
    echo "   Categories loaded: ✓ YES ($count categories)\n";
} else {
    echo "   Categories loaded: ✗ NO (but frontend has fallback categories)\n";
}

echo "\n=== TEST COMPLETE ===\n\n";
echo "Frontend Test URL: http://localhost:5173/menu?token=$token\n\n";

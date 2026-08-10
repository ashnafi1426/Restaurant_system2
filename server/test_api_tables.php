<?php

// Test the actual API endpoint with authentication

$token = '165|X1QMKONpYQlJduwLcj6TMhXPLl5AZPXGlPDVEtEH905e15dc'; // From console logs
$apiUrl = 'http://127.0.0.1:8000/api/manager/restaurant-tables';

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $apiUrl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Accept: application/json',
    'Content-Type: application/json',
    'Authorization: Bearer ' . $token,
]);

echo "Testing API: $apiUrl\n";
echo "With token: " . substr($token, 0, 20) . "...\n\n";

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error = curl_error($ch);
curl_close($ch);

echo "HTTP Code: $httpCode\n";
if ($error) {
    echo "CURL Error: $error\n";
}

echo "\n=== RESPONSE ===\n";
$data = json_decode($response, true);
echo json_encode($data, JSON_PRETTY_PRINT) . "\n";

if (isset($data['data']['data'])) {
    echo "\n=== TABLE COUNT ===\n";
    echo "Total in response: " . count($data['data']['data']) . "\n";
    echo "Total from pagination: " . ($data['data']['total'] ?? 'N/A') . "\n";
}

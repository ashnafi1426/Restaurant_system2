<?php
require_once __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\User;
use App\Models\Waiter;

// Clear and seed fresh data
echo "1. Seeding database...\n";
Artisan::call('migrate:fresh', ['--seed' => true]);
echo "Database seeded!\n\n";

// Get manager user
$manager = User::where('email', 'manager@example.com')->first();
if (!$manager) {
    $manager = User::where('role', 'manager')->first();
}

if (!$manager) {
    echo "ERROR: No manager found\n";
    exit(1);
}

echo "2. Manager found: " . $manager->email . "\n";
echo "   Manager ID: " . $manager->id . "\n\n";

// Create token
$token = $manager->createToken('api-test')->plainTextToken;
echo "3. Token created (first 30 chars): " . substr($token, 0, 30) . "...\n\n";

// Test waiter creation
echo "4. Testing POST /api/manager/waiters\n";
echo "   Method: POST\n";
echo "   Endpoint: http://127.0.0.1:8000/api/manager/waiters\n";
echo "   Auth: Bearer " . substr($token, 0, 20) . "...\n\n";

$waiterData = [
    'first_name' => 'Test',
    'last_name' => 'Waiter',
    'email' => 'test.waiter.' . time() . '@example.com',
    'phone' => '1234567890',
    'password' => 'password123',
    'section' => 'Dining Area',
    'shift' => 'morning',
    'experience_level' => 'senior',
    'status' => 'active',
    'maximum_orders' => 10,
];

echo "   Request Body:\n";
foreach ($waiterData as $key => $value) {
    echo "   - {$key}: {$value}\n";
}

// Test using curl
echo "\n5. Running CURL test...\n";
$curl_cmd = sprintf(
    'curl -X POST "http://127.0.0.1:8000/api/manager/waiters" -H "Authorization: Bearer %s" -H "Content-Type: application/json" -d \'%s\' 2>/dev/null',
    $token,
    json_encode($waiterData)
);

$output = shell_exec($curl_cmd);
echo "   Response:\n";
echo "   " . str_replace("\n", "\n   ", $output) . "\n\n";

echo "6. Verifying waiter was created in database...\n";
$createdWaiter = Waiter::where('section', 'Dining Area')->latest()->first();
if ($createdWaiter) {
    echo "   ✓ Waiter created successfully!\n";
    echo "   - ID: " . $createdWaiter->id . "\n";
    echo "   - User: " . $createdWaiter->user->email . "\n";
    echo "   - Status: " . $createdWaiter->status . "\n";
} else {
    echo "   ✗ Waiter was NOT created\n";
}

echo "\nTest complete!\n";
?>

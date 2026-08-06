<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Services\PaymentService;

echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
echo "🧪 TESTING PAYMENT METADATA PRESERVATION\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";

$paymentService = new PaymentService();

// Test data similar to what QR menu sends
$testData = [
    'amount' => 25.50,
    'first_name' => 'Test',
    'last_name' => 'User',
    'email' => 'test@example.com',
    'phone' => '+251912345678',
    'guest_id' => '019fc698-90ea-7029-9338-8b02f70d07ca', // Existing guest
    'room_id' => '22ad904d-3081-4257-8430-ad8ebee6a602', // Existing room
    'metadata' => [
        'type' => 'order',
        'room_id' => '22ad904d-3081-4257-8430-ad8ebee6a602',
        'items' => [
            [
                'menu_item_id' => 'test-item-1',
                'name' => 'Test Burger',
                'quantity' => 2,
                'price' => 10.00,
                'total' => 20.00,
            ],
            [
                'menu_item_id' => 'test-item-2',
                'name' => 'Test Fries',
                'quantity' => 1,
                'price' => 5.50,
                'total' => 5.50,
            ],
        ],
        'calculation' => [
            'subtotal' => 25.50,
            'tax' => 0,
            'discount' => 0,
            'total' => 25.50,
        ],
        'notes' => 'Test order - no mayo please',
    ],
];

echo "📝 Creating test payment with items...\n";
$payment = $paymentService->createOrderPayment($testData);

echo "\n✅ Payment Created:\n";
echo "   ID: {$payment->id}\n";
echo "   TX Ref: {$payment->tx_ref}\n";
echo "   Amount: {$payment->amount}\n";
echo "   Status: {$payment->status}\n";

echo "\n📦 Checking Metadata:\n";
$metadata = $payment->metadata;

if (isset($metadata['items'])) {
    echo "   ✅ Items array EXISTS!\n";
    echo "   Items count: " . count($metadata['items']) . "\n";
    echo "\n   Items details:\n";
    foreach ($metadata['items'] as $index => $item) {
        echo "   " . ($index + 1) . ". " . $item['name'] . " (x" . $item['quantity'] . ") - $" . $item['total'] . "\n";
    }
} else {
    echo "   ❌ Items array MISSING!\n";
}

if (isset($metadata['calculation'])) {
    echo "\n   ✅ Calculation EXISTS!\n";
    echo "      Subtotal: " . $metadata['calculation']['subtotal'] . "\n";
    echo "      Total: " . $metadata['calculation']['total'] . "\n";
} else {
    echo "\n   ❌ Calculation MISSING!\n";
}

echo "\n📋 All Metadata Keys: " . implode(', ', array_keys($metadata)) . "\n";

echo "\n━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
echo "✅ TEST COMPLETED - Check if items and calculation are preserved\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";

// Clean up test payment
echo "\n🧹 Cleaning up test payment...\n";
$payment->delete();
echo "✅ Test payment deleted\n";

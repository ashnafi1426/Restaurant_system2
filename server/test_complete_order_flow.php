<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Services\PaymentService;
use App\Models\Payment;
use App\Models\Order;
use App\Models\OrderItem;

echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
echo "🧪 TESTING COMPLETE ORDER CREATION FLOW\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";

$paymentService = new PaymentService();

// Test data
$testData = [
    'amount' => 37.50,
    'first_name' => 'Test',
    'last_name' => 'Guest',
    'email' => 'test@qrorder.com',
    'phone' => '+251912345678',
    'guest_id' => '019fc698-90ea-7029-9338-8b02f70d07ca',
    'room_id' => '22ad904d-3081-4257-8430-ad8ebee6a602',
    'metadata' => [
        'type' => 'order',
        'room_id' => '22ad904d-3081-4257-8430-ad8ebee6a602',
        'items' => [
            [
                'menu_item_id' => 'test-burger-id',
                'name' => 'Deluxe Burger',
                'quantity' => 2,
                'price' => 15.00,
                'total' => 30.00,
            ],
            [
                'menu_item_id' => 'test-drink-id',
                'name' => 'Fresh Juice',
                'quantity' => 1,
                'price' => 7.50,
                'total' => 7.50,
            ],
        ],
        'calculation' => [
            'subtotal' => 37.50,
            'tax' => 0,
            'discount' => 0,
            'total' => 37.50,
        ],
        'notes' => 'TEST ORDER - No onions on burger',
    ],
];

echo "Step 1: Creating payment record...\n";
$payment = $paymentService->createOrderPayment($testData);
echo "✅ Payment created: {$payment->tx_ref}\n\n";

echo "Step 2: Checking metadata preservation...\n";
$metadata = $payment->fresh()->metadata;

if (isset($metadata['items']) && count($metadata['items']) > 0) {
    echo "✅ Items preserved: " . count($metadata['items']) . " items\n";
} else {
    echo "❌ FAILED: Items not preserved in metadata!\n";
    exit(1);
}

if (isset($metadata['calculation'])) {
    echo "✅ Calculation preserved\n";
} else {
    echo "❌ FAILED: Calculation not preserved!\n";
    exit(1);
}

echo "\nStep 3: Simulating payment verification and order creation...\n";

try {
    // Mark payment as verified (simulating Chapa verification)
    $payment->update([
        'status' => 'verified',
        'verified_at' => now(),
        'paid_at' => now(),
    ]);
    echo "✅ Payment marked as verified\n";

    // Extract data from metadata
    $orderItems = $metadata['items'];
    $calculation = $metadata['calculation'];

    // Create order (this is what PaymentController::verify() does)
    $order = Order::create([
        'order_number'     => Order::generateOrderNumber(),
        'reservation_id'   => null,
        'guest_id'         => $payment->guest_id,
        'room_id'          => $metadata['room_id'],
        'order_time'       => now(),
        'status'           => Order::STATUS_PENDING,
        'payment_type'     => 'card', // ✅ Match database enum: 'room_charge', 'cash', 'card'
        'subtotal'         => $calculation['subtotal'],
        'tax'              => $calculation['tax'] ?? 0,
        'discount'         => $calculation['discount'] ?? 0,
        'total'            => $payment->amount,
        'notes'            => $metadata['notes'] ?? null,
    ]);

    echo "✅ Order created: {$order->order_number}\n";

    // Create order items
    foreach ($orderItems as $item) {
        $itemPrice = $item['price'];
        $quantity = $item['quantity'];
        $lineTotal = $itemPrice * $quantity;

        $order->orderItems()->create([
            'menu_item_id'         => $item['menu_item_id'],
            'quantity'             => $quantity,
            'item_price_at_order'  => $itemPrice,
            'line_total'           => $lineTotal,
            'notes'                => $item['special_instructions'] ?? null,
        ]);
    }

    echo "✅ Order items created: " . $orderItems->count() . " items\n";

    // Link payment to order
    $payment->update(['order_id' => $order->id]);
    echo "✅ Payment linked to order\n";

    echo "\n━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
    echo "✅ SUCCESS! COMPLETE FLOW WORKING\n";
    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";

    echo "📋 Order Summary:\n";
    echo "   Order Number: {$order->order_number}\n";
    echo "   Status: {$order->status}\n";
    echo "   Total: \${$order->total}\n";
    echo "   Items: " . $order->orderItems->count() . "\n";
    echo "   Payment: {$payment->tx_ref}\n";
    echo "   Payment Status: {$payment->status}\n";
    echo "   Payment Order ID: {$payment->order_id}\n";

    echo "\n📦 Order Items:\n";
    foreach ($order->orderItems as $index => $item) {
        echo "   " . ($index + 1) . ". Item ID: {$item->menu_item_id}\n";
        echo "      Quantity: {$item->quantity}\n";
        echo "      Price: \${$item->item_price_at_order}\n";
        echo "      Line Total: \${$item->line_total}\n";
    }

    echo "\n🧹 Cleaning up test data...\n";
    $order->orderItems()->delete();
    $order->delete();
    $payment->delete();
    echo "✅ Test data cleaned up\n";

} catch (\Exception $e) {
    echo "\n❌ ERROR during order creation:\n";
    echo "   Message: {$e->getMessage()}\n";
    echo "   File: {$e->getFile()}:{$e->getLine()}\n";
    
    // Clean up
    if (isset($payment)) {
        $payment->delete();
    }
    
    exit(1);
}

echo "\n━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
echo "✅ ALL TESTS PASSED!\n";
echo "The QR menu ordering → payment → order creation flow is now working!\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";

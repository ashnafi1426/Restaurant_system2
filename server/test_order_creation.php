<?php

/**
 * Test Order Creation After Payment
 * 
 * This script tests the payment-to-order flow manually
 * Run: php test_order_creation.php
 */

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Payment;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Guest;
use App\Models\Room;
use Illuminate\Support\Facades\Log;

echo "🧪 Testing Order Creation After Payment\n";
echo "==========================================\n\n";

// Find a verified payment with order metadata
echo "1️⃣ Finding a verified payment with order data...\n";

$payment = Payment::where('status', 'verified')
    ->whereNotNull('metadata')
    ->whereRaw("JSON_EXTRACT(metadata, '$.type') = 'order'")
    ->orderBy('created_at', 'desc')
    ->first();

if (!$payment) {
    echo "❌ No verified payments with order metadata found.\n";
    echo "💡 Try placing an order via QR menu and completing payment first.\n";
    exit(1);
}

echo "✅ Found payment: {$payment->tx_ref}\n";
echo "   Payment ID: {$payment->id}\n";
echo "   Status: {$payment->status}\n";
echo "   Amount: {$payment->amount}\n";

// Check if order already exists
if ($payment->order_id) {
    echo "\n✅ Order already exists for this payment\n";
    echo "   Order ID: {$payment->order_id}\n";
    
    $order = Order::find($payment->order_id);
    if ($order) {
        echo "   Order Number: {$order->order_number}\n";
        echo "   Status: {$order->status}\n";
        echo "   Order Time: {$order->order_time}\n";
        echo "   Items Count: " . $order->orderItems->count() . "\n";
    }
    
    exit(0);
}

echo "\n2️⃣ Payment has no order linked. Creating order...\n";

// Extract metadata
$metadata = $payment->metadata;
$calculation = $metadata['calculation'] ?? [];
$orderItems = $metadata['items'] ?? [];
$roomId = $metadata['room_id'] ?? null;
$notes = $metadata['notes'] ?? null;

echo "   Guest ID: {$payment->guest_id}\n";
echo "   Room ID: {$roomId}\n";
echo "   Items Count: " . count($orderItems) . "\n";
echo "   Total: {$payment->amount}\n";

if (empty($orderItems)) {
    echo "❌ No items found in payment metadata\n";
    echo "📋 Metadata: " . json_encode($metadata, JSON_PRETTY_PRINT) . "\n";
    exit(1);
}

try {
    // Create order
    echo "\n3️⃣ Creating Order record...\n";
    
    $order = Order::create([
        'order_number'     => Order::generateOrderNumber(),
        'guest_id'         => $payment->guest_id,
        'room_id'          => $roomId,
        'order_time'       => now(),
        'status'           => Order::STATUS_PENDING,
        'source'           => 'qr_menu',
        'payment_type'     => 'chapa',
        'subtotal'         => $calculation['subtotal'] ?? $payment->amount,
        'tax'              => $calculation['tax'] ?? 0,
        'discount'         => $calculation['discount'] ?? 0,
        'total'            => $payment->amount,
        'notes'            => $notes,
        'special_requests' => null,
        'user_id'          => null,
    ]);
    
    echo "✅ Order created: {$order->order_number}\n";
    echo "   Order ID: {$order->id}\n";
    echo "   Status: {$order->status}\n";
    
    // Create order items
    echo "\n4️⃣ Creating Order Items...\n";
    
    foreach ($orderItems as $item) {
        echo "   📦 Item: {$item['name']} x {$item['quantity']}\n";
        
        $order->orderItems()->create([
            'menu_item_id'         => $item['menu_item_id'],
            'quantity'             => $item['quantity'],
            'price'                => $item['price'] ?? 0,
            'special_instructions' => $item['special_instructions'] ?? null,
        ]);
    }
    
    echo "✅ All items created\n";
    
    // Link payment to order
    echo "\n5️⃣ Linking payment to order...\n";
    $payment->update(['order_id' => $order->id]);
    echo "✅ Payment linked\n";
    
    echo "\n";
    echo "==========================================\n";
    echo "✅ SUCCESS! Order created and visible to chef!\n";
    echo "==========================================\n";
    echo "Order Number: {$order->order_number}\n";
    echo "Status: {$order->status}\n";
    echo "Items: " . $order->orderItems->count() . "\n";
    echo "Total: {$order->total}\n";
    echo "\n";
    echo "👨‍🍳 Chef can now see this order in Pending Orders!\n";
    
} catch (\Exception $e) {
    echo "\n❌ ERROR: " . $e->getMessage() . "\n";
    echo "📄 File: " . $e->getFile() . "\n";
    echo "📍 Line: " . $e->getLine() . "\n";
    echo "\n🔍 Stack Trace:\n";
    echo $e->getTraceAsString() . "\n";
    exit(1);
}

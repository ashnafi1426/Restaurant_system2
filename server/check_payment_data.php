<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
echo "🔍 CHECKING PAYMENT AND ORDER DATA\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";

// Check the specific payment
$txRef = 'TX-20260805093409-IESANIXD';
$payment = \App\Models\Payment::where('tx_ref', $txRef)->first();

if ($payment) {
    echo "✅ Payment Found:\n";
    echo "   TX Ref: {$payment->tx_ref}\n";
    echo "   Status: {$payment->status}\n";
    echo "   Amount: {$payment->amount}\n";
    echo "   Guest ID: " . ($payment->guest_id ?? 'NULL') . "\n";
    echo "   Order ID: " . ($payment->order_id ?? 'NULL') . "\n";
    echo "\n";
    
    echo "📦 Payment Metadata:\n";
    $metadata = $payment->metadata;
    if ($metadata) {
        echo "   Type: " . ($metadata['type'] ?? 'MISSING') . "\n";
        echo "   Room ID: " . ($metadata['room_id'] ?? 'MISSING') . "\n";
        
        if (isset($metadata['items'])) {
            echo "   Items Count: " . count($metadata['items']) . "\n";
            echo "\n   Items Details:\n";
            foreach ($metadata['items'] as $index => $item) {
                echo "   " . ($index + 1) . ". Menu Item ID: " . ($item['menu_item_id'] ?? 'MISSING') . "\n";
                echo "      Name: " . ($item['name'] ?? 'MISSING') . "\n";
                echo "      Quantity: " . ($item['quantity'] ?? 'MISSING') . "\n";
                echo "      Price: " . ($item['price'] ?? 'MISSING') . "\n";
                echo "      Total: " . ($item['total'] ?? 'MISSING') . "\n";
            }
        } else {
            echo "   ❌ NO ITEMS FOUND IN METADATA!\n";
        }
        
        if (isset($metadata['calculation'])) {
            echo "\n   Calculation:\n";
            echo "      Subtotal: " . ($metadata['calculation']['subtotal'] ?? 'MISSING') . "\n";
            echo "      Tax: " . ($metadata['calculation']['tax'] ?? 'MISSING') . "\n";
            echo "      Total: " . ($metadata['calculation']['total'] ?? 'MISSING') . "\n";
        }
    } else {
        echo "   ❌ NO METADATA FOUND!\n";
    }
} else {
    echo "❌ Payment with TX Ref '{$txRef}' NOT FOUND\n";
}

echo "\n━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
echo "📋 RECENT PAYMENTS:\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";

$recentPayments = \App\Models\Payment::orderBy('created_at', 'desc')->take(3)->get();
foreach ($recentPayments as $p) {
    echo "TX: {$p->tx_ref} | Status: {$p->status} | Amount: {$p->amount} | Order: " . ($p->order_id ?? 'NULL') . "\n";
}

echo "\n━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
echo "🍽️ RECENT ORDERS:\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";

$recentOrders = \App\Models\Order::orderBy('created_at', 'desc')->take(5)->get();
if ($recentOrders->count() > 0) {
    foreach ($recentOrders as $o) {
        echo "Order: {$o->order_number} | Status: {$o->status} | Total: {$o->total} | Created: {$o->created_at}\n";
    }
} else {
    echo "❌ NO ORDERS FOUND IN DATABASE!\n";
}

echo "\n━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";

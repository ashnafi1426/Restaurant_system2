<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Waiter;
use App\Models\DeliveryTask;

$waiters = Waiter::all();
foreach ($waiters as $w) {
    $activeCount = DeliveryTask::where('waiter_id', $w->id)
        ->whereIn('status', ['assigned', 'accepted', 'picked_up', 'on_delivery'])
        ->whereHas('order', fn($q) => $q->whereIn('status', ['pending', 'preparing', 'ready', 'on_delivery']))
        ->count();
    echo "Waiter {$w->id} real active: {$activeCount} (stored: {$w->current_orders})\n";
}

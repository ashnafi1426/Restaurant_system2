<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "Total Delivery Tasks: " . \App\Models\DeliveryTask::count() . "\n";
foreach (\App\Models\DeliveryTask::latest()->take(10)->get() as $t) {
    echo "ID: {$t->id} | Hotel: {$t->hotel_id} | OrderID: {$t->order_id} | RoomID: {$t->room_id} | FloorID: {$t->floor_id} | WaiterID: {$t->waiter_id} | Status: {$t->status}\n";
}

echo "\nTotal Waiter Floor Assignments: " . \App\Models\WaiterFloorAssignment::count() . "\n";
foreach (\App\Models\WaiterFloorAssignment::with(['waiter.user', 'floor'])->get() as $a) {
    $waiterName = $a->waiter?->user?->name ?? "Waiter #{$a->waiter_id}";
    $floorName = $a->floor?->name ?? "Floor {$a->floor_id}";
    echo "Assignment ID: {$a->id} | Waiter: {$waiterName} (ID: {$a->waiter_id}) | Floor: {$floorName} (ID: {$a->floor_id}) | Hotel: {$a->hotel_id} | Status: {$a->status} | Active: {$a->is_active}\n";
}

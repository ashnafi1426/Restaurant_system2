<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$tasks = \App\Models\DeliveryTask::whereIn('room_id', ['01a0ef45-52d9-713a-8c91-21b0d24cbee8', '01a0f920-a98d-701d-9583-0ce5b898b0f9'])->get();
foreach ($tasks as $t) {
    echo "Task ID: {$t->id} | Room: {$t->room_id} | Floor: {$t->floor_id} | Waiter ID: {$t->waiter_id} | Status: {$t->status}\n";
}

<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$columns = \Illuminate\Support\Facades\Schema::getColumnListing('rooms');
echo "Rooms columns: " . implode(', ', $columns) . "\n\n";

$rooms = \App\Models\Room::all();
echo "Total rooms: " . $rooms->count() . "\n";
foreach ($rooms as $r) {
    echo "Room ID: {$r->id} | Number: {$r->room_number} | floor_id: {$r->floor_id} | floor (col): {$r->floor} | Hotel: {$r->hotel_id}\n";
}

echo "\nFloors in database:\n";
foreach (\App\Models\Floor::all() as $f) {
    echo "Floor ID: {$f->id} | Number: {$f->floor_number} | Name: {$f->name} | Hotel: {$f->hotel_id}\n";
}

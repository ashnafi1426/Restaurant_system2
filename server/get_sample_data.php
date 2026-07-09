<?php

require_once 'vendor/autoload.php';
require_once 'bootstrap/app.php';

use App\Models\Reservation;
use App\Models\Guest;
use App\Models\Room;
use App\Models\MenuItem;

$data = [
    'reservations' => Reservation::select('id', 'reservation_number', 'guest_id')->limit(3)->get(),
    'guests' => Guest::select('id', 'name', 'email')->limit(3)->get(),
    'rooms' => Room::select('id', 'room_number')->limit(3)->get(),
    'menu_items' => MenuItem::select('id', 'name', 'price')->limit(5)->get(),
];

echo json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
?>

<?php

$conn = new mysqli('127.0.0.1', 'root', '14263208@aA', 'hotel');

if ($conn->connect_error) {
    die('Connection failed: ' . $conn->connect_error);
}

echo "=== ROOM DATABASE CHECK ===\n";
echo "\n";

// Check total count
$result = $conn->query('SELECT COUNT(*) as count FROM rooms');
$row = $result->fetch_assoc();
echo "Total rooms in database: " . $row['count'] . "\n";
echo "\n";

// Get all rooms
echo "=== ALL ROOMS ===\n";
$result = $conn->query('SELECT id, room_number, floor, status, room_type_id, is_active FROM rooms ORDER BY CAST(room_number AS UNSIGNED) ASC');

if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        echo "Room {$row['room_number']}: Floor={$row['floor']}, Status={$row['status']}, Active={$row['is_active']}, TypeID=" . substr($row['room_type_id'], 0, 8) . "\n";
    }
} else {
    echo "No rooms found!\n";
}

echo "\n";
echo "=== SEARCHING FOR ROOM 202 ===\n";

$result = $conn->query("SELECT * FROM rooms WHERE room_number = '202'");

if ($result->num_rows > 0) {
    $row = $result->fetch_assoc();
    echo "✓ Room 202 FOUND\n";
    echo "  Room Number: {$row['room_number']}\n";
    echo "  Floor: {$row['floor']}\n";
    echo "  Status: {$row['status']}\n";
    echo "  Active: {$row['is_active']}\n";
    echo "  Room Type ID: {$row['room_type_id']}\n";
    echo "  Created At: {$row['created_at']}\n";
} else {
    echo "✗ Room 202 NOT FOUND\n";
}

echo "\n";
echo "=== SEARCH LIKE TEST ===\n";
$result = $conn->query("SELECT room_number, floor, status FROM rooms WHERE LOWER(room_number) LIKE '%202%'");
echo "Rooms with LIKE '%202%': " . $result->num_rows . "\n";
while ($row = $result->fetch_assoc()) {
    echo "  - {$row['room_number']} (Floor: {$row['floor']}, Status: {$row['status']})\n";
}

echo "\n";
echo "=== ROOM TYPE RELATIONSHIPS ===\n";
$result = $conn->query('SELECT r.room_number, rt.name FROM rooms r LEFT JOIN room_types rt ON r.room_type_id = rt.id');
while ($row = $result->fetch_assoc()) {
    echo "Room {$row['room_number']}: Type = {$row['name']}\n";
}

echo "\n";
echo "=== CACHE TABLE CHECK ===\n";
$result = $conn->query('SELECT COUNT(*) as count FROM cache');
if ($result) {
    $row = $result->fetch_assoc();
    echo "Cache entries: " . $row['count'] . "\n";
} else {
    echo "Cache table query failed\n";
}

$conn->close();
echo "\nDone!\n";
?>

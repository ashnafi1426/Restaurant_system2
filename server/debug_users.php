<?php

// Load Laravel
require_once __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';

// Get DB facade
use Illuminate\Support\Facades\DB;

// Get all users
$users = DB::table('users')->select('id', 'email', 'first_name', 'last_name', 'is_active', 'role', 'password_hash')->get();

echo "\n=== ALL USERS IN DATABASE ===\n";
foreach ($users as $user) {
    echo "Email: " . $user->email . "\n";
    echo "Name: " . $user->first_name . " " . $user->last_name . "\n";
    echo "Role: " . $user->role . "\n";
    echo "Active: " . ($user->is_active ? 'Yes' : 'No') . "\n";
    echo "Password Hash Length: " . strlen($user->password_hash) . " chars\n";
    echo "---\n";
}

// Test password hash for cashier
echo "\n=== PASSWORD VERIFICATION TEST ===\n";
$cashier = DB::table('users')->where('email', 'cashier@hotel.com')->first();

if ($cashier) {
    echo "Found Cashier User\n";
    echo "Email: " . $cashier->email . "\n";
    echo "Password Hash: " . $cashier->password_hash . "\n";
    
    // Test with Hash
    $testPassword = 'Cashier123@';
    $isValid = password_verify($testPassword, $cashier->password_hash);
    echo "Password Verification: " . ($isValid ? 'PASS' : 'FAIL') . "\n";
} else {
    echo "ERROR: Cashier user not found!\n";
}

<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "=== WAITERS TABLE (" . \App\Models\Waiter::withoutTenant()->count() . ") ===\n";
foreach (\App\Models\Waiter::withoutTenant()->with('user')->get() as $w) {
    echo "ID: {$w->id} | hotel_id: {$w->hotel_id} | user_id: {$w->user_id} | name: " . ($w->user ? ($w->user->first_name . ' ' . $w->user->last_name) : 'No User') . " | status: {$w->status} | section: {$w->section}\n";
}

echo "\n=== USERS WITH ROLE WAITER OR STAFF (" . \App\Models\User::whereIn('role', ['waiter', 'staff'])->count() . ") ===\n";
foreach (\App\Models\User::whereIn('role', ['waiter', 'staff'])->get() as $u) {
    $hasWaiter = \App\Models\Waiter::withoutTenant()->where('user_id', $u->id)->exists();
    echo "User ID: {$u->id} | hotel_id: {$u->hotel_id} | name: {$u->first_name} {$u->last_name} | email: {$u->email} | role: {$u->role} | has_waiter_record: " . ($hasWaiter ? 'YES' : 'NO') . "\n";
}

echo "\n=== HOTELS ===\n";
foreach (\App\Models\Hotel::all() as $h) {
    echo "Hotel: {$h->id} - {$h->name}\n";
}

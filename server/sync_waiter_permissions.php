<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\User;
use App\Models\Role;
use App\Models\Permission;
use Illuminate\Support\Facades\Cache;

Cache::flush();

echo "=== SYNCING WAITER ROLE PERMISSIONS ===\n";

$waiterRole = Role::where('slug', 'waiter')->orWhere('name', 'Waiter')->first();
if (!$waiterRole) {
    $waiterRole = Role::create([
        'name' => 'Waiter',
        'slug' => 'waiter',
        'display_name' => 'Waiter',
        'description' => 'Dining & Room Service Delivery Specialist',
        'is_system' => true,
        'is_active' => true,
    ]);
}

$waiterPermSlugs = [
    'dashboard.view',
    'orders.view',
    'orders.accept',
    'orders.deliver',
    'delivery.view',
    'delivery.accept',
    'delivery.pickup',
    'delivery.deliver',
    'notifications.view'
];

$permIds = [];
foreach ($waiterPermSlugs as $slug) {
    $perm = Permission::firstOrCreate(
        ['slug' => $slug],
        ['name' => ucwords(str_replace(['.', '_'], ' ', $slug)), 'module' => explode('.', $slug)[0], 'action' => explode('.', $slug)[1] ?? 'view', 'is_active' => true]
    );
    $permIds[] = $perm->id;
}

$waiterRole->permissions()->sync($permIds);

echo "Waiter role permissions synced! Count: " . count($permIds) . "\n";

// Sync all users with role 'waiter'
$waiterUsers = User::whereRaw('LOWER(role) = ?', ['waiter'])->get();
foreach ($waiterUsers as $user) {
    $user->roles()->syncWithoutDetaching([$waiterRole->id => ['is_primary' => true]]);
    Cache::forget("user_permissions_{$user->id}");
    echo "Synced user: {$user->email}\n";
}

echo "=== SYNC COMPLETE ===\n";

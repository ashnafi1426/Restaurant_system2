<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\User;
use App\Models\Role;
use App\Services\AuthorizationService;

$authService = app(AuthorizationService::class);

echo "=== CHECKING ROLES AND PERMISSIONS IN DB ===\n";

$roles = Role::with('permissions')->get();
foreach ($roles as $role) {
    echo "Role: {$role->name} (slug: {$role->slug})\n";
    echo "  Permissions count: " . $role->permissions->count() . "\n";
    echo "  Permissions: " . implode(', ', $role->permissions->pluck('slug')->toArray()) . "\n\n";
}

echo "=== CHECKING WAITER USERS ===\n";
$waiters = User::where('role', 'waiter')->orWhereHas('roles', fn($q) => $q->where('slug', 'waiter'))->get();
foreach ($waiters as $u) {
    $effPerms = $authService->getEffectivePermissions($u);
    echo "User: {$u->email} (id: {$u->id}, role col: {$u->role})\n";
    echo "  Assigned Roles: " . implode(', ', $u->roles->pluck('slug')->toArray()) . "\n";
    echo "  Effective Permissions: " . implode(', ', $effPerms) . "\n\n";
}

<?php

if (!isset($app) || $app === true) {
    $app = require_once __DIR__ . '/bootstrap/app.php';
    $kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
    $kernel->bootstrap();
}

use App\Models\User;
use App\Models\Role;
use App\Services\AuthorizationService;

$authService = app(AuthorizationService::class);

echo "========================================\n";
echo "CHECKING ALL USERS WITH ROLE = 'cashier'\n";
echo "========================================\n";

$cashiers = User::where('role', 'cashier')->get();
echo "Found " . $cashiers->count() . " cashier user(s).\n\n";

foreach ($cashiers as $c) {
    echo "User ID: {$c->id} | Name: {$c->full_name} | Email: {$c->email} | Role Column: {$c->role}\n";
    $roles = $c->roles;
    echo "  RolesRelation: " . $roles->pluck('slug')->implode(', ') . "\n";
    
    $authService->invalidateUserCache($c->id);
    $effectivePerms = $authService->getEffectivePermissions($c);
    echo "  Effective Permissions (" . count($effectivePerms) . "): " . implode(', ', $effectivePerms) . "\n\n";
}

echo "========================================\n";
echo "CHECKING CASHIER ROLE IN DATABASE\n";
echo "========================================\n";
$cashierRole = Role::where('slug', 'cashier')->first();
if ($cashierRole) {
    echo "Role ID: {$cashierRole->id} | Name: {$cashierRole->name}\n";
    echo "Permissions (" . $cashierRole->permissions->count() . "): " . $cashierRole->permissions->pluck('slug')->implode(', ') . "\n";
}

<?php

if (!isset($app) || $app === true) {
    $app = require_once __DIR__ . '/bootstrap/app.php';
    $kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
    $kernel->bootstrap();
}

use App\Models\Role;
use App\Models\Permission;
use App\Services\AuthorizationService;

echo "Syncing Cashier role permissions...\n";

$seeder = new \Database\Seeders\RbacSeeder();
$seeder->run();

$authService = app(AuthorizationService::class);
$cashierRole = Role::where('slug', 'cashier')->first();

if ($cashierRole) {
    $authService->invalidateRoleCache($cashierRole);
    echo "SUCCESS: Cashier role permissions synced. Permissions count: " . $cashierRole->permissions()->count() . "\n";
    foreach ($cashierRole->permissions as $p) {
        echo "  - {$p->name} ({$p->slug})\n";
    }
} else {
    echo "ERROR: Cashier role not found.\n";
}

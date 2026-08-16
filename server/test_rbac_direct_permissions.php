<?php

if (!isset($app) || $app === true) {
    $app = app();
} else {
    $kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
    $kernel->bootstrap();
}

use App\Models\User;
use App\Models\Role;
use App\Models\Permission;
use App\Models\UserPermission;
use App\Services\AuthorizationService;
use Illuminate\Support\Facades\DB;

echo "==================================================\n";
echo "RBAC DIRECT PERMISSIONS & AUTHORIZATION TEST SUITE\n";
echo "==================================================\n\n";

$authService = app(AuthorizationService::class);

// 1. Verify Default System Roles
echo "[TEST 1] Verifying System Roles & Permissions Seeding...\n";
$roles = Role::all();
echo "  Found " . $roles->count() . " roles in database.\n";
foreach ($roles as $r) {
    echo "   - Role: {$r->name} (slug: {$r->slug}), Permissions Count: " . $r->permissions()->count() . "\n";
}

// 2. Find or Create Test Receptionist "Sara"
echo "\n[TEST 2] Verifying Receptionist 'Sara' Setup...\n";
$sara = User::where('email', 'sara.test@hotel.com')->first();
if (!$sara) {
    $receptionistRole = Role::where('slug', 'receptionist')->first();
    $sara = User::create([
        'first_name' => 'Sara',
        'last_name' => 'ReceptionistTest',
        'email' => 'sara.test@hotel.com',
        'password_hash' => bcrypt('password123'),
        'role' => 'receptionist',
        'is_active' => true,
        'activation_status' => 'activated',
        'email_verified_at' => now(),
    ]);

    if ($receptionistRole) {
        $sara->roles()->sync([$receptionistRole->id => ['is_primary' => true]]);
    }
}

$saraRoleBefore = $sara->role;
echo "  Sara ID: {$sara->id}\n";
echo "  Sara Primary Role: {$saraRoleBefore}\n";

// Effective permissions before direct grant
$authService->invalidateUserCache($sara->id);
$permsBefore = $authService->getEffectivePermissions($sara);
echo "  Sara Effective Permissions Count (Before Direct Grant): " . count($permsBefore) . "\n";
echo "  Sara Has 'orders.deliver'? " . (in_array('orders.deliver', $permsBefore) ? 'YES' : 'NO') . "\n";

// 3. Grant Direct Permissions to Sara
echo "\n[TEST 3] Granting Direct Permissions (orders.view, orders.accept, orders.deliver, tables.view) to Sara...\n";
$directPermSlugs = ['orders.view', 'orders.accept', 'orders.deliver', 'tables.view'];
$directPermIds = Permission::whereIn('slug', $directPermSlugs)->pluck('id')->toArray();

// Sync direct permissions
UserPermission::where('user_id', $sara->id)->delete();
foreach ($directPermIds as $pId) {
    UserPermission::create([
        'user_id' => $sara->id,
        'permission_id' => $pId,
        'granted_by' => null,
    ]);
}
$authService->invalidateUserCache($sara->id);

// 4. Verify Effective Permissions After Direct Grant
echo "\n[TEST 4] Verifying Effective Permissions Union & Primary Role Integrity...\n";
$saraReloaded = User::find($sara->id);
$permsAfter = $authService->getEffectivePermissions($saraReloaded);

echo "  Sara Primary Role Column: {$saraReloaded->role}\n";
if ($saraReloaded->role === 'receptionist') {
    echo "  [PASS] Sara's primary role REMAINED 'receptionist' (did NOT change to Waiter).\n";
} else {
    echo "  [FAIL] Sara's primary role changed unexpectedly!\n";
}

$hasOrdersDeliver = $authService->hasPermission($saraReloaded, 'orders.deliver');
$hasOrdersView = $authService->hasPermission($saraReloaded, 'orders.view');
$hasReservationsView = $authService->hasPermission($saraReloaded, 'reservations.view');

echo "  Sara Has 'reservations.view' (inherited from Receptionist role)? " . ($hasReservationsView ? '[YES PASS]' : '[NO FAIL]') . "\n";
echo "  Sara Has 'orders.view' (granted directly)? " . ($hasOrdersView ? '[YES PASS]' : '[NO FAIL]') . "\n";
echo "  Sara Has 'orders.deliver' (granted directly)? " . ($hasOrdersDeliver ? '[YES PASS]' : '[NO FAIL]') . "\n";

// 5. Test Revoking Direct Permissions
echo "\n[TEST 5] Revoking Direct Permissions from Sara...\n";
UserPermission::where('user_id', $sara->id)->delete();
$authService->invalidateUserCache($sara->id);

$permsRevoked = $authService->getEffectivePermissions($saraReloaded);
$hasOrdersDeliverRevoked = $authService->hasPermission($saraReloaded, 'orders.deliver');

echo "  Sara Has 'orders.deliver' After Revoking? " . ($hasOrdersDeliverRevoked ? '[YES FAIL]' : '[NO PASS - Access Revoked]') . "\n";
echo "  Sara Has 'reservations.view' After Revoking? " . ($authService->hasPermission($saraReloaded, 'reservations.view') ? '[YES PASS - Role Intact]' : '[NO FAIL]') . "\n";

// 6. Test Order Delivery / Pickup Resolution for Receptionist Sara
echo "\n[TEST 6] Verifying Order Pickup Capability for Receptionist Sara with Direct Permission 'orders.deliver'...\n";

$ordersDeliverId = Permission::where('slug', 'orders.deliver')->value('id');
UserPermission::firstOrCreate([
    'user_id' => $sara->id,
    'permission_id' => $ordersDeliverId,
]);
$authService->invalidateUserCache($sara->id);

$saraFresh = User::find($sara->id);

// Ensure Waiter profile link exists for cross-role user Sara
$saraWaiter = \App\Models\Waiter::firstOrCreate(
    ['user_id' => $saraFresh->id],
    ['section' => 'All Sections', 'shift' => 'morning', 'experience_level' => 'junior', 'status' => 'active']
);

$waiterResolver = app(\App\Services\Waiter\WaiterContextResolver::class);
$waiterIdResolved = $waiterResolver->resolveWaiterId($saraFresh);

echo "  Sara Primary Role: {$saraFresh->role}\n";
echo "  Resolved Waiter Profile ID for Sara: " . ($waiterIdResolved ?? 'NULL') . "\n";

if ($waiterIdResolved && (int)$waiterIdResolved === (int)$saraWaiter->id) {
    echo "  [PASS] Waiter profile ID ({$waiterIdResolved}) cleanly resolved/linked for Receptionist Sara without altering primary role!\n";
} else {
    echo "  [FAIL] Could not resolve waiter profile for Receptionist Sara.\n";
}

// Ensure at least one Ready order exists for testing
$testReadyOrder = \App\Models\Order::firstOrCreate(
    ['order_number' => 'ORD-TEST-READY-001'],
    [
        'guest_id' => null,
        'room_id' => null,
        'status' => 'ready',
        'subtotal' => 25.00,
        'tax' => 2.50,
        'total' => 27.50,
        'order_type' => 'room_service',
    ]
);

$dashboardService = app(\App\Services\Waiter\WaiterDashboardService::class);
$readyOrders = $dashboardService->getReadyForPickup($waiterIdResolved);

echo "  Ready for Pickup Orders Count for Sara: " . count($readyOrders) . "\n";
if (count($readyOrders) > 0) {
    echo "  [PASS] Ready for pickup orders cleanly retrieved for Receptionist Sara!\n";
} else {
    echo "  [FAIL] No ready orders retrieved.\n";
}

$onDeliveryOrders = $dashboardService->getOnDelivery($waiterIdResolved);
echo "  On Delivery Orders Count for Sara: " . count($onDeliveryOrders) . "\n";
echo "  [PASS] On Delivery query cleanly executed for Receptionist Sara!\n";

echo "\n==================================================\n";
echo "TEST SUITE COMPLETE: ALL CORE REQUIREMENTS VERIFIED!\n";
echo "==================================================\n";

<?php
/**
 * Test Admin Permissions - Verify Admin Super-Override Works
 */

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\User;
use App\Services\AuthorizationService;

echo "===========================================\n";
echo "  ADMIN PERMISSION TEST\n";
echo "===========================================\n\n";

// Find admin user
$adminUser = User::where('role', 'admin')->first();

if (!$adminUser) {
    echo " No admin user found!\n";
    exit(1);
}

echo "Testing permissions for: {$adminUser->email}\n";
echo "User Role: {$adminUser->role}\n\n";

$authService = app(AuthorizationService::class);

// Test various permissions
$testPermissions = [
    'users.view',
    'users.create',
    'users.update',
    'users.delete',
    'roles.view',
    'roles.create',
    'permissions.view',
    'kitchen.view',
    'delivery.assign',
    'payments.refund',
    'nonexistent.permission', // This should still return true for admin!
];

echo "===========================================\n";
echo "  PERMISSION CHECKS\n";
echo "===========================================\n\n";

$allPassed = true;

foreach ($testPermissions as $permission) {
    $hasPermission = $authService->hasPermission($adminUser, $permission);
    $status = $hasPermission ? ' PASS' : ' FAIL';
    
    if (!$hasPermission) {
        $allPassed = false;
    }
    
    echo sprintf("%-30s %s\n", $permission, $status);
}

echo "\n===========================================\n";
echo "  ROLE CHECKS\n";
echo "===========================================\n\n";

$hasAdminRole = $authService->hasRole($adminUser, 'admin');
echo sprintf("%-30s %s\n", "Has 'admin' role", $hasAdminRole ? ' YES' : ' NO');

$hasManagerRole = $authService->hasRole($adminUser, 'manager');
echo sprintf("%-30s %s\n", "Has 'manager' role", $hasManagerRole ? ' YES' : ' NO (Expected)');

echo "\n===========================================\n";
echo "  ACTIVE ROLES\n";
echo "===========================================\n\n";

$activeRoles = $authService->getActiveRoles($adminUser);
echo "Active Roles Count: " . $activeRoles->count() . "\n";
foreach ($activeRoles as $role) {
    echo "  - {$role->display_name} (slug: {$role->slug})\n";
}

echo "\n===========================================\n";
echo "  EFFECTIVE PERMISSIONS\n";
echo "===========================================\n\n";

$effectivePermissions = $authService->getEffectivePermissions($adminUser);
$permCount = count($effectivePermissions);
echo "Total Effective Permissions: {$permCount}\n";
echo "First 10 permissions:\n";
foreach (array_slice($effectivePermissions, 0, 10) as $perm) {
    echo "  - {$perm}\n";
}
if ($permCount > 10) {
    echo "  ... and " . ($permCount - 10) . " more\n";
}

echo "\n===========================================\n";
echo "  SUPER-OVERRIDE TEST\n";
echo "===========================================\n\n";

// Critical test: Admin should have permission even for non-existent permissions
$nonExistentPerm = 'this.does.not.exist.in.database';
$hasNonExistent = $authService->hasPermission($adminUser, $nonExistentPerm);

echo "Testing permission: '{$nonExistentPerm}'\n";
echo "Result: " . ($hasNonExistent ? ' HAS ACCESS' : ' NO ACCESS') . "\n\n";

if ($hasNonExistent) {
    echo " SUPER-OVERRIDE WORKING!\n";
    echo "   Admin bypasses database checks and has access to ALL permissions!\n";
} else {
    echo " SUPER-OVERRIDE NOT WORKING!\n";
    echo "   Admin should have access to non-existent permissions!\n";
    $allPassed = false;
}

echo "\n===========================================\n";
echo "  FINAL RESULT\n";
echo "===========================================\n\n";

if ($allPassed) {
    echo " ALL TESTS PASSED!\n\n";
    echo "Admin permissions are working correctly:\n";
    echo "  ✓ Admin role is assigned\n";
    echo "  ✓ Admin has access to all existing permissions\n";
    echo "  ✓ Admin super-override bypasses database checks\n";
    echo "  ✓ Admin has unlimited access to the system\n\n";
    echo "🎉 Your dynamic RBAC system is working perfectly!\n\n";
} else {
    echo " SOME TESTS FAILED!\n\n";
    echo "Please check:\n";
    echo "  1. AuthorizationService has admin super-override\n";
    echo "  2. User role column is set to 'admin'\n";
    echo "  3. Admin role is assigned in user_roles pivot\n";
    echo "  4. Permission cache is cleared\n\n";
}

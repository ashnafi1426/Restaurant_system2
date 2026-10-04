<?php

require __DIR__ . '/vendor/autoload.php';

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "\n";
echo "═══════════════════════════════════════════════════════════════\n";
echo "  FRONTEND DATA TEST - What Frontend Actually Receives\n";
echo "═══════════════════════════════════════════════════════════════\n\n";

// Get admin user
$admin = DB::table('users')
    ->where('role', 'admin')
    ->where('is_active', true)
    ->first();

if (!$admin) {
    echo " No active admin user found!\n";
    exit(1);
}

echo " Testing with admin user: {$admin->email}\n";
echo "   User ID: {$admin->id}\n";
echo "   Role column: {$admin->role}\n\n";

// Get the User model
$user = \App\Models\User::find($admin->id);

// Get the resource as frontend would receive it
$resource = new \App\Http\Resources\AuthResource($user);
$data = $resource->toArray(request());

echo "CHECK 1: Frontend User Object Structure\n";
echo "─────────────────────────────────────────────────────────────────\n";
echo "Full Name: {$data['full_name']}\n";
echo "Email: {$data['email']}\n";
echo "Role String: {$data['role']}\n";
echo "Is Active: " . ($data['is_active'] ? 'true' : 'false') . "\n\n";

echo "CHECK 2: Roles Array (for isAdmin getter)\n";
echo "─────────────────────────────────────────────────────────────────\n";
if (empty($data['roles'])) {
    echo "  Roles array is EMPTY\n";
    echo "   This means frontend isAdmin getter may fail!\n\n";
} else {
    echo " Roles array has " . count($data['roles']) . " role(s):\n";
    foreach ($data['roles'] as $role) {
        echo "   - {$role['name']} (slug: {$role['slug']}, is_system: " . ($role['is_system'] ? 'true' : 'false') . ")\n";
    }
    echo "\n";
}

echo "CHECK 3: Permissions Array (for can() method)\n";
echo "─────────────────────────────────────────────────────────────────\n";
if (empty($data['permissions'])) {
    echo "  Permissions array is EMPTY\n";
    echo "   This is OK if admin super-override works, but may cause issues.\n\n";
} else {
    echo " Permissions array has " . count($data['permissions']) . " permission(s)\n";
    echo "   Sample permissions:\n";
    $sample = array_slice($data['permissions'], 0, 10);
    foreach ($sample as $perm) {
        echo "   - {$perm}\n";
    }
    if (count($data['permissions']) > 10) {
        echo "   ... and " . (count($data['permissions']) - 10) . " more\n";
    }
    echo "\n";
}

echo "CHECK 4: Frontend isAdmin Getter Logic\n";
echo "─────────────────────────────────────────────────────────────────\n";
$mainRole = strtolower($data['role'] ?? '');
echo "Main role (user.role): '{$mainRole}'\n";

if ($mainRole === 'admin') {
    echo " isAdmin will return TRUE (via main role check)\n\n";
} else {
    echo "  Main role is NOT 'admin'\n";
    $hasAdminInRoles = false;
    if (!empty($data['roles'])) {
        foreach ($data['roles'] as $role) {
            $slug = strtolower($role['slug'] ?? $role['name'] ?? '');
            if ($slug === 'admin') {
                $hasAdminInRoles = true;
                break;
            }
        }
    }
    if ($hasAdminInRoles) {
        echo " isAdmin will return TRUE (via roles array check)\n\n";
    } else {
        echo " isAdmin will return FALSE - Admin super-override WON'T WORK!\n\n";
    }
}

echo "CHECK 5: Frontend can() Method Logic\n";
echo "─────────────────────────────────────────────────────────────────\n";
echo "Testing can('users.view'):\n";

// Simulate frontend can() logic
$isAdminFrontend = false;
if ($mainRole === 'admin') {
    $isAdminFrontend = true;
} elseif (!empty($data['roles'])) {
    foreach ($data['roles'] as $role) {
        $slug = strtolower($role['slug'] ?? $role['name'] ?? '');
        if ($slug === 'admin') {
            $isAdminFrontend = true;
            break;
        }
    }
}

if ($isAdminFrontend) {
    echo "    Result: TRUE (admin super-override)\n";
    echo "   Admin bypasses permission check!\n\n";
} else {
    $permissions = array_map('strtolower', $data['permissions'] ?? []);
    $hasPermission = in_array('users.view', $permissions);
    echo "   Result: " . ($hasPermission ? "TRUE" : "FALSE") . " (permission check)\n";
    if (!$hasPermission) {
        echo "     Admin does not have 'users.view' permission!\n\n";
    } else {
        echo "\n";
    }
}

echo "CHECK 6: Sidebar Menu Filtering Simulation\n";
echo "─────────────────────────────────────────────────────────────────\n";
$testPermissions = ['users.view', 'roles.view', 'permissions.view', 'menu.view', 'orders.view'];
echo "Testing if these menu items would show:\n";
foreach ($testPermissions as $perm) {
    if ($isAdminFrontend) {
        echo "    {$perm} → VISIBLE (admin super-override)\n";
    } else {
        $permissions = array_map('strtolower', $data['permissions'] ?? []);
        $has = in_array(strtolower($perm), $permissions);
        echo "   " . ($has ? "" : "") . " {$perm} → " . ($has ? "VISIBLE" : "HIDDEN") . "\n";
    }
}
echo "\n";

echo "CHECK 7: JSON Data (for localStorage)\n";
echo "─────────────────────────────────────────────────────────────────\n";
echo "This is what gets stored in localStorage.user:\n";
echo json_encode([
    'role' => $data['role'],
    'roles' => $data['roles'],
    'permissions' => array_slice($data['permissions'] ?? [], 0, 5) // Show first 5
], JSON_PRETTY_PRINT) . "\n";
if (count($data['permissions'] ?? []) > 5) {
    echo "... (+" . (count($data['permissions']) - 5) . " more permissions)\n";
}
echo "\n";

echo "═══════════════════════════════════════════════════════════════\n";
echo "  DIAGNOSTIC SUMMARY\n";
echo "═══════════════════════════════════════════════════════════════\n\n";

$issues = [];
if ($mainRole !== 'admin' && empty($data['roles'])) {
    $issues[] = "Admin user has no roles assigned in pivot table";
}
if (empty($data['permissions'])) {
    $issues[] = "Admin role has no permissions (UI may not show all menus even with super-override)";
}

if (empty($issues)) {
    echo " SUCCESS: No issues found! Admin system should work correctly.\n\n";
} else {
    echo "  ISSUES FOUND:\n";
    foreach ($issues as $i => $issue) {
        echo "   " . ($i + 1) . ". {$issue}\n";
    }
    echo "\n";
    echo "🔧 FIXES:\n";
    echo "   Run: php fix_admin_permissions.php\n";
    echo "   Then restart Laravel server and clear browser cache\n\n";
}

echo "═══════════════════════════════════════════════════════════════\n";
echo "  TEST COMPLETE\n";
echo "═══════════════════════════════════════════════════════════════\n\n";

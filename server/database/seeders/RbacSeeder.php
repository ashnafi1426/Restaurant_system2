<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use App\Models\Role;
use App\Models\Permission;
use App\Models\User;

class RbacSeeder extends Seeder
{
    public function run(): void
    {
        // Flush permission caches
        \Illuminate\Support\Facades\Cache::flush();

        // 1. Initial Permission List

        $permissionsData = [
            // Dashboard
            ['name' => 'View Dashboard', 'slug' => 'dashboard.view', 'module' => 'dashboard', 'action' => 'view'],

            // Users
            ['name' => 'View Users', 'slug' => 'users.view', 'module' => 'users', 'action' => 'view'],
            ['name' => 'Create Users', 'slug' => 'users.create', 'module' => 'users', 'action' => 'create'],
            ['name' => 'Update Users', 'slug' => 'users.update', 'module' => 'users', 'action' => 'update'],
            ['name' => 'Delete Users', 'slug' => 'users.delete', 'module' => 'users', 'action' => 'delete'],

            // Roles
            ['name' => 'View Roles', 'slug' => 'roles.view', 'module' => 'roles', 'action' => 'view'],
            ['name' => 'Create Roles', 'slug' => 'roles.create', 'module' => 'roles', 'action' => 'create'],
            ['name' => 'Update Roles', 'slug' => 'roles.update', 'module' => 'roles', 'action' => 'update'],
            ['name' => 'Delete Roles', 'slug' => 'roles.delete', 'module' => 'roles', 'action' => 'delete'],
            ['name' => 'Assign Role Permissions', 'slug' => 'roles.assign_permissions', 'module' => 'roles', 'action' => 'assign_permissions'],

            // Permissions
            ['name' => 'View Permissions', 'slug' => 'permissions.view', 'module' => 'permissions', 'action' => 'view'],
            ['name' => 'Create Permissions', 'slug' => 'permissions.create', 'module' => 'permissions', 'action' => 'create'],
            ['name' => 'Update Permissions', 'slug' => 'permissions.update', 'module' => 'permissions', 'action' => 'update'],
            ['name' => 'Delete Permissions', 'slug' => 'permissions.delete', 'module' => 'permissions', 'action' => 'delete'],

            // Guests
            ['name' => 'View Guests', 'slug' => 'guests.view', 'module' => 'guests', 'action' => 'view'],
            ['name' => 'Create Guests', 'slug' => 'guests.create', 'module' => 'guests', 'action' => 'create'],
            ['name' => 'Update Guests', 'slug' => 'guests.update', 'module' => 'guests', 'action' => 'update'],
            ['name' => 'Delete Guests', 'slug' => 'guests.delete', 'module' => 'guests', 'action' => 'delete'],

            // Reservations
            ['name' => 'View Reservations', 'slug' => 'reservations.view', 'module' => 'reservations', 'action' => 'view'],
            ['name' => 'Create Reservations', 'slug' => 'reservations.create', 'module' => 'reservations', 'action' => 'create'],
            ['name' => 'Update Reservations', 'slug' => 'reservations.update', 'module' => 'reservations', 'action' => 'update'],
            ['name' => 'Cancel Reservations', 'slug' => 'reservations.cancel', 'module' => 'reservations', 'action' => 'cancel'],
            ['name' => 'Check-in Guest', 'slug' => 'reservations.checkin', 'module' => 'reservations', 'action' => 'checkin'],
            ['name' => 'Check-out Guest', 'slug' => 'reservations.checkout', 'module' => 'reservations', 'action' => 'checkout'],

            // Rooms
            ['name' => 'View Rooms', 'slug' => 'rooms.view', 'module' => 'rooms', 'action' => 'view'],
            ['name' => 'Create Rooms', 'slug' => 'rooms.create', 'module' => 'rooms', 'action' => 'create'],
            ['name' => 'Update Rooms', 'slug' => 'rooms.update', 'module' => 'rooms', 'action' => 'update'],
            ['name' => 'Delete Rooms', 'slug' => 'rooms.delete', 'module' => 'rooms', 'action' => 'delete'],
            ['name' => 'Assign Room', 'slug' => 'rooms.assign', 'module' => 'rooms', 'action' => 'assign'],

            // Tables
            ['name' => 'View Tables', 'slug' => 'tables.view', 'module' => 'tables', 'action' => 'view'],
            ['name' => 'Create Tables', 'slug' => 'tables.create', 'module' => 'tables', 'action' => 'create'],
            ['name' => 'Update Tables', 'slug' => 'tables.update', 'module' => 'tables', 'action' => 'update'],
            ['name' => 'Delete Tables', 'slug' => 'tables.delete', 'module' => 'tables', 'action' => 'delete'],

            // Menu
            ['name' => 'View Menu', 'slug' => 'menu.view', 'module' => 'menu', 'action' => 'view'],
            ['name' => 'Create Menu Items', 'slug' => 'menu.create', 'module' => 'menu', 'action' => 'create'],
            ['name' => 'Update Menu Items', 'slug' => 'menu.update', 'module' => 'menu', 'action' => 'update'],
            ['name' => 'Delete Menu Items', 'slug' => 'menu.delete', 'module' => 'menu', 'action' => 'delete'],
            ['name' => 'Toggle Menu Availability', 'slug' => 'menu.toggle_availability', 'module' => 'menu', 'action' => 'toggle_availability'],

            // Check-in / Check-out
            ['name' => 'View Check-in', 'slug' => 'checkin.view', 'module' => 'checkin', 'action' => 'view'],
            ['name' => 'Create Check-in', 'slug' => 'checkin.create', 'module' => 'checkin', 'action' => 'create'],
            ['name' => 'View Check-out', 'slug' => 'checkout.view', 'module' => 'checkout', 'action' => 'view'],
            ['name' => 'Create Check-out', 'slug' => 'checkout.create', 'module' => 'checkout', 'action' => 'create'],

            // Orders
            ['name' => 'View Orders', 'slug' => 'orders.view', 'module' => 'orders', 'action' => 'view'],
            ['name' => 'Create Orders', 'slug' => 'orders.create', 'module' => 'orders', 'action' => 'create'],
            ['name' => 'Update Orders', 'slug' => 'orders.update', 'module' => 'orders', 'action' => 'update'],
            ['name' => 'Cancel Orders', 'slug' => 'orders.cancel', 'module' => 'orders', 'action' => 'cancel'],
            ['name' => 'Assign Orders', 'slug' => 'orders.assign', 'module' => 'orders', 'action' => 'assign'],
            ['name' => 'Accept Orders', 'slug' => 'orders.accept', 'module' => 'orders', 'action' => 'accept'],
            ['name' => 'Prepare Orders', 'slug' => 'orders.prepare', 'module' => 'orders', 'action' => 'prepare'],
            ['name' => 'Mark Orders Ready', 'slug' => 'orders.mark_ready', 'module' => 'orders', 'action' => 'mark_ready'],
            ['name' => 'Deliver Orders', 'slug' => 'orders.deliver', 'module' => 'orders', 'action' => 'deliver'],
            ['name' => 'Update Order Status', 'slug' => 'orders.update_status', 'module' => 'orders', 'action' => 'update_status'],

            // Kitchen
            ['name' => 'View Kitchen Screen', 'slug' => 'kitchen.view', 'module' => 'kitchen', 'action' => 'view'],
            ['name' => 'Accept Food Order', 'slug' => 'kitchen.accept', 'module' => 'kitchen', 'action' => 'accept'],
            ['name' => 'Prepare Food Order', 'slug' => 'kitchen.prepare', 'module' => 'kitchen', 'action' => 'prepare'],
            ['name' => 'Mark Food Ready', 'slug' => 'kitchen.mark_ready', 'module' => 'kitchen', 'action' => 'mark_ready'],

            // Delivery
            ['name' => 'View Deliveries', 'slug' => 'delivery.view', 'module' => 'delivery', 'action' => 'view'],
            ['name' => 'Accept Delivery', 'slug' => 'delivery.accept', 'module' => 'delivery', 'action' => 'accept'],
            ['name' => 'Pickup Delivery', 'slug' => 'delivery.pickup', 'module' => 'delivery', 'action' => 'pickup'],
            ['name' => 'Deliver Order', 'slug' => 'delivery.deliver', 'module' => 'delivery', 'action' => 'deliver'],
            ['name' => 'Reassign Delivery', 'slug' => 'delivery.reassign', 'module' => 'delivery', 'action' => 'reassign'],

            // Payments
            ['name' => 'View Payments', 'slug' => 'payments.view', 'module' => 'payments', 'action' => 'view'],
            ['name' => 'Process Payment', 'slug' => 'payments.create', 'module' => 'payments', 'action' => 'create'],
            ['name' => 'Request Refund', 'slug' => 'payments.refund', 'module' => 'payments', 'action' => 'refund'],
            ['name' => 'Approve Refund', 'slug' => 'payments.approve_refund', 'module' => 'payments', 'action' => 'approve_refund'],

            // Reports
            ['name' => 'View Reports', 'slug' => 'reports.view', 'module' => 'reports', 'action' => 'view'],
            ['name' => 'Sales Reports', 'slug' => 'reports.sales', 'module' => 'reports', 'action' => 'sales'],
            ['name' => 'Order Reports', 'slug' => 'reports.orders', 'module' => 'reports', 'action' => 'orders'],
            ['name' => 'Occupancy Reports', 'slug' => 'reports.occupancy', 'module' => 'reports', 'action' => 'occupancy'],

            // Notifications
            ['name' => 'View Notifications', 'slug' => 'notifications.view', 'module' => 'notifications', 'action' => 'view'],

            // Waiters Management
            ['name' => 'View Waiters', 'slug' => 'waiters.view', 'module' => 'waiters', 'action' => 'view'],
            ['name' => 'Manage Waiters', 'slug' => 'waiters.manage', 'module' => 'waiters', 'action' => 'manage'],

            // Floor Management
            ['name' => 'View Floors', 'slug' => 'floors.view', 'module' => 'floors', 'action' => 'view'],
            ['name' => 'Assign Floors', 'slug' => 'floors.assign', 'module' => 'floors', 'action' => 'assign'],
            ['name' => 'Manage Floors', 'slug' => 'floors.manage', 'module' => 'floors', 'action' => 'manage'],

            // Audit Logs
            ['name' => 'View Audit Logs', 'slug' => 'audit_logs.view', 'module' => 'audit_logs', 'action' => 'view'],
        ];

        $createdPermissions = [];
        foreach ($permissionsData as $pData) {
            $permission = Permission::where('slug', $pData['slug'])->orWhere('name', $pData['name'])->first();
            if ($permission) {
                $permission->update([
                    'name' => $pData['name'],
                    'slug' => $pData['slug'],
                    'module' => $pData['module'],
                    'action' => $pData['action'],
                    'description' => "Permission to {$pData['name']}",
                    'is_active' => true,
                ]);
            } else {
                $permission = Permission::create([
                    'name' => $pData['name'],
                    'slug' => $pData['slug'],
                    'module' => $pData['module'],
                    'action' => $pData['action'],
                    'description' => "Permission to {$pData['name']}",
                    'is_active' => true,
                ]);
            }
            $createdPermissions[$permission->slug] = $permission->id;
        }

        // 2. Default System Roles
        // NOTE: Only Admin gets permissions by default.
        // All other roles start with NO permissions - Admin assigns them via the Permission Matrix UI.
        $rolesData = [
            [
                'name' => 'Admin',
                'slug' => 'admin',
                'description' => 'System Administrator with full access to configuration, users, roles, and permissions.',
                'is_system' => true,
                'permissions' => array_keys($createdPermissions), // Admin always gets ALL permissions
            ],
            [
                'name' => 'Manager',
                'slug' => 'manager',
                'description' => 'Hotel Operational Manager with oversight over hotel operations.',
                'is_system' => true,
                'permissions' => [
                    'dashboard.view', 'users.view', 'guests.view', 'reservations.view', 'rooms.view',
                    'tables.view', 'menu.view', 'orders.view', 'kitchen.view', 'delivery.view',
                    'delivery.reassign', 'payments.view', 'reports.view', 'reports.sales',
                    'reports.orders', 'reports.occupancy', 'waiters.view', 'waiters.manage',
                    'floors.view', 'floors.assign', 'floors.manage', 'notifications.view'
                ],
            ],
            [
                'name' => 'Receptionist',
                'slug' => 'receptionist',
                'description' => 'Front Desk & Guest Services Specialist.',
                'is_system' => true,
                'permissions' => [
                    'dashboard.view', 'guests.view', 'guests.create', 'guests.update',
                    'reservations.view', 'reservations.create', 'reservations.update',
                    'reservations.cancel', 'reservations.checkin', 'reservations.checkout',
                    'rooms.view', 'checkin.view', 'checkin.create', 'checkout.view',
                    'checkout.create', 'reports.view', 'notifications.view'
                ],
            ],
            [
                'name' => 'Chef',
                'slug' => 'chef',
                'description' => 'Kitchen Head & Culinary Operations Specialist.',
                'is_system' => true,
                'permissions' => [
                    'dashboard.view', 'kitchen.view', 'kitchen.accept', 'kitchen.prepare',
                    'kitchen.mark_ready', 'orders.view', 'notifications.view'
                ],
            ],
            [
                'name' => 'Waiter',
                'slug' => 'waiter',
                'description' => 'Dining & Room Service Delivery Specialist.',
                'is_system' => true,
                'permissions' => [
                    'dashboard.view', 'orders.view', 'orders.accept', 'orders.deliver',
                    'delivery.view', 'delivery.accept', 'delivery.pickup', 'delivery.deliver',
                    'notifications.view'
                ],
            ],
            [
                'name' => 'Cashier',
                'slug' => 'cashier',
                'description' => 'Financial Transactions & Billing Specialist.',
                'is_system' => true,
                'permissions' => [
                    'dashboard.view', 'payments.view', 'payments.create', 'payments.refund',
                    'orders.view', 'reports.view', 'notifications.view'
                ],
            ],
        ];

        $hasDisplayName = Schema::hasColumn('roles', 'display_name');
        foreach ($rolesData as $rData) {
            $rolePayload = [
                'name' => $rData['name'],
                'description' => $rData['description'],
                'is_system' => $rData['is_system'],
                'is_active' => true,
            ];
            if ($hasDisplayName) {
                $rolePayload['display_name'] = $rData['name'];
            }
            $role = Role::updateOrCreate(
                ['slug' => $rData['slug']],
                $rolePayload
            );

            // Sync role permissions
            $permissionIds = [];
            foreach ($rData['permissions'] as $pSlug) {
                if (isset($createdPermissions[$pSlug])) {
                    $permissionIds[] = $createdPermissions[$pSlug];
                }
            }
            $role->permissions()->sync($permissionIds);
        }

        // 3. Sync existing users into user_roles pivot
        $users = User::all();
        foreach ($users as $user) {
            if ($user->role) {
                $roleModel = Role::where('slug', $user->role)->first();
                if ($roleModel) {
                    DB::table('user_roles')->updateOrInsert(
                        ['user_id' => $user->id, 'role_id' => $roleModel->id],
                        ['is_primary' => true, 'updated_at' => now(), 'created_at' => now()]
                    );
                }
            }
        }
    }
}

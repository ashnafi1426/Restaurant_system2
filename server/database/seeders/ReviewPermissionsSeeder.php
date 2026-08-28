<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Cache;

class ReviewPermissionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Flush permission caches
        Cache::flush();

        // 1. Create Review System Permissions
        $reviewPermissions = [
            // Guest Review Operations
            [
                'name' => 'View Reviews',
                'slug' => 'reviews.view',
                'module' => 'reviews',
                'action' => 'view',
                'description' => 'Permission to view reviews',
                'is_active' => true,
            ],
            [
                'name' => 'Create Reviews',
                'slug' => 'reviews.create',
                'module' => 'reviews',
                'action' => 'create',
                'description' => 'Permission to submit reviews',
                'is_active' => true,
            ],
            [
                'name' => 'Update Reviews',
                'slug' => 'reviews.update',
                'module' => 'reviews',
                'action' => 'update',
                'description' => 'Permission to edit own reviews',
                'is_active' => true,
            ],
            [
                'name' => 'Delete Reviews',
                'slug' => 'reviews.delete',
                'module' => 'reviews',
                'action' => 'delete',
                'description' => 'Permission to delete own reviews',
                'is_active' => true,
            ],

            // Review Moderation (Managers/Admins only)
            [
                'name' => 'Moderate Reviews',
                'slug' => 'reviews.moderate',
                'module' => 'reviews',
                'action' => 'moderate',
                'description' => 'Permission to approve, reject, and moderate reviews',
                'is_active' => true,
            ],
            [
                'name' => 'Respond to Reviews',
                'slug' => 'reviews.respond',
                'module' => 'reviews',
                'action' => 'respond',
                'description' => 'Permission to add management responses to reviews',
                'is_active' => true,
            ],
            [
                'name' => 'Delete Reviews (Admin)',
                'slug' => 'reviews.delete_admin',
                'module' => 'reviews',
                'action' => 'delete_admin',
                'description' => 'Permission to delete any review',
                'is_active' => true,
            ],

            // Review Analytics (Managers/Admins only)
            [
                'name' => 'View Review Analytics',
                'slug' => 'reviews.analytics',
                'module' => 'reviews',
                'action' => 'analytics',
                'description' => 'Permission to view review analytics and statistics',
                'is_active' => true,
            ],
            [
                'name' => 'View Review Dashboard',
                'slug' => 'reviews.dashboard',
                'module' => 'reviews',
                'action' => 'dashboard',
                'description' => 'Permission to access review management dashboard',
                'is_active' => true,
            ],

            // Review Voting (Public/All users)
            [
                'name' => 'Vote on Reviews',
                'slug' => 'reviews.vote',
                'module' => 'reviews',
                'action' => 'vote',
                'description' => 'Permission to vote on review helpfulness',
                'is_active' => true,
            ],

            // Review Notifications
            [
                'name' => 'View Review Notifications',
                'slug' => 'reviews.notifications',
                'module' => 'reviews',
                'action' => 'notifications',
                'description' => 'Permission to view review notifications',
                'is_active' => true,
            ],
        ];

        // Create or update permissions
        $createdPermissions = [];
        foreach ($reviewPermissions as $permData) {
            $permission = Permission::where('slug', $permData['slug'])->first();

            if ($permission) {
                $permission->update($permData);
            } else {
                $permission = Permission::create($permData);
            }

            $createdPermissions[$permission->slug] = $permission->id;
        }

        echo "✓ Created " . count($createdPermissions) . " review permissions\n";

        // 2. Assign Permissions to Roles
        $this->assignPermissionsToRoles($createdPermissions);

        // 3. Clear cache
        Cache::flush();
        echo "✓ Permission cache cleared\n";
        echo "✓ Review permissions seeding completed!\n";
    }

    /**
     * Assign review permissions to appropriate roles
     */
    private function assignPermissionsToRoles(array $permissionSlugs): void
    {
        // Get all roles
        $adminRole = Role::where('slug', 'admin')->first();
        $managerRole = Role::where('slug', 'manager')->first();
        $receptionistRole = Role::where('slug', 'receptionist')->first();
        $cashierRole = Role::where('slug', 'cashier')->first();
        $waiterRole = Role::where('slug', 'waiter')->first();
        $chefRole = Role::where('slug', 'chef')->first();
        $guestRole = Role::where('slug', 'guest')->first();

        // Admin: All review permissions
        if ($adminRole) {
            $adminPerms = [
                'reviews.view',
                'reviews.create',
                'reviews.update',
                'reviews.delete',
                'reviews.moderate',
                'reviews.respond',
                'reviews.delete_admin',
                'reviews.analytics',
                'reviews.dashboard',
                'reviews.vote',
                'reviews.notifications',
            ];

            $permissionIds = Permission::whereIn('slug', $adminPerms)->pluck('id')->toArray();
            $adminRole->permissions()->syncWithoutDetaching($permissionIds);
            echo "✓ Assigned " . count($permissionIds) . " permissions to Admin role\n";
        }

        // Manager: Moderation & Analytics
        if ($managerRole) {
            $managerPerms = [
                'reviews.view',
                'reviews.moderate',
                'reviews.respond',
                'reviews.delete_admin',
                'reviews.analytics',
                'reviews.dashboard',
                'reviews.vote',
                'reviews.notifications',
            ];

            $permissionIds = Permission::whereIn('slug', $managerPerms)->pluck('id')->toArray();
            $managerRole->permissions()->syncWithoutDetaching($permissionIds);
            echo "✓ Assigned " . count($permissionIds) . " permissions to Manager role\n";
        }

        // Receptionist: View reviews & vote
        if ($receptionistRole) {
            $receptionistPerms = [
                'reviews.view',
                'reviews.vote',
                'reviews.notifications',
            ];

            $permissionIds = Permission::whereIn('slug', $receptionistPerms)->pluck('id')->toArray();
            $receptionistRole->permissions()->syncWithoutDetaching($permissionIds);
            echo "✓ Assigned " . count($permissionIds) . " permissions to Receptionist role\n";
        }

        // Cashier: View reviews & vote
        if ($cashierRole) {
            $cashierPerms = [
                'reviews.view',
                'reviews.vote',
                'reviews.notifications',
            ];

            $permissionIds = Permission::whereIn('slug', $cashierPerms)->pluck('id')->toArray();
            $cashierRole->permissions()->syncWithoutDetaching($permissionIds);
            echo "✓ Assigned " . count($permissionIds) . " permissions to Cashier role\n";
        }

        // Waiter: View reviews & vote
        if ($waiterRole) {
            $waiterPerms = [
                'reviews.view',
                'reviews.vote',
            ];

            $permissionIds = Permission::whereIn('slug', $waiterPerms)->pluck('id')->toArray();
            $waiterRole->permissions()->syncWithoutDetaching($permissionIds);
            echo "✓ Assigned " . count($permissionIds) . " permissions to Waiter role\n";
        }

        // Chef: View reviews & vote
        if ($chefRole) {
            $chefPerms = [
                'reviews.view',
                'reviews.vote',
            ];

            $permissionIds = Permission::whereIn('slug', $chefPerms)->pluck('id')->toArray();
            $chefRole->permissions()->syncWithoutDetaching($permissionIds);
            echo "✓ Assigned " . count($permissionIds) . " permissions to Chef role\n";
        }

        // Guest: Create, view, update own reviews & vote
        if ($guestRole) {
            $guestPerms = [
                'reviews.view',
                'reviews.create',
                'reviews.update',
                'reviews.delete',
                'reviews.vote',
                'reviews.notifications',
            ];

            $permissionIds = Permission::whereIn('slug', $guestPerms)->pluck('id')->toArray();
            $guestRole->permissions()->syncWithoutDetaching($permissionIds);
            echo "✓ Assigned " . count($permissionIds) . " permissions to Guest role\n";
        }
    }
}

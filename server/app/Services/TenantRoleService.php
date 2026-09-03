<?php

namespace App\Services;

use App\Models\Hotel;
use App\Models\Role;
use App\Models\Permission;
use App\Models\HotelUser;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Collection;

class TenantRoleService
{
    /**
     * Default core system role definitions.
     */
    public const DEFAULT_ROLES = [
        'admin' => [
            'name' => 'Admin',
            'description' => 'Hotel Administrator with management access to hotel configuration, staff, and operations.',
            'is_system' => true,
        ],
        'manager' => [
            'name' => 'Manager',
            'description' => 'Hotel Operational Manager with oversight over daily hotel and restaurant operations.',
            'is_system' => true,
        ],
        'receptionist' => [
            'name' => 'Receptionist',
            'description' => 'Front Desk & Guest Services Specialist handling bookings, check-in, and guest needs.',
            'is_system' => true,
        ],
        'chef' => [
            'name' => 'Chef',
            'description' => 'Kitchen Head & Culinary Operations Specialist managing food preparation and orders.',
            'is_system' => true,
        ],
        'waiter' => [
            'name' => 'Waiter',
            'description' => 'Dining & Room Service Delivery Specialist managing tables, deliveries, and guest service.',
            'is_system' => true,
        ],
        'cashier' => [
            'name' => 'Cashier',
            'description' => 'Financial Transactions & Billing Specialist handling order and folio payments.',
            'is_system' => true,
        ],
    ];

    /**
     * Provision independent, tenant-scoped roles and default permissions for a hotel.
     */
    public function provisionRolesForHotel(Hotel $hotel): Collection
    {
        $createdRoles = collect();

        foreach (self::DEFAULT_ROLES as $slug => $meta) {
            // Check if this hotel already has this role record
            $existing = Role::withoutTenant()
                ->where('hotel_id', $hotel->id)
                ->where('slug', $slug)
                ->first();

            if ($existing) {
                $createdRoles->push($existing);
                continue;
            }

            // Create new hotel-specific role
            $role = Role::withoutTenant()->create([
                'hotel_id' => $hotel->id,
                'name' => $meta['name'],
                'slug' => $slug,
                'description' => $meta['description'],
                'is_system' => $meta['is_system'],
                'is_active' => true,
            ]);

            // Clone default permissions from template role (where hotel_id is null) if available
            $templateRole = Role::withoutTenant()
                ->whereNull('hotel_id')
                ->where('slug', $slug)
                ->first();

            if ($templateRole) {
                $permissionIds = DB::table('role_permissions')
                    ->where('role_id', $templateRole->id)
                    ->pluck('permission_id')
                    ->toArray();

                if (!empty($permissionIds)) {
                    $role->permissions()->sync($permissionIds);
                }
            } elseif ($slug === 'admin') {
                // If no template role, admin gets all system permissions
                $allPermIds = Permission::pluck('id')->toArray();
                $role->permissions()->sync($allPermIds);
            }

            $createdRoles->push($role);
        }

        return $createdRoles;
    }

    /**
     * Get or create a specific role within a hotel.
     */
    public function getRoleForHotel(string $hotelId, string $roleSlug): ?Role
    {
        $cleanSlug = strtolower(trim($roleSlug));

        $role = Role::withoutTenant()
            ->where('hotel_id', $hotelId)
            ->where('slug', $cleanSlug)
            ->first();

        if (!$role) {
            $hotel = Hotel::find($hotelId);
            if ($hotel) {
                $this->provisionRolesForHotel($hotel);
                $role = Role::withoutTenant()
                    ->where('hotel_id', $hotelId)
                    ->where('slug', $cleanSlug)
                    ->first();
            }
        }

        return $role;
    }

    /**
     * Bind a user to a hotel with a specific role record.
     */
    public function assignUserRoleInHotel(User $user, string $hotelId, Role $role): HotelUser
    {
        // Enforce that the role belongs strictly to this hotel
        if ($role->hotel_id !== $hotelId) {
            throw new \InvalidArgumentException("Role ID {$role->id} does not belong to hotel {$hotelId}");
        }

        $membership = HotelUser::firstOrNew([
            'hotel_id' => $hotelId,
            'user_id' => $user->id,
        ]);

        $membership->id = $membership->id ?: (string) \Illuminate\Support\Str::uuid();
        $membership->role = $role->slug;
        $membership->role_id = $role->id;
        $membership->is_active = true;
        $membership->save();

        // Also sync into user_roles pivot for this hotel
        DB::table('user_roles')->updateOrInsert(
            [
                'hotel_id' => $hotelId,
                'user_id' => $user->id,
                'role_id' => $role->id,
            ],
            [
                'is_primary' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );

        // Invalidate permission cache
        app(AuthorizationService::class)->invalidateUserCache($user->id, $hotelId);

        return $membership;
    }
}

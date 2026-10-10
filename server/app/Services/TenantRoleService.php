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

    public function provisionRolesForHotel(Hotel $hotel): Collection
    {
        $createdRoles = collect();

        foreach (self::DEFAULT_ROLES as $slug => $meta) {
            $existing = Role::withoutTenant()
                ->where('hotel_id', $hotel->id)
                ->where('slug', $slug)
                ->first();

            if ($existing) {
                $createdRoles->push($existing);
                continue;
            }

            $role = Role::withoutTenant()->create([
                'hotel_id' => $hotel->id,
                'name' => $meta['name'],
                'slug' => $slug,
                'description' => $meta['description'],
                'is_system' => $meta['is_system'],
                'is_active' => true,
            ]);

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
                $allPermIds = Permission::pluck('id')->toArray();
                $role->permissions()->sync($allPermIds);
            }

            $createdRoles->push($role);
        }

        return $createdRoles;
    }

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

    public function assignUserRoleInHotel(User $user, string $hotelId, Role $role): HotelUser
    {
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

        app(AuthorizationService::class)->invalidateUserCache($user->id, $hotelId);

        return $membership;
    }
}


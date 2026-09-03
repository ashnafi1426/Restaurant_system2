<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\Hotel;
use App\Models\User;
use App\Models\Role;
use App\Models\Permission;
use App\Models\HotelUser;
use App\Services\AuthorizationService;
use App\Services\TenantRoleService;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;

class TenantPermissionIsolationTest extends TestCase
{
    protected Hotel $hotelA;
    protected Hotel $hotelB;
    protected User $adminA;
    protected User $adminB;
    protected User $managerA;
    protected User $managerB;
    protected Role $roleManagerA;
    protected Role $roleManagerB;
    protected Permission $permViewRooms;
    protected Permission $permDeleteRooms;

    protected function setUp(): void
    {
        parent::setUp();

        // 1. Create two test hotels
        $this->hotelA = Hotel::create([
            'id' => (string) Str::uuid(),
            'name' => 'Test Hotel Alpha ' . Str::random(5),
            'slug' => 'test-hotel-alpha-' . Str::random(5),
            'status' => 'active',
        ]);

        $this->hotelB = Hotel::create([
            'id' => (string) Str::uuid(),
            'name' => 'Test Hotel Beta ' . Str::random(5),
            'slug' => 'test-hotel-beta-' . Str::random(5),
            'status' => 'active',
        ]);

        // 2. Provision isolated roles for both hotels
        $tenantRoleService = app(TenantRoleService::class);
        $tenantRoleService->provisionRolesForHotel($this->hotelA);
        $tenantRoleService->provisionRolesForHotel($this->hotelB);

        $this->roleManagerA = Role::withoutTenant()->where('hotel_id', $this->hotelA->id)->where('slug', 'manager')->first();
        $this->roleManagerB = Role::withoutTenant()->where('hotel_id', $this->hotelB->id)->where('slug', 'manager')->first();

        // 3. Ensure test permissions exist
        $this->permViewRooms = Permission::firstOrCreate(
            ['slug' => 'rooms.view'],
            ['name' => 'View Rooms', 'module' => 'rooms', 'action' => 'view', 'is_active' => true]
        );

        $this->permDeleteRooms = Permission::firstOrCreate(
            ['slug' => 'rooms.delete'],
            ['name' => 'Delete Rooms', 'module' => 'rooms', 'action' => 'delete', 'is_active' => true]
        );

        // 4. Create Hotel A Admin
        $this->adminA = User::create([
            'id' => (string) Str::uuid(),
            'first_name' => 'Admin',
            'last_name' => 'Alpha',
            'email' => 'admin.alpha.' . Str::random(6) . '@test.com',
            'password_hash' => Hash::make('Secret123!'),
            'role' => 'admin',
            'is_active' => true,
        ]);
        $roleAdminA = Role::withoutTenant()->where('hotel_id', $this->hotelA->id)->where('slug', 'admin')->first();
        // Give Admin A permissions to manage roles
        $rolePerm = Permission::firstOrCreate(['slug' => 'roles.assign_permissions'], ['name' => 'Assign Role Perms', 'module' => 'roles', 'action' => 'assign_permissions']);
        $roleAdminA->permissions()->syncWithoutDetaching([$rolePerm->id]);
        $tenantRoleService->assignUserRoleInHotel($this->adminA, $this->hotelA->id, $roleAdminA);

        // 5. Create Hotel B Admin
        $this->adminB = User::create([
            'id' => (string) Str::uuid(),
            'first_name' => 'Admin',
            'last_name' => 'Beta',
            'email' => 'admin.beta.' . Str::random(6) . '@test.com',
            'password_hash' => Hash::make('Secret123!'),
            'role' => 'admin',
            'is_active' => true,
        ]);
        $roleAdminB = Role::withoutTenant()->where('hotel_id', $this->hotelB->id)->where('slug', 'admin')->first();
        $roleAdminB->permissions()->syncWithoutDetaching([$rolePerm->id]);
        $tenantRoleService->assignUserRoleInHotel($this->adminB, $this->hotelB->id, $roleAdminB);

        // 6. Create Manager in Hotel A
        $this->managerA = User::create([
            'id' => (string) Str::uuid(),
            'first_name' => 'Manager',
            'last_name' => 'Alpha',
            'email' => 'manager.alpha.' . Str::random(6) . '@test.com',
            'password_hash' => Hash::make('Secret123!'),
            'role' => 'manager',
            'is_active' => true,
        ]);
        $tenantRoleService->assignUserRoleInHotel($this->managerA, $this->hotelA->id, $this->roleManagerA);

        // 7. Create Manager in Hotel B
        $this->managerB = User::create([
            'id' => (string) Str::uuid(),
            'first_name' => 'Manager',
            'last_name' => 'Beta',
            'email' => 'manager.beta.' . Str::random(6) . '@test.com',
            'password_hash' => Hash::make('Secret123!'),
            'role' => 'manager',
            'is_active' => true,
        ]);
        $tenantRoleService->assignUserRoleInHotel($this->managerB, $this->hotelB->id, $this->roleManagerB);
    }

    public function test_hotels_have_distinct_role_records_for_the_same_slug()
    {
        $this->assertNotNull($this->roleManagerA);
        $this->assertNotNull($this->roleManagerB);
        $this->assertNotEquals($this->roleManagerA->id, $this->roleManagerB->id);
        $this->assertEquals('manager', $this->roleManagerA->slug);
        $this->assertEquals('manager', $this->roleManagerB->slug);
        $this->assertEquals($this->hotelA->id, $this->roleManagerA->hotel_id);
        $this->assertEquals($this->hotelB->id, $this->roleManagerB->hotel_id);
    }

    public function test_modifying_hotel_a_manager_permissions_does_not_leak_to_hotel_b_manager()
    {
        $authService = app(AuthorizationService::class);

        // Set initial state: Hotel A has rooms.view, Hotel B has rooms.view
        $this->roleManagerA->permissions()->sync([$this->permViewRooms->id]);
        $this->roleManagerB->permissions()->sync([$this->permViewRooms->id]);
        $authService->invalidateUserCache($this->managerA->id, $this->hotelA->id);
        $authService->invalidateUserCache($this->managerB->id, $this->hotelB->id);

        $this->assertTrue($authService->hasPermission($this->managerA, 'rooms.view', $this->hotelA->id));
        $this->assertFalse($authService->hasPermission($this->managerA, 'rooms.delete', $this->hotelA->id));

        $this->assertTrue($authService->hasPermission($this->managerB, 'rooms.view', $this->hotelB->id));
        $this->assertFalse($authService->hasPermission($this->managerB, 'rooms.delete', $this->hotelB->id));

        // Hotel A Admin adds 'rooms.delete' to Hotel A Manager
        $response = $this->actingAs($this->adminA)
            ->withHeader('X-Hotel-ID', $this->hotelA->id)
            ->postJson("/api/roles/{$this->roleManagerA->id}/permissions", [
                'permission_ids' => [$this->permViewRooms->id, $this->permDeleteRooms->id],
            ]);

        $response->assertStatus(200);

        // Assert Hotel A Manager now HAS 'rooms.delete'
        $this->assertTrue($authService->hasPermission($this->managerA, 'rooms.delete', $this->hotelA->id));

        // CRITICAL ASSERTION: Hotel B Manager must NOT have 'rooms.delete'
        $this->assertFalse(
            $authService->hasPermission($this->managerB, 'rooms.delete', $this->hotelB->id),
            'PERMISSION LEAKAGE DETECTED: Hotel B Manager unexpectedly received permissions modified by Hotel A Admin!'
        );
    }

    public function test_hotel_a_admin_cannot_sync_permissions_for_hotel_b_role()
    {
        // Hotel A Admin attempts to modify Hotel B Manager role
        $response = $this->actingAs($this->adminA)
            ->withHeader('X-Hotel-ID', $this->hotelA->id)
            ->postJson("/api/roles/{$this->roleManagerB->id}/permissions", [
                'permission_ids' => [$this->permDeleteRooms->id],
            ]);

        // Must be rejected with 403 Forbidden or 404 Not Found (tenant scope isolation)
        $this->assertTrue(
            in_array($response->status(), [403, 404]),
            "Expected 403 or 404 for cross-tenant role access, received {$response->status()}"
        );
    }

    public function test_same_user_can_have_different_roles_in_different_hotels()
    {
        $authService = app(AuthorizationService::class);

        // User 'Abebe' is Manager in Hotel A, but Receptionist in Hotel B
        $userAbebe = User::create([
            'id' => (string) Str::uuid(),
            'first_name' => 'Abebe',
            'last_name' => 'Bikila',
            'email' => 'abebe.' . Str::random(6) . '@test.com',
            'password_hash' => Hash::make('Secret123!'),
            'role' => 'staff',
            'is_active' => true,
        ]);

        $roleReceptionistB = Role::withoutTenant()->where('hotel_id', $this->hotelB->id)->where('slug', 'receptionist')->first();

        $tenantRoleService = app(TenantRoleService::class);
        $tenantRoleService->assignUserRoleInHotel($userAbebe, $this->hotelA->id, $this->roleManagerA);
        $tenantRoleService->assignUserRoleInHotel($userAbebe, $this->hotelB->id, $roleReceptionistB);

        // In Hotel A, Abebe is Manager
        $this->assertTrue($authService->hasRole($userAbebe, 'manager', $this->hotelA->id));
        $this->assertFalse($authService->hasRole($userAbebe, 'receptionist', $this->hotelA->id));

        // In Hotel B, Abebe is Receptionist
        $this->assertTrue($authService->hasRole($userAbebe, 'receptionist', $this->hotelB->id));
        $this->assertFalse($authService->hasRole($userAbebe, 'manager', $this->hotelB->id));
    }
}

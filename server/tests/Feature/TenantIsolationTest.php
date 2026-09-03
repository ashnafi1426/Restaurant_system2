<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Hotel;
use App\Models\HotelUser;
use App\Models\Room;
use App\Models\RoomType;
use App\Models\MenuItem;
use App\Models\Reservation;
use App\Models\Guest;
use Illuminate\Support\Str;
use App\Services\TenantContext;

class TenantIsolationTest extends TestCase
{
    protected Hotel $hotelA;
    protected Hotel $hotelB;
    protected User $userA;
    protected User $userB;
    protected User $superAdmin;
    protected RoomType $roomTypeA;
    protected RoomType $roomTypeB;
    protected Room $roomA;
    protected Room $roomB;
    protected MenuItem $menuItemA;
    protected MenuItem $menuItemB;

    protected function setUp(): void
    {
        parent::setUp();

        // 1. Create Hotel A & Hotel B
        $this->hotelA = Hotel::firstOrCreate(['slug' => 'test-hotel-a'], [
            'id' => (string) Str::uuid(),
            'name' => 'Hotel Alpha',
            'status' => 'active',
            'currency' => 'ETB',
        ]);

        $this->hotelB = Hotel::firstOrCreate(['slug' => 'test-hotel-b'], [
            'id' => (string) Str::uuid(),
            'name' => 'Hotel Beta',
            'status' => 'active',
            'currency' => 'ETB',
        ]);

        // 2. Create User A (Hotel A admin) and User B (Hotel B admin)
        $this->userA = User::firstOrCreate(['email' => 'admin.a@test.com'], [
            'id' => (string) Str::uuid(),
            'first_name' => 'Admin',
            'last_name' => 'Alpha',
            'password_hash' => bcrypt('password'),
            'role' => 'admin',
            'is_active' => true,
            'is_platform_admin' => false,
        ]);

        $this->userB = User::firstOrCreate(['email' => 'admin.b@test.com'], [
            'id' => (string) Str::uuid(),
            'first_name' => 'Admin',
            'last_name' => 'Beta',
            'password_hash' => bcrypt('password'),
            'role' => 'admin',
            'is_active' => true,
            'is_platform_admin' => false,
        ]);

        $this->superAdmin = User::firstOrCreate(['email' => 'superadmin@test.com'], [
            'id' => (string) Str::uuid(),
            'first_name' => 'Super',
            'last_name' => 'Admin',
            'password_hash' => bcrypt('password'),
            'role' => 'admin',
            'is_active' => true,
            'is_platform_admin' => true,
        ]);

        // Assign memberships
        HotelUser::firstOrCreate([
            'hotel_id' => $this->hotelA->id,
            'user_id' => $this->userA->id,
        ], [
            'id' => (string) Str::uuid(),
            'role' => 'admin',
            'is_active' => true,
        ]);

        HotelUser::firstOrCreate([
            'hotel_id' => $this->hotelB->id,
            'user_id' => $this->userB->id,
        ], [
            'id' => (string) Str::uuid(),
            'role' => 'admin',
            'is_active' => true,
        ]);

        // 3. Create room types and rooms
        $this->roomTypeA = RoomType::withoutTenant()->firstOrCreate([
            'hotel_id' => $this->hotelA->id,
            'name' => 'Deluxe A',
        ], [
            'id' => (string) Str::uuid(),
            'base_price_per_night' => 1000,
            'capacity' => 2,
            'is_active' => true,
        ]);

        $this->roomTypeB = RoomType::withoutTenant()->firstOrCreate([
            'hotel_id' => $this->hotelB->id,
            'name' => 'Deluxe B',
        ], [
            'id' => (string) Str::uuid(),
            'base_price_per_night' => 1200,
            'capacity' => 2,
            'is_active' => true,
        ]);

        $this->roomA = Room::withoutTenant()->firstOrCreate([
            'hotel_id' => $this->hotelA->id,
            'room_number' => '101',
        ], [
            'id' => (string) Str::uuid(),
            'room_type_id' => $this->roomTypeA->id,
            'status' => 'available',
            'is_active' => true,
        ]);

        $this->roomB = Room::withoutTenant()->firstOrCreate([
            'hotel_id' => $this->hotelB->id,
            'room_number' => '101', // Note: Same room number in different hotel!
        ], [
            'id' => (string) Str::uuid(),
            'room_type_id' => $this->roomTypeB->id,
            'status' => 'available',
            'is_active' => true,
        ]);

        // 4. Create menu items
        $this->menuItemA = MenuItem::withoutTenant()->firstOrCreate([
            'hotel_id' => $this->hotelA->id,
            'name' => 'Burger Alpha',
        ], [
            'id' => (string) Str::uuid(),
            'price' => 250,
            'is_available' => true,
        ]);

        $this->menuItemB = MenuItem::withoutTenant()->firstOrCreate([
            'hotel_id' => $this->hotelB->id,
            'name' => 'Burger Beta',
        ], [
            'id' => (string) Str::uuid(),
            'price' => 300,
            'is_available' => true,
        ]);
    }

    /**
     * Test 1: User A can see Hotel A room when context is Hotel A
     */
    public function test_hotel_a_user_can_access_hotel_a_room()
    {
        $token = $this->userA->createToken('test')->plainTextToken;

        $response = $this->withHeaders([
            'Authorization' => "Bearer {$token}",
            'X-Hotel-ID' => $this->hotelA->id,
        ])->getJson("/api/rooms/{$this->roomA->id}");

        $response->assertStatus(200);
        $this->assertEquals($this->roomA->id, $response->json('data.id') ?? $response->json('id'));
    }

    /**
     * Test 2: User A CANNOT see Hotel B room (Tenant scope isolates query -> 404)
     */
    public function test_hotel_a_user_cannot_access_hotel_b_room()
    {
        $token = $this->userA->createToken('test')->plainTextToken;

        $response = $this->withHeaders([
            'Authorization' => "Bearer {$token}",
            'X-Hotel-ID' => $this->hotelA->id,
        ])->getJson("/api/rooms/{$this->roomB->id}");

        $response->assertStatus(404);
    }

    /**
     * Test 3: User cannot switch to an unauthorized hotel (403 Forbidden)
     */
    public function test_user_cannot_switch_to_unauthorized_hotel()
    {
        $token = $this->userA->createToken('test')->plainTextToken;

        $response = $this->withHeaders([
            'Authorization' => "Bearer {$token}",
        ])->postJson('/api/auth/switch-hotel', [
            'hotel_id' => $this->hotelB->id,
        ]);

        $response->assertStatus(403);
    }

    /**
     * Test 4: Platform admin can access platform statistics
     */
    public function test_platform_admin_can_access_platform_management()
    {
        $token = $this->superAdmin->createToken('test')->plainTextToken;

        $response = $this->withHeaders([
            'Authorization' => "Bearer {$token}",
        ])->getJson('/api/platform/statistics');

        $response->assertStatus(200);
        $this->assertArrayHasKey('total_hotels', $response->json('data'));
    }

    /**
     * Test 5: Regular Hotel Admin cannot access platform management (403 Forbidden)
     */
    public function test_hotel_admin_cannot_access_platform_management()
    {
        $token = $this->userA->createToken('test')->plainTextToken;

        $response = $this->withHeaders([
            'Authorization' => "Bearer {$token}",
        ])->getJson('/api/platform/statistics');

        $response->assertStatus(403);
    }

    /**
     * Test 6: Cross-hotel menu item injection is rejected in orders
     */
    public function test_order_cannot_reference_foreign_hotel_menu_item()
    {
        $this->expectException(\InvalidArgumentException::class);

        // Attempting to calculate order total for Hotel A with a Hotel B menu item
        $controller = new \App\Http\Controllers\Api\UnifiedOrderController();
        $refMethod = new \ReflectionMethod($controller, 'calculateOrderTotal');
        $refMethod->setAccessible(true);

        $refMethod->invoke($controller, [
            ['menu_item_id' => $this->menuItemB->id, 'quantity' => 1],
        ], $this->hotelA->id);
    }

    /**
     * Test 7: Platform admin can assign a dedicated hotel administrator
     */
    public function test_platform_admin_can_assign_dedicated_hotel_admin()
    {
        $token = $this->superAdmin->createToken('test')->plainTextToken;

        $response = $this->withHeaders([
            'Authorization' => "Bearer {$token}",
        ])->postJson("/api/platform/hotels/{$this->hotelA->id}/admins", [
            'email' => 'new.manager@test.com',
            'first_name' => 'New',
            'last_name' => 'Manager',
            'password' => 'SecurePass123@',
        ]);

        $response->assertStatus(200);
        $this->assertTrue($response->json('success'));
        $this->assertEquals('admin', $response->json('data.role'));
    }

    /**
     * Test 8: Dedicated hotel admin cannot access platform statistics (403 Forbidden)
     */
    public function test_dedicated_hotel_admin_cannot_access_platform_statistics()
    {
        $hotelAdmin = \App\Models\User::firstOrCreate(
            ['email' => 'dedicated.admin@test.com'],
            [
                'id' => (string) \Illuminate\Support\Str::uuid(),
                'first_name' => 'Dedicated',
                'last_name' => 'Admin',
                'password_hash' => bcrypt('Admin123@'),
                'role' => 'admin',
                'is_active' => true,
                'is_platform_admin' => false,
            ]
        );

        $adminToken = $hotelAdmin->createToken('test')->plainTextToken;

        $response = $this->withHeaders([
            'Authorization' => "Bearer {$adminToken}",
        ])->getJson('/api/platform/statistics');

        $response->assertStatus(403);
    }

    /**
     * Test 9: Platform Super Admin can manage roles and permissions
     */
    public function test_super_admin_can_manage_roles_and_permissions()
    {
        $token = $this->superAdmin->createToken('test')->plainTextToken;

        $responseRoles = $this->withHeaders([
            'Authorization' => "Bearer {$token}",
        ])->getJson('/api/roles');

        $responseRoles->assertStatus(200);

        $responsePerms = $this->withHeaders([
            'Authorization' => "Bearer {$token}",
        ])->getJson('/api/permissions');

        $responsePerms->assertStatus(200);
    }

    /**
     * Test 10: Hotel Admin CANNOT manage system roles or permissions (403 Forbidden)
     */
    public function test_hotel_admin_cannot_manage_roles_or_permissions()
    {
        $token = $this->userA->createToken('test')->plainTextToken;

        $forbiddenRoles = $this->withHeaders([
            'Authorization' => "Bearer {$token}",
        ])->getJson('/api/roles');

        $forbiddenRoles->assertStatus(403);

        $forbiddenPerms = $this->withHeaders([
            'Authorization' => "Bearer {$token}",
        ])->getJson('/api/permissions');

        $forbiddenPerms->assertStatus(403);
    }

    /**
     * Test 11: Platform Super Admin can update role without 500 error
     */
    public function test_super_admin_can_update_role()
    {
        $token = $this->superAdmin->createToken('test')->plainTextToken;
        $role = \App\Models\Role::first();

        $response = $this->withHeaders([
            'Authorization' => "Bearer {$token}",
        ])->putJson("/api/roles/{$role->id}", [
            'name' => $role->name,
            'description' => 'Updated by superadmin test',
            'is_active' => true,
        ]);

        $response->assertStatus(200);
        $this->assertTrue($response->json('success'));
    }

    /**
     * Test 12: Platform Super Admin can create permission dynamically and sync to roles
     */
    public function test_super_admin_can_create_and_sync_permission_dynamically()
    {
        $token = $this->superAdmin->createToken('test')->plainTextToken;

        $rand = rand(1000, 9999);
        // Create new dynamic permission
        $createRes = $this->withHeaders([
            'Authorization' => "Bearer {$token}",
        ])->postJson('/api/permissions', [
            'name' => "Custom VIP Access {$rand}",
            'module' => "vip_service_{$rand}",
            'action' => 'access',
            'description' => 'Dynamic permission for VIP services',
        ]);

        $createRes->assertStatus(201);
        $this->assertTrue($createRes->json('success'));
        $createdSlug = $createRes->json('data.slug');
        $this->assertEquals("vip_service_{$rand}.access", $createdSlug);

        $permId = $createRes->json('data.id');
        $role = \App\Models\Role::where('slug', '!=', 'admin')->first();

        // Sync to role
        $syncRes = $this->withHeaders([
            'Authorization' => "Bearer {$token}",
        ])->postJson("/api/roles/{$role->id}/permissions", [
            'permission_ids' => [$permId],
        ]);

        $syncRes->assertStatus(200);
        $this->assertTrue($syncRes->json('success'));
        $this->assertTrue($role->fresh()->permissions->contains('id', $permId));
    }

    /**
     * Test 13: Hotel Admin strictly obeys dynamically configured permissions
     */
    public function test_hotel_admin_strictly_obeys_dynamically_configured_permissions()
    {
        $adminRole = \App\Models\Role::firstOrCreate(['slug' => 'admin'], ['name' => 'Admin']);
        $superToken = $this->superAdmin->createToken('super')->plainTextToken;

        // Ensure userA has role admin
        $this->userA->roles()->syncWithoutDetaching([$adminRole->id]);

        // Revoke all permissions from admin role
        $this->withHeaders([
            'Authorization' => "Bearer {$superToken}",
        ])->postJson("/api/roles/{$adminRole->id}/permissions", [
            'permission_ids' => [],
        ])->assertStatus(200);

        \Illuminate\Support\Facades\Cache::flush();

        $authService = app(\App\Services\AuthorizationService::class);
        // Hotel admin now does NOT have ungranted 'rooms.delete'
        $this->assertFalse($authService->hasPermission($this->userA->fresh(), 'rooms.delete'));

        // Super Admin still has master access to everything
        $this->assertTrue($authService->hasPermission($this->superAdmin, 'rooms.delete'));
    }

    /**
     * Test 14: Super Admin Hotel Management lifecycle (create, details, suspend, archive)
     */
    public function test_super_admin_hotel_management_lifecycle()
    {
        $superToken = $this->superAdmin->createToken('super')->plainTextToken;
        $rand = rand(1000, 9999);

        // 1. Create Hotel with initial Admin
        $createRes = $this->withHeaders([
            'Authorization' => "Bearer {$superToken}",
        ])->postJson('/api/platform/hotels', [
            'name' => "Lalibela Palace {$rand}",
            'slug' => "lalibela-palace-{$rand}",
            'email' => "contact{$rand}@lalibela.com",
            'phone' => '+251 91 222 3333',
            'city' => 'Lalibela',
            'country' => 'Ethiopia',
            'status' => 'active',
            'admin_first_name' => 'Tewodros',
            'admin_last_name' => 'Admin',
            'admin_email' => "tewodros{$rand}@lalibela.com",
            'admin_password' => 'HotelAdmin123@',
        ]);

        $createRes->assertStatus(201);
        $this->assertTrue($createRes->json('success'));
        $hotelId = $createRes->json('data.id');

        // 2. View Hotel Details & Statistics
        $showRes = $this->withHeaders([
            'Authorization' => "Bearer {$superToken}",
        ])->getJson("/api/platform/hotels/{$hotelId}");

        $showRes->assertStatus(200);
        $this->assertEquals("Lalibela Palace {$rand}", $showRes->json('data.name'));
        $this->assertArrayHasKey('rooms_count', $showRes->json('data'));
        $this->assertArrayHasKey('revenue_total', $showRes->json('data'));

        // 3. Suspend Hotel
        $suspendRes = $this->withHeaders([
            'Authorization' => "Bearer {$superToken}",
        ])->patchJson("/api/platform/hotels/{$hotelId}/status", [
            'status' => 'suspended',
        ]);

        $suspendRes->assertStatus(200);
        $this->assertEquals('suspended', $suspendRes->json('data.status'));

        // 4. Verify suspended hotel access produces exact message for tenant user
        auth()->forgetGuards();
        $hotelAdminUser = \App\Models\User::where('email', "tewodros{$rand}@lalibela.com")->first();
        $adminToken = $hotelAdminUser->createToken('tenant')->plainTextToken;

        $tenantReq = $this->withHeaders([
            'Authorization' => "Bearer {$adminToken}",
        ])->postJson('/api/auth/switch-hotel', [
            'hotel_id' => $hotelId,
        ]);

        $tenantReq->assertStatus(403);
        $this->assertEquals(
            'Your hotel account has been suspended. Please contact platform administration.',
            $tenantReq->json('message')
        );

        // 5. Archive Hotel safely
        auth()->forgetGuards();
        $archiveRes = $this->withHeaders([
            'Authorization' => "Bearer {$superToken}",
        ])->postJson("/api/platform/hotels/{$hotelId}/archive");

        $archiveRes->assertStatus(200);
        $this->assertEquals('archived', $archiveRes->json('data.status'));
    }

    /**
     * Test 15: CORS preflight request allows X-Hotel-ID header
     */
    public function test_cors_preflight_allows_x_hotel_id()
    {
        $response = $this->call('OPTIONS', '/api/platform/hotels', [], [], [], [
            'HTTP_ORIGIN' => 'http://localhost:5173',
            'HTTP_ACCESS_CONTROL_REQUEST_METHOD' => 'GET',
            'HTTP_ACCESS_CONTROL_REQUEST_HEADERS' => 'authorization,content-type,x-hotel-id',
        ]);

        $response->assertStatus(200);
        $this->assertEquals('http://localhost:5173', $response->headers->get('Access-Control-Allow-Origin'));
        $allowHeaders = strtolower((string)$response->headers->get('Access-Control-Allow-Headers'));
        $this->assertStringContainsString('x-hotel-id', $allowHeaders);
    }

    /**
     * Test 16: Hotel Admin creation with activation link, password reset, and View Hotel mode
     */
    public function test_hotel_admin_management_and_view_mode()
    {
        \Illuminate\Support\Facades\Mail::fake();
        $superToken = $this->superAdmin->createToken('super')->plainTextToken;
        $rand = rand(10000, 99999);

        // 1. Super Admin creates Hotel Admin with system-generated password (concealed from Super Admin)
        auth()->forgetGuards();
        $email = "abebe{$rand}@test.com";
        $createAdminRes = $this->withHeaders([
            'Authorization' => "Bearer {$superToken}",
        ])->postJson('/api/platform/hotel-admins', [
            'first_name' => 'Abebe',
            'last_name' => 'Bikila',
            'email' => $email,
            'phone' => '+251911000000',
            'hotel_id' => $this->hotelA->id,
            'role' => 'admin',
            'status' => 'active',
        ]);

        $createAdminRes->assertStatus(201);
        $this->assertTrue($createAdminRes->json('success'));
        // Super Admin cannot see the password in the response
        $this->assertNull($createAdminRes->json('data.temporary_password'));
        $this->assertTrue($createAdminRes->json('data.user.must_change_password'));
        $newUserId = $createAdminRes->json('data.user.id');
        $membershipId = $createAdminRes->json('data.membership_id');

        // Email was dispatched to Hotel Admin with their password
        $tempPassword = null;
        \Illuminate\Support\Facades\Mail::assertSent(\App\Mail\HotelAdminPasswordMail::class, function ($mail) use ($email, &$tempPassword) {
            if ($mail->hasTo($email)) {
                $tempPassword = $mail->temporaryPassword;
                return true;
            }
            return false;
        });
        $this->assertNotEmpty($tempPassword);

        // 2. Hotel Admin logs in directly using system-generated password received by email
        auth()->forgetGuards();
        $loginRes = $this->postJson('/api/login', [
            'email' => $email,
            'password' => $tempPassword,
        ]);
        $loginRes->assertStatus(200);
        $adminToken = $loginRes->json('token');
        $this->assertTrue($loginRes->json('user.must_change_password'));

        // 3. Hotel Admin updates password
        auth()->forgetGuards();
        $updatePwdRes = $this->withHeaders([
            'Authorization' => "Bearer {$adminToken}",
        ])->postJson('/api/auth/update-password', [
            'current_password' => $tempPassword,
            'new_password' => 'MyNewSecureP@ss123',
            'new_password_confirmation' => 'MyNewSecureP@ss123',
        ]);
        $updatePwdRes->assertStatus(200);
        $this->assertFalse($updatePwdRes->json('user.must_change_password'));

        // 4. Hotel Admin logs in with their newly set password
        auth()->forgetGuards();
        $reLoginRes = $this->postJson('/api/login', [
            'email' => $email,
            'password' => 'MyNewSecureP@ss123',
        ]);
        $reLoginRes->assertStatus(200);
        $this->assertFalse($reLoginRes->json('user.must_change_password'));

        // 5. Super Admin resets password to a new system-generated temporary password (sent via email)
        auth()->forgetGuards();
        $resetRes = $this->withHeaders([
            'Authorization' => "Bearer {$superToken}",
        ])->postJson("/api/platform/hotel-admins/{$newUserId}/reset-password");

        $resetRes->assertStatus(200);
        $this->assertTrue($resetRes->json('success'));
        $this->assertNull($resetRes->json('temporary_password')); // Concealed from Super Admin

        // 5b. Super Admin resends password by email
        auth()->forgetGuards();
        $resendRes = $this->withHeaders([
            'Authorization' => "Bearer {$superToken}",
        ])->postJson("/api/platform/hotel-admins/{$newUserId}/resend-password");
        $resendRes->assertStatus(200);
        $this->assertTrue($resendRes->json('success'));
        $this->assertNull($resendRes->json('temporary_password')); // Concealed from Super Admin

        // 6. Super Admin toggles admin status
        auth()->forgetGuards();
        $toggleRes = $this->withHeaders([
            'Authorization' => "Bearer {$superToken}",
        ])->patchJson("/api/platform/hotel-admins/{$membershipId}/toggle-status");

        $toggleRes->assertStatus(200);
        $this->assertFalse($toggleRes->json('data.is_active'));

        // 4. Super Admin enters controlled "View Hotel" Mode
        auth()->forgetGuards();
        $enterViewRes = $this->withHeaders([
            'Authorization' => "Bearer {$superToken}",
        ])->postJson("/api/platform/hotels/{$this->hotelA->id}/enter-view");

        $enterViewRes->assertStatus(200);
        $this->assertTrue($enterViewRes->json('data.is_viewing_as_platform_admin'));

        // 5. Super Admin exits "View Hotel" Mode
        auth()->forgetGuards();
        $exitViewRes = $this->withHeaders([
            'Authorization' => "Bearer {$superToken}",
        ])->postJson('/api/platform/hotels/exit-view');

        $exitViewRes->assertStatus(200);
        $this->assertTrue($exitViewRes->json('success'));
    }

    /**
     * Test 17: Platform Admin with multiple memberships is never blocked with 400 Bad Request
     */
    public function test_platform_admin_never_blocked_by_400_on_auth_or_platform_routes()
    {
        // Attach superAdmin to multiple hotels
        \App\Models\HotelUser::firstOrCreate([
            'hotel_id' => $this->hotelB->id,
            'user_id' => $this->superAdmin->id,
        ], [
            'id' => (string) \Illuminate\Support\Str::uuid(),
            'role' => 'admin',
            'is_active' => true,
        ]);

        $superToken = $this->superAdmin->createToken('super')->plainTextToken;

        auth()->forgetGuards();
        // /api/me without X-Hotel-ID should succeed (200), not 400
        $meRes = $this->withHeaders([
            'Authorization' => "Bearer {$superToken}",
        ])->getJson('/api/me');
        $meRes->assertStatus(200);

        // /api/platform/hotels without X-Hotel-ID should succeed (200), not 400
        $hotelsRes = $this->withHeaders([
            'Authorization' => "Bearer {$superToken}",
        ])->getJson('/api/platform/hotels');
        $hotelsRes->assertStatus(200);

        // /api/roles/active without X-Hotel-ID should not return 400
        $rolesRes = $this->withHeaders([
            'Authorization' => "Bearer {$superToken}",
        ])->getJson('/api/roles/active');
        $this->assertNotEquals(400, $rolesRes->status());
    }

    /**
     * Test 18: Hotel Admin with permissions can access /api/roles and /api/permissions without tenant restriction
     */
    public function test_hotel_admin_can_access_roles_and_permissions_with_permissions()
    {
        $adminRole = \App\Models\Role::firstOrCreate(
            ['slug' => 'admin'],
            ['name' => 'Admin', 'is_system' => true, 'is_active' => true]
        );
        $perm1 = \App\Models\Permission::firstOrCreate(
            ['slug' => 'roles.view'],
            ['name' => 'View Roles', 'module' => 'roles', 'action' => 'view', 'is_active' => true]
        );
        $perm2 = \App\Models\Permission::firstOrCreate(
            ['slug' => 'permissions.view'],
            ['name' => 'View Permissions', 'module' => 'permissions', 'action' => 'view', 'is_active' => true]
        );

        $adminRole->permissions()->syncWithoutDetaching([$perm1->id, $perm2->id]);
        $this->userA->roles()->syncWithoutDetaching([$adminRole->id => ['is_primary' => true]]);
        \Illuminate\Support\Facades\Cache::forget("user_permissions_{$this->userA->id}");

        $adminToken = $this->userA->createToken('tenant_admin')->plainTextToken;

        // Hotel Admin accesses /api/permissions without X-Hotel-ID
        $permRes = $this->withHeaders([
            'Authorization' => "Bearer {$adminToken}",
        ])->getJson('/api/permissions');

        $permRes->assertStatus(200);
        $this->assertTrue($permRes->json('success'));
        $this->assertNotEmpty($permRes->json('data'));

        // Hotel Admin accesses /api/roles without X-Hotel-ID
        $rolesRes = $this->withHeaders([
            'Authorization' => "Bearer {$adminToken}",
        ])->getJson('/api/roles');

        $rolesRes->assertStatus(200);
        $this->assertTrue($rolesRes->json('success'));
        $this->assertNotEmpty($rolesRes->json('data'));

        // Hotel Admin creates a staff user via POST /api/users
        \Illuminate\Support\Facades\Mail::fake();
        $rand = rand(1000, 9999);
        $createUserRes = $this->withHeaders([
            'Authorization' => "Bearer {$adminToken}",
            'X-Hotel-ID' => $this->hotelA->id,
        ])->postJson('/api/users', [
            'first_name' => 'Sara',
            'last_name' => 'Waiter',
            'email' => "sara{$rand}@example.com",
            'role' => 'waiter',
            'is_active' => true,
        ]);

        $createUserRes->assertStatus(201);
        $this->assertTrue($createUserRes->json('success'));

        // Hotel Admin fetches users for Hotel A: Must ONLY contain Hotel A users, NOT Hotel B users
        $getUsersRes = $this->withHeaders([
            'Authorization' => "Bearer {$adminToken}",
            'X-Hotel-ID' => $this->hotelA->id,
        ])->getJson('/api/users');

        $getUsersRes->assertStatus(200);
        $fetchedEmails = collect($getUsersRes->json('data'))->pluck('email')->all();
        $this->assertContains("sara{$rand}@example.com", $fetchedEmails);
        $this->assertNotContains($this->userB->email, $fetchedEmails, 'Users from Hotel B must not appear in Hotel A user list');

        // Hotel Admin fetches user roles for Hotel A: Must ONLY contain Hotel A users, NOT Hotel B users
        $getUserRolesRes = $this->withHeaders([
            'Authorization' => "Bearer {$adminToken}",
            'X-Hotel-ID' => $this->hotelA->id,
        ])->getJson('/api/user-roles');

        $getUserRolesRes->assertStatus(200);
        $roleUserEmails = collect($getUserRolesRes->json('data'))->pluck('email')->all();
        $this->assertContains("sara{$rand}@example.com", $roleUserEmails);
        $this->assertNotContains($this->userB->email, $roleUserEmails, 'Users from Hotel B must not appear in Hotel A user role assignment list');
    }
}


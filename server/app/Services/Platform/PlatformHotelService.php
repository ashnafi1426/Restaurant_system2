<?php

namespace App\Services\Platform;

use App\Models\Hotel;
use App\Models\HotelUser;
use App\Models\User;
use App\Models\Room;
use App\Models\Guest;
use App\Models\Reservation;
use App\Models\Order;
use App\Models\Payment;
use App\Models\AuditLog;
use App\Models\PlatformSetting;
use App\Models\Role;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use App\Services\TenantRoleService;
use App\Mail\HotelAdminPasswordMail;

class PlatformHotelService
{
    protected TenantRoleService $tenantRoleService;

    public function __construct(TenantRoleService $tenantRoleService)
    {
        $this->tenantRoleService = $tenantRoleService;
    }

    /**
     * Get platform statistics
     */
    public function getStatistics(): array
    {
        $totalHotels = Hotel::count();
        $activeHotels = Hotel::where('status', Hotel::STATUS_ACTIVE)->count();
        $inactiveHotels = Hotel::where('status', Hotel::STATUS_INACTIVE)->count();
        $suspendedHotels = Hotel::where('status', Hotel::STATUS_SUSPENDED)->count();

        $totalUsers = User::count();
        $totalHotelAdmins = HotelUser::where('role', 'admin')->distinct('user_id')->count('user_id');
        $totalStaff = User::where('role', '!=', 'admin')->count();
        $totalGuests = Guest::withoutTenant()->count();

        $totalRooms = Room::withoutTenant()->count();
        $totalReservations = Reservation::withoutTenant()->count();
        $totalOrders = Order::withoutTenant()->count();
        $totalPayments = Payment::withoutTenant()->count();
        $totalRevenue = (float) Payment::withoutTenant()->sum('amount');

        $recentHotels = $this->getRecentHotels();
        $topHotels = $this->getTopHotels();

        return [
            'total_hotels' => $totalHotels,
            'active_hotels' => $activeHotels,
            'inactive_hotels' => $inactiveHotels,
            'suspended_hotels' => $suspendedHotels,
            'total_users' => $totalUsers,
            'total_hotel_admins' => $totalHotelAdmins,
            'total_staff' => $totalStaff,
            'total_guests' => $totalGuests,
            'total_rooms' => $totalRooms,
            'total_reservations' => $totalReservations,
            'total_orders' => $totalOrders,
            'total_payments' => $totalPayments,
            'total_revenue' => $totalRevenue,
            'recent_hotels' => $recentHotels,
            'top_hotels' => $topHotels,
        ];
    }

    /**
     * Get recent hotels
     */
    private function getRecentHotels(): array
    {
        return Hotel::with(['users' => function ($q) {
            $q->wherePivot('role', 'admin');
        }])
        ->latest()
        ->take(5)
        ->get()
        ->map(function ($h) {
            return [
                'id' => $h->id,
                'name' => $h->name,
                'slug' => $h->slug,
                'city' => $h->city,
                'status' => $h->status,
                'rooms_count' => Room::withoutTenant()->where('hotel_id', $h->id)->count(),
                'admin' => $h->users->first() ? ($h->users->first()->first_name . ' ' . $h->users->first()->last_name) : 'Unassigned',
                'created_at' => $h->created_at->format('Y-m-d'),
            ];
        })->toArray();
    }

    /**
     * Get top hotels
     */
    private function getTopHotels(): array
    {
        return Hotel::take(5)->get()->map(function ($h) {
            return [
                'name' => $h->name,
                'city' => $h->city,
                'rooms' => Room::withoutTenant()->where('hotel_id', $h->id)->count(),
                'reservations' => Reservation::withoutTenant()->where('hotel_id', $h->id)->count(),
                'revenue' => (float) Payment::withoutTenant()->where('hotel_id', $h->id)->sum('amount'),
            ];
        })->toArray();
    }

    /**
     * Get hotels with filters and pagination
     */
    public function getHotels(array $filters, int $perPage = 15): \Illuminate\Contracts\Pagination\LengthAwarePaginator
    {
        $query = Hotel::query();

        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('slug', 'like', "%{$search}%")
                  ->orWhere('city', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (!empty($filters['city'])) {
            $query->where('city', $filters['city']);
        }

        $hotels = $query->with(['users' => function ($q) {
            $q->wherePivot('role', 'admin');
        }])
        ->latest()
        ->paginate($perPage);

        $hotels->getCollection()->each(function ($hotel) {
            $hotel->rooms_count = Room::withoutTenant()->where('hotel_id', $hotel->id)->count();
            $hotel->reservations_count = Reservation::withoutTenant()->where('hotel_id', $hotel->id)->count();
            $hotel->admin_name = $hotel->users->first()
                ? ($hotel->users->first()->first_name . ' ' . $hotel->users->first()->last_name)
                : 'No Admin Assigned';
            $hotel->admin_email = $hotel->users->first()?->email;
        });

        return $hotels;
    }

    /**
     * Create a new hotel with admin user
     */
    public function createHotel(array $data): array
    {
        $hotel = Hotel::create([
            'id' => (string) Str::uuid(),
            'name' => $data['name'],
            'slug' => Str::slug($data['slug']),
            'email' => $data['email'] ?? null,
            'phone' => $data['phone'] ?? null,
            'address' => $data['address'] ?? null,
            'city' => $data['city'] ?? null,
            'country' => $data['country'] ?? null,
            'logo' => $data['logo'] ?? null,
            'timezone' => $data['timezone'] ?? 'Africa/Addis_Ababa',
            'currency' => $data['currency'] ?? 'ETB',
            'status' => $data['status'] ?? Hotel::STATUS_ACTIVE,
        ]);

        $adminUser = null;
        $generatedPassword = null;

        if (!empty($data['admin_user_id'])) {
            $adminUser = User::find($data['admin_user_id']);
        } elseif (!empty($data['admin_email'])) {
            $adminUser = User::where('email', $data['admin_email'])->first();

            if (!$adminUser) {
                $generatedPassword = $data['admin_password'] ?? ('Adm#' . rand(1000, 9999) . '!' . Str::random(3));
                $adminUser = $this->createAdminUser($data, $generatedPassword);
            }
        }

        $this->tenantRoleService->provisionRolesForHotel($hotel);

        if ($adminUser) {
            $this->assignAdminRole($hotel, $adminUser);
        }

        AuditLog::record(
            'create_hotel',
            $hotel->id,
            Auth::id(),
            'hotels',
            $hotel->id,
            ['name' => $hotel->name, 'slug' => $hotel->slug, 'admin' => $adminUser?->email]
        );

        return [
            'hotel' => $hotel,
            'admin_user' => $adminUser,
            'generated_password' => $generatedPassword
        ];
    }

    /**
     * Create admin user
     */
    private function createAdminUser(array $data, string $password): User
    {
        return User::create([
            'id' => (string) Str::uuid(),
            'first_name' => $data['admin_first_name'],
            'last_name' => $data['admin_last_name'],
            'email' => $data['admin_email'],
            'password_hash' => Hash::make($password),
            'role' => 'admin',
            'is_active' => true,
            'must_change_password' => true,
            'activation_status' => 'active',
            'email_verified_at' => now(),
            'is_platform_admin' => false,
        ]);
    }

    /**
     * Assign admin role to user for hotel
     */
    private function assignAdminRole(Hotel $hotel, User $user): void
    {
        $adminRole = Role::withoutTenant()
            ->where('hotel_id', $hotel->id)
            ->where('slug', 'admin')
            ->first();

        HotelUser::firstOrCreate([
            'hotel_id' => $hotel->id,
            'user_id' => $user->id,
        ], [
            'id' => (string) Str::uuid(),
            'role' => 'admin',
            'role_id' => $adminRole?->id,
            'is_active' => true,
        ]);

        if ($adminRole) {
            DB::table('user_roles')->updateOrInsert(
                [
                    'hotel_id' => $hotel->id,
                    'user_id' => $user->id,
                    'role_id' => $adminRole->id,
                ],
                [
                    'is_primary' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );
        }
    }

    /**
     * Get hotel details with statistics
     */
    public function getHotelDetails(string $id): array
    {
        $hotel = Hotel::findOrFail($id);

        $totalRooms = Room::withoutTenant()->where('hotel_id', $id)->count();
        $totalReservations = Reservation::withoutTenant()->where('hotel_id', $id)->count();
        $totalGuests = Guest::withoutTenant()->where('hotel_id', $id)->count();
        $totalOrders = Order::withoutTenant()->where('hotel_id', $id)->count();
        $totalStaff = HotelUser::where('hotel_id', $id)->where('is_active', true)->count();
        $totalRevenue = (float) Payment::withoutTenant()->where('hotel_id', $id)->sum('amount');

        $admins = $this->getHotelAdmins($id);

        return array_merge($hotel->toArray(), [
            'rooms_count' => $totalRooms,
            'reservations_count' => $totalReservations,
            'guests_count' => $totalGuests,
            'orders_count' => $totalOrders,
            'staff_count' => $totalStaff,
            'revenue_total' => $totalRevenue,
            'admins' => $admins,
        ]);
    }

    /**
     * Update hotel information
     */
    public function updateHotel(string $id, array $data): Hotel
    {
        $hotel = Hotel::findOrFail($id);
        $hotel->update($data);

        AuditLog::record('update_hotel', $hotel->id, Auth::id(), 'hotels', $hotel->id, $data);

        return $hotel;
    }

    /**
     * Update hotel status
     */
    public function updateHotelStatus(string $id, string $status): array
    {
        $hotel = Hotel::findOrFail($id);
        $oldStatus = $hotel->status;
        $hotel->update(['status' => $status]);

        AuditLog::record(
            "hotel_status_changed_to_{$status}",
            $hotel->id,
            Auth::id(),
            'hotels',
            $hotel->id,
            ['old_status' => $oldStatus, 'new_status' => $status]
        );

        return [
            'hotel' => $hotel,
            'old_status' => $oldStatus,
            'new_status' => $status
        ];
    }

    /**
     * Archive hotel
     */
    public function archiveHotel(string $id): Hotel
    {
        $hotel = Hotel::findOrFail($id);
        $hotel->update(['status' => Hotel::STATUS_ARCHIVED]);

        AuditLog::record('archive_hotel', $hotel->id, Auth::id(), 'hotels', $hotel->id, [
            'name' => $hotel->name,
            'archived_at' => now()->toIso8601String(),
        ]);

        return $hotel;
    }

    /**
     * Delete hotel permanently
     */
    public function deleteHotel(string $id, string $confirmName): array
    {
        $hotel = Hotel::findOrFail($id);

        if (trim($confirmName) !== trim($hotel->name)) {
            throw new \InvalidArgumentException("Please type the exact hotel name '{$hotel->name}' to confirm permanent deletion.");
        }

        AuditLog::record('delete_hotel', $hotel->id, Auth::id(), 'hotels', $hotel->id, [
            'name' => $hotel->name,
            'deleted_at' => now()->toIso8601String(),
        ]);

        $hotelName = $hotel->name;
        $hotel->delete();

        return ['hotel_name' => $hotelName];
    }

    /**
     * Get all admins with filters
     */
    public function getAllAdmins(array $filters, int $perPage = 15): \Illuminate\Contracts\Pagination\LengthAwarePaginator
    {
        $query = HotelUser::with(['user', 'hotel'])->where('role', 'admin');

        if (!empty($filters['hotel_id'])) {
            $query->where('hotel_id', $filters['hotel_id']);
        }

        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $query->whereHas('user', function ($q) use ($search) {
                $q->where('first_name', 'like', "%{$search}%")
                  ->orWhere('last_name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        if (!empty($filters['status'])) {
            if ($filters['status'] === 'active') {
                $query->where('is_active', true);
            } elseif ($filters['status'] === 'inactive') {
                $query->where('is_active', false);
            }
        }

        return $query->latest()->paginate($perPage);
    }

    /**
     * Get hotel admins
     */
    public function getHotelAdmins(string $hotelId): array
    {
        return HotelUser::with('user')
            ->where('hotel_id', $hotelId)
            ->where('role', 'admin')
            ->get()
            ->map(function ($membership) {
                return [
                    'membership_id' => $membership->id,
                    'user_id' => $membership->user_id,
                    'name' => $membership->user ? ($membership->user->first_name . ' ' . $membership->user->last_name) : 'Unknown',
                    'email' => $membership->user?->email,
                    'phone' => $membership->user?->phone,
                    'role' => $membership->role,
                    'is_active' => $membership->is_active,
                    'created_at' => $membership->created_at,
                ];
            })->toArray();
    }

    /**
     * Assign admin to hotel
     */
    public function assignAdminToHotel(string $hotelId, array $data): array
    {
        $hotel = Hotel::findOrFail($hotelId);
        $user = null;

        if (!empty($data['user_id'])) {
            $user = User::findOrFail($data['user_id']);
        } elseif (!empty($data['email'])) {
            $user = User::where('email', $data['email'])->first();

            if (!$user) {
                $user = User::create([
                    'id' => (string) Str::uuid(),
                    'first_name' => $data['first_name'] ?? 'Hotel',
                    'last_name' => $data['last_name'] ?? 'Admin',
                    'email' => $data['email'],
                    'phone' => $data['phone'] ?? null,
                    'password_hash' => bcrypt($data['password'] ?? 'HotelAdmin123@'),
                    'role' => 'admin',
                    'is_active' => true,
                    'is_platform_admin' => false,
                ]);
            }
        }

        if (!$user) {
            throw new \InvalidArgumentException('Unable to determine user to assign as admin.');
        }

        $membership = HotelUser::updateOrCreate(
            ['hotel_id' => $hotel->id, 'user_id' => $user->id],
            [
                'id' => (string) Str::uuid(),
                'role' => 'admin',
                'is_active' => true,
            ]
        );

        AuditLog::record(
            'assign_hotel_admin',
            $hotel->id,
            Auth::id(),
            'hotel_users',
            $membership->id,
            ['user_email' => $user->email, 'hotel_name' => $hotel->name]
        );

        return [
            'hotel_id' => $hotel->id,
            'hotel_name' => $hotel->name,
            'user_id' => $user->id,
            'user_email' => $user->email,
            'role' => 'admin',
            'membership_id' => $membership->id,
        ];
    }

    /**
     * Remove admin from hotel
     */
    public function removeAdminFromHotel(string $hotelId, string $userId): Hotel
    {
        $hotel = Hotel::findOrFail($hotelId);

        $membership = HotelUser::where('hotel_id', $hotel->id)
            ->where('user_id', $userId)
            ->first();

        if (!$membership) {
            throw new \InvalidArgumentException('Admin membership not found for this hotel.');
        }

        $membership->delete();

        AuditLog::record(
            'remove_hotel_admin',
            $hotel->id,
            Auth::id(),
            'hotel_users',
            $userId,
            ['hotel_name' => $hotel->name]
        );

        return $hotel;
    }

    /**
     * Create hotel admin user
     */
    public function createHotelAdmin(array $data): array
    {
        $hotel = Hotel::findOrFail($data['hotel_id']);
        $temporaryPassword = 'Adm#' . rand(1000, 9999) . '!' . Str::random(3);

        $user = User::create([
            'id' => (string) Str::uuid(),
            'first_name' => $data['first_name'],
            'last_name' => $data['last_name'],
            'email' => $data['email'],
            'phone' => $data['phone'] ?? null,
            'password_hash' => Hash::make($temporaryPassword),
            'role' => $data['role'] ?? 'admin',
            'is_active' => ($data['status'] ?? 'active') === 'active',
            'must_change_password' => true,
            'activation_status' => 'activated',
            'email_verified_at' => now(),
            'is_platform_admin' => false,
        ]);

        $membership = HotelUser::create([
            'id' => (string) Str::uuid(),
            'hotel_id' => $hotel->id,
            'user_id' => $user->id,
            'role' => 'admin',
            'is_active' => true,
        ]);

        AuditLog::record(
            'create_hotel_admin_system_generated_password',
            $hotel->id,
            Auth::id(),
            'hotel_users',
            $membership->id,
            [
                'admin_email' => $user->email,
                'hotel_name' => $hotel->name,
            ]
        );

        $emailSent = $this->sendAdminCredentialsEmail($user, $temporaryPassword, $hotel, false);

        return [
            'user' => $user,
            'hotel' => $hotel,
            'membership_id' => $membership->id,
            'email_sent' => $emailSent,
            'temporary_password' => $temporaryPassword,
        ];
    }

    /**
     * Reset admin password
     */
    public function resetAdminPassword(string $userId): array
    {
        $user = User::findOrFail($userId);
        $newTempPassword = 'Adm#' . rand(1000, 9999) . '!' . Str::random(3);

        $user->password_hash = Hash::make($newTempPassword);
        $user->must_change_password = true;
        $user->activation_status = 'activated';
        $user->is_active = true;
        $user->save();

        $hotel = $user->hotels()->first();
        $emailSent = $this->sendAdminCredentialsEmail($user, $newTempPassword, $hotel, true);

        AuditLog::record(
            'admin_password_reset_system_generated',
            null,
            Auth::id(),
            'users',
            $user->id,
            ['email' => $user->email, 'email_sent' => $emailSent]
        );

        return [
            'user' => $user,
            'email_sent' => $emailSent,
            'temporary_password' => $newTempPassword,
        ];
    }

    /**
     * Send admin credentials email
     */
    private function sendAdminCredentialsEmail(User $user, string $password, ?Hotel $hotel, bool $isReset = false): bool
    {
        try {
            Mail::to($user->email)->send(new HotelAdminPasswordMail($user, $password, $hotel, $isReset));
            return true;
        } catch (\Throwable $e) {
            Log::warning("Could not send credentials email to {$user->email}: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Toggle admin status
     */
    public function toggleAdminStatus(string $membershipId): HotelUser
    {
        $membership = HotelUser::with(['user', 'hotel'])->findOrFail($membershipId);
        $membership->is_active = !$membership->is_active;
        $membership->save();

        AuditLog::record(
            'toggle_hotel_admin_status',
            $membership->hotel_id,
            Auth::id(),
            'hotel_users',
            $membership->id,
            ['is_active' => $membership->is_active]
        );

        return $membership;
    }

    /**
     * Enter view mode for hotel
     */
    public function enterViewMode(string $hotelId, array $requestData): Hotel
    {
        $hotel = Hotel::findOrFail($hotelId);

        AuditLog::record(
            'platform_admin_view_hotel_mode_entered',
            $hotel->id,
            Auth::id(),
            'hotels',
            $hotel->id,
            [
                'admin_id' => Auth::id(),
                'admin_email' => Auth::user()?->email,
                'hotel_name' => $hotel->name,
                'ip' => $requestData['ip'] ?? null,
                'user_agent' => $requestData['user_agent'] ?? null,
                'timestamp' => now()->toIso8601String(),
            ]
        );

        return $hotel;
    }

    /**
     * Exit view mode
     */
    public function exitViewMode(): void
    {
        AuditLog::record(
            'platform_admin_exit_view_mode',
            null,
            Auth::id(),
            'hotels',
            null,
            ['admin_id' => Auth::id(), 'timestamp' => now()->toIso8601String()]
        );
    }

    /**
     * Get all users with filters
     */
    public function getAllUsers(array $filters, int $perPage = 20): \Illuminate\Contracts\Pagination\LengthAwarePaginator
    {
        $query = User::with('hotels');

        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('first_name', 'like', "%{$search}%")
                  ->orWhere('last_name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        if (!empty($filters['role'])) {
            $query->where('role', $filters['role']);
        }

        if (!empty($filters['hotel_id'])) {
            $hotelId = $filters['hotel_id'];
            $query->whereHas('hotels', function ($q) use ($hotelId) {
                $q->where('hotels.id', $hotelId);
            });
        }

        return $query->latest()->paginate($perPage);
    }

    /**
     * Get audit logs with filters
     */
    public function getAuditLogs(array $filters, int $perPage = 25): \Illuminate\Contracts\Pagination\LengthAwarePaginator
    {
        $query = AuditLog::with(['user', 'hotel'])->latest('created_at');

        if (!empty($filters['hotel_id'])) {
            $query->where('hotel_id', $filters['hotel_id']);
        }

        if (!empty($filters['action'])) {
            $query->where('action', 'like', "%{$filters['action']}%");
        }

        return $query->paginate($perPage);
    }

    /**
     * Get platform settings
     */
    public function getSettings(): array
    {
        return PlatformSetting::all()->pluck('value', 'key')->toArray();
    }

    /**
     * Update platform settings
     */
    public function updateSettings(array $data): array
    {
        foreach ($data as $key => $value) {
            if ($value !== null) {
                PlatformSetting::set($key, $value);
            }
        }

        AuditLog::record('update_platform_settings', null, Auth::id(), 'platform_settings', null, $data);

        return PlatformSetting::all()->pluck('value', 'key')->toArray();
    }
}


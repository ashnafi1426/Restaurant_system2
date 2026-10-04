<?php

namespace App\Http\Controllers\Api\Platform;

use App\Http\Controllers\Controller;
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
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use App\Services\ActivationService;
use App\Mail\HotelAdminPasswordMail;
use App\Services\TenantRoleService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use App\Models\Role;
class PlatformHotelController extends Controller
{
    public function statistics(): JsonResponse
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
        $recentHotels = Hotel::with(['users' => function ($q) {
            $q->wherePivot('role', 'admin');
        }])
        ->withCount(['rooms', 'reservations'])
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
                'rooms_count' => $h->rooms_count,
                'admin' => $h->users->first() ? ($h->users->first()->first_name . ' ' . $h->users->first()->last_name) : 'Unassigned',
                'created_at' => $h->created_at->format('Y-m-d'),
            ];
            });
        $topHotels = Hotel::withCount(['rooms', 'reservations'])
            ->take(5)->get()->map(function ($h) {
            return [
                'name' => $h->name,
                'city' => $h->city,
                'rooms' => $h->rooms_count,
                'reservations' => $h->reservations_count,
                'revenue' => (float) Payment::withoutTenant()->where('hotel_id', $h->id)->sum('amount'),
            ];
        });
        return response()->json([
            'success' => true,
            'data' => [
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
            ],
        ]);
    }
    public function index(Request $request): JsonResponse
    {
        $query = Hotel::query();

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('slug', 'like', "%{$search}%")
                  ->orWhere('city', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }
        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }
        if ($request->filled('city')) {
            $query->where('city', $request->input('city'));
        }
        $hotels = $query->with(['users' => function ($q) {
            $q->wherePivot('role', 'admin');
        }])
        ->withCount([
            'rooms as rooms_count',
            'reservations as reservations_count'
        ])
        ->latest()
            ->paginate($request->integer('per_page', 15));
        $hotels->getCollection()->transform(function ($hotel) {
            $hotelData = $hotel->toArray();
            // rooms_count and reservations_count already loaded via withCount
            $hotelData['admin_name'] = $hotel->users->first() 
                ? ($hotel->users->first()->first_name . ' ' . $hotel->users->first()->last_name) 
                : 'No Admin Assigned';
            $hotelData['admin_email'] = $hotel->users->first()?->email;
            return $hotelData;
        });

        return response()->json([
            'success' => true,
            'data' => $hotels,
        ]);
    }
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'slug' => 'required|string|max:100|unique:hotels,slug',
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:50',
            'address' => 'nullable|string',
            'city' => 'nullable|string|max:100',
            'country' => 'nullable|string|max:100',
            'logo' => 'nullable|string|max:255',
            'timezone' => 'nullable|string|max:50',
            'currency' => 'nullable|string|max:10',
            'status' => 'nullable|string|in:active,inactive,suspended',
            'admin_user_id' => 'nullable|uuid|exists:users,id',
            'admin_email' => 'nullable|email',
            'admin_first_name' => 'required_with:admin_email|nullable|string|max:100',
            'admin_last_name' => 'required_with:admin_email|nullable|string|max:100',
            'admin_password' => 'nullable|string|min:8',
        ]);
        $hotel = Hotel::create([
            'id' => (string) Str::uuid(),
            'name' => $validated['name'],
            'slug' => Str::slug($validated['slug']),
            'email' => $validated['email'] ?? null,
            'phone' => $validated['phone'] ?? null,
            'address' => $validated['address'] ?? null,
            'city' => $validated['city'] ?? null,
            'country' => $validated['country'] ?? null,
            'logo' => $validated['logo'] ?? null,
            'timezone' => $validated['timezone'] ?? 'Africa/Addis_Ababa',
            'currency' => $validated['currency'] ?? 'ETB',
            'status' => $validated['status'] ?? Hotel::STATUS_ACTIVE,
        ]);
        $adminUser = null;
        $generatedPassword = null;
        if (!empty($validated['admin_user_id'])) {
            $adminUser = User::find($validated['admin_user_id']);
        } elseif (!empty($validated['admin_email'])) {
            $adminUser = User::where('email', $validated['admin_email'])->first();
            if (!$adminUser) {
                $generatedPassword = $validated['admin_password'] ?? ('Adm#' . rand(1000, 9999) . '!' . Str::random(3));
                $adminUser = User::create([
                    'id' => (string) Str::uuid(),
                    'first_name' => $validated['admin_first_name'],
                    'last_name' => $validated['admin_last_name'],
                    'email' => $validated['admin_email'],
                    'password_hash' => Hash::make($generatedPassword),
                    'role' => 'admin',
                    'is_active' => true,
                    'must_change_password' => true,
                    'activation_status' => 'active',
                    'email_verified_at' => now(),
                    'is_platform_admin' => false,
                ]);
            }
        }

        $tenantRoleService = app(TenantRoleService::class);
        $tenantRoleService->provisionRolesForHotel($hotel);
        if ($adminUser) {
            $adminRole = Role::withoutTenant()
                ->where('hotel_id', $hotel->id)
                ->where('slug', 'admin')
                ->first();

            HotelUser::firstOrCreate([
                'hotel_id' => $hotel->id,
                'user_id' => $adminUser->id,
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
                        'user_id' => $adminUser->id,
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

        AuditLog::record(
            'create_hotel',
            $hotel->id,
            Auth::id(),
            'hotels',
            $hotel->id,
            ['name' => $hotel->name, 'slug' => $hotel->slug, 'admin' => $adminUser?->email]
        );

        return response()->json([
            'success' => true,
            'message' => 'Hotel onboarded successfully',
            'data' => $hotel,
            'admin' => $adminUser ? [
                'id' => $adminUser->id,
                'email' => $adminUser->email,
                'name' => $adminUser->first_name . ' ' . $adminUser->last_name,
                'temporary_password' => $generatedPassword,
            ] : null,
        ], 201);
    }
    public function show(string $id): JsonResponse
    {
        $hotel = Hotel::findOrFail($id);

        $totalRooms = Room::withoutTenant()->where('hotel_id', $id)->count();
        $totalReservations = Reservation::withoutTenant()->where('hotel_id', $id)->count();
        $totalGuests = Guest::withoutTenant()->where('hotel_id', $id)->count();
        $totalOrders = Order::withoutTenant()->where('hotel_id', $id)->count();
        $totalStaff = HotelUser::where('hotel_id', $id)->where('is_active', true)->count();
        $totalRevenue = (float) Payment::withoutTenant()->where('hotel_id', $id)->sum('amount');

        $admins = HotelUser::with('user')
            ->where('hotel_id', $id)
            ->where('role', 'admin')
            ->get()
            ->map(function ($m) {
                return [
                    'id' => $m->user_id,
                    'name' => $m->user ? ($m->user->first_name . ' ' . $m->user->last_name) : 'Unknown',
                    'email' => $m->user?->email,
                    'phone' => $m->user?->phone,
                    'is_active' => $m->is_active,
                ];
            });

        return response()->json([
            'success' => true,
            'data' => array_merge($hotel->toArray(), [
                'rooms_count' => $totalRooms,
                'reservations_count' => $totalReservations,
                'guests_count' => $totalGuests,
                'orders_count' => $totalOrders,
                'staff_count' => $totalStaff,
                'revenue_total' => $totalRevenue,
                'admins' => $admins,
            ]),
        ]);
    }
    public function update(Request $request, string $id): JsonResponse
    {
        $hotel = Hotel::findOrFail($id);

        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'slug' => 'sometimes|string|max:100|unique:hotels,slug,' . $id,
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:50',
            'address' => 'nullable|string',
            'city' => 'nullable|string|max:100',
            'country' => 'nullable|string|max:100',
            'logo' => 'nullable|string|max:255',
            'timezone' => 'nullable|string|max:50',
            'currency' => 'nullable|string|max:10',
            'status' => 'nullable|string|in:active,inactive,suspended',
        ]);

        $hotel->update($validated);

        AuditLog::record('update_hotel', $hotel->id, Auth::id(), 'hotels', $hotel->id, $validated);
        return response()->json([
            'success' => true,
            'message' => 'Hotel updated successfully',
            'data' => $hotel,
        ]);
    }
    public function updateStatus(Request $request, string $id): JsonResponse
    {
        $hotel = Hotel::findOrFail($id);

        $validated = $request->validate([
            'status' => 'required|string|in:active,inactive,suspended,archived',
        ]);

        $oldStatus = $hotel->status;
        $hotel->update(['status' => $validated['status']]);

        AuditLog::record(
            "hotel_status_changed_to_{$validated['status']}",
            $hotel->id,
            Auth::id(),
            'hotels',
            $hotel->id,
            ['old_status' => $oldStatus, 'new_status' => $validated['status']]
        );

        return response()->json([
            'success' => true,
            'message' => "Hotel status changed from {$oldStatus} to {$validated['status']}",
            'data' => $hotel,
        ]);
    }
    public function archive(string $id): JsonResponse
    {
        $hotel = Hotel::findOrFail($id);
        $hotel->update(['status' => Hotel::STATUS_ARCHIVED]);

        AuditLog::record('archive_hotel', $hotel->id, Auth::id(), 'hotels', $hotel->id, [
            'name' => $hotel->name,
            'archived_at' => now()->toIso8601String(),
        ]);

        return response()->json([
            'success' => true,
            'message' => "Hotel {$hotel->name} has been archived. All data is preserved.",
            'data' => $hotel,
        ]);
    }
    public function destroy(Request $request, string $id): JsonResponse
    {
        $hotel = Hotel::findOrFail($id);

        $validated = $request->validate([
            'confirm_name' => 'required|string',
        ]);

        if (trim($validated['confirm_name']) !== trim($hotel->name)) {
            return response()->json([
                'success' => false,
                'message' => "Please type the exact hotel name '{$hotel->name}' to confirm permanent deletion.",
            ], 422);
        }

        AuditLog::record('delete_hotel', $hotel->id, Auth::id(), 'hotels', $hotel->id, [
            'name' => $hotel->name,
            'deleted_at' => now()->toIso8601String(),
        ]);

        $hotel->delete();

        return response()->json([
            'success' => true,
            'message' => "Hotel {$hotel->name} has been deleted permanently.",
        ]);
    }
    public function allAdmins(Request $request): JsonResponse
    {
        $query = HotelUser::with(['user', 'hotel'])
            ->where('role', 'admin');

        if ($request->filled('hotel_id')) {
            $query->where('hotel_id', $request->input('hotel_id'));
        }

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->whereHas('user', function ($q) use ($search) {
                $q->where('first_name', 'like', "%{$search}%")
                  ->orWhere('last_name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        $admins = $query->latest()->paginate($request->integer('per_page', 15));

        return response()->json([
            'success' => true,
            'data' => $admins,
        ]);
    }
    public function getAdmins(string $id): JsonResponse
    {
        $hotel = Hotel::findOrFail($id);

        $admins = HotelUser::with('user')
            ->where('hotel_id', $hotel->id)
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
            });

        return response()->json([
            'success' => true,
            'hotel' => [
                'id' => $hotel->id,
                'name' => $hotel->name,
                'slug' => $hotel->slug,
            ],
            'data' => $admins,
        ]);
    }
    public function assignAdmin(Request $request, string $id): JsonResponse
    {
        $hotel = Hotel::findOrFail($id);
        $validated = $request->validate([
            'user_id' => 'nullable|uuid|exists:users,id',
            'email' => 'required_without:user_id|nullable|email',
            'first_name' => 'required_with:password|nullable|string|max:100',
            'last_name' => 'required_with:password|nullable|string|max:100',
            'phone' => 'nullable|string|max:50',
            'password' => 'nullable|string|min:8',
        ]);

        $user = null;

        if (!empty($validated['user_id'])) {
            $user = User::findOrFail($validated['user_id']);
        } elseif (!empty($validated['email'])) {
            $user = User::where('email', $validated['email'])->first();

            if (!$user) {
                $user = User::create([
                    'id' => (string) Str::uuid(),
                    'first_name' => $validated['first_name'] ?? 'Hotel',
                    'last_name' => $validated['last_name'] ?? 'Admin',
                    'email' => $validated['email'],
                    'phone' => $validated['phone'] ?? null,
                    'password_hash' => bcrypt($validated['password'] ?? 'HotelAdmin123@'),
                    'role' => 'admin',
                    'is_active' => true,
                    'is_platform_admin' => false,
                ]);
            }
        }

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Unable to determine user to assign as admin.',
            ], 422);
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

        return response()->json([
            'success' => true,
            'message' => "User {$user->email} is now a Hotel Administrator for {$hotel->name}.",
            'data' => [
                'hotel_id' => $hotel->id,
                'hotel_name' => $hotel->name,
                'user_id' => $user->id,
                'user_email' => $user->email,
                'role' => 'admin',
                'membership_id' => $membership->id,
            ],
        ], 200);
    }
    public function removeAdmin(string $id, string $userId): JsonResponse
    {
        $hotel = Hotel::findOrFail($id);

        $membership = HotelUser::where('hotel_id', $hotel->id)
            ->where('user_id', $userId)
            ->first();

        if (!$membership) {
            return response()->json([
                'success' => false,
                'message' => 'Admin membership not found for this hotel.',
            ], 404);
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

        return response()->json([
            'success' => true,
            'message' => 'Admin privileges removed for this hotel successfully.',
        ]);
    }
    public function createHotelAdmin(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'first_name' => 'required|string|max:100',
            'last_name' => 'required|string|max:100',
            'email' => 'required|email|max:255|unique:users,email',
            'phone' => 'nullable|string|max:50',
            'hotel_id' => 'required|uuid|exists:hotels,id',
            'role' => 'nullable|string|max:50',
            'status' => 'nullable|string|in:active,inactive',
        ]);

        $hotel = Hotel::findOrFail($validated['hotel_id']);
        $temporaryPassword = 'Adm#' . rand(1000, 9999) . '!' . Str::random(3);

        $user = User::create([
            'id' => (string) Str::uuid(),
            'first_name' => $validated['first_name'],
            'last_name' => $validated['last_name'],
            'email' => $validated['email'],
            'phone' => $validated['phone'] ?? null,
            'password_hash' => Hash::make($temporaryPassword),
            'role' => $validated['role'] ?? 'admin',
            'is_active' => ($validated['status'] ?? 'active') === 'active',
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

        $emailSent = false;
        try {
            Mail::to($user->email)->send(new HotelAdminPasswordMail($user, $temporaryPassword, $hotel, false));
            $emailSent = true;
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning("Could not send credentials email to {$user->email}: " . $e->getMessage());
        }

        return response()->json([
            'success' => true,
            'message' => $emailSent
                ? "Hotel Admin created and login credentials securely sent to {$user->email}."
                : "Hotel Admin {$user->first_name} {$user->last_name} created successfully.",
            'data' => [
                'user' => [
                    'id' => $user->id,
                    'first_name' => $user->first_name,
                    'last_name' => $user->last_name,
                    'email' => $user->email,
                    'phone' => $user->phone,
                    'role' => $user->role,
                    'is_active' => $user->is_active,
                    'must_change_password' => true,
                ],
                'hotel' => [
                    'id' => $hotel->id,
                    'name' => $hotel->name,
                    'slug' => $hotel->slug,
                ],
                'membership_id' => $membership->id,
                'email_sent' => $emailSent,
            ],
        ], 201);
    }
    public function resetAdminPassword(Request $request, string $userId): JsonResponse
    {
        $user = User::findOrFail($userId);

        $newTempPassword = 'Adm#' . rand(1000, 9999) . '!' . Str::random(3);
        $user->password_hash = Hash::make($newTempPassword);
        $user->must_change_password = true;
        $user->activation_status = 'activated';
        $user->is_active = true;
        $user->save();

        $emailSent = false;
        try {
            $hotel = $user->hotels()->first();
            Mail::to($user->email)->send(new HotelAdminPasswordMail($user, $newTempPassword, $hotel, true));
            $emailSent = true;
        } catch (\Throwable $e) {
            Log::warning("Could not send reset credentials email to {$user->email}: " . $e->getMessage());
        }

        AuditLog::record(
            'admin_password_reset_system_generated',
            null,
            Auth::id(),
            'users',
            $user->id,
            ['email' => $user->email, 'email_sent' => $emailSent]
        );

        return response()->json([
            'success' => true,
            'message' => $emailSent
                ? "A new system-generated temporary password has been securely emailed to {$user->email}."
                : "New temporary password generated and logged for {$user->email}.",
            'email_sent' => $emailSent,
        ]);
    }

    public function resendAdminPassword(Request $request, string $userId): JsonResponse
    {
        return $this->resetAdminPassword($request, $userId);
    }

    public function toggleAdminStatus(string $membershipId): JsonResponse
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

        return response()->json([
            'success' => true,
            'message' => "Hotel admin status updated to " . ($membership->is_active ? 'Active' : 'Inactive') . ".",
            'data' => $membership,
        ]);
    }
    public function enterViewMode(Request $request, string $id): JsonResponse
    {
        $hotel = Hotel::findOrFail($id);

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
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'timestamp' => now()->toIso8601String(),
            ]
        );

        return response()->json([
            'success' => true,
            'message' => "Entered Super Admin View Mode for hotel {$hotel->name}.",
            'data' => [
                'id' => $hotel->id,
                'name' => $hotel->name,
                'slug' => $hotel->slug,
                'logo' => $hotel->logo,
                'currency' => $hotel->currency,
                'role' => 'admin',
                'is_viewing_as_platform_admin' => true,
            ],
        ]);
    }
    public function exitViewMode(Request $request): JsonResponse
    {
        AuditLog::record(
            'platform_admin_exit_view_mode',
            null,
            Auth::id(),
            'hotels',
            null,
            ['admin_id' => Auth::id(), 'timestamp' => now()->toIso8601String()]
        );

        return response()->json([
            'success' => true,
            'message' => 'Exited Super Admin View Mode.',
        ]);
    }
    public function allUsers(Request $request): JsonResponse
    {
        $query = User::with('hotels');

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('first_name', 'like', "%{$search}%")
                  ->orWhere('last_name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        if ($request->filled('role')) {
            $query->where('role', $request->input('role'));
        }

        if ($request->filled('hotel_id')) {
            $hotelId = $request->input('hotel_id');
            $query->whereHas('hotels', function ($q) use ($hotelId) {
                $q->where('hotels.id', $hotelId);
            });
        }

        $users = $query->latest()->paginate($request->integer('per_page', 20));

        return response()->json([
            'success' => true,
            'data' => $users,
        ]);
    }
    public function auditLogs(Request $request): JsonResponse
    {
        $query = AuditLog::with(['user', 'hotel'])->latest('created_at');

        if ($request->filled('hotel_id')) {
            $query->where('hotel_id', $request->input('hotel_id'));
        }

        if ($request->filled('action')) {
            $query->where('action', 'like', "%{$request->input('action')}%");
        }

        $logs = $query->paginate($request->integer('per_page', 25));

        return response()->json([
            'success' => true,
            'data' => $logs,
        ]);
    }
    public function getSettings(): JsonResponse
    {
        $settings = PlatformSetting::all()->pluck('value', 'key');

        return response()->json([
            'success' => true,
            'data' => $settings,
        ]);
    }
    public function updateSettings(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'platform_name' => 'nullable|string|max:255',
            'default_currency' => 'nullable|string|max:10',
            'default_timezone' => 'nullable|string|max:50',
            'maintenance_mode' => 'nullable|string|in:true,false',
            'support_email' => 'nullable|email|max:255',
            'allow_hotel_registration' => 'nullable|string|in:true,false',
        ]);

        foreach ($validated as $key => $val) {
            if ($val !== null) {
                PlatformSetting::set($key, $val);
            }
        }

        AuditLog::record('update_platform_settings', null, Auth::id(), 'platform_settings', null, $validated);

        return response()->json([
            'success' => true,
            'message' => 'Platform settings updated successfully.',
            'data' => PlatformSetting::all()->pluck('value', 'key'),
        ]);
    }
}

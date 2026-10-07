<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreUserRequest;
use App\Http\Requests\UpdateUserRequest;
use App\Http\Resources\UserResource;
use App\Mail\NewUserCreated;
use App\Models\HotelUser;
use App\Models\Role;
use App\Models\User;
use App\Services\TenantContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Throwable;

class UserController extends Controller
{
    /**
     * Invalidate cached user listings for a hotel or across platform.
     */
    protected function invalidateUserCaches(?string $hotelId = null): void
    {
        if ($hotelId) {
            $cur = (int) Cache::get("users_ver:{$hotelId}", 1);
            Cache::put("users_ver:{$hotelId}", $cur + 1, 86400);
            Cache::forget("rbac_roles:{$hotelId}");
        }
        $allCur = (int) Cache::get('users_ver:all', 1);
        Cache::put('users_ver:all', $allCur + 1, 86400);
        Cache::forget('rbac_roles:platform');
    }

    /**
     * Display a listing of users, scoped to the current hotel context.
     * Optimized with column pruning, indexed subqueries, and intelligent Cache::remember.
     */
    public function index(Request $request): JsonResponse
    {
        $hotelId = $request->header('X-Hotel-ID')
            ?: TenantContext::id()
            ?: $request->query('hotel_id');

        $isAllHotels = $request->boolean('all_hotels') && $request->user()?->isPlatformAdmin();

        if (!$isAllHotels && !$hotelId && $request->user()) {
            $hotelId = $request->user()->hotelMemberships()->first()?->hotel_id;
        }

        $tenantScope = $isAllHotels ? 'all' : ($hotelId ?: 'default');
        $forceRefresh = $request->boolean('refresh') || $request->header('X-Refresh') === 'true';

        $ver = (int) Cache::get("users_ver:{$tenantScope}", 1);
        $filterHash = md5(json_encode([
            's' => $request->search,
            'r' => $request->role,
            'a' => $request->input('is_active'),
            'p' => $request->input('page', 1),
            'pp' => (int) $request->get('per_page', 500),
        ]));
        $cacheKey = "users_list:{$tenantScope}:v{$ver}:{$filterHash}";

        if ($forceRefresh) {
            Cache::forget($cacheKey);
        }

        // Cache user results for 10 minutes (600s)
        $cachedPayload = Cache::remember($cacheKey, 600, function () use ($request, $hotelId, $isAllHotels) {
            $query = User::query();

            // 1. Column pruning: fetch only necessary columns
            $query->select([
                'id',
                'first_name',
                'last_name',
                'email',
                'phone',
                'role',
                'is_active',
                'last_login',
                'created_at',
                'updated_at',
            ]);

            // 2. High performance tenant scoping via indexed subquery instead of correlated whereHas
            if (!$isAllHotels && $hotelId) {
                $scopedUserIds = DB::table('hotel_users')
                    ->where('hotel_id', $hotelId)
                    ->pluck('user_id');
                $query->whereIn('id', $scopedUserIds);
            }

            // 3. Search filter
            if ($request->filled('search')) {
                $search = trim($request->search);
                $query->where(function ($q) use ($search) {
                    $q->where('first_name', 'like', "%{$search}%")
                      ->orWhere('last_name', 'like', "%{$search}%")
                      ->orWhere('email', 'like', "%{$search}%")
                      ->orWhere('role', 'like', "%{$search}%");
                });
            }

            // 4. Role filter
            if ($request->filled('role')) {
                $query->where('role', $request->role);
            }

            // 5. Active status filter
            if ($request->filled('is_active')) {
                $query->where('is_active', $request->boolean('is_active'));
            }

            $perPage = (int) $request->get('per_page', 500);
            $paginated = $query->latest('created_at')->paginate($perPage);

            return [
                'data' => UserResource::collection($paginated->items())->toArray($request),
                'links' => [
                    'first' => $paginated->url(1),
                    'last' => $paginated->url($paginated->lastPage()),
                    'prev' => $paginated->previousPageUrl(),
                    'next' => $paginated->nextPageUrl(),
                ],
                'meta' => [
                    'current_page' => $paginated->currentPage(),
                    'from' => $paginated->firstItem(),
                    'last_page' => $paginated->lastPage(),
                    'per_page' => $paginated->perPage(),
                    'to' => $paginated->lastItem(),
                    'total' => $paginated->total(),
                ],
            ];
        });

        return response()->json($cachedPayload);
    }

    /**
     * Store a newly created user in storage with temporary credentials.
     */
    public function store(StoreUserRequest $request): JsonResponse
    {
        DB::beginTransaction();
        try {
            $temporaryPassword = $this->generateSecurePassword();

            $user = User::create([
                'first_name' => $request->first_name,
                'last_name' => $request->last_name,
                'email' => $request->email,
                'phone' => $request->phone,
                'password_hash' => Hash::make($temporaryPassword),
                'role' => $request->role,
                'is_active' => $request->boolean('is_active', true),
                'activation_status' => 'activated',
                'email_verified_at' => now(),
            ]);

            $hotelId = TenantContext::id()
                ?: $request->header('X-Hotel-ID')
                ?: auth()->user()?->hotelMemberships()->first()?->hotel_id;

            $targetRole = null;
            if (!empty($user->role) && $hotelId) {
                $roleSearch = strtolower(trim($user->role));
                $targetRole = Role::withoutTenant()
                    ->where('hotel_id', $hotelId)
                    ->where(function ($q) use ($roleSearch) {
                        $q->whereRaw('LOWER(slug) = ?', [$roleSearch])
                          ->orWhereRaw('LOWER(name) = ?', [$roleSearch]);
                    })
                    ->first();
            }

            if ($hotelId) {
                HotelUser::firstOrCreate([
                    'hotel_id' => $hotelId,
                    'user_id' => $user->id,
                ], [
                    'id' => (string) Str::uuid(),
                    'role' => $user->role ?: 'staff',
                    'role_id' => $targetRole?->id,
                    'is_active' => true,
                ]);

                if ($targetRole) {
                    DB::table('user_roles')->updateOrInsert(
                        [
                            'hotel_id' => $hotelId,
                            'user_id' => $user->id,
                            'role_id' => $targetRole->id,
                        ],
                        [
                            'is_primary' => true,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]
                    );
                }

                if (in_array(strtolower($user->role ?? ''), ['waiter'])) {
                    \App\Models\Waiter::firstOrCreate(
                        ['user_id' => $user->id],
                        [
                            'hotel_id' => $hotelId,
                            'section' => 'All Sections',
                            'shift' => 'morning',
                            'experience_level' => 'junior',
                            'status' => 'active',
                            'availability' => 'available',
                            'maximum_orders' => 5,
                        ]
                    );
                }
            }

            $emailSent = true;
            try {
                Mail::to($user->email)->send(new NewUserCreated($user, $temporaryPassword));
            } catch (Throwable $mailException) {
                $emailSent = false;
                Log::error('Failed to send new user email', [
                    'user_id' => $user->id,
                    'email' => $user->email,
                    'error' => $mailException->getMessage(),
                ]);
            }

            DB::commit();

            // Invalidate user cache for hotel
            $this->invalidateUserCaches($hotelId);

            return response()->json([
                'success' => true,
                'message' => $emailSent
                    ? 'User created successfully. Login credentials sent to ' . $user->email
                    : 'User created successfully but failed to send email. Temporary password: ' . $temporaryPassword,
                'data' => new UserResource($user),
                'temporary_password' => $emailSent ? null : $temporaryPassword,
                'email_sent' => $emailSent,
            ], 201);
        } catch (Throwable $exception) {
            DB::rollBack();

            Log::error('Failed to create user', [
                'error' => $exception->getMessage(),
                'trace' => $exception->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Unable to create user.',
                'error' => $exception->getMessage(),
            ], 500);
        }
    }

    /**
     * Display the specified user.
     */
    public function show(User $user): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => 'User retrieved successfully.',
            'data' => new UserResource($user),
        ], 200);
    }

    /**
     * Update the specified user in storage.
     */
    public function update(UpdateUserRequest $request, User $user): JsonResponse
    {
        DB::beginTransaction();
        try {
            $user->fill($request->only([
                'first_name',
                'last_name',
                'email',
                'phone',
                'role',
            ]));

            if ($request->has('is_active')) {
                $user->is_active = $request->boolean('is_active');
            }

            if ($request->filled('password')) {
                $user->password_hash = Hash::make($request->password);
            }

            $user->save();

            if (!empty($user->role)) {
                $roleSearch = strtolower(trim($user->role));
                $roleModel = Role::whereRaw('LOWER(slug) = ?', [$roleSearch])
                    ->orWhereRaw('LOWER(name) = ?', [$roleSearch])
                    ->first();

                if ($roleModel) {
                    $user->roles()->sync([
                        $roleModel->id => ['is_primary' => true],
                    ]);
                }
            }

            DB::commit();

            $hotelId = $user->hotelMemberships()->first()?->hotel_id;
            $this->invalidateUserCaches($hotelId);

            return response()->json([
                'success' => true,
                'message' => 'User updated successfully.',
                'data' => new UserResource($user),
            ], 200);
        } catch (Throwable $exception) {
            DB::rollBack();

            Log::error('Failed to update user', [
                'user_id' => $user->id,
                'error' => $exception->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Unable to update user.',
                'error' => $exception->getMessage(),
            ], 500);
        }
    }

    /**
     * Remove the specified user from storage.
     */
    public function destroy(User $user): JsonResponse
    {
        if (auth('sanctum')->id() === $user->id || auth()->id() === $user->id) {
            return response()->json([
                'success' => false,
                'message' => 'You cannot delete your own account.',
            ], 403);
        }

        $hotelId = $user->hotelMemberships()->first()?->hotel_id;

        DB::beginTransaction();
        try {
            $user->delete();
            DB::commit();

            $this->invalidateUserCaches($hotelId);

            return response()->json([
                'success' => true,
                'message' => 'User deleted successfully.',
            ], 200);
        } catch (Throwable $exception) {
            DB::rollBack();

            Log::error('Failed to delete user', [
                'user_id' => $user->id,
                'error' => $exception->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Unable to delete user.',
                'error' => $exception->getMessage(),
            ], 500);
        }
    }

    /**
     * Toggle the active status of the specified user.
     */
    public function toggleStatus(User $user): JsonResponse
    {
        DB::beginTransaction();
        try {
            $user->is_active = !$user->is_active;
            $user->save();

            DB::commit();

            $hotelId = $user->hotelMemberships()->first()?->hotel_id;
            $this->invalidateUserCaches($hotelId);

            return response()->json([
                'success' => true,
                'message' => $user->is_active
                    ? 'User activated successfully.'
                    : 'User deactivated successfully.',
                'data' => new UserResource($user),
            ], 200);
        } catch (Throwable $exception) {
            DB::rollBack();

            Log::error('Failed to toggle user status', [
                'user_id' => $user->id,
                'error' => $exception->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Unable to update user status.',
                'error' => $exception->getMessage(),
            ], 500);
        }
    }

    /**
     * Generate a cryptographically secure temporary password.
     */
    private function generateSecurePassword(int $length = 12): string
    {
        $uppercase = 'ABCDEFGHJKLMNPQRSTUVWXYZ';
        $lowercase = 'abcdefghjkmnpqrstuvwxyz';
        $numbers = '23456789';
        $symbols = '!@#$%&*';

        $password =
            $uppercase[random_int(0, strlen($uppercase) - 1)] .
            $lowercase[random_int(0, strlen($lowercase) - 1)] .
            $numbers[random_int(0, strlen($numbers) - 1)] .
            $symbols[random_int(0, strlen($symbols) - 1)];

        $allChars = $uppercase . $lowercase . $numbers . $symbols;
        for ($i = 4; $i < $length; $i++) {
            $password .= $allChars[random_int(0, strlen($allChars) - 1)];
        }

        return str_shuffle($password);
    }
}

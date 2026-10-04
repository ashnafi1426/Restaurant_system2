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
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Throwable;

class UserController extends Controller
{
    /**
     * Display a listing of users, scoped to the current hotel context.
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $query = User::query();

        $hotelId = $request->header('X-Hotel-ID')
            ?: TenantContext::id()
            ?: $request->query('hotel_id');

        $isAllHotels = $request->boolean('all_hotels') && $request->user()?->isPlatformAdmin();

        if (!$isAllHotels) {
            if (!$hotelId && $request->user()) {
                $hotelId = $request->user()->hotelMemberships()->first()?->hotel_id;
            }

            if ($hotelId) {
                $query->whereHas('hotelMemberships', fn ($q) => $q->where('hotel_id', $hotelId));
            }
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('first_name', 'like', "%{$search}%")
                  ->orWhere('last_name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('role', 'like', "%{$search}%");
            });
        }

        if ($request->filled('role')) {
            $query->where('role', $request->role);
        }

        if ($request->filled('is_active')) {
            $query->where('is_active', $request->boolean('is_active'));
        }

        $perPage = (int) $request->get('per_page', 500);

        return UserResource::collection($query->latest()->paginate($perPage));
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

            DB::commit();

            // Send notification email asynchronously after response so user creation returns immediately
            $emailSent = true;
            try {
                dispatch(function () use ($user, $temporaryPassword) {
                    try {
                        Mail::to($user->email)->send(new NewUserCreated($user, $temporaryPassword));
                    } catch (Throwable $mailException) {
                        Log::error('Failed to send new user email', [
                            'user_id' => $user->id,
                            'email' => $user->email,
                            'error' => $mailException->getMessage(),
                        ]);
                    }
                })->afterResponse();
            } catch (Throwable $e) {
                // Fallback for environments without queue bus
                try {
                    Mail::to($user->email)->send(new NewUserCreated($user, $temporaryPassword));
                } catch (Throwable $mailEx) {
                    $emailSent = false;
                }
            }

            return response()->json([
                'success' => true,
                'message' => 'User created successfully. Login credentials sent to ' . $user->email,
                'data' => new UserResource($user),
                'temporary_password' => $temporaryPassword,
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

                $hotelId = TenantContext::id() ?: $request->header('X-Hotel-ID');
                if ($hotelId) {
                    HotelUser::where('hotel_id', $hotelId)
                        ->where('user_id', $user->id)
                        ->update([
                            'role' => $user->role,
                            'role_id' => $roleModel?->id,
                            'is_active' => $user->is_active,
                        ]);
                }
            }

            DB::commit();

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

        DB::beginTransaction();
        try {
            $user->delete();
            DB::commit();

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

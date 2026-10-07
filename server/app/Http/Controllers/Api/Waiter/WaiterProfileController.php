<?php

namespace App\Http\Controllers\Api\Waiter;

use App\Http\Controllers\Controller;
use App\Http\Requests\Waiter\UpdateWaiterProfileRequest;
use App\Models\User;
use App\Services\Waiter\WaiterContextResolver;
use App\Services\Waiter\WaiterPerformanceService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;

class WaiterProfileController extends Controller
{
    protected WaiterPerformanceService $performanceService;
    protected WaiterContextResolver $waiterContextResolver;

    public function __construct(WaiterPerformanceService $performanceService)
    {
        $this->performanceService = $performanceService;
        $this->waiterContextResolver = app(WaiterContextResolver::class);
    }

    public function getProfile(): JsonResponse
    {
        $user = auth()->user();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'User not authenticated',
            ], 401);
        }

        if ($user->relationLoaded('waiter') === false) {
            $user->load('waiter');
        }

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $user->id,
                'first_name' => $user->first_name,
                'last_name' => $user->last_name,
                'full_name' => $user->full_name,
                'email' => $user->email,
                'phone' => $user->phone ?? null,
                'avatar' => $user->avatar ?? null,
                'role' => $user->role ?? 'waiter',
                'waiter' => $user->waiter ? [
                    'id' => $user->waiter->id,
                    'employee_code' => $user->waiter->employee_code ?? null,
                    'manager_id' => $user->waiter->manager_id ?? null,
                    'phone' => $user->waiter->phone ?? null,
                    'shift' => $user->waiter->shift ?? 'flexible',
                    'status' => $user->waiter->status ?? 'active',
                    'profile_photo' => $user->waiter->profile_photo ?? null,
                    'bio' => $user->waiter->bio ?? null,
                    'created_at' => $user->waiter->created_at,
                    'updated_at' => $user->waiter->updated_at,
                ] : null,
                'created_at' => $user->created_at,
                'updated_at' => $user->updated_at,
            ],
        ]);
    }

    public function updateProfile(UpdateWaiterProfileRequest $request): JsonResponse
    {
        $user = auth()->user();
        $validated = $request->validated();

        $userUpdates = [];
        if (isset($validated['first_name'])) {
            $userUpdates['first_name'] = $validated['first_name'];
        }
        if (isset($validated['last_name'])) {
            $userUpdates['last_name'] = $validated['last_name'];
        }
        if (isset($validated['phone'])) {
            $userUpdates['phone'] = $validated['phone'];
        }

        if (!empty($userUpdates)) {
            $user->update($userUpdates);
        }

        if ($user->waiter) {
            $waiterData = [];

            if (isset($validated['shift'])) {
                $waiterData['shift'] = $validated['shift'];
            }

            if (isset($validated['bio'])) {
                $waiterData['bio'] = $validated['bio'];
            }

            if (!empty($waiterData)) {
                $user->waiter->update($waiterData);
            }
        }

        $user->load('waiter');

        return response()->json([
            'success' => true,
            'message' => 'Profile updated successfully',
            'data' => [
                'id' => $user->id,
                'first_name' => $user->first_name,
                'last_name' => $user->last_name,
                'full_name' => $user->full_name,
                'email' => $user->email,
                'phone' => $user->phone,
                'waiter' => $user->waiter ? [
                    'employee_code' => $user->waiter->employee_code,
                    'shift' => $user->waiter->shift,
                    'status' => $user->waiter->status,
                    'bio' => $user->waiter->bio,
                    'profile_photo' => $user->waiter->profile_photo,
                ] : null,
            ],
        ]);
    }

    public function getPerformanceOverview(): JsonResponse
    {
        $waiterId = $this->waiterContextResolver->resolveWaiterId(auth()->user());
        if (!$waiterId) {
            return response()->json([
                'success' => false,
                'message' => 'Waiter profile not linked to this account',
            ], 403);
        }
        $stats = $this->performanceService->getStatistics($waiterId);

        return response()->json([
            'success' => true,
            'data' => $stats,
        ]);
    }

    public function getRatingHistory(Request $request): JsonResponse
    {
        $waiterId = $this->waiterContextResolver->resolveWaiterId(auth()->user());
        if (!$waiterId) {
            return response()->json([
                'success' => false,
                'message' => 'Waiter profile not linked to this account',
            ], 403);
        }
        $days = (int) $request->query('days', 30);

        $trend = $this->performanceService->getPerformanceTrend($waiterId, $days);

        $ratings = array_map(function ($item) {
            return [
                'date' => $item['date'],
                'rating' => $item['rating'],
                'guest_rating' => $item['rating'],
            ];
        }, $trend);

        return response()->json([
            'success' => true,
            'data' => $ratings,
        ]);
    }

    public function changePassword(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'current_password' => 'required|string',
            'new_password' => 'required|string|min:8',
            'new_password_confirmation' => 'required|string|same:new_password',
        ]);

        $user = auth()->user();
        $passwordField = $user->password_hash ? 'password_hash' : 'password';

        if (!Hash::check($validated['current_password'], $user->{$passwordField})) {
            return response()->json([
                'success' => false,
                'message' => 'Current password is incorrect',
            ], 422);
        }

        $user->update([
            $passwordField => Hash::make($validated['new_password']),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Password changed successfully',
        ]);
    }

    public function getShiftInfo(): JsonResponse
    {
        $waiter = auth()->user();

        if (!$waiter->waiter) {
            return response()->json([
                'success' => false,
                'message' => 'Waiter profile not found',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'shift' => $waiter->waiter->shift,
                'status' => $waiter->waiter->status,
                'employee_code' => $waiter->waiter->employee_code,
            ],
        ]);
    }

    public function getAvailability(): JsonResponse
    {
        $waiter = auth()->user();

        if (!$waiter->waiter) {
            return response()->json([
                'success' => false,
                'message' => 'Waiter profile not found',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'is_available' => $waiter->waiter->status === 'active',
                'status' => $waiter->waiter->status,
            ],
        ]);
    }

    public function uploadPhoto(Request $request): JsonResponse
    {
        $request->validate([
            'photo' => 'required|image|mimes:jpeg,png,jpg,gif|max:2048',
        ]);

        $user = auth()->user();

        if (!$user->waiter) {
            return response()->json([
                'success' => false,
                'message' => 'Waiter profile not found',
            ], 404);
        }

        if ($user->waiter->profile_photo) {
            Storage::disk('public')->delete($user->waiter->profile_photo);
        }

        $path = $request->file('photo')->store('profile_photos', 'public');

        $user->waiter->update([
            'profile_photo' => $path,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Photo uploaded successfully',
            'data' => [
                'profile_photo' => $path,
                'photo_url' => asset('storage/' . $path),
            ],
        ]);
    }

    public function getStats(): JsonResponse
    {
        $waiterId = $this->waiterContextResolver->resolveWaiterId(auth()->user());

        if (!$waiterId) {
            return response()->json([
                'success' => false,
                'message' => 'Waiter profile not linked to this account',
            ], 403);
        }

        $deliveriesToday = DB::table('delivery_tasks')
            ->where('waiter_id', $waiterId)
            ->whereDate('created_at', today())
            ->count();

        $deliveriesWeek = DB::table('delivery_tasks')
            ->where('waiter_id', $waiterId)
            ->whereBetween('created_at', [now()->startOfWeek(), now()->endOfWeek()])
            ->count();

        $completedToday = DB::table('delivery_tasks')
            ->where('waiter_id', $waiterId)
            ->where('status', 'delivered')
            ->whereDate('updated_at', today())
            ->count();

        $pendingAssignments = DB::table('delivery_tasks')
            ->where('waiter_id', $waiterId)
            ->whereIn('status', ['pending', 'accepted', 'picked_up'])
            ->count();

        $avgRating = DB::table('waiters_performance')
            ->where('waiter_id', $waiterId)
            ->avg('guest_rating_avg') ?? 0;

        return response()->json([
            'success' => true,
            'data' => [
                'total_deliveries_today' => $deliveriesToday,
                'total_deliveries_week' => $deliveriesWeek,
                'average_rating' => round($avgRating, 2),
                'pending_assignments' => $pendingAssignments,
                'completed_today' => $completedToday,
            ],
        ]);
    }

    public function getSettings(): JsonResponse
    {
        $settings = [
            'notifications_enabled' => true,
            'email_notifications' => true,
            'sms_notifications' => false,
            'theme' => 'light',
            'language' => 'en',
        ];

        return response()->json([
            'success' => true,
            'data' => $settings,
        ]);
    }

    public function updateSettings(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'notifications_enabled' => 'sometimes|boolean',
            'email_notifications' => 'sometimes|boolean',
            'sms_notifications' => 'sometimes|boolean',
            'theme' => 'sometimes|in:light,dark',
            'language' => 'sometimes|in:en,es,fr,de',
        ]);

        $settings = [
            'notifications_enabled' => $validated['notifications_enabled'] ?? true,
            'email_notifications' => $validated['email_notifications'] ?? true,
            'sms_notifications' => $validated['sms_notifications'] ?? false,
            'theme' => $validated['theme'] ?? 'light',
            'language' => $validated['language'] ?? 'en',
        ];

        return response()->json([
            'success' => true,
            'message' => 'Settings updated successfully',
            'data' => $settings,
        ]);
    }
}

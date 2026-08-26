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

class WaiterProfileController extends Controller
{
    protected WaiterPerformanceService $performanceService;
    protected WaiterContextResolver $waiterContextResolver;

    public function __construct(WaiterPerformanceService $performanceService)
    {
        $this->performanceService = $performanceService;
        $this->waiterContextResolver = app(WaiterContextResolver::class);
    }

    /**
     * Get waiter profile
     * GET /api/waiter/profile
     */
    public function getProfile(): JsonResponse
    {
        try {
            $user = auth()->user();
            
            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'User not authenticated',
                ], 401);
            }
            
            // Try to load waiter relationship if it exists
            if ($user->relationLoaded('waiter') === false) {
                $user->load('waiter');
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'id' => $user->id,
                    'name' => $user->name,
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
        } catch (\Exception $e) {
            \Log::error('Profile error:', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch profile',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Update waiter profile
     * PUT /api/waiter/profile
     */
    public function updateProfile(UpdateWaiterProfileRequest $request): JsonResponse
    {
        try {
            $waiter = auth()->user();
            $validated = $request->validated();

            $waiter->update([
                'name' => $validated['name'] ?? $waiter->name,
                'email' => $validated['email'] ?? $waiter->email,
                'phone' => $validated['phone'] ?? $waiter->phone,
            ]);

            if ($waiter->waiter) {
                $waiterData = [];
                
                if (isset($validated['shift'])) {
                    $waiterData['shift'] = $validated['shift'];
                }
                
                if (isset($validated['bio'])) {
                    $waiterData['bio'] = $validated['bio'];
                }
                
                if (!empty($waiterData)) {
                    $waiter->waiter->update($waiterData);
                }
            }

            return response()->json([
                'success' => true,
                'message' => 'Profile updated successfully',
                'data' => [
                    'id' => $waiter->id,
                    'name' => $waiter->name,
                    'email' => $waiter->email,
                    'phone' => $waiter->phone,
                    'waiter' => $waiter->waiter ? [
                        'employee_code' => $waiter->waiter->employee_code,
                        'shift' => $waiter->waiter->shift,
                        'status' => $waiter->waiter->status,
                        'bio' => $waiter->waiter->bio,
                        'profile_photo' => $waiter->waiter->profile_photo,
                    ] : null,
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update profile',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get waiter performance overview
     * GET /api/waiter/profile/performance
     */
    public function getPerformanceOverview(): JsonResponse
    {
        try {
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
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch performance overview',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get waiter rating history
     * GET /api/waiter/profile/ratings
     */
    public function getRatingHistory(Request $request): JsonResponse
    {
        try {
            $waiterId = $this->waiterContextResolver->resolveWaiterId(auth()->user());
            if (!$waiterId) {
                return response()->json([
                    'success' => false,
                    'message' => 'Waiter profile not linked to this account',
                ], 403);
            }
            $days = $request->query('days', 30);

            $trend = $this->performanceService->getPerformanceTrend($waiterId, $days);

            // Extract guest ratings
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
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch rating history',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Change password
     * POST /api/waiter/profile/change-password
     */
    public function changePassword(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'current_password' => 'required|string',
                'new_password' => 'required|string|min:8|confirmed',
            ]);

            $user = auth()->user();

            // Check if password_hash exists, if not use password field
            $passwordField = $user->password_hash ? 'password_hash' : 'password';
            
            // Verify current password
            if (!\Hash::check($validated['current_password'], $user->{$passwordField})) {
                return response()->json([
                    'success' => false,
                    'message' => 'Current password is incorrect',
                ], 422);
            }

            // Update password - use password_hash if it exists
            $user->update([
                $passwordField => \Hash::make($validated['new_password']),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Password changed successfully',
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to change password',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get waiter shift information
     * GET /api/waiter/profile/shift
     */
    public function getShiftInfo(): JsonResponse
    {
        try {
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
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch shift info',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get waiter availability
     * GET /api/waiter/profile/availability
     */
    public function getAvailability(): JsonResponse
    {
        try {
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
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch availability',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Upload profile photo
     * POST /api/waiter/profile/photo
     */
    public function uploadPhoto(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'photo' => 'required|image|mimes:jpeg,png,jpg,gif|max:2048', // 2MB max
            ]);

            $user = auth()->user();
            
            if (!$user->waiter) {
                return response()->json([
                    'success' => false,
                    'message' => 'Waiter profile not found',
                ], 404);
            }

            // Delete old photo if exists
            if ($user->waiter->profile_photo) {
                \Storage::disk('public')->delete($user->waiter->profile_photo);
            }

            // Store new photo
            $path = $request->file('photo')->store('profile_photos', 'public');
            
            // Update waiter profile
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
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to upload photo',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get waiter statistics
     * GET /api/waiter/profile/stats
     */
    public function getStats(): JsonResponse
    {
        try {
            $waiterId = $this->waiterContextResolver->resolveWaiterId(auth()->user());
            
            if (!$waiterId) {
                return response()->json([
                    'success' => false,
                    'message' => 'Waiter profile not linked to this account',
                ], 403);
            }

            // Get today's deliveries
            $deliveriesToday = DB::table('delivery_tasks')
                ->where('waiter_id', $waiterId)
                ->whereDate('created_at', today())
                ->count();

            // Get this week's deliveries
            $deliveriesWeek = DB::table('delivery_tasks')
                ->where('waiter_id', $waiterId)
                ->whereBetween('created_at', [now()->startOfWeek(), now()->endOfWeek()])
                ->count();

            // Get completed today
            $completedToday = DB::table('delivery_tasks')
                ->where('waiter_id', $waiterId)
                ->where('status', 'delivered')
                ->whereDate('updated_at', today())
                ->count();

            // Get pending assignments
            $pendingAssignments = DB::table('delivery_tasks')
                ->where('waiter_id', $waiterId)
                ->whereIn('status', ['pending', 'accepted', 'picked_up'])
                ->count();

            // Get average rating from performance - use correct table name
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
        } catch (\Exception $e) {
            \Log::error('Failed to fetch waiter statistics', [
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch statistics',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get settings
     * GET /api/waiter/settings
     */
    public function getSettings(): JsonResponse
    {
        try {
            $waiter = auth()->user();

            // Get or create settings (could be stored in waiter table or separate settings table)
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
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch settings',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Update settings
     * PUT /api/waiter/settings
     */
    public function updateSettings(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'notifications_enabled' => 'sometimes|boolean',
                'email_notifications' => 'sometimes|boolean',
                'sms_notifications' => 'sometimes|boolean',
                'theme' => 'sometimes|in:light,dark',
                'language' => 'sometimes|in:en,es,fr,de',
            ]);

            // Store settings (could be in database or cache)
            // For now, just return the validated settings
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
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update settings',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}

<?php

namespace App\Http\Controllers\Api\Profile;

use App\Http\Controllers\Controller;
use App\Models\Manager;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;

class ManagerProfileController extends Controller
{
    /**
     * Get manager profile
     * GET /api/manager/profile
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

            // Load manager relationship if not loaded
            if ($user->relationLoaded('manager') === false) {
                $user->load('manager');
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
                    'role' => $user->role,
                    'is_active' => $user->is_active,
                    'manager' => $user->manager ? [
                        'id' => $user->manager->id,
                        'employee_code' => $user->manager->employee_code,
                        'department' => $user->manager->department,
                        'bio' => $user->manager->bio,
                        'profile_photo' => $user->manager->profile_photo,
                        'hire_date' => $user->manager->hire_date,
                        'status' => $user->manager->status,
                        'permissions' => $user->manager->permissions ?? [],
                        'created_at' => $user->manager->created_at,
                        'updated_at' => $user->manager->updated_at,
                    ] : null,
                    'created_at' => $user->created_at,
                    'updated_at' => $user->updated_at,
                ],
            ]);
        } catch (\Exception $e) {
            \Log::error('Manager Profile error:', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch profile',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Update manager profile
     * PUT /api/manager/profile
     */
    public function updateProfile(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'first_name' => 'sometimes|string|max:255',
                'last_name' => 'sometimes|string|max:255',
                'phone' => 'sometimes|nullable|string|max:20',
                'department' => 'sometimes|nullable|string|max:255',
                'bio' => 'sometimes|nullable|string|max:1000',
            ]);

            $user = auth()->user();
            
            DB::beginTransaction();

            // Update user fields
            $userUpdates = array_intersect_key($validated, array_flip(['first_name', 'last_name', 'phone']));
            if (!empty($userUpdates)) {
                $user->update($userUpdates);
            }

            // Update manager fields
            $managerUpdates = array_intersect_key($validated, array_flip(['department', 'bio']));
            if (!empty($managerUpdates) && $user->manager) {
                $user->manager->update($managerUpdates);
            }

            DB::commit();

            $user->load('manager');

            return response()->json([
                'success' => true,
                'message' => 'Profile updated successfully',
                'data' => [
                    'id' => $user->id,
                    'first_name' => $user->first_name,
                    'last_name' => $user->last_name,
                    'full_name' => $user->full_name,
                    'phone' => $user->phone,
                    'manager' => $user->manager ? [
                        'department' => $user->manager->department,
                        'bio' => $user->manager->bio,
                        'status' => $user->manager->status,
                    ] : null,
                ],
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to update profile',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Upload profile photo
     * POST /api/manager/profile/photo
     */
    public function uploadPhoto(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'photo' => 'required|image|mimes:jpeg,png,jpg,gif|max:2048',
            ]);

            $user = auth()->user();

            if (!$user->manager) {
                return response()->json([
                    'success' => false,
                    'message' => 'Manager profile not found',
                ], 404);
            }

            // Delete old photo if exists
            if ($user->manager->profile_photo) {
                Storage::disk('public')->delete($user->manager->profile_photo);
            }

            // Store new photo
            $path = $request->file('photo')->store('profile_photos/managers', 'public');

            $user->manager->update(['profile_photo' => $path]);

            return response()->json([
                'success' => true,
                'message' => 'Photo uploaded successfully',
                'data' => [
                    'profile_photo' => $path,
                    'photo_url' => Storage::url($path),
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to upload photo',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Change password
     * POST /api/manager/profile/change-password
     */
    public function changePassword(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'current_password' => 'required|string',
                'new_password' => 'required|string|min:8|confirmed',
            ]);

            $user = auth()->user();

            // Verify current password
            if (!Hash::check($validated['current_password'], $user->password_hash)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Current password is incorrect',
                ], 422);
            }

            // Update password
            $user->update([
                'password_hash' => Hash::make($validated['new_password']),
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
     * Get manager statistics
     * GET /api/manager/profile/stats
     */
    public function getStats(): JsonResponse
    {
        try {
            $user = auth()->user();

            if (!$user->manager) {
                return response()->json([
                    'success' => false,
                    'message' => 'Manager profile not found',
                ], 404);
            }

            // Get various statistics
            $stats = [
                'total_employees_managed' => DB::table('users')->where('role', '!=', 'admin')->where('role', '!=', 'manager')->count(),
                'total_orders_today' => DB::table('orders')->whereDate('created_at', today())->count(),
                'total_revenue_today' => DB::table('orders')->whereDate('created_at', today())->where('payment_status', 'paid')->sum('total_price'),
                'pending_complaints' => DB::table('complaint_tickets')->where('status', 'open')->count(),
                'active_reservations' => DB::table('reservations')->where('status', 'confirmed')->count(),
            ];

            return response()->json([
                'success' => true,
                'data' => $stats,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch statistics',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}

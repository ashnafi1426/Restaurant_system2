<?php

namespace App\Http\Controllers\Api\Profile;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;

class AdminProfileController extends Controller
{
    /**
     * Get admin profile
     * GET /api/admin/profile
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

            $user->load('administrator');

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
                    'administrator' => $user->administrator ? [
                        'id' => $user->administrator->id,
                        'employee_code' => $user->administrator->employee_code,
                        'department' => $user->administrator->department,
                        'bio' => $user->administrator->bio,
                        'profile_photo' => $user->administrator->profile_photo,
                        'hire_date' => $user->administrator->hire_date,
                        'status' => $user->administrator->status,
                        'created_at' => $user->administrator->created_at,
                        'updated_at' => $user->administrator->updated_at,
                    ] : null,
                    'created_at' => $user->created_at,
                    'updated_at' => $user->updated_at,
                ],
            ]);
        } catch (\Exception $e) {
            \Log::error('Admin Profile error:', [
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
     * Update admin profile
     * PUT /api/admin/profile
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

            $userUpdates = array_intersect_key($validated, array_flip(['first_name', 'last_name', 'phone']));
            if (!empty($userUpdates)) {
                $user->update($userUpdates);
            }

            $adminUpdates = array_intersect_key($validated, array_flip(['department', 'bio']));
            if (!empty($adminUpdates) && $user->administrator) {
                $user->administrator->update($adminUpdates);
            }

            DB::commit();

            $user->load('administrator');

            return response()->json([
                'success' => true,
                'message' => 'Profile updated successfully',
                'data' => [
                    'id' => $user->id,
                    'first_name' => $user->first_name,
                    'last_name' => $user->last_name,
                    'full_name' => $user->full_name,
                    'phone' => $user->phone,
                    'administrator' => $user->administrator ? [
                        'department' => $user->administrator->department,
                        'bio' => $user->administrator->bio,
                        'status' => $user->administrator->status,
                    ] : null,
                ],
            ]);
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
     * POST /api/admin/profile/photo
     */
    public function uploadPhoto(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'photo' => 'required|image|mimes:jpeg,png,jpg,gif|max:2048',
            ]);

            $user = auth()->user();

            if (!$user->administrator) {
                return response()->json([
                    'success' => false,
                    'message' => 'Administrator profile not found',
                ], 404);
            }

            if ($user->administrator->profile_photo) {
                Storage::disk('public')->delete($user->administrator->profile_photo);
            }

            $path = $request->file('photo')->store('profile_photos/admins', 'public');

            $user->administrator->update(['profile_photo' => $path]);

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
     * POST /api/admin/profile/change-password
     */
    public function changePassword(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'current_password' => 'required|string',
                'new_password' => 'required|string|min:8|confirmed',
            ]);

            $user = auth()->user();

            if (!Hash::check($validated['current_password'], $user->password_hash)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Current password is incorrect',
                ], 422);
            }

            $user->update([
                'password_hash' => Hash::make($validated['new_password']),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Password changed successfully',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to change password',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get admin statistics
     * GET /api/admin/profile/stats
     */
    public function getStats(): JsonResponse
    {
        try {
            $stats = [
                'total_users' => DB::table('users')->count(),
                'total_orders' => DB::table('orders')->count(),
                'total_revenue' => DB::table('orders')->where('payment_status', 'paid')->sum('total_price'),
                'active_reservations' => DB::table('reservations')->where('status', 'confirmed')->count(),
                'total_rooms' => DB::table('rooms')->count(),
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

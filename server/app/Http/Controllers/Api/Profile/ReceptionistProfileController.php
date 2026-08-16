<?php

namespace App\Http\Controllers\Api\Profile;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;

class ReceptionistProfileController extends Controller
{
    /**
     * Get receptionist profile
     * GET /api/receptionist/profile
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

            $user->load('receptionist');

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
                    'receptionist' => $user->receptionist ? [
                        'id' => $user->receptionist->id,
                        'employee_code' => $user->receptionist->employee_code,
                        'shift' => $user->receptionist->shift,
                        'desk_number' => $user->receptionist->desk_number,
                        'bio' => $user->receptionist->bio,
                        'profile_photo' => $user->receptionist->profile_photo,
                        'hire_date' => $user->receptionist->hire_date,
                        'status' => $user->receptionist->status,
                        'created_at' => $user->receptionist->created_at,
                        'updated_at' => $user->receptionist->updated_at,
                    ] : null,
                    'created_at' => $user->created_at,
                    'updated_at' => $user->updated_at,
                ],
            ]);
        } catch (\Exception $e) {
            \Log::error('Receptionist Profile error:', [
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
     * Update receptionist profile
     * PUT /api/receptionist/profile
     */
    public function updateProfile(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'first_name' => 'sometimes|string|max:255',
                'last_name' => 'sometimes|string|max:255',
                'phone' => 'sometimes|nullable|string|max:20',
                'shift' => 'sometimes|nullable|string|max:50',
                'desk_number' => 'sometimes|nullable|string|max:50',
                'bio' => 'sometimes|nullable|string|max:1000',
            ]);

            $user = auth()->user();
            
            DB::beginTransaction();

            $userUpdates = array_intersect_key($validated, array_flip(['first_name', 'last_name', 'phone']));
            if (!empty($userUpdates)) {
                $user->update($userUpdates);
            }

            $receptionistUpdates = array_intersect_key($validated, array_flip(['shift', 'desk_number', 'bio']));
            if (!empty($receptionistUpdates) && $user->receptionist) {
                $user->receptionist->update($receptionistUpdates);
            }

            DB::commit();

            $user->load('receptionist');

            return response()->json([
                'success' => true,
                'message' => 'Profile updated successfully',
                'data' => [
                    'id' => $user->id,
                    'first_name' => $user->first_name,
                    'last_name' => $user->last_name,
                    'full_name' => $user->full_name,
                    'phone' => $user->phone,
                    'receptionist' => $user->receptionist ? [
                        'shift' => $user->receptionist->shift,
                        'desk_number' => $user->receptionist->desk_number,
                        'bio' => $user->receptionist->bio,
                        'status' => $user->receptionist->status,
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
     * POST /api/receptionist/profile/photo
     */
    public function uploadPhoto(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'photo' => 'required|image|mimes:jpeg,png,jpg,gif|max:2048',
            ]);

            $user = auth()->user();

            if (!$user->receptionist) {
                return response()->json([
                    'success' => false,
                    'message' => 'Receptionist profile not found',
                ], 404);
            }

            if ($user->receptionist->profile_photo) {
                Storage::disk('public')->delete($user->receptionist->profile_photo);
            }

            $path = $request->file('photo')->store('profile_photos/receptionists', 'public');

            $user->receptionist->update(['profile_photo' => $path]);

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
     * POST /api/receptionist/profile/change-password
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
     * Get receptionist statistics
     * GET /api/receptionist/profile/stats
     */
    public function getStats(): JsonResponse
    {
        try {
            $today = now()->toDateString();
            
            // Get check-ins today (reservations that have check_in_date = today)
            $checkInsToday = DB::table('reservations')
                ->whereDate('check_in_date', $today)
                ->where('status', 'confirmed')
                ->count();
            
            // Get check-outs today (reservations that have check_out_date = today)
            $checkOutsToday = DB::table('reservations')
                ->whereDate('check_out_date', $today)
                ->whereIn('status', ['confirmed', 'checked_in'])
                ->count();
            
            // Get pending reservations
            $pendingReservations = DB::table('reservations')
                ->where('status', 'pending')
                ->count();
            
            // Get confirmed reservations
            $confirmedReservations = DB::table('reservations')
                ->where('status', 'confirmed')
                ->count();
            
            // Get available rooms
            $availableRooms = DB::table('rooms')
                ->where('status', 'available')
                ->count();
            
            // Get occupied rooms (active guests)
            $occupiedRooms = DB::table('rooms')
                ->where('status', 'occupied')
                ->count();

            $stats = [
                'total_check_ins_today' => $checkInsToday,
                'total_check_outs_today' => $checkOutsToday,
                'active_guests' => $occupiedRooms,
                'pending_reservations' => $pendingReservations,
                'confirmed_reservations' => $confirmedReservations,
                'available_rooms' => $availableRooms,
            ];

            return response()->json([
                'success' => true,
                'data' => $stats,
            ]);
        } catch (\Exception $e) {
            \Log::error('Receptionist stats error:', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch statistics',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Update availability status
     * POST /api/receptionist/profile/status
     */
    public function updateStatus(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'status' => 'required|in:active,on_break,off_duty',
            ]);

            $user = auth()->user();

            if (!$user->receptionist) {
                return response()->json([
                    'success' => false,
                    'message' => 'Receptionist profile not found',
                ], 404);
            }

            $user->receptionist->update(['status' => $validated['status']]);

            return response()->json([
                'success' => true,
                'message' => 'Status updated successfully',
                'data' => [
                    'status' => $validated['status'],
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update status',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}

<?php

namespace App\Http\Controllers\Api\Profile;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;

class CashierProfileController extends Controller
{
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

            $user->load('cashier');

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
                    'cashier' => $user->cashier ? [
                        'id' => $user->cashier->id,
                        'employee_code' => $user->cashier->employee_code,
                        'shift' => $user->cashier->shift,
                        'register_number' => $user->cashier->register_number,
                        'bio' => $user->cashier->bio,
                        'profile_photo' => $user->cashier->profile_photo,
                        'hire_date' => $user->cashier->hire_date,
                        'status' => $user->cashier->status,
                        'created_at' => $user->cashier->created_at,
                        'updated_at' => $user->cashier->updated_at,
                    ] : null,
                    'created_at' => $user->created_at,
                    'updated_at' => $user->updated_at,
                ],
            ]);
        } catch (\Exception $e) {
            \Log::error('Cashier Profile error:', [
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
     * Update cashier profile
     * PUT /api/cashier/profile
     */
    public function updateProfile(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'first_name' => 'sometimes|string|max:255',
                'last_name' => 'sometimes|string|max:255',
                'phone' => 'sometimes|nullable|string|max:20',
                'shift' => 'sometimes|nullable|string|max:50',
                'register_number' => 'sometimes|nullable|string|max:50',
                'bio' => 'sometimes|nullable|string|max:1000',
            ]);

            $user = auth()->user();
            
            DB::beginTransaction();

            $userUpdates = array_intersect_key($validated, array_flip(['first_name', 'last_name', 'phone']));
            if (!empty($userUpdates)) {
                $user->update($userUpdates);
            }

            $cashierUpdates = array_intersect_key($validated, array_flip(['shift', 'register_number', 'bio']));
            if (!empty($cashierUpdates) && $user->cashier) {
                $user->cashier->update($cashierUpdates);
            }

            DB::commit();

            $user->load('cashier');

            return response()->json([
                'success' => true,
                'message' => 'Profile updated successfully',
                'data' => [
                    'id' => $user->id,
                    'first_name' => $user->first_name,
                    'last_name' => $user->last_name,
                    'full_name' => $user->full_name,
                    'phone' => $user->phone,
                    'cashier' => $user->cashier ? [
                        'shift' => $user->cashier->shift,
                        'register_number' => $user->cashier->register_number,
                        'bio' => $user->cashier->bio,
                        'status' => $user->cashier->status,
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
     * POST /api/cashier/profile/photo
     */
    public function uploadPhoto(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'photo' => 'required|image|mimes:jpeg,png,jpg,gif|max:2048',
            ]);

            $user = auth()->user();

            if (!$user->cashier) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cashier profile not found',
                ], 404);
            }

            if ($user->cashier->profile_photo) {
                Storage::disk('public')->delete($user->cashier->profile_photo);
            }

            $path = $request->file('photo')->store('profile_photos/cashiers', 'public');

            $user->cashier->update(['profile_photo' => $path]);

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
     * POST /api/cashier/profile/change-password
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
     * Get cashier statistics
     * GET /api/cashier/profile/stats
     */
    public function getStats(): JsonResponse
    {
        try {
            $stats = [
                'total_transactions_today' => DB::table('payments')->whereDate('created_at', today())->count(),
                'total_amount_collected_today' => DB::table('payments')
                    ->whereDate('created_at', today())
                    ->where('payment_status', 'completed')
                    ->sum('amount'),
                'pending_payments' => DB::table('payments')->where('payment_status', 'pending')->count(),
                'completed_payments' => DB::table('payments')
                    ->whereDate('created_at', today())
                    ->where('payment_status', 'completed')
                    ->count(),
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

    /**
     * Update availability status
     * POST /api/cashier/profile/status
     */
    public function updateStatus(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'status' => 'required|in:active,on_break,off_duty',
            ]);

            $user = auth()->user();

            if (!$user->cashier) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cashier profile not found',
                ], 404);
            }

            $user->cashier->update(['status' => $validated['status']]);

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

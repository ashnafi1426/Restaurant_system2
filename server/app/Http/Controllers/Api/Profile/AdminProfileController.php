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
    private function ensureAdminColumns()
    {
        try {
            if (\Illuminate\Support\Facades\Schema::hasTable('administrators')) {
                if (!\Illuminate\Support\Facades\Schema::hasColumn('administrators', 'profile_photo')) {
                    \Illuminate\Support\Facades\Schema::table('administrators', function (\Illuminate\Database\Schema\Blueprint $table) {
                        $table->string('profile_photo')->nullable();
                    });
                }
                if (!\Illuminate\Support\Facades\Schema::hasColumn('administrators', 'department')) {
                    \Illuminate\Support\Facades\Schema::table('administrators', function (\Illuminate\Database\Schema\Blueprint $table) {
                        $table->string('department')->nullable();
                    });
                }
                if (!\Illuminate\Support\Facades\Schema::hasColumn('administrators', 'bio')) {
                    \Illuminate\Support\Facades\Schema::table('administrators', function (\Illuminate\Database\Schema\Blueprint $table) {
                        $table->text('bio')->nullable();
                    });
                }
                if (!\Illuminate\Support\Facades\Schema::hasColumn('administrators', 'employee_code')) {
                    \Illuminate\Support\Facades\Schema::table('administrators', function (\Illuminate\Database\Schema\Blueprint $table) {
                        $table->string('employee_code')->nullable();
                    });
                }
                if (!\Illuminate\Support\Facades\Schema::hasColumn('administrators', 'hire_date')) {
                    \Illuminate\Support\Facades\Schema::table('administrators', function (\Illuminate\Database\Schema\Blueprint $table) {
                        $table->date('hire_date')->nullable();
                    });
                }
                if (!\Illuminate\Support\Facades\Schema::hasColumn('administrators', 'status')) {
                    \Illuminate\Support\Facades\Schema::table('administrators', function (\Illuminate\Database\Schema\Blueprint $table) {
                        $table->string('status', 50)->default('active');
                    });
                }
            }
        } catch (\Throwable $e) {
            \Log::warning('ensureAdminColumns warning: ' . $e->getMessage());
        }
    }

    public function getProfile(): JsonResponse
    {
        try {
            $this->ensureAdminColumns();
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
                        'employee_code' => $user->administrator->employee_code ?? null,
                        'department' => $user->administrator->department ?? null,
                        'bio' => $user->administrator->bio ?? null,
                        'profile_photo' => $user->administrator->profile_photo ?? null,
                        'hire_date' => $user->administrator->hire_date ?? null,
                        'status' => $user->administrator->status ?? 'active',
                        'created_at' => $user->administrator->created_at ?? null,
                        'updated_at' => $user->administrator->updated_at ?? null,
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

    public function uploadPhoto(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'photo' => 'required|image|mimes:jpeg,png,jpg,gif,webp|max:5120',
            ]);

            $user = auth()->user();
            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'User not authenticated',
                ], 401);
            }

            if (!$user->administrator) {
                \App\Models\Administrator::create([
                    'id' => $user->id,
                    'status' => 'active',
                ]);
                $user->load('administrator');
            }

            if ($user->administrator && $user->administrator->profile_photo) {
                try {
                    Storage::disk('public')->delete($user->administrator->profile_photo);
                } catch (\Throwable $e) {}
            }

            $path = $request->file('photo')->store('profile_photos/admins', 'public');

            if ($user->administrator) {
                $user->administrator->update(['profile_photo' => $path]);
            }

            return response()->json([
                'success' => true,
                'message' => 'Photo uploaded successfully',
                'data' => [
                    'profile_photo' => $path,
                    'photo_url' => Storage::url($path),
                ],
            ]);
        } catch (\Exception $e) {
            \Log::error('Admin photo upload error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to upload photo',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

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

    public function getStats(): JsonResponse
    {
        try {
            $totalRevenue = 0;
            if (\Illuminate\Support\Facades\Schema::hasTable('orders')) {
                if (\Illuminate\Support\Facades\Schema::hasColumn('orders', 'total')) {
                    $totalRevenue = DB::table('orders')->sum('total') ?? 0;
                } elseif (\Illuminate\Support\Facades\Schema::hasColumn('orders', 'total_amount')) {
                    $totalRevenue = DB::table('orders')->sum('total_amount') ?? 0;
                } elseif (\Illuminate\Support\Facades\Schema::hasColumn('orders', 'total_price')) {
                    $totalRevenue = DB::table('orders')->sum('total_price') ?? 0;
                }
            }

            $stats = [
                'total_users' => \Illuminate\Support\Facades\Schema::hasTable('users') ? DB::table('users')->count() : 0,
                'total_orders' => \Illuminate\Support\Facades\Schema::hasTable('orders') ? DB::table('orders')->count() : 0,
                'total_revenue' => (float) $totalRevenue,
                'active_reservations' => \Illuminate\Support\Facades\Schema::hasTable('reservations') ? DB::table('reservations')->where('status', 'confirmed')->count() : 0,
                'total_rooms' => \Illuminate\Support\Facades\Schema::hasTable('rooms') ? DB::table('rooms')->count() : 0,
            ];

            return response()->json([
                'success' => true,
                'data' => $stats,
            ]);
        } catch (\Exception $e) {
            \Log::error('Admin getStats error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch statistics',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}

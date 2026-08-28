<?php

namespace App\Http\Controllers\Api\Profile;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;

class ChefProfileController extends Controller
{
    /**
     * Ensure all profile columns exist in chefs table
     */
    private function ensureChefColumns()
    {
        try {
            if (\Illuminate\Support\Facades\Schema::hasTable('chefs')) {
                if (!\Illuminate\Support\Facades\Schema::hasColumn('chefs', 'profile_photo')) {
                    \Illuminate\Support\Facades\Schema::table('chefs', function (\Illuminate\Database\Schema\Blueprint $table) {
                        $table->string('profile_photo')->nullable();
                    });
                }
                if (!\Illuminate\Support\Facades\Schema::hasColumn('chefs', 'bio')) {
                    \Illuminate\Support\Facades\Schema::table('chefs', function (\Illuminate\Database\Schema\Blueprint $table) {
                        $table->text('bio')->nullable();
                    });
                }
                if (!\Illuminate\Support\Facades\Schema::hasColumn('chefs', 'employee_code')) {
                    \Illuminate\Support\Facades\Schema::table('chefs', function (\Illuminate\Database\Schema\Blueprint $table) {
                        $table->string('employee_code')->nullable();
                    });
                }
                if (!\Illuminate\Support\Facades\Schema::hasColumn('chefs', 'specialization')) {
                    \Illuminate\Support\Facades\Schema::table('chefs', function (\Illuminate\Database\Schema\Blueprint $table) {
                        $table->string('specialization')->nullable();
                    });
                }
                if (!\Illuminate\Support\Facades\Schema::hasColumn('chefs', 'shift')) {
                    \Illuminate\Support\Facades\Schema::table('chefs', function (\Illuminate\Database\Schema\Blueprint $table) {
                        $table->string('shift')->nullable();
                    });
                }
                if (!\Illuminate\Support\Facades\Schema::hasColumn('chefs', 'experience_years')) {
                    \Illuminate\Support\Facades\Schema::table('chefs', function (\Illuminate\Database\Schema\Blueprint $table) {
                        $table->integer('experience_years')->nullable();
                    });
                }
                if (!\Illuminate\Support\Facades\Schema::hasColumn('chefs', 'hire_date')) {
                    \Illuminate\Support\Facades\Schema::table('chefs', function (\Illuminate\Database\Schema\Blueprint $table) {
                        $table->date('hire_date')->nullable();
                    });
                }
                if (!\Illuminate\Support\Facades\Schema::hasColumn('chefs', 'status')) {
                    \Illuminate\Support\Facades\Schema::table('chefs', function (\Illuminate\Database\Schema\Blueprint $table) {
                        $table->string('status', 50)->default('active');
                    });
                }
                if (!\Illuminate\Support\Facades\Schema::hasColumn('chefs', 'rank')) {
                    \Illuminate\Support\Facades\Schema::table('chefs', function (\Illuminate\Database\Schema\Blueprint $table) {
                        $table->string('rank', 50)->default('junior');
                    });
                }
            }
        } catch (\Throwable $e) {
            \Log::warning('ensureChefColumns warning: ' . $e->getMessage());
        }
    }

    /**
     * Get chef profile
     * GET /api/chef/profile
     */
    public function getProfile(): JsonResponse
    {
        try {
            $this->ensureChefColumns();
            $user = auth()->user();
            
            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'User not authenticated',
                ], 401);
            }

            $user->load('chef');

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
                    'chef' => $user->chef ? [
                        'id' => $user->chef->id,
                        'employee_code' => $user->chef->employee_code ?? null,
                        'specialization' => $user->chef->specialization ?? null,
                        'shift' => $user->chef->shift ?? null,
                        'experience_years' => $user->chef->experience_years ?? null,
                        'rank' => $user->chef->rank ?? null,
                        'bio' => $user->chef->bio ?? null,
                        'profile_photo' => $user->chef->profile_photo ?? null,
                        'hire_date' => $user->chef->hire_date ?? null,
                        'status' => $user->chef->status ?? 'active',
                        'created_at' => $user->chef->created_at ?? null,
                        'updated_at' => $user->chef->updated_at ?? null,
                    ] : null,
                    'created_at' => $user->created_at,
                    'updated_at' => $user->updated_at,
                ],
            ]);
        } catch (\Exception $e) {
            \Log::error('Chef Profile error:', [
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
     * Update chef profile
     * PUT /api/chef/profile
     */
    public function updateProfile(Request $request): JsonResponse
    {
        try {
            $this->ensureChefColumns();
            $validated = $request->validate([
                'first_name' => 'sometimes|string|max:255',
                'last_name' => 'sometimes|string|max:255',
                'phone' => 'sometimes|nullable|string|max:20',
                'specialization' => 'sometimes|nullable|string|max:255',
                'shift' => 'sometimes|nullable|string|max:50',
                'experience_years' => 'sometimes|nullable|integer|min:0',
                'bio' => 'sometimes|nullable|string|max:1000',
            ]);

            $user = auth()->user();
            
            DB::beginTransaction();

            $userUpdates = array_intersect_key($validated, array_flip(['first_name', 'last_name', 'phone']));
            if (!empty($userUpdates)) {
                $user->update($userUpdates);
            }

            if (!$user->chef) {
                \App\Models\Chef::create([
                    'id' => $user->id,
                    'status' => 'active',
                ]);
                $user->load('chef');
            }

            $chefUpdates = array_intersect_key($validated, array_flip(['specialization', 'shift', 'experience_years', 'bio']));
            if (!empty($chefUpdates) && $user->chef) {
                $user->chef->update($chefUpdates);
            }

            DB::commit();

            $user->load('chef');

            return response()->json([
                'success' => true,
                'message' => 'Profile updated successfully',
                'data' => [
                    'id' => $user->id,
                    'first_name' => $user->first_name,
                    'last_name' => $user->last_name,
                    'full_name' => $user->full_name,
                    'phone' => $user->phone,
                    'chef' => $user->chef ? [
                        'specialization' => $user->chef->specialization,
                        'shift' => $user->chef->shift,
                        'experience_years' => $user->chef->experience_years,
                        'rank' => $user->chef->rank,
                        'bio' => $user->chef->bio,
                        'status' => $user->chef->status,
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
     * POST /api/chef/profile/photo
     */
    public function uploadPhoto(Request $request): JsonResponse
    {
        try {
            $this->ensureChefColumns();
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

            if (!$user->chef) {
                \App\Models\Chef::create([
                    'id' => $user->id,
                    'status' => 'active',
                ]);
                $user->load('chef');
            }

            if ($user->chef && $user->chef->profile_photo) {
                try {
                    Storage::disk('public')->delete($user->chef->profile_photo);
                } catch (\Throwable $e) {}
            }

            $path = $request->file('photo')->store('profile_photos/chefs', 'public');

            if ($user->chef) {
                $user->chef->update(['profile_photo' => $path]);
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
            \Log::error('Chef uploadPhoto error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to upload photo',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Change password
     * POST /api/chef/profile/change-password
     */
    public function changePassword(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'current_password' => 'required|string',
                'new_password' => 'required|string|min:8',
                'new_password_confirmation' => 'required|string|same:new_password',
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
     * Get chef statistics
     * GET /api/chef/profile/stats
     */
    public function getStats(): JsonResponse
    {
        try {
            $user = auth()->user();
            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'User not authenticated',
                ], 401);
            }

            if (!$user->chef) {
                \App\Models\Chef::create([
                    'id' => $user->id,
                    'status' => 'active',
                ]);
                $user->load('chef');
            }

            $hasOrders = \Illuminate\Support\Facades\Schema::hasTable('orders');
            $hasChefId = $hasOrders && \Illuminate\Support\Facades\Schema::hasColumn('orders', 'chef_id');
            $hasKitchenStatus = $hasOrders && \Illuminate\Support\Facades\Schema::hasColumn('orders', 'kitchen_status');

            $totalOrders = 0;
            $ordersToday = 0;
            $ordersInProgress = 0;
            $completedToday = 0;

            if ($hasOrders) {
                $query = DB::table('orders');
                if ($hasChefId) {
                    $query->where('chef_id', $user->id);
                }

                $totalOrders = (clone $query)->count();
                $ordersToday = (clone $query)->whereDate('created_at', today())->count();

                if ($hasKitchenStatus) {
                    $ordersInProgress = (clone $query)->whereIn('kitchen_status', ['pending', 'preparing'])->count();
                    $completedToday = (clone $query)->where('kitchen_status', 'ready')->whereDate('updated_at', today())->count();
                } else {
                    $ordersInProgress = (clone $query)->whereIn('status', ['pending', 'in_progress', 'preparing'])->count();
                    $completedToday = (clone $query)->whereIn('status', ['ready', 'served', 'completed'])->whereDate('updated_at', today())->count();
                }
            }

            $stats = [
                'total_orders_prepared' => $totalOrders,
                'orders_today' => $ordersToday,
                'orders_in_progress' => $ordersInProgress,
                'completed_orders_today' => $completedToday,
            ];

            return response()->json([
                'success' => true,
                'data' => $stats,
            ]);
        } catch (\Exception $e) {
            \Log::error('Chef getStats error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch statistics',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Update availability status
     * POST /api/chef/profile/status
     */
    public function updateStatus(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'status' => 'required|in:active,on_break,off_duty',
            ]);

            $user = auth()->user();

            if (!$user->chef) {
                return response()->json([
                    'success' => false,
                    'message' => 'Chef profile not found',
                ], 404);
            }

            $user->chef->update(['status' => $validated['status']]);

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

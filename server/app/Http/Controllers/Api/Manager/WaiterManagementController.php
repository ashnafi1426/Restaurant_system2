<?php

namespace App\Http\Controllers\Api\Manager;

use App\Http\Controllers\Controller;
use App\Models\Waiter;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;

class WaiterManagementController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        try {
            // Get all waiters with user relationship loaded
            $waiters = Waiter::with('user')
                ->orderBy('section')
                ->get()
                ->map(function ($waiter) {
                    return [
                        'id' => $waiter->id,
                        'user_id' => $waiter->user_id,
                        'user' => $waiter->user ? [
                            'id' => $waiter->user->id,
                            'first_name' => $waiter->user->first_name,
                            'last_name' => $waiter->user->last_name,
                            'name' => $waiter->user->first_name . ' ' . $waiter->user->last_name,
                            'email' => $waiter->user->email,
                            'phone' => $waiter->user->phone,
                        ] : null,
                        'section' => $waiter->section,
                        'shift' => $waiter->shift,
                        'status' => $waiter->status,
                        'experience_level' => $waiter->experience_level,
                        'employment_type' => $waiter->employment_type,
                        'availability' => $waiter->availability,
                        'current_orders' => $waiter->current_orders,
                        'maximum_orders' => $waiter->maximum_orders,
                        'employee_number' => $waiter->employee_number,
                        'phone' => $waiter->phone,
                        'hire_date' => $waiter->hire_date,
                    ];
                });
            
            Log::info('Waiters fetched successfully', [
                'count' => $waiters->count(),
            ]);
            
            return response()->json([
                'success' => true,
                'data' => $waiters,
            ]);
        } catch (\Exception $e) {
            Log::error('Error fetching waiters', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to load waiters: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Create a new waiter
     */
    public function store(Request $request): JsonResponse
    {
        try {
            Log::info('Waiter store request received', [
                'all_data' => $request->all(),
                'keys' => array_keys($request->all())
            ]);
            $isNewUser = empty($request->input('user_id'));
            
            $rules = [
                'section' => 'required|string|max:100',
                'shift' => 'required|in:morning,afternoon,evening,night',
                'experience_level' => 'required|in:junior,senior,head',
                'status' => 'required|in:active,inactive,on_break',
                'maximum_orders' => 'required|integer|min:1|max:20',
                'employment_type' => 'sometimes|in:full_time,part_time,contract',
                'employee_number' => 'sometimes|string|max:50|unique:waiters,employee_number',
            ];
            
            if ($isNewUser) {
                // New user validation - all fields required
                $rules = array_merge($rules, [
                    'first_name' => 'required|string|max:255',
                    'last_name' => 'required|string|max:255',
                    'email' => 'required|email',
                    'phone' => 'required|string|max:20',
                    'password' => 'required|string|min:8',
                ]);
            } else {
                // Existing user - just need the ID
                $rules = array_merge($rules, [
                    'user_id' => 'required|uuid|exists:users,id',
                ]);
            }
            
            Log::info('Waiter validation rules', [
                'is_new_user' => $isNewUser,
                'rules' => array_keys($rules)
            ]);
            
            $validated = $request->validate($rules);
            
            Log::info('Waiter validation passed', [
                'validated_keys' => array_keys($validated)
            ]);

            // If creating new user
            if ($isNewUser) {
                try {
                    $user = User::where('email', $validated['email'])->first();

                    if ($user) {
                        $user->update([
                            'first_name' => $validated['first_name'],
                            'last_name' => $validated['last_name'],
                            'phone' => $validated['phone'] ?? $user->phone,
                            'role' => 'waiter',
                            'is_active' => true,
                        ]);

                        Log::info('Reusing existing user for waiter creation', [
                            'user_id' => $user->id,
                            'email' => $user->email,
                        ]);
                    } else {
                        $hashedPassword = Hash::make($validated['password']);

                        $user = User::create([
                            'first_name' => $validated['first_name'],
                            'last_name' => $validated['last_name'],
                            'email' => $validated['email'],
                            'phone' => $validated['phone'] ?? null,
                            'password_hash' => $hashedPassword,
                            'role' => 'waiter',
                            'is_active' => true,
                        ]);

                        Log::info('Waiter user created successfully', [
                            'user_id' => $user->id,
                            'email' => $user->email
                        ]);
                    }

                    $validated['user_id'] = $user->id;
                } catch (\Illuminate\Database\QueryException $dbError) {
                    Log::error('Database error creating user', [
                        'message' => $dbError->getMessage(),
                        'sql' => $dbError->getSql() ?? 'N/A',
                    ]);
                    
                    return response()->json([
                        'success' => false,
                        'message' => 'Database error: ' . $dbError->getMessage(),
                    ], 500);
                } catch (\Exception $userError) {
                    Log::error('Error creating user', [
                        'message' => $userError->getMessage(),
                        'file' => $userError->getFile(),
                        'line' => $userError->getLine(),
                    ]);
                    
                    return response()->json([
                        'success' => false,
                        'message' => 'Failed to create user: ' . $userError->getMessage(),
                    ], 500);
                }
            }

            try {
                $existingWaiter = Waiter::where('user_id', $validated['user_id'])->first();

                if ($existingWaiter) {
                    $existingWaiter->update([
                        'phone' => $validated['phone'] ?? $existingWaiter->phone,
                        'section' => $validated['section'],
                        'shift' => $validated['shift'],
                        'experience_level' => $validated['experience_level'],
                        'employment_type' => $validated['employment_type'] ?? ($existingWaiter->employment_type ?? 'full_time'),
                        'hire_date' => $request->input('hire_date') ? $validated['hire_date'] : ($existingWaiter->hire_date ?? now()->toDateString()),
                        'status' => $validated['status'],
                        'availability' => 'offline',
                        'maximum_orders' => $validated['maximum_orders'],
                        'employee_number' => $validated['employee_number'] ?? $existingWaiter->employee_number,
                    ]);

                    return response()->json([
                        'success' => true,
                        'data' => $existingWaiter->load('user'),
                        'message' => 'Waiter already existed for this user and was updated successfully',
                    ]);
                }

                // Prepare waiter data
                $waiterData = [
                    'user_id' => $validated['user_id'],
                    'phone' => $validated['phone'] ?? null,
                    'section' => $validated['section'],
                    'shift' => $validated['shift'],
                    'experience_level' => $validated['experience_level'],
                    'employment_type' => $validated['employment_type'] ?? 'full_time',
                    'hire_date' => $request->input('hire_date') ? $validated['hire_date'] : now()->toDateString(),
                    'status' => $validated['status'],
                    'availability' => 'offline',
                    'current_orders' => 0,
                    'maximum_orders' => $validated['maximum_orders'],
                    'employee_number' => $validated['employee_number'] ?? null,
                ];
                
                Log::info('Creating waiter with data', $waiterData);

                $waiter = Waiter::create($waiterData);

                Log::info('Waiter created successfully', [
                    'waiter_id' => $waiter->id,
                    'user_id' => $validated['user_id'],
                    'waiter_data' => $waiter->toArray(),
                    'user_data' => $waiter->user ? $waiter->user->toArray() : null
                ]);

                $responseData = $waiter->load('user');
                
                Log::info('Response being sent to client', [
                    'data' => $responseData
                ]);

                return response()->json([
                    'success' => true,
                    'data' => $responseData,
                    'message' => 'Waiter created successfully',
                ], 201);
            } catch (\Illuminate\Database\QueryException $dbError) {
                Log::error('Database error creating waiter', [
                    'message' => $dbError->getMessage(),
                    'sql' => $dbError->getSql() ?? 'N/A',
                ]);
                
                return response()->json([
                    'success' => false,
                    'message' => 'Database error: ' . $dbError->getMessage(),
                ], 500);
            } catch (\Exception $waiterError) {
                Log::error('Error creating waiter', [
                    'message' => $waiterError->getMessage(),
                    'file' => $waiterError->getFile(),
                    'line' => $waiterError->getLine(),
                ]);
                
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to create waiter: ' . $waiterError->getMessage(),
                ], 500);
            }
        } catch (\Illuminate\Validation\ValidationException $validationError) {
            Log::warning('Waiter validation error', [
                'errors' => $validationError->errors(),
                'request_data' => $request->all()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validationError->errors(),
            ], 422);
        } catch (\Exception $e) {
            Log::error('Unexpected error in waiter store', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
            ]);
            
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get single waiter with full details
     */
    public function show(Waiter $waiter): JsonResponse
    {
        try {
            $waiterData = $waiter->load('user')->toArray();
            
            return response()->json([
                'success' => true,
                'data' => $waiterData,
            ]);
        } catch (\Exception $e) {
            Log::error('Error fetching waiter', [
                'waiter_id' => $waiter->id,
                'message' => $e->getMessage(),
            ]);
            
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Update waiter details
     */
    public function update(Request $request, Waiter $waiter): JsonResponse
    {
        try {
            $validated = $request->validate([
                'section' => 'sometimes|string|max:100',
                'shift' => 'sometimes|in:morning,afternoon,evening,night',
                'experience_level' => 'sometimes|in:junior,senior,head',
                'status' => 'sometimes|in:active,inactive,on_break',
                'phone' => 'sometimes|string|max:20',
                'employment_type' => 'sometimes|in:full_time,part_time,contract',
                'maximum_orders' => 'sometimes|integer|min:1|max:20',
                'current_orders' => 'sometimes|integer|min:0',
                'availability' => 'sometimes|in:available,busy,break,offline',
                'employee_number' => 'sometimes|string|max:50|unique:waiters,employee_number,' . $waiter->id,
            ]);

            Log::info('Updating waiter', [
                'waiter_id' => $waiter->id,
                'updates' => $validated,
            ]);

            $waiter->update($validated);

            return response()->json([
                'success' => true,
                'data' => $waiter->load('user'),
                'message' => 'Waiter updated successfully',
            ]);
        } catch (\Exception $e) {
            Log::error('Error updating waiter', [
                'waiter_id' => $waiter->id,
                'message' => $e->getMessage(),
            ]);
            
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Delete a waiter
     */
    public function destroy(Waiter $waiter): JsonResponse
    {
        try {
            Log::info('Deleting waiter', [
                'waiter_id' => $waiter->id,
            ]);

            $waiter->delete();

            return response()->json([
                'success' => true,
                'message' => 'Waiter deleted successfully',
            ]);
        } catch (\Exception $e) {
            Log::error('Error deleting waiter', [
                'waiter_id' => $waiter->id,
                'message' => $e->getMessage(),
            ]);
            
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Deactivate waiter
     */
    public function deactivate(Waiter $waiter): JsonResponse
    {
        try {
            $waiter->update(['status' => 'inactive', 'availability' => 'offline']);

            Log::info('Waiter deactivated', [
                'waiter_id' => $waiter->id,
            ]);

            return response()->json([
                'success' => true,
                'data' => $waiter->load('user'),
                'message' => 'Waiter deactivated successfully',
            ]);
        } catch (\Exception $e) {
            Log::error('Error deactivating waiter', [
                'waiter_id' => $waiter->id,
                'message' => $e->getMessage(),
            ]);
            
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Reactivate waiter
     */
    public function reactivate(Waiter $waiter): JsonResponse
    {
        try {
            $waiter->update(['status' => 'active', 'availability' => 'offline']);

            Log::info('Waiter reactivated', [
                'waiter_id' => $waiter->id,
            ]);

            return response()->json([
                'success' => true,
                'data' => $waiter->load('user'),
                'message' => 'Waiter reactivated successfully',
            ]);
        } catch (\Exception $e) {
            Log::error('Error reactivating waiter', [
                'waiter_id' => $waiter->id,
                'message' => $e->getMessage(),
            ]);
            
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Suspend waiter
     */
    public function suspend(Waiter $waiter): JsonResponse
    {
        try {
            $waiter->update(['status' => 'suspended', 'availability' => 'offline']);

            Log::info('Waiter suspended', [
                'waiter_id' => $waiter->id,
            ]);

            return response()->json([
                'success' => true,
                'data' => $waiter->load('user'),
                'message' => 'Waiter suspended successfully',
            ]);
        } catch (\Exception $e) {
            Log::error('Error suspending waiter', [
                'waiter_id' => $waiter->id,
                'message' => $e->getMessage(),
            ]);
            
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Change waiter availability
     */
    public function changeAvailability(Request $request, Waiter $waiter): JsonResponse
    {
        try {
            $validated = $request->validate([
                'availability' => 'required|in:available,busy,break,offline',
            ]);

            $waiter->update($validated);

            Log::info('Waiter availability changed', [
                'waiter_id' => $waiter->id,
                'availability' => $validated['availability'],
            ]);

            return response()->json([
                'success' => true,
                'data' => $waiter->load('user'),
                'message' => 'Availability updated successfully',
            ]);
        } catch (\Exception $e) {
            Log::error('Error changing availability', [
                'waiter_id' => $waiter->id,
                'message' => $e->getMessage(),
            ]);
            
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get waiter statistics
     */
    public function stats(Waiter $waiter): JsonResponse
    {
        try {
            $stats = [
                'id' => $waiter->id,
                'today_deliveries' => $waiter->getTodayDeliveries(),
                'pending_deliveries' => $waiter->getPendingDeliveries(),
                'avg_delivery_time' => $waiter->getAverageDeliveryTime(),
                'current_orders' => $waiter->current_orders,
                'maximum_orders' => $waiter->maximum_orders,
                'availability' => $waiter->availability,
                'status' => $waiter->status,
            ];

            return response()->json([
                'success' => true,
                'data' => $stats,
            ]);
        } catch (\Exception $e) {
            Log::error('Error fetching waiter stats', [
                'waiter_id' => $waiter->id,
                'message' => $e->getMessage(),
            ]);
            
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }
}

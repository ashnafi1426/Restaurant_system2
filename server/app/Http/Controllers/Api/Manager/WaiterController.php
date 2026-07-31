<?php

namespace App\Http\Controllers\Api\Manager;

use App\Http\Controllers\Controller;
use App\Services\Manager\ManagerDashboardService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Manager Waiter Controller
 * 
 * Handles waiter management and assignments
 */
class WaiterController extends Controller
{
    protected ManagerDashboardService $dashboardService;

    public function __construct(ManagerDashboardService $dashboardService)
    {
        $this->dashboardService = $dashboardService;
    }

    /**
     * Get all waiters
     */
    public function index(Request $request): JsonResponse
    {
        try {
            // Get all waiters with user relationship loaded
            $waiters = \App\Models\Waiter::with('user')
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
            
            return response()->json([
                'success' => true,
                'data' => $waiters,
            ]);
        } catch (\Exception $e) {
            \Log::error('Error fetching waiters', [
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

    public function store(Request $request): JsonResponse
    {
        try {
            // Log incoming request
            \Log::info('Waiter store request received', [
                'all_data' => $request->all(),
                'keys' => array_keys($request->all())
            ]);

            // Determine if creating new user or using existing
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
                    'email' => 'required|email|unique:users,email',
                    'phone' => 'required|string|max:20',
                    'password' => 'required|string|min:8',
                ]);
            } else {
                // Existing user - just need the ID
                $rules = array_merge($rules, [
                    'user_id' => 'required|uuid|exists:users,id',
                ]);
            }
            
            \Log::info('Waiter validation rules', [
                'is_new_user' => $isNewUser,
                'rules' => array_keys($rules)
            ]);
            
            $validated = $request->validate($rules);
            
            \Log::info('Waiter validation passed', [
                'validated_keys' => array_keys($validated)
            ]);

            // If creating new user
            if ($isNewUser) {
                try {
                    $hashedPassword = \Illuminate\Support\Facades\Hash::make($validated['password']);
                    
                    $user = \App\Models\User::create([
                        'first_name' => $validated['first_name'],
                        'last_name' => $validated['last_name'],
                        'email' => $validated['email'],
                        'phone' => $validated['phone'] ?? null,
                        'password_hash' => $hashedPassword,
                        'role' => 'waiter',
                        'is_active' => true,
                    ]);

                    $validated['user_id'] = $user->id;
                    
                    \Log::info('Waiter user created successfully', [
                        'user_id' => $user->id,
                        'email' => $user->email
                    ]);
                } catch (\Illuminate\Database\QueryException $dbError) {
                    \Log::error('Database error creating user', [
                        'message' => $dbError->getMessage(),
                        'sql' => $dbError->getSql() ?? 'N/A',
                    ]);
                    
                    return response()->json([
                        'success' => false,
                        'message' => 'Database error: ' . $dbError->getMessage(),
                    ], 500);
                } catch (\Exception $userError) {
                    \Log::error('Error creating user', [
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
                
                \Log::info('Creating waiter with data', $waiterData);

                $waiter = \App\Models\Waiter::create($waiterData);

                \Log::info('Waiter created successfully', [
                    'waiter_id' => $waiter->id,
                    'user_id' => $validated['user_id'],
                    'waiter_data' => $waiter->toArray(),
                    'user_data' => $waiter->user ? $waiter->user->toArray() : null
                ]);

                $responseData = $waiter->load('user');
                
                \Log::info('Response being sent to client', [
                    'data' => $responseData
                ]);

                return response()->json([
                    'success' => true,
                    'data' => $responseData,
                    'message' => 'Waiter created successfully',
                ], 201);
            } catch (\Illuminate\Database\QueryException $dbError) {
                \Log::error('Database error creating waiter', [
                    'message' => $dbError->getMessage(),
                    'sql' => $dbError->getSql() ?? 'N/A',
                ]);
                
                return response()->json([
                    'success' => false,
                    'message' => 'Database error: ' . $dbError->getMessage(),
                ], 500);
            } catch (\Exception $waiterError) {
                \Log::error('Error creating waiter', [
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
            \Log::warning('Waiter validation error', [
                'errors' => $validationError->errors(),
                'request_data' => $request->all()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validationError->errors(),
            ], 422);
        } catch (\Exception $e) {
            \Log::error('Unexpected error in waiter store', [
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
     * Update waiter status
     */
    public function updateStatus(Request $request, \App\Models\Waiter $waiter): JsonResponse
    {
        try {
            $validated = $request->validate([
                'status' => 'required|in:active,inactive,on_break',
            ]);

            $waiter->update($validated);

            return response()->json([
                'success' => true,
                'data' => $waiter->load('user'),
                'message' => 'Waiter status updated successfully',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get single waiter with full details
     */
    public function show(\App\Models\Waiter $waiter): JsonResponse
    {
        try {
            $waiterData = $waiter->withStats()->first();
            
            return response()->json([
                'success' => true,
                'data' => $waiterData,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Update waiter details
     */
    public function update(Request $request, \App\Models\Waiter $waiter): JsonResponse
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

            $waiter->update($validated);

            return response()->json([
                'success' => true,
                'data' => $waiter->load('user'),
                'message' => 'Waiter updated successfully',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Delete a waiter
     */
    public function destroy(\App\Models\Waiter $waiter): JsonResponse
    {
        try {
            $waiter->delete();

            return response()->json([
                'success' => true,
                'message' => 'Waiter deleted successfully',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get waiter assignments
     */
    public function getAssignments(\App\Models\Waiter $waiter): JsonResponse
    {
        try {
            $assignments = $waiter->assignments()
                ->with(['order'])
                ->latest('assigned_at')
                ->limit(50)
                ->get();

            return response()->json([
                'success' => true,
                'data' => $assignments,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get waiter performance metrics
     */
    public function getPerformance(\App\Models\Waiter $waiter): JsonResponse
    {
        try {
            $performance = $waiter->performanceMetrics()
                ->latest('metric_date')
                ->limit(30)
                ->get()
                ->map(function ($metric) {
                    return [
                        'date' => $metric->metric_date,
                        'assigned' => $metric->deliveries_assigned,
                        'accepted' => $metric->deliveries_accepted,
                        'rejected' => $metric->deliveries_rejected,
                        'acceptance_rate' => $metric->acceptance_rate,
                        'completed' => $metric->deliveries_completed,
                        'failed' => $metric->deliveries_failed,
                        'completion_rate' => $metric->completion_rate,
                        'avg_time_minutes' => $metric->avg_delivery_time_minutes,
                        'on_time' => $metric->on_time_deliveries,
                        'on_time_rate' => $metric->on_time_rate,
                        'guest_rating' => $metric->guest_rating_avg,
                        'rating_count' => $metric->total_ratings,
                        'performance_score' => $metric->getPerformanceRating(),
                    ];
                });

            return response()->json([
                'success' => true,
                'data' => $performance,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get all available users for waiter assignment
     */
    public function getAvailableUsers(): JsonResponse
    {
        try {
            $users = \App\Models\User::where('role', '!=', 'waiter')
                ->where('is_active', true)
                ->select('id', 'first_name', 'last_name', 'email', 'phone', 'role')
                ->get()
                ->map(function ($user) {
                    return [
                        'id' => $user->id,
                        'name' => $user->first_name . ' ' . $user->last_name,
                        'email' => $user->email,
                        'phone' => $user->phone,
                        'role' => $user->role,
                    ];
                });

            return response()->json([
                'success' => true,
                'data' => $users,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }
}

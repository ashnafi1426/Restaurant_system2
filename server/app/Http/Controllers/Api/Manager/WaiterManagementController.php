<?php

namespace App\Http\Controllers\Api\Manager;

use App\Http\Controllers\Controller;
use App\Models\Waiter;
use App\Models\User;
use App\Services\ActivationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class WaiterManagementController extends Controller
{
    protected function getHotelId(): ?string
    {
        $hotelId = request()->header('X-Hotel-ID')
            ?: app(\App\Services\TenantContext::class)->getHotelId()
            ?: (auth()->check() ? auth()->user()->hotel_id : null);

        if (!$hotelId && auth()->check()) {
            $hotelId = auth()->user()->hotelMemberships()->where('is_active', true)->value('hotel_id');
        }

        if ($hotelId) {
            app(\App\Services\TenantContext::class)->setHotelId($hotelId);
        }

        return $hotelId;
    }

    public function index(Request $request): JsonResponse
    {
        try {
            $hotelId = $this->getHotelId();

            $waitersQuery = Waiter::with([
                'user',
                'floorAssignments' => function ($q) use ($hotelId) {
                    $q->where('assignment_date', '>=', today())
                      ->where('status', 'active')
                      ->when($hotelId, fn($sub) => $sub->where('hotel_id', $hotelId))
                      ->with(['floor', 'shift'])
                      ->orderBy('priority');
                }
            ]);

            if ($hotelId) {
                $waitersQuery->where('hotel_id', $hotelId);
            }

            $waiters = $waitersQuery
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
                        'floor_assignments' => $waiter->floorAssignments->map(function ($assignment) {
                            return [
                                'id' => $assignment->id,
                                'floor_id' => $assignment->floor_id,
                                'floor_name' => $assignment->floor->name ?? 'Unknown',
                                'floor_number' => $assignment->floor->floor_number ?? 0,
                                'shift_id' => $assignment->shift_id,
                                'shift_name' => $assignment->shift->name ?? 'Unknown',
                                'shift_time' => ($assignment->shift ? "{$assignment->shift->start_time} - {$assignment->shift->end_time}" : 'N/A'),
                                'priority' => $assignment->priority,
                                'assignment_date' => $assignment->assignment_date,
                                'status' => $assignment->status,
                            ];
                        })->toArray(),
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
                'employee_number' => 'sometimes|string|max:50',
                'floor_assignments' => 'sometimes|array',
                'floor_assignments.*.floor_id' => 'required_with:floor_assignments|exists:hotel_floors,id',
                'floor_assignments.*.shift_id' => 'required_with:floor_assignments|exists:hotel_shifts,id',
                'floor_assignments.*.priority' => 'required_with:floor_assignments|in:primary,secondary,backup',
                'floor_assignments.*.assignment_date' => 'sometimes|date',
            ];
            
            if ($isNewUser) {
                $rules = array_merge($rules, [
                    'first_name' => 'required|string|max:255',
                    'last_name' => 'required|string|max:255',
                    'email' => 'required|email',
                    'phone' => 'required|string|max:20',
                ]);
            } else {
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

            if ($isNewUser) {
                try {
                    $existingUser = User::where('email', $validated['email'])->first();
                    
                    if (!empty($validated['employee_number'])) {
                        $existingWaiterWithNumber = Waiter::where('employee_number', $validated['employee_number'])->first();
                        if ($existingWaiterWithNumber) {
                            return response()->json([
                                'success' => false,
                                'message' => 'Validation failed',
                                'errors' => [
                                    'employee_number' => ['The employee number has already been taken.']
                                ],
                            ], 422);
                        }
                    }

                    if ($existingUser) {
                        if ($existingUser->activation_status === 'pending' || $existingUser->activation_status === 'expired') {
                            $existingUser->update([
                                'first_name' => $validated['first_name'],
                                'last_name' => $validated['last_name'],
                                'phone' => $validated['phone'] ?? $existingUser->phone,
                                'role' => 'waiter',
                            ]);

                            $activationService = new ActivationService();
                            $activationService->generateActivationToken($existingUser);

                            Log::info('Reusing existing pending user for waiter, resent activation', [
                                'user_id' => $existingUser->id,
                                'email' => $existingUser->email,
                            ]);
                        } else {
                            $existingUser->update([
                                'first_name' => $validated['first_name'],
                                'last_name' => $validated['last_name'],
                                'phone' => $validated['phone'] ?? $existingUser->phone,
                                'role' => 'waiter',
                                'is_active' => true,
                            ]);

                            Log::info('Reusing existing activated user for waiter creation', [
                                'user_id' => $existingUser->id,
                                'email' => $existingUser->email,
                            ]);
                        }
                        
                        $user = $existingUser;
                    } else {
                        $temporaryPassword = $this->generateSecurePassword();
                        
                        $user = User::create([
                            'first_name' => $validated['first_name'],
                            'last_name' => $validated['last_name'],
                            'email' => $validated['email'],
                            'phone' => $validated['phone'] ?? null,
                            'password_hash' => \Illuminate\Support\Facades\Hash::make($temporaryPassword),
                            'role' => 'waiter',
                            'is_active' => true,
                            'activation_status' => 'activated',
                            'email_verified_at' => now(),
                        ]);

                        try {
                            \Illuminate\Support\Facades\Mail::to($user->email)->send(new \App\Mail\NewUserCreated($user, $temporaryPassword));
                            
                            Log::info('Waiter user created with auto-generated password', [
                                'user_id' => $user->id,
                                'email' => $user->email,
                            ]);
                        } catch (\Exception $mailException) {
                            Log::error('Failed to send waiter credentials email', [
                                'user_id' => $user->id,
                                'email' => $user->email,
                                'error' => $mailException->getMessage()
                            ]);
                        }
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
                $hotelId = $this->getHotelId();

                $existingWaiter = Waiter::where('user_id', $validated['user_id'])
                    ->when($hotelId, fn($q) => $q->where('hotel_id', $hotelId))
                    ->first();

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

                $user = User::find($validated['user_id']);
                
                $waiterData = [
                    'hotel_id' => $hotelId,
                    'user_id' => $validated['user_id'],
                    'phone' => $validated['phone'] ?? null,
                    'section' => $validated['section'],
                    'shift' => $validated['shift'],
                    'experience_level' => $validated['experience_level'],
                    'employment_type' => $validated['employment_type'] ?? 'full_time',
                    'hire_date' => $request->input('hire_date') ? $validated['hire_date'] : now()->toDateString(),
                    'status' => $validated['status'] ?? 'active',
                    'availability' => 'offline',
                    'current_orders' => 0,
                    'maximum_orders' => $validated['maximum_orders'],
                    'employee_number' => $validated['employee_number'] ?? null,
                ];
                
                Log::info('Creating waiter with data', $waiterData);

                $waiter = Waiter::create($waiterData);

                if ($hotelId && $user) {
                    \App\Models\HotelUser::firstOrCreate(
                        ['hotel_id' => $hotelId, 'user_id' => $user->id],
                        ['id' => (string) \Illuminate\Support\Str::uuid(), 'role' => 'waiter', 'is_active' => true]
                    );
                }

                if (!empty($validated['floor_assignments'])) {
                    $this->syncFloorAssignments($waiter, $validated['floor_assignments']);
                    
                    Log::info('Floor assignments created', [
                        'waiter_id' => $waiter->id,
                        'assignments_count' => count($validated['floor_assignments']),
                    ]);
                }

                Log::info('Waiter created successfully', [
                    'waiter_id' => $waiter->id,
                    'user_id' => $validated['user_id'],
                    'waiter_data' => $waiter->toArray(),
                    'user_data' => $waiter->user ? $waiter->user->toArray() : null
                ]);

                $responseData = $waiter->load('user', 'floorAssignments.floor', 'floorAssignments.shift');
                
                Log::info('Response being sent to client', [
                    'data' => $responseData
                ]);

                return response()->json([
                    'success' => true,
                    'data' => $responseData,
                    'message' => $isNewUser 
                        ? 'Waiter created successfully. Login credentials sent to ' . $validated['email']
                        : 'Waiter created successfully',
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
                'floor_assignments' => 'sometimes|array',
                'floor_assignments.*.floor_id' => 'required_with:floor_assignments|exists:hotel_floors,id',
                'floor_assignments.*.shift_id' => 'required_with:floor_assignments|exists:hotel_shifts,id',
                'floor_assignments.*.priority' => 'required_with:floor_assignments|in:primary,secondary,backup',
                'floor_assignments.*.assignment_date' => 'sometimes|date',
            ]);

            Log::info('Updating waiter', [
                'waiter_id' => $waiter->id,
                'updates' => $validated,
            ]);

            $waiter->update(collect($validated)->except('floor_assignments')->toArray());

            if (isset($validated['floor_assignments'])) {
                $this->syncFloorAssignments($waiter, $validated['floor_assignments']);
                
                Log::info('Floor assignments updated', [
                    'waiter_id' => $waiter->id,
                    'assignments_count' => count($validated['floor_assignments']),
                ]);
            }

            return response()->json([
                'success' => true,
                'data' => $waiter->load('user', 'floorAssignments.floor', 'floorAssignments.shift'),
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

    private function syncFloorAssignments(Waiter $waiter, array $assignments): void
    {
        $dates = collect($assignments)->pluck('assignment_date')
            ->map(fn($date) => $date ?? today()->toDateString())
            ->unique()
            ->toArray();
        
        \App\Models\WaiterFloorAssignment::where('waiter_id', $waiter->id)
            ->whereIn('assignment_date', $dates)
            ->delete();
        
        foreach ($assignments as $assignment) {
            \App\Models\WaiterFloorAssignment::create([
                'id' => \Illuminate\Support\Str::uuid(),
                'waiter_id' => $waiter->id,
                'floor_id' => $assignment['floor_id'],
                'shift_id' => $assignment['shift_id'],
                'priority' => $assignment['priority'],
                'assignment_date' => $assignment['assignment_date'] ?? today()->toDateString(),
                'status' => 'active',
                'assigned_by' => auth()->id(),
            ]);
        }
    }

    private function generateSecurePassword(int $length = 12): string
    {
        $uppercase = 'ABCDEFGHJKLMNPQRSTUVWXYZ';
        $lowercase = 'abcdefghjkmnpqrstuvwxyz';
        $numbers = '23456789';
        $symbols = '!@#$%&*';
        
        $password = 
            $uppercase[random_int(0, strlen($uppercase) - 1)] .
            $lowercase[random_int(0, strlen($lowercase) - 1)] .
            $numbers[random_int(0, strlen($numbers) - 1)] .
            $symbols[random_int(0, strlen($symbols) - 1)];
        
        $allChars = $uppercase . $lowercase . $numbers . $symbols;
        for ($i = 4; $i < $length; $i++) {
            $password .= $allChars[random_int(0, strlen($allChars) - 1)];
        }
        
        return str_shuffle($password);
    }
}

<?php

use Illuminate\Support\Facades\Route;
use App\Models\DeliveryTask;
use App\Models\Waiter;
use App\Models\User;

Route::middleware('auth:sanctum')->prefix('debug')->group(function () {
    /**
     * Debug endpoint to test waiter ID resolution
     */
    Route::get('/waiter-resolution', function () {
        $user = auth()->user();
        
        \Log::info('=== DEBUG WAITER RESOLUTION ===');
        \Log::info('User:', ['id' => $user?->id, 'email' => $user?->email]);
        
        if (!$user) {
            return response()->json(['error' => 'No user'], 401);
        }
        
        // Try to get waiter_id
        $waiterId = null;
        
        // Method 1: Check loaded relation
        if ($user->relationLoaded('waiter')) {
            $waiterId = $user->waiter?->id;
            \Log::info('Method 1 (loaded relation):', ['waiter_id' => $waiterId]);
        }
        
        // Method 2: Load missing
        if (!$waiterId) {
            $user->loadMissing('waiter');
            $waiterId = $user->waiter?->id;
            \Log::info('Method 2 (loadMissing):', ['waiter_id' => $waiterId]);
        }
        
        // Method 3: Direct query by user_id
        if (!$waiterId) {
            $waiter = Waiter::where('user_id', $user->id)->first();
            $waiterId = $waiter?->id;
            \Log::info('Method 3 (direct query by user_id):', ['waiter_id' => $waiterId]);
        }
        
        return response()->json([
            'user_id' => $user->id,
            'user_email' => $user->email,
            'waiter_id' => $waiterId,
            'on_delivery_count' => DeliveryTask::where('waiter_id', $waiterId ?? -1)
                ->where('status', 'on_delivery')
                ->count(),
        ]);
    });
    
    /**
     * Debug endpoint to check database directly
     */
    Route::get('/db-status', function () {
        $user = auth()->user();
        
        if (!$user) {
            return response()->json(['error' => 'No user'], 401);
        }
        
        // Find waiter for this user
        $waiter = Waiter::where('user_id', $user->id)->first();
        
        if (!$waiter) {
            return response()->json([
                'error' => 'No waiter profile for user',
                'user_id' => $user->id,
                'all_waiters' => Waiter::count(),
            ]);
        }
        
        $onDeliveryCount = DeliveryTask::where('waiter_id', $waiter->id)
            ->where('status', 'on_delivery')
            ->count();
        
        $onDeliveryTasks = DeliveryTask::where('waiter_id', $waiter->id)
            ->where('status', 'on_delivery')
            ->select('id', 'order_id', 'waiter_id', 'status', 'on_delivery_at')
            ->get();
        
        return response()->json([
            'user_id' => $user->id,
            'user_email' => $user->email,
            'waiter_id' => $waiter->id,
            'waiter_section' => $waiter->section,
            'on_delivery_count' => $onDeliveryCount,
            'on_delivery_tasks' => $onDeliveryTasks,
            'all_tasks_count' => DeliveryTask::where('waiter_id', $waiter->id)->count(),
            'tasks_by_status' => DeliveryTask::where('waiter_id', $waiter->id)
                ->selectRaw('status, COUNT(*) as count')
                ->groupBy('status')
                ->get(),
        ]);
    });
    
    /**
     * Debug endpoint to manually call getOnDelivery
     */
    Route::get('/get-on-delivery', function () {
        $user = auth()->user();
        
        if (!$user) {
            return response()->json(['error' => 'No user'], 401);
        }
        
        // Find waiter
        $waiter = Waiter::where('user_id', $user->id)->first();
        
        if (!$waiter) {
            return response()->json(['error' => 'No waiter profile'], 404);
        }
        
        $waiterId = $waiter->id;
        
        \Log::info('🔵 [DEBUG] Calling getOnDelivery manually', ['waiter_id' => $waiterId]);
        
        // Call the service directly
        $dashboardService = app(\App\Services\Waiter\WaiterDashboardService::class);
        $result = $dashboardService->getOnDelivery($waiterId);
        
        \Log::info('✅ [DEBUG] Result:', ['count' => count($result)]);
        
        return response()->json([
            'waiter_id' => $waiterId,
            'count' => count($result),
            'data' => $result,
        ]);
    });
});

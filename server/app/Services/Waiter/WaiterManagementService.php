<?php

namespace App\Services\Waiter;

use App\Models\User;
use App\Models\Waiter;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * WaiterManagementService
 * 
 * Handles waiter registration, updates, and lifecycle management
 */
class WaiterManagementService
{
    public function registerWaiter(array $waiterData): Waiter
    {
        try {
            DB::beginTransaction();
            $user = User::create([
                'name' => "{$waiterData['first_name']} {$waiterData['last_name']}",
                'email' => $waiterData['email'],
                'password' => Hash::make($waiterData['password']),
                'phone' => $waiterData['phone'] ?? null,
                'role' => 'waiter',
            ]);

            // Create waiter profile
            $waiter = Waiter::create([
                'user_id' => $user->id,
                'employee_number' => $waiterData['employee_number'] ?? $this->generateEmployeeNumber(),
                'phone' => $waiterData['phone'] ?? null,
                'employment_type' => $waiterData['employment_type'] ?? 'full_time',
                'hire_date' => $waiterData['hire_date'] ?? now()->toDateString(),
                'status' => 'active',
                'availability' => 'offline',
                'current_orders' => 0,
                'maximum_orders' => $waiterData['maximum_orders'] ?? 5,
                'profile_photo' => $waiterData['profile_photo'] ?? null,
            ]);

            DB::commit();

            Log::info("Waiter registered successfully", [
                'waiter_id' => $waiter->id,
                'user_id' => $user->id,
                'name' => $user->name,
                'employee_number' => $waiter->employee_number,
            ]);

            return $waiter->load('user');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Failed to register waiter: {$e->getMessage()}");
            throw $e;
        }
    }
    public function updateWaiter(string $waiterId, array $data): Waiter
    {
        try {
            DB::beginTransaction();

            $waiter = Waiter::findOrFail($waiterId);

            // Update user info if provided
            if (isset($data['name']) || isset($data['email'])) {
                $userData = [];
                if (isset($data['name'])) {
                    $userData['name'] = $data['name'];
                }
                if (isset($data['email'])) {
                    $userData['email'] = $data['email'];
                }
                if (isset($data['phone'])) {
                    $userData['phone'] = $data['phone'];
                }
                
                $waiter->user->update($userData);
            }

            // Update waiter profile
            $waiter->update(array_filter([
                'phone' => $data['phone'] ?? null,
                'employment_type' => $data['employment_type'] ?? null,
                'maximum_orders' => $data['maximum_orders'] ?? null,
                'profile_photo' => $data['profile_photo'] ?? null,
            ], function($value) { return $value !== null; }));

            DB::commit();

            Log::info("Waiter updated", [
                'waiter_id' => $waiterId,
                'updated_fields' => array_keys($data),
            ]);

            return $waiter->load('user');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Failed to update waiter: {$e->getMessage()}");
            throw $e;
        }
    }
    public function deactivateWaiter(string $waiterId): bool
    {
        try {
            DB::beginTransaction();

            $waiter = Waiter::findOrFail($waiterId);
            $waiter->deactivate();

            // Cancel all pending assignments
            $waiter->floorAssignments()
                ->where('status', '!=', 'completed')
                ->where('status', '!=', 'cancelled')
                ->update(['status' => 'cancelled']);

            // Cancel pending deliveries
            $waiter->deliveryTasks()
                ->whereIn('status', ['assigned', 'accepted', 'picked_up'])
                ->update([
                    'status' => 'cancelled',
                    'cancelled_at' => now(),
                    'cancellation_reason' => 'Waiter deactivated',
                ]);

            DB::commit();

            Log::info("Waiter deactivated", ['waiter_id' => $waiterId]);

            return true;
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Failed to deactivate waiter: {$e->getMessage()}");
            return false;
        }
    }
    public function reactivateWaiter(string $waiterId): bool
    {
        try {
            $waiter = Waiter::findOrFail($waiterId);
            $waiter->reactivate();

            Log::info("Waiter reactivated", ['waiter_id' => $waiterId]);

            return true;
        } catch (\Exception $e) {
            Log::error("Failed to reactivate waiter: {$e->getMessage()}");
            return false;
        }
    }
    public function suspendWaiter(string $waiterId, string $reason = null): bool
    {
        try {
            DB::beginTransaction();

            $waiter = Waiter::findOrFail($waiterId);
            $waiter->suspend();

            // Create audit log
            Log::info("Waiter suspended", [
                'waiter_id' => $waiterId,
                'reason' => $reason,
            ]);

            DB::commit();

            return true;
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Failed to suspend waiter: {$e->getMessage()}");
            return false;
        }
    }
    public function deleteWaiter(string $waiterId): bool
    {
        return $this->deactivateWaiter($waiterId);
    }
    public function changeAvailability(string $waiterId, string $availability): bool
    {
        try {
            $waiter = Waiter::findOrFail($waiterId);

            match ($availability) {
                'available' => $waiter->setAsAvailable(),
                'busy' => $waiter->setAsBusy(),
                'break' => $waiter->setOnBreak(),
                'offline' => $waiter->setOffline(),
                default => throw new \Exception("Invalid availability status: {$availability}"),
            };

            Log::info("Waiter availability changed", [
                'waiter_id' => $waiterId,
                'availability' => $availability,
            ]);

            return true;
        } catch (\Exception $e) {
            Log::error("Failed to change availability: {$e->getMessage()}");
            return false;
        }
    }
    private function generateEmployeeNumber(): string
    {
        $prefix = 'WTR';
        $count = Waiter::count() + 1;
        return "{$prefix}" . str_pad($count, 4, '0', STR_PAD_LEFT);
    }
    public function getWaiterWithDetails(string $waiterId): Waiter
    {
        return Waiter::with([
            'user',
            'floorAssignments.floor',
            'floorAssignments.shift',
            'deliveryTasks' => function ($query) {
                $query->whereDate('assigned_at', today())->orderBy('assigned_at', 'desc');
            },
        ])->findOrFail($waiterId);
    }
    public function getAllActiveWaiters()
    {
        return Waiter::active()
            ->with('user')
            ->orderBy('created_at', 'desc')
            ->get();
    }
    public function getWaiterStats(string $waiterId): array
    {
        $waiter = Waiter::findOrFail($waiterId);

        return [
            'status' => $waiter->status,
            'availability' => $waiter->availability,
            'current_orders' => $waiter->current_orders,
            'maximum_orders' => $waiter->maximum_orders,
            'total_deliveries_today' => $waiter->getTodayDeliveries(),
            'pending_deliveries' => $waiter->getPendingDeliveries(),
            'average_delivery_time' => round($waiter->getAverageDeliveryTime(), 2),
            'employment_type' => $waiter->employment_type,
            'hire_date' => $waiter->hire_date,
        ];
    }
}

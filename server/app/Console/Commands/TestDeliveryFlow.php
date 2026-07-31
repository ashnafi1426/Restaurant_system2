<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\DeliveryTask;
use App\Models\Waiter;
use App\Models\User;
use App\Services\Waiter\WaiterContextResolver;
use App\Services\Waiter\WaiterDashboardService;

class TestDeliveryFlow extends Command
{
    protected $signature = 'test:delivery-flow';
    protected $description = 'Test the complete delivery workflow';

    public function handle()
    {
        $this->line('');
        $this->line('╔════════════════════════════════════════════════════════════════════════╗');
        $this->line('║                    DELIVERY WORKFLOW TEST                              ║');
        $this->line('╚════════════════════════════════════════════════════════════════════════╝');

        // Get the waiter user
        $user = User::whereHas('waiter')->first();
        
        if (!$user) {
            $this->error('❌ No user with waiter profile found');
            return;
        }

        $this->line('');
        $this->line('👤 USER INFORMATION:');
        $this->line('═══════════════════════════════════════');
        $this->line("User ID: {$user->id}");
        $this->line("Email: {$user->email}");
        $this->line("Role: {$user->role}");

        // Test 1: Waiter Context Resolver
        $this->line('');
        $this->line('🔍 TEST 1: WAITER CONTEXT RESOLVER');
        $this->line('═══════════════════════════════════════');
        
        $resolver = app(WaiterContextResolver::class);
        $waiterId = $resolver->resolveWaiterId($user);
        
        if (!$waiterId) {
            $this->error('❌ Could not resolve waiter ID');
            return;
        }
        
        $this->info("✅ Waiter ID resolved: {$waiterId}");
        
        $waiter = Waiter::find($waiterId);
        if ($waiter) {
            $this->line("  - Waiter section: {$waiter->section}");
            $this->line("  - Total tasks: " . $waiter->deliveryTasks()->count());
        }

        // Test 2: Direct query for on_delivery tasks
        $this->line('');
        $this->line('🔍 TEST 2: DIRECT DATABASE QUERY');
        $this->line('═══════════════════════════════════════');
        
        $onDeliveryTasks = DeliveryTask::where('waiter_id', $waiterId)
            ->where('status', 'on_delivery')
            ->get();
        
        $this->info("✅ Found " . $onDeliveryTasks->count() . " on_delivery tasks");
        
        foreach ($onDeliveryTasks as $task) {
            $this->line("  - Task #{$task->id}: Order {$task->order_id}");
        }

        // Test 3: Call the Dashboard Service
        $this->line('');
        $this->line('🔍 TEST 3: DASHBOARD SERVICE');
        $this->line('═══════════════════════════════════════');
        
        $dashboardService = app(WaiterDashboardService::class);
        $serviceResult = $dashboardService->getOnDelivery($waiterId);
        
        $this->info("✅ Service returned " . count($serviceResult) . " tasks");
        
        if (empty($serviceResult)) {
            $this->error('❌ Service returned empty array even though database has tasks!');
            $this->line('This indicates a bug in getOnDelivery method');
        } else {
            foreach ($serviceResult as $task) {
                $this->line("  - {$task['id']}: Order {$task['order_id']}, Room {$task['room_number']}");
            }
        }

        // Test 4: Simulate API Response
        $this->line('');
        $this->line('🔍 TEST 4: API RESPONSE FORMAT');
        $this->line('═══════════════════════════════════════');
        
        $apiResponse = [
            'success' => true,
            'data' => $serviceResult,
        ];
        
        $this->line("Response status: success = {$apiResponse['success']}");
        $this->line("Response data count: " . count($apiResponse['data']));
        
        if (empty($apiResponse['data'])) {
            $this->error('❌ API would return empty array');
        } else {
            $this->info("✅ API would return " . count($apiResponse['data']) . " tasks");
        }

        // Test 5: Check for waiter_id type mismatches
        $this->line('');
        $this->line('🔍 TEST 5: DATA TYPE VERIFICATION');
        $this->line('═══════════════════════════════════════');
        
        $this->line("Resolved waiter_id type: " . gettype($waiterId) . " = {$waiterId}");
        
        $allTasks = DeliveryTask::where('waiter_id', $waiterId)->get();
        $this->line("Tasks with waiter_id {$waiterId}: " . $allTasks->count());
        
        if ($allTasks->isNotEmpty()) {
            $firstTask = $allTasks->first();
            $this->line("First task waiter_id type: " . gettype($firstTask->waiter_id) . " = {$firstTask->waiter_id}");
        }

        $this->line('');
        $this->line('═══════════════════════════════════════');
        $this->info('✅ Test complete.');
        $this->line('');
    }
}

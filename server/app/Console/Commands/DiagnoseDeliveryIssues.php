<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\DeliveryTask;
use App\Models\Waiter;
use App\Models\User;

class DiagnoseDeliveryIssues extends Command
{
    protected $signature = 'diagnose:delivery-issues';
    protected $description = 'Diagnose delivery workflow issues';

    public function handle()
    {
        $this->line('');
        $this->line('╔════════════════════════════════════════════════════════════════════════╗');
        $this->line('║                    DELIVERY WORKFLOW DIAGNOSTICS                       ║');
        $this->line('╚════════════════════════════════════════════════════════════════════════╝');

        // 1. Check if there are any waiters
        $this->line('');
        $this->line('📊 WAITER PROFILES:');
        $this->line('═══════════════════════════════════════');
        
        $waiters = Waiter::with('user')->get();
        $this->line("Total waiters: " . $waiters->count());
        
        if ($waiters->count() === 0) {
            $this->error('❌ NO WAITERS FOUND IN SYSTEM!');
        } else {
            foreach ($waiters as $waiter) {
                $userInfo = $waiter->user ? "User #{$waiter->user->id} ({$waiter->user->email})" : 'NO USER';
                $this->line("  ✓ Waiter #{$waiter->id} - {$userInfo} - Section: {$waiter->section}");
            }
        }

        // 2. Check delivery tasks
        $this->line('');
        $this->line('📦 DELIVERY TASKS BY STATUS:');
        $this->line('═══════════════════════════════════════');
        
        $tasksByStatus = DeliveryTask::select('status')
            ->selectRaw('COUNT(*) as count')
            ->groupBy('status')
            ->get();
            
        $totalTasks = DeliveryTask::count();
        $this->line("Total tasks: {$totalTasks}");
        
        foreach ($tasksByStatus as $item) {
            $this->line("  • {$item->status}: {$item->count}");
        }

        // 3. Check on_delivery tasks specifically
        $this->line('');
        $this->line('🚚 ON_DELIVERY TASKS ANALYSIS:');
        $this->line('═══════════════════════════════════════');
        
        $onDeliveryTasks = DeliveryTask::where('status', 'on_delivery')->get();
        $this->line("Total on_delivery tasks: " . $onDeliveryTasks->count());
        
        if ($onDeliveryTasks->count() > 0) {
            foreach ($onDeliveryTasks as $task) {
                $this->line("  • Task #{$task->id}:");
                $this->line("      - Waiter ID: {$task->waiter_id}");
                $this->line("      - Order ID: {$task->order_id}");
                $this->line("      - Status: {$task->status}");
                $this->line("      - Started at: {$task->on_delivery_at}");
                
                // Check if waiter exists
                $waiter = Waiter::find($task->waiter_id);
                if ($waiter) {
                    $this->line("      - Waiter exists: YES (#{$waiter->id}, Section: {$waiter->section})");
                } else {
                    $this->error("      - Waiter exists: NO ❌");
                }
            }
        } else {
            $this->warn('ℹ️ No on_delivery tasks found');
        }

        // 4. Check waiter-specific tasks
        $this->line('');
        $this->line('👤 WAITER-SPECIFIC TASK BREAKDOWN:');
        $this->line('═══════════════════════════════════════');
        
        foreach ($waiters as $waiter) {
            $tasks = DeliveryTask::where('waiter_id', $waiter->id)->get();
            $statusBreakdown = $tasks->groupBy('status')->map->count();
            
            $this->line("Waiter #{$waiter->id} ({$waiter->user?->email}):");
            $this->line("  Total tasks: " . $tasks->count());
            
            foreach ($statusBreakdown as $status => $count) {
                $this->line("    • {$status}: {$count}");
            }
        }

        // 5. Check if there are any orphaned tasks
        $this->line('');
        $this->line('⚠️  ORPHANED TASKS (waiter_id not in waiters table):');
        $this->line('═══════════════════════════════════════');
        
        $orphanedTasks = DeliveryTask::whereNotIn('waiter_id', Waiter::pluck('id'))->get();
        
        if ($orphanedTasks->count() > 0) {
            $this->error("Found {$orphanedTasks->count()} orphaned tasks:");
            foreach ($orphanedTasks as $task) {
                $this->line("  ✗ Task #{$task->id} - waiter_id: {$task->waiter_id} (does not exist)");
            }
        } else {
            $this->info('✓ No orphaned tasks found');
        }

        // 6. Check User/Waiter relationships
        $this->line('');
        $this->line('🔗 USER-WAITER RELATIONSHIPS:');
        $this->line('═══════════════════════════════════════');
        
        $waiterUsers = User::whereHas('waiter')->with('waiter')->get();
        $this->line("Users with waiter profile: " . $waiterUsers->count());
        
        foreach ($waiterUsers as $user) {
            $waiter = $user->waiter;
            $this->line("  • User #{$user->id} ({$user->email})");
            $this->line("      - Waiter ID: {$waiter->id}");
            $this->line("      - Waiter Section: {$waiter->section}");
            $this->line("      - Waiter Tasks Count: " . $waiter->deliveryTasks()->count());
        }

        $this->line('');
        $this->line('═══════════════════════════════════════');
        $this->info('✅ Diagnosis complete. Check logs for detailed information.');
        $this->line('');
    }
}

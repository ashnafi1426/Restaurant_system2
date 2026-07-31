<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class TestDashboardResponse extends Command
{
    protected $signature = 'test:dashboard-response';
    protected $description = 'Test dashboard response structure';

    public function handle()
    {
        $this->info('🟠 Testing Dashboard Response...');
        
        $waiter = \App\Models\Waiter::first();
        if (!$waiter) {
            $this->error('No waiter found');
            return 1;
        }

        $this->line("Waiter: {$waiter->id} - {$waiter->user->name}");
        
        $service = app(\App\Services\Waiter\WaiterDashboardService::class);
        $result = $service->getDashboardStats($waiter->id);
        
        $this->info('📊 Full Response:');
        $this->line(json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
        
        $this->info('📈 Today Stats Only:');
        $this->line(json_encode($result['today_stats'], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
        
        return 0;
    }
}

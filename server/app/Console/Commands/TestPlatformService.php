<?php

namespace App\Console\Commands;

use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use App\Services\Platform\PlatformHotelService;

#[Signature('test:platform-service')]
#[Description('Test the Platform Hotel Service')]
class TestPlatformService extends Command
{
    /**
     * Execute the console command.
     */
    public function handle()
    {
        $service = app(PlatformHotelService::class);

        try {
            $this->info('Testing getHotels method...');
            $hotels = $service->getHotels([], 5);
            $this->info('Hotels count: ' . $hotels->count());
            $this->info('First hotel: ' . ($hotels->first()?->name ?? 'No hotels'));

            foreach ($hotels as $hotel) {
                $admin = $hotel->admin_name ?? 'N/A'; $this->info("Hotel: {$hotel->name} - Admin: {$admin}");
            }
        } catch (\Exception $e) {
            $this->error('Error: ' . $e->getMessage());
            $this->error('File: ' . $e->getFile() . ':' . $e->getLine());
            $this->error('Trace: ' . $e->getTraceAsString());
        }
    }
}


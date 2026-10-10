<?php

namespace App\Console\Commands;

use App\Models\Order;
use App\Models\User;
use Illuminate\Console\Command;

class AssignChefsToExistingOrders extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'orders:assign-chefs';

    /**
     * The description of the console command.
     *
     * @var string
     */
    protected $description = 'Assign unassigned orders to available chefs based on workload';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $this->info(' Starting to assign chefs to unassigned orders...');

        $unassignedOrders = Order::whereNull('chef_id')->get();
        $total = $unassignedOrders->count();

        if ($total === 0) {
            $this->info(' No unassigned orders found!');
            return 0;
        }

        $this->info("Found {$total} unassigned orders");

        $chefs = User::where('role', 'chef')->pluck('id')->toArray();

        if (empty($chefs)) {
            $this->error(' No chefs found in the system!');
            return 1;
        }

        $this->info('Available chefs: ' . count($chefs));

        $assigned = 0;
        $bar = $this->output->createProgressBar($total);
        $bar->start();

        foreach ($unassignedOrders as $order) {

            $chefWorkload = [];
            foreach ($chefs as $chefId) {
                $count = Order::where('chef_id', $chefId)
                    ->whereIn('status', [Order::STATUS_PENDING, Order::STATUS_PREPARING])
                    ->count();
                $chefWorkload[$chefId] = $count;
            }

            $selectedChef = array_key_first($chefWorkload);
            foreach ($chefWorkload as $chefId => $count) {
                if ($count < $chefWorkload[$selectedChef]) {
                    $selectedChef = $chefId;
                }
            }

            $order->update(['chef_id' => $selectedChef]);
            $assigned++;

            $bar->advance();
        }

        $bar->finish();
        $this->newLine();

        $this->info(" Successfully assigned {$assigned} orders to chefs!");

        return 0;
    }
}


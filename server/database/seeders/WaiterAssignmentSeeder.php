<?php

namespace Database\Seeders;

use App\Models\Order;
use App\Models\User;
use App\Models\WaiterAssignment;
use Illuminate\Database\Seeder;
use Carbon\Carbon;

class WaiterAssignmentSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get all ready orders
        $readyOrders = Order::where('status', 'ready')->get();

        // Get all waiter users
        $waiters = User::where('role', 'waiter')->get();

        // Get admin user for "assigned_by"
        $admin = User::where('role', 'admin')->first();

        if ($waiters->isEmpty()) {
            echo "No waiters found. Skipping waiter assignment seeding.\n";
            return;
        }

        if ($admin === null) {
            echo "No admin found. Skipping waiter assignment seeding.\n";
            return;
        }

        // Assign each ready order to a waiter
        $waiterIndex = 0;
        foreach ($readyOrders as $order) {
            // Skip if already has an assignment
            $existingAssignment = WaiterAssignment::where('order_id', $order->id)->first();
            if ($existingAssignment) {
                continue;
            }

            // Cycle through waiters
            $waiter = $waiters[$waiterIndex % $waiters->count()];
            $waiterIndex++;

            // Create assignment
            WaiterAssignment::create([
                'waiter_id' => $waiter->id,
                'order_id' => $order->id,
                'assigned_by' => $admin->id,
                'assigned_at' => Carbon::now(),
                'status' => 'pending', // Waiter hasn't seen it yet
                'acceptance_rate' => 100,
                'completion_rate' => 0,
            ]);

            echo "✓ Assigned order {$order->order_number} to waiter {$waiter->first_name} {$waiter->last_name}\n";
        }

        echo " Waiter assignments seeding complete!\n";
    }
}

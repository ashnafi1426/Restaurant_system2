<?php

namespace App\Console\Commands;

use App\Models\DeliveryTask;
use App\Models\Guest;
use App\Models\HotelFloor;
use App\Models\Order;
use App\Models\Room;
use App\Models\User;
use App\Models\Waiter;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

class SeedTestDeliveryData extends Command
{
    protected $signature = 'seed:delivery-data {--fresh : Delete existing delivery tasks first} {--email= : Email of waiter to seed for}';
    protected $description = 'Seed test delivery tasks with various statuses for waiter testing';

    public function handle()
    {
        $this->info('🚀 Starting delivery task seeding...');
        
        // Get target user email
        $email = $this->option('email') ?? 'ashenafisileski7@gmail.com';
        
        // Find or get first waiter
        $user = User::where('email', $email)->first();
        if (!$user) {
            $this->error('❌ User not found: ' . $email);
            return 1;
        }

        $this->info('✅ Found user: ' . $user->email);

        // Load waiter relationship
        $user->load('waiter');
        $waiter = $user->waiter;

        if (!$waiter) {
            $this->error('❌ No waiter profile for user: ' . $email);
            return 1;
        }

        $this->info('✅ Waiter ID: ' . $waiter->id);

        // Option to delete existing
        if ($this->option('fresh')) {
            $this->info('🗑️ Deleting existing delivery tasks for this waiter...');
            DeliveryTask::where('waiter_id', $waiter->id)->delete();
            $this->info('✅ Deleted');
        }

        // Get manager
        $manager = User::where('role', 'manager')->first();
        if (!$manager) {
            $manager = User::where('role', 'admin')->first();
        }

        if (!$manager) {
            $this->error('❌ No manager/admin found!');
            return 1;
        }

        // Get or create floor
        $floor = HotelFloor::first();
        if (!$floor) {
            $floor = HotelFloor::create([
                'id' => Str::uuid(),
                'floor_number' => 1,
                'name' => 'Ground Floor',
                'description' => 'Main dining floor',
            ]);
            $this->info('✅ Created floor: ' . $floor->name);
        }

        // Create guest and order
        $guest = Guest::firstOrCreate(
            ['email' => 'test-guest-' . time() . '@test.com'],
            [
                'id' => Str::uuid(),
                'first_name' => 'Test',
                'last_name' => 'Guest',
                'phone' => '1234567890',
            ]
        );

        // Get available room or create one
        $room = Room::where('status', 'available')->first();
        if (!$room) {
            $room = Room::first();
            if (!$room) {
                $this->error('❌ No rooms found in database');
                return 1;
            }
        }

        // Create test orders with delivery tasks
        $statuses = ['assigned', 'accepted', 'picked_up', 'on_delivery', 'delivered'];
        
        $this->info('📦 Creating delivery tasks...');
        
        foreach ($statuses as $index => $status) {
            $order = Order::create([
                'id' => Str::uuid(),
                'order_number' => 'ORD-TEST-' . time() . '-' . $index,
                'guest_id' => $guest->id,
                'room_id' => $room->id,
                'status' => $status === 'assigned' ? 'preparing' : ($status === 'accepted' || $status === 'picked_up' ? 'ready' : 'ready'),
                'order_time' => now()->subHours(2),
                'total' => rand(500, 2000),
                'source' => 'guest_qr',
            ]);

            $assignedAt = now()->subHours(rand(1, 3));
            $acceptedAt = null;
            $pickedUpAt = null;
            $onDeliveryAt = null;
            $deliveredAt = null;

            // For "Ready for Pickup" page to work, we need status='accepted'
            if ($status === 'assigned') {
                // Don't set any other times yet
            } elseif ($status === 'accepted') {
                $acceptedAt = $assignedAt->clone()->addMinutes(rand(2, 10));
            } elseif ($status === 'picked_up') {
                $acceptedAt = $assignedAt->clone()->addMinutes(rand(2, 10));
                $pickedUpAt = $acceptedAt->clone()->addMinutes(rand(5, 15));
            } elseif ($status === 'on_delivery') {
                $acceptedAt = $assignedAt->clone()->addMinutes(rand(2, 10));
                $pickedUpAt = $acceptedAt->clone()->addMinutes(rand(5, 15));
                $onDeliveryAt = $pickedUpAt->clone()->addMinutes(rand(1, 5));
            } elseif ($status === 'delivered') {
                $acceptedAt = $assignedAt->clone()->addMinutes(rand(2, 10));
                $pickedUpAt = $acceptedAt->clone()->addMinutes(rand(5, 15));
                $onDeliveryAt = $pickedUpAt->clone()->addMinutes(rand(1, 5));
                $deliveredAt = $onDeliveryAt->clone()->addMinutes(rand(5, 15));
            }

            $task = DeliveryTask::create([
                'id' => Str::uuid(),
                'order_id' => $order->id,
                'room_id' => $room->id,
                'floor_id' => $floor->id,
                'waiter_id' => $waiter->id,
                'assigned_by' => $manager->id,
                'assignment_type' => 'manual',
                'status' => $status,
                'assigned_at' => $assignedAt,
                'accepted_at' => $acceptedAt,
                'picked_up_at' => $pickedUpAt,
                'on_delivery_at' => $onDeliveryAt,
                'delivered_at' => $deliveredAt,
                'remarks' => "Test delivery - Status: {$status}",
            ]);

            $this->info("✅ Created delivery task: {$task->id} - Status: {$status}");
        }

        // Update waiter's current orders count
        $activeCount = DeliveryTask::where('waiter_id', $waiter->id)
            ->whereIn('status', ['assigned', 'accepted', 'picked_up', 'on_delivery'])
            ->count();
        
        $waiter->update(['current_orders' => $activeCount]);

        // Verify the data was created
        $readyPickupCount = DeliveryTask::where('waiter_id', $waiter->id)
            ->where('status', 'accepted')
            ->whereHas('order', fn($q) => $q->where('status', 'ready'))
            ->count();

        $this->info('');
        $this->info('✅ Seeding completed successfully!');
        $this->info('📊 Summary:');
        $this->info("  - User Email: {$user->email}");
        $this->info("  - Waiter ID: {$waiter->id}");
        $this->info("  - Created 5 delivery tasks with different statuses");
        $this->info("  - Active orders: {$activeCount}");
        $this->info("  - Ready for Pickup (accepted + order ready): {$readyPickupCount}");
        $this->info('');
        $this->info('🧪 Test the API endpoint:');
        $this->info('  GET /api/waiter/dashboard/ready-pickup');
        $this->info('  (with valid auth token for: ' . $user->email . ')');

        return 0;
    }
}


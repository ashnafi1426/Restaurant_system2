<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Waiter;
use App\Models\Order;
use App\Models\RoomServiceDelivery;
use App\Models\HousekeepingTask;
use App\Models\Reservation;
use App\Models\Room;
use Illuminate\Database\Seeder;
use Carbon\Carbon;

class ManagerSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create waiter users and waiters
        $this->createWaiters();
        
        // Create orders
        $this->createOrders();
        
        // Create deliveries
        $this->createDeliveries();
        
        // Create housekeeping tasks
        $this->createHousekeepingTasks();
    }

    private function createWaiters(): void
    {
        $sections = ['Section A', 'Section B', 'Section C'];
        $shifts = ['morning', 'afternoon', 'evening', 'night'];
        $experienceLevels = ['junior', 'senior', 'head'];
        
        for ($i = 1; $i <= 10; $i++) {
            $user = User::firstOrCreate(
                ['email' => 'waiter' . $i . '@hotel.com'],
                [
                    'first_name' => 'Waiter',
                    'last_name' => 'User' . $i,
                    'phone' => '5550000' . str_pad($i, 2, '0', STR_PAD_LEFT),
                    'password_hash' => bcrypt('password123'),
                    'role' => 'waiter',
                    'is_active' => true,
                ]
            );

            Waiter::firstOrCreate(
                ['user_id' => $user->id],
                [
                    'section' => $sections[($i - 1) % count($sections)],
                    'status' => $i % 3 === 0 ? 'inactive' : ($i % 3 === 1 ? 'on_break' : 'active'),
                    'shift' => $shifts[($i - 1) % count($shifts)],
                    'experience_level' => $experienceLevels[($i - 1) % count($experienceLevels)],
                    'current_tables' => json_encode([($i * 2 - 1), ($i * 2)]),
                ]
            );
        }
    }

    private function createOrders(): void
    {
        $statuses = ['pending', 'preparing', 'ready', 'served', 'cancelled'];
        
        // Get some reservations and rooms to create orders for
        $reservations = Reservation::limit(15)->get();
        $rooms = Room::limit(15)->get();
        
        foreach ($reservations as $index => $reservation) {
            $room = $rooms[$index] ?? $rooms->first();
            $orderNumber = 'ORD-' . now()->format('Ymd') . '-' . str_pad($index + 100, 5, '0', STR_PAD_LEFT);
            
            Order::firstOrCreate(
                ['order_number' => $orderNumber],
                [
                    'reservation_id' => $reservation->id,
                    'guest_id' => $reservation->guest_id,
                    'room_id' => $room->id,
                    'status' => $statuses[array_rand($statuses)],
                    'subtotal' => rand(50, 250),
                    'tax' => rand(5, 50),
                    'discount' => rand(0, 20),
                    'total' => rand(50, 300),
                    'notes' => 'Special request ' . ($index + 1),
                    'created_at' => Carbon::now()->subHours(rand(0, 24)),
                    'updated_at' => Carbon::now()->subHours(rand(0, 24)),
                ]
            );
        }
    }

    private function createDeliveries(): void
    {
        $statuses = ['pending', 'in_progress', 'delivered', 'cancelled'];
        $orders = Order::limit(8)->get();
        $rooms = Room::limit(8)->get();
        $waiters = User::where('role', 'waiter')->limit(8)->get();
        
        foreach ($orders as $index => $order) {
            $room = $rooms[$index] ?? $rooms->first();
            $waiter = $waiters[$index] ?? $waiters->first();
            
            // Check if delivery already exists
            $existingDelivery = RoomServiceDelivery::where('order_id', $order->id)
                ->where('room_id', $room->id)
                ->first();
            
            if (!$existingDelivery) {
                RoomServiceDelivery::create([
                    'order_id' => $order->id,
                    'room_id' => $room->id,
                    'status' => $statuses[array_rand($statuses)],
                    'delivered_by' => $waiter?->id,
                    'scheduled_time' => Carbon::now()->addMinutes(rand(5, 120)),
                    'delivered_time' => rand(0, 1) ? Carbon::now()->addMinutes(rand(5, 120)) : null,
                    'notes' => 'Delivery note ' . ($index + 1),
                ]);
            }
        }
    }

    private function createHousekeepingTasks(): void
    {
        $priorities = ['low', 'medium', 'high'];
        $statuses = ['pending', 'in_progress', 'completed'];
        $rooms = Room::limit(12)->get();
        $staff = User::whereIn('role', ['admin', 'manager'])->limit(12)->get();
        
        foreach ($rooms as $index => $room) {
            $stuffer = $staff[$index] ?? $staff->first();
            
            HousekeepingTask::create([
                'room_id' => $room->id,
                'assigned_to' => $stuffer?->id,
                'task_type' => ['cleaning', 'maintenance', 'inspection', 'linen_change'][array_rand(['cleaning', 'maintenance', 'inspection', 'linen_change'])],
                'status' => $statuses[array_rand($statuses)],
                'priority' => $priorities[array_rand($priorities)],
                'description' => 'Task description ' . ($index + 1),
                'scheduled_time' => Carbon::now()->addHours(rand(1, 24)),
                'completed_time' => rand(0, 1) ? Carbon::now()->addHours(rand(1, 24)) : null,
                'notes' => 'Task notes ' . ($index + 1),
            ]);
        }
    }
}

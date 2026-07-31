<?php

namespace Database\Seeders;

use App\Models\DeliveryTask;
use App\Models\Guest;
use App\Models\HotelFloor;
use App\Models\Order;
use App\Models\Reservation;
use App\Models\Room;
use App\Models\User;
use App\Models\Waiter;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class DeliveryTaskSeeder extends Seeder
{
    /**
     * Run the database seeds.
     * Creates realistic delivery task data with various statuses and relationships
     */
    public function run(): void
    {
        $this->command->info('Starting Delivery Task Seeding...');

        // Get or create test data
        $floors = $this->getOrCreateFloors();
        $rooms = $this->getOrCreateRooms($floors);
        $guests = $this->getOrCreateGuests();
        $reservations = $this->getOrCreateReservations($guests, $rooms);
        $orders = $this->getOrCreateOrders($reservations, $rooms);
        $waiters = $this->getOrCreateWaiters();
        $manager = $this->getOrCreateManager();

        $this->command->info("Floors: {$floors->count()}, Rooms: {$rooms->count()}, Guests: {$guests->count()}");
        $this->command->info("Reservations: {$reservations->count()}, Orders: {$orders->count()}, Waiters: {$waiters->count()}");

        // Create delivery tasks with various statuses
        $this->createDeliveryTasks($orders, $waiters, $manager, $floors);

        $this->command->info('Delivery Task Seeding completed successfully!');
    }

    /**
     * Get or create hotel floors
     */
    private function getOrCreateFloors()
    {
        $floors = HotelFloor::all();

        if ($floors->count() > 0) {
            $this->command->info("Using existing {$floors->count()} floors");
            return $floors;
        }

        $this->command->info('Creating hotel floors...');

        $floorsData = [
            ['floor_number' => 1, 'name' => 'Ground Floor', 'description' => 'Ground floor with restaurant and lobby', 'is_active' => true],
            ['floor_number' => 2, 'name' => 'First Floor', 'description' => 'First floor with standard rooms', 'is_active' => true],
            ['floor_number' => 3, 'name' => 'Second Floor', 'description' => 'Second floor with deluxe rooms', 'is_active' => true],
            ['floor_number' => 4, 'name' => 'Third Floor', 'description' => 'Third floor with suite rooms', 'is_active' => true],
        ];

        foreach ($floorsData as $floorData) {
            HotelFloor::create([
                'id' => Str::uuid(),
                ...$floorData,
            ]);
        }

        return HotelFloor::all();
    }

    /**
     * Get or create rooms
     */
    private function getOrCreateRooms($floors)
    {
        $rooms = Room::all();

        if ($rooms->count() > 0) {
            $this->command->info("Using existing {$rooms->count()} rooms");
            return $rooms;
        }

        $this->command->info('Creating rooms...');

        $roomsCreated = collect();

        foreach ($floors as $floor) {
            $roomCount = $floor->floor_number === 1 ? 0 : 10;

            for ($i = 1; $i <= $roomCount; $i++) {
                $roomNumber = ($floor->floor_number * 100) + $i;

                $room = Room::create([
                    'id' => Str::uuid(),
                    'room_number' => (string) $roomNumber,
                    'room_type_id' => 1,
                    'floor' => $floor->floor_number,
                    'floor_id' => $floor->id,
                    'description' => $this->getRandomRoomType() . " on {$floor->name}",
                    'status' => 'available',
                    'is_active' => true,
                    'qr_token' => Room::generateUniqueToken(),
                ]);

                $roomsCreated->push($room);
            }
        }

        $this->command->info("Created {$roomsCreated->count()} rooms");
        return $roomsCreated;
    }

    /**
     * Get or create guests
     */
    private function getOrCreateGuests()
    {
        $guests = Guest::all();

        if ($guests->count() > 0) {
            $this->command->info("Using existing {$guests->count()} guests");
            return $guests;
        }

        $this->command->info('Creating guests...');

        $guestNames = [
            ['John', 'Smith'],
            ['Emily', 'Johnson'],
            ['Michael', 'Brown'],
            ['Sarah', 'Davis'],
            ['Robert', 'Wilson'],
            ['Jennifer', 'Garcia'],
            ['David', 'Rodriguez'],
            ['Maria', 'Martinez'],
            ['James', 'Taylor'],
            ['Patricia', 'Anderson'],
            ['Christopher', 'Thomas'],
            ['Linda', 'Moore'],
            ['William', 'Jackson'],
            ['Barbara', 'White'],
            ['Daniel', 'Harris'],
        ];

        $guestsCreated = collect();

        foreach ($guestNames as $index => [$firstName, $lastName]) {
            $guest = Guest::create([
                'id' => Str::uuid(),
                'first_name' => $firstName,
                'last_name' => $lastName,
                'email' => strtolower("{$firstName}.{$lastName}@example.com"),
                'phone' => $this->generatePhoneNumber(),
                'address' => "{$this->getRandomAddress()}, City",
                'nationality' => $this->getRandomNationality(),
                'passport_number' => strtoupper(Str::random(9)),
                'date_of_birth' => fake()->dateTimeBetween('-60 years', '-18 years')->format('Y-m-d'),
            ]);

            $guestsCreated->push($guest);
        }

        $this->command->info("Created {$guestsCreated->count()} guests");
        return $guestsCreated;
    }

    /**
     * Get or create reservations
     */
    private function getOrCreateReservations($guests, $rooms)
    {
        $reservations = Reservation::all();

        if ($reservations->count() > 0) {
            $this->command->info("Using existing {$reservations->count()} reservations");
            return $reservations;
        }

        $this->command->info('Creating reservations...');

        $reservationsCreated = collect();
        $checkInDate = now()->subDays(2);
        $checkOutDate = now()->addDays(3);

        foreach ($guests->take(12) as $guest) {
            $room = $rooms->random();

            $reservation = Reservation::create([
                'id' => Str::uuid(),
                'booking_reference' => 'BK-' . now()->format('Ymd') . '-' . str_pad($guest->id % 1000, 4, '0', STR_PAD_LEFT),
                'guest_id' => $guest->id,
                'room_id' => $room->id,
                'check_in_date' => $checkInDate,
                'check_out_date' => $checkOutDate,
                'number_of_guests' => rand(1, 4),
                'status' => fake()->randomElement(['pending', 'confirmed', 'checked_in']),
                'special_requests' => fake()->randomElement([
                    'High floor preferred',
                    'Non-smoking room',
                    'Extra pillows needed',
                    'Early breakfast requested',
                    null,
                ]),
            ]);

            $reservationsCreated->push($reservation);
        }

        $this->command->info("Created {$reservationsCreated->count()} reservations");
        return $reservationsCreated;
    }

    /**
     * Get or create orders
     */
    private function getOrCreateOrders($reservations, $rooms)
    {
        $orders = Order::all();

        if ($orders->count() > 0) {
            $this->command->info("Using existing {$orders->count()} orders");
            return $orders;
        }

        $this->command->info('Creating orders...');

        $ordersCreated = collect();

        foreach ($reservations->take(20) as $reservation) {
            $order = Order::create([
                'id' => Str::uuid(),
                'order_number' => 'ORD-' . strtoupper(Str::random(8)),
                'reservation_id' => $reservation->id,
                'guest_id' => $reservation->guest_id,
                'room_id' => $reservation->room_id,
                'order_time' => now()->subHours(rand(1, 4)),
                'status' => fake()->randomElement(['ready', 'pending', 'preparing']),
                'source' => 'room_service',
                'notes' => fake()->randomElement([
                    'No onions please',
                    'Extra spicy',
                    'Vegetarian option',
                    'Allergy: nuts',
                    null,
                ]),
                'special_requests' => fake()->randomElement([
                    'Deliver by window',
                    'Leave outside door',
                    'Room service bell at door',
                    null,
                ]),
                'payment_type' => 'room_charge',
                'subtotal' => fake()->randomFloat(2, 50, 200),
                'tax' => fake()->randomFloat(2, 5, 30),
                'discount' => fake()->randomElement([0, 0, 0, fake()->randomFloat(2, 5, 20)]),
            ]);

            // Calculate total
            $order->update([
                'total' => $order->subtotal + $order->tax - $order->discount,
            ]);

            $ordersCreated->push($order);
        }

        $this->command->info("Created {$ordersCreated->count()} orders");
        return $ordersCreated;
    }

    /**
     * Get or create waiters
     */
    private function getOrCreateWaiters()
    {
        $waiters = Waiter::all();

        if ($waiters->count() > 0) {
            $this->command->info("Using existing {$waiters->count()} waiters");
            return $waiters;
        }

        $this->command->info('Creating waiters...');

        $waiterNames = [
            ['John', 'Smith'],
            ['Sarah', 'Johnson'],
            ['Michael', 'Brown'],
            ['Emily', 'Davis'],
            ['Robert', 'Wilson'],
        ];

        $waitersCreated = collect();

        foreach ($waiterNames as $index => [$firstName, $lastName]) {
            // Create user first
            $user = User::create([
                'first_name' => $firstName,
                'last_name' => $lastName,
                'email' => strtolower("waiter{$index}@test.com"),
                'password_hash' => bcrypt('password123'),
                'role' => 'waiter',
                'is_active' => true,
            ]);

            // Create waiter profile
            $waiter = Waiter::create([
                'user_id' => $user->id,
                'employee_number' => 'W' . str_pad($index + 1, 4, '0', STR_PAD_LEFT),
                'phone' => $this->generatePhoneNumber(),
                'section' => fake()->randomElement(['Ground Floor', 'First Floor', 'Second Floor', 'Third Floor']),
                'shift' => fake()->randomElement(['morning', 'afternoon', 'evening']),
                'experience_level' => fake()->randomElement(['junior', 'senior', 'lead']),
                'employment_type' => fake()->randomElement(['full_time', 'part_time']),
                'hire_date' => now()->subMonths(rand(3, 24))->toDateString(),
                'status' => 'active',
                'availability' => 'available',
                'current_orders' => 0,
                'maximum_orders' => 10,
            ]);

            $waitersCreated->push($waiter);
        }

        $this->command->info("Created {$waitersCreated->count()} waiters");
        return $waitersCreated;
    }

    /**
     * Get or create manager user
     */
    private function getOrCreateManager()
    {
        $manager = User::where('role', 'manager')->first();

        if ($manager) {
            $this->command->info('Using existing manager user');
            return $manager;
        }

        $this->command->info('Creating manager user...');

        $manager = User::create([
            'first_name' => 'Alice',
            'last_name' => 'Manager',
            'email' => 'manager@test.com',
            'password_hash' => bcrypt('password123'),
            'role' => 'manager',
            'is_active' => true,
        ]);

        return $manager;
    }

    /**
     * Create delivery tasks with various statuses
     */
    private function createDeliveryTasks($orders, $waiters, $manager, $floors)
    {
        $this->command->info('Creating delivery tasks with various statuses...');

        $taskCount = 0;
        $statuses = ['assigned', 'accepted', 'picked_up', 'on_delivery', 'delivered'];

        // Group orders by status and create delivery tasks
        $ordersToProcess = $orders->take(20);

        foreach ($ordersToProcess as $index => $order) {
            if (DeliveryTask::where('order_id', $order->id)->exists()) {
                continue;
            }

            $waiter = $waiters->random();
            $floor = $floors->random();
            $status = $statuses[$index % count($statuses)];

            // Calculate timestamps based on status progression
            $assignedAt = now()->subHours(rand(1, 4));
            $acceptedAt = null;
            $pickedUpAt = null;
            $onDeliveryAt = null;
            $deliveredAt = null;

            if (in_array($status, ['accepted', 'picked_up', 'on_delivery', 'delivered'])) {
                $acceptedAt = $assignedAt->clone()->addMinutes(rand(2, 10));
            }

            if (in_array($status, ['picked_up', 'on_delivery', 'delivered'])) {
                $pickedUpAt = $acceptedAt->clone()->addMinutes(rand(5, 15));
            }

            if (in_array($status, ['on_delivery', 'delivered'])) {
                $onDeliveryAt = $pickedUpAt->clone()->addMinutes(rand(1, 5));
            }

            if ($status === 'delivered') {
                $deliveredAt = $onDeliveryAt->clone()->addMinutes(rand(5, 25));
            }

            $deliveryTask = DeliveryTask::create([
                'id' => Str::uuid(),
                'order_id' => $order->id,
                'reservation_id' => $order->reservation_id,
                'room_id' => $order->room_id,
                'floor_id' => $floor->id,
                'waiter_id' => $waiter->id,
                'assigned_by' => $manager->id,
                'assignment_type' => fake()->randomElement(['automatic', 'manual']),
                'status' => $status,
                'assigned_at' => $assignedAt,
                'accepted_at' => $acceptedAt,
                'picked_up_at' => $pickedUpAt,
                'on_delivery_at' => $onDeliveryAt,
                'delivered_at' => $deliveredAt,
                'remarks' => $this->getRandomRemark($status),
            ]);

            // Update waiter's current orders based on delivery status
            if (in_array($status, ['assigned', 'accepted', 'picked_up', 'on_delivery'])) {
                $waiter->incrementOrders();
            }

            $taskCount++;
        }

        $this->command->info("Created {$taskCount} delivery tasks");

        // Add some cancelled and edge case deliveries
        $this->createCancelledAndEdgeCaseDeliveries($orders, $waiters, $manager, $floors);
    }

    /**
     * Create cancelled and edge case delivery tasks
     */
    private function createCancelledAndEdgeCaseDeliveries($orders, $waiters, $manager, $floors)
    {
        $this->command->info('Creating cancelled and edge case deliveries...');

        $edgeCases = 0;

        // Create some cancelled deliveries
        foreach ($orders->slice(15, 3) as $order) {
            if (DeliveryTask::where('order_id', $order->id)->exists()) {
                continue;
            }

            $waiter = $waiters->random();
            $floor = $floors->random();
            $assignedAt = now()->subHours(rand(2, 6));
            $cancelledAt = $assignedAt->clone()->addMinutes(rand(10, 40));

            DeliveryTask::create([
                'id' => Str::uuid(),
                'order_id' => $order->id,
                'reservation_id' => $order->reservation_id,
                'room_id' => $order->room_id,
                'floor_id' => $floor->id,
                'waiter_id' => $waiter->id,
                'assigned_by' => $manager->id,
                'assignment_type' => 'manual',
                'status' => 'cancelled',
                'assigned_at' => $assignedAt,
                'accepted_at' => $assignedAt->clone()->addMinutes(rand(1, 5)),
                'cancelled_at' => $cancelledAt,
                'cancellation_reason' => fake()->randomElement([
                    'Guest cancelled order',
                    'Room not responding',
                    'Guest left hotel',
                    'Order items unavailable',
                    'Guest requested cancellation',
                ]),
                'remarks' => 'Delivery cancelled by manager request',
            ]);

            $edgeCases++;
        }

        // Create some "waiting_assignment" tasks (pending delivery)
        foreach ($orders->slice(18, 2) as $order) {
            if (DeliveryTask::where('order_id', $order->id)->exists()) {
                continue;
            }

            $floor = $floors->random();
            $assignedAt = now()->subMinutes(rand(5, 20));

            DeliveryTask::create([
                'id' => Str::uuid(),
                'order_id' => $order->id,
                'reservation_id' => $order->reservation_id,
                'room_id' => $order->room_id,
                'floor_id' => $floor->id,
                'waiter_id' => null, // Not yet assigned to a waiter
                'assigned_by' => $manager->id,
                'assignment_type' => 'automatic',
                'status' => 'waiting_assignment',
                'assigned_at' => $assignedAt,
                'remarks' => 'Waiting for available waiter',
            ]);

            $edgeCases++;
        }

        $this->command->info("Created {$edgeCases} edge case/cancelled deliveries");
    }

    /**
     * Get random room type description
     */
    private function getRandomRoomType()
    {
        return fake()->randomElement([
            'Standard Room',
            'Deluxe Room',
            'Suite',
            'Executive Suite',
            'Luxury Suite',
        ]);
    }

    /**
     * Generate random phone number
     */
    private function generatePhoneNumber()
    {
        return '+1' . rand(200, 999) . rand(2000, 9999) . rand(1000, 9999);
    }

    /**
     * Get random address
     */
    private function getRandomAddress()
    {
        return rand(100, 9999) . ' ' . fake()->randomElement([
            'Main Street',
            'Oak Avenue',
            'Pine Road',
            'Elm Street',
            'Maple Drive',
            'Cedar Lane',
        ]);
    }

    /**
     * Get random nationality
     */
    private function getRandomNationality()
    {
        return fake()->randomElement([
            'American',
            'British',
            'Canadian',
            'Australian',
            'French',
            'German',
            'Spanish',
            'Italian',
            'Japanese',
            'Chinese',
            'Indian',
            'Brazilian',
        ]);
    }

    /**
     * Get random remark based on delivery status
     */
    private function getRandomRemark($status)
    {
        $remarks = [
            'assigned' => 'Order assigned to waiter for pickup',
            'accepted' => 'Waiter accepted the delivery task',
            'picked_up' => 'Order picked up from kitchen',
            'on_delivery' => 'Delivery in progress',
            'delivered' => fake()->randomElement([
                'Delivered successfully',
                'Left at door per guest request',
                'Handed to guest personally',
                'Room service completed',
            ]),
        ];

        return $remarks[$status] ?? null;
    }
}

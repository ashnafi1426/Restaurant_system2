<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class WaiterSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create test waiter users
        $waiters = [
            [
                'first_name' => 'John',
                'last_name' => 'Smith',
                'email' => 'waiter1@test.com',
                'password_hash' => bcrypt('password123'),
                'role' => 'waiter',
                'is_active' => true,
            ],
            [
                'first_name' => 'Sarah',
                'last_name' => 'Johnson',
                'email' => 'waiter2@test.com',
                'password_hash' => bcrypt('password123'),
                'role' => 'waiter',
                'is_active' => true,
            ],
            [
                'first_name' => 'Michael',
                'last_name' => 'Brown',
                'email' => 'waiter3@test.com',
                'password_hash' => bcrypt('password123'),
                'role' => 'waiter',
                'is_active' => true,
            ],
            [
                'first_name' => 'Emily',
                'last_name' => 'Davis',
                'email' => 'waiter4@test.com',
                'password_hash' => bcrypt('password123'),
                'role' => 'waiter',
                'is_active' => true,
            ],
            [
                'first_name' => 'Robert',
                'last_name' => 'Wilson',
                'email' => 'waiter5@test.com',
                'password_hash' => bcrypt('password123'),
                'role' => 'waiter',
                'is_active' => true,
            ],
        ];

        foreach ($waiters as $waiter) {
            // Check if user already exists
            $existingUser = User::where('email', $waiter['email'])->first();
            
            if (!$existingUser) {
                User::create($waiter);
                $this->command->info("Created waiter: {$waiter['first_name']} {$waiter['last_name']}");
            } else {
                // Update role if needed
                if ($existingUser->role !== 'waiter') {
                    $existingUser->update(['role' => 'waiter']);
                    $this->command->info("Updated {$existingUser->email} to waiter role");
                } else {
                    $this->command->info("Waiter {$existingUser->email} already exists");
                }
            }
        }

        $this->command->info('Waiter seeding completed!');
    }
}

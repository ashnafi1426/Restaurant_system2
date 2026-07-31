<?php

namespace Database\Seeders;

use App\Models\Waiter;
use App\Models\HotelFloor;
use App\Models\HotelShift;
use App\Models\WaiterFloorAssignment;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;

/**
 * WaiterManagementSeeder
 * Seeds shifts, floors, waiters, and floor assignments with proper UUID handling
 */
class WaiterManagementSeeder extends Seeder
{
    private array $shiftIds = [];
    private array $floorIds = [];

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('🚀 Starting Waiter Management seeding...');
        $this->command->newLine();

        try {
            DB::beginTransaction();

            // Step 1: Create Hotel Shifts with UUID tracking
            $this->command->info('📋 Step 1: Creating hotel shifts...');
            $this->seedShifts();
            $this->command->newLine();

            // Step 2: Create Hotel Floors with UUID tracking
            $this->command->info('🏢 Step 2: Creating hotel floors...');
            $this->seedFloors();
            $this->command->newLine();

            // Step 3: Create Waiters (with users)
            $this->command->info('👔 Step 3: Creating waiters...');
            $waiters = $this->seedWaiters();
            $this->command->newLine();

            // Step 4: Create Floor Assignments
            $this->command->info('📌 Step 4: Creating floor assignments...');
            $assignmentCount = $this->seedFloorAssignments($waiters);
            $this->command->newLine();

            DB::commit();

            $this->command->info(' Waiter Management seeding completed successfully!');
            $this->command->newLine();
            $this->outputSummary($waiters, $assignmentCount);

        } catch (\Exception $e) {
            DB::rollBack();
            $this->command->error('❌ Seeding failed: ' . $e->getMessage());
            $this->command->line('Stack trace: ' . $e->getTraceAsString());
            throw $e;
        }
    }

    /**
     * Output seeding summary
     */
    private function outputSummary(array $waiters, int $assignments): void
    {
        $this->command->line('');
        $this->command->line('═══════════════════════════════════════════');
        $this->command->line('        SEEDING SUMMARY');
        $this->command->line('═══════════════════════════════════════════');
        $this->command->line('✓ Shifts Created:           ' . count($this->shiftIds));
        $this->command->line('✓ Floors Created:           ' . count($this->floorIds));
        $this->command->line('✓ Waiters Created:          ' . count($waiters));
        $this->command->line('✓ Floor Assignments:        ' . $assignments);
        $this->command->line('═══════════════════════════════════════════');
        $this->command->line('');
    }

    /**
     * Seed hotel shifts with proper UUID generation
     */
    private function seedShifts(): void
    {
        $shifts = [
            [
                'name' => 'Morning',
                'start_time' => '06:00:00',
                'end_time' => '14:00:00',
                'status' => 'active',
                'description' => 'Early morning breakfast and brunch shift',
            ],
            [
                'name' => 'Afternoon',
                'start_time' => '14:00:00',
                'end_time' => '22:00:00',
                'status' => 'active',
                'description' => 'Afternoon lunch and early dinner shift',
            ],
            [
                'name' => 'Night',
                'start_time' => '22:00:00',
                'end_time' => '06:00:00',
                'status' => 'active',
                'description' => 'Late night dinner and room service shift',
            ],
        ];

        foreach ($shifts as $shiftData) {
            $existing = HotelShift::where('name', $shiftData['name'])->first();
            
            if ($existing) {
                $this->shiftIds[$shiftData['name']] = $existing->id;
                $this->command->line("  ℹ️  Shift exists: {$shiftData['name']}");
                $this->command->line("     UUID: {$existing->id}");
            } else {
                $uuid = Str::uuid()->toString();
                HotelShift::create([
                    'id' => $uuid,
                    ...$shiftData,
                ]);
                $this->shiftIds[$shiftData['name']] = $uuid;
                $this->command->line("   Created shift: {$shiftData['name']}");
                $this->command->line("     UUID: {$uuid}");
            }
        }
    }

    /**
     * Seed hotel floors with proper UUID generation
     */
    private function seedFloors(): void
    {
        $floors = [
            ['floor_number' => 1, 'name' => 'Ground Floor', 'description' => 'Main restaurant and reception area', 'is_active' => true, 'total_rooms' => 0],
            ['floor_number' => 2, 'name' => 'First Floor', 'description' => 'Room service area for rooms 101-110', 'is_active' => true, 'total_rooms' => 10],
            ['floor_number' => 3, 'name' => 'Second Floor', 'description' => 'Room service area for rooms 201-210', 'is_active' => true, 'total_rooms' => 10],
            ['floor_number' => 4, 'name' => 'Third Floor', 'description' => 'Room service area for rooms 301-310', 'is_active' => true, 'total_rooms' => 10],
            ['floor_number' => 5, 'name' => 'Conference Hall', 'description' => 'Banquet and conference area', 'is_active' => true, 'total_rooms' => 0],
        ];

        foreach ($floors as $floorData) {
            $existing = HotelFloor::where('floor_number', $floorData['floor_number'])->first();
            
            if ($existing) {
                $this->floorIds[$floorData['floor_number']] = $existing->id;
                $this->command->line("  ℹ️  Floor exists: {$floorData['name']}");
                $this->command->line("     UUID: {$existing->id}");
            } else {
                $uuid = Str::uuid()->toString();
                HotelFloor::create([
                    'id' => $uuid,
                    ...$floorData,
                ]);
                $this->floorIds[$floorData['floor_number']] = $uuid;
                $this->command->line("   Created floor: {$floorData['name']}");
                $this->command->line("     UUID: {$uuid}");
            }
        }
    }

    /**
     * Seed waiters with users
     */
    private function seedWaiters(): array
    {
        $waitersData = [
            ['first_name' => 'John', 'last_name' => 'Smith', 'email' => 'john.smith@waiter.com', 'phone' => '+1-555-0101', 'employment_type' => 'full_time', 'experience_level' => 'senior'],
            ['first_name' => 'Sarah', 'last_name' => 'Johnson', 'email' => 'sarah.johnson@waiter.com', 'phone' => '+1-555-0102', 'employment_type' => 'full_time', 'experience_level' => 'senior'],
            ['first_name' => 'Michael', 'last_name' => 'Brown', 'email' => 'michael.brown@waiter.com', 'phone' => '+1-555-0103', 'employment_type' => 'full_time', 'experience_level' => 'junior'],
            ['first_name' => 'Emily', 'last_name' => 'Davis', 'email' => 'emily.davis@waiter.com', 'phone' => '+1-555-0104', 'employment_type' => 'part_time', 'experience_level' => 'junior'],
            ['first_name' => 'David', 'last_name' => 'Wilson', 'email' => 'david.wilson@waiter.com', 'phone' => '+1-555-0105', 'employment_type' => 'contract', 'experience_level' => 'senior'],
            ['first_name' => 'Lisa', 'last_name' => 'Martinez', 'email' => 'lisa.martinez@waiter.com', 'phone' => '+1-555-0106', 'employment_type' => 'full_time', 'experience_level' => 'junior'],
        ];

        $createdWaiters = [];

        foreach ($waitersData as $waiterData) {
            // Create or update user
            $user = User::firstOrCreate(
                ['email' => $waiterData['email']],
                [
                    'id' => Str::uuid()->toString(),
                    'first_name' => $waiterData['first_name'],
                    'last_name' => $waiterData['last_name'],
                    'password_hash' => Hash::make('password123'),
                    'role' => 'waiter',
                    'phone' => $waiterData['phone'],
                ]
            );

            // Create or update waiter
            $waiter = Waiter::firstOrCreate(
                ['user_id' => $user->id],
                [
                    'phone' => $waiterData['phone'],
                    'employment_type' => $waiterData['employment_type'],
                    'hire_date' => now()->subMonths(rand(3, 24))->toDateString(),
                    'status' => 'active',
                    'availability' => 'offline',
                    'current_orders' => 0,
                    'maximum_orders' => $waiterData['experience_level'] === 'senior' ? 8 : 5,
                    'experience_level' => $waiterData['experience_level'],
                ]
            );
            
            // Generate employee number if not set
            if (!$waiter->employee_number) {
                $waiter->update(['employee_number' => 'WTR' . str_pad($waiter->id, 4, '0', STR_PAD_LEFT)]);
            }

            $createdWaiters[] = $waiter;
            $this->command->line("   Created waiter: {$waiterData['first_name']} {$waiterData['last_name']}");
            $this->command->line("     Email: {$user->email} | ID: {$waiter->id}");
        }

        return $createdWaiters;
    }

    /**
     * Seed floor assignments for today with proper UUID handling
     */
    private function seedFloorAssignments(array $waiters): int
    {
        if (empty($this->shiftIds) || empty($this->floorIds)) {
            $this->command->line("   Missing shifts or floors. Skipping assignments.");
            return 0;
        }

        $today = now()->toDateString();
        $manager = User::where('role', 'manager')->first();
        $managerId = $manager?->id;

        if (!$managerId) {
            $this->command->line("   No manager found for assignments. Skipping.");
            return 0;
        }

        $priorities = ['primary', 'secondary', 'backup'];
        $assignmentCount = 0;
        $waiterIndex = 0;

        // Assign waiters to each floor for each shift
        foreach ($this->shiftIds as $shiftName => $shiftId) {
            foreach ($this->floorIds as $floorNumber => $floorId) {
                for ($i = 0; $i < min(3, count($waiters)); $i++) {
                    $waiter = $waiters[($waiterIndex + $i) % count($waiters)];
                    $priority = $priorities[$i];

                    // Check if assignment already exists
                    $exists = WaiterFloorAssignment::where([
                        ['waiter_id', '=', $waiter->id],
                        ['floor_id', '=', $floorId],
                        ['shift_id', '=', $shiftId],
                        ['assignment_date', '=', $today],
                    ])->exists();

                    if (!$exists) {
                        try {
                            WaiterFloorAssignment::create([
                                'id' => Str::uuid()->toString(),
                                'waiter_id' => $waiter->id,
                                'floor_id' => $floorId,
                                'shift_id' => $shiftId,
                                'assignment_date' => $today,
                                'status' => 'active',
                                'priority' => $priority,
                                'assigned_by' => $managerId,
                            ]);
                            $assignmentCount++;
                        } catch (\Exception $e) {
                            $this->command->line("   Failed to assign {$waiter->user->name} to floor {$floorNumber}: " . $e->getMessage());
                        }
                    }
                }
                $waiterIndex++;
            }
        }

        $this->command->line("   Created {$assignmentCount} floor assignments for today");
        return $assignmentCount;
    }
}

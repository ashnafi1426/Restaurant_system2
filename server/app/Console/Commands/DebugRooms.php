<?php

namespace App\Console\Commands;

use App\Models\Room;
use Illuminate\Console\Command;

class DebugRooms extends Command
{
    protected $signature = 'debug:rooms';
    protected $description = 'Debug room data in database';

    public function handle()
    {
        $this->info('=== ROOM DATABASE DEBUG ===');
        $this->newLine();

        // Check total rooms
        $totalRooms = Room::count();
        $this->info("Total rooms in database: {$totalRooms}");
        $this->newLine();

        // Get all rooms with their relationships
        $rooms = Room::with('roomType')->get();

        if ($rooms->isEmpty()) {
            $this->warn('No rooms found in database!');
            return;
        }

        $this->info('=== ALL ROOMS ===');
        $this->table(
            ['Room Number', 'Floor', 'Status', 'Room Type', 'Active', 'ID'],
            $rooms->map(function ($room) {
                return [
                    $room->room_number,
                    $room->floor,
                    $room->status,
                    $room->roomType?->name ?? 'N/A',
                    $room->is_active ? 'Yes' : 'No',
                    substr($room->id, 0, 8) . '...',
                ];
            })
        );

        $this->newLine();
        $this->info('=== SEARCHING FOR ROOM 202 ===');
        $room202 = Room::where('room_number', '202')->first();
        
        if ($room202) {
            $this->info('✓ Room 202 FOUND');
            $this->info("  Room Number: {$room202->room_number}");
            $this->info("  Floor: {$room202->floor}");
            $this->info("  Status: {$room202->status}");
            $this->info("  Room Type ID: {$room202->room_type_id}");
            $this->info("  Room Type Name: " . ($room202->roomType?->name ?? 'N/A'));
            $this->info("  Active: " . ($room202->is_active ? 'Yes' : 'No'));
            $this->info("  ID: {$room202->id}");
            $this->info("  Created: {$room202->created_at}");
            $this->info("  Updated: {$room202->updated_at}");
        } else {
            $this->error('✗ Room 202 NOT FOUND');
        }

        $this->newLine();
        $this->info('=== SEARCH TEST ===');
        $searchResults = Room::search('202')->get();
        $this->info("Search results for '202': " . $searchResults->count());
        foreach ($searchResults as $room) {
            $this->line("  - {$room->room_number} (Floor: {$room->floor}, Status: {$room->status})");
        }

        $this->newLine();
        $this->info('=== CACHE STATUS ===');
        $cacheStore = config('cache.default');
        $this->info("Cache Store: {$cacheStore}");
        $this->info("Cache Prefix: " . config('cache.prefix'));
    }
}

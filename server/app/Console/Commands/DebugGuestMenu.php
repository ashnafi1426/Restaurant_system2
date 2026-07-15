<?php

namespace App\Console\Commands;

use App\Models\Room;
use App\Models\MenuItem;
use App\Models\Reservation;
use App\Models\Guest;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class DebugGuestMenu extends Command
{
    protected $signature = 'debug:guest-menu';
    protected $description = 'Debug guest menu and QR system data';

    public function handle()
    {
        $this->info('=== GUEST MENU DEBUG REPORT ===');
        $this->newLine();

        // 1. Check rooms with QR tokens
        $this->info('1. ROOMS WITH QR TOKENS:');
        $rooms = Room::orderBy('room_number')->get();
        $this->line("Total rooms: " . $rooms->count());
        
        foreach ($rooms->take(5) as $room) {
            $this->line("  Room #{$room->room_number}: qr_token='" . ($room->qr_token ?? 'NULL') . "' status={$room->status}");
        }
        if ($rooms->count() > 5) {
            $this->line("  ... and " . ($rooms->count() - 5) . " more rooms");
        }
        $this->newLine();

        // 2. Check reservations with checked_in status
        $this->info('2. RESERVATIONS WITH checked_in STATUS:');
        $checkedInReservations = Reservation::where('status', 'checked_in')->get();
        $this->line("Total checked_in reservations: " . $checkedInReservations->count());
        
        foreach ($checkedInReservations->take(5) as $res) {
            $room = Room::find($res->room_id);
            $this->line("  Reservation #{$res->id}: Room=#{$room?->room_number}, Guest={$res->guest_id}, Status={$res->status}");
        }
        $this->newLine();

        // 3. Check all reservations by status
        $this->info('3. ALL RESERVATIONS BY STATUS:');
        $statuses = Reservation::select('status', DB::raw('COUNT(*) as count'))
            ->groupBy('status')
            ->get();
        
        foreach ($statuses as $status) {
            $this->line("  {$status->status}: {$status->count}");
        }
        $this->newLine();

        // 4. Check menu items
        $this->info('4. MENU ITEMS:');
        $menuItems = MenuItem::orderBy('category')->get();
        $this->line("Total menu items: " . $menuItems->count());
        
        $byCat = $menuItems->groupBy('category');
        foreach ($byCat as $cat => $items) {
            $this->line("  {$cat}: " . count($items) . " items");
        }
        $this->newLine();

        // 5. Check guests
        $this->info('5. GUESTS:');
        $guests = Guest::all();
        $this->line("Total guests: " . $guests->count());
        
        foreach ($guests->take(3) as $guest) {
            $this->line("  {$guest->first_name} {$guest->last_name} ({$guest->email})");
        }
        $this->newLine();

        $this->info('=== END DEBUG REPORT ===');
    }
}

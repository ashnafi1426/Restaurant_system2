<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;

class DebugUsers extends Command
{
    protected $signature = 'debug:users';
    protected $description = 'Debug user authentication issue';

    public function handle()
    {
        $this->info("\n=== ALL USERS IN DATABASE ===\n");
        
        $users = User::all();
        
        if ($users->isEmpty()) {
            $this->error("NO USERS FOUND IN DATABASE!");
            return 1;
        }
        
        foreach ($users as $user) {
            $this->line("Email: " . $user->email);
            $this->line("Name: " . $user->first_name . " " . $user->last_name);
            $this->line("Role: " . $user->role);
            $this->line("Active: " . ($user->is_active ? 'Yes' : 'No'));
            $this->line("Password Hash: " . substr($user->password_hash, 0, 20) . "...");
            $this->line("---");
        }
        
        $this->info("\n=== PASSWORD TESTS FOR ALL USERS ===\n");
        
        $testPasswords = [
            'admin@hotel.com' => 'Admin123@',
            'receptionist@hotel.com' => 'Reception123@',
            'cashier@hotel.com' => 'Cashier123@',
            'manager@hotel.com' => 'Manager123@',
            'chef@hotel.com' => 'Chef123@',
        ];
        
        foreach ($testPasswords as $email => $password) {
            $user = User::where('email', $email)->first();
            
            if (!$user) {
                $this->error("✗ User not found: {$email}");
                continue;
            }
            
            $isValid = password_verify($password, $user->password_hash);
            
            if ($isValid) {
                $this->info("✓ {$email}: PASSWORD MATCHES");
            } else {
                $this->error("✗ {$email}: PASSWORD DOES NOT MATCH");
                $this->line("  Expected password: {$password}");
                $this->line("  User info: {$user->first_name} {$user->last_name} ({$user->role})");
            }
        }
        
        return 0;
    }
}


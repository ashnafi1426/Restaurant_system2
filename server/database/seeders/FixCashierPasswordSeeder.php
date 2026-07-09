<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Support\Str;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class FixCashierPasswordSeeder extends Seeder
{
    public function run(): void
    {
        // Fix admin
        $admin = User::where('email', 'admin@hotel.com')->first();
        if ($admin) {
            $admin->update([
                'first_name' => 'System',
                'last_name' => 'Admin',
                'password_hash' => Hash::make('Admin123@'),
            ]);
            echo "✓ Admin user fixed\n";
        }
        
        // Fix receptionist
        $receptionist = User::where('email', 'receptionist@hotel.com')->first();
        if ($receptionist) {
            $receptionist->update([
                'first_name' => 'John',
                'last_name' => 'Reception',
                'password_hash' => Hash::make('Reception123@'),
            ]);
            echo "✓ Receptionist user fixed\n";
        }
        
        // Fix cashier
        $cashier = User::where('email', 'cashier@hotel.com')->first();
        if ($cashier) {
            $cashier->update([
                'first_name' => 'Sarah',
                'last_name' => 'Cashier',
                'password_hash' => Hash::make('Cashier123@'),
            ]);
            echo "✓ Cashier user fixed\n";
        }
        
        // Fix manager
        $manager = User::where('email', 'manager@hotel.com')->first();
        if ($manager) {
            $manager->update([
                'first_name' => 'David',
                'last_name' => 'Manager',
                'password_hash' => Hash::make('Manager123@'),
            ]);
            echo "✓ Manager user fixed\n";
        }
        
        // Fix chef
        $chef = User::where('email', 'chef@hotel.com')->first();
        if ($chef) {
            $chef->update([
                'first_name' => 'Michael',
                'last_name' => 'Chef',
                'password_hash' => Hash::make('Chef123@'),
            ]);
            echo "✓ Chef user fixed\n";
        }
    }
}


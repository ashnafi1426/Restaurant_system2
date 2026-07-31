<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // First, modify the role enum to include 'waiter'
        DB::statement("ALTER TABLE users MODIFY COLUMN role ENUM('admin', 'receptionist', 'cashier', 'manager', 'chef', 'waiter')");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Revert back to original enum without 'waiter'
        DB::statement("ALTER TABLE users MODIFY COLUMN role ENUM('admin', 'receptionist', 'cashier', 'manager', 'chef')");
    }
};

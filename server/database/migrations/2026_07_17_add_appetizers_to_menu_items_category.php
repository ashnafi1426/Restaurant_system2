<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // For MySQL, we need to modify the enum column
        // The safest way is to use raw SQL to change the enum values
        DB::statement("ALTER TABLE menu_items MODIFY category ENUM('breakfast', 'lunch', 'dinner', 'drinks', 'dessert', 'appetizers')");
    }

    public function down(): void
    {
        // Rollback to original enum values
        DB::statement("ALTER TABLE menu_items MODIFY category ENUM('breakfast', 'lunch', 'dinner', 'drinks', 'dessert')");
    }
};

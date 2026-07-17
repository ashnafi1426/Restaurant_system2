<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('menu_items', function (Blueprint $table) {
            // Drop the old ENUM column constraint
            // Convert category to VARCHAR to store category slugs
            $table->string('category', 100)->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('menu_items', function (Blueprint $table) {
            // Revert back to ENUM if rolling back
            $table->enum('category', ['breakfast', 'lunch', 'dinner', 'appetizers', 'pizza', 'pasta', 'dessert', 'drinks'])->nullable()->change();
        });
    }
};

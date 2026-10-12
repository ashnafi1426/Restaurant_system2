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
        if (Schema::hasTable('menu_items') && !Schema::hasColumn('menu_items', 'deleted_at')) {
            Schema::table('menu_items', function (Blueprint $table) {
                $table->softDeletes()->after('is_available');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('menu_items') && Schema::hasColumn('menu_items', 'deleted_at')) {
            Schema::table('menu_items', function (Blueprint $table) {
                $table->dropSoftDeletes();
            });
        }
    }
};

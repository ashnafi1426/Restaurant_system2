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
        if (DB::connection()->getDriverName() === 'pgsql') {
            DB::statement("ALTER TABLE notifications ALTER COLUMN type TYPE VARCHAR(100), ALTER COLUMN type SET DEFAULT 'booking'");
            return;
        }
        DB::statement("ALTER TABLE notifications MODIFY type VARCHAR(100) DEFAULT 'booking'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (DB::connection()->getDriverName() === 'pgsql') {
            return;
        }
        DB::statement("ALTER TABLE notifications MODIFY type VARCHAR(100) DEFAULT 'booking'");
    }
};

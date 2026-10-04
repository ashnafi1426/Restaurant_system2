<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::connection()->getDriverName() === 'pgsql') {
            DB::statement("ALTER TABLE menu_items ALTER COLUMN category TYPE VARCHAR(100)");
            return;
        }
        DB::statement("ALTER TABLE menu_items MODIFY category VARCHAR(100)");
    }

    public function down(): void
    {
        if (DB::connection()->getDriverName() === 'pgsql') {
            return;
        }
        DB::statement("ALTER TABLE menu_items MODIFY category VARCHAR(100)");
    }
};

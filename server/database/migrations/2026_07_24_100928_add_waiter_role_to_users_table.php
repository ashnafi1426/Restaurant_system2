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
            return;
        }
        DB::statement("ALTER TABLE users MODIFY COLUMN role VARCHAR(100)");
    }

    public function down(): void
    {
        if (DB::connection()->getDriverName() === 'pgsql') {
            return;
        }
        DB::statement("ALTER TABLE users MODIFY COLUMN role VARCHAR(100)");
    }
};

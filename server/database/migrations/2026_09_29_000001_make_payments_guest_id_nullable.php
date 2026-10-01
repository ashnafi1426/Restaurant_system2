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
        if (Schema::hasTable('payments')) {
            try {
                // Ensure guest_id is nullable on MySQL
                DB::statement("ALTER TABLE `payments` MODIFY `guest_id` CHAR(36) NULL");
            } catch (\Throwable $e) {
                // Fallback using Schema builder
                try {
                    Schema::table('payments', function (Blueprint $table) {
                        $table->uuid('guest_id')->nullable()->change();
                    });
                } catch (\Throwable $e2) {
                    // Ignore if already nullable
                }
            }

            try {
                // Ensure invoice_id is nullable on MySQL
                DB::statement("ALTER TABLE `payments` MODIFY `invoice_id` CHAR(36) NULL");
            } catch (\Throwable $e) {
                try {
                    Schema::table('payments', function (Blueprint $table) {
                        $table->uuid('invoice_id')->nullable()->change();
                    });
                } catch (\Throwable $e2) {}
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Safe no-op
    }
};

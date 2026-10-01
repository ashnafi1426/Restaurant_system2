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
        if (!Schema::hasTable('waiter_table_assignments')) {
            Schema::create('waiter_table_assignments', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->uuid('hotel_id')->nullable()->index();
                $table->unsignedBigInteger('waiter_id')->index();
                $table->uuid('table_id')->index();
                $table->uuid('shift_id')->nullable()->index();
                $table->date('assignment_date')->index();
                $table->string('priority', 50)->default('primary');
                $table->string('status', 50)->default('active')->index();
                $table->uuid('assigned_by')->nullable();
                $table->timestamps();
            });
        }

        if (Schema::hasTable('waiter_table_assignments')) {
            if (!Schema::hasColumn('waiter_table_assignments', 'hotel_id')) {
                Schema::table('waiter_table_assignments', function (Blueprint $table) {
                    $table->uuid('hotel_id')->nullable()->after('id')->index();
                });
            }

            if (!Schema::hasColumn('waiter_table_assignments', 'assigned_by')) {
                Schema::table('waiter_table_assignments', function (Blueprint $table) {
                    $table->uuid('assigned_by')->nullable()->after('status');
                });
            }

            try {
                DB::statement("
                    UPDATE waiter_table_assignments wta
                    INNER JOIN restaurant_tables rt ON wta.table_id = rt.id
                    SET wta.hotel_id = rt.hotel_id
                    WHERE wta.hotel_id IS NULL OR wta.hotel_id = ''
                ");
            } catch (\Throwable $e) {
                // Ignore if driver differences
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('waiter_table_assignments');
    }
};

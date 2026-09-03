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
        Schema::table('reservations', function (Blueprint $table) {
            $table->uuid('cancellation_policy_id')->nullable()->after('room_id');

            // Foreign key
            $table->foreign('cancellation_policy_id')
                ->references('id')
                ->on('cancellation_policies')
                ->onDelete('set null');

            // Index for queries
            $table->index('cancellation_policy_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('reservations', function (Blueprint $table) {
            $table->dropForeignIdFor(\App\Models\CancellationPolicy::class);
            $table->dropColumn('cancellation_policy_id');
        });
    }
};

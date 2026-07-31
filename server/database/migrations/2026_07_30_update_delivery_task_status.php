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
        // Update all old 'assigned' delivery tasks to 'accepted'
        // This ensures backward compatibility with old data
        DB::table('delivery_tasks')
            ->where('status', 'assigned')
            ->update([
                'status' => 'accepted',
                'accepted_at' => DB::raw('COALESCE(accepted_at, assigned_at, NOW())'),
                'updated_at' => now(),
            ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Rollback - convert 'accepted' back to 'assigned' (but only those without delivery progress)
        DB::table('delivery_tasks')
            ->where('status', 'accepted')
            ->where('picked_up_at', null)
            ->where('on_delivery_at', null)
            ->where('delivered_at', null)
            ->update([
                'status' => 'assigned',
                'updated_at' => now(),
            ]);
    }
};

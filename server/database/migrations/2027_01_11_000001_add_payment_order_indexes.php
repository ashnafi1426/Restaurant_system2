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
        if (Schema::hasTable('payments')) {
            Schema::table('payments', function (Blueprint $table) {
                if (Schema::hasColumn('payments', 'order_id')) {
                    if (!Schema::hasIndex('payments', 'payments_order_id_index')) {
                        $table->index('order_id', 'payments_order_id_index');
                    }
                    if (!Schema::hasIndex('payments', 'payments_order_status_index')) {
                        $table->index(['order_id', 'status'], 'payments_order_status_index');
                    }
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('payments')) {
            Schema::table('payments', function (Blueprint $table) {
                try { $table->dropIndex('payments_order_status_index'); } catch (\Throwable $e) {}
                try { $table->dropIndex('payments_order_id_index'); } catch (\Throwable $e) {}
            });
        }
    }
};

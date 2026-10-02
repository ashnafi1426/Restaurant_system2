<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (Schema::hasTable('orders')) {
            Schema::table('orders', function (Blueprint $table) {
                if (!Schema::hasColumn('orders', 'taxable_amount')) {
                    $table->decimal('taxable_amount', 10, 2)->default(0)->after('subtotal');
                }
            });

            try {
                DB::statement("
                    UPDATE orders 
                    SET taxable_amount = subtotal 
                    WHERE taxable_amount = 0 AND subtotal > 0
                ");
            } catch (\Throwable $e) {}
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('orders') && Schema::hasColumn('orders', 'taxable_amount')) {
            Schema::table('orders', function (Blueprint $table) {
                $table->dropColumn('taxable_amount');
            });
        }
    }
};

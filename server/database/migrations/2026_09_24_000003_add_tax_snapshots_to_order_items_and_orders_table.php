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
        Schema::table('order_items', function (Blueprint $table) {
            if (!Schema::hasColumn('order_items', 'tax_rate_id')) {
                $table->uuid('tax_rate_id')->nullable()->after('item_price_at_order');
            }
            if (!Schema::hasColumn('order_items', 'tax_rate')) {
                $table->decimal('tax_rate', 5, 2)->default(0)->after('tax_rate_id');
            }
            if (!Schema::hasColumn('order_items', 'tax_amount')) {
                $table->decimal('tax_amount', 10, 2)->default(0)->after('tax_rate');
            }
            if (!Schema::hasColumn('order_items', 'subtotal')) {
                $table->decimal('subtotal', 10, 2)->default(0)->after('tax_amount');
            }
            if (!Schema::hasColumn('order_items', 'total')) {
                $table->decimal('total', 10, 2)->default(0)->after('subtotal');
            }
        });

        Schema::table('orders', function (Blueprint $table) {
            if (!Schema::hasColumn('orders', 'service_charge_rate')) {
                $table->decimal('service_charge_rate', 5, 2)->default(0)->after('tax');
            }
            if (!Schema::hasColumn('orders', 'service_charge_amount')) {
                $table->decimal('service_charge_amount', 10, 2)->default(0)->after('service_charge_rate');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('order_items', function (Blueprint $table) {
            $table->dropColumn(['tax_rate_id', 'tax_rate', 'tax_amount', 'subtotal', 'total']);
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn(['service_charge_rate', 'service_charge_amount']);
        });
    }
};

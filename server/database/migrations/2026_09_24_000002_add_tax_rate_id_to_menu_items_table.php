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
        Schema::table('menu_items', function (Blueprint $table) {
            $table->uuid('tax_rate_id')->nullable()->after('price');
            $table->boolean('tax_included')->default(false)->after('tax_rate_id');

            $table->foreign('tax_rate_id')
                ->references('id')
                ->on('tax_rates')
                ->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('menu_items', function (Blueprint $table) {
            $table->dropForeign(['tax_rate_id']);
            $table->dropColumn(['tax_rate_id', 'tax_included']);
        });
    }
};

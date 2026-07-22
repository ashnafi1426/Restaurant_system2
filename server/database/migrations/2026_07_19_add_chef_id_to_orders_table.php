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
        Schema::table('orders', function (Blueprint $table) {
            // Check if column doesn't already exist
            if (!Schema::hasColumn('orders', 'chef_id')) {
                $table->uuid('chef_id')->nullable();
                $table->foreign('chef_id')->references('id')->on('users')->onDelete('set null');
                $table->index('chef_id');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            if (Schema::hasColumn('orders', 'chef_id')) {
                $table->dropForeign(['chef_id']);
                $table->dropColumn('chef_id');
            }
        });
    }
};

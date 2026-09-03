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
        if (Schema::hasTable('waiters') && !Schema::hasColumn('waiters', 'bio')) {
            Schema::table('waiters', function (Blueprint $table) {
                $table->text('bio')->nullable();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('waiters', function (Blueprint $table) {
            $table->dropColumn('bio');
        });
    }
};

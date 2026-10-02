<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('floors')) {
            Schema::table('floors', function (Blueprint $table) {
                if (!Schema::hasColumn('floors', 'description')) {
                    $table->text('description')->nullable()->after('name');
                }
                if (!Schema::hasColumn('floors', 'total_rooms')) {
                    $table->integer('total_rooms')->default(0)->after('description');
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('floors')) {
            Schema::table('floors', function (Blueprint $table) {
                if (Schema::hasColumn('floors', 'description')) {
                    $table->dropColumn('description');
                }
                if (Schema::hasColumn('floors', 'total_rooms')) {
                    $table->dropColumn('total_rooms');
                }
            });
        }
    }
};

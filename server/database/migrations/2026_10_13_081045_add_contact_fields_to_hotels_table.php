<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('hotels', function (Blueprint $table) {
            $table->string('reservation_email')->nullable()->after('email');
            $table->string('reservation_phone')->nullable()->after('phone');
            $table->string('front_desk_hours')->nullable()->after('country');
            $table->string('dining_hours')->nullable()->after('front_desk_hours');
        });
    }

    public function down(): void
    {
        Schema::table('hotels', function (Blueprint $table) {
            $table->dropColumn([
                'reservation_email',
                'reservation_phone',
                'front_desk_hours',
                'dining_hours',
            ]);
        });
    }
};

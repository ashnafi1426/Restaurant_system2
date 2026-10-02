<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Create restaurant_sections table
        if (!Schema::hasTable('restaurant_sections')) {
            Schema::create('restaurant_sections', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->uuid('hotel_id')->nullable();
                $table->string('name', 100);
                $table->text('description')->nullable();
                $table->boolean('is_active')->default(true);
                $table->timestamps();

                $table->index(['hotel_id', 'is_active']);
                $table->index('name');
            });
        }

        // 2. Add section_id to restaurant_tables if not exists
        if (Schema::hasTable('restaurant_tables') && !Schema::hasColumn('restaurant_tables', 'section_id')) {
            Schema::table('restaurant_tables', function (Blueprint $table) {
                $table->uuid('section_id')->nullable()->after('location');
                $table->index('section_id');
            });
        }

        // 3. Populate default sections for each hotel and link existing tables
        $hotels = DB::table('hotels')->pluck('id');
        $defaultSectionNames = ['Main Dining', 'Terrace', 'VIP Room', 'Bar Area', 'Outdoor'];

        foreach ($hotels as $hotelId) {
            foreach ($defaultSectionNames as $secName) {
                $exists = DB::table('restaurant_sections')
                    ->where('hotel_id', $hotelId)
                    ->where('name', $secName)
                    ->first();

                if (!$exists) {
                    DB::table('restaurant_sections')->insert([
                        'id' => (string) Str::uuid(),
                        'hotel_id' => $hotelId,
                        'name' => $secName,
                        'description' => "{$secName} section for dining and service",
                        'is_active' => true,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
            }

            // Backfill section_id on restaurant_tables where section or location matches
            $sections = DB::table('restaurant_sections')->where('hotel_id', $hotelId)->get();
            foreach ($sections as $sec) {
                DB::table('restaurant_tables')
                    ->where('hotel_id', $hotelId)
                    ->whereNull('section_id')
                    ->where(function ($q) use ($sec) {
                        $q->where('section', $sec->name)
                          ->orWhere('location', $sec->name);
                    })
                    ->update([
                        'section_id' => $sec->id,
                        'section' => $sec->name,
                    ]);
            }

            // For any remaining tables without section_id, link to Main Dining
            $mainDining = $sections->firstWhere('name', 'Main Dining');
            if ($mainDining) {
                DB::table('restaurant_tables')
                    ->where('hotel_id', $hotelId)
                    ->whereNull('section_id')
                    ->update([
                        'section_id' => $mainDining->id,
                        'section' => $mainDining->name,
                    ]);
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Non-destructive rollback
        if (Schema::hasColumn('restaurant_tables', 'section_id')) {
            Schema::table('restaurant_tables', function (Blueprint $table) {
                $table->dropColumn('section_id');
            });
        }
        Schema::dropIfExists('restaurant_sections');
    }
};

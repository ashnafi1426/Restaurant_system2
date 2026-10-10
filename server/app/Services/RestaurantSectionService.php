<?php

namespace App\Services;

use App\Models\RestaurantSection;
use App\Models\RestaurantTable;
use App\Services\TenantContext;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class RestaurantSectionService
{
    private static bool $schemaChecked = false;

    public function ensureSchema(): void
    {
        if (self::$schemaChecked) {
            return;
        }

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

        if (Schema::hasTable('restaurant_tables') && !Schema::hasColumn('restaurant_tables', 'section_id')) {
            Schema::table('restaurant_tables', function (Blueprint $table) {
                $table->uuid('section_id')->nullable()->after('location');
                $table->index('section_id');
            });
        }

        self::$schemaChecked = true;
    }

    /**
     * Get all sections for a hotel with table counts.
     */
    public function getSections(?string $hotelId = null, bool $activeOnly = false): Collection
    {
        $this->ensureSchema();
        $hotelId = $hotelId ?: TenantContext::id();

        if ($hotelId) {
            $this->seedDefaultSectionsIfNone($hotelId);
        }

        $query = RestaurantSection::query()
            ->withCount('tables')
            ->orderBy('name', 'asc');

        if ($hotelId) {
            $query->where('hotel_id', $hotelId);
        }

        if ($activeOnly) {
            $query->where('is_active', true);
        }

        return $query->get();
    }

    /**
     * Get a single section.
     */
    public function getSection(string $id, ?string $hotelId = null): RestaurantSection
    {
        $this->ensureSchema();
        $hotelId = $hotelId ?: TenantContext::id();

        $query = RestaurantSection::query()->withCount('tables');
        if ($hotelId) {
            $query->where('hotel_id', $hotelId);
        }

        return $query->findOrFail($id);
    }

    /**
     * Create a new restaurant section.
     */
    public function createSection(array $data, ?string $hotelId = null): RestaurantSection
    {
        $this->ensureSchema();
        $hotelId = $hotelId ?: TenantContext::id();

        if (!$hotelId) {
            throw ValidationException::withMessages([
                'hotel_id' => ['Active hotel tenant context is required.'],
            ]);
        }

        $name = trim($data['name']);

        $exists = RestaurantSection::where('hotel_id', $hotelId)
            ->where('name', $name)
            ->exists();

        if ($exists) {
            throw ValidationException::withMessages([
                'name' => ['A section with this name already exists in this hotel.'],
            ]);
        }

        return RestaurantSection::create([
            'hotel_id'    => $hotelId,
            'name'        => $name,
            'description' => $data['description'] ?? null,
            'is_active'   => isset($data['is_active']) ? (bool) $data['is_active'] : true,
        ]);
    }

    /**
     * Update an existing section.
     */
    public function updateSection(RestaurantSection $section, array $data, ?string $hotelId = null): RestaurantSection
    {
        $hotelId = $hotelId ?: $section->hotel_id ?: TenantContext::id();

        if (isset($data['name'])) {
            $name = trim($data['name']);
            if ($name !== $section->name) {
                $conflict = RestaurantSection::where('hotel_id', $hotelId)
                    ->where('name', $name)
                    ->where('id', '!=', $section->id)
                    ->exists();

                if ($conflict) {
                    throw ValidationException::withMessages([
                        'name' => ['A section with this name already exists in this hotel.'],
                    ]);
                }
            }
        }

        DB::transaction(function () use ($section, $data) {
            $oldName = $section->name;

            $section->update([
                'name'        => isset($data['name']) ? trim($data['name']) : $section->name,
                'description' => array_key_exists('description', $data) ? $data['description'] : $section->description,
                'is_active'   => isset($data['is_active']) ? (bool) $data['is_active'] : $section->is_active,
            ]);

            if (isset($data['name']) && trim($data['name']) !== $oldName) {
                RestaurantTable::where('section_id', $section->id)
                    ->update(['section' => trim($data['name'])]);
            }
        });

        return $section->fresh(['tables']);
    }

    /**
     * Delete a section safely.
     */
    public function deleteSection(RestaurantSection $section): void
    {
        DB::transaction(function () use ($section) {

            RestaurantTable::where('section_id', $section->id)
                ->update(['section_id' => null]);

            $section->delete();
        });
    }

    /**
     * Ensure default sections exist for a hotel if none exist.
     */
    public function seedDefaultSectionsIfNone(string $hotelId): void
    {
        $hasSections = RestaurantSection::where('hotel_id', $hotelId)->exists();
        if (!$hasSections) {
            $defaults = [
                ['name' => 'Main Dining', 'description' => 'Main indoor dining room'],
                ['name' => 'Terrace',     'description' => 'Outdoor terrace seating'],
                ['name' => 'VIP Room',    'description' => 'Private dining and VIP tables'],
                ['name' => 'Bar Area',    'description' => 'Cocktail and high-top seating'],
                ['name' => 'Outdoor',     'description' => 'Garden and patio dining'],
            ];

            foreach ($defaults as $sec) {
                RestaurantSection::create([
                    'hotel_id'    => $hotelId,
                    'name'        => $sec['name'],
                    'description' => $sec['description'],
                    'is_active'   => true,
                ]);
            }
        }
    }
}


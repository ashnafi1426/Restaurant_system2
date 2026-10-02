<?php

namespace App\Services;

use App\Models\RestaurantSection;
use App\Models\RestaurantTable;
use App\Services\TenantContext;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class RestaurantTableService
{
    /**
     * Get paginated tables for a hotel with filters applied.
     */
    public function getTables(array $filters = [], int $perPage = 15, ?string $hotelId = null): LengthAwarePaginator
    {
        app(RestaurantSectionService::class)->ensureSchema();
        $hotelId = $hotelId ?: TenantContext::id();

        $query = RestaurantTable::query()->with('restaurantSection');

        if ($hotelId) {
            $query->where('hotel_id', $hotelId);
        }

        if (!empty($filters['search'])) {
            $query->search($filters['search']);
        }

        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (isset($filters['is_active']) && $filters['is_active'] !== null && $filters['is_active'] !== '') {
            $query->where('is_active', filter_var($filters['is_active'], FILTER_VALIDATE_BOOLEAN));
        }

        if (!empty($filters['section_id'])) {
            $query->where('section_id', $filters['section_id']);
        } elseif (!empty($filters['section'])) {
            $query->where(function ($q) use ($filters) {
                $q->where('section', $filters['section'])
                  ->orWhereHas('restaurantSection', fn($sq) => $sq->where('name', $filters['section']));
            });
        }

        if (!empty($filters['location'])) {
            $query->where('location', $filters['location']);
        }

        $sortBy = $filters['sort_by'] ?? 'table_number';
        $sortOrder = strtolower($filters['sort_order'] ?? 'asc') === 'desc' ? 'desc' : 'asc';
        $query->orderBy($sortBy, $sortOrder);

        $paginator = $query->paginate($perPage);

        $paginator->getCollection()->transform(function ($table) {
            $table->qr_code_url = $table->qr_code_url;
            $table->section_name = $table->section_name;
            return $table;
        });

        return $paginator;
    }

    /**
     * Get a single table scoped to the tenant.
     */
    public function getTable(string $id, ?string $hotelId = null): RestaurantTable
    {
        app(RestaurantSectionService::class)->ensureSchema();
        $hotelId = $hotelId ?: TenantContext::id();

        $query = RestaurantTable::query()->with('restaurantSection');

        if ($hotelId) {
            $query->where('hotel_id', $hotelId);
        }

        $table = $query->findOrFail($id);
        $table->qr_code_url = $table->qr_code_url;
        $table->section_name = $table->section_name;

        return $table;
    }

    /**
     * Create a new table with server-generated QR token and strict validation.
     */
    public function createTable(array $data, ?string $hotelId = null): RestaurantTable
    {
        app(RestaurantSectionService::class)->ensureSchema();
        $hotelId = $hotelId ?: TenantContext::id();

        if (!$hotelId) {
            throw ValidationException::withMessages([
                'hotel_id' => ['Active hotel tenant context is required.'],
            ]);
        }

        // Validate table_number uniqueness within hotel
        $existing = RestaurantTable::where('hotel_id', $hotelId)
            ->where('table_number', trim($data['table_number']))
            ->whereNull('deleted_at')
            ->exists();

        if ($existing) {
            throw ValidationException::withMessages([
                'table_number' => ['Table number already exists in this hotel.'],
            ]);
        }

        // Resolve section
        $sectionId = $data['section_id'] ?? null;
        $sectionName = $data['section'] ?? null;

        if ($sectionId) {
            $sec = RestaurantSection::where('hotel_id', $hotelId)->find($sectionId);
            if ($sec) {
                $sectionName = $sec->name;
            }
        } elseif ($sectionName) {
            $sec = RestaurantSection::where('hotel_id', $hotelId)
                ->where('name', trim($sectionName))
                ->first();
            if ($sec) {
                $sectionId = $sec->id;
            }
        }

        return DB::transaction(function () use ($data, $hotelId, $sectionId, $sectionName) {
            $table = RestaurantTable::create([
                'hotel_id'        => $hotelId,
                'table_number'    => trim($data['table_number']),
                'table_name'      => $data['table_name'] ?? null,
                'capacity'        => (int) ($data['capacity'] ?? 4),
                'location'        => $data['location'] ?? $sectionName,
                'section_id'      => $sectionId,
                'section'         => $sectionName,
                'status'          => $data['status'] ?? RestaurantTable::STATUS_AVAILABLE,
                'is_active'       => isset($data['is_active']) ? (bool) $data['is_active'] : true,
            ]);

            $table->refresh();
            $table->load('restaurantSection');
            $table->qr_code_url = $table->qr_code_url;
            $table->section_name = $table->section_name;

            return $table;
        });
    }

    /**
     * Update an existing table.
     */
    public function updateTable(RestaurantTable $table, array $data, ?string $hotelId = null): RestaurantTable
    {
        $hotelId = $hotelId ?: $table->hotel_id ?: TenantContext::id();

        if (isset($data['table_number']) && trim($data['table_number']) !== $table->table_number) {
            $conflict = RestaurantTable::where('hotel_id', $hotelId)
                ->where('table_number', trim($data['table_number']))
                ->where('id', '!=', $table->id)
                ->whereNull('deleted_at')
                ->exists();

            if ($conflict) {
                throw ValidationException::withMessages([
                    'table_number' => ['Table number already exists in this hotel.'],
                ]);
            }
        }

        // Resolve section
        $sectionId = array_key_exists('section_id', $data) ? $data['section_id'] : $table->section_id;
        $sectionName = array_key_exists('section', $data) ? $data['section'] : $table->section;

        if (!empty($data['section_id'])) {
            $sec = RestaurantSection::where('hotel_id', $hotelId)->find($data['section_id']);
            if ($sec) {
                $sectionName = $sec->name;
                $sectionId = $sec->id;
            }
        } elseif (!empty($data['section'])) {
            $sec = RestaurantSection::where('hotel_id', $hotelId)
                ->where('name', trim($data['section']))
                ->first();
            if ($sec) {
                $sectionId = $sec->id;
            }
        }

        $updateData = [];

        if (isset($data['table_number'])) $updateData['table_number'] = trim($data['table_number']);
        if (array_key_exists('table_name', $data)) $updateData['table_name'] = $data['table_name'];
        if (isset($data['capacity'])) $updateData['capacity'] = (int) $data['capacity'];
        if (array_key_exists('location', $data)) $updateData['location'] = $data['location'];
        if (isset($data['status'])) $updateData['status'] = $data['status'];
        if (isset($data['is_active'])) $updateData['is_active'] = (bool) $data['is_active'];

        $updateData['section_id'] = $sectionId;
        $updateData['section'] = $sectionName;

        $table->update($updateData);

        $table->refresh();
        $table->load('restaurantSection');
        $table->qr_code_url = $table->qr_code_url;
        $table->section_name = $table->section_name;

        return $table;
    }

    /**
     * Delete a table safely.
     */
    public function deleteTable(RestaurantTable $table): void
    {
        DB::transaction(function () use ($table) {
            $table->delete();
        });
    }

    /**
     * Regenerate QR code for a table.
     */
    public function regenerateQR(RestaurantTable $table): string
    {
        $table->qr_token = RestaurantTable::generateUniqueToken();
        $table->save();

        return $table->regenerateQRCode();
    }

    /**
     * Get aggregate statistics for tables scoped to hotel.
     */
    public function getStatistics(?string $hotelId = null): array
    {
        $hotelId = $hotelId ?: TenantContext::id();

        $base = RestaurantTable::query();
        if ($hotelId) {
            $base->where('hotel_id', $hotelId);
        }

        $total = (clone $base)->count();
        $active = (clone $base)->where('is_active', true)->count();
        $available = (clone $base)->where('status', RestaurantTable::STATUS_AVAILABLE)->count();
        $occupied = (clone $base)->where('status', RestaurantTable::STATUS_OCCUPIED)->count();
        $reserved = (clone $base)->where('status', RestaurantTable::STATUS_RESERVED)->count();
        $cleaning = (clone $base)->where('status', RestaurantTable::STATUS_CLEANING)->count();
        $outOfService = (clone $base)->where('status', RestaurantTable::STATUS_OUT_OF_SERVICE)->count();

        return [
            'total'          => $total,
            'active'         => $active,
            'available'      => $available,
            'occupied'       => $occupied,
            'reserved'       => $reserved,
            'cleaning'       => $cleaning,
            'out_of_service' => $outOfService,
        ];
    }
}

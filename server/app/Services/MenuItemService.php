<?php

namespace App\Services;

use App\Models\Category;
use App\Models\MenuItem;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class MenuItemService
{
    /**
     * List menu items for the current hotel with filters and pagination.
     */
    public function listMenuItems(array $filters = [], int $perPage = 10): LengthAwarePaginator
    {
        $hotelId = app(TenantContext::class)->getHotelId()
            ?? auth()->user()?->hotel_id
            ?? auth()->user()?->hotelMemberships()->first()?->id;

        $query = MenuItem::with(['taxRate', 'categoryRelation']);

        if ($hotelId) {
            $query->where('hotel_id', $hotelId);
        }

        if (!empty($filters['search'])) {
            $search = trim($filters['search']);
            $query->where(function ($q) use ($search) {
                $q->where('name', 'LIKE', "%{$search}%")
                  ->orWhere('description', 'LIKE', "%{$search}%");
            });
        }

        if (!empty($filters['category'])) {
            $cat = $filters['category'];
            $query->where(function ($q) use ($cat) {
                $q->where('category', $cat)
                  ->orWhere('category_id', $cat)
                  ->orWhereHas('categoryRelation', fn($cq) => $cq->where('slug', $cat)->orWhere('name', $cat));
            });
        }

        if (isset($filters['is_available'])) {
            $query->where('is_available', filter_var($filters['is_available'], FILTER_VALIDATE_BOOLEAN));
        }

        $sortBy = $filters['sort_by'] ?? 'created_at';
        $sortDirection = $filters['sort_direction'] ?? 'desc';
        $allowedSorts = ['name', 'price', 'category', 'created_at'];

        if (!in_array($sortBy, $allowedSorts)) {
            $sortBy = 'created_at';
        }

        $query->orderBy($sortBy, $sortDirection);

        return $query->paginate($perPage);
    }

    /**
     * Create a menu item scoped to the current hotel.
     */
    public function createMenuItem(array $data, ?UploadedFile $imageFile = null): MenuItem
    {
        $hotelId = app(TenantContext::class)->getHotelId()
            ?? auth()->user()?->hotel_id
            ?? auth()->user()?->hotelMemberships()->first()?->id;

        return DB::transaction(function () use ($data, $imageFile, $hotelId) {

            $categorySlug = $data['category'];
            $category = Category::where('hotel_id', $hotelId)
                ->where(function ($q) use ($categorySlug, $data) {
                    $q->where('slug', $categorySlug)
                      ->orWhere('id', $data['category_id'] ?? null)
                      ->orWhere('name', $categorySlug);
                })
                ->first();

            if (!$category) {

                $category = Category::where('slug', $categorySlug)->first();
            }

            $imagePath = null;
            if ($imageFile) {
                $imagePath = $imageFile->store('menu-items', 'public');
            } elseif (!empty($data['image_url'])) {
                $imagePath = $data['image_url'];
            }

            return MenuItem::create([
                'hotel_id' => $hotelId,
                'name' => trim($data['name']),
                'description' => $data['description'] ?? null,
                'category_id' => $category?->id,
                'category' => $category?->slug ?? $categorySlug,
                'price' => (float) $data['price'],
                'tax_rate_id' => $data['tax_rate_id'] ?? null,
                'tax_included' => filter_var($data['tax_included'] ?? false, FILTER_VALIDATE_BOOLEAN),
                'is_available' => filter_var($data['is_available'] ?? true, FILTER_VALIDATE_BOOLEAN),
                'image' => $imagePath,
            ]);

            MenuService::invalidateMenuCache($hotelId);

            return $item;
        });
    }

    /**
     * Update a menu item.
     */
    public function updateMenuItem(MenuItem $menuItem, array $data, ?UploadedFile $imageFile = null): MenuItem
    {
        return DB::transaction(function () use ($menuItem, $data, $imageFile) {

            $categorySlug = $data['category'] ?? $menuItem->category;
            $category = Category::where('hotel_id', $menuItem->hotel_id)
                ->where(function ($q) use ($categorySlug, $data) {
                    $q->where('slug', $categorySlug)
                      ->orWhere('id', $data['category_id'] ?? null)
                      ->orWhere('name', $categorySlug);
                })
                ->first();

            $updateData = [
                'name' => trim($data['name']),
                'description' => $data['description'] ?? $menuItem->description,
                'category_id' => $category?->id ?? $menuItem->category_id,
                'category' => $category?->slug ?? $categorySlug,
                'price' => (float) $data['price'],
                'tax_rate_id' => array_key_exists('tax_rate_id', $data) ? $data['tax_rate_id'] : $menuItem->tax_rate_id,
                'tax_included' => filter_var($data['tax_included'] ?? $menuItem->tax_included, FILTER_VALIDATE_BOOLEAN),
                'is_available' => filter_var($data['is_available'] ?? $menuItem->is_available, FILTER_VALIDATE_BOOLEAN),
            ];

            if ($imageFile) {
                $this->deleteStoredImage($menuItem->image);
                $updateData['image'] = $imageFile->store('menu-items', 'public');
            } elseif (!empty($data['image_url']) && $data['image_url'] !== $menuItem->image) {
                $this->deleteStoredImage($menuItem->image);
                $updateData['image'] = $data['image_url'];
            }

            $menuItem->update($updateData);

            MenuService::invalidateMenuCache($menuItem->hotel_id);

            return $menuItem->fresh(['taxRate', 'categoryRelation']);
        });
    }

    /**
     * Delete a menu item and its stored image.
     */
    public function deleteMenuItem(MenuItem $menuItem): void
    {
        DB::transaction(function () use ($menuItem) {
            $hotelId = $menuItem->hotel_id;
            $this->deleteStoredImage($menuItem->image);
            $menuItem->delete();
            MenuService::invalidateMenuCache($hotelId);
        });
    }

    /**
     * Toggle availability status of a menu item.
     */
    public function toggleAvailability(MenuItem $menuItem): MenuItem
    {
        $menuItem->update([
            'is_available' => !$menuItem->is_available,
        ]);

        MenuService::invalidateMenuCache($menuItem->hotel_id);

        return $menuItem->fresh(['taxRate', 'categoryRelation']);
    }

    /**
     * Calculate menu item statistics for the current hotel.
     */
    public function getStatistics(): array
    {
        $hotelId = app(TenantContext::class)->getHotelId()
            ?? auth()->user()?->hotel_id
            ?? auth()->user()?->hotelMemberships()->first()?->id;

        $query = MenuItem::query();
        if ($hotelId) {
            $query->where('hotel_id', $hotelId);
        }

        $total = (clone $query)->count();
        $available = (clone $query)->where('is_available', true)->count();
        $unavailable = (clone $query)->where('is_available', false)->count();

        return [
            'total_items' => $total,
            'available_items' => $available,
            'unavailable_items' => $unavailable,
            'breakfast_items' => (clone $query)->where('category', 'breakfast')->count(),
            'lunch_items' => (clone $query)->where('category', 'lunch')->count(),
            'dinner_items' => (clone $query)->where('category', 'dinner')->count(),
            'drink_items' => (clone $query)->whereIn('category', ['drinks', 'beverages'])->count(),
            'dessert_items' => (clone $query)->where('category', 'dessert')->count(),
        ];
    }

    /**
     * Delete an existing local image from the public disk.
     */
    protected function deleteStoredImage(?string $image): void
    {
        if ($image && !filter_var($image, FILTER_VALIDATE_URL)) {
            try {
                Storage::disk('public')->delete($image);
            } catch (\Throwable $e) {
            }
        }
    }
}


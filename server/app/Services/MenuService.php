<?php

namespace App\Services;

use App\Models\Category;
use App\Models\MenuItem;
use App\Models\Scopes\TenantScope;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class MenuService
{
    /**
     * Get menu items for guest view, grouped by category.
     */
    public function getCategorizedMenuItems(?string $hotelId): Collection
    {
        if ($hotelId) {
            $this->ensureHotelHasMenuAndCategories($hotelId);
        }

        $query = MenuItem::withoutGlobalScope(TenantScope::class)
            ->with('taxRate')
            ->where('is_available', true);

        if ($hotelId) {
            $query->where(function ($q) use ($hotelId) {
                $q->where('hotel_id', $hotelId)
                  ->orWhereNull('hotel_id');
            });
        }

        $menuItems = $query->orderBy('category')->orderBy('name')->get();

        // Fallback to global items if hotel has none yet
        if ($menuItems->isEmpty() && $hotelId) {
            $menuItems = MenuItem::withoutGlobalScope(TenantScope::class)
                ->with('taxRate')
                ->where('is_available', true)
                ->orderBy('category')
                ->orderBy('name')
                ->get();
        }

        return $menuItems->groupBy('category')
            ->map(fn($items, $category) => [
                'category' => $category,
                'items' => $items->map(fn($item) => $this->formatMenuItemForGuest($item))->values(),
            ])
            ->values();
    }

    /**
     * Get all active categories with counts for a hotel (without N+1 queries).
     */
    public function getCategoriesWithCounts(?string $hotelId): array
    {
        if ($hotelId) {
            $this->ensureHotelHasMenuAndCategories($hotelId);
        }

        // 1. Get counts grouped by category slug/name in 1 single aggregate query
        $itemCountQuery = MenuItem::withoutGlobalScope(TenantScope::class)
            ->where('is_available', true)
            ->selectRaw('category, category_id, COUNT(*) as item_count');

        if ($hotelId) {
            $itemCountQuery->where(function ($q) use ($hotelId) {
                $q->where('hotel_id', $hotelId)->orWhereNull('hotel_id');
            });
        }

        $itemCounts = $itemCountQuery->groupBy('category', 'category_id')->get();

        $countByCatId = [];
        $countBySlug = [];
        foreach ($itemCounts as $row) {
            if ($row->category_id) {
                $countByCatId[$row->category_id] = ($countByCatId[$row->category_id] ?? 0) + $row->item_count;
            }
            if ($row->category) {
                $slug = Str::slug($row->category);
                $countBySlug[$slug] = ($countBySlug[$slug] ?? 0) + $row->item_count;
            }
        }

        // 2. Query categories
        $catQuery = Category::withoutGlobalScope(TenantScope::class)->where('is_active', true);
        if ($hotelId) {
            $catQuery->where(function ($q) use ($hotelId) {
                $q->where('hotel_id', $hotelId)->orWhereNull('hotel_id');
            });
        }
        $dbCategories = $catQuery->orderBy('display_order')->get();

        if ($dbCategories->isEmpty()) {
            $dbCategories = Category::withoutGlobalScope(TenantScope::class)
                ->where('is_active', true)
                ->orderBy('display_order')
                ->get();
        }

        $resultMap = [];

        foreach ($dbCategories as $category) {
            $slug = $category->slug ?: Str::slug($category->name);
            $cnt = $countByCatId[$category->id] ?? $countBySlug[$slug] ?? 0;

            $icon = $category->icon;
            if (!$icon || in_array($icon, ['grid', 'menu'])) {
                $icon = self::guessCategoryIcon($slug);
            }

            $resultMap[$slug] = [
                'id' => $category->id ?: $slug,
                'name' => $category->name,
                'slug' => $slug,
                'icon' => $icon,
                'count' => $cnt,
            ];
        }

        // Fallback for distinct item categories not in categories table
        foreach ($countBySlug as $slug => $cnt) {
            if (!isset($resultMap[$slug])) {
                $resultMap[$slug] = [
                    'id' => $slug,
                    'name' => ucwords(str_replace('-', ' ', $slug)),
                    'slug' => $slug,
                    'icon' => self::guessCategoryIcon($slug),
                    'count' => $cnt,
                ];
            }
        }

        return array_values($resultMap);
    }

    /**
     * Format a single menu item with price and tax calculations for guests.
     */
    public function formatMenuItemForGuest($item): array
    {
        $imageUrl = null;
        if ($item->image) {
            $imageUrl = filter_var($item->image, FILTER_VALIDATE_URL)
                ? $item->image
                : asset('storage/' . $item->image);
        }

        $price = (float) $item->price;
        $taxRateModel = $item->relationLoaded('taxRate') ? $item->taxRate : $item->taxRate;
        $rate = $taxRateModel ? (float) $taxRateModel->rate : 0.0;
        $taxIncluded = (bool) ($item->tax_included ?? false);

        if ($rate > 0) {
            if ($taxIncluded) {
                $basePrice = round($price / (1 + ($rate / 100)), 2);
                $taxAmount = round($price - $basePrice, 2);
                $totalPrice = $price;
            } else {
                $basePrice = $price;
                $taxAmount = round($price * ($rate / 100), 2);
                $totalPrice = round($price + $taxAmount, 2);
            }
        } else {
            $basePrice = $price;
            $taxAmount = 0.0;
            $totalPrice = $price;
        }

        return [
            'id' => $item->id,
            'name' => $item->name,
            'description' => $item->description,
            'price' => $price,
            'base_price' => $basePrice,
            'tax_amount' => $taxAmount,
            'total_price' => $totalPrice,
            'formatted_price' => number_format($price, 2),
            'formatted_total_price' => number_format($totalPrice, 2),
            'tax_rate_id' => $item->tax_rate_id,
            'tax_included' => $taxIncluded,
            'tax_rate' => $taxRateModel ? [
                'id' => $taxRateModel->id,
                'name' => $taxRateModel->name,
                'rate' => (float) $taxRateModel->rate,
                'type' => $taxRateModel->type,
            ] : null,
            'image' => $imageUrl,
            'category' => $item->category,
            'is_available' => (bool) $item->is_available,
        ];
    }

    /**
     * Map category names or slugs to intuitive UI icons.
     */
    public static function guessCategoryIcon(?string $nameOrSlug): string
    {
        $key = strtolower(trim(str_replace([' ', '_'], '-', $nameOrSlug ?? '')));
        return match (true) {
            str_contains($key, 'breakfast') || str_contains($key, 'morning') => 'clock',
            str_contains($key, 'soup') => 'soup',
            str_contains($key, 'appetizer') || str_contains($key, 'starter') => 'leaf',
            str_contains($key, 'salad') => 'salad',
            str_contains($key, 'main') || str_contains($key, 'entree') => 'utensils',
            str_contains($key, 'sandwich') || str_contains($key, 'burger') => 'sandwich',
            str_contains($key, 'pasta') || str_contains($key, 'noodle') => 'layers',
            str_contains($key, 'pizza') => 'pizza',
            str_contains($key, 'dessert') || str_contains($key, 'sweet') || str_contains($key, 'cake') => 'cake',
            str_contains($key, 'drink') || str_contains($key, 'beverage') || str_contains($key, 'wine') || str_contains($key, 'bar') || str_contains($key, 'coffee') => 'wine',
            default => 'utensils',
        };
    }

    /**
     * Seed initial menu categories and items for newly created hotels.
     */
    public function ensureHotelHasMenuAndCategories(?string $hotelId): void
    {
        if (!$hotelId) return;

        try {
            $catCount = Category::withoutGlobalScope(TenantScope::class)
                ->where('hotel_id', $hotelId)
                ->count();

            if ($catCount === 0) {
                $standards = [
                    ['name' => 'Breakfast', 'slug' => 'breakfast', 'icon' => 'clock', 'description' => 'Morning delicacies to start your day', 'display_order' => 1],
                    ['name' => 'Soups', 'slug' => 'soups', 'icon' => 'soup', 'description' => 'Hearty and comforting soups', 'display_order' => 2],
                    ['name' => 'Appetizers', 'slug' => 'appetizers', 'icon' => 'leaf', 'description' => 'Delicious starters and small plates', 'display_order' => 3],
                    ['name' => 'Main Courses', 'slug' => 'main-courses', 'icon' => 'utensils', 'description' => 'Signature dishes and entrees', 'display_order' => 4],
                    ['name' => 'Sandwiches', 'slug' => 'sandwiches', 'icon' => 'sandwich', 'description' => 'Gourmet burgers and sandwiches', 'display_order' => 5],
                    ['name' => 'Pasta', 'slug' => 'pasta', 'icon' => 'layers', 'description' => 'Handcrafted pasta specialties', 'display_order' => 6],
                    ['name' => 'Desserts', 'slug' => 'desserts', 'icon' => 'cake', 'description' => 'Sweet treats and decadent desserts', 'display_order' => 7],
                    ['name' => 'Beverages', 'slug' => 'beverages', 'icon' => 'wine', 'description' => 'Refreshing beverages and drinks', 'display_order' => 8],
                ];

                foreach ($standards as $std) {
                    Category::withoutGlobalScope(TenantScope::class)->firstOrCreate(
                        ['hotel_id' => $hotelId, 'slug' => $std['slug']],
                        [
                            'id' => (string) Str::uuid(),
                            'name' => $std['name'],
                            'description' => $std['description'],
                            'icon' => $std['icon'],
                            'display_order' => $std['display_order'],
                            'is_active' => true,
                        ]
                    );
                }
            }
        } catch (\Throwable $e) {
            Log::warning('[MENU SERVICE] Could not auto-seed categories: ' . $e->getMessage());
        }
    }
}

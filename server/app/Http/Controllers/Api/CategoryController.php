<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CategoryController extends Controller
{
    public function __construct()
    {
        // Allow unauthenticated access to index method for public QR menu
        // All other methods are protected by route middleware
    }

    /**
     * Get all categories (PUBLIC - No authentication required)
     */
    public function index(Request $request)
    {
        $query = Category::query();

        // Filter by active status
        if ($request->filled('is_active')) {
            $query->where('is_active', filter_var($request->is_active, FILTER_VALIDATE_BOOLEAN));
        }

        // Search by name
        if ($request->filled('search')) {
            $search = trim($request->search);
            $query->where(function ($q) use ($search) {
                $q->where('name', 'LIKE', "%{$search}%")
                  ->orWhere('description', 'LIKE', "%{$search}%");
            });
        }

        // Get with count of menu items
        $categories = $query->withCount('menuItems')->get();

        if ($categories->count() < 8) {
            $defaults = [
                ['name' => 'Breakfast', 'slug' => 'breakfast', 'icon' => 'clock', 'display_order' => 1, 'is_active' => true],
                ['name' => 'Soups', 'slug' => 'soups', 'icon' => 'soup', 'display_order' => 2, 'is_active' => true],
                ['name' => 'Appetizers', 'slug' => 'appetizers', 'icon' => 'leaf', 'display_order' => 3, 'is_active' => true],
                ['name' => 'Main Courses', 'slug' => 'main-courses', 'icon' => 'utensils', 'display_order' => 4, 'is_active' => true],
                ['name' => 'Sandwiches', 'slug' => 'sandwiches', 'icon' => 'sandwich', 'display_order' => 5, 'is_active' => true],
                ['name' => 'Pasta', 'slug' => 'pasta', 'icon' => 'layers', 'display_order' => 6, 'is_active' => true],
                ['name' => 'Desserts', 'slug' => 'desserts', 'icon' => 'cake', 'display_order' => 7, 'is_active' => true],
                ['name' => 'Beverages', 'slug' => 'beverages', 'icon' => 'wine', 'display_order' => 8, 'is_active' => true],
            ];

            foreach ($defaults as $cat) {
                Category::firstOrCreate(['slug' => $cat['slug']], $cat);
            }

            $categories = Category::orderBy('display_order', 'asc')->orderBy('name', 'asc')->withCount('menuItems')->get();
        }

        return response()->json([
            'success' => true,
            'data' => $categories,
        ]);
    }

    /**
     * Store a new category
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:100|unique:categories,name',
            'description' => 'nullable|string|max:500',
            'icon' => 'nullable|string|max:50',
            'display_order' => 'nullable|integer|min:0',
            'is_active' => 'nullable|boolean',
        ]);

        DB::beginTransaction();

        try {
            $category = Category::create([
                'name' => $validated['name'],
                'slug' => Str::slug($validated['name']),
                'description' => $validated['description'] ?? null,
                'icon' => $validated['icon'] ?? null,
                'display_order' => $validated['display_order'] ?? 0,
                'is_active' => $validated['is_active'] ?? true,
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Category created successfully.',
                'data' => $category,
            ], 201);

        } catch (\Throwable $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Failed to create category.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get a single category
     */
    public function show(Category $category)
    {
        $category->loadCount('menuItems');

        return response()->json([
            'success' => true,
            'data' => $category,
        ]);
    }

    /**
     * Update a category
     */
    public function update(Request $request, Category $category)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:100|unique:categories,name,' . $category->id . ',id',
            'description' => 'nullable|string|max:500',
            'icon' => 'nullable|string|max:50',
            'display_order' => 'nullable|integer|min:0',
            'is_active' => 'nullable|boolean',
        ]);

        DB::beginTransaction();

        try {
            $category->update([
                'name' => $validated['name'],
                'description' => $validated['description'] ?? $category->description,
                'icon' => $validated['icon'] ?? $category->icon,
                'display_order' => $validated['display_order'] ?? $category->display_order,
                'is_active' => $validated['is_active'] ?? $category->is_active,
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Category updated successfully.',
                'data' => $category->fresh()->loadCount('menuItems'),
            ]);

        } catch (\Throwable $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Failed to update category.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Delete a category
     */
    public function destroy(Category $category)
    {
        DB::beginTransaction();

        try {
            // Check if category has menu items
            if ($category->menuItems()->count() > 0) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot delete category with existing menu items. Please reassign or delete menu items first.',
                ], 422);
            }

            $category->delete();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Category deleted successfully.',
            ]);

        } catch (\Throwable $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Failed to delete category.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Toggle category active status
     */
    public function toggle(Category $category)
    {
        DB::beginTransaction();

        try {
            $category->update([
                'is_active' => !$category->is_active,
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => $category->is_active 
                    ? 'Category is now active.' 
                    : 'Category has been deactivated.',
                'data' => $category,
            ]);

        } catch (\Throwable $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Failed to toggle category status.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Reorder categories
     */
    public function reorder(Request $request)
    {
        $validated = $request->validate([
            'categories' => 'required|array',
            'categories.*.id' => 'required|uuid|exists:categories,id',
            'categories.*.display_order' => 'required|integer|min:0',
        ]);

        DB::beginTransaction();

        try {
            foreach ($validated['categories'] as $item) {
                Category::find($item['id'])->update([
                    'display_order' => $item['display_order'],
                ]);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Categories reordered successfully.',
            ]);

        } catch (\Throwable $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Failed to reorder categories.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}

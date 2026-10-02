<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreCategoryRequest;
use App\Http\Requests\UpdateCategoryRequest;
use App\Models\Category;
use App\Services\CategoryService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Throwable;

class CategoryController extends Controller
{
    public function __construct(
        protected CategoryService $categoryService
    ) {}

    /**
     * Display a listing of categories for the current hotel.
     */
    public function index(Request $request): JsonResponse
    {
        $categories = $this->categoryService->listCategories($request->only(['is_active', 'search']));

        return response()->json([
            'success' => true,
            'data' => $categories,
        ]);
    }

    /**
     * Store a newly created category in storage.
     */
    public function store(StoreCategoryRequest $request): JsonResponse
    {
        try {
            $category = $this->categoryService->createCategory($request->validated());

            return response()->json([
                'success' => true,
                'message' => trans_msg('category_created', default: 'Category created successfully.'),
                'data' => $category,
            ], 201);
        } catch (Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create category: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Display the specified category.
     */
    public function show(Category $category): JsonResponse
    {
        $category->loadCount('menuItems');

        return response()->json([
            'success' => true,
            'data' => $category,
        ]);
    }

    /**
     * Update the specified category in storage.
     */
    public function update(UpdateCategoryRequest $request, Category $category): JsonResponse
    {
        try {
            $updated = $this->categoryService->updateCategory($category, $request->validated());

            return response()->json([
                'success' => true,
                'message' => trans_msg('category_updated', default: 'Category updated successfully.'),
                'data' => $updated,
            ]);
        } catch (Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update category: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Remove the specified category from storage.
     */
    public function destroy(Category $category): JsonResponse
    {
        try {
            $this->categoryService->deleteCategory($category);

            return response()->json([
                'success' => true,
                'message' => trans_msg('category_deleted', default: 'Category deleted successfully.'),
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->validator->errors()->first() ?: 'Cannot delete category.',
                'errors' => $e->errors(),
            ], 422);
        } catch (Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete category: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Toggle category active status.
     */
    public function toggle(Category $category): JsonResponse
    {
        try {
            $updated = $this->categoryService->toggleCategory($category);

            return response()->json([
                'success' => true,
                'message' => $updated->is_active 
                    ? 'Category is now active.' 
                    : 'Category has been deactivated.',
                'data' => $updated,
            ]);
        } catch (Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to toggle category: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Reorder categories.
     */
    public function reorder(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'categories' => 'required|array',
            'categories.*.id' => 'required|uuid|exists:categories,id',
            'categories.*.display_order' => 'required|integer|min:0',
        ]);

        try {
            $this->categoryService->reorderCategories($validated['categories']);

            return response()->json([
                'success' => true,
                'message' => 'Categories reordered successfully.',
            ]);
        } catch (Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to reorder categories: ' . $e->getMessage(),
            ], 500);
        }
    }
}

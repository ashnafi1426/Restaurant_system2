<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreMenuItemRequest;
use App\Http\Requests\UpdateMenuItemRequest;
use App\Http\Resources\MenuItemResource;
use App\Models\MenuItem;
use App\Services\MenuItemService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Throwable;

class MenuItemController extends Controller
{
    public function __construct(
        protected MenuItemService $menuItemService
    ) {}

    /**
     * Display a listing of menu items for the current hotel.
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $perPage = (int) $request->get('per_page', 10);
        $filters = $request->only(['search', 'category', 'is_available', 'sort_by', 'sort_direction']);

        $menuItems = $this->menuItemService->listMenuItems($filters, $perPage);

        return MenuItemResource::collection($menuItems);
    }

    /**
     * Store a newly created menu item in storage.
     */
    public function store(StoreMenuItemRequest $request): JsonResponse
    {
        try {
            $menuItem = $this->menuItemService->createMenuItem(
                $request->validated(),
                $request->file('image')
            );

            return response()->json([
                'success' => true,
                'message' => 'Menu item created successfully.',
                'data' => new MenuItemResource($menuItem),
            ], 201);
        } catch (Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create menu item: ' . $e->getMessage(),
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Display the specified menu item.
     */
    public function show(MenuItem $menuItem): JsonResponse
    {
        $menuItem->loadMissing(['taxRate', 'categoryRelation']);

        return response()->json([
            'success' => true,
            'message' => 'Menu item retrieved successfully.',
            'data' => new MenuItemResource($menuItem),
        ]);
    }

    /**
     * Update the specified menu item in storage.
     */
    public function update(UpdateMenuItemRequest $request, MenuItem $menuItem): JsonResponse
    {
        try {
            $updated = $this->menuItemService->updateMenuItem(
                $menuItem,
                $request->validated(),
                $request->file('image')
            );

            return response()->json([
                'success' => true,
                'message' => 'Menu item updated successfully.',
                'data' => new MenuItemResource($updated),
            ]);
        } catch (Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update menu item: ' . $e->getMessage(),
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Remove the specified menu item from storage.
     */
    public function destroy(MenuItem $menuItem): JsonResponse
    {
        try {
            $this->menuItemService->deleteMenuItem($menuItem);

            return response()->json([
                'success' => true,
                'message' => 'Menu item deleted successfully.',
            ]);
        } catch (Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete menu item: ' . $e->getMessage(),
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Toggle availability status of a menu item.
     */
    public function toggleAvailability(MenuItem $menuItem): JsonResponse
    {
        try {
            $updated = $this->menuItemService->toggleAvailability($menuItem);

            return response()->json([
                'success' => true,
                'message' => $updated->is_available
                    ? 'Menu item is now available.'
                    : 'Menu item has been disabled.',
                'data' => new MenuItemResource($updated),
            ]);
        } catch (Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update menu availability: ' . $e->getMessage(),
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get statistics for menu items in current hotel.
     */
    public function statistics(): JsonResponse
    {
        $stats = $this->menuItemService->getStatistics();

        return response()->json([
            'success' => true,
            'data' => $stats,
        ]);
    }
}
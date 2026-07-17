<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreMenuItemRequest;
use App\Http\Requests\UpdateMenuItemRequest;
use App\Http\Resources\MenuItemResource;
use App\Models\MenuItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class MenuItemController extends Controller
{
   
    public function index(Request $request)
    {
        $query = MenuItem::query();
        if ($request->filled('search')) {

            $search = trim($request->search);

            $query->where(function ($q) use ($search) {

                $q->where('name', 'LIKE', "%{$search}%")
                  ->orWhere('description', 'LIKE', "%{$search}%");

            });
        }

        /*
        |--------------------------------------------------------------------------
        | Filter by Category
        |--------------------------------------------------------------------------
        */

        if ($request->filled('category')) {

            $query->where('category', $request->category);

        }

        /*
        |--------------------------------------------------------------------------
        | Filter by Availability
        |--------------------------------------------------------------------------
        */

        if ($request->filled('is_available')) {

            $query->where(
                'is_available',
                filter_var($request->is_available, FILTER_VALIDATE_BOOLEAN)
            );

        }

        /*
        |--------------------------------------------------------------------------
        | Sorting
        |--------------------------------------------------------------------------
        */

        $sortBy = $request->get('sort_by', 'created_at');

        $sortDirection = $request->get('sort_direction', 'desc');

        $allowedSorts = [

            'name',

            'price',

            'category',

            'created_at',

        ];

        if (! in_array($sortBy, $allowedSorts)) {

            $sortBy = 'created_at';

        }

        $query->orderBy($sortBy, $sortDirection);

        /*
        |--------------------------------------------------------------------------
        | Pagination
        |--------------------------------------------------------------------------
        */

        $perPage = $request->get('per_page', 10);

        $menuItems = $query->paginate($perPage);

        return MenuItemResource::collection($menuItems);
    }

    /**
     * Store a newly created menu item.
     */
    public function store(StoreMenuItemRequest $request)
    {
        DB::beginTransaction();

        try {
            \Log::info('🟢 Store request received', [
                'name' => $request->name,
                'category' => $request->category,
                'has_image' => $request->hasFile('image'),
                'has_image_url' => $request->filled('image_url'),
                'all_input' => $request->all(),
            ]);

            $data = [
                'name' => $request->name,
                'description' => $request->description,
                'category' => $request->category,
                'price' => (float) $request->price,
                'is_available' => $request->boolean('is_available', true),
            ];

            \Log::info('📝 Data prepared for creation', $data);

            // Handle image upload or URL
            if ($request->hasFile('image')) {
                \Log::info('📸 Image file detected', [
                    'size' => $request->file('image')->getSize(),
                    'mime' => $request->file('image')->getMimeType(),
                    'original_name' => $request->file('image')->getClientOriginalName(),
                ]);

                try {
                    $path = $request->file('image')->store(
                        'menu-items',
                        'public'
                    );

                    \Log::info('✅ Image stored successfully', [
                        'path' => $path,
                    ]);

                    $data['image'] = $path;
                } catch (\Exception $e) {
                    \Log::error('❌ Image upload failed', [
                        'error' => $e->getMessage(),
                        'trace' => $e->getTraceAsString(),
                    ]);
                    throw $e;
                }
            } elseif ($request->filled('image_url')) {
                \Log::info('🔗 Image URL provided', [
                    'url' => $request->image_url,
                ]);

                $data['image'] = $request->image_url;
            } else {
                \Log::error('❌ No image source provided');
                throw new \Exception('Either upload an image file or provide an image URL.');
            }

            \Log::info('🔍 Attempting to create MenuItem with:', $data);

            $menuItem = MenuItem::create($data);

            \Log::info('✅ MenuItem created successfully', [
                'id' => $menuItem->id,
                'name' => $menuItem->name,
                'image' => $menuItem->image,
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Menu item created successfully.',
                'data' => new MenuItemResource($menuItem),
            ], 201);

        } catch (\Illuminate\Validation\ValidationException $e) {
            DB::rollBack();
            \Log::error('❌ Validation Error', [
                'errors' => $e->errors(),
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Validation failed.',
                'errors' => $e->errors(),
            ], 422);

        } catch (\Throwable $e) {
            DB::rollBack();

            \Log::error('❌ Store failed - Unexpected Error', [
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to create menu item: ' . $e->getMessage(),
                'error' => $e->getMessage(),
                'details' => config('app.debug') ? [
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                    'trace' => explode("\n", $e->getTraceAsString()),
                ] : null,
            ], 500);
        }
    }
        /**
     * Display the specified menu item.
     */
    public function show(MenuItem $menuItem)
    {
        return response()->json([

            'success' => true,

            'message' => 'Menu item retrieved successfully.',

            'data' => new MenuItemResource($menuItem),

        ]);
    }

    /**
     * Update the specified menu item.
     */
    public function update(
        UpdateMenuItemRequest $request,
        MenuItem $menuItem
    ) {
        DB::beginTransaction();

        try {

            $data = [

                'name' => $request->name,

                'description' => $request->description,

                'category' => $request->category,

                'price' => $request->price,

                'is_available' => $request->boolean('is_available'),

            ];

            // Handle image upload or URL
            if ($request->hasFile('image')) {

                // Delete old image if exists and is a local file (not a URL)
                if ($menuItem->image && !filter_var($menuItem->image, FILTER_VALIDATE_URL)) {

                    Storage::disk('public')->delete($menuItem->image);

                }

                $path = $request->file('image')->store(
                    'menu-items',
                    'public'
                );

                $data['image'] = $path;

            } elseif ($request->filled('image_url')) {

                // Delete old local image if exists
                if ($menuItem->image && !filter_var($menuItem->image, FILTER_VALIDATE_URL)) {

                    Storage::disk('public')->delete($menuItem->image);

                }

                $data['image'] = $request->image_url;

            }

            $menuItem->update($data);

            DB::commit();

            return response()->json([

                'success' => true,

                'message' => 'Menu item updated successfully.',

                'data' => new MenuItemResource(
                    $menuItem->fresh()
                ),

            ]);

        } catch (\Throwable $e) {

            DB::rollBack();

            return response()->json([

                'success' => false,

                'message' => 'Failed to update menu item.',

                'error' => $e->getMessage(),

            ], 500);

        }
    }

    /**
     * Remove the specified menu item.
     */
    public function destroy(MenuItem $menuItem)
    {
        DB::beginTransaction();

        try {
            \Log::info('🗑️ Deleting menu item', ['id' => $menuItem->id, 'name' => $menuItem->name]);

            // Delete image if exists and is a local file (not a URL)
            if ($menuItem->image && !filter_var($menuItem->image, FILTER_VALIDATE_URL)) {
                \Log::info('🖼️ Deleting local image', ['path' => $menuItem->image]);
                Storage::disk('public')->delete($menuItem->image);
            }

            $menuItem->delete();

            DB::commit();
            \Log::info('✅ Menu item deleted successfully', ['id' => $menuItem->id]);

            return response()->json([

                'success' => true,

                'message' => 'Menu item deleted successfully.',

            ]);

        } catch (\Throwable $e) {

            DB::rollBack();
            \Log::error('❌ Error deleting menu item', ['id' => $menuItem->id, 'error' => $e->getMessage()]);

            return response()->json([

                'success' => false,

                'message' => 'Failed to delete menu item.',

                'error' => $e->getMessage(),

            ], 500);

        }
    }
        /**
     * Toggle menu item availability.
     */
    public function toggleAvailability(MenuItem $menuItem)
    {
        DB::beginTransaction();

        try {

            $menuItem->update([

                'is_available' => ! $menuItem->is_available,

            ]);

            DB::commit();

            return response()->json([

                'success' => true,

                'message' => $menuItem->is_available
                    ? 'Menu item is now available.'
                    : 'Menu item has been disabled.',

                'data' => new MenuItemResource(
                    $menuItem->fresh()
                ),

            ]);

        } catch (\Throwable $e) {

            DB::rollBack();

            return response()->json([

                'success' => false,

                'message' => 'Failed to update menu availability.',

                'error' => $e->getMessage(),

            ], 500);

        }
    }

    /**
     * Menu statistics.
     */
    public function statistics()
    {
        return response()->json([

            'success' => true,

            'data' => [

                'total_items' => MenuItem::count(),

                'available_items' => MenuItem::where(
                    'is_available',
                    true
                )->count(),

                'unavailable_items' => MenuItem::where(
                    'is_available',
                    false
                )->count(),

                'breakfast_items' => MenuItem::where(
                    'category',
                    'breakfast'
                )->count(),

                'lunch_items' => MenuItem::where(
                    'category',
                    'lunch'
                )->count(),

                'dinner_items' => MenuItem::where(
                    'category',
                    'dinner'
                )->count(),

                'drink_items' => MenuItem::where(
                    'category',
                    'drinks'
                )->count(),

                'dessert_items' => MenuItem::where(
                    'category',
                    'dessert'
                )->count(),

            ],

        ]);
    }
}
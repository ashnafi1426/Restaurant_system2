<?php

namespace App\Http\Controllers\Api\Manager;

use App\Http\Controllers\Controller;
use App\Models\RestaurantTable;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class RestaurantTableController extends Controller
{
    /**
     * Get all restaurant tables with pagination and filtering
     */
    public function index(Request $request): JsonResponse
    {
        try {
            Log::info('🔵 RestaurantTable::index called', [
                'all_params' => $request->all(),
                'query_params' => $request->query(),
                'user_id' => auth()->id(),
                'user_role' => auth()->user()->role ?? 'N/A',
            ]);
            
            $query = RestaurantTable::query();

            // Search filter
            if ($request->filled('search')) {
                $query->search($request->search);
            }

            // Status filter
            if ($request->filled('status')) {
                $query->where('status', $request->status);
            }

            // Active filter
            if ($request->has('is_active') && $request->is_active !== null) {
                $query->where('is_active', $request->boolean('is_active'));
            }

            // Location filter
            if ($request->filled('location')) {
                $query->where('location', $request->location);
            }

            // Sorting
            $sortBy = $request->get('sort_by', 'table_number');
            $sortOrder = $request->get('sort_order', 'asc');
            $query->orderBy($sortBy, $sortOrder);

            // Pagination
            $perPage = $request->get('per_page', 15);
            $tables = $query->paginate($perPage);
            
            Log::info('🔵 Query executed', [
                'total' => $tables->total(),
                'count' => $tables->count(),
                'per_page' => $tables->perPage(),
                'current_page' => $tables->currentPage(),
            ]);

            // Add QR code URLs to each table
            $tables->getCollection()->transform(function ($table) {
                $table->qr_code_url = $table->qr_code_url;
                return $table;
            });
            
            Log::info('🔵 Returning response', [
                'success' => true,
                'total' => $tables->total(),
                'data_count' => count($tables->items()),
            ]);

            return response()->json([
                'success' => true,
                'data' => $tables,
            ], 200);

        } catch (\Exception $e) {
            Log::error('Failed to fetch restaurant tables', [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch restaurant tables',
            ], 500);
        }
    }

    /**
     * Get a single restaurant table by ID
     */
    public function show(string $id): JsonResponse
    {
        try {
            $table = RestaurantTable::findOrFail($id);
            $table->qr_code_url = $table->qr_code_url;

            return response()->json([
                'success' => true,
                'data' => $table,
            ], 200);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Restaurant table not found',
            ], 404);

        } catch (\Exception $e) {
            Log::error('Failed to fetch restaurant table', [
                'table_id' => $id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch restaurant table',
            ], 500);
        }
    }

    /**
     * Create a new restaurant table
     */
    public function store(Request $request): JsonResponse
    {
        // Log incoming request data
        Log::info('Creating restaurant table', [
            'request_data' => $request->all(),
            'user_id' => auth()->id(),
        ]);

        $validator = Validator::make($request->all(), [
            'table_number' => 'required|string|unique:restaurant_tables,table_number',
            'table_name' => 'nullable|string|max:255',
            'capacity' => 'nullable|integer|min:1|max:20',
            'location' => 'nullable|string|max:255',
            'status' => 'nullable|in:available,occupied,reserved,cleaning,out_of_service',
            'is_active' => 'nullable|boolean',
        ]);

        if ($validator->fails()) {
            Log::warning('Restaurant table validation failed', [
                'validation_errors' => $validator->errors()->toArray(),
                'request_data' => $request->all(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $table = RestaurantTable::create([
                'table_number' => $request->table_number,
                'table_name' => $request->table_name,
                'capacity' => $request->get('capacity', 4),
                'location' => $request->location,
                'status' => $request->get('status', RestaurantTable::STATUS_AVAILABLE),
                'is_active' => $request->get('is_active', true),
            ]);

            // Reload to get QR code info
            $table->refresh();
            $table->qr_code_url = $table->qr_code_url;

            Log::info('Restaurant table created', [
                'table_id' => $table->id,
                'table_number' => $table->table_number,
                'created_by' => auth()->id(),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Restaurant table created successfully',
                'data' => $table,
            ], 201);

        } catch (\Exception $e) {
            Log::error('Failed to create restaurant table', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'request_data' => $request->all(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to create restaurant table',
            ], 500);
        }
    }

    /**
     * Update an existing restaurant table
     */
    public function update(Request $request, string $id): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'table_number' => 'sometimes|required|string|unique:restaurant_tables,table_number,' . $id,
            'table_name' => 'nullable|string|max:255',
            'capacity' => 'nullable|integer|min:1|max:20',
            'location' => 'nullable|string|max:255',
            'status' => 'nullable|in:available,occupied,reserved,cleaning,out_of_service',
            'is_active' => 'nullable|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $table = RestaurantTable::findOrFail($id);

            $table->update($request->only([
                'table_number',
                'table_name',
                'capacity',
                'location',
                'status',
                'is_active',
            ]));

            $table->qr_code_url = $table->qr_code_url;

            Log::info('Restaurant table updated', [
                'table_id' => $table->id,
                'updated_by' => auth()->id(),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Restaurant table updated successfully',
                'data' => $table,
            ], 200);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Restaurant table not found',
            ], 404);

        } catch (\Exception $e) {
            Log::error('Failed to update restaurant table', [
                'table_id' => $id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to update restaurant table',
            ], 500);
        }
    }

    /**
     * Delete a restaurant table (soft delete)
     */
    public function destroy(string $id): JsonResponse
    {
        try {
            $table = RestaurantTable::findOrFail($id);

            // Check if table has active orders
            $activeOrders = $table->orders()
                                  ->whereIn('status', ['pending', 'preparing', 'ready'])
                                  ->count();

            if ($activeOrders > 0) {
                return response()->json([
                    'success' => false,
                    'message' => "Cannot delete table with {$activeOrders} active order(s)",
                ], 422);
            }

            $table->delete();

            Log::info('Restaurant table deleted', [
                'table_id' => $id,
                'deleted_by' => auth()->id(),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Restaurant table deleted successfully',
            ], 200);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Restaurant table not found',
            ], 404);

        } catch (\Exception $e) {
            Log::error('Failed to delete restaurant table', [
                'table_id' => $id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to delete restaurant table',
            ], 500);
        }
    }

    /**
     * Regenerate QR code for a table
     */
    public function regenerateQR(string $id): JsonResponse
    {
        try {
            $table = RestaurantTable::findOrFail($id);

            $table->regenerateQRCode();

            Log::info('Restaurant table QR code regenerated', [
                'table_id' => $id,
                'regenerated_by' => auth()->id(),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'QR code regenerated successfully',
                'data' => [
                    'qr_token' => $table->qr_token,
                    'qr_image_path' => $table->qr_image_path,
                    'qr_code_url' => $table->qr_code_url,
                    'qr_generated_at' => $table->qr_generated_at,
                ],
            ], 200);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Restaurant table not found',
            ], 404);

        } catch (\Exception $e) {
            Log::error('Failed to regenerate QR code', [
                'table_id' => $id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to regenerate QR code',
            ], 500);
        }
    }

    /**
     * Get table statistics
     */
    public function statistics(): JsonResponse
    {
        try {
            $stats = [
                'total' => RestaurantTable::count(),
                'active' => RestaurantTable::where('is_active', true)->count(),
                'available' => RestaurantTable::where('status', RestaurantTable::STATUS_AVAILABLE)
                                              ->where('is_active', true)
                                              ->count(),
                'occupied' => RestaurantTable::where('status', RestaurantTable::STATUS_OCCUPIED)->count(),
                'reserved' => RestaurantTable::where('status', RestaurantTable::STATUS_RESERVED)->count(),
                'cleaning' => RestaurantTable::where('status', RestaurantTable::STATUS_CLEANING)->count(),
                'out_of_service' => RestaurantTable::where('status', RestaurantTable::STATUS_OUT_OF_SERVICE)->count(),
            ];

            return response()->json([
                'success' => true,
                'data' => $stats,
            ], 200);

        } catch (\Exception $e) {
            Log::error('Failed to fetch table statistics', [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch statistics',
            ], 500);
        }
    }
}

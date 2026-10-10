<?php

namespace App\Http\Controllers\Api\Manager;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreRestaurantTableRequest;
use App\Http\Requests\UpdateRestaurantTableRequest;
use App\Http\Resources\RestaurantTableResource;
use App\Models\RestaurantTable;
use App\Services\RestaurantTableService;
use App\Services\TenantContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Throwable;

class RestaurantTableController extends Controller
{
    public function __construct(
        protected RestaurantTableService $tableService,
        protected TenantContext $tenantContext
    ) {}

    protected function getHotelId(): ?string
    {
        return $this->tenantContext->getHotelId()
            ?: (auth()->check() ? auth()->user()->hotel_id : null)
            ?: request()->header('X-Hotel-ID');
    }

    /**
     * Display a listing of tables for the current tenant.
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $hotelId = $this->getHotelId();
            $perPage = $request->integer('per_page', 15);

            $tables = $this->tableService->getTables($request->all(), $perPage, $hotelId);

            return response()->json([
                'success' => true,
                'data'    => $tables,
            ], 200);

        } catch (Throwable $e) {
            Log::error('Failed to fetch restaurant tables', ['error' => $e->getMessage()]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch restaurant tables',
            ], 500);
        }
    }

    /**
     * Display the specified table.
     */
    public function show(string $id): JsonResponse
    {
        try {
            $hotelId = $this->getHotelId();
            $table = $this->tableService->getTable($id, $hotelId);

            return response()->json([
                'success' => true,
                'data'    => new RestaurantTableResource($table),
            ], 200);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Restaurant table not found',
            ], 404);

        } catch (Throwable $e) {
            Log::error('Failed to fetch restaurant table', ['table_id' => $id, 'error' => $e->getMessage()]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch restaurant table',
            ], 500);
        }
    }

    /**
     * Store a newly created table.
     */
    public function store(StoreRestaurantTableRequest $request): JsonResponse
    {
        try {
            $hotelId = $this->getHotelId();
            $table = $this->tableService->createTable($request->validated(), $hotelId);

            return response()->json([
                'success' => true,
                'message' => 'Restaurant table created successfully',
                'data'    => new RestaurantTableResource($table),
            ], 201);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors'  => $e->errors(),
            ], 422);

        } catch (Throwable $e) {
            Log::error('Failed to create restaurant table', ['error' => $e->getMessage()]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to create restaurant table: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Update the specified table.
     */
    public function update(UpdateRestaurantTableRequest $request, string $id): JsonResponse
    {
        try {
            $hotelId = $this->getHotelId();
            $table = $this->tableService->getTable($id, $hotelId);
            $updated = $this->tableService->updateTable($table, $request->validated(), $hotelId);

            return response()->json([
                'success' => true,
                'message' => 'Restaurant table updated successfully',
                'data'    => new RestaurantTableResource($updated),
            ], 200);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Restaurant table not found',
            ], 404);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors'  => $e->errors(),
            ], 422);

        } catch (Throwable $e) {
            Log::error('Failed to update restaurant table', ['table_id' => $id, 'error' => $e->getMessage()]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to update restaurant table: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Remove the specified table.
     */
    public function destroy(string $id): JsonResponse
    {
        try {
            $hotelId = $this->getHotelId();
            $table = $this->tableService->getTable($id, $hotelId);
            $this->tableService->deleteTable($table);

            return response()->json([
                'success' => true,
                'message' => 'Restaurant table deleted successfully',
            ], 200);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Restaurant table not found',
            ], 404);

        } catch (Throwable $e) {
            Log::error('Failed to delete restaurant table', ['table_id' => $id, 'error' => $e->getMessage()]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to delete restaurant table',
            ], 500);
        }
    }
    public function regenerateQR(string $id): JsonResponse
    {
        try {
            $hotelId = $this->getHotelId();
            $table = $this->tableService->getTable($id, $hotelId);
            $newPath = $this->tableService->regenerateQR($table);

            return response()->json([
                'success'     => true,
                'message'     => 'QR code regenerated successfully',
                'qr_token'    => $table->qr_token,
                'qr_code_url' => $table->qr_code_url,
            ], 200);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Restaurant table not found',
            ], 404);

        } catch (Throwable $e) {
            Log::error('Failed to regenerate QR code', ['table_id' => $id, 'error' => $e->getMessage()]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to regenerate QR code',
            ], 500);
        }
    }

    public function statistics(): JsonResponse
    {
        try {
            $hotelId = $this->getHotelId();
            $stats = $this->tableService->getStatistics($hotelId);

            return response()->json([
                'success' => true,
                'data'    => $stats,
            ], 200);

        } catch (Throwable $e) {
            Log::error('Failed to fetch table statistics', ['error' => $e->getMessage()]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch statistics',
            ], 500);
        }
    }

    public function downloadQR(string $id)
    {
        try {
            $hotelId = $this->getHotelId();
            $table = $this->tableService->getTable($id, $hotelId);

            if (!$table->qr_image_path) {
                $this->tableService->regenerateQR($table);
            }

            $relativePath = ltrim(str_replace('/storage/', '', $table->qr_image_path), '/');
            $filePath = storage_path('app/public/' . $relativePath);

            if (!file_exists($filePath)) {
                $this->tableService->regenerateQR($table);
                $relativePath = ltrim(str_replace('/storage/', '', $table->qr_image_path), '/');
                $filePath = storage_path('app/public/' . $relativePath);
            }

            if (!file_exists($filePath)) {
                return response()->json([
                    'success' => false,
                    'message' => 'QR image file missing on disk',
                ], 404);
            }

            $fileName = "Table_{$table->table_number}_QR.png";
            return response()->download($filePath, $fileName, [
                'Content-Type' => 'image/png',
                'Access-Control-Allow-Origin' => '*',
            ]);

        } catch (Throwable $e) {
            Log::error('Failed to download table QR image', ['table_id' => $id, 'error' => $e->getMessage()]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to download QR code',
            ], 500);
        }
    }
}


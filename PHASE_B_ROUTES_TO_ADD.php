<?php

/*
|--------------------------------------------------------------------------
| Phase B: API Routes to Add
|--------------------------------------------------------------------------
|
| Add these routes to your server/routes/api.php file
| 
| Location: server/routes/api.php
|
| These routes enable:
| - QR token resolution (public)
| - Unified order creation (public)
| - Restaurant table management (manager only)
|
*/

// Import the controllers at the top of your api.php file
use App\Http\Controllers\Api\QRResolutionController;
use App\Http\Controllers\Api\UnifiedOrderController;
use App\Http\Controllers\Api\Manager\RestaurantTableController;

/*
|--------------------------------------------------------------------------
| Public QR Resolution Routes
|--------------------------------------------------------------------------
| These routes allow the frontend to resolve QR tokens and determine
| whether they belong to a room (room service) or table (walk-in).
*/

// Resolve QR token (POST with JSON body)
Route::post('/qr/resolve', [QRResolutionController::class, 'resolveQRToken']);

// Resolve QR token (GET with URL parameter)
Route::get('/qr/resolve/{token}', [QRResolutionController::class, 'resolveFromUrl']);

// Validate QR token (lightweight check)
Route::post('/qr/validate', [QRResolutionController::class, 'validateQRToken']);


/*
|--------------------------------------------------------------------------
| Public Unified Order Creation Route
|--------------------------------------------------------------------------
| This single endpoint handles BOTH room service and walk-in orders.
| The backend determines the order type based on QR token resolution.
*/

// Create order (room service OR walk-in) - determined by qr_token
Route::post('/orders', [UnifiedOrderController::class, 'createOrder']);


/*
|--------------------------------------------------------------------------
| Manager: Restaurant Table Management Routes
|--------------------------------------------------------------------------
| Protected routes for managers to create and manage restaurant tables.
| Requires authentication and manager role.
*/

Route::middleware(['auth:sanctum', 'role:manager'])->prefix('manager')->group(function () {
    
    // List all restaurant tables (with pagination, filtering, sorting)
    Route::get('/restaurant-tables', [RestaurantTableController::class, 'index']);
    
    // Get table statistics (total, active, available, occupied, etc.)
    Route::get('/restaurant-tables/statistics', [RestaurantTableController::class, 'statistics']);
    
    // Get single restaurant table by ID
    Route::get('/restaurant-tables/{id}', [RestaurantTableController::class, 'show']);
    
    // Create new restaurant table
    Route::post('/restaurant-tables', [RestaurantTableController::class, 'store']);
    
    // Update existing restaurant table
    Route::put('/restaurant-tables/{id}', [RestaurantTableController::class, 'update']);
    
    // Delete restaurant table (soft delete)
    Route::delete('/restaurant-tables/{id}', [RestaurantTableController::class, 'destroy']);
    
    // Regenerate QR code for a table
    Route::post('/restaurant-tables/{id}/regenerate-qr', [RestaurantTableController::class, 'regenerateQR']);
});


/*
|--------------------------------------------------------------------------
| BACKWARD COMPATIBILITY NOTE
|--------------------------------------------------------------------------
|
| The existing room service order endpoints in GuestOrderController still work:
| - GET  /api/guest/order/room/{qrToken}
| - GET  /api/guest/order/menu/{qrToken}
| - POST /api/guest/order/create
|
| These can continue to be used for room service orders.
| The new UnifiedOrderController (/api/orders) is recommended going forward
| as it handles both room service and walk-in orders seamlessly.
|
*/

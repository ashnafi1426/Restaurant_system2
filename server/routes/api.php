<?php
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\RoomTypeController;
use App\Http\Controllers\Api\RoomController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\GuestController;
use App\Http\Controllers\Api\ReservationController;
use App\Http\Controllers\Api\CheckInController;
use App\Http\Controllers\Api\ReceptionController;
use App\Http\Controllers\Api\MenuItemController;
use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\Api\OrderController;
use App\Http\Controllers\Api\NotificationController;
use App\Http\Controllers\Api\KitchenController;
use App\Http\Controllers\Api\GuestOrderController;
use App\Http\Controllers\Api\QRCodeController;
use App\Http\Controllers\Api\QRCodePrintController;
// =====================================================
// PUBLIC ROUTES - No Authentication Required
// =====================================================
Route::post('/login', [AuthController::class, 'login']);
Route::get('/rooms', [RoomController::class, 'index']);
Route::get('/rooms/{room}', [RoomController::class, 'show']);
Route::post('/reservations', [ReservationController::class, 'store']);
Route::post('/guests', [GuestController::class, 'store']);
// Route::get('/menu-items', [MenuItemController::class, 'index']);
Route::get('/guests', [GuestController::class, 'index']);

// Public QR code access (for downloading QR codes)
Route::get('/qr-codes/download/{roomId}', [QRCodePrintController::class, 'downloadQRCode']);
Route::get('/qr-codes/print/{roomId}', [QRCodePrintController::class, 'getPrintTemplate']);

// =====================================================
// GUEST QR CODE ORDERING ROUTES - No Authentication Required
// Token-based for security (not room numbers)
// =====================================================
Route::prefix('guest')->group(function () {
    // Public menu (no token required) - MUST be before parameterized routes
    Route::get('/menu/items', [GuestOrderController::class, 'getAllMenuItems']);
    
    // Room validation by QR token - returns room info + guest data
    Route::get('/menu/{qrToken}', [GuestOrderController::class, 'getRoom']);
    
    // Menu items with token validation
    Route::get('/menu/{qrToken}/items', [GuestOrderController::class, 'getMenuItems']);
    
    // Create order with token
    Route::post('/orders', [GuestOrderController::class, 'createOrder']);
    
    Route::get('/orders/{qrToken}/status', [GuestOrderController::class, 'getOrderStatus']);
});

// =====================================================
// QR CODE GENERATION ROUTES - No Authentication Required
// Generate QR codes for guest ordering
// =====================================================
Route::prefix('qr-code')->group(function () {
    // Generate QR code for specific room
    Route::get('/generate/{roomId}', [QRCodeController::class, 'generateForRoom']);
    
    // Generate QR codes for all rooms
    // Route::get('/generate-all', [QRCodeController::class, 'generateAll']);
    
    // Get QR code data (room info + token)
    Route::get('/data/{roomId}', [QRCodeController::class, 'getQRCodeData']);
});

// Route::prefix('guest')->group(function () {
//     Route::get('/menu/{token}', [GuestOrderController::class, 'menu']);
//     Route::post('/orders',[GuestOrderController::class, 'store']);
//     Route::get('/orders/{token}/{id}',[GuestOrderController::class, 'show']);
//     Route::get('/orders/history/{token}',[GuestOrderController::class,'history']);
// });
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/me', [AuthController::class, 'me']);
    Route::post('/logout', [AuthController::class, 'logout']);

    // =====================================================
    // ADMIN ROUTES
    // =====================================================
    Route::middleware('role:admin')->group(function () {
        Route::get('/admin/dashboard', [DashboardController::class, 'index']);
        
        // User Management
        Route::get('/users', [UserController::class, 'index']);
        Route::post('/users', [UserController::class, 'store']);
        Route::get('/users/{user}', [UserController::class, 'show']);
        Route::put('/users/{user}', [UserController::class, 'update']);
        Route::delete('/users/{user}', [UserController::class, 'destroy']);
        Route::patch(
            '/users/{user}/toggle-status',
            [UserController::class, 'toggleStatus']
        )->name('users.toggleStatus');

        // Room Types Management
        Route::get('/room-types', [RoomTypeController::class, 'index']);
        Route::post('/room-types', [RoomTypeController::class, 'store']);
        Route::get('/room-types/{roomType}', [RoomTypeController::class, 'show']);
        Route::put('/room-types/{roomType}', [RoomTypeController::class, 'update']);
        Route::delete('/room-types/{roomType}', [RoomTypeController::class, 'destroy']); 
        Route::patch(
            '/room-types/{roomType}/toggle-status',
            [RoomTypeController::class, 'toggleStatus']
        )->name('room-types.toggleStatus');

        // Room Management
        // Route::get('/rooms', [RoomController::class, 'index']);
        Route::post('/rooms', [RoomController::class, 'store']);
        Route::get('/rooms/{room}', [RoomController::class, 'show']);
        Route::put('/rooms/{room}', [RoomController::class, 'update']);
        Route::delete('/rooms/{room}', [RoomController::class, 'destroy']);
        Route::patch('/rooms/{room}/toggle-status', [RoomController::class, 'toggleStatus']);

        // QR Code Management (Admin Panel)
        Route::prefix('admin/qr-codes')->group(function () {
            Route::get('/{roomId}/image', [QRCodePrintController::class, 'getQRCodeImage']);
            Route::get('/{roomId}/download', [QRCodePrintController::class, 'downloadQRCode']);
            Route::get('/{roomId}/print-template', [QRCodePrintController::class, 'getPrintTemplate']);
            Route::post('/{roomId}/regenerate', [QRCodePrintController::class, 'regenerateQRCode']);
            Route::get('/all', [QRCodePrintController::class, 'getAllQRCodes']);
        });
    });

    Route::middleware('role:chef')->group(function () {
       Route::prefix('kitchen')->group(function(){
           Route::get('/orders',[KitchenController::class,'index']);
           Route::get('/statistics',[KitchenController::class,'statistics']);
           Route::patch('/orders/{order}/start',[KitchenController::class,'start']);
           Route::patch('/orders/{order}/ready',[KitchenController::class,'ready']);
           Route::patch('/orders/{order}/complete',[KitchenController::class,'complete']);
       });
    });

    // =====================================================
    // NOTIFICATIONS MANAGEMENT - All Authenticated Users
    // =====================================================
    Route::prefix('notifications')->group(function () {
        // Named routes first to avoid parameter collision
        Route::get('/latest', [NotificationController::class, 'latest']);
        Route::get('/unread-count', [NotificationController::class, 'unreadCount']);
        Route::put('/read-all', [NotificationController::class, 'markAllAsRead']);
        Route::delete('/clear-all', [NotificationController::class, 'clearAll']);
        // Generic routes last
        Route::get('/', [NotificationController::class, 'index']);
        Route::put('/{id}/read', [NotificationController::class, 'markAsRead']);
        Route::delete('/{id}', [NotificationController::class, 'destroy']);
    });

    // =====================================================
    // SHARED ROUTES - Admin & Receptionist
    // =====================================================
    Route::middleware('role:admin|receptionist')->group(function(){
        Route::get('/menu-items', [MenuItemController::class, 'index']);
        Route::get('/menu-items/statistics', [MenuItemController::class, 'statistics']);
    });
    Route::middleware('role:admin')->group(function(){
        Route::post('/menu-items', [MenuItemController::class, 'store']);
        Route::get('/menu-items/{menuItem}', [MenuItemController::class, 'show']);
        Route::put('/menu-items/{menuItem}', [MenuItemController::class, 'update']);
        Route::patch('/menu-items/{menuItem}/toggle-availability', [MenuItemController::class, 'toggleAvailability']);
        Route::delete('/menu-items/{menuItem}', [MenuItemController::class, 'destroy']);

        // =====================================================
        // CATEGORY ROUTES
        // =====================================================
        Route::get('/categories', [CategoryController::class, 'index']);
        Route::post('/categories', [CategoryController::class, 'store']);
        Route::get('/categories/{category}', [CategoryController::class, 'show']);
        Route::put('/categories/{category}', [CategoryController::class, 'update']);
        Route::patch('/categories/{category}/toggle', [CategoryController::class, 'toggle']);
        Route::post('/categories/reorder', [CategoryController::class, 'reorder']);
        Route::delete('/categories/{category}', [CategoryController::class, 'destroy']);
    });

    // =====================================================
    // RECEPTIONIST ROUTES
    // =====================================================
    Route::middleware('role:receptionist')->group(function(){
        // Orders Management
        Route::get('/orders',[OrderController::class, 'index']);
        Route::post('/orders',[OrderController::class, 'store']);
        Route::get('/orders/{id}',[OrderController::class, 'show']);
        Route::put('/orders/{id}',[OrderController::class, 'update']);
        Route::patch('/orders/{id}',[OrderController::class, 'update']);
        Route::delete('/orders/{id}',[OrderController::class, 'destroy']);
        Route::patch('/orders/{id}/status',[OrderController::class, 'changeStatus']);
      
        // Reception Dashboard
        Route::get('/reception/dashboard', [ReceptionController::class, 'index']);

        // Guest Management (Receptionist-specific operations)
        Route::prefix('admin-guests')->group(function () {
            Route::get('/', [GuestController::class, 'index']);
            Route::post('/', [GuestController::class, 'store']);
            Route::get('/{guest}', [GuestController::class, 'show']);
            Route::get('/{guest}/reservations', [GuestController::class, 'reservations']);
            Route::put('/{guest}', [GuestController::class, 'update']);
            Route::patch('/{guest}', [GuestController::class, 'update']);
            Route::delete('/{guest}', [GuestController::class, 'destroy']);
        });

        // Reservation Management (Receptionist-specific operations)
        Route::get('/reservations', [ReservationController::class, 'index']);
        Route::get('/admin-reservations/{reservation}', [ReservationController::class, 'show']);
        Route::put('/admin-reservations/{reservation}', [ReservationController::class, 'update']);
        Route::patch('/admin-reservations/{reservation}', [ReservationController::class, 'update']);
        Route::delete('/admin-reservations/{reservation}', [ReservationController::class, 'destroy']);
        Route::post('/admin-reservations/{reservation}/confirm', [ReservationController::class, 'confirm']);
        Route::post('/admin-reservations/{reservation}/check-in', [ReservationController::class, 'checkIn']);
        Route::post('/admin-reservations/{reservation}/check-out', [ReservationController::class, 'checkOut']);
        Route::post('/admin-reservations/{reservation}/cancel', [ReservationController::class, 'cancel']);

        // Check-Ins Management
        Route::prefix('check-ins')->group(function () {
            Route::get('/statistics', [CheckInController::class, 'statistics']);
            Route::get('/', [CheckInController::class, 'index']);
            Route::post('/', [CheckInController::class, 'store']);
            Route::get('/{checkIn}', [CheckInController::class, 'show']);
            Route::post('/{checkIn}/checkout', [CheckInController::class, 'checkout']);
            Route::delete('/{checkIn}', [CheckInController::class, 'destroy']);
        });
    });
});
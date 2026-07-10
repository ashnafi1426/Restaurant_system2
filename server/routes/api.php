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
use App\Http\Controllers\Api\GuestOrderController;
use App\Http\Controllers\Api\OrderController;
use App\Http\Controllers\Api\KitchenController;

Route::post('/login', [AuthController::class, 'login']);
Route::prefix('guest')->group(function () {
    Route::get('/menu/{token}', [GuestOrderController::class, 'menu']);
    Route::post('/orders',[GuestOrderController::class, 'store']);
    Route::get('/orders/{token}/{id}',[GuestOrderController::class, 'show']);
    Route::get('/orders/history/{token}',[GuestOrderController::class,'history']);
});
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/me', [AuthController::class, 'me']);
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::middleware('role:admin')->group(function () {
        Route::get('/admin/dashboard', [DashboardController::class, 'index']);
        Route::get('/users', [UserController::class, 'index']);
        Route::post('/users', [UserController::class, 'store']);
        Route::get('/users/{user}', [UserController::class, 'show']);
        Route::put('/users/{user}', [UserController::class, 'update']);
        Route::delete('/users/{user}', [UserController::class, 'destroy']);
        Route::patch(
            '/users/{user}/toggle-status',
            [UserController::class, 'toggleStatus']
        )->name('users.toggleStatus');
        Route::get('/room-types', [RoomTypeController::class, 'index']);
        Route::post('/room-types', [RoomTypeController::class, 'store']);
        Route::get('/room-types/{roomType}', [RoomTypeController::class, 'show']);
        Route::put('/room-types/{roomType}', [RoomTypeController::class, 'update']);
        Route::delete('/room-types/{roomType}', [RoomTypeController::class, 'destroy']); 
        Route::patch(
            '/room-types/{roomType}/toggle-status',
            [RoomTypeController::class, 'toggleStatus']
        )->name('room-types.toggleStatus');
        Route::get('/rooms', [RoomController::class, 'index']);
        Route::post('/rooms', [RoomController::class, 'store']);
        Route::get('/rooms/{room}', [RoomController::class, 'show']);
        Route::put('/rooms/{room}', [RoomController::class, 'update']);
        Route::delete('/rooms/{room}', [RoomController::class, 'destroy']);
        Route::patch('/rooms/{room}/toggle-status', [RoomController::class, 'toggleStatus']);
        Route::get('/menu-items', [MenuItemController::class,'index']);
        Route::post('/menu-items', [MenuItemController::class,'store']);
        Route::get('/menu-items/statistics', [MenuItemController::class,'statistics']);
        Route::get('/menu-items/{menuItem}', [MenuItemController::class,'show']);
        Route::put('/menu-items/{menuItem}', [MenuItemController::class,'update']);
        Route::patch('/menu-items/{menuItem}/toggle-availability', [MenuItemController::class,'toggleAvailability']);
        Route::delete('/menu-items/{menuItem}', [MenuItemController::class,'destroy']);
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
    Route::middleware('role:receptionist')->group(function(){
         Route::get('/menu-items', [MenuItemController::class,'index']);
        Route::post('/menu-items', [MenuItemController::class,'store']);
        Route::get('/menu-items/statistics', [MenuItemController::class,'statistics']);
        Route::get('/menu-items/{menuItem}', [MenuItemController::class,'show']);
        Route::get('/orders',[OrderController::class, 'index']);
    Route::post('/orders',[OrderController::class, 'store']);
    Route::get('/orders/{id}',[OrderController::class, 'show']);
    Route::put('/orders/{id}',[OrderController::class, 'update']);
    Route::patch('/orders/{id}',[OrderController::class, 'update']);
    Route::delete( '/orders/{id}',[OrderController::class, 'destroy']);
    Route::patch('/orders/{id}/status',[OrderController::class, 'changeStatus']);
        Route::get('/reception/dashboard', [ReceptionController::class, 'index']);
        Route::prefix('guests')->group(function () {
        Route::get('/', [GuestController::class, 'index']);
        Route::post('/', [GuestController::class, 'store']);
        Route::get('/{guest}', [GuestController::class, 'show']);
        Route::get('/{guest}/reservations', [GuestController::class, 'reservations']);
        Route::put('/{guest}', [GuestController::class, 'update']);
        Route::patch('/{guest}', [GuestController::class, 'update']);
        Route::delete('/{guest}', [GuestController::class, 'destroy']);
        });
        Route::get('/rooms', [RoomController::class, 'index']);
        Route::get('/rooms/{room}', [RoomController::class, 'show']);
        //Reservation
        Route::get('/reservations', [ReservationController::class, 'index']);
        Route::post('/reservations', [ReservationController::class, 'store']);
        Route::get('/reservations/{reservation}', [ReservationController::class, 'show']);
        Route::put('/reservations/{reservation}', [ReservationController::class, 'update']);
        Route::patch('/reservations/{reservation}', [ReservationController::class, 'update']);
        Route::delete('/reservations/{reservation}', [ReservationController::class, 'destroy']);
        Route::post('/reservations/{reservation}/confirm', [ReservationController::class, 'confirm']);
        Route::post('/reservations/{reservation}/check-in', [ReservationController::class, 'checkIn']);
        Route::post('/reservations/{reservation}/check-out', [ReservationController::class, 'checkOut']);
        Route::post('/reservations/{reservation}/cancel', [ReservationController::class, 'cancel']);
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
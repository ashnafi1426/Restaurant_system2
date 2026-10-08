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
use App\Http\Controllers\Api\CustomerOrderController;
use App\Http\Controllers\Api\BroadcastAuthController;
use App\Http\Controllers\Api\GuestOrderController;
use App\Http\Controllers\Api\QRCodeController;
use App\Http\Controllers\Api\QRCodePrintController;
use App\Http\Controllers\Api\ManagerController;
use App\Http\Controllers\Api\Manager\DashboardController as ManagerDashboardController;
use App\Http\Controllers\Api\Manager\RevenueController;
use App\Http\Controllers\Api\Manager\OccupancyController as ManagerOccupancyController;
use App\Http\Controllers\Api\Manager\StaffController;
use App\Http\Controllers\Api\Manager\OperationsController as ManagerOperationsController;
use App\Http\Controllers\Api\Manager\WaiterController as ManagerWaiterController;
use App\Http\Controllers\Api\Manager\AnalyticsController as ManagerAnalyticsController;
use App\Http\Controllers\Api\Manager\ActivityController as ManagerActivityController;
use App\Http\Controllers\Api\Manager\SettingsController as ManagerSettingsController;
use App\Http\Controllers\Api\Manager\ComplaintController;
use App\Http\Controllers\Api\Manager\KitchenController as ManagerKitchenController;
use App\Http\Controllers\Api\Manager\WaiterManagementController;
use App\Http\Controllers\Api\Manager\FloorAssignmentController;
use App\Http\Controllers\Api\Manager\FloorManagementController;
use App\Http\Controllers\Api\Manager\ShiftManagementController;
use App\Http\Controllers\Api\Manager\WaiterTableAssignmentController;
use App\Http\Controllers\Api\Manager\ManagerDeliveryManagementController;
use App\Http\Controllers\Api\Waiter\WaiterDashboardController;
use App\Http\Controllers\Api\Waiter\WaiterAssignmentController;
use App\Http\Controllers\Api\Waiter\WaiterHistoryController;
use App\Http\Controllers\Api\Waiter\WaiterProfileController;
use App\Http\Controllers\Api\Waiter\WaiterNotificationController;
use App\Http\Controllers\Api\PaymentController;
use App\Http\Controllers\Api\ReservationPaymentController;
use App\Http\Controllers\Api\GuestOrderPaymentController;
use App\Http\Controllers\Api\WalkInOrderPaymentController;
use App\Http\Controllers\Api\Cashier\CashierDashboardController;
use App\Http\Controllers\Api\Cashier\CashierPaymentController;
use App\Http\Controllers\Api\Cashier\CashierReportController;
use App\Http\Controllers\Api\ActivationController;
use App\Http\Controllers\Api\PasswordResetController;
use App\Http\Controllers\Api\QRResolutionController;
use App\Http\Controllers\Api\UnifiedOrderController;
use App\Http\Controllers\Api\Manager\RestaurantTableController;
use App\Http\Controllers\Api\Manager\RestaurantSectionController;
use App\Http\Controllers\Api\Profile\ManagerProfileController;
use App\Http\Controllers\Api\Profile\AdminProfileController;
use App\Http\Controllers\Api\Profile\CashierProfileController;
use App\Http\Controllers\Api\Profile\ReceptionistProfileController;
use App\Http\Controllers\Api\Profile\ChefProfileController;
use App\Http\Controllers\Api\Rbac\RoleController;
use App\Http\Controllers\Api\Rbac\PermissionController;
use App\Http\Controllers\Api\Rbac\UserRoleController;
use App\Http\Controllers\Api\Rbac\TemporaryRoleController;
use App\Http\Controllers\Api\Rbac\AuditLogController;
use App\Http\Controllers\Api\Rbac\UserDirectPermissionController;
use App\Http\Controllers\Api\ReceptionReportController;
use App\Http\Controllers\Api\PublicReviewController;
use App\Http\Controllers\Api\VotingController;
use App\Http\Controllers\Api\ReviewController;
use App\Http\Controllers\Api\ModerationController;
use App\Http\Controllers\Api\ResponseController;
use App\Http\Controllers\Api\AnalyticsController;
use App\Http\Controllers\Api\ReviewNotificationController;
use App\Http\Controllers\Api\Admin\AdminBookingController;
use App\Http\Controllers\Api\Platform\PlatformHotelController;
use App\Http\Controllers\Api\Guests\PublicHotelController;
use App\Http\Controllers\Api\Guests\GuestBookingController;
use App\Http\Controllers\Api\Payment\PaymentGatewayController;
use App\Http\Controllers\Api\Analytics\BookingAnalyticsController;
use App\Http\Controllers\Api\CancellationPolicyController;
use App\Http\Controllers\Api\TranslationController;
use App\Http\Controllers\Api\TaxRateController;

Route::get('/translations', [TranslationController::class, 'index']);
Route::get('/tax-rates', [TaxRateController::class, 'index']);

Route::post('/auth/login', [AuthController::class, 'login'])->name('login');

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::post('/auth/logout', [AuthController::class, 'logout']);
    Route::get('/me', [AuthController::class, 'me']);
    Route::get('/auth/me', [AuthController::class, 'me']);
    Route::post('/auth/switch-hotel', [AuthController::class, 'switchHotel']);
    Route::get('/auth/my-hotels', [AuthController::class, 'myHotels']);
    Route::post('/auth/update-password', [AuthController::class, 'updatePassword']);

    // Admin Booking Management Routes
    Route::prefix('admin/bookings')->middleware('auth:sanctum')->group(function () {
        Route::get('/', [AdminBookingController::class, 'index']);
        Route::get('/{reservationId}', [AdminBookingController::class, 'show']);
        Route::put('/{reservationId}', [AdminBookingController::class, 'update']);
        Route::delete('/{reservationId}', [AdminBookingController::class, 'destroy']);
    });
});

Route::middleware(['auth:sanctum', 'platform.admin'])->prefix('platform')->group(function () {
    Route::get('/statistics', [PlatformHotelController::class, 'statistics']);
    Route::get('/hotels', [PlatformHotelController::class, 'index']);
    Route::post('/hotels', [PlatformHotelController::class, 'store']);
    Route::get('/hotels/{id}', [PlatformHotelController::class, 'show']);
    Route::put('/hotels/{id}', [PlatformHotelController::class, 'update']);
    Route::patch('/hotels/{id}/status', [PlatformHotelController::class, 'updateStatus']);
    Route::post('/hotels/{id}/archive', [PlatformHotelController::class, 'archive']);
    Route::delete('/hotels/{id}', [PlatformHotelController::class, 'destroy']);
    Route::get('/hotels/{id}/admins', [PlatformHotelController::class, 'getAdmins']);
    Route::post('/hotels/{id}/admins', [PlatformHotelController::class, 'assignAdmin']);
    Route::delete('/hotels/{id}/admins/{userId}', [PlatformHotelController::class, 'removeAdmin']);
    Route::get('/admins', [PlatformHotelController::class, 'allAdmins']);
    Route::post('/hotel-admins', [PlatformHotelController::class, 'createHotelAdmin']);
    Route::post('/hotel-admins/{id}/reset-password', [PlatformHotelController::class, 'resetAdminPassword']);
    Route::post('/hotel-admins/{id}/resend-password', [PlatformHotelController::class, 'resendAdminPassword']);
    Route::patch('/hotel-admins/{id}/toggle-status', [PlatformHotelController::class, 'toggleAdminStatus']);
    Route::post('/hotels/{id}/enter-view', [PlatformHotelController::class, 'enterViewMode']);
    Route::post('/hotels/exit-view', [PlatformHotelController::class, 'exitViewMode']);
    Route::get('/users', [PlatformHotelController::class, 'allUsers']);
    Route::get('/audit-logs', [PlatformHotelController::class, 'auditLogs']);
    Route::get('/settings', [PlatformHotelController::class, 'getSettings']);
    Route::put('/settings', [PlatformHotelController::class, 'updateSettings']);
});
Route::get('/activation/{token}', [ActivationController::class, 'validateToken']);
Route::post('/activate-account', [ActivationController::class, 'activateAccount']);
Route::post('/resend-activation', [ActivationController::class, 'resendActivation']);
Route::post('/check-activation-status', [ActivationController::class, 'checkActivationStatus']);
Route::post('/forgot-password', [PasswordResetController::class, 'forgotPassword']);

Route::post('/reset-password', [PasswordResetController::class, 'resetPassword']);

Route::post('/verify-reset-token', [PasswordResetController::class, 'verifyToken']);
Route::get('/rooms', [RoomController::class, 'index']);
Route::get('/rooms/{room}', [RoomController::class, 'show']);
Route::get('/room-types', [RoomTypeController::class, 'index']);
Route::get('/room-types/{roomType}', [RoomTypeController::class, 'show']);
Route::get('/floors', [\App\Http\Controllers\Api\Manager\FloorManagementController::class, 'index']);


Route::post('/guests', [GuestController::class, 'store']);
Route::get('/guests', [GuestController::class, 'index']);
Route::get('/reservations/availability', [ReservationController::class, 'availability']);
Route::get('/qr-codes/download/{roomId}', [QRCodePrintController::class, 'downloadQRCode']);
Route::get('/qr-codes/print/{roomId}', [QRCodePrintController::class, 'getPrintTemplate']);
Route::get('/qr/resolve/{qrToken}', [\App\Http\Controllers\Api\QRResolutionController::class, 'resolveFromUrl']);
Route::post('/qr/resolve', [\App\Http\Controllers\Api\QRResolutionController::class, 'resolveQRToken']);
Route::post('/qr/validate', [\App\Http\Controllers\Api\QRResolutionController::class, 'validateQRToken']);

Route::prefix('payments')->group(function () {
    Route::post('/initialize', [PaymentController::class, 'initialize']);
    Route::get('/verify/{txRef}', [PaymentController::class, 'verify']);
    Route::get('/status/{txRef}', [PaymentController::class, 'getByTransactionRef']);
    Route::get('/callback', [PaymentController::class, 'callback']);
});

Route::prefix('reservation-payments')->group(function () {
    Route::post('/initialize', [ReservationPaymentController::class, 'initializePayment']);
    Route::post('/complete/{txRef}', [ReservationPaymentController::class, 'completeReservation']);
    Route::get('/{txRef}', [ReservationPaymentController::class, 'getReservationByPayment']);
});
Route::prefix('order-payments')->group(function () {
    Route::post('/initialize', [GuestOrderPaymentController::class, 'initializePayment']);
    Route::post('/initialize-existing', [GuestOrderPaymentController::class, 'initializeExistingOrderPayment']);
    Route::match(['get', 'post'], '/complete/{txRef}', [GuestOrderPaymentController::class, 'completeOrder']);
    Route::get('/{txRef}', [GuestOrderPaymentController::class, 'getOrderByPayment']);
});

Route::prefix('walk-in-payments')->group(function () {
    Route::post('/initialize', [WalkInOrderPaymentController::class, 'initializePayment']);
    Route::post('/initialize-for-order', [WalkInOrderPaymentController::class, 'initializePaymentForExistingOrder']);
    Route::match(['get', 'post'], '/complete/{txRef}', [WalkInOrderPaymentController::class, 'completeOrder']);
    Route::get('/{txRef}', [WalkInOrderPaymentController::class, 'getOrderByPayment']);
});

Route::prefix('qr')->group(function () {
    Route::post('/resolve', [QRResolutionController::class, 'resolveQRToken']);
    Route::get('/resolve/{qrToken}', [QRResolutionController::class, 'resolveFromUrl']);
    Route::post('/validate', [QRResolutionController::class, 'validateQRToken']);
});

Route::get('/categories', [CategoryController::class, 'index']);
Route::get('/menu-items', [MenuItemController::class, 'index']);

Route::prefix('guest')->group(function () {
    Route::get('/hotels', [PublicHotelController::class, 'index']);
    Route::get('/hotels/{slug}', [PublicHotelController::class, 'show']);
    Route::get('/categories', [GuestOrderController::class, 'getPublicCategories']);
    Route::get('/menu/items', [GuestOrderController::class, 'getAllMenuItems']);
    Route::get('/menu/{qrToken}', [GuestOrderController::class, 'getRoom']);
    Route::get('/menu/{qrToken}/items', [GuestOrderController::class, 'getMenuItems']);
    Route::post('/orders', [GuestOrderController::class, 'createOrder']);
    Route::post('/unified-orders', [UnifiedOrderController::class, 'store']);
    Route::get('/orders/{qrToken}/status', [GuestOrderController::class, 'getOrderStatus']);
    
    // Real-time order status tracking (NEW - WebSocket support)
    Route::get('/orders/{orderId}/realtime-status', [CustomerOrderController::class, 'getOrderStatus']);
});

// Broadcasting Auth Route - Required for Laravel Echo WebSocket authorization
Route::post('/broadcasting/auth', [\App\Http\Controllers\Api\BroadcastAuthController::class, 'authenticate'])->middleware('api');

Route::prefix('guest/bookings')->middleware('qr.token')->group(function () {
    Route::post('/check-availability', [GuestBookingController::class, 'checkAvailability']);
    Route::get('/rooms/{roomId}', [GuestBookingController::class, 'getRoomDetails']);
    Route::post('/', [GuestBookingController::class, 'createBooking']);
    Route::get('/{bookingReference}', [GuestBookingController::class, 'getBookingStatus']);
    Route::post('/{bookingReference}/cancel', [GuestBookingController::class, 'cancelBooking']);
});
Route::prefix('qr-code')->group(function () {
    Route::get('/generate/{roomId}', [QRCodeController::class, 'generateForRoom']);
    Route::get('/data/{roomId}', [QRCodeController::class, 'getQRCodeData']);
});
Route::get('/public/roles', [RoleController::class, 'getActiveRoles']);
Route::get('/roles/active', [RoleController::class, 'getActiveRoles']);

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/me', [AuthController::class, 'me']);
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/dashboard', [DashboardController::class, 'index']);
    Route::middleware('permission:roles.view|roles.create|roles.update|roles.delete|roles.assign_permissions|permissions.view|permissions.create|permissions.update|permissions.delete')->group(function () {
        Route::get('/roles', [RoleController::class, 'index']);
        Route::post('/roles', [RoleController::class, 'store']);
        Route::get('/roles/{role}', [RoleController::class, 'show']);
        Route::put('/roles/{role}', [RoleController::class, 'update']);
        Route::delete('/roles/{role}', [RoleController::class, 'destroy']);
        Route::get('/roles/{role}/permissions', [RoleController::class, 'getPermissions']);
        Route::post('/roles/{role}/permissions', [RoleController::class, 'syncPermissions']);

        Route::get('/permissions', [PermissionController::class, 'index']);
        Route::post('/permissions', [PermissionController::class, 'store']);
        Route::get('/permissions/{permission}', [PermissionController::class, 'show']);
        Route::put('/permissions/{permission}', [PermissionController::class, 'update']);
        Route::delete('/permissions/{permission}', [PermissionController::class, 'destroy']);
        Route::get('/users/{user}/direct-permissions', [UserDirectPermissionController::class, 'getUserPermissions']);
        Route::post('/users/{user}/direct-permissions', [UserDirectPermissionController::class, 'assignDirectPermissions']);
        Route::delete('/users/{user}/direct-permissions/{permission}', [UserDirectPermissionController::class, 'removeDirectPermission']);

        Route::get('/temporary-roles', [TemporaryRoleController::class, 'index']);
        Route::post('/users/{user}/temporary-role', [TemporaryRoleController::class, 'store']);
        Route::delete('/temporary-roles/{temporaryRoleAssignment}', [TemporaryRoleController::class, 'destroy']);
        Route::get('/audit-logs', [AuditLogController::class, 'index']);
    });

    // Staff Role Assignment (Hotel Admin & Super Admin)
    Route::middleware('permission:users.view|users.create|users.update')->group(function () {
        Route::get('/user-roles', [UserRoleController::class, 'index']);
        Route::get('/users/{user}/roles', [UserRoleController::class, 'getUserRoles']);
        Route::post('/users/{user}/roles', [UserRoleController::class, 'assignRoles']);
        Route::delete('/users/{user}/roles/{role}', [UserRoleController::class, 'removeRole']);
    });

    // Authenticated Payment Routes
    Route::prefix('payments')->group(function () {
        Route::get('/', [PaymentController::class, 'index']);
        Route::get('/{paymentId}', [PaymentController::class, 'getStatus']);
    });
    // Operational Orders Access (Shared across all operational roles)
    Route::middleware('role:staff')->group(function () {
        Route::get('/orders', [OrderController::class, 'index']);
        Route::post('/orders', [OrderController::class, 'store']);
        Route::get('/orders/{id}', [OrderController::class, 'show']);
        Route::put('/orders/{id}', [OrderController::class, 'update']);
        Route::patch('/orders/{id}', [OrderController::class, 'update']);
        Route::delete('/orders/{id}', [OrderController::class, 'destroy']);
        Route::patch('/orders/{id}/status', [OrderController::class, 'changeStatus']);
        Route::post('/orders/{id}/clear', [CashierDashboardController::class, 'clearOrder']);
    });
    // Reservations, Guests, Check-Ins, Rooms & Room Types Access (Permission & Role Driven)
    Route::middleware('role:staff')->group(function () {
        Route::get('/reservations', [ReservationController::class, 'index']);
        // Route::get('/reservations/availability', [ReservationController::class, 'availability']);
        Route::post('/reservations', [ReservationController::class, 'store']);
        Route::get('/reservations/{reservation}', [ReservationController::class, 'show']);
        Route::put('/reservations/{reservation}', [ReservationController::class, 'update']);
        Route::patch('/reservations/{reservation}', [ReservationController::class, 'update']);
        Route::delete('/reservations/{reservation}', [ReservationController::class, 'destroy']);
        Route::post('/reservations/{reservation}/confirm', [ReservationController::class, 'confirm']);
        Route::post('/reservations/{reservation}/check-in', [ReservationController::class, 'checkIn']);
        Route::post('/reservations/{reservation}/check-out', [ReservationController::class, 'checkOut']);
        Route::post('/reservations/{reservation}/cancel', [ReservationController::class, 'cancel']);

        Route::prefix('admin-guests')->group(function () {
            Route::get('/', [GuestController::class, 'index']);
            Route::post('/', [GuestController::class, 'store']);
            Route::get('/{guest}', [GuestController::class, 'show']);
            Route::get('/{guest}/reservations', [GuestController::class, 'reservations']);
            Route::put('/{guest}', [GuestController::class, 'update']);
            Route::patch('/{guest}', [GuestController::class, 'update']);
            Route::delete('/{guest}', [GuestController::class, 'destroy']);
        });

        Route::prefix('check-ins')->group(function () {
            Route::get('/statistics', [CheckInController::class, 'statistics']);
            Route::get('/', [CheckInController::class, 'index']);
            Route::post('/', [CheckInController::class, 'store']);
            Route::get('/{checkIn}', [CheckInController::class, 'show']);
            Route::post('/{checkIn}/checkout', [CheckInController::class, 'checkout']);
            Route::delete('/{checkIn}', [CheckInController::class, 'destroy']);
        });
    });

    // Room, Room-Type & QR-Code Management Access (Permission & Role Driven)
    Route::middleware('role:staff')->group(function () {
        Route::post('/room-types', [RoomTypeController::class, 'store']);
        Route::put('/room-types/{roomType}', [RoomTypeController::class, 'update']);
        Route::delete('/room-types/{roomType}', [RoomTypeController::class, 'destroy']); 
        Route::patch(
            '/room-types/{roomType}/toggle-status',
            [RoomTypeController::class, 'toggleStatus']
        )->name('room-types.toggleStatus');

        Route::post('/rooms', [RoomController::class, 'store']);
        Route::put('/rooms/{room}', [RoomController::class, 'update']);
        Route::delete('/rooms/{room}', [RoomController::class, 'destroy']);
        Route::patch('/rooms/{room}/toggle-status', [RoomController::class, 'toggleStatus']);

        Route::get('/floors', [FloorManagementController::class, 'index']);
        Route::post('/floors', [FloorManagementController::class, 'store']);

        Route::prefix('admin/qr-codes')->group(function () {
            Route::get('/{roomId}/image', [QRCodePrintController::class, 'getQRCodeImage']);
            Route::get('/{roomId}/download', [QRCodePrintController::class, 'downloadQRCode']);
            Route::get('/{roomId}/print-template', [QRCodePrintController::class, 'getPrintTemplate']);
            Route::post('/{roomId}/regenerate', [QRCodePrintController::class, 'regenerateQRCode']);
            Route::get('/all', [QRCodePrintController::class, 'getAllQRCodes']);
        });
    });

    // Admin Profile & Dashboard Routes
    Route::middleware('role:admin|staff')->group(function () {
        Route::prefix('admin/profile')->group(function () {
            Route::get('/', [AdminProfileController::class, 'getProfile']);
            Route::put('/', [AdminProfileController::class, 'updateProfile']);
            Route::post('/photo', [AdminProfileController::class, 'uploadPhoto']);
            Route::post('/change-password', [AdminProfileController::class, 'changePassword']);
            Route::get('/stats', [AdminProfileController::class, 'getStats']);
        });
        
        Route::get('/admin/dashboard', [DashboardController::class, 'index']);
        Route::get('/admin/dashboard/revenue', [DashboardController::class, 'revenue']);
        Route::get('/dashboard/revenue', [DashboardController::class, 'revenue']);
    });

    // Users & Staff Management (Admin or users with permission)
    Route::middleware('permission:users.view|users.create|users.update|users.delete')->group(function () {
        Route::get('/users', [UserController::class, 'index']);
        Route::post('/users', [UserController::class, 'store']);
        Route::get('/users/{user}', [UserController::class, 'show']);
        Route::put('/users/{user}', [UserController::class, 'update']);
        Route::delete('/users/{user}', [UserController::class, 'destroy']);
        Route::patch(
            '/users/{user}/toggle-status',
            [UserController::class, 'toggleStatus']
        )->name('users.toggleStatus');
    });
    Route::middleware('role:staff')->group(function () {
       Route::prefix('kitchen')->group(function(){
           Route::get('/orders',[KitchenController::class,'index']);
           Route::get('/statistics',[KitchenController::class,'statistics']);
           Route::patch('/orders/{order}/start',[KitchenController::class,'start']);
           Route::patch('/orders/{order}/ready',[KitchenController::class,'ready']);
           Route::patch('/orders/{order}/complete',[KitchenController::class,'complete']);
       });
    });
    Route::middleware('role:staff')->group(function () {
       // Chef Profile Routes
       Route::prefix('chef/profile')->group(function () {
           Route::get('/', [ChefProfileController::class, 'getProfile']);
           Route::put('/', [ChefProfileController::class, 'updateProfile']);
           Route::post('/photo', [ChefProfileController::class, 'uploadPhoto']);
           Route::post('/change-password', [ChefProfileController::class, 'changePassword']);
           Route::get('/stats', [ChefProfileController::class, 'getStats']);
           Route::post('/status', [ChefProfileController::class, 'updateStatus']);
       });
    });
    Route::middleware('role:staff')->group(function () {
        Route::get('/menu-items', [MenuItemController::class, 'index']);
        Route::get('/menu-items/statistics', [MenuItemController::class, 'statistics']);
        Route::get('/menu-items/{menuItem}', [MenuItemController::class, 'show']);
        Route::post('/menu-items', [MenuItemController::class, 'store']);
        Route::put('/menu-items/{menuItem}', [MenuItemController::class, 'update']);
        Route::patch('/menu-items/{menuItem}/toggle-availability', [MenuItemController::class, 'toggleAvailability']);
        Route::delete('/menu-items/{menuItem}', [MenuItemController::class, 'destroy']);

        Route::get('/categories', [CategoryController::class, 'index']);
        Route::get('/categories/{category}', [CategoryController::class, 'show']);
        Route::post('/categories', [CategoryController::class, 'store']);
        Route::put('/categories/{category}', [CategoryController::class, 'update']);
        Route::patch('/categories/{category}/toggle', [CategoryController::class, 'toggle']);
        Route::post('/categories/reorder', [CategoryController::class, 'reorder']);
        Route::delete('/categories/{category}', [CategoryController::class, 'destroy']);

        Route::get('/tax-rates/{taxRate}', [TaxRateController::class, 'show']);
        Route::post('/tax-rates', [TaxRateController::class, 'store']);
        Route::put('/tax-rates/{taxRate}', [TaxRateController::class, 'update']);
        Route::patch('/tax-rates/{taxRate}/toggle', [TaxRateController::class, 'toggleStatus']);
        Route::delete('/tax-rates/{taxRate}', [TaxRateController::class, 'destroy']);

        Route::prefix('receptionist/profile')->group(function () {
            Route::get('/', [ReceptionistProfileController::class, 'getProfile']);
            Route::put('/', [ReceptionistProfileController::class, 'updateProfile']);
            Route::post('/photo', [ReceptionistProfileController::class, 'uploadPhoto']);
            Route::post('/change-password', [ReceptionistProfileController::class, 'changePassword']);
            Route::get('/stats', [ReceptionistProfileController::class, 'getStats']);
            Route::post('/status', [ReceptionistProfileController::class, 'updateStatus']);
        });
        
        Route::get('/reception/dashboard', [ReceptionController::class, 'index']);
        
        // Reception Reports
        Route::prefix('reception/reports')->group(function () {
            Route::get('/reservations', [ReceptionReportController::class, 'reservationReport']);
            Route::get('/occupancy', [ReceptionReportController::class, 'occupancyReport']);
            Route::get('/guests', [ReceptionReportController::class, 'guestReport']);
            Route::get('/revenue', [ReceptionReportController::class, 'revenueReport']);
            Route::get('/check-in-out', [ReceptionReportController::class, 'checkInOutReport']);
        });
    });
    Route::middleware('role:staff')->prefix('manager')->group(function () {
        // Profile Routes
        Route::prefix('profile')->group(function () {
            Route::get('/', [ManagerProfileController::class, 'getProfile']);
            Route::put('/', [ManagerProfileController::class, 'updateProfile']);
            Route::post('/photo', [ManagerProfileController::class, 'uploadPhoto']);
            Route::post('/change-password', [ManagerProfileController::class, 'changePassword']);
            Route::get('/stats', [ManagerProfileController::class, 'getStats']);
        });
        
        Route::prefix('dashboard')->group(function () {
            Route::get('/', [ManagerDashboardController::class, 'index']);
            Route::get('/statistics', [ManagerDashboardController::class, 'statistics']);
            Route::get('/daily-trends', [ManagerDashboardController::class, 'dailyTrends']);
            Route::get('/performance', [ManagerDashboardController::class, 'performanceSummary']);
            Route::get('/top-items', [ManagerDashboardController::class, 'topSellingItems']);
        });
        Route::prefix('kitchen')->group(function () {
            Route::get('/orders', [ManagerKitchenController::class, 'orders']);
            Route::get('/metrics', [ManagerKitchenController::class, 'metrics']);
            Route::get('/delayed-orders', [ManagerKitchenController::class, 'delayedOrders']);
            Route::get('/performance', [ManagerKitchenController::class, 'performance']);
            Route::get('/chef-workload', [ManagerKitchenController::class, 'chefWorkload']);
            Route::get('/top-items', [ManagerKitchenController::class, 'topItems']);
            Route::get('/queue-status', [ManagerKitchenController::class, 'queueStatus']);
        });

        // Complaint Management
        Route::prefix('complaints')->group(function () {
            Route::get('/', [ComplaintController::class, 'index']);
            Route::get('/statistics', [ComplaintController::class, 'statistics']);
            Route::get('/by-type', [ComplaintController::class, 'byType']);
            Route::get('/by-severity', [ComplaintController::class, 'bySeverity']);
            Route::get('/department-performance', [ComplaintController::class, 'departmentPerformance']);
            Route::get('/report', [ComplaintController::class, 'report']);
            Route::get('/{id}', [ComplaintController::class, 'show']);
            Route::post('/', [ComplaintController::class, 'store']);
            Route::patch('/{id}/assign', [ComplaintController::class, 'assign']);
            Route::patch('/{id}/escalate', [ComplaintController::class, 'escalate']);
            Route::patch('/{id}/resolve', [ComplaintController::class, 'resolve']);
        });

        Route::prefix('revenue')->group(function () {
            Route::get('/summary', [RevenueController::class, 'summary']);
            Route::get('/chart', [RevenueController::class, 'chart']);
        });
        Route::prefix('occupancy')->group(function () {
            Route::get('/summary', [ManagerOccupancyController::class, 'summary']);
            Route::get('/chart', [ManagerOccupancyController::class, 'chart']);
            Route::get('/reservations', [ManagerOccupancyController::class, 'reservations']);
        });
        Route::prefix('staff')->group(function () {
            Route::get('/', [StaffController::class, 'index']);
        });
        Route::prefix('operations')->group(function () {
            Route::get('/orders', [ManagerOperationsController::class, 'orders']);
            Route::get('/deliveries', [ManagerOperationsController::class, 'deliveries']);
            Route::get('/housekeeping', [ManagerOperationsController::class, 'housekeeping']);
            Route::get('/laundry', [ManagerOperationsController::class, 'laundry']);
        });
        Route::prefix('waiters')->group(function () {
            Route::get('/', [WaiterManagementController::class, 'index']);
            Route::get('/available', [WaiterManagementController::class, 'available']);
            Route::get('/available-users', [WaiterManagementController::class, 'availableUsers']);
            Route::post('/', [WaiterManagementController::class, 'store']);
            Route::get('/{waiter}', [WaiterManagementController::class, 'show']);
            Route::put('/{waiter}', [WaiterManagementController::class, 'update']);
            Route::delete('/{waiter}', [WaiterManagementController::class, 'destroy']);
            Route::patch('/{waiter}/deactivate', [WaiterManagementController::class, 'deactivate']);
            Route::patch('/{waiter}/reactivate', [WaiterManagementController::class, 'reactivate']);
            Route::patch('/{waiter}/suspend', [WaiterManagementController::class, 'suspend']);
            Route::patch('/{waiter}/availability', [WaiterManagementController::class,'changeAvailability']);
            Route::get('/{waiter}/stats', [WaiterManagementController::class, 'stats']);
        });
        Route::prefix('floors')->group(function () {
            Route::prefix('assignments')->group(function () {
                Route::get('/today', [FloorAssignmentController::class, 'today']);
                Route::get('/stats', [FloorAssignmentController::class, 'stats']);
                Route::get('/', [FloorAssignmentController::class, 'index']);
                Route::post('/', [FloorAssignmentController::class, 'store']);
                Route::patch('/{assignment}', [FloorAssignmentController::class, 'update']);
                Route::delete('/{assignment}', [FloorAssignmentController::class, 'destroy']);
            });
            Route::get('/', [FloorManagementController::class, 'index']);
            Route::post('/', [FloorManagementController::class, 'store']);
            Route::get('/{floor}', [FloorManagementController::class, 'show']);
            Route::put('/{floor}', [FloorManagementController::class, 'update']);
            Route::delete('/{floor}', [FloorManagementController::class, 'destroy']);
            Route::patch('/{floor}/deactivate', [FloorManagementController::class, 'deactivate']);
            Route::patch('/{floor}/activate', [FloorManagementController::class, 'activate']);
            Route::get('/{floor}/stats', [FloorManagementController::class, 'stats']);
        });
        
        // Restaurant Table Assignment Routes (Waiters to Tables for Walk-in Customers)
        Route::prefix('table-assignments')->group(function () {
            Route::get('/today', [WaiterTableAssignmentController::class, 'today']);
            Route::get('/stats', [WaiterTableAssignmentController::class, 'stats']);
            Route::get('/', [WaiterTableAssignmentController::class, 'index']);
            Route::post('/', [WaiterTableAssignmentController::class, 'store']);
            Route::patch('/{id}', [WaiterTableAssignmentController::class, 'update']);
            Route::delete('/{id}', [WaiterTableAssignmentController::class, 'destroy']);
            Route::get('/table/{tableId}/assigned-waiter', [WaiterTableAssignmentController::class, 'getAssignedWaiter']);
            Route::get('/waiter/{waiterId}/tables', [WaiterTableAssignmentController::class, 'getWaiterTables']);
        });
        
        Route::prefix('shifts')->group(function () {
            Route::get('/', [ShiftManagementController::class, 'index']);
            Route::post('/', [ShiftManagementController::class, 'store']);
            Route::get('/current', [ShiftManagementController::class, 'current']);
            Route::get('/{shift}', [ShiftManagementController::class, 'show']);
            Route::put('/{shift}', [ShiftManagementController::class, 'update']);
            Route::delete('/{shift}', [ShiftManagementController::class, 'destroy']);
            Route::patch('/{shift}/deactivate', [ShiftManagementController::class, 'deactivate']);
            Route::patch('/{shift}/activate', [ShiftManagementController::class, 'activate']);
            Route::get('/{shift}/stats', [ShiftManagementController::class, 'stats']);
        });
        Route::prefix('deliveries')->group(function () {
            Route::get('/', [ManagerDeliveryManagementController::class, 'index']);
            Route::get('/summary/today', [ManagerDeliveryManagementController::class, 'todaySummary']);
            Route::get('/report', [ManagerDeliveryManagementController::class, 'report']);
            Route::get('/waiting/assignment', [ManagerDeliveryManagementController::class, 'waitingAssignment']);
            Route::get('/{delivery}', [ManagerDeliveryManagementController::class, 'show']);
            Route::patch('/{delivery}/reassign', [ManagerDeliveryManagementController::class, 'reassign']);
            Route::patch('/{delivery}/assign', [ManagerDeliveryManagementController::class, 'manuallyAssign']);
            Route::delete('/{delivery}', [ManagerDeliveryManagementController::class, 'destroy']);
        });
        
        // Restaurant Tables Management
        Route::prefix('restaurant-tables')->group(function () {
            Route::get('/', [RestaurantTableController::class, 'index']);
            Route::get('/statistics', [RestaurantTableController::class, 'statistics']);
            Route::get('/{id}', [RestaurantTableController::class, 'show']);
            Route::post('/', [RestaurantTableController::class, 'store']);
            Route::put('/{id}', [RestaurantTableController::class, 'update']);
            Route::delete('/{id}', [RestaurantTableController::class, 'destroy']);
            Route::post('/{id}/regenerate-qr', [RestaurantTableController::class, 'regenerateQR']);
            Route::get('/{id}/download-qr', [RestaurantTableController::class, 'downloadQR']);
        });

        // Restaurant Sections Management
        Route::prefix('restaurant-sections')->group(function () {
            Route::get('/', [RestaurantSectionController::class, 'index']);
            Route::get('/{id}', [RestaurantSectionController::class, 'show']);
            Route::post('/', [RestaurantSectionController::class, 'store']);
            Route::put('/{id}', [RestaurantSectionController::class, 'update']);
            Route::delete('/{id}', [RestaurantSectionController::class, 'destroy']);
        });
        
        Route::prefix('analytics')->group(function () {
            Route::get('/', [ManagerAnalyticsController::class, 'index']);
        });
        Route::prefix('activities')->group(function () {
            Route::get('/', [ManagerActivityController::class, 'activities']);
        });
        Route::prefix('notifications')->group(function () {
            Route::get('/', [ManagerActivityController::class, 'notifications']);
            Route::post('/', [ManagerActivityController::class, 'storeNotification']);
            Route::put('/{notification}', [ManagerActivityController::class, 'updateNotification']);
            Route::delete('/{notification}', [ManagerActivityController::class, 'destroyNotification']);
            Route::patch('/{notification}/read', [ManagerActivityController::class, 'markAsRead']);
        });
        Route::prefix('settings')->group(function () {
            Route::get('/dashboard', [ManagerSettingsController::class, 'dashboardSettings']);
            Route::put('/dashboard/{setting}', [ManagerSettingsController::class, 'updateDashboardSettings']);
            Route::get('/announcements', [ManagerSettingsController::class, 'announcements']);
            Route::post('/announcements', [ManagerSettingsController::class, 'storeAnnouncement']);
            Route::put('/announcements/{announcement}', [ManagerSettingsController::class, 'updateAnnouncement']);
            Route::delete('/announcements/{announcement}', [ManagerSettingsController::class, 'destroyAnnouncement']);
            
            Route::get('/reports', [ManagerSettingsController::class, 'reports']);
        });
    });
    
    Route::middleware('role:staff|admin|manager|waiter')->prefix('waiter')->group(function () {
        Route::prefix('dashboard')->group(function () {
            Route::get('/', [WaiterDashboardController::class, 'getDashboard']);
            Route::get('/today', [WaiterDashboardController::class, 'getTodayStats']);
            Route::get('/performance', [WaiterDashboardController::class, 'getPerformance']);
            Route::get('/recent-assignments', [WaiterDashboardController::class, 'getRecentAssignments']);
            Route::get('/kitchen-ready-orders', [WaiterDashboardController::class, 'getKitchenReadyOrders']);
            Route::get('/ready-pickup', [WaiterDashboardController::class, 'getReadyForPickup']);
            Route::get('/pending-pickup', [WaiterDashboardController::class, 'getPendingPickupOrders']);
            Route::get('/on-delivery', [WaiterDashboardController::class, 'getOnDelivery']);
            Route::get('/completed', [WaiterDashboardController::class, 'getCompletedDeliveries']);
            Route::get('/failed', [WaiterDashboardController::class, 'getFailedDeliveries']);
            Route::get('/timeline', [WaiterDashboardController::class, 'getDeliveryTimeline']);
            Route::get('/weekly-performance', [WaiterDashboardController::class, 'getWeeklyPerformance']);
            Route::get('/monthly-performance', [WaiterDashboardController::class, 'getMonthlyPerformance']);
            Route::get('/performance-comparison', [WaiterDashboardController::class, 'getPerformanceComparison']);
            Route::get('/quick-stats', [WaiterDashboardController::class, 'getQuickStats']);
        });
        // Assignments
        Route::prefix('assignments')->group(function () {
            Route::get('/', [WaiterAssignmentController::class, 'index']);
            Route::get('/{id}', [WaiterAssignmentController::class, 'show']);
            Route::get('/pending/list', [WaiterAssignmentController::class, 'getPending']);
            Route::get('/active/list', [WaiterAssignmentController::class, 'getActive']);
            Route::get('/today/list', [WaiterAssignmentController::class, 'getToday']);
            Route::patch('/{id}/accept', [WaiterAssignmentController::class, 'accept']);
            Route::patch('/{id}/reject', [WaiterAssignmentController::class, 'reject']);
            Route::patch('/{id}/pickup', [WaiterAssignmentController::class, 'pickup']);
            Route::patch('/{id}/start-delivery', [WaiterAssignmentController::class, 'startDelivery']);
            Route::patch('/{id}/deliver', [WaiterAssignmentController::class, 'deliver']);
            Route::patch('/{id}/failed', [WaiterAssignmentController::class, 'failed']);
        });
        Route::prefix('history')->group(function () {
            Route::get('/', [WaiterHistoryController::class, 'getHistory']);
            Route::get('/export', [WaiterHistoryController::class, 'exportHistory']);
        });
        
        Route::prefix('performance-history')->group(function () {
            Route::get('/', [WaiterHistoryController::class, 'getPerformanceHistory']);
        });
        Route::prefix('report')->group(function () {
            Route::get('/performance', [WaiterHistoryController::class, 'getPerformanceReport']);
            Route::get('/performance/export', [WaiterHistoryController::class, 'exportPerformanceReport']);
            Route::get('/trend', [WaiterHistoryController::class, 'getPerformanceTrend']);
            Route::get('/delivery-time-distribution', [WaiterHistoryController::class, 'getDeliveryTimeDistribution']);
            Route::get('/monthly-average', [WaiterHistoryController::class, 'getMonthlyAverage']);
        });
        
        Route::get('/stats', [WaiterHistoryController::class, 'getStatistics']);
        
        // Profile
        Route::prefix('profile')->group(function () {
            Route::get('/', [WaiterProfileController::class, 'getProfile']);
            Route::put('/', [WaiterProfileController::class, 'updateProfile']);
            Route::post('/photo', [WaiterProfileController::class, 'uploadPhoto']);
            Route::post('/change-password', [WaiterProfileController::class, 'changePassword']);
            Route::get('/stats', [WaiterProfileController::class, 'getStats']);
            Route::get('/performance', [WaiterProfileController::class, 'getPerformanceOverview']);
            Route::get('/ratings', [WaiterProfileController::class, 'getRatingHistory']);
            Route::get('/shift', [WaiterProfileController::class, 'getShiftInfo']);
            Route::get('/availability', [WaiterProfileController::class, 'getAvailability']);
        });
        
        // Settings
        Route::prefix('settings')->group(function () {
            Route::get('/', [WaiterProfileController::class, 'getSettings']);
            Route::put('/', [WaiterProfileController::class, 'updateSettings']);
        });

        // Notifications
        Route::prefix('notifications')->group(function () {
            Route::get('/', [WaiterNotificationController::class, 'getNotifications']);
            Route::get('/unread-count', [WaiterNotificationController::class, 'getUnreadCount']);
            Route::get('/unread', [WaiterNotificationController::class, 'getUnread']);
            Route::get('/stats', [WaiterNotificationController::class, 'getStats']);
            Route::patch('/{id}/read', [WaiterNotificationController::class, 'markAsRead']);
            Route::patch('/read-all', [WaiterNotificationController::class, 'markAllAsRead']);
            Route::delete('/{id}', [WaiterNotificationController::class, 'deleteNotification']);
            Route::delete('/', [WaiterNotificationController::class, 'deleteAll']);
        });
    });
    Route::middleware('role:staff')->prefix('cashier')->group(function () {
        // Cashier Profile Routes
        Route::prefix('profile')->group(function () {
            Route::get('/', [CashierProfileController::class, 'getProfile']);
            Route::put('/', [CashierProfileController::class, 'updateProfile']);
            Route::post('/photo', [CashierProfileController::class, 'uploadPhoto']);
            Route::post('/change-password', [CashierProfileController::class, 'changePassword']);
            Route::get('/stats', [CashierProfileController::class, 'getStats']);
            Route::post('/status', [CashierProfileController::class, 'updateStatus']);
        });
        
        // Dashboard
        Route::prefix('dashboard')->group(function () {
            Route::get('/', [CashierDashboardController::class, 'index']);
            Route::get('/recent-payments', [CashierDashboardController::class, 'recentPayments']);
            Route::get('/pending-payments', [CashierDashboardController::class, 'pendingPayments']);
            Route::get('/recent-transactions', [CashierDashboardController::class, 'recentTransactions']);
            Route::get('/revenue-chart', [CashierDashboardController::class, 'revenueChart']);
            Route::get('/payment-method-chart', [CashierDashboardController::class, 'paymentMethodChart']);
            Route::get('/refund-requests', [CashierDashboardController::class, 'refundRequests']);
            Route::get('/orders', [CashierDashboardController::class, 'activeOrders']);
            Route::post('/orders/{id}/clear', [CashierDashboardController::class, 'clearOrder']);
        });

        // Payments Management
        Route::prefix('payments')->group(function () {
            Route::get('/', [CashierPaymentController::class, 'index']);
            Route::get('/{id}', [CashierPaymentController::class, 'show']);
            Route::post('/{id}/refund', [CashierPaymentController::class, 'refund']);
        });
        Route::prefix('reports')->group(function () {
            Route::get('/revenue', [CashierReportController::class, 'revenueReport']);
            Route::get('/payment', [CashierReportController::class, 'paymentReport']);
            Route::get('/refund', [CashierReportController::class, 'refundReport']);
        });
    });
});
Route::prefix('menu-items/{menuItemId}/reviews')->group(function () {
    Route::get('/', [PublicReviewController::class, 'index']);
});

Route::get('/menu-items/{menuItemId}/review-stats', [PublicReviewController::class, 'stats']);

Route::prefix('reviews/{reviewId}')->group(function () {
    Route::post('/vote', [VotingController::class, 'vote']);
    Route::get('/votes', [VotingController::class, 'getCounts']);
});

Route::middleware('auth:sanctum')->prefix('reviews')->group(function () {
    Route::post('/', [ReviewController::class, 'store']);
    Route::get('/{id}', [ReviewController::class, 'show']);
    Route::put('/{id}', [ReviewController::class, 'update']);
    Route::delete('/{id}', [ReviewController::class, 'destroy']);
});

Route::post('/guest-reviews', [ReviewController::class, 'storeGuest']);

Route::middleware('auth:sanctum')->get('/guests/{guestId}/eligible-reviews', [ReviewController::class, 'eligibleItems']);

Route::middleware(['auth:sanctum', 'role:manager,admin'])->prefix('admin/reviews')->group(function () {
    Route::get('/', [ModerationController::class, 'index']);
    Route::post('/{id}/approve', [ModerationController::class, 'approve']);
    Route::post('/{id}/reject', [ModerationController::class, 'reject']);
    Route::delete('/{id}', [ModerationController::class, 'destroy']);
    Route::get('/stats', [ModerationController::class, 'stats']);
});

Route::middleware(['auth:sanctum', 'role:manager,admin'])->prefix('admin')->group(function () {
    Route::prefix('reviews/{reviewId}/response')->group(function () {
        Route::post('/', [ResponseController::class, 'store']);
    });

    Route::prefix('responses/{id}')->group(function () {
        Route::put('/', [ResponseController::class, 'update']);
        Route::delete('/', [ResponseController::class, 'destroy']);
    });
});

Route::middleware(['auth:sanctum', 'role:manager,admin'])->prefix('admin/analytics')->group(function () {
    Route::get('/menu-items/{id}/review-stats', [AnalyticsController::class, 'itemStats']);
    Route::get('/reviews/top-rated', [AnalyticsController::class, 'topRated']);
    Route::get('/reviews/lowest-rated', [AnalyticsController::class, 'lowestRated']);
    Route::get('/reviews/pending-count', [AnalyticsController::class, 'pendingCount']);
    Route::get('/reviews/trends', [AnalyticsController::class, 'trends']);
    Route::get('/reviews/overall', [AnalyticsController::class, 'overall']);
});

Route::middleware('auth:sanctum')->prefix('notifications/reviews')->group(function () {
    Route::get('/', [ReviewNotificationController::class, 'index']);
    Route::get('/unread-count', [ReviewNotificationController::class, 'unreadCount']);
    Route::post('/{id}/read', [ReviewNotificationController::class, 'markAsRead']);
});

// Payment Gateway Routes (authenticated staff/admin)
Route::middleware(['auth:sanctum', 'role:staff'])->prefix('payment-gateway')->group(function () {
    Route::post('/callback', [PaymentGatewayController::class, 'handleCallback']);
    Route::get('/status/{paymentId}', [PaymentGatewayController::class, 'getPaymentStatus']);
    Route::get('/history', [PaymentGatewayController::class, 'getPaymentHistory']);
    Route::post('/refund', [PaymentGatewayController::class, 'processRefund']);
    Route::get('/revenue-report', [PaymentGatewayController::class, 'getRevenueReport']);
});

// Booking Analytics Routes (authenticated admin/manager)
Route::middleware(['auth:sanctum', 'role:admin|manager'])->prefix('booking-analytics')->group(function () {
    Route::get('/occupancy-rate', [BookingAnalyticsController::class, 'getOccupancyRate']);
    Route::get('/revenue-analytics', [BookingAnalyticsController::class, 'getRevenueAnalytics']);
    Route::get('/booking-trends', [BookingAnalyticsController::class, 'getBookingTrends']);
    Route::get('/guest-statistics', [BookingAnalyticsController::class, 'getGuestStatistics']);
    Route::get('/dashboard-summary', [BookingAnalyticsController::class, 'getDashboardSummary']);
});

// Cancellation Policy Routes (authenticated staff)
Route::middleware(['auth:sanctum', 'role:staff'])->prefix('cancellation-policies')->group(function () {
    Route::get('/', [CancellationPolicyController::class, 'index']);
    Route::post('/', [CancellationPolicyController::class, 'store']);
    Route::get('/{policyId}', [CancellationPolicyController::class, 'show']);
    Route::put('/{policyId}', [CancellationPolicyController::class, 'update']);
    Route::delete('/{policyId}', [CancellationPolicyController::class, 'destroy']);
    Route::post('/{policyId}/calculate-refund', [CancellationPolicyController::class, 'calculateRefund']);
});


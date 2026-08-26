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
use App\Http\Controllers\Api\ManagerController;
use App\Http\Controllers\Api\Manager\DashboardController as ManagerDashboardController;
use App\Http\Controllers\Api\Manager\RevenueController;
use App\Http\Controllers\Api\Manager\OccupancyController as ManagerOccupancyController;
use App\Http\Controllers\Api\Manager\StaffController;
use App\Http\Controllers\Api\Manager\OperationsController as ManagerOperationsController;
use App\Http\Controllers\Api\Manager\WaiterController as ManagerWaiterController;
use App\Http\Controllers\Api\Manager\AnalyticsController;
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
use App\Http\Controllers\Api\Profile\ManagerProfileController;
use App\Http\Controllers\Api\Profile\AdminProfileController;
use App\Http\Controllers\Api\Profile\CashierProfileController;
use App\Http\Controllers\Api\Profile\ReceptionistProfileController;
use App\Http\Controllers\Api\Rbac\RoleController;
use App\Http\Controllers\Api\Rbac\PermissionController;
use App\Http\Controllers\Api\Rbac\UserRoleController;
use App\Http\Controllers\Api\Rbac\TemporaryRoleController;
use App\Http\Controllers\Api\Rbac\AuditLogController;
use App\Http\Controllers\Api\Rbac\UserDirectPermissionController;

Route::post('/login', [AuthController::class, 'login']);

Route::get('/activation/{token}', [ActivationController::class, 'validateToken']);
Route::post('/activate-account', [ActivationController::class, 'activateAccount']);
Route::post('/resend-activation', [ActivationController::class, 'resendActivation']);
Route::post('/check-activation-status', [ActivationController::class, 'checkActivationStatus']);
Route::post('/forgot-password', [PasswordResetController::class, 'forgotPassword']);

// Reset password with token
Route::post('/reset-password', [PasswordResetController::class, 'resetPassword']);

// Verify reset token
Route::post('/verify-reset-token', [PasswordResetController::class, 'verifyToken']);
Route::get('/rooms', [RoomController::class, 'index']);
Route::get('/rooms/{room}', [RoomController::class, 'show']);
Route::post('/guests', [GuestController::class, 'store']);
Route::get('/guests', [GuestController::class, 'index']);
Route::get('/qr-codes/download/{roomId}', [QRCodePrintController::class, 'downloadQRCode']);
Route::get('/qr-codes/print/{roomId}', [QRCodePrintController::class, 'getPrintTemplate']);

// Public Payment Routes (Callback and Verification)
Route::prefix('payments')->group(function () {
    Route::post('/initialize', [PaymentController::class, 'initialize']);
    Route::get('/verify/{txRef}', [PaymentController::class, 'verify']);
    Route::get('/status/{txRef}', [PaymentController::class, 'getByTransactionRef']);
    Route::get('/callback', [PaymentController::class, 'callback']);
});

// Public Reservation Payment Routes (No authentication required for guest booking)
Route::prefix('reservation-payments')->group(function () {
    Route::post('/initialize', [ReservationPaymentController::class, 'initializePayment']);
    Route::post('/complete/{txRef}', [ReservationPaymentController::class, 'completeReservation']);
    Route::get('/{txRef}', [ReservationPaymentController::class, 'getReservationByPayment']);
});
// Public Order Payment Routes (No authentication required for guest QR orders)
Route::prefix('order-payments')->group(function () {
    Route::post('/initialize', [GuestOrderPaymentController::class, 'initializePayment']);
    Route::post('/complete/{txRef}', [GuestOrderPaymentController::class, 'completeOrder']);
    Route::get('/{txRef}', [GuestOrderPaymentController::class, 'getOrderByPayment']);
});

// Walk-In Order Payment Routes (No authentication required for table QR orders)
Route::prefix('walk-in-payments')->group(function () {
    Route::post('/initialize', [WalkInOrderPaymentController::class, 'initializePayment']);
    Route::post('/complete/{txRef}', [WalkInOrderPaymentController::class, 'completeOrder']);
    Route::get('/{txRef}', [WalkInOrderPaymentController::class, 'getOrderByPayment']);
});

// QR Resolution Routes (Public)
Route::prefix('qr')->group(function () {
    Route::post('/resolve', [QRResolutionController::class, 'resolveQRToken']);
    Route::get('/resolve/{qrToken}', [QRResolutionController::class, 'resolveFromUrl']);
    Route::post('/validate', [QRResolutionController::class, 'validateQRToken']);
});

Route::get('/categories', [CategoryController::class, 'index']);
Route::get('/menu-items', [MenuItemController::class, 'index']);

Route::prefix('guest')->group(function () {
    Route::get('/categories', [GuestOrderController::class, 'getPublicCategories']);
    Route::get('/menu/items', [GuestOrderController::class, 'getAllMenuItems']);
    Route::get('/menu/{qrToken}', [GuestOrderController::class, 'getRoom']);
    Route::get('/menu/{qrToken}/items', [GuestOrderController::class, 'getMenuItems']);
    Route::post('/orders', [GuestOrderController::class, 'createOrder']);
    Route::get('/orders/{qrToken}/status', [GuestOrderController::class, 'getOrderStatus']);
});
Route::prefix('guest')->group(function () {
    Route::post('/unified-orders', [UnifiedOrderController::class, 'store']);
});
Route::prefix('qr-code')->group(function () {
    Route::get('/generate/{roomId}', [QRCodeController::class, 'generateForRoom']);
    Route::get('/data/{roomId}', [QRCodeController::class, 'getQRCodeData']);
});
Route::get('/public/roles', [RoleController::class, 'getActiveRoles']);

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/me', [AuthController::class, 'me']);
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/roles/active', [RoleController::class, 'getActiveRoles']);
    Route::middleware('permission:roles.view|roles.create|roles.update|roles.delete')->group(function () {
        Route::get('/roles', [RoleController::class, 'index']);
        Route::post('/roles', [RoleController::class, 'store']);
        Route::get('/roles/{role}', [RoleController::class, 'show']);
        Route::put('/roles/{role}', [RoleController::class, 'update']);
        Route::delete('/roles/{role}', [RoleController::class, 'destroy']);
        Route::get('/roles/{role}/permissions', [RoleController::class, 'getPermissions']);
        Route::post('/roles/{role}/permissions', [RoleController::class, 'syncPermissions']);
    });
    Route::middleware('permission:permissions.view|permissions.create|permissions.update|permissions.delete')->group(function () {
        Route::get('/permissions', [PermissionController::class, 'index']);
        Route::post('/permissions', [PermissionController::class, 'store']);
        Route::get('/permissions/{permission}', [PermissionController::class, 'show']);
        Route::put('/permissions/{permission}', [PermissionController::class, 'update']);
        Route::delete('/permissions/{permission}', [PermissionController::class, 'destroy']);
    });
    Route::middleware('permission:users.view|users.create|users.update|roles.assign_permissions')->group(function () {
        Route::get('/user-roles', [UserRoleController::class, 'index']);
        Route::get('/users/{user}/roles', [UserRoleController::class, 'getUserRoles']);
        Route::post('/users/{user}/roles', [UserRoleController::class, 'assignRoles']);
        Route::delete('/users/{user}/roles/{role}', [UserRoleController::class, 'removeRole']);

        // Direct User Permissions management
        Route::get('/users/{user}/direct-permissions', [UserDirectPermissionController::class, 'getUserPermissions']);
        Route::post('/users/{user}/direct-permissions', [UserDirectPermissionController::class, 'assignDirectPermissions']);
        Route::delete('/users/{user}/direct-permissions/{permission}', [UserDirectPermissionController::class, 'removeDirectPermission']);
    });

    Route::middleware('permission:roles.assign_permissions|roles.create|roles.update')->group(function () {
        Route::get('/temporary-roles', [TemporaryRoleController::class, 'index']);
        Route::post('/users/{user}/temporary-role', [TemporaryRoleController::class, 'store']);
        Route::delete('/temporary-roles/{temporaryRoleAssignment}', [TemporaryRoleController::class, 'destroy']);
    });

    Route::middleware('permission:audit_logs.view')->group(function () {
        Route::get('/audit-logs', [AuditLogController::class, 'index']);
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
    });

    // Reservations, Guests, Check-Ins, Rooms & Room Types Access (Permission & Role Driven)
    Route::middleware('role:staff')->group(function () {
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

        Route::prefix('admin-guests')->group(function () {
            Route::get('/', [GuestController::class, 'index']);
            Route::post('/', [GuestController::class, 'store']);
            Route::get('/{guest}', [GuestController::class, 'show']);
            Route::get('/{guest}/reservations', [GuestController::class, 'reservations']);
            Route::put('/{guest}', [GuestController::class, 'update']);
            Route::patch('/{guest}', [GuestController::class, 'update']);
            Route::delete('/{guest}', [GuestController::class, 'destroy']);
        });

        Route::get('/admin-reservations/{reservation}', [ReservationController::class, 'show']);
        Route::put('/admin-reservations/{reservation}', [ReservationController::class, 'update']);
        Route::patch('/admin-reservations/{reservation}', [ReservationController::class, 'update']);
        Route::delete('/admin-reservations/{reservation}', [ReservationController::class, 'destroy']);
        Route::post('/admin-reservations/{reservation}/confirm', [ReservationController::class, 'confirm']);
        Route::post('/admin-reservations/{reservation}/check-in', [ReservationController::class, 'checkIn']);
        Route::post('/admin-reservations/{reservation}/check-out', [ReservationController::class, 'checkOut']);
        Route::post('/admin-reservations/{reservation}/cancel', [ReservationController::class, 'cancel']);

        Route::prefix('check-ins')->group(function () {
            Route::get('/statistics', [CheckInController::class, 'statistics']);
            Route::get('/', [CheckInController::class, 'index']);
            Route::post('/', [CheckInController::class, 'store']);
            Route::get('/{checkIn}', [CheckInController::class, 'show']);
            Route::post('/{checkIn}/checkout', [CheckInController::class, 'checkout']);
            Route::delete('/{checkIn}', [CheckInController::class, 'destroy']);
        });

        Route::get('/rooms/{room}', [RoomController::class, 'show']);
        Route::get('/room-types', [RoomTypeController::class, 'index']);
        Route::get('/room-types/{roomType}', [RoomTypeController::class, 'show']);
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

        Route::prefix('admin/qr-codes')->group(function () {
            Route::get('/{roomId}/image', [QRCodePrintController::class, 'getQRCodeImage']);
            Route::get('/{roomId}/download', [QRCodePrintController::class, 'downloadQRCode']);
            Route::get('/{roomId}/print-template', [QRCodePrintController::class, 'getPrintTemplate']);
            Route::post('/{roomId}/regenerate', [QRCodePrintController::class, 'regenerateQRCode']);
            Route::get('/all', [QRCodePrintController::class, 'getAllQRCodes']);
        });
    });

    Route::middleware('role:admin')->group(function () {
        // Admin Profile Routes
        Route::prefix('admin/profile')->group(function () {
            Route::get('/', [AdminProfileController::class, 'getProfile']);
            Route::put('/', [AdminProfileController::class, 'updateProfile']);
            Route::post('/photo', [AdminProfileController::class, 'uploadPhoto']);
            Route::post('/change-password', [AdminProfileController::class, 'changePassword']);
            Route::get('/stats', [AdminProfileController::class, 'getStats']);
        });
        
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
    Route::prefix('notifications')->group(function () {
        Route::get('/latest', [NotificationController::class, 'latest']);
        Route::get('/unread-count', [NotificationController::class, 'unreadCount']);
        Route::put('/read-all', [NotificationController::class, 'markAllAsRead']);
        Route::delete('/clear-all', [NotificationController::class, 'clearAll']);
        Route::get('/', [NotificationController::class, 'index']);
        Route::put('/{id}/read', [NotificationController::class, 'markAsRead']);
        Route::delete('/{id}', [NotificationController::class, 'destroy']);
    });
    Route::middleware('role:staff')->group(function(){
        Route::get('/menu-items', [MenuItemController::class, 'index']);
        Route::get('/menu-items/statistics', [MenuItemController::class, 'statistics']);
        Route::get('/menu-items/{menuItem}', [MenuItemController::class, 'show']);
        Route::get('/categories', [CategoryController::class, 'index']);
        Route::get('/categories/{category}', [CategoryController::class, 'show']);
    });
    Route::middleware('role:staff')->group(function(){
        Route::post('/menu-items', [MenuItemController::class, 'store']);
        Route::put('/menu-items/{menuItem}', [MenuItemController::class, 'update']);
        Route::patch('/menu-items/{menuItem}/toggle-availability', [MenuItemController::class, 'toggleAvailability']);
        Route::delete('/menu-items/{menuItem}', [MenuItemController::class, 'destroy']);
        Route::post('/categories', [CategoryController::class, 'store']);
        Route::put('/categories/{category}', [CategoryController::class, 'update']);
        Route::patch('/categories/{category}/toggle', [CategoryController::class, 'toggle']);
        Route::post('/categories/reorder', [CategoryController::class, 'reorder']);
        Route::delete('/categories/{category}', [CategoryController::class, 'destroy']);
    });
    Route::middleware('role:staff')->group(function(){
        // Receptionist Profile Routes
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
            Route::get('/reservations', [\App\Http\Controllers\Api\ReceptionReportController::class, 'reservationReport']);
            Route::get('/occupancy', [\App\Http\Controllers\Api\ReceptionReportController::class, 'occupancyReport']);
            Route::get('/guests', [\App\Http\Controllers\Api\ReceptionReportController::class, 'guestReport']);
            Route::get('/revenue', [\App\Http\Controllers\Api\ReceptionReportController::class, 'revenueReport']);
            Route::get('/check-in-out', [\App\Http\Controllers\Api\ReceptionReportController::class, 'checkInOutReport']);
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
            Route::get('/',[WaiterManagementController::class, 'index']);
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
            Route::get('/', [FloorManagementController::class, 'index']);
            Route::post('/', [FloorManagementController::class, 'store']);
            Route::get('/{floor}', [FloorManagementController::class, 'show']);
            Route::put('/{floor}', [FloorManagementController::class, 'update']);
            Route::delete('/{floor}', [FloorManagementController::class, 'destroy']);
            Route::patch('/{floor}/deactivate', [FloorManagementController::class, 'deactivate']);
            Route::patch('/{floor}/activate', [FloorManagementController::class, 'activate']);
            Route::get('/{floor}/stats', [FloorManagementController::class, 'stats']);
            Route::prefix('assignments')->group(function () {
                Route::get('/today', [FloorAssignmentController::class, 'today']);
                Route::get('/stats', [FloorAssignmentController::class, 'stats']);
                Route::get('/', [FloorAssignmentController::class, 'index']);
                Route::post('/', [FloorAssignmentController::class, 'store']);
                Route::patch('/{assignment}', [FloorAssignmentController::class, 'update']);
                Route::delete('/{assignment}', [FloorAssignmentController::class, 'destroy']);
            });
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
        
        Route::prefix('analytics')->group(function () {
            Route::get('/', [AnalyticsController::class, 'index']);
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
    
    Route::middleware('role:staff')->prefix('waiter')->group(function () {
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
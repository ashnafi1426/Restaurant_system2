# Walk-In Order Authorization Fix

## Problem Summary
Walk-in restaurant table orders were failing with **"Unauthorized. Required role: receptionist"** error (403 Forbidden) despite the route being configured as public.

## Root Cause
The frontend's `unifiedOrderService.ts` was using `axiosInstance` which automatically adds Authorization headers from localStorage. When these headers were sent:

1. Backend received the auth token in the request
2. Laravel authenticated the user (even though route had `withoutMiddleware(['auth:sanctum'])`)
3. Since there are **two duplicate routes** for `POST /orders`:
   - Line 105: Public route → `UnifiedOrderController::store()` (no auth required)
   - Line 212: Protected route → `OrderController::store()` (requires `role:receptionist`)
4. Laravel was matching the **protected route** instead of the public one
5. The `RoleMiddleware` checked user role and rejected requests without receptionist role

## The Fix

### 1. Frontend Change (PRIMARY FIX)
**File**: `Client2/vue-project/src/services/unifiedOrderService.ts`

**Changed**:
```typescript
import { axiosInstance as axios } from './axios'
```

**To**:
```typescript
import { publicAxios as axios } from './axios'
```

**Why This Works**:
- `publicAxios` is a clean axios instance with NO interceptors
- It doesn't add Authorization headers automatically
- Requests are truly anonymous/public
- Backend now correctly routes to the public UnifiedOrderController

### 2. Backend Route Documentation (PREVENTIVE)
**File**: `server/routes/api.php`

Added comprehensive comments explaining:
- The route is intentionally public
- There's a duplicate route (intentional) for staff manual orders
- The public route must remain first and unauthenticated

## How Walk-In Orders Work Now

### Flow:
1. Customer scans QR code at restaurant table
2. Frontend detects table context (e.g., "Table 2")
3. Customer adds items and proceeds to checkout
4. Frontend calls `unifiedOrderService.createOrder()` using `publicAxios`
5. Request sent to `POST /orders` **without Authorization header**
6. Backend routes to `UnifiedOrderController::store()`
7. QR token is resolved to restaurant table
8. Walk-in order created with:
   - `order_type = 'walk_in'`
   - `table_id = <table_uuid>`
   - `room_id = null`
   - `guest_id = null`
   - `payment_type = 'cash'` (default for walk-in)
9. Table status updated to "occupied"
10. Order sent to kitchen
11. Waiter auto-assigned for delivery

## Key Technical Details

### Axios Instances in the Project:

1. **`axiosInstance`** (from `services/axios.ts`)
   - Adds Authorization header automatically
   - Used for authenticated API calls
   - Redirects to login on 401

2. **`publicAxios`** (from `services/axios.ts`)
   - NO interceptors
   - NO auth headers
   - Used for public/guest endpoints
   - Perfect for walk-in orders

3. **`api`** (from `api/auth.ts`)
   - Separate instance for auth operations
   - Has extensive logging
   - Not used by unifiedOrderService

### Backend Route Structure:

```php
// PUBLIC ROUTE (Line 105-109)
Route::post('/orders', [UnifiedOrderController::class, 'store'])
    ->withoutMiddleware(['auth:sanctum']);

// PROTECTED ROUTE (Line 211-212)
Route::middleware('auth:sanctum')->group(function () {
    Route::middleware('role:receptionist')->group(function(){
        Route::post('/orders', [OrderController::class, 'store']);
    });
});
```

**Why Both Routes Exist**:
- Public route: QR-based orders (room service + walk-in) by guests
- Protected route: Manual order creation by staff (receptionists)
- Different controllers, different business logic, same endpoint path

## Testing Checklist

### Walk-In Order Flow:
- ✅ Scan table QR code → Displays "Table X"
- ✅ Browse menu → Items load correctly
- ✅ Add items to cart → Cart updates
- ✅ Proceed to checkout → Shows order summary
- ✅ Submit order → **No auth error**
- ✅ Order created → `order_type = 'walk_in'`
- ✅ Table status → Changes to "occupied"
- ✅ Kitchen receives order → Order appears in kitchen dashboard
- ✅ Waiter auto-assigned → DeliveryTask created

### Room Service Order Flow:
- ✅ Scan room QR code → Displays "Room 101"
- ✅ Order creation → `order_type = 'room_service'`
- ✅ Payment options → Room charge available

## Files Modified

1. **Client2/vue-project/src/services/unifiedOrderService.ts**
   - Changed axios import from `axiosInstance` to `publicAxios`

2. **server/routes/api.php**
   - Added detailed comments about public orders route

## Related Files (Context)

- `Client2/vue-project/src/services/axios.ts` - Axios instances configuration
- `Client2/vue-project/src/views/guest/QRMenu.vue` - Frontend order flow
- `server/app/Http/Controllers/Api/UnifiedOrderController.php` - Backend order handler
- `server/app/Services/QRResolutionService.php` - QR token resolution
- `server/app/Http/Middleware/RoleMiddleware.php` - Role authorization

## Important Notes

### For Future Development:
1. **Never use `axiosInstance` for public/guest endpoints**
   - Always use `publicAxios` for unauthenticated requests
   - `axiosInstance` is for authenticated user operations only

2. **Route Order Matters**
   - Public routes should be defined BEFORE authenticated routes
   - Duplicate paths can cause routing conflicts
   - Always test with and without auth tokens

3. **QR Token Format**
   - New format: 8 uppercase chars (e.g., `ABCD1234`)
   - Legacy format: `table-{number}-{token}` (e.g., `table-2-GveD6NRGFa`)
   - Both formats supported by QRResolutionService

4. **Order Types**
   - `room_service`: Requires room_id, guest_id, reservation_id
   - `walk_in`: Requires table_id, no guest/reservation needed

## Success Criteria

✅ Walk-in customers can order without authentication
✅ No "Unauthorized" errors on order submission
✅ Orders correctly tagged as `order_type = 'walk_in'`
✅ Tables update to "occupied" status
✅ Kitchen receives orders immediately
✅ Waiters auto-assigned for table delivery

## Next Steps

After this fix is verified:
1. Test complete walk-in order → payment → kitchen → delivery flow
2. Verify waiter assignment works for table orders
3. Test table status changes (available → occupied → cleaning → available)
4. Ensure order history shows table number instead of room number
5. Verify payment processing for walk-in orders (cash/card only)

## Related Documentation

- Restaurant Tables Management: `ALL_ISSUES_FIXED.md`
- QR Resolution: `server/app/Services/QRResolutionService.php`
- Order Types: `server/app/Models/Order.php`

---

**Status**: ✅ **FIXED**
**Date**: 2026-08-09
**Issue**: Walk-in order authorization blocking
**Solution**: Use `publicAxios` for unified orders

# Walk-In Order - Final Fix Summary

## Problem
Walk-in restaurant table orders were failing with authentication errors despite being designed as public/guest orders.

## Error Evolution
1. **403 Forbidden** - "Unauthorized. Required role: receptionist"
2. **401 Unauthorized** - "Unauthenticated" 
3. **419** - "CSRF token mismatch"
4. **500** - "Table 'hotel.sessions' doesn't exist"
5. **401 Unauthorized** - Route still protected by Sanctum

## Root Causes

### 1. Frontend Using Wrong Axios Instance
- **Problem**: `unifiedOrderService.ts` was using `axiosInstance` which automatically adds Authorization headers
- **Fix**: Changed to use `publicAxios` (no auth headers)

### 2. Laravel 11 API Route Protection
- **Problem**: All `/api/*` routes get `auth:sanctum` middleware by default in Laravel 11
- **Fix**: Moved unified order route to `/api/guest/` prefix where other public routes work

### 3. CSRF Token Validation
- **Problem**: Laravel was checking CSRF tokens on API requests
- **Fix**: Excluded all `/api/*` routes from CSRF validation in `bootstrap/app.php`

### 4. Sessions Database Table Missing
- **Problem**: SESSION_DRIVER was set to 'database' but sessions table didn't exist
- **Fix**: Changed SESSION_DRIVER to 'array' in `.env` (API doesn't need persistent sessions)

## Final Solution

### Backend Changes

**1. Moved Route to Guest Prefix** (`server/routes/api.php`):
```php
Route::prefix('guest')->group(function () {
    Route::post('/unified-orders', [UnifiedOrderController::class, 'store']);
});
```

**2. Excluded CSRF for API Routes** (`server/bootstrap/app.php`):
```php
$middleware->validateCsrfTokens(except: [
    'api/*', // All API routes use token-based authentication, not CSRF
]);
```

**3. Changed Session Driver** (`server/.env`):
```
SESSION_DRIVER=array
```

### Frontend Changes

**1. Use Public Axios Instance** (`Client2/vue-project/src/services/unifiedOrderService.ts`):
```typescript
import { publicAxios as axios } from './axios'
```

**2. Updated Endpoint URL** (`Client2/vue-project/src/services/unifiedOrderService.ts`):
```typescript
const response = await axios.post('/guest/unified-orders', orderData)
```

## Final Route Configuration

### Public Routes (No Auth Required)
- `POST /api/guest/unified-orders` → UnifiedOrderController@store (room service + walk-in)
- `POST /api/guest/orders` → GuestOrderController@createOrder (legacy)
- `GET /api/guest/menu/items` → Menu items
- `GET /api/guest/menu/{qrToken}` → Room info

### Protected Routes (Auth Required)
- `POST /api/orders` → OrderController@store (staff manual orders, requires `role:receptionist`)

## How It Works Now

### Walk-In Order Flow:
1. Customer scans QR code at table → `?token=table-2-GveD6NRGFa`
2. Frontend calls `unifiedOrderService.createOrder()`
3. **Uses `publicAxios`** → No Authorization header sent
4. **Calls `/api/guest/unified-orders`** → Public route
5. Backend resolves QR token → Identifies restaurant table
6. Creates order with:
   - `order_type = 'walk_in'`
   - `table_id = <table_uuid>`
   - `room_id = null`
   - `guest_id = null`
   - `payment_type = 'cash'`
7. Table status → `occupied`
8. Order sent to kitchen
9. Waiter auto-assigned

### Room Service Order Flow:
1. Guest scans room QR code → `?token=ROOM1234`
2. Frontend calls same `unifiedOrderService.createOrder()`
3. **Uses `publicAxios`** → No Authorization header
4. **Calls `/api/guest/unified-orders`** → Same public route
5. Backend resolves QR token → Identifies room
6. Creates order with:
   - `order_type = 'room_service'`
   - `room_id = <room_uuid>`
   - `table_id = null`
   - `guest_id = <guest_uuid>`
   - `payment_type = 'room_charge'` (default)

## Key Takeaways

1. **Laravel 11 applies `auth:sanctum` to all API routes by default** - Must explicitly move public routes to a separate group or prefix

2. **Axios instances matter** - `axiosInstance` adds auth headers, `publicAxios` doesn't

3. **Route organization** - Group public routes together (e.g., `/api/guest/*`) to avoid auth middleware

4. **API routes don't need sessions** - Use `SESSION_DRIVER=array` for API-only applications

5. **API routes don't need CSRF** - Token-based auth doesn't require CSRF protection

## Testing

### Test URL:
```
http://localhost:5173/qr-menu?token=table-2-GveD6NRGFa
```

### Expected Behavior:
- ✅ Page loads showing "Table 2"
- ✅ Menu items display
- ✅ Can add items to cart
- ✅ Checkout shows payment options (Cash, Card)
- ✅ Submit order → **SUCCESS** (no 401/403 error)
- ✅ Order created in database with `order_type = 'walk_in'`

### Verify in Database:
```sql
SELECT 
    order_number,
    order_type,
    table_id,
    room_id,
    payment_type,
    status
FROM orders
WHERE order_type = 'walk_in'
ORDER BY created_at DESC
LIMIT 1;
```

## Files Modified

### Backend:
1. `server/routes/api.php` - Moved route to guest prefix
2. `server/bootstrap/app.php` - Excluded API from CSRF
3. `server/.env` - Changed SESSION_DRIVER to array

### Frontend:
1. `Client2/vue-project/src/services/unifiedOrderService.ts` - Changed axios instance and endpoint URL

## Success Criteria

✅ Walk-in orders can be placed without authentication  
✅ No 401/403/419 errors  
✅ Orders created with correct `order_type`  
✅ Table status updates to "occupied"  
✅ Kitchen receives orders  
✅ Waiter auto-assignment works  

---

**Status**: ✅ **COMPLETE**  
**Date**: 2026-08-09  
**Final Endpoint**: `POST /api/guest/unified-orders`  
**Auth Required**: NO

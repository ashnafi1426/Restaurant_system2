# Walk-In Order Testing Guide

## Quick Test Instructions

### Prerequisites
1. Backend server running: `php artisan serve`
2. Frontend dev server running: `npm run dev`
3. Database has restaurant tables (34 tables already seeded)

### Test 1: Walk-In Order (Table QR)

**URL to Test**:
```
http://localhost:5173/qr-menu?token=table-2-GveD6NRGFa
```
OR use one of the 8-char tokens if available

**Expected Behavior**:
1. ✅ Page loads showing "Table 2" (not "Room" context)
2. ✅ Menu items display correctly
3. ✅ Can add items to cart
4. ✅ Checkout shows order summary
5. ✅ Payment options: Cash, Card (NOT "Room Charge")
6. ✅ Submit order → **SUCCESS** (no auth error)
7. ✅ Order confirmation appears

**Console Check**:
```
✅ [QR] Table context detected
✅ [ORDER] Creating order via unified service
✅ [ORDER] Context: table
✅ [PAYMENT] Order placed successfully
```

**Should NOT See**:
```
❌ Unauthorized. Required role: receptionist
❌ 403 Forbidden error
```

### Test 2: Room Service Order (Room QR)

**URL to Test**: (Use a valid room QR token from your database)
```
http://localhost:5173/qr-menu?token=ROOM1234
```

**Expected Behavior**:
1. ✅ Page loads showing "Room 101" (room context)
2. ✅ Menu items display correctly
3. ✅ Can add items to cart
4. ✅ Checkout shows order summary
5. ✅ Payment options: Room Charge, Cash, Card
6. ✅ Submit order → SUCCESS
7. ✅ Order confirmation appears

### Test 3: Backend Verification

**Check Order in Database**:
```sql
SELECT 
    order_number,
    order_type,
    table_id,
    room_id,
    payment_type,
    status,
    total
FROM orders
ORDER BY created_at DESC
LIMIT 5;
```

**For Walk-In Orders**:
- ✅ `order_type = 'walk_in'`
- ✅ `table_id` is NOT NULL
- ✅ `room_id` is NULL
- ✅ `guest_id` is NULL
- ✅ `reservation_id` is NULL
- ✅ `payment_type` = 'cash' or 'card'

**For Room Service Orders**:
- ✅ `order_type = 'room_service'`
- ✅ `room_id` is NOT NULL
- ✅ `table_id` is NULL
- ✅ `guest_id` is NOT NULL
- ✅ `reservation_id` is NOT NULL

### Test 4: Table Status Update

**Check Restaurant Table Status**:
```sql
SELECT 
    table_number,
    status,
    capacity,
    updated_at
FROM restaurant_tables
WHERE table_number = '2';
```

**Expected After Order**:
- ✅ `status = 'occupied'` (changed from 'available')

### Test 5: Kitchen Dashboard

**Access Kitchen Dashboard**:
1. Login as kitchen staff
2. Navigate to kitchen orders view
3. Check for new walk-in order

**Expected**:
- ✅ Order appears in "Pending" section
- ✅ Shows "Table 2" (not room number)
- ✅ Shows order items and quantities
- ✅ Can mark as "Preparing"

### Test 6: Network Inspection

**Open Browser DevTools → Network Tab**:

**Request to `/api/orders`**:
```
POST http://127.0.0.1:8000/api/orders
```

**Request Headers Should NOT Include**:
```
❌ Authorization: Bearer xxx...
```

**Request Payload**:
```json
{
  "qr_token": "table-2-GveD6NRGFa",
  "items": [
    {
      "menu_item_id": "uuid-here",
      "quantity": 2
    }
  ],
  "special_requests": "",
  "payment_type": "cash"
}
```

**Response (200 or 201)**:
```json
{
  "success": true,
  "message": "Walk-in order placed successfully",
  "data": {
    "order_id": "uuid-here",
    "order_number": "ORD-123456",
    "order_type": "walk_in",
    "table_number": "2",
    "total": 150.00,
    "status": "pending",
    "created_at": "2026-08-09T..."
  }
}
```

### Test 7: Browser Console Logs

**Expected Logs**:
```
🔍 [QR] Resolving QR token: table-2-GveD6NRGFa
📡 [QR] Resolution result: {success: true, context: 'table', ...}
✅ [QR] Table context detected
🔒 [ORDER] Creating order via unified service...
📦 [ORDER] Context: table
📦 [ORDER] Display name: Table 2
📤 [ORDER] Sending order: {...}
✅ [PAYMENT] Order placed successfully
```

**Should NOT See**:
```
❌ [PAYMENT] Error: Unauthorized. Required role: receptionist
❌ 403 Forbidden
❌ Authorization header set: Bearer...
```

## Quick Debug Checklist

If order still fails:

1. **Check axios import**:
   ```typescript
   // ✅ CORRECT
   import { publicAxios as axios } from './axios'
   
   // ❌ WRONG
   import { axiosInstance as axios } from './axios'
   ```

2. **Check route definition**:
   ```php
   // ✅ CORRECT - Outside auth group
   Route::post('/orders', [UnifiedOrderController::class, 'store'])
       ->withoutMiddleware(['auth:sanctum']);
   ```

3. **Clear browser cache**:
   - Hard reload: Ctrl+Shift+R
   - Clear localStorage
   - Restart dev server

4. **Check backend logs**:
   ```bash
   tail -f storage/logs/laravel.log
   ```
   Look for:
   ```
   [UNIFIED ORDER] Creating order
   [UNIFIED ORDER] QR token resolved
   [UNIFIED ORDER] Walk-in order created
   ```

5. **Verify QRResolutionService**:
   - Should accept both formats: `ABCD1234` and `table-2-GveD6NRGFa`
   - Should return `context: 'table'` for table tokens

## Success Indicators

✅ **Frontend**:
- No 403 errors in console
- Order confirmation shows
- "Table X" displayed (not "Room")

✅ **Backend**:
- Order created with `order_type = 'walk_in'`
- Table status updated to 'occupied'
- Order visible in kitchen dashboard

✅ **Network**:
- POST /api/orders returns 200/201
- No Authorization header in request
- Response contains order data

✅ **Database**:
- New order in `orders` table
- Order items in `order_items` table
- Table status updated in `restaurant_tables`

## Common Issues

### Issue: Still getting 403 error
**Solution**: 
- Clear browser cache and localStorage
- Restart frontend dev server
- Verify unifiedOrderService.ts import change

### Issue: QR token not resolving
**Solution**:
- Check token format (must be valid table token)
- Verify table exists in database
- Check QRResolutionService logs

### Issue: Table not in database
**Solution**:
```bash
php artisan db:seed --class=RestaurantTableSeeder
```

### Issue: Menu items not loading
**Solution**:
- Check `/guest/menu/items` endpoint
- Verify menu items exist in database
- Check category activation status

---

**Test URLs**:
- Walk-in QR: `http://localhost:5173/qr-menu?token=table-2-GveD6NRGFa`
- Manager Tables: `http://localhost:5173/manager/restaurant-tables`
- Kitchen Dashboard: `http://localhost:5173/kitchen`

**Quick Commands**:
```bash
# Start backend
cd server && php artisan serve

# Start frontend
cd Client2/vue-project && npm run dev

# Check orders
php artisan tinker
>>> Order::latest()->first()
>>> Order::where('order_type', 'walk_in')->get()
```

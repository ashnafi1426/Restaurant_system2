# WebSocket Authorization Fix - Testing Guide

## Problem Identified ✅

**Root Cause**: Hotel ID mismatch between frontend and order
- QR Room belongs to hotel: `01a0604b-e2dd-7366-8051-296ebcd72233`  
- Frontend was using: `01a07c38-a5f7-722f-8051-2ce8cf3e473b`
- Backend rejected WebSocket auth with **403 Forbidden** because hotel IDs didn't match

## Solution Applied ✅

Updated `QRMenu.vue` to store the correct `hotel_id` from QR resolution API response:
- Stores `hotel_id` from `result.data.hotel_id` immediately after QR resolution
- Applied to both **room** and **table** contexts
- Stored in both `localStorage.hotel_id` and `localStorage.active_hotel_id`

## Testing Steps

### 1. Clear Browser Data
```javascript
// Open Browser Console (F12)
localStorage.clear()
location.reload()
```

### 2. Access QR Menu
Navigate to:
```
http://localhost:5173/qr-menu/7IGJT4RP
```

### 3. Verify Hotel ID Stored
Check console logs:
```
[QRMenu] Stored hotel_id from QR resolution: 01a0604b-e2dd-7366-8051-296ebcd72233
```

Check localStorage:
```javascript
localStorage.getItem('hotel_id')
// Should return: "01a0604b-e2dd-7366-8051-296ebcd72233"
```

### 4. Place an Order
- Add items to cart
- Click "Place Order"
- Select payment method
- Submit order

### 5. Check Order Status Page
- Should redirect to Order Status page
- Look for **Green "Live" dot** in header
- Console should show:
```
[Echo] Authorizing channel: private-orders...
[Echo] Including QR token in request body for guest order channel
[BroadcastAuth] Guest WebSocket channel authorized via QR token
✅ Channel subscription setup complete
```

### 6. Verify No 403 Errors
- Check Network tab (F12 → Network)
- Filter by "broadcasting"
- `/api/broadcasting/auth` should return **200 OK** (not 403)

### 7. Test Real-Time Updates
**Two browser tabs:**

**Tab 1** - Guest Order Status:
- Keep order status page open
- Watch the green "Live" indicator

**Tab 2** - Admin Panel:
- Login as admin/chef
- Go to Orders Management
- Find your test order
- Change status: Pending → Preparing → Ready

**Expected in Tab 1**:
- Status updates **instantly** without refresh
- Status card color changes (yellow → orange → green)
- Status icon changes (📝 → 👨‍🍳 → ✅)
- "Last updated" timestamp refreshes

## Success Criteria ✅

All these must be true:
1. ✅ Hotel ID stored correctly from QR resolution
2. ✅ Order created with matching hotel_id
3. ✅ WebSocket authorization returns 200 OK (no 403)
4. ✅ Green "Live" dot appears and pulses
5. ✅ Status updates instantly when changed in admin
6. ✅ No errors in browser console
7. ✅ No errors in Laravel logs

## If Still Fails

### Check 1: Verify Hotel ID Matches
```javascript
// In browser console on Order Status page
const hotelId = localStorage.getItem('hotel_id')
const orderData = JSON.parse(localStorage.getItem('pending_order_data'))
console.log('Frontend hotel_id:', hotelId)
console.log('Order hotel_id:', orderData.hotel_id)
// These MUST match!
```

### Check 2: Laravel Logs
```bash
cd d:\Restaurant_system2\server
Get-Content storage\logs\laravel.log -Tail 30
```

Look for:
- `[BroadcastAuth] Hotel ID mismatch` → Still have mismatch
- `[BroadcastAuth] Guest WebSocket channel authorized` → Success!
- `[BroadcastAuth] Invalid QR token` → QR token validation issue

### Check 3: WebSocket Server Running
```bash
# Must be running
php artisan websockets:serve
```

### Check 4: Queue Worker Running
```bash
# Must be running for broadcasts to work
php artisan queue:work
```

## Technical Details

### What Changed:

**File**: `Client2/vue-project/src/views/guest/QRMenu.vue`

**Function**: `detectOrderContext()`

**Lines Added** (after room context resolution):
```javascript
// CRITICAL: Store hotel_id from QR resolution to ensure correct tenant isolation
if (result.data.hotel_id) {
  localStorage.setItem('hotel_id', result.data.hotel_id)
  localStorage.setItem('active_hotel_id', result.data.hotel_id)
  console.log('[QRMenu] Stored hotel_id from QR resolution:', result.data.hotel_id)
}
```

**Lines Added** (after table context resolution):
```javascript
// CRITICAL: Store hotel_id from QR resolution for table orders
if (result.data.hotel_id) {
  localStorage.setItem('hotel_id', result.data.hotel_id)
  localStorage.setItem('active_hotel_id', result.data.hotel_id)
  console.log('[QRMenu] Stored hotel_id from table QR resolution:', result.data.hotel_id)
}
```

### Why This Fix Works:

1. **QR Resolution Returns hotel_id**: The backend `QRResolutionService::resolveQRToken()` already returns `hotel_id` in the response
2. **Order Uses hotel_id**: When creating an order, it uses the room/table's `hotel_id`
3. **WebSocket Checks hotel_id**: The `BroadcastAuthController` validates that the channel's hotel_id matches the order's hotel_id
4. **Frontend Now Stores It**: By storing hotel_id immediately after QR resolution, the frontend always uses the correct tenant ID

### Data Flow:

```
1. Guest scans QR code (7IGJT4RP)
   ↓
2. Frontend calls /api/qr/resolve/7IGJT4RP
   ↓
3. Backend returns room data WITH hotel_id: "01a0604b-..."
   ↓
4. Frontend stores hotel_id in localStorage ← **NEW FIX**
   ↓
5. Guest places order → uses stored hotel_id
   ↓
6. Order created with correct hotel_id
   ↓
7. Guest navigates to Order Status
   ↓
8. WebSocket tries to authorize channel
   ↓
9. Backend checks: channel hotel_id == order hotel_id ← **NOW MATCHES!**
   ↓
10. Authorization succeeds → Green "Live" dot! ✅
```

## Additional Notes

- This issue only affected **multi-tenant** systems with multiple hotels
- Single-hotel systems wouldn't see this issue
- The fix ensures **tenant isolation** is properly maintained
- Critical for **security** - prevents guests from one hotel seeing orders from another

## Clean Up After Testing

Once confirmed working, you can clear test orders:
```sql
-- Be careful with this!
DELETE FROM orders WHERE order_number LIKE 'ORD-20261006%';
DELETE FROM order_items WHERE order_id NOT IN (SELECT id FROM orders);
```

---

**Status**: Fix applied, ready for testing
**Expected Result**: Green "Live" dot with real-time updates working
**Time to Test**: ~5 minutes

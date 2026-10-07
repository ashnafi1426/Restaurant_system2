# Implementation Plan: Order Status Real-Time Updates Fix

## Problem Summary

The order status page fails to update live when chefs change order status from 'pending' to 'preparing'. Multiple root causes identified:

1. **Frontend API endpoint mismatch**: useOrderStatus.ts calls `/api/guest/orders/{qrToken}/status` correctly, but the route definition in api.php points this to `GuestOrderController@getOrderStatus` which expects a room/table number, not an order lookup by qrToken
2. **Missing hotel_id in channel subscription**: If hotelId is empty at subscription time, the channel becomes `orders..{orderId}` which will NEVER match backend broadcast channel `orders.{hotelId}.{orderId}`
3. **Queue worker not running**: OrderStatusUpdated event implements ShouldQueue, so events are queued but never broadcast if no worker processes them
4. **Guest authorization in BroadcastAuthController**: The auth endpoint already handles guest orders correctly but may fail if hotelId is not properly extracted from the order

## Confirmed Issues

### Issue 1: Wrong API Endpoint in Frontend (CRITICAL)
**File**: `d:\Restaurant_system2\Client2\vue-project\src\composables\useOrderStatus.ts`
**Line**: 142
**Problem**: The composable fetches from `/api/guest/orders/{qrToken}/status` which routes to `GuestOrderController@getOrderStatus`, but that method expects a room/table identifier and returns the last 5 orders for that room/table, NOT a specific order by its ID or qrToken.

**Expected endpoint**: `/api/guest/orders/{orderId}/realtime-status?qr_token={qrToken}` which routes to `CustomerOrderController@getOrderStatus` - this is the correct endpoint that returns a single order's full details.

**Evidence**: Error log shows `GET http://127.0.0.1:8000/api/guest/orders/01a10d38-4f13-72b1-ba7a-9cfee336703a/realtime-status?qr_token=7IGJT4RP 403 (Forbidden)` - this is the correct URL pattern but the frontend code at line 142 builds the wrong URL.

### Issue 2: hotelId is Empty at Channel Subscription (CRITICAL)
**File**: `d:\Restaurant_system2\Client2\vue-project\src\views\guest\OrderStatusPage.vue`
**Lines**: 18-24
**Problem**: 
```javascript
const hotelId = ref(
  (localStorage.getItem('hotel_id') ||
  localStorage.getItem('active_hotel_id') ||
  route.query.hotel_id as string ||
  '').toString()
)
```
If localStorage is empty and no hotel_id in query params, hotelId is empty string `''`. This empty string is passed to useOrderStatus, which then subscribes to channel `orders..{orderId}` (double dot, missing hotel_id segment). The backend broadcasts to `orders.{hotelId}.{orderId}`, so the subscription NEVER matches.

**Root cause**: The guest QR menu flow stores qr_token but does NOT reliably store hotel_id in localStorage. The API response from `/api/guest/orders/{orderId}/realtime-status` includes `hotel_id`, but by the time the response arrives, `subscribeToChannel()` has already run with the stale empty hotelId.

### Issue 3: Queue Worker Not Running (HIGH IMPACT)
**File**: `d:\Restaurant_system2\server\app\Events\OrderStatusUpdated.php`
**Problem**: Event implements `ShouldQueue`, so when `OrderStatusUpdated::dispatch()` is called in `KitchenService.php`, the event is added to the `jobs` table (QUEUE_CONNECTION=database per .env). If no queue worker is running (`php artisan queue:work`), the event sits in the queue forever and is NEVER broadcast via WebSocket.

**Evidence**: QUEUE_CONNECTION=database in server/.env. User has not confirmed a queue worker is running.

### Issue 4: 403 Forbidden on realtime-status Endpoint
**File**: `d:\Restaurant_system2\server\app\Http\Controllers\Api\CustomerOrderController.php`
**Line**: 66-77
**Problem**: The endpoint validates hotel_id matches between request and order. For guest requests, `extractHotelId()` falls back to returning the order's hotel_id (line 195-197), which should allow the comparison to pass. However, if the qr_token is NOT being validated or if X-Hotel-ID header is missing/wrong, the validation could fail.

**Investigation needed**: The route `/api/guest/orders/{orderId}/realtime-status` requires `qr_token` query param for guest authorization. The controller does NOT currently validate the qr_token - it only checks hotel_id. This could cause false 403 errors if the frontend sends the wrong hotel_id header.

### Issue 5: Missing qr_token Validation in CustomerOrderController
**File**: `d:\Restaurant_system2\server\app\Http\Controllers\Api\CustomerOrderController.php`
**Problem**: The controller does NOT validate the qr_token query parameter. For guest orders via QR menu, the security model should validate that the qr_token matches the order's room/table qr_token, OR that the request comes from the correct hotel context.

Currently, any guest can access any order if they know the order ID, which is a security vulnerability.

## Implementation Plan

###  Prerequisites Verification

Before fixing code, verify these services are running:

- [ ] 1. **Verify Laravel API server is running**
      Command: Check if http://127.0.0.1:8000 responds
      Verify: Open http://127.0.0.1:8000/api/categories in browser (should return JSON)
      If not running: `cd d:\Restaurant_system2\server && php artisan serve --host=127.0.0.1 --port=8000`

- [ ] 2. **Verify Laravel Reverb WebSocket server is running**
      Command: Check if port 8080 is listening
      Verify: `netstat -an | Select-String "8080.*LISTENING"`
      If not running: Open new terminal, `cd d:\Restaurant_system2\server && php artisan reverb:start --host=0.0.0.0 --port=8080`

- [ ] 3. **Verify Queue Worker is running**
      Command: Check if queue:work process exists
      Verify: `Get-Process | Where-Object {$_.CommandLine -like "*queue:work*"}`
      If not running: Open new terminal, `cd d:\Restaurant_system2\server && php artisan queue:work --queue=default --tries=3 --timeout=90`

- [ ] 4. **Verify Vue dev server is running**
      Command: Check if http://localhost:5173 responds
      Verify: Open http://localhost:5173 in browser
      If not running: `cd d:\Restaurant_system2\Client2\vue-project && npm run dev`

### 🔧 Code Fixes

- [ ] 5. **Fix API endpoint URL in useOrderStatus.ts**
      **File**: `d:\Restaurant_system2\Client2\vue-project\src\composables\useOrderStatus.ts`
      **Change**: Line 142, update the fetch URL from:
      ```typescript
      const url = `${apiBaseUrl}/api/guest/orders/${qrToken}/status`
      ```
      to:
      ```typescript
      const url = `${apiBaseUrl}/api/guest/orders/${orderId}/realtime-status?qr_token=${qrToken}`
      ```
      **Rationale**: The `/api/guest/orders/{qrToken}/status` endpoint returns orders for a room/table, not a specific order. The correct endpoint `/api/guest/orders/{orderId}/realtime-status` expects orderId and validates using qr_token query param.
      **Verify**: Run frontend dev server, open order status page, check browser network tab - URL should be `/api/guest/orders/{uuid}/realtime-status?qr_token=...` and should return 200 OK with order data

- [ ] 6. **Extract and store hotel_id from API response in useOrderStatus.ts**
      **File**: `d:\Restaurant_system2\Client2\vue-project\src\composables\useOrderStatus.ts`
      **Change**: Line 161-165, after successfully fetching order data, extract hotel_id and update it if it was missing:
      ```typescript
      if (response.data.success && response.data.data) {
        orderData.value = response.data.data
        status.value = response.data.data.status
        paymentStatus.value = response.data.data.payment_status || 'pending'
        lastUpdate.value = response.data.data.updated_at
        
        // CRITICAL FIX: Extract hotel_id from response and update local storage
        if (response.data.data.hotel_id) {
          const responseHotelId = response.data.data.hotel_id
          localStorage.setItem('hotel_id', responseHotelId)
          
          // Update hotelId in parent scope if it was empty
          // Note: This won't affect the already-subscribed channel, fix that in step 7
          console.log('[useOrderStatus] Extracted hotel_id from API:', responseHotelId)
        }
      }
      ```
      **Verify**: Open browser console, check logs for `[useOrderStatus] Extracted hotel_id from API: {uuid}`

- [ ] 7. **Fix channel subscription to use extracted hotel_id (CRITICAL)**
      **File**: `d:\Restaurant_system2\Client2\vue-project\src\composables\useOrderStatus.ts`
      **Problem**: The hotelId parameter is a primitive string passed by value. When fetchOrderData updates localStorage, the subscribeToChannel function (which runs after fetchOrderData) still uses the stale empty hotelId.
      **Change**: Refactor useOrderStatus to make hotelId reactive and defer channel subscription until hotel_id is confirmed:
      
      Lines 72-73: Change function signature from:
      ```typescript
      export function useOrderStatus(orderId: string, hotelId: string) {
      ```
      to:
      ```typescript
      export function useOrderStatus(orderId: string, initialHotelId: string) {
        const hotelId = ref<string>(initialHotelId)
      ```
      
      Lines 165-170: After extracting hotel_id from API response, update the reactive hotelId:
      ```typescript
      if (response.data.data.hotel_id) {
        const responseHotelId = response.data.data.hotel_id
        hotelId.value = responseHotelId  // Update reactive ref
        localStorage.setItem('hotel_id', responseHotelId)
        console.log('[useOrderStatus] Extracted hotel_id from API:', responseHotelId)
      }
      ```
      
      Line 192: Update channel name construction to use reactive hotelId:
      ```typescript
      const channelName = `orders.${hotelId.value}.${orderId}`
      ```
      
      Line 277: Update cleanup function to use reactive hotelId:
      ```typescript
      const channelName = `orders.${hotelId.value}.${orderId}`
      ```
      
      Lines 296-298: Update onMounted to ensure hotel_id is available before subscribing:
      ```typescript
      onMounted(async () => {
        await fetchOrderData() // This extracts hotel_id if missing
        
        // Only subscribe if we have a valid hotel_id
        if (!hotelId.value) {
          console.error('[useOrderStatus] Cannot subscribe: hotel_id is missing after fetch')
          error.value = 'Configuration error: hotel ID not available'
          return
        }
        
        subscribeToChannel()
      })
      ```
      
      **Verify**: Open browser console, check logs show `[useOrderStatus] Subscribing to channel: orders.{valid-uuid}.{order-id}` (not `orders..{order-id}` with empty hotel segment)

- [ ] 8. **Add qr_token validation to CustomerOrderController**
      **File**: `d:\Restaurant_system2\server\app\Http\Controllers\Api\CustomerOrderController.php`
      **Change**: Add qr_token validation for guest orders. Insert after line 48 (after order is found):
      ```php
      // Validate qr_token for guest access
      $qrToken = $request->query('qr_token');
      if ($qrToken) {
          // For guest orders, validate the qr_token matches the order's room or table
          $validToken = false;
          
          if ($order->room && $order->room->qr_token === $qrToken) {
              $validToken = true;
          } elseif ($order->table && $order->table->qr_token === $qrToken) {
              $validToken = true;
          }
          
          if (!$validToken) {
              Log::warning('[CustomerOrder] Invalid qr_token for order', [
                  'order_id' => $orderId,
                  'provided_token' => $qrToken,
                  'ip' => $request->ip()
              ]);
              
              return response()->json([
                  'success' => false,
                  'message' => 'Invalid QR token for this order.',
                  'error' => 'INVALID_QR_TOKEN'
              ], 403);
          }
          
          Log::info('[CustomerOrder] QR token validated for guest access', [
              'order_id' => $orderId,
              'qr_token' => substr($qrToken, 0, 4) . '****' // Log partial token for security
          ]);
      }
      ```
      **Verify**: Try accessing order status with wrong qr_token - should return 403. With correct token - should return 200.

- [ ] 9. **Ensure queue worker processes OrderStatusUpdated events**
      **No code change required** - this is a runtime requirement.
      **Verify**: 
      1. Start queue worker: `cd d:\Restaurant_system2\server && php artisan queue:work`
      2. In kitchen dashboard, click "Start Preparing" on a pending order
      3. Check queue worker terminal output - should show: `Processing: App\Events\OrderStatusUpdated`
      4. Check order status page in guest browser - status should update to "Preparing" within 1-2 seconds

- [ ] 10. **Test the complete flow end-to-end**
      **Steps**:
      1. Place a test order via QR menu (http://localhost:5173/qr/{qrToken})
      2. Navigate to order status page - should show "Order Received" with status=pending
      3. Check browser console - should see:
         - `[useOrderStatus] Extracted hotel_id from API: {uuid}`
         - `[useOrderStatus] Subscribing to channel: orders.{hotel-id}.{order-id}`
         - `[Echo]  Connected to Reverb WebSocket server`
         - Live indicator should show green "Live" dot
      4. In chef/kitchen dashboard (http://localhost:5173/kitchen), find the order and click "Start Preparing"
      5. Within 1-2 seconds, order status page should automatically update to "Preparing Your Order" with chef icon
      6. No page refresh needed - update should be instant via WebSocket
      **Verify**: Status updates live without manual refresh

## Services Startup Guide

To run the system with real-time order updates, you need 4 services running simultaneously:

### Terminal 1: Laravel API Server
```powershell
cd d:\Restaurant_system2\server
php artisan serve --host=127.0.0.1 --port=8000
```
**Expected output**: `Laravel development server started: http://127.0.0.1:8000`
**Keep running**: Leave this terminal open

### Terminal 2: Laravel Reverb WebSocket Server
```powershell
cd d:\Restaurant_system2\server
php artisan reverb:start --host=0.0.0.0 --port=8080
```
**Expected output**: 
```
  Reverb server started on 0.0.0.0:8080
  Application ID: restaurant-app
```
**Keep running**: Leave this terminal open

### Terminal 3: Laravel Queue Worker
```powershell
cd d:\Restaurant_system2\server
php artisan queue:work --queue=default --tries=3 --timeout=90
```
**Expected output**: `Processing: {JobClassName}`
**Keep running**: Leave this terminal open
**Note**: This processes OrderStatusUpdated events from the database queue and broadcasts them via WebSocket

### Terminal 4: Vue.js Development Server
```powershell
cd d:\Restaurant_system2\Client2\vue-project
npm run dev
```
**Expected output**: 
```
  VITE ready in XXXms
  ➜  Local:   http://localhost:5173/
```
**Keep running**: Leave this terminal open

### Quick Health Check Commands

Run these to verify all services are running:

```powershell
# Check Laravel API (should return JSON with categories)
curl http://127.0.0.1:8000/api/categories

# Check Reverb WebSocket (should show LISTENING on port 8080)
netstat -an | Select-String "8080.*LISTENING"

# Check Queue Worker (should show php artisan queue:work process)
Get-Process | Where-Object {$_.CommandLine -like "*queue:work*"}

# Check Vue Dev Server (should return HTML)
curl http://localhost:5173
```

## Testing Checklist

After implementing all fixes:

- [ ] Place order via QR menu - should succeed
- [ ] Navigate to order status page - should load order details
- [ ] Browser console shows hotel_id extracted from API
- [ ] Browser console shows WebSocket connected (green "Live" indicator)
- [ ] Browser console shows subscribed to channel with valid hotel_id (not empty)
- [ ] Kitchen dashboard shows the new order
- [ ] Click "Start Preparing" in kitchen - order status updates instantly on guest page
- [ ] No page refresh needed - update is live via WebSocket
- [ ] Check queue worker terminal - shows "Processing: App\Events\OrderStatusUpdated"

## Root Cause Summary

The issue is caused by **THREE independent failures** that compound:

1. **Wrong API endpoint**: Frontend calls endpoint that returns room's orders, not the specific order by ID
2. **Empty hotel_id at subscription time**: Channel name becomes `orders..{orderId}` which never matches backend broadcast
3. **Missing queue worker**: Events are queued but never broadcast because no worker processes them

All three must be fixed for real-time updates to work. The most critical fix is #7 (making hotelId reactive and deferring subscription).

## Technical Notes

- **Multi-tenant security**: All fixes preserve hotel_id validation to prevent cross-tenant data access
- **WebSocket protocol**: Uses Pusher protocol via Laravel Reverb (not Pusher Cloud)
- **Channel naming**: Backend broadcasts to `orders.{hotelId}.{orderId}`, frontend must subscribe to exact same channel name
- **Queue configuration**: QUEUE_CONNECTION=database means events go to `jobs` table, worker picks them up and broadcasts
- **Event timing**: With queue worker, broadcast happens within ~100-500ms of status change (near real-time)

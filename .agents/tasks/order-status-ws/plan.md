# Implementation Plan: Real-Time Order Status with Laravel Reverb + WebSockets

## Project Context

**Architecture**: Laravel 13 + Vue 3 (TypeScript) multi-tenant Hotel Management System  

**Current State**: 
- Order management exists (pending → preparing → ready → served flow)
- KitchenService and OrderStatusService handle status transitions
- OrderStatusTimeline.vue exists but is STATIC (no real-time updates)
- NO WebSocket infrastructure exists (no Reverb, no Laravel Echo, no broadcasting.php, no channels.php)
- QRMenu.vue handles customer ordering flow
- Multi-tenant isolation via hotel_id (TenantScope applied globally)
- WaiterDashboardService.php exists and may use polling

**WebSocket Stack**:
```
Vue.js + Laravel Echo + pusher-js
         ↓
    Laravel Reverb (WebSocket Server)
         ↓
    Laravel Broadcasting Events
         ↓
    Laravel Services (Kitchen/Order/Payment)
         ↓
       DATABASE
```

**Build Commands**:
- Backend: `cd server && composer install && php artisan migrate`
- Frontend: `cd Client2/vue-project && npm install && npm run build`
- Dev: `cd server && composer run dev` (starts Laravel server, queue, logs, vite)

**Test Command**: `cd server && php artisan test`

---

## Implementation Plan

### PHASE 1: BACKEND WEBSOCKET INFRASTRUCTURE

- [ ] **1. Install Laravel Reverb**
      
      Install Laravel Reverb package for WebSocket server. Run `cd d:\Restaurant_system2\server && composer require laravel/reverb`. After installation, run `php artisan reverb:install` to publish configuration files.
      
      Files Modified:
      - `d:\Restaurant_system2\server\composer.json`
      - `d:\Restaurant_system2\server\config\reverb.php` (created)
      - `d:\Restaurant_system2\server\.env` (REVERB_* variables added)
      
      Verify: Run `cd d:\Restaurant_system2\server ; composer show laravel/reverb` — package version appears. Run `php artisan reverb:start` — server starts on default port 8080 without errors.

- [ ] **2. Configure broadcasting.php for Reverb**
      
      Publish broadcasting config if not exists: `php artisan vendor:publish --tag=laravel-broadcasting`. Update `config/broadcasting.php` to set default driver to 'reverb'. Verify 'reverb' connection exists in connections array with proper configuration (app_id, key, secret, host, port, scheme).
      
      Files Modified:
      - `d:\Restaurant_system2\server\config\broadcasting.php`
      - `d:\Restaurant_system2\server\.env` (set BROADCAST_CONNECTION=reverb)
      
      Verify: Run `php artisan tinker --execute="dump(config('broadcasting.default'));"` — outputs "reverb".

- [ ] **3. Configure .env for Reverb (backend)**
      
      Add Reverb environment variables to `.env`: BROADCAST_CONNECTION=reverb, REVERB_APP_ID=restaurant-app, REVERB_APP_KEY=(generate secure random key), REVERB_APP_SECRET=(generate secure secret), REVERB_HOST=127.0.0.1, REVERB_PORT=8080, REVERB_SCHEME=http. DO NOT commit .env file. Update `.env.example` with placeholder values.
      
      Files Modified:
      - `d:\Restaurant_system2\server\.env` (add Reverb vars, do NOT commit)
      - `d:\Restaurant_system2\server\.env.example` (add placeholders with comments)
      
      Verify: Run `php artisan config:cache && php artisan tinker --execute="dump(config('reverb'));"` — configuration appears correctly.

- [ ] **4. Configure Laravel Queue for broadcast events**
      
      Verify queue configuration in `.env`: QUEUE_CONNECTION=database or redis. Run migration if needed: `php artisan queue:table && php artisan migrate`. Ensure broadcast events will be queued (not synchronous) for performance.
      
      Files Modified:
      - `d:\Restaurant_system2\server\.env` (verify QUEUE_CONNECTION)
      - Database (jobs table created if needed)
      
      Verify: Run `php artisan queue:work --once` — worker starts and processes queued jobs without errors.

- [ ] **5. Create routes/channels.php for multi-tenant WebSocket authorization**
      
      Create `d:\Restaurant_system2\server\routes\channels.php` if not exists. Define private channels with strict multi-tenant authorization:
      
      **Channel 1**: `orders.{hotelId}.{orderId}` — Customer order status updates. Authorization: (1) Find Order by {orderId}, (2) Verify order->hotel_id === {hotelId}, (3) Check: authenticated user exists AND (user is guest who owns order OR user is staff of that hotel). Return true/false.
      
      **Channel 2**: `hotel.{hotelId}.kitchen` — Kitchen/chef real-time updates. Authorization: Verify user is authenticated staff of hotel {hotelId} with kitchen access (chef, kitchen_staff, manager, admin).
      
      **Channel 3**: `hotel.{hotelId}.waiters` — Waiter notifications. Authorization: Verify user is authenticated waiter/staff of hotel {hotelId}.
      
      **Channel 4**: `hotel.{hotelId}.orders` — Hotel-wide order events (for dashboards). Authorization: Verify user is authenticated staff of hotel {hotelId}.
      
      Files Created:
      - `d:\Restaurant_system2\server\routes\channels.php`
      
      Verify: Run `php artisan route:list | Select-String -Pattern "broadcasting"` — broadcasting auth routes appear (POST /broadcasting/auth).

---

### PHASE 2: BACKEND BROADCAST EVENTS

- [ ] **6. Create app/Events/OrderCreated.php**
      
      Create new broadcast event `OrderCreated` implementing ShouldBroadcast interface. Broadcast on private channel `hotel.{order->hotel_id}.kitchen` AND `hotel.{order->hotel_id}.orders`. Payload: order_id, order_number, hotel_id, order_type, room_number, table_number, status ('received'), order_time, customer_name, items_count, total, created_at. Constructor accepts Order $order (with orderItems loaded). Use SerializesModels trait. Add ShouldQueue interface for async broadcasting.
      
      Files Created:
      - `d:\Restaurant_system2\server\app\Events\OrderCreated.php`
      
      Verify: Run `php artisan tinker --execute="$order = App\Models\Order::with('orderItems')->first(); event(new App\Events\OrderCreated(\$order)); dump('OrderCreated dispatched');"` — no errors, check `php artisan queue:work` processes the broadcast job.

- [ ] **7. Create app/Events/OrderStatusUpdated.php**
      
      Create broadcast event `OrderStatusUpdated` implementing ShouldBroadcast, ShouldQueue. Broadcast on TWO channels: (1) `orders.{order->hotel_id}.{order->id}` for customer, (2) `hotel.{order->hotel_id}.kitchen` for chef dashboard. Payload: order_id, hotel_id, status (pending/preparing/ready/served/cancelled), previous_status, order_number, message (human-readable status text), room_number, table_number, chef_id, updated_at, estimated_completion_time. Constructor accepts Order $order. Keep payload small (no full order items array).
      
      Files Created:
      - `d:\Restaurant_system2\server\app\Events\OrderStatusUpdated.php`
      
      Verify: Run tinker test: `$order = App\Models\Order::first(); event(new App\Events\OrderStatusUpdated(\$order)); dump('Event dispatched');` — queue job appears, processes successfully.

- [ ] **8. Create app/Events/PaymentStatusUpdated.php**
      
      Create broadcast event for payment status changes. Broadcast on private channel `orders.{order->hotel_id}.{order->id}`. Payload: order_id, hotel_id, payment_status (pending/paid/failed/refunded), payment_method, payment_type, amount, transaction_ref, updated_at. Constructor accepts Order $order. Implements ShouldBroadcast, ShouldQueue.
      
      Files Created:
      - `d:\Restaurant_system2\server\app\Events\PaymentStatusUpdated.php`
      
      Verify: Tinker test — event dispatches and queues successfully.

- [ ] **9. Create app/Events/OrderCancelled.php (optional but recommended)**
      
      Create broadcast event for order cancellation. Broadcast on channels: `orders.{order->hotel_id}.{order->id}` AND `hotel.{order->hotel_id}.kitchen`. Payload: order_id, hotel_id, cancelled_by (user_id), reason, cancelled_at. Implements ShouldBroadcast, ShouldQueue.
      
      Files Created:
      - `d:\Restaurant_system2\server\app\Events\OrderCancelled.php`
      
      Verify: Tinker test — event works correctly.

---

### PHASE 3: BACKEND SERVICE INTEGRATION

- [ ] **10. Update app/Services/KitchenService.php to dispatch OrderStatusUpdated**
      
      In `d:\Restaurant_system2\server\app\Services\KitchenService.php`, import OrderStatusUpdated event. Modify methods:
      
      - `startPreparing($order, $actor)`: After successful status transition via orderStatusService, dispatch `OrderStatusUpdated` event with updated order.
      - `markReady($order)`: After successful transition, dispatch `OrderStatusUpdated`.
      - `markServed($order)`: After successful transition, dispatch `OrderStatusUpdated`.
      
      IMPORTANT: Dispatch AFTER database commit succeeds. Wrap in DB::transaction where appropriate. Do NOT dispatch if transaction fails.
      
      Files Modified:
      - `d:\Restaurant_system2\server\app\Services\KitchenService.php`
      
      Verify: Test via tinker or HTTP request: update order status via KitchenService — verify OrderStatusUpdated event appears in queue, broadcasts via Reverb. Check `storage/logs/laravel.log` for broadcast confirmation.

- [ ] **11. Update app/Services/OrderStatusService.php to dispatch events**
      
      In `d:\Restaurant_system2\server\app\Services\OrderStatusService.php`, add event dispatching in transition handlers: handlePreparing(), handleReady(), handleServed(), handleCancelled(). Dispatch OrderStatusUpdated after successful database update. For handleCancelled(), dispatch OrderCancelled event.
      
      Files Modified:
      - `d:\Restaurant_system2\server\app\Services\OrderStatusService.php`
      
      Verify: Transition order through statuses via service — events broadcast correctly.

- [ ] **12. Integrate PaymentStatusUpdated in payment webhooks/controllers**
      
      Locate Chapa payment webhook handler (likely in GuestOrderPaymentController or WalkInOrderPaymentController). After successful payment verification and order->payment_status update, dispatch `PaymentStatusUpdated` event. Ensure this happens AFTER database commit.
      
      Files Modified:
      - `d:\Restaurant_system2\server\app\Http\Controllers\Api\GuestOrderPaymentController.php` (completeOrder method)
      - `d:\Restaurant_system2\server\app\Http\Controllers\Api\WalkInOrderPaymentController.php` (completeOrder method)
      
      Verify: Complete a Chapa payment flow — verify PaymentStatusUpdated broadcasts, customer receives real-time payment confirmation.

- [ ] **13. Dispatch OrderCreated event in order creation flow**
      
      Locate where orders are created (likely UnifiedOrderController or GuestOrderController). After successful order creation and database commit, dispatch `OrderCreated` event. This notifies kitchen dashboard of new orders in real-time.
      
      Files Modified:
      - `d:\Restaurant_system2\server\app\Http\Controllers\Api\UnifiedOrderController.php` (store method)
      - `d:\Restaurant_system2\server\app\Http\Controllers\Api\GuestOrderController.php` (createOrder method)
      
      Verify: Place order via QR menu — OrderCreated broadcasts to kitchen channel, kitchen dashboard receives event without polling.

- [ ] **14. Create app/Http/Controllers/Api/CustomerOrderController.php for order status API**
      
      Create new controller for customer-facing order status endpoint. Method: `getOrderStatus(Request $request, string $orderId)`.
      
      Logic:
      1. Find order by $orderId (UUID) OR by order_number if not found as UUID
      2. If not found, return 404 JSON error
      3. Extract hotel_id: (a) if authenticated user, get user->hotel_id, (b) else extract from qr_token in request query/header, resolve hotel_id
      4. Validate order->hotel_id matches extracted hotel_id (multi-tenant security)
      5. If mismatch, return 403 Forbidden
      6. Load order with relations: orderItems.menuItem, room, table, guest
      7. Return JSON: { success: true, data: { order_id, order_number, status, order_type, order_time, room_number, table_number, customer_name, items: [...], subtotal, tax, service_charge, total, payment_type, payment_status, chef_id, estimated_completion, created_at, updated_at } }
      
      Files Created:
      - `d:\Restaurant_system2\server\app\Http\Controllers\Api\CustomerOrderController.php`
      
      Verify: Send GET request to endpoint with valid order_id — returns order data. Send with wrong hotel_id — returns 403.

- [ ] **15. Add customer order status route in routes/api.php**
      
      In `d:\Restaurant_system2\server\routes\api.php`, add route under `prefix('guest')` group: `Route::get('/orders/{orderId}/status', [CustomerOrderController::class, 'getOrderStatus']);`. Import CustomerOrderController at top. This route should be public (no auth middleware) but validates hotel_id internally.
      
      Files Modified:
      - `d:\Restaurant_system2\server\routes\api.php`
      
      Verify: Run `php artisan route:list | Select-String -Pattern "guest.*orders.*status"` — route appears as `GET api/guest/orders/{orderId}/status`.

---

### PHASE 4: FRONTEND WEBSOCKET SETUP

- [ ] **16. Install laravel-echo and pusher-js in Vue project**
      
      Run `cd d:\Restaurant_system2\Client2\vue-project && npm install laravel-echo pusher-js --save`. Pusher-js is used as the protocol library for Laravel Reverb (Reverb uses Pusher protocol, NOT the Pusher cloud service).
      
      Files Modified:
      - `d:\Restaurant_system2\Client2\vue-project\package.json`
      
      Verify: Run `npm list laravel-echo pusher-js` — both packages appear in installed dependencies.

- [ ] **17. Configure frontend .env for Reverb connection**
      
      Create or update `d:\Restaurant_system2\Client2\vue-project\.env` with Reverb connection settings:
      ```
      VITE_REVERB_APP_KEY=restaurant-app
      VITE_REVERB_HOST=127.0.0.1
      VITE_REVERB_PORT=8080
      VITE_REVERB_SCHEME=http
      VITE_API_BASE_URL=http://127.0.0.1:8000
      ```
      
      Add same variables to `.env.example` with comments. For production, set VITE_REVERB_HOST to actual domain/IP, VITE_REVERB_SCHEME=https, VITE_REVERB_PORT=443 or 6001 depending on deployment.
      
      Files Modified:
      - `d:\Restaurant_system2\Client2\vue-project\.env` (do NOT commit with secrets)
      - `d:\Restaurant_system2\Client2\vue-project\.env.example`
      
      Verify: Check environment variables are accessible in Vue: `console.log(import.meta.env.VITE_REVERB_APP_KEY)` shows correct value.

- [ ] **18. Create src/plugins/echo.ts for Laravel Echo singleton**
      
      Create `d:\Restaurant_system2\Client2\vue-project\src\plugins\echo.ts`. Import Echo from 'laravel-echo' and Pusher from 'pusher-js'. Export configured Echo instance:
      
      ```typescript
      import Echo from 'laravel-echo'
      import Pusher from 'pusher-js'
      
      declare global {
        interface Window {
          Pusher: typeof Pusher
          Echo: Echo
        }
      }
      
      window.Pusher = Pusher
      
      const echo = new Echo({
        broadcaster: 'reverb',
        key: import.meta.env.VITE_REVERB_APP_KEY,
        wsHost: import.meta.env.VITE_REVERB_HOST,
        wsPort: import.meta.env.VITE_REVERB_PORT ?? 8080,
        wssPort: import.meta.env.VITE_REVERB_PORT ?? 8080,
        forceTLS: (import.meta.env.VITE_REVERB_SCHEME ?? 'https') === 'https',
        enabledTransports: ['ws', 'wss'],
        authorizer: (channel: any) => {
          return {
            authorize: (socketId: string, callback: Function) => {
              const token = localStorage.getItem('token')
              const hotelId = localStorage.getItem('hotel_id')
              
              fetch(import.meta.env.VITE_API_BASE_URL + '/api/broadcasting/auth', {
                method: 'POST',
                headers: {
                  'Content-Type': 'application/json',
                  'Accept': 'application/json',
                  'Authorization': token ? `Bearer ${token}` : '',
                  'X-Hotel-ID': hotelId || '',
                },
                body: JSON.stringify({
                  socket_id: socketId,
                  channel_name: channel.name
                })
              })
              .then(response => response.json())
              .then(data => callback(null, data))
              .catch(error => callback(error))
            }
          }
        }
      })
      
      window.Echo = echo
      
      export default echo
      ```
      
      Files Created:
      - `d:\Restaurant_system2\Client2\vue-project\src\plugins\echo.ts`
      
      Verify: Run `npm run type-check` — no TypeScript errors. Import echo in main.ts, check browser console for Reverb connection logs.

- [ ] **19. Import Echo plugin in main.ts**
      
      In `d:\Restaurant_system2\Client2\vue-project\src\main.ts`, import the Echo singleton near the top: `import './plugins/echo'`. This initializes Echo when app starts. Ensure this import happens BEFORE Vue app is mounted.
      
      Files Modified:
      - `d:\Restaurant_system2\Client2\vue-project\src\main.ts`
      
      Verify: Run dev server `npm run dev`, open browser console — Echo connects to Reverb WebSocket server, no connection errors.

---

### PHASE 5: FRONTEND COMPOSABLES & REAL-TIME LOGIC

- [ ] **20. Create src/composables/useOrderStatus.ts**
      
      Create TypeScript composable for managing order status WebSocket subscriptions. Export `useOrderStatus(orderId: string, hotelId: string)`.
      
      Implementation:
      - Accept orderId (UUID), hotelId (UUID or number)
      - Create reactive refs: status (string), orderData (Order object), isConnected (boolean), error (string | null), isLoading (boolean)
      - onMounted: (1) Fetch initial order data from API GET `/api/guest/orders/${orderId}/status`, (2) Subscribe to private channel `orders.${hotelId}.${orderId}` using window.Echo.private(), (3) Listen for '.OrderStatusUpdated' event, update reactive status and orderData, (4) Listen for '.PaymentStatusUpdated', update payment fields, (5) Listen for '.OrderCancelled', update status to cancelled
      - Implement reconnection handling: on echo.connector.pusher.connection.bind('connected'), set isConnected=true; on 'disconnected', set false and re-sync order data
      - onUnmounted: Leave channel via echo.leave()
      - Return { status, orderData, isConnected, error, isLoading, refresh: async function to re-fetch API }
      
      NO setInterval or polling loops. Use WebSocket events only.
      
      Files Created:
      - `d:\Restaurant_system2\Client2\vue-project\src\composables\useOrderStatus.ts`
      
      Verify: Run `npm run type-check` — no errors. Test composable in a test component — subscribes to channel, receives events correctly.

- [ ] **21. Create src/composables/useKitchenOrders.ts for kitchen dashboard**
      
      Create composable for kitchen staff to receive real-time order updates. Export `useKitchenOrders(hotelId: string)`.
      
      Implementation:
      - Accept hotelId
      - Create reactive refs: orders (categorized: pending[], preparing[], ready[], served[]), isConnected (boolean)
      - onMounted: (1) Fetch initial orders from API GET `/api/kitchen/orders`, (2) Subscribe to `hotel.${hotelId}.kitchen` channel, (3) Listen for '.OrderCreated' — add order to pending array, (4) Listen for '.OrderStatusUpdated' — move order between arrays based on new status
      - onUnmounted: Leave channel
      - Return { orders, isConnected, refresh }
      
      NO polling. Replace existing setInterval/setTimeout polling in kitchen components.
      
      Files Created:
      - `d:\Restaurant_system2\Client2\vue-project\src\composables/useKitchenOrders.ts`
      
      Verify: Type-check passes, composable works in kitchen dashboard without polling.

- [ ] **22. Create src/composables/useWaiterNotifications.ts for waiter dashboard**
      
      Create composable for waiters to receive order-ready notifications. Export `useWaiterNotifications(hotelId: string)`.
      
      Implementation:
      - Accept hotelId
      - Create reactive refs: notifications (array), readyOrders (array), isConnected (boolean)
      - onMounted: (1) Fetch initial waiter notifications/ready orders from API, (2) Subscribe to `hotel.${hotelId}.waiters` channel, (3) Listen for waiter-specific events (order ready, delivery task assigned), (4) Update notifications array in real-time
      - onUnmounted: Leave channel
      - Return { notifications, readyOrders, isConnected, markAsRead, refresh }
      
      Files Created:
      - `d:\Restaurant_system2\Client2\vue-project\src\composables/useWaiterNotifications.ts`
      
      Verify: Composable works without polling.

---

### PHASE 6: FRONTEND UI COMPONENTS

- [ ] **23. Create src/views/guest/OrderStatusPage.vue**
      
      Create single-page customer order status component that updates in real-time via WebSocket.
      
      Features:
      - Extract orderId from route params: `route.params.orderId`
      - Extract hotelId from localStorage (set during QR menu session) or QR token context
      - Use `useOrderStatus(orderId, hotelId)` composable
      - Display order header: order number, room/table number, order time, customer name
      - Display visual status timeline using OrderStatusTimeline component (pass reactive status prop)
      - Display ordered items list with quantities, prices
      - Display order totals: subtotal, tax, service charge, total
      - Display payment section: payment_type, payment_status, "Pay Now with Chapa" button if payment_status === 'pending'
      - Display WebSocket connection indicator: green dot if isConnected, red if disconnected
      - Display loading state while fetching initial data
      - Display error message if order not found or unauthorized
      - Animate status transitions when WebSocket event received (smooth transition, confetti for 'ready' status)
      
      NO page refresh. NO setInterval polling. Updates happen via WebSocket events only.
      
      Files Created:
      - `d:\Restaurant_system2\Client2\vue-project\src\views/guest/OrderStatusPage.vue`
      
      Verify: Run dev server, navigate to `/order-status/{orderId}` — page loads, shows initial status, updates in real-time when chef changes status in kitchen dashboard.

- [ ] **24. Update src/components/guest/OrderStatusTimeline.vue for dynamic status updates**
      
      The existing `OrderStatusTimeline.vue` accepts a `status` prop. Verify it supports Order model statuses: 'pending', 'preparing', 'ready', 'served'. Current component uses 'confirmed', 'delivered' — map these:
      - 'pending' → display as "Order Received" or "Confirmed"
      - 'preparing' → "Preparing" (show chef icon, animation)
      - 'ready' → "Ready for Delivery/Pickup" (show checkmark, green color)
      - 'served' → "Delivered" or "Completed"
      
      Ensure component reactively updates when status prop changes (Vue's reactivity should handle this automatically). Add smooth transition animations when status changes.
      
      Files Modified:
      - `d:\Restaurant_system2\Client2\vue-project\src\components/guest/OrderStatusTimeline.vue`
      
      Verify: Pass changing status prop to component — visual timeline updates smoothly, animations play correctly.

- [ ] **25. Add /order-status/:orderId route to router**
      
      In `d:\Restaurant_system2\Client2\vue-project\src\router\index.ts`, add new public route for customer order status page:
      
      ```typescript
      {
        path: '/order-status/:orderId',
        name: 'order-status',
        component: () => import('../views/guest/OrderStatusPage.vue'),
        meta: {
          title: 'Order Status - Live Updates',
          requiresAuth: false,
          public: true
        }
      }
      ```
      
      Place near other guest routes (around line 400 after guest QR menu routes).
      
      Files Modified:
      - `d:\Restaurant_system2\Client2\vue-project\src\router\index.ts`
      
      Verify: Run dev server, navigate to `/order-status/test-123` — route resolves, component loads.

- [ ] **26. Update src/views/guest/QRMenu.vue to redirect after order creation**
      
      In `QRMenu.vue`, locate `handlePlaceOrder` function. After successful order creation (orderResponse.success === true), extract orderId from orderResponse.data.id or orderResponse.data.order_id. Store hotelId in localStorage if not already stored. Navigate to order status page:
      
      ```typescript
      const orderId = orderResponse.data.id || orderResponse.data.order_id
      localStorage.setItem('current_order_id', orderId)
      router.push({ name: 'order-status', params: { orderId } })
      ```
      
      Remove or hide the static success modal since user is now redirected to live order status page.
      
      Files Modified:
      - `d:\Restaurant_system2\Client2\vue-project\src\views/guest/QRMenu.vue`
      
      Verify: Place order via QR menu — after submission, browser navigates to `/order-status/{orderId}`, live order status page appears.

---

### PHASE 7: REMOVE POLLING FROM KITCHEN/WAITER DASHBOARDS

- [ ] **27. Update kitchen dashboard to use useKitchenOrders composable**
      
      Locate kitchen dashboard component(s) (kitchenDashboard.vue, FoodOrdersView.vue, etc.). Remove any setInterval/setTimeout polling for order updates. Replace with `useKitchenOrders(hotelId)` composable. Use reactive orders from composable, remove manual refresh calls.
      
      Search files for:
      - `setInterval` + `fetchOrders` or `loadOrders`
      - `setTimeout` + `refreshKitchen`
      - Polling-related code in kitchen components
      
      Files Modified:
      - `d:\Restaurant_system2\Client2\vue-project\src\views/kitchen/*.vue` (remove polling)
      - Add useKitchenOrders import and usage
      
      Verify: Kitchen dashboard updates in real-time when new order created, no setInterval in code, no repeated API calls in Network tab.

- [ ] **28. Update waiter dashboard to use useWaiterNotifications composable**
      
      Open `d:\Restaurant_system2\Client2\vue-project\src\views/waiter/WaiterDashboard.vue`. Search for polling logic (setInterval, fetchReadyOrders loops). Replace with `useWaiterNotifications(hotelId)` composable. Remove polling intervals.
      
      Also check `d:\Restaurant_system2\server\app\Services\Waiter\WaiterDashboardService.php` — if service has polling-related comments or methods, update documentation to reflect WebSocket-based real-time updates.
      
      Files Modified:
      - `d:\Restaurant_system2\Client2\vue-project\src\views/waiter/WaiterDashboard.vue`
      - `d:\Restaurant_system2\server\app\Services\Waiter\WaiterDashboardService.php` (update comments if needed)
      
      Verify: Waiter dashboard receives order-ready notifications in real-time via WebSocket, no polling in code or Network tab.

- [ ] **29. Search and remove all order-related polling across Vue frontend**
      
      Run global search in `d:\Restaurant_system2\Client2\vue-project\src` for:
      - `setInterval` (review each occurrence)
      - `setTimeout` + `fetch` or `axios` (review for polling patterns)
      - `poll`, `polling`, `refresh` functions that loop
      - Comments like "poll every X seconds" or "check order status"
      
      For each occurrence:
      - If related to order status, kitchen orders, waiter notifications → REMOVE, replace with WebSocket
      - If unrelated to orders (e.g., UI animations, debounce) → KEEP
      
      Files Modified: Various Vue components with polling
      
      Verify: Global search for `setInterval.*order` or `setInterval.*fetch` returns no order-related polling. Network tab shows NO repeated API calls for order data.

---

### PHASE 8: TESTING & VERIFICATION

- [ ] **30. TEST 1: End-to-end order flow with real-time updates**
      
      Test Steps:
      1. Start Reverb server: `cd d:\Restaurant_system2\server && php artisan reverb:start`
      2. Start queue worker: `php artisan queue:work` (separate terminal)
      3. Start Laravel server: `php artisan serve` (separate terminal)
      4. Start Vue dev server: `cd d:\Restaurant_system2\Client2\vue-project && npm run dev`
      5. Open browser: navigate to QR menu `http://localhost:5173/order/{qrToken}`
      6. Place an order, verify redirect to `/order-status/{orderId}`
      7. Verify page shows status: "Order Received"
      8. Open kitchen dashboard in another browser window: `http://localhost:5173/chef`
      9. Find the order in pending list
      10. Click "Start Preparing" button
      11. **Verify customer Order Status page updates to "Preparing" in real-time WITHOUT page refresh**
      12. In kitchen, click "Mark as Ready"
      13. **Verify customer page updates to "Ready" in real-time**
      14. Check browser console: WebSocket connection logs, OrderStatusUpdated events received
      15. Check Laravel logs: Broadcast jobs processed
      
      Expected Results:
      - Customer sees real-time status changes
      - NO page refresh required
      - NO repeated API calls in Network tab
      - WebSocket connection active (green indicator)
      - Smooth animations on status transitions
      
      Files: None (manual testing)
      
      Verify: All steps pass, real-time updates work correctly.

- [ ] **31. TEST 2: Multi-tenant isolation — Hotel A cannot see Hotel B orders**
      
      Test Steps:
      1. Create order A from Hotel A (use QR token for Hotel A)
      2. Create order B from Hotel B (use QR token for Hotel B)
      3. Open Order Status page for Order A in browser tab 1
      4. Open Order Status page for Order B in browser tab 2
      5. Change status of Order A in kitchen (Hotel A)
      6. **Verify ONLY Tab 1 updates, Tab 2 does NOT update**
      7. Check browser console: Tab 1 subscribed to `private-orders.{hotelA}.{orderA}`, Tab 2 to `private-orders.{hotelB}.{orderB}`
      8. Attempt to manually subscribe to wrong channel in console: `window.Echo.private('orders.{hotelB}.{orderA}')` from Hotel A context
      9. **Verify authorization fails (403 Forbidden)**
      
      Expected Results:
      - Orders from different hotels do NOT cross-contaminate
      - Channel authorization blocks unauthorized subscriptions
      - hotel_id is validated in channel auth callback
      
      Verify: Multi-tenant isolation is enforced, no data leakage.

- [ ] **32. TEST 3: Chapa payment real-time update**
      
      Test Steps:
      1. Place order with "Pay Now with Chapa" option
      2. Verify Order Status page shows "Pay Now" button
      3. Click button, complete Chapa payment flow (use test credentials)
      4. After payment webhook completes, **verify Order Status page updates payment_status to "Paid" in real-time via WebSocket**
      5. Verify NO page refresh required
      6. Check browser console: PaymentStatusUpdated event received
      
      Expected Results:
      - Payment status updates immediately via WebSocket
      - Customer sees "Payment Successful" without refresh
      - Existing Chapa integration not broken
      
      Verify: Payment real-time updates work correctly.

- [ ] **33. TEST 4: WebSocket reconnection handling**
      
      Test Steps:
      1. Open Order Status page
      2. Verify WebSocket connected (green indicator)
      3. Stop Reverb server: kill `php artisan reverb:start` process
      4. **Verify connection indicator turns red, "Disconnected" message appears**
      5. Change order status in database manually or via kitchen (if kitchen still connected)
      6. Restart Reverb server: `php artisan reverb:start`
      7. **Verify Order Status page automatically reconnects (green indicator returns)**
      8. **Verify page re-syncs order data (calls API once)**
      9. Change order status again
      10. **Verify real-time update resumes correctly**
      
      Expected Results:
      - Graceful disconnect handling
      - Automatic reconnection
      - Data re-sync on reconnect
      - No setInterval polling as fallback
      
      Verify: Reconnection logic works, data stays synchronized.

- [ ] **34. TEST 5: Kitchen dashboard real-time order creation**
      
      Test Steps:
      1. Open kitchen dashboard
      2. Place order from QR menu (different device/browser)
      3. **Verify order appears in kitchen pending list in real-time WITHOUT manual refresh**
      4. Check browser console: OrderCreated event received on `hotel.{hotelId}.kitchen` channel
      5. Verify NO setInterval polling in Network tab
      
      Expected Results:
      - Kitchen receives new orders instantly
      - No polling, only WebSocket events
      
      Verify: Kitchen real-time order creation works.

- [ ] **35. TEST 6: Waiter dashboard order-ready notifications**
      
      Test Steps:
      1. Open waiter dashboard
      2. In kitchen, mark an order as "Ready"
      3. **Verify waiter dashboard receives order-ready notification in real-time**
      4. Check console: Event received on `hotel.{hotelId}.waiters` channel
      5. Verify NO polling in waiter dashboard
      
      Expected Results:
      - Waiters notified instantly when orders ready
      - No polling, only WebSocket
      
      Verify: Waiter real-time notifications work.

- [ ] **36. TEST 7: Performance — No repeated API requests**
      
      Test Steps:
      1. Open Order Status page
      2. Open browser DevTools Network tab
      3. Wait 60 seconds
      4. **Verify NO repeated GET requests to `/api/guest/orders/{orderId}/status`**
      5. Only 1 initial API call on page load
      6. All subsequent updates via WebSocket events (check WS tab in DevTools)
      
      Expected Results:
      - NO polling in Network tab
      - Only WebSocket messages after initial load
      - Efficient, low-bandwidth real-time updates
      
      Verify: No unnecessary API requests, WebSocket-only updates.

- [ ] **37. TEST 8: Database is source of truth, WebSocket reflects DB state**
      
      Test Steps:
      1. Place order (status: pending)
      2. Open Order Status page
      3. Manually update order status in database to 'preparing' (SQL query or tinker)
      4. Dispatch OrderStatusUpdated event manually: `event(new App\Events\OrderStatusUpdated($order))`
      5. **Verify customer page updates to "Preparing"**
      6. Refresh page (hard refresh)
      7. **Verify status still shows "Preparing" (loaded from database)**
      
      Expected Results:
      - WebSocket events reflect database state
      - Page refresh shows correct DB state
      - No discrepancy between WebSocket and DB
      
      Verify: Database is source of truth, WebSocket is real-time sync mechanism.

---

### PHASE 9: PRODUCTION DEPLOYMENT PREPARATION

- [ ] **38. Document production Reverb deployment requirements**
      
      Create `d:\Restaurant_system2\REVERB_DEPLOYMENT.md` with production deployment guide:
      
      - System requirements: Linux server, Supervisor for process management, HTTPS/SSL certificate
      - Laravel Reverb production configuration: REVERB_HOST (production domain), REVERB_PORT (443 or 6001), REVERB_SCHEME=https
      - Supervisor config for keeping Reverb running 24/7:
        ```ini
        [program:laravel-reverb]
        command=php /path/to/artisan reverb:start --host=0.0.0.0 --port=8080
        autostart=true
        autorestart=true
        user=www-data
        redirect_stderr=true
        stdout_logfile=/var/log/reverb.log
        ```
      - Queue worker supervisor config
      - Nginx/Apache reverse proxy config for WebSocket (upgrade connection headers)
      - Environment variables for production (.env)
      - SSL certificate setup for wss:// connections
      - Firewall rules (allow WebSocket port)
      - Monitoring and logging
      
      Files Created:
      - `d:\Restaurant_system2\REVERB_DEPLOYMENT.md`
      
      Verify: Documentation is complete and accurate.

- [ ] **39. Update README with WebSocket architecture and setup instructions**
      
      Update `d:\Restaurant_system2\server\README.md` or create `d:\Restaurant_system2\README_WEBSOCKET.md`:
      
      - Architecture diagram (Vue → Echo → Reverb → Laravel → DB)
      - Development setup:
        - `composer require laravel/reverb`
        - `php artisan reverb:install`
        - Configure .env (BROADCAST_CONNECTION, REVERB_*)
        - `php artisan reverb:start` (terminal 1)
        - `php artisan queue:work` (terminal 2)
        - `php artisan serve` (terminal 3)
        - `npm run dev` (terminal 4)
      - Multi-tenant channel structure
      - Event documentation (OrderCreated, OrderStatusUpdated, etc.)
      - Testing steps
      - Troubleshooting common issues
      
      Files Modified/Created:
      - `d:\Restaurant_system2\README_WEBSOCKET.md`
      
      Verify: README provides clear setup instructions for developers.

- [ ] **40. Create .env.example entries with Reverb placeholders**
      
      Update both backend and frontend .env.example files:
      
      Backend (`server/.env.example`):
      ```env
      BROADCAST_CONNECTION=reverb
      REVERB_APP_ID=restaurant-app
      REVERB_APP_KEY=your-secure-random-key
      REVERB_APP_SECRET=your-secure-secret
      REVERB_HOST=127.0.0.1
      REVERB_PORT=8080
      REVERB_SCHEME=http
      QUEUE_CONNECTION=database
      ```
      
      Frontend (`Client2/vue-project/.env.example`):
      ```env
      VITE_REVERB_APP_KEY=restaurant-app
      VITE_REVERB_HOST=127.0.0.1
      VITE_REVERB_PORT=8080
      VITE_REVERB_SCHEME=http
      VITE_API_BASE_URL=http://127.0.0.1:8000
      ```
      
      Files Modified:
      - `d:\Restaurant_system2\server\.env.example`
      - `d:\Restaurant_system2\Client2\vue-project\.env.example`
      
      Verify: Placeholders are documented, developers can copy and customize.

---

## FINAL ARCHITECTURE

```
┌─────────────────────────────────────────────────────────────┐
│                       CUSTOMER BROWSER                       │
│                                                              │
│  Vue.js OrderStatusPage.vue                                 │
│           ↓ (uses)                                          │
│  useOrderStatus composable                                  │
│           ↓ (subscribes to)                                 │
│  Laravel Echo + pusher-js                                   │
│           ↓ (WebSocket connection)                          │
└──────────────────────────┬──────────────────────────────────┘
                           │
                    WebSocket (ws:// or wss://)
                           │
┌──────────────────────────▼──────────────────────────────────┐
│                   LARAVEL REVERB SERVER                      │
│                  (WebSocket Server)                          │
│                                                              │
│  Private Channels:                                          │
│  - orders.{hotelId}.{orderId}                               │
│  - hotel.{hotelId}.kitchen                                  │
│  - hotel.{hotelId}.waiters                                  │
└──────────────────────────┬──────────────────────────────────┘
                           │
                    Broadcast Events
                           │
┌──────────────────────────▼──────────────────────────────────┐
│              LARAVEL BROADCASTING SYSTEM                     │
│                                                              │
│  Events (implement ShouldBroadcast, ShouldQueue):          │
│  - OrderCreated                                             │
│  - OrderStatusUpdated                                       │
│  - PaymentStatusUpdated                                     │
│  - OrderCancelled                                           │
└──────────────────────────┬──────────────────────────────────┘
                           │
                    Dispatched by Services
                           │
┌──────────────────────────▼──────────────────────────────────┐
│                 LARAVEL SERVICES LAYER                       │
│                                                              │
│  - KitchenService (startPreparing, markReady)              │
│  - OrderStatusService (transition handlers)                 │
│  - Payment Controllers (Chapa webhook)                      │
│  - UnifiedOrderController (order creation)                  │
└──────────────────────────┬──────────────────────────────────┘
                           │
                    Database Transactions
                           │
┌──────────────────────────▼──────────────────────────────────┐
│                   DATABASE (Source of Truth)                 │
│                                                              │
│  - orders table (status, payment_status, hotel_id)         │
│  - order_items table                                        │
│  - Multi-tenant via hotel_id                                │
└─────────────────────────────────────────────────────────────┘

QUEUE WORKER (separate process):
  php artisan queue:work
  └─> Processes broadcast jobs asynchronously
  └─> Sends WebSocket messages via Reverb
```

---

## COMMANDS SUMMARY

### Development Setup (First Time):
```bash
# Backend
cd d:\Restaurant_system2\server
composer require laravel/reverb
php artisan reverb:install
# Configure .env (BROADCAST_CONNECTION=reverb, REVERB_*)
php artisan migrate

# Frontend
cd d:\Restaurant_system2\Client2\vue-project
npm install laravel-echo pusher-js
# Configure .env (VITE_REVERB_*)
```

### Daily Development (4 terminals):
```bash
# Terminal 1: Reverb WebSocket Server
cd d:\Restaurant_system2\server
php artisan reverb:start

# Terminal 2: Queue Worker
cd d:\Restaurant_system2\server
php artisan queue:work

# Terminal 3: Laravel Server
cd d:\Restaurant_system2\server
php artisan serve

# Terminal 4: Vue Dev Server
cd d:\Restaurant_system2\Client2\vue-project
npm run dev
```

### Production (using Supervisor):
- Configure supervisor for `reverb:start` and `queue:work`
- Use Nginx/Apache reverse proxy for WebSocket
- Set REVERB_SCHEME=https, configure SSL
- Monitor logs in /var/log/reverb.log

---

## SECURITY REQUIREMENTS ✅

- [ ] **Multi-tenant channel authorization**: routes/channels.php validates hotel_id for every channel
- [ ] **Order ownership verification**: Customer can only subscribe to their own order channels
- [ ] **Staff authorization**: Kitchen/waiter channels only accessible to authenticated hotel staff
- [ ] **Database validation**: All order updates validated before broadcasting
- [ ] **Environment secrets**: REVERB_APP_SECRET not exposed to frontend, only REVERB_APP_KEY
- [ ] **.env not committed**: .gitignore includes .env, only .env.example committed
- [ ] **HTTPS in production**: wss:// connections, SSL certificate configured
- [ ] **Rate limiting**: Consider adding rate limits to broadcasting/auth endpoint

---

## FILES CREATED/MODIFIED SUMMARY

### Backend Created:
- `config/reverb.php`
- `routes/channels.php`
- `app/Events/OrderCreated.php`
- `app/Events/OrderStatusUpdated.php`
- `app/Events/PaymentStatusUpdated.php`
- `app/Events/OrderCancelled.php`
- `app/Http/Controllers/Api/CustomerOrderController.php`

### Backend Modified:
- `composer.json` (add laravel/reverb)
- `config/broadcasting.php` (set default to reverb)
- `.env` (add REVERB_*, BROADCAST_CONNECTION)
- `.env.example` (add placeholders)
- `app/Services/KitchenService.php` (dispatch events)
- `app/Services/OrderStatusService.php` (dispatch events)
- `app/Http/Controllers/Api/GuestOrderPaymentController.php` (dispatch PaymentStatusUpdated)
- `app/Http/Controllers/Api/UnifiedOrderController.php` (dispatch OrderCreated)
- `routes/api.php` (add customer order status route)

### Frontend Created:
- `src/plugins/echo.ts`
- `src/composables/useOrderStatus.ts`
- `src/composables/useKitchenOrders.ts`
- `src/composables/useWaiterNotifications.ts`
- `src/views/guest/OrderStatusPage.vue`

### Frontend Modified:
- `package.json` (add laravel-echo, pusher-js)
- `.env` (add VITE_REVERB_*)
- `.env.example` (add placeholders)
- `src/main.ts` (import echo plugin)
- `src/router/index.ts` (add order-status route)
- `src/views/guest/QRMenu.vue` (redirect after order creation)
- `src/components/guest/OrderStatusTimeline.vue` (map status names)
- `src/views/kitchen/*.vue` (remove polling, use composable)
- `src/views/waiter/WaiterDashboard.vue` (remove polling, use composable)

### Documentation Created:
- `REVERB_DEPLOYMENT.md`
- `README_WEBSOCKET.md`

---

## COMPLIANCE WITH REQUIREMENTS ✅

✅ **Use Laravel Reverb** — Laravel Reverb installed and configured as WebSocket server  
✅ **Use Laravel Broadcasting** — All events implement ShouldBroadcast, use Broadcasting system  
✅ **Use Laravel Queue** — Events implement ShouldQueue, broadcast asynchronously  
✅ **Use Laravel Echo + pusher-js** — Frontend connects via Echo using Reverb protocol  
✅ **Multi-tenant private channels** — Channel names include hotel_id, authorization validates tenant  
✅ **NO Pusher Cloud** — Using self-hosted Laravel Reverb, not Pusher cloud service  
✅ **NO polling (setInterval/setTimeout)** — All order-related polling removed, replaced with WebSocket  
✅ **Initial data via API, updates via WebSocket** — useOrderStatus fetches once, then subscribes  
✅ **One dynamic Order Status page** — OrderStatusPage.vue updates in place, no separate pages  
✅ **Database is source of truth** — Events dispatched AFTER database commit  
✅ **Reconnection handling** — Echo handles reconnect, composable re-syncs data on reconnect  
✅ **Separate order_status and payment_status** — Events and UI keep statuses separate  
✅ **Chapa payment real-time update** — PaymentStatusUpdated dispatched from webhook  
✅ **Kitchen/waiter real-time updates** — Hotel-level channels for staff dashboards  
✅ **Security/authorization** — routes/channels.php validates hotel_id, user permissions  
✅ **Performance** — Broadcast events queued, WebSocket messages small payloads  
✅ **Testing plan** — 9 comprehensive tests covering all scenarios  
✅ **Production deployment docs** — REVERB_DEPLOYMENT.md with Supervisor config  

---

## TROUBLESHOOTING GUIDE

**Problem**: WebSocket connection fails (net::ERR_CONNECTION_REFUSED)  
**Solution**: Verify Reverb server is running (`php artisan reverb:start`), check VITE_REVERB_HOST and VITE_REVERB_PORT match Reverb config

**Problem**: 403 Forbidden on /broadcasting/auth  
**Solution**: Check routes/channels.php authorization callback, verify hotel_id matches order->hotel_id, check user authentication token

**Problem**: Events not received on frontend  
**Solution**: Verify queue worker is running (`php artisan queue:work`), check Laravel logs for broadcast job errors, verify channel name matches exactly (case-sensitive)

**Problem**: Order status updates in database but not via WebSocket  
**Solution**: Check event is dispatched after DB commit (not rolled back), verify ShouldBroadcast interface implemented, check queue worker processed job

**Problem**: Cross-tenant data leakage  
**Solution**: Verify channel name includes hotel_id, check authorization callback validates hotel_id parameter, review CustomerOrderController hotel_id validation

**Problem**: WebSocket disconnects frequently  
**Solution**: Check network stability, verify Reverb server not crashing (check logs), increase Reverb connection timeout in config, check firewall not blocking WebSocket port

**Problem**: High latency on WebSocket events  
**Solution**: Verify queue worker is running (events are queued), check Reverb server resources (CPU/memory), optimize event payload size, check network latency

**Problem**: Multiple duplicate events received  
**Solution**: Check component is not subscribing multiple times (use onMounted, not setup), verify channel.leave() called in onUnmounted, check for duplicate event dispatches in backend

---

**END OF IMPLEMENTATION PLAN**

This plan fully replaces Pusher Cloud with Laravel Reverb, eliminates all polling, implements multi-tenant secure WebSocket architecture, and provides production-ready real-time order status updates following the exact architecture diagram provided.

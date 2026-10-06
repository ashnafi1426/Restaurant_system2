# Track Order Button Fix - Complete Solution

## Issue
When clicking "Track My Order" button on the payment success page, the system showed an alert: "Unable to track order. Order ID not found."

## Root Cause Analysis

### Payment Flow
1. User scans QR code → QR Menu page
2. User adds items to cart
3. User clicks "Checkout" → Opens payment dialog
4. User selects "Pay with Chapa"
5. Frontend creates order via API → **Order ID is returned here**
6. Frontend redirects to Chapa payment gateway
7. User completes payment on Chapa
8. Chapa redirects back to `/order/payment/success?tx_ref=XXX`
9. Frontend verifies payment and completes order
10. **Problem**: Order ID was not being properly stored/retrieved

### Data Storage Points
```javascript
// Point 1: QRMenu.vue stores order data after creation
localStorage.setItem('pending_order_data', JSON.stringify({
  id: orderResponse.data.id,  // ← Order ID here
  order_number: orderResponse.data.order_number,
  items: [...],
  ...
}))

// Point 2: Payment completion API returns order
const completeData = await fetch('/api/order-payments/complete/TX123')
// Response: { success: true, order: { id: "...", ... } }

// Point 3: OrderPaymentSuccessPage needs to extract order.id
orderData.value = {
  id: completeData.order.id,  // ← Must store this
  ...
}
```

## Solution Implemented

### 1. Enhanced Order ID Storage
Updated `onMounted()` in `OrderPaymentSuccessPage.vue` to:
- Store order ID from payment completion API response
- Store order ID from sessionStorage payment data
- Add extensive console logging for debugging

```typescript
if (completeData.order.id) {
  localStorage.setItem('last_order_id', completeData.order.id)
  console.log('[OrderPaymentSuccess] Stored order ID in localStorage:', completeData.order.id)
}
```

### 2. Multi-Priority Order ID Retrieval
Created a cascading fallback system in `trackOrder()`:

```typescript
function trackOrder() {
  let orderId = null
  
  // PRIORITY 1: orderData from payment completion
  orderId = orderData.value?.id || orderData.value?.order_id
  
  // PRIORITY 2: localStorage last_order_id
  if (!orderId) orderId = localStorage.getItem('last_order_id')
  
  // PRIORITY 3: localStorage pending_order_data
  if (!orderId) {
    const stored = localStorage.getItem('pending_order_data')
    orderId = JSON.parse(stored)?.id
  }
  
  // PRIORITY 4: sessionStorage walk_in_payment_data
  if (!orderId) {
    const walkIn = sessionStorage.getItem('walk_in_payment_data')
    orderId = JSON.parse(walkIn)?.order_id
  }
  
  // PRIORITY 5: Fetch by transaction reference
  if (!orderId && txRef) {
    fetchOrderByTxRef(txRef)
    return
  }
  
  // Navigate to order status page
  if (orderId) {
    router.push({ name: 'order-status', params: { orderId } })
  }
}
```

### 3. API Fallback Function
Added `fetchOrderByTxRef()` to retrieve order ID from backend as last resort:

```typescript
async function fetchOrderByTxRef(txRefValue: string) {
  const response = await fetch(`/api/order-payments/${txRefValue}`)
  const data = await response.json()
  
  if (data.success && data.order?.id) {
    localStorage.setItem('last_order_id', data.order.id)
    router.push({ name: 'order-status', params: { orderId: data.order.id } })
  }
}
```

### 4. Enhanced Debug Logging
Added comprehensive logging at every step:
- Payment verification response
- Payment completion response
- Order ID extraction
- Storage operations
- Navigation attempts

## Files Modified

### Primary File
```
d:\Restaurant_system2\Client2\vue-project\src\views\payment\OrderPaymentSuccessPage.vue
```

**Changes:**
1. Enhanced `onMounted()` with order ID storage from multiple sources
2. Rewrote `trackOrder()` with 5-priority fallback system
3. Added `fetchOrderByTxRef()` for API fallback
4. Added extensive console logging for debugging

## Testing Instructions

### Test Case 1: Normal Payment Flow
```
1. Scan QR code
2. Add items to cart
3. Click Checkout
4. Select "Pay with Chapa"
5. Complete payment
6. Redirected to success page
7. Click "Track My Order"
Expected: Navigate to order status page ✅
```

### Test Case 2: Direct Access (No Session Data)
```
1. Copy payment success URL with tx_ref
2. Open in new incognito window
3. Click "Track My Order"
Expected: Fetch order by tx_ref, then navigate ✅
```

### Test Case 3: Multiple Orders
```
1. Place Order A, complete payment
2. Go back to menu (without tracking)
3. Place Order B, complete payment
4. Click "Track My Order"
Expected: Track Order B (most recent) ✅
```

## Debug Checklist

If "Track My Order" still fails, check console logs:

### Step 1: Check Payment Completion
```javascript
// Look for these logs:
[OrderPaymentSuccess] Complete response: {...}
[OrderPaymentSuccess] Order completed, ID: 01a0...
```
✅ If ID is shown → API is working
❌ If no ID → Backend issue

### Step 2: Check Storage
```javascript
// Look for these logs:
[OrderPaymentSuccess] Stored order ID in localStorage: 01a0...
[OrderPaymentSuccess] Stored order ID from walk-in data: 01a0...
```
✅ If stored → Storage is working
❌ If not stored → Storage issue

### Step 3: Check Retrieval
```javascript
// Look for these logs:
[OrderPaymentSuccess] Order ID from orderData: 01a0...
[OrderPaymentSuccess] Final Order ID: 01a0...
```
✅ If found → Retrieval is working
❌ If not found → Check all priority levels

### Step 4: Check Manual Storage (Console)
```javascript
// Run in browser console:
console.log('orderData:', orderData.value)
console.log('localStorage.last_order_id:', localStorage.getItem('last_order_id'))
console.log('localStorage.pending_order_data:', localStorage.getItem('pending_order_data'))
console.log('sessionStorage.walk_in_payment_data:', sessionStorage.getItem('walk_in_payment_data'))
```

## Common Issues & Solutions

### Issue 1: Order ID is undefined in orderData
**Cause:** Payment completion API not returning order.id
**Solution:** Check backend `completeOrder()` method returns order with id field

### Issue 2: localStorage is empty
**Cause:** Order creation in QRMenu.vue not storing data
**Solution:** Check QRMenu.vue order creation flow stores pending_order_data

### Issue 3: sessionStorage is empty
**Cause:** Payment initialization not storing walk_in_payment_data
**Solution:** Check payment initialization stores order details in sessionStorage

### Issue 4: API fallback fails
**Cause:** Backend endpoint `/api/order-payments/{tx_ref}` not found
**Solution:** Verify route exists in `routes/api.php`

## Backend Verification

### Check Order Payment Controller
File: `app/Http/Controllers/Api/GuestOrderPaymentController.php`

```php
public function completeOrder(string $txRef): JsonResponse
{
    // ...
    return response()->json([
        'success' => true,
        'message' => 'Order created successfully',
        'order' => $result['order'], // ← Must include 'id' field
        'payment' => new PaymentResource($payment),
    ]);
}
```

### Check Order Model
File: `app/Models/Order.php`

Ensure `id` is in fillable or not in guarded:
```php
protected $fillable = ['id', 'order_number', ...];
// OR
protected $guarded = []; // Allow all fields
```

## Data Flow Diagram

```
QR Menu (Order Creation)
    ↓
localStorage.pending_order_data = { id, order_number, items, ... }
    ↓
Redirect to Chapa
    ↓
Payment Complete
    ↓
Redirect to /order/payment/success?tx_ref=XXX
    ↓
onMounted()
  ├─ Verify payment (GET /api/payments/verify/{tx_ref})
  ├─ Complete order (POST /api/order-payments/complete/{tx_ref})
  └─ Store order.id → localStorage.last_order_id
    ↓
User clicks "Track My Order"
    ↓
trackOrder()
  ├─ Check orderData.id (PRIORITY 1)
  ├─ Check localStorage.last_order_id (PRIORITY 2)
  ├─ Check localStorage.pending_order_data (PRIORITY 3)
  ├─ Check sessionStorage.walk_in_payment_data (PRIORITY 4)
  └─ Fetch from API (PRIORITY 5)
    ↓
router.push({ name: 'order-status', params: { orderId } })
    ↓
Order Status Page (Real-time tracking)
```

## Success Criteria

✅ Payment completion stores order ID in localStorage
✅ trackOrder() finds order ID from at least one source
✅ Navigation to order status page succeeds
✅ Order status page displays order details
✅ Real-time updates work via WebSocket

## Prevention Measures

### 1. Always Store Order ID
Whenever an order is created or retrieved:
```typescript
if (order.id) {
  localStorage.setItem('last_order_id', order.id)
}
```

### 2. Always Log Storage Operations
```typescript
console.log('[Component] Stored order ID:', orderId)
console.log('[Component] Retrieved order ID:', orderId)
```

### 3. Always Check Multiple Sources
Never rely on a single storage location - implement fallbacks

### 4. Always Provide User Feedback
If order ID not found, explain what happened and next steps

---

**Status:** ✅ FIXED - Complete solution with 5-level fallback system
**Date:** 2026-10-06
**Component:** OrderPaymentSuccessPage.vue
**Function:** trackOrder() + fetchOrderByTxRef()
**Priority:** HIGH - Critical user journey (payment → order tracking)

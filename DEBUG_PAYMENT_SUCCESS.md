# Debug Guide: Payment Success Page Issue

## Current Problem
```
[OrderPaymentSuccess] No order ID found after checking all sources!
Debug info: {
  orderData: Proxy(Object),
  localStorage_last_order_id: null,
  localStorage_pending_order_data: null,
  sessionStorage_walk_in: null,
  txRef: ''
}
```

## Root Cause
**The tx_ref is empty** - this means either:
1. Chapa is not sending back tx_ref in the URL
2. The return URL is wrong
3. SessionStorage is being cleared before the redirect back

## Step-by-Step Debug Process

### Step 1: Check What Chapa Redirects With

Open your payment success page and check the URL in the browser:

#### Expected URL:
```
http://localhost:5173/order/payment/success?tx_ref=TX-CHAPA-xxxxx&status=success
```

#### What we're getting (probably):
```
http://localhost:5173/order/payment/success
```

**Action**: Copy the exact URL after Chapa redirects back

---

### Step 2: Check SessionStorage Before Redirect

Before payment, open browser DevTools Console and run:

```javascript
// This should show the data stored before Chapa redirect
console.log(sessionStorage.getItem('walk_in_payment_data'))
```

**Expected output**:
```json
{
  "payment_id": "01a0...",
  "tx_ref": "TX-CHAPA-xxxxx",
  "amount": 1000,
  "qr_token": "guest_...",
  "table_number": "Table 4",
  "items": [...],
  "calculation": {...}
}
```

**Action**: Copy this output before clicking "Pay Now"

---

### Step 3: Check SessionStorage After Redirect

After Chapa redirects back, open Console again and run:

```javascript
// Check if sessionStorage still has the data
console.log(sessionStorage.getItem('walk_in_payment_data'))
console.log(localStorage.getItem('last_order_id'))
console.log(localStorage.getItem('pending_order_data'))
```

**Question**: Is the data still there after redirect?

- ✅ YES → tx_ref should be extracted from sessionStorage
- ❌ NO → SessionStorage is being cleared (cross-origin issue?)

---

### Step 4: Manual Test

Open Console on the payment success page and run:

```javascript
// Manually set the data to test
sessionStorage.setItem('walk_in_payment_data', JSON.stringify({
  payment_id: 'test-payment',
  tx_ref: 'TX-CHAPA-TEST123',
  amount: 1000,
  qr_token: sessionStorage.getItem('guest_qr_token') || 'guest_test',
  table_number: 'Table 4',
  order_id: '01a06789-1234-5678-9012-345678901234', // ADD THIS
  items: [],
  calculation: { total: 1000 }
}))

// Then reload the page
location.reload()
```

After reload, click "Track My Order" - does it work now?

---

## Common Issues & Solutions

### Issue 1: Chapa Not Sending tx_ref Back

**Problem**: Chapa's return URL doesn't include tx_ref

**Solution**: Update backend to append tx_ref to return URL

File: `app/Http/Controllers/Api/GuestOrderPaymentController.php`

```php
'return_url' => config('chapa.order_return_url') . '?tx_ref=' . $payment->tx_ref,
```

Change line 97 from:
```php
'return_url' => config('chapa.order_return_url', config('app.frontend_url') . '/order/payment/success'),
```

To:
```php
'return_url' => config('chapa.order_return_url', config('app.frontend_url') . '/order/payment/success') . '?tx_ref=' . $payment->tx_ref . '&order_id=' . ($order->id ?? ''),
```

### Issue 2: SessionStorage Cleared on Redirect

**Problem**: Cross-origin redirect clears sessionStorage

**Solution**: Use localStorage instead of sessionStorage

In `QRMenu.vue`, change line 815:
```javascript
// FROM:
sessionStorage.setItem('walk_in_payment_data', JSON.stringify({...}))

// TO:
localStorage.setItem('walk_in_payment_data', JSON.stringify({...}))
```

In `OrderPaymentSuccessPage.vue`, change all:
```javascript
// FROM:
sessionStorage.getItem('walk_in_payment_data')

// TO:
localStorage.getItem('walk_in_payment_data') || sessionStorage.getItem('walk_in_payment_data')
```

### Issue 3: Order Not Created Before Payment

**Problem**: Payment is initialized but order isn't created yet

**Solution**: Create order FIRST, then initialize payment

Current flow:
```
1. Click "Pay with Chapa"
2. Initialize payment → Get payment_id, tx_ref
3. Redirect to Chapa
4. Complete payment
5. Redirect back
6. Create order ← ORDER CREATED HERE
```

Better flow:
```
1. Click "Pay with Chapa"
2. Create order → Get order_id
3. Initialize payment with order_id → Get payment_id, tx_ref
4. Redirect to Chapa
5. Complete payment
6. Redirect back with order_id in URL
7. Just verify and display
```

---

## Quick Fix: Add order_id to Return URL

### Backend Fix (RECOMMENDED)

File: `server/app/Http/Controllers/Api/WalkInOrderPaymentController.php`

Find the `initializePayment` method and update it to:

1. Create the order FIRST
2. Then initialize payment
3. Store order_id in payment metadata
4. Append order_id to return URL

```php
public function initializePayment(Request $request): JsonResponse
{
    // ... validation ...
    
    // CREATE ORDER FIRST
    $order = Order::create([
        'hotel_id' => $table->hotel_id,
        'table_id' => $table->id,
        'order_number' => $this->generateOrderNumber(),
        'status' => 'pending',
        'payment_status' => 'pending',
        // ... other fields
    ]);
    
    // Add order items
    foreach ($validated['items'] as $item) {
        $order->orderItems()->create([...]);
    }
    
    // NOW initialize payment with order_id
    $payment = $this->paymentService->createWalkInPayment([
        'hotel_id' => $table->hotel_id,
        'table_id' => $table->id,
        'order_id' => $order->id, // ← Add this
        'amount' => $total,
        'metadata' => [
            'order_id' => $order->id, // ← Add this
            'order_number' => $order->order_number,
            'items' => $orderItems,
        ],
    ]);
    
    // Initialize Chapa with order_id in return URL
    $returnUrl = config('chapa.order_return_url') . 
                 '?tx_ref=' . $payment->tx_ref . 
                 '&order_id=' . $order->id; // ← Add this
    
    $chapaResponse = $this->chapaService->initialize([
        // ... other fields
        'return_url' => $returnUrl, // ← Use modified URL
    ]);
    
    return response()->json([
        'success' => true,
        'order_id' => $order->id, // ← Return this
        'tx_ref' => $payment->tx_ref,
        'checkout_url' => $checkoutUrl,
    ]);
}
```

### Frontend Fix (TEMPORARY WORKAROUND)

If you can't change the backend right now, store order_id before redirect:

File: `Client2/vue-project/src/views/guest/QRMenu.vue`

After line 815, add:

```javascript
sessionStorage.setItem('walk_in_payment_data', JSON.stringify({
  payment_id: paymentResponse.payment_id,
  tx_ref: paymentResponse.tx_ref,
  amount: paymentResponse.amount,
  order_id: paymentResponse.order_id, // ← Add this if backend returns it
  qr_token: qrToken.value,
  table_number: orderContext.value.displayName,
  items: cartItems.value.map(item => ({
    name: item.name,
    quantity: item.quantity,
    price: item.price,
    total: item.price * item.quantity,
  })),
  calculation: paymentResponse.calculation,
}))
```

---

## Testing Commands

Run these in browser console after loading payment success page:

```javascript
// 1. Check what URL parameters we have
console.log('URL:', window.location.href)
console.log('Query params:', new URLSearchParams(window.location.search))

// 2. Check storage
console.log('sessionStorage walk_in:', sessionStorage.getItem('walk_in_payment_data'))
console.log('localStorage last_order_id:', localStorage.getItem('last_order_id'))
console.log('localStorage pending_order_data:', localStorage.getItem('pending_order_data'))

// 3. Check all storage keys
console.log('All sessionStorage keys:', Object.keys(sessionStorage))
console.log('All localStorage keys:', Object.keys(localStorage))

// 4. Manually trigger track order
// (if you see the button, click it and watch console)
```

---

## Next Steps

1. **Complete a payment** and immediately copy:
   - The URL after Chapa redirects
   - Console output (all logs starting with `[OrderPaymentSuccess]`)
   - SessionStorage contents

2. **Share these** so we can see exactly what's happening

3. **Apply the backend fix** (add order_id to return URL) - this is the best permanent solution

---

**Status**: Debugging in progress
**Priority**: HIGH - Critical payment flow
**Impact**: Users cannot track orders after payment

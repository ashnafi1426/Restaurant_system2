# Complete Fix Testing Guide

## Changes Made

### 1. Storage Fix (CRITICAL - localStorage instead of sessionStorage)
**File**: `Client2/vue-project/src/views/guest/QRMenu.vue` (Line ~815)
**Change**: Store payment data in BOTH localStorage AND sessionStorage

**Why**: SessionStorage is cleared during cross-origin redirects (to Chapa payment gateway and back). LocalStorage persists.

### 2. Backend Fix (CRITICAL - tx_ref in URL)
**Files**: 
- `server/app/Http/Controllers/Api/WalkInOrderPaymentController.php` (Line ~164)
- `server/app/Http/Controllers/Api/GuestOrderPaymentController.php` (Line ~97)

**Change**: Append tx_ref to return URL: `?tx_ref={tx_ref}`

### 3. Frontend Retrieval Fix
**File**: `Client2/vue-project/src/views/payment/OrderPaymentSuccessPage.vue`
**Change**: Check localStorage FIRST, then sessionStorage as fallback

## Testing Steps

### STEP 1: Verify Backend Changes

1. **Restart Laravel server**:
   ```bash
   cd d:\Restaurant_system2\server
   # Stop server if running (Ctrl+C)
   php artisan serve
   ```

2. **Verify routes exist**:
   ```bash
   php artisan route:list | Select-String "walk-in-payments"
   ```
   Should show:
   ```
   POST api/walk-in-payments/initialize
   GET  api/walk-in-payments/{txRef}
   POST api/walk-in-payments/complete/{txRef}
   ```

### STEP 2: Clear Browser Data

**IMPORTANT**: Old cached data will cause issues!

1. Open DevTools (F12)
2. Go to Application tab
3. Click "Clear storage" on the left
4. Click "Clear site data" button
5. Close and reopen browser

### STEP 3: Test Payment Flow

1. **Navigate to QR Menu**:
   ```
   http://localhost:5173/qr-menu/{some-qr-token}
   ```
   (Replace with actual QR token from your database)

2. **Open Console (F12)** - Keep it open throughout!

3. **Add items to cart**:
   - Click + button on menu items
   - Verify items appear in cart badge

4. **Click Cart Icon**

5. **Click "Pay with Chapa"**

6. **Fill payment form**:
   - First Name: Test
   - Last Name: User
   - Email: test@example.com
   - Phone: +251911234567

7. **Click "Confirm & Pay"**

8. **CHECK CONSOLE - BEFORE REDIRECT**:
   Look for:
   ```
   [QRMenu] Stored payment data before redirect: {
     payment_id: "...",
     tx_ref: "TX-CHAPA-xxxxx",
     amount: 1000,
     ...
   }
   [QRMenu] Redirecting to Chapa: https://checkout.chapa.co/...
   ```

9. **Manually verify localStorage** (in Console):
   ```javascript
   console.log(localStorage.getItem('walk_in_payment_data'))
   ```
   Should show the JSON with tx_ref!

10. **Complete payment on Chapa test page**
    - Use test card: 4200000000000000
    - Expiry: Any future date
    - CVV: Any 3 digits

11. **After redirect, CHECK URL**:
    Should be: `http://localhost:5173/order/payment/success?tx_ref=TX-CHAPA-xxxxx`
    
    **If NO ?tx_ref=**, backend changes didn't take effect!

12. **CHECK CONSOLE - AFTER REDIRECT**:
    Look for:
    ```
    [OrderPaymentSuccess] Mounted
    [OrderPaymentSuccess] Query params: { tx_ref: "TX-CHAPA-xxxxx" }
    [OrderPaymentSuccess] tx_ref from query: TX-CHAPA-xxxxx
    [OrderPaymentSuccess] walk_in_payment_data (localStorage): {"payment_id":"...","tx_ref":"TX-CHAPA-xxxxx",...}
    [OrderPaymentSuccess] After loading session data:
    - txRef: TX-CHAPA-xxxxx
    ```

13. **Manually verify localStorage still exists** (in Console):
    ```javascript
    console.log(localStorage.getItem('walk_in_payment_data'))
    console.log(sessionStorage.getItem('walk_in_payment_data'))
    ```
    localStorage should still have data!
    sessionStorage might be empty (that's OK now)

14. **Verify payment success page displays**:
    - Transaction ID shows
    - Order Number shows
    - Table number shows
    - Amount shows
    - "Track My Order" button visible

15. **Click "Track My Order"**

16. **CHECK CONSOLE**:
    ```
    [OrderPaymentSuccess] trackOrder called
    [OrderPaymentSuccess] orderData: { id: "01a0...", ... }
    [OrderPaymentSuccess] Order ID from orderData: 01a0...
    [OrderPaymentSuccess] Final Order ID: 01a0...
    [OrderPaymentSuccess] Navigating to order status with: {...}
    ```

17. **Verify navigation to order status page**:
    URL should be: `http://localhost:5173/order-status/01a0...`
    Page should show order details and real-time updates

## Expected Console Output (Complete Flow)

### On QR Menu (Before Chapa):
```
[QRMenu] Payment initialization started
[QRMenu] Stored payment data before redirect: {payment_id: "...", tx_ref: "TX-CHAPA-123", ...}
[QRMenu] Redirecting to Chapa: https://checkout.chapa.co/...
```

### On Payment Success (After Chapa):
```
[OrderPaymentSuccess] Mounted
[OrderPaymentSuccess] Query params: {tx_ref: "TX-CHAPA-123"}
[OrderPaymentSuccess] tx_ref from query: TX-CHAPA-123
[OrderPaymentSuccess] walk_in_payment_data (localStorage): {...}
[OrderPaymentSuccess] Parsed walk-in data: {...}
[OrderPaymentSuccess] Using tx_ref from sessionStorage: TX-CHAPA-123
[OrderPaymentSuccess] After loading session data:
- txRef: TX-CHAPA-123
- orderData: {...}
- localStorage keys: ["walk_in_payment_data", "guest_qr_token", ...]
[OrderPaymentSuccess] Verifying payment with tx_ref: TX-CHAPA-123
[OrderPaymentSuccess] Verify response: {success: true, ...}
[OrderPaymentSuccess] Completing payment at: .../api/walk-in-payments/complete/TX-CHAPA-123
[OrderPaymentSuccess] Complete response: {success: true, order: {id: "01a0..."}}
[OrderPaymentSuccess] Order completed, ID: 01a0...
[OrderPaymentSuccess] Stored order ID in localStorage: 01a0...
```

### On Track Order Click:
```
[OrderPaymentSuccess] trackOrder called
[OrderPaymentSuccess] orderData: {id: "01a0...", order_number: "ORD-001", ...}
[OrderPaymentSuccess] Order ID from orderData: 01a0...
[OrderPaymentSuccess] Final Order ID: 01a0...
[OrderPaymentSuccess] Navigating to order status with: {orderId: "01a0...", ...}
```

## Troubleshooting

### Issue 1: No tx_ref in URL after redirect
```
URL is: http://localhost:5173/order/payment/success
NOT:    http://localhost:5173/order/payment/success?tx_ref=TX-CHAPA-123
```

**Solution**:
1. Verify backend changes saved
2. Restart Laravel server: `php artisan serve`
3. Clear Laravel cache: `php artisan cache:clear`
4. Try payment again

### Issue 2: localStorage is empty
```
console.log(localStorage.getItem('walk_in_payment_data'))
// Returns: null
```

**Solution**:
1. Verify frontend changes saved in QRMenu.vue
2. Hard refresh: Ctrl+Shift+R
3. Clear browser cache completely
4. Check console for QRMenu logs before redirect

### Issue 3: Order ID still not found
```
[OrderPaymentSuccess] Final Order ID: null
```

**Check in this order**:
1. Is tx_ref in URL? → Fix backend
2. Is localStorage populated? → Fix QRMenu storage
3. Does API complete successfully? → Check server logs
4. Is order.id returned by API? → Check WalkInOrderPaymentController

### Issue 4: Direct navigation to success page (testing)
If you navigate directly to `/order/payment/success` for testing:

**Manual Setup** (in Console):
```javascript
// Set fake data for testing
localStorage.setItem('walk_in_payment_data', JSON.stringify({
  payment_id: 'test-payment-123',
  tx_ref: 'TX-CHAPA-TEST123',
  amount: 1000,
  qr_token: 'guest_test_token',
  table_number: 'Table 4',
  order_id: '01a06789-1234-5678-9012-345678901234', // FAKE ORDER ID
  items: [{name: 'Test Item', quantity: 1, price: 1000, total: 1000}],
  calculation: {total: 1000}
}))

localStorage.setItem('last_order_id', '01a06789-1234-5678-9012-345678901234')
localStorage.setItem('guest_qr_token', 'guest_test_token')
localStorage.setItem('hotel_id', '01a06789-0000-0000-0000-000000000000')

// Then reload
location.reload()
```

Now "Track My Order" should work!

## Verification Commands

Run these in browser console to debug:

```javascript
// 1. Check URL
console.log('URL:', window.location.href)
console.log('Query params:', Object.fromEntries(new URLSearchParams(window.location.search)))

// 2. Check storage
console.log('localStorage.walk_in_payment_data:', localStorage.getItem('walk_in_payment_data'))
console.log('sessionStorage.walk_in_payment_data:', sessionStorage.getItem('walk_in_payment_data'))
console.log('localStorage.last_order_id:', localStorage.getItem('last_order_id'))

// 3. Parse and check
const data = JSON.parse(localStorage.getItem('walk_in_payment_data') || '{}')
console.log('Parsed data:', data)
console.log('Has tx_ref?', !!data.tx_ref)
console.log('Has order_id?', !!data.order_id)
```

## Success Criteria

✅ Backend: Return URL includes `?tx_ref=TX-CHAPA-xxxxx`
✅ Frontend: localStorage contains walk_in_payment_data with tx_ref
✅ Payment success page: URL shows tx_ref parameter
✅ Console: Shows "Order completed, ID: 01a0..."
✅ Track Order: Button navigates to order status page
✅ Order Status: Page displays order details

---

**Status**: Ready for testing
**Date**: 2026-10-06
**Key Change**: localStorage instead of sessionStorage (persists through redirects)
**Files Modified**: 3 (QRMenu.vue, WalkInOrderPaymentController.php, OrderPaymentSuccessPage.vue)

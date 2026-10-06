# Quick Fix Reference - Track Order Button

## The Problem
Track Order button error: "Order ID not found"

## The Cause
SessionStorage cleared during Chapa redirect (cross-origin)

## The Solution
Use **localStorage** instead of sessionStorage

## Changes Made (3 files)

### 1. QRMenu.vue (Line ~815)
```javascript
// ADD THIS LINE:
localStorage.setItem('walk_in_payment_data', JSON.stringify(paymentData))
```

### 2. OrderPaymentSuccessPage.vue (Multiple lines)
```javascript
// CHANGE FROM:
sessionStorage.getItem('walk_in_payment_data')

// TO:
localStorage.getItem('walk_in_payment_data') || sessionStorage.getItem('walk_in_payment_data')
```

### 3. WalkInOrderPaymentController.php (Line 164)
```php
// CHANGE FROM:
'return_url' => config('chapa.order_return_url', ...),

// TO:
'return_url' => config('chapa.order_return_url', ...) . '?tx_ref=' . $payment->tx_ref,
```

## To Test

1. **Restart server**: `php artisan serve`
2. **Clear browser**: Ctrl+Shift+Delete → Clear all
3. **Complete payment** through QR menu
4. **Check URL**: Should have `?tx_ref=TX-CHAPA-xxxxx`
5. **Click "Track My Order"**: Should work! ✅

## Quick Debug

Run in console after payment success page loads:

```javascript
// Check data exists
console.log(localStorage.getItem('walk_in_payment_data'))
console.log(window.location.href) // Should have ?tx_ref=

// If null, manually set test data:
localStorage.setItem('walk_in_payment_data', JSON.stringify({
  tx_ref: 'TX-TEST',
  order_id: '01a06789-1234-5678-9012-345678901234',
  table_number: 'Table 4'
}))
localStorage.setItem('last_order_id', '01a06789-1234-5678-9012-345678901234')
location.reload()
```

## Expected Console Output

```
[OrderPaymentSuccess] tx_ref from query: TX-CHAPA-xxxxx
[OrderPaymentSuccess] walk_in_payment_data (localStorage): {...}
[OrderPaymentSuccess] Order completed, ID: 01a0...
[OrderPaymentSuccess] Order ID from orderData: 01a0...
```

## Common Issues

| Issue | Solution |
|-------|----------|
| No `?tx_ref=` in URL | Restart Laravel server |
| localStorage is null | Clear cache, try again |
| Still no order ID | Check console for error logs |

---

**TL;DR**: Changed from sessionStorage (cleared on redirect) to localStorage (persists). Restart server, clear cache, test payment.

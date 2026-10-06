# Real Payment Flow - Testing Guide

## Why Track Order Fails

You're navigating directly to:
```
http://localhost:5173/order/payment/success
```

This page has:
- ❌ NO tx_ref in URL
- ❌ NO data in localStorage
- ❌ NO order ID

## The REAL Flow (What Should Happen)

```
1. User scans QR → QR Menu page
2. Add items → Click cart
3. Click "Pay with Chapa" → Fill form
4. Redirect to Chapa → Complete payment
5. Chapa redirects back WITH tx_ref:
   http://localhost:5173/order/payment/success?tx_ref=TX-CHAPA-123
6. Page loads tx_ref → Verifies payment → Creates order → Gets order ID
7. "Track Order" button works ✅
```

## How To Test Properly

### Method 1: Complete Real Payment

1. **Get QR token from database:**
   ```sql
   SELECT qr_token FROM restaurant_tables LIMIT 1;
   ```

2. **Navigate to QR Menu:**
   ```
   http://localhost:5173/qr-menu/YOUR_TOKEN_HERE
   ```

3. **Add items and checkout**

4. **Use test card on Chapa:**
   - Card: 5200000000000007
   - Expiry: 12/25
   - CVV: 123

5. **After redirect, Track Order will work**

### Method 2: Manual Test (Debug Only)

If you're already on the success page without payment:

**Run in Console:**
```javascript
// Set fake data with REAL tx_ref from a completed payment
localStorage.setItem('walk_in_payment_data', JSON.stringify({
  tx_ref: 'TX-CHAPA-PASTE_REAL_TX_REF_HERE',
  payment_id: 'test',
  amount: 1000,
  table_number: 'Table 4',
  items: []
}))

// Reload page
location.reload()

// Then click the blue "Manual Complete Order" button that appears
```

## Check If Backend Fix Worked

After completing payment, URL should be:
```
✅ http://localhost:5173/order/payment/success?tx_ref=TX-CHAPA-xxxxx
❌ http://localhost:5173/order/payment/success (NO tx_ref = backend not fixed)
```

If NO tx_ref in URL:
1. Verify backend files saved
2. Restart Laravel: `php artisan serve`
3. Try payment again

---

**TL;DR**: Don't navigate directly to success page. Complete the full payment flow from QR menu!

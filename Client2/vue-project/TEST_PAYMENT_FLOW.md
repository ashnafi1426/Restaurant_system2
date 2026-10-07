# Payment Flow Testing Guide

## Current Issue
❌ You are navigating directly to `/order/payment/success`
❌ This bypasses the entire payment flow
❌ No tx_ref, no localStorage data, no order to track

## Correct Testing Steps

### 1. Get a Valid Table Token
```bash
# In server directory
php artisan tinker
```
```php
// In tinker
$table = App\Models\Table::first();
echo $table->qr_token;
```

### 2. Start From QR Menu
Navigate to: `http://localhost:5173/qr-menu/YOUR_TOKEN_HERE`

### 3. Add Items to Cart
- Click the **+ button** on any food item
- Verify quantity increases
- Click "View Cart" to see items

### 4. Proceed to Payment
- Click "Proceed to Payment"
- Fill in customer details:
  - Name: Test Customer
  - Phone: 0912345678
- Click "Continue to Payment"

### 5. Verify Payment Summary
- Review order details
- Click "Pay with Chapa"

### 6. Check localStorage (Before Chapa Redirect)
Open browser console and run:
```javascript
console.log('Payment Data:', localStorage.getItem('walk_in_payment_data'));
```

You should see:
```json
{
  "tx_ref": "TX-WALKIN-1234567890",
  "amount": 250,
  "items": [...],
  "customer_name": "Test Customer",
  "customer_phone": "0912345678"
}
```

### 7. Complete Chapa Payment
- On Chapa test page, click "Approve" or complete test payment
- Chapa redirects to: `http://localhost:5173/order/payment/success?tx_ref=TX-WALKIN-1234567890`

### 8. Verify Success Page
The page should:
1. Read `tx_ref` from URL query parameter ✓
2. Read payment data from localStorage ✓
3. Verify payment with backend API ✓
4. Complete the order and get order ID ✓
5. Show "Track My Order" button ✓

### 9. Track Order
- Click "Track My Order"
- Should navigate to `/order-status/123` (your order ID)
- Should show real-time order status

## Debug Commands

### Check localStorage in Browser Console:
```javascript
// Check payment data
console.log('Payment Data:', localStorage.getItem('walk_in_payment_data'));

// Clear if needed
localStorage.removeItem('walk_in_payment_data');

// Check all localStorage
for (let i = 0; i < localStorage.length; i++) {
    const key = localStorage.key(i);
    console.log(key, ':', localStorage.getItem(key));
}
```

### Check Backend Server is Running:
```bash
# In server directory
php artisan serve
```

Should show: `Starting Laravel development server: http://127.0.0.1:8000`

### Check Frontend Server is Running:
```bash
# In Client2/vue-project directory
npm run dev
```

Should show: `Local: http://localhost:5173/`

## Common Mistakes

❌ **Navigating directly to success page** → No payment flow, no data
❌ **Using old table token** → Token might be expired
❌ **Backend not restarted** → PHP changes not applied
❌ **Testing in incognito** → localStorage cleared on close

## Expected Console Logs (Success Page)

When working correctly, you should see:
```
[OrderPaymentSuccess] Component mounted
[OrderPaymentSuccess] tx_ref from query: TX-WALKIN-1733467890
[OrderPaymentSuccess] walk_in_payment_data (localStorage): {tx_ref: "TX-WALKIN-1733467890", amount: 250, ...}
[OrderPaymentSuccess] Payment data found: {tx_ref: "TX-WALKIN-1733467890", ...}
[OrderPaymentSuccess] Verifying payment for tx_ref: TX-WALKIN-1733467890
[OrderPaymentSuccess] Payment verified successfully
[OrderPaymentSuccess] Order completed successfully, Order ID: 123
[OrderPaymentSuccess] Order ID retrieved: 123
```

## Next Steps

1.  Backend server running with tx_ref append fix
2.  Frontend using localStorage (not sessionStorage)
3.  Success page checking all sources
4. 🔄 **YOU NEED TO TEST**: Go through complete flow starting from QR Menu
5. ⏸️ QR Menu refactoring workflow paused until this is verified

## If Still Failing After Proper Test

Check these files were updated:
- `server/app/Http/Controllers/Api/WalkInOrderPaymentController.php` line 164
- `server/app/Http/Controllers/Api/GuestOrderPaymentController.php` line 97
- `Client2/vue-project/src/views/guest/QRMenu.vue` line 815
- `Client2/vue-project/src/views/payment/OrderPaymentSuccessPage.vue`

Restart backend server:
```bash
# Stop current server (Ctrl+C)
php artisan serve
```

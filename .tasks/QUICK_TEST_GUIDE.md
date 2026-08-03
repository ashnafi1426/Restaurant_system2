# QR Menu Payment - Quick Test Guide

## 🚀 START TESTING IN 5 MINUTES

### Step 1: Start Backend (30 seconds)
```bash
cd server
php artisan serve
```
✅ Backend should be running at `http://127.0.0.1:8000`

### Step 2: Start Frontend (30 seconds)
```bash
cd Client2/vue-project
npm run dev
```
✅ Frontend should be running at `http://localhost:5173`

### Step 3: Get a QR Token (1 minute)
You need a valid QR token to test. Get it from:
- Your room's QR code data
- Database: `SELECT qr_token FROM rooms WHERE id = 'your-room-id'`
- Or use test token if available

### Step 4: Open QR Menu (10 seconds)
Navigate to: `http://localhost:5173/qr-menu/{YOUR-QR-TOKEN}`

Example: `http://localhost:5173/qr-menu/room-101-abc123`

---

## ✅ COMPLETE TEST FLOW (3 minutes)

### 1. Add Items to Cart
- Click on menu items
- Add to cart with quantity
- **Verify:** Cart icon shows item count

### 2. View Cart
- Click cart icon in header
- **Verify:** All items shown with correct prices
- **Verify:** Calculations visible:
  - Subtotal = Sum of (item price × quantity)
  - Tax (15%) = Subtotal × 0.15
  - Service (10%) = Subtotal × 0.10
  - Total = Subtotal + Tax + Service

### 3. Open Payment Dialog
- Click "Proceed to Payment" button
- **Verify:** Payment confirmation dialog opens
- **Verify:** Shows:
  - Room number
  - Guest name
  - Number of items
  - All cart items with quantities and prices
  - Price breakdown (subtotal, tax, service, total)
  - "Cancel" and "Pay Now" buttons

### 4. Review Payment Details
- **Check:** All prices match cart
- **Check:** Total is correct
- **Check:** No calculation errors

### 5. Proceed to Payment
- Click "💳 Pay Now" button
- **Verify:** Browser console shows logs starting with `[PAYMENT]`
- **Verify:** Page redirects to Chapa checkout URL
- **Expected:** URL should start with `https://checkout.chapa.co/`

### 6. Complete Payment (Chapa Test Mode)
If using Chapa test mode:
- Enter test card: `4200 0000 0000 0000`
- CVV: `123`
- Expiry: Any future date
- Click "Pay"

### 7. Success Page
- **Verify:** Redirects to `/order/payment/success`
- **Verify:** Success page shows:
  - ✓ Green header with checkmark
  - ✓ "Payment Successful!" message
  - ✓ Order number
  - ✓ Room number
  - ✓ Estimated delivery time (30 minutes)
  - ✓ Cart items list
  - ✓ Price breakdown
  - ✓ Transaction reference
  - ✓ "What's Next?" section
  - ✓ Action buttons

### 8. Verify Order in Kitchen
- Login as chef
- Go to kitchen dashboard
- **Verify:** Order appears in pending orders
- **Verify:** Order shows as PAID
- **Verify:** Order details match what was ordered

---

## 🐛 QUICK TROUBLESHOOTING

### Issue: Payment dialog doesn't open
**Check:**
- Browser console for errors
- Make sure cart has items

### Issue: Redirect to Chapa fails
**Check:**
- `.env` has `CHAPA_SECRET_KEY`
- Backend console for errors
- Network tab for API response

### Issue: Success page shows no data
**Check:**
- Browser console for `[ORDER PAYMENT SUCCESS]` logs
- SessionStorage has `order_payment_data`
- URL has `?tx_ref=` parameter

### Issue: Order not in kitchen
**Check:**
- Payment was actually completed
- Laravel logs: `storage/logs/laravel.log`
- Database: Check `orders` and `payments` tables

---

## 📊 WHAT TO CHECK

### Frontend Console Logs
Look for these log patterns:
```
🔒 [PAYMENT] Initializing payment for order...
📡 [PAYMENT] Fetching room/guest info...
✅ [PAYMENT] Room verified
💳 [PAYMENT] Initializing payment...
✅ [PAYMENT] Payment initialized successfully
🔄 [PAYMENT] Redirecting to Chapa checkout...
```

### Backend Logs
Check `storage/logs/laravel.log` for:
```
Order Payment Initialized
Payment ID: {uuid}
Guest ID: {uuid}
Room ID: {uuid}
Amount: {amount}
```

### Database Tables to Check
1. **payments** - Payment record should exist
2. **orders** - Order created after payment
3. **order_items** - Order items with quantities

---

## ✅ SUCCESS INDICATORS

If everything works, you should see:

1. ✅ Payment dialog opens smoothly
2. ✅ Calculations are correct (subtotal + 15% tax + 10% service)
3. ✅ Redirect to Chapa happens
4. ✅ Success page displays with all data
5. ✅ Order appears in kitchen dashboard
6. ✅ Order status is "pending" or "confirmed"
7. ✅ Payment status is "verified"

---

## 🎯 EXAMPLE TEST CALCULATION

### Sample Order:
- Burger: $10.00 × 2 = $20.00
- Fries: $5.00 × 1 = $5.00
- **Subtotal:** $25.00

### Expected Calculations:
- **Tax (15%):** $25.00 × 0.15 = $3.75
- **Service (10%):** $25.00 × 0.10 = $2.50
- **Total:** $25.00 + $3.75 + $2.50 = **$31.25**

### Verify:
- Cart shows: $31.25
- Payment dialog shows: $31.25
- Chapa checkout shows: $31.25
- Success page shows: $31.25

---

## 🚨 IMPORTANT NOTES

1. **Clear Browser Cache:** If you see stale data, clear cache and reload
2. **Check Chapa Mode:** Make sure you're in TEST mode, not LIVE
3. **Session Storage:** Payment data stored in browser's sessionStorage
4. **No Auto-Redirect:** Success page stays visible until user clicks button
5. **Kitchen Display:** Order only appears AFTER payment verification

---

## 📞 NEED HELP?

### Check These First:
1. Browser console (F12 → Console tab)
2. Network tab (F12 → Network tab)
3. Laravel logs (`storage/logs/laravel.log`)
4. `.env` file has correct Chapa credentials

### Still Stuck?
- Review main documentation: `QR_MENU_PAYMENT_INTEGRATION_COMPLETE.md`
- Check backend API with Postman/Insomnia
- Verify database has required data (rooms, menu items, guests)

---

**Happy Testing! 🎉**


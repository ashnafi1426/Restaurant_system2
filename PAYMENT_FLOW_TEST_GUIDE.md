# 🧪 Chapa Payment Flow - Complete Testing Guide

## 📋 Overview
This guide walks you through testing the complete 4-page Chapa payment integration flow for guest QR menu orders.

---

## 🔄 Complete Payment Flow

```
QR Menu → Cart → Place Order → Order Status → Pay Now →
Payment Verifying (2s) → Payment Summary → Chapa Checkout (External) →
Payment Success → Track My Order (Live Updates)
```

---

## ✅ Test Checklist

### **Phase 1: Order Placement**

1. **Open QR Menu**
   - Navigate to: `http://localhost:5173/qr-menu?hotel_id=YOUR_HOTEL_ID&qr_token=YOUR_TOKEN`
   - Verify: Menu items load correctly
   - Verify: Logo and branding display

2. **Add Items to Cart**
   - Click on menu item cards
   - Use **large green + button** to increase quantity (w-10 h-10)
   - Use **red - button** to decrease quantity
   - Verify: Quantity updates immediately
   - Verify: Cart badge shows correct item count
   - Add multiple items with different quantities

3. **Cart Modal**
   - Open cart modal
   - Verify: All items display with correct quantities and prices
   - Verify: Subtotal, service charge (10%), tax (15%) calculated correctly
   - Test: Adjust quantities using +/- buttons in cart
   - Test: Remove items
   - Verify: Total updates in real-time

4. **Place Order**
   - Click "Place Order" button
   - Select payment method: **"Online Payment"** (for Chapa flow)
   - Or select "Room Charge" if testing room billing
   - Verify: Order submits successfully
   - Verify: Redirects to Order Status page
   - **Important**: Check browser console for any errors

---

### **Phase 2: Order Status Page**

**URL Format**: `/order-status/:orderId?qr_token=YOUR_TOKEN`

#### Verify:
- ✅ Order number displays
- ✅ Table/Room number shows in header
- ✅ "Live" green dot indicates WebSocket connection
- ✅ Order items list with quantities and prices
- ✅ Total amount correct
- ✅ Payment status shows "⏳ Pending"
- ✅ Payment method shows "Online"
- ✅ **"Pay Now with Chapa"** button appears (purple gradient)

#### Test WebSocket Connection:
```
Watch for:
- Green "Live" indicator = Connected
- Gray "Offline" indicator = Disconnected
```

---

### **Phase 3: Payment Flow (4 Pages)**

#### **Page 1: Payment Verifying** ⏱️
**Route**: `/payment/verifying?order_id=XXX`

**Click**: "Pay Now with Chapa" button on Order Status page

**Expected**:
- Beige/amber background (bg-amber-50)
- Large circular play button loader with pulsing animation
- Text: "Verifying payment..."
- Three animated dots below
- **Auto-redirects after 2 seconds** to Payment Summary

**Check**:
- [ ] Page loads immediately
- [ ] Play button animates smoothly
- [ ] Automatically redirects after 2 seconds
- [ ] Order ID preserved in URL query

---

#### **Page 2: Payment Summary** 💳
**Route**: `/payment/summary/:orderId`

**Expected UI** (Dark theme with red gradient):
- Dark gray/black background
- Red gradient header card showing:
  - Shopping cart icon
  - "Your Order" title
  - Item count
  - **Large total amount** (ETB XXX.XX)
- List of all order items with quantities and prices
- **Tip Section** 🎁:
  - Three preset buttons: 10% (👍), 15% (😊), 20% (🌟)
  - "No Tip" button
  - "$ Custom" tip option
- Order Total breakdown:
  - Order Total
  - Tip amount (if selected)
  - **Final Total** in large red text
- **"Pay Now" button** (red-orange gradient)
- Security badge: "Secure Payment • Double-click Protected"

**Test Functionality**:
- [ ] Order details load from localStorage
- [ ] All items display correctly
- [ ] Click each tip option (10%, 15%, 20%)
- [ ] Verify tip amount calculates correctly
- [ ] Click "No Tip" - amount should be 0
- [ ] Click "$ Custom" - input field appears
- [ ] Enter custom tip amount - total updates
- [ ] Final total = Order Total + Tip
- [ ] Click "Pay Now" button

**Expected on "Pay Now"**:
- Button shows "Processing..."
- Makes API call to: `/api/order-payments/initialize-existing`
- Payload includes: `order_id`, guest info (first_name, last_name, email, phone)
- **Check browser console** for API request/response
- If successful: Response contains `checkout_url`
- **Redirects to Chapa** external checkout page

**Troubleshooting**:
```javascript
// Check browser console for:
console.log('[PaymentSummary] Initializing payment:', payload)
console.log('[PaymentSummary] Redirecting to Chapa:', checkout_url)

// If error occurs:
console.error('[PaymentSummary] Payment error:', error)
```

---

#### **Page 3: Chapa Checkout** 🏦
**External URL**: `https://checkout.chapa.co/...`

**This is Chapa's external payment page**

**Test**:
- [ ] Page loads in Chapa's domain
- [ ] Amount displayed correctly
- [ ] Can see order details
- [ ] **Test Payment** using Chapa test credentials
- [ ] Complete payment or cancel

**After Payment**:
- Chapa redirects to your callback URL
- Should route to Payment Success page

---

#### **Page 4: Payment Success** ✅
**Route**: `/payment/success-order`

**Expected UI** (matches reference screenshot):
- Dark gradient background (gray-900 to black)
- **Large green checkmark** animation at top
- "Payment Successful!" heading
- Subtitle: "Thank you for your order"

**Order Details Section** (white card):
- Order number with date/time
- Status badge (yellow "Preparing" or green "Ready")
- Full items list with quantities and individual prices
- Price breakdown:
  - Subtotal
  - Service Charge (10%)
  - Tax (15%)
  - Tip (if added)
  - **Grand Total** (large, bold)

**What's Next Section** (3 steps):
1. 👨‍🍳 Preparing - "Our chefs are preparing your order"
2. 🔔 Ready - "We'll notify you when ready"
3. 🎉 Delivered - "Enjoy your delicious meal"

**Important Notice** (blue card):
- Estimated time: 30-45 minutes
- Order confirmation sent to email
- Track order in real-time

**Action Buttons**:
- 📥 Download Receipt (blue)
- 📍 Track My Order (green gradient) - **Most important**
- 🍽️ Order More (gray)
- 🏠 Back to Home (gray outline)

**Test Functionality**:
- [ ] All order details display correctly
- [ ] Status badge shows current order status
- [ ] Items list complete with quantities/prices
- [ ] All totals calculated correctly
- [ ] Click **"Track My Order"** button
- [ ] Should navigate to Order Status page with real-time updates
- [ ] Click "Order More" - returns to QR menu
- [ ] Click "Back to Home" - returns to QR menu

---

### **Phase 4: Real-Time Order Tracking** 🔴 (Critical Test)

After clicking **"Track My Order"** from Payment Success:

#### Expected:
- Returns to **Order Status Page** with same order
- **"Live" green dot** should be pulsing (WebSocket connected)
- Payment status now shows: **"✅ Paid"**
- Status shows current order state (Preparing/Ready/Served)

#### Test WebSocket Updates:
**You need TWO browser windows/tabs:**

**Tab 1** (Guest - Order Status Page):
- Keep this open showing your order
- Watch the status section

**Tab 2** (Admin/Chef Dashboard):
- Login to admin panel
- Navigate to Orders Management
- Find your test order
- **Change order status**: Pending → Preparing → Ready → Served

**Expected in Tab 1** (Guest Order Status):
- Status updates **INSTANTLY** without page refresh
- Status card changes color:
  - Yellow: Pending
  - Orange: Preparing
  - Green: Ready/Served
- Status icon changes (📝 → 👨‍🍳 → ✅ → 🎉)
- Status message updates
- If browser notifications enabled: Shows notification popup
- "Last updated" timestamp updates

**Debugging WebSocket**:
```javascript
// Open browser console on Order Status page
// Check for:
[Echo] Connected to WebSocket
[OrderStatus] Subscribed to channel: hotel.{hotelId}.table.{tableNumber}.orders.{orderId}
[OrderStatus] Status updated: {status}
```

---

## 🔍 Common Issues & Solutions

### **Issue 1: "No match for payment-verifying route"**
**Solution**: 
```bash
# Restart dev server
cd Client2/vue-project
npm run dev
```

### **Issue 2: 403 Forbidden on Order Status**
**Cause**: Missing or invalid QR token
**Solution**: 
- Check localStorage has `guest_qr_token`
- Verify URL includes `?qr_token=YOUR_TOKEN`
- Token must match the one used to place order

### **Issue 3: Order data not showing on Payment Summary**
**Cause**: Order data not in localStorage
**Solution**: 
- Check localStorage for `pending_order_data`
- Ensure order placed successfully before payment
- Check browser console for errors

### **Issue 4: WebSocket not connecting (Gray "Offline")**
**Possible Causes**:
1. **Laravel WebSocket server not running**
   ```bash
   cd server
   php artisan websockets:serve
   ```

2. **Broadcasting queue not processed**
   ```bash
   cd server
   php artisan queue:work
   ```

3. **QR token not authorized for WebSocket**
   - Check `BroadcastAuthController.php` processes qr_token
   - Verify Echo.ts sends qr_token in authorization

**Debug**:
```javascript
// In browser console
localStorage.getItem('guest_qr_token')
// Should return valid token
```

### **Issue 5: Status not updating in real-time**
**Checks**:
1. Green "Live" dot should be visible and pulsing
2. Check WebSocket connection in browser Network tab (WS filter)
3. Verify channel name matches in backend broadcast and frontend subscription
4. Check Laravel logs: `storage/logs/laravel.log`

---

## 🎯 Success Criteria

### ✅ Full Flow Working When:
1. Can add items to cart with +/- buttons
2. Order places successfully with "Online Payment" method
3. Order Status page loads with "Pay Now" button
4. Clicking "Pay Now" redirects through 4 pages smoothly
5. Payment Summary shows correct totals and tip options
6. Can reach Chapa checkout page
7. Payment Success page shows complete order details
8. "Track My Order" returns to Order Status
9. **Green "Live" dot indicates WebSocket connected**
10. **Order status updates INSTANTLY when changed in admin**
11. All buttons navigate correctly

---

## 📊 Data Flow Architecture

```
┌─────────────────────────────────────────────────────────────┐
│                    ORDER PLACEMENT                          │
│  QR Menu → Add to Cart → localStorage → Place Order API    │
└─────────────────────────┬───────────────────────────────────┘
                          │
                          ▼
┌─────────────────────────────────────────────────────────────┐
│              ORDER STATUS (Initial)                         │
│  • Fetch from API: /api/guest/orders/{id}/realtime-status  │
│  • Requires qr_token authentication                         │
│  • Shows "Pay Now" if payment_status = pending              │
└─────────────────────────┬───────────────────────────────────┘
                          │
                   Click "Pay Now"
                          │
                          ▼
┌─────────────────────────────────────────────────────────────┐
│            PAYMENT VERIFYING (2 seconds)                    │
│  • Auto-redirect to Payment Summary                         │
│  • Passes order_id via query param                          │
└─────────────────────────┬───────────────────────────────────┘
                          │
                          ▼
┌─────────────────────────────────────────────────────────────┐
│               PAYMENT SUMMARY                               │
│  • Load order from localStorage (pending_order_data)        │
│  • Calculate tip options                                    │
│  • On "Pay Now":                                            │
│    POST /api/order-payments/initialize-existing             │
│    → Returns checkout_url                                   │
│    → Redirect to Chapa                                      │
└─────────────────────────┬───────────────────────────────────┘
                          │
                          ▼
┌─────────────────────────────────────────────────────────────┐
│              CHAPA CHECKOUT (External)                      │
│  • Customer completes payment                               │
│  • Chapa callback updates order in database                 │
│  • Redirects to Payment Success                             │
└─────────────────────────┬───────────────────────────────────┘
                          │
                          ▼
┌─────────────────────────────────────────────────────────────┐
│              PAYMENT SUCCESS                                │
│  • Show order details from localStorage                     │
│  • Display success message                                  │
│  • "Track My Order" button available                        │
└─────────────────────────┬───────────────────────────────────┘
                          │
                Click "Track My Order"
                          │
                          ▼
┌─────────────────────────────────────────────────────────────┐
│         ORDER STATUS (Real-Time Tracking)                   │
│  • WebSocket connection via Laravel Echo                    │
│  • Subscribe to: hotel.{id}.table.{num}.orders.{id}         │
│  • Listen for: OrderStatusUpdated event                     │
│  • Status updates instantly without refresh                 │
│  • QR token required for channel authorization              │
└─────────────────────────────────────────────────────────────┘
```

---

## 🔐 Authentication Flow

```
┌──────────────────────────┐
│   Guest scans QR code    │
│   Gets qr_token          │
└───────────┬──────────────┘
            │
            ▼
┌────────────────────────────────────────┐
│  Token stored in:                      │
│  1. URL query param (?qr_token=XXX)    │
│  2. localStorage (guest_qr_token)      │
└───────────┬────────────────────────────┘
            │
            ▼
┌────────────────────────────────────────┐
│  Token used for:                       │
│  • API calls (query param or header)   │
│  • WebSocket auth (in request body)    │
│  • Order access validation             │
└────────────────────────────────────────┘
```

---

## 📞 Support

If you encounter issues not covered here:

1. **Check browser console** for JavaScript errors
2. **Check Laravel logs**: `server/storage/logs/laravel.log`
3. **Check WebSocket server logs** if status not updating
4. **Verify database**: Check `orders` table for correct status
5. **Test WebSocket separately**: Use WebSocket test tools

---

## 🚀 Quick Start Command

```bash
# Terminal 1 - Backend
cd server
php artisan serve

# Terminal 2 - WebSocket Server
cd server
php artisan websockets:serve

# Terminal 3 - Queue Worker
cd server
php artisan queue:work

# Terminal 4 - Frontend
cd Client2/vue-project
npm run dev
```

---

## ✨ Expected User Experience

**Perfect flow:**
1. Guest scans QR → Browses menu (2 min)
2. Adds items with + button → Reviews cart (30 sec)
3. Places order → Sees "Order Received" (instant)
4. Clicks "Pay Now" → Smooth 4-page flow (1 min)
5. Completes Chapa payment (1-2 min)
6. Sees success page → Clicks "Track My Order" (instant)
7. Watches order status update live as chef prepares (10-20 min)
8. Gets notified when ready → Enjoys meal! 🎉

**Total time from scan to tracking: ~5 minutes**

---

## 📝 Notes

- All amounts in Ethiopian Birr (ETB)
- Service charge: 10% of subtotal
- Tax: 15% of subtotal
- Tip is optional and added to final total
- WebSocket requires persistent connection
- QR token expires after session (configurable)
- Payment confirmation emails sent automatically

---

**Created**: Based on implemented payment flow
**Last Updated**: Current session
**Status**: Ready for testing ✅

# 🧪 UNIFIED QR ORDERING SYSTEM - TESTING CHECKLIST
**Date**: July 31, 2026  
**Status**: ✅ READY FOR TESTING  
**API Fix Applied**: ✅ Fixed `/api/categories` → `/categories`

---

## 📋 PRE-TEST SETUP

### ✅ Backend Status
- **Status**: Running on `http://127.0.0.1:8000`
- **Command**: `php artisan serve`
- **Database**: MySQL configured (hotel database)
- **API**: All 16 endpoints functional

### ✅ Frontend Status
- **Status**: Should be running on `http://localhost:5173`
- **Framework**: Vue 3 + Vite
- **State Management**: Pinia

### ✅ Recent Fixes Applied
- Fixed API path: `/api/categories` → `/categories`
- Customer type modal displays FIRST on page load
- Walk-in session creates automatically (NO extra prompts)
- Hotel guest verification modal appears after type selection
- Unified shopping cart for both customer types

---

## 🎯 TEST SCENARIO 1: WALK-IN CUSTOMER FLOW

### Test URL
```
http://localhost:5173/menu?token=ZZFHYZZI
```

### Expected Behavior
1. ✅ Page loads
2. ✅ Customer Type Modal appears FIRST
   - Text: "Welcome! How are you dining today?"
   - Option 1: "I am staying in the hotel"
   - Option 2: "I am visiting the restaurant"
3. ✅ User clicks "I am visiting the restaurant"
4. ✅ Modal closes
5. ✅ Session creates automatically (NO room/reservation request)
6. ✅ Menu appears with categories and items
7. ✅ Console logs show:
   - "Showing customer type modal - first time visit"
   - "Customer type selected: walk_in"
   - "Walk-in customer selected - creating session"
   - "Walk-in session created successfully: { id: ..., session_number: ... }"

### Test Steps
```
1. Open browser console (F12)
2. Navigate to: http://localhost:5173/menu?token=ZZFHYZZI
3. Wait for page load (3-5 seconds)
4. Verify customer type modal appears
5. Click "I am visiting the restaurant"
6. Verify session creates (check console)
7. Verify menu displays with items
8. Add 2-3 items to cart
9. Click "View Cart" or cart icon
10. Verify cart shows all items with:
    - Subtotal (sum of all items × quantity)
    - Tax (15% of subtotal)
    - Service Charge (10% of subtotal)
    - Total (subtotal + tax + service)
11. Click "Place Order"
12. Verify success modal appears with Order Number
```

### Console Logs to Check
```
✅ "Showing customer type modal - first time visit"
✅ "Customer type selected: walk_in"
✅ "Walk-in customer selected - creating session"
✅ "Creating walk-in session with QR token: ZZFHYZZI"
✅ "Walk-in session created successfully: ..."
✅ [QRMenuLayout] Fetching categories from API...
✅ API Response: {data: Array(9), ...}
✅ Loaded X categories from backend
```

### ❌ If You See These Errors, It Means:
| Error | Cause | Solution |
|-------|-------|----------|
| `404 Not Found: /api/api/categories` | Double `/api` prefix (FIXED) | Clear browser cache & refresh |
| `404 Not Found: /guest/menu/...` | Backend route not found | Check API routes in server/routes/api.php |
| `ERR_CONNECTION_REFUSED` | Backend not running | Run `php artisan serve` in server folder |
| Modal doesn't appear | localStorage has old data | Open DevTools → Clear Storage → Reload |

---

## 🎯 TEST SCENARIO 2: HOTEL GUEST FLOW

### Test URL
```
http://localhost:5173/order/hotel-qr-token
```

### Expected Behavior
1. ✅ Page loads
2. ✅ Customer Type Modal appears FIRST
3. ✅ User clicks "I am staying in the hotel"
4. ✅ Modal closes
5. ✅ Room Verification Modal appears
   - Text: "Verify Your Room"
   - Input 1: "Room Number" (e.g., 101)
   - Input 2: "Reservation Number" (e.g., RES001)
   - "OR" divider between inputs
6. ✅ User enters room number and clicks "Verify"
7. ✅ API verifies all 3 conditions:
   - Reservation exists
   - Guest checked in
   - Room active
8. ✅ Menu appears (after verification)
9. ✅ Console logs show:
   - "Showing customer type modal - first time visit"
   - "Customer type selected: hotel_guest"
   - "Hotel guest selected - showing room verification modal"
   - "Verifying room with QR token: hotel-qr-token"
   - "Room verification successful: 101"

### Test Steps
```
1. Open browser console (F12)
2. Navigate to: http://localhost:5173/order/hotel-qr-token
3. Wait for page load (3-5 seconds)
4. Verify customer type modal appears
5. Click "I am staying in the hotel"
6. Verify room verification modal appears
7. Enter room number: "101"
8. Click "Verify"
9. Verify room is verified (modal closes, menu appears)
10. Add 2-3 items to cart
11. Click "View Cart"
12. Verify cart calculations:
    - Subtotal = price × quantity (all items)
    - Tax = subtotal × 0.15
    - Service = subtotal × 0.10
    - Total = subtotal + tax + service
13. Click "Place Order"
14. Verify success modal appears
15. Click "Track Order"
```

### Console Logs to Check
```
✅ "Showing customer type modal - first time visit"
✅ "Customer type selected: hotel_guest"
✅ "Hotel guest selected - showing room verification modal"
✅ "Verifying room with QR token: hotel-qr-token"
✅ "Room verification successful: 101"
✅ Room number saved to: localStorage
✅ Guest info saved to: localStorage
```

### ❌ Verification Errors to Test
```
Test Case 1: Empty Room Number
- Don't enter anything
- Click "Verify"
- Expected: "Please enter either room number or reservation number"

Test Case 2: Invalid Room
- Enter: 999
- Click "Verify"
- Expected: "Room not found or reservation is not active"

Test Case 3: Unchecked Guest
- Enter: 105 (unchecked guest)
- Click "Verify"
- Expected: Error message (if room exists but guest not checked in)
```

---

## 🛒 TEST SCENARIO 3: SHOPPING CART FUNCTIONALITY

### Test for Both Customer Types

#### Add to Cart
```
1. Browse menu items
2. Click "Add to Cart" on any item
3. Verify item appears in cart
4. Click "Add to Cart" again on same item
5. Verify quantity increments (not duplicate entries)
```

#### Update Quantity
```
1. Open cart modal
2. Find any item
3. Click "+" button (increment quantity)
4. Verify quantity increases
5. Click "-" button (decrement quantity)
6. Verify quantity decreases
7. If quantity = 1 and click "-", verify item is removed
```

#### Remove Item
```
1. Open cart modal
2. Click trash icon on any item
3. Verify item is removed immediately
4. Verify totals recalculate
```

#### Verify Calculations
```
Example: 
- Grilled Chicken: $15.00 × 2 = $30.00
- Pasta: $12.00 × 1 = $12.00
- Subtotal: $42.00
- Tax (15%): $6.30
- Service (10%): $4.20
- Total: $52.50

Formula:
- Subtotal = SUM(price × quantity)
- Tax = Subtotal × 0.15
- Service = Subtotal × 0.10
- Total = Subtotal + Tax + Service
```

#### Empty Cart
```
1. Remove all items
2. Verify: "Your cart is empty" message
3. Verify: "Place Order" button is disabled
4. Verify: "Continue Shopping" button is enabled
5. Click "Continue Shopping"
6. Verify: Cart modal closes, menu is visible
```

---

## 💳 TEST SCENARIO 4: CHECKOUT PROCESS

### Walk-in Checkout
```
1. Add items to cart
2. Click "Place Order"
3. Verify: Order creates successfully
4. Verify: Success modal appears
5. Modal shows:
   - Checkmark icon (green)
   - "Order Placed Successfully!"
   - Order Number
   - Room/Table info
   - Estimated Time
   - Total Amount
6. Click "Track Order"
7. Click "Back to Menu"
8. Verify: Cart clears
9. Verify: Can add new items
```

### Hotel Guest Checkout
```
Same as walk-in, BUT:
- No payment required (charge to room)
- Success message may mention "charged to room"
- Room number displayed in success modal
```

---

## 📊 CALCULATOR TEST - VERIFY TAX & SERVICE CALCULATIONS

### Test Case 1: Single Item
```
Item: Biryani - $20.00 × 1

Calculation:
- Subtotal: $20.00
- Tax (15%): $3.00
- Service (10%): $2.00
- Total: $25.00

Verify in cart:
- Subtotal: $20.00 ✓
- Tax (15%): $3.00 ✓
- Service Charge (10%): $2.00 ✓
- Total: $25.00 ✓
```

### Test Case 2: Multiple Items
```
Items:
- Grilled Fish: $18.00 × 2 = $36.00
- Vegetable Dish: $8.00 × 3 = $24.00
- Juice: $5.00 × 1 = $5.00

Calculation:
- Subtotal: $36 + $24 + $5 = $65.00
- Tax (15%): $65 × 0.15 = $9.75
- Service (10%): $65 × 0.10 = $6.50
- Total: $65 + $9.75 + $6.50 = $81.25

Verify in cart:
- Subtotal: $65.00 ✓
- Tax (15%): $9.75 ✓
- Service Charge (10%): $6.50 ✓
- Total: $81.25 ✓
```

### Test Case 3: Round Numbers Check
```
Test if calculations handle decimals properly:
- Item: $9.99 × 2 = $19.98
- Tax: $19.98 × 0.15 = $2.997 (should round to $3.00)
- Service: $19.98 × 0.10 = $1.998 (should round to $2.00)
- Total: $19.98 + $3.00 + $2.00 = $24.98

Verify displayed values are formatted correctly
```

---

## 🔍 BROWSER CONSOLE - VERIFICATION CHECKLIST

### Walk-in Flow Console Output
```javascript
✅ "Showing customer type modal - first time visit"
✅ "Customer type selected: walk_in"
✅ "Walk-in customer selected - creating session"
✅ "Creating walk-in session with QR token: ZZFHYZZI"
✅ "Session creation response: { data: { ... } }"
✅ "Walk-in session created successfully: { id: ..., session_number: ... }"
✅ "[QRMenuLayout] Fetching categories from API..."
✅ "[QRMenuLayout] API Response: {data: Array(9)}"
✅ "[QRMenuLayout] Loaded X categories from backend"
✅ "[QRMenuLayout] Loading menu items for QR token: ZZFHYZZI"
✅ "Placing order for: walk_in"
✅ "Processing walk-in customer order - requires payment"
✅ "Walk-in order created:"
```

### Hotel Guest Flow Console Output
```javascript
✅ "Showing customer type modal - first time visit"
✅ "Customer type selected: hotel_guest"
✅ "Hotel guest selected - showing room verification modal"
✅ "Verifying room with QR token: hotel-qr-token"
✅ "Room verification response: { room: {...}, guest: {...}, reservation: {...} }"
✅ "Room verification successful: 101"
✅ "[QRMenuLayout] Fetching categories from API..."
✅ "[QRMenuLayout] Loaded X categories from backend"
✅ "Placing order for: hotel_guest"
✅ "Processing hotel guest order - charging to room: 101"
✅ "Hotel guest order created:"
```

---

## 📱 RESPONSIVE DESIGN CHECK

### Mobile (320px - 640px)
```
✅ Customer type modal fits screen
✅ Buttons are clickable (not too small)
✅ Text is readable
✅ Modal overlay covers full screen
✅ Sidebar toggles correctly
```

### Tablet (641px - 1024px)
```
✅ Layout is properly centered
✅ Sidebar is visible
✅ Main content is readable
✅ Cart modal displays correctly
```

### Desktop (1025px+)
```
✅ Full sidebar visible
✅ Main content uses full width (minus sidebar)
✅ Hero section displays properly
✅ All UI elements aligned
```

---

## 🚀 FINAL CHECKLIST BEFORE PRODUCTION

- [ ] Backend is running (`php artisan serve`)
- [ ] Frontend is running (`npm run dev`)
- [ ] Customer type modal appears FIRST
- [ ] Walk-in flow completes without extra prompts
- [ ] Hotel guest verification works
- [ ] Menu loads with categories and items
- [ ] Shopping cart adds/removes items correctly
- [ ] Cart calculations are accurate (tax 15%, service 10%)
- [ ] Order places successfully (both types)
- [ ] Success modal displays order info
- [ ] No console errors
- [ ] All API calls are successful (check Network tab)
- [ ] localStorage persists customer type
- [ ] Mobile responsive design works
- [ ] Can test multiple times (localStorage clears properly)

---

## 🐛 DEBUGGING TIPS

### Clear Browser Cache & Storage
```
1. Open DevTools (F12)
2. Go to "Application" tab
3. Click "Clear site data"
4. Select all options
5. Refresh page
```

### Check API Calls in Network Tab
```
1. Open DevTools (F12)
2. Go to "Network" tab
3. Reload page
4. Filter by "XHR" (XMLHttpRequest)
5. Look for:
   - ✅ /categories (200 OK)
   - ✅ /guest/menu/.../items (200 OK)
   - ✅ /walk-in/session/initialize (201 Created)
   - ✅ /guest/orders or /walk-in/orders (200 OK)
```

### Enable All Console Logs
```
In browser console, you should see:
- QRMenu.vue logs
- QRMenuLayout.vue logs
- Auth interceptor logs
- Category loading logs
```

---

## ✅ SUCCESS CRITERIA

**All tests pass when:**
1. ✅ Customer type modal shows FIRST (before menu)
2. ✅ Walk-in customers can order without room prompts
3. ✅ Hotel guests can verify room and order
4. ✅ Cart works identically for both types
5. ✅ All calculations are correct
6. ✅ Orders place successfully
7. ✅ No 404 errors in API calls
8. ✅ No console errors
9. ✅ Mobile and desktop work correctly
10. ✅ localStorage persists data

---

**Status**: 🎯 READY FOR COMPREHENSIVE TESTING  
**Next Step**: Start with Test Scenario 1 (Walk-in Flow)

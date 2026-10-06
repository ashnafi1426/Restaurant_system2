# Test Payment Flow - OrderPaymentPage

## ✅ Configuration Status

### Files Confirmed
- ✅ **OrderPaymentPage.vue** exists at `src/views/payment/OrderPaymentPage.vue`
- ✅ **Route** configured in `router/index.ts` as `/order/payment`
- ✅ **QRMenu.vue** navigation configured to push to `/order/payment`
- ✅ **Build successful** - No TypeScript or compilation errors

## 🧪 How to Test

### Step 1: Start the Development Server
```bash
cd d:\Restaurant_system2\Client2\vue-project
npm run dev
```

### Step 2: Access QR Menu
Open your browser and go to:
```
http://localhost:5173/menu
```
or
```
http://localhost:5173/restaurant-order/YOUR_QR_TOKEN
```

### Step 3: Add Items to Cart
1. Click on menu items to add them to cart
2. Click the cart icon to view cart

### Step 4: Click "Pay with Chapa"
This should:
- Close the cart modal
- Navigate directly to `/order/payment` (OrderPaymentPage)
- **Skip** the customer details form dialog

### Step 5: Verify OrderPaymentPage Shows
You should see:
- **Dark theme** (slate-900 background)
- **Header** with "Pay Your Order" and table number
- **Your Order** card showing all items
- **Add Tip?** section with 6 options:
  - 👍 10%
  - 😊 15%
  - ✨ 20%
  - ❤️ 25%
  - 💵 Custom
  - No Tip
- **Total** card showing Order Total + Tip = Total
- **Pay Now** button

## 🔍 Troubleshooting

### If OrderPaymentPage doesn't appear:

#### Check 1: Verify Route in Browser
After clicking "Pay with Chapa", check the browser URL:
- Should be: `http://localhost:5173/order/payment?qr_token=...`
- If URL is correct but page blank → check console for errors

#### Check 2: Open Browser Console (F12)
Look for errors like:
- `[OrderPayment] No payment data found in localStorage`
- Component mount errors
- API errors

#### Check 3: Check localStorage
In browser console, run:
```javascript
JSON.parse(localStorage.getItem('walk_in_payment_data'))
```
Should show:
```json
{
  "qr_token": "ABC123",
  "table_number": "Table 11",
  "items": [...],
  "calculation": {
    "subtotal": 500,
    "tip": 0,
    "total": 500
  }
}
```

#### Check 4: Verify QRMenu Navigation
Add console logging in QRMenu.vue `openPaymentDialog` function:
```javascript
console.log('[QRMenu] Button clicked, navigating to payment page')
console.log('[QRMenu] Payment data:', paymentData)
console.log('[QRMenu] Pushing to route:', '/order/payment')
```

#### Check 5: Check Dev Server Output
Look at terminal where `npm run dev` is running:
- Should show no errors
- Should compile successfully

### If You See the Customer Details Form Dialog:
This means the old code is still running. Try:
1. **Hard refresh** browser: `Ctrl + Shift + R` (Windows) or `Cmd + Shift + R` (Mac)
2. **Clear cache**: Browser DevTools → Network tab → "Disable cache" checkbox
3. **Restart dev server**:
   ```bash
   # Stop with Ctrl+C, then:
   npm run dev
   ```

## 📱 Expected Flow

### Current Flow (After Fix)
```
[Cart Page]
    ↓ Click "Pay with Chapa"
    ↓
[OrderPaymentPage] ← DARK THEME WITH TIP SELECTION
    ↓ Select tip (e.g., 15%)
    ↓ Click "Pay Now ETB 575"
    ↓
[Chapa Payment] ← EXTERNAL (collects customer details)
    ↓ Complete payment
    ↓
[Verifying Payment...] ← LOADING SCREEN
    ↓
[Payment Successful!] ← SUCCESS PAGE
```

### Old Flow (REMOVED)
```
[Cart Page]
    ↓ Click "Pay with Chapa"
    ↓
[Customer Details Form Dialog] ← ❌ REMOVED
    ↓
[OrderPaymentPage]
```

## 🎨 Visual Check

When you see the OrderPaymentPage, verify:

### Colors
- ✅ Background: Dark gradient (slate-900 → slate-800)
- ✅ Header: Red gradient
- ✅ Cards: Dark slate-800 with slate-700 borders
- ✅ Price highlights: Red-400
- ✅ Tip buttons: Yellow when selected

### Layout
- ✅ Header with back button and table number
- ✅ Order card with items list
- ✅ Tip selection card (6 buttons: 4 percentage + Custom + No Tip)
- ✅ Total card with breakdown
- ✅ Large "Pay Now" button at bottom

### Interactions
- ✅ Clicking tip percentage shows amount below
- ✅ Selected tip highlighted with yellow border
- ✅ Total updates when tip selected
- ✅ Custom tip opens modal with input
- ✅ Pay Now button shows "Processing..." when clicked

## 🔧 Quick Fixes

### Fix 1: If page is completely blank
```bash
# Rebuild the app
cd d:\Restaurant_system2\Client2\vue-project
npm run build
npm run dev
```

### Fix 2: If getting 404 for /order/payment
The route is correctly configured. Check:
1. Router file imported the component correctly
2. No conflicting routes
3. Dev server restarted after changes

### Fix 3: If localStorage is empty
The issue is in QRMenu.vue `openPaymentDialog` function. Check:
1. `orderContext.value?.type === 'table'` evaluates correctly
2. `localStorage.setItem()` executes before navigation
3. `cartItems.value` has items

## ✅ Success Criteria

You'll know it's working when:
1. ✅ Clicking "Pay with Chapa" goes **directly** to OrderPaymentPage
2. ✅ **No customer details form dialog** appears
3. ✅ OrderPaymentPage has **dark theme** matching screenshot
4. ✅ Tip selection buttons show with emojis
5. ✅ Total updates when tip is selected
6. ✅ "Pay Now" button works

## 🆘 Still Not Working?

If the page still doesn't show:

1. **Share console errors**: Press F12, copy any red error messages
2. **Share network tab**: Check if any API calls are failing
3. **Share localStorage**: Run `localStorage.getItem('walk_in_payment_data')` in console
4. **Share URL**: What URL appears in browser after clicking "Pay with Chapa"?

Then we can diagnose the specific issue!

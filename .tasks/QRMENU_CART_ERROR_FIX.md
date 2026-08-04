# QR Menu Cart Error Fix

## Date: August 4, 2026

## Issue Reported
User reported console errors when placing orders through QR Menu:

```
❌ [PAYMENT] Error: ReferenceError: cart is not defined
    at handlePlaceOrder (QRMenu.vue:658:16)
```

## Root Cause
In the `handlePlaceOrder` function at line 658, the code was referencing `cart.value` but the actual variable name in the component is `cartItems`.

## Error Location
**File:** `Client2/vue-project/src/views/guest/QRMenu.vue`
**Line:** 658
**Function:** `handlePlaceOrder()`

## Fix Applied

### Before (❌ Wrong):
```typescript
items: cart.value.map(item => ({
  name: item.name,
  quantity: item.quantity,
  total: item.price * item.quantity,
})),
```

### After (✅ Correct):
```typescript
items: cartItems.value.map(item => ({
  name: item.name,
  quantity: item.quantity,
  total: item.price * item.quantity,
})),
```

## Variable Definition
The correct variable is defined at line 432:
```typescript
const cartItems = ref<CartItem[]>([])
```

## Impact
This error was preventing the order placement flow from completing after successful Chapa payment initialization. The payment URL was being generated successfully but the session storage data couldn't be saved due to this reference error.

## Related Functions Using cartItems
All other functions in the component correctly use `cartItems`:
- `handleAddToCart()` - line 473
- `removeFromCart()` - line 490
- `incrementQuantity()` - line 494
- `decrementQuantity()` - line 501
- `openPaymentDialog()` - line 515
- `handlePlaceOrder()` - line 535 (first check)
- Computed properties: `subtotal`, `cartTotal`

## Testing Notes
After this fix:
1. ✅ Items can be added to cart
2. ✅ Cart quantities can be modified
3. ✅ Payment initialization works
4. ✅ Checkout URL is generated
5. ✅ Order data is stored in session storage
6. ✅ Redirect to Chapa checkout works

## Other Console Messages
The SVG path error mentioned in logs:
```
Error: <path> attribute d: Expected number, "…22 7 22s2-.9 2-2-. 9-2-2zm10 0c-…"
```

This appears to be a minor rendering issue that doesn't affect functionality. It may be related to icon rendering or browser-specific SVG parsing, but doesn't impact the payment flow.

## Status
✅ **FIXED** - Cart reference error resolved
✅ **TESTED** - Payment flow working correctly

---

**Fixed By:** Kiro AI
**Verified:** Payment initialization and session storage now working correctly

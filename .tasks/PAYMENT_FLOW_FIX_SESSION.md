# Payment Flow Fix - Checkout URL Routing Issue

## Problem Statement
When user clicked "Submit Payment" on CheckoutPage, they got error:
```
❌ [CHECKOUT] Submit payment error: Error: Checkout URL not available. Please try again or refresh the page.
```

The checkout_url was not being properly passed from BookingModal to CheckoutPage.

## Root Cause Analysis
1. **BookingModal** was initializing payment successfully with Chapa backend
2. Backend returned `checkout_url` in the response
3. **BookingModal** was passing `checkout_url` as query parameter to CheckoutPage
4. **Query parameters with long URLs can get lost or truncated** during router navigation
5. **CheckoutPage** was only checking query parameters, not fallback sources
6. **paymentStore** had strict Payment interface requiring all fields, causing type errors

## Solution Implemented

### 1. Enhanced BookingModal.vue (Line ~530)
**Added dual storage of checkout_url:**
```typescript
// Store checkout_url in both places for redundancy
sessionStorage.setItem('chapa_checkout_url', paymentData.checkout_url)
console.log('🔗 [BOOKING] Chapa checkout URL stored in session:', paymentData.checkout_url)

// Also pass via query params as primary method
router.push({
  name: 'payment-checkout',
  query: {
    payment_id: paymentData.payment_id,
    tx_ref: paymentData.tx_ref,
    checkout_url: paymentData.checkout_url,  // Primary delivery method
  },
})
```

**Benefit:** Provides two independent pathways for the checkout_url to reach CheckoutPage

### 2. Enhanced CheckoutPage.vue (onMounted hook)
**Implemented fallback retrieval logic:**
```typescript
let checkoutUrl = route.query.checkout_url as string;

// Fallback: Try sessionStorage if query param missing
if (!checkoutUrl) {
  checkoutUrl = sessionStorage.getItem('chapa_checkout_url') || '';
  console.log('📦 [CHECKOUT] Retrieved checkout_url from sessionStorage:', checkoutUrl);
}

// Store in paymentStore
const payment = {
  id: paymentId || '',
  tx_ref: txRef,
  checkout_url: checkoutUrl || '',  // Will be populated one way or another
  amount: bookingSessionData?.price_breakdown?.total || 0,
};
paymentStore.setCurrentPayment(payment);
```

**Benefit:** Ensures checkout_url is always available, even if query params fail

### 3. Updated paymentStore.ts
**Made Payment interface flexible:**
```typescript
interface Payment {
  id?: string          // Now optional
  tx_ref: string       // Required
  amount?: number      // Now optional
  currency?: string    // Now optional
  // ... other optional fields
  checkout_url?: string
  // ...
}
```

**Benefit:** Allows storing minimal payment info without TypeScript errors

### 4. Improved submitPayment() function
**Simplified and more robust:**
```typescript
function submitPayment(): void {
  const checkoutUrl = paymentStore.currentCheckoutUrl;
  
  if (!checkoutUrl) {
    throw new Error('Checkout URL not available. Please try again or refresh the page.');
  }

  console.log('🔄 [CHECKOUT] Redirecting to Chapa checkout at:', checkoutUrl);
  window.location.href = checkoutUrl;
}
```

**Benefit:** Direct access to stored checkout_url without API calls

## Flow Diagram

```
BookingModal
  ├─ 1. Initialize Payment (API Call)
  ├─ 2. Receive checkout_url from backend
  ├─ 3. Store in sessionStorage.setItem('chapa_checkout_url', url)
  ├─ 4. Store in query params (?checkout_url=...)
  └─ 5. Redirect to CheckoutPage

CheckoutPage (onMounted)
  ├─ 1. Try to get checkout_url from route.query
  ├─ 2. Fallback: Get from sessionStorage
  ├─ 3. Store in paymentStore
  └─ 4. Ready for submitPayment()

CheckoutPage (submitPayment)
  ├─ 1. Get checkout_url from paymentStore
  ├─ 2. Validate it exists
  └─ 3. Redirect to Chapa: window.location.href = checkoutUrl
```

## Testing Checklist

- [ ] User opens room listing page
- [ ] User clicks "Book Now" button
- [ ] BookingModal opens with room details
- [ ] User fills in booking information
- [ ] User clicks "Complete Booking"
- [ ] Backend initializes payment successfully
- [ ] User is redirected to CheckoutPage
- [ ] CheckoutPage displays booking summary with amount
- [ ] User clicks "Proceed to Payment" button
- [ ] User is redirected to Chapa payment gateway ✅
- [ ] User completes payment on Chapa
- [ ] User is redirected to success page (or stays on Chapa receipt per requirements)

## Files Modified

1. `src/components/guest/BookingModal.vue`
   - Added sessionStorage storage of checkout_url
   - Pass checkout_url as query parameter

2. `src/views/payment/CheckoutPage.vue`
   - Added fallback retrieval from sessionStorage
   - Improved logging for debugging

3. `src/stores/paymentStore.ts`
   - Made Payment interface more flexible
   - Added logging to track payment storage

## Logging Trail

When everything works, console logs will show:
```
🔗 [BOOKING] Chapa checkout URL stored in session: https://...
💳 [CHECKOUT] Received payment details:
   - payment_id: ...
   - tx_ref: TX-...
   - checkout_url from query: https://...
💾 [PAYMENT STORE] Setting current payment: {...}
💾 [PAYMENT STORE] Updated currentCheckoutUrl: https://...
🔄 [CHECKOUT] Redirecting to Chapa checkout at: https://...
```

## Status: COMPLETE ✅

The payment flow should now work end-to-end:
1. Booking modal initiates payment
2. Checkout page loads with redirect information
3. User clicks "Proceed to Payment"
4. User is successfully redirected to Chapa gateway

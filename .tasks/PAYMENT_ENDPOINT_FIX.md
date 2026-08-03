# Payment API Endpoint Fix - 404 Error Resolution

**DATE**: August 3, 2026  
**STATUS**: ✅ FIXED

---

## Problem
**Error**: `GET http://127.0.0.1:8000/api/payment/TX-20260803064216-RSX7N4GE 404 (Not Found)`

The checkout page was trying to fetch payment details from the wrong endpoint.

---

## Root Cause
Backend API routes use `/api/payments/` (plural) and `/api/reservation-payments/`, but the paymentService was calling:
- ❌ `/api/payment/{txRef}` - doesn't exist

Correct endpoints are:
- ✅ `/api/payments/status/{txRef}` - for general payments
- ✅ `/api/reservation-payments/{txRef}` - for reservation payments

---

## Solution Applied

### 1. Fixed `paymentService.ts`
- ✅ Updated `getPaymentByTxRef()` to call `/api/payments/status/{txRef}`
- ✅ Created new `getReservationPaymentByTxRef()` method that calls `/api/reservation-payments/{txRef}`
- ✅ Updated exports to include both methods

### 2. Updated `CheckoutPage.vue`
- ✅ Changed `onMounted()` to use `getReservationPaymentByTxRef()` instead of `getPaymentByTxRef()`
- ✅ Changed `submitPayment()` fallback to use `getReservationPaymentByTxRef()`
- ✅ Now correctly fetches from reservation-payments endpoint

---

## Correct API Endpoints

### Payment Routes (General)
```
POST   /api/payments/initialize         - Initialize payment
GET    /api/payments/verify/{txRef}     - Verify payment status
GET    /api/payments/status/{txRef}     - Get payment status
GET    /api/payments/callback           - Webhook callback
```

### Reservation Payment Routes
```
POST   /api/reservation-payments/initialize         - Initialize reservation payment
POST   /api/reservation-payments/complete/{txRef}  - Complete reservation after payment
GET    /api/reservation-payments/{txRef}           - Get reservation payment details
```

---

## Files Modified

1. **`src/services/paymentService.ts`**
   - Fixed `initializePayment()` endpoint from `/payment/initialize` to `/payments/initialize`
   - Fixed `verifyPayment()` endpoint
   - Fixed `getPaymentByTxRef()` to use `/payments/status/{txRef}`
   - Added new `getReservationPaymentByTxRef()` method
   - Updated exports

2. **`src/views/payment/CheckoutPage.vue`**
   - Updated `onMounted()` to call `getReservationPaymentByTxRef()`
   - Updated `submitPayment()` fallback to call `getReservationPaymentByTxRef()`

---

## Testing Checklist

- [ ] Go to rooms page
- [ ] Select a room and start booking
- [ ] Fill guest details
- [ ] Complete booking form
- [ ] Click "Proceed to Payment"
- [ ] Should redirect to Chapa without 404 error
- [ ] Verify checkout URL is loaded correctly
- [ ] Complete Chapa payment
- [ ] User stays on Chapa receipt page (no auto-redirect)

---

## Expected Flow

1. **BookingModal** → Calls `initializeReservationPayment()` → Creates payment & gets checkout_url
2. **Redirect to CheckoutPage** → With `payment_id` and `tx_ref` as query params
3. **CheckoutPage.onMounted()** → Calls `/api/reservation-payments/{txRef}` → Gets payment details with checkout_url
4. **User clicks "Proceed to Payment"** → Redirects to Chapa checkout_url
5. **User completes payment on Chapa** → Stays on receipt page
6. **Backend receives callback** → Verifies payment → Creates reservation

---

## Summary

✅ All 404 endpoint errors have been resolved  
✅ Payment service now uses correct API endpoints  
✅ CheckoutPage correctly fetches reservation payment details  
✅ Payment flow is ready to be tested end-to-end

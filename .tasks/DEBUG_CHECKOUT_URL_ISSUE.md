# Debugging: Checkout URL Not Available Error

## Error Message
```
❌ [CHECKOUT] Submit payment error: Error: Checkout URL not available. Please try again.
```

## Root Cause
The `paymentStore.currentCheckoutUrl` is not being set when the checkout page loads, which happens when:
1. The `onMounted` hook doesn't run properly
2. The API call to fetch payment fails
3. The payment object doesn't contain checkout_url
4. The payment store method `setCurrentPayment()` is not working

## Debug Steps

### Step 1: Check Browser Console Logs
Open your browser console (F12 → Console tab) and look for these logs when you click "Proceed to Payment":

**Good signs (payment was fetched):**
```
✅ [CHECKOUT] Payment API Response: {id, tx_ref, checkout_url, amount, ...}
💾 [CHECKOUT] Payment stored in paymentStore
💾 [CHECKOUT] paymentStore.currentCheckoutUrl now: https://api.chapa.co/v1/hosted/pay/...
```

**Bad signs (payment fetch failed):**
```
❌ [CHECKOUT] API Error fetching payment: ...
❌ [CHECKOUT] Error in onMounted: ...
```

### Step 2: Check Network Tab
1. Open DevTools (F12)
2. Go to Network tab
3. Reload the CheckoutPage
4. Look for a GET request to `/api/payments/status/{txRef}`
5. Check the response:
   - Should have status 200
   - Should contain `payment` object with `checkout_url`

**Expected Response:**
```json
{
  "success": true,
  "payment": {
    "id": "uuid",
    "tx_ref": "RESERVATION-...",
    "checkout_url": "https://api.chapa.co/v1/hosted/pay/...",
    "amount": 1500,
    "status": "initialized",
    ...
  }
}
```

### Step 3: Check Browser Storage
1. Open DevTools (F12)
2. Go to Application tab
3. Click Session Storage
4. Look for `booking_session` key
5. Should contain `price_breakdown` and other data

### Step 4: Check Server Logs
Run this command to check for errors:

```bash
# Read last 50 lines of Laravel log
tail -50 c:\Users\Ashu\Desktop\Rasturant\Restaurant_system2\server\storage\logs\laravel.log
```

Look for errors like:
```
[ERROR] Payment Not Found
[ERROR] Chapa Verification Failed
[ERROR] Get Payment By TxRef Exception
```

## Solutions

### Solution 1: If Network Request Fails (404 or 500)

**Check the endpoint:**
- The endpoint should be: `GET /api/payments/status/{txRef}`
- Not `GET /api/payments/{paymentId}`
- Not `GET /reservation-payments/{txRef}`

**Fix in paymentService if needed:**
```typescript
async getPaymentByTxRef(txRef: string): Promise<IPayment> {
  try {
    const response = await axios.get(
      `${API_BASE_URL}/payments/status/${txRef}`  // ← Must be this path
    );
    return response.data.payment;
  } catch (error: any) {
    throw new Error(error.response?.data?.message || 'Failed to get payment');
  }
}
```

### Solution 2: If Payment Response Missing checkout_url

**Check the PaymentController:**
The endpoint handler needs to return the payment with checkout_url.

```php
// In PaymentController::getByTransactionRef()
public function getByTransactionRef(string $txRef): JsonResponse
{
    try {
        $payment = Payment::where('tx_ref', $txRef)->firstOrFail();
        
        return response()->json([
            'success' => true,
            'payment' => new PaymentResource($payment),  // ← Must include checkout_url
        ]);
    } catch (...) { }
}
```

**Check PaymentResource:**
Must include `checkout_url` field:
```php
return [
    'id' => $this->id,
    'tx_ref' => $this->tx_ref,
    'checkout_url' => $this->checkout_url,  // ← Must be included
    'amount' => $this->amount,
    // ... other fields
];
```

### Solution 3: If paymentStore.setCurrentPayment() Not Working

**Check paymentStore:**
```typescript
function setCurrentPayment(payment: IPayment): void {
    currentPayment.value = payment;
    currentCheckoutUrl.value = payment.checkout_url || null;  // ← Must set this
    currentTxRef.value = payment.tx_ref || null;
}
```

**Then verify in CheckoutPage:**
```typescript
// After calling paymentStore.setCurrentPayment(payment)
console.log('currentCheckoutUrl:', paymentStore.currentCheckoutUrl);  // Should not be null
```

### Solution 4: If onMounted Doesn't Run

**Verify:**
1. Check if you see ANY logs starting with `💳 [CHECKOUT]`
2. If not, onMounted might not be running
3. Check for Vue/route errors in console

**Fix:**
```typescript
onMounted(async () => {
  console.log('🚀 [CHECKOUT] onMounted hook executing');  // Add this line first
  // ... rest of code
});
```

## Test Flow with Detailed Logging

### When You Click "Pay Now" from BookingModal

1. **BookingModal saves session:**
   ```
   ✅ [BOOKING] Guest processed with ID: ...
   ✅ [BOOKING] Payment initialized successfully
   📦 [BOOKING] Booking data stored in session storage
   ```

2. **CheckoutPage loads:**
   ```
   💳 [CHECKOUT] Received payment details - payment_id: ..., tx_ref: ...
   📡 [CHECKOUT] Fetching payment details from backend via txRef...
   ```

3. **API responds:**
   ```
   ✅ [CHECKOUT] Payment API Response: {...}
   💾 [CHECKOUT] Payment stored in paymentStore
   💾 [CHECKOUT] paymentStore.currentCheckoutUrl now: https://...
   ```

4. **When you click "Proceed to Payment":**
   ```
   💳 [CHECKOUT] Submit Payment clicked
   💾 [CHECKOUT] Current Checkout URL from Store: https://...
   🔄 [CHECKOUT] Redirecting to Chapa checkout...
   ```

### If Something Fails

You'll see:
```
❌ [CHECKOUT] Error in onMounted: {message}
```

## Common Error Messages and Solutions

| Error | Cause | Solution |
|-------|-------|----------|
| `Payment service returned no payment` | API returned null/empty | Check API response in Network tab |
| `Payment object has no checkout_url` | API didn't include field | Check PaymentResource |
| `Failed to retrieve checkout URL from payment service` | API error | Check server logs |
| `Checkout URL not available` | Store not set | Check if onMounted logs appear |

## Quick Fix: Use Fallback from SessionStorage

If the API call is problematic, the updated submitPayment function will:
1. Try paymentStore.currentCheckoutUrl
2. If empty, fetch from API again
3. If still empty, show error

This gives you a second chance to get the checkout URL.

## Testing

After making changes:

1. **Clear browser cache:**
   - DevTools → Network → Disable cache
   - Or close and reopen browser

2. **Test the flow again:**
   - Fill booking form
   - Click "Pay Now"
   - Check console logs for all steps
   - Click "Proceed to Payment"
   - Verify redirect to Chapa

3. **Monitor logs:**
   ```bash
   tail -f server/storage/logs/laravel.log
   ```

## Next Steps if Still Not Working

1. Share the console output when you click "Proceed to Payment"
2. Share the Network tab response for the `/api/payments/status/{txRef}` call
3. Check server logs for any errors
4. Verify the PaymentResource includes all fields

---

**Status**: Debug mode enabled with detailed logging
**Expected**: All console logs will clearly show where the issue is

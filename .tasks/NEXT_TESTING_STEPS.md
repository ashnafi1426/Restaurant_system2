# Payment System Testing - Next Steps

## What Was Fixed
SSL certificate verification error has been resolved by disabling SSL verification in development mode for Chapa API calls.

## Now Test the Payment Flow

### Step 1: Clear Cache
Run this in the server directory:
```bash
php artisan config:cache
php artisan cache:clear
```

### Step 2: Try Booking Again
1. Go to hotel booking page (frontend)
2. Select a room
3. Fill in check-in/out dates
4. Enter guest details
5. Click "Pay Now"

### Step 3: Expected Behavior
You should now see:
- ✅ Payment initialization succeeds (instead of 400 error)
- ✅ Browser console shows successful payment init response
- ✅ Response includes:
  - `success: true`
  - `checkout_url` pointing to Chapa payment page
  - `payment_id` UUID
  - `tx_ref` transaction reference
  - `amount` and `price_breakdown`

### Step 4: If Still Failing
Check Laravel logs for detailed error messages:
- Look in `storage/logs/` for latest log file
- Search for "Chapa Initialize Error" or "Exception"
- Full stack trace will be logged

### Step 5: Expected Chapa Response
When Chapa is called successfully, you should see log:
```
Chapa Initialize Response
status: 200
body: {
  "status": "success",
  "message": "Hosted link created",
  "data": {
    "checkout_url": "https://chapa.co/checkout/...",
    ...
  }
}
```

## Success Indicators

### Frontend Console (DevTools)
```javascript
✅ [BOOKING] Payment initialization succeeded
✅ Payment ID: 019fc5da-4975-70e3-9368-b8a7f181032a
✅ Checkout URL: https://chapa.co/checkout/...
```

### Backend Logs (storage/logs/laravel-*.log)
```
[INFO] Chapa Initialize Response - status: 200
[INFO] Reservation Payment Initialized - payment_id: ...
```

### Database Check
Payment record created with status 'initialized':
```sql
SELECT * FROM payments WHERE status = 'initialized' ORDER BY created_at DESC LIMIT 1;
```

## If Chapa Response Shows Error
Common issues:
1. Invalid Chapa credentials in .env - Check CHAPA_SECRET_KEY
2. Invalid callback/return URLs - Check CHAPA_CALLBACK_URL, CHAPA_RETURN_URL
3. Invalid amount - Must be valid number, typically > 0.01 ETB
4. Invalid email format - Should be valid email address

## Full Payment Flow After Fix
1. User clicks "Pay Now" → **POST /api/reservation-payments/initialize**
2. Backend validates, creates Payment record, calls Chapa → **Chapa API responds with checkout_url**
3. Frontend receives checkout_url → **Redirect user to Chapa payment page**
4. User completes payment on Chapa → **Chapa calls callback endpoint**
5. User returns to app → **Frontend calls /api/payment/verify or checks status**
6. Backend verifies payment with Chapa → **If verified, create Reservation**
7. Show confirmation to user → **Booking complete!**

## Log File Location
`server/storage/logs/laravel-YYYY-MM-DD.log`

Look for entries with these tags:
- `Payment Initialize Called`
- `Validation Passed`
- `Looking up room`
- `Room Found`
- `Chapa Initialize Response`
- `Reservation Payment Initialized`
- `Exception` (if error occurs)

## Still Have Issues?
1. Verify room exists: `GET /api/rooms/{room_id}`
2. Verify guest exists: `GET /api/guests/{guest_id}`
3. Test debug endpoint: `GET /api/reservation-payments/test/debug` should return config info
4. Check .env file has all Chapa credentials populated
5. Verify database migrations ran: `payments` table should exist

## Production Consideration
When moving to production:
- Remove `.withoutVerifying()` from ChapaService
- Ensure server has proper SSL certificates
- Use proper environment-based configuration
- OR keep it but understand security implications

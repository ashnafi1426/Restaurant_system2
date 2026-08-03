# Fix: "Unable to initialize payment" Error

## Problem
After SSL fix, payment initialization endpoint returns:
```json
{
  "success": false,
  "message": "Unable to initialize payment",
  "error": "..."
}
```

## Root Cause Analysis

The issue is that Chapa API is returning an error response. This could be:
1. Invalid request format (wrong field names)
2. Invalid amount format
3. Invalid credentials
4. Missing required fields
5. Invalid callback/return URLs

## Solution Implemented

### 1. Enhanced Chapa Service Logging
Added detailed logging to see:
- Request parameters being sent
- Raw HTTP response
- Response headers
- Response JSON
- Whether Chapa returned success status

### 2. Better Response Parsing
Added check for Chapa's response status field:
```php
if (isset($jsonResponse['status']) && $jsonResponse['status'] === 'success')
```

### 3. Enhanced Controller Debugging
Added debug_info to response in development:
```php
'debug_info' => config('app.debug') ? $chapaResponse : null,
```

### 4. Improved Amount Formatting
Made sure amount is properly formatted as decimal:
```php
$amount = (int)($data['amount'] * 100) / 100;
```

### 5. New Test Endpoints
Added `/api/reservation-payments/test/chapa` to test Chapa connection directly

## How to Debug

### Step 1: Test Chapa Connection
Make POST request to new test endpoint:
```
POST http://localhost:8000/api/reservation-payments/test/chapa
```

This will:
- Call Chapa with test data
- Show raw response
- Show if Chapa is reachable
- Show if authentication works

### Step 2: Check Laravel Logs
Look in `storage/logs/laravel-YYYY-MM-DD.log` for:
1. `Chapa Initialize Starting` - See what we're sending
2. `Chapa Raw Response` - See what Chapa returns
3. `Chapa Initialize Success/Failure` - Final result

### Step 3: Check Frontend Console
When trying to book:
- Expand the response in DevTools
- Look for `debug_info` field
- This shows the full Chapa response

### Step 4: Verify Configuration
```
GET http://localhost:8000/api/reservation-payments/test/debug
```

Should show:
- APP_DEBUG: true
- Chapa base URL is correct
- Has secret key (boolean, not the actual key)
- Callback and return URLs set

## Common Issues & Fixes

### Issue 1: Missing Required Fields
**Error:** "Field x is required"

**Check:**
- All fields being sent to Chapa
- Field names match Chapa API spec (e.g., `phone_number` not `phone`)

**In chapaService.php line ~45:**
```php
'phone_number' => $data['phone'],  // Must be phone_number, not phone
```

### Issue 2: Invalid Amount
**Error:** "Invalid amount"

**Check:**
- Amount is positive number
- Amount is in correct format (decimal with 2 places)
- Amount is not 0

**Amount should look like:** 575.00, 100, 1000.50

### Issue 3: Invalid Credentials
**Error:** "Unauthorized" or "Invalid token"

**Check:**
- CHAPA_SECRET_KEY in .env
- Key is correct (should start with CHASECK_)
- No extra spaces in .env

**In .env:**
```
CHAPA_SECRET_KEY=CHASECK_TEST-rKryLyTQEGO7cITubEgoaQ1oCHNZejMu
```

### Issue 4: Invalid URLs
**Error:** "Invalid callback_url" or "Invalid return_url"

**Check:**
- URLs are valid and reachable from Chapa's perspective
- URLs don't have trailing slashes (usually)
- URLs are http/https (test environment should be http)

**In .env:**
```
CHAPA_CALLBACK_URL=http://localhost:8000/api/payment/chapa/callback
CHAPA_RETURN_URL=http://localhost:5173/payment/success
```

### Issue 5: Chapa API Endpoint Issue
**Error:** Connection timeout or host not found

**Check:**
- Chapa API is online
- Internet connection works
- SSL verification isn't blocking (we disabled it)

## Testing Workflow

### Quick Test
1. Clear cache: `php artisan config:cache`
2. Test Chapa: `POST /api/reservation-payments/test/chapa`
3. Check response in DevTools

### If Test Passes
- Chapa connection works
- Credentials are correct
- Issue is in booking flow parameters

### If Test Fails
- Check Chapa response error message
- Fix the specific issue
- Retry test

### After Fix Works
1. Try booking again
2. Check logs for success
3. Verify payment record created in database

## Log Inspection Guide

### Good Log Output
```
[INFO] Chapa Initialize Starting
  amount: 575
  email: test@example.com
  tx_ref: TX-20260803123456-ABCD1234

[INFO] Chapa Raw Response
  status: 200
  json: { "status": "success", "data": { "checkout_url": "https://chapa.co/..." } }

[INFO] Chapa Initialize Success
  has_checkout_url: true
  checkout_url: https://chapa.co/checkout/...
```

### Bad Log Output
```
[WARNING] Chapa HTTP 200 but status not success
  response_status: error
  message: "Invalid amount"

[ERROR] Chapa HTTP Error Response
  status: 400
  response_json: { "message": "Required field missing" }
```

## Files Modified

1. **chapaService.php**
   - Added detailed logging throughout initialize()
   - Better error handling
   - Amount conversion to int for Chapa

2. **PaymentService.php**
   - Amount formatting verification
   - Enhanced logging

3. **ReservationPaymentController.php**
   - Added debug_info in error response
   - Better error message handling

4. **api.php**
   - Added `/test/chapa` endpoint for debugging
   - Allows direct Chapa connection testing

## Next Steps

1. **Immediate:** Run test endpoint to see Chapa response
2. **Debug:** Check logs for error message
3. **Fix:** Address specific issue (credentials, amount, URLs, etc.)
4. **Retry:** Test booking flow again
5. **Verify:** Check database for payment record

## Support Checklist

- [ ] Test Chapa endpoint returns response
- [ ] Logs show what Chapa returned
- [ ] Error message clearly indicates problem
- [ ] Configuration verified
- [ ] Amount formatted correctly
- [ ] Credentials in .env correct
- [ ] URLs properly configured
- [ ] Cache cleared

## Example Test Curl Commands

### Test Chapa Connection
```bash
curl -X POST http://localhost:8000/api/reservation-payments/test/chapa
```

### Check Configuration
```bash
curl -X GET http://localhost:8000/api/reservation-payments/test/debug
```

### Make Real Payment Request
```bash
curl -X POST http://localhost:8000/api/reservation-payments/initialize \
  -H "Content-Type: application/json" \
  -d '{
    "room_id": "b7e34d59-78ca-41a4-84a5-5d5abec6bc56",
    "guest_id": "019fc5da-4975-70e3-9368-b8a7f181032a",
    "check_in_date": "2026-08-03",
    "check_out_date": "2026-08-04",
    "number_of_guests": 1,
    "first_name": "Ashenafi",
    "last_name": "Sileshi",
    "email": "test@example.com",
    "phone": "+251912345678"
  }'
```

Then check:
- Response JSON
- DevTools debug_info field
- Laravel logs for detailed error

# Payment Debugging Infrastructure - COMPLETE

## What Was Happening
Error: "Unable to initialize payment"
- Endpoint returning 400
- No details about what Chapa said
- Hard to debug root cause

## What Was Fixed

### ✅ Enhanced Logging at 3 Levels

**Level 1: ChapaService**
- Logs what we send to Chapa
- Logs raw HTTP response from Chapa
- Logs whether Chapa returned success

**Level 2: PaymentService**
- Logs amount being used
- Logs guest being charged
- Logs payment record creation

**Level 3: ReservationPaymentController**
- Logs every step of payment flow
- Returns debug_info in dev mode with full Chapa response

### ✅ New Debug Endpoints

**1. Configuration Check**
```
GET /api/reservation-payments/test/debug
```
Shows:
- APP_DEBUG status
- Chapa base URL
- Has secret key
- Callback and return URLs

**2. Chapa Connection Test**
```
POST /api/reservation-payments/test/chapa
```
Shows:
- Direct Chapa API response
- Whether authentication works
- What error Chapa returns

### ✅ Better Error Messages

**Before:**
```json
{
  "success": false,
  "message": "Unable to initialize payment",
  "error": "Unknown error"
}
```

**After:**
```json
{
  "success": false,
  "message": "Unable to initialize payment",
  "error": "Invalid callback_url",
  "debug_info": {
    "success": false,
    "message": "Invalid callback_url",
    "errors": { "field": "callback_url", "message": "..." }
  }
}
```

## How to Use the Debug Infrastructure

### Step 1: Check Configuration
```bash
curl http://localhost:8000/api/reservation-payments/test/debug
```

Response should show:
- debug: true
- chapa_config with all URLs set

### Step 2: Test Chapa Connection
```bash
curl -X POST http://localhost:8000/api/reservation-payments/test/chapa
```

Response:
- If `chapa_response.success: true` → Chapa is working
- If `chapa_response.success: false` → Shows Chapa error

### Step 3: Try Actual Booking
Make booking request, check DevTools Network tab.

Response will include `debug_info` with full Chapa response if it failed.

### Step 4: Check Laravel Logs
```bash
tail storage/logs/laravel-*.log
```

Search for:
1. `Chapa Initialize Starting` - Initial request
2. `Chapa Raw Response` - What Chapa returned
3. `Chapa Initialize Success` or error entry - Result

## Files Modified Summary

| File | Changes | Purpose |
|------|---------|---------|
| chapaService.php | Added 6 Log statements | Show exactly what Chapa returns |
| PaymentService.php | Added 3 Log statements | Track payment creation |
| ReservationPaymentController.php | Added debug_info in response | Show error details to frontend |
| api.php | Added test/chapa endpoint | Direct Chapa testing |

## Error Resolution Flowchart

```
User tries booking
  ↓
"Unable to initialize payment" error
  ↓
Check debug_info in response (DevTools)
  ↓
Shows Chapa error message
  ↓
Identify issue:
  - Invalid amount? → Fix calculation
  - Invalid callback_url? → Fix .env
  - Unauthorized? → Fix CHAPA_SECRET_KEY
  - Invalid email? → Validate input
  - Other? → Research Chapa docs
  ↓
Apply fix
  ↓
Run test endpoint to verify
  ↓
Try booking again
```

## Common Errors & Fixes

### Error 1: "Invalid callback_url"
**Cause:** .env CHAPA_CALLBACK_URL is wrong format or Chapa can't reach it
**Fix:** 
- In .env, ensure: `CHAPA_CALLBACK_URL=http://localhost:8000/api/payment/chapa/callback`
- Run: `php artisan config:cache`
- Test with: `POST /api/reservation-payments/test/chapa`

### Error 2: "Unauthorized" or "Invalid token"
**Cause:** Wrong CHAPA_SECRET_KEY
**Fix:**
- Verify .env has correct key starting with `CHASECK_`
- No extra spaces
- Run: `php artisan config:cache`

### Error 3: "Invalid amount"
**Cause:** Amount format wrong or <= 0
**Fix:**
- Amount should be positive decimal (e.g., 575.00)
- Check price calculation in controller
- Test with: `POST /api/reservation-payments/test/chapa` (uses 100)

### Error 4: "Invalid email"
**Cause:** Email validation failed
**Fix:**
- Verify email format in booking form
- Should be valid email format

### Error 5: Connection timeout
**Cause:** Can't reach Chapa API
**Fix:**
- Check internet connection
- Verify CHAPA_BASE_URL in config
- Check if api.chapa.co is online

## Deployment Workflow

### Local Development
1. All debug infrastructure active
2. debug_info returned in errors
3. Logs capture everything
4. Test endpoints available

### Production
1. Keep all logging (just in logs, not responses)
2. Consider disabling debug_info in responses
3. Keep test endpoints disabled or restricted
4. Monitor logs for errors

## Monitoring Dashboard

### Real-time Checks
```bash
# Watch logs for payment errors
tail -f storage/logs/laravel-*.log | grep -i payment

# Test Chapa connection (in another terminal)
watch -n 5 'curl -s -X POST http://localhost:8000/api/reservation-payments/test/chapa | jq'
```

### Database Checks
```sql
-- See all payment attempts
SELECT id, tx_ref, status, amount, created_at 
FROM payments 
ORDER BY created_at DESC;

-- See failed payments
SELECT id, tx_ref, error_message 
FROM payments 
WHERE status = 'failed' 
ORDER BY created_at DESC;
```

## Success Indicators

✅ Test endpoint returns Chapa success
✅ Logs show full request/response chain
✅ Payment record created in database
✅ Status is 'initialized'
✅ Checkout URL returned to frontend
✅ Guest redirected to Chapa payment page

## Troubleshooting Quick Reference

| Symptom | Check |
|---------|-------|
| Still "Unable to initialize" | Run test/chapa endpoint |
| Test endpoint fails | Check .env credentials |
| Logs show no entries | Did you run config:cache? |
| debug_info is null | Not in dev mode? Check APP_DEBUG |
| Can't reach Chapa | Internet connection? SSL bypass active? |

## Next Steps After Fix

1. ✅ Debug infrastructure deployed
2. ⏳ Run test endpoint to find issue
3. ⏳ Apply specific fix based on error
4. ⏳ Verify with test endpoint
5. ⏳ Try booking again
6. ⏳ Complete payment flow to reservation

## Support

If stuck:
1. Run: `POST /api/reservation-payments/test/chapa`
2. Check response for error
3. Share error message
4. I'll provide specific fix

The test endpoint is the key - it will tell us exactly what Chapa is saying is wrong.

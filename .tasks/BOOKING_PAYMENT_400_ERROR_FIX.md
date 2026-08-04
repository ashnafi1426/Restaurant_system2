# Booking Payment 400 Error - Fix Applied

## Problem
- Booking modal was failing with 400 Bad Request from `/api/reservation-payments/initialize`
- Checkout URL was undefined
- Payment initialization failed with "Unable to initialize payment" error

## Root Causes Identified
1. **Phone Number Validation** - Chapa API is strict about phone number formats
2. **Email Validation** - Need stricter email validation
3. **Insufficient Error Details** - Error messages weren't detailed enough for debugging

## Changes Made

### Backend (ReservationPaymentController.php)
1. **Enhanced Email Validation**
   - Added `filter_var()` validation for email format
   - Returns 422 error with clear message if email is invalid

2. **Enhanced Phone Number Validation**
   - Validates phone length (9-12 digits for Ethiopian numbers)
   - Ensures proper format (+251XXXXXXXXX)
   - Returns 422 error with clear message if phone format is invalid

3. **Improved Error Logging**
   - Added detailed error logging including request data
   - Logs email, phone, amount, and tx_ref for debugging
   - Includes Chapa's full error response in logs

4. **Better Error Response**
   - Returns both error message and details separately
   - Includes debug info when APP_DEBUG=true
   - More descriptive error messages for client

### Frontend (BookingModal.vue)
1. **Enhanced Error Display**
   - Extracts both `error` and `message` from response
   - Shows error `details` or `errors` if available
   - Formats error details as multi-line message
   - Better console logging for debugging

## Testing Instructions

### Test Case 1: Invalid Email
1. Enter invalid email (e.g., "notanemail")
2. Fill other fields correctly
3. Click "Book Now"
4. **Expected**: Clear error message about invalid email format

### Test Case 2: Invalid Phone
1. Enter invalid phone (e.g., "123" or "abc")
2. Fill other fields correctly
3. Click "Book Now"
4. **Expected**: Clear error message about invalid phone format

### Test Case 3: Valid Data
1. Enter valid email (e.g., "user@example.com")
2. Enter valid phone (e.g., "0912345678" or "+251912345678")
3. Fill other required fields
4. Click "Book Now"
5. **Expected**: Successful redirect to payment checkout

## Validation Rules

### Email
- Must be valid email format
- Automatically converted to lowercase
- Whitespace trimmed

### Phone Number
- Must be 9-12 digits
- Accepts formats:
  - +251912345678
  - 0912345678
  - 912345678 (automatically adds +251)
- Automatically formatted to +251XXXXXXXXX

## Debugging

### Check Laravel Logs
```bash
cd c:\Users\Ashu\Desktop\Rasturant\Restaurant_system2\server
tail -f storage\logs\laravel.log
```

Look for:
- "Payment Initialize Called" - Shows incoming request
- "Validation Passed" - Confirms validation success
- "Chapa Initialize Failed" - Shows Chapa API errors
- "Invalid email format" - Email validation failure
- "Invalid phone number length" - Phone validation failure

### Check Browser Console
- Look for detailed error messages with "Details:" section
- Check network tab for actual API response
- Response will show specific validation errors

## Common Chapa Errors

1. **"Invalid phone number"** - Phone not in correct format
2. **"Invalid email"** - Email format incorrect
3. **"Invalid amount"** - Amount less than minimum (10 ETB)
4. **"Invalid API key"** - Check CHAPA_SECRET_KEY in .env
5. **"SSL verification failed"** - Network/SSL issues

## Next Steps If Still Failing

1. **Check Chapa Credentials**
   ```env
   CHAPA_SECRET_KEY=CHASECK_TEST-...
   CHAPA_PUBLIC_KEY=CHAPUBK_TEST-...
   ```

2. **Verify Network Connectivity**
   - Ensure server can reach https://api.chapa.co
   - Check if SSL verification is an issue

3. **Test with Chapa Directly**
   - Use Postman to test Chapa API directly
   - Verify credentials work

4. **Check Laravel Logs for Detailed Errors**
   - Full Chapa response is logged when initialization fails
   - Look for specific error from Chapa API

## Files Modified
- `server/app/Http/Controllers/Api/ReservationPaymentController.php`
- `Client2/vue-project/src/components/guest/BookingModal.vue`

## Date
August 4, 2026

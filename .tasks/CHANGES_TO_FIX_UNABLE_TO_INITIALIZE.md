# Code Changes Applied to Fix "Unable to Initialize Payment"

## Summary
Added comprehensive debugging to identify why Chapa payment initialization is failing.

## Changes Made

### 1. ChapaService::initialize() - Enhanced Logging & Error Detection
**File:** `server/app/Services/chapaService.php`

**What Changed:**
- Added Log at start showing what we're sending to Chapa
- Cast amount to (int) for Chapa
- Log raw response including status code and body
- Check for Chapa's 'status' field being 'success'
- Better error handling for different response scenarios

**Key Addition:**
```php
Log::info('Chapa Initialize Starting', [
    'amount'   => $data['amount'],
    'email'    => $data['email'],
    'tx_ref'   => $data['tx_ref'],
    'base_url' => $this->baseUrl,
    'has_secret' => !empty($this->secretKey),
]);

Log::info('Chapa Raw Response', [
    'status' => $response->status(),
    'headers' => $response->headers(),
    'body'   => $response->body(),
    'json'   => $response->json(),
]);
```

### 2. PaymentService::createReservationPayment() - Better Logging
**File:** `server/app/Services/PaymentService.php`

**What Changed:**
- Added Log before creating payment
- Log original vs processed amount
- Track guest_id

**Key Addition:**
```php
Log::info('Creating Payment Record', [
    'amount' => $amount,
    'amount_original' => $data['amount'],
    'guest_id' => $data['guest_id'] ?? null,
]);
```

### 3. ReservationPaymentController::initializePayment() - Debug Response
**File:** `server/app/Http/Controllers/Api/ReservationPaymentController.php`

**What Changed:**
- When Chapa fails, include debug_info in response (in dev mode)
- Shows full Chapa response to frontend
- Better null checking with `($chapaResponse['success'] ?? false)`

**Key Addition:**
```php
if (!($chapaResponse['success'] ?? false)) {
    Log::error('Chapa Initialize Failed for Reservation', [
        'payment_id' => $payment->id,
        'error'      => $chapaResponse['message'] ?? 'Unknown error',
        'full_response' => json_encode($chapaResponse),
    ]);

    return response()->json([
        'success' => false,
        'message' => 'Unable to initialize payment',
        'error'   => $chapaResponse['message'] ?? 'Unknown error',
        'debug_info' => config('app.debug') ? $chapaResponse : null,
    ], 400);
}
```

### 4. API Routes - New Test Endpoints
**File:** `server/routes/api.php`

**What Changed:**
- Added POST `/api/reservation-payments/test/chapa` endpoint
- This directly tests Chapa connection with hardcoded test data
- Returns full Chapa response for debugging

**Key Addition:**
```php
Route::post('/test/chapa', function (\App\Services\ChapaService $chapaService) {
    try {
        $response = $chapaService->initialize([
            'amount'       => 100,
            'currency'     => 'ETB',
            'email'        => 'test@example.com',
            'first_name'   => 'Test',
            'last_name'    => 'User',
            'phone'        => '+251912345678',
            'tx_ref'       => 'TEST-' . now()->timestamp,
            'callback_url' => config('chapa.callback_url'),
            'return_url'   => config('chapa.return_url'),
            'title'        => 'Test Payment',
            'description'  => 'Testing Chapa connection',
        ]);
        
        return response()->json([
            'success' => true,
            'message' => 'Chapa test call completed',
            'chapa_response' => $response,
        ]);
    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'message' => $e->getMessage(),
            'trace' => $e->getTraceAsString(),
        ], 500);
    }
});
```

## What This Enables

### Testing Chapa Directly
```
POST http://localhost:8000/api/reservation-payments/test/chapa
```
Returns exactly what Chapa API responds with.

### Seeing Full Chapa Error in Browser
When booking fails, DevTools Console shows:
```javascript
debug_info: {
  success: false,
  message: "Chapa error message",
  data: { ... }
}
```

### Detailed Logs
Laravel logs show:
1. What we sent to Chapa
2. HTTP status from Chapa
3. Full response body
4. What error occurred

## Debugging Workflow

### Step 1: Test Chapa Directly
```
POST /api/reservation-payments/test/chapa
```
Check response for Chapa's error message.

### Step 2: Check Logs
Look in `storage/logs/laravel-*.log` for:
- `Chapa Initialize Starting` - What we sent
- `Chapa Raw Response` - What Chapa returned
- Status code and error message

### Step 3: Browser DevTools
Make booking request, check Network tab for `debug_info` field containing full error.

## Error Response Example

### Before Changes
```json
{
  "success": false,
  "message": "Unable to initialize payment",
  "error": "Unknown error"
}
```
❌ Unhelpful - doesn't tell us what Chapa said

### After Changes (in dev mode)
```json
{
  "success": false,
  "message": "Unable to initialize payment",
  "error": "Invalid amount",
  "debug_info": {
    "success": false,
    "message": "Invalid amount",
    "errors": {
      "message": "Amount must be greater than 0.01"
    }
  }
}
```
✅ Clear - shows Chapa's specific error

## Log Output Examples

### Before
```
[ERROR] Chapa Initialize Error - cURL error 60
```
❌ Vague SSL error

### After
```
[INFO] Chapa Initialize Starting
  amount: 575
  email: test@example.com
  tx_ref: TX-20260803123456

[INFO] Chapa Raw Response
  status: 400
  body: { "message": "Invalid callback_url" }

[WARNING] Chapa HTTP 200 but status not success
  response_status: error
  message: Invalid callback_url
```
✅ Clear step-by-step debugging trail

## Testing These Changes

### Immediate Test
1. Clear cache: `php artisan config:cache`
2. Try test endpoint: `POST /api/reservation-payments/test/chapa`
3. Check response
4. Share error message with me

### If Test Passes
Chapa works, issue is in booking parameters.

### If Test Fails
Chapa response shows exact problem.

## Next Steps

1. ✅ Changes applied
2. ⏳ Clear cache
3. ⏳ Run test endpoint
4. ⏳ Check Chapa response
5. ⏳ Share error message
6. ⏳ Apply targeted fix based on error

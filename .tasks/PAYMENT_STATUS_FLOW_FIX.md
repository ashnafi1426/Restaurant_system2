# Payment Status Flow - Fix and Documentation

## Issue
Payment records in database show status as "initialized" instead of progressing to "paid" or "verified" after successful payment.

## Root Cause
The `verify()` method in `PaymentController` was only calling `markAsVerified()`, skipping the `markAsPaid()` step. This meant:
1. Payment status went from "initialized" → "verified" (skipping "paid")
2. `paid_at` timestamp was never set
3. `chapa_transaction_id` was set separately instead of via `markAsPaid()`

## Payment Status Flow

### Status Constants (from Payment Model)
```php
const STATUS_PENDING = 'pending';        // Initial state
const STATUS_INITIALIZED = 'initialized'; // Chapa checkout URL obtained
const STATUS_PROCESSING = 'processing';   // (Reserved for future use)
const STATUS_PAID = 'paid';              // Payment confirmed by Chapa
const STATUS_VERIFIED = 'verified';      // Payment fully verified
const STATUS_FAILED = 'failed';          // Payment failed
const STATUS_CANCELLED = 'cancelled';    // User cancelled
const STATUS_EXPIRED = 'expired';        // Payment link expired
const STATUS_REFUNDED = 'refunded';      // Payment refunded
```

### Correct Status Progression

#### **Happy Path:**
```
1. pending         → Payment::create() creates record
2. initialized     → markAsInitialized() after getting Chapa checkout URL
3. paid            → markAsPaid() when Chapa confirms payment
4. verified        → markAsVerified() after full verification
```

#### **Alternative Flows:**
```
pending → initialized → failed      (Payment fails at Chapa)
pending → initialized → cancelled   (User cancels)
pending → initialized → expired     (Link expires)
verified → refunded                 (Refund processed)
```

## Changes Made

### Before (Wrong)
```php
// In PaymentController::verify()
if ($this->chapa->isSuccessful($response)) {
    // Only marked as verified, skipped paid status
    $payment->markAsVerified($response);
    $payment->update([
        'chapa_transaction_id' => $this->chapa->getTransactionId($response),
        'payment_method'       => $this->chapa->getPaymentMethod($response),
    ]);
    // ...
}
```

**Problems:**
- ❌ Status jumps from "initialized" to "verified"
- ❌ `paid_at` timestamp never set
- ❌ Transaction ID set via separate update
- ❌ No clear distinction between "paid" and "verified"

### After (Fixed)
```php
// In PaymentController::verify()
if ($this->chapa->isSuccessful($response)) {
    // First mark as paid (sets paid_at timestamp and transaction ID)
    $payment->markAsPaid($this->chapa->getTransactionId($response));
    
    // Then mark as verified (sets verified_at and raw_response)
    $payment->markAsVerified($response);
    
    // Update payment method
    $payment->update([
        'payment_method' => $this->chapa->getPaymentMethod($response),
    ]);
    
    // Return fresh instance with updated status
    return response()->json([
        'success' => true,
        'message' => 'Payment verified successfully',
        'status'  => $payment->fresh()->status,  // ✅ Get fresh status
        'payment' => new PaymentResource($payment->fresh()),
    ]);
}
```

**Improvements:**
- ✅ Proper status progression: initialized → paid → verified
- ✅ `paid_at` timestamp set automatically
- ✅ Transaction ID set via `markAsPaid()`
- ✅ Clear separation of concerns
- ✅ Fresh model instance ensures latest status returned

## Method Breakdown

### 1. `markAsPaid()` - Payment::class
```php
public function markAsPaid(?string $transactionId = null): void
{
    $this->update([
        'status' => self::STATUS_PAID,
        'chapa_transaction_id' => $transactionId,
        'paid_at' => now(),  // ✅ Sets timestamp
    ]);
}
```

**Purpose:** Records that payment was successfully processed by Chapa
**Sets:**
- status = 'paid'
- chapa_transaction_id
- paid_at timestamp

### 2. `markAsVerified()` - Payment::class
```php
public function markAsVerified(array $response = []): void
{
    $this->update([
        'status' => self::STATUS_VERIFIED,
        'verified_at' => now(),  // ✅ Sets timestamp
        'raw_response' => $response,  // ✅ Stores full Chapa response
    ]);
}
```

**Purpose:** Records that payment verification is complete
**Sets:**
- status = 'verified'
- verified_at timestamp
- raw_response (full Chapa response for audit trail)

## Database Schema

### Relevant Columns
```sql
-- payments table
status                VARCHAR      -- 'pending', 'initialized', 'paid', 'verified', 'failed', etc.
chapa_transaction_id  VARCHAR      -- Chapa's transaction ID
paid_at               TIMESTAMP    -- When payment was confirmed
verified_at           TIMESTAMP    -- When payment was verified
raw_response          JSON         -- Full Chapa response for audit
```

### Sample Record (After Fix)
```json
{
  "id": "uuid",
  "tx_ref": "TX-20260803071403-JQDFCA4D",
  "amount": "322.00",
  "status": "verified",  // ✅ Proper final status
  "chapa_transaction_id": "TXN123456789",
  "paid_at": "2026-08-03 07:15:00",  // ✅ Set
  "verified_at": "2026-08-03 07:15:01",  // ✅ Set
  "raw_response": { /* Chapa response */ },
  "created_at": "2026-08-03 07:14:03",
  "updated_at": "2026-08-03 07:15:01"
}
```

## Complete Payment Flow

### 1. **User Initiates Payment**
```
POST /api/reservation-payments/initialize
↓
Status: pending → initialized
Response: { checkout_url: "https://checkout.chapa.co/..." }
```

### 2. **User Completes Payment on Chapa**
```
User pays on Chapa
↓
Chapa processes payment
↓
Chapa sends callback to: /api/payments/callback
```

### 3. **Backend Receives Callback**
```
POST /api/payments/callback
↓
Extracts tx_ref from payload
↓
Calls verify(tx_ref)
```

### 4. **Backend Verifies Payment**
```
GET /api/payments/verify/{tx_ref}
↓
Query Chapa API for status
↓
If successful:
  - markAsPaid() → status: 'paid', paid_at set
  - markAsVerified() → status: 'verified', verified_at set
↓
Return: { success: true, status: 'verified' }
```

### 5. **Create Reservation** (After Verification)
```
POST /api/reservation-payments/complete/{tx_ref}
↓
Checks payment.status === 'verified'
↓
Creates reservation record
↓
Links payment to reservation
↓
Return: { success: true, reservation: {...} }
```

## Why Two Statuses (paid + verified)?

### **'paid'** Status
- **Meaning:** Chapa confirmed payment was successful
- **When:** Immediately after Chapa returns success
- **Purpose:** Quick acknowledgment that money was received

### **'verified'** Status  
- **Meaning:** Payment has been fully verified and processed
- **When:** After additional checks and data validation
- **Purpose:** Final confirmation, ready for business logic (create reservation)

### **Benefits of Separation:**
1. **Audit Trail:** Can track exactly when payment was confirmed vs verified
2. **Error Handling:** Can catch issues between payment and verification
3. **Business Logic:** Can add additional checks before marking "verified"
4. **Refunds:** Know exactly when payment was received (paid_at) vs verified

## Frontend Impact

### Success Page
The success page now receives proper status:

**Before:**
```javascript
// Payment status was "initialized" ❌
status: "initialized"
```

**After:**
```javascript
// Payment status progresses properly ✅
status: "paid"      // After Chapa confirms
status: "verified"  // After full verification
```

### Status Checks
Frontend can now check:
```javascript
if (payment.status === 'paid') {
  // Payment confirmed, processing...
}

if (payment.status === 'verified') {
  // Fully verified, show success!
}
```

## Testing the Fix

### Test Case 1: Complete Payment Flow
**Steps:**
1. Create booking with services
2. Initialize payment (status: pending → initialized)
3. Complete payment on Chapa
4. Verify payment (status: initialized → paid → verified) ✅
5. Create reservation
6. Check database

**Expected Database Record:**
```sql
SELECT status, paid_at, verified_at 
FROM payments 
WHERE tx_ref = 'TX-xxx';

-- Result:
-- status: 'verified' ✅
-- paid_at: '2026-08-03 07:15:00' ✅ (not NULL)
-- verified_at: '2026-08-03 07:15:01' ✅ (not NULL)
```

### Test Case 2: Failed Payment
**Steps:**
1. Initialize payment (status: pending → initialized)
2. Payment fails on Chapa
3. Verify payment (status: initialized → failed) ✅

**Expected:**
```sql
-- status: 'failed' ✅
-- paid_at: NULL ✅
-- verified_at: NULL ✅
```

### Test Case 3: Cancelled Payment
**Steps:**
1. Initialize payment
2. User cancels
3. Status should be 'cancelled'

## Files Modified

- `server/app/Http/Controllers/Api/PaymentController.php`
  - Updated `verify()` method to call both `markAsPaid()` and `markAsVerified()`
  - Added `.fresh()` calls to return updated status

## Files Documented (No Changes)

- `server/app/Models/Payment.php`
  - Has `markAsPaid()` method (was unused, now used)
  - Has `markAsVerified()` method (already in use)
  - Has all status constants

## Summary

✅ **Payment status now progresses correctly:**
   `pending` → `initialized` → `paid` → `verified`

✅ **Timestamps are set properly:**
   - `paid_at` set when payment confirmed
   - `verified_at` set when verification complete

✅ **Transaction ID set correctly:**
   - Set via `markAsPaid()` instead of separate update

✅ **Fresh model returned:**
   - Frontend receives latest status

✅ **Clear separation of concerns:**
   - "paid" = payment confirmed
   - "verified" = payment fully processed

**The payment status flow is now correct and complete!**

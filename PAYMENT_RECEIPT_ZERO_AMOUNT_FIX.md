# Payment Receipt Zero Amount Fix

## The Problem
After successful payment, the receipt shows **"TOTAL AMOUNT PAID: 0 ETB"** instead of the actual amount paid.

**Example Receipt Issue:**
```
PAYMENT SUMMARY
Transaction Reference: TX-20260809021346-LEU7UPE9
TOTAL AMOUNT PAID: 0 ETB  ← Should show actual amount (e.g., 2500 ETB)
```

## Root Cause
The `reservations` table **does not have a `total_amount` column**, so when creating a reservation after payment, the payment amount was not being stored in the reservation record.

### The Data Flow Issue
1. ✅ Payment amount is stored in `payments` table
2. ✅ Payment is verified successfully
3. ✅ Reservation is created
4. ❌ Reservation is created **WITHOUT** `total_amount` field
5. ❌ Receipt tries to read `reservationData.total_amount` → returns `0`

## The Solution

### 1. Add `total_amount` Column to Reservations Table

**Migration File:** `2026_08_09_000001_add_total_amount_to_reservations_table.php`

```php
public function up(): void
{
    Schema::table('reservations', function (Blueprint $table) {
        $table->decimal('total_amount', 10, 2)->nullable()->after('number_of_guests');
    });
}
```

### 2. Update Reservation Model

**File:** `server/app/Models/Reservation.php`

Added `total_amount` to:
- `$fillable` array - allows mass assignment
- `$casts` array - ensures proper decimal formatting

```php
protected $fillable = [
    // ... existing fields ...
    'total_amount', // ← NEW
    // ... rest of fields ...
];

protected $casts = [
    // ... existing casts ...
    'total_amount' => 'decimal:2', // ← NEW
    // ... rest of casts ...
];
```

### 3. Update PaymentService to Save Amount

**File:** `server/app/Services/PaymentService.php`

Modified `handleReservationPaymentSuccess()` method:

```php
$reservation = Reservation::create([
    // ... existing fields ...
    'total_amount' => $payment->amount, // ← NEW - Save payment amount
    // ... rest of fields ...
]);
```

## Implementation Steps

### Step 1: Run the Migration

```bash
cd server
php artisan migrate
```

**Expected Output:**
```
Migrating: 2026_08_09_000001_add_total_amount_to_reservations_table
Migrated:  2026_08_09_000001_add_total_amount_to_reservations_table (XX.XXms)
```

### Step 2: Verify Database Column

```sql
-- Check reservations table structure
DESCRIBE reservations;

-- Should show:
-- total_amount | decimal(10,2) | YES | NULL
```

### Step 3: Test New Bookings

1. Make a new reservation with payment
2. After payment, check receipt
3. **Expected**: Amount shows correctly (e.g., "TOTAL AMOUNT PAID: 2500 ETB")

## What Gets Fixed

### Before Fix
- ❌ Receipt shows: "TOTAL AMOUNT PAID: 0 ETB"
- ❌ Payment amount not stored in reservation
- ❌ Historical data missing amount information

### After Fix
- ✅ Receipt shows actual amount paid
- ✅ Payment amount saved in reservation record
- ✅ Can query reservation amounts directly
- ✅ Better reporting and analytics

## Data Integrity

### For New Reservations
- ✅ `total_amount` automatically populated from payment
- ✅ Stored as decimal(10,2) for currency precision
- ✅ Nullable to support legacy data

### For Existing Reservations
Existing reservations will have `NULL` for `total_amount`. If needed, you can backfill:

```sql
-- Backfill total_amount from payments table
UPDATE reservations r
INNER JOIN payments p ON p.reservation_id = r.id
SET r.total_amount = p.amount
WHERE r.total_amount IS NULL;
```

## Receipt Display Logic

**Frontend:** `PaymentSuccessPage.vue` (line 198)

```vue
<p class="text-5xl font-bold text-orange-600">
  {{ reservationData.total_amount || 0 }}
</p>
<span class="text-2xl font-bold text-orange-600">ETB</span>
```

Now `reservationData.total_amount` will have the actual payment amount!

## Files Modified

### Database
1. `server/database/migrations/2026_08_09_000001_add_total_amount_to_reservations_table.php` - NEW migration

### Models
2. `server/app/Models/Reservation.php` - Added `total_amount` to fillable and casts

### Services
3. `server/app/Services/PaymentService.php` - Save `total_amount` when creating reservation

### Frontend
- No changes needed - already reads `reservationData.total_amount`

## Testing Checklist

### Test Case 1: New Reservation Payment
1. Go to guest booking page
2. Select room and dates
3. Proceed to payment (e.g., 2500 ETB)
4. Complete payment via Chapa
5. Check receipt page
6. **Expected**: Shows "TOTAL AMOUNT PAID: 2500 ETB"

### Test Case 2: Database Verification
```sql
-- After making a test booking
SELECT 
    id,
    booking_reference,
    total_amount,
    created_at
FROM reservations
ORDER BY created_at DESC
LIMIT 1;

-- Expected: total_amount should match payment amount
```

### Test Case 3: Receipt Download
1. After payment success, click "Download Receipt"
2. Open downloaded PDF
3. **Expected**: PDF shows correct amount

## Verification Queries

### Check Migration Status
```bash
php artisan migrate:status
```

### Check Recent Reservations with Amounts
```sql
SELECT 
    r.booking_reference,
    r.total_amount as reservation_amount,
    p.amount as payment_amount,
    r.created_at
FROM reservations r
LEFT JOIN payments p ON p.reservation_id = r.id
ORDER BY r.created_at DESC
LIMIT 10;
```

### Check For NULL Amounts (Legacy Data)
```sql
SELECT COUNT(*) as legacy_reservations
FROM reservations
WHERE total_amount IS NULL;
```

## Error Handling

### If Migration Fails
```bash
# Check for existing column
php artisan tinker
>>> Schema::hasColumn('reservations', 'total_amount');

# If true, skip migration or rollback first
php artisan migrate:rollback --step=1
```

### If Amount Still Shows 0
1. Check Laravel logs: `storage/logs/laravel.log`
2. Look for: `Reservation Created After Payment`
3. Verify `total_amount` is in log output
4. Check database directly:
   ```sql
   SELECT * FROM reservations WHERE id = 'your-reservation-id';
   ```

## Related Components

### Payment Flow
1. Guest selects room → `BookingModal.vue`
2. Payment initiated → `ReservationPaymentController->initiate()`
3. Chapa processes payment
4. Callback received → `ReservationPaymentController->callback()`
5. **Reservation created with amount** → `PaymentService->handleReservationPaymentSuccess()`
6. Success page shown → `PaymentSuccessPage.vue`
7. Receipt generated → `receiptService.ts`

### Amount Sources
- **Primary Source**: `payments.amount` (verified by Chapa)
- **Copied To**: `reservations.total_amount` (for quick access)
- **Displayed From**: `reservations.total_amount` (on receipt)

## Benefits of This Fix

### Immediate Benefits
- ✅ Receipts show correct amounts
- ✅ Guests see payment confirmation
- ✅ Professional appearance

### Long-term Benefits
- ✅ Better financial reporting
- ✅ Revenue analytics by reservation
- ✅ Audit trail for payments
- ✅ No need to join payments table for amount queries

## Notes

### Why Store Amount in Both Tables?
- **Payments Table**: Source of truth for payment transaction
- **Reservations Table**: Denormalized for performance and convenience
- Trade-off: Small redundancy for much faster queries

### Currency Precision
- Stored as `decimal(10,2)` for proper currency handling
- Supports amounts up to 99,999,999.99 ETB
- Avoids floating-point rounding errors

### Nullable Column
- Made nullable to support existing reservations
- New reservations always have amount
- Can backfill legacy data if needed

---

**Status**: ✅ READY TO DEPLOY
**Priority**: High (User-facing issue)
**Risk Level**: Low (additive change, no data loss)
**Rollback**: Simple - just rollback migration

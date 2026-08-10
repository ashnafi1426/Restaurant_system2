# Payment Form Requirements - Walk-In vs Booked Guests

## Summary

**Walk-in customers MUST fill payment form**  
**Booked hotel guests do NOT need payment form** (guest info from check-in used automatically)

---

## Current Behavior

### Walk-In Customers (Restaurant Tables)

**QR Code:** Table QR (e.g., Table T18)

**Payment Flow:**
1. Customer scans table QR code
2. System detects `context: 'table'`
3. Customer adds items to cart
4. Customer clicks "View Cart & Checkout"
5. **Payment modal shows with required form fields:**
   - First Name * (required)
   - Last Name * (required)
   - Email * (required)
   - Phone Number * (required)
6. Customer fills all fields
7. Customer clicks "Pay Now"
8. System validates form (all fields required)
9. System initializes Chapa payment using form data
10. Redirects to Chapa checkout

**Guest Information Source:** User manual input (required)

---

### Booked Hotel Guests (Room Service)

**QR Code:** Room QR (e.g., Room 101)

**Payment Flow:**
1. Guest scans room QR code
2. System detects `context: 'room'`
3. Backend checks for active check-in
4. System retrieves guest information:
   - `guest_id` from check_ins table
   - `guest_name` (first_name + last_name)
   - `guest_email`
   - `guest_phone`
   - `reservation_id`
5. Guest adds items to cart
6. Guest clicks "View Cart & Checkout"
7. **Payment modal shows WITHOUT customer details form**
   - No first name field
   - No last name field
   - No email field
   - No phone field
8. Guest clicks "Pay Now"
9. System uses guest info from check-in automatically
10. System initializes Chapa payment using check-in data
11. Redirects to Chapa checkout

**Guest Information Source:** Check-in record (automatic)

---

## Implementation Details

### Frontend - Payment Form Visibility

**File:** `Client2/vue-project/src/views/guest/QRMenu.vue`

```vue
<!-- Payment Modal -->
<div class="payment-modal">
  <h3>Payment Confirmation</h3>
  
  <!-- Walk-In Payment Form - ONLY for tables -->
  <div v-if="orderContext?.type === 'table'" class="space-y-3">
    <h4 class="font-semibold text-sm mb-2">Customer Details</h4>
    
    <!-- First Name -->
    <div>
      <label>First Name *</label>
      <input v-model="paymentForm.first_name" type="text" required />
    </div>
    
    <!-- Last Name -->
    <div>
      <label>Last Name *</label>
      <input v-model="paymentForm.last_name" type="text" required />
    </div>
    
    <!-- Email -->
    <div>
      <label>Email *</label>
      <input v-model="paymentForm.email" type="email" required />
    </div>
    
    <!-- Phone -->
    <div>
      <label>Phone Number *</label>
      <input v-model="paymentForm.phone" type="tel" required />
    </div>
  </div>
  
  <!-- Order Summary (shown for both) -->
  <div class="order-summary">
    <!-- Items, totals, etc. -->
  </div>
  
  <button @click="proceedToPayment">Pay Now</button>
</div>
```

**Key Logic:**
- `v-if="orderContext?.type === 'table'"` - Form ONLY shows for walk-in customers
- No form shown for room service orders
- Payment button visible for both types

---

### Frontend - Payment Processing

**File:** `Client2/vue-project/src/views/guest/QRMenu.vue`

#### Walk-In Order Processing

```javascript
if (orderContext.value.type === 'table') {
  // Validate form fields (REQUIRED for walk-in)
  if (!paymentForm.value.first_name.trim()) {
    alert('Please enter your first name')
    return
  }
  if (!paymentForm.value.last_name.trim()) {
    alert('Please enter your last name')
    return
  }
  if (!paymentForm.value.email.trim() || !paymentForm.value.email.includes('@')) {
    alert('Please enter a valid email address')
    return
  }
  if (!paymentForm.value.phone.trim() || paymentForm.value.phone.length < 10) {
    alert('Please enter a valid phone number')
    return
  }
  
  // Initialize walk-in payment using FORM DATA
  const paymentResponse = await unifiedOrderService.initializeWalkInPayment({
    table_id: orderContext.value.id,
    qr_token: qrToken.value,
    items: orderItems,
    special_requests: '',
    first_name: paymentForm.value.first_name,  // From form
    last_name: paymentForm.value.last_name,    // From form
    email: paymentForm.value.email,            // From form
    phone: paymentForm.value.phone,            // From form
  })
  
  // Redirect to Chapa
  window.location.href = paymentResponse.checkout_url
}
```

#### Room Service Order Processing

```javascript
if (orderContext.value.type === 'room') {
  // Get guest info from QR resolution (from check-in)
  const result = await qrService.resolveQRToken(qrToken.value)
  
  if (!result.success || !result.data?.guest) {
    throw new Error('No guest checked into this room. Please contact reception.')
  }
  
  const guestInfo = result.data.guest  // From check_ins table
  console.log('Using guest from check-in:', guestInfo.guest_name)
  
  // Initialize room service payment using GUEST DATA FROM CHECK-IN
  const paymentInitRequest = {
    guest_id: guestInfo.guest_id,              // From check-in
    room_id: result.data.room_id,
    items: orderItems,
    first_name: guestInfo.guest_name.split(' ')[0],           // From check-in
    last_name: guestInfo.guest_name.split(' ').slice(1).join(' '),  // From check-in
    email: guestInfo.guest_email,              // From check-in
    phone: guestInfo.guest_phone,              // From check-in
  }
  
  const paymentResponse = await fetch('http://127.0.0.1:8000/api/order-payments/initialize', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify(paymentInitRequest),
  })
  
  // Redirect to Chapa
  window.location.href = paymentData.checkout_url
}
```

---

### Backend - Guest Information Retrieval

**File:** `server/app/Services/QRResolutionService.php`

When room QR is scanned, backend automatically fetches guest info:

```php
public static function resolveQRToken(string $qrToken): array
{
    // Find room
    $room = Room::where('qr_token', $qrToken)->first();
    
    if ($room) {
        // Check for active check-in
        $activeCheckIn = \App\Models\CheckIn::where('room_id', $room->id)
            ->whereNull('checked_out_at')
            ->with(['guest', 'reservation'])
            ->first();
        
        $guestInfo = null;
        if ($activeCheckIn && $activeCheckIn->guest) {
            $guestInfo = [
                'guest_id' => $activeCheckIn->guest_id,
                'guest_name' => $activeCheckIn->guest->first_name . ' ' . $activeCheckIn->guest->last_name,
                'guest_email' => $activeCheckIn->guest->email,
                'guest_phone' => $activeCheckIn->guest->phone,
                'reservation_id' => $activeCheckIn->reservation_id,
            ];
        }
        
        return [
            'success' => true,
            'context' => 'room',
            'data' => [
                'room_id' => $room->id,
                'room_number' => $room->room_number,
                'guest' => $guestInfo,  // ✓ Guest info included
            ],
        ];
    }
}
```

---

## Data Flow Comparison

### Walk-In Customer Flow

```
Customer → Scans Table QR
         → System: context = 'table'
         → Shows payment form
         → Customer fills: first_name, last_name, email, phone
         → Customer submits
         → System validates form
         → System sends to WalkInOrderPaymentController
         → Creates Payment record with form data
         → Redirects to Chapa
         → Customer pays
         → Order created with walk_in type
```

### Hotel Guest Flow

```
Guest → Scans Room QR
      → System: context = 'room'
      → Backend queries check_ins table
      → Finds active check-in for room
      → Retrieves guest: guest_id, name, email, phone
      → Frontend receives guest info
      → Payment modal shows WITHOUT form
      → Guest clicks Pay Now
      → System uses guest info from check-in
      → System sends to GuestOrderPaymentController
      → Creates Payment record with check-in data
      → Redirects to Chapa
      → Guest pays
      → Order created with room_service type
```

---

## Validation Rules

### Walk-In Orders (Form Required)

| Field | Validation | Error Message |
|-------|-----------|---------------|
| First Name | Required, non-empty | "Please enter your first name" |
| Last Name | Required, non-empty | "Please enter your last name" |
| Email | Required, must contain @ | "Please enter a valid email address" |
| Phone | Required, minimum 10 digits | "Please enter a valid phone number" |

**If any validation fails:** Form submission blocked, alert shown

### Room Service Orders (No Form)

| Field | Source | Validation |
|-------|--------|-----------|
| guest_id | check_ins table | Must exist |
| guest_name | guests table | From check-in |
| guest_email | guests table | From check-in |
| guest_phone | guests table | From check-in |
| reservation_id | check_ins table | From check-in |

**If guest not found:** Error: "No guest checked into this room. Please contact reception."

---

## UI/UX Differences

### Walk-In Customer Experience

1. **Menu heading:** "Restaurant Menu"
2. **Subheading:** "Table T18"
3. **Guest label:** "Walk-in Guest"
4. **Payment modal:**
   - ✓ Shows customer details form
   - ✓ All fields required
   - ✓ Form validation before payment
5. **Info badge:** "Walk-in order for Table T18"

### Hotel Guest Experience

1. **Menu heading:** "Room Service Menu"
2. **Subheading:** "Room 101"
3. **Guest label:** "John Doe" (actual guest name)
4. **Payment modal:**
   - ✗ No customer details form
   - ✓ Direct to payment
   - ✓ Guest info from check-in used automatically
5. **Info badge:** None or "Room service for Room 101"

---

## Error Scenarios

### Walk-In Order Errors

**Error:** User doesn't fill form  
**Result:** Alert shown, payment blocked  
**Solution:** User must fill all required fields

**Error:** Invalid email format  
**Result:** Alert shown, payment blocked  
**Solution:** User must enter valid email

**Error:** Phone number too short  
**Result:** Alert shown, payment blocked  
**Solution:** User must enter valid phone

### Room Service Order Errors

**Error:** No active check-in for room  
**Result:** "No guest checked into this room. Please contact reception."  
**Solution:** Guest must check in at reception first

**Error:** Guest record missing  
**Result:** "No guest checked into this room. Please contact reception."  
**Solution:** Database issue, contact support

**Error:** QR token not found  
**Result:** "QR code not found or has been deactivated"  
**Solution:** Generate new QR code for room

---

## Benefits of This Approach

✅ **Better UX for Hotel Guests**
- No redundant data entry
- Faster checkout process
- Uses verified guest information

✅ **Required Info for Walk-Ins**
- Captures customer contact details
- Enables follow-up and receipts
- Payment accountability

✅ **Data Accuracy**
- Room service uses verified check-in data
- Prevents typos in guest information
- Maintains guest privacy (no manual entry)

✅ **Security**
- Room orders linked to actual checked-in guests
- Walk-in payments require identification
- Transaction tracking per order type

---

## Testing Checklist

### Walk-In Customer Test
1. ✓ Scan table QR code
2. ✓ See "Restaurant Menu" and "Walk-in Guest"
3. ✓ Add items to cart
4. ✓ Click checkout
5. ✓ **Verify payment form is shown**
6. ✓ Try submitting without filling → Should show alerts
7. ✓ Fill all fields correctly
8. ✓ Click "Pay Now"
9. ✓ Verify redirects to Chapa with form data

### Hotel Guest Test
1. ✓ Check in guest to room first
2. ✓ Scan room QR code
3. ✓ See "Room Service Menu" and actual guest name
4. ✓ Add items to cart
5. ✓ Click checkout
6. ✓ **Verify NO payment form is shown**
7. ✓ Click "Pay Now" directly
8. ✓ Verify redirects to Chapa with check-in data

### Empty Room Test
1. ✓ Scan room QR for empty room (no check-in)
2. ✓ Add items to cart
3. ✓ Click checkout
4. ✓ Click "Pay Now"
5. ✓ **Should show error:** "No guest checked into this room"

---

## Summary

| Aspect | Walk-In Customer | Hotel Guest |
|--------|------------------|-------------|
| **Payment Form** | ✓ Required | ✗ Not shown |
| **First Name** | Manual input | From check-in |
| **Last Name** | Manual input | From check-in |
| **Email** | Manual input | From check-in |
| **Phone** | Manual input | From check-in |
| **Validation** | Form fields | Check-in existence |
| **Data Source** | User input | Database (check_ins + guests) |
| **User Experience** | Fill form → Pay | Direct → Pay |

# Guest Identification Fix

## Issue
The system was showing "Walk-in Guest" for hotel guests who scanned their room QR code. It should show their actual name from the check-in record.

## Root Cause
`QRResolutionService.php` was only returning room information but not the guest who is currently checked into that room.

## Solution

### Backend Changes

**File:** `server/app/Services/QRResolutionService.php`

Added logic to fetch active check-in record and guest information:

```php
// Check if there's an active check-in for this room
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
        'check_in_date' => $activeCheckIn->checked_in_at?->format('Y-m-d'),
        'expected_checkout' => $activeCheckIn->expected_check_out_at?->format('Y-m-d'),
    ];
}

return [
    'success' => true,
    'context' => 'room',
    'data' => [
        'room_id' => $room->id,
        'room_number' => $room->room_number,
        'floor' => $room->floor,
        'floor_id' => $room->floor_id,
        'room_type' => $room->roomType ? $room->roomType->name : null,
        'status' => $room->status,
        'guest' => $guestInfo, // ✓ Guest information included
    ],
    'message' => 'QR code belongs to a hotel room',
];
```

### Frontend Changes

**File:** `Client2/vue-project/src/views/guest/QRMenu.vue`

Updated context detection to use guest information:

```javascript
if (result.context === 'room') {
  orderContext.value = {
    type: 'room',
    id: result.data.room_id,
    displayName: `Room ${result.data.room_number}`,
    paymentOptions: [{ value: 'room_charge', label: 'Charge to Room' }],
  }
  roomNumber.value = result.data.room_number || '101'
  heroHeading.value = 'Room Service Menu'
  heroSubheading.value = `Room ${result.data.room_number}`
  
  // Use guest information if available (checked-in guest)
  if (result.data.guest) {
    guestName.value = result.data.guest.guest_name  // ✓ Actual guest name
    guestEmail.value = result.data.guest.guest_email || 'guest@hotel.com'
    console.log('✅ [QR] Room context with checked-in guest:', result.data.guest.guest_name)
  } else {
    // Room has no active check-in
    guestName.value = 'Hotel Guest'
    guestEmail.value = 'guest@hotel.com'
    console.log('⚠️ [QR] Room has no active check-in')
  }
}
```

## How It Works Now

### Scenario 1: Hotel Guest (Checked In)
1. Guest checks into Room 101
2. System creates check-in record linking guest to room
3. Guest scans room QR code
4. Backend finds room AND active check-in
5. Returns guest information: "John Doe"
6. Frontend displays: "John Doe" (not "Walk-in Guest")

### Scenario 2: Walk-In Customer (Restaurant Table)
1. Customer scans table QR code
2. Backend identifies it as a restaurant table
3. Returns table information (no guest)
4. Frontend displays: "Walk-in Guest"

### Scenario 3: Empty Room (No Check-In)
1. Someone scans a room QR code that has no active check-in
2. Backend finds room but no check-in record
3. Returns room info with `guest: null`
4. Frontend displays: "Hotel Guest" (generic fallback)

## Database Relations

```
Room (rooms table)
  ↓
CheckIn (check_ins table)
  - room_id → links to room
  - guest_id → links to guest
  - checked_out_at → NULL means active
  ↓
Guest (guests table)
  - first_name
  - last_name
  - email
  - phone
```

## Testing

### Test 1: Checked-In Guest Orders Food
1. Make sure guest is checked in to a room
2. Get room QR code
3. Scan QR code
4. **Expected**: Should show guest's actual name (e.g., "John Doe")
5. **Should NOT show**: "Walk-in Guest"

### Test 2: Walk-In Customer at Table
1. Get restaurant table QR code
2. Scan QR code
3. **Expected**: Should show "Walk-in Guest"
4. **Expected**: Should show table number (e.g., "Table T18")

### Test 3: Empty Room (No Check-In)
1. Get room QR code for empty room
2. Scan QR code
3. **Expected**: Should show "Hotel Guest" (generic)
4. Order may fail at payment if no guest/reservation

## Key Differences

| User Type | QR Code Type | Display Name | Payment Modal |
|-----------|--------------|--------------|---------------|
| **Checked-In Guest** | Room | "John Doe" (actual name) | Shows room number |
| **Walk-In Customer** | Table | "Walk-in Guest" | Shows table number |
| **Empty Room** | Room | "Hotel Guest" (generic) | Shows room number |

## Files Modified

1. ✅ `server/app/Services/QRResolutionService.php`
   - Added guest information lookup from check_ins table
   
2. ✅ `Client2/vue-project/src/views/guest/QRMenu.vue`
   - Updated to use guest name from backend response

## Benefits

✅ **Accurate Guest Identification**
- Shows actual guest name for room service orders
- Clear distinction between hotel guests and walk-in customers

✅ **Better User Experience**
- Personalized greeting for checked-in guests
- No confusion about order type

✅ **Proper Data Flow**
- Guest information flows from check-in → QR scan → order
- Orders properly linked to guest_id and reservation_id

✅ **Clear Visual Indicators**
- "John Doe" = Hotel guest ordering room service
- "Walk-in Guest" = Customer ordering from restaurant table

## Before vs After

### BEFORE (Incorrect)
```
Room 101 QR Scan → Shows "Walk-in Guest" ❌
Table T18 QR Scan → Shows "Walk-in Guest" ✓
```

### AFTER (Correct)
```
Room 101 QR Scan (with check-in) → Shows "John Doe" ✓
Room 101 QR Scan (no check-in) → Shows "Hotel Guest" ✓
Table T18 QR Scan → Shows "Walk-in Guest" ✓
```

## Next Steps

1. Test with actual check-in data
2. Verify guest name appears correctly
3. Ensure payment flow works for both guest types
4. Test kitchen and waiter displays show correct information

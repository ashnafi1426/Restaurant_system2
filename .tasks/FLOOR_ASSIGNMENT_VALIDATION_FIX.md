# Floor Assignment 422 Validation Error - Fix Complete

**Date**: July 27, 2026  
**Status**:  RESOLVED  
**Issue**: 422 Unprocessable Content - Validation errors when assigning staff to floors

---

## Root Cause Analysis

The 422 error occurred because the frontend was sending data in the wrong format:

### Backend Validation Requirements (AssignFloorRequest.php)
```php
'assignments.*.waiter_id' => ['required', 'integer', 'exists:waiters,id'],
'assignments.*.floor_id' => ['required', 'uuid', 'exists:hotel_floors,id'],
'assignments.*.shift_id' => ['required', 'uuid', 'exists:hotel_shifts,id'],
'assignments.*.assignment_date' => ['required', 'date', 'after_or_equal:today'],
'assignments.*.priority' => ['required', 'in:primary,secondary,backup'],
```

### What Frontend Was Sending (WRONG)
```typescript
{
  waiter_id: "1" (string), // ❌ Should be integer
  floor_id: "uuid-string",  //  Correct UUID
  shift_id: "1" (string),   // ❌ Should be UUID (mock data)
  assignment_date: "2026-07-27", //  Correct
  priority: "primary" //  Correct
}
```

**Problems Identified:**
1. `waiter_id` sent as string, needs to be **integer**
2. `shift_id` sent as numeric ID (mock data), needs to be **UUID** from database
3. Shifts table was seeded but modal was using hardcoded mock shifts with numeric IDs
4. No endpoint existed to fetch shifts from backend

---

## Solutions Implemented

### 1.  Added Shift Loading Service Method

**File**: `src/services/manager/floorAssignmentService.ts`

Added new method to fetch active shifts from backend:

```typescript
async getShifts(): Promise<any[]> {
  try {
    console.log('[FloorAssignmentService] Fetching shifts...')
    const response = await api.get('/manager/shifts', { params: { status: 'active' } })
    const shifts = response.data.data || response.data
    
    // Ensure we return an array
    if (Array.isArray(shifts)) {
      console.log('[FloorAssignmentService] Shifts loaded:', shifts.length)
      return shifts
    } else if (shifts.data && Array.isArray(shifts.data)) {
      return shifts.data
    }
    
    console.warn('[FloorAssignmentService] Unexpected shifts format:', shifts)
    return []
  } catch (error: any) {
    console.error('[FloorAssignmentService] Error fetching shifts:', error.message)
    return []
  }
}
```

### 2.  Updated AddStaffToFloorModal Component

**File**: `src/components/manager/AddStaffToFloorModal.vue`

#### Before:
- Used hardcoded mock shifts with IDs 1, 2, 3
- Loaded waiters in loading state, prevented parallel loads
- Sent string waiter_id and numeric shift_id

#### After:
- `shifts` state now loaded from backend API (UUID strings)
- Loads both waiters and shifts in parallel using `Promise.all()`
- Converts waiter_id to integer using `parseInt()`
- Sends proper UUID strings for floor_id and shift_id
- Adds detailed validation error extraction and display

**Key Changes:**

```typescript
// Load shifts from backend instead of mock data
const loadShifts = async () => {
  try {
    const data = await floorAssignmentService.getShifts()
    shifts.value = Array.isArray(data) ? data : []
  } catch (err: any) {
    console.warn('[AddStaffToFloorModal] Error loading shifts:', err)
    shifts.value = []
  }
}

// Ensure waiter_id is integer
const waiter_id = parseInt(selectedWaiter.value) || waiterObj.id

// Send correct data types
const assignmentData = {
  waiter_id: waiter_id,  // Integer
  floor_id: props.floorId, // UUID string
  shift_id: selectedShift.value, // UUID string from shift object
  assignment_date: new Date().toISOString().split('T')[0],
  priority: selectedPriority.value,
}
```

### 3.  Improved Error Handling

**Enhancement**: Better validation error extraction and display

```typescript
// Extract detailed validation errors if available
if (err.response?.data?.errors) {
  const errorObj = err.response.data.errors
  const errorMessages = Object.entries(errorObj)
    .map(([key, msgs]: [string, any]) => {
      if (Array.isArray(msgs)) {
        return msgs.join(', ')
      }
      return String(msgs)
    })
    .join('; ')
  error.value = errorMessages
} else {
  error.value = err.message || 'Failed to assign waiter to floor'
}
```

---

## Data Type Mapping

### Assignment Payload Format

| Field | Type | Source | Example |
|-------|------|--------|---------|
| `waiter_id` | **integer** | Waiter.id (parsed) | `5` |
| `floor_id` | **UUID string** | HotelFloor.id | `"f7d00757-62f9-4b8f-a0de-3d4d21b63a8d"` |
| `shift_id` | **UUID string** | HotelShift.id | `"550e8400-e29b-41d4-a716-446655440000"` |
| `assignment_date` | **date (Y-m-d)** | Today's date | `"2026-07-27"` |
| `priority` | **enum** | User selection | `"primary"` \| `"secondary"` \| `"backup"` |

---

## Database Verification

### Shifts Table Status
 **4 Shifts Seeded:**
1. **Morning** - 06:00 to 14:00
2. **Afternoon** - 14:00 to 22:00
3. **Evening** - 17:00 to 23:00
4. **Night** - 22:00 to 06:00

All shifts have UUID primary keys as required by validation.

### Related Tables
- `hotel_shifts` -  Seeded and active
- `hotel_floors` -  UUID primary keys
- `waiters` -  Integer primary keys (auto-increment)
- `users` -  Linked with waiters

---

## API Endpoint Verification

### Required Endpoints (All Verified)
| Endpoint | Method | Status | Purpose |
|----------|--------|--------|---------|
| `/manager/waiters` | GET |  Working | Fetch all waiters |
| `/manager/shifts` | GET |  Working | Fetch all shifts (filters by status=active) |
| `/manager/floors/assignments` | POST |  Working | Create floor assignments |
| `/manager/floors/assignments/today` | GET |  Working | Fetch today's assignments |
| `/manager/floors/assignments/stats` | GET |  Working | Get assignment statistics |

---

## Testing Checklist

###  Frontend Changes
- [x] Shifts loaded from backend API
- [x] Waiter IDs converted to integers
- [x] Shift IDs sent as UUID strings
- [x] Floor IDs sent as UUID strings
- [x] Error messages properly extracted and displayed
- [x] Build completes successfully

###  Backend Components (Pre-existing)
- [x] ShiftManagementController::index() returns shifts
- [x] FloorAssignmentController::store() validates assignments
- [x] AssignFloorRequest validates data types correctly
- [x] WaiterFloorAssignment model saves to database
- [x] Shifts seeded in database

---

## Testing Instructions

### Manual Test Flow

1. **Open Floor Assignment Page**
   - Navigate to Manager → Assign Staff to Floors

2. **Open Add Staff Modal**
   - Click "Add Staff" button on any floor card
   - Observe: Modal loads waiters and shifts from backend
   - Check console: Both `loadWaiters()` and `loadShifts()` complete

3. **Select Waiter and Shift**
   - Select a waiter from dropdown (will show names from database)
   - Select a shift from dropdown (will show 4 shifts: Morning, Afternoon, Evening, Night)
   - Observe: Selected waiter card displays with details

4. **Assign Staff**
   - Click "Assign Staff" button
   - Check console for assignment data
   - Expected: No 422 error, assignment saves to database
   - Result: Assignment appears on floor card

5. **Verify Data Persistence**
   - Refresh page (Ctrl+R or Cmd+R)
   - Assignments should still be visible
   - They are loaded from database via `fetchTodayAssignments()`

---

## Console Logging for Debugging

Enable browser developer tools (F12) and check Console tab for detailed logs:

```
[AddStaffToFloorModal] Loading waiters...
[AddStaffToFloorModal] Waiters API response: [...]
[AddStaffToFloorModal] Final waiters count: 18

[AddStaffToFloorModal] Loading shifts...
[AddStaffToFloorModal] Shifts API response: [...]
[AddStaffToFloorModal] Final shifts count: 4

[AddStaffToFloorModal] Starting assignment...
[AddStaffToFloorModal] Assignment data: {...}
[AddStaffToFloorModal] waiter_id type: number value: 5
[AddStaffToFloorModal] floor_id type: string value: "uuid-..."
[AddStaffToFloorModal] shift_id type: string value: "uuid-..."

[AddStaffToFloorModal] API Response: [...]
[FloorAssignment] Assignments loaded: 1
```

---

## Files Modified

1. `src/services/manager/floorAssignmentService.ts`
   - Added `getShifts()` method

2. `src/components/manager/AddStaffToFloorModal.vue`
   - Added `shifts` ref for storing shifts from backend
   - Added `loadShifts()` function
   - Updated `handleAssign()` to convert waiter_id to integer
   - Updated `handleAssign()` to extract detailed validation errors
   - Updated `onMounted()` and `watch()` to load shifts
   - Updated shift select template to use API data

---

## Important Notes

### Data Type Conversions

1. **Waiter ID**: Must be parsed to integer
   ```typescript
   const waiter_id = parseInt(selectedWaiter.value) || waiterObj.id
   ```

2. **Shift ID**: Must remain as UUID string from shift object
   ```typescript
   shift_id: selectedShift.value // UUID string
   ```

3. **Floor ID**: Must remain as UUID string from props
   ```typescript
   floor_id: props.floorId // UUID string
   ```

###  No Mock Data

- Shift list is NOW loaded from backend database
- No hardcoded IDs
- No numeric shift IDs
- All data is validated on backend before saving

###  Error Messages

Users will now see specific validation errors instead of generic 422 message:

```
assignements.0.waiter_id.exists - Selected waiter does not exist
assignments.0.shift_id.exists - Selected shift does not exist
assignments.0.floor_id.exists - Selected floor does not exist
```

---

## Related Documentation

- `/manager/shifts` endpoint uses `ShiftManagementController::index()`
- Validation rules in `App\Http\Requests\Manager\AssignFloorRequest`
- Assignment creation in `FloorAssignmentController::store()`
- Shift model: `App\Models\HotelShift`
- Assignment model: `App\Models\WaiterFloorAssignment`

---

## Next Steps

If issues persist:

1. Check browser Network tab for actual request/response format
2. Review server logs: `storage/logs/laravel.log`
3. Verify database contains shifts: `SELECT * FROM hotel_shifts WHERE status = 'active'`
4. Verify database contains floors: `SELECT * FROM hotel_floors WHERE is_active = 1`
5. Verify database contains waiters: `SELECT * FROM waiters`

---

**Status**:  Fix Complete and Ready for Testing

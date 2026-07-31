# Modal Backend Integration - Detailed Part-by-Part Guide

**Status**:  COMPLETELY REFACTORED FOR CLARITY  
**Date**: July 27, 2026  
**Purpose**: Clear identification of each part of the modal flow

---

## Modal Architecture - 4 Parts

The modal works in 4 distinct parts:

```
┌─────────────────────────────────────────────────────────┐
│ PART 0: INITIALIZATION                                  │
│ - Modal mounts                                          │
│ - Calls loadWaiters() + loadShifts() in parallel       │
└─────────────────────────────────────────────────────────┘
         ↓
┌─────────────────────────────────────────────────────────┐
│ PART 1: LOAD WAITERS FROM BACKEND                       │
│ - GET /manager/waiters                                 │
│ - Response: Array of waiter objects                    │
│ - Populates: <select v-model="selectedWaiter">        │
└─────────────────────────────────────────────────────────┘
         ↓
┌─────────────────────────────────────────────────────────┐
│ PART 2: LOAD SHIFTS FROM BACKEND                        │
│ - GET /manager/shifts?status=active                    │
│ - Response: Array of shift objects with UUIDs          │
│ - Populates: <select v-model="selectedShift">          │
└─────────────────────────────────────────────────────────┘
         ↓
     [User Interface]
     User selects waiter + shift + priority
     User clicks "Assign Staff" button
         ↓
┌─────────────────────────────────────────────────────────┐
│ PART 3: SEND ASSIGNMENT TO BACKEND                      │
│ - POST /manager/floors/assignments                     │
│ - Payload: { assignments: [{...}] }                    │
│ - waiter_id: INTEGER (e.g., 5)                         │
│ - floor_id: UUID STRING (from props.floorId)           │
│ - shift_id: UUID STRING (from selected shift)          │
│ - assignment_date: DATE STRING (Y-m-d)                 │
│ - priority: ENUM (primary/secondary/backup)            │
└─────────────────────────────────────────────────────────┘
         ↓
┌─────────────────────────────────────────────────────────┐
│ PART 4: BACKEND VALIDATION & SAVE                       │
│ - Backend validates all fields                         │
│ - Checks foreign keys exist                            │
│ - Saves to waiter_floor_assignments table              │
│ - Returns 201 Created + assignment data                │
└─────────────────────────────────────────────────────────┘
```

---

## PART 0: Initialization

### When it happens
- User clicks "Add Staff" button on floor card
- Modal component mounts with `isOpen=true`
- `props.floorId` and `props.floorName` are passed from parent

### Code Location
```typescript
onMounted(() => {
  console.log('[Modal] PART 0: Modal mounted, isOpen=', props.isOpen)
  if (props.isOpen) {
    isLoading.value = true
    console.log('[Modal] PART 0: Starting data load...')
    Promise.all([loadWaiters(), loadShifts()]).then(() => {
      isLoading.value = false
      console.log('[Modal] PART 0:  All data loaded')
    }).catch(err => {
      console.error('[Modal] PART 0: ❌ Error:', err)
      isLoading.value = false
    })
  }
})
```

### What it does
1. Sets `isLoading = true` (shows spinner)
2. Calls `loadWaiters()` and `loadShifts()` in parallel
3. Waits for BOTH to complete
4. Sets `isLoading = false` (hides spinner)

### Console output to expect
```
[Modal] PART 0: Modal mounted, isOpen= true
[Modal] PART 0: Starting data load...
[Modal] PART 1: Loading waiters...
[Modal] PART 2: Loading shifts...
... (API calls) ...
[Modal] PART 0:  All data loaded
```

---

## PART 1: Load Waiters

### API Call
```
GET /manager/waiters
```

### Code
```typescript
const loadWaiters = async () => {
  try {
    console.log('[Modal] PART 1: Loading waiters...')
    const response = await api.get('/manager/waiters')
    const data = response.data.data || response.data
    
    if (Array.isArray(data)) {
      waiters.value = data
      console.log('[Modal] PART 1:  Loaded', data.length, 'waiters')
    }
  } catch (err: any) {
    error.value = 'Failed to load waiters: ' + err.message
    waiters.value = []
  }
}
```

### Expected Response
```json
{
  "data": [
    {
      "id": 1,
      "user_id": 1,
      "user": {
        "id": 1,
        "name": "Michael Brown",
        "email": "michael@hotel.com"
      },
      "employment_type": "full_time",
      "status": "active",
      "experience_level": "junior",
      "section": "dining"
    },
    // ... more waiters
  ]
}
```

### What stores the data
- `waiters.value = data` → array of waiter objects
- Used by waiter dropdown template

### Template Usage
```vue
<select v-model="selectedWaiter">
  <option
    v-for="waiter in waiters"
    :key="waiter.id"
    :value="waiter.id"
  >
    {{ waiter.user?.name }} - {{ waiter.employment_type }}
  </option>
</select>
```

### Important Notes
- Waiter **ID is INTEGER** (database auto-increment)
- Stores full user object with name, email
- Used to display selected waiter card
- Example ID: `1`, `2`, `5` (not UUID)

---

## PART 2: Load Shifts

### API Call
```
GET /manager/shifts?status=active
```

### Code
```typescript
const loadShifts = async () => {
  try {
    console.log('[Modal] PART 2: Loading shifts...')
    const response = await api.get('/manager/shifts', {
      params: { status: 'active' }
    })
    const data = response.data.data || response.data
    
    if (Array.isArray(data)) {
      shifts.value = data
      console.log('[Modal] PART 2:  Loaded', data.length, 'shifts')
    }
  } catch (err: any) {
    console.warn('[Modal] PART 2: Warning:', err.message)
    shifts.value = []
  }
}
```

### Expected Response
```json
{
  "data": [
    {
      "id": "550e8400-e29b-41d4-a716-446655440001",
      "name": "Morning",
      "start_time": "06:00",
      "end_time": "14:00",
      "status": "active"
    },
    {
      "id": "550e8400-e29b-41d4-a716-446655440002",
      "name": "Afternoon",
      "start_time": "14:00",
      "end_time": "22:00",
      "status": "active"
    },
    {
      "id": "550e8400-e29b-41d4-a716-446655440003",
      "name": "Evening",
      "start_time": "17:00",
      "end_time": "23:00",
      "status": "active"
    },
    {
      "id": "550e8400-e29b-41d4-a716-446655440004",
      "name": "Night",
      "start_time": "22:00",
      "end_time": "06:00",
      "status": "active"
    }
  ]
}
```

### What stores the data
- `shifts.value = data` → array of shift objects
- Each shift has UUID ID (from database)
- Used by shift dropdown template

### Template Usage
```vue
<select v-model="selectedShift">
  <option
    v-for="shift in shifts"
    :key="shift.id"
    :value="shift.id"
  >
    {{ shift.name }} ({{ shift.start_time }} - {{ shift.end_time }})
  </option>
</select>
```

### Important Notes
- Shift **ID is UUID** (UUID primary key in database)
- Shifts should show: Morning, Afternoon, Evening, Night (4 total)
- If empty, check: 
  1. Shifts table seeded: `php artisan db:seed --class=HotelShiftSeeder`
  2. Shifts are active: `SELECT * FROM hotel_shifts WHERE status='active'`
- This is **non-critical** - if fails, shows "No shifts available"

---

## PART 3: Send Assignment to Backend

### When it happens
- User selects waiter (PART 1 result)
- User selects shift (PART 2 result)
- User selects priority (radio buttons: primary/secondary/backup)
- User clicks "Assign Staff" button

### API Call
```
POST /manager/floors/assignments
```

### Payload Structure
```json
{
  "assignments": [
    {
      "waiter_id": 5,
      "floor_id": "550e8400-e29b-41d4-a716-446655440099",
      "shift_id": "550e8400-e29b-41d4-a716-446655440001",
      "assignment_date": "2026-07-27",
      "priority": "primary"
    }
  ]
}
```

### Data Type Requirements (CRITICAL)

| Field | Type | Example | Source |
|-------|------|---------|--------|
| `waiter_id` | INTEGER | `5` | From waiter dropdown (Part 1) |
| `floor_id` | UUID STRING | `"550e...99"` | `props.floorId` (passed from parent) |
| `shift_id` | UUID STRING | `"550e...01"` | From shift dropdown (Part 2) |
| `assignment_date` | DATE STRING | `"2026-07-27"` | Today's date in Y-m-d format |
| `priority` | ENUM STRING | `"primary"` | Radio button selection |

### Code
```typescript
const handleAssign = async () => {
  // 1. Get waiter object
  const waiterObj = waiters.value.find(w => String(w.id) === selectedWaiter.value)
  
  // 2. Get shift object
  const shiftObj = shifts.value.find(s => s.id === selectedShift.value)
  
  // 3. Convert waiter_id to INTEGER
  const waiter_id = typeof waiterObj.id === 'number' 
    ? waiterObj.id 
    : parseInt(String(waiterObj.id))
  
  // 4. Build payload
  const assignmentData = {
    assignments: [{
      waiter_id: waiter_id,           // INTEGER
      floor_id: props.floorId,        // UUID STRING
      shift_id: selectedShift.value,  // UUID STRING
      assignment_date: new Date().toISOString().split('T')[0],  // Y-m-d
      priority: selectedPriority.value,  // ENUM
    }]
  }
  
  // 5. Send to backend
  const response = await api.post('/manager/floors/assignments', assignmentData)
  
  // 6. Check success
  if (response.status === 201 || response.status === 200) {
    successMessage.value = `Assignment successful!`
  }
}
```

### Console output to expect
```
[Modal] PART 3: Building assignment data...
[Modal] PART 3: Waiter ID: 5 Type: number
[Modal] PART 3: Waiter Object: {id: 5, user_id: 1, user: {...}, ...}
[Modal] PART 3: Shift ID: 550e8400-... Type: string
[Modal] PART 3: Shift Object: {id: "550e...", name: "Morning", ...}
[Modal] PART 3:  Assignment payload: {...}
[Modal] PART 3: Sending to POST /manager/floors/assignments
[Modal] PART 3:  API Response: {...}
```

### Error handling
If validation fails (422):
```json
{
  "errors": {
    "assignments.0.waiter_id": ["The selected waiter does not exist"],
    "assignments.0.shift_id": ["The selected shift does not exist"],
    "assignments.0.floor_id": ["The selected floor does not exist"]
  }
}
```

Modal displays: "Error: The selected waiter does not exist"

---

## PART 4: Backend Processing (Not in Modal Code)

### What backend does
1. **Receives POST** `/manager/floors/assignments`
2. **Validates** using `AssignFloorRequest`
3. **Checks** all foreign keys exist
4. **Creates** `WaiterFloorAssignment` record
5. **Returns** 201 Created + assignment object

### Backend Route
```php
Route::post('/', [ManagerFloorAssignmentController::class, 'store']);
```

### Backend Validation
```php
'assignments.*.waiter_id' => ['required', 'integer', 'exists:waiters,id'],
'assignments.*.floor_id' => ['required', 'uuid', 'exists:hotel_floors,id'],
'assignments.*.shift_id' => ['required', 'uuid', 'exists:hotel_shifts,id'],
'assignments.*.assignment_date' => ['required', 'date', 'after_or_equal:today'],
'assignments.*.priority' => ['required', 'in:primary,secondary,backup'],
```

### Success Response
```json
{
  "success": true,
  "message": "1 assignment(s) created/updated successfully",
  "data": [
    {
      "id": "uuid-of-assignment",
      "waiter_id": 5,
      "floor_id": "uuid-of-floor",
      "shift_id": "uuid-of-shift",
      "assignment_date": "2026-07-27",
      "priority": "primary",
      "created_at": "2026-07-27 12:34:56"
    }
  ]
}
```

---

## Testing Checklist

###  PART 0: Initialization
- [ ] Click "Add Staff" button on any floor
- [ ] Modal opens
- [ ] Loading spinner appears briefly

###  PART 1: Load Waiters
- [ ] After loading, waiter dropdown shows names (e.g., "Michael Brown - Full time")
- [ ] Count: should show ~18 waiters
- [ ] Console shows: `[Modal] PART 1:  Loaded 18 waiters`

###  PART 2: Load Shifts
- [ ] After loading, shift dropdown shows 4 shifts
- [ ] Options: "Morning (06:00 - 14:00)", "Afternoon (14:00 - 22:00)", etc.
- [ ] Console shows: `[Modal] PART 2:  Loaded 4 shifts`

###  PART 3: Send Assignment
- [ ] Select waiter from dropdown
- [ ] Selected waiter card appears showing details
- [ ] Select shift from dropdown
- [ ] Select priority (primary/secondary/backup)
- [ ] Click "Assign Staff"
- [ ] Console shows: `[Modal] PART 3:  API Response: {...}`
- [ ] Success message appears: "Michael Brown assigned successfully!"
- [ ] Form clears (dropdowns reset)
- [ ] No 422 errors

###  PART 4: Backend Processing
- [ ] Assignment appears on floor card
- [ ] Shows: waiter name, shift, priority badge
- [ ] Refresh page (Ctrl+R)
- [ ] Assignment STILL VISIBLE (persisted from database)

---

## Debugging Guide

### Problem: Waiter dropdown empty
**Check console:**
```
[Modal] PART 1: ❌ Data not array: {...}
```
**Solution**: Ensure backend returns array in `response.data.data`

### Problem: Shift dropdown empty
**Check console:**
```
[Modal] PART 2: Warning: 404 Not Found
```
**Solutions**:
1. Verify shifts seeded: `php artisan db:seed --class=HotelShiftSeeder`
2. Check shifts exist: `SELECT * FROM hotel_shifts`
3. Restart backend: `php artisan serve`

### Problem: 422 on submit
**Check console:**
```
[Modal] PART 3: ❌ Error: {response: {data: {errors: {...}}}}
```
**Solutions**:
1. Check waiter_id is INTEGER
2. Check shift_id is UUID STRING
3. Check floor_id is UUID STRING
4. Verify all entities exist in database

### Problem: Assignment doesn't persist after refresh
**Check**: FloorAssignment page, not modal
**Verify**: `GET /manager/floors/assignments/today` returns data

---

## Quick Reference

| Part | Function | Endpoint | Data Type |
|------|----------|----------|-----------|
| 0 | Init | - | - |
| 1 | loadWaiters | GET /manager/waiters | Array of waiters |
| 2 | loadShifts | GET /manager/shifts | Array of shifts (UUIDs) |
| 3 | handleAssign | POST /manager/floors/assignments | Assignment payload |
| 4 | Backend | (not in modal) | Saves to database |

---

**All parts must work for complete flow to succeed!**

Test each part in order using the console logging.

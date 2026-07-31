# Floor Creation - Complete Flow Diagram

**Visual Flowchart of the Floor Creation Process**

---

## User Journey

```
┌─────────────────────────────────────────────────────────────────┐
│                                                                 │
│  Manager Dashboard                                              │
│  ├─ Click "Assign Floors" in sidebar                          │
│  └─ → FloorAssignment page loads                              │
│                                                                 │
└──────────────────────────┬──────────────────────────────────────┘
                           │
                           ↓
┌─────────────────────────────────────────────────────────────────┐
│                                                                 │
│  Floor Assignment Page                                          │
│  ├─ Displays all floors in grid (1-5 seeded)                  │
│  ├─ Stats: Active Floors, Wait Staff, Available Waiters       │
│  └─ Click "Add New Floor" button                              │
│                                                                 │
└──────────────────────────┬──────────────────────────────────────┘
                           │
                           ↓
┌─────────────────────────────────────────────────────────────────┐
│                                                                 │
│  Add Floor Page (AddFloor.vue)                                 │
│                                                                 │
│  ┌─────────────────────────────────────────────────────────┐  │
│  │  FORM SECTION                                            │  │
│  ├─────────────────────────────────────────────────────────┤  │
│  │                                                           │  │
│  │  Floor Number: [_____]  →  On blur: Check uniqueness   │  │
│  │  Zone Name:    [_____]  →  Show validation errors      │  │
│  │  Description:  [_____]  →  Optional field              │  │
│  │                                                           │  │
│  │  [Cancel] [Create Floor]  ← Submit button enabled only │  │
│  │                             if form is valid            │  │
│  │                                                           │  │
│  └─────────────────────────────────────────────────────────┘  │
│                                                                 │
│  ┌─────────────────────────────────────────────────────────┐  │
│  │  STATS SECTION (Right side)                             │  │
│  ├─────────────────────────────────────────────────────────┤  │
│  │                                                           │  │
│  │  Active Floors:    [6 / 15]                             │  │
│  │  Wait Staff Pool:  [42]                                 │  │
│  │  Available Waiters:[18]                                 │  │
│  │                                                           │  │
│  │  Floor Map Preview                                       │  │
│  │  [Auto-generating...]                                    │  │
│  │                                                           │  │
│  └─────────────────────────────────────────────────────────┘  │
│                                                                 │
└──────────────────────────┬──────────────────────────────────────┘
                           │
              ┌────────────┴─────────────┐
              │                          │
              ↓                          ↓
    ┌─────────────────┐      ┌──────────────────┐
    │  USER FILLS     │      │  VALIDATION      │
    │  FORM & SUBMITS │      │  HAPPENS         │
    └────────┬────────┘      └──────────────────┘
             │
             ↓
```

---

## Form Validation Flow

```
User enters floor_number and presses TAB
            │
            ↓
    handleFloorNumberBlur() triggered
            │
            ├─ Call: checkFloorNumberUniqueness()
            │         │
            │         ├─ Validate format (1-100)
            │         │
            │         ├─ Call backend: /api/manager/floors?search=6
            │         │
            │         ├─ Backend checks: SELECT * FROM hotel_floors WHERE floor_number = 6
            │         │
            │         └─ Response: { unique: true }
            │
            ├─ IF unique: Show "✓ Floor number available"
            │
            └─ IF duplicate: Show "The floor number has already been taken"
```

---

## Form Submission Flow

```
User clicks "Create Floor"
            │
            ↓
    validateAll() checks:
    ├─ Floor number: numeric(1-100) ✓
    ├─ Zone name: 3-100 chars ✓
    └─ Description: max 500 chars ✓
            │
            ├─ IF ANY ERROR: Stop, show error below field
            │
            └─ IF ALL VALID: Continue
                    │
                    ↓
            Button shows "Creating Floor..."
            Button becomes disabled
                    │
                    ↓
            Call: addFloorStore.createFloor()
                    │
                    ├─ Prepare data object:
                    │  {
                    │    floor_number: 6,
                    │    name: "Premium Level",
                    │    description: "..."
                    │  }
                    │
                    └─ Call: floorManagementService.createFloor()
                            │
                            ├─ Add auth token to header
                            │
                            └─ POST /api/manager/floors
                                    │
                                    ↓
```

---

## Backend Processing

```
Request received: POST /api/manager/floors
            │
            ├─ Check authentication (Sanctum token) ✓
            │
            ├─ Check authorization (role:manager) ✓
            │
            ↓
FloorManagementController@store()
            │
            ├─ Validate request:
            │  ├─ floor_number: required | integer | unique
            │  ├─ name: required | string | max:100 | unique
            │  └─ description: nullable | string | max:500
            │
            ├─ IF VALIDATION FAILS:
            │  ├─ Return 422 (Validation Error)
            │  └─ Include field errors:
            │     {
            │       "errors": {
            │         "floor_number": ["The floor number has already been taken."]
            │       }
            │     }
            │
            └─ IF VALIDATION PASSES:
                    │
                    ├─ Generate UUID for id
                    │
                    ├─ Create HotelFloor record:
                    │  INSERT INTO hotel_floors
                    │  (id, floor_number, name, description, is_active, created_at, updated_at)
                    │  VALUES (...)
                    │
                    ├─ Log success:
                    │  "Floor created successfully"
                    │
                    └─ Return 201 (Created)
                       {
                         "success": true,
                         "message": "Floor created successfully",
                         "data": {
                           "id": "uuid-here",
                           "floor_number": 6,
                           "name": "Premium Level",
                           ...
                         }
                       }
```

---

## Response Handling

```
Response received by frontend
            │
            ├─ IF Status 201 (Success):
            │  │
            │  ├─ Set success message: "Floor created successfully!"
            │  ├─ Reset form to empty
            │  ├─ Show green success alert
            │  │
            │  └─ Wait 2 seconds for user to read message
            │     │
            │     ├─ Then: router.push('/manager/floor-assignment')
            │     │
            │     └─ Redirect to floor assignments page
            │        (which now includes the new floor)
            │
            └─ IF Status 422 (Validation Error):
               │
               ├─ Extract error messages
               ├─ Display error for each field
               ├─ Show red error alert
               │
               └─ Keep form data intact for user to correct
                  (Button re-enabled, form still has data)
```

---

## Error Handling Paths

```
Different error scenarios:

PATH 1: Duplicate Floor Number
    User enters 1 (already exists)
    │
    ├─ On blur: Backend returns { unique: false }
    │
    ├─ Show: "✓ Floor number available" → ✕ Red error shows
    │
    ├─ Button disabled: "Create Floor" grayed out
    │
    └─ User must enter different number (6, 7, 8...)

PATH 2: Duplicate Zone Name
    User enters "Ground Floor" (already exists)
    │
    ├─ Submit attempt
    │
    ├─ Backend validation fails
    │
    ├─ Returns 422 with error:
    │  { "name": ["The name has already been taken."] }
    │
    ├─ Show: Red error below Zone Name field
    │
    └─ User must enter different name

PATH 3: Invalid Floor Number Format
    User enters "abc" or "-5"
    │
    ├─ Client-side validation on blur
    │
    ├─ Show: "Floor number must be a positive number"
    │
    └─ Form remains invalid, can't submit

PATH 4: Network Error
    Connection lost during submission
    │
    ├─ Catch error in try-catch
    │
    ├─ Show: Red alert with error message
    │
    └─ User can retry after checking connection

PATH 5: Server Error (500)
    Unexpected backend error
    │
    ├─ Return 500 status
    │
    ├─ Show: "Failed to create floor: [error details]"
    │
    ├─ Log to server: storage/logs/laravel.log
    │
    └─ User should contact support
```

---

## State Management Updates

```
During floor creation, store state changes:

INITIAL STATE:
{
  formData: {
    floor_number: '',
    name: '',
    description: ''
  },
  submitting: false,
  error: null,
  success: null,
  validationErrors: {},
  floorNumberUnique: true
}

↓

AFTER BLUR (checking uniqueness):
{
  ...(same)
  checkingUniqueness: true  ← Set to true
}

↓

AFTER BLUR COMPLETE:
{
  ...(same)
  floorNumberUnique: true   ← Updated
  checkingUniqueness: false ← Back to false
}

↓

AFTER SUBMIT:
{
  ...(same)
  submitting: true  ← Form locked, button disabled
}

↓

AFTER SUCCESS:
{
  formData: { floor_number: '', name: '', description: '' },  ← Reset!
  submitting: false,
  error: null,
  success: 'Floor created successfully!',  ← Success message
  validationErrors: {},
  floorNumberUnique: true
}

↓ (2 second delay)

Then redirect to /manager/floor-assignment
```

---

## Component Interaction Diagram

```
┌──────────────────────┐
│   AddFloor.vue       │
│  (Template + Logic)  │
└──────────┬───────────┘
           │
           │ uses
           ↓
┌──────────────────────┐
│ useAddFloorStore()   │
│  (State Management)  │
└──────────┬───────────┘
           │
           │ calls
           ↓
┌──────────────────────────────────┐
│ floorManagementService           │
│  (API Integration)               │
└──────────┬───────────────────────┘
           │
           │ sends HTTP request
           ↓
┌────────────────────────────────────────────────┐
│ POST /api/manager/floors                       │
│  (Laravel Backend)                             │
│                                                │
│  FloorManagementController@store()             │
└──────────┬────────────────────────────────────┘
           │
           │ creates & validates
           ↓
┌────────────────────────────────────────────────┐
│ HotelFloor Model                               │
│  (Database Layer)                              │
│                                                │
│  INSERT INTO hotel_floors                      │
└──────────┬────────────────────────────────────┘
           │
           │ returns JSON response
           ↓
┌──────────────────────────────────────────────────┐
│ floorManagementService (receives response)      │
└──────────┬───────────────────────────────────────┘
           │
           │ updates state
           ↓
┌──────────────────────────────────────────────────┐
│ useAddFloorStore() (updates computed properties) │
└──────────┬───────────────────────────────────────┘
           │
           │ re-renders UI
           ↓
┌──────────────────────────────────────────────────┐
│ AddFloor.vue                                     │
│  - Shows success message                         │
│  - Disables form                                │
│  - Redirects after 2 seconds                    │
└──────────────────────────────────────────────────┘
           │
           ↓
┌──────────────────────────────────────────────────┐
│ FloorAssignment.vue                             │
│  - Shows all floors (including new one)         │
│  - Updated stats                                │
└──────────────────────────────────────────────────┘
```

---

## Data Model

```
┌──────────────────────────────────┐
│      hotel_floors table          │
├──────────────────────────────────┤
│ id (UUID) ........... PRIMARY KEY│
│ floor_number (INT) .. UNIQUE     │
│ name (VARCHAR)...... UNIQUE      │
│ description (TEXT).. nullable    │
│ is_active (BOOL).... default 1   │
│ total_rooms (INT)... default 0   │
│ created_at ......... timestamp   │
│ updated_at ......... timestamp   │
└──────────────────────────────────┘
            │
            │ 1 --- N
            ↓
┌──────────────────────────────────┐
│ waiter_floor_assignments table   │
├──────────────────────────────────┤
│ id ..................... PRIMARY │
│ floor_id (FK) .......... refs id │
│ waiter_id (FK) ......... refs id │
│ shift_id (FK) .......... refs id │
│ priority ............... enum    │
│ status ................. enum    │
│ assignment_date ........ date    │
└──────────────────────────────────┘
```

---

## Timeline - Typical User Session

```
Time    Action                          UI State
────────────────────────────────────────────────────────────
00:00s  Click "Add New Floor"           Page loads, form visible
00:05s  Enter floor_number: 6           Input accepted
00:10s  Press TAB (blur event)          Checking uniqueness...
00:15s  Backend responds                "✓ Floor number available"
00:20s  Enter name: "Premium Level"     Input accepted
00:25s  Enter description (optional)    Input accepted
00:30s  Click "Create Floor"            Button: "Creating Floor..."
00:35s  Backend processing              Server-side validation
00:40s  Response received (201)         "Floor created successfully!"
00:42s  Success message shown           Green alert visible
01:45s  Auto-redirect begins            Page transition
02:00s  FloorAssignment page loads      New floor visible in grid
```

---

## Key Transition Points

```
1. USER INPUT ──→ VALIDATION ──→ SUBMISSION

2. SUBMISSION ──→ SERVER PROCESSING ──→ RESPONSE

3. RESPONSE (Success) ──→ FORM RESET ──→ REDIRECT

4. RESPONSE (Error) ──→ ERROR DISPLAY ──→ FORM RETENTION

5. FINAL STATE ──→ FLOOR AVAILABLE ──→ ASSIGNMENTS PAGE
```

---

## Critical Decision Points

```
┌─────────────────────────┐
│ Submit Form?            │
└────┬────────────────────┘
     │
     ├─ NO: Form invalid
     │      └─ Button disabled
     │         Show errors below fields
     │
     └─ YES: Form valid
            └─ Submit to backend
               ├─ Success (201)
               │  ├─ Show "created!"
               │  ├─ Reset form
               │  └─ Redirect (2s)
               │
               └─ Error (422)
                  ├─ Show field errors
                  ├─ Keep form data
                  └─ Enable button
```

---

## Success Path (Happy Path)

```
User fills form correctly
        ↓
All validations pass
        ↓
Submit to backend
        ↓
Backend creates floor
        ↓
Response 201 received
        ↓
Success message displayed
        ↓
2 second wait
        ↓
Redirect to assignments
        ↓
New floor visible in list
        ↓
 SUCCESS COMPLETE
```

---

## Error Path

```
User submits with duplicate floor
        ↓
Backend validation fails
        ↓
Response 422 with errors
        ↓
Frontend catches error
        ↓
Error message displayed
        ↓
Form data preserved
        ↓
Button re-enabled
        ↓
User can correct & retry
        ↓
✓ Error handled gracefully
```

---

This flowchart shows the complete journey of floor creation from user action through to successful completion or error handling.

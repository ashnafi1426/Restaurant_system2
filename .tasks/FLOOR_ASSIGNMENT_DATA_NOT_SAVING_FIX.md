# Fix: Floor Assignment Data Not Saving to Database

## Problem Statement
- Frontend shows success message for waiter floor assignment
- Data appears to display in frontend UI
- **BUT**: Data is NOT being saved to `waiter_floor_assignments` database table
- Assignment records don't persist after page refresh

---

## Root Cause Analysis

### Issue 1: Database Constraint Violation
**Problem:** Status field default value mismatch
```php
// Migration sets default:
$table->enum('status', ['assigned', 'active', 'completed', 'cancelled'])->default('assigned');

// But controller tries to set:
'status' => 'active'
```

If there's a NOT NULL constraint and the migration default is 'assigned', but you try to save with 'active', this could cause issues.

### Issue 2: Foreign Key Constraint Failed
**Problem:** Waiter IDs, Floor IDs, or Shift IDs might not exist in related tables
```
FOREIGN KEY constraint failed:
- waiter_id references waiters.id (must exist)
- floor_id references hotel_floors.id (must exist)
- shift_id references hotel_shifts.id (must exist)
```

**Most Likely Cause:** The frontend is sending `waiter_id` as a STRING but the database expects BIGINT UNSIGNED INTEGER.

### Issue 3: UUID Mismatch
**Problem:** Floor IDs and Shift IDs must be valid UUIDs
```
If floor_id or shift_id is not a valid UUID format:
- Migration expects: uuid type
- But receives: invalid UUID or string
- Result: Constraint violation
```

### Issue 4: Assignment Date Validation
**Problem:** Assignment date might be in the past
```php
'assignments.*.assignment_date.after_or_equal' => 'Assignment date cannot be in the past'
```
If you're assigning for today and it's already past midnight UTC, it fails.

---

## Deep Diagnostic Steps

### Step 1: Check Server Logs
```bash
cd /path/to/server
tail -f storage/logs/laravel.log
```

Look for errors like:
```
SQLSTATE[23000]: Integrity constraint violation: 1452 Cannot add or update a child row
[42601] ERROR: duplicate key value violates unique constraint
```

### Step 2: Verify Table Structure in Database
```sql
-- Check actual table structure
DESCRIBE waiter_floor_assignments;

-- Output should show:
-- Column              Type                              Null    Key
-- id                  CHAR(36)                          NO      PRI
-- waiter_id           BIGINT UNSIGNED                   NO      MUL
-- floor_id            CHAR(36)                          NO      MUL
-- shift_id            CHAR(36)                          NO      MUL
-- assignment_date     DATE                              NO
-- status              ENUM('assigned','active'...)      NO
-- priority            ENUM('primary','secondary'...)    NO
-- assigned_by         CHAR(36)                          YES     
-- created_at          TIMESTAMP                         YES
-- updated_at          TIMESTAMP                         YES
```

### Step 3: Check Related Table Data Exists
```sql
-- Verify waiter exists
SELECT id, first_name, status FROM waiters WHERE id = {waiter_id_being_assigned};

-- Verify floor exists
SELECT id, name, floor_number FROM hotel_floors WHERE id = '{floor_id}';

-- Verify shift exists
SELECT id, name, start_time, end_time FROM hotel_shifts WHERE id = '{shift_id}';
```

### Step 4: Check Request Validation
Add logging to verify request passes validation:
```php
// In FloorAssignmentController@store
\Log::info('[DEBUG] Request validated successfully', [
    'assignments_count' => count($assignments),
    'first_assignment' => $assignments[0] ?? null,
    'waiter_id_type' => gettype($assignments[0]['waiter_id'] ?? null),
    'floor_id_type' => gettype($assignments[0]['floor_id'] ?? null),
]);
```

### Step 5: Manually Test API with Correct Data Types
```bash
curl -X POST http://localhost:8000/api/manager/floors/assignments \
  -H "Authorization: Bearer {token}" \
  -H "Content-Type: application/json" \
  -d '{
    "assignments": [{
      "waiter_id": 1,
      "floor_id": "550e8400-e29b-41d4-a716-446655440000",
      "shift_id": "550e8400-e29b-41d4-a716-446655440001",
      "assignment_date": "2026-07-28",
      "priority": "primary"
    }]
  }'
```

---

## Solution: Data Type Fixes

### FIX #1: Frontend Service - Ensure Correct Data Types
**File:** `src/services/manager/floorAssignmentService.ts`

```typescript
async assignWaitersToFloors(assignments: Array<{
  waiter_id: string | number  // ← CAN BE STRING
  floor_id: string
  shift_id: string
  assignment_date: string
  priority: 'primary' | 'secondary' | 'backup'
}>): Promise<FloorAssignment[]> {
  try {
    // Convert waiter_id to NUMBER if it's a string
    const normalizedAssignments = assignments.map(a => ({
      ...a,
      waiter_id: Number(a.waiter_id), // ← FORCE TO NUMBER
    }))
    
    console.log('[FloorAssignmentService] Normalized assignments:', normalizedAssignments)
    
    const response = await api.post('/manager/floors/assignments', {
      assignments: normalizedAssignments,
    })
    
    return response.data.data || []
  } catch (error: any) {
    console.error('[FloorAssignmentService] Error:', error.response?.data)
    throw error
  }
}
```

### FIX #2: Frontend Modal - Pass Correct Waiter ID
**File:** `src/components/manager/AddStaffToFloorModal.vue`

```typescript
const handleAssign = async () => {
  try {
    const waiterObj = waiters.value.find(w => 
      w.id === Number(selectedWaiter.value) || 
      String(w.id) === String(selectedWaiter.value)
    )
    
    if (!waiterObj) {
      error.value = `Waiter not found`
      return
    }
    
    //  ENSURE WAITER_ID IS A NUMBER
    const waiter_id = Number(waiterObj.id)
    
    if (!Number.isInteger(waiter_id)) {
      error.value = 'Invalid waiter ID'
      return
    }
    
    const assignmentData = {
      assignments: [{
        waiter_id: waiter_id,  // ← NOW A NUMBER
        floor_id: props.floorId,
        shift_id: selectedShift.value,
        assignment_date: new Date().toISOString().split('T')[0],
        priority: selectedPriority.value,
      }]
    }
    
    console.log('[Modal] Final payload:', JSON.stringify(assignmentData))
    
    const response = await api.post('/manager/floors/assignments', assignmentData)
    
    if (response.status === 201 || response.data.success) {
      successMessage.value = 'Staff assigned successfully!'
      emit('assigned')
    }
  } catch (err: any) {
    error.value = err.response?.data?.message || err.message
  }
}
```

### FIX #3: Backend Validation - Better Error Messages
**File:** `server/app/Http/Requests/Manager/AssignFloorRequest.php`

```php
protected function prepareForValidation(): void
{
    if ($this->has('assignments')) {
        $assignments = $this->assignments;
        
        foreach ($assignments as &$assignment) {
            //  CONVERT WAITER_ID TO INTEGER
            if (isset($assignment['waiter_id'])) {
                $assignment['waiter_id'] = (int) $assignment['waiter_id'];
            }
            
            //  STANDARDIZE PRIORITY
            if (isset($assignment['priority'])) {
                $assignment['priority'] = strtolower($assignment['priority']);
            }
            
            //  LOG FOR DEBUGGING
            \Log::info('[AssignFloorRequest] Prepared assignment', [
                'waiter_id' => $assignment['waiter_id'],
                'waiter_id_type' => gettype($assignment['waiter_id']),
                'floor_id' => $assignment['floor_id'] ?? null,
                'shift_id' => $assignment['shift_id'] ?? null,
            ]);
        }
        
        $this->merge(['assignments' => $assignments]);
    }
}
```

### FIX #4: Backend Controller - Logging & Error Handling
**File:** `server/app/Http/Controllers/Api/Manager/FloorAssignmentController.php`

```php
public function store(AssignFloorRequest $request): JsonResponse
{
    try {
        DB::beginTransaction();

        $assignments = $request->getAssignments();
        $createdAssignments = [];
        $errors = [];

        \Log::info('[FloorAssignmentController] ===== ASSIGNMENT PROCESS STARTED =====');
        \Log::info('[FloorAssignmentController] Total assignments to process: ' . count($assignments));

        foreach ($assignments as $index => $assignment) {
            try {
                \Log::info('[FloorAssignmentController] ===== PROCESSING ASSIGNMENT ' . ($index + 1) . ' =====', [
                    'waiter_id' => $assignment['waiter_id'],
                    'waiter_id_type' => gettype($assignment['waiter_id']),
                    'floor_id' => $assignment['floor_id'],
                    'shift_id' => $assignment['shift_id'],
                    'assignment_date' => $assignment['assignment_date'],
                    'priority' => $assignment['priority'],
                ]);

                // VERIFY IDs EXIST BEFORE PROCESSING
                $waiter = \App\Models\Waiter::find($assignment['waiter_id']);
                if (!$waiter) {
                    throw new \Exception('Waiter not found: ' . $assignment['waiter_id']);
                }
                \Log::info('[FloorAssignmentController] ✓ Waiter found: ' . $waiter->id);

                $floor = \App\Models\HotelFloor::find($assignment['floor_id']);
                if (!$floor) {
                    throw new \Exception('Floor not found: ' . $assignment['floor_id']);
                }
                \Log::info('[FloorAssignmentController] ✓ Floor found: ' . $floor->id);

                $shift = \App\Models\HotelShift::find($assignment['shift_id']);
                if (!$shift) {
                    throw new \Exception('Shift not found: ' . $assignment['shift_id']);
                }
                \Log::info('[FloorAssignmentController] ✓ Shift found: ' . $shift->id);

                // CHECK IF ALREADY EXISTS
                $existing = WaiterFloorAssignment::where([
                    'waiter_id' => $assignment['waiter_id'],
                    'floor_id' => $assignment['floor_id'],
                    'shift_id' => $assignment['shift_id'],
                    'assignment_date' => $assignment['assignment_date'],
                ])->first();

                if ($existing) {
                    \Log::info('[FloorAssignmentController] Assignment already exists, updating...');
                    $existing->update([
                        'priority' => $assignment['priority'],
                        'assigned_by' => auth()->id(),
                    ]);
                    $createdAssignments[] = $existing;
                    \Log::info('[FloorAssignmentController] ✓ Assignment updated: ' . $existing->id);
                } else {
                    \Log::info('[FloorAssignmentController] Creating new assignment...');
                    $newAssignment = WaiterFloorAssignment::create([
                        'id' => \Illuminate\Support\Str::uuid(),
                        'waiter_id' => $assignment['waiter_id'],
                        'floor_id' => $assignment['floor_id'],
                        'shift_id' => $assignment['shift_id'],
                        'assignment_date' => $assignment['assignment_date'],
                        'status' => 'active',
                        'priority' => $assignment['priority'],
                        'assigned_by' => auth()->id(),
                    ]);
                    $createdAssignments[] = $newAssignment;
                    \Log::info('[FloorAssignmentController] ✓ Assignment created: ' . $newAssignment->id);
                }
            } catch (\Exception $e) {
                \Log::error('[FloorAssignmentController] Assignment creation failed', [
                    'error' => $e->getMessage(),
                    'assignment' => $assignment,
                    'trace' => $e->getTraceAsString(),
                ]);
                $errors[] = [
                    'assignment' => $assignment,
                    'error' => $e->getMessage(),
                ];
            }
        }

        DB::commit();

        \Log::info('[FloorAssignmentController] ===== PROCESS COMPLETED =====', [
            'created_count' => count($createdAssignments),
            'error_count' => count($errors),
        ]);

        // VERIFY DATA WAS SAVED
        $countInDb = WaiterFloorAssignment::count();
        \Log::info('[FloorAssignmentController] Total records in database: ' . $countInDb);

        $response = [
            'success' => count($errors) === 0,
            'message' => count($createdAssignments) . ' assignment(s) created/updated',
            'data' => WaiterFloorAssignmentResource::collection(
                WaiterFloorAssignment::whereIn('id', collect($createdAssignments)->pluck('id'))
                    ->with('waiter', 'floor', 'shift')
                    ->get()
            ),
        ];

        if (!empty($errors)) {
            $response['errors'] = $errors;
        }

        return response()->json($response, 201);

    } catch (\Exception $e) {
        DB::rollBack();

        \Log::error('[FloorAssignmentController] ===== PROCESS FAILED =====', [
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString(),
        ]);

        return response()->json([
            'success' => false,
            'message' => 'Failed to assign floors',
            'error' => $e->getMessage(),
        ], 500);
    }
}
```

---

## Verification Checklist

After applying fixes:

- [ ] Frontend sends `waiter_id` as **number** not string
- [ ] Frontend sends `floor_id` as valid **UUID** string
- [ ] Frontend sends `shift_id` as valid **UUID** string
- [ ] Frontend sends `assignment_date` as date string (YYYY-MM-DD)
- [ ] Backend receives valid data
- [ ] Backend validates all foreign keys exist
- [ ] Backend creates UUID for assignment ID
- [ ] Backend inserts record into `waiter_floor_assignments`
- [ ] Database commit succeeds
- [ ] Check logs show "✓ Assignment created"
- [ ] Query database confirms record exists
- [ ] Frontend page refresh shows persisted data

---

## Testing Steps

### Test 1: Check Server Logs
```bash
tail -f storage/logs/laravel.log | grep "FloorAssignmentController"
```

Should show:
```
[2026-07-27 14:30:00] local.INFO: [FloorAssignmentController] ===== ASSIGNMENT PROCESS STARTED =====
[2026-07-27 14:30:00] local.INFO: [FloorAssignmentController] Total assignments to process: 1
[2026-07-27 14:30:00] local.INFO: [FloorAssignmentController] ===== PROCESSING ASSIGNMENT 1 =====
[2026-07-27 14:30:00] local.INFO: [FloorAssignmentController] ✓ Waiter found: 1
[2026-07-27 14:30:00] local.INFO: [FloorAssignmentController] ✓ Floor found: {uuid}
[2026-07-27 14:30:00] local.INFO: [FloorAssignmentController] ✓ Shift found: {uuid}
[2026-07-27 14:30:00] local.INFO: [FloorAssignmentController] Creating new assignment...
[2026-07-27 14:30:00] local.INFO: [FloorAssignmentController] ✓ Assignment created: {uuid}
```

### Test 2: Query Database After Assignment
```sql
SELECT * FROM waiter_floor_assignments 
WHERE assignment_date = CURDATE() 
ORDER BY created_at DESC 
LIMIT 1;
```

Should return the newly created record.

### Test 3: Check Frontend Network Request
1. Open DevTools (F12)
2. Go to Network tab
3. Click "Add Staff" button
4. Check POST request to `/api/manager/floors/assignments`
5. In Request body, verify:
   - `waiter_id` is a number
   - `floor_id` is a UUID string
   - `shift_id` is a UUID string
6. In Response, verify `success: true` and data has `id`

---

## Common Errors & Solutions

| Error | Cause | Solution |
|-------|-------|----------|
| `FOREIGN KEY constraint failed` | Waiter/Floor/Shift doesn't exist | Verify IDs exist in those tables |
| `Duplicate entry` | Assignment already exists for that combo | Check unique constraint |
| `Incorrect INTEGER value` | waiter_id sent as string/UUID | Convert to Number in frontend |
| `Invalid UUID` | floor_id/shift_id not valid UUID | Verify UUID format |
| `Date in past` | assignment_date is before today | Use tomorrow or today |
| `500 error` | DB commit failed | Check server logs for details |

---

## Next Steps

1. Apply FIX #1-4 above
2. Test API with correct data types
3. Check server logs during assignment
4. Query database to verify records exist
5. Refresh frontend page and confirm data persists
6. Document results

---

## Emergency Rollback

If something breaks:
```bash
# Rollback migration
php artisan migrate:rollback --step=1

# Re-run migration
php artisan migrate

# Clear cache
php artisan cache:clear
```

---

**Status:** Ready to implement
**Priority:** HIGH - Data not persisting
**Impact:** Floor assignments not working end-to-end

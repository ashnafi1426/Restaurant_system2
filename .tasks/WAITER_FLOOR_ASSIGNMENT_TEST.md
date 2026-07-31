# Waiter Floor Assignment System - Database & Testing Guide

## Overview
The waiter floor assignment system allows managers to assign waiters to floors with different priority levels (primary, secondary, backup). All assignments are stored in the `waiter_floor_assignments` database table.

## Database Structure

### Table: `waiter_floor_assignments`
```sql
CREATE TABLE waiter_floor_assignments (
    id              UUID PRIMARY KEY
    waiter_id       BIGINT (references waiters.id)
    floor_id        UUID (references hotel_floors.id)
    shift_id        UUID (references hotel_shifts.id)
    assignment_date DATE
    status          ENUM('assigned', 'active', 'completed', 'cancelled')
    priority        ENUM('primary', 'secondary', 'backup')
    assigned_by     UUID (references users.id - manager who assigned)
    created_at      TIMESTAMP
    updated_at      TIMESTAMP
    
    INDEXES:
    - (waiter_id, assignment_date)
    - (floor_id, assignment_date)
    - (shift_id, assignment_date)
    - status
    - priority
    - UNIQUE(floor_id, shift_id, assignment_date, priority)
);
```

## Assignment Data Structure

### Example Assignment as Seen in Database:
```
Floor 1 (Floor ID: 123e4567-e89b-12d3-a456-426614174000)
├─ PRIMARY:   John (Waiter ID: 1) - Shift: Morning (Shift ID: 456e7890-ab12-34cd-ef56-789012345678)
├─ SECONDARY: Abel (Waiter ID: 2) - Shift: Morning
└─ BACKUP:    Henok (Waiter ID: 3) - Shift: Morning

Floor 2
├─ PRIMARY:   Michael (Waiter ID: 4) - Shift: Morning
├─ SECONDARY: Robel (Waiter ID: 5) - Shift: Morning
└─ BACKUP:    Daniel (Waiter ID: 6) - Shift: Morning

Floor 3
├─ PRIMARY:   David (Waiter ID: 7) - Shift: Morning
├─ SECONDARY: Samuel (Waiter ID: 8) - Shift: Morning
└─ BACKUP:    John (Waiter ID: 1) - Shift: Morning

Floor 4
├─ PRIMARY:   Samuel (Waiter ID: 8) - Shift: Morning
├─ SECONDARY: Michael (Waiter ID: 4) - Shift: Morning
└─ BACKUP:    Robel (Waiter ID: 5) - Shift: Morning
```

## Backend API Flow

### 1. Frontend: Add Staff to Floor Modal
**File:** `src/components/manager/AddStaffToFloorModal.vue`

**Flow:**
1. Manager opens FloorAssignment page
2. Manager clicks "Add Staff" button on a floor card
3. Modal opens with form:
   - Select Waiter dropdown (loaded from `/manager/waiters`)
   - Select Shift dropdown (loaded from `/manager/shifts`)
   - Select Priority radio buttons (primary/secondary/backup)
4. Manager submits form

### 2. Backend: Save to Database
**Endpoint:** `POST /api/manager/floors/assignments`

**Controller:** `App\Http\Controllers\Api\Manager\FloorAssignmentController@store`

**Request Payload:**
```json
{
  "assignments": [
    {
      "waiter_id": 1,
      "floor_id": "123e4567-e89b-12d3-a456-426614174000",
      "shift_id": "456e7890-ab12-34cd-ef56-789012345678",
      "assignment_date": "2026-07-27",
      "priority": "primary"
    }
  ]
}
```

**Processing Logic:**
1. Controller validates request (AssignFloorRequest)
2. For each assignment:
   - Check if assignment already exists
   - If exists: UPDATE with new priority
   - If new: CREATE with UUID and status='active'
   - Increment waiter's current_orders if needed
3. All assignments saved in transaction
4. Return 201 with created assignments

**Response Example:**
```json
{
  "success": true,
  "message": "2 assignment(s) created/updated successfully",
  "data": [
    {
      "id": "uuid-1",
      "waiter_id": 1,
      "waiter": {
        "id": 1,
        "user": {
          "name": "John Doe"
        }
      },
      "floor_id": "123e4567-e89b-12d3-a456-426614174000",
      "floor": {
        "name": "Floor 1",
        "floor_number": 1
      },
      "shift_id": "456e7890-ab12-34cd-ef56-789012345678",
      "shift": {
        "name": "Morning Shift",
        "start_time": "06:00",
        "end_time": "14:00"
      },
      "assignment_date": "2026-07-27",
      "status": "active",
      "priority": "primary"
    }
  ]
}
```

### 3. Retrieval Endpoints

**Get Today's Assignments:**
```
GET /api/manager/floors/assignments/today
```

Response includes all assignments for today, grouped by floor with relationships loaded.

**Get All Assignments (with filters):**
```
GET /api/manager/floors/assignments?date=2026-07-27&floor_id=123&waiter_id=1&status=active
```

**Get Assignment Statistics:**
```
GET /api/manager/floors/assignments/stats?date=2026-07-27
```

Returns:
```json
{
  "total_assignments": 12,
  "total_floors": 4,
  "total_waiters": 8,
  "primary_assignments": 4,
  "secondary_assignments": 4,
  "backup_assignments": 4
}
```

## Testing Checklist

###  Test 1: Create Assignment for Floor 1
```
POST /api/manager/floors/assignments
Payload:
{
  "assignments": [
    {
      "waiter_id": 1,          // John
      "floor_id": "floor-1-id",
      "shift_id": "morning-shift-id",
      "assignment_date": "2026-07-27",
      "priority": "primary"
    },
    {
      "waiter_id": 2,          // Abel
      "floor_id": "floor-1-id",
      "shift_id": "morning-shift-id",
      "assignment_date": "2026-07-27",
      "priority": "secondary"
    },
    {
      "waiter_id": 3,          // Henok
      "floor_id": "floor-1-id",
      "shift_id": "morning-shift-id",
      "assignment_date": "2026-07-27",
      "priority": "backup"
    }
  ]
}

Expected Response: 201 Created
- 3 assignments created
- All with status='active'
- priority values correct
```

###  Test 2: Verify Data in Database
```sql
SELECT 
  wfa.id,
  w.id as waiter_id,
  u.first_name,
  hf.name as floor_name,
  hs.name as shift_name,
  wfa.priority,
  wfa.status,
  wfa.assignment_date
FROM waiter_floor_assignments wfa
JOIN waiters w ON wfa.waiter_id = w.id
JOIN users u ON w.user_id = u.id
JOIN hotel_floors hf ON wfa.floor_id = hf.id
JOIN hotel_shifts hs ON wfa.shift_id = hs.id
WHERE wfa.assignment_date = '2026-07-27'
ORDER BY hf.floor_number, wfa.priority;
```

Expected Output:
```
Floor 1:
  John    - Primary   - active
  Abel    - Secondary - active
  Henok   - Backup    - active

Floor 2:
  Michael - Primary   - active
  Robel   - Secondary - active
  Daniel  - Backup    - active
```

###  Test 3: Retrieve Assignments via API
```
GET /api/manager/floors/assignments/today

Expected:
- All assignments for today returned
- Grouped correctly by floor and priority
- Waiter, floor, and shift relationships loaded
- Status shows as 'active' or 'assigned'
```

###  Test 4: Update Assignment Priority
```
PATCH /api/manager/floors/assignments/{assignment-id}
Body: { "priority": "secondary" }

Expected: Assignment updated from primary to secondary
```

###  Test 5: Delete Assignment
```
DELETE /api/manager/floors/assignments/{assignment-id}

Expected: Assignment deleted from database
```

###  Test 6: Statistics
```
GET /api/manager/floors/assignments/stats?date=2026-07-27

Expected:
{
  "total_assignments": 12,
  "total_floors": 4,
  "total_waiters": 8,
  "primary_assignments": 4,
  "secondary_assignments": 4,
  "backup_assignments": 4
}
```

## Frontend Flow

### Manager Dashboard Flow:
1. Manager → FloorAssignment page
2. Page loads today's assignments + all floors
3. Displays 3-column grid with floor cards
4. Each floor card shows assigned waiters with priority badges
5. Manager clicks "Add Staff" button
6. Modal opens with dropdowns for waiter/shift/priority
7. Manager selects values and clicks "Assign Staff"
8. Modal sends POST request to backend
9. Backend saves to database with transaction
10. Modal closes and floor card updates with new waiter
11. Success message displays

### Data Persistence:
- All assignments stored permanently in `waiter_floor_assignments` table
- Unique constraint ensures only one primary, secondary, and backup per floor/shift/date
- Related data accessible through Eloquent relationships:
  - Assignment.waiter (Waiter model)
  - Assignment.floor (HotelFloor model)
  - Assignment.shift (HotelShift model)
  - Assignment.assignedBy (User model - manager)

## How to Run Tests

### Via Laravel Tinker:
```php
// Connect to database and query assignments
php artisan tinker

// Get all assignments
App\Models\WaiterFloorAssignment::with('waiter', 'floor', 'shift')->get();

// Get assignments for specific date
App\Models\WaiterFloorAssignment::whereDate('assignment_date', '2026-07-27')->with('waiter', 'floor', 'shift')->get();

// Get assignments for specific floor
App\Models\WaiterFloorAssignment::where('floor_id', 'floor-uuid')->with('waiter', 'floor', 'shift')->get();

// Check stats
App\Models\WaiterFloorAssignment::where('assignment_date', '2026-07-27')->count();
```

### Via Frontend (Postman/Insomnia):
1. Get auth token via login
2. Set Authorization: Bearer {token}
3. Test each endpoint:
   - POST to create assignments
   - GET to retrieve assignments
   - PATCH to update priority
   - DELETE to remove

### Via Frontend UI:
1. Login as manager
2. Navigate to "Assign Staff to Floors"
3. Click "Add Staff" on any floor
4. Select waiter, shift, priority
5. Click "Assign Staff"
6. Verify success message
7. Refresh page to confirm persistence

## Summary

 Database Table: `waiter_floor_assignments` - CREATED
 API Endpoints: FloorAssignmentController - IMPLEMENTED
 Frontend Modal: AddStaffToFloorModal - IMPLEMENTED
 Data Relationships: Eloquent relationships - CONFIGURED
 Transaction Safety: DB::beginTransaction() - ACTIVE
 Unique Constraints: One primary/secondary/backup per floor/shift/date - ENFORCED

The system is ready for testing. Assignments are properly saved to the database with all required relationships.

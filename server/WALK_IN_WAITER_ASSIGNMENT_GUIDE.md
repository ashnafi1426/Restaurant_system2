# Walk-In (Table-Based) Waiter Assignment System

## Overview

This system extends the waiter assignment functionality to support **walk-in customers** (dine-in service) at restaurant tables, in addition to the existing room service for hotel guests.

---

## How It Works

### **Two Order Types:**

1. **Room Service** (`order_type = 'room_service'`)
   - Guest orders from hotel room via QR code
   - System assigns waiter based on `room → floor → waiter assignment`
   
2. **Walk-In/Dine-In** (`order_type = 'walk_in'`)
   - Customer orders from restaurant table via QR code
   - System assigns waiter based on `table → waiter assignment`

---

## Database Structure

### New Table: `waiter_table_assignments`

Tracks which waiters are assigned to which restaurant tables.

```sql
CREATE TABLE waiter_table_assignments (
    id               CHAR(36) PRIMARY KEY,
    waiter_id        BIGINT UNSIGNED NOT NULL,    -- References waiters.id
    table_id         CHAR(36) NOT NULL,           -- References restaurant_tables.id
    shift_id         CHAR(36) NOT NULL,           -- References hotel_shifts.id
    assignment_date  DATE NOT NULL,               -- Which day
    priority         ENUM('primary', 'secondary', 'backup') DEFAULT 'primary',
    status           ENUM('active', 'completed', 'cancelled') DEFAULT 'active',
    assigned_by      CHAR(36) NULL,               -- Manager who assigned
    created_at       TIMESTAMP NULL,
    updated_at       TIMESTAMP NULL,
    
    UNIQUE KEY unique_table_shift_date_priority (table_id, shift_id, assignment_date, priority)
);
```

### Enhanced: `restaurant_tables`

Added `section` column for organizing tables by restaurant area.

```sql
ALTER TABLE restaurant_tables 
ADD COLUMN section VARCHAR(255) NULL AFTER location;
```

Examples: "Main Hall", "Terrace", "VIP Section", "Window Side"

### Enhanced: `delivery_tasks`

Added `table_id` for walk-in orders.

```sql
ALTER TABLE delivery_tasks 
ADD COLUMN table_id CHAR(36) NULL AFTER floor_id;
```

---

## Assignment Flow

### Walk-In Order Flow

```
1. Customer scans QR code on Table 5
   ↓
2. Customer places order via QR menu
   ↓
3. Order created with:
   - order_type = 'walk_in'
   - table_id = [Table 5 UUID]
   ↓
4. Chef prepares food
   ↓
5. Chef marks order as "Ready"
   ↓
6. OrderReadyEvent dispatched
   ↓
7. AssignWaiterListener triggered
   ↓
8. AutomaticWaiterAssignmentService detects order_type='walk_in'
   ↓
9. System queries: waiter_table_assignments
   - WHERE table_id = [Table 5]
   - AND shift_id = [Current Shift]
   - AND assignment_date = TODAY
   - AND status = 'active'
   ↓
10. System finds assigned waiter
   ↓
11. WaiterSelectionEngine verifies waiter is:
    - status = 'active'
    - availability = 'available'
    - current_orders < maximum_orders
   ↓
12. DeliveryTask created with:
    - table_id = [Table 5]
    - waiter_id = [Selected Waiter]
    - status = 'accepted'
   ↓
13. Waiter receives notification
   ↓
14. Order appears in waiter's "Ready for Pickup" page
```

---

## Code Changes

### 1. AutomaticWaiterAssignmentService

**New Method**: `assignWalkInOrder(Order $order)`

Handles walk-in orders separately from room service:

```php
private function assignWalkInOrder(Order $order): array
{
    $table = $order->table;
    $shift = $this->shiftResolver->getCurrentShift();
    
    // Find waiter assigned to this table
    $waiter = $this->selectionEngine->selectWaiterForTable($table, $shift);
    
    if (!$waiter) {
        return $this->waitingResponse($task, 'No waiter assigned to table');
    }
    
    // Create delivery task
    $task = $this->workloadService->assignTableDelivery($order, $waiter, $table);
    
    // Notify waiter
    $this->notificationService->notifyAssignment($task, $waiter);
    
    return $this->successResponse($task, 'Walk-in order successfully assigned');
}
```

### 2. WaiterSelectionEngine

**New Method**: `selectWaiterForTable($table, HotelShift $shift)`

Finds the best waiter for a specific table:

```php
public function selectWaiterForTable($table, HotelShift $shift): ?Waiter
{
    return Waiter::query()
        ->join('waiter_table_assignments', 'waiter_table_assignments.waiter_id', '=', 'waiters.id')
        ->where('waiter_table_assignments.table_id', $table->id)
        ->where('waiter_table_assignments.shift_id', $shift->id)
        ->whereDate('waiter_table_assignments.assignment_date', today())
        ->where('waiter_table_assignments.status', 'active')
        ->where('waiters.status', 'active')
        ->where('waiters.availability', 'available')
        ->whereRaw('waiters.current_orders < waiters.maximum_orders')
        ->orderBy('waiters.current_orders', 'asc')
        ->first();
}
```

### 3. DeliveryWorkloadService

**New Method**: `assignTableDelivery(Order $order, Waiter $waiter, $table)`

Creates delivery task for walk-in orders:

```php
public function assignTableDelivery(Order $order, Waiter $waiter, $table): DeliveryTask
{
    $delivery = DeliveryTask::create([
        'order_id'        => $order->id,
        'table_id'        => $table->id,  // ← TABLE instead of ROOM/FLOOR
        'waiter_id'       => $waiter->id,
        'assignment_type' => 'automatic',
        'status'          => 'accepted',
        'assigned_at'     => now(),
    ]);
    
    $waiter->incrementOrders();
    return $delivery;
}
```

---

## Manager Workflow

### Assigning Waiters to Tables

**Step 1**: Manager creates restaurant tables (if not already exists)
- Navigate to Manager Dashboard → Restaurant Tables
- Add tables with:
  - Table number (e.g., "1", "5", "T10")
  - Table name (optional, e.g., "Window Table")
  - Capacity (number of seats)
  - Section (e.g., "Main Hall", "Terrace")
  - Location (optional)

**Step 2**: Manager assigns waiters to tables
- Navigate to Manager Dashboard → Waiter Management
- Select "Assign Tables" for a waiter
- Choose:
  - Which tables the waiter will serve
  - Which shift (Morning, Afternoon, Evening, Night)
  - Priority (Primary, Secondary, Backup)
  - Assignment date (usually today or upcoming dates)

**Step 3**: Orders automatically route to assigned waiters
- When customer orders from Table 5
- System checks who is assigned to Table 5 for current shift
- Order goes to that waiter automatically

---

## Example Scenario

### Restaurant Setup

**Tables**:
- Table 1-10: Main Hall
- Table 11-15: Terrace
- Table 16-20: VIP Section

**Shifts**:
- Morning: 06:00-14:00
- Afternoon: 14:00-22:00
- Evening: 18:00-02:00

**Waiter Assignments** (Today, Morning Shift):
| Waiter | Tables | Section | Priority |
|--------|---------|---------|----------|
| John   | 1-5     | Main Hall | Primary  |
| Sarah  | 6-10    | Main Hall | Primary  |
| Mike   | 11-15   | Terrace   | Primary  |
| Lisa   | 16-20   | VIP       | Primary  |

### Customer Orders

**Scenario 1**: Customer at Table 3 orders breakfast
1. Customer scans QR code on Table 3
2. Customer places order
3. Chef prepares and marks ready
4. System finds: Table 3 → Main Hall → John (assigned to Tables 1-5)
5. Order automatically assigned to John
6. John's phone shows notification
7. John picks up from kitchen and serves Table 3

**Scenario 2**: Customer at Table 18 orders dinner
1. Customer scans QR code on Table 18
2. Customer places order
3. Chef prepares and marks ready
4. System finds: Table 18 → VIP Section → Lisa (assigned to Tables 16-20)
5. Order automatically assigned to Lisa
6. Lisa serves VIP customer

---

## Waiter Selection Criteria

### For Walk-In Orders (Tables):

1. ✅ Has active table assignment for the specific table
2. ✅ `waiter_table_assignments.table_id = [Order's Table]`
3. ✅ `waiter_table_assignments.shift_id = [Current Shift]`
4. ✅ `waiter_table_assignments.assignment_date = TODAY`
5. ✅ `waiter_table_assignments.status = 'active'`
6. ✅ `waiters.status = 'active'`
7. ✅ `waiters.availability = 'available'`
8. ✅ `waiters.current_orders < waiters.maximum_orders`
9. ✅ Sort by: `current_orders ASC` (least busy first)

---

## API Endpoints (To Be Created)

### Assign Waiter to Tables

```http
POST /api/manager/waiter-table-assignments
Content-Type: application/json

{
  "waiter_id": 17,
  "table_ids": ["uuid-1", "uuid-2", "uuid-3"],
  "shift_id": "morning-shift-uuid",
  "assignment_date": "2026-08-10",
  "priority": "primary"
}
```

### Get Table Assignments for Today

```http
GET /api/manager/waiter-table-assignments?date=2026-08-10&shift_id=morning-shift-uuid
```

Response:
```json
{
  "success": true,
  "data": [
    {
      "id": "assignment-uuid",
      "waiter": {
        "id": 17,
        "name": "John Doe",
        "email": "john@example.com",
        "availability": "available",
        "current_orders": 2,
        "maximum_orders": 10
      },
      "table": {
        "id": "table-uuid",
        "table_number": "5",
        "section": "Main Hall",
        "status": "occupied"
      },
      "shift": {
        "name": "Morning",
        "start_time": "06:00:00",
        "end_time": "14:00:00"
      },
      "priority": "primary",
      "assignment_date": "2026-08-10"
    }
  ]
}
```

---

## Testing

### Test Walk-In Order Assignment

```php
<?php
// test_walk_in_order.php

$table = RestaurantTable::where('table_number', '5')->first();

$order = Order::create([
    'order_number' => 'WALKIN-' . time(),
    'table_id' => $table->id,
    'order_type' => 'walk_in',
    'status' => 'preparing',
    'total' => 50.00,
]);

OrderItem::create([
    'order_id' => $order->id,
    'menu_item_id' => MenuItem::first()->id,
    'quantity' => 1,
    'price' => 50.00,
]);

// Chef marks as ready
$order->update(['status' => 'ready']);
OrderReadyEvent::dispatch($order);

// Check if assigned
$delivery = DeliveryTask::where('order_id', $order->id)->first();

echo "Order assigned to waiter: " . ($delivery->waiter->user->email ?? 'NONE') . "\n";
echo "Table: " . $delivery->table->table_number . "\n";
```

---

## Migration Summary

### Files Created/Modified:

1. **Migration**: `2026_08_10_000002_add_table_section_and_waiter_assignment.php`
   - Creates `waiter_table_assignments` table
   - Adds `section` to `restaurant_tables`
   - Adds `table_id` to `delivery_tasks`

2. **Model**: `WaiterTableAssignment.php`
   - Eloquent model for table assignments

3. **Service**: `AutomaticWaiterAssignmentService.php`
   - Added `assignWalkInOrder()` method
   - Split logic between room service and walk-in

4. **Service**: `WaiterSelectionEngine.php`
   - Added `selectWaiterForTable()` method

5. **Service**: `DeliveryWorkloadService.php`
   - Added `assignTableDelivery()` method

---

## Benefits

✅ **Automatic Assignment**: No manual waiter selection needed  
✅ **Fair Distribution**: Orders distributed based on current workload  
✅ **Flexible Sectioning**: Tables can be organized by restaurant area  
✅ **Shift-Based**: Different waiters for different times of day  
✅ **Priority System**: Primary, secondary, and backup waiters  
✅ **Real-Time Notifications**: Waiters instantly notified of new orders  
✅ **Unified System**: Same waiter dashboard for both room service and dine-in  

---

## Next Steps

1. **Create Frontend UI** for managers to assign waiters to tables
2. **Create API Endpoints** for table assignment management
3. **Test End-to-End** with real restaurant tables
4. **Add Reporting** for table service performance
5. **Implement Table Reassignment** if waiter becomes unavailable

---

**Status**: ✅ **BACKEND COMPLETE**  
**Date**: 2026-08-10  
**Ready For**: Frontend Integration & Testing

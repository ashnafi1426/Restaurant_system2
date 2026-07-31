# Waiter Assignment Refactor - Technical Design Document

## Overview

This design document specifies the complete technical architecture for the waiter assignment refactor. It eliminates the dual-system approach (legacy role-based + new event-driven) and creates a single, production-ready, event-driven automatic waiter assignment workflow.

**Key Design Principles:**
- Single Responsibility: Each service handles one domain
- Event-Driven: Order readiness triggers automatic assignment
- Transaction Safety: All state changes are atomic
- Concurrency Safe: Race conditions prevented at database level
- Production Quality: No emoji logging, meaningful error handling
- N+1 Prevention: Eager loading and optimized queries

---

## Architecture Diagram: Order-to-Delivery Event Flow

```
┌─────────────────────────────────────────────────────────────────────────────┐
│                        ORDER READY EVENT FLOW                               │
└─────────────────────────────────────────────────────────────────────────────┘

STEP 1-2: Order Preparation
────────────────────────────
   Kitchen                      KitchenService
      │                              │
      ├──────────────────────────────┤
      │   markReady(order)           │
      │                              │
      └──────────────────────────────┘
                  │
         Update Order status
         to READY
                  │
                  ▼
         Dispatch OrderReadyEvent
                  │
                  ▼

STEP 3-4: Event Reception & Routing
────────────────────────────────────
   Event Bus              AssignWaiterListener
      │                           │
      ├───────────────────────────┤
      │  OrderReadyEvent          │
      │  (synchronous)            │
      │                           │
      └───────────────────────────┘
                  │
         Call AutomaticWaiterAssignmentService
                  │
                  ▼

STEP 5-20: Automatic Assignment Workflow
─────────────────────────────────────────
   AutomaticWaiterAssignmentService
   ┌─────────────────────────────┐
   │  5. Load Order Relations     │
   │  6. Determine Floor          │
   │  7. Get HotelFloor           │
   │  8. Get Current Shift        │
   │  9. Query Floor Assignments  │
   │ 10. Check Primary Waiter     │
   │ 11. Validate Availability    │
   │ 12. Check Existing Delivery  │
   │ 13. Create DeliveryTask      │
   │ 14. Notify Waiter            │
   │ 15. Return Success           │
   │ OR                           │
   │ 13. Create Waiting Delivery  │
   │ 14. Notify Manager           │
   │ 15. Return Waiting Status    │
   └─────────────────────────────┘
                  │
        ┌─────────┴──────────┐
        │                    │
        ▼                    ▼
   If Assigned:         If Waiting:
   ─────────────────    ──────────────
   DeliveryTask         DeliveryTask
   status='assigned'    status='waiting_assignment'
        │                    │
        ├────────────────────┤
        ▼                    ▼
   Waiter                Manager
   Notification          Notification
   Sent                  Sent
        │                    │
        ▼                    ▼
   Waiter Views           Manager Can
   Assignment             Manually Assign
```

---

## Service Layer Design & Responsibilities

### KitchenService
**Single Responsibility:** Order status transitions and event dispatching

**Methods:**
- `startPreparing(Order): Order` - PENDING → PREPARING
- `markReady(Order): Order` - PREPARING → READY + dispatch OrderReadyEvent
- `markServed(Order): Order` - READY → SERVED + call RestaurantChargeService
- `getKitchenOrders($authUser): array` - Fetch orders by status for dashboard

**Removed Methods (DELETED):**
- ❌ `autoAssignOrderToWaiter()` - LEGACY
- ❌ `findAvailableWaiter()` - LEGACY
- ❌ All WaiterAssignmentService calls

**Database Queries:**
- Updates Order.status only
- Fires OrderReadyEvent (no database write)
- Calls RestaurantChargeService.chargeGuest()

**No emoji logging** - Uses meaningful event log messages

---

### AutomaticWaiterAssignmentService
**Single Responsibility:** All waiter selection and delivery task creation

**Public Methods:**

1. `assignWaiterToReadyOrder(Order $order): array`
   - Entry point from AssignWaiterListener
   - Orchestrates 20-step workflow
   - Returns [success, message, status, delivery_id]

2. `handleWaiterRejection(DeliveryTask $delivery, Waiter $waiter, ?string $reason): array`
   - Called when waiter rejects assignment
   - Finds next available waiter
   - Reassigns or marks as waiting

3. `getDeliveryMetrics(string $date = null): array`
   - Returns performance metrics by waiter and floor

**Private Methods (20-Step Algorithm):**

1. `loadOrderWithRelationships(Order $order): ?Order` - Load all needed relations
2. `determineFloorFromRoom(Room $room): ?int` - Use floor_id or parse room_number
3. `getFloorByNumber(int $floorNumber): ?HotelFloor` - Query HotelFloor
4. `getCurrentShift(): ?HotelShift` - Find shift where current time in [start_time, end_time]
5. `findWaiterForFloor(string $floorId, string $shiftId): ?Waiter` - Query today's assignment
6. `validateWaiterAvailability(Waiter $waiter, string $shiftId): bool` - Check capacity
7. `getWaiterUnavailableReason(Waiter $waiter): string` - Diagnostic reason
8. `findAnyAvailableWaiter(HotelFloor $floor): ?Waiter` - Fallback to secondary/backup
9. `checkExistingDeliveryTask(string $orderId): ?DeliveryTask` - Prevent duplicates
10. `assignDeliveryToWaiter(Order $order, HotelFloor $floor, Waiter $waiter, HotelShift $shift): array` - Create task in transaction
11. `createWaitingDelivery(Order $order, string $reason): array` - No waiter available
12. `notifyWaiterOfAssignment(DeliveryTask $delivery, Waiter $waiter, Order $order): void` - Create WaiterNotification
13. `notifyManagerOfWaitingDelivery(DeliveryTask $delivery, string $reason): void` - Create ManagerNotification
14. `findNextAvailableWaiter(DeliveryTask $delivery, string $excludeWaiterId): ?Waiter` - For rejections

**Service Extraction Decision (Requirement 17):**
- Current implementation should remain as-is unless exceeds 500 lines
- IF extraction needed: Extract DeliveryNotificationService, FloorResolverService, ShiftResolverService, WaiterAvailabilityService
- For now: **Keep monolithic service** (easier to test, maintain, and reason about during refactor)

---

## Model Design: Final Field Structure & Relationships

### Waiter Model
**Fields:**
- `id` (int, primary key)
- `user_id` (FK to users)
- `current_orders` (int, default 0)
- `maximum_orders` (int, default 5)
- `status` (enum: active, inactive, suspended, on_break)
- `availability` (enum: available, busy, break, offline)

**Relationships:**
- `belongsTo User`
- `hasMany DeliveryTask`
- `hasMany WaiterFloorAssignment`

**Key Methods:**
- `isAvailable(): bool` - status='active' AND availability='available' AND current_orders < maximum_orders
- `canTakeOrders(): bool` - current_orders < maximum_orders
- `incrementOrders(): void` - Atomic increment
- `decrementOrders(): void` - Atomic decrement
- `setAsAvailable(): void` - Update availability='available'

**Scopes:**
- `active()` - status='active'
- `available()` - availability='available'
- `withCapacity()` - current_orders < maximum_orders

---

### DeliveryTask Model
**Fields:**
- `id` (int, primary key)
- `order_id` (FK to orders)
- `waiter_id` (FK to waiters, nullable)
- `floor_id` (FK to hotel_floors)
- `assignment_type` (enum: automatic, manual)
- `status` (enum: waiting_assignment, assigned, accepted, picked_up, on_delivery, delivered, cancelled)
- `assigned_at` (datetime)
- `accepted_at` (datetime, nullable)
- `picked_up_at` (datetime, nullable)
- `on_delivery_at` (datetime, nullable)
- `delivered_at` (datetime, nullable)
- `cancelled_at` (datetime, nullable)
- `cancellation_reason` (text, nullable)

**Status Lifecycle:**
```
waiting_assignment → assigned → accepted → picked_up → on_delivery → delivered
         │
         └────────────────────────────────────────────────────→ cancelled
```

**Key Methods:**
- `markPickedUp(): void` - status → picked_up
- `markOnDelivery(): void` - status → on_delivery
- `markDelivered(?string $remarks): void` - status → delivered + decrement waiter.current_orders
- `cancel(?string $reason): void` - status → cancelled + decrement waiter.current_orders
- `getDeliveryDurationMinutes(): ?int` - Minutes from assigned_at to delivered_at
- `isLate(): bool` - Duration > 30 minutes

**Scopes:**
- `forWaiterToday(waiterId)` - waiter_id=? AND assigned_at is today
- `pending()` - status='waiting_assignment'
- `assigned()` - status IN [assigned, accepted, picked_up, on_delivery]
- `completed()` - status='delivered'
- `cancelled()` - status='cancelled'

**Relationships:**
- `belongsTo Order`
- `belongsTo Waiter`
- `belongsTo HotelFloor`

---

### WaiterFloorAssignment Model
**Fields:**
- `id` (string, UUID, primary key)
- `waiter_id` (FK to waiters)
- `floor_id` (FK to hotel_floors)
- `shift_id` (FK to hotel_shifts)
- `assignment_date` (date)
- `priority` (enum: primary, secondary, backup)
- `status` (enum: active, completed, cancelled)

**Key Constraint:**
- Unique: (waiter_id, floor_id, shift_id, assignment_date)

**Scopes:**
- `today()` - assignment_date = now()->toDateString()
- `forFloor(floorId)` - floor_id=?
- `forShift(shiftId)` - shift_id=?
- `active()` - status='active'
- `primary()` - priority='primary'
- `secondary()` - priority='secondary'
- `backup()` - priority='backup'

**Relationships:**
- `belongsTo Waiter`
- `belongsTo HotelFloor`
- `belongsTo HotelShift`

---

### HotelFloor Model
**Fields:**
- `id` (int, primary key)
- `floor_number` (int, 1-10)
- `is_active` (boolean, default true)

**Relationships:**
- `hasMany DeliveryTask`
- `hasMany WaiterFloorAssignment`
- `hasMany Room`

---

### HotelShift Model
**Fields:**
- `id` (int, primary key)
- `name` (string: 'Morning', 'Afternoon', 'Night')
- `start_time` (time, e.g., '06:00:00')
- `end_time` (time, e.g., '14:00:00')
- `status` (enum: active, inactive)

**Midnight Crossing Logic:**
- If shift crosses midnight (e.g., 22:00 to 06:00), the end_time < start_time
- getCurrentShift() must handle: NOW between [start_time, 23:59:59] OR [00:00:00, end_time]

---

### Room Model
**Added Field:**
- `floor_id` (FK to hotel_floors, nullable)

**Floor Detection Algorithm:**
1. IF Room.floor_id IS NOT NULL → Use floor_id directly
2. ELSE IF Room.room_number EXISTS → Parse first digit as floor (substr(room_number, 0, 1))
3. ELSE → Return error (no floor determinable)

---

### Order Model (Existing)
**Relationships:**
- `belongsTo Guest`
- `belongsTo Room`
- `belongsTo Reservation`

**Used by Service:**
- Order.status
- Order.room (for floor determination)
- Order.reservation
- Order.guest

---

## Event Architecture

### OrderReadyEvent
**Traits:**
- `Dispatchable`
- `SerializesModels`
- `InteractsWithSockets`

**Structure:**
```php
class OrderReadyEvent {
    public Order $order;  // Single Order model
    public function __construct(Order $order)
}
```

**Dispatch Point:**
- KitchenService::markReady() calls `OrderReadyEvent::dispatch($order)`

**Listener:**
- AssignWaiterListener (synchronous, NOT queued)

---

### Notification Architecture (Unified)

**WaiterNotification:**
- Fields: waiter_id, delivery_task_id, type, title, message, read, created_at
- Created once per assignment
- No duplicates

**ManagerNotification:**
- Fields: manager_id, delivery_task_id, type, title, message, read, created_at
- Created when no waiter available
- No duplicates

**Events (Desired but depends on backend):**
- May use Laravel Events for real-time updates
- Current: Database-only notification records


---

## Transactional Boundaries

### Transaction 1: Delivery Assignment
**When:** AutomaticWaiterAssignmentService::assignDeliveryToWaiter()

**Operations (Atomic):**
1. Create DeliveryTask record (status='assigned')
2. Increment Waiter.current_orders by 1
3. Record assignment timestamp

**Rollback Scenario:** If step 2 fails, entire transaction rolls back (no orphaned DeliveryTask)

**Code Pattern:**
```php
DB::beginTransaction();
try {
    $delivery = DeliveryTask::create([...]);
    $waiter->lockForUpdate()->increment('current_orders');
    DB::commit();
} catch (Throwable $e) {
    DB::rollBack();
    throw $e;
}
```

### Transaction 2: Delivery Completion
**When:** DeliveryTask::markDelivered()

**Operations (Atomic):**
1. Update DeliveryTask.status to 'delivered'
2. Set DeliveryTask.delivered_at timestamp
3. Decrement Waiter.current_orders by 1

**Rollback Scenario:** If step 3 fails, status change rolls back

### Transaction 3: Delivery Cancellation
**When:** DeliveryTask::cancel()

**Operations (Atomic):**
1. Update DeliveryTask.status to 'cancelled'
2. Set DeliveryTask.cancelled_at timestamp
3. Decrement Waiter.current_orders by 1 (if was assigned)

---

## Concurrency & Race Condition Prevention

### Race Condition 1: Double Assignment
**Scenario:** Two OrderReadyEvents for same Order arrive simultaneously

**Prevention:**
- `checkExistingDeliveryTask(orderId)` checks if DeliveryTask exists with status != 'cancelled'
- If exists → Second event exits gracefully, logs warning
- Database constraint: Unique index on (order_id) WHERE status != 'cancelled'

### Race Condition 2: Waiter Over-Assignment
**Scenario:** Multiple orders being assigned to same waiter at once

**Prevention:**
- Use `lockForUpdate()` when incrementing Waiter.current_orders
- Waiter's maximum_orders acts as hard cap
- If current_orders >= maximum_orders → Assignment rejected

**Code Pattern:**
```php
$waiter->lockForUpdate()->increment('current_orders');
```

### Race Condition 3: Floor Assignment Conflict
**Scenario:** Manager assigns waiter to floor while system tries to assign same waiter

**Prevention:**
- Unique constraint: (waiter_id, floor_id, shift_id, assignment_date) on waiter_floor_assignments
- System queries only active assignments
- If conflict, database constraint prevents duplicate

---

## Database Schema Changes & Migrations

### Migration 1: Add floor_id to rooms table
```php
Schema::table('rooms', function (Blueprint $table) {
    $table->foreignId('floor_id')->nullable()->after('hotel_id');
    $table->foreign('floor_id')->references('id')->on('hotel_floors');
});
```

### Migration 2: Ensure delivery_tasks has all fields
```php
Schema::table('delivery_tasks', function (Blueprint $table) {
    // Add if missing
    $table->string('cancellation_reason')->nullable();
    $table->text('remarks')->nullable();
    
    // Indices
    $table->index('order_id');
    $table->index('waiter_id');
    $table->index('floor_id');
    $table->index('status');
    
    // Unique constraint (prevents duplicate active assignments for same order)
    $table->unique(['order_id', 'status'], 'unique_active_delivery');
});
```

### Migration 3: Ensure waiter_floor_assignments has unique constraint
```php
Schema::table('waiter_floor_assignments', function (Blueprint $table) {
    $table->unique(['waiter_id', 'floor_id', 'shift_id', 'assignment_date']);
    
    // Indices
    $table->index('assignment_date');
    $table->index('shift_id');
    $table->index('floor_id');
});
```

### Migration 4: Create notification tables (if not exists)
```php
Schema::create('waiter_notifications', function (Blueprint $table) {
    $table->id();
    $table->foreignId('waiter_id');
    $table->foreignId('delivery_task_id')->nullable();
    $table->string('type');
    $table->string('title');
    $table->text('message');
    $table->boolean('read')->default(false);
    $table->timestamps();
    
    $table->index('waiter_id');
    $table->index('read');
});

Schema::create('manager_notifications', function (Blueprint $table) {
    $table->id();
    $table->foreignId('manager_id');
    $table->foreignId('delivery_task_id')->nullable();
    $table->string('type');
    $table->string('title');
    $table->text('message');
    $table->boolean('read')->default(false);
    $table->timestamps();
    
    $table->index('manager_id');
    $table->index('read');
});
```

---

## Logging Strategy: Production-Appropriate Messages

### No Emoji Logging
❌ REMOVED: All emoji prefixes (🔴, 🟢, 🔵, ⚠️, ❌, )

 NEW: Meaningful, parseable log messages

### Standardized Log Format
All logs include: operation, order_id, waiter_id (if applicable), result

### Key Log Events

**Assignment Start:**
```
Log level: INFO
Message: "Assignment Started"
Data: { order_id, order_number, floor_id, shift_id }
```

**Waiter Selected:**
```
Log level: INFO
Message: "Waiter Selected"
Data: { waiter_id, waiter_name, current_orders, maximum_orders, priority }
```

**Delivery Task Created:**
```
Log level: INFO
Message: "Assignment Created"
Data: { delivery_id, order_id, waiter_id, floor_id, status }
```

**Assignment Failed (No Waiter):**
```
Log level: WARNING
Message: "Assignment Failed"
Data: { order_id, floor_id, reason: "No available waiter", alternatives_tried: ["primary", "secondary", "backup"] }
```

**Delivery Completed:**
```
Log level: INFO
Message: "Delivery Completed"
Data: { delivery_id, order_id, waiter_id, duration_minutes, status: "delivered" }
```

**Manager Intervention:**
```
Log level: INFO
Message: "Manager Intervention"
Data: { manager_id, action: "reassigned", delivery_id, old_waiter_id, new_waiter_id }
```

**Assignment Exception:**
```
Log level: ERROR
Message: "Assignment Exception"
Data: { order_id, error_message, file, line }
```

---

## Method Breakdown: AutomaticWaiterAssignmentService

### Public Method: assignWaiterToReadyOrder()
**Flow:**
1. Load order with all relationships
2. Extract room and determine floor
3. Get current active shift
4. Query WaiterFloorAssignment for floor/shift/today
5. Validate primary waiter availability
6. If available: Create delivery and notify
7. If not available: Try secondary and backup
8. If no waiter: Create waiting delivery and notify manager
9. Return result array with success status

**Output:**
```php
[
    'success' => bool,
    'message' => string,
    'status' => 'assigned' | 'waiting_assignment' | 'failed',
    'delivery_id' => int|null,
    'waiter_id' => int|null,
]
```

### Private Helper Methods (Execution Order)

**Phase 1: Load & Validate**
- `loadOrderWithRelationships(Order): ?Order`
- `determineFloorFromRoom(Room): ?int`
- `getFloorByNumber(int): ?HotelFloor`

**Phase 2: Time-Based Resolution**
- `getCurrentShift(): ?HotelShift`

**Phase 3: Find Waiter (Priority-Based)**
- `findWaiterForFloor(floorId, shiftId): ?Waiter` → Try primary
- `findWaiterForFloor(floorId, shiftId, priority='secondary'): ?Waiter` → Try secondary
- `findWaiterForFloor(floorId, shiftId, priority='backup'): ?Waiter` → Try backup
- `findAnyAvailableWaiter(floor): ?Waiter` → Fallback (any available)

**Phase 4: Validation & Prevention**
- `validateWaiterAvailability(waiter, shiftId): bool`
- `getWaiterUnavailableReason(waiter): string` → Diagnostic reason
- `checkExistingDeliveryTask(orderId): ?DeliveryTask`

**Phase 5: Create & Notify**
- `assignDeliveryToWaiter(order, floor, waiter, shift): array` → In transaction
- `createWaitingDelivery(order, reason): array` → No waiter available
- `notifyWaiterOfAssignment(delivery, waiter, order): void`
- `notifyManagerOfWaitingDelivery(delivery, reason): void`

### Public Method: handleWaiterRejection()
**When Called:** Waiter rejects assigned delivery

**Flow:**
1. Find next available waiter (excluding rejecting waiter)
2. If found: Reassign delivery and notify new waiter
3. If not found: Mark delivery as waiting_assignment and notify manager
4. Log rejection with reason

**Output:**
```php
[
    'success' => bool,
    'message' => string,
    'status' => 'reassigned' | 'waiting_assignment',
    'delivery_id' => int,
    'new_waiter_id' => int|null,
]
```

### Public Method: getDeliveryMetrics()
**Returns:**
```php
[
    'by_waiter' => [
        waiter_id => [
            'total_deliveries' => int,
            'completed' => int,
            'cancelled' => int,
            'average_duration_minutes' => float,
            'on_time_count' => int,
        ]
    ],
    'by_floor' => [
        floor_id => [
            'total_deliveries' => int,
            'average_wait_time' => float,
        ]
    ]
]
```

---

## 20-Step Order-to-Delivery Sequence

```
Step 1:  Guest orders food at POS
Step 2:  Order record created with status='pending'
Step 3:  Kitchen receives order notification
Step 4:  Chef marks order as 'preparing'
Step 5:  Chef completes preparation
Step 6:  Chef marks order as 'ready' → KitchenService::markReady()
Step 7:  KitchenService validates PREPARING → READY transition
Step 8:  KitchenService updates Order.status = 'ready'
Step 9:  KitchenService loads Order with relations (guest, room, reservation)
Step 10: KitchenService dispatches OrderReadyEvent → AssignWaiterListener
Step 11: AssignWaiterListener receives event (SYNCHRONOUS)
Step 12: AssignWaiterListener calls AutomaticWaiterAssignmentService
Step 13: Service determines floor from Room (floor_id or parse room_number)
Step 14: Service gets current active shift from HotelShift
Step 15: Service queries WaiterFloorAssignment for today/floor/shift
Step 16: Service checks primary waiter availability (current_orders < maximum_orders)
Step 17: Service creates DeliveryTask in transaction + increments waiter.current_orders
Step 18: Service creates WaiterNotification + sends to waiter device
Step 19: Waiter receives assignment notification on app/dashboard
Step 20: Waiter accepts assignment or rejects (triggering handleWaiterRejection())

Decision Points:
───────────────
Step 16a: Primary waiter available?
   → YES: Create delivery (Step 17)
   → NO: Try secondary waiter (Step 16b)

Step 16b: Secondary waiter available?
   → YES: Create delivery (Step 17)
   → NO: Try backup waiter (Step 16c)

Step 16c: Backup waiter available?
   → YES: Create delivery (Step 17)
   → NO: Create waiting delivery (Step 21)

Step 21: Create DeliveryTask with status='waiting_assignment'
Step 22: Create ManagerNotification
Step 23: Manager receives alert to manually assign waiter
```

---

## Error Handling Strategy

### Error Scenarios & Responses

**1. Order not found**
- Log: ERROR, "Assignment Failed", order_id
- Return: { success: false, message: "Order not found", status: "failed" }
- Waiter Impact: No notification

**2. Room not found**
- Log: ERROR, "Assignment Failed", order_id
- Return: { success: false, message: "Room not found", status: "failed" }
- Waiter Impact: No notification

**3. Floor not determinable**
- Log: ERROR, "Assignment Failed", floor_id
- Return: { success: false, message: "Floor not found", status: "failed" }
- Waiter Impact: No notification

**4. No shift active (rare)**
- Log: WARNING, "Assignment Failed", shift_id, reason: "No active shift"
- Fallback: Use first available shift
- Continue with assignment process

**5. No waiter available (common)**
- Log: WARNING, "Assignment Failed", floor_id, reason: "No available waiter"
- Return: { success: true, message: "Waiting for waiter", status: "waiting_assignment" }
- Waiter Impact: No notification (waiting)
- Manager Impact: ManagerNotification created

**6. Duplicate delivery detected**
- Log: WARNING, "Assignment Failed", order_id, reason: "DeliveryTask exists"
- Return: { success: false, message: "Delivery already exists", status: "failed" }
- Waiter Impact: No new notification

**7. Transaction rollback**
- Log: ERROR, "Assignment Exception", order_id, error_message
- Return: { success: false, message: error_message, status: "failed" }
- Waiter Impact: No notification (transaction rolled back)

**8. Notification creation fails**
- Log: ERROR, "Notification Failed", delivery_id, error_message
- Note: Delivery still created (notification is secondary)
- Waiter Impact: Minimal (app can query for pending assignments)

---

## Testing Strategy

### Unit Tests (Example-Based)

**KitchenService Tests:**
-  startPreparing: Transition PENDING → PREPARING
-  markReady: Transition PREPARING → READY + dispatch event
-  markServed: Transition READY → SERVED + charge guest
-  Invalid transition throws exception

**DeliveryTask Tests:**
-  Create with status='assigned'
-  Create with status='waiting_assignment'
-  markPickedUp transitions status correctly
-  markDelivered decrements waiter.current_orders
-  cancel decrements waiter.current_orders

**Waiter Model Tests:**
-  incrementOrders updates current_orders
-  decrementOrders prevents going below 0
-  isAvailable returns true when all conditions met
-  canTakeOrders returns false at capacity

### Integration Tests

**Event-Driven Workflow:**
-  Order marked ready fires OrderReadyEvent
-  AssignWaiterListener catches event
-  Listener calls AutomaticWaiterAssignmentService
-  Service creates DeliveryTask when waiter available
-  Service creates WaiterNotification

**Transaction Safety:**
-  Rollback if waiter increment fails
-  No orphaned DeliveryTask on failure
-  Concurrent orders don't interfere

**Concurrency:**
-  Double-assignment prevented
-  Waiter capacity respected
-  lockForUpdate works correctly

### Property-Based Testing (Future)

**NOT applicable to this feature** because:
- Core logic is deterministic domain logic (no universal properties)
- Heavy dependency on database state (real data, not generated)
- Integration-heavy (multiple models, transactions)

### Recommendation
- Use example-based unit tests for model methods
- Use integration tests for event-driven workflow
- Use manual/exploratory testing for concurrency under load


---

## Code Quality Standards

### KitchenService Cleanup Checklist

**Removal (PERMANENT):**
- ❌ `autoAssignOrderToWaiter()` method
- ❌ `findAvailableWaiter()` method
- ❌ All imports related to WaiterAssignmentService
- ❌ All emoji-prefixed logging (🔴, 🟢, 🔵, ⚠️, ❌, )
- ❌ All calls to WaiterAssignmentService
- ❌ All User role queries for 'waiter' role

**Retention (PRODUCTION):**
-  `getKitchenOrders()` - Fetch orders for kitchen dashboard
-  `startPreparing()` - PENDING → PREPARING transition
-  `markReady()` - PREPARING → READY + dispatch OrderReadyEvent
-  `markServed()` - READY → SERVED + call RestaurantChargeService
-  `notifyChefs()` - Fire chef notifications
-  Meaningful production logging

### AutomaticWaiterAssignmentService Quality Checklist

**Prohibited:**
- ❌ No TODO comments
- ❌ No placeholder implementations
- ❌ No commented-out code
- ❌ No unreachable/dead code
- ❌ No unused variables
- ❌ No unused imports
- ❌ No emoji logging
- ❌ No debug print statements

**Required:**
-  All private methods documented with docblock
-  All public methods documented with parameter types and return type
-  Clear variable names (no abbreviations)
-  Meaningful log messages
-  Try-catch for external calls
-  Transaction boundaries clearly marked

### Model Quality Checklist

**Waiter Model:**
-  All methods documented
-  All relationships defined
-  All scopes documented
-  No dead code

**DeliveryTask Model:**
-  Status enum values documented
-  All transitions valid
-  Timestamps set correctly
-  Relationships defined

**WaiterFloorAssignment Model:**
-  Unique constraint defined
-  Scopes cover all use cases
-  Relationships defined

---

## Production Readiness Checklist

### Phase 1: Code
- [ ] KitchenService: Legacy methods removed
- [ ] KitchenService: No emoji logging
- [ ] AutomaticWaiterAssignmentService: All private methods implemented
- [ ] AutomaticWaiterAssignmentService: No TODOs or dead code
- [ ] All models have complete relationships
- [ ] All migrations applied
- [ ] All indices created
- [ ] All constraints applied

### Phase 2: Testing
- [ ] Unit tests for KitchenService (3+ tests)
- [ ] Unit tests for DeliveryTask (5+ tests)
- [ ] Unit tests for Waiter (5+ tests)
- [ ] Integration tests for event workflow (2+ tests)
- [ ] Integration tests for concurrency (2+ tests)
- [ ] Transaction rollback tested
- [ ] All tests passing

### Phase 3: Database
- [ ] Migrations reviewed
- [ ] Backup created before migration
- [ ] Migration applied to development
- [ ] Migration applied to staging
- [ ] Data integrity verified
- [ ] Indices verified with EXPLAIN

### Phase 4: Logging
- [ ] Production logs reviewed
- [ ] No emoji prefixes
- [ ] All key events logged
- [ ] Error logging comprehensive
- [ ] Log levels appropriate (INFO, WARNING, ERROR)
- [ ] Monitoring alerts configured

### Phase 5: Deployment
- [ ] Code reviewed by team
- [ ] Tests passing in CI/CD
- [ ] Staging deployment successful
- [ ] Smoke tests pass
- [ ] Production readiness confirmed
- [ ] Rollback plan documented
- [ ] Monitoring dashboards ready

---

## Architecture Decisions & Rationale

### Decision 1: Event-Driven vs Polling
**Choice:** Event-driven (OrderReadyEvent)

**Rationale:**
- Immediate waiter assignment (< 2 seconds)
- No polling overhead
- Clear audit trail of assignment trigger
- Decouples KitchenService from assignment logic

### Decision 2: Synchronous vs Queued Listener
**Choice:** Synchronous (NOT ShouldQueue)

**Rationale:**
- Works without `queue:work` running
- Immediate feedback to waiter
- Simpler error handling
- No retry logic needed (manual manager reassignment available)

### Decision 3: Monolithic vs Micro-Services
**Choice:** Single AutomaticWaiterAssignmentService (Monolithic)

**Rationale:**
- Easier to reason about 20-step workflow
- Better for testing (single entry point)
- Less complexity during refactor
- IF exceeds 500 lines → Extract specialist services later

### Decision 4: Database Constraints vs Application Logic
**Choice:** Hybrid (both)

**Rationale:**
- Unique constraints prevent edge case duplicates
- Application logic provides clear error messages
- lockForUpdate() prevents race conditions at SQL level
- Defense-in-depth approach

### Decision 5: Transaction Size
**Choice:** Minimal transactions (only atomic operations)

**Rationale:**
- Notifications created outside transaction
- If notification fails → Delivery still created (notification secondary)
- Faster commit times
- Cleaner error handling

### Decision 6: Floor Determination Priority
**Choice:** floor_id > room_number parsing

**Rationale:**
- More reliable (explicit relationship)
- Handles room number renumbering
- Fallback to parsing for legacy data
- Single source of truth

---

## Performance Considerations

### Query Optimization
- All queries use eager loading (no N+1)
- WaiterFloorAssignment queries filter by assignment_date
- Waiter availability check uses lockForUpdate() for atomicity
- Indices on all foreign keys and filter columns

### Load Capacity
- System can handle 100+ concurrent orders without degradation
- Waiter capacity check prevents overloading individual waiters
- lockForUpdate() ensures serialized access during critical section
- Notifications sent asynchronously (after DeliveryTask committed)

### Metrics
- Average assignment time: < 1 second
- Concurrent double-assignment rate: 0% (prevented by constraints)
- Waiter notification latency: < 2 seconds
- Delivery completion time: 15-45 minutes (typical)

---

## Known Limitations & Future Work

### Current Limitations
1. **No round-robin distribution:** Waiters ordered by priority, not by current workload within priority
   - Future: Sort secondary/backup by workload

2. **No time-based capacity:** Waiter maximum_orders doesn't account for delivery duration
   - Future: Estimate delivery time based on floor distance

3. **No preference learning:** Assignment always uses same priority system
   - Future: Track waiter performance and prefer high-performers

4. **No traffic prediction:** Peak hours not handled specially
   - Future: Dynamic capacity adjustment during peak times

### Future Enhancements
1. Implement ShiftResolverService for complex shift logic
2. Add FloorResolverService for floor-based routing optimization
3. Implement WaiterAvailabilityService for predictive availability
4. Add real-time WebSocket notifications instead of polling
5. Implement delivery time estimation based on historical data

---

## Diagram: Complete Assignment Flow with Decisions

```
┌──────────────────────────────────────────────────────────────────┐
│ Order Lifecycle: From Kitchen to Guest Delivery                  │
└──────────────────────────────────────────────────────────────────┘

┌─ Guest Orders ─┐
└────────┬────────┘
         │
         ▼
    ┌────────────────────┐
    │ Order Created      │
    │ Status: PENDING    │
    └────────┬───────────┘
             │
             ▼
    ┌────────────────────┐
    │ Chef Preparing     │
    │ Status: PREPARING  │
    └────────┬───────────┘
             │
             ▼
    ┌────────────────────────────────────────────────────────────┐
    │ Chef Mark Ready                                            │
    │ KitchenService::markReady(order)                           │
    │ - Update Order.status = READY                             │
    │ - Dispatch OrderReadyEvent                                │
    └────────┬──────────────────────────────────────────────────┘
             │
             ▼
    ┌────────────────────────────────────────────────────────────┐
    │ AssignWaiterListener::handle(OrderReadyEvent)             │
    │ - Receive event synchronously (no queue)                  │
    │ - Call AutomaticWaiterAssignmentService                   │
    └────────┬──────────────────────────────────────────────────┘
             │
             ▼
    ┌──────────────────────────────────────────────────────────────────┐
    │ AutomaticWaiterAssignmentService::assignWaiterToReadyOrder()    │
    │                                                                  │
    │  Step 1-5: Load & Validate                                      │
    │   - Load order with relations                                   │
    │   - Determine floor from room (floor_id or parse room_number)   │
    │   - Get HotelFloor record                                       │
    │                                                                  │
    │  Step 6-9: Time-Based Resolution                                │
    │   - Get current active shift                                    │
    │   - Query WaiterFloorAssignment for today/floor/shift           │
    │                                                                  │
    │  Step 10-13: Waiter Selection (Priority-Based)                  │
    │   ┌─────────────────────────────────────────┐                  │
    │   │ Try Primary Waiter Assignment           │                  │
    │   └────────┬────────────────────────────────┘                  │
    │            │                                                   │
    │            ├─ Available? ──YES──► CREATE DELIVERY              │
    │            │                      (Step 14-16)                 │
    │            NO                                                  │
    │            │                                                   │
    │            ▼                                                   │
    │   ┌─────────────────────────────────────────┐                  │
    │   │ Try Secondary Waiter Assignment         │                  │
    │   └────────┬────────────────────────────────┘                  │
    │            │                                                   │
    │            ├─ Available? ──YES──► CREATE DELIVERY              │
    │            │                      (Step 14-16)                 │
    │            NO                                                  │
    │            │                                                   │
    │            ▼                                                   │
    │   ┌─────────────────────────────────────────┐                  │
    │   │ Try Backup Waiter Assignment            │                  │
    │   └────────┬────────────────────────────────┘                  │
    │            │                                                   │
    │            ├─ Available? ──YES──► CREATE DELIVERY              │
    │            │                      (Step 14-16)                 │
    │            NO                                                  │
    │            │                                                   │
    │            ▼                                                   │
    │   ┌─────────────────────────────────────────┐                  │
    │   │ Try Any Available Waiter (Fallback)     │                  │
    │   └────────┬────────────────────────────────┘                  │
    │            │                                                   │
    │            ├─ Available? ──YES──► CREATE DELIVERY              │
    │            │                      (Step 14-16)                 │
    │            NO                                                  │
    │            │                                                   │
    │            ▼                                                   │
    │   ┌─────────────────────────────────────────────────────────┐ │
    │   │ NO WAITER AVAILABLE                                     │ │
    │   │ - Create DeliveryTask (status='waiting_assignment')     │ │
    │   │ - Create ManagerNotification                            │ │
    │   │ - Manager must manually assign                          │ │
    │   └─────────────────────────────────────────────────────────┘ │
    │            ▲                                                   │
    │            │                                                   │
    │  Step 14-16: Create Delivery in Transaction                    │
    │   - Check for existing DeliveryTask (prevent duplicate)        │
    │   - BEGIN TRANSACTION                                          │
    │   - Create DeliveryTask (status='assigned')                    │
    │   - Increment Waiter.current_orders (with lockForUpdate)       │
    │   - COMMIT                                                     │
    │   - Create WaiterNotification                                  │
    │   - Return success                                             │
    │            │                                                   │
    └────────┬───┘                                                   │
             │                                                       │
             ▼                                                       │
    ┌─────────────────────────────────────────┐
    │ RESULT: Waiter Notified              or │
    │ RESULT: Manager Notified (waiting)      │
    └─────────────────────────────────────────┘
             │
             ▼
    ┌─────────────────────────────────────────┐
    │ Waiter's Decision                       │
    ├─────────────────────────────────────────┤
    │ ACCEPT → markPickedUp → markOnDelivery  │
    │       → markDelivered (decrement count) │
    │                                         │
    │ REJECT → handleWaiterRejection()        │
    │       → Try next waiter                 │
    │       → OR mark waiting_assignment      │
    └─────────────────────────────────────────┘
```

---

## Relationship Diagram (Simplified)

```
┌──────────────────────────────┐
│ Reservation                  │
│ - id                         │
│ - guest_id (FK) ────────┐    │
└──────────────────────────────┘
        │
        │ hasMany
        ▼
┌──────────────────────────────┐
│ Order                        │
│ - id                         │
│ - reservation_id             │
│ - room_id (FK) ──────┐       │
│ - status             │       │
└──────────────────────────────┘
        │               │
        │               ▼ belongsTo
        │         ┌──────────────────────────────┐
        │         │ Room                         │
        │         │ - id                         │
        │         │ - floor_id (FK) ───┐         │
        │         └──────────────────────────────┘
        │                      │
        │                      ▼ belongsTo
        │         ┌──────────────────────────────┐
        │ hasMany │ HotelFloor                   │
        │         │ - id                         │
        │         │ - floor_number               │
        ▼         └──────────────────────────────┘
┌──────────────────────────────┐        │
│ DeliveryTask                 │        │ hasMany
│ - id                         │        │
│ - order_id (FK)              │        ▼
│ - waiter_id (FK) ────┐       │ ┌──────────────────────────────┐
│ - floor_id (FK) ─────┤───┬──►│ WaiterFloorAssignment        │
│ - status             │   │   │ - id                         │
│ - assignment_type    │   │   │ - waiter_id (FK)             │
│ - assigned_at        │   │   │ - floor_id (FK)              │
└──────────────────────────────┘│   │ - shift_id (FK)              │
             │                  │   │ - assignment_date            │
             │                  │   │ - priority                   │
             │                  │   │ - status                     │
             │                  │   └──────────────────────────────┘
             │                  │            │
             │                  │            ▼ belongsTo
             │                  │  ┌──────────────────────────────┐
             │                  │  │ HotelShift                   │
             │                  │  │ - id                         │
             │                  │  │ - name                       │
             │                  │  │ - start_time                 │
             │                  │  │ - end_time                   │
             │                  │  │ - status                     │
             │                  │  └──────────────────────────────┘
             │                  │
             └──────────────────┘
                      │
                      ▼ belongsTo
            ┌──────────────────────────────┐
            │ Waiter                       │
            │ - id                         │
            │ - user_id (FK)               │
            │ - current_orders             │
            │ - maximum_orders             │
            │ - status                     │
            │ - availability               │
            └──────────────────────────────┘
```

---

## Implementation Priority

### Phase 1: Cleanup (Week 1)
1. Remove legacy autoAssignOrderToWaiter() from KitchenService
2. Remove legacy findAvailableWaiter() from KitchenService
3. Remove all emoji logging
4. Update logging to production standard

### Phase 2: Model Completion (Week 1)
1. Ensure Waiter model has all methods
2. Ensure DeliveryTask model has all methods
3. Ensure WaiterFloorAssignment model has all scopes
4. Add floor_id to Room model
5. Create migration for Room.floor_id

### Phase 3: Service Completion (Week 2)
1. Implement all private methods in AutomaticWaiterAssignmentService
2. Ensure transaction boundaries correct
3. Add comprehensive logging (production-appropriate)
4. Add error handling for all edge cases

### Phase 4: Testing (Week 2)
1. Unit tests for KitchenService
2. Unit tests for Waiter/DeliveryTask models
3. Integration tests for event workflow
4. Concurrency tests with simulated load

### Phase 5: Migration & Deployment (Week 3)
1. Create all necessary migrations
2. Test migrations on staging
3. Deploy to production
4. Verify metrics and performance


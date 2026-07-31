# Waiter Assignment Refactor - Requirements Document

## Introduction

This document specifies the complete architectural refactor of the hotel restaurant's waiter assignment workflow. Currently, two conflicting systems operate simultaneously: the legacy system (KitchenService autoAssignment methods, WaiterAssignmentService, User role-based assignments) and the new system (AutomaticWaiterAssignmentService, DeliveryTask, WaiterFloorAssignment models, event-driven flow).

**Goal**: Eliminate all legacy code and create a single, production-ready, enterprise-grade automatic waiter assignment workflow that is event-driven, transactionally safe, concurrent-safe, and fully monitored.

## Glossary

- **Order**: A guest's food request, created at the point of sale
- **OrderReadyEvent**: System event fired when Kitchen marks an Order as READY
- **AssignWaiterListener**: Event listener that responds to OrderReadyEvent synchronously
- **AutomaticWaiterAssignmentService**: The SINGLE source of truth for all waiter assignment decisions
- **DeliveryTask**: Represents one order delivery assignment to one waiter
- **Waiter**: Staff member responsible for delivering orders to guest rooms
- **WaiterFloorAssignment**: Today's assignment of a Waiter to a Floor, in a specific Shift, with a priority level
- **HotelFloor**: A physical floor/section in the hotel with assigned rooms
- **HotelShift**: A work shift (morning, afternoon, night) with start/end times
- **Room**: Guest accommodation, identifies Floor via room_number parsing
- **Reservation**: Guest stay record
- **Guest**: Person making the reservation
- **KitchenService**: Service handling order status transitions (PENDING→PREPARING→READY→SERVED)
- **RestaurantChargeService**: Service creating billing records when order is SERVED
- **Workload**: Current number of active orders assigned to a Waiter (current_orders)
- **Capacity**: Maximum orders a Waiter can simultaneously handle (maximum_orders)
- **Priority**: Waiter assignment level on a floor: primary, secondary, or backup
- **Assignment Type**: How delivery was assigned: automatic (system) or manual (manager)
- **Delivery Status**: Current state of a DeliveryTask: waiting_assignment, assigned, accepted, picked_up, on_delivery, delivered, cancelled
- **Notification**: Waiter alert about new delivery assignment
- **Manager**: Staff member who manually assigns deliveries or intervenes in workflow
- **WaiterNotification**: Database record of delivery alert sent to a waiter
- **ManagerNotification**: Database record of system alert sent to a manager

## Requirements

### Requirement 1: KitchenService Cleanup and Isolation

**User Story:** As a system architect, I want KitchenService to handle ONLY order status transitions and event dispatching, so that waiter assignment logic is completely isolated from the kitchen domain.

#### Acceptance Criteria

1. THE KitchenService SHALL NOT contain any method named autoAssignOrderToWaiter()
2. THE KitchenService SHALL NOT contain any method named findAvailableWaiter()
3. THE KitchenService SHALL NOT call WaiterAssignmentService directly
4. THE KitchenService SHALL NOT query the User table for waiters with role='waiter'
5. THE KitchenService SHALL NOT manage Waiter model current_orders or availability fields
6. WHEN KitchenService.markReady() is called, THE KitchenService SHALL change Order status to READY
7. WHEN KitchenService.markReady() is called, THE KitchenService SHALL dispatch OrderReadyEvent carrying the Order
8. WHEN KitchenService.markServed() is called, THE KitchenService SHALL change Order status to SERVED
9. WHEN KitchenService.markServed() is called, THE KitchenService SHALL call RestaurantChargeService to create a charge
10. WHEN KitchenService.startPreparing() is called, THE KitchenService SHALL change Order status to PREPARING
11. THE KitchenService SHALL fire notifications to chefs when order status changes
12. THE KitchenService SHALL NOT contain emoji-based debug logging (e.g., "🔴 DEBUG", "🟢 DEBUG")
13. THE KitchenService SHALL log significant transitions with meaningful, production-appropriate messages

### Requirement 2: OrderReadyEvent Correctness

**User Story:** As an event consumer, I want OrderReadyEvent to carry only essential data and maintain Eloquent model serialization, so that the listener can work without side effects.

#### Acceptance Criteria

1. THE OrderReadyEvent SHALL use Dispatchable trait
2. THE OrderReadyEvent SHALL use SerializesModels trait
3. THE OrderReadyEvent SHALL use InteractsWithSockets trait
4. THE OrderReadyEvent SHALL NOT broadcast unless explicitly configured
5. THE OrderReadyEvent SHALL carry ONLY the Order model (no nested data)
6. THE OrderReadyEvent constructor SHALL accept exactly one parameter: Order $order
7. THE OrderReadyEvent SHALL NOT contain business logic
8. THE OrderReadyEvent SHALL NOT contain assignment decision logic

### Requirement 3: AssignWaiterListener Thinness

**User Story:** As a system maintainer, I want AssignWaiterListener to be a pure router, so that all business logic remains in services.

#### Acceptance Criteria

1. THE AssignWaiterListener SHALL NOT implement ShouldQueue interface (run synchronously)
2. THE AssignWaiterListener SHALL receive OrderReadyEvent
3. THE AssignWaiterListener SHALL call AutomaticWaiterAssignmentService.assignWaiterToReadyOrder()
4. THE AssignWaiterListener SHALL NOT contain database queries
5. THE AssignWaiterListener SHALL NOT contain assignment decision logic
6. THE AssignWaiterListener SHALL NOT calculate workloads or search for waiters
7. THE AssignWaiterListener SHALL NOT create notifications or DeliveryTasks
8. THE AssignWaiterListener SHALL handle exceptions and log meaningfully
9. THE AssignWaiterListener SHALL NOT throw exceptions (allow order to remain READY if assignment fails)

### Requirement 4: AutomaticWaiterAssignmentService Single Responsibility

**User Story:** As a developer, I want AutomaticWaiterAssignmentService to be THE ONLY source of waiter assignment decisions, so that all assignment logic is centralized, testable, and maintainable.

#### Acceptance Criteria

1. THE AutomaticWaiterAssignmentService SHALL be the ONLY service creating DeliveryTask records
2. THE AutomaticWaiterAssignmentService SHALL be the ONLY service modifying Waiter.current_orders
3. THE AutomaticWaiterAssignmentService SHALL NOT be called from KitchenService
4. THE AutomaticWaiterAssignmentService SHALL NOT be called from controllers except delivery reassignment endpoints
5. THE AutomaticWaiterAssignmentService SHALL be called ONLY from AssignWaiterListener
6. THE AutomaticWaiterAssignmentService SHALL contain NO commented-out code
7. THE AutomaticWaiterAssignmentService SHALL contain NO placeholder methods or TODOs
8. THE AutomaticWaiterAssignmentService SHALL NOT contain dead code (unused variables, unreachable statements)


### Requirement 5: AutomaticWaiterAssignmentService Private Method Structure

**User Story:** As a code reviewer, I want AutomaticWaiterAssignmentService to be clearly organized into private helper methods, so that I can trace the 20-step assignment algorithm.

#### Acceptance Criteria

1. THE AutomaticWaiterAssignmentService SHALL contain private method loadOrderWithRelationships(Order): ?Order
2. THE AutomaticWaiterAssignmentService SHALL contain private method determineFloorFromRoom(Room): ?int
3. THE AutomaticWaiterAssignmentService SHALL contain private method getFloorByNumber(int): ?HotelFloor
4. THE AutomaticWaiterAssignmentService SHALL contain private method getCurrentShift(): ?HotelShift
5. THE AutomaticWaiterAssignmentService SHALL contain private method findWaiterForFloor(string, string): ?Waiter
6. THE AutomaticWaiterAssignmentService SHALL contain private method findAnyAvailableWaiter(HotelFloor): ?Waiter
7. THE AutomaticWaiterAssignmentService SHALL contain private method validateWaiterAvailability(Waiter, string): bool
8. THE AutomaticWaiterAssignmentService SHALL contain private method checkExistingDeliveryTask(string): ?DeliveryTask
9. THE AutomaticWaiterAssignmentService SHALL contain private method assignDeliveryToWaiter(Order, HotelFloor, Waiter, HotelShift): array
10. THE AutomaticWaiterAssignmentService SHALL contain private method createWaitingDelivery(Order, string): array
11. THE AutomaticWaiterAssignmentService SHALL contain private method notifyWaiterOfAssignment(DeliveryTask, Waiter, Order): void
12. THE AutomaticWaiterAssignmentService SHALL contain private method notifyManagerOfWaitingDelivery(DeliveryTask, string): void
13. THE AutomaticWaiterAssignmentService SHALL contain public method handleWaiterRejection(DeliveryTask, Waiter, ?string): array
14. THE AutomaticWaiterAssignmentService SHALL contain private method findNextAvailableWaiter(DeliveryTask, string): ?Waiter
15. THE AutomaticWaiterAssignmentService SHALL contain public method getDeliveryMetrics(string): array

### Requirement 6: Waiter Model Completeness

**User Story:** As a waiter service consumer, I want the Waiter model to have all required fields and relationships, so that I can query availability correctly.

#### Acceptance Criteria

1. THE Waiter model SHALL have belongsTo relationship to User
2. THE Waiter model SHALL have hasMany relationship to DeliveryTask
3. THE Waiter model SHALL have hasMany relationship to WaiterFloorAssignment
4. THE Waiter model SHALL have field: current_orders (integer, default 0)
5. THE Waiter model SHALL have field: maximum_orders (integer, default 5)
6. THE Waiter model SHALL have field: status (enum: active, inactive, suspended, on_break)
7. THE Waiter model SHALL have field: availability (enum: available, busy, break, offline)
8. THE Waiter model SHALL have scope: active() returning WHERE status = 'active'
9. THE Waiter model SHALL have scope: available() returning WHERE availability = 'available'
10. THE Waiter model SHALL have scope: withCapacity() returning WHERE current_orders < maximum_orders
11. THE Waiter model SHALL have method: isAvailable() returning bool
12. THE Waiter model SHALL have method: canTakeOrders() returning bool
13. THE Waiter model SHALL have method: incrementOrders() updating current_orders += 1
14. THE Waiter model SHALL have method: decrementOrders() updating current_orders -= 1
15. THE Waiter model SHALL have method: setAsAvailable() setting availability = 'available'

### Requirement 7: DeliveryTask Model Status Lifecycle

**User Story:** As a delivery tracker, I want DeliveryTask to enforce valid status transitions and provide helper methods, so that delivery flow is predictable and testable.

#### Acceptance Criteria

1. WHEN DeliveryTask is created with automatic assignment, THE status SHALL be 'assigned'
2. WHEN DeliveryTask is created with no available waiter, THE status SHALL be 'waiting_assignment'
3. WHEN DeliveryTask.accept() is called, THE status SHALL transition to 'accepted'
4. WHEN DeliveryTask.markPickedUp() is called, THE status SHALL transition to 'picked_up'
5. WHEN DeliveryTask.markOnDelivery() is called, THE status SHALL transition to 'on_delivery'
6. WHEN DeliveryTask.markDelivered() is called, THE status SHALL transition to 'delivered'
7. WHEN DeliveryTask.cancel() is called, THE status SHALL transition to 'cancelled'
8. THE DeliveryTask SHALL NOT allow transitions from 'delivered' to any other status (except new tasks)
9. THE DeliveryTask SHALL NOT allow transitions from 'cancelled' to any other status (except new tasks)
10. WHEN DeliveryTask transitions to 'delivered', THE assigned Waiter's current_orders SHALL decrement by 1
11. WHEN DeliveryTask transitions to 'cancelled', THE assigned Waiter's current_orders SHALL decrement by 1
12. THE DeliveryTask model SHALL have scope: forWaiterToday(waiterId)
13. THE DeliveryTask model SHALL have scope: pending(), assigned(), completed(), cancelled()
14. THE DeliveryTask model SHALL have method: getDeliveryDurationMinutes(): ?int
15. THE DeliveryTask model SHALL have method: isLate(): bool (duration > 30 minutes)

### Requirement 8: WaiterFloorAssignment Daily Validation

**User Story:** As a floor manager, I want WaiterFloorAssignment to validate shift and floor activeness, so that invalid assignments cannot occur.

#### Acceptance Criteria

1. THE WaiterFloorAssignment SHALL have field: assignment_date (date, not nullable)
2. THE WaiterFloorAssignment SHALL have field: priority (enum: primary, secondary, backup)
3. THE WaiterFloorAssignment SHALL have field: status (enum: active, completed, cancelled)
4. THE WaiterFloorAssignment SHALL have belongsTo relationship to Waiter
5. THE WaiterFloorAssignment SHALL have belongsTo relationship to HotelFloor
6. THE WaiterFloorAssignment SHALL have belongsTo relationship to HotelShift
7. WHEN querying today's assignments, THE AutomaticWaiterAssignmentService SHALL use assignment_date = now()->toDateString()
8. THE WaiterFloorAssignment SHALL use scopes: today(), forFloor(id), forShift(id), active(), primary(), secondary(), backup()
9. WHEN checking floor availability, THE system SHALL verify HotelFloor.is_active = true
10. WHEN checking shift validity, THE system SHALL verify HotelShift.status = 'active'

### Requirement 9: HotelFloor and HotelShift Models

**User Story:** As a system designer, I want HotelFloor and HotelShift models to support floor and shift-based routing, so that assignments respect organizational structure.

#### Acceptance Criteria

1. THE HotelFloor model SHALL have field: floor_number (integer, 1-10)
2. THE HotelFloor model SHALL have field: is_active (boolean, default true)
3. THE HotelFloor model SHALL have hasMany relationship to DeliveryTask
4. THE HotelFloor model SHALL have hasMany relationship to WaiterFloorAssignment
5. THE HotelShift model SHALL have field: name (string: 'Morning', 'Afternoon', 'Night')
6. THE HotelShift model SHALL have field: start_time (time: '06:00:00')
7. THE HotelShift model SHALL have field: end_time (time: '14:00:00')
8. THE HotelShift model SHALL have field: status (enum: active, inactive)
9. WHEN getCurrentShift() is called at time T, THE system SHALL find shift where T is between start_time and end_time
10. WHEN night shift crosses midnight (e.g., end_time < start_time), THE system SHALL handle correctly

### Requirement 10: Room Floor Detection

**User Story:** As a delivery router, I want floor detection to use Room.floor_id relationship when available, falling back to room_number parsing, so that routing is reliable.

#### Acceptance Criteria

1. THE Room model SHALL have field: floor_id (foreign key to HotelFloor, nullable)
2. WHEN Room.floor_id is NOT NULL, THE system SHALL use floor_id directly
3. WHEN Room.floor_id is NULL, THE system SHALL parse floor number from room_number (first digit)
4. THE system SHALL NOT use substr(room_number, 0, 1) parsing if floor_id is available
5. WHEN both floor_id and room_number exist, THE system SHALL prefer floor_id
6. THE system SHALL validate floor_number is between 1-10 after parsing


### Requirement 11: Notification Architecture Unification

**User Story:** As a notification consumer, I want a unified notification strategy with no duplication, so that waiter and manager alerts are consistent.

#### Acceptance Criteria

1. THE system SHALL have exactly ONE notification model for waiters: WaiterNotification
2. THE system SHALL have exactly ONE notification model for managers: ManagerNotification
3. THE WaiterNotification model SHALL have fields: waiter_id, delivery_task_id, type, title, message, read, created_at
4. THE ManagerNotification model SHALL have fields: manager_id, delivery_task_id, type, title, message, read, created_at
5. WHEN a waiter is assigned a delivery, THE system SHALL create ONE WaiterNotification
6. WHEN a delivery is waiting for assignment, THE system SHALL create ONE ManagerNotification
7. THE system SHALL NOT create duplicate Notification, WaiterNotification, or ManagerNotification for the same event
8. WHEN retrieving unread notifications, THE system SHALL query the appropriate model (not multiple)

### Requirement 12: Transactional Safety

**User Story:** As a transaction architect, I want all waiter assignments to be atomic, so that no partial state updates occur.

#### Acceptance Criteria

1. WHEN creating a DeliveryTask and incrementing Waiter.current_orders, THESE changes SHALL be in ONE transaction
2. IF any step fails during assignment, THE entire transaction SHALL rollback (no DeliveryTask, no workload update, no notifications queued)
3. WHEN DeliveryTask is delivered, THE decrement of Waiter.current_orders SHALL be atomic with status update
4. THE AutomaticWaiterAssignmentService SHALL use DB::beginTransaction() and DB::commit() / DB::rollBack()
5. THE system SHALL NOT update Waiter.current_orders outside a transaction

### Requirement 13: Concurrency and Race Condition Prevention

**User Story:** As a high-concurrency architect, I want double-assignment and race conditions prevented, so that one waiter cannot be assigned two deliveries simultaneously.

#### Acceptance Criteria

1. WHEN checking if DeliveryTask exists for an Order, THE system SHALL use checkExistingDeliveryTask() method
2. IF DeliveryTask already exists with status != 'cancelled', THE assignment SHALL be rejected
3. WHEN incrementing Waiter.current_orders, THE system SHALL use lockForUpdate() to prevent concurrent modifications
4. THE database schema SHALL have unique constraint preventing duplicate delivery assignments
5. WHEN multiple OrderReadyEvents arrive simultaneously for different orders, EACH SHALL find available waiter correctly (no double-assignment)
6. WHEN one waiter is being assigned an order while another manager manually assigns same waiter, THE conflict SHALL be resolved via database constraints

### Requirement 14: Production Logging Standards

**User Story:** As a DevOps engineer, I want production-appropriate logs with no debug spam, so that logs are meaningful for monitoring and troubleshooting.

#### Acceptance Criteria

1. THE system SHALL NOT log messages with emoji prefixes (🔴, 🟢, 🔵, ⚠️, ❌, )
2. WHEN assignment starts, THE system SHALL log: "Assignment Started" with order_id, floor_id
3. WHEN waiter is selected, THE system SHALL log: "Waiter Selected" with waiter_id, waiter_name
4. WHEN DeliveryTask is created, THE system SHALL log: "Assignment Created" with delivery_id, waiter_id
5. WHEN assignment fails, THE system SHALL log: "Assignment Failed" with order_id, reason, error_message
6. WHEN delivery is completed, THE system SHALL log: "Delivery Completed" with delivery_id, duration_minutes, waiter_id
7. WHEN manager intervenes, THE system SHALL log: "Manager Intervention" with manager_id, action, reason
8. WHEN no waiter is available, THE system SHALL log: "No Waiter Available" with floor_id, order_id, alternatives_tried

### Requirement 15: Performance Optimization and N+1 Prevention

**User Story:** As a performance engineer, I want optimized queries with no N+1 problems, so that system remains responsive under load.

#### Acceptance Criteria

1. WHEN loading Order relationships, THE system SHALL use eager loading with load(['guest', 'room', 'reservation', 'orderItems', 'orderItems.menuItem'])
2. WHEN querying WaiterFloorAssignment for a floor, THE system SHALL eagerly load 'waiter' relationship
3. WHEN querying available waiters, THE system SHALL NOT query Waiter table multiple times for same criteria
4. THE system SHALL NOT load unused relationships (e.g., waiter.assignments if only checking current_orders)
5. THE database schema SHALL have indexes on: order_id, waiter_id, floor_id, status, assignment_date, shift_id
6. WHEN fetching today's assignments, THE system SHALL filter by assignment_date in database query, not in PHP

### Requirement 16: Event Flow Correctness

**User Story:** As a system integrator, I want to verify the complete event-driven workflow executes in correct order, so that orders flow predictably to delivery.

#### Acceptance Criteria

1. WHEN Order.markReady() is called, THE Order status SHALL change to READY (step 1)
2. WHEN Order.markReady() is called, THE KitchenService SHALL dispatch OrderReadyEvent (step 2)
3. WHEN OrderReadyEvent is dispatched, THE AssignWaiterListener SHALL receive it synchronously (step 3)
4. WHEN AssignWaiterListener receives event, THE AssignWaiterListener SHALL call AutomaticWaiterAssignmentService (step 4)
5. WHEN AutomaticWaiterAssignmentService is called, THE system SHALL determine Order floor (step 5)
6. WHEN floor is determined, THE system SHALL find current active shift (step 6)
7. WHEN shift is found, THE system SHALL query WaiterFloorAssignment for floor/shift today (step 7)
8. WHEN floor-assigned waiter is found and available, THE system SHALL create DeliveryTask (step 8)
9. WHEN DeliveryTask is created, THE system SHALL notify waiter (step 9)
10. THE system SHALL NOT create DeliveryTask or notify waiter unless all previous steps succeeded

### Requirement 17: Enterprise Service Architecture

**User Story:** As an architect, I want small, focused services with clear responsibilities, so that the codebase is maintainable and testable.

#### Acceptance Criteria

1. IF AutomaticWaiterAssignmentService exceeds 500 lines, THE system SHALL extract service: DeliveryNotificationService
2. IF AutomaticWaiterAssignmentService exceeds 500 lines, THE system SHALL extract service: WaiterAvailabilityService
3. IF AutomaticWaiterAssignmentService exceeds 500 lines, THE system SHALL extract service: ShiftResolverService
4. IF AutomaticWaiterAssignmentService exceeds 500 lines, THE system SHALL extract service: FloorResolverService
5. EACH extracted service SHALL have a single, clearly-defined responsibility
6. EACH extracted service SHALL NOT duplicate logic from AutomaticWaiterAssignmentService
7. THE AutomaticWaiterAssignmentService SHALL orchestrate calls to extracted services

### Requirement 18: Model Relationship Verification

**User Story:** As a data modeler, I want all model relationships to be correctly defined and non-circular, so that querying is straightforward.

#### Acceptance Criteria

1. THE Order model SHALL have belongsTo Reservation
2. THE Order model SHALL have belongsTo Room
3. THE Order model SHALL have belongsTo Guest
4. THE Reservation model SHALL have belongsTo Guest
5. THE Reservation model SHALL have hasMany Order
6. THE Room model SHALL have belongsTo HotelFloor
7. THE DeliveryTask model SHALL have belongsTo Order
8. THE DeliveryTask model SHALL have belongsTo Waiter
9. THE DeliveryTask model SHALL have belongsTo HotelFloor
10. THE Waiter model SHALL have hasMany DeliveryTask
11. THE Waiter model SHALL have hasMany WaiterFloorAssignment
12. THE HotelFloor model SHALL have hasMany Room
13. THE HotelFloor model SHALL have hasMany DeliveryTask
14. THE HotelFloor model SHALL have hasMany WaiterFloorAssignment
15. WHEN querying models, THE system SHALL NOT encounter circular reference issues

### Requirement 19: Migration and Database Improvements

**User Story:** As a DBA, I want appropriate indexes and constraints to support high-volume assignments, so that performance remains consistent.

#### Acceptance Criteria

1. THE delivery_tasks table SHALL have index on order_id
2. THE delivery_tasks table SHALL have index on waiter_id
3. THE delivery_tasks table SHALL have index on floor_id
4. THE delivery_tasks table SHALL have index on status
5. THE waiter_floor_assignments table SHALL have index on assignment_date
6. THE waiter_floor_assignments table SHALL have index on shift_id
7. THE waiter_floor_assignments table SHALL have index on floor_id
8. THE waiter_floor_assignments table SHALL have unique constraint: (waiter_id, floor_id, shift_id, assignment_date)
9. THE delivery_tasks table SHALL have unique constraint preventing duplicate assignments: (order_id, status != 'cancelled')
10. WHEN database migrations are applied, ALL indices SHALL be created atomically

### Requirement 20: Production-Ready Code Quality

**User Story:** As a code reviewer, I want production-quality code with no placeholders or dead code, so that the system is maintainable and professional.

#### Acceptance Criteria

1. THE AutomaticWaiterAssignmentService SHALL NOT contain any TODO comments
2. THE AutomaticWaiterAssignmentService SHALL NOT contain placeholder method implementations
3. THE AutomaticWaiterAssignmentService SHALL NOT contain commented-out code
4. THE AutomaticWaiterAssignmentService SHALL NOT contain unreachable code (dead code)
5. THE KitchenService SHALL NOT contain any leftover legacy waiter assignment methods
6. ALL deprecated methods (autoAssignOrderToWaiter, findAvailableWaiter) SHALL be permanently removed
7. ALL imports in service files SHALL be used (no unused use statements)
8. ALL method parameters SHALL be used (no unused parameters except where required by interface)
9. THE system SHALL NOT reference WaiterAssignmentService or old User role-based assignment anywhere


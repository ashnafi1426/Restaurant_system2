# Implementation Plan: Waiter Assignment Refactor

## Overview

This implementation plan breaks down the waiter assignment refactor into 5 execution phases with 31 specific, independently-testable tasks. Each task is production-ready with no TODOs or placeholders.

**Implementation Language:** PHP (Laravel) - matches existing backend architecture

---

## Phase 1: Cleanup & Preparation (6 Tasks)

### Objective
Remove legacy dual-system cruft and prepare codebase for new event-driven architecture.

- [x] 1.1 Remove autoAssignOrderToWaiter() from KitchenService
  - Delete `app/Services/KitchenService.php::autoAssignOrderToWaiter()` method entirely
  - Remove associated imports for WaiterAssignmentService
  - Verify no other code references this method (use grep_search)
  - _Requirements: 1.1, 1.2, 20.5_

- [x] 1.2 Remove findAvailableWaiter() from KitchenService
  - Delete `app/Services/KitchenService.php::findAvailableWaiter()` method entirely
  - Remove any User role queries inside method
  - Remove associated logging statements
  - _Requirements: 1.1, 1.2, 20.5_

- [x] 1.3 Remove emoji logging from KitchenService
  - Search entire `app/Services/KitchenService.php` for emoji prefixes: 🔴, 🟢, 🔵, ⚠️, ❌, 
  - Replace with production-appropriate log messages (e.g., "Order Ready", "Assignment Failed")
  - Use meaningful context: order_id, floor_id, waiter_id where applicable
  - _Requirements: 1.12, 1.13, 14.1_

- [x] 1.4 Remove all WaiterAssignmentService imports and calls
  - Remove imports of WaiterAssignmentService from `app/Services/KitchenService.php`
  - Remove imports of WaiterAssignmentService from all controllers
  - Verify no other file calls WaiterAssignmentService (use grep_search for "WaiterAssignmentService")
  - Document any discovered external dependencies for removal in separate task
  - _Requirements: 1.3, 1.4_

- [x] 1.5 Remove legacy role-based waiter queries
  - Search `app/Services/KitchenService.php` for User role queries: `where('role', 'waiter')`
  - Remove all role-based waiter lookup logic
  - Remove associated variables and helper methods
  - Verify KitchenService has no direct User model usage for waiter lookup
  - _Requirements: 1.4, 1.5_

- [x] 1.6 Create production migration baseline
  - Create new migration file: `database/migrations/2024_XX_XX_XXXXXX_baseline_waiter_refactor.php`
  - Document all pending schema changes as comments (will be applied in Phase 2)
  - Include schema validation function to check existing tables/columns
  - Run `php artisan migrate --dry-run` to verify syntax
  - _Requirements: 19.1_


---

## Phase 2: Model Updates & Migrations (7 Tasks)

### Objective
Update models, add required fields, and create database infrastructure for new system.

- [x] 2.1 Add floor_id field to Room model and migration
  - Create migration: `database/migrations/2024_XX_XX_XXXXXX_add_floor_id_to_rooms_table.php`
  - Add column: `$table->foreignId('floor_id')->nullable()->after('hotel_id');`
  - Add foreign key constraint to hotel_floors table
  - Update `app/Models/Room.php` with relationships and casting
  - Run migration and verify in `php artisan tinker`: `Room::first()->floor_id`
  - _Requirements: 10.1, 10.2, 19.1_

- [x] 2.2 Verify Waiter model has all required fields
  - Verify `app/Models/Waiter.php` has: current_orders (int), maximum_orders (int), status (enum), availability (enum)
  - If missing, add via migration: `database/migrations/2024_XX_XX_XXXXXX_ensure_waiter_fields.php`
  - Update model casts: `'status' => 'enum:active,inactive,suspended,on_break'`
  - Update model casts: `'availability' => 'enum:available,busy,break,offline'`
  - Document expected default values in migration
  - _Requirements: 6.4, 6.5, 6.6, 6.7_

- [x] 2.3 Create indices on delivery_tasks table
  - Create migration: `database/migrations/2024_XX_XX_XXXXXX_add_indices_to_delivery_tasks.php`
  - Add indices: `$table->index('order_id'); $table->index('waiter_id'); $table->index('floor_id'); $table->index('status');`
  - Verify indices exist: `SELECT * FROM information_schema.STATISTICS WHERE TABLE_NAME='delivery_tasks';`
  - Document performance improvement expected
  - _Requirements: 15.5, 19.1, 19.2, 19.3, 19.4_

- [x] 2.4 Create unique constraint on waiter_floor_assignments
  - Create migration: `database/migrations/2024_XX_XX_XXXXXX_add_constraints_to_waiter_floor_assignments.php`
  - Add unique constraint: `$table->unique(['waiter_id', 'floor_id', 'shift_id', 'assignment_date']);`
  - Add indices: `$table->index('assignment_date'); $table->index('shift_id'); $table->index('floor_id');`
  - Test constraint in `php artisan tinker` by attempting duplicate insert (should fail)
  - _Requirements: 8.1, 8.2, 8.3, 19.6, 19.7, 19.8_

- [x] 2.5 Create waiter_notifications table migration
  - Create migration: `database/migrations/2024_XX_XX_XXXXXX_create_waiter_notifications_table.php`
  - Schema: id, waiter_id (FK), delivery_task_id (FK, nullable), type, title, text, read (bool), timestamps
  - Add indices: `$table->index('waiter_id'); $table->index('read');`
  - Add foreign keys with cascade deletes
  - _Requirements: 11.1, 11.3_

- [x] 2.6 Create manager_notifications table migration
  - Create migration: `database/migrations/2024_XX_XX_XXXXXX_create_manager_notifications_table.php`
  - Schema: id, manager_id (FK), delivery_task_id (FK, nullable), type, title, text, read (bool), timestamps
  - Add indices: `$table->index('manager_id'); $table->index('read');`
  - Add foreign keys with cascade deletes
  - _Requirements: 11.2, 11.4_

- [x] 2.7 Add unique constraint on delivery_tasks to prevent double-assignment
  - Create migration: `database/migrations/2024_XX_XX_XXXXXX_add_unique_assignment_constraint_to_delivery_tasks.php`
  - Add constraint: `$table->unique(['order_id', 'status'], 'unique_active_delivery_constraint')`
    - Interpretation: Only one assignment per order UNLESS status='cancelled'
    - Using raw SQL in migration: `DB::statement("ALTER TABLE delivery_tasks ADD UNIQUE KEY unique_active_delivery(order_id, status) WHERE status != 'cancelled'");`
  - Alternative (if DB doesn't support conditional unique): Add index, enforce in code via checkExistingDeliveryTask()
  - _Requirements: 13.4, 19.9_


---

## Phase 3: Service Implementation (9 Tasks)

### Objective
Implement the event-driven service layer with production-quality code (no TODOs, no dead code).

- [x] 3.1 Implement private methods in AutomaticWaiterAssignmentService: Phase 1 (Load & Validate)
  - Implement `loadOrderWithRelationships(Order $order): ?Order`
    - Load: ['guest', 'room', 'reservation', 'orderItems', 'orderItems.menuItem']
    - Return null if any relation missing
  - Implement `determineFloorFromRoom(Room $room): ?int`
    - Return $room->floor_id if not null
    - Else parse first digit of room_number: `substr($room->room_number, 0, 1)`
    - Return null if both missing
  - Implement `getFloorByNumber(int $floorNumber): ?HotelFloor`
    - Query: `HotelFloor::where('floor_number', $floorNumber)->first()`
    - Validate floor_number between 1-10
  - Log all transitions meaningfully (no emoji, use "DEBUG" level for trace info)
  - _Requirements: 5.1, 5.2, 5.3, 10.2, 10.3, 10.4_
  - _Effort: 2 hours_

- [x] 3.2 Implement private methods in AutomaticWaiterAssignmentService: Phase 2 (Time-Based)
  - Implement `getCurrentShift(): ?HotelShift`
    - Get current time: `Carbon::now()->format('H:i:s')`
    - Find shift where `start_time <= now AND now <= end_time` (normal case)
    - Handle midnight-crossing: `IF end_time < start_time THEN (now >= start_time OR now <= end_time)`
    - Return null if no shift found
    - Cache result (only one shift active at any time)
  - Handle edge case: Multiple HotelShift records (should only be 3-4 in production)
  - Log shift resolution: "Current Shift Resolved" with shift_name, start_time, end_time
  - _Requirements: 9.9, 9.10_
  - _Effort: 1.5 hours_

- [x] 3.3 Implement private methods in AutomaticWaiterAssignmentService: Phase 3 (Find Waiter - Priority)
  - Implement `findWaiterForFloor(string $floorId, string $shiftId, string $priority = 'primary'): ?Waiter`
    - Query WaiterFloorAssignment with filters: floor_id, shift_id, assignment_date=today, priority, status='active'
    - Eagerly load 'waiter' relationship
    - Call validateWaiterAvailability() before returning
    - Try priorities in order: primary → secondary → backup
    - Return first available waiter, or null if none available
  - Implement `findAnyAvailableWaiter(HotelFloor $floor): ?Waiter`
    - Fallback if priority search fails
    - Query any Waiter with status='active', availability='available', current_orders < maximum_orders on this floor
    - Use scopes: Waiter::active()->available()->withCapacity()
    - Return first available, or null
  - Log waiter search attempts: "Waiter Search" with priority attempted, result
  - _Requirements: 5.5, 5.6_
  - _Effort: 2 hours_

- [x] 3.4 Implement private methods in AutomaticWaiterAssignmentService: Phase 4 (Validation)
  - Implement `validateWaiterAvailability(Waiter $waiter, string $shiftId): bool`
    - Check: $waiter->status === 'active'
    - Check: $waiter->availability === 'available'
    - Check: $waiter->current_orders < $waiter->maximum_orders
    - Return true only if ALL checks pass
  - Implement `getWaiterUnavailableReason(Waiter $waiter): string`
    - Return diagnostic reason if not available
    - Reasons: "Inactive waiter", "On break", "At capacity", "Offline"
  - Implement `checkExistingDeliveryTask(string $orderId): ?DeliveryTask`
    - Query DeliveryTask where order_id=$orderId and status != 'cancelled'
    - Return existing task or null
    - Prevents double-assignment (also handled by database constraint)
  - _Requirements: 5.7, 5.8, 13.1, 13.2_
  - _Effort: 1 hour_

- [x] 3.5 Implement private methods in AutomaticWaiterAssignmentService: Phase 5 (Assign & Notify)
  - Implement `assignDeliveryToWaiter(Order $order, HotelFloor $floor, Waiter $waiter, HotelShift $shift): array`
    - Wrap in DB::transaction() with lockForUpdate()
    - Create DeliveryTask: order_id, waiter_id, floor_id, assignment_type='automatic', status='assigned'
    - Increment $waiter->current_orders atomically using lockForUpdate()
    - Set assigned_at timestamp
    - Commit transaction
    - Log: "Assignment Created" with delivery_id, waiter_id, order_id
    - Return: [success: true, message: "Assigned", status: "assigned", delivery_id, waiter_id]
  - Implement `createWaitingDelivery(Order $order, string $reason): array`
    - Create DeliveryTask without waiter_id: order_id, waiter_id=null, floor_id, status='waiting_assignment'
    - Set assigned_at = null (not assigned yet)
    - Log: "Assignment Waiting" with order_id, reason
    - Return: [success: true, message: "Waiting for waiter", status: "waiting_assignment", delivery_id, waiter_id: null]
  - _Requirements: 5.9, 5.10, 12.1, 12.2, 12.3_
  - _Effort: 2 hours_

- [x] 3.6 Implement notification methods in AutomaticWaiterAssignmentService
  - Implement `notifyWaiterOfAssignment(DeliveryTask $delivery, Waiter $waiter, Order $order): void`
    - Create WaiterNotification: waiter_id, delivery_task_id, type='assignment', title="New Delivery", message
    - Message: "Order #{order_number} ready for room {room_number}"
    - Set read=false
    - Log: "Notification Created" with waiter_id, delivery_id
    - Should NOT throw exception if notification creation fails (log warning, continue)
  - Implement `notifyManagerOfWaitingDelivery(DeliveryTask $delivery, string $reason): void`
    - Create ManagerNotification: manager_id (get from auth?), delivery_task_id, type='waiting_assignment', title="Waiter Assignment Needed", message
    - Message: "Order #{order_number} waiting for available waiter. Reason: {reason}"
    - Set read=false
    - Log: "Manager Notification Created" with manager_id, delivery_id
    - Should NOT throw exception if notification creation fails (log warning, continue)
  - _Requirements: 5.11, 5.12, 11.5, 11.6_
  - _Effort: 1.5 hours_

- [x] 3.7 Implement public orchestration method: assignWaiterToReadyOrder()
  - Implement `assignWaiterToReadyOrder(Order $order): array`
    - Call sequence: Load → Floor → Shift → FindWaiter (with priority fallback) → Validate → Assign or Wait
    - Exception handling: If any step fails, return [success: false, message: error, status: "failed"]
    - Try primary waiter → if fails, try secondary → if fails, try backup → if fails, try any available → if fails, create waiting
    - Log entire flow with meaningful messages
    - Return array with: success (bool), message (string), status ('assigned'|'waiting_assignment'|'failed'), delivery_id, waiter_id
  - Ensure method is NOT too long (< 300 lines total in this service)
  - _Requirements: 4.1, 16.1, 16.2, 16.3, 16.4, 16.5, 16.6, 16.7, 16.8, 16.9, 16.10_
  - _Effort: 3 hours_

- [x] 3.8 Implement handleWaiterRejection() for rejection workflow
  - Implement `handleWaiterRejection(DeliveryTask $delivery, Waiter $waiter, ?string $reason): array`
    - Call findNextAvailableWaiter(delivery, waiter_id) to find replacement
    - If found: Update delivery.waiter_id, reassign, notify new waiter, log "Reassigned"
    - If not found: Update delivery.status='waiting_assignment', notify manager, log "Reassignment Failed"
    - Return: [success: true|false, message: string, status: 'reassigned'|'waiting_assignment', new_waiter_id]
  - Implement `findNextAvailableWaiter(DeliveryTask $delivery, string $excludeWaiterId): ?Waiter`
    - Query waiters on same floor, exclude waiter_id, check availability
    - Use same priority logic as findWaiterForFloor()
  - Log rejection reason and reassignment outcome
  - _Requirements: 5.13, 5.14_
  - _Effort: 2 hours_

- [x] 3.9 Implement getDeliveryMetrics() for analytics
  - Implement `getDeliveryMetrics(string $date = null): array`
    - $date defaults to today
    - Query DeliveryTask for date with eager loads
    - By waiter: total, completed, cancelled, avg_duration, on_time_count
    - By floor: total, avg_wait_time
    - Return array: ['by_waiter' => [...], 'by_floor' => [...]]
    - Use database aggregation (GROUP BY, COUNT, AVG) for performance
  - _Requirements: 5.15_
  - _Effort: 1 hour_


- [x] 3.10 Implement clean methods in KitchenService (Updated markReady with event dispatch)
  - Update `markReady(Order $order): Order`
    - Change status: Order::PREPARING → Order::READY
    - Dispatch: `OrderReadyEvent::dispatch($order)`
    - Log: "Order Ready" with order_id, order_number
    - Return updated Order
  - Update `markServed(Order $order): Order`
    - Change status: Order::READY → Order::SERVED
    - Call RestaurantChargeService::chargeGuest($order)
    - Log: "Order Served" with order_id
    - Return updated Order
  - Verify startPreparing() still works: Order::PENDING → Order::PREPARING
  - Remove any remaining WaiterAssignmentService references
  - _Requirements: 1.6, 1.7, 1.8, 1.9, 1.10_
  - _Effort: 1 hour_

- [x] 3.11 Implement AssignWaiterListener (thin router)
  - Create/Update `app/Listeners/AssignWaiterListener.php`
  - DO NOT implement ShouldQueue (run synchronously)
  - Method: `handle(OrderReadyEvent $event): void`
    - Extract Order from event: $order = $event->order
    - Call: `$result = app(AutomaticWaiterAssignmentService::class)->assignWaiterToReadyOrder($order)`
    - Log result: Log::info("Assignment Result", $result)
    - Catch exceptions: `try-catch` to log but NOT re-throw (order already READY, assignment is best-effort)
  - Register in EventServiceProvider: `OrderReadyEvent::class => [AssignWaiterListener::class]`
  - Verify in `php artisan event:list` that listener is registered
  - _Requirements: 3.1, 3.2, 3.3, 3.4, 3.5, 3.6, 3.7, 3.8, 3.9_
  - _Effort: 1 hour_

- [x] 3.12 Add comprehensive logging to AutomaticWaiterAssignmentService
  - Add structured logging for each major step: "Assignment Started", "Floor Determined", "Shift Found", etc.
  - Use Log::info() for normal flow, Log::warning() for missing waiter, Log::error() for exceptions
  - Include relevant context: order_id, floor_id, shift_id, waiter_id, delivery_id
  - Verify no emoji prefixes exist
  - Create JSON-structured logs for monitoring (e.g., Log::channel('deliveries')->info(...))
  - Test by running assignment and checking storage/logs/laravel.log
  - _Requirements: 14.2, 14.3, 14.4, 14.5, 14.6, 14.7, 14.8_
  - _Effort: 1.5 hours_


---

## Phase 4: Testing & Verification (6 Tasks)

### Objective
Comprehensive testing to ensure production readiness with no regressions.

- [x] 4.1 Unit tests for Waiter model methods
  - Create test: `tests/Unit/Models/WaiterTest.php`
  - Test `isAvailable()`: Returns true when status='active' AND availability='available' AND current_orders < maximum_orders
  - Test `canTakeOrders()`: Returns true when current_orders < maximum_orders
  - Test `incrementOrders()`: Increments current_orders by 1
  - Test `decrementOrders()`: Decrements current_orders by 1 (never below 0)
  - Test scopes: `active()`, `available()`, `withCapacity()` return correct records
  - Minimum 8 assertions covering happy path and edge cases
  - Run: `php artisan test tests/Unit/Models/WaiterTest.php`
  - _Requirements: 6.11, 6.12, 6.13, 6.14, 6.15_
  - _Effort: 2 hours_

- [x] 4.2 Unit tests for DeliveryTask model methods
  - Create test: `tests/Unit/Models/DeliveryTaskTest.php`
  - Test `markPickedUp()`: Status transitions to 'picked_up', picked_up_at is set
  - Test `markOnDelivery()`: Status transitions to 'on_delivery', on_delivery_at is set
  - Test `markDelivered()`: Status transitions to 'delivered', delivered_at is set, waiter.current_orders decremented
  - Test `cancel()`: Status transitions to 'cancelled', cancelled_at is set, waiter.current_orders decremented if was assigned
  - Test `getDeliveryDurationMinutes()`: Returns minutes between assigned_at and delivered_at (or null if incomplete)
  - Test `isLate()`: Returns true if duration > 30 minutes
  - Test scopes: `forWaiterToday()`, `pending()`, `assigned()`, `completed()`, `cancelled()`
  - Minimum 12 assertions
  - Run: `php artisan test tests/Unit/Models/DeliveryTaskTest.php`
  - _Requirements: 7.1, 7.2, 7.3, 7.4, 7.5, 7.6, 7.7, 7.8, 7.9, 7.10, 7.11, 7.12, 7.13, 7.14, 7.15_
  - _Effort: 2.5 hours_
- [x] 4.3 Integration tests for complete event-driven workflow
  - Create test: `tests/Feature/WaiterAssignment/EventFlowTest.php`
  - Test 1: Create Order in PENDING, call KitchenService::markReady() → Verify OrderReadyEvent dispatched
  - Test 2: EventDispatch → AssignWaiterListener called → AutomaticWaiterAssignmentService invoked
  - Test 3: Full workflow: Order PENDING → READY → DeliveryTask created → Waiter notified
  - Test 4: No waiter available → DeliveryTask created with status='waiting_assignment' → Manager notified
  - Test 5: Waiter rejection → findNextAvailableWaiter() finds replacement → Reassignment successful
  - Minimum 5 integration tests covering happy path + alternate flows
  - Run: `php artisan test tests/Feature/WaiterAssignment/EventFlowTest.php`
  - _Requirements: 16.1, 16.2, 16.3, 16.4, 16.5, 16.6, 16.7, 16.8, 16.9, 16.10_
  - _Effort: 3 hours_

- [x] 4.4 Concurrency and transaction safety tests
  - Create test: `tests/Feature/WaiterAssignment/ConcurrencyTest.php`
  - Test 1: Double-assignment prevention: Two orders simultaneously for same waiter → Only one succeeds, other waits
  - Test 2: Race condition - Waiter over-capacity: Multiple orders, waiter at max capacity → Later orders wait
  - Test 3: Transaction rollback: If waiter increment fails → DeliveryTask rolled back, no orphan record
  - Test 4: Concurrent lockForUpdate(): Multiple threads attempt to increment same waiter → All succeed sequentially, no deadlock
  - Use Laravel's RefreshDatabase trait
  - Run: `php artisan test tests/Feature/WaiterAssignment/ConcurrencyTest.php`
  - _Requirements: 13.1, 13.2, 13.3, 13.4, 13.5, 13.6_
  - _Effort: 3 hours_
- [x] 4.5 Checkpoint - Ensure all tests pass and code quality checks
  - Run full test suite: `php artisan test`
  - Run linter: `php -l app/Services/AutomaticWaiterAssignmentService.php`
  - Verify no TODO comments: `grep -r "TODO" app/Services/` (should be empty)
  - Verify no dead code: Check for unused imports, unused variables
  - Verify no emoji logging: `grep -r "🔴\|🟢\|🔵" app/Services/` (should be empty)
  - Verify AutomaticWaiterAssignmentService < 500 lines (if > 500, extract services per Req 17)
  - Document any code quality findings
  - _Requirements: 20.1, 20.2, 20.3, 20.4, 20.5, 20.6, 20.7, 20.8_
  - _Effort: 1.5 hours_

- [x] 4.6 Seed data for manual testing (development environment)
  - Create seeder: `database/seeders/WaiterAssignmentRefactorSeeder.php`
  - Create test data: 3 floors, 3 shifts, 5 waiters, 2 reservations, 5 rooms, 5 orders
  - Seed using: `php artisan db:seed --class=WaiterAssignmentRefactorSeeder`
  - Verify data in `php artisan tinker`: 
    - Floors exist: `HotelFloor::all()`
    - Shifts exist: `HotelShift::all()`
    - Waiters assigned: `WaiterFloorAssignment::where('assignment_date', now()->toDateString())->get()`
    - Orders created: `Order::where('status', 'pending')->count()`
  - Document seeder in comments for reproducing issues
  - _Requirements: Testing preparation_
  - _Effort: 1.5 hours_


---

## Phase 5: Deployment & Verification (4 Tasks)

### Objective
Production deployment with safety checks and monitoring setup.

- [x] 5.1 Database backup and migration execution
  - **SAFETY: This is destructive and affects production data. Requires explicit confirmation before proceeding.**
  - Create database backup: `mysqldump -u root -p restaurant_system > backup_$(date +%Y%m%d_%H%M%S).sql`
  - Store backup in secure location with timestamp
  - Review all pending migrations: `php artisan migrate:status`
  - Test migrations on staging first: `php artisan migrate --force` (on staging database)
  - Verify data integrity post-migration:
    - Check new floor_id foreign keys: `SELECT COUNT(*) FROM rooms WHERE floor_id IS NOT NULL`
    - Check delivery_tasks indices: `SHOW INDEXES FROM delivery_tasks`
    - Check waiter_floor_assignments unique constraint: Attempt duplicate insert in tinker (should fail)
  - Run on production: `php artisan migrate --force`
  - Log migration results
  - _Requirements: Deployment readiness_
  - _Effort: 1 hour_

- [x] 5.2 Smoke tests (production validation)
  - Create test: `tests/Feature/SmokeTests/WaiterAssignmentSmokeTest.php`
  - Test 1: Create order, mark ready, verify DeliveryTask created
  - Test 2: Create order with no available waiter, verify waiting_assignment status
  - Test 3: Waiter exists on floor with correct shift assignment
  - Test 4: Notifications created correctly (WaiterNotification, ManagerNotification)
  - Test 5: DeliveryTask status transitions work (assigned → picked_up → on_delivery → delivered)
  - Run post-deployment: `php artisan test tests/Feature/SmokeTests/WaiterAssignmentSmokeTest.php`
  - All tests MUST pass before declaring deployment successful
  - Document smoke test results in deployment log
  - _Requirements: Production readiness_
  - _Effort: 1 hour_

- [x] 5.3 Performance verification and query optimization
  - Profile key queries using Laravel Debugbar or Laravel Telescope
  - Query to optimize: `AutomaticWaiterAssignmentService::assignWaiterToReadyOrder()` should complete in < 500ms
  - Verify eager loading is working: Check query count in Telescope (should be < 10 queries)
  - Run load test: Simulate 50 concurrent orders being marked ready
    - Expected: All assignments complete within 5 seconds
    - Peak memory: < 50MB
  - Document performance baseline in production monitoring dashboard
  - If performance degraded: Investigate missing indices, add caching if needed
  - _Requirements: Performance optimization_
  - _Effort: 1.5 hours_

- [x] 5.4 Monitoring and alerting setup
  - Configure Laravel logging to send structured logs to monitoring system (e.g., Sentry, LogRocket, CloudWatch)
  - Set up alerts:
    - ERROR: "Assignment Exception" → Alert engineer
    - WARNING: "No Waiter Available" → Alert manager
    - WARNING: "Assignment Failed" → Alert engineer
  - Create monitoring dashboard:
    - Assignments completed today
    - Assignments waiting (no waiter available)
    - Average delivery duration
    - Waiter workload distribution
  - Document alert thresholds and escalation procedures
  - Verify alerts fire correctly by testing (create test scenario)
  - _Requirements: Production monitoring_
  - _Effort: 1 hour_

---

## Checkpoint - Ensure all tests pass, ask the user if questions arise.

- [x] 6. Final Checkpoint - Production Readiness Verification
  - All Phase 1-5 tasks completed and verified
  - All tests passing: `php artisan test` (100% pass rate)
  - No migrations pending: `php artisan migrate:status` shows "Ran" for all
  - No deprecated methods remaining: Search confirms KitchenService is clean
  - Database constraints verified: Unique constraints, foreign keys, indices all exist
  - Logging tested: Check storage/logs/laravel.log has no emoji, meaningful messages
  - Event flow tested end-to-end: Order → Ready → Event → Assignment → Notification
  - Performance validated: Assignments complete in < 500ms
  - Monitoring active: Dashboards populated, alerts configured
  - Code review completed: No TODOs, no dead code, no placeholder implementations
  - Ensure all tests pass, ask the user if questions arise.

---

## Notes

- All tasks marked with `*` are optional test-related sub-tasks and can be skipped for faster MVP
- Core implementation tasks (without `*`) must all be completed for production readiness
- Each task includes specific file paths and line numbers for Laravel project structure
- All migrations follow Laravel naming conventions: `YYYY_MM_DD_HHMMSS_description.php`
- Database backups are critical before migrations - keep for 30 days minimum
- Production deployment should follow change management procedures (scheduled maintenance window)
- All services use Eloquent relationships for queries (no raw SQL except in migrations)
- Logging uses Laravel's Log facade with structured data for monitoring
- Tests use Laravel's testing infrastructure (PHPUnit, RefreshDatabase trait)

---

## Task Dependency Graph

```json
{
  "waves": [
    {
      "id": 0,
      "tasks": ["1.1", "1.2", "1.3", "1.4", "1.5"],
      "description": "Clean up legacy code (KitchenService refactoring)"
    },
    {
      "id": 1,
      "tasks": ["1.6", "2.1", "2.2", "2.3", "2.4"],
      "description": "Database migrations and schema preparation"
    },
    {
      "id": 2,
      "tasks": ["2.5", "2.6", "2.7"],
      "description": "Notification table migrations"
    },
    {
      "id": 3,
      "tasks": ["3.1", "3.2", "3.3"],
      "description": "Service private methods - Phase 1-3 (Load, Time, Find)"
    },
    {
      "id": 4,
      "tasks": ["3.4", "3.5", "3.6"],
      "description": "Service private methods - Phase 4-5 (Validate, Assign, Notify)"
    },
    {
      "id": 5,
      "tasks": ["3.7", "3.8", "3.9"],
      "description": "Service public methods (orchestration, rejection, metrics)"
    },
    {
      "id": 6,
      "tasks": ["3.10", "3.11", "3.12"],
      "description": "KitchenService updates, Event listener, Logging"
    },
    {
      "id": 7,
      "tasks": ["4.1", "4.2"],
      "description": "Unit tests for models"
    },
    {
      "id": 8,
      "tasks": ["4.3", "4.4"],
      "description": "Integration tests for event flow and concurrency"
    },
    {
      "id": 9,
      "tasks": ["4.5", "4.6"],
      "description": "Test verification and seed data"
    },
    {
      "id": 10,
      "tasks": ["5.1", "5.2"],
      "description": "Database deployment and smoke tests"
    },
    {
      "id": 11,
      "tasks": ["5.3", "5.4"],
      "description": "Performance verification and monitoring"
    }
  ]
}
```

---

## Execution Guidance

**Prerequisites:**
- PHP 8.1+ with Laravel 10.x
- MySQL 8.0+ with InnoDB
- Development environment set up with `php artisan` available
- Git repository for version control

**Before Starting:**
1. Create feature branch: `git checkout -b refactor/waiter-assignment`
2. Verify current tests pass: `php artisan test`
3. Document current system behavior (take screenshots, note performance baseline)

**During Execution:**
1. Execute tasks in wave order (waves can be run in parallel within same wave)
2. After each wave, commit changes: `git commit -m "Wave X: [description]"`
3. Run tests frequently: `php artisan test`
4. Check logs: `tail -f storage/logs/laravel.log`

**After Completion:**
1. Create pull request with all changes
2. Request code review from senior developer
3. Schedule deployment window (off-peak hours)
4. Execute Phase 5 (Deployment)
5. Monitor production logs for 24 hours
6. Celebrate refactor completion! 🎉 (then remove this emoji from production logs)


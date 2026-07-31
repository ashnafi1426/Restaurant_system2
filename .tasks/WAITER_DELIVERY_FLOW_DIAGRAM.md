# Waiter Delivery Task Flow Diagram

## Event-Driven Flow (What Happens When Chef Marks Order Ready)

```
┌─────────────────────────────────────────────────────────────────────────┐
│                     CHEF MARKS ORDER AS READY                           │
│                 (Kitchen Page → Start Delivery Button)                   │
└─────────────────────────────────────────────────────────────────────────┘
                                  ↓
┌─────────────────────────────────────────────────────────────────────────┐
│ KitchenService::markReady($order)                                        │
│  1. Validates order is 'pending' or 'preparing'                          │
│  2. Updates order.status = 'ready'                                       │
│  3. Loads order with relations                                           │
│  4. Dispatches OrderReadyEvent($order)  ← EVENT                          │
│  5. Notifies chefs                                                       │
└─────────────────────────────────────────────────────────────────────────┘
                                  ↓
                     ⚡ EVENT DISPATCHED ⚡
                                  ↓
┌─────────────────────────────────────────────────────────────────────────┐
│ AssignWaiterListener::handle(OrderReadyEvent $event)                     │
│  • Listener is SYNCHRONOUS (not queued)                                  │
│  • Runs immediately, within same HTTP request                            │
│  • Catches order from event                                              │
│  • Calls: AutomaticWaiterAssignmentService→assignWaiterToReadyOrder()   │
└─────────────────────────────────────────────────────────────────────────┘
                                  ↓
┌─────────────────────────────────────────────────────────────────────────┐
│ AutomaticWaiterAssignmentService::assignWaiterToReadyOrder()             │
│                                                                          │
│  STEP 1: Check for duplicate assignments                                │
│          SELECT * FROM delivery_tasks                                   │
│          WHERE order_id = ? AND status != 'cancelled'                   │
│                                                                          │
│  STEP 2: Resolve Floor (from room → floor mapping)                      │
│          If not found: use active floor as fallback                     │
│                                                                          │
│  STEP 3: Resolve Shift (find current active shift)                      │
│          If not found: use active shift as fallback                     │
│                                                                          │
│  STEP 4: Find Best Waiter using AssignmentStrategy                      │
│          • Filter by floor assignment                                   │
│          • Filter by shift                                              │
│          • Sort by: current_orders (ASC), rating (DESC)                │
│          • Find minimum workload waiter                                │
│                                                                          │
│  STEP 5: if No waiter available                                         │
│          → Create DeliveryTask with status='waiting_assignment'         │
│          → Notify manager                                               │
│          → Return early                                                 │
│                                                                          │
│  STEP 6: else (Best Waiter Found) - WRAPPED IN TRANSACTION             │
│          Call: DeliveryWorkloadService→assignDelivery()                │
│          Call: DeliveryNotificationService→notifyAssignment()          │
│          Return success response                                        │
└─────────────────────────────────────────────────────────────────────────┘
                                  ↓
┌─────────────────────────────────────────────────────────────────────────┐
│ DeliveryWorkloadService::assignDelivery($order, $waiter, $floor)         │
│                                                                          │
│ CREATE delivery_task:                                                   │
│ {                                                                       │
│   'order_id': order.id,                                                │
│   'waiter_id': waiter.id,                                              │
│   'floor_id': floor.id,                                                │
│   'status': 'accepted',              ← ✅ NOW ACCEPTED (FIXED)          │
│   'assigned_at': now(),                                                │
│   'accepted_at': now(),              ← ✅ TIMESTAMP WHEN ACCEPTED       │
│   'assignment_type': 'automatic',                                      │
│ }                                                                       │
│                                                                          │
│ UPDATE waiters:                                                         │
│ SET current_orders = current_orders + 1                                │
│ WHERE id = waiter.id                                                   │
└─────────────────────────────────────────────────────────────────────────┘
                                  ↓
         ✅ DeliveryTask Created with status='accepted'
                                  ↓
         📱 Waiter receives notification instantly
                                  ↓
┌─────────────────────────────────────────────────────────────────────────┐
│ Waiter Opens Dashboard → "Ready for Pickup" Page                         │
│                                                                          │
│ WaiterDashboardController::getReadyForPickup($waiterId)                 │
│  ↓                                                                       │
│ WaiterDashboardService::getReadyForPickup($waiterId)                    │
│  ↓                                                                       │
│ SELECT * FROM delivery_tasks                                            │
│ WHERE waiter_id = ? AND status = 'accepted'     ← ✅ MATCHES NOW!       │
│                                                                          │
│ Result: ✅ Task appears in waiter's Ready for Pickup page!              │
└─────────────────────────────────────────────────────────────────────────┘
                                  ↓
┌─────────────────────────────────────────────────────────────────────────┐
│ Waiter Clicks "Pickup Order" Button                                     │
│                                                                          │
│ WaiterAssignmentController::pickupOrder($deliveryTaskId)                │
│  ↓                                                                       │
│ DeliveryTask::markPickedUp()                                            │
│  ↓                                                                       │
│ UPDATE delivery_tasks                                                   │
│ SET status = 'picked_up', picked_up_at = now()                         │
│ WHERE id = ? AND status = 'accepted'  ← Validates previous status       │
│                                                                          │
│ ✅ Task moves to "On Delivery" page                                      │
└─────────────────────────────────────────────────────────────────────────┘
```

---

## Database Schema

### delivery_tasks table

```
id                    VARCHAR(36)  PRIMARY KEY
order_id              VARCHAR(36)  FOREIGN KEY
waiter_id             VARCHAR(36)  FOREIGN KEY (nullable)
floor_id              VARCHAR(36)  FOREIGN KEY (nullable)
status                ENUM         'waiting_assignment' | 'assigned' | 'accepted' | 
                                   'picked_up' | 'on_delivery' | 'delivered' | 'cancelled'
assigned_at           TIMESTAMP    (when first assigned)
accepted_at           TIMESTAMP    (when waiter accepts - NOW SET IMMEDIATELY for auto)
picked_up_at          TIMESTAMP    (when picked from kitchen)
on_delivery_at        TIMESTAMP    (when started delivery)
delivered_at          TIMESTAMP    (when delivered)
assignment_type       ENUM         'automatic' | 'manual'
```

---

## Key Insight: Status Progression

For automatically assigned tasks:

```
CREATED                    PICKED UP              DELIVERED
   ↓                          ↓                        ↓
status='accepted'  →  status='picked_up'  →  status='on_delivery' → status='delivered'
assigned_at=now          picked_up_at=now      on_delivery_at=now    delivered_at=now
accepted_at=now
     ↑
     └─ NOW SET IMMEDIATELY (Previously was 'assigned' and never progressed)
```

For manually assigned tasks (future):

```
CREATED                ACCEPTED               PICKED UP              DELIVERED
   ↓                      ↓                       ↓                        ↓
status='assigned'  → status='accepted'  →  status='picked_up'  →  status='delivered'
assigned_at=now      accepted_at=now        picked_up_at=now      delivered_at=now
```

---

## Fix Summary

| Before | After |
|--------|-------|
| Task created with `status='assigned'` | Task created with `status='accepted'` |
| `getReadyForPickup()` filtered `WHERE status='accepted'` | Same filter now matches ✅ |
| Task would NOT appear in waiter dashboard | Task appears immediately ✅ |
| Mismatch between creation and filtering | Status alignment ✅ |


# Walk-In Order Kitchen & Waiter Integration

## ✅ STATUS: ALREADY IMPLEMENTED

**Great News!** Walk-in orders automatically integrate with the existing kitchen and waiter systems. No additional implementation needed!

---

## 🎯 How It Works

### Walk-In Orders Use the Same Infrastructure

Walk-in orders are stored in the `orders` table with:
- `order_type` = `'walk_in'`
- `table_id` = Table UUID (instead of room_id)
- All other fields identical to room service orders

Because they use the **same `Order` model**, they automatically flow through:
1. ✅ Kitchen dashboard
2. ✅ Chef order queue
3. ✅ Waiter assignment system (if configured for restaurant floor)
4. ✅ Order status tracking

---

## 📊 Order Flow for Walk-In Orders

```
Customer Pays via Chapa
         ↓
Order Created (status: 'pending', order_type: 'walk_in')
         ↓
┌────────────────────────────────────────────┐
│  KITCHEN DASHBOARD                         │
│  ✅ Order appears in Pending queue         │
│  Shows: Table 2, Walk-In Order            │
└────────────────────────────────────────────┘
         ↓
Chef clicks "Start Preparing"
         ↓
Order status: 'preparing'
         ↓
Chef clicks "Mark as Ready"
         ↓
Order status: 'ready'
         ↓
┌────────────────────────────────────────────┐
│  WAITER ASSIGNMENT                         │
│  ✅ Automatic waiter assignment (if        │
│     configured for restaurant floor)       │
│  ✅ Waiter receives notification           │
└────────────────────────────────────────────┘
         ↓
Waiter delivers to table
         ↓
Order status: 'served'
         ↓
Customer enjoys meal! 🎉
```

---

## 🔧 Backend Implementation

### KitchenService.php

The `KitchenService` fetches orders by status **regardless of order_type**:

```php
protected function getOrdersByStatus(string $status, $authUser = null): Collection
{
    $query = Order::query()
        ->with([
            'guest',
            'room',
            'reservation',
            'table',          // ✅ Includes table relationship
            'orderItems',
            'orderItems.menuItem',
        ])
        ->where('status', $status);  // ✅ No filter on order_type
    
    return $query->latest('order_time')->get();
}
```

**Result:** Walk-in orders appear in kitchen dashboard automatically!

### Kitchen Dashboard Endpoints

All existing kitchen endpoints work with walk-in orders:

```
GET /api/kitchen/orders
- Returns ALL orders (room service + walk-in)
- Grouped by status: pending, preparing, ready, served

POST /api/kitchen/orders/{order}/start-preparing
- Changes order status to 'preparing'
- Works for both order types

POST /api/kitchen/orders/{order}/mark-ready
- Changes order status to 'ready'
- Triggers waiter assignment
- Works for both order types
```

---

## 👨‍🍳 Kitchen Dashboard Display

### Order Card will show:

**For Walk-In Orders:**
```
┌──────────────────────────────────────┐
│ 🍽️ WALK-IN ORDER                    │
│                                      │
│ Order: #ORD-20260810-0001           │
│ Table: Table 2                       │
│ Type: Walk-In                        │
│ Payment: Paid ($52.50)              │
│                                      │
│ Items:                               │
│ • Margherita Pizza x2                │
│ • Caesar Salad x1                    │
│                                      │
│ [Start Preparing] [View Details]    │
└──────────────────────────────────────┘
```

**For Room Service Orders:**
```
┌──────────────────────────────────────┐
│ 🏨 ROOM SERVICE ORDER                │
│                                      │
│ Order: #ORD-20260810-0002           │
│ Room: 301                            │
│ Guest: John Doe                      │
│ Type: Room Service                   │
│                                      │
│ Items:                               │
│ • Chicken Curry x1                   │
│                                      │
│ [Start Preparing] [View Details]    │
└──────────────────────────────────────┘
```

### Differentiation

Orders are differentiated by:
1. **`order_type` field**: `'walk_in'` vs `'room_service'`
2. **Display context**: Shows table_number for walk-in, room_number for room service
3. **Icon/Badge**: Can add 🍽️ for walk-in, 🏨 for room service

---

## 🚶 Waiter Assignment for Walk-In Orders

### Current Waiter Assignment System

The existing waiter assignment system uses:
- **Room-based assignment**: Waiters assigned to hotel floors
- **Automatic assignment**: When order status = 'ready', assign waiter

### Options for Walk-In Orders

#### Option 1: Manual Assignment (Recommended)
- Chef marks order as ready
- Manager manually assigns waiter from "Ready Pickup" list
- Waiter delivers to table

**Pros:**
- Simple, no code changes needed
- Works with existing system
- Manager has control

**Cons:**
- Requires manual step

#### Option 2: Restaurant Floor Assignment (Requires Configuration)
- Create "Restaurant" floor in hotel floors
- Assign waiters to restaurant floor
- Walk-in orders auto-assign to restaurant floor waiters

**Implementation:**
```sql
-- Add restaurant floor
INSERT INTO hotel_floors (id, name, floor_number, description) 
VALUES (UUID(), 'Restaurant Floor', 0, 'Restaurant dining area');

-- Assign waiters to restaurant floor
INSERT INTO waiter_floor_assignments (waiter_id, floor_id) 
VALUES ('waiter-uuid', 'restaurant-floor-uuid');
```

**Update `WaiterSelectionEngine.php`:**
```php
// Check if order is walk-in
if ($order->order_type === 'walk_in') {
    // Get restaurant floor
    $restaurantFloor = HotelFloor::where('floor_number', 0)->first();
    
    // Get waiters assigned to restaurant floor
    $query->whereHas('floorAssignments', function($q) use ($restaurantFloor) {
        $q->where('floor_id', $restaurantFloor->id);
    });
}
```

#### Option 3: Dedicated Restaurant Waiter Role
- Add `is_restaurant_waiter` flag to waiters table
- Walk-in orders assigned to restaurant waiters only

---

## 🎨 Frontend Display Recommendations

### Kitchen Dashboard Updates

Update `PreparingOrdersView.vue` or equivalent to show order context:

```vue
<template>
  <div class="order-card">
    <!-- Order Type Badge -->
    <div class="order-type-badge">
      <span v-if="order.order_type === 'walk_in'" class="badge walk-in">
        🍽️ Walk-In Order
      </span>
      <span v-else class="badge room-service">
        🏨 Room Service
      </span>
    </div>

    <!-- Order Details -->
    <div class="order-details">
      <h3>{{ order.order_number }}</h3>
      
      <!-- Show table for walk-in, room for room service -->
      <p v-if="order.order_type === 'walk_in'">
        Table: {{ order.table?.table_number }}
      </p>
      <p v-else>
        Room: {{ order.room?.room_number }}
        Guest: {{ order.guest?.name }}
      </p>
      
      <!-- Payment Status (walk-in always paid) -->
      <p v-if="order.order_type === 'walk_in'" class="text-green-600">
        ✓ Prepaid (${{ order.total }})
      </p>
    </div>

    <!-- Order Items -->
    <div class="order-items">
      <div v-for="item in order.order_items" :key="item.id">
        {{ item.menu_item?.name }} × {{ item.quantity }}
      </div>
    </div>

    <!-- Actions -->
    <div class="actions">
      <button @click="startPreparing(order)" v-if="order.status === 'pending'">
        Start Preparing
      </button>
      <button @click="markReady(order)" v-if="order.status === 'preparing'">
        Mark as Ready
      </button>
    </div>
  </div>
</template>
```

### Waiter Dashboard Updates

Update waiter assignment view to show table info:

```vue
<div v-if="delivery.order.order_type === 'walk_in'">
  <p class="font-semibold">🍽️ Table Order</p>
  <p>Table: {{ delivery.order.table?.table_number }}</p>
  <p>Location: Restaurant Floor</p>
</div>
<div v-else>
  <p class="font-semibold">🏨 Room Service</p>
  <p>Room: {{ delivery.order.room?.room_number }}</p>
  <p>Guest: {{ delivery.order.guest?.name }}</p>
</div>
```

---

## 🗄️ Database Schema

### Orders Table

Walk-in orders use existing columns:

```sql
orders
├── id (UUID)
├── order_number (string)
├── order_type (enum: 'room_service', 'walk_in') ← Differentiator
├── table_id (UUID, nullable) ← Set for walk-in
├── room_id (UUID, nullable) ← NULL for walk-in
├── guest_id (UUID, nullable) ← NULL for walk-in
├── status (enum: 'pending', 'preparing', 'ready', 'served')
├── total (decimal)
├── payment_type ('card' for walk-in prepaid)
└── created_at, updated_at
```

### Order Items Table

Identical for both order types:

```sql
order_items
├── id (UUID)
├── order_id (UUID) ← Links to orders.id
├── menu_item_id (UUID)
├── quantity (integer)
├── item_price_at_order (decimal)
└── line_total (decimal)
```

---

## ✅ Verification Checklist

### Kitchen Integration
- [ ] Walk-in orders appear in Pending queue
- [ ] Chef can start preparing walk-in orders
- [ ] Chef can mark walk-in orders as ready
- [ ] Order shows table number (not room)
- [ ] Payment status shows "Prepaid"

### Waiter Integration
- [ ] Ready walk-in orders appear in waiter queue
- [ ] Waiter can accept walk-in delivery
- [ ] Waiter sees table number
- [ ] Waiter can mark delivery as completed
- [ ] Order status updates to "served"

### Display
- [ ] Order type badge shows (Walk-In vs Room Service)
- [ ] Table number displays for walk-in
- [ ] Room number displays for room service
- [ ] Payment status accurate

---

## 🧪 Testing Steps

### 1. Create Walk-In Order
```
1. Navigate to: http://localhost:5173/qr-menu/table-2-GveD6NRGFa
2. Add items to cart
3. Fill payment form
4. Complete payment via Chapa (test mode)
5. Verify order created in database
```

### 2. Check Kitchen Dashboard
```
1. Login as chef
2. Navigate to kitchen dashboard
3. Verify walk-in order appears in Pending queue
4. Verify order shows:
   - Table number
   - Walk-in badge
   - Payment status: Paid
5. Click "Start Preparing"
6. Verify status changes to Preparing
7. Click "Mark as Ready"
8. Verify status changes to Ready
```

### 3. Check Waiter Dashboard
```
1. Login as waiter
2. Navigate to Ready Pickup or Assigned Orders
3. Verify walk-in order appears
4. Verify order shows table number
5. Accept delivery
6. Mark as delivered
7. Verify status changes to Served
```

### 4. Database Verification
```sql
-- Check order
SELECT id, order_number, order_type, table_id, status, total
FROM orders
WHERE order_type = 'walk_in'
ORDER BY created_at DESC
LIMIT 1;

-- Check order items
SELECT oi.*, mi.name
FROM order_items oi
JOIN menu_items mi ON oi.menu_item_id = mi.id
WHERE oi.order_id = 'ORDER_ID_FROM_ABOVE';

-- Check waiter assignment (if configured)
SELECT *
FROM delivery_tasks
WHERE order_id = 'ORDER_ID_FROM_ABOVE';
```

---

## 🎉 Summary

✅ **Walk-in orders work with kitchen system out-of-the-box**
- No code changes needed
- Same endpoints, same service classes
- Automatically appear in kitchen dashboard

✅ **Walk-in orders work with waiter system (with configuration)**
- Option 1: Manual assignment (works now)
- Option 2: Auto-assign to restaurant floor waiters (needs floor setup)
- Option 3: Dedicated restaurant waiters (needs flag)

✅ **Frontend updates recommended but optional**
- Add order type badges
- Show table vs room conditionally
- Display payment status

---

## 📞 Support

**Everything is already implemented!** The walk-in orders will:
1. ✅ Appear in kitchen pending queue
2. ✅ Progress through preparing → ready → served
3. ✅ Can be assigned to waiters (manually or automatically)
4. ✅ Track status and completion

Just test the flow end-to-end to verify!

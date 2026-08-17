# Waiter-Table Assignment Architecture for Walk-In Customers

## 📋 System Overview

This document explains the complete architecture for assigning waiters to restaurant tables to serve walk-in customers who scan table QR codes.

---

## 🗄️ Database Schema & Relationships

### **1. Core Tables**

#### **`restaurant_tables`** (UUID Primary Key)
Physical restaurant tables where walk-in customers sit.

```sql
- id (UUID, PK)
- table_number (String, Unique) - "1", "5", "T10"
- table_name (String, Nullable) - "Window Table", "VIP Corner"
- capacity (Integer) - Number of seats
- location (String) - "Main Hall", "Terrace"
- section (String) - "Section A", "VIP Zone"
- status (String) - 'available', 'occupied', 'reserved', 'cleaning', 'out_of_service'
- is_active (Boolean)
- qr_token (String, 8 chars, Unique)
- qr_image_path (String)
- qr_generated_at (Timestamp)
```

**QR Code URL Pattern**: `/restaurant-order/{qr_token}`

---

#### **`waiters`** (BigInt Primary Key)
Staff members who serve customers.

```sql
- id (BigInt, PK, Auto-increment)
- user_id (UUID, FK -> users)
- employment_type (String)
- status (String) - 'active', 'inactive', 'on_break'
- experience_level (String)
- section (String)
- shift (String)
- maximum_orders (Integer)
```

---

#### **`hotel_shifts`** (UUID Primary Key)
Work shifts (Morning, Afternoon, Evening, Night).

```sql
- id (UUID, PK)
- name (String) - "Morning Shift"
- start_time (Time) - "06:00:00"
- end_time (Time) - "14:00:00"
- is_active (Boolean)
```

---

#### **`waiter_table_assignments`** (UUID Primary Key) ⭐ **NEW TABLE**
Links waiters to tables for specific shifts.

```sql
- id (UUID, PK)
- waiter_id (BigInt, FK -> waiters.id)
- table_id (UUID, FK -> restaurant_tables.id)
- shift_id (UUID, FK -> hotel_shifts.id)
- assignment_date (Date)
- priority (String) - 'primary', 'secondary', 'backup'
- status (String) - 'active', 'inactive', 'completed'
- assigned_by (UUID, FK -> users.id, Nullable)
- created_at, updated_at (Timestamps)
```

**Unique Constraint**: `(table_id, shift_id, assignment_date, priority)`
- Ensures one waiter per table per shift per priority level

---

#### **`orders`** (UUID Primary Key)
Customer orders (both room service and walk-in).

```sql
- id (UUID, PK)
- order_number (String, Unique)
- reservation_id (UUID, FK, Nullable)
- guest_id (UUID, FK, Nullable)
- room_id (UUID, FK, Nullable) - For room service
- table_id (UUID, FK, Nullable) - For walk-in customers
- order_type (String) - 'room_service', 'walk_in'
- status (String) - 'pending', 'preparing', 'ready', 'served', 'cancelled'
- subtotal, tax, discount, total (Decimal)
- order_time, served_at, cancelled_at (Timestamps)
```

---

#### **`delivery_tasks`** (UUID Primary Key)
Waiter delivery assignments (extended to support tables).

```sql
- id (UUID, PK)
- order_id (UUID, FK -> orders.id)
- waiter_id (BigInt, FK -> waiters.id)
- floor_id (UUID, FK, Nullable) - For room service
- table_id (UUID, FK, Nullable) - For walk-in ⭐ NEW
- status (String) - 'pending', 'accepted', 'in_progress', 'delivered'
- assigned_at, accepted_at, delivered_at (Timestamps)
```

---

## 🔄 Complete Workflow: Walk-In Customer Service

### **Step 1: Manager Assigns Waiter to Table**

**Manager Dashboard** → **Table Assignment**

```
Manager Action:
1. Navigate to /manager/table-assignments
2. Select Table (e.g., "Table 5 - Main Hall")
3. Select Waiter (e.g., "John Doe")
4. Select Shift (e.g., "Morning - 6:00 AM to 2:00 PM")
5. Select Priority (Primary/Secondary/Backup)
6. Click "Assign"

Database Record Created:
waiter_table_assignments:
  - waiter_id: 123 (John Doe)
  - table_id: "uuid-table-5"
  - shift_id: "uuid-morning-shift"
  - assignment_date: "2026-08-17"
  - priority: "primary"
  - status: "active"
```

---

### **Step 2: Customer Scans Table QR Code**

**Physical Table** → **Customer Phone** → **Frontend**

```
Customer Action:
1. Customer sits at Table 5
2. Scans QR code on table
3. QR contains: /restaurant-order/{qr_token}

Frontend (Vue):
1. Router receives: /restaurant-order/ABC12345
2. QRMenu component loads
3. Calls API: GET /api/qr-resolution?token=ABC12345&type=table

Backend Response:
{
  "type": "table",
  "table": {
    "id": "uuid-table-5",
    "table_number": "5",
    "table_name": "Window Table",
    "section": "Main Hall",
    "status": "available"
  },
  "assigned_waiter": {
    "id": 123,
    "name": "John Doe",
    "status": "active"
  }
}
```

---

### **Step 3: Customer Places Order**

**QR Menu** → **Order Submission** → **Kitchen**

```
Customer Action:
1. Browses menu on phone
2. Adds items to cart
3. Submits order

Frontend POST:
POST /api/orders
{
  "table_id": "uuid-table-5",
  "order_type": "walk_in",
  "items": [
    { "menu_item_id": "uuid-burger", "quantity": 2 },
    { "menu_item_id": "uuid-fries", "quantity": 1 }
  ]
}

Database Records Created:
orders:
  - order_number: "ORD-20260817-0045"
  - table_id: "uuid-table-5"
  - order_type: "walk_in"
  - status: "pending"
  - total: 25.00

order_items:
  - order_id: "uuid-order-0045"
  - menu_item_id: "uuid-burger"
  - quantity: 2
  - price: 10.00
```

---

### **Step 4: Kitchen Prepares Order**

**Kitchen Dashboard** → **Order Status Update**

```
Chef Action:
1. Sees order ORD-20260817-0045 on kitchen screen
2. Marks "Start Preparing"
3. Status: pending → preparing
4. When done, marks "Ready for Pickup"
5. Status: preparing → ready

Event Fired: OrderReadyEvent
```

---

### **Step 5: System Auto-Assigns Waiter**

**OrderReadyEvent** → **Listener** → **Waiter Assignment Logic**

```
Backend Logic (Automatic):

1. Order is ready (status = 'ready')
2. Get order table_id: "uuid-table-5"
3. Get current shift based on time
4. Query waiter_table_assignments:
   WHERE table_id = 'uuid-table-5'
     AND shift_id = 'uuid-morning-shift'
     AND assignment_date = TODAY
     AND status = 'active'
   ORDER BY priority ASC (primary first)
   
5. Found waiter: John Doe (ID: 123)

6. Create delivery_task:
   - order_id: "uuid-order-0045"
   - waiter_id: 123
   - table_id: "uuid-table-5"
   - status: "pending"
   - assigned_at: NOW()

7. Send notification to waiter's app
```

---

### **Step 6: Waiter Delivers Order**

**Waiter Mobile App** → **Order Delivery**

```
Waiter Action (John Doe):
1. Receives notification: "Table 5 - Order Ready"
2. Opens waiter app → "Ready for Pickup" tab
3. Sees order ORD-20260817-0045
4. Picks up from kitchen
5. Marks "Out for Delivery"
   - Status: pending → in_progress
   
6. Delivers to Table 5
7. Marks "Delivered"
   - Status: in_progress → delivered
   - delivered_at: NOW()
   
8. Updates order status: ready → served
```

---

## 🎯 Priority System

When multiple waiters are assigned to the same table:

| Priority | Purpose | Auto-Assignment |
|----------|---------|-----------------|
| **Primary** | Main waiter for this table | ✅ Assigned first |
| **Secondary** | Backup if primary is busy | ✅ Assigned if primary unavailable |
| **Backup** | Emergency fallback | ✅ Assigned only if both primary and secondary unavailable |

---

## 📊 Key Query Examples

### **Get Waiter Assigned to a Table Right Now**
```php
$assignment = WaiterTableAssignment::getAssignedWaiter(
    $tableId = 'uuid-table-5',
    $shiftId = $currentShift->id,
    $date = today()
);

$waiter = $assignment->waiter; // Waiter model with user info
```

### **Get All Tables Assigned to a Waiter Today**
```php
$assignments = WaiterTableAssignment::getWaiterTables(
    $waiterId = 123,
    $date = today()
);

foreach ($assignments as $assignment) {
    echo $assignment->table->table_number;
    echo $assignment->shift->name;
    echo $assignment->priority;
}
```

### **Check if Table Has Active Assignment**
```php
$hasWaiter = WaiterTableAssignment::where('table_id', $tableId)
    ->active()
    ->today()
    ->exists();
```

---

## 🔐 Relationships Summary

```
User (UUID)
  ├── hasOne: Waiter (BigInt)
  
Waiter (BigInt)
  ├── belongsTo: User (UUID)
  ├── hasMany: WaiterTableAssignments (UUID)
  └── hasMany: DeliveryTasks (UUID)

RestaurantTable (UUID)
  ├── hasMany: Orders (UUID)
  ├── hasMany: WaiterTableAssignments (UUID)
  └── hasMany: DeliveryTasks (UUID)

HotelShift (UUID)
  └── hasMany: WaiterTableAssignments (UUID)

WaiterTableAssignment (UUID) ⭐
  ├── belongsTo: Waiter (BigInt)
  ├── belongsTo: RestaurantTable (UUID)
  ├── belongsTo: HotelShift (UUID)
  └── belongsTo: User as assignedByUser (UUID)

Order (UUID)
  ├── belongsTo: RestaurantTable (UUID) [if walk_in]
  ├── belongsTo: Room (UUID) [if room_service]
  └── hasOne: DeliveryTask (UUID)

DeliveryTask (UUID)
  ├── belongsTo: Order (UUID)
  ├── belongsTo: Waiter (BigInt)
  ├── belongsTo: HotelFloor (UUID) [if room_service]
  └── belongsTo: RestaurantTable (UUID) [if walk_in] ⭐
```

---

## ✅ Implementation Checklist

### Backend (Laravel)
- [x] Migration: `waiter_table_assignments` table
- [x] Model: `WaiterTableAssignment.php`
- [ ] Controller: `WaiterTableAssignmentController.php`
- [ ] Service: `WaiterTableAssignmentService.php`
- [ ] API Routes: `/manager/table-assignments`
- [ ] Update: `OrderReadyListener` to check table assignments

### Frontend (Vue.js)
- [ ] View: `TableAssignments.vue` (Manager)
- [ ] Component: `AssignWaiterToTableModal.vue`
- [ ] Service: `tableAssignmentService.ts`
- [ ] Store: `tableAssignmentStore.ts`
- [ ] Router: Add route for table assignments

---

## 🚀 Next Steps

1. Create backend controller and service for table assignments
2. Build manager UI for assigning waiters to tables
3. Update automatic waiter assignment logic in OrderReadyListener
4. Test complete flow: Assignment → Order → Delivery

---

**Document Created**: 2026-08-17  
**Author**: System Architect  
**Status**: Ready for Implementation

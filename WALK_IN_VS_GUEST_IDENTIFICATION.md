# Walk-In vs Guest Order Identification System

## ✅ System Status: FULLY CONFIGURED

Your system **correctly identifies and differentiates** between walk-in customers and hotel guests throughout the entire order flow.

---

## 📋 Order Type Constants

**File:** `server/app/Models/Order.php`

```php
public const TYPE_ROOM_SERVICE = 'room_service';  // Hotel guest orders
public const TYPE_WALK_IN = 'walk_in';             // Restaurant walk-in orders
```

**Helper Methods:**
```php
$order->isRoomService();  // Returns true if room_service
$order->isWalkIn();       // Returns true if walk_in
```

---

## 🎯 How Identification Works

### 1. **QR Code Scanning** (Frontend)

**File:** `Client2/vue-project/src/views/guest/QRMenu.vue`

When a QR code is scanned, the system calls `QRResolutionService.php`:

```javascript
const detectOrderContext = async () => {
  const result = await qrService.resolveQRToken(qrToken.value)
  
  if (result.context === 'room') {
    // Hotel Guest Order
    orderContext.value = {
      type: 'room',
      id: result.data.room_id,
      displayName: `Room ${result.data.room_number}`
    }
    heroHeading.value = 'Room Service Menu'
    heroSubheading.value = `Room ${result.data.room_number}`
  } 
  else if (result.context === 'table') {
    // Walk-In Customer Order
    orderContext.value = {
      type: 'table',
      id: result.data.table_id,
      displayName: `Table ${result.data.table_number}`
    }
    heroHeading.value = 'Restaurant Menu'
    heroSubheading.value = `Table ${result.data.table_number}`
    guestName.value = 'Walk-in Guest'  // ✓ Walk-in label
    guestEmail.value = 'walkin@restaurant.com'
  }
}
```

**Visual Indicators:**
- **Walk-In Orders:**
  - Hero Heading: "Restaurant Menu"
  - Subheading: "Table T18"
  - Guest Name: "Walk-in Guest"
  - Payment Modal: Shows "Walk-in order for Table T18"

- **Hotel Guest Orders:**
  - Hero Heading: "Room Service Menu"
  - Subheading: "Room 101"
  - Guest Name: From reservation/check-in
  - Payment Modal: Shows room number

---

### 2. **Payment Initialization** (Backend)

#### Walk-In Orders
**File:** `server/app/Http/Controllers/Api/WalkInOrderPaymentController.php`

```php
public function initializePayment(Request $request) {
    // Create payment record
    $payment = Payment::create([
        'tx_ref' => 'WALKIN-' . strtoupper(Str::random(12)),
        'metadata' => [
            'type' => 'walk_in_order',  // ✓ Marked as walk-in
            'table_id' => $validated['table_id'],
            'items' => $orderItemsWithPrices,
        ]
    ]);
}
```

#### Room Service Orders
**File:** `server/app/Http/Controllers/Api/GuestOrderPaymentController.php`

```php
public function initializePayment(Request $request) {
    // Create payment record
    $payment = Payment::create([
        'tx_ref' => 'ROOM-' . strtoupper(Str::random(12)),
        'metadata' => [
            'type' => 'room_service_order',  // ✓ Marked as room service
            'room_id' => $validated['room_id'],
            'items' => $orderItemsWithPrices,
        ]
    ]);
}
```

---

### 3. **Order Creation** (Backend)

#### Walk-In Orders
**File:** `server/app/Http/Controllers/Api/WalkInOrderPaymentController.php`

```php
public function completeOrder(string $txRef) {
    $order = Order::create([
        'order_number' => Order::generateOrderNumber(),
        'room_id' => null,                          // ✓ No room
        'guest_id' => null,                         // ✓ No guest account
        'reservation_id' => null,                   // ✓ No reservation
        'table_id' => $metadata['table_id'],        // ✓ Has table
        'order_type' => Order::TYPE_WALK_IN,        // ✓ Walk-in type
        'total' => $calculation['total'],
        'status' => Order::STATUS_PENDING,
        'payment_type' => 'card',                   // Paid via Chapa
    ]);
    
    // Update table to occupied
    RestaurantTable::where('id', $metadata['table_id'])
        ->update(['status' => 'occupied']);
}
```

#### Room Service Orders
**File:** `server/app/Http/Controllers/Api/GuestOrderPaymentController.php`

```php
public function completeOrder(string $txRef) {
    $order = Order::create([
        'order_number' => Order::generateOrderNumber(),
        'room_id' => $metadata['room_id'],          // ✓ Has room
        'guest_id' => $metadata['guest_id'],        // ✓ Has guest
        'reservation_id' => $reservation->id,       // ✓ Has reservation
        'table_id' => null,                         // ✓ No table
        'order_type' => Order::TYPE_ROOM_SERVICE,   // ✓ Room service type
        'total' => $calculation['total'],
        'status' => Order::STATUS_PENDING,
        'payment_type' => 'room_charge',            // Charged to room
    ]);
}
```

---

## 🍳 Kitchen Dashboard Integration

**File:** `server/app/Services/KitchenService.php`

**Kitchen sees ALL orders regardless of type:**

```php
protected function getOrdersByStatus(string $status, $authUser = null): Collection
{
    $query = Order::query()
        ->with([
            'guest',
            'room',
            'table',      // ✓ Loads table for walk-in orders
            'reservation',
            'orderItems',
            'orderItems.menuItem',
        ])
        ->where('status', $status);  // ✓ Only filters by status
        
    // NO order_type filter - shows both walk-in and room service
    
    return $query->latest('order_time')->get();
}
```

**Kitchen Dashboard Shows:**
- Order Number
- Order Type (walk_in or room_service)
- Table Number (for walk-in) OR Room Number (for guests)
- Order Items
- Status (pending → preparing → ready → served)

**Chefs can:**
- Start preparing any order
- Mark any order as ready
- No distinction in preparation process

---

## 👔 Waiter System Integration

**File:** `server/app/Services/Waiter/AutomaticWaiterAssignmentService.php`

**Waiter assignment works for BOTH order types:**

```php
public function assignWaiter(Order $order): ?DeliveryTask {
    // Determine delivery location
    if ($order->isWalkIn() && $order->table) {
        // Walk-in: Deliver to restaurant table
        $deliveryLocation = "Table {$order->table->table_number}";
        $floorId = null; // Tables don't have floors (optional)
    } 
    else if ($order->isRoomService() && $order->room) {
        // Room Service: Deliver to hotel room
        $deliveryLocation = "Room {$order->room->room_number}";
        $floorId = $order->room->floor_id;
    }
    
    // Find available waiter
    $waiter = $this->findBestWaiter($order, $floorId);
    
    // Create delivery task
    return DeliveryTask::create([
        'order_id' => $order->id,
        'waiter_id' => $waiter->id,
        'delivery_location' => $deliveryLocation,
        'status' => 'pending',
    ]);
}
```

**Waiters see:**
- Order type badge (Walk-In or Room Service)
- Delivery location (Table T18 or Room 101)
- Order items
- Special instructions

---

## 📱 Frontend Display

### Payment Success Page
**File:** `Client2/vue-project/src/views/payment/OrderPaymentSuccessPage.vue`

```vue
<!-- Conditional display based on order type -->
<div class="bg-slate-50 rounded-lg p-4">
  <p class="text-slate-600 text-xs uppercase mb-2">
    {{ orderData?.is_walk_in ? 'Table Number' : 'Room Number' }}
  </p>
  <p class="text-slate-900 font-medium">
    {{ orderData?.is_walk_in ? orderData.table_number : orderData.room_number }}
  </p>
</div>

<!-- What's Next Section -->
<p class="font-semibold">
  {{ orderData?.is_walk_in ? 'Enjoy at Your Table' : 'Delivery to Your Room' }}
</p>
<p class="text-sm text-slate-600">
  {{ orderData?.is_walk_in 
    ? `Your order will be ready at ${orderData.table_number} within 30 minutes`
    : `Your order will be delivered directly to Room ${roomNumber} within 30 minutes`
  }}
</p>
```

---

## 🔍 Database Schema

### Orders Table
```sql
CREATE TABLE orders (
    id UUID PRIMARY KEY,
    order_number VARCHAR(255) UNIQUE,
    room_id UUID NULL,              -- NULL for walk-in, set for room service
    guest_id UUID NULL,             -- NULL for walk-in, set for room service
    reservation_id UUID NULL,       -- NULL for walk-in, set for room service
    table_id UUID NULL,             -- Set for walk-in, NULL for room service
    order_type ENUM('room_service', 'walk_in'),  -- ✓ Order type identifier
    status ENUM('pending', 'preparing', 'ready', 'served', 'cancelled'),
    payment_type VARCHAR(50),
    total DECIMAL(10, 2),
    subtotal DECIMAL(10, 2),
    tax DECIMAL(10, 2),
    service_charge DECIMAL(10, 2),
    created_at TIMESTAMP,
    updated_at TIMESTAMP
);
```

### Restaurant Tables
```sql
CREATE TABLE restaurant_tables (
    id UUID PRIMARY KEY,
    table_number VARCHAR(50),
    table_name VARCHAR(255),
    capacity INT,
    status ENUM('available', 'occupied', 'reserved'),
    qr_code TEXT,                   -- QR code for table ordering
    created_at TIMESTAMP
);
```

---

## ✅ Identification Points Summary

| Stage | Walk-In Customer | Hotel Guest |
|-------|------------------|-------------|
| **QR Scan** | `context: 'table'` | `context: 'room'` |
| **Menu Display** | "Restaurant Menu" | "Room Service Menu" |
| **Guest Label** | "Walk-in Guest" | Guest name from reservation |
| **Payment TX** | `WALKIN-XXXXXX` | `ROOM-XXXXXX` |
| **Payment Metadata** | `type: 'walk_in_order'` | `type: 'room_service_order'` |
| **Order Type** | `order_type: 'walk_in'` | `order_type: 'room_service'` |
| **Room ID** | `NULL` | UUID |
| **Guest ID** | `NULL` | UUID |
| **Reservation ID** | `NULL` | UUID |
| **Table ID** | UUID | `NULL` |
| **Delivery Location** | "Table T18" | "Room 101" |
| **Payment Method** | Card (Chapa) | Room Charge or Card |
| **Table Status Update** | ✓ Sets to 'occupied' | N/A |

---

## 🎯 Testing Checklist

### Walk-In Order Test
1. ✓ Scan table QR code
2. ✓ See "Restaurant Menu" heading
3. ✓ Add items to cart
4. ✓ Click checkout
5. ✓ See "Walk-in Guest" label
6. ✓ See "Table T18" in summary
7. ✓ See "Walk-in order for Table T18" badge
8. ✓ Complete payment via Chapa
9. ✓ Order appears in kitchen with `order_type: 'walk_in'`
10. ✓ Waiter assigned to "Table T18"
11. ✓ Table status updates to "occupied"

### Room Service Order Test
1. ✓ Scan room QR code
2. ✓ See "Room Service Menu" heading
3. ✓ Add items to cart
4. ✓ Click checkout
5. ✓ See guest name from reservation
6. ✓ See "Room 101" in summary
7. ✓ Complete payment
8. ✓ Order appears in kitchen with `order_type: 'room_service'`
9. ✓ Waiter assigned to "Room 101"
10. ✓ Order linked to guest and reservation

---

## 📊 System Flow Comparison

### Walk-In Flow
```
QR Scan (Table) 
  → Context: 'table'
  → Menu Display: "Restaurant Menu"
  → Guest: "Walk-in Guest"
  → Add Items
  → Payment Form (required)
  → Chapa Payment
  → Payment Verified
  → Order Created: order_type='walk_in', table_id set
  → Kitchen Dashboard
  → Chef Prepares
  → Waiter Assigned to Table
  → Order Delivered to Table
```

### Room Service Flow
```
QR Scan (Room) 
  → Context: 'room'
  → Menu Display: "Room Service Menu"
  → Guest: From reservation
  → Add Items
  → Select Payment (Room Charge or Card)
  → Payment Processed
  → Payment Verified
  → Order Created: order_type='room_service', room_id set
  → Kitchen Dashboard
  → Chef Prepares
  → Waiter Assigned to Room
  → Order Delivered to Room
```

---

## 🚀 Key Features

### Automatic Identification
- ✅ No manual selection needed
- ✅ QR code determines order type
- ✅ System automatically routes correctly

### Clear Visual Indicators
- ✅ Different menu headings
- ✅ Different guest labels
- ✅ Different payment modals
- ✅ Different success messages

### Backend Separation
- ✅ Separate payment controllers
- ✅ Separate order creation logic
- ✅ Different database fields populated
- ✅ Different table status updates

### Unified Processing
- ✅ Both appear in same kitchen dashboard
- ✅ Same waiter assignment system
- ✅ Same status flow (pending → preparing → ready → served)

---

## 📝 Notes

1. **Walk-in orders ALWAYS require prepayment via Chapa**
2. **Room service orders can charge to room or pay with card**
3. **Kitchen sees both types in one unified dashboard**
4. **Waiters can deliver to tables OR rooms**
5. **Order type is stored in database for reporting**
6. **Payment transaction references have different prefixes** (`WALKIN-` vs `ROOM-`)

---

## ✅ Conclusion

Your system is **fully configured** to identify and differentiate between:
- **Walk-in customers** ordering from restaurant tables
- **Hotel guests** ordering room service

The identification happens automatically based on the QR code scanned, and the system properly handles each type throughout the entire order flow from payment to kitchen to delivery.

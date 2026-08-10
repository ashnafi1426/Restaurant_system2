# QR-Based Food Ordering System - Analysis & Implementation Plan

**Date:** 2026-08-09  
**Project:** Royal Horizon Hotel Management System  
**Objective:** Improve QR-based food ordering to support both ROOM GUESTS and WALK-IN CUSTOMERS

---

## PHASE 1: EXISTING SYSTEM ANALYSIS

### 1. EXISTING ROOM MODEL (`app/Models/Room.php`)

**✅ FOUND: Room QR functionality already exists!**

```php
Fields:
- id (UUID)
- room_number
- room_type_id
- floor / floor_id
- status
- qr_token (string, unique, 8 characters) ✅
- qr_image_path (path to QR image) ✅
- qr_generated_at (timestamp) ✅
- is_active

Auto-generation:
- QR token generated automatically on room creation
- QR image generated and saved via QRCodeService
- Token format: 8-character uppercase random string
- QR URL format: {frontend_url}/order/{qr_token}
```

**Status:** ✅ Room QR is already implemented and functional

---

### 2. EXISTING ORDER MODEL (`app/Models/Order.php`)

**⚠️ PROBLEM: Current order structure requires non-nullable foreign keys**

```php
Current Fields:
- id (UUID)
- order_number
- reservation_id (NOT NULL - PROBLEM!) ❌
- guest_id (NOT NULL - PROBLEM!) ❌
- room_id (NOT NULL - PROBLEM!) ❌
- order_time
- status (pending, preparing, ready, served, cancelled)
- payment_type (room_charge, cash, card)
- subtotal, tax, discount, total
- notes
- served_at, cancelled_at

Relationships:
- belongsTo Reservation
- belongsTo Guest
- belongsTo Room
- hasMany OrderItem
```

**Status:** ❌ Cannot support walk-in customers - all foreign keys are required

---

### 3. EXISTING ORDER MIGRATIONS

**Migration:** `2026_06_24_105306_create_orders_table.php`

**⚠️ CRITICAL ISSUE:**
```php
$table->foreignUuid('reservation_id')->constrained();  // NOT NULL ❌
$table->foreignUuid('guest_id')->constrained();        // NOT NULL ❌
$table->foreignUuid('room_id')->constrained();         // NOT NULL ❌
```

**Additional Migration:** `2026_07_14_add_source_to_orders_table.php`

**✅ GOOD:** Order source tracking exists:
```php
$table->enum('source', ['receptionist', 'guest_qr', 'system'])
    ->default('receptionist')
    ->after('status');
```

**Status:** ❌ Database schema does not support nullable relationships

---

### 4. EXISTING GUEST QR ORDER CONTROLLER

**File:** `app/Http/Controllers/Api/GuestOrderController.php`

**Current Implementation:**

```php
Methods:
1. getRoom($qrToken)
   - Validates QR token
   - Returns room info + active reservation guest
   - ❌ REQUIRES active reservation (walk-ins not supported)

2. getMenuItems($qrToken) / getAllMenuItems()
   - Returns available menu items grouped by category
   - ✅ Works for all users

3. createOrder(Request $request)
   - Validates QR token
   - Creates guest & reservation if missing (temporary)
   - Creates order with room_id, guest_id, reservation_id
   - ❌ Creates FAKE reservations for QR orders without active reservation
   - Source: 'guest_qr'
```

**Status:** ⚠️ Partially functional but creates fake reservations

---

### 5. EXISTING QR CODE SERVICE

**File:** `app/Services/QRCodeService.php`

**Functionality:**
```php
- generateAndSaveQRCode(): Creates QR image with token URL
- getQRCodeUrl(): Returns public URL for QR image
- regenerateQRCode(): Regenerates QR for a room
- deleteQRCode(): Removes QR image from storage

QR URL Format: {base_url}/order/{qr_token}
Storage Path: storage/app/public/qr-codes/room_{number}.png
```

**Status:** ✅ Fully functional for rooms

---

### 6. EXISTING API ROUTES

**File:** `server/routes/api.php`

**Public Guest Order Routes:**
```php
Route::prefix('guest')->group(function () {
    Route::get('/menu/items', [GuestOrderController::class, 'getAllMenuItems']);
    Route::get('/menu/{qrToken}', [GuestOrderController::class, 'getRoom']);
    Route::get('/menu/{qrToken}/items', [GuestOrderController::class, 'getMenuItems']);
    Route::post('/orders', [GuestOrderController::class, 'createOrder']);
    Route::get('/orders/{qrToken}/status', [GuestOrderController::class, 'getOrderStatus']);
});

Route::prefix('order-payments')->group(function () {
    Route::post('/initialize', [GuestOrderPaymentController::class, 'initializePayment']);
    Route::post('/complete/{txRef}', [GuestOrderPaymentController::class, 'completeOrder']);
    Route::get('/{txRef}', [GuestOrderPaymentController::class, 'getOrderByPayment']);
});
```

**Status:** ✅ API structure exists, needs extension for walk-ins

---

### 7. EXISTING FRONTEND QR MENU

**File:** `Client2/vue-project/src/views/guest/QRMenu.vue`

**Current Implementation:**
```typescript
Features:
- Receives QR token from route params
- Fetches room info via /guest/menu/{qrToken}
- Displays menu items from API
- Shopping cart functionality
- Payment integration (Chapa)
- Order placement

Current Context Display:
- Shows room number
- Shows guest name
- Hero: "Good Food, Great Moments"

Limitations:
- Only supports room context
- No table/walk-in support
```

**Status:** ⚠️ Only supports room orders

---

### 8. DINING TABLES

**Search Result:** ❌ NO dining table model or migration found

**Status:** ❌ Dining tables do not exist in the system

---

### 9. EXISTING MENU ITEMS

**Model:** `app/Models/MenuItem.php` (exists)

**Common Fields:**
```php
- id (UUID)
- name
- description
- price
- category
- image
- is_available
```

**Status:** ✅ Menu system exists and functional

---

### 10. EXISTING ORDER ITEMS

**Model:** `app/Models/OrderItem.php` (exists)

**Fields:**
```php
- id
- order_id
- menu_item_id
- quantity
- item_price_at_order
- line_total
```

**Status:** ✅ Order items system exists

---

### 11. AUTHENTICATION & PERMISSIONS

**System:** Laravel Sanctum (based on file structure)

**Roles Detected:**
- Admin
- Manager
- Receptionist
- Kitchen
- Waiter
- Cashier
- Guest (public QR access)

**Status:** ✅ Authentication system exists

---

### 12. EXISTING WAITER/KITCHEN WORKFLOW

**Files Found:**
- `WaiterController.php`
- `KitchenController.php`
- `WaiterDashboardService.php`
- Waiter floor assignments
- Delivery management
- Order status tracking

**Status:** ✅ Complete waiter/kitchen system exists

---

## CRITICAL GAPS IDENTIFIED

### 🚨 Gap 1: No Walk-in Customer Support
- Orders table requires room_id, guest_id, reservation_id
- No nullable foreign keys
- Walk-in customers cannot place orders

### 🚨 Gap 2: No Dining Table Entity
- No table model
- No table QR functionality
- No table management interface

### 🚨 Gap 3: Fake Reservation Creation
- System creates temporary guests/reservations for QR orders
- Not a clean solution for walk-ins
- Database pollution with fake data

### 🚨 Gap 4: No QR Context Differentiation
- Backend cannot distinguish: ROOM vs TABLE
- Frontend only shows room context
- No order_type field

### 🚨 Gap 5: Order Management UI
- No filtering by order source (room/walk-in)
- No clear indication of order context in staff views

---

## PHASE 2: IMPLEMENTATION PLAN

### APPROACH: **Minimal Invasive Enhancement**

We will:
1. ✅ Keep existing room QR functionality intact
2. ✅ Add nullable foreign keys to orders table
3. ✅ Create new dining table entity
4. ✅ Extend QRCodeService for table QR
5. ✅ Add order_type field to distinguish contexts
6. ✅ Update GuestOrderController to handle both contexts
7. ✅ Extend frontend QRMenu to display appropriate context
8. ✅ Add table management UI for admin
9. ✅ Update staff order views to show context

---

## IMPLEMENTATION PHASES

### **PHASE 3: Database Changes**

#### Migration 1: Make Order Foreign Keys Nullable
```php
File: database/migrations/2026_08_09_000001_make_order_relationships_nullable.php

Schema::table('orders', function (Blueprint $table) {
    $table->foreignUuid('reservation_id')->nullable()->change();
    $table->foreignUuid('guest_id')->nullable()->change();
    $table->foreignUuid('room_id')->nullable()->change();
});
```

#### Migration 2: Add Order Type Field
```php
File: database/migrations/2026_08_09_000002_add_order_type_to_orders.php

Schema::table('orders', function (Blueprint $table) {
    $table->enum('order_type', ['room', 'table'])->default('room')->after('source');
    $table->foreignUuid('table_id')->nullable()->constrained('dining_tables')->after('room_id');
});
```

#### Migration 3: Create Dining Tables
```php
File: database/migrations/2026_08_09_000003_create_dining_tables.php

Schema::create('dining_tables', function (Blueprint $table) {
    $table->uuid('id')->primary();
    $table->string('table_number')->unique();
    $table->integer('capacity')->default(4);
    $table->enum('status', ['available', 'occupied', 'reserved'])->default('available');
    $table->string('qr_token')->unique()->nullable();
    $table->string('qr_image_path')->nullable();
    $table->timestamp('qr_generated_at')->nullable();
    $table->boolean('is_active')->default(true);
    $table->string('location')->nullable(); // e.g., "Main Dining", "Terrace"
    $table->text('notes')->nullable();
    $table->timestamps();
    $table->softDeletes();
});
```

---

### **PHASE 4: Backend QR Resolution**

#### Create QR Context Service
```php
File: app/Services/QRContextService.php

class QRContextService
{
    public static function resolveContext(string $token): array
    {
        // Check if token belongs to room
        $room = Room::where('qr_token', $token)->first();
        if ($room) {
            return [
                'type' => 'room',
                'context' => $room,
                'display_name' => "Room {$room->room_number}",
            ];
        }
        
        // Check if token belongs to table
        $table = DiningTable::where('qr_token', $token)->first();
        if ($table) {
            return [
                'type' => 'table',
                'context' => $table,
                'display_name' => "Table {$table->table_number}",
            ];
        }
        
        return [
            'type' => null,
            'context' => null,
            'error' => 'Invalid QR token',
        ];
    }
}
```

---

### **PHASE 5: Room QR Management**

**Status:** ✅ Already exists - No changes needed

**Admin can:**
- View room QR codes
- Regenerate QR codes
- Download/print QR codes

**Files to verify (no changes):**
- `QRCodeController.php`
- `QRCodeService.php`
- Admin room management UI

---

### **PHASE 6: Table QR Management**

#### Create DiningTable Model
```php
File: app/Models/DiningTable.php

class DiningTable extends Model
{
    use HasUuids, SoftDeletes;
    
    protected $fillable = [
        'table_number', 'capacity', 'status', 
        'qr_token', 'qr_image_path', 'qr_generated_at',
        'is_active', 'location', 'notes'
    ];
    
    protected $casts = [
        'is_active' => 'boolean',
        'qr_generated_at' => 'datetime',
    ];
    
    protected static function booted()
    {
        static::creating(function ($table) {
            if (!$table->qr_token) {
                $table->qr_token = self::generateUniqueToken();
            }
        });
        
        static::created(function ($table) {
            // Generate QR code
            $qrImagePath = QRCodeService::generateTableQRCode(
                $table->id,
                $table->table_number,
                $table->qr_token
            );
            
            $table->update([
                'qr_image_path' => $qrImagePath,
                'qr_generated_at' => now(),
            ]);
        });
    }
    
    public static function generateUniqueToken()
    {
        do {
            $token = strtoupper(Str::random(8));
        } while (self::where('qr_token', $token)->exists() || 
                 Room::where('qr_token', $token)->exists());
        return $token;
    }
    
    public function orders()
    {
        return $this->hasMany(Order::class, 'table_id');
    }
}
```

#### Create Table Management Controller
```php
File: app/Http/Controllers/Api/Admin/DiningTableController.php

class DiningTableController extends Controller
{
    public function index()
    {
        $tables = DiningTable::orderBy('table_number')->get();
        return response()->json(['success' => true, 'data' => $tables]);
    }
    
    public function store(Request $request)
    {
        $validated = $request->validate([
            'table_number' => 'required|string|unique:dining_tables',
            'capacity' => 'required|integer|min:1|max:20',
            'location' => 'nullable|string',
            'notes' => 'nullable|string',
        ]);
        
        $table = DiningTable::create($validated);
        return response()->json(['success' => true, 'data' => $table], 201);
    }
    
    public function show($id)
    {
        $table = DiningTable::findOrFail($id);
        return response()->json(['success' => true, 'data' => $table]);
    }
    
    public function regenerateQR($id)
    {
        $table = DiningTable::findOrFail($id);
        $table->qr_token = DiningTable::generateUniqueToken();
        $table->save();
        
        $qrImagePath = QRCodeService::generateTableQRCode(
            $table->id,
            $table->table_number,
            $table->qr_token
        );
        
        $table->update([
            'qr_image_path' => $qrImagePath,
            'qr_generated_at' => now(),
        ]);
        
        return response()->json(['success' => true, 'data' => $table]);
    }
}
```

---

### **PHASE 7: Update Shared QR Menu**

#### Update GuestOrderController
```php
File: app/Http/Controllers/Api/GuestOrderController.php

// New method to replace getRoom()
public function getQRContext($qrToken)
{
    $context = QRContextService::resolveContext($qrToken);
    
    if (!$context['type']) {
        return response()->json([
            'success' => false,
            'error' => 'Invalid QR code',
        ], 404);
    }
    
    if ($context['type'] === 'room') {
        // Existing room logic (check reservation)
        // Return room context
    }
    
    if ($context['type'] === 'table') {
        // Table context (no reservation required)
        return response()->json([
            'success' => true,
            'data' => [
                'type' => 'table',
                'table_id' => $context['context']->id,
                'table_number' => $context['context']->table_number,
                'display_name' => $context['display_name'],
                'capacity' => $context['context']->capacity,
            ],
        ]);
    }
}

// Update createOrder() method
public function createOrder(Request $request)
{
    $context = QRContextService::resolveContext($request->qr_token);
    
    if ($context['type'] === 'room') {
        // Create room order (existing logic)
        // Set: order_type = 'room', room_id = X
    }
    
    if ($context['type'] === 'table') {
        // Create table order (new logic)
        // Set: order_type = 'table', table_id = X
        // guest_id, reservation_id, room_id = NULL
        
        $order = Order::create([
            'order_number' => Order::generateOrderNumber(),
            'table_id' => $context['context']->id,
            'order_type' => 'table',
            'source' => 'guest_qr',
            'status' => 'pending',
            'total' => $calculatedTotal,
            'room_id' => null,
            'guest_id' => null,
            'reservation_id' => null,
        ]);
    }
}
```

---

### **PHASE 8: Update Order Creation**

#### Update Order Model
```php
File: app/Models/Order.php

protected $fillable = [
    // ... existing fields
    'order_type',  // NEW
    'table_id',    // NEW
];

// NEW relationship
public function diningTable()
{
    return $this->belongsTo(DiningTable::class, 'table_id');
}

// NEW helper methods
public function isRoomOrder(): bool
{
    return $this->order_type === 'room';
}

public function isTableOrder(): bool
{
    return $this->order_type === 'table';
}

public function getDisplayContextAttribute(): string
{
    if ($this->isRoomOrder() && $this->room) {
        return "Room {$this->room->room_number}";
    }
    
    if ($this->isTableOrder() && $this->diningTable) {
        return "Table {$this->diningTable->table_number}";
    }
    
    return "Unknown";
}
```

---

### **PHASE 9: Update Admin/Staff Order Management**

#### Update Kitchen/Waiter Views

**Add order context display:**
```php
// In order resources/views

@if($order->isRoomOrder())
    <span class="badge badge-primary">
        Room {{ $order->room->room_number }}
    </span>
@elseif($order->isTableOrder())
    <span class="badge badge-info">
        Table {{ $order->diningTable->table_number }}
    </span>
@endif
```

**Add filters:**
```php
// In OrderController

public function index(Request $request)
{
    $query = Order::with(['room', 'diningTable', 'orderItems.menuItem']);
    
    // Filter by order type
    if ($request->has('order_type')) {
        $query->where('order_type', $request->order_type);
    }
    
    // Filter by source
    if ($request->has('source')) {
        $query->where('source', $request->source);
    }
    
    return $query->latest()->paginate(20);
}
```

---

### **PHASE 10: Frontend Vue Components**

#### Update QRMenu.vue

```typescript
// Add context detection
const orderContext = ref<'room' | 'table' | null>(null)
const contextDisplay = ref('')

// Fetch context
const fetchContext = async (token: string) => {
  const response = await fetch(`/api/guest/qr-context/${token}`)
  const data = await response.json()
  
  if (data.success) {
    orderContext.value = data.data.type
    contextDisplay.value = data.data.display_name
    
    if (data.data.type === 'room') {
      roomNumber.value = data.data.room_number
      guestName.value = data.data.guest?.name || 'Guest'
    }
    
    if (data.data.type === 'table') {
      contextDisplay.value = `Table ${data.data.table_number}`
    }
  }
}

// Update hero heading based on context
const heroHeading = computed(() => {
  if (orderContext.value === 'room') {
    return `Ordering for ${roomNumber.value}`
  }
  if (orderContext.value === 'table') {
    return `Ordering for ${contextDisplay.value}`
  }
  return 'Good Food, Great Moments'
})
```

#### Create Table Management Vue Component

```vue
File: Client2/vue-project/src/views/admin/DiningTables.vue

<template>
  <div class="dining-tables-page">
    <h1>Dining Tables Management</h1>
    
    <button @click="showAddModal = true">Add New Table</button>
    
    <table>
      <thead>
        <tr>
          <th>Table Number</th>
          <th>Capacity</th>
          <th>Location</th>
          <th>Status</th>
          <th>QR Status</th>
          <th>Actions</th>
        </tr>
      </thead>
      <tbody>
        <tr v-for="table in tables" :key="table.id">
          <td>{{ table.table_number }}</td>
          <td>{{ table.capacity }} seats</td>
          <td>{{ table.location || '-' }}</td>
          <td>{{ table.status }}</td>
          <td>
            <span v-if="table.qr_token">Active</span>
            <span v-else>Not Generated</span>
          </td>
          <td>
            <button @click="viewQR(table)">View QR</button>
            <button @click="regenerateQR(table)">Regenerate</button>
            <button @click="editTable(table)">Edit</button>
          </td>
        </tr>
      </tbody>
    </table>
  </div>
</template>
```

---

## TEST CASES

### Test 1: Room QR Scan
```
✅ Scan room QR
✅ System identifies context as "room"
✅ System shows room number
✅ Menu loads correctly
✅ Can place order
✅ Order has room_id populated
✅ Order has order_type = 'room'
```

### Test 2: Table QR Scan
```
✅ Scan table QR
✅ System identifies context as "table"
✅ System shows table number
✅ Menu loads correctly
✅ Can place order
✅ Order has table_id populated
✅ Order has order_type = 'table'
✅ room_id, guest_id, reservation_id are NULL
```

### Test 3: Invalid QR Token
```
✅ Scan invalid token
✅ System returns 404
✅ Error message displayed
✅ No order created
```

### Test 4: Kitchen Receives Orders
```
✅ Room order shows "Room 101"
✅ Table order shows "Table 5"
✅ Staff can filter by order_type
✅ Staff can filter by source (guest_qr)
```

### Test 5: QR Regeneration
```
✅ Admin regenerates room QR
✅ Old token becomes invalid
✅ New token works
✅ Admin regenerates table QR
✅ Old token becomes invalid
✅ New token works
```

### Test 6: Existing Features
```
✅ Room reservations still work
✅ Check-in/check-out still work
✅ Receptionist orders still work
✅ Menu management still works
✅ Waiter assignments still work
✅ Kitchen workflow still works
✅ Payments still work
```

---

## FILES TO CREATE

### Backend
1. ✅ Migration: `2026_08_09_000001_make_order_relationships_nullable.php`
2. ✅ Migration: `2026_08_09_000002_add_order_type_to_orders.php`
3. ✅ Migration: `2026_08_09_000003_create_dining_tables.php`
4. ✅ Model: `app/Models/DiningTable.php`
5. ✅ Service: `app/Services/QRContextService.php`
6. ✅ Controller: `app/Http/Controllers/Api/Admin/DiningTableController.php`
7. ✅ Resource: `app/Http/Resources/DiningTableResource.php`
8. ✅ Request: `app/Http/Requests/StoreDiningTableRequest.php`
9. ✅ Request: `app/Http/Requests/UpdateDiningTableRequest.php`

### Frontend
1. ✅ View: `src/views/admin/DiningTables.vue`
2. ✅ Component: `src/components/admin/TableQRModal.vue`
3. ✅ Component: `src/components/admin/TableFormModal.vue`
4. ✅ Service: `src/services/diningTableService.ts`
5. ✅ Store: `src/stores/diningTableStore.ts`
6. ✅ Type: `src/types/diningTable.ts`

---

## FILES TO MODIFY

### Backend
1. ✅ `app/Models/Order.php` - Add table_id, order_type relationships
2. ✅ `app/Http/Controllers/Api/GuestOrderController.php` - Add QR context logic
3. ✅ `app/Services/QRCodeService.php` - Add generateTableQRCode method
4. ✅ `routes/api.php` - Add table management routes

### Frontend
1. ✅ `src/views/guest/QRMenu.vue` - Add context detection
2. ✅ `src/router/index.ts` - Add table management route
3. ✅ Admin sidebar - Add "Dining Tables" menu item

---

## RISK MITIGATION

### Risk 1: Breaking Existing Orders
**Mitigation:** Make foreign keys nullable in separate migration, test thoroughly

### Risk 2: QR Token Collision
**Mitigation:** Check both rooms and tables when generating tokens

### Risk 3: Performance Impact
**Mitigation:** Indexed qr_token columns, efficient queries

### Risk 4: Frontend Breaking Changes
**Mitigation:** Backwards compatible API, context detection, fallback handling

---

## DEPLOYMENT CHECKLIST

### Pre-Deployment
- [ ] Backup database
- [ ] Test all migrations on staging
- [ ] Test QR token generation
- [ ] Test order creation for both contexts
- [ ] Test existing room QR orders
- [ ] Test waiter/kitchen workflow

### Deployment
- [ ] Run migrations in production
- [ ] Generate QR codes for existing rooms (if needed)
- [ ] Create initial dining tables
- [ ] Deploy frontend updates
- [ ] Clear caches

### Post-Deployment
- [ ] Verify room QR orders still work
- [ ] Verify table QR orders work
- [ ] Verify kitchen receives orders correctly
- [ ] Monitor logs for errors
- [ ] Test payment flow end-to-end

---

## SUMMARY

**Current Status:**
- ✅ Room QR system exists and works
- ❌ Walk-in/table system does not exist
- ⚠️ Orders table requires modifications

**Implementation Strategy:**
- Minimal changes to existing code
- Add new dining table entity
- Make order relationships nullable
- Extend QR resolution logic
- Reuse existing QR menu frontend

**Estimated Effort:**
- Backend: 3-4 hours
- Frontend: 2-3 hours
- Testing: 2 hours
- Total: 7-9 hours

**Next Step:**
AWAIT USER APPROVAL TO PROCEED TO PHASE 3 (DATABASE CHANGES)

---

**END OF ANALYSIS REPORT**

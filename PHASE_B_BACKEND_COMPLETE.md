# Phase B: Backend Models & Controllers - COMPLETE ✅

## Overview
Phase B creates the backend infrastructure to support QR-based food ordering for both room service and walk-in restaurant customers.

---

## Files Created

### 1. ✅ RestaurantTable Model
**File**: `server/app/Models/RestaurantTable.php`

**Features**:
- UUID primary key with soft deletes
- Auto-generates 8-character QR token on creation
- Auto-generates QR code image (stored in `storage/app/public/qr-codes/tables/`)
- QR URL pattern: `/restaurant-order/{token}` (different from room `/order/{token}`)
- Status constants: AVAILABLE, OCCUPIED, RESERVED, MAINTENANCE
- Relationships: `orders()` - hasMany Order
- Helper methods:
  - `isAvailable()`, `isOccupied()`, `isReserved()`, `isInMaintenance()`
  - `getQRCodeUrlAttribute()` - returns full URL to QR image
  - `regenerateQRCode()` - regenerate QR token and image
- Scopes:
  - `search($term)` - search by table_number, table_name, location, status
  - `active()` - only active tables
  - `available()` - available status AND active

**QR Generation**:
- Uses same QRCodeService pattern as rooms
- QR codes stored in separate directory: `qr-codes/tables/`
- Filename pattern: `table_{table_number}.png`
- Fallback to online API if local generation fails

---

### 2. ✅ Order Model Updates
**File**: `server/app/Models/Order.php` (modified)

**Changes**:
- Added `table_id` and `order_type` to `$fillable`
- Added order type constants:
  - `TYPE_ROOM_SERVICE = 'room_service'`
  - `TYPE_WALK_IN = 'walk_in'`
- Added `table()` relationship - belongsTo RestaurantTable
- Added helper methods:
  - `isRoomService()` - check if order is room service
  - `isWalkIn()` - check if order is walk-in

---

### 3. ✅ QRResolutionService
**File**: `server/app/Services/QRResolutionService.php`

**Purpose**: Central service to resolve QR tokens and determine context

**Methods**:

#### `resolveQRToken(string $qrToken): array`
Validates QR token and determines if it belongs to a room or table.

**Returns**:
```php
[
    'success' => bool,
    'context' => 'room'|'table'|null,
    'data' => [
        // For rooms: room_id, room_number, floor, floor_id, room_type, status
        // For tables: table_id, table_number, table_name, capacity, location, status
    ],
    'message' => string
]
```

**Logic**:
1. Validates token format (8 uppercase alphanumeric)
2. Checks `rooms` table for matching `qr_token` where `is_active = true`
3. If not found, checks `restaurant_tables` table
4. Returns context and data or error

#### `validateOrderData(array $orderData, string $context): array`
Validates order payload based on context (room vs table).

**Room service validation**:
- MUST have `room_id`
- CAN have `guest_id` and `reservation_id` (nullable)

**Walk-in validation**:
- MUST have `table_id`
- MUST NOT have `room_id`, `guest_id`, or `reservation_id`

#### `getOrderTypeFromContext(string $context): string`
Returns `'room_service'` for room context, `'walk_in'` for table context.

#### `isTableAvailable(string $qrToken): bool`
Check if QR token belongs to an available restaurant table.

#### `isRoomAvailable(string $qrToken): bool`
Check if QR token belongs to an available/occupied room.

---

### 4. ✅ QRResolutionController
**File**: `server/app/Http/Controllers/Api/QRResolutionController.php`

**Purpose**: API endpoints for QR token resolution

**Endpoints**:

#### `POST /api/qr/resolve`
Body: `{ "qr_token": "ABC12345" }`

Response:
```json
{
    "success": true,
    "context": "room",
    "data": {
        "room_id": "uuid",
        "room_number": "101",
        "floor": "1"
    },
    "message": "QR code belongs to a hotel room"
}
```

#### `GET /api/qr/resolve/{token}`
Same as POST but token in URL.

#### `POST /api/qr/validate`
Lightweight check - returns only `valid` (bool) and `context`.

---

### 5. ✅ RestaurantTableController (Manager)
**File**: `server/app/Http/Controllers/Api/Manager/RestaurantTableController.php`

**Purpose**: CRUD operations for restaurant tables (manager access)

**Endpoints**:

#### `GET /api/manager/restaurant-tables`
List all tables with pagination, filtering, sorting.

**Query params**:
- `search` - search term
- `status` - filter by status
- `is_active` - filter by active status
- `location` - filter by location
- `sort_by` - column to sort (default: table_number)
- `sort_order` - asc/desc (default: asc)
- `per_page` - items per page (default: 15)

**Response**: Paginated table list with QR code URLs

#### `GET /api/manager/restaurant-tables/{id}`
Get single table by ID.

#### `POST /api/manager/restaurant-tables`
Create new restaurant table.

**Body**:
```json
{
    "table_number": "T01",
    "table_name": "Window Table 1",
    "capacity": 4,
    "location": "Main Dining",
    "status": "available",
    "is_active": true
}
```

**Auto-generated**: `qr_token`, `qr_image_path`, `qr_generated_at`

#### `PUT /api/manager/restaurant-tables/{id}`
Update existing table.

#### `DELETE /api/manager/restaurant-tables/{id}`
Soft delete table. Fails if table has active orders.

#### `POST /api/manager/restaurant-tables/{id}/regenerate-qr`
Regenerate QR code for a table.

**Returns**: New QR token, image path, and URL.

#### `GET /api/manager/restaurant-tables/statistics`
Get table statistics.

**Returns**:
```json
{
    "total": 20,
    "active": 18,
    "available": 12,
    "occupied": 4,
    "reserved": 2,
    "maintenance": 2
}
```

---

### 6. ✅ UnifiedOrderController
**File**: `server/app/Http/Controllers/Api/UnifiedOrderController.php`

**Purpose**: Unified order creation endpoint that handles both room service and walk-in orders

**Key Method**: `POST /api/orders`

**Body**:
```json
{
    "qr_token": "ABC12345",
    "items": [
        {
            "menu_item_id": "uuid",
            "quantity": 2
        }
    ],
    "special_requests": "No onions please",
    "payment_type": "room_charge"
}
```

**Flow**:
1. Validate request
2. Resolve QR token using `QRResolutionService`
3. Route to `createRoomServiceOrder()` or `createWalkInOrder()`
4. Calculate total, create order, create order items
5. Return order details

**Room Service Order**:
- Creates Order with: `room_id`, `guest_id`, `reservation_id`, `order_type='room_service'`
- Finds/creates guest and reservation if needed
- Default payment: `room_charge`

**Walk-in Order**:
- Creates Order with: `table_id`, `order_type='walk_in'`
- No room/guest/reservation
- Updates table status to OCCUPIED
- Default payment: `cash`

---

## Database Schema Summary

### `restaurant_tables` table
```
id (UUID, primary)
table_number (string, unique)
table_name (string, nullable)
capacity (integer, default 4)
location (string, nullable)
status (enum: available/occupied/reserved/maintenance)
is_active (boolean)
qr_token (char(8), unique, indexed)
qr_image_path (string, nullable)
qr_generated_at (timestamp, nullable)
created_at, updated_at
deleted_at (soft delete)
```

### `orders` table (modified)
```
... existing fields ...
table_id (UUID, nullable, foreign key -> restaurant_tables)
order_type (enum: room_service/walk_in, default room_service)
... existing fields ...
```

---

## API Routes to Add

Add these routes to `server/routes/api.php`:

```php
// QR Resolution (Public)
Route::post('/qr/resolve', [QRResolutionController::class, 'resolveQRToken']);
Route::get('/qr/resolve/{token}', [QRResolutionController::class, 'resolveFromUrl']);
Route::post('/qr/validate', [QRResolutionController::class, 'validateQRToken']);

// Unified Order Creation (Public)
Route::post('/orders', [UnifiedOrderController::class, 'createOrder']);

// Restaurant Table Management (Manager only)
Route::middleware(['auth:sanctum', 'role:manager'])->prefix('manager')->group(function () {
    Route::get('/restaurant-tables', [RestaurantTableController::class, 'index']);
    Route::get('/restaurant-tables/statistics', [RestaurantTableController::class, 'statistics']);
    Route::get('/restaurant-tables/{id}', [RestaurantTableController::class, 'show']);
    Route::post('/restaurant-tables', [RestaurantTableController::class, 'store']);
    Route::put('/restaurant-tables/{id}', [RestaurantTableController::class, 'update']);
    Route::delete('/restaurant-tables/{id}', [RestaurantTableController::class, 'destroy']);
    Route::post('/restaurant-tables/{id}/regenerate-qr', [RestaurantTableController::class, 'regenerateQR']);
});
```

---

## Security Features

1. **Server-Side QR Resolution**: Frontend NEVER sends `room_id` or `table_id` directly - only `qr_token`
2. **Context Validation**: Backend validates that order data matches resolved context
3. **Active Check**: Only active rooms/tables can accept orders
4. **Unique Tokens**: QR tokens are unique across BOTH rooms and tables
5. **Status Check**: Tables must be available/occupied, rooms must be active

---

## Testing Checklist

Before proceeding to Phase C, test:

1. **Database Migrations**:
   - [ ] Run `php artisan migrate` successfully
   - [ ] Verify `restaurant_tables` table created
   - [ ] Verify `orders` table has nullable foreign keys
   - [ ] Verify `orders` table has `table_id` and `order_type` columns

2. **Restaurant Table Management**:
   - [ ] Create a restaurant table via API
   - [ ] Verify QR code generated in `storage/app/public/qr-codes/tables/`
   - [ ] List tables with filters
   - [ ] Update table
   - [ ] Regenerate QR code

3. **QR Resolution**:
   - [ ] Resolve a room QR token -> returns context='room'
   - [ ] Resolve a table QR token -> returns context='table'
   - [ ] Try invalid token -> returns 404

4. **Order Creation**:
   - [ ] Create room service order with room QR token
   - [ ] Create walk-in order with table QR token
   - [ ] Verify order_type set correctly
   - [ ] Verify table status updated to OCCUPIED

---

## Next Phase

Once Phase B is tested and working, proceed to:
**Phase C: Frontend Components & Views**
- QR Menu component updates
- Restaurant table ordering UI
- Order context detection
- Shared menu between contexts

---

## Notes

- **Backward Compatible**: Existing room QR ordering still works via `GuestOrderController`
- **Unified Approach**: `UnifiedOrderController` is the NEW recommended endpoint
- **Gradual Migration**: Can migrate frontend to use unified endpoint incrementally
- **QR URL Patterns**:
  - Room: `/order/{token}`
  - Table: `/restaurant-order/{token}`
  - Both can use `/api/qr/resolve/{token}` to determine context dynamically

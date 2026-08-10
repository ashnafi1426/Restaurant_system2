# QR-Based Food Ordering System - Phase A & B Summary

## 🎯 Project Goal

Add QR-based food ordering that supports **TWO contexts**:
1. **Room Service** - Hotel guests scan room QR codes to order food to their room
2. **Walk-In Restaurant** - Restaurant customers scan table QR codes to order at their table

**Key Requirement**: Both contexts use the **SAME MENU**.

---

## ✅ Phase A: Database Foundation (COMPLETE)

### Migrations Created

#### 1. `2026_08_09_000001_create_restaurant_tables_table.php`
Creates the `restaurant_tables` table to store restaurant table information and QR codes.

**Key Features**:
- UUID primary key
- QR fields: `qr_token` (8 char), `qr_image_path`, `qr_generated_at`
- Table info: `table_number`, `table_name`, `capacity`, `location`
- Status enum: available/occupied/reserved/maintenance
- Soft deletes
- Indexes on critical fields

#### 2. `2026_08_09_000002_make_orders_foreign_keys_nullable.php`
Makes `room_id`, `guest_id`, and `reservation_id` **NULLABLE** in the `orders` table.

**Why**: Walk-in customers don't have rooms, guests, or reservations - they just order at a table.

**Implementation**:
- Drops existing foreign key constraints
- Uses raw SQL to modify UUID columns to nullable
- Re-adds foreign keys with `nullOnDelete()` behavior
- Includes rollback warning

#### 3. `2026_08_09_000003_add_table_and_type_to_orders_table.php`
Adds `table_id` and `order_type` columns to the `orders` table.

**New Columns**:
- `table_id` (UUID, nullable, foreign key to restaurant_tables)
- `order_type` (enum: 'room_service', 'walk_in', default 'room_service')

**Indexes**: Added for performance on `table_id`, `order_type`, and composite `(order_type, status)`

---

## ✅ Phase B: Backend Models & Controllers (COMPLETE)

### 1. Models

#### `RestaurantTable` Model
**Location**: `server/app/Models/RestaurantTable.php`

**Features**:
- Auto-generates 8-character QR token on creation
- Auto-generates QR code image after creation
- QR URL pattern: `/restaurant-order/{token}` (different from rooms)
- Status constants and helper methods
- Relationships: `orders()` hasMany
- Scopes: `search()`, `active()`, `available()`
- QR regeneration method

**QR Code Storage**: `storage/app/public/qr-codes/tables/table_{table_number}.png`

#### `Order` Model Updates
**Location**: `server/app/Models/Order.php`

**Changes**:
- Added `table_id` and `order_type` to fillable
- Added order type constants: `TYPE_ROOM_SERVICE`, `TYPE_WALK_IN`
- Added `table()` relationship
- Added helper methods: `isRoomService()`, `isWalkIn()`

### 2. Services

#### `QRResolutionService`
**Location**: `server/app/Services/QRResolutionService.php`

**Purpose**: Central service to validate QR tokens and determine context (room or table)

**Key Methods**:
- `resolveQRToken($qrToken)` - Returns context ('room' or 'table') and data
- `validateOrderData($data, $context)` - Validates order payload for context
- `getOrderTypeFromContext($context)` - Returns order type constant
- `isTableAvailable($qrToken)` - Check table availability
- `isRoomAvailable($qrToken)` - Check room availability

**Security**: Validates QR tokens server-side - frontend never sends room_id/table_id directly

### 3. Controllers

#### `QRResolutionController`
**Location**: `server/app/Http/Controllers/Api/QRResolutionController.php`

**Endpoints**:
- `POST /api/qr/resolve` - Resolve QR token (JSON body)
- `GET /api/qr/resolve/{token}` - Resolve QR token (URL param)
- `POST /api/qr/validate` - Lightweight validation check

**Response Format**:
```json
{
    "success": true,
    "context": "room" | "table",
    "data": { ... room or table data ... },
    "message": "..."
}
```

#### `RestaurantTableController` (Manager)
**Location**: `server/app/Http/Controllers/Api/Manager/RestaurantTableController.php`

**Purpose**: CRUD operations for restaurant tables (manager access only)

**Endpoints**:
- `GET /api/manager/restaurant-tables` - List with pagination/filtering
- `GET /api/manager/restaurant-tables/statistics` - Get table stats
- `GET /api/manager/restaurant-tables/{id}` - Get single table
- `POST /api/manager/restaurant-tables` - Create table
- `PUT /api/manager/restaurant-tables/{id}` - Update table
- `DELETE /api/manager/restaurant-tables/{id}` - Delete table (soft)
- `POST /api/manager/restaurant-tables/{id}/regenerate-qr` - Regenerate QR

**Features**:
- Pagination, search, filtering, sorting
- Prevents deletion if table has active orders
- Auto-generates QR codes
- Returns statistics

#### `UnifiedOrderController`
**Location**: `server/app/Http/Controllers/Api/UnifiedOrderController.php`

**Purpose**: Single endpoint that handles BOTH room service and walk-in orders

**Endpoint**: `POST /api/orders`

**Request Body**:
```json
{
    "qr_token": "ABC12345",
    "items": [
        {"menu_item_id": "uuid", "quantity": 2}
    ],
    "special_requests": "No onions",
    "payment_type": "room_charge" | "cash" | "card"
}
```

**Flow**:
1. Validate request
2. Resolve QR token using `QRResolutionService`
3. Route to `createRoomServiceOrder()` or `createWalkInOrder()`
4. Calculate total, create order, create order items
5. Return order details

**Room Service Order**:
- Sets: `room_id`, `guest_id`, `reservation_id`, `order_type='room_service'`
- Creates guest/reservation if needed
- Default payment: `room_charge`

**Walk-In Order**:
- Sets: `table_id`, `order_type='walk_in'`
- No room/guest/reservation (all NULL)
- Updates table status to OCCUPIED
- Default payment: `cash`

### 4. Seeders

#### `RestaurantTableSeeder`
**Location**: `server/database/seeders/RestaurantTableSeeder.php`

**Purpose**: Creates 17 sample restaurant tables for testing

**Categories**:
- Main Dining (8 tables: T01-T08)
- Terrace (4 tables: T09-T12)
- VIP Private Dining (2 tables: V01-V02)
- Bar (3 tables: B01-B03)

**Run**: `php artisan db:seed --class=RestaurantTableSeeder`

---

## 📁 Files Created Summary

### Database (3 files)
- `server/database/migrations/2026_08_09_000001_create_restaurant_tables_table.php`
- `server/database/migrations/2026_08_09_000002_make_orders_foreign_keys_nullable.php`
- `server/database/migrations/2026_08_09_000003_add_table_and_type_to_orders_table.php`

### Models (2 files)
- `server/app/Models/RestaurantTable.php` *(new)*
- `server/app/Models/Order.php` *(modified)*

### Services (1 file)
- `server/app/Services/QRResolutionService.php`

### Controllers (3 files)
- `server/app/Http/Controllers/Api/QRResolutionController.php`
- `server/app/Http/Controllers/Api/Manager/RestaurantTableController.php`
- `server/app/Http/Controllers/Api/UnifiedOrderController.php`

### Seeders (1 file)
- `server/database/seeders/RestaurantTableSeeder.php`

### Documentation (4 files)
- `PHASE_B_BACKEND_COMPLETE.md` - Detailed Phase B documentation
- `PHASE_B_ROUTES_TO_ADD.php` - Route configuration to add
- `PHASE_A_B_TESTING_CHECKLIST.md` - Complete testing guide
- `QR_FOOD_ORDERING_PHASE_A_B_SUMMARY.md` - This file

---

## 🔑 Key Architecture Decisions

### 1. **QR Token Uniqueness**
QR tokens are unique across **both rooms and tables**. The system checks rooms first, then tables.

### 2. **Server-Side Resolution**
Frontend sends only `qr_token` - never `room_id` or `table_id` directly. Backend resolves the token server-side for security.

### 3. **Nullable Foreign Keys**
`orders.room_id`, `orders.guest_id`, `orders.reservation_id` are nullable to support walk-in orders that don't have hotel associations.

### 4. **Unified Order Endpoint**
Single `/api/orders` endpoint handles both order types. Backend determines context from QR token.

### 5. **Backward Compatibility**
Existing room service ordering via `GuestOrderController` still works. New `UnifiedOrderController` is recommended.

### 6. **QR Code Separation**
- Room QR codes: `storage/app/public/qr-codes/room_{number}.png`
- Table QR codes: `storage/app/public/qr-codes/tables/table_{number}.png`

---

## 🚀 Setup Instructions

### 1. Run Migrations
```bash
cd server
php artisan migrate
```

### 2. Seed Test Data (Optional)
```bash
php artisan db:seed --class=RestaurantTableSeeder
```

### 3. Add Routes
Copy routes from `PHASE_B_ROUTES_TO_ADD.php` to `server/routes/api.php`

### 4. Link Storage (if not done)
```bash
php artisan storage:link
```

### 5. Test API Endpoints
Follow the testing checklist in `PHASE_A_B_TESTING_CHECKLIST.md`

---

## 🔒 Security Features

1. **Server-side QR resolution** - Frontend can't fake context
2. **Active checks** - Only active rooms/tables accept orders
3. **Unique tokens** - QR tokens unique across system
4. **Manager auth required** - Table management needs authentication
5. **Validation** - Context-specific order validation
6. **Foreign key constraints** - Data integrity maintained

---

## 📊 Data Flow

### Room Service Order Flow
```
Customer scans room QR
    ↓
Frontend: GET /api/qr/resolve/{token}
    ↓
Backend: QRResolutionService resolves → context='room'
    ↓
Frontend: Shows menu, customer selects items
    ↓
Frontend: POST /api/orders with qr_token
    ↓
Backend: Resolves token again, creates room service order
    ↓
Order created with room_id, guest_id, reservation_id
```

### Walk-In Order Flow
```
Customer scans table QR
    ↓
Frontend: GET /api/qr/resolve/{token}
    ↓
Backend: QRResolutionService resolves → context='table'
    ↓
Frontend: Shows menu, customer selects items
    ↓
Frontend: POST /api/orders with qr_token
    ↓
Backend: Resolves token again, creates walk-in order
    ↓
Order created with table_id only
Table status → OCCUPIED
```

---

## 🧪 Testing Status

### Phase A: Database ✅
- [x] Migrations created
- [x] Schema verified
- [ ] **YOU MUST**: Run migrations and verify

### Phase B: Backend ✅
- [x] Models created
- [x] Services created
- [x] Controllers created
- [x] Seeders created
- [ ] **YOU MUST**: Add routes to api.php
- [ ] **YOU MUST**: Test all endpoints
- [ ] **YOU MUST**: Verify QR code generation

---

## ➡️ Next Phase: Phase C - Frontend

Once Phase A & B testing is complete, Phase C will create:

1. **Updated QR Menu Component** - Detects context (room vs table)
2. **Restaurant Table Ordering UI** - Walk-in customer interface
3. **Manager Table Management UI** - CRUD for restaurant tables
4. **Unified Order Service** - Frontend service using `/api/orders`
5. **Context-Aware Components** - Show different UI based on context

---

## 📝 Notes

- **Backward Compatible**: Existing room QR ordering continues to work
- **Shared Menu**: Both contexts use the same menu items
- **Flexible**: Can add more contexts (e.g., hotel lobby kiosk) later
- **Scalable**: Table assignment can be extended to restaurant seating management

---

## 🐛 Known Limitations

1. **Walk-in orders** don't track customer information (anonymous)
2. **Table status** updates to OCCUPIED but doesn't auto-reset to AVAILABLE when order completes (future enhancement)
3. **QR regeneration** requires manual action by manager

---

## 📞 Support & Questions

Before proceeding to Phase C:
1. Review this summary
2. Follow testing checklist
3. Report any issues or deviations
4. Confirm all tests pass

**Ready to proceed to Phase C?** Reply with your test results!

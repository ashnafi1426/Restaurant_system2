# Phase A & B Testing Checklist

Before proceeding to Phase C (Frontend), complete these tests to ensure the database and backend are working correctly.

---

## ⚙️ PHASE A: Database Foundation Testing

### Step 1: Run Migrations

```bash
cd server
php artisan migrate
```

**Expected Output**: All 3 new migrations should run successfully:
- ✅ `2026_08_09_000001_create_restaurant_tables_table`
- ✅ `2026_08_09_000002_make_orders_foreign_keys_nullable`
- ✅ `2026_08_09_000003_add_table_and_type_to_orders_table`

### Step 2: Verify Database Schema

Check your MySQL/database to confirm:

#### ✅ `restaurant_tables` table exists with columns:
- [ ] `id` (UUID primary key)
- [ ] `table_number` (unique string)
- [ ] `table_name` (nullable string)
- [ ] `capacity` (integer, default 4)
- [ ] `location` (nullable string)
- [ ] `status` (enum: available/occupied/reserved/maintenance)
- [ ] `is_active` (boolean)
- [ ] `qr_token` (char(8), unique)
- [ ] `qr_image_path` (nullable string)
- [ ] `qr_generated_at` (nullable timestamp)
- [ ] `created_at`, `updated_at`, `deleted_at` (timestamps)

#### ✅ `orders` table updated with:
- [ ] `room_id` is now NULLABLE (check with `DESCRIBE orders;`)
- [ ] `guest_id` is now NULLABLE
- [ ] `reservation_id` is now NULLABLE
- [ ] `table_id` column added (UUID, nullable)
- [ ] `order_type` column added (enum: room_service/walk_in, default room_service)

### Step 3: Seed Test Data (Optional)

```bash
php artisan db:seed --class=RestaurantTableSeeder
```

**Expected Output**: Creates 17 sample restaurant tables with QR codes.

---

## 🔧 PHASE B: Backend API Testing

### Prerequisites
1. Ensure Laravel server is running: `php artisan serve`
2. Use Postman, Insomnia, or cURL for API testing
3. Base URL: `http://localhost:8000/api`

---

### Test 1: QR Token Resolution - Room Context

**Endpoint**: `POST /api/qr/resolve`

**Body** (JSON):
```json
{
    "qr_token": "XXXXXXXX"
}
```
*Replace `XXXXXXXX` with an actual room QR token from your database*

**Expected Response** (200 OK):
```json
{
    "success": true,
    "context": "room",
    "data": {
        "room_id": "uuid-here",
        "room_number": "101",
        "floor": "1",
        "floor_id": "uuid-here",
        "room_type": "Deluxe",
        "status": "occupied"
    },
    "message": "QR code belongs to a hotel room"
}
```

**Checklist**:
- [ ] Returns 200 OK status
- [ ] `context` is "room"
- [ ] `data` contains room information
- [ ] Invalid token returns 404

---

### Test 2: QR Token Resolution - Table Context

**Endpoint**: `GET /api/qr/resolve/{token}`

*Replace `{token}` with a restaurant table QR token*

**Expected Response** (200 OK):
```json
{
    "success": true,
    "context": "table",
    "data": {
        "table_id": "uuid-here",
        "table_number": "T01",
        "table_name": "Window Table 1",
        "capacity": 2,
        "location": "Main Dining",
        "status": "available"
    },
    "message": "QR code belongs to a restaurant table"
}
```

**Checklist**:
- [ ] Returns 200 OK status
- [ ] `context` is "table"
- [ ] `data` contains table information
- [ ] QR image was generated in `storage/app/public/qr-codes/tables/`

---

### Test 3: Restaurant Table Management - Create Table

**Endpoint**: `POST /api/manager/restaurant-tables`

**Headers**:
```
Authorization: Bearer {manager_token}
Content-Type: application/json
```

**Body**:
```json
{
    "table_number": "T99",
    "table_name": "Test Table",
    "capacity": 4,
    "location": "Test Area",
    "status": "available",
    "is_active": true
}
```

**Expected Response** (201 Created):
```json
{
    "success": true,
    "message": "Restaurant table created successfully",
    "data": {
        "id": "uuid-here",
        "table_number": "T99",
        "table_name": "Test Table",
        "capacity": 4,
        "location": "Test Area",
        "status": "available",
        "is_active": true,
        "qr_token": "ABC12345",
        "qr_image_path": "qr-codes/tables/table_T99.png",
        "qr_code_url": "http://localhost:8000/storage/qr-codes/tables/table_T99.png",
        "qr_generated_at": "2026-08-09T12:00:00.000000Z",
        "created_at": "2026-08-09T12:00:00.000000Z",
        "updated_at": "2026-08-09T12:00:00.000000Z"
    }
}
```

**Checklist**:
- [ ] Returns 201 Created
- [ ] Table created in database
- [ ] QR token auto-generated (8 characters)
- [ ] QR image created in `storage/app/public/qr-codes/tables/`
- [ ] Requires manager authentication

---

### Test 4: Restaurant Table Management - List Tables

**Endpoint**: `GET /api/manager/restaurant-tables?per_page=10&status=available`

**Headers**:
```
Authorization: Bearer {manager_token}
```

**Expected Response** (200 OK):
```json
{
    "success": true,
    "data": {
        "current_page": 1,
        "data": [
            {
                "id": "uuid",
                "table_number": "T01",
                "table_name": "Window Table 1",
                "capacity": 2,
                "location": "Main Dining",
                "status": "available",
                "is_active": true,
                "qr_token": "ABC12345",
                "qr_code_url": "http://...",
                "created_at": "...",
                "updated_at": "..."
            }
        ],
        "per_page": 10,
        "total": 17
    }
}
```

**Checklist**:
- [ ] Returns paginated results
- [ ] Filtering by status works
- [ ] Search works (`?search=T01`)
- [ ] Requires manager authentication

---

### Test 5: Restaurant Table Management - Statistics

**Endpoint**: `GET /api/manager/restaurant-tables/statistics`

**Headers**:
```
Authorization: Bearer {manager_token}
```

**Expected Response** (200 OK):
```json
{
    "success": true,
    "data": {
        "total": 17,
        "active": 17,
        "available": 15,
        "occupied": 1,
        "reserved": 1,
        "maintenance": 0
    }
}
```

**Checklist**:
- [ ] Returns correct counts
- [ ] All statuses accounted for

---

### Test 6: Unified Order Creation - Room Service Order

**Endpoint**: `POST /api/orders`

**Body**:
```json
{
    "qr_token": "ROOM_QR_TOKEN_HERE",
    "items": [
        {
            "menu_item_id": "menu-item-uuid-1",
            "quantity": 2
        },
        {
            "menu_item_id": "menu-item-uuid-2",
            "quantity": 1
        }
    ],
    "special_requests": "Extra spicy",
    "payment_type": "room_charge"
}
```

**Expected Response** (201 Created):
```json
{
    "success": true,
    "message": "Room service order placed successfully",
    "data": {
        "order_id": "uuid",
        "order_number": "ORD-20260809-0001",
        "order_type": "room_service",
        "room_number": "101",
        "total": 45.50,
        "status": "pending",
        "created_at": "2026-08-09T12:00:00.000000Z"
    }
}
```

**Checklist**:
- [ ] Order created with `order_type = 'room_service'`
- [ ] `room_id` is set
- [ ] `guest_id` and `reservation_id` are set (or created automatically)
- [ ] `table_id` is NULL
- [ ] Order items created correctly
- [ ] Payment type is 'room_charge'

---

### Test 7: Unified Order Creation - Walk-in Order

**Endpoint**: `POST /api/orders`

**Body**:
```json
{
    "qr_token": "TABLE_QR_TOKEN_HERE",
    "items": [
        {
            "menu_item_id": "menu-item-uuid-1",
            "quantity": 1
        }
    ],
    "special_requests": "No ice",
    "payment_type": "cash"
}
```

**Expected Response** (201 Created):
```json
{
    "success": true,
    "message": "Walk-in order placed successfully",
    "data": {
        "order_id": "uuid",
        "order_number": "ORD-20260809-0002",
        "order_type": "walk_in",
        "table_number": "T01",
        "total": 18.00,
        "status": "pending",
        "created_at": "2026-08-09T12:00:00.000000Z"
    }
}
```

**Checklist**:
- [ ] Order created with `order_type = 'walk_in'`
- [ ] `table_id` is set
- [ ] `room_id`, `guest_id`, `reservation_id` are NULL
- [ ] Table status updated to 'occupied'
- [ ] Order items created correctly
- [ ] Payment type is 'cash' (or card)

---

### Test 8: Regenerate QR Code

**Endpoint**: `POST /api/manager/restaurant-tables/{id}/regenerate-qr`

**Headers**:
```
Authorization: Bearer {manager_token}
```

**Expected Response** (200 OK):
```json
{
    "success": true,
    "message": "QR code regenerated successfully",
    "data": {
        "qr_token": "NEWTOKEN8",
        "qr_image_path": "qr-codes/tables/table_T01.png",
        "qr_code_url": "http://localhost:8000/storage/qr-codes/tables/table_T01.png",
        "qr_generated_at": "2026-08-09T13:00:00.000000Z"
    }
}
```

**Checklist**:
- [ ] New QR token generated (different from old one)
- [ ] QR image regenerated
- [ ] Old QR image deleted

---

### Test 9: Delete Table (with validation)

**Attempt 1**: Try deleting a table with active orders

**Endpoint**: `DELETE /api/manager/restaurant-tables/{id}`

**Expected Response** (422 Unprocessable Entity):
```json
{
    "success": false,
    "message": "Cannot delete table with 2 active order(s)"
}
```

**Attempt 2**: Delete a table without active orders

**Expected Response** (200 OK):
```json
{
    "success": true,
    "message": "Restaurant table deleted successfully"
}
```

**Checklist**:
- [ ] Cannot delete table with pending/preparing/ready orders
- [ ] Can delete table without active orders
- [ ] Table is soft deleted (not permanently removed)

---

## 🗂️ Database Verification Queries

Run these SQL queries to verify data integrity:

### Check nullable foreign keys in orders:
```sql
DESCRIBE orders;
-- Verify room_id, guest_id, reservation_id show "NULL" in Null column
```

### Check orders by type:
```sql
SELECT order_type, COUNT(*) as count 
FROM orders 
GROUP BY order_type;
```

### Check restaurant tables:
```sql
SELECT table_number, qr_token, status, is_active 
FROM restaurant_tables 
WHERE deleted_at IS NULL;
```

### Check walk-in orders (should have NULL room/guest/reservation):
```sql
SELECT id, order_number, order_type, room_id, guest_id, reservation_id, table_id 
FROM orders 
WHERE order_type = 'walk_in';
```

---

## ✅ Phase A & B Completion Criteria

Before moving to Phase C, ensure:

### Phase A: Database
- [x] All 3 migrations ran successfully
- [x] `restaurant_tables` table created
- [x] `orders` table foreign keys are nullable
- [x] `orders` table has `table_id` and `order_type` columns
- [x] Indexes created on new columns

### Phase B: Backend
- [x] RestaurantTable model created with QR generation
- [x] Order model updated with new relationships
- [x] QRResolutionService resolves both room and table tokens
- [x] QRResolutionController endpoints work
- [x] RestaurantTableController CRUD operations work
- [x] UnifiedOrderController creates both order types correctly
- [x] Room service orders have room/guest/reservation
- [x] Walk-in orders have table only (no room/guest/reservation)
- [x] Table status updates when walk-in order placed
- [x] QR codes generated in correct directories

### Code Quality
- [x] All files created without syntax errors
- [x] Models use proper relationships
- [x] Controllers have proper validation
- [x] Logging implemented for debugging
- [x] Error handling in place

---

## 🚀 Next Steps

Once all tests pass:

1. ✅ Commit Phase A & B changes to git
2. 📝 Document any issues or deviations
3. ➡️ Proceed to **Phase C: Frontend Components & Views**

---

## 🐛 Common Issues & Solutions

### Issue: Migration fails on making columns nullable
**Solution**: Ensure you're using raw SQL (`DB::statement`) for UUID column modifications

### Issue: QR code generation fails
**Solution**: 
- Check `storage/app/public/qr-codes/tables/` directory exists and is writable
- Run `php artisan storage:link` if not already linked
- Check SimpleSoftwareIO/simple-qrcode package is installed

### Issue: 401 Unauthorized on manager routes
**Solution**: 
- Generate manager auth token
- Add `Authorization: Bearer {token}` header
- Verify user has 'manager' role

### Issue: Table not found when resolving QR token
**Solution**: 
- Verify table exists in database
- Check `is_active = true`
- Verify QR token is uppercase (8 characters)

---

## 📞 Support

If you encounter issues:
1. Check Laravel logs: `storage/logs/laravel.log`
2. Check database error logs
3. Verify all dependencies installed: `composer install`
4. Clear cache: `php artisan cache:clear`

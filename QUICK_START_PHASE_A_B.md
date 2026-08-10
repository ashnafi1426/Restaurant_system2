# Quick Start Guide - Phase A & B

## 🚀 5-Minute Setup

### Step 1: Run Migrations (30 seconds)
```bash
cd server
php artisan migrate
```

### Step 2: Add Routes (2 minutes)
Open `server/routes/api.php` and add these imports at the top:
```php
use App\Http\Controllers\Api\QRResolutionController;
use App\Http\Controllers\Api\UnifiedOrderController;
use App\Http\Controllers\Api\Manager\RestaurantTableController;
```

Then add these routes:
```php
// QR Resolution (Public)
Route::post('/qr/resolve', [QRResolutionController::class, 'resolveQRToken']);
Route::get('/qr/resolve/{token}', [QRResolutionController::class, 'resolveFromUrl']);
Route::post('/qr/validate', [QRResolutionController::class, 'validateQRToken']);

// Unified Orders (Public)
Route::post('/orders', [UnifiedOrderController::class, 'createOrder']);

// Restaurant Tables (Manager)
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

### Step 3: Seed Test Data (30 seconds)
```bash
php artisan db:seed --class=RestaurantTableSeeder
```

### Step 4: Link Storage (if needed)
```bash
php artisan storage:link
```

### Step 5: Quick Test (1 minute)
```bash
# Start server
php artisan serve

# In another terminal, test QR resolution:
# Get a table QR token from database first
php artisan tinker
>>> $table = \App\Models\RestaurantTable::first();
>>> echo $table->qr_token;
>>> exit

# Test the endpoint (replace TOKEN with actual token)
curl http://localhost:8000/api/qr/resolve/TOKEN
```

---

## 📂 What Was Created?

### Database
- ✅ `restaurant_tables` table (with QR codes)
- ✅ `orders.room_id`, `guest_id`, `reservation_id` → nullable
- ✅ `orders.table_id`, `order_type` → added

### Backend
- ✅ `RestaurantTable` model
- ✅ `Order` model updated
- ✅ `QRResolutionService`
- ✅ 3 new controllers

### Files Location
```
server/
├── app/
│   ├── Models/
│   │   ├── RestaurantTable.php          ← NEW
│   │   └── Order.php                     ← MODIFIED
│   ├── Services/
│   │   └── QRResolutionService.php       ← NEW
│   └── Http/Controllers/Api/
│       ├── QRResolutionController.php    ← NEW
│       ├── UnifiedOrderController.php    ← NEW
│       └── Manager/
│           └── RestaurantTableController.php  ← NEW
└── database/
    ├── migrations/
    │   ├── 2026_08_09_000001_create_restaurant_tables_table.php
    │   ├── 2026_08_09_000002_make_orders_foreign_keys_nullable.php
    │   └── 2026_08_09_000003_add_table_and_type_to_orders_table.php
    └── seeders/
        └── RestaurantTableSeeder.php      ← NEW
```

---

## 🧪 Quick Test Checklist

### Must Test Before Phase C:
- [ ] Migrations ran without errors
- [ ] Can create a restaurant table via manager endpoint
- [ ] QR code image generated in `storage/app/public/qr-codes/tables/`
- [ ] Can resolve room QR token → returns context='room'
- [ ] Can resolve table QR token → returns context='table'
- [ ] Can create room service order via `/api/orders`
- [ ] Can create walk-in order via `/api/orders`
- [ ] Walk-in order has NULL room/guest/reservation
- [ ] Table status updates to OCCUPIED

---

## 📋 API Quick Reference

### Resolve QR Token
```bash
GET /api/qr/resolve/{token}
# Returns: { success, context, data, message }
```

### Create Order (Both Types)
```bash
POST /api/orders
Body: {
  "qr_token": "ABC12345",
  "items": [{"menu_item_id": "uuid", "quantity": 2}],
  "special_requests": "optional",
  "payment_type": "room_charge|cash|card"
}
```

### Create Restaurant Table (Manager)
```bash
POST /api/manager/restaurant-tables
Headers: Authorization: Bearer {token}
Body: {
  "table_number": "T01",
  "table_name": "Window Table",
  "capacity": 4,
  "location": "Main Dining"
}
```

### List Tables (Manager)
```bash
GET /api/manager/restaurant-tables?per_page=10&status=available
Headers: Authorization: Bearer {token}
```

---

## 🔑 Key Concepts

### Two Order Types:
1. **room_service** → Has room_id, guest_id, reservation_id
2. **walk_in** → Has table_id only

### QR Token Resolution:
- Frontend sends `qr_token` only
- Backend resolves to room or table
- Frontend shows appropriate UI

### Database Structure:
```sql
orders
├── room_id (nullable)          ← For room service
├── guest_id (nullable)         ← For room service
├── reservation_id (nullable)   ← For room service
├── table_id (nullable)         ← For walk-in
└── order_type (enum)           ← 'room_service' or 'walk_in'
```

---

## ⚠️ Common Issues

### Issue: "Class not found" error
**Fix**: Run `composer dump-autoload`

### Issue: QR codes not generating
**Fix**: 
```bash
chmod -R 775 storage/app/public/qr-codes
php artisan storage:link
```

### Issue: Migration fails
**Fix**: Check if you have existing orders. May need to handle existing data.

### Issue: 401 on manager routes
**Fix**: Login as manager and get auth token

---

## ✅ You're Ready for Phase C When:

- [x] All migrations successful
- [x] Routes added to api.php
- [x] Can create restaurant tables
- [x] QR codes generating
- [x] Can resolve QR tokens
- [x] Can create both order types
- [x] Database structure verified

---

## 📞 Next Steps

1. ✅ Complete Phase A & B setup
2. 🧪 Run tests from PHASE_A_B_TESTING_CHECKLIST.md
3. 📝 Report results
4. ➡️ Get approval to start Phase C (Frontend)

---

## 📚 Documentation Files

- `QR_FOOD_ORDERING_PHASE_A_B_SUMMARY.md` - Complete overview
- `PHASE_B_BACKEND_COMPLETE.md` - Detailed Phase B docs
- `PHASE_A_B_TESTING_CHECKLIST.md` - Full testing guide
- `PHASE_B_ROUTES_TO_ADD.php` - Route configuration
- `QUICK_START_PHASE_A_B.md` - This file

---

**Ready?** Start with migrations, then test the QR resolution endpoint! 🚀

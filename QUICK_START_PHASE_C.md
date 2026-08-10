# Phase C: Quick Start Guide

**Last Updated**: August 9, 2026  
**Time to Deploy**: 10-15 minutes

---

## 🚀 Quick Deployment (Step-by-Step)

### Step 1: Backend Setup (5 minutes)

```bash
# Navigate to server directory
cd Restaurant_system2/server

# Run migrations
php artisan migrate

# You should see:
# ✓ 2026_08_09_000001_create_restaurant_tables_table
# ✓ 2026_08_09_000002_make_orders_foreign_keys_nullable
# ✓ 2026_08_09_000003_add_table_and_type_to_orders_table

# Seed restaurant tables
php artisan db:seed --class=RestaurantTableSeeder

# You should see:
# ✓ 17 restaurant tables created with QR codes

# Create storage symlink
php artisan storage:link

# Clear caches
php artisan config:clear
php artisan route:clear

# Start backend (if not running)
php artisan serve
```

### Step 2: Add Backend Routes (2 minutes)

Open `server/routes/api.php` and add these routes:

```php
// QR Resolution Routes
Route::get('/qr/resolve/{token}', [QRResolutionController::class, 'resolveByToken']);
Route::post('/qr/validate', [QRResolutionController::class, 'validate']);

// Unified Order Creation
Route::post('/orders', [UnifiedOrderController::class, 'store']);

// Manager Restaurant Tables (Protected)
Route::middleware(['auth:sanctum'])->prefix('manager')->group(function () {
    Route::get('/restaurant-tables', [RestaurantTableController::class, 'index']);
    Route::get('/restaurant-tables/statistics', [RestaurantTableController::class, 'statistics']);
    Route::get('/restaurant-tables/{id}', [RestaurantTableController::class, 'show']);
    Route::post('/restaurant-tables', [RestaurantTableController::class, 'store']);
    Route::put('/restaurant-tables/{id}', [RestaurantTableController::class, 'update']);
    Route::delete('/restaurant-tables/{id}', [RestaurantTableController::class, 'destroy']);
    Route::post('/restaurant-tables/{id}/regenerate-qr', [RestaurantTableController::class, 'regenerateQR']);
});
```

**Import these controllers at the top of the file:**
```php
use App\Http\Controllers\Api\QRResolutionController;
use App\Http\Controllers\Api\UnifiedOrderController;
use App\Http\Controllers\Api\Manager\RestaurantTableController;
```

### Step 3: Frontend Setup (2 minutes)

```bash
# Navigate to frontend directory
cd Restaurant_system2/Client2/vue-project

# Install dependencies (if needed)
npm install

# Start dev server
npm run dev

# Frontend should be running at: http://localhost:5173
```

---

## ✅ Quick Verification

### Test Backend (1 minute)

```bash
# Get a table QR token from database
mysql -u your_user -p your_database

SELECT qr_token FROM restaurant_tables LIMIT 1;
# Copy the token (e.g., "ABC12345")
```

Test the endpoint:
```bash
curl http://127.0.0.1:8000/api/qr/resolve/ABC12345
```

**Expected Response:**
```json
{
  "success": true,
  "context": "table",
  "data": {
    "table_id": "uuid",
    "table_number": "T1",
    "table_name": "Main Dining - Table 1",
    "capacity": 4,
    "location": "Main Dining",
    "status": "available"
  },
  "message": "QR code resolved successfully"
}
```

### Test Frontend (2 minutes)

1. **Open browser**: `http://localhost:5173/restaurant-order/ABC12345`
   - Should show menu with "Table 1" header
   
2. **Login as manager** and go to: `http://localhost:5173/manager/restaurant-tables`
   - Should show table list with 17 tables

---

## 🧪 Quick Test Scenarios

### Scenario 1: Walk-in Order (2 minutes)

1. Get a table QR token from database
2. Open: `http://localhost:5173/restaurant-order/{token}`
3. Add items to cart
4. Click "Proceed to Payment"
5. Select "Cash" payment
6. Confirm order
7. ✅ Success modal should show

**Verify in Database:**
```sql
SELECT order_number, order_type, table_id, room_id, guest_id
FROM orders
ORDER BY created_at DESC
LIMIT 1;
```

**Expected**: `order_type = 'walk_in'`, `table_id` is set, `room_id` and `guest_id` are NULL

### Scenario 2: Room Service Order (2 minutes)

1. Get a room QR token from database
2. Open: `http://localhost:5173/order/{token}`
3. Add items to cart
4. Click "Proceed to Payment"
5. Payment should be "Charge to Room"
6. Confirm order
7. ✅ Should redirect to Chapa payment

### Scenario 3: Manager Table Management (2 minutes)

1. Login as manager
2. Go to: `http://localhost:5173/manager/restaurant-tables`
3. Click "Create Table"
4. Fill form: Table Number "T99", Capacity 4
5. Submit
6. ✅ Table should appear in list

---

## 📊 Quick Database Checks

```sql
-- Check restaurant tables
SELECT COUNT(*) FROM restaurant_tables;
-- Expected: 17

-- Check orders table structure
DESCRIBE orders;
-- Should show: table_id, order_type columns

-- View sample tables
SELECT table_number, table_name, location, status, qr_token
FROM restaurant_tables
LIMIT 5;

-- Check both order types exist
SELECT order_type, COUNT(*) as count
FROM orders
GROUP BY order_type;
```

---

## 🐛 Quick Troubleshooting

### Issue: "Table restaurant_tables doesn't exist"
**Fix**: Run migrations
```bash
cd server
php artisan migrate
```

### Issue: "QR code images not loading"
**Fix**: Create storage symlink
```bash
cd server
php artisan storage:link
```

### Issue: "Cannot resolve QR token"
**Fix**: Check routes are added
```bash
cd server
php artisan route:list | grep qr
```

### Issue: "Frontend shows 404"
**Fix**: Restart frontend dev server
```bash
cd Client2/vue-project
npm run dev
```

### Issue: "CORS errors"
**Fix**: Check backend .env
```env
FRONTEND_URL=http://localhost:5173
```

---

## 📁 Quick File Reference

### New Files Created
```
src/services/qrService.ts
src/services/unifiedOrderService.ts
src/types/restaurantTable.ts
src/services/manager/restaurantTableService.ts
src/stores/restaurantTableStore.ts
src/views/manager/RestaurantTables.vue
src/components/manager/RestaurantTableFormModal.vue
```

### Modified Files
```
src/views/guest/QRMenu.vue
src/router/index.ts
src/router/managerRouter.ts
```

---

## 🎯 Success Criteria

Phase C is successfully deployed when:

- [x] Backend migrations ran without errors
- [x] 17 sample tables exist in database
- [x] QR codes generated in storage
- [x] Backend routes return correct responses
- [x] Frontend loads without console errors
- [x] Walk-in orders can be placed
- [x] Room service orders can be placed
- [x] Manager can view/create/edit/delete tables
- [x] QR codes can be viewed and downloaded

---

## 🔜 Next Steps

Once Phase C is verified:

1. **Run Full Test Suite** (30 minutes)
   - Follow: `PHASE_C_DEPLOYMENT_CHECKLIST.md`
   
2. **Review Documentation** (15 minutes)
   - Read: `PHASE_C_DEVELOPER_GUIDE.md`
   
3. **Plan Phase D** (15 minutes)
   - Kitchen & Waiter Integration
   - Estimated time: 1-2 hours

---

## 💡 Quick Tips

### Get QR Tokens Fast
```sql
-- Get a table QR token
SELECT qr_token FROM restaurant_tables WHERE table_number = 'T1';

-- Get a room QR token
SELECT qr_token FROM rooms WHERE room_number = '101';
```

### Test API Endpoints
```bash
# Install HTTPie (optional, easier than curl)
pip install httpie

# Test QR resolution
http GET http://127.0.0.1:8000/api/qr/resolve/ABC12345

# Test order creation
http POST http://127.0.0.1:8000/api/orders \
  qr_token="ABC12345" \
  items:='[{"menu_item_id":"uuid","quantity":1}]' \
  payment_type="cash"
```

### Frontend Hot Reload
The dev server has hot reload enabled. Changes to Vue files will automatically update the browser.

---

## 📞 Quick Help

**Need detailed instructions?**
- Deployment: `PHASE_C_DEPLOYMENT_CHECKLIST.md`
- Development: `PHASE_C_DEVELOPER_GUIDE.md`
- Overview: `PHASE_C_COMPLETE_SUMMARY.md`

**Got issues?**
- Check troubleshooting section above
- Review backend logs: `server/storage/logs/laravel.log`
- Check browser console for errors

---

## ✅ Quick Checklist

**Before Starting:**
- [ ] Backend is running (http://127.0.0.1:8000)
- [ ] Database is accessible
- [ ] Phase A & B completed

**After Deployment:**
- [ ] Migrations ran successfully
- [ ] 17 tables created
- [ ] Storage symlink exists
- [ ] Routes added to api.php
- [ ] Frontend builds without errors
- [ ] Can access both routes

**Verification:**
- [ ] Table QR works
- [ ] Room QR works
- [ ] Manager view loads
- [ ] Orders create correctly

---

**Total Time**: ~15 minutes  
**Difficulty**: Easy  
**Status**: Ready to Deploy ✅

---

**Happy Testing! 🚀**

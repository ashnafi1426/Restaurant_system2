# Phase A & B Deployment Commands

Quick reference for deploying Phase A & B to your development environment.

---

## 🚀 Quick Deploy (5 Minutes)

Run these commands in order:

```bash
# Navigate to server directory
cd c:\Users\Ashu\Desktop\Rasturant\Restaurant_system2\server

# 1. Check dependencies (30 seconds)
composer show simplesoftwareio/simple-qrcode

# If not installed:
composer require simplesoftwareio/simple-qrcode

# 2. Run migrations (30 seconds)
php artisan migrate

# 3. Seed test data (30 seconds)
php artisan db:seed --class=RestaurantTableSeeder

# 4. Link storage (10 seconds)
php artisan storage:link

# 5. Clear caches (20 seconds)
php artisan cache:clear
php artisan config:clear
php artisan route:clear
composer dump-autoload

# 6. Start server (if not running)
php artisan serve
```

---

## 📝 Step-by-Step Deployment

### Step 1: Install Dependencies

```bash
cd c:\Users\Ashu\Desktop\Rasturant\Restaurant_system2\server
composer require simplesoftwareio/simple-qrcode
```

**Expected Output**: "Package installed successfully"

---

### Step 2: Add Routes

**File**: `server/routes/api.php`

**Add at the top** (with other use statements):
```php
use App\Http\Controllers\Api\QRResolutionController;
use App\Http\Controllers\Api\UnifiedOrderController;
use App\Http\Controllers\Api\Manager\RestaurantTableController;
```

**Add routes** (copy from `PHASE_B_ROUTES_TO_ADD.php`):
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

---

### Step 3: Run Migrations

```bash
cd c:\Users\Ashu\Desktop\Rasturant\Restaurant_system2\server
php artisan migrate
```

**Expected Output**:
```
Migrating: 2026_08_09_000001_create_restaurant_tables_table
Migrated:  2026_08_09_000001_create_restaurant_tables_table (XX.XXms)
Migrating: 2026_08_09_000002_make_orders_foreign_keys_nullable
Migrated:  2026_08_09_000002_make_orders_foreign_keys_nullable (XX.XXms)
Migrating: 2026_08_09_000003_add_table_and_type_to_orders_table
Migrated:  2026_08_09_000003_add_table_and_type_to_orders_table (XX.XXms)
```

**If Error**: Check database connection in `.env`

---

### Step 4: Verify Database Schema

```bash
php artisan tinker
```

Then run:
```php
Schema::hasTable('restaurant_tables')
// Should return: true

Schema::hasColumn('orders', 'table_id')
// Should return: true

Schema::hasColumn('orders', 'order_type')
// Should return: true

DB::table('restaurant_tables')->count()
// Should return: 0 (before seeding)

exit
```

---

### Step 5: Seed Test Data

```bash
php artisan db:seed --class=RestaurantTableSeeder
```

**Expected Output**:
```
Created table: T01 - Window Table 1
Created table: T02 - Window Table 2
...
Created table: B03 - Bar High Table
✅ Restaurant tables seeded successfully!
Total tables created: 17
```

---

### Step 6: Verify QR Codes Generated

Check file system:
```bash
dir "c:\Users\Ashu\Desktop\Rasturant\Restaurant_system2\server\storage\app\public\qr-codes\tables"
```

**Expected**: See 17 PNG files (table_T01.png, table_T02.png, etc.)

Or use tinker:
```bash
php artisan tinker
```

```php
$table = \App\Models\RestaurantTable::first();
$table->qr_token
// Should show 8-character token like "ABC12XYZ"

$table->qr_image_path
// Should show "qr-codes/tables/table_T01.png"

$table->qr_code_url
// Should show full URL like "http://localhost:8000/storage/qr-codes/tables/table_T01.png"

exit
```

---

### Step 7: Link Storage (if needed)

```bash
php artisan storage:link
```

**Expected Output**:
```
The [public/storage] link has been connected to [storage/app/public].
The links have been created.
```

**If already exists**: "The [public/storage] link already exists."

---

### Step 8: Set Frontend URL in .env

Edit `server/.env`:
```
FRONTEND_URL=http://localhost:5173
```

Or use command:
```bash
cd c:\Users\Ashu\Desktop\Rasturant\Restaurant_system2\server
echo FRONTEND_URL=http://localhost:5173 >> .env
```

---

### Step 9: Clear Caches

```bash
php artisan cache:clear
php artisan config:clear
php artisan route:clear
composer dump-autoload
```

**Expected**: Success messages for each command

---

### Step 10: Start/Restart Server

```bash
# If not running:
php artisan serve

# If running, restart it (Ctrl+C then):
php artisan serve
```

**Expected Output**:
```
Starting Laravel development server: http://127.0.0.1:8000
```

---

## 🧪 Quick Test Commands

### Test 1: Check Tables Created
```bash
php artisan tinker
```
```php
\App\Models\RestaurantTable::count()
// Should return: 17

\App\Models\RestaurantTable::first()->qr_token
// Should return: 8-character token

exit
```

---

### Test 2: Test QR Resolution (PowerShell)
```powershell
# Get a table token first
cd c:\Users\Ashu\Desktop\Rasturant\Restaurant_system2\server
php artisan tinker

# In tinker:
$token = \App\Models\RestaurantTable::first()->qr_token;
echo $token;
exit

# Then test API (replace TOKEN with actual value):
Invoke-RestMethod -Uri "http://localhost:8000/api/qr/resolve/TOKEN" -Method GET
```

**Expected Response**:
```json
{
  "success": true,
  "context": "table",
  "data": {
    "table_id": "...",
    "table_number": "T01",
    "table_name": "Window Table 1",
    "capacity": 2,
    "location": "Main Dining",
    "status": "available"
  },
  "message": "QR code belongs to a restaurant table"
}
```

---

### Test 3: Check Routes Loaded
```bash
php artisan route:list | findstr "qr"
php artisan route:list | findstr "restaurant-tables"
php artisan route:list | findstr "orders"
```

**Expected**: Should see all new routes listed

---

## 🐛 Troubleshooting

### Issue: Migration fails
```bash
# Check database connection
php artisan db:show

# Check migration status
php artisan migrate:status

# Roll back and try again
php artisan migrate:rollback
php artisan migrate
```

---

### Issue: QR codes not generating
```bash
# Check storage permissions
dir "c:\Users\Ashu\Desktop\Rasturant\Restaurant_system2\server\storage\app\public"

# Create directory manually
mkdir "c:\Users\Ashu\Desktop\Rasturant\Restaurant_system2\server\storage\app\public\qr-codes\tables"

# Check if package installed
composer show simplesoftwareio/simple-qrcode
```

---

### Issue: Routes not found
```bash
# Clear route cache
php artisan route:clear

# Check if routes file was edited correctly
php artisan route:list

# Dump autoload
composer dump-autoload
```

---

### Issue: 404 on QR resolve endpoint
```bash
# Verify routes added
php artisan route:list | findstr "qr"

# Check server is running
# Open browser: http://localhost:8000/api/qr/resolve/TESTTOKEN
```

---

## ✅ Verification Checklist

Run through this checklist to verify deployment:

```bash
cd c:\Users\Ashu\Desktop\Rasturant\Restaurant_system2\server

# [ ] Dependencies installed
composer show simplesoftwareio/simple-qrcode

# [ ] Migrations ran
php artisan migrate:status

# [ ] Tables created
php artisan tinker
>>> \App\Models\RestaurantTable::count()
>>> exit

# [ ] QR codes generated
dir "storage\app\public\qr-codes\tables"

# [ ] Storage linked
dir "public\storage"

# [ ] Routes added
php artisan route:list | findstr "qr"

# [ ] Server running
# Check: http://localhost:8000
```

---

## 📊 Get Statistics

```bash
php artisan tinker
```

```php
// Count tables
\App\Models\RestaurantTable::count()

// Count by status
\App\Models\RestaurantTable::where('status', 'available')->count()
\App\Models\RestaurantTable::where('status', 'occupied')->count()

// Get all table numbers
\App\Models\RestaurantTable::pluck('table_number')

// Check order types
\App\Models\Order::pluck('order_type')->unique()

// Count orders by type
\App\Models\Order::where('order_type', 'room_service')->count()
\App\Models\Order::where('order_type', 'walk_in')->count()

exit
```

---

## 🔄 Rollback Commands (if needed)

If you need to undo the changes:

```bash
# Rollback last 3 migrations
php artisan migrate:rollback --step=3

# Delete seeded data
php artisan tinker
>>> \App\Models\RestaurantTable::truncate()
>>> exit

# Clear QR codes
del "storage\app\public\qr-codes\tables\*.png"
```

---

## 📝 Next Steps After Deployment

Once deployed and tested:

1. ✅ Verify all endpoints work
2. ✅ Test creating orders (both types)
3. ✅ Check QR codes accessible
4. 📝 Document any issues
5. ➡️ Report results
6. ➡️ Proceed to Phase C (Frontend)

---

## 🆘 Need Help?

- Check `server/storage/logs/laravel.log` for errors
- Run `php artisan about` for system info
- Check database with: `php artisan db:show`
- Test routes with: `php artisan route:list`

---

**Ready to deploy?** Run the Quick Deploy commands at the top! 🚀

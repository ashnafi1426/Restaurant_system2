# Phase C: Deployment Checklist

**Date**: August 9, 2026  
**Phase**: C - Frontend Implementation  
**Status**: Ready for Deployment

---

## 📋 Pre-Deployment Checklist

### Backend Prerequisites (Must be completed first!)

#### Phase A: Database ✅
- [ ] Migration 1: `2026_08_09_000001_create_restaurant_tables_table.php`
- [ ] Migration 2: `2026_08_09_000002_make_orders_foreign_keys_nullable.php`
- [ ] Migration 3: `2026_08_09_000003_add_table_and_type_to_orders_table.php`
- [ ] Run: `php artisan migrate`
- [ ] Verify: `restaurant_tables` table exists
- [ ] Verify: `orders` table has nullable foreign keys
- [ ] Verify: `orders` table has `table_id` and `order_type` columns

#### Phase B: Backend ✅
- [ ] Routes added to `server/routes/api.php` (from `PHASE_B_ROUTES_TO_ADD.php`)
- [ ] Models created and working
- [ ] Services created and working
- [ ] Controllers created and working
- [ ] Run: `php artisan db:seed --class=RestaurantTableSeeder`
- [ ] Run: `php artisan storage:link`
- [ ] Verify: Sample tables created (17 tables)
- [ ] Verify: QR codes generated in `storage/app/public/qr-codes/tables/`
- [ ] Test API endpoints with Postman/curl

### Frontend Files Created ✅
- [ ] `Client2/vue-project/src/services/qrService.ts`
- [ ] `Client2/vue-project/src/services/unifiedOrderService.ts`
- [ ] `Client2/vue-project/src/types/restaurantTable.ts`
- [ ] `Client2/vue-project/src/services/manager/restaurantTableService.ts`
- [ ] `Client2/vue-project/src/stores/restaurantTableStore.ts`
- [ ] `Client2/vue-project/src/views/manager/RestaurantTables.vue`
- [ ] `Client2/vue-project/src/components/manager/RestaurantTableFormModal.vue`

### Frontend Files Modified ✅
- [ ] `Client2/vue-project/src/views/guest/QRMenu.vue`
- [ ] `Client2/vue-project/src/router/index.ts`
- [ ] `Client2/vue-project/src/router/managerRouter.ts`

---

## 🔧 Environment Configuration

### Backend (.env)
```bash
cd server

# Check/Set these variables
FRONTEND_URL=http://localhost:5173
APP_URL=http://127.0.0.1:8000

# Database should already be configured
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=your_database
DB_USERNAME=your_username
DB_PASSWORD=your_password

# Storage (for QR codes)
FILESYSTEM_DISK=public
```

### Frontend (if needed)
```bash
cd Client2/vue-project

# Check vite.config.ts or .env (if exists)
# API base URL should point to backend
```

---

## 🚀 Deployment Steps

### Step 1: Backend Deployment
```bash
cd server

# 1. Run migrations
php artisan migrate

# Expected output:
# ✓ 2026_08_09_000001_create_restaurant_tables_table
# ✓ 2026_08_09_000002_make_orders_foreign_keys_nullable
# ✓ 2026_08_09_000003_add_table_and_type_to_orders_table

# 2. Seed restaurant tables
php artisan db:seed --class=RestaurantTableSeeder

# Expected output:
# ✓ 17 restaurant tables created
# ✓ QR codes generated

# 3. Create storage symlink
php artisan storage:link

# Expected output:
# The [public/storage] link has been connected to [storage/app/public]

# 4. Clear caches
php artisan config:clear
php artisan route:clear
php artisan cache:clear

# 5. Start backend server (if not already running)
php artisan serve
# Should be accessible at http://127.0.0.1:8000
```

### Step 2: Verify Backend
```bash
# Test QR resolution endpoint
curl http://127.0.0.1:8000/api/qr/resolve/{qr_token}

# Expected: JSON response with context and data

# Test restaurant tables list
curl -H "Authorization: Bearer {manager_token}" \
  http://127.0.0.1:8000/api/manager/restaurant-tables

# Expected: Paginated list of 17 tables

# Test unified orders endpoint
curl -X POST http://127.0.0.1:8000/api/orders \
  -H "Content-Type: application/json" \
  -d '{"qr_token":"ABC12345","items":[{"menu_item_id":"uuid","quantity":1}]}'

# Expected: Order created successfully
```

### Step 3: Frontend Deployment
```bash
cd Client2/vue-project

# 1. Install dependencies (if not already installed)
npm install

# 2. Run TypeScript type check
npm run type-check

# Expected: No errors

# 3. Build for production (optional - for testing)
npm run build

# Expected: dist folder created with no errors

# 4. Start dev server
npm run dev

# Expected: Server running at http://localhost:5173
```

### Step 4: Verify Frontend
Open browser and test:

**Public Routes**:
1. `http://localhost:5173/restaurant-order/{qr_token}`
   - Should show menu
   - Should detect table context
   - Should allow ordering

2. `http://localhost:5173/order/{qr_token}`
   - Should show menu
   - Should detect room context
   - Should allow ordering

**Manager Routes** (login as manager first):
3. `http://localhost:5173/manager/restaurant-tables`
   - Should show table list
   - Should show statistics
   - Should allow CRUD operations

---

## 🧪 Testing After Deployment

### Test 1: Room Service Order (End-to-End)
```
1. Get a room QR token from database:
   SELECT qr_token FROM rooms WHERE room_number = '101' LIMIT 1;

2. Open: http://localhost:5173/order/{qr_token}

3. Verify:
   ✓ Page loads
   ✓ Shows "Room 101" (or correct room number)
   ✓ Menu items load
   ✓ Can add to cart
   ✓ Cart shows items
   ✓ Payment option is "Charge to Room"
   ✓ Can place order
   ✓ Order appears in database with order_type='room_service'
```

### Test 2: Walk-in Restaurant Order (End-to-End)
```
1. Get a table QR token from database:
   SELECT qr_token FROM restaurant_tables WHERE table_number = 'T1' LIMIT 1;

2. Open: http://localhost:5173/restaurant-order/{qr_token}

3. Verify:
   ✓ Page loads
   ✓ Shows "Table 1" (or correct table number)
   ✓ Menu items load
   ✓ Can add to cart
   ✓ Cart shows items
   ✓ Payment options are "Cash" and "Card"
   ✓ Can place order
   ✓ Success modal shows
   ✓ Order appears in database with order_type='walk_in'
```

### Test 3: Manager Table Management
```
1. Login as manager

2. Navigate to: http://localhost:5173/manager/restaurant-tables

3. Test Create:
   ✓ Click "Create Table"
   ✓ Fill form (table_number: "T99", capacity: 4)
   ✓ Submit
   ✓ Table appears in list
   ✓ QR code generated

4. Test View QR:
   ✓ Click "View" QR button
   ✓ QR code image displays
   ✓ Can download QR
   ✓ Can regenerate QR

5. Test Edit:
   ✓ Click edit icon
   ✓ Form pre-fills with data
   ✓ Change capacity to 6
   ✓ Submit
   ✓ Changes saved

6. Test Delete:
   ✓ Click delete icon
   ✓ Confirmation modal shows
   ✓ Confirm delete
   ✓ Table removed from list

7. Test Search/Filter:
   ✓ Search for "T1"
   ✓ Only matching tables show
   ✓ Filter by status "available"
   ✓ Only available tables show

8. Test Pagination:
   ✓ If more than 10 tables, pagination shows
   ✓ Can navigate between pages
```

### Test 4: Error Handling
```
Test Invalid QR Token:
1. Open: http://localhost:5173/restaurant-order/INVALID123
2. Verify: Error message shows

Test Expired QR Token:
1. Delete a table from database
2. Try to use its QR token
3. Verify: Error message shows

Test Network Error:
1. Stop backend server
2. Try to load menu
3. Verify: Error message shows
4. Restart backend server
5. Verify: Works again
```

---

## 📊 Database Verification Queries

### Check Tables Created
```sql
-- Check restaurant_tables table
SELECT COUNT(*) FROM restaurant_tables;
-- Expected: 17 rows

-- Check orders table columns
DESCRIBE orders;
-- Should show: table_id (nullable), order_type (enum)

-- View sample restaurant tables
SELECT table_number, table_name, location, status, qr_token
FROM restaurant_tables
LIMIT 5;

-- Check QR tokens are unique
SELECT COUNT(DISTINCT qr_token) FROM restaurant_tables;
-- Should equal total count
```

### Check Orders After Testing
```sql
-- Room service orders
SELECT id, order_number, order_type, room_id, guest_id, table_id
FROM orders
WHERE order_type = 'room_service'
LIMIT 5;

-- Walk-in orders
SELECT id, order_number, order_type, room_id, guest_id, table_id
FROM orders
WHERE order_type = 'walk_in'
LIMIT 5;

-- Verify nullable foreign keys
SELECT COUNT(*)
FROM orders
WHERE room_id IS NULL AND guest_id IS NULL;
-- Should have walk-in orders (> 0)
```

---

## 🔍 Troubleshooting

### Issue: "Table restaurant_tables doesn't exist"
**Solution**:
```bash
php artisan migrate
```

### Issue: "QR code images not loading"
**Solution**:
```bash
php artisan storage:link
# Check: storage/app/public/qr-codes/tables/ has images
# Check: public/storage symlink exists
```

### Issue: "Cannot resolve QR token"
**Solution**:
```bash
# Check backend routes are added
php artisan route:list | grep qr

# Expected:
# GET|HEAD  api/qr/resolve/{token}
# POST      api/qr/validate

# If missing, add routes from PHASE_B_ROUTES_TO_ADD.php
```

### Issue: "Manager routes return 403 Forbidden"
**Solution**:
```bash
# Check user has manager role
SELECT u.name, r.name as role
FROM users u
JOIN model_has_roles mhr ON u.id = mhr.model_id
JOIN roles r ON mhr.role_id = r.id
WHERE u.email = 'manager@example.com';

# If no manager role, assign it
# (Use appropriate method for your app)
```

### Issue: "Orders table foreign key errors"
**Solution**:
```bash
# Check migration ran successfully
php artisan migrate:status

# If needed, rollback and re-run
php artisan migrate:rollback --step=3
php artisan migrate
```

### Issue: "Frontend API calls fail (CORS)"
**Solution**:
```bash
# Check server/config/cors.php
# Verify FRONTEND_URL in server/.env
# Restart backend server
```

### Issue: "TypeScript errors in frontend"
**Solution**:
```bash
cd Client2/vue-project
npm run type-check

# Check imports are correct
# Verify all type files exist
```

---

## ✅ Final Verification Checklist

### Backend
- [ ] All 3 migrations ran successfully
- [ ] RestaurantTableSeeder created 17 tables
- [ ] QR codes exist in `storage/app/public/qr-codes/tables/`
- [ ] Storage symlink created (`public/storage` → `storage/app/public`)
- [ ] All API routes accessible
- [ ] Backend server running on http://127.0.0.1:8000

### Frontend
- [ ] All new files created
- [ ] All modified files updated
- [ ] No TypeScript errors
- [ ] Dev server running on http://localhost:5173
- [ ] No console errors in browser

### Functionality
- [ ] Room service orders work end-to-end
- [ ] Walk-in restaurant orders work end-to-end
- [ ] Manager can view table list
- [ ] Manager can create tables
- [ ] Manager can edit tables
- [ ] Manager can delete tables
- [ ] Manager can view QR codes
- [ ] Manager can download QR codes
- [ ] Manager can regenerate QR codes
- [ ] Search and filters work
- [ ] Pagination works

### Data Integrity
- [ ] Orders table has correct structure
- [ ] Restaurant tables have unique QR tokens
- [ ] Room service orders have room_id and guest_id
- [ ] Walk-in orders have table_id, null room_id/guest_id
- [ ] Order types are correctly set

---

## 📝 Post-Deployment Notes

### Database Backups
```bash
# Before deployment (recommended)
mysqldump -u username -p database_name > backup_before_phase_c.sql
```

### Rollback Plan (If needed)
```bash
# Rollback migrations
cd server
php artisan migrate:rollback --step=3

# Remove seeded data
DELETE FROM restaurant_tables;

# Restore backup
mysql -u username -p database_name < backup_before_phase_c.sql

# Remove frontend changes (git)
cd Client2/vue-project
git checkout -- .
```

### Monitoring
After deployment, monitor:
- Server logs: `storage/logs/laravel.log`
- Browser console for JavaScript errors
- Network tab for failed API calls
- Database for order creation

---

## 🎉 Deployment Complete!

Once all checklist items are verified:
- ✅ Phase C is successfully deployed
- ✅ System supports room service AND walk-in orders
- ✅ Manager can manage restaurant tables
- ✅ Ready for Phase D (Kitchen & Waiter Integration)

---

## 📞 Support

If issues arise:
1. Check troubleshooting section above
2. Review error logs (backend and browser)
3. Verify all prerequisites are met
4. Test with sample data first
5. Refer to `PHASE_C_COMPLETE_SUMMARY.md` for implementation details

---

**Deployment Date**: _______________  
**Deployed By**: _______________  
**Status**: ⬜ Pending | ⬜ In Progress | ⬜ Complete  
**Notes**: 

---

**Document Version**: 1.0  
**Last Updated**: August 9, 2026

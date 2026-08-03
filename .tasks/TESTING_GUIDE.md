# UNIFIED QR ORDERING SYSTEM - TESTING GUIDE
## Complete End-to-End Test Instructions

**Date**: July 31, 2026  
**Status**: Ready for Testing  
**Estimated Time**: 30 minutes

---

## 🎯 QUICK START

### Prerequisites
```bash
# Backend requirements
✅ PHP 8.1+
✅ Laravel 12
✅ PostgreSQL running
✅ Redis (optional for cache)

# Frontend requirements
✅ Node.js 18+
✅ Vue 3
✅ npm/yarn
```

---

## 📋 SETUP (First Time Only)

### 1. Backend Setup
```bash
cd server

# Install dependencies (if not done)
composer install

# Create .env if needed
cp .env.example .env

# Set up database
php artisan migrate

# Seed restaurant tables
php artisan db:seed --class=RestaurantTableSeeder

# Clear caches
php artisan cache:clear
php artisan config:clear
```

### 2. Frontend Setup
```bash
cd Client2/vue-project

# Install dependencies (if not done)
npm install

# Clear build cache
rm -rf dist node_modules/.vite
npm run build
```

---

## 🚀 RUNNING TESTS

### 1. Start Services

**Terminal 1 - Backend**
```bash
cd c:\Users\Ashu\Desktop\Rasturant\Restaurant_system2\server
php artisan serve
# Should show: http://127.0.0.1:8000
```

**Terminal 2 - Frontend**
```bash
cd c:\Users\Ashu\Desktop\Rasturant\Restaurant_system2\Client2\vue-project
npm run dev
# Should show: http://localhost:5173
```

**Terminal 3 - Monitor Logs (optional)**
```bash
cd c:\Users\Ashu\Desktop\Rasturant\Restaurant_system2\server
tail -f storage/logs/laravel.log
```

---

## ✅ TEST SCENARIOS

### TEST 1: Walk-in Customer Complete Flow

**Objective**: Test walk-in customer from QR scan to order placement

**Steps**:
```
1. Open browser to: http://localhost:5173/menu
   Expected: QRMenu.vue loads

2. See modal: "How are you dining today?"
   Expected: Two buttons visible

3. Click: "I am visiting the restaurant"
   Expected: Modal closes, menu appears

4. Verify console: Check for session creation
   ```
   Open DevTools (F12) → Console
   You should see:
   - POST /api/walk-in/session/initialize → 201 Response
   - Session ID returned
   ```

5. Browse menu
   Expected: 
   - Menu items load
   - Categories visible
   - Search works
   - Items have prices

6. Add items to cart
   - Click on 3 different items
   - Add quantity for each
   Expected:
   - Items appear in cart
   - Quantities update
   - Totals calculate

7. Open cart (floating button at bottom)
   Expected:
   - Items listed
   - Subtotal = sum of (price × qty)
   - Tax = subtotal × 0.15
   - Service = subtotal × 0.10
   - Total = subtotal + tax + service
   Example: $50 subtotal
   - Tax: $7.50 ✅
   - Service: $5.00 ✅
   - Total: $62.50 ✅

8. Click "Place Order"
   Expected:
   - POST /api/walk-in/orders → 201
   - Order success modal appears
   - Order # shown (WLK-001, etc)
   - Table # shown
   - Total amount shown

9. Click "Track Order"
   Expected: Modal closes, can continue shopping

**Verification**:
```bash
# Check database for created records
cd c:\Users\Ashu\Desktop\Rasturant\Restaurant_system2\server

# 1. Check session created
php artisan tinker
>>> DB::table('restaurant_sessions')->latest()->first();
=> Should show walk_in customer type

# 2. Check order created
>>> DB::table('restaurant_orders')->latest()->first();
=> Should show order with items

# 3. Check order items
>>> DB::table('restaurant_order_items')->latest()->limit(3)->get();
=> Should show 3 items added

# 4. Check waiter assigned
>>> $order = DB::table('restaurant_orders')->latest()->first();
>>> $order->waiter_id;
=> Should have a waiter_id assigned
```

**Pass Criteria**:
- ✅ Session created
- ✅ Menu loaded
- ✅ Items added to cart
- ✅ Cart totals correct
- ✅ Order placed successfully
- ✅ Database records created
- ✅ Waiter assigned
- ✅ No console errors

---

### TEST 2: Hotel Guest Flow

**Objective**: Test hotel guest from QR scan to room-charged order

**Steps**:
```
1. Open browser to: http://localhost:5173/order/table-1-qr-token
   (or use any valid qr_token from database)
   Expected: QRMenu.vue loads

2. See modal: "How are you dining today?"
   
3. Click: "I am staying in the hotel"
   Expected: Modal closes, room verification modal appears

4. Enter room number
   Example: "101"
   Expected:
   - Room verification succeeds
   - Menu appears

5. Add items (same as walk-in)
   Expected: Cart updates

6. Open cart and review
   Expected: Same calculations as walk-in

7. Click "Place Order"
   Expected:
   - POST /api/guest/orders → 201
   - Order success modal
   - Order charged to room shown
   - Different order # prefix (HG-001, etc)

**Verification**:
```bash
php artisan tinker
>>> $order = DB::table('restaurant_orders')->latest()->first();
>>> $order->order_number;
=> Should start with "HG-" not "WLK-"
>>> $order->payment_status;
=> Should be "room_account" not "pending"
```

**Pass Criteria**:
- ✅ Room verification works
- ✅ Menu loads for hotel guest
- ✅ Order placed with room_account status
- ✅ Order number indicates hotel guest
- ✅ No payment screen shown

---

### TEST 3: API Direct Testing

**Objective**: Test API endpoints directly

**Using Postman or curl**:

**3.1 Get Menu Items**
```bash
GET http://localhost:8000/api/guest/menu/items

Expected Response (200):
{
  "success": true,
  "data": [
    {
      "id": 1,
      "name": "Grilled Salmon",
      "price": 25.00,
      "image": "...",
      "category": "Main Course"
    },
    ...
  ]
}
```

**3.2 Initialize Session**
```bash
POST http://localhost:8000/api/walk-in/session/initialize
Content-Type: application/json

{
  "qr_token": "table-1-qr-token"
}

Expected Response (201):
{
  "success": true,
  "data": {
    "id": "550e8400-e29b-41d4-a716-446655440000",
    "session_number": "TBL-1-20260731-abc123",
    "table_id": "650e8400-e29b-41d4-a716-446655440001",
    "customer_type": "walk_in",
    "status": "ordering",
    "started_at": "2026-07-31T14:30:00Z"
  }
}
```

**3.3 Create Order**
```bash
POST http://localhost:8000/api/walk-in/orders
Content-Type: application/json

{
  "table_id": "550e8400-e29b-41d4-a716-446655440000",
  "items": [
    {
      "menu_item_id": 1,
      "quantity": 2
    },
    {
      "menu_item_id": 5,
      "quantity": 1
    }
  ]
}

Expected Response (201):
{
  "success": true,
  "data": {
    "id": "750e8400-e29b-41d4-a716-446655440002",
    "order_number": "WLK-001",
    "subtotal": 50.00,
    "tax": 7.50,
    "service_charge": 5.00,
    "total": 62.50,
    "payment_status": "pending",
    "order_status": "created"
  }
}
```

**3.4 Get Order**
```bash
GET http://localhost:8000/api/walk-in/orders/750e8400-e29b-41d4-a716-446655440002

Expected Response (200):
{
  "success": true,
  "data": {
    "id": "750e8400-e29b-41d4-a716-446655440002",
    "order_number": "WLK-001",
    "items": [
      {
        "id": 1,
        "name": "Grilled Salmon",
        "quantity": 2,
        "unit_price": 25.00
      },
      {
        "id": 5,
        "name": "Caesar Salad",
        "quantity": 1,
        "unit_price": 15.00
      }
    ],
    "total": 62.50
  }
}
```

**3.5 Get Today's Orders**
```bash
GET http://localhost:8000/api/walk-in/orders/today

Expected Response (200):
{
  "success": true,
  "data": [
    { Order 1 },
    { Order 2 },
    ...
  ]
}
```

**Pass Criteria**:
- ✅ Menu endpoint returns items
- ✅ Session endpoint creates session
- ✅ Order endpoint creates order with correct totals
- ✅ Get order shows correct items
- ✅ Today's orders returns all orders
- ✅ All 2xx status codes

---

### TEST 4: Database Integrity

**Objective**: Verify database consistency and relationships

```bash
cd c:\Users\Ashu\Desktop\Rasturant\Restaurant_system2\server
php artisan tinker

# 1. Verify restaurant tables seeded
>>> DB::table('restaurant_tables')->count();
=> 15

# 2. Check table details
>>> DB::table('restaurant_tables')->first();
=> Should show:
   - id (UUID)
   - table_number (1-15)
   - qr_token (unique)
   - capacity (4, 6, or 8)
   - status (available)
   - assigned_waiter_id (NULL initially)

# 3. Check session relationships
>>> $session = DB::table('restaurant_sessions')->latest()->first();
>>> $session->table_id
>>> DB::table('restaurant_tables')->find($session->table_id);
=> Should find corresponding table

# 4. Check order relationships
>>> $order = DB::table('restaurant_orders')->latest()->first();
>>> $order->restaurant_session_id
>>> $order->table_id
>>> $order->waiter_id
=> All should be valid UUIDs/IDs

# 5. Check order items
>>> DB::table('restaurant_order_items')
   ->where('restaurant_order_id', $order->id)
   ->get();
=> Should show line items with quantities and prices

# 6. Verify foreign key integrity
>>> DB::table('restaurant_orders')
   ->where('table_id', 'invalid-id')
   ->count();
=> Should be 0 (foreign key constraint)

# 7. Check payment table (for future use)
>>> DB::table('walk_in_payments')->count();
=> Should be 0 initially (no payments yet)
```

**Pass Criteria**:
- ✅ 15 tables seeded
- ✅ All UUIDs valid
- ✅ Foreign keys working
- ✅ Orders linked to sessions
- ✅ Order items linked to orders
- ✅ No orphaned records

---

## 🔍 DEBUGGING CHECKLIST

### Issue: Session not created
```
1. Check if QR token exists:
   php artisan tinker
   >>> DB::table('restaurant_tables')->where('qr_token', 'table-1-qr-token')->first();

2. Check API response:
   Network tab → POST /api/walk-in/session/initialize
   Look for error message

3. Check server logs:
   tail -f storage/logs/laravel.log
   Look for validation or database errors

4. Verify table status:
   A table can't have two active sessions
   Check: SELECT * FROM restaurant_sessions WHERE table_id = '...' AND status != 'completed';
```

### Issue: Menu not loading
```
1. Verify menu items exist:
   DB::table('menu_items')->where('is_active', 1)->count();
   Should be > 0

2. Check API response:
   GET /api/guest/menu/items → Should return array

3. Check browser console:
   F12 → Console tab
   Look for JavaScript errors

4. Verify RestaurantService import:
   src/services/restaurantService.ts should exist
```

### Issue: Cart totals wrong
```
1. Check calculation:
   Tax should be: subtotal × 0.15
   Service should be: subtotal × 0.10
   Total should be: subtotal + tax + service

2. Verify restaurantStore:
   Open browser console
   >>> localStorage.restaurantStore
   Should show cart state

3. Check computed properties:
   QRMenu.vue lines ~230-260
   Should calculate all totals
```

### Issue: Order creation fails
```
1. Check request format:
   Must include either table_id OR session_id
   Must include items array with menu_item_id and quantity

2. Verify menu item IDs:
   Items must exist in database and be active
   php artisan tinker
   >>> DB::table('menu_items')->find(1);

3. Check response errors:
   Network tab → POST /api/walk-in/orders
   Look at response JSON for validation messages
```

---

## 📊 VERIFICATION QUERIES

### Quick Database Check
```bash
php artisan tinker

# Summary
>>> [
'tables' => DB::table('restaurant_tables')->count(),
'sessions' => DB::table('restaurant_sessions')->count(),
'orders' => DB::table('restaurant_orders')->count(),
'order_items' => DB::table('restaurant_order_items')->count(),
'payments' => DB::table('walk_in_payments')->count(),
];

# Should show increased counts as tests run
# Example after tests:
# tables: 15
# sessions: 3
# orders: 3
# order_items: 7
# payments: 0
```

### Check Order with Items
```bash
php artisan tinker

>>> $order = DB::table('restaurant_orders')->latest()->first();
>>> $order->with('items')->get();

# Should show:
# {
#   id: uuid,
#   order_number: "WLK-001",
#   total: 62.50,
#   items: [
#     { menu_item_id: 1, quantity: 2 },
#     { menu_item_id: 5, quantity: 1 }
#   ]
# }
```

---

## 🎯 SUCCESS CRITERIA

### All Tests Pass If:

```
✅ Walk-in Flow
  - Customer type modal appears
  - Session creates on "I am visiting" click
  - Menu loads with items
  - Cart updates correctly
  - Totals calculate correctly (15% tax, 10% service)
  - Order places successfully
  - Database records created
  - Waiter auto-assigned

✅ Hotel Guest Flow
  - Customer type modal appears
  - Room verification modal appears on "I am staying" click
  - Menu loads after verification
  - Order creates with room_account payment status
  - Database records created
  - Order number shows HG- prefix

✅ API Tests
  - All endpoints return correct status codes
  - Response JSON is valid
  - All required fields present
  - Calculations correct

✅ Database Tests
  - 15 tables seeded
  - Foreign keys working
  - No orphaned records
  - Constraints enforced
  - Counts increasing with tests

✅ No Critical Errors
  - No 500 errors in backend
  - No uncaught exceptions in frontend
  - No JavaScript errors in console
  - All logs clean
```

---

## 📝 TEST REPORT TEMPLATE

After running all tests, fill in this report:

```
TEST EXECUTION REPORT
=====================

Date: _______________
Tester: ______________
Build: _______________

TEST 1: Walk-in Customer Flow
Result: [ ] PASS [ ] FAIL
Issues: _________________________

TEST 2: Hotel Guest Flow
Result: [ ] PASS [ ] FAIL
Issues: _________________________

TEST 3: API Direct Testing
Result: [ ] PASS [ ] FAIL
Issues: _________________________

TEST 4: Database Integrity
Result: [ ] PASS [ ] FAIL
Issues: _________________________

Overall Status: [ ] ALL PASS [ ] ISSUES FOUND

Critical Issues: ________________
Minor Issues: ___________________

Sign-off: ______________________
```

---

## 🚀 NEXT ACTIONS

### If All Tests Pass ✅
1. Proceed to integration with kitchen dashboard
2. Set up Chapa payment webhook
3. Implement real-time notifications
4. Deploy to staging environment

### If Issues Found ⚠️
1. Note issue details from debugging section
2. Check error logs: `storage/logs/laravel.log`
3. Verify database state: `php artisan tinker`
4. Check browser console: F12 → Console tab
5. Create issue ticket with reproduction steps

---

## 📞 SUPPORT

For issues or questions:
1. Check error logs: `storage/logs/laravel.log`
2. Review browser console: F12 → Console
3. Check network requests: F12 → Network
4. Review database state: `php artisan tinker`
5. Refer to integration guide: `.tasks/WALKIN_INTEGRATION_GUIDE.md`

---

**Testing Guide Ready** ✅

Estimated Time: 30 minutes for complete test cycle
Status: Ready to execute
Next Step: Start Backend (php artisan serve)


# Walk-In Order System - Testing Checklist

## 🚀 Quick Start Testing

### Prerequisites
```bash
✅ Backend running: php artisan serve
✅ Frontend running: npm run dev
✅ Database seeded: php artisan db:seed
```

---

## ✅ Test Checklist

### 1. QR Menu Access
- [ ] Navigate to: `http://localhost:5173/qr-menu/table-2-GveD6NRGFa`
- [ ] Page loads without errors
- [ ] Header shows "Table 2"
- [ ] Menu items display
- [ ] Categories work

**Expected:** QR menu loads with table context

---

### 2. Cart Functionality
- [ ] Click "Add to Cart" on item
- [ ] Cart badge updates with count
- [ ] Click cart icon
- [ ] Cart modal shows items
- [ ] Can increase/decrease quantities
- [ ] Can remove items
- [ ] Subtotal calculates correctly
- [ ] Tax (15%) shows
- [ ] Service charge (10%) shows
- [ ] Total calculates correctly

**Expected:** Cart management works smoothly

---

### 3. Payment Form
- [ ] Click "Proceed to Payment"
- [ ] Payment dialog appears
- [ ] Form shows all fields:
  - First Name
  - Last Name
  - Email
  - Phone
- [ ] Order summary displays
- [ ] Total amount matches cart
- [ ] Fill all fields
- [ ] Click "Pay Now"

**Expected:** Form validation works, redirects to Chapa

---

### 4. Chapa Payment
- [ ] Chapa checkout page loads
- [ ] Order details visible
- [ ] Amount matches
- [ ] Complete payment (test mode)
- [ ] Redirects back to app

**Expected:** Payment flow smooth, returns to success page

---

### 5. Success Page
- [ ] Success page loads
- [ ] Shows "Payment Successful"
- [ ] Displays order number
- [ ] Shows table number (not room)
- [ ] Shows transaction reference
- [ ] Shows payment amount
- [ ] Shows order items
- [ ] "What's Next" section visible

**Expected:** Success page displays all information

---

### 6. Kitchen Dashboard
- [ ] Login as chef
- [ ] Navigate to kitchen dashboard
- [ ] Check "Pending" tab
- [ ] Walk-in order appears
- [ ] Shows table number
- [ ] Shows "Prepaid" or payment status
- [ ] Shows order items
- [ ] Click "Start Preparing"
- [ ] Order moves to "Preparing" tab
- [ ] Click "Mark as Ready"
- [ ] Order moves to "Ready" tab

**Expected:** Order flows through kitchen statuses

---

### 7. Waiter Assignment (Manual)
- [ ] Login as manager
- [ ] Navigate to "Ready Pickup" or delivery management
- [ ] See walk-in order in ready list
- [ ] Assign waiter to order
- [ ] Login as waiter
- [ ] Check notifications or assignments
- [ ] Accept assignment
- [ ] Mark as delivered
- [ ] Order status becomes "Served"

**Expected:** Waiter can deliver walk-in orders

---

### 8. Database Verification

```sql
-- Check payment
SELECT * FROM payments WHERE tx_ref LIKE 'WALKIN-%' ORDER BY created_at DESC LIMIT 1;
-- Expected: status = 'verified', order_id linked

-- Check order
SELECT * FROM orders WHERE order_type = 'walk_in' ORDER BY created_at DESC LIMIT 1;
-- Expected: table_id populated, status = 'served'

-- Check order items
SELECT oi.*, mi.name FROM order_items oi 
JOIN menu_items mi ON oi.menu_item_id = mi.id 
WHERE oi.order_id = 'YOUR_ORDER_ID';
-- Expected: All items present with correct quantities

-- Check table
SELECT * FROM restaurant_tables WHERE id = 'TABLE_ID';
-- Expected: status = 'occupied' (optional)
```

**Expected:** All database records correct

---

## 🐛 Common Issues & Quick Fixes

### Issue: Payment initialization fails
```bash
# Fix: Check Chapa keys
grep CHAPA server/.env
php artisan config:clear
```

### Issue: Order not in kitchen
```sql
-- Check payment status
SELECT status, order_id FROM payments WHERE tx_ref = 'WALKIN-XXX';
-- If order_id NULL, payment verification failed
```

### Issue: QR menu not loading
```bash
# Check QR resolution
curl http://127.0.0.1:8000/api/guest/qr/resolve/table-2-GveD6NRGFa
# Should return table context
```

### Issue: Table not found
```bash
# Seed tables
php artisan db:seed --class=RestaurantTableSeeder
# Verify
php artisan tinker
>>> \App\Models\RestaurantTable::count()
```

---

## 📋 Test Data

### Test Customer Info
```
First Name: John
Last Name: Doe
Email: john@example.com
Phone: +251912345678
```

### Test QR Tokens
```
Table 1: table-1-xxxxxx
Table 2: table-2-GveD6NRGFa
Table 3: table-3-xxxxxx
```

### Test Chapa Credentials
```
Use test mode credentials from Chapa dashboard
Card: 5555 5555 5555 5555
Expiry: Any future date
CVV: Any 3 digits
```

---

## ✅ Success Criteria

| Component | Pass Criteria |
|-----------|--------------|
| QR Menu | Loads without errors, shows table context |
| Cart | Add/remove items, calculates totals correctly |
| Payment | Redirects to Chapa, accepts payment |
| Success Page | Shows order details, transaction ref |
| Kitchen | Order appears, can progress through statuses |
| Waiter | Can accept and deliver order |
| Database | All records created correctly |

---

## 🎯 Final Verification

Run this complete flow 3 times with different:
- [ ] Different tables
- [ ] Different menu items
- [ ] Different quantities

All 3 runs should work identically.

---

## 📞 Support

If any step fails:
1. Check browser console for errors
2. Check Laravel logs: `tail -f server/storage/logs/laravel.log`
3. Check database records (SQL queries above)
4. Review error messages carefully

---

**Status:** Ready for testing! 🚀

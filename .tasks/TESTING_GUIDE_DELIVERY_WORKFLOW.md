# Testing Guide: Complete Delivery Workflow Fix

**Date**: July 30, 2026  
**Status**: ✅ All fixes applied - Ready for testing  

---

## Quick Summary

✅ Fixed: Pickup 500 error (was: "Cannot pickup delivery task in 'assigned' state")  
✅ Fixed: Database migration updated all legacy 'assigned' tasks to 'accepted'  
✅ Fixed: All validation methods now accept both 'assigned' and 'accepted' states  
✅ Fixed: Laravel caches cleared  

**On Delivery page being empty**: This is EXPECTED when there are no active deliveries in transit. Not an error.

---

## Pre-Testing Checklist

Before testing, verify:

1. **Database**: MySQL running on `127.0.0.1:3306`
2. **Database name**: `hotel`
3. **Migrations**: Latest migration `2026_07_30_update_delivery_task_status` was executed
4. **Caches**: Cleared (already done)
5. **Server**: Laravel backend running on port 8000
6. **Frontend**: Vue frontend running on port 5173

---

## Test Scenario 1: Verify Database Migration

**Goal**: Confirm old 'assigned' tasks were converted to 'accepted'

**Steps**:
```bash
# SSH to server or use DB client
mysql -h 127.0.0.1 -u root -p hotel

# Check task statuses
SELECT status, COUNT(*) as count FROM delivery_tasks GROUP BY status;

# Check for any 'assigned' status (should be 0)
SELECT COUNT(*) as assigned_count FROM delivery_tasks WHERE status = 'assigned';
```

**Expected Output**:
```
mysql> SELECT status, COUNT(*) as count FROM delivery_tasks GROUP BY status;
+-------------------+-------+
| status            | count |
+-------------------+-------+
| accepted          |  X    | ← All migrated tasks here
| picked_up         |  X    |
| on_delivery       |  0    |
| delivered         |  X    |
| waiting_assignment|  X    |
| cancelled         |  X    |
+-------------------+-------+

mysql> SELECT COUNT(*) as assigned_count FROM delivery_tasks WHERE status = 'assigned';
+----------------+
| assigned_count |
+----------------+
|              0 | ✅ GOOD - No more 'assigned' status
+----------------+
```

---

## Test Scenario 2: Complete Pickup Workflow

**Goal**: Verify the pickup button works without 500 error

**Prerequisites**:
- At least one order in kitchen marked as "ready"
- Waiter is assigned to that floor

**Steps**:

### 2.1 Kitchen marks order ready

1. Go to Kitchen view
2. See orders being prepared
3. Click "Mark as Ready" on an order
4. Check backend logs - should see `OrderReadyEvent` triggered

**Expected Logs**:
```
[INFO] OrderReadyEvent fired for order_id=XXX
[INFO] Delivery task created with status='accepted'
[INFO] Waiter notification sent
```

### 2.2 Waiter sees ready pickup orders

1. Go to Waiter → "Ready for Pickup" page
2. Should see the order from step 2.1

**Expected**:
```
✅ Order visible
✅ Shows: Room number, Guest name, Items, Wait time
✅ "Pickup Order" button visible
```

### 2.3 Waiter clicks pickup button

1. Click "Pickup Order" button
2. Watch browser Network tab for the request
3. Check server logs

**Expected Logs** (Server):
```
[INFO] 🔵 [SERVICE] pickupOrder called: id=XXX, waiter_id=YYY
[INFO] ✅ [SERVICE] Task found: id=XXX, status=accepted
[INFO] ✅ [SERVICE] Task marked as picked up: id=XXX, new_status=picked_up
```

**Expected Response**:
```json
{
  "success": true,
  "message": "Order picked up successfully",
  "data": {
    "id": "XXX",
    "status": "picked_up",
    "picked_up_at": "2026-07-30 12:34:56",
    ...
  }
}
```

**Status Code**: ✅ 200 OK (NOT 500)

---

## Test Scenario 3: Start Delivery

**Goal**: Move order from "picked up" to "on delivery" state

**Steps**:

1. After pickup (from Test 2.3), order should be removed from "Ready for Pickup"
2. Navigate to "On Delivery" page
3. See the picked-up order there (if explicit start is required)
4. OR it may appear automatically

**Expected**:
- Order appears in "On Delivery" page
- Shows: Room number, Guest name, Pickup time, Duration in transit
- "Mark as Delivered" button visible

---

## Test Scenario 4: Mark Order as Delivered

**Goal**: Complete the delivery workflow

**Steps**:

1. From "On Delivery" page, click "Mark as Delivered"
2. Optional: Enter delivery remarks in dialog
3. Submit

**Expected Logs**:
```
[INFO] 🔵 [SERVICE] deliverOrder called: id=XXX, waiter_id=YYY
[INFO] ✅ [SERVICE] Task marked as delivered: id=XXX, new_status=delivered
[INFO] [WAITER] Current orders decremented from X to X-1
```

**Expected Response**:
```json
{
  "success": true,
  "message": "Order delivered successfully",
  "data": {
    "id": "XXX",
    "status": "delivered",
    "delivered_at": "2026-07-30 12:35:10",
    ...
  }
}
```

**Expected UI**:
- Order disappears from "On Delivery"
- Order appears in "Completed Orders"

---

## Test Scenario 5: Verify No 500 Errors

**Goal**: Ensure all endpoints return correct status codes

**Using Postman or curl**:

```bash
# Test 1: Get Ready for Pickup
curl -X GET http://localhost:8000/api/waiter/dashboard/ready-pickup \
  -H "Authorization: Bearer TOKEN"
# Expected: 200 OK

# Test 2: Pickup Order
curl -X PATCH http://localhost:8000/api/waiter/assignments/TASK_ID/pickup \
  -H "Authorization: Bearer TOKEN"
# Expected: 200 OK (NOT 500)

# Test 3: Start Delivery
curl -X PATCH http://localhost:8000/api/waiter/assignments/TASK_ID/start-delivery \
  -H "Authorization: Bearer TOKEN"
# Expected: 200 OK

# Test 4: Deliver Order
curl -X PATCH http://localhost:8000/api/waiter/assignments/TASK_ID/deliver \
  -H "Authorization: Bearer TOKEN" \
  -d '{"remarks": "Left at front desk"}'
# Expected: 200 OK
```

---

## Test Scenario 6: Verify "On Delivery" Empty Page

**Goal**: Confirm empty page is expected behavior, not a bug

**Steps**:

1. Ensure NO orders are currently in "on_delivery" status
2. Navigate to "On Delivery" page
3. Should show: "No active deliveries - You're all caught up!"

**Expected**:
```
✅ Page loads without errors
✅ Shows empty state message (NOT error)
✅ No console errors
✅ No 500 errors in network tab
```

**What would be WRONG**:
- ❌ Error message instead of empty state
- ❌ Spinner spinning forever
- ❌ Console errors
- ❌ Network error 500

---

## Common Issues & Troubleshooting

### Issue 1: Still getting 500 error on pickup

**Symptom**:
```
Error: "Cannot pickup delivery task in 'assigned' state..."
Status: 500
```

**Solution**:
```bash
# 1. Verify migration ran
php artisan migrate --step
# Should show migration complete

# 2. Check database
mysql -u root -p hotel
SELECT COUNT(*) FROM delivery_tasks WHERE status = 'assigned';
# Should return 0

# 3. Clear caches
php artisan config:clear
php artisan cache:clear

# 4. Restart Laravel
# If using php artisan serve, stop and restart
```

### Issue 2: On Delivery page shows error instead of empty state

**Symptom**:
- Console error visible
- Network tab shows 500 or 400 error

**Solution**:
```bash
# 1. Check server logs
tail -f storage/logs/laravel.log

# 2. Look for error in getOnDelivery
# Common: Missing relationship, null waiter_id, etc.

# 3. Verify waiter is linked to user
mysql -u root -p hotel
SELECT u.id, u.email, w.id as waiter_id FROM users u 
LEFT JOIN waiters w ON w.user_id = u.id 
WHERE u.role = 'waiter';

# If waiter_id is NULL, waiter not linked to user
```

### Issue 3: Caching still showing old behavior

**Symptom**:
- Changes don't take effect
- Still getting old error message

**Solution**:
```bash
# Clear all caches completely
php artisan cache:clear
php artisan config:clear
php artisan view:clear
php artisan route:clear

# If still cached:
# 1. Delete storage/framework/cache/*
# 2. Delete storage/bootstrap/cache/config.php
# 3. Restart PHP-FPM or Laravel server
```

---

## Success Criteria

✅ All tests pass if:

1. **No more 500 errors** on pickup button
2. **Migration completed** with 0 'assigned' tasks left
3. **Ready for Pickup** shows orders
4. **Pickup works** without errors
5. **On Delivery** shows active deliveries (or empty state)
6. **Completed Orders** shows delivered orders
7. **All status transitions** work: accepted → picked_up → on_delivery → delivered
8. **Empty pages show** expected message, not error

---

## Performance Check

After fixes, performance should be:

- **Pickup endpoint**: < 500ms response time
- **Ready for Pickup**: < 1s load time
- **Database queries**: Using proper indexes
- **No N+1 queries**: Related data loaded with `with()`

---

## Rollback Instructions

If issues occur and need to rollback:

```bash
# Rollback one migration
php artisan migrate:rollback --step=1

# This will convert 'accepted' back to 'assigned' 
# (only for non-progressed tasks)

# Then run fresh migrations
php artisan migrate
```

---

## Next Steps

1. **Run all 6 test scenarios** above
2. **Check logs** for any errors
3. **Verify database** has correct statuses
4. **Clear browser cache** (Ctrl+Shift+Del)
5. **Try complete workflow** from order ready → delivered

If all tests pass: ✅ **Delivery system is fully fixed**

If any test fails: Check troubleshooting above or contact development team

---

## Support

**Issue**: Still seeing errors?  
**Solution**:
1. Check `storage/logs/laravel.log` for error details
2. Run `php artisan tinker` to inspect database
3. Verify all files were modified correctly
4. Clear caches multiple times
5. Restart both Laravel and frontend servers


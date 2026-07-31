# Testing Guide: Pickup Workflow

## Pre-Testing Checklist

- [ ] Code changes deployed
- [ ] Frontend rebuild complete
- [ ] Backend cache cleared (artisan cache:clear)
- [ ] Test waiter account logged in
- [ ] Test orders in system (assigned to waiter)

## Test Scenarios

### Scenario 1: Complete Workflow (Happy Path)

**Objective:** Verify complete pickup workflow from acceptance to delivery

**Steps:**
1. Go to Waiter Dashboard → Assigned Orders page
2. Find an order with status `assigned`
3. ✅ Verify: "Accept" button is visible
4. Click "Accept"
   - ✅ Status should change to `accepted`
   - ✅ "Pickup Order" button should appear (orange)
   - ✅ No "Start Delivery" button yet
5. Click "Pickup Order"
   - ✅ Status should change to `picked_up`
   - ✅ "Start Delivery" button should appear (green)
   - ✅ No "Pickup Order" button anymore
6. Click "Start Delivery"
   - ✅ Status should change to `on_delivery`
   - ✅ Order shows "On the Way"
7. Click "Deliver" (if available)
   - ✅ Status should change to `delivered`

**Expected Log Messages:**
```
✅ Order accepted successfully
✅ Order picked up successfully
✅ Delivery started successfully
✅ Order delivered successfully
```

---

### Scenario 2: Modal Workflow

**Objective:** Verify buttons work in modal detail view

**Steps:**
1. From Assigned Orders table, click "View Details"
2. Modal opens showing order details
3. If status is `accepted`:
   - ✅ "Pickup Order" button visible in modal
4. Click "Pickup Order" in modal
   - ✅ Status updates to `picked_up`
   - ✅ Modal should close
   - ✅ Table refreshes with new status
5. Click "View Details" again
6. If status is `picked_up`:
   - ✅ "Start Delivery" button visible in modal
7. Click "Start Delivery"
   - ✅ Status updates to `on_delivery`

---

### Scenario 3: Button Visibility by Status

**Objective:** Verify correct buttons appear for each status

| Order Status | Button Shown | Button Should NOT Show |
|---|---|---|
| assigned | Accept (from accept logic) | ❌ Pickup, ❌ Start Delivery |
| accepted | ✅ Pickup Order | ❌ Start Delivery |
| picked_up | ✅ Start Delivery | ❌ Pickup Order |
| on_delivery | (none for these actions) | ❌ Pickup, ❌ Start Delivery |
| delivered | (none for these actions) | ❌ All action buttons |

**Test:**
1. Navigate through several orders
2. For each order, verify:
   - Correct button appears ✅
   - Incorrect buttons are hidden ✅

---

### Scenario 4: Error Handling

**Objective:** Verify errors are handled gracefully

**Test Cases:**

#### 4.1 Network Error During Pickup
1. Disable internet or mock network failure
2. Click "Pickup Order"
3. ✅ Error message appears
4. ✅ Order status doesn't change
5. ✅ Button remains clickable
6. Re-enable network, retry
7. ✅ Should succeed on retry

#### 4.2 Invalid State Transition
1. Manually set order status to `delivered`
2. Try clicking "Pickup Order"
3. ✅ Button should be hidden (not applicable to delivered)

#### 4.3 Unauthorized Access
1. Try accessing another waiter's order
2. ✅ Proper 403 error response
3. ✅ User-friendly error message shown

---

### Scenario 5: Loading States

**Objective:** Verify loading indicators work

**Steps:**
1. Click "Pickup Order"
2. ✅ Button shows loading spinner/text "Picking up..."
3. ✅ Button is disabled during request
4. ✅ Once complete, button returns to normal state

**Test:**
- Fast network: loading state briefly visible
- Slow network: loading state clearly visible

---

### Scenario 6: Pagination and Multi-Order

**Objective:** Verify workflow works with pagination

**Steps:**
1. Go to Assigned Orders
2. Set items per page to 5
3. Accept and pickup orders on first page
4. Change to page 2
5. ✅ Orders on page 2 still have correct buttons
6. Accept and pickup order on page 2
7. Go back to page 1
8. ✅ Page 1 orders show updated status

---

### Scenario 7: Concurrent Operations

**Objective:** Test multiple orders being processed

**Steps:**
1. Open 2-3 orders in separate tabs/windows
2. Click "Pickup Order" on order 1
3. While loading, click "Pickup Order" on order 2
4. ✅ Both requests process correctly
5. ✅ Each order updates independently
6. ✅ No interference between requests

---

## Browser Console Checks

**During Testing, Verify Console Shows:**

### Successful Pickup:
```
[AssignedOrders] Picking up order: abc-123-def
[AssignedOrders] Full order data: {id: "abc-123-def", ...}
[AssignedOrders] ✅ Order picked up successfully: {status: "picked_up", ...}
[AssignedOrders] ✅ Assignments reloaded after pickup
```

### Successful Start Delivery:
```
[AssignedOrders] Starting delivery for order: abc-123-def
[AssignedOrders] ✅ Delivery started successfully: {status: "on_delivery", ...}
[AssignedOrders] ✅ Assignments reloaded after delivery start
```

### Error Handling:
```
[AssignedOrders] ❌ Error picking up order: {
  status: 400,
  message: "Cannot pickup...",
  error: "..."
}
```

---

## Backend API Testing (Postman/cURL)

### Test Pickup Endpoint

```bash
curl -X PATCH \
  http://localhost:8000/api/waiter/assignments/{id}/pickup \
  -H "Authorization: Bearer {token}" \
  -H "Content-Type: application/json"
```

**Expected Response (Success):**
```json
{
  "success": true,
  "message": "Order picked up successfully",
  "data": {
    "id": "abc-123",
    "status": "picked_up",
    "picked_up_at": "2026-07-30T15:30:45Z"
  }
}
```

### Test Start Delivery Endpoint

```bash
curl -X PATCH \
  http://localhost:8000/api/waiter/assignments/{id}/start-delivery \
  -H "Authorization: Bearer {token}" \
  -H "Content-Type: application/json"
```

**Expected Response (Success):**
```json
{
  "success": true,
  "message": "Delivery started successfully",
  "data": {
    "id": "abc-123",
    "status": "on_delivery",
    "on_delivery_at": "2026-07-30T15:31:20Z"
  }
}
```

---

## Database Verification

### Check Status Transitions in Database

```sql
SELECT id, status, picked_up_at, on_delivery_at, delivered_at 
FROM delivery_tasks 
WHERE waiter_id = '{waiter_id}' 
ORDER BY assigned_at DESC 
LIMIT 10;
```

**Verify:**
- ✅ `picked_up_at` has timestamp when pickup happened
- ✅ `on_delivery_at` has timestamp when delivery started
- ✅ Status correctly reflects current state
- ✅ Timestamps are in chronological order

---

## Performance Testing

### Objective: Ensure no performance regression

**Measurements:**
1. Time to load Assigned Orders page: < 2 seconds
2. Time for Pickup action to complete: < 1 second
3. Time for Start Delivery action to complete: < 1 second
4. Database query time for status update: < 100ms

**Tools:**
- Chrome DevTools → Network Tab (check timing)
- Chrome DevTools → Performance Tab (record operations)
- Backend logs for service execution time

---

## Edge Cases

### Edge Case 1: Rapid Clicking
1. Click "Pickup Order" multiple times quickly
2. ✅ Button becomes disabled after first click
3. ✅ Only one request sent
4. ✅ Status updates once

### Edge Case 2: Order Assigned During Workflow
1. Start accepting order A
2. While accepting, another order B is assigned
3. ✅ Both operations complete correctly
4. ✅ Both appear in list with correct statuses

### Edge Case 3: Stale Data
1. Accept order in one browser tab
2. In another tab, order still shows `assigned`
3. Refresh second tab
4. ✅ Status updates to `accepted`

### Edge Case 4: Session Timeout
1. Long delay between Pickup and Start Delivery
2. Session expires
3. Try to click "Start Delivery"
4. ✅ Redirect to login (or proper error)
5. ✅ No silent failures

---

## Accessibility Testing

- [ ] Tab navigation works through buttons
- [ ] Buttons have proper ARIA labels
- [ ] Loading states announced to screen readers
- [ ] Error messages accessible
- [ ] Color contrast meets WCAG AA standards

---

## Regression Testing

### Verify Existing Functionality Still Works

1. ✅ Accept Assignment workflow unchanged
2. ✅ Reject Assignment workflow unchanged
3. ✅ Deliver Order workflow unchanged
4. ✅ Failed Delivery workflow unchanged
5. ✅ Pagination still works
6. ✅ Filtering/Sorting still works
7. ✅ Notifications still work
8. ✅ Dashboard stats still accurate

---

## Sign-Off Checklist

- [ ] All scenarios pass
- [ ] No console errors
- [ ] No network errors
- [ ] Database states correct
- [ ] Performance acceptable
- [ ] Edge cases handled
- [ ] Accessibility verified
- [ ] No regression issues
- [ ] Documentation complete

---

**Status:** Ready for Testing

**Estimated Time:** 30-45 minutes for complete testing

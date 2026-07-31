# Floor Assignment - Quick Test Steps

## Start Here

### Setup (1 minute)
1. Open terminal 1: `cd server && php artisan serve`
2. Open terminal 2: `cd Client2/vue-project && npm run dev`
3. Wait for both to fully start
4. Open browser at `http://localhost:5173`
5. Login as manager@hotel.com / Manager123@

### Test 1: Open Modal and Check Data (1 minute)
1. Navigate to **Manager** → **Assign Staff to Floors**
2. Click **"Add Staff"** on any floor
3. **Check**:
   -  Modal opens
   -  Modal shows loading
   -  Waiter dropdown has options (should see ~18 waiters)
   -  Shift dropdown has options (should see: Morning, Afternoon, Evening, Night)
   -  No errors in browser console (F12)

### Test 2: Assign a Waiter (1 minute)
1. Select any **Waiter** from dropdown
2. **Check**: Selected waiter card appears below showing details
3. Select any **Shift** from dropdown
4. Click **"Assign Staff"** button
5. **Check**:
   -  Button shows "Assigning..." with spinner
   -  After 1-2 seconds, green success message appears
   -  Modal form clears (dropdowns reset)
   -  Modal stays open

### Test 3: Verify Assignment Appears (1 minute)
1. Close modal (click Cancel or X button)
2. **Check**:
   -  The floor card now shows the assigned waiter
   -  Card shows: avatar with initial, name, shift, priority badge
   -  Badge color matches priority selected

### Test 4: Refresh Page - THE CRITICAL TEST (2 minutes)
1. Press **Ctrl+R** (Windows) or **Cmd+R** (Mac) to reload page
2. **Check**:
   -  Page reloads
   -  Assignment is STILL VISIBLE on floor card
   -  Data persists (NOT lost)
   -  Console shows: "[FloorAssignmentStore] Fetched assignments: X records"

**If this test passes, everything is working! **

### Test 5: Assign More Waiters (1 minute optional)
1. Click "Add Staff" again on same floor
2. Select **different waiter** and **different shift**
3. Click "Assign Staff"
4. Close modal
5. **Check**:
   -  Multiple waiter cards appear on floor
   -  Each shows correct waiter name and shift

---

## If Something Goes Wrong

### Error: Modal shows "No waiters available"
**Fix**:
```bash
# In server terminal:
php artisan db:seed --class=WaiterSeeder
# Then refresh browser
```

### Error: No shifts in dropdown
**Fix**:
```bash
# In server terminal:
php artisan db:seed --class=HotelShiftSeeder
# Then refresh browser
```

### Error: 422 Validation Error
**Check**: Browser console (F12) for detailed error message
**Common causes**:
- Waiter doesn't exist
- Shift doesn't exist
- Floor doesn't exist

### Error: 404 Not Found for stats endpoint
**This is OK!** It doesn't break anything. The page continues to work.
**If needed**, ensure backend server is running: `php artisan serve`

### Assignment disappears after refresh
**Check**: Open browser console Network tab
- Should see `GET /api/manager/floors/assignments/today` returning data
**If not**: Check backend is running and auth token is valid

---

## Expected Results

### Before Fix
- ❌ 422 validation error when trying to assign
- ❌ Modal used hardcoded mock shifts with wrong IDs
- ❌ Assignment disappeared on page refresh
- ❌ Generic error messages, no details

### After Fix
-  Modal loads real shifts from backend (4 UUID shifts)
-  Modal loads real waiters from backend (~18 waiters)
-  Assignment saves to database successfully
-  Assignment persists after page refresh
-  Detailed error messages if validation fails
-  No more 422 errors for valid data

---

## Success Criteria

**ALL of these must pass:**
- [ ] Modal opens without errors
- [ ] Waiter dropdown populated (shows names)
- [ ] Shift dropdown populated (shows: Morning, Afternoon, Evening, Night)
- [ ] Can select waiter and shift
- [ ] Can click "Assign Staff" without 422 error
- [ ] Success message appears
- [ ] Assignment appears on floor card after modal closes
- [ ] Assignment PERSISTS after page refresh ( **MOST IMPORTANT**)
- [ ] Can assign multiple waiters to same floor
- [ ] Console shows no errors (warnings are OK)

---

## Time Estimate
- Setup: 1 min
- Test 1: 1 min
- Test 2: 1 min
- Test 3: 1 min
- Test 4: 2 min ← **CRITICAL**
- Test 5: 1 min (optional)
- **Total: 5-7 minutes**

---

## Developer Notes

### Console Logging
Look for these patterns in console (F12 → Console tab):

**Success**:
```
[AddStaffToFloorModal] Final waiters count: 18
[AddStaffToFloorModal] Final shifts count: 4
[FloorAssignmentStore] Fetched assignments: 1 records
```

**Errors**:
```
[API INTERCEPTOR] Response error: 422
assignments.0.waiter_id.exists
```

### Network Tab
In browser DevTools (F12 → Network):
- Look for `POST /api/manager/floors/assignments` - should return 201 (Created)
- Look for `GET /api/manager/floors/assignments/today` - should return 200
- If 404 appears, backend server likely not running

---

**Status**: Ready to test! Follow the 5 tests above in order.

# Modal Testing - Quick Reference Card

## 4 Parts to Test

### Part 0: Modal Opens
```
Action: Click "Add Staff"
 Check: Modal appears with loading spinner
🔍 Console: [Modal] PART 0:  All data loaded
```

### Part 1: Waiters Load
```
Action: Wait for loading to finish
 Check: Waiter dropdown shows names (Michael Brown, etc.)
 Check: Count ~18 waiters
🔍 Console: [Modal] PART 1:  Loaded 18 waiters
```

### Part 2: Shifts Load
```
Action: Look at Shift dropdown
 Check: Shows 4 shifts:
  - Morning (06:00 - 14:00)
  - Afternoon (14:00 - 22:00)
  - Evening (17:00 - 23:00)
  - Night (22:00 - 06:00)
🔍 Console: [Modal] PART 2:  Loaded 4 shifts
```

### Part 3: Assignment Sends
```
Action: 
1. Select waiter
2. Select shift
3. Select priority
4. Click "Assign Staff"

 Check: Button shows "Assigning..." with spinner
 Check: Success message appears
 Check: Form clears
 Check: NO 422 ERROR
🔍 Console: [Modal] PART 3:  API Response: {...}
```

---

## Pass/Fail Criteria

| Part | Pass | Fail |
|------|------|------|
| 0 | Modal opens | Modal doesn't open |
| 1 | Waiter dropdown full | Empty / "No waiters" |
| 2 | Shift dropdown has 4 | Empty / "No shifts" |
| 3 | Success, no errors | 422 error / timeout |

**All 4 must PASS for feature to work!**

---

## If Something Fails

### Shift dropdown empty?
```bash
# In server terminal:
php artisan db:seed --class=HotelShiftSeeder
php artisan cache:clear
php artisan route:clear
# Then refresh browser
```

### 422 Error on assign?
```
Likely causes:
- Waiter doesn't exist
- Shift doesn't exist  
- Floor doesn't exist

Check console for exact error message
```

### Waiter dropdown empty?
```bash
# Verify API working:
# In server terminal, check logs:
tail -f storage/logs/laravel.log
# Look for errors in /manager/waiters endpoint
```

---

## Browser Console Help

1. Open DevTools: **F12**
2. Go to **Console** tab
3. Look for messages starting with `[Modal]`
4. Red errors = problems
5. Blue logs = normal flow

### Expected Sequence
```
[Modal] PART 0: Modal mounted, isOpen= true
[Modal] PART 0: Starting data load...
[Modal] PART 1: Loading waiters...
[Modal] PART 2: Loading shifts...
[Modal] PART 1: API response: {...}
[Modal] PART 1:  Loaded 18 waiters
[Modal] PART 2: API response: {...}
[Modal] PART 2:  Loaded 4 shifts
[Modal] PART 0:  All data loaded

(User selects and clicks button)

[Modal] PART 3: Building assignment data...
[Modal] PART 3:  Assignment payload: {...}
[Modal] PART 3: Sending to POST /manager/floors/assignments
[Modal] PART 3:  API Response: {...}
```

---

## Success Signs

-  Modal opens
-  Waiters dropdown shows names
-  Shifts dropdown shows 4 options
-  Can select all and click button
-  Green success message
-  No red errors
-  Assignment appears on floor card
-  Assignment persists after refresh

---

**All 8 signs = Feature is working! 🎉**

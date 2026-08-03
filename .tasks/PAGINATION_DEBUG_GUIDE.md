# Pagination Per-Page Selection - Debug Guide

## Problem
When selecting "5 per page" from the dropdown, the table doesn't show only 5 items.

## Debugging Steps to Follow

### Step 1: Open Browser Console
- Press **F12** in your browser (or right-click → Inspect)
- Go to **Console** tab
- Keep it open while testing

### Step 2: Test the Pagination
1. Load the Delivery Management page
2. Open the dropdown and select "5 per page"
3. Check the browser console for logs

### Step 3: Check Console Logs (in order)

Look for these logs to understand the flow:

**A) Select Dropdown Changed:**
```
Select changed to: 5
```
✅ If you see this, the dropdown @change event fired correctly
❌ If missing, the dropdown handler isn't triggering

**B) changePageSize Function Called:**
```
changePageSize called with size: 5
Current store.perPage before update: 20
Updated store.perPage: 5
Fetching deliveries with page 1 and perPage: 5
```
✅ If you see this, the component function is working
❌ If missing, the function isn't being called

**C) Store fetchDeliveries Called:**
```
Store fetchDeliveries called - page: 1 perPage: 5
```
✅ Confirms store received the request
❌ If showing perPage: 20 instead of 5, store didn't update

**D) Service API Response:**
```
getDeliveries raw response: {...}
getDeliveries response.data: {...}
```
✅ Check what the API returned
❌ If error, API call failed

**E) Store Processing Response:**
```
Store received full response: {...}
Processing paginated response format
After pagination - perPage: 5 deliveries count: 5
```
✅ Should show 5 deliveries
❌ If showing more than 5, response format issue
❌ If showing same as before, API didn't filter

### Step 4: Identify the Issue

Based on which logs are missing or incorrect:

| What's Missing/Wrong | Likely Issue |
|-----|-----|
| "Select changed to" not in console | Dropdown @change handler broken |
| "changePageSize called" not in console | Component method not firing |
| "perPage: 20" instead of "5" in Store log | Store.perPage not updating before fetch |
| "deliveries count: 20" instead of "5" | API not respecting per_page parameter |
| Pagination object shows old perPage value | API response not returning correct per_page |

### Step 5: Report Back With Logs

After testing, share:
1. The EXACT console log sequence you see
2. What you expected vs what actually happened
3. The API response (the {...} part in logs)

## Common Issues & Solutions

### Issue: Select shows "10 per page" but doesn't change
**Solution:** The dropdown value binding is using wrong type. Options are strings, need to convert.
✅ **FIXED** - Changed to use `.toString()` and proper value conversion

### Issue: changePageSize logs show perPage: 5 but data still shows 20 items
**Solution:** The API might not be supporting the per_page parameter correctly.
**Check:** Backend API endpoint in `ManagerDeliveryManagementController.php`

### Issue: No logs in console at all
**Solution:** 
1. Hard refresh page (Ctrl+Shift+R on Windows)
2. Check if console is open BEFORE selecting dropdown
3. Try selecting "20 per page" to see if anything changes

## Quick Checklist Before Testing

- [ ] File saved: `DeliveryManagement.vue`
- [ ] File saved: `deliveryManagementStore.ts`
- [ ] Browser console open
- [ ] Page refreshed after changes
- [ ] Deliveries are loading (table shows data)

## Testing Expected Behavior

✅ **When working correctly:**
1. Select "5 per page"
2. See loading spinner briefly
3. Table shows only 5 rows
4. "Page 1 of X (Z total)" updates
5. Console shows all logs in sequence

❌ **If broken:**
1. Table doesn't update
2. Some console logs missing
3. Same number of rows showing
4. Console shows errors

---

**Next Step:** Test it and share the console logs you see. This will tell us exactly where the issue is.

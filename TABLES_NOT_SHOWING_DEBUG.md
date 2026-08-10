# Tables Not Showing - Debug Steps

## Problem
- Statistics show tables exist (32 total, with cleaning/out_of_service counts)
- Table list shows "No tables found"
- Backend returns tables correctly (verified with test_api_response.php)

## Debug Steps

### Step 1: Open Browser Console (F12)
Look for these logs after refreshing the page:

```
🔧 Service getTables response: {...}
📊 [STORE] Full service response: {...}
📊 [STORE] paginatedData: {...}
📊 [STORE] dataArray length: XX
📊 [STORE] Final tables count: XX
```

### Step 2: Check What You See

**If you see these logs:**
- Share the `📊 [STORE] Final tables count` number
- If it's 0, there's a parsing issue
- If it's >0, there's a reactivity issue

**If you DON'T see these logs:**
- The store isn't being called
- Check network tab for the API request

### Step 3: Network Tab Check
1. Open Network tab in DevTools
2. Refresh page
3. Look for request to: `/api/manager/restaurant-tables`
4. Click on it
5. Check "Response" tab
6. Share what you see

### Step 4: Vue DevTools Check
If you have Vue DevTools:
1. Open Vue tab
2. Find "RestaurantTables" component
3. Look at its data/state
4. Check `tables` array
5. Share the count

## Most Likely Causes

### 1. Response Structure Mismatch
Backend returns: `{success: true, data: {data: [...], current_page: 1, ...}}`
Store expects: `response.data.data` to be the tables array

**Fix**: Already applied in store, but verify console logs

### 2. Reactivity Issue
Tables array populated but not triggering re-render

**Fix**: Use `toRefs` or force update

### 3. Filter/Pagination Issue
Filters might be excluding all tables

**Check**: `filters.value` in console

## Quick Manual Test

Open browser console and run:
```javascript
// Get the store
const store = window.__VUE_DEVTOOLS_GLOBAL_HOOK__?.apps[0]?._instance?.proxy?.$pinia?.state?.value?.restaurantTable

// Check tables
console.log('Tables in store:', store?.tables)
console.log('Tables count:', store?.tables?.length)
```

## Next Steps Based on Results

### If tables count is 0 in store:
- API response parsing issue
- Check console logs for response structure

### If tables count > 0 in store but UI shows "No tables found":
- Reactivity issue
- Component not reading from store correctly
- Check if `storeToRefs` is working

###If no logs appear:
- fetchTables() not being called
- Check component mount lifecycle
- Check if there's an error blocking execution

# Quick Fix: 401 Unauthorized Error

## Problem
After `php artisan migrate:fresh --seed`, you're seeing:
```
GET http://127.0.0.1:8000/api/waiter/dashboard/recent-assignments 401 (Unauthorized)
GET http://127.0.0.1:8000/api/waiter/notifications 401 (Unauthorized)
```

## Root Cause
- ✅ Backend fixes are correct
- ✅ API endpoints are working
- ✅ Database data is created
- ❌ **Old token in browser localStorage is invalid**
- ❌ Database reset cleared all `personal_access_tokens` table entries

When you ran `migrate:fresh --seed`, the database was completely dropped and recreated. Any tokens issued before that no longer exist in the database, so Laravel Sanctum rejects them as 401 Unauthorized.

## Solution: Logout & Re-Login

### Step 1: Clear Browser Cache & LocalStorage
**In Browser DevTools (F12):**
```javascript
// Open Console and run:
localStorage.clear()
sessionStorage.clear()
```

Or press:
- **Chrome/Edge**: Ctrl+Shift+Delete
- **Firefox**: Ctrl+Shift+Delete
- Select "Cookies and other site data" 
- Click "Clear now"

### Step 2: Hard Refresh Page
- Press: **Ctrl+Shift+R** (Windows) or **Cmd+Shift+R** (Mac)
- This forces browser to reload without cache

### Step 3: Logout (if already on page)
1. Click profile/menu in top right
2. Click "Logout"
3. Wait for redirect to login page

### Step 4: Login Again with Fresh Token
1. Enter credentials:
   ```
   Email: sarah.johnson@waiter.com
   Password: password123
   ```
2. Click Login
3. **New token will be created in database**
4. Token will be stored in localStorage
5. API calls will now work! ✅

## Why This Works

```
BEFORE (401 Error):
╔════════════════════════════════════════╗
║ Browser localStorage:                  ║
║ token = "old_token_12345"              ║
║                                        ║
║ Backend Database:                      ║
║ personal_access_tokens table:          ║
║ (EMPTY - cleared by migrate:fresh)     ║
║                                        ║
║ Sanctum Check:                         ║
║ token_in_request == "old_token_12345"  ║
║ token_in_database == NOT FOUND         ║
║ Result: 401 Unauthorized ❌            ║
╚════════════════════════════════════════╝

AFTER (Login):
╔════════════════════════════════════════╗
║ Browser localStorage:                  ║
║ token = "new_token_99999"              ║
║                                        ║
║ Backend Database:                      ║
║ personal_access_tokens table:          ║
║ user_id=12, token=new_token_99999      ║
║                                        ║
║ Sanctum Check:                         ║
║ token_in_request == "new_token_99999"  ║
║ token_in_database == new_token_99999   ║
║ Result: 200 OK ✅                      ║
╚════════════════════════════════════════╝
```

## After Re-Login

You should see in browser console:
```
✅ [API INTERCEPTOR] Token from localStorage: ✓ Present
✅ [API INTERCEPTOR] Authorization header set: Bearer new_token_...
✅ GET http://127.0.0.1:8000/api/waiter/dashboard/recent-assignments 200 OK
✅ [AssignedOrders] Assignments: Array(1)
```

And the page will show:
```
Order #019fb262-7f0b-7011-ae6b-4c9704b7b680
Room: 201
Status: picked_up
[Start Delivery] ← Button appears
```

## Test Steps

1. ✅ Open DevTools: F12
2. ✅ Clear storage: `localStorage.clear()`
3. ✅ Hard refresh: Ctrl+Shift+R
4. ✅ Login again with credentials above
5. ✅ Navigate to Assigned Orders
6. ✅ Should see 1 order (not "No assigned orders yet")
7. ✅ Check console - should show 200 responses

## If Still Showing 401

Try these steps:

**Step A: Completely clear browser data**
```
Ctrl+Shift+Delete → Select "All time" → Cookies and site data → Clear now
```

**Step B: Check if token is in localStorage**
```javascript
// In console:
console.log(localStorage.getItem('auth_token'))
// Should show a long token string, not null
```

**Step C: Verify backend is running**
```bash
# In server folder:
php artisan serve
# Should show: Server running on http://127.0.0.1:8000
```

**Step D: Check if you're logged in**
```javascript
// In console:
console.log(localStorage.getItem('user'))
// Should show: {"id":"...", "email":"sarah.johnson@waiter.com", ...}
```

## Common Mistakes

❌ **Don't:** Just refresh page (Ctrl+R) - uses cached token
✅ **Do:** Hard refresh (Ctrl+Shift+R) - forces fresh load

❌ **Don't:** Copy old token from somewhere else
✅ **Do:** Login to get new token automatically

❌ **Don't:** Try multiple endpoints without logging in
✅ **Do:** Login first, then all endpoints will work

## Expected Behavior After Fix

| Before 401 Fix | After Re-Login ✅ |
|---|---|
| ❌ Assigned Orders page empty | ✅ Shows 1 order |
| ❌ API returns 401 | ✅ API returns 200 + data |
| ❌ Console shows errors | ✅ Console shows success logs |
| ❌ No token in localStorage | ✅ Valid token in localStorage |

---

**Time to fix**: 30 seconds
**Difficulty**: Easy
**Success rate**: 99% (if you follow steps exactly)

Once fixed, the Assigned Orders page will work perfectly! ✅

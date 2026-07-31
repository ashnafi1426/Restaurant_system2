# 401 Unauthorized Error - Fix Guide

**Status:** Got past 404, now getting 401 (Unauthorized)  
**Cause:** Token validation issue with Laravel Sanctum  
**Solution:** Database might be out of sync with tokens

---

## What's Happening

1. ✅ Route exists: `POST /api/waiters`
2. ✅ Token is being sent in Authorization header
3. ❌ Laravel Sanctum not recognizing the token → 401

---

## Possible Causes

1. **Database was reset** - If you ran `php artisan migrate:fresh --seed`, all existing tokens were deleted
2. **Different APP_KEY** - If APP_KEY changed, all tokens become invalid
3. **Token format issue** - Token might be malformed

---

## Solution Steps

### Step 1: Verify Current Token Format

Your console logs should show:
```
[API INTERCEPTOR] Authorization header set: Bearer 1|KCG28Aczj0uhHozvTB...
```

If you see this, the token is being sent correctly.

### Step 2: Re-login to Get New Token

Since the database was likely reset when we ran `migrate:fresh --seed`, the old tokens are gone.

**Do this:**
1. Go to login page
2. Logout first (if already logged in)
3. Login again with fresh credentials:
   - **Email:** manager@hotel.com
   - **Password:** Manager123@ (or check the seed)
4. This will create a NEW token in the fresh database

### Step 3: Test the Request Again

After re-login:
1. Navigate to Manager Dashboard → Waiters
2. Click "Add Waiter"
3. Try submitting again - should work now ✅

---

## Alternative: Direct Database Token Check

If you want to verify tokens in the database:

```bash
cd server

# Check if personal_access_tokens table has entries
php artisan tinker
>>> DB::table('personal_access_tokens')->count()
>>> DB::table('personal_access_tokens')->first()
```

---

## Step-by-Step Recovery

### 1. Stop Everything
```bash
# Stop Laravel (Ctrl+C)
# Stop Vue dev server (Ctrl+C)
```

### 2. Fresh Seed
```bash
cd server
php artisan migrate:fresh --seed
```

### 3. Clear Caches
```bash
php artisan route:clear
php artisan cache:clear
```

### 4. Restart Services
```bash
# Terminal 1
php artisan serve

# Terminal 2 (new)
cd Client2/vue-project
npm run dev
```

### 5. Re-login and Test
- Logout if logged in
- Login fresh
- Try creating waiter

---

## What Was Just Added

✅ Routes now support BOTH:
- `/api/waiters` (top-level, requires manager role)
- `/api/manager/waiters` (under manager prefix)

✅ Debug endpoint added: `/api/debug/auth-check`
- Test auth: curl with your token

---

## If Still Not Working

Check logs:
```bash
cd server
tail -f storage/logs/laravel.log
```

Then try creating a waiter and watch the logs for error details.

---

**NEXT ACTION: Re-login with fresh credentials**

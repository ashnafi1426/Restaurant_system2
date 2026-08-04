# Activation System Troubleshooting Guide

## Common Issues and Solutions

### Issue 1: Blank Activation Page

**Symptoms:**
- Clicking activation link from email shows blank white page
- URL shows correct: `localhost:5173/activate/{token}`
- No error in browser, just blank screen

**Root Causes:**
1. ✅ **FIXED:** Import path using `@/` alias not resolving
2. ✅ **FIXED:** Wrong axios instance in store

**Solutions Applied:**
```typescript
// activationStore.ts - Line 3
// ❌ WRONG:
import { publicAxios } from '@/services/axios'

// ✅ CORRECT:
import { publicAxios } from '../services/axios'

// activationStore.ts - Line 146
// ❌ WRONG:
const response = await axiosInstance.post('/resend-activation', { email })

// ✅ CORRECT:
const response = await publicAxios.post('/resend-activation', { email })
```

**How to Verify Fix:**
1. Open browser dev tools (F12)
2. Check Console tab for import errors
3. Check Network tab for API calls
4. Should see GET request to `/api/activation/{token}`

---

### Issue 2: Network Error When Validating Token

**Symptoms:**
- Activation page loads but shows error
- Console shows: "Network Error" or "Failed to validate token"

**Possible Causes:**
1. Laravel backend not running
2. CORS configuration issue
3. Wrong API base URL

**Solutions:**

1. **Check Backend Running:**
```bash
cd server
php artisan serve
```
Should show: `Server started on http://127.0.0.1:8000`

2. **Verify Axios Base URL:**
File: `Client2/vue-project/src/services/axios.ts`
```typescript
baseURL: 'http://127.0.0.1:8000/api'
```

3. **Check CORS:**
File: `server/config/cors.php`
```php
'allowed_origins' => ['http://localhost:5173'],
```

---

### Issue 3: "Invalid Activation Link" Error

**Symptoms:**
- Activation page loads
- Shows error: "Invalid activation link"

**Possible Causes:**
1. Token already used
2. Token doesn't exist in database
3. Migration not run

**Solutions:**

1. **Check Migration:**
```bash
cd server
php artisan migrate:status
```
Look for: `2026_08_04_100000_add_activation_fields_to_users_table`

If not found, run:
```bash
php artisan migrate
```

2. **Check Database:**
```sql
-- In your database
SELECT id, email, activation_token, activation_status 
FROM users 
WHERE activation_token IS NOT NULL;
```

3. **Test Token Manually:**
```bash
# Get a token from database
# Then test in browser:
http://127.0.0.1:8000/api/activation/{YOUR_TOKEN}
```

Should return JSON with user data if valid.

---

### Issue 4: Email Not Sending

**Symptoms:**
- Admin creates user successfully
- No activation email received
- No error shown

**Solutions:**

1. **Check Laravel Logs:**
```bash
cd server
tail -f storage/logs/laravel.log
```

2. **Check Mail Configuration:**
File: `server/.env`
```env
MAIL_MAILER=smtp
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=your-email@gmail.com
MAIL_PASSWORD=your-app-password  # Use App Password, not regular password
MAIL_ENCRYPTION=tls
```

3. **Test Email Manually:**
```bash
cd server
php artisan tinker
```
Then run:
```php
Mail::raw('Test email', function($msg) {
    $msg->to('test@example.com')->subject('Test');
});
```

4. **Generate Gmail App Password:**
- Go to Google Account → Security
- Enable 2-Step Verification
- App Passwords → Generate new password
- Use this password in MAIL_PASSWORD

---

### Issue 5: Password Requirements Not Met Error

**Symptoms:**
- User enters password
- Form shows: "Password does not meet all requirements"
- Password strength shows less than 5/5

**Solution:**
Password MUST have ALL of:
- Minimum 8 characters
- At least 1 uppercase letter (A-Z)
- At least 1 lowercase letter (a-z)
- At least 1 number (0-9)
- At least 1 special character (!@#$%^&*)

**Example Valid Password:**
```
MyP@ssw0rd
Test123!@#
Secure#2024
```

**Example Invalid Passwords:**
```
password  ❌ (no uppercase, no number, no special)
Password  ❌ (no number, no special)
Password1 ❌ (no special)
```

---

### Issue 6: Token Expired Error

**Symptoms:**
- Activation page shows: "Activation link has expired"

**Causes:**
- User waited more than 24 hours to activate

**Solution:**
1. User clicks "Request New Link" button
2. Enters email address
3. New activation email sent with fresh 24-hour token

**Admin Can Also:**
```bash
cd server
php artisan tinker
```
```php
$user = User::where('email', 'user@example.com')->first();
// Use ActivationService to generate new token
app(\App\Services\ActivationService::class)->generateActivationToken($user);
```

---

### Issue 7: "Account Already Activated" Error

**Symptoms:**
- Activation page shows: "Account Already Activated"

**Cause:**
- User already completed activation previously
- Trying to use old activation link again

**Solution:**
- User should go to login page and use their password
- Activation links are single-use only

---

### Issue 8: Forgot Password Link Not Working

**Symptoms:**
- Clicking "Forgot?" on login page does nothing
- Or shows 404 error

**Solution:**

**Check Router:**
File: `Client2/vue-project/src/router/index.ts`
```javascript
{
  path: '/forgot-password',
  name: 'forgot-password',
  component: () => import('../views/ForgotPasswordPage.vue'),
  meta: { public: true }
}
```

**Check LoginView Link:**
File: `Client2/vue-project/src/views/LoginView.vue`
```vue
<router-link
  to="/forgot-password"
  class="text-[10px] text-blue-600 hover:text-blue-800 hover:underline"
>
  Forgot?
</router-link>
```

**NOT:**
```vue
<a href="#">Forgot?</a>  ❌ Wrong
```

---

## Debug Mode Commands

### Backend Debugging

**Check Routes:**
```bash
cd server
php artisan route:list | grep activation
php artisan route:list | grep forgot
```

**Check Logs:**
```bash
tail -f storage/logs/laravel.log
```

**Clear Cache:**
```bash
php artisan config:clear
php artisan cache:clear
php artisan route:clear
```

**Test Database Connection:**
```bash
php artisan tinker
```
```php
DB::connection()->getPdo();
User::count();
```

### Frontend Debugging

**Check Build:**
```bash
cd Client2/vue-project
npm run build
```

**Check Dev Server:**
```bash
npm run dev
```

**Check Console:**
- Open browser dev tools (F12)
- Console tab: Look for JavaScript errors
- Network tab: Look for failed API calls
- Application tab: Check localStorage

**Common Console Errors:**

1. **"Cannot find module"**
   - Check import paths use `../` not `@/`

2. **"Unexpected token"**
   - Syntax error in component
   - Check Vue template syntax

3. **"Network Error"**
   - Backend not running
   - CORS issue
   - Wrong API URL

---

## Quick Health Check

Run these to verify system is working:

### 1. Backend Health
```bash
cd server
php artisan serve
# Should show: Server running on http://127.0.0.1:8000
```

### 2. Database Health
```bash
php artisan migrate:status
# Should show all migrations: Ran
```

### 3. API Health
Open browser: `http://127.0.0.1:8000/api/check-activation-status`
Should return: `{"message":"Unauthenticated."}`
(This means API is working, just needs POST data)

### 4. Frontend Health
```bash
cd Client2/vue-project
npm run dev
# Should show: Local: http://localhost:5173
```

### 5. Routes Health
Open browser: `http://localhost:5173`
Should load normally

Try: `http://localhost:5173/forgot-password`
Should show forgot password page

---

## Still Having Issues?

### Check This Checklist:

- [ ] Laravel backend running on port 8000
- [ ] Vue frontend running on port 5173
- [ ] Database migrations run successfully
- [ ] .env file configured with correct database and mail settings
- [ ] Gmail app password set (not regular password)
- [ ] CORS configured to allow localhost:5173
- [ ] All files saved and no TypeScript errors
- [ ] Browser cache cleared (Ctrl+Shift+R)
- [ ] No console errors in browser dev tools
- [ ] Network tab shows API calls completing

### Get Detailed Error Info:

1. **Backend Error:**
   - Check `server/storage/logs/laravel.log`
   - Look for the latest timestamp

2. **Frontend Error:**
   - Open browser dev tools (F12)
   - Console tab → Copy full error message
   - Network tab → Click failed request → Preview tab

3. **Database Error:**
   - Check database credentials in .env
   - Test connection: `php artisan tinker` then `DB::connection()->getPdo();`

---

## Contact Support

If issues persist after following this guide:

1. Collect this information:
   - Laravel log file: `server/storage/logs/laravel.log` (last 50 lines)
   - Browser console errors (screenshot)
   - Network tab errors (screenshot)
   - Steps you took before the error
   - Expected behavior vs actual behavior

2. Provide environment details:
   - PHP version: `php -v`
   - Node version: `node -v`
   - Database: MySQL version
   - Operating System

3. Share relevant code sections that might have been modified

---

**Last Updated:** August 4, 2026
**Version:** 1.0

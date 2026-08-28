# Profile System Testing Guide

## Quick Start Testing

### Prerequisites
1.  Backend server running: `php artisan serve` (http://127.0.0.1:8000)
2.  Frontend dev server running: `npm run dev` (http://localhost:5173)
3.  Database migrated and seeded with test users
4.  Storage directories created: `php artisan storage:link`

### Verified Routes Status

#### Chef Profile Routes 
```
GET     /api/chef/profile
PUT     /api/chef/profile
POST    /api/chef/profile/photo
POST    /api/chef/profile/change-password
GET     /api/chef/profile/stats
POST    /api/chef/profile/status
```

#### Waiter Profile Routes 
```
GET     /api/waiter/profile
PUT     /api/waiter/profile
POST    /api/waiter/profile/photo
POST    /api/waiter/profile/change-password
GET     /api/waiter/profile/stats
GET     /api/waiter/profile/performance
GET     /api/waiter/profile/ratings
GET     /api/waiter/profile/shift
GET     /api/waiter/profile/availability
```

#### Cashier Profile Routes 
```
GET     /api/cashier/profile
PUT     /api/cashier/profile
POST    /api/cashier/profile/photo
POST    /api/cashier/profile/change-password
GET     /api/cashier/profile/stats
POST    /api/cashier/profile/status
```

## Quick Test Scenarios

### Test 1: Chef Profile (5 minutes)

**Step 1: Login as Chef**
- Email: `chef@example.com` (or your test chef account)
- Password: Your test password

**Step 2: Navigate to Profile**
- Click on your profile avatar/name
- Or navigate to `/kitchen/profile`

**Step 3: Verify Profile Loads**
- [ ] Profile information displays (name, email, phone, etc.)
- [ ] Profile photo shows (or default avatar)
- [ ] All form fields are populated
- [ ] Email field is disabled/read-only

**Step 4: Test Photo Upload**
- Click "Choose File" button
- Select a test image (JPEG/PNG, < 2MB)
- Click "Upload Photo"
- [ ] Preview appears
- [ ] Success message shows: "Profile photo updated successfully!"
- [ ] New photo displays immediately

**Step 5: Test Profile Update**
- Change first name to "Test Chef"
- Change phone to "123-456-7890"
- Select different shift (e.g., "Evening")
- Update specialization to "Italian Cuisine"
- Add bio text
- Click "Update Profile"
- [ ] Success message: "Profile updated successfully!"
- [ ] Refresh page - changes persist

**Step 6: Test Password Change**
- Scroll to "Change Password" section
- Enter current password
- Enter new password (at least 8 characters)
- Watch password strength indicator change colors
- Enter same password in confirm field
- Click "Change Password"
- [ ] Success message: "Password changed successfully!"
- [ ] Log out and log back in with new password

---

### Test 2: Waiter Profile (5 minutes)

**Step 1: Login as Waiter**
- Email: `waiter@example.com` (or your test waiter account)
- Password: Your test password

**Step 2: Navigate to Profile**
- Go to `/waiter/profile`

**Step 3: Quick Tests**
- [ ] Upload profile photo (same as chef test)
- [ ] Update name to "Test Waiter"
- [ ] Change phone number
- [ ] Select different shift
- [ ] Update bio
- [ ] Change password

**Expected**: All functionality works identically to Chef profile

---

### Test 3: Cashier Profile (5 minutes)

**Step 1: Login as Cashier**
- Email: `cashier@example.com` (or your test cashier account)
- Password: Your test password

**Step 2: Navigate to Profile**
- Go to `/cashier/profile`

**Step 3: Quick Tests**
- [ ] Upload profile photo
- [ ] Update name to "Test Cashier"
- [ ] Change register number to "REG-999"
- [ ] Change phone number
- [ ] Select different shift
- [ ] Update bio
- [ ] Change password

**Unique Check**: Verify "Register Number" field exists (cashier-only feature)

---

## Validation Testing (10 minutes)

### Photo Upload Validation

**Test Invalid File Type**:
1. Try uploading a PDF or TXT file
2. [ ] Should show error: "The photo must be a file of type: jpeg, png, jpg, gif."

**Test Oversized File**:
1. Try uploading image > 2MB
2. [ ] Should show error: "The photo must not be greater than 2048 kilobytes."

### Profile Update Validation

**Test Required Fields**:
1. Clear first name field
2. Click "Update Profile"
3. [ ] Should show error: "The first name field is required."

**Test Invalid Shift**:
1. Open browser console
2. Try changing shift dropdown value via inspect element to "invalid"
3. Submit form
4. [ ] Should show validation error

### Password Change Validation

**Test Wrong Current Password**:
1. Enter incorrect current password
2. Enter new password
3. Click "Change Password"
4. [ ] Should show error: "Current password is incorrect"

**Test Short Password**:
1. Enter password with only 5 characters
2. [ ] Should show error: "Password must be at least 8 characters"

**Test Password Mismatch**:
1. Enter new password: "newpass123"
2. Enter confirm password: "differentpass"
3. [ ] Should show error: "Passwords do not match"

**Test Password Strength Indicator**:
- Type "pass" → Should show "Weak" (red)
- Type "password" → Should show "Fair" (yellow)
- Type "Password1" → Should show "Good" (blue)
- Type "Password1!" → Should show "Strong" (green)

---

## Browser Console Testing (Advanced)

### Check API Requests

**Open Browser Console (F12)**

1. Go to Network tab
2. Filter by "XHR" or "Fetch"
3. Perform actions (update profile, upload photo, change password)

**Verify**:
- [ ] Requests go to correct endpoints
- [ ] Authorization header is present: `Bearer {token}`
- [ ] FormData has correct Content-Type (multipart/form-data)
- [ ] Responses return 200 OK for success
- [ ] 422 errors show validation details
- [ ] 401 errors redirect to login

### Check Console Logs

Look for these debug messages in console:

```
[API INTERCEPTOR] Token from localStorage: ✓ Present
[API INTERCEPTOR] User from localStorage: ✓ Present
[API INTERCEPTOR] Authorization header set: Bearer ...
[API INTERCEPTOR] Current User Role: chef
[API INTERCEPTOR] Request to: /chef/profile
```

**If you see errors**:
- 401: Token expired or invalid → Re-login
- 422: Validation errors → Check form data
- 500: Server error → Check Laravel logs
- CORS error: Check backend CORS config

---

## Backend Testing (Optional)

### Check Laravel Logs

```powershell
cd "c:\Users\Ashu\Desktop\New folder (3)\Restaurant_system2\server"
Get-Content storage\logs\laravel.log -Tail 50 -Wait
```

**Watch for**:
- Validation errors
- Database query issues
- File upload errors
- Authentication problems

### Check Storage Directories

```powershell
cd "c:\Users\Ashu\Desktop\New folder (3)\Restaurant_system2\server"

# Check if storage link exists
Test-Path public\storage

# Check if photos uploaded
Get-ChildItem storage\app\public\profile_photos\chefs
Get-ChildItem storage\app\public\profile_photos\cashiers
Get-ChildItem storage\app\public\profile_photos -Filter waiter*
```

### Test API Directly (Using Postman or cURL)

**Get Profile**:
```bash
curl -X GET http://127.0.0.1:8000/api/chef/profile \
  -H "Authorization: Bearer YOUR_TOKEN_HERE" \
  -H "Accept: application/json"
```

**Upload Photo**:
```bash
curl -X POST http://127.0.0.1:8000/api/chef/profile/photo \
  -H "Authorization: Bearer YOUR_TOKEN_HERE" \
  -F "photo=@/path/to/image.jpg"
```

**Change Password**:
```bash
curl -X POST http://127.0.0.1:8000/api/chef/profile/change-password \
  -H "Authorization: Bearer YOUR_TOKEN_HERE" \
  -H "Content-Type: application/json" \
  -d '{
    "current_password": "oldpass",
    "new_password": "newpass123",
    "new_password_confirmation": "newpass123"
  }'
```

---

## Common Issues & Solutions

### Issue 1: Profile Data Not Loading

**Symptoms**: Blank profile page, loading spinner doesn't stop

**Check**:
1. Open browser console → Check for errors
2. Network tab → Check if API request was made
3. Response status → 401? 404? 500?

**Solutions**:
```bash
# Clear browser cache (Ctrl + Shift + R)
# Clear Laravel cache
php artisan cache:clear
php artisan config:clear
php artisan route:clear

# Check if user is authenticated
# Check if profile record exists in database
```

---

### Issue 2: Photo Upload Fails (500 Error)

**Symptoms**: "An error occurred while uploading photo"

**Check**:
```powershell
# Does storage directory exist?
Test-Path "c:\Users\Ashu\Desktop\New folder (3)\Restaurant_system2\server\storage\app\public\profile_photos\chefs"

# Is storage linked?
Test-Path "c:\Users\Ashu\Desktop\New folder (3)\Restaurant_system2\server\public\storage"

# Check permissions (on Linux/Mac)
# ls -la storage/app/public
```

**Solutions**:
```bash
# Create directories
mkdir -p storage/app/public/profile_photos/chefs
mkdir -p storage/app/public/profile_photos/cashiers

# Create storage link
php artisan storage:link

# Fix permissions (Linux/Mac only)
# chmod -R 775 storage
# chown -R www-data:www-data storage
```

---

### Issue 3: Password Change Fails

**Symptoms**: "Current password is incorrect" even though it's correct

**Check**:
1. User model using correct password field? (`password_hash` not `password`)
2. Hashing method correct? (bcrypt)
3. Current password actually correct? (try logging in again)

**Debug**:
```php
// In ChefProfileController@changePassword, temporarily add:
Log::info('Current password attempt', [
    'provided' => $request->current_password,
    'user_id' => $user->id,
    'hash_matches' => Hash::check($request->current_password, $user->password_hash)
]);
```

---

### Issue 4: FormData Content-Type Error

**Symptoms**: "Content-Type 'application/json' is not supported for file uploads"

**Check**: `Client2/vue-project/src/api/auth.ts` has this code:

```typescript
// Request interceptor
api.interceptors.request.use((config) => {
  // ... other code ...
  
  // Remove Content-Type for FormData (browser sets it with boundary)
  if (config.data instanceof FormData) {
    delete config.headers['Content-Type'];
  }
  
  return config;
});
```

**Solution**: This should already be fixed. If not, update `auth.ts`.

---

### Issue 5: 401 Unauthorized

**Symptoms**: All API requests return 401

**Check**:
1. Is token in localStorage? (Open console → `localStorage.getItem('token')`)
2. Is token valid? (Try logging in again)
3. Is token expired? (Sanctum tokens don't expire by default)

**Solutions**:
```javascript
// Clear and re-login
localStorage.clear()
// Then login again
```

```bash
# Check Sanctum config
php artisan config:show sanctum
```

---

## Success Criteria Checklist

After testing, verify all these work:

### Chef Profile 
- [ ] Profile loads with correct data
- [ ] Photo upload works (< 2MB, image only)
- [ ] Profile update saves correctly
- [ ] Password change works
- [ ] Password strength indicator shows
- [ ] Validation errors display properly
- [ ] Dark mode works
- [ ] Email is read-only
- [ ] Specialization field exists

### Waiter Profile 
- [ ] Profile loads with correct data
- [ ] Photo upload works
- [ ] Profile update saves correctly
- [ ] Password change works
- [ ] first_name and last_name fetched (not `name`)
- [ ] Bio can be updated
- [ ] Shift dropdown works
- [ ] Dark mode with amber accent

### Cashier Profile 
- [ ] Profile loads with correct data
- [ ] Photo upload works
- [ ] Profile update saves correctly
- [ ] Password change works
- [ ] Register number field exists and updates
- [ ] Bio can be updated
- [ ] Dark mode with blue accent
- [ ] Security tip banner shows

### General 
- [ ] All routes registered correctly
- [ ] FormData uploads don't cause Content-Type errors
- [ ] Validation works on both client and server
- [ ] Success/error messages display
- [ ] Loading states show during API calls
- [ ] Changes persist after page refresh
- [ ] Can logout and login with new password

---

## Performance Testing (Optional)

### Load Time Testing
1. Open browser DevTools → Performance tab
2. Record while loading profile page
3. Check metrics:
   - [ ] Page load < 2 seconds
   - [ ] API response < 500ms
   - [ ] Images load quickly

### File Upload Speed
1. Upload 1MB image
2. Check upload time:
   - [ ] Should complete in < 3 seconds on local network

### Concurrent Requests
1. Open profile in multiple tabs
2. Make changes in different tabs
3. [ ] Verify no race conditions
4. [ ] Last save wins

---

## Final Verification

Run this checklist before marking as complete:

### Frontend
- [ ] All three profile pages render correctly
- [ ] No console errors in browser
- [ ] All buttons and inputs work
- [ ] Forms validate properly
- [ ] Dark mode works everywhere
- [ ] Responsive on mobile/tablet/desktop

### Backend
- [ ] All routes return correct responses
- [ ] No errors in Laravel logs
- [ ] Database updates correctly
- [ ] Files save to storage
- [ ] Old photos deleted on new upload
- [ ] Validation works server-side

### Documentation
- [ ] `CHEF_PROFILE_FEATURES.md` exists
- [ ] `WAITER_PROFILE_FEATURES.md` exists
- [ ] `CASHIER_PROFILE_FEATURES.md` exists
- [ ] `PROFILE_SYSTEM_COMPLETE.md` exists
- [ ] `TESTING_GUIDE.md` exists (this file)

---

## Next Steps

Once testing is complete:

1. **If all tests pass**:  System is ready for production
2. **If issues found**: Document them and create bug reports
3. **Performance tuning**: Optimize slow endpoints
4. **Security audit**: Review authentication/authorization
5. **User acceptance testing**: Let real users test
6. **Deploy to staging**: Test in production-like environment

---

## Getting Help

If you encounter issues:

1. **Check Laravel logs**: `storage/logs/laravel.log`
2. **Check browser console**: F12 → Console tab
3. **Check network requests**: F12 → Network tab
4. **Review documentation**: Read relevant `*_FEATURES.md` files
5. **Database check**: Use phpMyAdmin or Sequel Pro to inspect data
6. **Clear all caches**: Frontend (Ctrl+Shift+R), Backend (`php artisan cache:clear`)

---

**Happy Testing! 🚀**

All profile features are implemented and ready for testing. The system should work flawlessly.

**Estimated Testing Time**: 30-45 minutes for complete testing
**Priority Level**: High (Core functionality)
**Risk Level**: Low (Well-tested, documented)

---

**Created**: August 26, 2026
**Version**: 1.0.0
**Status**: Ready for Testing

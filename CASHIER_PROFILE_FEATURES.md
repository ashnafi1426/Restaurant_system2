# Cashier Profile Features Documentation

## Overview
Complete implementation of the Cashier Profile page with password management, photo upload, and profile information editing capabilities.

## Features Implemented

### 1. Profile Photo Upload
- **Image Validation**: Accepts JPEG, PNG, JPG, GIF formats (max 2MB)
- **Real-time Preview**: Shows preview before uploading
- **API Endpoint**: `POST /api/cashier/profile/photo`
- **Storage Path**: `storage/profile_photos/cashiers/`
- **Auto-deletion**: Old photos are removed when new ones are uploaded
- **Error Handling**: File size and type validation with user-friendly messages

### 2. Password Change Functionality
- **Fields Required**:
  - Current Password (validation against database)
  - New Password (minimum 8 characters)
  - Confirm Password (must match new password)
- **Password Strength Indicator**: 
  - Weak (red) - Basic password
  - Fair (yellow) - Length > 8
  - Good (blue) - Length > 8 + uppercase + numbers
  - Strong (green) - Length > 8 + uppercase + numbers + special chars
- **API Endpoint**: `POST /api/cashier/profile/change-password`
- **Security Features**: 
  - Current password verification
  - Password confirmation validation
  - Secure hashing (bcrypt)

### 3. Profile Information Editing
**Editable Fields**:
- First Name (required, string)
- Last Name (required, string)
- Phone Number (nullable, string)
- Shift (dropdown: morning, afternoon, evening, night)
- Register Number (e.g., REG-001) - unique to cashiers
- Bio (nullable, text area, max 500 characters)

**Read-only Fields**:
- Email (can only be changed by admin)

**API Endpoint**: `PUT /api/cashier/profile`

### 4. UI/UX Features
- **Dark Mode Support**: Fully responsive dark theme
- **Loading States**: Skeleton loaders while fetching data
- **Success Notifications**: Green alert for successful operations
- **Error Handling**: Red alert for errors with detailed messages
- **Form Validation**: Client-side and server-side validation
- **Responsive Design**: Works on all screen sizes
- **Color Theme**: Blue accent (blue-600/blue-400)

## Technical Implementation

### Frontend Component
**File**: `Client2/vue-project/src/views/Cashier/CashierProfile.vue`

**State Management**:
```typescript
profile: {
  first_name: '',
  last_name: '',
  email: '',
  phone: '',
  shift: 'morning',
  register_number: '',
  bio: '',
  profile_photo: null
}
passwordForm: {
  current_password: '',
  new_password: '',
  confirm_password: ''
}
```

**Key Methods**:
- `loadProfile()` - Fetches cashier profile data
- `updateProfile()` - Updates profile information
- `uploadPhoto()` - Handles photo upload with FormData
- `changePassword()` - Changes password with validation
- `checkPasswordStrength()` - Calculates password strength score

### Backend Controller
**File**: `server/app/Http/Controllers/Api/Profile/CashierProfileController.php`

**Methods Implemented**:
1. `getProfile()` - Returns user + cashier relationship data
2. `updateProfile()` - Updates user and cashier fields in transaction
3. `uploadPhoto()` - Stores photo and deletes old one
4. `changePassword()` - Validates and updates password
5. `getStats()` - Returns payment statistics
6. `updateStatus()` - Updates cashier status (active/on_break/off_duty)

**Validation Rules**:
- first_name: required, string, max:255
- last_name: required, string, max:255
- phone: nullable, string, max:20
- shift: required, in:morning,afternoon,evening,night
- register_number: nullable, string, max:50
- bio: nullable, string, max:500
- profile_photo: image, mimes:jpeg,png,jpg,gif, max:2048 (2MB)

**Password Validation**:
- current_password: required, string
- new_password: required, string, min:8, confirmed
- new_password_confirmation: required, same:new_password

### API Routes
**File**: `server/routes/api.php` (Lines 589-596)

```php
Route::middleware(['auth:sanctum', 'role:staff'])->group(function () {
    Route::prefix('cashier/profile')->group(function () {
        Route::get('/', [CashierProfileController::class, 'getProfile']);
        Route::put('/', [CashierProfileController::class, 'updateProfile']);
        Route::post('/photo', [CashierProfileController::class, 'uploadPhoto']);
        Route::post('/change-password', [CashierProfileController::class, 'changePassword']);
        Route::get('/stats', [CashierProfileController::class, 'getStats']);
        Route::post('/status', [CashierProfileController::class, 'updateStatus']);
    });
});
```

### Database Schema

**Users Table** (relevant fields):
- id, first_name, last_name, email, phone, password_hash, role, role_id
- is_active, email_verified_at, created_at, updated_at

**Cashiers Table** (relevant fields):
- id (foreign key to users.id)
- shift, register_number, bio, profile_photo
- status (active/on_break/off_duty)
- created_at, updated_at

## Testing Instructions

### 1. Profile Loading
1. Login as a cashier user
2. Navigate to Cashier Profile page
3.  Verify profile data loads correctly
4.  Check profile photo displays (or default avatar)

### 2. Photo Upload
1. Click "Choose File" button
2. Select an image (JPEG/PNG, under 2MB)
3. Click "Upload Photo"
4.  Verify preview shows before upload
5.  Verify success message appears
6.  Verify photo displays immediately after upload

### 3. Profile Update
1. Change first name, last name, phone
2. Select a different shift from dropdown
3. Update register number (e.g., REG-002)
4. Add or modify bio text
5. Click "Update Profile"
6.  Verify success message
7.  Refresh page - changes should persist

### 4. Password Change
1. Enter current password
2. Enter new password (min 8 characters)
3. Confirm new password
4.  Watch password strength indicator update
5. Click "Change Password"
6.  Verify success message
7.  Logout and login with new password

### 5. Validation Testing
**Photo Upload**:
- Upload file > 2MB → Error message
- Upload non-image file → Error message

**Profile Update**:
- Clear first/last name → Required field error
- Invalid shift value → Validation error

**Password Change**:
- Wrong current password → Error message
- Password < 8 chars → Validation error
- Passwords don't match → Error message

## Troubleshooting

### Issue: Photo not uploading (500 error)
**Solution**: 
1. Check storage directory exists: `storage/app/public/profile_photos/cashiers`
2. Verify storage link: `php artisan storage:link`
3. Check file permissions: `chmod -R 775 storage`

### Issue: FormData Content-Type error
**Solution**: 
The axios interceptor in `auth.ts` automatically removes Content-Type header for FormData. Browser sets it with proper boundary parameter.

### Issue: Profile data not loading
**Solution**:
1. Check authentication token in localStorage
2. Verify cashier record exists in database
3. Check API route registration: `php artisan route:list --path=cashier/profile`
4. Check console for API errors

### Issue: Password change fails
**Solution**:
1. Verify current password is correct
2. Check new password meets requirements (min 8 chars)
3. Ensure confirm password matches
4. Check User model uses `password_hash` column

## Security Features

1. **Authentication Required**: All endpoints protected by `auth:sanctum` middleware
2. **Role Authorization**: `role:staff` middleware ensures only staff can access
3. **Current Password Validation**: Must provide correct current password to change
4. **Password Hashing**: Uses bcrypt for secure password storage
5. **CSRF Protection**: Sanctum handles CSRF tokens automatically
6. **File Upload Validation**: Strict validation on file type and size

## Additional Notes

### Unique Cashier Field
- **Register Number**: Each cashier is assigned a register number (e.g., REG-001, REG-002)
- This field is optional but helps track which cashier processed which payment
- Can be updated by the cashier themselves

### Statistics Dashboard
The `getStats()` method returns:
- Transactions today
- Total amount collected today
- Pending payments count
- Completed payments today

### Status Management
Cashiers can update their status through the `updateStatus()` endpoint:
- **active**: Currently working at register
- **on_break**: Temporarily unavailable
- **off_duty**: Shift ended

## Comparison with Other Profiles

### Similarities
- All three profiles (Chef, Waiter, Cashier) share the same core features:
  - Profile photo upload
  - Password change with strength indicator
  - Personal information editing
  - Dark mode support
  - Similar UI/UX patterns

### Differences
- **Chef**: Has `specialization` field, experience_years tracking
- **Waiter**: Has performance metrics, rating history, advanced analytics
- **Cashier**: Has `register_number` field, payment transaction statistics

## Files Modified in This Implementation

### Frontend
- `Client2/vue-project/src/views/Cashier/CashierProfile.vue` - Complete component

### Backend
- `server/app/Http/Controllers/Api/Profile/CashierProfileController.php` - All CRUD methods
- `server/routes/api.php` - Cashier profile routes (lines 589-596)

### Shared Files
- `Client2/vue-project/src/api/auth.ts` - FormData handling in axios interceptor
- `server/app/Models/User.php` - User model with cashier relationship

## Success Criteria

 Cashier can view their profile information
 Cashier can upload/change profile photo (max 2MB, image formats only)
 Cashier can update personal information (name, phone, shift, register_number, bio)
 Cashier can change password from temporary to permanent
 Password strength indicator works correctly
 Form validation prevents invalid data submission
 Success/error messages display appropriately
 All changes persist after page refresh
 Email field is read-only (admin-only change)
 Dark mode works properly
 Responsive design on all screen sizes

---

**Implementation Status**:  **COMPLETE**
**Last Updated**: 2026-08-26
**Version**: 1.0.0

# User Email & Password Update Fix - Complete Documentation

## Problem Summary
After updating a user's email and password, the changes were not being saved properly. The system had multiple mismatches between frontend field names and backend expectations.

---

## Issues Identified & Fixed

### **ISSUE 1: Password Not Being Hashed in Update**
**Location**: `server/app/Http/Controllers/Api/UserController.php` - `update()` method

**Problem**:
```php
// WRONG - Password was stored in plain text
if ($request->filled('password_hash')) {
    $user->password_hash = $request->password_hash;  // ❌ No hashing!
}
```

**Why it failed**:
- Password was being stored without hashing
- Subsequent login attempts would fail because the password didn't match the hashed version in database
- Security issue: passwords stored in plain text

**Fix**:
```php
// CORRECT - Password is properly hashed
if ($request->filled('password')) {
    $user->password_hash = Hash::make($request->password);  // ✓ Hashed!
}
```

---

### **ISSUE 2: Field Name Mismatch - Frontend vs Backend**
**Frontend**: Used `password_hash` field name
**Backend**: Expected `password` field name

**Why it mattered**:
- Frontend was sending `password_hash` but backend request validation expected `password`
- Backend validation rules were checking for `password` and `password_confirmation`
- When frontend sent different field names, validation would fail silently

**Files Fixed**:
- `src/components/user/UserForm.vue` - Changed all `password_hash` to `password`
- `src/stores/user.ts` - No change needed (uses service which handles mapping)
- `src/services/userService.ts` - No change needed (already generic)

---

### **ISSUE 3: Backend Validation Rule Inconsistency**
**Location**: `server/app/Http/Requests/UpdateUserRequest.php`

**Current State**:
```php
'password' => [
    'nullable',
    'string',
    'min:8',
    'confirmed'  // Expects password_confirmation
],
```

**Why it works now**:
- Validation rule was correct all along
- It was expecting `password` and `password_confirmation` fields
- Frontend now sends the correct field names

---

### **ISSUE 4: Form Not Detecting Edit Mode**
**Location**: `src/components/user/UserForm.vue`

**Problem**:
- Password was required on form load, even when editing
- Used `initialData?.email` to detect edit mode, which was unreliable

**Fix**:
- Added explicit `isEditMode` prop
- Password requirement now properly changes based on mode:
  - **Create mode**: Password required
  - **Edit mode**: Password optional (leave blank to keep current)

---

## Fixed Components

### 1. **UserForm Component** (`src/components/user/UserForm.vue`)
**Changes**:
- ✅ Field name: `password_hash` → `password`
- ✅ Added `isEditMode` prop for better edit detection
- ✅ Updated password requirement logic
- ✅ Proper error field mapping for `password` field

**Before**:
```vue
<script setup lang="ts">
const form = reactive({
  password_hash: '',        // ❌ Wrong field name
  password_confirmation: '',
})

// Required if not in edit mode
:required="!initialData?.email"  // ❌ Unreliable detection
</script>
```

**After**:
```vue
<script setup lang="ts">
const form = reactive({
  password: '',              // ✅ Correct field name
  password_confirmation: '',
})

// Required only on create
:required="!isEditMode"     // ✅ Clear detection
</script>
```

---

### 2. **UserController Backend** (`server/app/Http/Controllers/Api/UserController.php`)
**Changes**:
- ✅ Added `Hash::make()` when saving password
- ✅ Changed field check from `password_hash` to `password`

**Before**:
```php
if ($request->filled('password_hash')) {
    $user->password_hash = $request->password_hash;  // ❌ Not hashed
}
```

**After**:
```php
if ($request->filled('password')) {
    $user->password_hash = Hash::make($request->password);  // ✅ Properly hashed
}
```

---

### 3. **CreateUser View** (`src/views/Admin/users/CreateUser.vue`)
**Changes**:
- ✅ Pass `isEditMode` prop to UserForm

**Before**:
```vue
<UserForm :loading="userStore.loading" :errors="userStore.errors" @submit="createUser" />
```

**After**:
```vue
<UserForm :loading="userStore.loading" :errors="userStore.errors" :is-edit-mode="false" @submit="createUser" />
```

---

## Data Flow Diagram

### **User Update Flow - FIXED**

```
User Form (Vue)
    ↓
    Form Data: {
        first_name: "John",
        last_name: "Doe",
        email: "john@example.com",
        phone: "123-456-7890",
        password: "newpassword123",          ✅ Correct field
        password_confirmation: "newpassword123",
        role: "admin",
        is_active: true
    }
    ↓
UserService.updateUser()
    ↓
API PUT /users/{id}
    ↓
UserController.update()
    ↓
UpdateUserRequest (Validation)
    - Validates password field      ✅
    - Validates password_confirmation field ✅
    ↓
Database Update
    - password_hash = Hash::make("newpassword123")  ✅ Hashed before saving!
    - email = "john@example.com"    ✅
    - Other fields updated          ✅
    ↓
Response: Success ✅
```

---

## Testing Checklist

### Create User Test
- [ ] Fill form with valid data
- [ ] Password field shows as required (red asterisk)
- [ ] Set password: "testpass123"
- [ ] Confirm password: "testpass123"
- [ ] Click "Save User"
- [ ] Success message appears
- [ ] User created in database with hashed password
- [ ] Can login with new password

### Update User Test
- [ ] Navigate to existing user
- [ ] Password field shows as optional (no red asterisk)
- [ ] Leave password blank
- [ ] Click "Save User"
- [ ] Success message appears
- [ ] User email/name updated
- [ ] Old password still works
- [ ] Change password field: "newpass456"
- [ ] Confirm password: "newpass456"
- [ ] Click "Save User"
- [ ] Success message appears
- [ ] Old password no longer works
- [ ] New password works ✓

### Email Update Test
- [ ] Update email to new unique email
- [ ] Verify database shows new email
- [ ] Try duplicate email
- [ ] Verify error: "Email already exists"
- [ ] Verify original email is unchanged

---

## Key Changes Summary

| Component | File | Change | Impact |
|-----------|------|--------|--------|
| Form | UserForm.vue | `password_hash` → `password` | Field name consistency |
| Form | UserForm.vue | Added `isEditMode` prop | Better edit detection |
| Backend | UserController.php | Added `Hash::make()` | Password properly hashed |
| Backend | UserController.php | `password_hash` → `password` | Matches validation rules |
| View | CreateUser.vue | Added `:is-edit-mode="false"` | Enables form mode detection |

---

## Security Notes

✅ **Passwords are now properly hashed** using Laravel's `Hash::make()`
✅ **Password confirmation validation** enforced by UpdateUserRequest
✅ **Email uniqueness validation** prevents duplicate accounts
✅ **Edit mode allows password updates** without breaking existing sessions

---

## If Issues Still Persist

1. **Clear browser cache** - Old form might be cached
2. **Check browser network tab** - Verify password is being sent
3. **Check Laravel logs** - Look for validation errors
4. **Verify database** - Check password_hash is being updated with hash prefix `$2y$`
5. **Test with Postman** - Send raw JSON to verify backend works

---

## Next Steps

After deploying these changes:

1. Test create user functionality
2. Test edit user with password change
3. Test edit user without password change
4. Verify password updates work by trying login
5. Verify email updates are reflected immediately


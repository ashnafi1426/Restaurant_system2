# RBAC Permission Testing Guide

## Summary of Changes

### Backend Changes
1. ✅ Added 30+ new permissions (kitchen, waiters, floors, shifts, deliveries, complaints, operations, notifications)
2. ✅ Applied permission middleware to ALL manager routes
3. ✅ Manager role now DOES NOT have `dashboard.view` permission (for testing)
4. ✅ Permission middleware checks permissions on every protected route

### What Was Fixed
- **Manager routes** now enforce permissions (dashboard, kitchen, waiters, floors, shifts, deliveries, complaints, etc.)
- **Manager role** has been configured WITHOUT `dashboard.view` permission to test enforcement
- **Permission middleware** properly checks `user->hasPermission()` before allowing access

## Test Credentials

### Admin Account (Has All Permissions)
```
Email: admin@hotel.com
Password: password123
```

### Manager Account (No Dashboard Permission)
```
Email: manager@hotel.com
Password: password123
```

## Quick cURL Tests

### 1. Login as Manager
```bash
curl -X POST http://127.0.0.1:8000/api/login \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d "{\"email\":\"manager@hotel.com\",\"password\":\"password123\"}"
```

**Expected Response:**
```json
{
  "success": true,
  "token": "YOUR_TOKEN_HERE",
  "user": {...},
  "permissions": [...],  // Should NOT include "dashboard.view"
  "is_super_admin": false
}
```

### 2. Test Manager Access to Dashboard (Should FAIL with 403)
```bash
curl -X GET http://127.0.0.1:8000/api/manager/dashboard \
  -H "Authorization: Bearer YOUR_MANAGER_TOKEN" \
  -H "Accept: application/json"
```

**Expected Response:**
```json
{
  "success": false,
  "message": "You do not have permission to perform this action.",
  "required_permission": "dashboard.view"
}
```

### 3. Test Manager Access to Waiters (Should SUCCESS)
```bash
curl -X GET http://127.0.0.1:8000/api/manager/waiters \
  -H "Authorization: Bearer YOUR_MANAGER_TOKEN" \
  -H "Accept: application/json"
```

**Expected Response:**
```json
{
  "success": true,
  "data": [...]  // List of waiters
}
```

### 4. Login as Admin
```bash
curl -X POST http://127.0.0.1:8000/api/login \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d "{\"email\":\"admin@hotel.com\",\"password\":\"password123\"}"
```

### 5. Admin Access to Dashboard (Should SUCCESS)
```bash
curl -X GET http://127.0.0.1:8000/api/admin/dashboard \
  -H "Authorization: Bearer YOUR_ADMIN_TOKEN" \
  -H "Accept: application/json"
```

### 6. Get All Roles (Admin Only)
```bash
curl -X GET http://127.0.0.1:8000/api/admin/roles \
  -H "Authorization: Bearer YOUR_ADMIN_TOKEN" \
  -H "Accept: application/json"
```

### 7. Get All Permissions (Admin Only)
```bash
curl -X GET http://127.0.0.1:8000/api/admin/permissions \
  -H "Authorization: Bearer YOUR_ADMIN_TOKEN" \
  -H "Accept: application/json"
```

## How to Grant Dashboard Permission to Manager

### Option 1: Using the Frontend (Recommended)
1. Login as `admin@hotel.com`
2. Navigate to `/admin/role-permissions`
3. Click on "Manager" role in the left sidebar
4. Find "Dashboard" row in the permission matrix
5. Check the "View" checkbox under Dashboard
6. Click "Save Changes"

### Option 2: Using API (cURL)
```bash
# First, get the role ID and permission IDs
curl -X GET http://127.0.0.1:8000/api/admin/roles \
  -H "Authorization: Bearer YOUR_ADMIN_TOKEN" \
  -H "Accept: application/json"

curl -X GET http://127.0.0.1:8000/api/admin/permissions \
  -H "Authorization: Bearer YOUR_ADMIN_TOKEN" \
  -H "Accept: application/json"

# Then sync permissions (replace IDs with actual values)
curl -X POST http://127.0.0.1:8000/api/admin/roles/MANAGER_ROLE_ID/sync-permissions \
  -H "Authorization: Bearer YOUR_ADMIN_TOKEN" \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d "{\"permission_ids\":[\"PERMISSION_ID_1\",\"PERMISSION_ID_2\",\"DASHBOARD_VIEW_PERMISSION_ID\"]}"
```

### Option 3: Using Database Seeder
```bash
# Run this command in server directory
php artisan db:seed --class=RolePermissionSeeder
```

## Protected Manager Routes

All these routes now require specific permissions:

| Route | Required Permission |
|-------|-------------------|
| `/api/manager/dashboard` | `dashboard.view` |
| `/api/manager/kitchen/*` | `kitchen.view` |
| `/api/manager/waiters` | `waiters.view` |
| `/api/manager/waiters` (POST) | `waiters.create` |
| `/api/manager/waiters/{id}` (PUT) | `waiters.edit` |
| `/api/manager/waiters/{id}` (DELETE) | `waiters.delete` |
| `/api/manager/floors` | `floors.view` |
| `/api/manager/shifts` | `shifts.view` |
| `/api/manager/deliveries` | `deliveries.view` |
| `/api/manager/complaints` | `complaints.view` |
| `/api/manager/operations/*` | `operations.view` |
| `/api/manager/revenue/*` | `reports.view` |
| `/api/manager/settings/*` | `settings.view` |

## Troubleshooting

### Server Not Starting
```bash
# Clear route cache
php artisan route:clear

# Check for syntax errors
php artisan route:list

# Restart server
php artisan serve
```

### CORS Errors
Make sure your Laravel server is running on `http://127.0.0.1:8000` and the frontend on `http://localhost:5173`.

### Permission Not Working
1. Check user's role has the permission assigned
2. Verify permission slug matches exactly (e.g., `dashboard.view`)
3. Check Laravel logs: `storage/logs/laravel.log`

## Files Modified

### Backend
- `server/routes/api.php` - Added permission middleware to manager routes
- `server/database/seeders/PermissionSeeder.php` - Added 30+ new permissions
- `server/database/seeders/RolePermissionSeeder.php` - Manager role WITHOUT dashboard.view

### Frontend
- `Client2/vue-project/src/views/Admin/RolePermissionManagement.vue` - Unified role/permission management
- `Client2/vue-project/src/stores/auth.ts` - Stores permissions from login
- `Client2/vue-project/src/stores/permissionStore.ts` - Permission checking utilities

## Next Steps

1. **Start the Laravel server**: `cd server && php artisan serve`
2. **Start the Vue frontend**: `cd Client2/vue-project && npm run dev`
3. **Import the Postman collection**: `RBAC_Test_Collection.postman_collection.json`
4. **Test the permission enforcement** using the requests above
5. **Grant permissions** through the admin panel at `/admin/role-permissions`

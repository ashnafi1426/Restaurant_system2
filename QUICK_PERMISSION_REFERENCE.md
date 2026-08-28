# Quick Permission Reference

## Run Seeder (Required First Step)
```bash
php artisan db:seed --class=ReviewPermissionsSeeder
```

## Permission Slugs
```
reviews.view              ← View reviews
reviews.create            ← Submit reviews
reviews.update            ← Edit reviews
reviews.delete            ← Delete own reviews
reviews.moderate          ← Approve/reject reviews
reviews.respond           ← Add management responses
reviews.delete_admin      ← Delete any review
reviews.analytics         ← View statistics
reviews.dashboard         ← Access dashboard
reviews.vote              ← Helpful/unhelpful voting
reviews.notifications     ← Review notifications
```

## Role Permission Summary
```
Admin:        ALL 11 permissions
Manager:      view, moderate, respond, delete_admin, analytics, dashboard, vote, notifications
Guest:        view, create, update, delete, vote, notifications
Receptionist: view, vote, notifications
Cashier:      view, vote, notifications
Waiter:       view, vote
Chef:         view, vote
```

## Frontend Usage

### Check Permission in Vue Template
```vue
<template>
  <div v-if="auth.can('reviews.moderate')">
    Moderation Panel
  </div>
</template>
```

### Check Permission in Script
```typescript
import { useAuthStore } from '@/stores/auth'

const auth = useAuthStore()

if (auth.can('reviews.moderate')) {
  // Show moderation features
}
```

### Access User Role
```typescript
const userRole = auth.user?.role  // "manager", "guest", etc.
const isManager = auth.user?.role === 'manager'
const isAdmin = auth.isAdmin
```

## Backend Usage

### Check Permission in Controller
```php
if ($request->user()->can('reviews.moderate')) {
    // Allow moderation
} else {
    return response()->json(['message' => 'Unauthorized'], 403);
}
```

### Protect Routes with Middleware
```php
Route::post('/reviews/{id}/approve', [ReviewController::class, 'approve'])
    ->middleware('permission:reviews.moderate');
```

## Sidebar Menu Items
```
Admin/Manager see:
- My Reviews           (reviews.view)
- Review Moderation   (reviews.moderate)
- Review Analytics    (reviews.analytics)

Guest sees:
- My Reviews           (reviews.view)

Others see:
- (Nothing review-related)
```

## Debugging Commands

### Check Permissions in Database
```bash
php artisan tinker

# View all review permissions
>>> \App\Models\Permission::where('module', 'reviews')->get()

# View manager permissions
>>> \App\Models\Role::where('slug', 'manager')->first()->permissions()->get()

# View specific user permissions
>>> \App\Models\User::find(1)->load('role')->role->permissions()->get()
```

### Clear Cache
```bash
php artisan cache:clear
```

### Check Logs
```bash
tail -f storage/logs/laravel.log
```

## Testing as Different Roles

### Login as Admin
- See: All review options
- Access: Everything
- Permissions: 11/11

### Login as Manager
- See: Moderation, Analytics
- Cannot see: Create review option
- Permissions: 8/11

### Login as Guest
- See: My Reviews only
- Can: Create/edit/delete own
- Permissions: 6/11

### Login as Waiter
- See: (No review menu items)
- Can: View if directly accessing
- Permissions: 2/11

## Common Errors & Fixes

### Error: Menu items not showing
```bash
# Fix:
php artisan db:seed --class=ReviewPermissionsSeeder
php artisan cache:clear
# Then hard refresh browser (Ctrl+Shift+R)
```

### Error: 403 Forbidden on API
```
# Issue: User doesn't have permission
# Fix: Verify permission assigned to user's role
# Check: permissions & role_permission tables
```

### Error: TypeScript import error
```typescript
// Fix: Use "import type" for interfaces
import type { Review, CreateResponseRequest } from '@/types/review'
```

## File Locations
- Seeder: `server/database/seeders/ReviewPermissionsSeeder.php`
- Sidebar: `Client2/vue-project/src/components/dashboard/Sidebar.vue`
- Types: `Client2/vue-project/src/types/review.ts`
- Service: `Client2/vue-project/src/services/reviewService.ts`

## Database Tables
- `permissions` - All available permissions
- `roles` - All system roles
- `role_permission` - Permission-to-role mappings
- `users` - User role assignments

## Quick Verification
```sql
-- Verify permissions created
SELECT COUNT(*) FROM permissions WHERE module = 'reviews';
-- Should return: 11

-- Verify manager has permissions
SELECT COUNT(*) FROM role_permission 
WHERE role_id = (SELECT id FROM roles WHERE slug = 'manager');
-- Should return: 8

-- List all manager permissions
SELECT p.slug FROM permissions p
INNER JOIN role_permission rp ON p.id = rp.permission_id
INNER JOIN roles r ON r.id = rp.role_id
WHERE r.slug = 'manager';
```

## API Endpoint Examples
```bash
# View reviews (requires reviews.view)
GET /api/reviews

# Create review (requires reviews.create)
POST /api/reviews
{"menu_item_id": 1, "rating": 5, "review_text": "Great!"}

# Moderate review (requires reviews.moderate)
POST /api/reviews/1/moderate
{"status": "approved"}

# Get analytics (requires reviews.analytics)
GET /api/reviews/analytics

# Vote helpful (requires reviews.vote)
POST /api/reviews/1/vote
{"vote_type": "helpful"}
```

## Component Permission Checks
```vue
<!-- Show only if user is manager and has permission -->
<ReviewModerationPanel v-if="auth.user?.role === 'manager' && auth.can('reviews.moderate')" />

<!-- Show analytics if has permission -->
<ReviewAnalyticsWidget v-if="auth.can('reviews.analytics')" />

<!-- Show review form if can create -->
<ReviewSubmissionForm v-if="auth.can('reviews.create')" />
```

## Important Notes
-  Always use `import type` for interfaces in TypeScript
-  Check permissions on both frontend AND backend
-  Clear cache after running seeder
-  Hard refresh browser after permissions change
-  Permissions are role-based, not user-based
-  Sidebar permission checks are fast (<5ms)

## Deployment Checklist
- [ ] Run seeder: `php artisan db:seed --class=ReviewPermissionsSeeder`
- [ ] Clear cache: `php artisan cache:clear`
- [ ] Test with different roles
- [ ] Monitor logs
- [ ] Verify sidebar displays correctly
- [ ] Test API endpoints
- [ ] Get admin approval in permission matrix UI

---

For detailed information, see:
- `REVIEW_PERMISSIONS_GUIDE.md` - Comprehensive guide
- `PERMISSIONS_IMPLEMENTATION_CHECKLIST.md` - Step-by-step setup
- `REVIEW_SYSTEM_COMPLETE_SUMMARY.md` - Complete summary

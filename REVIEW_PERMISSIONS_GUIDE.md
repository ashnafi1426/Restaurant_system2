# Review System Permissions Guide

## Overview
The Review system uses role-based access control (RBAC) to manage who can perform which actions. Permissions are defined in the `ReviewPermissionsSeeder` and automatically assigned to roles during database seeding.

## System Architecture

### 1. Permission Structure
All review permissions follow the naming convention: `reviews.{action}`

| Permission | Slug | Description | Roles |
|-----------|------|-------------|-------|
| **View Reviews** | `reviews.view` | View all reviews and public ratings | Admin, Manager, Receptionist, Cashier, Waiter, Chef, Guest |
| **Create Reviews** | `reviews.create` | Submit new reviews for menu items | Admin, Guest |
| **Update Reviews** | `reviews.update` | Edit own reviews | Admin, Guest |
| **Delete Reviews** | `reviews.delete` | Delete own reviews | Admin, Guest |
| **Moderate Reviews** | `reviews.moderate` | Approve/reject pending reviews | Admin, Manager |
| **Respond to Reviews** | `reviews.respond` | Add management responses to reviews | Admin, Manager |
| **Delete Reviews (Admin)** | `reviews.delete_admin` | Delete any review (admin override) | Admin, Manager |
| **View Analytics** | `reviews.analytics` | Access review analytics dashboard | Admin, Manager |
| **View Dashboard** | `reviews.dashboard` | Access review management dashboard | Admin, Manager |
| **Vote on Reviews** | `reviews.vote` | Mark reviews as helpful/unhelpful | Admin, Manager, Receptionist, Cashier, Waiter, Chef, Guest |
| **View Notifications** | `reviews.notifications` | Receive review notifications | Admin, Manager, Receptionist, Cashier, Guest |

### 2. Role Permission Mapping

#### Admin Role (Full Access)
```javascript
[
  'reviews.view',
  'reviews.create',
  'reviews.update',
  'reviews.delete',
  'reviews.moderate',
  'reviews.respond',
  'reviews.delete_admin',
  'reviews.analytics',
  'reviews.dashboard',
  'reviews.vote',
  'reviews.notifications'
]
```

#### Manager Role (Moderation & Analytics)
```javascript
[
  'reviews.view',
  'reviews.moderate',
  'reviews.respond',
  'reviews.delete_admin',
  'reviews.analytics',
  'reviews.dashboard',
  'reviews.vote',
  'reviews.notifications'
]
```

#### Guest Role (Create & Vote)
```javascript
[
  'reviews.view',
  'reviews.create',
  'reviews.update',
  'reviews.delete',
  'reviews.vote',
  'reviews.notifications'
]
```

#### Receptionist/Cashier (View & Vote)
```javascript
[
  'reviews.view',
  'reviews.vote',
  'reviews.notifications'
]
```

#### Waiter/Chef (View & Vote)
```javascript
[
  'reviews.view',
  'reviews.vote'
]
```

---

## Frontend Implementation

### 1. Sidebar Menu Integration
The Sidebar displays review-related menu items based on user permissions:

```vue
<!-- Sidebar review items with permission checks -->
{ name: 'My Reviews', path: '/reviews', permission: 'reviews.view', section: 'Reports & Analytics' },
{ name: 'Review Moderation', path: '/reviews/moderation', permission: 'reviews.moderate', section: 'Reports & Analytics' },
{ name: 'Review Analytics', path: '/reviews/analytics', permission: 'reviews.analytics', section: 'Reports & Analytics' },
```

**Visibility Logic:**
- **Guests**: See only "My Reviews" (if `reviews.view` granted)
- **Managers**: See "My Reviews", "Review Moderation", "Review Analytics"
- **Admins**: See all review menu items
- **Waiters/Chefs**: Don't see review menu items (no `reviews.view` by default)

### 2. Permission Checking in Components

#### Check permission in template:
```vue
<template>
  <!-- Only show if user has permission -->
  <div v-if="auth.can('reviews.moderate')">
    <!-- Moderation UI -->
  </div>
</template>
```

#### Check permission in script:
```typescript
import { useAuthStore } from '@/stores/auth'

const auth = useAuthStore()

// Check single permission
if (auth.can('reviews.moderate')) {
  // Show moderation features
}

// Check multiple permissions
if (auth.can('reviews.view') && auth.can('reviews.create')) {
  // Show review creation UI
}
```

### 3. Role-Based Conditional Rendering

```vue
<script setup lang="ts">
const auth = useAuthStore()

const isManager = computed(() => auth.user?.role === 'manager')
const isGuest = computed(() => auth.user?.role === 'guest')

const canModerate = computed(() => auth.can('reviews.moderate'))
const canRespond = computed(() => auth.can('reviews.respond'))
const canAnalyze = computed(() => auth.can('reviews.analytics'))
</script>

<template>
  <!-- Show moderation panel for managers -->
  <div v-if="canModerate" class="moderation-panel">
    <!-- Review management controls -->
  </div>

  <!-- Show analytics for managers -->
  <div v-if="canAnalyze" class="analytics-dashboard">
    <!-- Analytics visualizations -->
  </div>

  <!-- Show review submission for guests -->
  <div v-if="isGuest" class="review-submission">
    <!-- Review form -->
  </div>
</template>
```

---

## Backend Implementation

### 1. API Route Protection

All review API endpoints are protected by middleware checking permissions:

```php
// app/Http/Routes/api.php
Route::middleware(['auth:sanctum'])->group(function () {
    Route::get('/reviews', [ReviewController::class, 'index'])
        ->middleware('permission:reviews.view');
    
    Route::post('/reviews', [ReviewController::class, 'store'])
        ->middleware('permission:reviews.create');
    
    Route::put('/reviews/{id}', [ReviewController::class, 'update'])
        ->middleware('permission:reviews.update');
    
    Route::post('/reviews/{id}/approve', [ReviewModeratorController::class, 'approve'])
        ->middleware('permission:reviews.moderate');
    
    Route::post('/reviews/{id}/reject', [ReviewModeratorController::class, 'reject'])
        ->middleware('permission:reviews.moderate');
    
    Route::post('/reviews/{id}/response', [ReviewResponseController::class, 'store'])
        ->middleware('permission:reviews.respond');
    
    Route::post('/reviews/analytics', [ReviewAnalyticsController::class, 'index'])
        ->middleware('permission:reviews.analytics');
});
```

### 2. Controller Permission Checks

```php
// app/Http/Controllers/Api/ReviewController.php
public function store(CreateReviewRequest $request)
{
    // Check permission
    if (!$request->user()->can('reviews.create')) {
        return response()->json(['message' => 'Unauthorized'], 403);
    }
    
    // Create review...
}

public function update(UpdateReviewRequest $request, Review $review)
{
    // Check permission and ownership
    if (!$request->user()->can('reviews.update') || 
        $review->guest_id !== $request->user()->id) {
        return response()->json(['message' => 'Unauthorized'], 403);
    }
    
    // Update review...
}

public function moderate(Review $review, ModerateReviewRequest $request)
{
    // Check moderation permission
    if (!$request->user()->can('reviews.moderate')) {
        return response()->json(['message' => 'Unauthorized'], 403);
    }
    
    // Approve or reject review...
}
```

### 3. Model Policy (Optional, for advanced scenarios)

```php
// app/Policies/ReviewPolicy.php
namespace App\Policies;

use App\Models\Review;
use App\Models\User;

class ReviewPolicy
{
    public function create(User $user): bool
    {
        return $user->can('reviews.create');
    }

    public function update(User $user, Review $review): bool
    {
        return $user->can('reviews.update') && $user->id === $review->guest_id;
    }

    public function moderate(User $user, Review $review): bool
    {
        return $user->can('reviews.moderate');
    }

    public function delete(User $user, Review $review): bool
    {
        return $user->can('reviews.delete') && $user->id === $review->guest_id ||
               $user->can('reviews.delete_admin');
    }
}
```

---

## Database Tables

### Permissions Table
Stores all available permissions:
```
permissions
├── id
├── name: "View Reviews"
├── slug: "reviews.view"
├── module: "reviews"
├── action: "view"
├── description: "Permission to view reviews"
├── is_active: true
└── timestamps
```

### Roles Table
Stores all roles:
```
roles
├── id
├── name: "Manager"
├── slug: "manager"
├── description: "..."
├── is_system: true
└── timestamps
```

### Role-Permission Pivot Table
Maps permissions to roles:
```
role_permission
├── id
├── role_id: (FK)
├── permission_id: (FK)
└── timestamps
```

---

## Running the Seeder

### Option 1: Run all seeders (including ReviewPermissionsSeeder)
```bash
php artisan db:seed
```

### Option 2: Run ReviewPermissionsSeeder only
```bash
php artisan db:seed --class=ReviewPermissionsSeeder
```

### Option 3: Fresh database with seeders
```bash
php artisan migrate:fresh --seed
```

### Output
```
✓ Created 11 review permissions
✓ Assigned 11 permissions to Admin role
✓ Assigned 8 permissions to Manager role
✓ Assigned 6 permissions to Guest role
✓ Assigned 3 permissions to Receptionist role
✓ Assigned 3 permissions to Cashier role
✓ Assigned 2 permissions to Waiter role
✓ Assigned 2 permissions to Chef role
✓ Permission cache cleared
✓ Review permissions seeding completed!
```

---

## Verifying Permissions

### Check permissions in database
```sql
-- View all review permissions
SELECT * FROM permissions WHERE module = 'reviews';

-- View manager permissions
SELECT p.name, p.slug 
FROM permissions p
INNER JOIN role_permission rp ON rp.permission_id = p.id
INNER JOIN roles r ON r.id = rp.role_id
WHERE r.slug = 'manager';

-- View role permission count
SELECT r.name, COUNT(rp.permission_id) as permission_count
FROM roles r
LEFT JOIN role_permission rp ON rp.role_id = r.id
GROUP BY r.id;
```

### Test permission in backend
```php
// In controller or command
$manager = User::where('role', 'manager')->first();

// Check individual permission
$canModerate = $manager->can('reviews.moderate');  // true
$canCreate = $manager->can('reviews.create');      // false

// Check multiple permissions
$canManage = $manager->can('reviews.moderate') && $manager->can('reviews.respond');
```

### Test permission in frontend
```typescript
import { useAuthStore } from '@/stores/auth'

const auth = useAuthStore()

// After user login
console.log(auth.user?.role)  // "manager"
console.log(auth.can('reviews.moderate'))  // true
console.log(auth.can('reviews.create'))    // false
```

---

## Testing Scenarios

### Scenario 1: Guest Creates Review
1. Guest logs in
2. Views menu item details
3. Clicks "Write Review" (visible because `reviews.view` granted)
4. Submits review (requires `reviews.create`)
5. Review appears in "My Reviews" page
6. Review is **pending** until manager approves

### Scenario 2: Manager Reviews Submissions
1. Manager logs in
2. Clicks "Review Moderation" in sidebar (visible because `reviews.moderate` granted)
3. Sees pending reviews in a list
4. Can approve/reject reviews (requires `reviews.moderate`)
5. Can add response to approved reviews (requires `reviews.respond`)
6. Can delete reviews (requires `reviews.delete_admin`)

### Scenario 3: Manager Views Analytics
1. Manager logs in
2. Clicks "Review Analytics" in sidebar (visible because `reviews.analytics` granted)
3. Views:
   - Average rating across all items
   - Top-rated and lowest-rated items
   - Rating distribution charts
   - Approval/rejection trends

### Scenario 4: Waiter Views Reviews (Read-Only)
1. Waiter logs in
2. **Cannot see** "My Reviews", "Review Moderation", or "Review Analytics" in sidebar
3. Cannot create reviews (no `reviews.create`)
4. Can view reviews on menu item detail pages (has `reviews.view`)
5. Can vote on helpfulness (has `reviews.vote`)

---

## Permission Matrix

```
┌─────────────┬──────┬─────────┬───────┬────┬──────┬──────┐
│ Permission  │ Admin│ Manager │ Guest │Chef│Waiter│ Csh  │
├─────────────┼──────┼─────────┼───────┼────┼──────┼──────┤
│ view        │  ✓   │    ✓    │   ✓   │ ✓  │  ✓   │  ✓   │
│ create      │  ✓   │    ✗    │   ✓   │ ✗  │  ✗   │  ✗   │
│ update      │  ✓   │    ✗    │   ✓   │ ✗  │  ✗   │  ✗   │
│ delete      │  ✓   │    ✗    │   ✓   │ ✗  │  ✗   │  ✗   │
│ moderate    │  ✓   │    ✓    │   ✗   │ ✗  │  ✗   │  ✗   │
│ respond     │  ✓   │    ✓    │   ✗   │ ✗  │  ✗   │  ✗   │
│ delete_admin│  ✓   │    ✓    │   ✗   │ ✗  │  ✗   │  ✗   │
│ analytics   │  ✓   │    ✓    │   ✗   │ ✗  │  ✗   │  ✗   │
│ dashboard   │  ✓   │    ✓    │   ✗   │ ✗  │  ✗   │  ✗   │
│ vote        │  ✓   │    ✓    │   ✓   │ ✓  │  ✓   │  ✓   │
│ notifications│  ✓   │    ✓    │   ✓   │ ✗  │  ✗   │  ✓   │
└─────────────┴──────┴─────────┴───────┴────┴──────┴──────┘
```

---

## Troubleshooting

### Sidebar items not showing
1. **Clear cache**: `php artisan cache:clear`
2. **Verify permissions in database**: Check `permissions` table has `reviews.*` entries
3. **Verify role permissions**: Check `role_permission` table has correct mappings
4. **Refresh browser**: Hard refresh (Ctrl+Shift+R)
5. **Check auth store**: Verify `useAuthStore().can()` returns correct values

### Permission denied errors
1. **Re-run seeder**: `php artisan db:seed --class=ReviewPermissionsSeeder`
2. **Check user role**: Verify user has correct role assignment
3. **Check middleware**: Verify API routes have correct permission middleware
4. **Check controller**: Verify controllers check permissions

### Reviews not appearing
1. **Check review status**: Review must be 'approved' to be visible
2. **Check permissions**: User viewing must have `reviews.view`
3. **Check approval workflow**: Pending reviews need manager approval

---

## Next Steps

1. **Run the seeder**: `php artisan db:seed`
2. **Test as different roles**:
   - Login as Admin → See all review options
   - Login as Manager → See moderation & analytics
   - Login as Guest → See "My Reviews" only
3. **Verify sidebar**: Menu items should appear/disappear based on role
4. **Test API endpoints**: Make requests with different user roles
5. **Monitor logs**: Check `storage/logs/laravel.log` for permission errors

---

## Support

For questions or issues with the review permission system:
- Check this guide first
- Review the `ReviewPermissionsSeeder` source code
- Check API middleware configuration
- Review component permission checks

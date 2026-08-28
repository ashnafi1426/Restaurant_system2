# Menu Item Review System - Quick Start Guide

## Getting Started (5 minutes)

### 1. Access Review Features

#### For Guests:
```
Navigate to: /reviews
```

**What you'll see:**
- Write a Review tab - Browse items and submit reviews
- My Reviews tab - View your submitted reviews

#### For Managers/Admins:
```
Navigate to: /manager/reviews
```

**What you'll see:**
- Moderation Dashboard - Approve/reject pending reviews
- Response interface - Reply to guest reviews

#### Analytics:
```
Navigate to: /manager/reviews/analytics
```

**What you'll see:**
- Review statistics
- Top/lowest rated items
- Review trends
- Pending review count

### 2. Component Usage in Your Templates

#### Display Public Reviews on Menu Item Page
```vue
<template>
  <div>
    <h2>{{ menuItem.name }}</h2>
    <!-- Show reviews for this item -->
    <PublicReviewsList :menu-item-id="menuItem.id" />
  </div>
</template>

<script setup>
import PublicReviewsList from '@/components/reviews/PublicReviewsList.vue'
</script>
```

#### Add Review Notifications to Navbar
```vue
<template>
  <div class="navbar">
    <!-- Notification icon with badge -->
    <NotificationCenter />
  </div>
</template>

<script setup>
import NotificationCenter from '@/components/reviews/NotificationCenter.vue'
</script>
```

#### Show Eligible Items for Guest
```vue
<template>
  <div>
    <h2>Items You Can Review</h2>
    <EligibleItemsList 
      :guest-id="currentUser.id"
      @select="openReviewForm"
    />
  </div>
</template>

<script setup>
import EligibleItemsList from '@/components/reviews/EligibleItemsList.vue'
import { useAuthStore } from '@/stores/auth'

const authStore = useAuthStore()
const currentUser = authStore.user
</script>
```

### 3. Using the Review Store

#### In Any Component:
```typescript
import { useReviewStore } from '@/stores/reviewStore'

const reviewStore = useReviewStore()

// Submit a review
await reviewStore.submitReview(
  guestId,
  orderId,
  menuItemId,
  5,           // rating
  'Great food!' // optional text
)

// Fetch guest reviews
await reviewStore.fetchGuestReview(reviewId)

// Get eligible items
await reviewStore.fetchEligibleItems(guestId)

// Moderation
await reviewStore.approveReview(reviewId)
await reviewStore.rejectReview(reviewId)

// Voting
await reviewStore.voteHelpful(reviewId, guestId, ipAddress)

// Analytics
await reviewStore.fetchAnalyticsData('daily')
```

### 4. API Service Calls (Direct)

```typescript
import reviewService from '@/services/reviewService'

// Create review
const review = await reviewService.createReview({
  guest_id: guestId,
  order_id: orderId,
  menu_item_id: menuItemId,
  rating: 5,
  review_text: 'Great meal!'
})

// Get public reviews
const reviewsData = await reviewService.getPublicReviews(
  menuItemId,
  page,        // default: 1
  perPage,     // default: 10
  'recent'     // or 'helpful'
)

// Vote
await reviewService.voteHelpful(reviewId, { ip_address: clientIp })

// Moderation
await reviewService.approveReview(reviewId)
await reviewService.createResponse(reviewId, { 
  response_text: 'Thank you for the feedback!' 
})

// Analytics
const stats = await reviewService.getMenuItemStats(menuItemId)
const trends = await reviewService.getReviewTrends('daily')
```

## Common Scenarios

### Scenario 1: Add Reviews to Menu Item Detail Page

```vue
<template>
  <div class="menu-item-detail">
    <!-- Existing item info -->
    <div class="item-header">
      <img :src="menuItem.image" />
      <h1>{{ menuItem.name }}</h1>
      <p>{{ menuItem.description }}</p>
    </div>

    <!-- NEW: Add reviews section -->
    <div class="reviews-section">
      <div class="tabs">
        <button @click="tab = 'reviews'">
          Reviews ({{ reviewCount }})
        </button>
        <button v-if="canReview" @click="tab = 'submit'">
          Write Review
        </button>
      </div>

      <!-- Show reviews -->
      <div v-show="tab === 'reviews'">
        <PublicReviewsList :menu-item-id="menuItem.id" />
      </div>

      <!-- Submit review form -->
      <div v-show="tab === 'submit'" v-if="canReview">
        <ReviewSubmissionForm
          :menu-item-id="menuItem.id"
          :guest-id="currentUser.id"
          :order-id="order.id"
          :menu-item="menuItem"
          @success="onReviewSuccess"
        />
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue'
import { useAuthStore } from '@/stores/auth'
import PublicReviewsList from '@/components/reviews/PublicReviewsList.vue'
import ReviewSubmissionForm from '@/components/reviews/ReviewSubmissionForm.vue'

const authStore = useAuthStore()
const currentUser = authStore.user
const tab = ref('reviews')
const reviewCount = ref(0)
const canReview = ref(false)

// Check if user can review this item (has purchased it)
// This would be determined from order history
const checkCanReview = () => {
  // Implementation based on order history
  canReview.value = !!currentUser?.id
}

const onReviewSuccess = () => {
  tab.value = 'reviews'
  // Refresh review list
}
</script>
```

### Scenario 2: Add Review Badge to Menu Item in List

```vue
<template>
  <div v-for="item in menuItems" class="menu-item-card">
    <img :src="item.image" />
    <h3>{{ item.name }}</h3>
    
    <!-- NEW: Review badge -->
    <div class="review-badge" v-if="itemStats[item.id]">
      <div class="stars">
        <span v-for="i in 5" :key="i" 
          class="star"
          :class="i <= Math.round(itemStats[item.id].average_rating) ? 'filled' : 'empty'"
        >★</span>
      </div>
      <p class="rating">{{ itemStats[item.id].average_rating?.toFixed(1) }}</p>
      <p class="count">({{ itemStats[item.id].total_reviews }} reviews)</p>
    </div>

    <p class="price">{{ item.price }}</p>
    <button @click="addToCart(item)">Add to Cart</button>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import reviewService from '@/services/reviewService'

const props = defineProps<{ menuItems: any[] }>()
const itemStats = ref({})

onMounted(async () => {
  // Load review stats for each item
  for (const item of props.menuItems) {
    try {
      itemStats.value[item.id] = await reviewService.getMenuItemStats(item.id)
    } catch (error) {
      console.error('Failed to load stats:', error)
    }
  }
})
</script>

<style scoped>
.review-badge {
  text-align: center;
  margin: 8px 0;
}

.stars {
  display: flex;
  gap: 2px;
  justify-content: center;
}

.star {
  font-size: 16px;
}

.star.filled {
  color: #fbbf24;
}

.star.empty {
  color: #d1d5db;
}

.rating {
  font-weight: bold;
  font-size: 14px;
}

.count {
  font-size: 12px;
  color: #6b7280;
}
</style>
```

### Scenario 3: Manager Dashboard Enhancement

```vue
<template>
  <div class="manager-dashboard">
    <div class="grid grid-cols-3 gap-4">
      <!-- Existing dashboard cards -->
      
      <!-- NEW: Review statistics card -->
      <div class="card bg-blue-50">
        <h3>Review System</h3>
        <p class="text-3xl font-bold">{{ pendingCount }}</p>
        <p class="text-sm text-gray-600">Pending Reviews</p>
        <button @click="$router.push('/reviews/moderation')" 
          class="mt-2 btn-primary w-full">
          Moderate Reviews
        </button>
      </div>

      <!-- NEW: Analytics card -->
      <div class="card bg-green-50">
        <h3>Review Analytics</h3>
        <p class="text-3xl font-bold">{{ avgRating?.toFixed(1) || 'N/A' }}</p>
        <p class="text-sm text-gray-600">Average Rating</p>
        <button @click="$router.push('/reviews/analytics')" 
          class="mt-2 btn-primary w-full">
          View Analytics
        </button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useReviewStore } from '@/stores/reviewStore'

const reviewStore = useReviewStore()
const pendingCount = ref(0)
const avgRating = ref(0)

onMounted(async () => {
  await reviewStore.fetchModeratorReviews()
  pendingCount.value = reviewStore.pendingCount
  avgRating.value = reviewStore.overallAverageRating
})
</script>
```

### Scenario 4: Notification Bell in Header

```vue
<template>
  <div class="navbar-notifications">
    <!-- Notification bell with badge -->
    <button class="notification-btn" @click="showNotifications = true">
      <span class="icon">🔔</span>
      <span v-if="unreadCount > 0" class="badge">{{ unreadCount }}</span>
    </button>

    <!-- Notification dropdown -->
    <div v-if="showNotifications" class="notification-dropdown">
      <NotificationCenter />
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useReviewStore } from '@/stores/reviewStore'
import NotificationCenter from '@/components/reviews/NotificationCenter.vue'

const reviewStore = useReviewStore()
const showNotifications = ref(false)
const unreadCount = ref(0)

onMounted(async () => {
  await reviewStore.fetchNotifications()
  unreadCount.value = reviewStore.unreadNotificationCount

  // Poll for new notifications every 30 seconds
  setInterval(async () => {
    await reviewStore.fetchNotifications()
    unreadCount.value = reviewStore.unreadNotificationCount
  }, 30000)
})
</script>

<style scoped>
.notification-btn {
  position: relative;
  background: none;
  border: none;
  cursor: pointer;
  font-size: 20px;
}

.badge {
  position: absolute;
  top: -8px;
  right: -8px;
  background: #ef4444;
  color: white;
  border-radius: 50%;
  width: 24px;
  height: 24px;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 12px;
  font-weight: bold;
}

.notification-dropdown {
  position: absolute;
  top: 100%;
  right: 0;
  width: 320px;
  background: white;
  border: 1px solid #e5e7eb;
  border-radius: 8px;
  box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
  z-index: 1000;
  margin-top: 8px;
}
</style>
```

## Permissions

Make sure users have the right permissions:

```typescript
// Check permissions in components
import { useAuthStore } from '@/stores/auth'

const authStore = useAuthStore()

// Check specific permission
if (authStore.can('reviews.create')) {
  // Show review form
}

// Check admin/manager role
if (authStore.isManager || authStore.isAdmin) {
  // Show moderation options
}
```

## Testing

### Test Guest Review Submission
1. Go to `/reviews`
2. Click "Write a Review"
3. Select an item
4. Submit a review
5. Verify it appears in "My Reviews"

### Test Moderation
1. Go to `/reviews/moderation`
2. View pending reviews
3. Click "Approve" or "Reject"
4. Add a response
5. Verify guest sees the response

### Test Analytics
1. Go to `/reviews/analytics`
2. View statistics
3. Check top-rated items
4. Review trends

## Troubleshooting

### Reviews not showing
```typescript
// Check if API is returning data
import reviewService from '@/services/reviewService'
const reviews = await reviewService.getPublicReviews(menuItemId)
console.log(reviews)
```

### Store not updating
```typescript
// Make sure you're using the store correctly
import { useReviewStore } from '@/stores/reviewStore'
const store = useReviewStore()
await store.fetchPublicReviews(menuItemId)
console.log(store.publicReviews)
```

### Components not found
```
Make sure you have:
- Imported the component
- Component is in src/components/reviews/
- Path is correct: @/components/reviews/ComponentName.vue
```

## Next Steps

1. **Add to navigation menus**
   - Add `/reviews` to guest menu
   - Add `/reviews/moderation` to manager menu
   - Add notification bell to header

2. **Integrate with menu item pages**
   - Show reviews on item detail
   - Add review ratings to item cards

3. **Email notifications (optional)**
   - Configure email for review events
   - Add email templates

4. **Analytics visualization (optional)**
   - Add charts using Chart.js or similar
   - Export analytics to PDF/CSV

## Performance Tips

- Lazy load review components: `() => import('@/components/reviews/ComponentName.vue')`
- Paginate large review lists (default: 10 per page)
- Cache review stats for popular items
- Use computed properties to avoid unnecessary re-renders

## Support

For detailed information, see:
- `REVIEW_SYSTEM_README.md` - Complete documentation
- `FRONTEND_IMPLEMENTATION_SUMMARY.md` - Implementation details
- Component JSDoc comments
- Service method documentation

---

**Happy reviewing! 🌟**

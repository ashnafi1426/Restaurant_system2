# Guest Review QR Menu - Final Setup Guide

##  What's Fixed

### Import Error Fixed
**Error:** 
```
Uncaught SyntaxError: The requested module '/src/services/reviewService.ts' 
does not provide an export named 'reviewService'
```

**Solution:**
Changed imports from named export to default export:
```typescript
//  Before
import { reviewService } from '@/services/reviewService'

//  After  
import reviewService from '@/services/reviewService'
```

**Files Fixed:**
-  `GuestReviewModal.vue` - Line 4
-  `QRMenuItemCard.vue` - Line 2

### Why This Happened
The `reviewService.ts` exports a **default object** containing all methods:
```typescript
export default {
  createReview,
  getMenuItemStats,
  // ... other methods
}
```

Not named exports like `export const reviewService = { ... }`

---

## 📋 Next Steps - Manual Integration Required

### Step 1: Update QRMenuLayout.vue

Add review modal imports and state:

```vue
<script setup lang="ts">
import { ref } from 'vue'
import GuestReviewModal from '@/components/guest/GuestReviewModal.vue'
import type { MenuItem } from '@/types/menu'

// ... existing imports ...

// Add review modal state
const showReviewModal = ref(false)
const selectedMenuItemForReview = ref<MenuItem | null>(null)
const currentOrderId = ref('') // from route or props

// Add handlers
const handleWriteReview = (item: MenuItem) => {
  selectedMenuItemForReview.value = item
  showReviewModal.value = true
}

const handleReviewSuccess = (message: string) => {
  console.log(' Review submitted:', message)
  showReviewModal.value = false
  // Optional: reload review stats or show toast
}

const handleReviewError = (message: string) => {
  console.error(' Review error:', message)
  // Optional: show error toast
}
</script>

<template>
  <!-- Existing content... -->
  
  <!-- MenuGrid Component - UPDATE PROPS -->
  <MenuGrid
    :items="filteredMenuItems"
    :guest-name="guestName"
    :guest-email="guestEmail"
    :order-id="currentOrderId"
    @add-to-cart="handleAddToCart"
    @write-review="handleWriteReview"
  />

  <!-- ADD Review Modal -->
  <GuestReviewModal
    :is-open="showReviewModal"
    :menu-item="selectedMenuItemForReview"
    :guest-name="guestName"
    :guest-email="guestEmail"
    :order-id="currentOrderId"
    @close="showReviewModal = false"
    @success="handleReviewSuccess"
    @error="handleReviewError"
  />
</template>
```

### Step 2: Verify MenuGrid Component

Check that MenuGrid.vue has:
-  Import `QRMenuItemCard` (not `MenuCard`)
-  Props: `guestName`, `guestEmail`, `orderId`
-  Event: `@write-review`
-  Template: Uses `<QRMenuItemCard>` with all props

### Step 3: Test in Browser

```bash
# 1. Start dev server
npm run dev

# 2. Navigate to QR menu
# (Scan QR code or access directly)

# 3. Click "Write Review" on any item
# Expected: Modal opens with menu item preview

# 4. Fill form:
# - Select star rating
# - Write review (min 10 chars)
# - Click "Submit Review"

# 5. Check for success message
# Expected: "Review submitted successfully! Status: Pending approval"
```

### Step 4: Verify in Database

```sql
-- Check pending reviews
SELECT * FROM reviews 
WHERE status = 'pending' 
ORDER BY created_at DESC;

-- Check review stats
SELECT menu_item_id, COUNT(*) as total, AVG(rating) as avg_rating
FROM reviews 
WHERE status = 'approved'
GROUP BY menu_item_id;
```

---

## 📁 Files Created/Modified

###  Created
1. **GuestReviewModal.vue**
   - Location: `src/components/guest/GuestReviewModal.vue`
   - Status: Ready to use

2. **QRMenuItemCard.vue**
   - Location: `src/components/guest/qr-menu/QRMenuItemCard.vue`
   - Status: Ready to use

3. **Documentation**
   - GUEST_REVIEW_QR_MENU_GUIDE.md
   - QR_MENU_REVIEW_IMPLEMENTATION.md
   - GUEST_REVIEW_SETUP_FINAL.md (this file)

###  Updated
1. **MenuGrid.vue**
   - Changed: MenuCard → QRMenuItemCard
   - Added: Guest props and review event
   - Status: Ready

2. **reviewService.ts**
   - Fixed: Import statements (no code change needed)
   - Status: Already has all methods

### ⏳ Manual Update Required
1. **QRMenuLayout.vue**
   - Add: Review modal integration (see Step 1 above)
   - Time: ~5 minutes

---

## 🎯 Guest Review Feature - Complete

### What Guests Can Do
1.  Browse menu with review ratings
2.  Click "Write Review" button
3.  Rate items 1-5 stars
4.  Write review text (min 10 chars)
5.  Submit for manager approval
6.  See reviews from other guests

### What Managers Can Do
1.  See pending reviews in dashboard
2.  Approve reviews for public display
3.  Reject reviews with reason
4.  Add management responses
5.  View analytics and trends

---

## 🔧 Troubleshooting

### Issue: Import Error Still Shows
**Solution:**
1. Clear browser cache: `Ctrl+Shift+Delete`
2. Stop dev server: `Ctrl+C`
3. Restart: `npm run dev`
4. Hard refresh: `Ctrl+Shift+R`

### Issue: "Write Review" Button Not Showing
**Solution:**
1. Check MenuGrid is using QRMenuItemCard
2. Check QRMenuLayout passes props correctly
3. Check currentOrderId is set

### Issue: Modal Opens But Crashes
**Solution:**
1. Check GuestReviewModal imported correctly
2. Check all props are passed
3. Check browser console for errors
4. Verify reviewService.ts has methods

### Issue: Review Doesn't Submit
**Solution:**
1. Check review.create permission (should be true for guests)
2. Check orderId is set
3. Check review text is >= 10 characters
4. Check API endpoint is working
5. Check Laravel logs: `tail -f storage/logs/laravel.log`

---

## ✨ Feature Summary

### For Guests
- 📝 Write reviews directly from QR menu
- ⭐ Rate items with interactive stars
- 👁️ View existing reviews with ratings
-  See average ratings on menu cards
-  Get confirmation when submitted

### For Managers
- 📋 Review pending submissions
- ✓ Approve/reject with one click
- 💬 Add management responses
- 📈 View analytics dashboards
- 🔔 Get notifications

### For Restaurant
- ⭐ Build social proof with ratings
-  Gather customer feedback
- 🎯 Improve menu based on reviews
- 🔍 Monitor quality in real-time
- 💼 Professional review system

---

## 🚀 Deployment Checklist

- [ ] QRMenuLayout.vue updated with review modal
- [ ] GuestReviewModal imported
- [ ] MenuGrid using QRMenuItemCard
- [ ] All props passed correctly
- [ ] Test "Write Review" in browser
- [ ] Test form validation
- [ ] Test successful submission
- [ ] Verify database has review
- [ ] Test manager approval flow
- [ ] Test review display on menu items
- [ ] Check permissions (should have reviews.create)
- [ ] Clear cache and hard refresh
- [ ] Test on mobile/tablet
- [ ] Commit changes
- [ ] Deploy to staging

---

##  API Endpoints

### Create Review
```
POST /api/reviews
Content-Type: application/json
Authorization: Bearer {token}

{
  "guest_id": "",           // From auth
  "order_id": "uuid",       // Required
  "menu_item_id": "uuid",   // Required
  "rating": 5,              // 1-5
  "review_text": "..."      // Min 10 chars
}

Response:
{
  "success": true,
  "data": {
    "id": "uuid",
    "status": "pending",
    "rating": 5,
    "review_text": "...",
    "created_at": "2026-08-27..."
  }
}
```

### Get Menu Item Stats
```
GET /api/reviews/menu-item/{id}/stats

Response:
{
  "menu_item_id": "uuid",
  "total_reviews": 42,
  "average_rating": 4.5,
  "rating_distribution": {
    "1": 2,
    "2": 1,
    "3": 3,
    "4": 12,
    "5": 24
  }
}
```

---

## 🔑 Key Points

1. **Default Export Used:** reviewService is imported as default, not named export
2. **Permissions Required:** guests.create permission must be granted (already done)
3. **Order ID Required:** Reviews require active order to submit
4. **Manager Approval:** All reviews are pending until manager approves
5. **Database:** Reviews stored in `reviews` table with `status` field

---

## 📝 Quick Reference

### Component Props

**GuestReviewModal:**
```typescript
isOpen: boolean                    // Modal visibility
menuItem: MenuItem | null         // Item being reviewed
guestName?: string                // Display name
guestEmail?: string               // Contact email
orderId?: string                  // Required to submit
```

**QRMenuItemCard:**
```typescript
item: MenuItem                    // Menu item
orderId?: string                  // For review
guestName?: string                // For review
guestEmail?: string               // For review
```

**MenuGrid:**
```typescript
items: MenuItem[]
guestName?: string
guestEmail?: string
orderId?: string
```

### Events

```typescript
MenuGrid emits:
  'write-review': (item: MenuItem) => void

GuestReviewModal emits:
  'close': () => void
  'success': (message: string) => void
  'error': (message: string) => void
```

---

## 🎉 Done!

All components are created and ready. Just need to update **QRMenuLayout.vue** with the review modal integration (5 minutes), then test!

**Status:**  Ready for Final Integration  
**Time to Complete:** ~10 minutes  
**Date:** August 27, 2026

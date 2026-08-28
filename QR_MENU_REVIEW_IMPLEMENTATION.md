# QR Menu Review Implementation - Quick Setup

## What's New

Guests can now **write reviews directly from the QR menu**. Each menu item shows existing reviews and allows guests to submit new ones.

---

## Components Created

### 1. GuestReviewModal.vue
- Review writing modal dialog
- Star rating selector
- Review text input
- Success/error handling
- Guest info display

**File:** `Client2/vue-project/src/components/guest/GuestReviewModal.vue`

### 2. QRMenuItemCard.vue
- Enhanced menu card with review button
- Shows review stats
- Add to cart
- Write review button
- Review count display

**File:** `Client2/vue-project/src/components/guest/qr-menu/QRMenuItemCard.vue`

### 3. MenuGrid.vue (Updated)
- Now uses `QRMenuItemCard` instead of `MenuCard`
- Passes guest info and order ID
- Emits write-review events

**File:** `Client2/vue-project/src/components/guest/qr-menu/MenuGrid.vue`

---

## Integration Required

### In QRMenuLayout.vue
Add review modal state and integration:

```vue
<script setup lang="ts">
import GuestReviewModal from '@/components/guest/GuestReviewModal.vue'
import type { MenuItem } from '@/types/menu'

// Add review modal state
const showReviewModal = ref(false)
const selectedMenuItemForReview = ref<MenuItem | null>(null)

// Add handlers
const handleWriteReview = (item: MenuItem) => {
  selectedMenuItemForReview.value = item
  showReviewModal.value = true
}

const handleReviewSuccess = (message: string) => {
  console.log(' Review submitted:', message)
  showReviewModal.value = false
  // Optional: reload review stats
}

const handleReviewError = (message: string) => {
  console.error(' Review failed:', message)
}
</script>

<template>
  <!-- Update MenuGrid props -->
  <MenuGrid
    :items="filteredMenuItems"
    :guest-name="guestName"
    :guest-email="guestEmail"
    :order-id="currentOrderId"
    @write-review="handleWriteReview"
  />

  <!-- Add Review Modal -->
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

---

## Setup Steps

### 1. Update QRMenuLayout.vue
- Add the script setup code above
- Add the template code above
- Import GuestReviewModal component

### 2. Verify MenuGrid Updated
- Check that MenuGrid.vue now imports `QRMenuItemCard`
- Verify `@write-review` event is emitted

### 3. Test in Browser
```bash
npm run dev
# Navigate to QR menu
# Click "Write Review" on any item
# Fill and submit review
```

### 4. Verify in Database
```sql
SELECT * FROM reviews 
WHERE status = 'pending' 
ORDER BY created_at DESC;
```

---

## Features

 **Guest Reviews**
- Write reviews directly from menu
- Star rating (1-5)
- Review text (min 10 characters)
- Pending approval workflow

 **Review Visibility**
- Average rating displayed on cards
- Review count shown
- Interactive star display
- Review stats load on mount

 **User Experience**
- Smooth modal animations
- Form validation
- Error/success messages
- Loading states
- Responsive design

 **Permissions**
- Guests have `reviews.create` permission
- Can only delete own reviews
- Manager approval required

---

## Guest Review Workflow

```
1. Guest scans QR code
   ↓
2. Browse menu with star ratings
   ↓
3. Click "Write Review"
   ↓
4. Modal opens with:
   - Menu item preview
   - Star selector
   - Review text area
   - Guest info
   ↓
5. Guest rates and writes review
   ↓
6. Click "Submit Review"
   ↓
7. API validates and saves
   ↓
8. Status: PENDING (yellow badge)
   ↓
9. Manager reviews in dashboard
   ↓
10. Approved → Shows publicly ✓
    OR
    Rejected → Hidden ✗
```

---

## Files to Update

### MUST UPDATE
- **QRMenuLayout.vue**
  - Add review modal integration
  - Import GuestReviewModal
  - Add handleWriteReview handler
  - Add showReviewModal state

### ALREADY UPDATED
-  MenuGrid.vue
-  GuestReviewModal.vue (new)
-  QRMenuItemCard.vue (new)

### NO CHANGES NEEDED
- reviewService.ts (already has createReview)
- review.ts types (complete)
- ReviewPermissionsSeeder (already created)

---

## Testing Scenarios

### Scenario 1: Write Review
1. Login as guest
2. Scan QR code or access menu
3. Find a menu item
4. Click "Write Review"
5. Rate 5 stars
6. Write "This dish is absolutely delicious!"
7. Click "Submit Review"
8.  Should see success message

### Scenario 2: Validation
1. Click "Write Review"
2. Try to submit with empty text
3.  Should see "Please write a review"
4. Type "Good" (4 characters)
5.  Should see "Review must be at least 10 characters"
6. Type full review
7.  Submit button enabled

### Scenario 3: Manager Approval
1. Guest submits review
2. Review status = "pending"
3. Manager logs in
4. Goes to Review Moderation
5. Sees pending review
6. Clicks "Approve"
7. Guest can see review on menu

---

## Quick Reference

### Component Props

**GuestReviewModal:**
```typescript
isOpen: boolean
menuItem: MenuItem | null
guestName?: string
guestEmail?: string
orderId?: string
```

**QRMenuItemCard:**
```typescript
item: MenuItem
orderId?: string
guestName?: string
guestEmail?: string
```

**MenuGrid:**
```typescript
items: MenuItem[]
guestName?: string
guestEmail?: string
orderId?: string
// ... existing props
```

### Events

**MenuGrid emits:**
- `write-review`: (item: MenuItem) => void

**GuestReviewModal emits:**
- `close`: () => void
- `success`: (message: string) => void
- `error`: (message: string) => void

---

## Error Messages

| Situation | Message |
|-----------|---------|
| Empty review | "Please write a review" |
| Too short (< 10 chars) | "Review must be at least 10 characters" |
| No order ID | "Order information missing" |
| API error | Server error message |
| Network error | "Failed to submit review" |
| Missing menu item | "Menu item information missing" |

---

## Success Messages

| Situation | Message |
|-----------|---------|
| Review submitted | "Review submitted successfully! Status: Pending approval" |

---

## Styling

### Review Modal
- Background: White
- Header: Amber gradient (from-amber-500 to-amber-600)
- Stars: Yellow/gray interactive
- Button: Amber gradient background

### Menu Card
- Rating badge: Yellow background
- Review button: Blue text, border style
- Add to cart: Amber gradient

---

## Database

### Reviews Table
```sql
CREATE TABLE reviews (
  id UUID PRIMARY KEY,
  guest_id UUID,
  order_id UUID,
  menu_item_id UUID,
  rating INT (1-5),
  review_text TEXT,
  status ENUM('pending','approved','rejected'),
  approved_by UUID,
  approved_at TIMESTAMP,
  created_at TIMESTAMP,
  updated_at TIMESTAMP
)
```

---

## Permissions

From ReviewPermissionsSeeder (already run):

**Guest can:**
- ✓ `reviews.view` - See all reviews
- ✓ `reviews.create` - Submit reviews
- ✓ `reviews.update` - Edit own reviews
- ✓ `reviews.delete` - Delete own reviews
- ✓ `reviews.vote` - Mark helpful
- ✓ `reviews.notifications` - Get notified

**Guest cannot:**
- ✗ `reviews.moderate` - Approve/reject
- ✗ `reviews.respond` - Add responses
- ✗ `reviews.analytics` - View analytics

---

## Checklist

- [ ] QRMenuLayout.vue updated with review modal
- [ ] GuestReviewModal imported correctly
- [ ] State and handlers added
- [ ] MenuGrid component using QRMenuItemCard
- [ ] Test "Write Review" button
- [ ] Test form validation
- [ ] Test successful submission
- [ ] Check database for pending reviews
- [ ] Test manager approval flow
- [ ] Verify reviews display correctly
- [ ] Check permissions are correct
- [ ] Test error handling
- [ ] Test on mobile/tablet

---

## Next Steps

1. **Update QRMenuLayout.vue** (5 minutes)
   - Add review modal integration code

2. **Test** (10 minutes)
   - Write a test review
   - Check success message
   - Verify in database

3. **Manager Approval** (5 minutes)
   - Login as manager
   - Approve/reject review
   - Verify display

4. **Deploy** (when ready)
   - Commit changes
   - Deploy to staging
   - Test with real users

---

## Support

For questions, see:
- **GUEST_REVIEW_QR_MENU_GUIDE.md** - Full guide
- **REVIEW_PERMISSIONS_GUIDE.md** - Permissions info
- **GuestReviewModal.vue** - Component code
- **QRMenuItemCard.vue** - Menu card component

---

**Status:**  Ready for Implementation  
**Date:** August 27, 2026  
**Version:** 1.0.0

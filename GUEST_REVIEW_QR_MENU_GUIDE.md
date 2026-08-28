# Guest Review System - QR Menu Integration

## Overview
Guests can now **write and submit reviews directly from the QR menu** when viewing individual menu items. Reviews are instantly visible with star ratings and guest feedback.

---

## How It Works for Guests

### 1. **Scan QR Code**
- Guest scans QR code on table or menu
- Accesses restaurant menu through QR portal

### 2. **Browse Menu Items**
- Views menu items with images, descriptions, and prices
- Each item shows **star rating** from existing reviews

### 3. **Write Review**
- Clicks **"Write Review"** button on any menu item
- Modal opens with review form

### 4. **Submit Review**
- Selects **1-5 star rating** (interactive stars)
- Writes review (minimum 10 characters)
- Submits review (requires active order)
- Review enters "Pending" state for manager approval

### 5. **View Reviews**
- After approval, review appears publicly on menu item
- Shows guest name, rating, and text
- Guest can see "See Reviews" link if reviews exist

---

## Components Created

### 1. **GuestReviewModal.vue**
**Location:** `Client2/vue-project/src/components/guest/GuestReviewModal.vue`

Modal dialog for writing reviews with:
- Star rating selector (interactive hover effect)
- Review text input (minimum 10 characters)
- Menu item preview
- Guest information display
- Approval status notification

**Props:**
```typescript
- isOpen: boolean                // Modal visibility
- menuItem: MenuItem | null      // The item being reviewed
- guestName?: string             // Reviewer name
- guestEmail?: string            // Reviewer email
- orderId?: string              // Required to submit
```

**Emits:**
```typescript
- close()           // Close modal
- success(message)  // Review submitted
- error(message)    // Error occurred
```

### 2. **QRMenuItemCard.vue**
**Location:** `Client2/vue-project/src/components/guest/qr-menu/QRMenuItemCard.vue`

Enhanced menu card with review button, showing:
- Menu item image
- Category badge
- Item name & description
- Average rating with stars (from existing reviews)
- Price
- Quantity selector
- "Add to Cart" button
- **"Write Review" button** (NEW)
- Availability status

**Features:**
- ⭐ Shows review stats if available
- 📝 "Write Review" button always visible
- 🛒 "Add to Cart" for ordering
- ✓ Loads review statistics onMount

### 3. **MenuGrid.vue** (Updated)
**Location:** `Client2/vue-project/src/components/guest/qr-menu/MenuGrid.vue`

Updated to:
- Import `QRMenuItemCard` instead of `MenuCard`
- Pass guest info and order ID to cards
- Emit `write-review` event
- Handle review modal integration

---

## Integration Points

### In QRMenuLayout.vue

Add review modal state and handlers:

```vue
<script setup lang="ts">
import { ref } from 'vue'
import GuestReviewModal from '@/components/guest/GuestReviewModal.vue'

// Review modal state
const showReviewModal = ref(false)
const selectedMenuItemForReview = ref<MenuItem | null>(null)

// Menu Grid passes review request
const handleWriteReview = (item: MenuItem) => {
  selectedMenuItemForReview.value = item
  showReviewModal.value = true
}

const handleReviewSuccess = (message: string) => {
  // Show toast notification
  console.log('', message)
  // Optionally reload review stats
}

const handleReviewError = (message: string) => {
  // Show error notification
  console.error('', message)
}
</script>

<template>
  <!-- Menu Grid Component -->
  <MenuGrid
    :items="filteredMenuItems"
    :guest-name="guestName"
    :guest-email="guestEmail"
    :order-id="currentOrderId"
    @write-review="handleWriteReview"
  />

  <!-- Review Modal -->
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

## User Flow

```
Guest on QR Menu
       ↓
Browse Menu Items (see ratings)
       ↓
Click "Write Review" on item
       ↓
Review Modal Opens
  - Select 1-5 stars
  - Write review (min 10 chars)
  - See guest info
       ↓
Click "Submit Review"
       ↓
Review Status: PENDING
(shown as yellow badge)
       ↓
Manager approves/rejects
       ↓
If approved: Shows publicly ✓
If rejected: Hidden ✗
```

---

## Review Submission Flow

### Frontend
1. Guest clicks "Write Review" button
2. Modal opens with menu item preview
3. Guest fills rating and text
4. Clicks "Submit Review"
5. Frontend validates (rating + min 10 chars)
6. Submits to API

### Backend (API)
```
POST /api/reviews
{
  guest_id: string,       // From auth
  order_id: string,       // Required
  menu_item_id: string,   // From modal
  rating: number,         // 1-5
  review_text: string     // Min 10 chars
}
```

### Response
```json
{
  "success": true,
  "data": {
    "id": "uuid",
    "status": "pending",
    "rating": 5,
    "review_text": "...",
    "created_at": "2026-08-27..."
  },
  "message": "Review submitted successfully!"
}
```

---

## Styling

### Review Modal
- **Header:** Amber gradient background
- **Content:** White background with sections
- **Stars:** Interactive yellow/gray (Lucide icons)
- **Buttons:** Amber/gray with hover effects
- **Animations:** Smooth fade transition

### Menu Item Card
- **Review Button:** Border style, blue text
- **Rating Badge:** Yellow background with stars
- **Quantity Selector:** Gray background
- **Add to Cart:** Amber gradient

---

## Permissions Required

Guest must have these permissions to write reviews:
```
- reviews.view       ✓ (see reviews)
- reviews.create     ✓ (submit reviews)
- reviews.update     ✓ (edit own reviews)
- reviews.vote       ✓ (vote helpful)
```

All enabled by default for guests via ReviewPermissionsSeeder.

---

## API Endpoints Used

### Review Creation
```
POST /api/reviews
Authorization: Bearer {token}
```

### Review Stats
```
GET /api/reviews/menu-item/{id}/stats
```

### Review List
```
GET /api/reviews/public?menu_item_id={id}
```

---

## Error Handling

### Validation Errors
- **Empty review:** "Please write a review"
- **Too short:** "Review must be at least 10 characters"
- **No rating:** Uses default 5 stars

### Submission Errors
- **Network error:** "Failed to submit review"
- **Missing order ID:** "Order information missing"
- **API error:** Shows server message

### User Feedback
-  Success toast: "Review submitted! Pending approval"
-  Error alert: Shows error message
- ⏳ Loading state: "Submitting..." with spinner

---

## Testing Checklist

### Basic Functionality
- [ ] Click "Write Review" opens modal
- [ ] Star rating selector works
- [ ] Text input accepts text
- [ ] Submit button disabled until valid
- [ ] Close button closes modal

### Validation
- [ ] Empty review shows error
- [ ] Short review shows error
- [ ] Min 10 chars enables submit
- [ ] Star selection works (1-5)

### Integration
- [ ] Review modal shows correct menu item
- [ ] Guest info displays
- [ ] Order ID passed correctly
- [ ] Success message shows
- [ ] Error message shows

### API
- [ ] Review submits successfully
- [ ] Status is "pending" after submit
- [ ] Guest name stored correctly
- [ ] Rating saved correctly
- [ ] Review text saved correctly

### UI
- [ ] Modal is responsive
- [ ] Stars highlight on hover
- [ ] Buttons are clickable
- [ ] Character count displays
- [ ] Guest info visible

---

## Future Enhancements

1. **Photos:** Allow guests to attach photos to reviews
2. **Anonymous Reviews:** Option to post anonymously
3. **Editing:** Allow guests to edit pending reviews
4. **Rating Distribution:** Show rating breakdown chart
5. **Helpful Votes:** Allow upvoting helpful reviews
6. **Management Response:** Show manager responses
7. **Email Notifications:** Notify on review approval
8. **Moderation Dashboard:** Bulk moderation actions

---

## Files Modified/Created

### Created
-  `GuestReviewModal.vue` - Review writing modal
-  `QRMenuItemCard.vue` - Menu card with review button
-  `GUEST_REVIEW_QR_MENU_GUIDE.md` - This file

### Updated
-  `MenuGrid.vue` - Use QRMenuItemCard + emit review events
- ⏳ `QRMenuLayout.vue` - Add review modal integration (MANUAL)

### No Changes
- `reviewService.ts` - Already has createReview method
- `review.ts` types - Already has all needed interfaces

---

## Installation Steps

### 1. Verify Files Exist
```bash
ls Client2/vue-project/src/components/guest/GuestReviewModal.vue
ls Client2/vue-project/src/components/guest/qr-menu/QRMenuItemCard.vue
ls Client2/vue-project/src/components/guest/qr-menu/MenuGrid.vue
```

### 2. Update QRMenuLayout.vue Manually
Add the review modal integration (see Integration Points above)

### 3. Test in Browser
- Open QR menu
- Click "Write Review"
- Fill form and submit
- Verify success/error messages

### 4. Check Database
```sql
SELECT * FROM reviews WHERE status = 'pending';
```

---

## Review Lifecycle

```
Created (pending)
    ↓
Manager Reviews
    ↓
    ├─→ Approved → Displayed publicly
    │
    └─→ Rejected → Hidden from guests

Statuses:
- pending    → Waiting manager approval
- approved   → Public, visible on menu items
- rejected   → Hidden from guests
```

---

## Permissions Reference

Review permissions for guests (from ReviewPermissionsSeeder):

```
reviews.view        ✓ View all reviews
reviews.create      ✓ Submit new reviews
reviews.update      ✓ Edit own reviews
reviews.delete      ✓ Delete own reviews
reviews.moderate    ✗ Approve/reject (managers only)
reviews.respond     ✗ Add responses (managers only)
reviews.vote        ✓ Mark helpful/unhelpful
reviews.notifications ✓ Get notifications
```

---

## Summary

Guests can now write reviews directly from the QR menu:

1. **Browse** menu items with ratings
2. **Click** "Write Review" button
3. **Rate** and **write** review
4. **Submit** for approval
5. **Manager** approves/rejects
6. **Reviews** display publicly

All with beautiful UI, proper validation, and error handling!

---

**Status:**  Complete  
**Version:** 1.0.0  
**Date:** August 27, 2026

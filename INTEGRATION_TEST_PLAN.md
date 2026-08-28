# Review System Integration - Test Plan

## Overview
This document outlines the comprehensive testing approach for the integrated menu item review system.

## Test Environment Setup

### Prerequisites
- Backend API running with all review endpoints (routes/api.php lines 640-698)
- Frontend development server running (`npm run dev`)
- Browser with DevTools
- Test database with sample data

### Database Considerations
- Ensure `restaurant_test` database exists
- Run all migrations: `php artisan migrate --database=mysql_test`
- Verify review-related tables created:
  - `menu_item_reviews`
  - `review_responses`
  - `review_helpfulness_votes`
  - `review_notifications`

---

## Integration Test Cases

### 1. Menu Item Display Integration

#### Test 1.1: Review Ratings on Menu Cards
**File:** `src/components/menu/MenuCard.vue`

**Steps:**
1. Navigate to menu management page
2. Load menu items
3. Verify review ratings display
4. Check:
   - Average rating displayed
   - Star count accurate
   - Review count shown
   - "View Reviews" button present

**Expected Results:**
- ✓ Ratings load without errors
- ✓ Stats match backend data
- ✓ Click "View Reviews" navigates to detail page

**API Calls:**
- `GET /api/admin/analytics/menu-items/{id}/stats`

---

### 2. Menu Item Detail Page Integration

#### Test 2.1: Detail Page Loads Correctly
**File:** `src/views/guest/MenuItemDetail.vue`

**Steps:**
1. Click "View Reviews" from menu card OR navigate to `/menu-items/{id}`
2. Verify detail page loads
3. Check:
   - Item image displays
   - Item info visible
   - Rating stats show
   - Review tabs present

**Expected Results:**
- ✓ Page loads in <2 seconds
- ✓ All item data displays correctly
- ✓ No console errors

**API Calls:**
- `GET /api/menu-items/{id}` (to be added)
- `GET /api/admin/analytics/menu-items/{id}/stats`

#### Test 2.2: Public Reviews Tab
**Steps:**
1. Click "Reviews" tab
2. Verify reviews list loads
3. Check:
   - Reviews display with ratings
   - Guest names shown
   - Helpful/not helpful votes work
   - Pagination works

**Expected Results:**
- ✓ Reviews load and display
- ✓ Voting updates counts
- ✓ Pagination functional

**API Calls:**
- `GET /api/menu-items/{id}/reviews?page=1&per_page=10&sort=recent`
- `POST /api/reviews/{id}/vote`

#### Test 2.3: Review Submission Tab
**Steps:**
1. Log in as guest
2. Navigate to eligible menu item detail
3. Click "Write Review" tab
4. Submit review with:
   - Rating (1-5 stars)
   - Text review
5. Verify submission

**Expected Results:**
- ✓ Review form displays for eligible items
- ✓ Submit successful
- ✓ Review appears in list
- ✓ User redirected to reviews tab

**API Calls:**
- `POST /api/reviews`

---

### 3. Guest Dashboard Integration

#### Test 3.1: Dashboard Loads
**File:** `src/views/guest/GuestDashboard.vue`

**Steps:**
1. Log in as guest
2. Navigate to `/dashboard/guest`
3. Verify dashboard displays

**Expected Results:**
- ✓ Stats cards load
- ✓ Review counts accurate
- ✓ Notification panel renders

**API Calls:**
- `GET /api/notifications/reviews`
- `GET /api/notifications/reviews/unread-count`
- `GET /api/guests/{id}/eligible-items`

#### Test 3.2: Eligible Items Section
**Steps:**
1. On guest dashboard
2. Check "Items Available to Review"
3. Click "Review" button
4. Verify navigation

**Expected Results:**
- ✓ List shows eligible items
- ✓ Clicking review navigates correctly
- ✓ Item pre-filled in form

**API Calls:**
- `GET /api/guests/{id}/eligible-items`

#### Test 3.3: Notifications Section
**Steps:**
1. On guest dashboard
2. Click "View" under notifications
3. Verify notifications load
4. Click notification to mark read

**Expected Results:**
- ✓ Notifications display
- ✓ Unread badge shows count
- ✓ Clicking marks as read

**API Calls:**
- `GET /api/notifications/reviews`
- `POST /api/notifications/{id}/read`

---

### 4. Notification Bell Integration

#### Test 4.1: Bell in Navbar
**File:** `src/components/dashboard/ReviewNotificationBell.vue`

**Steps:**
1. Log in as any user
2. Look at navbar
3. Find notification bell icon
4. Click to open dropdown
5. Verify notifications load

**Expected Results:**
- ✓ Bell visible in navbar
- ✓ Unread badge shows if notifications exist
- ✓ Dropdown opens smoothly
- ✓ Notifications update on poll

**API Calls:**
- `GET /api/notifications/reviews` (every 30 seconds)
- `GET /api/notifications/reviews/unread-count`

---

### 5. Manager Moderation Integration

#### Test 5.1: Manager Dashboard Widget
**File:** `src/components/dashboard/ManagerReviewWidget.vue`

**Steps:**
1. Log in as manager
2. Go to manager dashboard
3. Find "Review Moderation" widget
4. Verify stats display
5. Try approve/reject actions

**Expected Results:**
- ✓ Widget displays
- ✓ Pending count accurate
- ✓ Latest review shows
- ✓ Approve/reject buttons work

**API Calls:**
- `GET /api/admin/reviews?status=pending&page=1`
- `GET /api/admin/analytics/pending-count`
- `POST /api/admin/reviews/{id}/approve`
- `POST /api/admin/reviews/{id}/reject`

#### Test 5.2: Full Moderation Dashboard
**File:** `src/views/reviews/ModerationPage.vue`

**Steps:**
1. Navigate to `/reviews/moderation`
2. Check permission guard (manager+ only)
3. View pending reviews
4. Test approve/reject/delete
5. Test add response

**Expected Results:**
- ✓ Access denied for non-managers
- ✓ Reviews list loads
- ✓ Actions work correctly
- ✓ Response form works

**API Calls:**
- `GET /api/admin/reviews?status=pending`
- `POST /api/admin/reviews/{id}/approve`
- `POST /api/admin/reviews/{id}/reject`
- `DELETE /api/admin/reviews/{id}`
- `POST /api/admin/reviews/{id}/response`

---

### 6. Manager Analytics Integration

#### Test 6.1: Analytics Widget
**File:** `src/components/dashboard/ManagerAnalyticsWidget.vue`

**Steps:**
1. On manager dashboard
2. Find "Review Analytics" widget
3. Check stats display
4. Change trend period
5. Verify trends update

**Expected Results:**
- ✓ Stats cards load
- ✓ Top/lowest items show
- ✓ Response rate displays
- ✓ Period selector works

**API Calls:**
- `GET /api/admin/analytics/top-rated`
- `GET /api/admin/analytics/lowest-rated`
- `GET /api/admin/analytics/review-trends?period=daily`

#### Test 6.2: Full Analytics Dashboard
**File:** `src/views/reviews/AnalyticsPage.vue`

**Steps:**
1. Navigate to `/reviews/analytics`
2. Check all sections load
3. Test period selector
4. Verify all stats update

**Expected Results:**
- ✓ Permission check works
- ✓ All charts load
- ✓ Data accurate
- ✓ Responsive design

**API Calls:**
- All analytics endpoints

---

### 7. Navigation Integration

#### Test 7.1: Sidebar Links
**File:** `src/components/dashboard/Sidebar.vue`

**Steps:**
1. Log in as different roles
2. Check sidebar shows appropriate review links
3. Click each link
4. Verify navigation

**Expected Results:**
- ✓ Guest sees: "My Reviews"
- ✓ Manager sees: "My Reviews", "Review Moderation", "Review Analytics"
- ✓ All links work correctly

#### Test 7.2: Permission Guards
**Steps:**
1. Log in as guest
2. Try to access `/reviews/moderation`
3. Should be denied
4. Log in as manager
5. Should be allowed

**Expected Results:**
- ✓ Permission checks work
- ✓ Redirects to unauthorized page for guests
- ✓ Managers can access

---

### 8. State Management Integration

#### Test 8.1: Pinia Store
**File:** `src/stores/reviewStore.ts`

**Steps:**
1. Open browser DevTools
2. Install Vue Devtools if needed
3. Navigate to review pages
4. Check Pinia store state
5. Verify state updates on actions

**Expected Results:**
- ✓ Store initializes correctly
- ✓ State updates reflect API responses
- ✓ Getters compute correctly
- ✓ Actions complete successfully

---

### 9. API Integration Verification

#### Test 9.1: Backend Connectivity
**Steps:**
1. Open Network tab in DevTools
2. Navigate through review features
3. Check all API calls
4. Verify response statuses

**Expected Results:**
- ✓ All requests return 200/201
- ✓ No 404 errors
- ✓ Response times < 500ms
- ✓ CORS headers correct

#### Test 9.2: Error Handling
**Steps:**
1. Simulate API errors:
   - Kill backend server
   - Make API calls
   - Verify error messages
2. Check loading states
3. Test retry logic

**Expected Results:**
- ✓ User-friendly error messages
- ✓ Loading indicators show/hide
- ✓ No silent failures

---

### 10. Cross-Feature Integration

#### Test 10.1: End-to-End Review Workflow

**Guest Workflow:**
1. Guest logs in
2. Browses menu items
3. Sees review ratings on cards
4. Clicks "View Reviews"
5. Views public reviews
6. Votes helpful/not helpful
7. Returns to detail page
8. Submits review
9. Checks dashboard for pending reviews
10. Checks notifications

**Expected Results:**
- ✓ All steps complete successfully
- ✓ Data persists correctly
- ✓ No errors or warnings

**Manager Workflow:**
1. Manager logs in
2. Sees notification bell with count
3. Clicks notification → views list
4. Marks notification as read
5. Goes to moderation dashboard
6. Reviews pending reviews
7. Approves one review
8. Adds response to another
9. Checks analytics dashboard
10. Views trends and stats

**Expected Results:**
- ✓ All features work together
- ✓ Data updates in real-time
- ✓ Permissions enforced

---

## Performance Testing

### Load Testing
- Load homepage with 100+ menu items
- Verify all rating stats load
- Check response times

### Pagination Testing
- Test pagination on reviews page
- Load different pages
- Verify data accuracy

### Polling Testing
- Notification bell polls every 30 seconds
- Verify polls work for 5+ minutes
- Check for memory leaks

---

## Browser Compatibility Testing

Test on:
- Chrome (latest)
- Firefox (latest)
- Safari (latest)
- Edge (latest)
- Mobile browsers (iOS Safari, Chrome Mobile)

---

## Accessibility Testing

- Tab navigation through all components
- Screen reader compatibility
- Color contrast ratios
- Keyboard-only navigation

---

## Test Execution Checklist

### Pre-Test
- [ ] Backend running
- [ ] Frontend running
- [ ] Database seeded
- [ ] All migrations run
- [ ] Cache cleared

### During Test
- [ ] Document any failures
- [ ] Take screenshots of errors
- [ ] Record console errors
- [ ] Note response times

### Post-Test
- [ ] Compile results
- [ ] File issues if needed
- [ ] Update documentation
- [ ] Mark ready for production

---

## Known Issues & Workarounds

(To be updated as testing proceeds)

---

## Sign-Off

- **Tested By:** [Your Name]
- **Test Date:** [Date]
- **Results:** PASS / FAIL
- **Ready for Production:** YES / NO

---

## Next Steps

After successful integration testing:
1. Deploy to staging
2. Perform user acceptance testing
3. Deploy to production
4. Monitor for issues
5. Gather feedback


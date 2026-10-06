# Frontend Optimization Implementation Plan

## Context
Backend optimization is complete (17-19 queries, 66-152ms response time). Frontend still shows 2.42-3.06s LCP due to:
1. **Duplicate API call**: Making separate `getRecentAssignments(8)` call even though `/api/waiter/dashboard` already returns `recent_assignments`
2. **Sequential loading**: Two network roundtrips instead of one
3. **Font rendering**: LCP element is `<h3>` with bold font that may block rendering

## Implementation Plan

- [x] 1. **Remove duplicate API call in WaiterDashboard.vue** ✅ COMPLETED
      - Remove the `await waiterService.getRecentAssignments(8)` call on line ~383
      - Use `dashboardData.recent_assignments` instead (already included in dashboard response)
      - Update the logic to handle `recent_assignments` from dashboard data
      
      **Files:** `d:\Restaurant_system2\Client2\vue-project\src\views\waiter\WaiterDashboard.vue`
      
      **Verify:** 
      - Run `npm run dev` in Client2/vue-project directory
      - Open Chrome DevTools → Network tab → Clear → Refresh dashboard
      - Confirm only ONE `/api/waiter/dashboard` call is made (no `/api/waiter/dashboard/recent-assignments`)
      - Measure LCP in Performance tab - target: <1.5s (down from 2.42s)

- [x] 2. **Add loading skeleton for perceived performance** ✅ COMPLETED
      - Add skeleton loader component that displays immediately
      - Show skeleton for stats cards and recent assignments section
      - Replace loading spinner with skeleton UI
      
      **Files:** 
      - `d:\Restaurant_system2\Client2\vue-project\src\views\waiter\WaiterDashboard.vue`
      - `d:\Restaurant_system2\Client2\vue-project\src\components\SkeletonLoader.vue` (create if needed)
      
      **Verify:**
      - Refresh dashboard and observe immediate content placeholder
      - LCP should improve as skeleton renders faster than data load
      - User perceives faster load even if actual time is similar

- [x] 3. **Optimize font loading strategy** ✅ COMPLETED
      - Check if custom fonts are blocking render
      - Add `font-display: swap` to CSS font declarations
      - Consider system font stack for critical text
      
      **Files:** 
      - `d:\Restaurant_system2\Client2\vue-project\src\assets\main.css` or theme file
      - `d:\Restaurant_system2\Client2\vue-project\index.html` (check for font links)
      
      **Verify:**
      - LCP element (`<h3>` with bold font) should render with fallback font immediately
      - Custom font swaps in after load without layout shift
      - Check Lighthouse report → "Ensure text remains visible during webfont load"

- [ ] 4. **Defer below-the-fold content**
      - Identify content not visible on initial viewport
      - Use `v-if` with intersection observer to lazy-load off-screen sections
      - Load "Recent Assignments" table only after stats cards are visible
      
      **Files:** `d:\Restaurant_system2\Client2\vue-project\src\views\waiter\WaiterDashboard.vue`
      
      **Verify:**
      - Stats cards (above fold) render immediately
      - Below-fold content loads progressively
      - No impact on LCP (which is above-fold `<h3>`)

- [ ] 5. **Verify final performance**
      - Run Lighthouse performance audit
      - Measure LCP, FCP, TTI metrics
      - Compare against baseline (LCP: 2.42s → target <1.5s)
      
      **Files:** N/A (measurement only)
      
      **Verify:**
      - Open Chrome DevTools → Lighthouse tab
      - Run audit in incognito mode (no extensions)
      - Performance score should be >90
      - LCP should be <1.5 seconds (ideally <1.0s)
      - Network waterfall should show single dashboard API call

## Expected Outcomes

### Before (Current State)
- **LCP:** 2.42-3.06 seconds
- **Network:** 2 sequential API calls (dashboard + recent-assignments)
- **Backend:** 66-152ms (good)
- **Frontend:** ~2.3s blocking time

### After (Target State)
- **LCP:** <1.5 seconds (ideally <1.0s)
- **Network:** 1 API call (dashboard only)
- **Backend:** 66-152ms (unchanged)
- **Frontend:** <1.0s blocking time
- **Perceived performance:** Immediate skeleton → fast data load

## Priority Order

**Critical (Must Do):**
- Step 1: Remove duplicate API call - **biggest impact, easiest fix**

**High Value (Should Do):**
- Step 2: Add skeleton loaders - improves perceived performance
- Step 3: Optimize font loading - LCP element is text with bold font

**Nice to Have:**
- Step 4: Defer below-fold - marginal benefit for LCP
- Step 5: Verification - confirms we hit targets

## Technical Notes

### Backend Response Structure (verified)
```json
{
  "data": {
    "today_stats": { ... },
    "performance_metrics": { ... },
    "recent_assignments": [ ... ],  // ← ALREADY INCLUDED
    "ready_for_pickup": [ ... ],
    "on_delivery": [ ... ],
    "weekly_performance": [ ... ]
  }
}
```

### Current Code Issue (line ~383)
```javascript
// ❌ PROBLEM: Making redundant call
const dashboardData = await waiterService.getDashboard({ hotel_id: hotelStore.hotelId })
const assignments = await waiterService.getRecentAssignments(8)  // ← Duplicate data
recentAssignments.value = assignments || []
```

### Fixed Code
```javascript
// ✅ SOLUTION: Use data already in dashboard response
const dashboardData = await waiterService.getDashboard({ hotel_id: hotelStore.hotelId })
recentAssignments.value = dashboardData.recent_assignments || []
```

## Success Criteria

✅ Only 1 API call to `/api/waiter/dashboard` on page load
✅ LCP metric < 1.5 seconds (measured in Chrome DevTools)
✅ No visual regressions (all data displays correctly)
✅ Lighthouse performance score > 90
✅ Backend performance maintains 66-152ms response time

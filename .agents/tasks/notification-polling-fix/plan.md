# Implementation Plan: Fix Notification Polling Architecture

## Problem Analysis

The server logs show `/api/notifications/unread-count` being called excessively due to:

1. **NotificationCenter.vue remounting issue**: Every route navigation may trigger Vue to remount `NotificationCenter.vue`, which calls `startPolling()` on mount and `stopPolling()` on unmount. The `stopPolling()` resets the module-level `notificationPollingActive` singleton guard, allowing new parallel polling intervals to spawn.

2. **ReviewNotificationBell.vue independent polling**: This component polls review-specific endpoints every 30s with its own `setInterval`, creating duplicate polling instances.

3. **Other domain-specific polling**: Components like `GuestBookingStatus.vue`, `kitchenDashboard.vue`, and `PaymentPendingPage.vue` use `setInterval` for their respective domains (booking status, kitchen orders, payment status) - these are legitimate and should remain unchanged.

## Implementation Plan

- [ ] 1. Move notification polling lifecycle to DashboardLayout.vue for stable single-instance management.
      Create a `useNotificationPolling` composable to centralize polling logic and prevent duplicate intervals.
      Files: d:/Restaurant_system2/Client2/vue-project/src/composables/useNotificationPolling.ts (new), d:/Restaurant_system2/Client2/vue-project/src/Layouts/DashboardLayout.vue
      Verify: `npm run type-check` - TypeScript compilation passes without errors.

- [ ] 2. Remove polling lifecycle management from NotificationCenter.vue component.
      Keep only the UI and store interaction logic, removing `startPolling` and `stopPolling` calls from lifecycle hooks.
      Files: d:/Restaurant_system2/Client2/vue-project/src/components/reception/NotificationCenter.vue
      Verify: `npm run type-check` - Component compiles and no console warnings about duplicate polling appear.

- [ ] 3. Fix ReviewNotificationBell.vue to use singleton polling pattern and integrate with the notification system.
      Replace independent `setInterval` with a singleton pattern or integrate with the main notification store if review notifications share auth context.
      Files: d:/Restaurant_system2/Client2/vue-project/src/components/dashboard/ReviewNotificationBell.vue
      Verify: `npm run build` - Build succeeds and review notifications still display correctly.

- [ ] 4. Implement proper polling cleanup on logout using auth store or router guard.
      Add cleanup to auth store logout method to ensure polling stops when user logs out, not just on component unmount.
      Files: d:/Restaurant_system2/Client2/vue-project/src/stores/auth.ts
      Verify: Test logout functionality and confirm no polling continues after logout in browser dev tools Network tab.

- [ ] 5. Update notification service to better handle singleton polling and prevent race conditions.
      Improve the existing singleton pattern in notificationService.ts to be more robust and add better error handling for concurrent polling attempts.
      Files: d:/Restaurant_system2/Client2/vue-project/src/services/notificationService.ts
      Verify: `npm run type-check` - Service compiles correctly and no polling errors appear in console.

- [ ] 6. Add debugging and monitoring capabilities to track notification polling behavior.
      Add console logging (development only) to track polling start/stop events and help identify any remaining duplicate polling issues.
      Files: d:/Restaurant_system2/Client2/vue-project/src/stores/notificationStore.ts, d:/Restaurant_system2/Client2/vue-project/src/services/notificationService.ts
      Verify: Run app in dev mode and check console logs for proper polling lifecycle events without duplicates.

## Key Architecture Changes

1. **Centralized Polling Management**: Notification polling will be managed at the `DashboardLayout.vue` level (which mounts once per session) instead of in individual components that can remount.

2. **Singleton Pattern Enforcement**: The notification service will enforce true singleton behavior for polling intervals, preventing multiple concurrent polls.

3. **Auth-Tied Cleanup**: Polling cleanup will be tied to authentication state changes, not component lifecycle events.

4. **Role-Based Polling**: Maintain existing role-based polling logic (receptionist role gets 30s intervals) but ensure it only runs once per session.

## Files Verified Safe to Leave Unchanged

- `d:/Restaurant_system2/Client2/vue-project/src/components/guest/GuestBookingStatus.vue` - Domain-specific booking status polling (legitimate)
- `d:/Restaurant_system2/Client2/vue-project/src/views/kitchen/kitchenDashboard.vue` - Kitchen order polling (legitimate)
- `d:/Restaurant_system2/Client2/vue-project/src/views/payment/PaymentPendingPage.vue` - Payment status polling (legitimate)
- `d:/Restaurant_system2/Client2/vue-project/src/components/kitchen/KitchenHeader.vue` - Clock timer (UI timer)
- `d:/Restaurant_system2/Client2/vue-project/src/components/manager/managerHeader.vue` - Clock timer (UI timer)
- `d:/Restaurant_system2/Client2/vue-project/src/components/loading/GlobalPageLoader.vue` - Loading animation timer (UI timer)

## Verification Commands

- Build verification: `npm run build`
- Type checking: `npm run type-check`
- Development testing: `npm run dev`
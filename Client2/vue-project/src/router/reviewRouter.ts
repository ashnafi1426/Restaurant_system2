/**
 * Review Routes
 * Routes for review-related pages and components
 */

export default [
  {
    path: '/dashboard/guest',
    name: 'guest.dashboard',
    component: () => import('@/views/guest/GuestDashboard.vue'),
    meta: {
      requiresAuth: true,
      title: 'Guest Dashboard',
    },
  },
  {
    path: '/menu-items/:id',
    name: 'menu-item.detail',
    component: () => import('@/views/guest/MenuItemDetail.vue'),
    meta: {
      title: 'Menu Item Details',
    },
  },
  {
    path: '/reviews',
    name: 'reviews.index',
    component: () => import('@/views/reviews/ReviewsPage.vue'),
    meta: {
      requiresAuth: true,
      title: 'My Reviews',
    },
  },
  {
    path: '/reviews/moderation',
    name: 'reviews.moderation',
    component: () => import('@/views/reviews/ModerationPageSimple.vue'),
    meta: {
      requiresAuth: true,
      permission: 'reviews.moderate',
      title: 'Review Moderation',
    },
  },
  {
    path: '/reviews/analytics',
    name: 'reviews.analytics',
    component: () => import('@/views/reviews/AnalyticsPageSimple.vue'),
    meta: {
      requiresAuth: true,
      permission: 'reviews.analytics',
      title: 'Review Analytics',
    },
  },
  {
    path: '/manager/reviews',
    name: 'manager.reviews',
    component: () => import('@/views/reviews/ModerationPageSimple.vue'),
    meta: {
      requiresAuth: true,
      permission: 'reviews.moderate',
      title: 'Review Moderation',
    },
  },
  {
    path: '/manager/reviews/analytics',
    name: 'manager.reviews.analytics',
    component: () => import('@/views/reviews/AnalyticsPageSimple.vue'),
    meta: {
      requiresAuth: true,
      permission: 'reviews.analytics',
      title: 'Review Analytics',
    },
  },
]

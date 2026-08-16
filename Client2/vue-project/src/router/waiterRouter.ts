import type { RouteRecordRaw } from 'vue-router'

const WaiterDashboard = () => import('../views/waiter/WaiterDashboard.vue')
const AssignedOrders = () => import('../views/waiter/AssignedOrders.vue')
const ReadyPickup = () => import('../views/waiter/ReadyPickup.vue')
const OnDelivery = () => import('../views/waiter/OnDelivery.vue')
const CompletedOrders = () => import('../views/waiter/CompletedOrders.vue')
const DeliveryHistory = () => import('../views/waiter/DeliveryHistory.vue')
const Performance = () => import('../views/waiter/Performance.vue')
const Notifications = () => import('../views/waiter/Notifications.vue')
const WaiterProfile = () => import('../views/waiter/WaiterProfile.vue')
const WaiterSettings = () => import('../views/waiter/WaiterSettings.vue')

const waiterRoutes: RouteRecordRaw[] = [
  {
    path: '/waiter',
    name: 'WaiterDashboard',
    component: WaiterDashboard,
    meta: {
      requiresAuth: true,
      permission: 'dashboard.view',
      title: 'Waiter Dashboard',
    },
  },
  {
    path: '/waiter/assigned-orders',
    name: 'AssignedOrders',
    component: AssignedOrders,
    meta: {
      requiresAuth: true,
      permission: 'delivery.accept',
      title: 'Assigned Orders',
    },
  },
  {
    path: '/waiter/ready-pickup',
    name: 'ReadyPickup',
    component: ReadyPickup,
    meta: {
      requiresAuth: true,
      permission: 'delivery.pickup',
      title: 'Ready for Pickup',
    },
  },
  {
    path: '/waiter/on-delivery',
    name: 'OnDelivery',
    component: OnDelivery,
    meta: {
      requiresAuth: true,
      permission: 'delivery.deliver',
      title: 'On Delivery',
    },
  },
  {
    path: '/waiter/completed-orders',
    name: 'CompletedOrders',
    component: CompletedOrders,
    meta: {
      requiresAuth: true,
      permission: 'delivery.view',
      title: 'Completed Orders',
    },
  },
  {
    path: '/waiter/delivery-history',
    name: 'DeliveryHistory',
    component: DeliveryHistory,
    meta: {
      requiresAuth: true,
      permission: 'delivery.view',
      title: 'Delivery History',
    },
  },
  {
    path: '/waiter/performance',
    name: 'WaiterPerformance',
    component: Performance,
    meta: {
      requiresAuth: true,
      permission: 'delivery.view',
      title: 'Performance',
    },
  },
  {
    path: '/waiter/notifications',
    name: 'WaiterNotifications',
    component: Notifications,
    meta: {
      requiresAuth: true,
      permission: 'notifications.view',
      title: 'Notifications',
    },
  },
  {
    path: '/waiter/profile',
    name: 'WaiterProfilePage',
    component: WaiterProfile,
    meta: {
      requiresAuth: true,
      title: 'My Profile',
    },
  },
  {
    path: '/waiter/settings',
    name: 'WaiterSettingsPage',
    component: WaiterSettings,
    meta: {
      requiresAuth: true,
      title: 'Settings',
    },
  },
]

export default waiterRoutes

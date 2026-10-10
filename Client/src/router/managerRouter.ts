import type { RouteRecordRaw } from 'vue-router'

const ManagerDashboard = () => import('../views/manager/ManagerDashboard.vue')
const ManagerRevenue = () => import('../views/manager/ManagerRevenue.vue')
const ManagerOperations = () => import('../views/manager/ManagerOperations.vue')
const ManagerLaundry = () => import('../views/manager/ManagerLaundry.vue')
const ManagerOrders = () => import('../views/manager/ManagerOrders.vue')
const ManagerInventory = () => import('../views/manager/ManagerInventory.vue')
const ManagerFinance = () => import('../views/manager/ManagerFinance.vue')
const ManagerAnalytics = () => import('../views/manager/ManagerAnalytics.vue')
const ManagerWaiters = () => import('../views/manager/ManagerWaiters.vue')
const WaiterManagement = () => import('../views/manager/WaiterManagement.vue')
const FloorAssignment = () => import('../views/manager/FloorAssignment.vue')
const AddFloor = () => import('../views/manager/AddFloor.vue')
const DeliveryManagement = () => import('../views/manager/DeliveryManagement.vue')
const RestaurantTables = () => import('../views/manager/RestaurantTables.vue')
const TableAssignments = () => import('../views/manager/TableAssignments.vue')
const Setting = () => import('../views/manager/Setting.vue')
const ManagerProfile = () => import('../views/manager/ManagerProfile.vue')

const managerRoutes: RouteRecordRaw[] = [
  {
    path: '/manager',
    name: 'ManagerDashboard',
    component: ManagerDashboard,
    meta: {
      requiresAuth: true,
      role: 'manager',
      permission: 'dashboard.view',
      title: 'Manager Dashboard',
    },
  },
  {
    path: '/manager/revenue',
    name: 'RevenueReport',
    component: ManagerRevenue,
    meta: {
      requiresAuth: true,
      permission: 'reports.view',
      title: 'Revenue Report',
    },
  },
  {
    path: '/manager/operations',
    name: 'Operations',
    component: ManagerOperations,
    meta: {
      requiresAuth: true,
      permission: 'reports.view',
      title: 'Operations',
    },
  },
  {
    path: '/manager/laundry',
    name: 'LaundryManagement',
    component: ManagerLaundry,
    meta: {
      requiresAuth: true,
      permission: 'reports.view',
      title: 'Laundry Management',
    },
  },
  {
    path: '/manager/waiters',
    name: 'WaiterManagement',
    component: ManagerWaiters,
    meta: {
      requiresAuth: true,
      permission: 'waiters.view',
      title: 'Waiter Management',
    },
  },
  {
    path: '/manager/waiters/management',
    name: 'WaiterFullManagement',
    component: WaiterManagement,
    meta: {
      requiresAuth: true,
      permission: 'waiters.manage',
      title: 'Waiter Management',
    },
  },
  {
    path: '/manager/floor-assignment',
    name: 'FloorAssignment',
    alias: ['/manager/floors', '/floors', '/admin/floors'],
    component: FloorAssignment,
    meta: {
      requiresAuth: true,
      permission: 'floors.view',
      title: 'Floor Management',
    },
  },
  {
    path: '/manager/add-floor',
    name: 'AddFloor',
    component: AddFloor,
    meta: {
      requiresAuth: true,
      permission: 'floors.manage',
      title: 'Add New Floor',
    },
  },
  {
    path: '/manager/delivery-management',
    name: 'DeliveryManagement',
    component: DeliveryManagement,
    meta: {
      requiresAuth: true,
      permission: 'delivery.reassign',
      title: 'Delivery Management',
    },
  },
  {
    path: '/manager/orders',
    name: 'ManagerFoodOrders',
    component: ManagerOrders,
    meta: {
      requiresAuth: true,
      permission: 'kitchen.view',
      title: 'Food Orders Management',
    },
  },
  {
    path: '/manager/restaurant-tables',
    name: 'RestaurantTables',
    component: RestaurantTables,
    meta: {
      requiresAuth: true,
      permission: 'tables.view',
      title: 'Restaurant Tables',
    },
  },
  {
    path: '/manager/table-assignments',
    name: 'TableAssignments',
    component: TableAssignments,
    meta: {
      requiresAuth: true,
      permission: 'tables.assign',
      title: 'Table Assignments',
    },
  },
  {
    path: '/manager/inventory',
    name: 'InventoryManagement',
    component: ManagerInventory,
    meta: {
      requiresAuth: true,
      permission: 'reports.view',
      title: 'Inventory Management',
    },
  },
  {
    path: '/manager/finance',
    name: 'FinanceManagement',
    component: ManagerFinance,
    meta: {
      requiresAuth: true,
      permission: 'reports.view',
      title: 'Finance Management',
    },
  },
  {
    path: '/manager/analytics',
    name: 'AnalyticsDashboard',
    component: ManagerAnalytics,
    meta: {
      requiresAuth: true,
      permission: 'reports.view',
      title: 'Analytics Dashboard',
    },
  },
  {
    path: '/manager/settings',
    name: 'ManagerSettings',
    component: Setting,
    meta: {
      requiresAuth: true,
      title: 'Manager Settings',
    },
  },
  {
    path: '/manager/profile',
    name: 'ManagerProfile',
    component: ManagerProfile,
    meta: {
      requiresAuth: true,
      title: 'Manager Profile',
    },
  },
]

export default managerRoutes

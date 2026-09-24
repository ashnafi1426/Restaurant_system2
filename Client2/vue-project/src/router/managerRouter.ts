import type { RouteRecordRaw } from 'vue-router'
import ManagerDashboard from '../views/manager/ManagerDashboard.vue'
import ManagerRevenue from '../views/manager/ManagerRevenue.vue'
import ManagerOperations from '../views/manager/ManagerOperations.vue'
import ManagerLaundry from '../views/manager/ManagerLaundry.vue'
import ManagerOrders from '../views/manager/ManagerOrders.vue'
import ManagerInventory from '../views/manager/ManagerInventory.vue'
import ManagerFinance from '../views/manager/ManagerFinance.vue'
import ManagerAnalytics from '../views/manager/ManagerAnalytics.vue'
import ManagerWaiters from '../views/manager/ManagerWaiters.vue'
import WaiterManagement from '../views/manager/WaiterManagement.vue'
import FloorAssignment from '../views/manager/FloorAssignment.vue'
import AddFloor from '../views/manager/AddFloor.vue'
import DeliveryManagement from '../views/manager/DeliveryManagement.vue'
import RestaurantTables from '../views/manager/RestaurantTables.vue'
import TableAssignments from '../views/manager/TableAssignments.vue'
import Setting from '../views/manager/Setting.vue'
import ManagerProfile from '../views/manager/ManagerProfile.vue'

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
    component: FloorAssignment,
    meta: {
      requiresAuth: true,
      permission: 'floors.view',
      title: 'Floor Assignment',
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

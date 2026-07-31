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
import Setting from '../views/manager/Setting.vue'

const managerRoutes: RouteRecordRaw[] = [
  /*|--------------------------------------------------------------------------
  | Manager Dashboard
  |--------------------------------------------------------------------------*/
  {
    path: '/manager',
    name: 'ManagerDashboard',
    component: ManagerDashboard,
    meta: {
      requiresAuth: true,
      role: 'manager',
      title: 'Manager Dashboard',
    },
  },
  {
    path: '/manager/revenue',
    name: 'RevenueReport',
    component: ManagerRevenue,
    meta: {
      requiresAuth: true,
      role: 'manager',
      title: 'Revenue Report',
    },
  },

  /*|--------------------------------------------------------------------------
  | Operations
  |--------------------------------------------------------------------------*/
  {
    path: '/manager/operations',
    name: 'Operations',
    component: ManagerOperations,
    meta: {
      requiresAuth: true,
      role: 'manager',
      title: 'Operations',
    },
  },

  /*|--------------------------------------------------------------------------
  | Laundry Management
  |--------------------------------------------------------------------------*/
  {
    path: '/manager/laundry',
    name: 'LaundryManagement',
    component: ManagerLaundry,
    meta: {
      requiresAuth: true,
      role: 'manager',
      title: 'Laundry Management',
    },
  },

  /*|--------------------------------------------------------------------------
  | Waiter Management
  |--------------------------------------------------------------------------*/
  {
    path: '/manager/waiters',
    name: 'WaiterManagement',
    component: ManagerWaiters,
    meta: {
      requiresAuth: true,
      role: 'manager',
      title: 'Waiter Management',
    },
  },
  {
    path: '/manager/waiters/management',
    name: 'WaiterFullManagement',
    component: WaiterManagement,
    meta: {
      requiresAuth: true,
      role: 'manager',
      title: 'Waiter Management',
    },
  },
  {
    path: '/manager/floor-assignment',
    name: 'FloorAssignment',
    component: FloorAssignment,
    meta: {
      requiresAuth: true,
      role: 'manager',
      title: 'Floor Assignment',
    },
  },
  {
    path: '/manager/add-floor',
    name: 'AddFloor',
    component: AddFloor,
    meta: {
      requiresAuth: true,
      role: 'manager',
      title: 'Add New Floor',
    },
  },
  {
    path: '/manager/delivery-management',
    name: 'DeliveryManagement',
    component: DeliveryManagement,
    meta: {
      requiresAuth: true,
      role: 'manager',
      title: 'Delivery Management',
    },
  },

  /*|--------------------------------------------------------------------------
  | Food Orders Management
  |--------------------------------------------------------------------------*/
  {
    path: '/manager/orders',
    name: 'ManagerFoodOrders',
    component: ManagerOrders,
    meta: {
      requiresAuth: true,
      role: 'manager',
      title: 'Food Orders Management',
    },
  },

  /*|--------------------------------------------------------------------------
  | Inventory Management
  |--------------------------------------------------------------------------*/
  {
    path: '/manager/inventory',
    name: 'InventoryManagement',
    component: ManagerInventory,
    meta: {
      requiresAuth: true,
      role: 'manager',
      title: 'Inventory Management',
    },
  },

  /*|--------------------------------------------------------------------------
  | Finance Management
  |--------------------------------------------------------------------------*/
  {
    path: '/manager/finance',
    name: 'FinanceManagement',
    component: ManagerFinance,
    meta: {
      requiresAuth: true,
      role: 'manager',
      title: 'Finance Management',
    },
  },

  /*|--------------------------------------------------------------------------
  | Analytics Dashboard
  |--------------------------------------------------------------------------*/
  {
    path: '/manager/analytics',
    name: 'AnalyticsDashboard',
    component: ManagerAnalytics,
    meta: {
      requiresAuth: true,
      role: 'manager',
      title: 'Analytics Dashboard',
    },
  },

  /*|--------------------------------------------------------------------------
  | Manager Settings
  |--------------------------------------------------------------------------*/
  {
    path: '/manager/settings',
    name: 'ManagerSettings',
    component: Setting,
    meta: {
      requiresAuth: true,
      role: 'manager',
      title: 'Manager Settings',
    },
  },
]

export default managerRoutes

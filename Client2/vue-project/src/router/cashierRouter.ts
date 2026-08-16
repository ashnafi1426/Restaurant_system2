import type { RouteRecordRaw } from 'vue-router'

const cashierRoutes: RouteRecordRaw[] = [
  {
    path: '/cashier',
    name: 'cashier',
    redirect: '/cashier/dashboard',
    meta: { requiresAuth: true },
    children: [
      {
        path: 'dashboard',
        name: 'cashier-dashboard',
        component: () => import('@/views/Cashier/CashierDashboard.vue'),
        meta: { title: 'Cashier Dashboard', permission: 'dashboard.view' },
      },
      {
        path: 'payments',
        name: 'cashier-payments',
        component: () => import('@/views/Cashier/PaymentsPage.vue'),
        meta: { title: 'Payments', permission: 'payments.view' },
      },
      {
        path: 'payments/:id',
        name: 'cashier-payment-detail',
        component: () => import('@/views/Cashier/PaymentDetailPage.vue'),
        meta: { title: 'Payment Details', permission: 'payments.view' },
      },
      {
        path: 'reports',
        name: 'cashier-reports',
        component: () => import('@/views/Cashier/ReportsPage.vue'),
        meta: { title: 'Reports', permission: 'reports.sales' },
      },
      {
        path: 'profile',
        name: 'cashier-profile',
        component: () => import('@/views/Cashier/CashierProfile.vue'),
        meta: { title: 'Profile Settings' },
      },
    ],
  },
]

export default cashierRoutes

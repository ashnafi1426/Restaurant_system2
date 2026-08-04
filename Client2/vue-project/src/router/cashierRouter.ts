import type { RouteRecordRaw } from 'vue-router'

const cashierRoutes: RouteRecordRaw[] = [
  {
    path: '/cashier',
    name: 'cashier',
    redirect: '/cashier/dashboard',
    meta: { requiresAuth: true, role: 'cashier' },
    children: [
      {
        path: 'dashboard',
        name: 'cashier-dashboard',
        component: () => import('@/views/Cashier/CashierDashboard.vue'),
        meta: { title: 'Cashier Dashboard' },
      },
      {
        path: 'payments',
        name: 'cashier-payments',
        component: () => import('@/views/Cashier/PaymentsPage.vue'),
        meta: { title: 'Payments' },
      },
      {
        path: 'payments/:id',
        name: 'cashier-payment-detail',
        component: () => import('@/views/Cashier/PaymentDetailPage.vue'),
        meta: { title: 'Payment Details' },
      },
      {
        path: 'reports',
        name: 'cashier-reports',
        component: () => import('@/views/Cashier/ReportsPage.vue'),
        meta: { title: 'Reports' },
      },
    ],
  },
]

export default cashierRoutes

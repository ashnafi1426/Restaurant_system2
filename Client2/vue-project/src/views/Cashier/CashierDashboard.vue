<script setup lang="ts">
import { onMounted, computed } from 'vue'
import { useRouter } from 'vue-router'
import DashboardLayout from '../../layouts/DashboardLayout.vue'
import { useCashierStore } from '@/stores/cashierStore'
import {
  TrendingUp,
  Clock,
  CheckCircle,
  XCircle,
  DollarSign,
  Calendar,
  CreditCard,
  RefreshCw,
  FileText,
  ArrowUpRight,
} from 'lucide-vue-next'

const router = useRouter()
const cashierStore = useCashierStore()

// Load dashboard data
onMounted(() => {
  cashierStore.loadDashboard()
})

// Computed stats
const stats = computed(() => [
  {
    title: "Today's Revenue",
    value: `${cashierStore.todayRevenue.toFixed(2)} ETB`,
    icon: DollarSign,
    color: 'bg-green-500',
    trend: '+12%',
  },
  {
    title: 'Pending Payments',
    value: cashierStore.dashboardStats?.pending_payments ?? 0,
    icon: Clock,
    color: 'bg-yellow-500',
  },
  {
    title: 'Completed Payments',
    value: cashierStore.dashboardStats?.completed_payments ?? 0,
    icon: CheckCircle,
    color: 'bg-blue-500',
  },
  {
    title: 'Refund Requests',
    value: cashierStore.dashboardStats?.refund_requests ?? 0,
    icon: XCircle,
    color: 'bg-red-500',
  },
])

const weeklyRevenue = computed(() => cashierStore.weeklyRevenue.toFixed(2))
const monthlyRevenue = computed(() => cashierStore.monthlyRevenue.toFixed(2))

// Format currency
const formatCurrency = (amount: number | string) => {
  const numAmount = typeof amount === 'string' ? parseFloat(amount) : amount
  return `${numAmount.toFixed(2)} ETB`
}

// Format date
const formatDate = (date: string) => {
  return new Date(date).toLocaleDateString('en-US', {
    month: 'short',
    day: 'numeric',
    hour: '2-digit',
    minute: '2-digit',
  })
}
const getStatusColor = (status: string) => {
  const statusColors: Record<string, string> = {
    paid: 'bg-green-100 text-green-700',
    verified: 'bg-green-100 text-green-700',
    pending: 'bg-yellow-100 text-yellow-700',
    initialized: 'bg-blue-100 text-blue-700',
    failed: 'bg-red-100 text-red-700',
    refunded: 'bg-purple-100 text-purple-700',
  }
  return statusColors[status.toLowerCase()] || 'bg-gray-100 text-gray-700'
}

// Navigate to payments page
const navigateToPayments = (filter?: string) => {
  router.push({ name: 'cashier-payments', query: filter ? { filter } : {} })
}

// Navigate to payment detail
const viewPaymentDetails = (id: string) => {
  router.push({ name: 'cashier-payment-detail', params: { id } })
}

// Refresh dashboard
const refreshDashboard = () => {
  cashierStore.loadDashboard()
}
</script>

<template>
  <DashboardLayout>
    <div class="space-y-6 py-4 md:py-6">
      <!-- Header -->
      <div class="flex justify-between items-center">
        <div>
          <h1 class="text-3xl font-bold text-slate-800 dark:text-white">Cashier Dashboard</h1>
          <p class="text-slate-500 dark:text-slate-400 mt-1">
            Manage invoices, payments, transactions and refunds
          </p>
        </div>
        <button
          @click="refreshDashboard"
          class="flex items-center gap-2 px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg transition-colors"
        >
          <RefreshCw :size="18" />
          Refresh
        </button>
      </div>

      <!-- Loading State -->
      <div v-if="cashierStore.isLoading && !cashierStore.dashboardStats" class="space-y-6">
        <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-6">
          <div v-for="i in 4" :key="i" class="bg-white dark:bg-slate-800 rounded-xl border dark:border-slate-700 shadow-sm p-6 animate-pulse">
            <div class="h-4 bg-slate-200 dark:bg-slate-700 rounded w-1/2 mb-4"></div>
            <div class="h-8 bg-slate-200 dark:bg-slate-700 rounded w-3/4"></div>
          </div>
        </div>
      </div>

      <!-- Stats Cards -->
      <div v-else class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-6">
        <div
          v-for="stat in stats"
          :key="stat.title"
          class="bg-white dark:bg-slate-800 rounded-xl border dark:border-slate-700 shadow-sm p-6 hover:shadow-lg transition-shadow cursor-pointer"
          @click="stat.title === 'Pending Payments' ? navigateToPayments('pending') : null"
        >
          <div class="flex items-start justify-between">
            <div class="flex-1">
              <p class="text-sm text-slate-500 dark:text-slate-400">
                {{ stat.title }}
              </p>
              <h2 class="text-3xl font-bold text-slate-800 dark:text-white mt-2">
                {{ stat.value }}
              </h2>
              <p v-if="stat.trend" class="text-sm text-green-600 mt-1">{{ stat.trend }} from last week</p>
            </div>
            <div :class="[stat.color, 'p-3 rounded-lg']">
              <component :is="stat.icon" :size="24" class="text-white" />
            </div>
          </div>
        </div>
      </div>

      <!-- Quick Actions -->
      <div class="bg-white dark:bg-slate-800 rounded-xl border dark:border-slate-700 shadow-sm p-6">
        <h2 class="text-xl font-semibold text-slate-800 dark:text-white mb-4">Quick Actions</h2>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
          <button
            @click="navigateToPayments()"
            class="flex items-center justify-center gap-2 bg-blue-600 hover:bg-blue-700 text-white py-3 rounded-lg transition-colors"
          >
            <FileText :size="18" />
            View Payments
          </button>
          <button
            @click="navigateToPayments('paid')"
            class="flex items-center justify-center gap-2 bg-green-600 hover:bg-green-700 text-white py-3 rounded-lg transition-colors"
          >
            <CheckCircle :size="18" />
            Paid Payments
          </button>
          <button
            @click="navigateToPayments('pending')"
            class="flex items-center justify-center gap-2 bg-yellow-600 hover:bg-yellow-700 text-white py-3 rounded-lg transition-colors"
          >
            <Clock :size="18" />
            Pending Payments
          </button>
          <button
            @click="router.push({ name: 'cashier-reports' })"
            class="flex items-center justify-center gap-2 bg-indigo-600 hover:bg-indigo-700 text-white py-3 rounded-lg transition-colors"
          >
            <TrendingUp :size="18" />
            View Reports
          </button>
        </div>
      </div>

      <!-- Revenue Overview -->
      <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div class="bg-white dark:bg-slate-800 rounded-xl border dark:border-slate-700 shadow-sm p-6">
          <div class="flex items-center gap-2 mb-2">
            <Calendar :size="18" class="text-blue-600" />
            <h3 class="font-semibold text-slate-700 dark:text-slate-300">Weekly Revenue</h3>
          </div>
          <p class="text-3xl font-bold text-blue-600 mt-4">{{ weeklyRevenue }} ETB</p>
        </div>
        <div class="bg-white dark:bg-slate-800 rounded-xl border dark:border-slate-700 shadow-sm p-6">
          <div class="flex items-center gap-2 mb-2">
            <Calendar :size="18" class="text-green-600" />
            <h3 class="font-semibold text-slate-700 dark:text-slate-300">Monthly Revenue</h3>
          </div>
          <p class="text-3xl font-bold text-green-600 mt-4">{{ monthlyRevenue }} ETB</p>
        </div>
        <div class="bg-white dark:bg-slate-800 rounded-xl border dark:border-slate-700 shadow-sm p-6">
          <div class="flex items-center gap-2 mb-2">
            <CreditCard :size="18" class="text-purple-600" />
            <h3 class="font-semibold text-slate-700 dark:text-slate-300">Total Transactions</h3>
          </div>
          <p class="text-3xl font-bold text-purple-600 mt-4">
            {{ cashierStore.dashboardStats?.total_transactions ?? 0 }}
          </p>
        </div>
      </div>

      <!-- Recent Payments -->
      <div class="bg-white dark:bg-slate-800 rounded-xl border dark:border-slate-700 shadow-sm">
        <div class="px-6 py-4 border-b dark:border-slate-700 flex justify-between items-center">
          <h2 class="text-lg font-semibold text-slate-800 dark:text-white">Recent Payments</h2>
          <button
            @click="navigateToPayments()"
            class="text-sm text-blue-600 hover:text-blue-700 flex items-center gap-1"
          >
            View All
            <ArrowUpRight :size="16" />
          </button>
        </div>

        <div v-if="cashierStore.recentPayments.length === 0" class="p-12 text-center">
          <p class="text-slate-500 dark:text-slate-400">No recent payments found</p>
        </div>

        <div v-else class="overflow-x-auto">
          <table class="w-full">
            <thead class="bg-slate-50 dark:bg-slate-900">
              <tr>
                <th class="text-left p-4 text-sm font-semibold text-slate-700 dark:text-slate-300">
                  Transaction Ref
                </th>
                <th class="text-left p-4 text-sm font-semibold text-slate-700 dark:text-slate-300">
                  Customer
                </th>
                <th class="text-left p-4 text-sm font-semibold text-slate-700 dark:text-slate-300">
                  Amount
                </th>
                <th class="text-left p-4 text-sm font-semibold text-slate-700 dark:text-slate-300">
                  Type
                </th>
                <th class="text-left p-4 text-sm font-semibold text-slate-700 dark:text-slate-300">
                  Status
                </th>
                <th class="text-left p-4 text-sm font-semibold text-slate-700 dark:text-slate-300">
                  Date
                </th>
                <th class="text-left p-4 text-sm font-semibold text-slate-700 dark:text-slate-300">
                  Actions
                </th>
              </tr>
            </thead>
            <tbody>
              <tr
                v-for="payment in cashierStore.recentPayments"
                :key="payment.id"
                class="border-t dark:border-slate-700 hover:bg-slate-50 dark:hover:bg-slate-900 transition-colors"
              >
                <td class="p-4 text-sm text-slate-700 dark:text-slate-300 font-mono">
                  {{ payment.tx_ref }}
                </td>
                <td class="p-4 text-sm text-slate-700 dark:text-slate-300">
                  {{ payment.customer_name }}
                </td>
                <td class="p-4 text-sm font-semibold text-slate-800 dark:text-white">
                  {{ formatCurrency(payment.amount) }}
                </td>
                <td class="p-4 text-sm text-slate-600 dark:text-slate-400">
                  {{ payment.type }}
                </td>
                <td class="p-4">
                  <span
                    :class="[
                      getStatusColor(payment.status),
                      'px-3 py-1 rounded-full text-xs font-medium capitalize',
                    ]"
                  >
                    {{ payment.status }}
                  </span>
                </td>
                <td class="p-4 text-sm text-slate-600 dark:text-slate-400">
                  {{ formatDate(payment.created_at) }}
                </td>
                <td class="p-4">
                  <button
                    @click="viewPaymentDetails(payment.id)"
                    class="text-sm text-blue-600 hover:text-blue-700 dark:text-blue-400"
                  >
                    View
                  </button>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

      <!-- Pending Payments -->
      <div v-if="cashierStore.pendingPayments.length > 0" class="bg-white dark:bg-slate-800 rounded-xl border dark:border-slate-700 shadow-sm">
        <div class="px-6 py-4 border-b dark:border-slate-700 flex justify-between items-center">
          <h2 class="text-lg font-semibold text-slate-800 dark:text-white">Pending Payments</h2>
          <button
            @click="navigateToPayments('pending')"
            class="text-sm text-blue-600 hover:text-blue-700 flex items-center gap-1"
          >
            View All
            <ArrowUpRight :size="16" />
          </button>
        </div>
        <div class="overflow-x-auto">
          <table class="w-full">
            <thead class="bg-slate-50 dark:bg-slate-900">
              <tr>
                <th class="text-left p-4 text-sm font-semibold text-slate-700 dark:text-slate-300">
                  Transaction Ref
                </th>
                <th class="text-left p-4 text-sm font-semibold text-slate-700 dark:text-slate-300">
                  Customer
                </th>
                <th class="text-left p-4 text-sm font-semibold text-slate-700 dark:text-slate-300">
                  Amount
                </th>
                <th class="text-left p-4 text-sm font-semibold text-slate-700 dark:text-slate-300">
                  Status
                </th>
                <th class="text-left p-4 text-sm font-semibold text-slate-700 dark:text-slate-300">
                  Date
                </th>
              </tr>
            </thead>
            <tbody>
              <tr
                v-for="payment in cashierStore.pendingPayments"
                :key="payment.id"
                class="border-t dark:border-slate-700 hover:bg-slate-50 dark:hover:bg-slate-900 transition-colors cursor-pointer"
                @click="viewPaymentDetails(payment.id)"
              >
                <td class="p-4 text-sm text-slate-700 dark:text-slate-300 font-mono">
                  {{ payment.tx_ref }}
                </td>
                <td class="p-4 text-sm text-slate-700 dark:text-slate-300">
                  {{ payment.customer_name }}
                </td>
                <td class="p-4 text-sm font-semibold text-slate-800 dark:text-white">
                  {{ formatCurrency(payment.amount) }}
                </td>
                <td class="p-4">
                  <span
                    :class="[
                      getStatusColor(payment.status),
                      'px-3 py-1 rounded-full text-xs font-medium capitalize',
                    ]"
                  >
                    {{ payment.status }}
                  </span>
                </td>
                <td class="p-4 text-sm text-slate-600 dark:text-slate-400">
                  {{ formatDate(payment.created_at) }}
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </DashboardLayout>
</template>

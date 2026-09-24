<script setup lang="ts">
import { onMounted, computed, watch } from 'vue'
import { useRouter } from 'vue-router'
import DashboardLayout from '@/Layouts/DashboardLayout.vue'
import { useCashierStore } from '@/stores/cashierStore'
import { useHotelStore } from '@/stores/hotelStore'
import { useAuthStore } from '@/stores/auth'
import { useLanguageStore } from '@/stores/language'
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
  Building2,
  Receipt,
  Wallet,
} from 'lucide-vue-next'

const router = useRouter()
const cashierStore = useCashierStore()
const hotelStore = useHotelStore()
const auth = useAuthStore()
const languageStore = useLanguageStore()

const currency = computed(() => hotelStore.currentHotel?.currency || 'ETB')

// Load dashboard data
onMounted(() => {
  cashierStore.loadDashboard()
})

// Watch hotel switch to reload dashboard data for selected tenant
watch(() => hotelStore.hotelId, () => {
  cashierStore.loadDashboard()
})

// Computed stats
const stats = computed(() => [
  {
    title: languageStore.t("Today's Revenue", "Today's Revenue"),
    value: `${cashierStore.todayRevenue.toFixed(2)} ${currency.value}`,
    icon: DollarSign,
    color: 'bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 border border-emerald-500/20',
    trend: '+12%',
  },
  {
    title: languageStore.t('pending_payments', 'Pending Payments'),
    value: cashierStore.dashboardStats?.pending_payments ?? 0,
    icon: Clock,
    color: 'bg-amber-500/10 text-amber-600 dark:text-amber-400 border border-amber-500/20',
  },
  {
    title: languageStore.t('completed_payments', 'Completed Payments'),
    value: cashierStore.dashboardStats?.completed_payments ?? 0,
    icon: CheckCircle,
    color: 'bg-blue-500/10 text-blue-600 dark:text-blue-400 border border-blue-500/20',
  },
  {
    title: languageStore.t('refund_requests', 'Refund Requests'),
    value: cashierStore.dashboardStats?.refund_requests ?? 0,
    icon: XCircle,
    color: 'bg-rose-500/10 text-rose-600 dark:text-rose-400 border border-rose-500/20',
  },
])

const weeklyRevenue = computed(() => cashierStore.weeklyRevenue.toFixed(2))
const monthlyRevenue = computed(() => cashierStore.monthlyRevenue.toFixed(2))

// Format currency
const formatCurrency = (amount: number | string) => {
  const numAmount = typeof amount === 'string' ? parseFloat(amount) : amount
  return `${(numAmount || 0).toFixed(2)} ${currency.value}`
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
    paid: 'bg-emerald-500/10 text-emerald-700 dark:text-emerald-400 border border-emerald-500/20',
    verified: 'bg-emerald-500/10 text-emerald-700 dark:text-emerald-400 border border-emerald-500/20',
    pending: 'bg-amber-500/10 text-amber-700 dark:text-amber-400 border border-amber-500/20',
    initialized: 'bg-blue-500/10 text-blue-700 dark:text-blue-400 border border-blue-500/20',
    failed: 'bg-rose-500/10 text-rose-700 dark:text-rose-400 border border-rose-500/20',
    refunded: 'bg-purple-500/10 text-purple-700 dark:text-purple-400 border border-purple-500/20',
  }
  return statusColors[status.toLowerCase()] || 'bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300'
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
    <div class="space-y-6 bg-slate-50 dark:bg-slate-950 min-h-screen p-4 sm:p-6 max-w-full overflow-hidden font-sans">
      <!-- Header Banner with Hotel Scope Badge -->
      <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 p-5 sm:p-6 rounded-2xl shadow-xs flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div class="flex items-center gap-3">
          <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-emerald-600 to-teal-700 flex items-center justify-center shadow-md flex-shrink-0 text-white">
            <Wallet class="w-5 h-5 stroke-[2.2]" />
          </div>
          <div>
            <div class="flex items-center gap-2">
              <h1 class="text-xl sm:text-2xl font-black text-slate-900 dark:text-white">{{ languageStore.t('cashier_dashboard', 'Cashier Dashboard') }}</h1>
              <span v-if="hotelStore.hotelName" class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-xs font-bold bg-blue-100 dark:bg-blue-900/30 text-blue-800 dark:text-blue-300 border border-blue-200 dark:border-blue-700/50">
                <Building2 class="w-3 h-3" />
                {{ hotelStore.hotelName }}
              </span>
              <span class="text-[10px] uppercase font-black bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 px-2 py-0.5 rounded-md border border-emerald-500/20">
                {{ languageStore.t('cashier', 'Cashier') }}
              </span>
            </div>
            <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">
              {{ languageStore.t('cashier_dashboard_desc', 'Multi-property payment collection, live transaction auditing, and financial settlements.') }}
            </p>
          </div>
        </div>

        <button
          @click="refreshDashboard"
          :disabled="cashierStore.isLoading"
          class="inline-flex items-center justify-center gap-2 px-4 py-2.5 bg-blue-600 hover:bg-blue-700 text-white rounded-xl text-xs sm:text-sm font-bold shadow-md shadow-blue-600/20 transition cursor-pointer disabled:opacity-50"
        >
          <RefreshCw class="w-4 h-4" :class="{ 'animate-spin': cashierStore.isLoading }" />
          <span>{{ languageStore.t('refresh', 'Refresh') }}</span>
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
          @click="stat.title === languageStore.t('pending_payments', 'Pending Payments') ? navigateToPayments('pending') : null"
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
        <h2 class="text-xl font-semibold text-slate-800 dark:text-white mb-4">{{ languageStore.t('quick_actions', 'Quick Actions') }}</h2>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
          <button
            v-if="auth.can('payments.view')"
            @click="navigateToPayments()"
            class="flex items-center justify-center gap-2 bg-blue-600 hover:bg-blue-700 text-white py-3 rounded-lg transition-colors cursor-pointer"
          >
            <FileText :size="18" />
            {{ languageStore.t('view_payments', 'View Payments') }}
          </button>
          <button
            v-if="auth.can('payments.view')"
            @click="navigateToPayments('paid')"
            class="flex items-center justify-center gap-2 bg-green-600 hover:bg-green-700 text-white py-3 rounded-lg transition-colors cursor-pointer"
          >
            <CheckCircle :size="18" />
            {{ languageStore.t('paid_payments', 'Paid Payments') }}
          </button>
          <button
            v-if="auth.can('payments.view')"
            @click="navigateToPayments('pending')"
            class="flex items-center justify-center gap-2 bg-yellow-600 hover:bg-yellow-700 text-white py-3 rounded-lg transition-colors cursor-pointer"
          >
            <Clock :size="18" />
            {{ languageStore.t('pending_payments', 'Pending Payments') }}
          </button>
          <button
            v-if="auth.can('reports.sales')"
            @click="router.push({ name: 'cashier-reports' })"
            class="flex items-center justify-center gap-2 bg-indigo-600 hover:bg-indigo-700 text-white py-3 rounded-lg transition-colors cursor-pointer"
          >
            <Receipt :size="18" />
            {{ languageStore.t('financial_reports', 'Financial Reports') }}
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
          <p class="text-3xl font-bold text-blue-600 mt-4">{{ weeklyRevenue }} {{ currency }}</p>
        </div>
        <div class="bg-white dark:bg-slate-800 rounded-xl border dark:border-slate-700 shadow-sm p-6">
          <div class="flex items-center gap-2 mb-2">
            <Calendar :size="18" class="text-green-600" />
            <h3 class="font-semibold text-slate-700 dark:text-slate-300">Monthly Revenue</h3>
          </div>
          <p class="text-3xl font-bold text-green-600 mt-4">{{ monthlyRevenue }} {{ currency }}</p>
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

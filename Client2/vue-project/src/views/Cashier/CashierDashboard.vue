<script setup lang="ts">
import { ref, onMounted, onUnmounted, computed, watch } from 'vue'
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
  CheckCircle2,
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
  Utensils,
  Printer,
  Search,
  Sparkles,
  ChevronDown,
  ChevronUp,
  X,
  AlertCircle,
  Check,
} from 'lucide-vue-next'

const router = useRouter()
const cashierStore = useCashierStore()
const hotelStore = useHotelStore()
const auth = useAuthStore()
const languageStore = useLanguageStore()

const currency = computed(() => hotelStore.currentHotel?.currency || 'ETB')

// Order filters & UI states
const orderFilter = ref<'all' | 'paid' | 'unpaid' | 'cleared'>('all')
const orderSearch = ref('')
const isClearingId = ref<string | null>(null)
const expandedOrders = ref<Record<string, boolean>>({})

// Modals
const showSettleModal = ref(false)
const settleOrder = ref<any | null>(null)
const settleMethod = ref<'cash' | 'card' | 'bank_transfer'>('cash')
const isSubmittingSettle = ref(false)

const showReceiptModal = ref(false)
const receiptOrder = ref<any | null>(null)

// Toast notification
const toast = ref<{ show: boolean; message: string; type: 'success' | 'error' }>({
  show: false,
  message: '',
  type: 'success',
})
let toastTimeout: any = null

function showToast(message: string, type: 'success' | 'error' = 'success') {
  if (toastTimeout) clearTimeout(toastTimeout)
  toast.value = { show: true, message, type }
  toastTimeout = setTimeout(() => {
    toast.value.show = false
  }, 4000)
}

// Polling interval for live orders (every 20s)
let refreshInterval: any = null

onMounted(() => {
  cashierStore.loadDashboard()
  refreshInterval = setInterval(() => {
    cashierStore.fetchOrders({ filter: orderFilter.value, search: orderSearch.value })
  }, 20000)
})

onUnmounted(() => {
  if (refreshInterval) clearInterval(refreshInterval)
  if (toastTimeout) clearTimeout(toastTimeout)
})

// Watch hotel switch to reload dashboard data for selected tenant
watch(() => hotelStore.hotelId, () => {
  cashierStore.loadDashboard()
})

// Watch order search / filter change
watch([orderFilter, orderSearch], () => {
  cashierStore.fetchOrders({ filter: orderFilter.value, search: orderSearch.value })
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

// Filtered orders computed
const filteredOrders = computed(() => {
  const list = cashierStore.activeOrders || []
  if (!orderSearch.value.trim()) return list
  const q = orderSearch.value.toLowerCase().trim()
  return list.filter((o: any) =>
    o.order_number?.toLowerCase().includes(q) ||
    o.table_number?.toLowerCase().includes(q) ||
    o.guest_name?.toLowerCase().includes(q) ||
    o.room_number?.toLowerCase().includes(q)
  )
})

// Format currency
const formatCurrency = (amount: number | string) => {
  const numAmount = typeof amount === 'string' ? parseFloat(amount) : amount
  return `${(numAmount || 0).toFixed(2)} ${currency.value}`
}

// Format date
const formatDate = (date: string) => {
  if (!date) return '—'
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
    preparing: 'bg-blue-500/10 text-blue-700 dark:text-blue-400 border border-blue-500/20',
    ready: 'bg-indigo-500/10 text-indigo-700 dark:text-indigo-400 border border-indigo-500/20',
    served: 'bg-slate-500/10 text-slate-700 dark:text-slate-300 border border-slate-500/20',
    initialized: 'bg-blue-500/10 text-blue-700 dark:text-blue-400 border border-blue-500/20',
    failed: 'bg-rose-500/10 text-rose-700 dark:text-rose-400 border border-rose-500/20',
    refunded: 'bg-purple-500/10 text-purple-700 dark:text-purple-400 border border-purple-500/20',
  }
  return statusColors[status?.toLowerCase()] || 'bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300'
}

function toggleExpand(orderId: string) {
  expandedOrders.value[orderId] = !expandedOrders.value[orderId]
}

// Clear an already paid order
async function handleClearPaidOrder(order: any) {
  try {
    isClearingId.value = order.id
    const res = await cashierStore.clearOrder(order.id, { mark_as_paid: false })
    showToast(
      res.message || `Order #${order.order_number} cleared successfully! Table released.`,
      'success'
    )
  } catch (err: any) {
    showToast(err.message || 'Failed to clear order.', 'error')
  } finally {
    isClearingId.value = null
  }
}

// Open modal to settle and clear unpaid order
function openSettleModal(order: any) {
  settleOrder.value = order
  settleMethod.value = 'cash'
  showSettleModal.value = true
}

// Confirm settle & clear
async function confirmSettleAndClear() {
  if (!settleOrder.value) return
  try {
    isSubmittingSettle.value = true
    const res = await cashierStore.clearOrder(settleOrder.value.id, {
      mark_as_paid: true,
      payment_method: settleMethod.value,
    })
    showSettleModal.value = false
    showToast(
      res.message || `Payment collected and Order #${settleOrder.value.order_number} cleared!`,
      'success'
    )
    settleOrder.value = null
  } catch (err: any) {
    showToast(err.message || 'Failed to settle and clear order.', 'error')
  } finally {
    isSubmittingSettle.value = false
  }
}

// Open Receipt Modal
function openReceipt(order: any) {
  receiptOrder.value = order
  showReceiptModal.value = true
}

// Print Receipt
function printReceipt() {
  window.print()
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
      
      <!-- Toast Alert Notification -->
      <transition
        enter-active-class="transform ease-out duration-300 transition"
        enter-from-class="translate-y-2 opacity-0 sm:translate-y-0 sm:translate-x-2"
        enter-to-class="translate-y-0 opacity-100 sm:translate-x-0"
        leave-active-class="transition ease-in duration-100"
        leave-from-class="opacity-100"
        leave-to-class="opacity-0"
      >
        <div
          v-if="toast.show"
          class="fixed bottom-5 right-5 z-50 flex items-center gap-3 px-4 py-3 rounded-xl shadow-xl text-sm font-semibold border backdrop-blur-md"
          :class="toast.type === 'success'
            ? 'bg-emerald-50 dark:bg-emerald-950/90 text-emerald-800 dark:text-emerald-200 border-emerald-300 dark:border-emerald-700'
            : 'bg-rose-50 dark:bg-rose-950/90 text-rose-800 dark:text-rose-200 border-rose-300 dark:border-rose-700'"
        >
          <CheckCircle2 v-if="toast.type === 'success'" class="w-5 h-5 text-emerald-600 dark:text-emerald-400 flex-shrink-0" />
          <AlertCircle v-else class="w-5 h-5 text-rose-600 dark:text-rose-400 flex-shrink-0" />
          <span>{{ toast.message }}</span>
          <button @click="toast.show = false" class="ml-2 text-slate-400 hover:text-slate-600 dark:hover:text-slate-200">
            <X class="w-4 h-4" />
          </button>
        </div>
      </transition>

      <!-- Header Banner with Hotel Scope Badge -->
      <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 p-5 sm:p-6 rounded-2xl shadow-xs flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div class="flex items-center gap-3">
          <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-emerald-600 to-teal-700 flex items-center justify-center shadow-md flex-shrink-0 text-white">
            <Wallet class="w-5 h-5 stroke-[2.2]" />
          </div>
          <div>
            <div class="flex items-center gap-2">
              <h1 class="text-xl sm:text-2xl font-black text-slate-900 dark:text-white">
                {{ languageStore.t('cashier_dashboard', 'Cashier Dashboard') }}
              </h1>
              <span v-if="hotelStore.hotelName" class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-xs font-bold bg-blue-100 dark:bg-blue-900/30 text-blue-800 dark:text-blue-300 border border-blue-200 dark:border-blue-700/50">
                <Building2 class="w-3 h-3" />
                {{ hotelStore.hotelName }}
              </span>
              <span class="text-[10px] uppercase font-black bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 px-2 py-0.5 rounded-md border border-emerald-500/20">
                {{ languageStore.t('cashier', 'Cashier') }}
              </span>
            </div>
            <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">
              {{ languageStore.t('cashier_dashboard_desc', 'Payment collection, live customer order settlement, table clearing, and financial auditing.') }}
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

      <!-- Stats Cards -->
      <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-6">
        <div
          v-for="stat in stats"
          :key="stat.title"
          class="bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700 shadow-sm p-6 hover:shadow-lg transition-shadow cursor-pointer"
          @click="stat.title === languageStore.t('pending_payments', 'Pending Payments') ? navigateToPayments('pending') : null"
        >
          <div class="flex items-start justify-between">
            <div class="flex-1">
              <p class="text-sm text-slate-500 dark:text-slate-400 font-medium">
                {{ stat.title }}
              </p>
              <h2 class="text-2xl sm:text-3xl font-extrabold text-slate-800 dark:text-white mt-2">
                {{ stat.value }}
              </h2>
              <p v-if="stat.trend" class="text-xs text-green-600 mt-1 font-semibold">{{ stat.trend }} from last week</p>
            </div>
            <div :class="[stat.color, 'p-3 rounded-xl']">
              <component :is="stat.icon" :size="22" />
            </div>
          </div>
        </div>
      </div>

      <!-- ========================================================================= -->
      <!-- CUSTOMER ORDERS & TABLE CLEARING SECTION (Primary Cashier Workflow) -->
      <!-- ========================================================================= -->
      <div class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-800 shadow-sm overflow-hidden">
        
        <!-- Section Header -->
        <div class="p-5 sm:p-6 border-b border-slate-100 dark:border-slate-800 bg-gradient-to-r from-emerald-500/5 via-blue-500/5 to-transparent">
          <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
            <div>
              <div class="flex items-center gap-2">
                <div class="w-8 h-8 rounded-lg bg-emerald-500/10 flex items-center justify-center text-emerald-600 dark:text-emerald-400">
                  <Utensils class="w-4 h-4" />
                </div>
                <h2 class="text-lg font-black text-slate-900 dark:text-white">
                  {{ languageStore.t('customer_orders_clearing', 'Customer Orders & Table Settlement') }}
                </h2>
                <span class="px-2 py-0.5 rounded-full text-xs font-bold bg-emerald-100 dark:bg-emerald-950 text-emerald-700 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-800">
                  {{ cashierStore.orderCounts?.total_active ?? 0 }} Active
                </span>
              </div>
              <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">
                {{ languageStore.t('clear_order_help', 'When an order is paid, click "Clear Order" to finalize billing and release the restaurant table.') }}
              </p>
            </div>

            <!-- Summary KPI Badges -->
            <div class="flex flex-wrap items-center gap-2">
              <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl text-xs font-bold bg-amber-50 dark:bg-amber-950/40 text-amber-700 dark:text-amber-400 border border-amber-200 dark:border-amber-800/40">
                <Clock class="w-3.5 h-3.5" />
                <span>{{ cashierStore.orderCounts?.unpaid_orders ?? 0 }} Unpaid</span>
              </span>
              <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl text-xs font-bold bg-emerald-50 dark:bg-emerald-950/40 text-emerald-700 dark:text-emerald-400 border border-emerald-200 dark:border-emerald-800/40">
                <Sparkles class="w-3.5 h-3.5" />
                <span>{{ cashierStore.orderCounts?.paid_orders ?? 0 }} Paid (Ready to Clear)</span>
              </span>
              <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl text-xs font-bold bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 border border-slate-200 dark:border-slate-700">
                <CheckCircle class="w-3.5 h-3.5" />
                <span>{{ cashierStore.orderCounts?.cleared_today ?? 0 }} Cleared Today</span>
              </span>
            </div>
          </div>

          <!-- Search and Tab Controls -->
          <div class="mt-5 flex flex-col sm:flex-row items-stretch sm:items-center justify-between gap-3">
            <!-- Filter Tabs -->
            <div class="flex items-center p-1 bg-slate-100 dark:bg-slate-800/70 rounded-xl max-w-fit overflow-x-auto">
              <button
                @click="orderFilter = 'all'"
                class="px-3.5 py-1.5 rounded-lg text-xs font-bold transition"
                :class="orderFilter === 'all'
                  ? 'bg-white dark:bg-slate-900 text-slate-900 dark:text-white shadow-xs'
                  : 'text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white'"
              >
                All Orders
              </button>
              <button
                @click="orderFilter = 'paid'"
                class="px-3.5 py-1.5 rounded-lg text-xs font-bold transition flex items-center gap-1.5"
                :class="orderFilter === 'paid'
                  ? 'bg-emerald-600 text-white shadow-xs'
                  : 'text-emerald-700 dark:text-emerald-400 hover:bg-emerald-50 dark:hover:bg-emerald-950/40'"
              >
                <Sparkles class="w-3 h-3" />
                <span>Paid (Ready to Clear)</span>
              </button>
              <button
                @click="orderFilter = 'unpaid'"
                class="px-3.5 py-1.5 rounded-lg text-xs font-bold transition flex items-center gap-1.5"
                :class="orderFilter === 'unpaid'
                  ? 'bg-amber-600 text-white shadow-xs'
                  : 'text-amber-700 dark:text-amber-400 hover:bg-amber-50 dark:hover:bg-amber-950/40'"
              >
                <Clock class="w-3 h-3" />
                <span>Unpaid</span>
              </button>
              <button
                @click="orderFilter = 'cleared'"
                class="px-3.5 py-1.5 rounded-lg text-xs font-bold transition flex items-center gap-1.5"
                :class="orderFilter === 'cleared'
                  ? 'bg-slate-700 text-white shadow-xs'
                  : 'text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white'"
              >
                <Check class="w-3 h-3" />
                <span>Cleared</span>
              </button>
            </div>

            <!-- Search Field -->
            <div class="relative w-full sm:w-72">
              <Search class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400" />
              <input
                v-model="orderSearch"
                type="text"
                placeholder="Search Order #, Table #, Guest..."
                class="w-full pl-9 pr-4 py-2 bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl text-xs text-slate-800 dark:text-slate-200 focus:outline-none focus:ring-2 focus:ring-emerald-500"
              />
              <button
                v-if="orderSearch"
                @click="orderSearch = ''"
                class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600"
              >
                <X class="w-3.5 h-3.5" />
              </button>
            </div>
          </div>
        </div>

        <!-- Orders Table -->
        <div v-if="filteredOrders.length === 0" class="p-12 text-center">
          <div class="w-12 h-12 rounded-full bg-slate-100 dark:bg-slate-800 flex items-center justify-center mx-auto text-slate-400 mb-3">
            <Utensils class="w-6 h-6" />
          </div>
          <p class="text-sm font-semibold text-slate-700 dark:text-slate-300">No matching customer orders found</p>
          <p class="text-xs text-slate-400 mt-1">Orders placed by customers via table QR or staff will appear here.</p>
        </div>

        <div v-else class="overflow-x-auto">
          <table class="w-full text-left">
            <thead class="bg-slate-50 dark:bg-slate-900/80 border-b border-slate-100 dark:border-slate-800 text-[11px] uppercase tracking-wider text-slate-500 font-bold">
              <tr>
                <th class="p-4">Order / Type</th>
                <th class="p-4">Location / Table</th>
                <th class="p-4">Customer</th>
                <th class="p-4">Items</th>
                <th class="p-4">Amount</th>
                <th class="p-4">Order Status</th>
                <th class="p-4">Payment</th>
                <th class="p-4 text-right">Cashier Actions</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
              <template v-for="order in filteredOrders" :key="order.id">
                <tr
                  class="hover:bg-slate-50/70 dark:hover:bg-slate-800/40 transition-colors"
                  :class="{ 'bg-emerald-500/5': order.payment_status === 'paid' && !order.is_cleared }"
                >
                  <!-- Order Number & Type -->
                  <td class="p-4">
                    <div class="flex items-center gap-2">
                      <span class="font-mono font-bold text-xs text-slate-900 dark:text-white">
                        #{{ order.order_number }}
                      </span>
                    </div>
                    <div class="flex items-center gap-1.5 mt-1">
                      <span class="text-[10px] font-bold px-2 py-0.5 rounded-md bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-400 uppercase">
                        {{ order.order_type || 'dine_in' }}
                      </span>
                      <span class="text-[10px] text-slate-400">
                        {{ formatDate(order.order_time) }}
                      </span>
                    </div>
                  </td>

                  <!-- Table / Room -->
                  <td class="p-4">
                    <div v-if="order.table_number" class="flex flex-col">
                      <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg text-xs font-black bg-blue-50 dark:bg-blue-950/50 text-blue-700 dark:text-blue-300 border border-blue-200 dark:border-blue-800/40 w-fit">
                        {{ order.table_number }}
                      </span>
                      <span class="text-[10px] text-slate-400 mt-1">
                        Status: <strong class="capitalize" :class="order.table_status === 'occupied' ? 'text-amber-600 dark:text-amber-400' : 'text-emerald-600 dark:text-emerald-400'">{{ order.table_status || 'Occupied' }}</strong>
                      </span>
                    </div>
                    <div v-else-if="order.room_number" class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg text-xs font-black bg-purple-50 dark:bg-purple-950/50 text-purple-700 dark:text-purple-300 border border-purple-200 dark:border-purple-800/40 w-fit">
                      Room {{ order.room_number }}
                    </div>
                    <span v-else class="text-xs text-slate-400 font-medium">Walk-in</span>
                  </td>

                  <!-- Customer Name -->
                  <td class="p-4">
                    <div class="text-xs font-semibold text-slate-800 dark:text-slate-200">
                      {{ order.guest_name }}
                    </div>
                    <div v-if="order.guest_phone" class="text-[10px] text-slate-400 font-mono mt-0.5">
                      {{ order.guest_phone }}
                    </div>
                  </td>

                  <!-- Items Preview -->
                  <td class="p-4">
                    <button
                      @click="toggleExpand(order.id)"
                      class="text-xs font-bold text-blue-600 dark:text-blue-400 hover:underline flex items-center gap-1 cursor-pointer"
                    >
                      <span>{{ order.items_count }} items</span>
                      <ChevronDown v-if="!expandedOrders[order.id]" class="w-3.5 h-3.5" />
                      <ChevronUp v-else class="w-3.5 h-3.5" />
                    </button>
                    <div class="text-[11px] text-slate-500 dark:text-slate-400 truncate max-w-[160px] mt-0.5">
                      {{ order.items?.map((i: any) => `${i.quantity}x ${i.name}`).join(', ') }}
                    </div>
                  </td>

                  <!-- Total Amount -->
                  <td class="p-4">
                    <div class="text-sm font-black text-slate-900 dark:text-white">
                      {{ formatCurrency(order.total) }}
                    </div>
                  </td>

                  <!-- Order Status -->
                  <td class="p-4">
                    <span
                      class="px-2.5 py-1 rounded-full text-[11px] font-bold capitalize inline-block"
                      :class="getStatusColor(order.status)"
                    >
                      {{ order.status }}
                    </span>
                  </td>

                  <!-- Payment Status -->
                  <td class="p-4">
                    <div class="flex flex-col gap-1">
                      <span
                        v-if="order.payment_status === 'paid'"
                        class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-[11px] font-black bg-emerald-100 dark:bg-emerald-950/60 text-emerald-800 dark:text-emerald-300 border border-emerald-300 dark:border-emerald-700/60 w-fit"
                      >
                        <CheckCircle2 class="w-3 h-3 text-emerald-600 dark:text-emerald-400" />
                        <span>PAID</span>
                      </span>
                      <span
                        v-else
                        class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-[11px] font-black bg-rose-100 dark:bg-rose-950/60 text-rose-800 dark:text-rose-300 border border-rose-300 dark:border-rose-700/60 w-fit"
                      >
                        <Clock class="w-3 h-3 text-rose-600 dark:text-rose-400" />
                        <span>UNPAID</span>
                      </span>
                      <span v-if="order.payment_method" class="text-[10px] text-slate-400 font-medium capitalize">
                        via {{ order.payment_method }}
                      </span>
                    </div>
                  </td>

                  <!-- Cashier Actions -->
                  <td class="p-4 text-right">
                    <div class="flex items-center justify-end gap-2">
                      
                      <!-- IF PAID: Primary Action is CLEAR ORDER -->
                      <button
                        v-if="order.payment_status === 'paid' && !order.is_cleared"
                        @click="handleClearPaidOrder(order)"
                        :disabled="isClearingId === order.id"
                        class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl text-xs font-black bg-emerald-600 hover:bg-emerald-700 text-white shadow-sm shadow-emerald-600/30 transition cursor-pointer disabled:opacity-50"
                        title="Clear order and free table"
                      >
                        <RefreshCw v-if="isClearingId === order.id" class="w-3.5 h-3.5 animate-spin" />
                        <Sparkles v-else class="w-3.5 h-3.5" />
                        <span>{{ languageStore.t('clear_order', 'Clear Order') }}</span>
                      </button>

                      <!-- IF UNPAID: SETTLE & CLEAR (collect payment and clear) -->
                      <button
                        v-else-if="!order.is_cleared"
                        @click="openSettleModal(order)"
                        class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl text-xs font-black bg-amber-600 hover:bg-amber-700 text-white shadow-sm shadow-amber-600/30 transition cursor-pointer"
                        title="Collect payment and clear order"
                      >
                        <DollarSign class="w-3.5 h-3.5" />
                        <span>{{ languageStore.t('settle_clear', 'Settle & Clear') }}</span>
                      </button>

                      <!-- ALREADY CLEARED BADGE -->
                      <span
                        v-else
                        class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg text-xs font-bold bg-slate-100 dark:bg-slate-800 text-slate-500 border border-slate-200 dark:border-slate-700"
                      >
                        <Check class="w-3 h-3 text-emerald-500" />
                        <span>Cleared</span>
                      </span>

                      <!-- View Receipt Button -->
                      <button
                        @click="openReceipt(order)"
                        class="p-1.5 rounded-lg border border-slate-200 dark:border-slate-700 text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800 transition cursor-pointer"
                        title="View Receipt"
                      >
                        <Receipt class="w-4 h-4" />
                      </button>
                    </div>
                  </td>
                </tr>

                <!-- Expandable Order Line Items Row -->
                <tr v-if="expandedOrders[order.id]" class="bg-slate-50/80 dark:bg-slate-900/60">
                  <td colspan="8" class="p-4 sm:px-8 border-t border-b border-slate-100 dark:border-slate-800">
                    <div class="bg-white dark:bg-slate-800/80 rounded-xl p-4 border border-slate-200 dark:border-slate-700/60 shadow-xs">
                      <div class="flex items-center justify-between mb-3 border-b pb-2 dark:border-slate-700">
                        <span class="text-xs font-bold text-slate-700 dark:text-slate-300">
                          Order Breakdown (#{{ order.order_number }})
                        </span>
                        <span v-if="order.notes" class="text-xs text-amber-600 dark:text-amber-400 font-medium">
                          Note: "{{ order.notes }}"
                        </span>
                      </div>
                      <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-3">
                        <div
                          v-for="item in order.items"
                          :key="item.id"
                          class="flex items-center justify-between p-2.5 bg-slate-50 dark:bg-slate-900/50 rounded-lg border border-slate-100 dark:border-slate-800 text-xs"
                        >
                          <span class="font-medium text-slate-800 dark:text-slate-200">
                            {{ item.quantity }}x {{ item.name }}
                          </span>
                          <span class="font-bold text-slate-900 dark:text-white">
                            {{ formatCurrency(item.total) }}
                          </span>
                        </div>
                      </div>
                      <div class="flex justify-end gap-6 mt-4 pt-3 border-t text-xs dark:border-slate-700">
                        <span class="text-slate-500">Subtotal: <strong>{{ formatCurrency(order.subtotal) }}</strong></span>
                        <span class="text-slate-500">Tax: <strong>{{ formatCurrency(order.tax) }}</strong></span>
                        <span class="text-slate-900 dark:text-white font-black">Total: {{ formatCurrency(order.total) }}</span>
                      </div>
                    </div>
                  </td>
                </tr>
              </template>
            </tbody>
          </table>
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
            class="text-sm text-blue-600 hover:text-blue-700 flex items-center gap-1 cursor-pointer"
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
                    class="text-sm text-blue-600 hover:text-blue-700 dark:text-blue-400 cursor-pointer"
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
            class="text-sm text-blue-600 hover:text-blue-700 flex items-center gap-1 cursor-pointer"
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

    <!-- ========================================================================= -->
    <!-- SETTLE & CLEAR CONFIRMATION MODAL -->
    <!-- ========================================================================= -->
    <div
      v-if="showSettleModal && settleOrder"
      class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-sm"
    >
      <div class="bg-white dark:bg-slate-900 rounded-2xl max-w-md w-full border border-slate-200 dark:border-slate-800 shadow-2xl p-6 overflow-hidden">
        <div class="flex items-center justify-between pb-3 border-b dark:border-slate-800">
          <div class="flex items-center gap-2">
            <div class="w-8 h-8 rounded-lg bg-amber-500/10 text-amber-600 flex items-center justify-center">
              <DollarSign class="w-4 h-4" />
            </div>
            <h3 class="text-base font-black text-slate-900 dark:text-white">
              Settle & Clear Order
            </h3>
          </div>
          <button @click="showSettleModal = false" class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-200">
            <X class="w-5 h-5" />
          </button>
        </div>

        <div class="py-4 space-y-4 text-xs">
          <!-- Order summary box -->
          <div class="p-3 bg-slate-50 dark:bg-slate-800 rounded-xl border border-slate-100 dark:border-slate-700/60 flex items-center justify-between">
            <div>
              <p class="font-bold text-slate-800 dark:text-slate-200">#{{ settleOrder.order_number }}</p>
              <p class="text-slate-500">{{ settleOrder.table_number || 'Walk-in' }} • {{ settleOrder.guest_name }}</p>
            </div>
            <div class="text-right">
              <span class="text-xs text-slate-400 uppercase font-bold">Total Due</span>
              <p class="text-base font-black text-emerald-600 dark:text-emerald-400">
                {{ formatCurrency(settleOrder.total) }}
              </p>
            </div>
          </div>

          <!-- Select Payment Method -->
          <div>
            <label class="block font-bold text-slate-700 dark:text-slate-300 mb-2">Payment Collection Method</label>
            <div class="grid grid-cols-3 gap-2">
              <button
                type="button"
                @click="settleMethod = 'cash'"
                class="p-2.5 rounded-xl border text-center font-bold transition flex flex-col items-center gap-1 cursor-pointer"
                :class="settleMethod === 'cash'
                  ? 'border-emerald-500 bg-emerald-50 dark:bg-emerald-950/40 text-emerald-700 dark:text-emerald-300'
                  : 'border-slate-200 dark:border-slate-700 text-slate-600 dark:text-slate-400'"
              >
                <Wallet class="w-4 h-4" />
                <span>Cash</span>
              </button>
              <button
                type="button"
                @click="settleMethod = 'card'"
                class="p-2.5 rounded-xl border text-center font-bold transition flex flex-col items-center gap-1 cursor-pointer"
                :class="settleMethod === 'card'
                  ? 'border-blue-500 bg-blue-50 dark:bg-blue-950/40 text-blue-700 dark:text-blue-300'
                  : 'border-slate-200 dark:border-slate-700 text-slate-600 dark:text-slate-400'"
              >
                <CreditCard class="w-4 h-4" />
                <span>Card (POS)</span>
              </button>
              <button
                type="button"
                @click="settleMethod = 'bank_transfer'"
                class="p-2.5 rounded-xl border text-center font-bold transition flex flex-col items-center gap-1 cursor-pointer"
                :class="settleMethod === 'bank_transfer'
                  ? 'border-purple-500 bg-purple-50 dark:bg-purple-950/40 text-purple-700 dark:text-purple-300'
                  : 'border-slate-200 dark:border-slate-700 text-slate-600 dark:text-slate-400'"
              >
                <Building2 class="w-4 h-4" />
                <span>Transfer</span>
              </button>
            </div>
          </div>

          <!-- Release Table Note -->
          <div class="p-3 bg-blue-50 dark:bg-blue-950/40 border border-blue-200 dark:border-blue-800/40 rounded-xl flex items-start gap-2.5 text-blue-800 dark:text-blue-300">
            <CheckCircle2 class="w-4 h-4 flex-shrink-0 mt-0.5 text-blue-600" />
            <p>
              Clearing this order will record the payment as <strong>{{ settleMethod.toUpperCase() }}</strong>, update the order status to Served, and automatically release <strong>{{ settleOrder.table_number || 'the table' }}</strong> back to <strong>Available</strong>.
            </p>
          </div>
        </div>

        <div class="flex items-center justify-end gap-3 pt-4 border-t dark:border-slate-800">
          <button
            @click="showSettleModal = false"
            class="px-4 py-2 rounded-xl text-xs font-bold text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800 transition cursor-pointer"
          >
            Cancel
          </button>
          <button
            @click="confirmSettleAndClear"
            :disabled="isSubmittingSettle"
            class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl text-xs font-bold bg-emerald-600 hover:bg-emerald-700 text-white shadow-md shadow-emerald-600/30 transition cursor-pointer disabled:opacity-50"
          >
            <RefreshCw v-if="isSubmittingSettle" class="w-4 h-4 animate-spin" />
            <Check v-else class="w-4 h-4" />
            <span>Confirm Payment & Clear</span>
          </button>
        </div>
      </div>
    </div>

    <!-- ========================================================================= -->
    <!-- PRINTABLE RECEIPT MODAL -->
    <!-- ========================================================================= -->
    <div
      v-if="showReceiptModal && receiptOrder"
      class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-sm"
    >
      <div class="bg-white dark:bg-slate-900 rounded-2xl max-w-md w-full border border-slate-200 dark:border-slate-800 shadow-2xl p-6 overflow-hidden">
        
        <!-- Header -->
        <div class="flex items-center justify-between pb-3 border-b dark:border-slate-800">
          <div class="flex items-center gap-2">
            <Receipt class="w-5 h-5 text-emerald-600" />
            <h3 class="text-base font-black text-slate-900 dark:text-white">Order Receipt</h3>
          </div>
          <button @click="showReceiptModal = false" class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-200">
            <X class="w-5 h-5" />
          </button>
        </div>

        <!-- Receipt Card (Print Target) -->
        <div id="printable-receipt" class="my-4 p-4 bg-slate-50 dark:bg-slate-800/60 rounded-xl border border-dashed border-slate-200 dark:border-slate-700 font-mono text-xs text-slate-800 dark:text-slate-200 space-y-3">
          <div class="text-center border-b pb-3 dark:border-slate-700">
            <h4 class="font-bold text-sm tracking-wide">{{ hotelStore.hotelName || 'HOTEL & RESTAURANT' }}</h4>
            <p class="text-[10px] text-slate-500">Official Customer Invoice</p>
            <p class="text-[10px] text-slate-400 mt-1">Order #{{ receiptOrder.order_number }}</p>
            <p class="text-[10px] text-slate-400">{{ formatDate(receiptOrder.order_time) }}</p>
          </div>

          <div class="flex justify-between text-[11px]">
            <span>Guest: <strong>{{ receiptOrder.guest_name }}</strong></span>
            <span>Location: <strong>{{ receiptOrder.table_number || receiptOrder.room_number || 'Walk-in' }}</strong></span>
          </div>

          <!-- Items Table -->
          <div class="border-t border-b py-2 dark:border-slate-700 space-y-1">
            <div
              v-for="item in receiptOrder.items"
              :key="item.id"
              class="flex justify-between text-[11px]"
            >
              <span>{{ item.quantity }}x {{ item.name }}</span>
              <span>{{ formatCurrency(item.total) }}</span>
            </div>
          </div>

          <!-- Financial Breakdown -->
          <div class="space-y-1 text-[11px]">
            <div class="flex justify-between text-slate-500">
              <span>Subtotal:</span>
              <span>{{ formatCurrency(receiptOrder.subtotal) }}</span>
            </div>
            <div class="flex justify-between text-slate-500">
              <span>Tax / VAT:</span>
              <span>{{ formatCurrency(receiptOrder.tax) }}</span>
            </div>
            <div v-if="receiptOrder.service_charge > 0" class="flex justify-between text-slate-500">
              <span>Service Charge:</span>
              <span>{{ formatCurrency(receiptOrder.service_charge) }}</span>
            </div>
            <div class="flex justify-between font-bold text-sm pt-2 border-t dark:border-slate-700 text-slate-900 dark:text-white">
              <span>TOTAL:</span>
              <span>{{ formatCurrency(receiptOrder.total) }}</span>
            </div>
          </div>

          <div class="text-center pt-2 text-[10px] text-slate-400 border-t dark:border-slate-700">
            Status: <span class="uppercase font-bold" :class="receiptOrder.payment_status === 'paid' ? 'text-emerald-600' : 'text-rose-600'">{{ receiptOrder.payment_status }}</span> • Thank you for dining with us!
          </div>
        </div>

        <!-- Modal Actions -->
        <div class="flex items-center justify-end gap-3 pt-3 border-t dark:border-slate-800">
          <button
            @click="showReceiptModal = false"
            class="px-4 py-2 rounded-xl text-xs font-bold text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800 transition cursor-pointer"
          >
            Close
          </button>
          <button
            @click="printReceipt"
            class="inline-flex items-center gap-2 px-4 py-2 rounded-xl text-xs font-bold bg-blue-600 hover:bg-blue-700 text-white shadow-md shadow-blue-600/30 transition cursor-pointer"
          >
            <Printer class="w-4 h-4" />
            <span>Print Receipt</span>
          </button>
        </div>
      </div>
    </div>

  </DashboardLayout>
</template>

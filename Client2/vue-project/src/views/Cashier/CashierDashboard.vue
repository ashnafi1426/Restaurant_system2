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
  ChevronLeft,
  ChevronRight,
  X,
  AlertCircle,
  Check,
  Radio,
  Wifi,
  WifiOff,
  Filter,
  RotateCcw,
  Maximize2,
  Minimize2,
} from 'lucide-vue-next'

const router = useRouter()
const cashierStore = useCashierStore()
const hotelStore = useHotelStore()
const auth = useAuthStore()
const languageStore = useLanguageStore()

const currency = computed(() => hotelStore.currentHotel?.currency || 'ETB')

// WebSocket Live State
const isWsConnected = ref(false)
const wsOrdersChannel = ref<any>(null)
const wsPaymentsChannel = ref<any>(null)

// Order filters, pagination & UI states
const orderFilter = ref<'all' | 'paid' | 'unpaid' | 'cleared'>('all')
const orderSearch = ref('')
const perPageOptions = [5, 10, 20, 30, 50]
const perPage = ref(10)
const currentPage = ref(1)
const isClearingId = ref<string | null>(null)
const expandedOrders = ref<Record<string, boolean>>({})

// Advanced Filter & Fullscreen states
const isFilterOpen = ref(false)
const isFullscreen = ref(false)

const filters = ref({
  payment_status: 'all',
  order_status: 'all',
  order_type: 'all',
  payment_method: 'all',
})

// Number of active advanced filters
const activeFilterCount = computed(() => {
  let count = 0
  if (filters.value.payment_status && filters.value.payment_status !== 'all') count++
  if (filters.value.order_status && filters.value.order_status !== 'all') count++
  if (filters.value.order_type && filters.value.order_type !== 'all') count++
  if (filters.value.payment_method && filters.value.payment_method !== 'all') count++
  return count
})

function triggerFetchOrders() {
  cashierStore.fetchOrders({
    filter: orderFilter.value,
    search: orderSearch.value,
    page: currentPage.value,
    per_page: perPage.value,
    payment_status: filters.value.payment_status,
    order_status: filters.value.order_status,
    order_type: filters.value.order_type,
    payment_method: filters.value.payment_method,
  })
}

function selectQuickTab(tab: 'all' | 'paid' | 'unpaid' | 'cleared') {
  orderFilter.value = tab
  filters.value.payment_status = tab
  currentPage.value = 1
  triggerFetchOrders()
}

function onFilterChange() {
  if (['all', 'paid', 'unpaid', 'cleared'].includes(filters.value.payment_status)) {
    orderFilter.value = filters.value.payment_status as any
  }
  currentPage.value = 1
  triggerFetchOrders()
}

function resetFilters() {
  orderSearch.value = ''
  orderFilter.value = 'all'
  filters.value = {
    payment_status: 'all',
    order_status: 'all',
    order_type: 'all',
    payment_method: 'all',
  }
  currentPage.value = 1
  triggerFetchOrders()
}

function toggleFilter() {
  isFilterOpen.value = !isFilterOpen.value
}

function toggleFullscreen() {
  isFullscreen.value = !isFullscreen.value
}

// Visible page numbers for pagination
const visiblePages = computed(() => {
  const current = cashierStore.orderPagination?.current_page || 1
  const last = cashierStore.orderPagination?.last_page || 1
  if (last <= 7) {
    return Array.from({ length: last }, (_, i) => i + 1)
  }
  const pages: number[] = []
  pages.push(1)
  if (current > 3) pages.push(-1)
  const start = Math.max(2, current - 1)
  const end = Math.min(last - 1, current + 1)
  for (let i = start; i <= end; i++) {
    pages.push(i)
  }
  if (current < last - 2) pages.push(-1)
  pages.push(last)
  return pages
})

function handlePageChange(page: number) {
  if (page < 1 || page > (cashierStore.orderPagination?.last_page || 1)) return
  currentPage.value = page
  triggerFetchOrders()
}

function handlePerPageChange(limit: number) {
  perPage.value = limit
  currentPage.value = 1
  triggerFetchOrders()
}

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
  }, 4500)
}

// WebSocket Setup
function setupWebSocket() {
  if (typeof window === 'undefined' || !window.Echo || !hotelStore.hotelId) {
    return
  }

  try {
    const ordersChannelName = `hotel.${hotelStore.hotelId}.orders`
    const paymentsChannelName = `payments.${hotelStore.hotelId}`

    // Monitor connection state
    if (window.Echo.connector?.pusher) {
      const conn = window.Echo.connector.pusher.connection
      isWsConnected.value = conn.state === 'connected'

      conn.bind('connected', () => {
        isWsConnected.value = true
      })
      conn.bind('disconnected', () => {
        isWsConnected.value = false
      })
      conn.bind('error', () => {
        isWsConnected.value = false
      })
    }

    // 1. Listen for order events
    wsOrdersChannel.value = window.Echo.private(ordersChannelName)

    wsOrdersChannel.value.listen('.OrderCreated', (event: any) => {
      const location = event.table_number || (event.room_number ? 'Room ' + event.room_number : 'Dining')
      showToast(
        `🔔 New Order #${event.order_number || ''} placed for ${location}!`,
        'success'
      )
      cashierStore.fetchOrders({ filter: orderFilter.value, search: orderSearch.value })
      cashierStore.fetchDashboardStats()
    })

    wsOrdersChannel.value.listen('.OrderStatusUpdated', (event: any) => {
      // Refresh active orders list and stats
      cashierStore.fetchOrders({ filter: orderFilter.value, search: orderSearch.value })
      cashierStore.fetchDashboardStats()
    })

    // 2. Listen for payment events
    wsPaymentsChannel.value = window.Echo.private(paymentsChannelName)

    wsPaymentsChannel.value.listen('.PaymentStatusUpdated', (event: any) => {
      if (event.payment_status === 'paid' || event.payment_status === 'verified') {
        showToast(
          `💰 Payment verified for Order #${event.order_number || event.order_id || ''}!`,
          'success'
        )
      }
      cashierStore.fetchOrders({ filter: orderFilter.value, search: orderSearch.value })
      cashierStore.fetchDashboardStats()
    })

    wsPaymentsChannel.value.listen('.PaymentVerified', () => {
      showToast('💰 Payment verified successfully!', 'success')
      cashierStore.fetchOrders({ filter: orderFilter.value, search: orderSearch.value })
      cashierStore.fetchDashboardStats()
    })

    wsPaymentsChannel.value.listen('.PaymentInitialized', () => {
      cashierStore.fetchOrders({ filter: orderFilter.value, search: orderSearch.value })
    })

    wsPaymentsChannel.value.listen('.OrderCompleted', () => {
      cashierStore.fetchOrders({ filter: orderFilter.value, search: orderSearch.value })
      cashierStore.fetchDashboardStats()
    })
  } catch (err) {
    console.error('[CashierDashboard] WebSocket setup failed:', err)
  }
}

function cleanupWebSocket() {
  if (wsOrdersChannel.value) {
    wsOrdersChannel.value.stopListening('.OrderCreated')
    wsOrdersChannel.value.stopListening('.OrderStatusUpdated')
    if (hotelStore.hotelId) {
      window.Echo?.leave(`hotel.${hotelStore.hotelId}.orders`)
    }
    wsOrdersChannel.value = null
  }
  if (wsPaymentsChannel.value) {
    wsPaymentsChannel.value.stopListening('.PaymentStatusUpdated')
    wsPaymentsChannel.value.stopListening('.PaymentVerified')
    wsPaymentsChannel.value.stopListening('.PaymentInitialized')
    wsPaymentsChannel.value.stopListening('.OrderCompleted')
    if (hotelStore.hotelId) {
      window.Echo?.leave(`payments.${hotelStore.hotelId}`)
    }
    wsPaymentsChannel.value = null
  }
  isWsConnected.value = false
}

// Background polling fallback (every 30s)
let refreshInterval: any = null

onMounted(() => {
  cashierStore.loadDashboard()
  triggerFetchOrders()
  setupWebSocket()
  window.addEventListener('keydown', handleKeydown)
  refreshInterval = setInterval(() => {
    triggerFetchOrders()
  }, 30000)
})

onUnmounted(() => {
  cleanupWebSocket()
  window.removeEventListener('keydown', handleKeydown)
  if (refreshInterval) clearInterval(refreshInterval)
  if (toastTimeout) clearTimeout(toastTimeout)
})

// Watch hotel switch to reload dashboard data for selected tenant
watch(() => hotelStore.hotelId, () => {
  cleanupWebSocket()
  currentPage.value = 1
  cashierStore.loadDashboard()
  triggerFetchOrders()
  setupWebSocket()
})

// Debounced search watcher
let searchDebounceTimer: any = null
watch(orderSearch, () => {
  if (searchDebounceTimer) clearTimeout(searchDebounceTimer)
  searchDebounceTimer = setTimeout(() => {
    currentPage.value = 1
    triggerFetchOrders()
  }, 300)
})

// Cashier operational KPI cards
const stats = computed(() => [
  {
    title: languageStore.t("Today's Revenue", "Today's Revenue"),
    value: `${cashierStore.todayRevenue.toFixed(2)} ${currency.value}`,
    subtitle: `${cashierStore.dashboardStats?.today_transactions ?? 0} settled today`,
    icon: DollarSign,
    color: 'bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 border border-emerald-500/20',
    action: null,
  },
  {
    title: languageStore.t('active_orders', 'Active Orders'),
    value: cashierStore.orderCounts?.total_active ?? 0,
    subtitle: 'Dining in restaurant',
    icon: Utensils,
    color: 'bg-blue-500/10 text-blue-600 dark:text-blue-400 border border-blue-500/20',
    action: () => { selectQuickTab('all') },
  },
  {
    title: languageStore.t('unpaid_orders', 'Unpaid Orders'),
    value: cashierStore.orderCounts?.unpaid_orders ?? 0,
    subtitle: 'Pending payment',
    icon: Clock,
    color: 'bg-amber-500/10 text-amber-600 dark:text-amber-400 border border-amber-500/20',
    action: () => { selectQuickTab('unpaid') },
  },
  {
    title: languageStore.t('cleared_today', 'Cleared Today'),
    value: cashierStore.orderCounts?.cleared_today ?? 0,
    subtitle: 'Freed tables',
    icon: CheckCircle,
    color: 'bg-teal-500/10 text-teal-600 dark:text-teal-400 border border-teal-500/20',
    action: () => { selectQuickTab('cleared') },
  },
])

const weeklyRevenue = computed(() => cashierStore.weeklyRevenue.toFixed(2))
const monthlyRevenue = computed(() => cashierStore.monthlyRevenue.toFixed(2))

// Filtered orders computed
const filteredOrders = computed(() => {
  let list = cashierStore.activeOrders || []
  if (orderSearch.value.trim()) {
    const q = orderSearch.value.toLowerCase().trim()
    list = list.filter((o: any) =>
      o.order_number?.toLowerCase().includes(q) ||
      o.table_number?.toLowerCase().includes(q) ||
      o.guest_name?.toLowerCase().includes(q) ||
      o.guest_phone?.toLowerCase().includes(q) ||
      o.room_number?.toLowerCase().includes(q)
    )
  }
  if (filters.value.order_status && filters.value.order_status !== 'all') {
    list = list.filter((o: any) => o.status?.toLowerCase() === filters.value.order_status.toLowerCase())
  }
  if (filters.value.order_type && filters.value.order_type !== 'all') {
    list = list.filter((o: any) => o.order_type?.toLowerCase() === filters.value.order_type.toLowerCase())
  }
  if (filters.value.payment_method && filters.value.payment_method !== 'all') {
    list = list.filter((o: any) =>
      (o.payment_method || o.payment_type)?.toLowerCase() === filters.value.payment_method.toLowerCase()
    )
  }
  return list
})

// Format currency
const formatCurrency = (amount: number | string) => {
  const numAmount = typeof amount === 'string' ? parseFloat(amount) : amount
  return `${(numAmount || 0).toFixed(2)} ${currency.value}`
}

// Format date safely
const formatDate = (date: string | null | undefined) => {
  if (!date) return '—'
  try {
    const d = new Date(date)
    if (isNaN(d.getTime())) return String(date)
    return d.toLocaleDateString('en-US', {
      month: 'short',
      day: 'numeric',
      hour: '2-digit',
      minute: '2-digit',
    })
  } catch {
    return String(date)
  }
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
  if (!order) return
  receiptOrder.value = { ...order }
  showReceiptModal.value = true
}

// Print Receipt
function printReceipt() {
  window.print()
}

// Keyboard shortcut listener to close modals
function handleKeydown(e: KeyboardEvent) {
  if (e.key === 'Escape') {
    showReceiptModal.value = false
    showSettleModal.value = false
  }
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
    <div class="space-y-3.5 bg-slate-50 dark:bg-slate-950 min-h-screen p-3 sm:p-4.5 max-w-full overflow-hidden font-sans">
      
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

      <!-- Cashier Operational KPI Cards (Compact Modern Real-World POS Bar) -->
      <div class="grid grid-cols-2 lg:grid-cols-4 gap-2.5 sm:gap-3.5">
        <div
          v-for="stat in stats"
          :key="stat.title"
          class="bg-white dark:bg-slate-900 rounded-xl border border-slate-200/90 dark:border-slate-800/90 shadow-xs p-3 sm:p-3.5 transition-all group"
          :class="{ 'cursor-pointer hover:border-emerald-500/50 dark:hover:border-emerald-500/50 hover:shadow-xs': stat.action }"
          @click="stat.action ? stat.action() : null"
        >
          <div class="flex items-center justify-between gap-2">
            <div class="min-w-0 flex-1">
              <p class="text-[10px] sm:text-[11px] font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider">
                {{ stat.title }}
              </p>
              <h2 class="text-lg sm:text-xl font-black text-slate-900 dark:text-white tracking-tight truncate mt-0.5">
                {{ stat.value }}
              </h2>
              <p v-if="stat.subtitle" class="text-[10px] text-slate-400 dark:text-slate-500 truncate mt-0.5">
                {{ stat.subtitle }}
              </p>
            </div>
            <div :class="[stat.color, 'w-8 h-8 sm:w-9 sm:h-9 rounded-lg flex items-center justify-center flex-shrink-0 transition-transform group-hover:scale-105']">
              <component :is="stat.icon" :size="16" />
            </div>
          </div>
        </div>
      </div>

      <!-- ========================================================================= -->
      <!-- CUSTOMER ORDERS & TABLE SETTLEMENT SECTION (Compact Real-World POS Table) -->
      <!-- ========================================================================= -->
      <!-- CUSTOMER ORDERS & TABLE SETTLEMENT SECTION (Compact Real-World POS Table) -->
      <!-- ========================================================================= -->
      <div
        class="bg-white dark:bg-slate-900 rounded-xl border border-slate-200/90 dark:border-slate-800/90 shadow-xs overflow-hidden transition-all"
        :class="{ 'fixed inset-0 z-50 p-4 sm:p-6 overflow-y-auto rounded-none': isFullscreen }"
      >
        
        <!-- Section Header Bar -->
        <div class="p-3 sm:p-3.5 border-b border-slate-100 dark:border-slate-800 bg-gradient-to-r from-emerald-500/5 via-blue-500/5 to-transparent">
          <div class="flex items-center justify-between gap-3">
            <div class="flex items-center gap-2 min-w-0">
              <div class="w-7 h-7 rounded-lg bg-emerald-500/10 flex items-center justify-center text-emerald-600 dark:text-emerald-400 flex-shrink-0">
                <Utensils class="w-3.5 h-3.5" />
              </div>
              <h2 class="text-sm sm:text-base font-black text-slate-900 dark:text-white truncate">
                {{ languageStore.t('customer_orders_clearing', 'Customer Orders & Table Settlement') }}
              </h2>
              <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-100 dark:bg-emerald-950 text-emerald-700 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-800 flex-shrink-0">
                {{ cashierStore.orderCounts?.total_active ?? 0 }} Active
              </span>
            </div>

            <!-- Controls: Live WebSocket Status -->
            <div class="flex items-center gap-2 flex-shrink-0">
              <!-- Live WebSocket Status Pill -->
              <span
                v-if="isWsConnected"
                class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg text-xs font-bold bg-emerald-50 dark:bg-emerald-950/50 text-emerald-700 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-800/60"
                title="Real-time WebSocket active via Laravel Reverb"
              >
                <span class="relative flex h-2 w-2">
                  <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span>
                  <span class="relative inline-flex rounded-full h-2 w-2 bg-emerald-500"></span>
                </span>
                <span>Live Sync</span>
              </span>
              <span
                v-else
                class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg text-xs font-bold bg-amber-50 dark:bg-amber-950/50 text-amber-700 dark:text-amber-400 border border-amber-200 dark:border-amber-800/60"
                title="Connecting to WebSocket server..."
              >
                <span class="h-2 w-2 rounded-full bg-amber-400 animate-pulse"></span>
                <span>Connecting...</span>
              </span>
            </div>
          </div>

          <!-- Toolbar (Matching User's Screenshot: Search + Filter + Actions) -->
          <div class="mt-2.5 flex flex-wrap items-center justify-between gap-2.5">
            <!-- Left: Search Input & Filter Button -->
            <div class="flex flex-1 items-center gap-2 min-w-[260px] max-w-xl">
              <div class="relative flex-1">
                <Search class="absolute left-3 top-1/2 -translate-y-1/2 w-3.5 h-3.5 text-slate-400" />
                <input
                  v-model="orderSearch"
                  type="text"
                  placeholder="Search order #, table #, guest name, phone, room..."
                  class="w-full pl-9 pr-7 py-1.5 bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-lg text-xs text-slate-800 dark:text-slate-200 focus:outline-none focus:ring-2 focus:ring-emerald-500"
                />
                <button
                  v-if="orderSearch"
                  @click="orderSearch = ''"
                  class="absolute right-2.5 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600"
                >
                  <X class="w-3.5 h-3.5" />
                </button>
              </div>

              <!-- Filter Toggle Button (Matching Screenshot) -->
              <button
                type="button"
                @click="toggleFilter"
                class="inline-flex items-center gap-1.5 rounded-lg px-3 py-1.5 text-xs font-bold transition cursor-pointer flex-shrink-0"
                :class="[
                  isFilterOpen || activeFilterCount > 0
                    ? 'bg-emerald-600/10 dark:bg-emerald-900/30 text-emerald-600 dark:text-emerald-400 border border-emerald-500/40 shadow-xs'
                    : 'border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-700 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-700'
                ]"
              >
                <component :is="isFilterOpen ? X : Filter" class="w-3.5 h-3.5" />
                <span>{{ isFilterOpen ? 'Hide Filter' : 'Filter' }}</span>
                <span
                  v-if="activeFilterCount > 0"
                  class="px-1.5 py-0.2 rounded-full text-[10px] bg-emerald-600 text-white font-black"
                >
                  {{ activeFilterCount }}
                </span>
              </button>
            </div>

            <!-- Right: Quick Status Pills & Action Buttons (Refresh + Fullscreen) -->
            <div class="flex items-center gap-2">
              <!-- Quick Status Tabs -->
              <div class="flex items-center p-0.5 bg-slate-100 dark:bg-slate-800/80 rounded-lg overflow-x-auto">
                <button
                  @click="selectQuickTab('all')"
                  class="px-2 py-1 rounded-md text-xs font-bold transition flex items-center gap-1"
                  :class="orderFilter === 'all' && filters.payment_status === 'all'
                    ? 'bg-white dark:bg-slate-900 text-slate-900 dark:text-white shadow-xs'
                    : 'text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white'"
                >
                  <span>All</span>
                  <span class="px-1.5 py-0.2 rounded-full text-[10px] bg-slate-200/70 dark:bg-slate-800 text-slate-700 dark:text-slate-300 font-semibold">
                    {{ cashierStore.orderCounts?.total_active ?? 0 }}
                  </span>
                </button>
                <button
                  @click="selectQuickTab('paid')"
                  class="px-2 py-1 rounded-md text-xs font-bold transition flex items-center gap-1"
                  :class="orderFilter === 'paid' || filters.payment_status === 'paid'
                    ? 'bg-emerald-600 text-white shadow-xs'
                    : 'text-emerald-700 dark:text-emerald-400 hover:bg-emerald-50 dark:hover:bg-emerald-950/40'"
                >
                  <Sparkles class="w-3 h-3" />
                  <span>Paid</span>
                  <span class="px-1.5 py-0.2 rounded-full text-[10px]" :class="(orderFilter === 'paid' || filters.payment_status === 'paid') ? 'bg-emerald-700 text-white' : 'bg-emerald-100 dark:bg-emerald-950/80 text-emerald-800 dark:text-emerald-300'">
                    {{ cashierStore.orderCounts?.paid_orders ?? 0 }}
                  </span>
                </button>
                <button
                  @click="selectQuickTab('unpaid')"
                  class="px-2 py-1 rounded-md text-xs font-bold transition flex items-center gap-1"
                  :class="orderFilter === 'unpaid' || filters.payment_status === 'unpaid'
                    ? 'bg-amber-600 text-white shadow-xs'
                    : 'text-amber-700 dark:text-amber-400 hover:bg-amber-50 dark:hover:bg-amber-950/40'"
                >
                  <Clock class="w-3 h-3" />
                  <span>Unpaid</span>
                  <span class="px-1.5 py-0.2 rounded-full text-[10px]" :class="(orderFilter === 'unpaid' || filters.payment_status === 'unpaid') ? 'bg-amber-700 text-white' : 'bg-amber-100 dark:bg-amber-950/80 text-amber-800 dark:text-amber-300'">
                    {{ cashierStore.orderCounts?.unpaid_orders ?? 0 }}
                  </span>
                </button>
                <button
                  @click="selectQuickTab('cleared')"
                  class="px-2 py-1 rounded-md text-xs font-bold transition flex items-center gap-1"
                  :class="orderFilter === 'cleared' || filters.payment_status === 'cleared'
                    ? 'bg-slate-700 text-white shadow-xs'
                    : 'text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white'"
                >
                  <Check class="w-3 h-3" />
                  <span>Cleared</span>
                  <span class="px-1.5 py-0.2 rounded-full text-[10px]" :class="(orderFilter === 'cleared' || filters.payment_status === 'cleared') ? 'bg-slate-800 text-slate-200' : 'bg-slate-200/70 dark:bg-slate-800 text-slate-700 dark:text-slate-300'">
                    {{ cashierStore.orderCounts?.cleared_today ?? 0 }}
                  </span>
                </button>
              </div>

              <!-- Refresh Button (Matching Screenshot) -->
              <button
                type="button"
                @click="refreshDashboard"
                :disabled="cashierStore.isLoading"
                title="Refresh"
                class="inline-flex items-center justify-center h-8 w-8 rounded-lg border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-700 hover:text-slate-900 dark:hover:text-white transition disabled:opacity-50 cursor-pointer shadow-xs"
              >
                <RefreshCw class="w-3.5 h-3.5" :class="{ 'animate-spin': cashierStore.isLoading }" />
              </button>

              <!-- Fullscreen Button (Matching Screenshot) -->
              <button
                type="button"
                @click="toggleFullscreen"
                title="Toggle Fullscreen"
                class="inline-flex items-center justify-center h-8 w-8 rounded-lg border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-700 hover:text-slate-900 dark:hover:text-white transition cursor-pointer shadow-xs"
              >
                <component :is="isFullscreen ? Minimize2 : Maximize2" class="w-3.5 h-3.5" />
              </button>
            </div>
          </div>

          <!-- Expandable Filter Panel (Multi-Criteria Filtering by Status and More) -->
          <Transition
            enter-active-class="transition duration-200 ease-out"
            enter-from-class="transform -translate-y-2 opacity-0 scale-98"
            enter-to-class="transform translate-y-0 opacity-100 scale-100"
            leave-active-class="transition duration-150 ease-in"
            leave-from-class="transform translate-y-0 opacity-100 scale-100"
            leave-to-class="transform -translate-y-2 opacity-0 scale-98"
          >
            <div
              v-if="isFilterOpen"
              class="mt-3 p-3.5 sm:p-4 rounded-xl border border-slate-200 dark:border-slate-700/80 bg-slate-50/80 dark:bg-slate-800/60 shadow-xs space-y-3"
            >
              <div class="flex items-center justify-between border-b pb-2 dark:border-slate-700">
                <div class="flex items-center gap-1.5 text-xs font-black text-slate-900 dark:text-white">
                  <Filter class="w-3.5 h-3.5 text-emerald-600 dark:text-emerald-400" />
                  <span>Filter Orders by Criteria</span>
                  <span v-if="activeFilterCount > 0" class="text-[10px] text-emerald-600 dark:text-emerald-400 font-bold">
                    ({{ activeFilterCount }} active)
                  </span>
                </div>
                <button
                  type="button"
                  @click="resetFilters"
                  class="inline-flex items-center gap-1 text-[11px] font-bold text-slate-500 hover:text-rose-600 transition cursor-pointer"
                >
                  <RotateCcw class="w-3 h-3" />
                  <span>Reset Filters</span>
                </button>
              </div>

              <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3">
                <!-- 1. Payment Status Filter -->
                <div>
                  <label class="mb-1 block text-[11px] font-bold text-slate-600 dark:text-slate-300 uppercase tracking-wide">
                    Payment Status
                  </label>
                  <select
                    v-model="filters.payment_status"
                    @change="onFilterChange"
                    class="w-full rounded-lg border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-white px-2.5 py-1.5 text-xs focus:ring-2 focus:ring-emerald-500 font-medium outline-none cursor-pointer"
                  >
                    <option value="all">All Payment Statuses</option>
                    <option value="unpaid">Unpaid (Needs Settle)</option>
                    <option value="paid">Paid (Ready to Clear)</option>
                    <option value="cleared">Cleared Tables</option>
                  </select>
                </div>

                <!-- 2. Kitchen / Order Status Filter -->
                <div>
                  <label class="mb-1 block text-[11px] font-bold text-slate-600 dark:text-slate-300 uppercase tracking-wide">
                    Kitchen / Order Status
                  </label>
                  <select
                    v-model="filters.order_status"
                    @change="onFilterChange"
                    class="w-full rounded-lg border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-white px-2.5 py-1.5 text-xs focus:ring-2 focus:ring-emerald-500 font-medium outline-none cursor-pointer"
                  >
                    <option value="all">All Order Statuses</option>
                    <option value="pending">Pending</option>
                    <option value="preparing">Preparing</option>
                    <option value="ready">Ready for Pickup</option>
                    <option value="served">Served / Cleared</option>
                    <option value="cancelled">Cancelled</option>
                  </select>
                </div>

                <!-- 3. Dining Area / Order Type Filter -->
                <div>
                  <label class="mb-1 block text-[11px] font-bold text-slate-600 dark:text-slate-300 uppercase tracking-wide">
                    Order Location / Type
                  </label>
                  <select
                    v-model="filters.order_type"
                    @change="onFilterChange"
                    class="w-full rounded-lg border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-white px-2.5 py-1.5 text-xs focus:ring-2 focus:ring-emerald-500 font-medium outline-none cursor-pointer"
                  >
                    <option value="all">All Order Types</option>
                    <option value="dine_in">Dine In (Restaurant Table)</option>
                    <option value="room_service">Room Service (Hotel Room)</option>
                    <option value="takeaway">Takeaway / Delivery</option>
                    <option value="walk_in">Walk-in Guest</option>
                  </select>
                </div>

                <!-- 4. Payment Method Filter -->
                <div>
                  <label class="mb-1 block text-[11px] font-bold text-slate-600 dark:text-slate-300 uppercase tracking-wide">
                    Payment Method
                  </label>
                  <select
                    v-model="filters.payment_method"
                    @change="onFilterChange"
                    class="w-full rounded-lg border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-white px-2.5 py-1.5 text-xs focus:ring-2 focus:ring-emerald-500 font-medium outline-none cursor-pointer"
                  >
                    <option value="all">All Payment Methods</option>
                    <option value="cash">Cash</option>
                    <option value="card">Card / POS</option>
                    <option value="room_charge">Room Charge</option>
                    <option value="telebirr">Telebirr</option>
                    <option value="cbe">CBE Birr / Bank</option>
                  </select>
                </div>
              </div>
            </div>
          </Transition>
        </div>

        <!-- Orders Table -->
        <div v-if="filteredOrders.length === 0" class="p-8 text-center">
          <div class="w-10 h-10 rounded-full bg-slate-100 dark:bg-slate-800 flex items-center justify-center mx-auto text-slate-400 mb-2">
            <Utensils class="w-5 h-5" />
          </div>
          <p class="text-xs font-semibold text-slate-700 dark:text-slate-300">No matching customer orders found</p>
          <p class="text-[11px] text-slate-400 mt-0.5">Orders placed by customers via table QR or staff will appear here.</p>
        </div>

        <div v-else class="overflow-x-auto">
          <table class="w-full text-left">
            <thead class="bg-slate-50 dark:bg-slate-900/90 border-b border-slate-100 dark:border-slate-800 text-[10px] uppercase tracking-wider text-slate-500 font-bold">
              <tr>
                <th class="py-2.5 px-3">Order / Type</th>
                <th class="py-2.5 px-3">Location / Table</th>
                <th class="py-2.5 px-3">Customer</th>
                <th class="py-2.5 px-3">Items</th>
                <th class="py-2.5 px-3">Amount</th>
                <th class="py-2.5 px-3">Order Status</th>
                <th class="py-2.5 px-3">Payment</th>
                <th class="py-2.5 px-3 text-right">Cashier Actions</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
              <template v-for="order in filteredOrders" :key="order.id">
                <tr
                  class="hover:bg-slate-50/70 dark:hover:bg-slate-800/40 transition-colors"
                  :class="{ 'bg-emerald-500/5': order.payment_status === 'paid' && !order.is_cleared }"
                >
                  <!-- Order Number & Type -->
                  <td class="py-2 px-3">
                    <div class="font-mono font-bold text-xs text-slate-900 dark:text-white">
                      #{{ order.order_number }}
                    </div>
                    <div class="flex items-center gap-1.5 mt-0.5 text-[10px] text-slate-400">
                      <span class="font-bold px-1.5 py-0.2 rounded bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-400 uppercase text-[9px]">
                        {{ order.order_type || 'dine_in' }}
                      </span>
                      <span>{{ formatDate(order.order_time) }}</span>
                    </div>
                  </td>

                  <!-- Table / Room -->
                  <td class="py-2 px-3">
                    <div v-if="order.table_number" class="flex flex-col">
                      <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md text-xs font-black bg-blue-50 dark:bg-blue-950/50 text-blue-700 dark:text-blue-300 border border-blue-200 dark:border-blue-800/40 w-fit">
                        {{ order.table_number }}
                      </span>
                      <span class="text-[10px] text-slate-400 mt-0.5">
                        <strong class="capitalize" :class="order.table_status === 'occupied' ? 'text-amber-600 dark:text-amber-400' : 'text-emerald-600 dark:text-emerald-400'">{{ order.table_status || 'Occupied' }}</strong>
                      </span>
                    </div>
                    <div v-else-if="order.room_number" class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md text-xs font-black bg-purple-50 dark:bg-purple-950/50 text-purple-700 dark:text-purple-300 border border-purple-200 dark:border-purple-800/40 w-fit">
                      Room {{ order.room_number }}
                    </div>
                    <span v-else class="text-xs text-slate-400 font-medium">Walk-in</span>
                  </td>

                  <!-- Customer Name -->
                  <td class="py-2 px-3">
                    <div class="text-xs font-semibold text-slate-800 dark:text-slate-200 truncate max-w-[120px]">
                      {{ order.guest_name || 'Walk-in Guest' }}
                    </div>
                    <div v-if="order.guest_phone" class="text-[10px] text-slate-400 font-mono">
                      {{ order.guest_phone }}
                    </div>
                  </td>

                  <!-- Items Preview -->
                  <td class="py-2 px-3">
                    <button
                      @click="toggleExpand(order.id)"
                      class="text-xs font-bold text-blue-600 dark:text-blue-400 hover:underline inline-flex items-center gap-1 cursor-pointer"
                    >
                      <span>{{ order.items_count }} items</span>
                      <ChevronDown v-if="!expandedOrders[order.id]" class="w-3 h-3" />
                      <ChevronUp v-else class="w-3 h-3" />
                    </button>
                    <div class="text-[10px] text-slate-400 truncate max-w-[150px]">
                      {{ order.items?.map((i: any) => `${i.quantity}x ${i.name}`).join(', ') }}
                    </div>
                  </td>

                  <!-- Total Amount -->
                  <td class="py-2 px-3">
                    <div class="text-xs sm:text-sm font-black text-slate-900 dark:text-white">
                      {{ formatCurrency(order.total) }}
                    </div>
                  </td>

                  <!-- Order Status -->
                  <td class="py-2 px-3">
                    <span
                      class="px-2 py-0.5 rounded-md text-[10px] font-bold capitalize inline-block"
                      :class="getStatusColor(order.status)"
                    >
                      {{ order.status }}
                    </span>
                  </td>

                  <!-- Payment Status -->
                  <td class="py-2 px-3">
                    <div class="flex items-center gap-1.5 flex-wrap">
                      <span
                        v-if="order.payment_status === 'paid'"
                        class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md text-[10px] font-black bg-emerald-100 dark:bg-emerald-950/60 text-emerald-800 dark:text-emerald-300 border border-emerald-300 dark:border-emerald-700/60"
                      >
                        <CheckCircle2 class="w-2.5 h-2.5 text-emerald-600" />
                        <span>PAID</span>
                      </span>
                      <span
                        v-else
                        class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md text-[10px] font-black bg-rose-100 dark:bg-rose-950/60 text-rose-800 dark:text-rose-300 border border-rose-300 dark:border-rose-700/60"
                      >
                        <Clock class="w-2.5 h-2.5 text-rose-600" />
                        <span>UNPAID</span>
                      </span>
                      <span v-if="order.payment_method" class="text-[10px] text-slate-400 capitalize">
                        ({{ order.payment_method }})
                      </span>
                    </div>
                  </td>

                  <!-- Cashier Actions -->
                  <td class="py-2 px-3 text-right">
                    <div class="flex items-center justify-end gap-1.5">
                      
                      <!-- IF PAID: Primary Action is CLEAR ORDER -->
                      <button
                        v-if="order.payment_status === 'paid' && !order.is_cleared"
                        @click="handleClearPaidOrder(order)"
                        :disabled="isClearingId === order.id"
                        class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg text-xs font-bold bg-emerald-600 hover:bg-emerald-700 text-white shadow-xs transition cursor-pointer disabled:opacity-50"
                        title="Clear order and free table"
                      >
                        <RefreshCw v-if="isClearingId === order.id" class="w-3 h-3 animate-spin" />
                        <Sparkles v-else class="w-3 h-3" />
                        <span>{{ languageStore.t('clear_order', 'Clear Order') }}</span>
                      </button>

                      <!-- IF UNPAID: SETTLE & CLEAR (collect payment and clear) -->
                      <button
                        v-else-if="!order.is_cleared"
                        @click="openSettleModal(order)"
                        class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg text-xs font-bold bg-amber-600 hover:bg-amber-700 text-white shadow-xs transition cursor-pointer"
                        title="Collect payment and clear order"
                      >
                        <DollarSign class="w-3 h-3" />
                        <span>{{ languageStore.t('settle_clear', 'Settle & Clear') }}</span>
                      </button>

                      <!-- ALREADY CLEARED BADGE -->
                      <span
                        v-else
                        class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md text-[11px] font-bold bg-slate-100 dark:bg-slate-800 text-slate-500 border border-slate-200 dark:border-slate-700"
                      >
                        <Check class="w-3 h-3 text-emerald-500" />
                        <span>Cleared</span>
                      </span>

                      <!-- View Receipt Button -->
                      <button
                        @click.stop="openReceipt(order)"
                        type="button"
                        class="p-1.5 rounded-lg border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-700 dark:text-slate-200 hover:bg-slate-100 dark:hover:bg-slate-700 transition cursor-pointer flex items-center justify-center shadow-xs"
                        title="View Receipt"
                      >
                        <Receipt class="w-3.5 h-3.5 text-slate-700 dark:text-slate-200" />
                      </button>
                    </div>
                  </td>
                </tr>

                <!-- Expandable Order Line Items Row -->
                <tr v-if="expandedOrders[order.id]" class="bg-slate-50/80 dark:bg-slate-900/60">
                  <td colspan="8" class="p-3 sm:px-6 border-t border-b border-slate-100 dark:border-slate-800">
                    <div class="bg-white dark:bg-slate-800/80 rounded-xl p-3 border border-slate-200 dark:border-slate-700/60 shadow-xs">
                      <div class="flex items-center justify-between mb-2 border-b pb-1.5 dark:border-slate-700">
                        <span class="text-xs font-bold text-slate-700 dark:text-slate-300">
                          Order Breakdown (#{{ order.order_number }})
                        </span>
                        <span v-if="order.notes" class="text-xs text-amber-600 dark:text-amber-400 font-medium">
                          Note: "{{ order.notes }}"
                        </span>
                      </div>
                      <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-2">
                        <div
                          v-for="item in order.items"
                          :key="item.id"
                          class="flex items-center justify-between p-2 bg-slate-50 dark:bg-slate-900/50 rounded-lg border border-slate-100 dark:border-slate-800 text-xs"
                        >
                          <span class="font-medium text-slate-800 dark:text-slate-200">
                            {{ item.quantity }}x {{ item.name }}
                          </span>
                          <span class="font-bold text-slate-900 dark:text-white">
                            {{ formatCurrency(item.total) }}
                          </span>
                        </div>
                      </div>
                      <div class="flex justify-end gap-5 mt-2.5 pt-2 border-t text-xs dark:border-slate-700">
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

        <!-- ========================================================================= -->
        <!-- ORDERS PAGINATION BAR (5, 10, 20, 30, 50 rows per page - Compact Bar) -->
        <!-- ========================================================================= -->
        <div class="px-3.5 py-2 border-t border-slate-100 dark:border-slate-800 bg-slate-50/70 dark:bg-slate-900/60 flex flex-col sm:flex-row items-center justify-between gap-2.5 text-xs">
          <!-- Left: Rows per page & counter -->
          <div class="flex items-center gap-2.5 text-slate-600 dark:text-slate-400">
            <div class="flex items-center gap-1.5">
              <span class="font-medium text-[11px]">Rows:</span>
              <select
                :value="perPage"
                @change="handlePerPageChange(Number(($event.target as HTMLSelectElement).value))"
                class="px-2 py-0.5 rounded-md border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-800 dark:text-slate-200 text-xs font-bold focus:outline-none focus:ring-1 focus:ring-emerald-500 cursor-pointer shadow-xs"
              >
                <option v-for="size in perPageOptions" :key="size" :value="size">
                  {{ size }}
                </option>
              </select>
            </div>

            <span class="text-slate-300 dark:text-slate-700">|</span>

            <span v-if="cashierStore.orderPagination?.total > 0" class="text-[11px]">
              Showing <strong>{{ cashierStore.orderPagination.from || 1 }}</strong>-<strong>{{ cashierStore.orderPagination.to || cashierStore.activeOrders.length }}</strong> of <strong>{{ cashierStore.orderPagination.total }}</strong>
            </span>
            <span v-else class="text-[11px]">0 orders</span>
          </div>

          <!-- Right: Page Navigation Buttons -->
          <div class="flex items-center gap-1" v-if="cashierStore.orderPagination?.last_page > 1">
            <!-- Previous Button -->
            <button
              type="button"
              :disabled="cashierStore.orderPagination.current_page <= 1"
              @click="handlePageChange(cashierStore.orderPagination.current_page - 1)"
              class="h-7 px-2.5 rounded-md border text-xs font-bold transition flex items-center gap-1 cursor-pointer disabled:opacity-40 disabled:cursor-not-allowed"
              :class="cashierStore.orderPagination.current_page <= 1
                ? 'border-slate-200 dark:border-slate-800 text-slate-400'
                : 'border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-700 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-700'"
            >
              <ChevronLeft class="w-3 h-3" />
              <span>Prev</span>
            </button>

            <!-- Page Number Buttons -->
            <template v-for="page in visiblePages" :key="page">
              <span
                v-if="page === -1"
                class="px-1 text-slate-400 text-xs select-none"
              >...</span>
              <button
                v-else
                type="button"
                @click="handlePageChange(page)"
                class="min-w-7 h-7 px-2 rounded-md text-xs font-bold transition flex items-center justify-center cursor-pointer"
                :class="page === cashierStore.orderPagination.current_page
                  ? 'bg-emerald-600 text-white shadow-xs'
                  : 'bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-slate-700 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-700'"
              >
                {{ page }}
              </button>
            </template>

            <!-- Next Button -->
            <button
              type="button"
              :disabled="cashierStore.orderPagination.current_page >= cashierStore.orderPagination.last_page"
              @click="handlePageChange(cashierStore.orderPagination.current_page + 1)"
              class="h-7 px-2.5 rounded-md border text-xs font-bold transition flex items-center gap-1 cursor-pointer disabled:opacity-40 disabled:cursor-not-allowed"
              :class="cashierStore.orderPagination.current_page >= cashierStore.orderPagination.last_page
                ? 'border-slate-200 dark:border-slate-800 text-slate-400'
                : 'border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-700 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-700'"
            >
              <span>Next</span>
              <ChevronRight class="w-3 h-3" />
            </button>
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

    </div>

    <!-- ========================================================================= -->
    <!-- SETTLE & CLEAR CONFIRMATION MODAL -->
    <!-- ========================================================================= -->
    <Teleport to="body">
      <div
        v-if="showSettleModal && settleOrder"
        class="fixed inset-0 z-[9999] flex items-center justify-center p-4 bg-slate-950/70 backdrop-blur-sm overflow-y-auto"
        @click.self="showSettleModal = false"
      >
        <div
          class="relative bg-white dark:bg-slate-900 rounded-2xl max-w-md w-full border border-slate-200 dark:border-slate-800 shadow-2xl p-6 overflow-hidden my-8"
          @click.stop
        >
          <div class="flex items-center justify-between pb-3 border-b dark:border-slate-800">
            <div class="flex items-center gap-2">
              <div class="w-8 h-8 rounded-lg bg-amber-500/10 text-amber-600 flex items-center justify-center">
                <DollarSign class="w-4 h-4" />
              </div>
              <h3 class="text-base font-black text-slate-900 dark:text-white">
                Settle & Clear Order
              </h3>
            </div>
            <button
              @click="showSettleModal = false"
              type="button"
              class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 p-1 rounded-lg transition"
            >
              <X class="w-5 h-5" />
            </button>
          </div>

          <div class="py-4 space-y-4 text-xs">
            <!-- Order summary box -->
            <div class="p-3 bg-slate-50 dark:bg-slate-800 rounded-xl border border-slate-100 dark:border-slate-700/60 flex items-center justify-between">
              <div>
                <p class="font-bold text-slate-800 dark:text-slate-200">#{{ settleOrder.order_number }}</p>
                <p class="text-slate-500">{{ settleOrder.table_number || 'Walk-in' }} • {{ settleOrder.guest_name || 'Guest' }}</p>
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
              type="button"
              class="px-4 py-2 rounded-xl text-xs font-bold text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800 transition cursor-pointer"
            >
              Cancel
            </button>
            <button
              @click="confirmSettleAndClear"
              type="button"
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
    </Teleport>

    <!-- ========================================================================= -->
    <!-- PRINTABLE RECEIPT MODAL -->
    <!-- ========================================================================= -->
    <Teleport to="body">
      <div
        v-if="showReceiptModal && receiptOrder"
        class="fixed inset-0 z-[9999] flex items-center justify-center p-4 bg-slate-950/70 backdrop-blur-sm overflow-y-auto"
        @click.self="showReceiptModal = false"
      >
        <div
          class="relative bg-white dark:bg-slate-900 rounded-2xl max-w-lg w-full border border-slate-200 dark:border-slate-800 shadow-2xl p-6 overflow-hidden my-8"
          @click.stop
        >
          <!-- Header -->
          <div class="flex items-center justify-between pb-3 border-b dark:border-slate-800">
            <div class="flex items-center gap-2">
              <div class="w-8 h-8 rounded-lg bg-emerald-500/10 flex items-center justify-center text-emerald-600">
                <Receipt class="w-5 h-5" />
              </div>
              <h3 class="text-base font-black text-slate-900 dark:text-white">Order Receipt</h3>
            </div>
            <button
              @click="showReceiptModal = false"
              type="button"
              class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 p-1 rounded-lg transition cursor-pointer"
            >
              <X class="w-5 h-5" />
            </button>
          </div>

          <!-- Receipt Card (Print Target) -->
          <div id="printable-receipt" class="my-4 p-5 bg-slate-50 dark:bg-slate-800/60 rounded-xl border border-dashed border-slate-300 dark:border-slate-700 font-mono text-xs text-slate-800 dark:text-slate-200 space-y-3">
            <div class="text-center border-b pb-3 dark:border-slate-700">
              <h4 class="font-bold text-base tracking-wide uppercase">{{ hotelStore.hotelName || 'HOTEL & RESTAURANT' }}</h4>
              <p class="text-[11px] text-slate-500">Official Customer Invoice</p>
              <div class="inline-block mt-1 px-2.5 py-0.5 rounded bg-slate-200/70 dark:bg-slate-700 text-[11px] font-bold">
                Order #{{ receiptOrder.order_number }}
              </div>
              <p class="text-[10px] text-slate-400 mt-1">{{ formatDate(receiptOrder.order_time) }}</p>
            </div>

            <div class="flex justify-between items-center text-[11px] py-1">
              <span>Customer: <strong>{{ receiptOrder.guest_name || 'Walk-in Guest' }}</strong></span>
              <span>Location: <strong class="text-blue-600 dark:text-blue-400">{{ receiptOrder.table_number || (receiptOrder.room_number ? 'Room ' + receiptOrder.room_number : 'Walk-in') }}</strong></span>
            </div>

            <!-- Items Table -->
            <div class="border-t border-b py-2 dark:border-slate-700 space-y-1.5">
              <div v-if="receiptOrder.items && receiptOrder.items.length > 0">
                <div
                  v-for="item in receiptOrder.items"
                  :key="item.id || item.name"
                  class="flex justify-between text-[11px]"
                >
                  <span class="truncate pr-2">{{ item.quantity }}x {{ item.name }}</span>
                  <span class="font-bold flex-shrink-0">{{ formatCurrency(item.total) }}</span>
                </div>
              </div>
              <div v-else class="text-center text-slate-400 py-1 text-[11px]">
                Order Items ({{ receiptOrder.items_count || 1 }} items)
              </div>
            </div>

            <!-- Financial Breakdown -->
            <div class="space-y-1 text-[11px] pt-1">
              <div class="flex justify-between text-slate-500">
                <span>Subtotal:</span>
                <span>{{ formatCurrency(receiptOrder.subtotal ?? 0) }}</span>
              </div>
              <div class="flex justify-between text-slate-500">
                <span>Tax / VAT:</span>
                <span>{{ formatCurrency(receiptOrder.tax ?? 0) }}</span>
              </div>
              <div v-if="receiptOrder.service_charge > 0" class="flex justify-between text-slate-500">
                <span>Service Charge:</span>
                <span>{{ formatCurrency(receiptOrder.service_charge) }}</span>
              </div>
              <div v-if="receiptOrder.discount > 0" class="flex justify-between text-emerald-600">
                <span>Discount:</span>
                <span>-{{ formatCurrency(receiptOrder.discount) }}</span>
              </div>
              <div class="flex justify-between font-bold text-sm pt-2 border-t dark:border-slate-700 text-slate-900 dark:text-white">
                <span>TOTAL:</span>
                <span>{{ formatCurrency(receiptOrder.total ?? 0) }}</span>
              </div>
            </div>

            <div class="text-center pt-2 text-[10px] text-slate-400 border-t dark:border-slate-700 flex items-center justify-between">
              <span>Status: <strong class="uppercase" :class="receiptOrder.payment_status === 'paid' ? 'text-emerald-600' : 'text-amber-600'">{{ receiptOrder.payment_status }}</strong></span>
              <span>Payment: <strong>{{ receiptOrder.payment_method || receiptOrder.payment_type || 'cash' }}</strong></span>
            </div>
          </div>

          <!-- Modal Actions -->
          <div class="flex items-center justify-end gap-3 pt-3 border-t dark:border-slate-800">
            <button
              @click="showReceiptModal = false"
              type="button"
              class="px-4 py-2 rounded-xl text-xs font-bold text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800 transition cursor-pointer"
            >
              Close
            </button>
            <button
              @click="printReceipt"
              type="button"
              class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl text-xs font-bold bg-blue-600 hover:bg-blue-700 text-white shadow-md shadow-blue-600/30 transition cursor-pointer"
            >
              <Printer class="w-4 h-4" />
              <span>Print Receipt</span>
            </button>
          </div>
        </div>
      </div>
    </Teleport>

  </DashboardLayout>
</template>

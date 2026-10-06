<script setup lang="ts">
import { ref, onMounted, onUnmounted, watch, computed } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import DashboardLayout from '../../Layouts/DashboardLayout.vue'
import { useCashierStore } from '@/stores/cashierStore'
import { useHotelStore } from '@/stores/hotelStore'
import { useLanguageStore } from '@/stores/language'
import {
  Search,
  Filter,
  Download,
  Eye,
  RefreshCw,
  ChevronLeft,
  ChevronRight,
  Calendar,
  X,
  CreditCard,
  Loader2,
  Building2,
  Wifi,
  WifiOff,
} from 'lucide-vue-next'

const router = useRouter()
const route = useRoute()
const cashierStore = useCashierStore()
const hotelStore = useHotelStore()
const languageStore = useLanguageStore()

// WebSocket state
const isConnected = ref(false)
const wsChannel = ref<any>(null)

// Filter state
const filters = ref({
  search: '',
  status: '',
  provider: '',
  type: '',
  filter: (route.query.filter as string) || '',
  date_from: '',
  date_to: '',
  sort_by: 'created_at',
  sort_order: 'desc' as 'asc' | 'desc',
  per_page: 10,
  page: 1,
})

const showFilters = ref(false)

// Load payments
onMounted(() => {
  loadPayments()
  subscribeToPaymentUpdates()
})

onUnmounted(() => {
  cleanupWebSocket()
})

// Watch for query parameter changes
watch(() => route.query.filter, (newFilter) => {
  if (newFilter !== undefined) {
    filters.value.filter = (newFilter as string) || ''
    filters.value.page = 1
    loadPayments()
  }
})

watch(() => hotelStore.hotelId, () => {
  filters.value.page = 1
  loadPayments()
  // Resubscribe to new hotel's payment channel
  cleanupWebSocket()
  subscribeToPaymentUpdates()
})

const loadPayments = async () => {
  await cashierStore.fetchPayments(filters.value as any)
}

// WebSocket: Subscribe to payment updates
const subscribeToPaymentUpdates = () => {
  if (!window.Echo || !hotelStore.hotelId) {
    console.warn('[CashierPayments] Echo not initialized or no hotel ID')
    return
  }

  try {
    const channelName = `payments.${hotelStore.hotelId}`
    console.log(`[CashierPayments] 📡 Subscribing to channel: ${channelName}`)

    wsChannel.value = window.Echo.private(channelName)

    // Connection state handlers
    if (window.Echo.connector?.pusher) {
      const pusherConnection = window.Echo.connector.pusher.connection
      
      pusherConnection.bind('connected', () => {
        console.log('[CashierPayments] ✅ WebSocket connected')
        isConnected.value = true
      })

      pusherConnection.bind('disconnected', () => {
        console.log('[CashierPayments] ❌ WebSocket disconnected')
        isConnected.value = false
      })

      pusherConnection.bind('error', (err: any) => {
        console.error('[CashierPayments] ⚠️ WebSocket error:', err)
        isConnected.value = false
      })

      // Set initial state
      isConnected.value = pusherConnection.state === 'connected'
    }

    // Listen for payment status updates
    wsChannel.value.listen('.PaymentStatusUpdated', (event: any) => {
      console.log('[CashierPayments] 💰 Payment status updated:', event)
      handlePaymentUpdate(event)
    })

    // Listen for payment verification
    wsChannel.value.listen('.PaymentVerified', (event: any) => {
      console.log('[CashierPayments] ✅ Payment verified:', event)
      handlePaymentUpdate(event)
    })

    // Listen for order completion
    wsChannel.value.listen('.OrderCompleted', (event: any) => {
      console.log('[CashierPayments] 🎉 Order completed:', event)
      handleOrderCompletionUpdate(event)
    })

    // Listen for payment initialization
    wsChannel.value.listen('.PaymentInitialized', (event: any) => {
      console.log('[CashierPayments] 🆕 Payment initialized:', event)
      // Refresh list to show new payment
      loadPayments()
    })

    console.log('[CashierPayments] ✅ Channel subscription setup complete')
  } catch (error) {
    console.error('[CashierPayments] Error subscribing to channel:', error)
  }
}

// Handle payment status update from WebSocket
const handlePaymentUpdate = (event: any) => {
  const paymentId = event.payment_id || event.id
  const payment = cashierStore.payments.find((p: any) => p.id === paymentId)
  
  if (payment) {
    // Update existing payment
    payment.status = event.status || event.payment_status
    payment.payment_status = event.payment_status || event.status
    if (event.verified_at) payment.verified_at = event.verified_at
    if (event.updated_at) payment.updated_at = event.updated_at
    
    console.log('[CashierPayments] Updated payment in list:', payment.tx_ref)
  } else {
    // Payment not in current view, refresh if it matches filters
    loadPayments()
  }
}

// Handle order completion update
const handleOrderCompletionUpdate = (event: any) => {
  // Find payment by order_id
  const payment = cashierStore.payments.find((p: any) => 
    p.order_id === event.order_id || p.reservation_id === event.order_id
  )
  
  if (payment) {
    payment.order_status = 'completed'
    payment.order_completed_at = event.completed_at || event.updated_at
    console.log('[CashierPayments] Updated order completion:', payment.tx_ref)
  }
}

// Cleanup WebSocket on unmount
const cleanupWebSocket = () => {
  if (wsChannel.value) {
    console.log('[CashierPayments] 🔌 Leaving payment channel')
    wsChannel.value.stopListening('.PaymentStatusUpdated')
    wsChannel.value.stopListening('.PaymentVerified')
    wsChannel.value.stopListening('.OrderCompleted')
    wsChannel.value.stopListening('.PaymentInitialized')
    window.Echo?.leave(`payments.${hotelStore.hotelId}`)
    wsChannel.value = null
  }
  isConnected.value = false
}

// Search handler
const handleSearch = () => {
  filters.value.page = 1
  loadPayments()
}

// Filter handlers
const applyFilters = () => {
  filters.value.page = 1
  loadPayments()
  showFilters.value = false
}

const clearFilters = () => {
  filters.value = {
    search: '',
    status: '',
    provider: '',
    type: '',
    filter: '',
    date_from: '',
    date_to: '',
    sort_by: 'created_at',
    sort_order: 'desc',
    per_page: filters.value.per_page,
    page: 1,
  }
  loadPayments()
}
const setQuickFilter = (filter: string) => {
  filters.value.filter = filter
  filters.value.page = 1
  loadPayments()
}
const changePerPage = (event: Event) => {
  const target = event.target as HTMLSelectElement
  filters.value.per_page = Number(target.value)
  filters.value.page = 1
  loadPayments()
}

// Pagination handlers
const goToPage = (page: number) => {
  filters.value.page = page
  loadPayments()
}

const nextPage = () => {
  if (filters.value.page < (cashierStore.pagination?.last_page || 1)) {
    filters.value.page++
    loadPayments()
  }
}

const previousPage = () => {
  if (filters.value.page > 1) {
    filters.value.page--
    loadPayments()
  }
}

const paginationPages = computed(() => {
  const pages: number[] = []
  const max = cashierStore.pagination?.last_page || 1
  const cur = cashierStore.pagination?.current_page || 1

  for (let i = Math.max(1, cur - 2); i <= Math.min(max, cur + 2); i++) {
    pages.push(i)
  }
  return pages
})

// Sorting
const sortBy = (column: string) => {
  if (filters.value.sort_by === column) {
    filters.value.sort_order = filters.value.sort_order === 'asc' ? 'desc' : 'asc'
  } else {
    filters.value.sort_by = column
    filters.value.sort_order = 'desc'
  }
  loadPayments()
}

// Compact Format helpers
const currency = computed(() => hotelStore.currentHotel?.currency || 'ETB')

const formatCurrency = (amount: number | string) => {
  const numAmount = typeof amount === 'string' ? parseFloat(amount) : amount
  return `${(numAmount || 0).toFixed(2)} ${currency.value}`
}

const formatDateShort = (date: string) => {
  if (!date) return '-'
  return new Date(date).toLocaleDateString('en-US', {
    month: 'short',
    day: 'numeric',
    hour: '2-digit',
    minute: '2-digit',
  })
}

const formatTxRefShort = (ref: string) => {
  if (!ref) return 'N/A'
  if (ref.length <= 16) return ref
  return `${ref.substring(0, 8)}...${ref.slice(-4)}`
}

const formatTypeShort = (type: string) => {
  if (!type) return '-'
  const lower = type.toLowerCase()
  if (lower.includes('order')) return 'Order'
  if (lower.includes('reservation')) return 'Booking'
  return type
}

const getStatusBadgeClass = (status: string) => {
  switch ((status || '').toLowerCase()) {
    case 'paid':
    case 'verified':
    case 'completed':
      return 'bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 border-emerald-500/20 font-black'
    case 'pending':
    case 'initialized':
      return 'bg-amber-500/10 text-amber-600 dark:text-amber-400 border-amber-500/20 font-black'
    case 'failed':
    case 'cancelled':
      return 'bg-rose-500/10 text-rose-600 dark:text-rose-400 border-rose-500/20 font-black'
    case 'refunded':
      return 'bg-purple-500/10 text-purple-600 dark:text-purple-400 border-purple-500/20 font-black'
    default:
      return 'bg-slate-500/10 text-slate-600 dark:text-slate-400 border-slate-500/20 font-black'
  }
}
const viewPayment = (id: string) => {
  router.push({ name: 'cashier-payment-detail', params: { id } })
}
</script>

<template>
  <DashboardLayout>
    <div class="space-y-5 bg-slate-50 dark:bg-slate-950 min-h-screen p-3 sm:p-5 max-w-full font-sans">
      <!-- Header Banner -->
      <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 p-5 rounded-3xl shadow-xs flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
          <div class="flex items-center gap-2">
            <h1 class="text-xl font-black text-slate-900 dark:text-white tracking-tight">{{ languageStore.t('payments_billing', 'Payments & Transactions') }}</h1>
            <span v-if="hotelStore.hotelName" class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-xs font-bold bg-blue-100 dark:bg-blue-900/30 text-blue-800 dark:text-blue-300 border border-blue-200 dark:border-blue-700/50">
              <Building2 class="w-3 h-3" />
              {{ hotelStore.hotelName }}
            </span>
          </div>
          <p class="text-[11px] text-slate-500 dark:text-slate-400 mt-0.5">{{ languageStore.t('transactions_desc', 'View and manage all payment transaction logs.') }}</p>
        </div>

        <button
          @click="loadPayments"
          :disabled="cashierStore.isLoading"
          class="px-3 py-2 rounded-xl bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 dark:hover:bg-slate-700 text-slate-800 dark:text-slate-200 text-xs font-extrabold transition flex items-center justify-center gap-1.5 cursor-pointer border border-slate-200 dark:border-slate-700 disabled:opacity-50"
        >
          <RefreshCw :class="['w-3.5 h-3.5', cashierStore.isLoading && 'animate-spin']" />
          <span>{{ languageStore.t('refresh', 'Refresh') }}</span>
        </button>
      </div>

      <!-- Quick Filter Pills -->
      <div class="flex flex-wrap items-center gap-1.5 text-xs">
        <button
          @click="setQuickFilter('')"
          :class="[
            'px-3 py-1 rounded-xl font-black transition cursor-pointer border',
            filters.filter === ''
              ? 'bg-blue-600 border-blue-600 text-white shadow-xs'
              : 'bg-white dark:bg-slate-900 border-slate-200 dark:border-slate-800 text-slate-700 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800'
          ]"
        >
          {{ languageStore.t('All', 'All') }}
        </button>
        <button
          @click="setQuickFilter('today')"
          :class="[
            'px-3 py-1 rounded-xl font-black transition cursor-pointer border',
            filters.filter === 'today'
              ? 'bg-blue-600 border-blue-600 text-white shadow-xs'
              : 'bg-white dark:bg-slate-900 border-slate-200 dark:border-slate-800 text-slate-700 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800'
          ]"
        >
          {{ languageStore.t('due_today', 'Today') }}
        </button>
        <button
          @click="setQuickFilter('week')"
          :class="[
            'px-3 py-1 rounded-xl font-black transition cursor-pointer border',
            filters.filter === 'week'
              ? 'bg-blue-600 border-blue-600 text-white shadow-xs'
              : 'bg-white dark:bg-slate-900 border-slate-200 dark:border-slate-800 text-slate-700 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800'
          ]"
        >
          {{ languageStore.t('Week', 'Week') }}
        </button>
        <button
          @click="setQuickFilter('month')"
          :class="[
            'px-3 py-1 rounded-xl font-black transition cursor-pointer border',
            filters.filter === 'month'
              ? 'bg-blue-600 border-blue-600 text-white shadow-xs'
              : 'bg-white dark:bg-slate-900 border-slate-200 dark:border-slate-800 text-slate-700 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800'
          ]"
        >
          {{ languageStore.t('Month', 'Month') }}
        </button>
        <button
          @click="setQuickFilter('paid')"
          :class="[
            'px-3 py-1 rounded-xl font-black transition cursor-pointer border',
            filters.filter === 'paid'
              ? 'bg-emerald-600 border-emerald-600 text-white shadow-xs'
              : 'bg-white dark:bg-slate-900 border-slate-200 dark:border-slate-800 text-slate-700 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800'
          ]"
        >
          {{ languageStore.t('paid', 'Paid') }}
        </button>
        <button
          @click="setQuickFilter('pending')"
          :class="[
            'px-3 py-1 rounded-xl font-black transition cursor-pointer border',
            filters.filter === 'pending'
              ? 'bg-amber-500 border-amber-500 text-slate-950 shadow-xs'
              : 'bg-white dark:bg-slate-900 border-slate-200 dark:border-slate-800 text-slate-700 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800'
          ]"
        >
          {{ languageStore.t('pending', 'Pending') }}
        </button>
        <button
          @click="setQuickFilter('failed')"
          :class="[
            'px-3 py-1 rounded-xl font-black transition cursor-pointer border',
            filters.filter === 'failed'
              ? 'bg-rose-600 border-rose-600 text-white shadow-xs'
              : 'bg-white dark:bg-slate-900 border-slate-200 dark:border-slate-800 text-slate-700 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800'
          ]"
        >
          {{ languageStore.t('cancelled', 'Failed') }}
        </button>
        <button
          @click="setQuickFilter('refunded')"
          :class="[
            'px-3 py-1 rounded-xl font-black transition cursor-pointer border',
            filters.filter === 'refunded'
              ? 'bg-purple-600 border-purple-600 text-white shadow-xs'
              : 'bg-white dark:bg-slate-900 border-slate-200 dark:border-slate-800 text-slate-700 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800'
          ]"
        >
          {{ languageStore.t('refund', 'Refunded') }}
        </button>
      </div>

      <!-- Search and Filter Controls -->
      <div class="bg-white dark:bg-slate-900 rounded-3xl border border-slate-200 dark:border-slate-800 p-3.5 shadow-xs space-y-3">
        <div class="flex flex-col sm:flex-row gap-2.5">
          <div class="flex-1 relative">
            <Search class="w-4 h-4 absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400" />
            <input
              v-model="filters.search"
              @keyup.enter="handleSearch"
              type="text"
              :placeholder="languageStore.t('search_transactions_ph', 'Search ref, email, or name...')"
              class="w-full pl-10 pr-3 py-1.5 rounded-xl bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 text-slate-900 dark:text-white text-xs font-medium focus:outline-none focus:border-amber-500 transition"
            />
          </div>

          <div class="flex items-center gap-2">
            <button
              @click="handleSearch"
              class="px-3.5 py-1.5 rounded-xl bg-blue-600 hover:bg-blue-700 text-white text-xs font-extrabold shadow-xs transition cursor-pointer"
            >
              {{ languageStore.t('Search', 'Search') }}
            </button>

            <button
              @click="showFilters = !showFilters"
              class="px-3.5 py-1.5 rounded-xl bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 dark:hover:bg-slate-700 text-slate-800 dark:text-slate-200 text-xs font-bold transition cursor-pointer border border-slate-200 dark:border-slate-700 flex items-center gap-1"
            >
              <Filter class="w-3.5 h-3.5" />
              <span>{{ languageStore.t('Filters', 'Filters') }}</span>
            </button>
          </div>
        </div>

        <!-- Advanced Filter Dropdowns -->
        <div v-if="showFilters" class="pt-3 border-t border-slate-100 dark:border-slate-800 grid grid-cols-1 md:grid-cols-3 gap-3">
          <div>
            <label class="block text-[9px] font-bold uppercase text-slate-500 dark:text-slate-400 mb-1">{{ languageStore.t('Status', 'Status') }}</label>
            <select
              v-model="filters.status"
              class="w-full px-2.5 py-1.5 rounded-xl bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 text-slate-900 dark:text-white text-xs font-semibold focus:outline-none"
            >
              <option value="">{{ languageStore.t('All Statuses', 'All Statuses') }}</option>
              <option value="paid">{{ languageStore.t('paid', 'Paid') }}</option>
              <option value="verified">{{ languageStore.t('Confirmed', 'Verified') }}</option>
              <option value="pending">{{ languageStore.t('pending', 'Pending') }}</option>
              <option value="initialized">{{ languageStore.t('pending', 'Initialized') }}</option>
              <option value="failed">{{ languageStore.t('cancelled', 'Failed') }}</option>
              <option value="refunded">{{ languageStore.t('refund', 'Refunded') }}</option>
            </select>
          </div>

          <div>
            <label class="block text-[9px] font-bold uppercase text-slate-500 dark:text-slate-400 mb-1">{{ languageStore.t('Type', 'Type') }}</label>
            <select
              v-model="filters.type"
              class="w-full px-2.5 py-1.5 rounded-xl bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 text-slate-900 dark:text-white text-xs font-semibold focus:outline-none"
            >
              <option value="">{{ languageStore.t('All', 'All Types') }}</option>
              <option value="reservation">{{ languageStore.t('Reservations', 'Reservation') }}</option>
              <option value="order">{{ languageStore.t('Food Orders', 'Restaurant Order') }}</option>
            </select>
          </div>

          <div>
            <label class="block text-[9px] font-bold uppercase text-slate-500 dark:text-slate-400 mb-1">{{ languageStore.t('payment_method', 'Provider') }}</label>
            <select
              v-model="filters.provider"
              class="w-full px-2.5 py-1.5 rounded-xl bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 text-slate-900 dark:text-white text-xs font-semibold focus:outline-none"
            >
              <option value="">{{ languageStore.t('All', 'All Providers') }}</option>
              <option value="chapa">Chapa</option>
              <option value="telebirr">{{ languageStore.t('telebirr', 'Telebirr') }}</option>
              <option value="cbe_birr">{{ languageStore.t('cbe_birr', 'CBE Birr') }}</option>
            </select>
          </div>

          <div class="flex items-center gap-2 md:col-span-3 justify-end pt-1">
            <button
              @click="applyFilters"
              class="px-3 py-1.5 rounded-xl bg-blue-600 hover:bg-blue-700 text-white font-extrabold text-xs shadow-xs transition cursor-pointer"
            >
              {{ languageStore.t('Confirm', 'Apply') }}
            </button>
            <button
              @click="clearFilters"
              class="px-3 py-1.5 rounded-xl bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-300 font-bold text-xs transition cursor-pointer border border-slate-200 dark:border-slate-700"
            >
              {{ languageStore.t('Reset', 'Reset') }}
            </button>
          </div>
        </div>
      </div>

      <!-- Payments Data Table Card -->
      <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-3xl overflow-hidden shadow-xs">
        <div class="p-4 border-b border-slate-200 dark:border-slate-800 flex items-center justify-between bg-slate-50/50 dark:bg-slate-950/50">
          <div class="flex items-center gap-2">
            <CreditCard class="w-4 h-4 text-blue-500" />
            <h2 class="text-sm font-extrabold text-slate-900 dark:text-white">{{ languageStore.t('Payment History', 'Transaction Logs') }}</h2>
          </div>
          <span v-if="cashierStore.pagination" class="px-2.5 py-0.5 bg-slate-200 dark:bg-slate-800 text-slate-700 dark:text-slate-300 text-[11px] font-black rounded-full border border-slate-300/60 dark:border-slate-700">
            {{ cashierStore.pagination.total || 0 }} {{ languageStore.t('records', 'Records') }}
          </span>
        </div>

        <div class="overflow-x-auto w-full">
          <table class="w-full text-left border-collapse table-fixed min-w-[700px]">
            <thead>
              <tr class="bg-slate-50 dark:bg-slate-950/80 border-b border-slate-200 dark:border-slate-800 text-[10px] font-black uppercase text-slate-500 dark:text-slate-400 tracking-wider">
                <th @click="sortBy('tx_ref')" class="w-[18%] px-2.5 py-2.5 whitespace-nowrap cursor-pointer hover:text-slate-900 dark:hover:text-white">{{ languageStore.t('Reservation #', 'Ref') }}</th>
                <th @click="sortBy('customer_name')" class="w-[22%] px-2.5 py-2.5 whitespace-nowrap cursor-pointer hover:text-slate-900 dark:hover:text-white">{{ languageStore.t('Guest', 'Customer') }}</th>
                <th @click="sortBy('amount')" class="w-[13%] px-2.5 py-2.5 whitespace-nowrap cursor-pointer hover:text-slate-900 dark:hover:text-white">{{ languageStore.t('Amount', 'Amount') }}</th>
                <th class="w-[10%] px-2.5 py-2.5 whitespace-nowrap">{{ languageStore.t('Room Type', 'Type') }}</th>
                <th class="w-[9%] px-2.5 py-2.5 whitespace-nowrap">{{ languageStore.t('payment_method', 'Method') }}</th>
                <th @click="sortBy('status')" class="w-[12%] px-2.5 py-2.5 text-center whitespace-nowrap cursor-pointer hover:text-slate-900 dark:hover:text-white">{{ languageStore.t('Status', 'Status') }}</th>
                <th @click="sortBy('created_at')" class="w-[11%] px-2.5 py-2.5 whitespace-nowrap cursor-pointer hover:text-slate-900 dark:hover:text-white">{{ languageStore.t('Check-in Date', 'Date') }}</th>
                <th class="w-[5%] px-2 py-2 text-right whitespace-nowrap pr-3">{{ languageStore.t('Actions', 'Action') }}</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-100 dark:divide-slate-800/60 text-[11px]">
              <tr v-if="cashierStore.isLoading" v-for="i in 5" :key="i">
                <td colspan="8" class="p-3">
                  <div class="h-5 bg-slate-100 dark:bg-slate-800 rounded-xl animate-pulse"></div>
                </td>
              </tr>

              <tr v-else-if="cashierStore.payments.length === 0">
                <td colspan="8" class="p-10 text-center text-slate-500 dark:text-slate-400 text-xs font-bold">
                  {{ languageStore.t('no_records_found', 'No payment records found matching criteria.') }}
                </td>
              </tr>

              <tr
                v-else
                v-for="payment in cashierStore.payments"
                :key="payment.id"
                class="hover:bg-slate-50/70 dark:hover:bg-slate-800/40 transition cursor-pointer"
                @click="viewPayment(payment.id)"
              >
                <!-- Transaction Ref -->
                <td class="px-2.5 py-2.5 whitespace-nowrap font-mono text-[10px] font-extrabold text-slate-900 dark:text-white truncate" :title="payment.tx_ref">
                  {{ formatTxRefShort(payment.tx_ref) }}
                </td>

                <!-- Customer -->
                <td class="px-2.5 py-2.5 whitespace-nowrap">
                  <div class="font-extrabold text-slate-900 dark:text-white text-[11px] truncate" :title="payment.customer_name">
                    {{ payment.customer_name }}
                  </div>
                  <div class="text-[9px] text-slate-500 dark:text-slate-400 font-medium truncate" :title="payment.email">
                    {{ payment.email }}
                  </div>
                </td>

                <!-- Amount -->
                <td class="px-2.5 py-2.5 whitespace-nowrap font-black text-slate-900 dark:text-white text-[11px]">
                  {{ formatCurrency(payment.amount) }}
                </td>

                <!-- Type -->
                <td class="px-2.5 py-2.5 whitespace-nowrap text-slate-700 dark:text-slate-300 font-semibold text-[11px] capitalize">
                  {{ formatTypeShort(payment.type) }}
                </td>

                <!-- Method -->
                <td class="px-2.5 py-2.5 whitespace-nowrap text-slate-600 dark:text-slate-400 text-[10px] capitalize font-medium">
                  {{ payment.payment_method || '-' }}
                </td>

                <!-- Status Pill -->
                <td class="px-2.5 py-2.5 text-center whitespace-nowrap">
                  <span
                    :class="[
                      'inline-flex items-center gap-1 px-2 py-0.2 rounded-full text-[9px] font-black uppercase tracking-wider border',
                      getStatusBadgeClass(payment.status)
                    ]"
                  >
                    <span class="w-1.5 h-1.5 rounded-full bg-current"></span>
                    {{ payment.status }}
                  </span>
                </td>

                <!-- Date -->
                <td class="px-2.5 py-2.5 whitespace-nowrap text-[10px] font-bold text-slate-500 dark:text-slate-400">
                  {{ formatDateShort(payment.created_at) }}
                </td>

                <!-- Actions -->
                <td class="px-2 py-2 text-right whitespace-nowrap pr-3">
                  <button
                    @click.stop="viewPayment(payment.id)"
                    class="p-1.5 rounded-lg bg-blue-500/10 text-blue-600 dark:text-blue-400 hover:bg-blue-600 hover:text-white transition cursor-pointer border border-blue-500/20"
                    :title="languageStore.t('View Details', 'View Transaction')"
                  >
                    <Eye class="w-3.5 h-3.5" />
                  </button>
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <!-- Pagination Bar with 5, 10, 20, 50 Options -->
        <div
          v-if="cashierStore.pagination && cashierStore.pagination.total > 0"
          class="flex flex-col sm:flex-row items-center justify-between gap-4 border-t border-slate-200 dark:border-slate-800 bg-slate-50/80 dark:bg-slate-950 p-3.5 text-xs font-sans"
        >
          <!-- Left Side: Per Page Selector & Showing Count -->
          <div class="flex flex-wrap items-center gap-3 text-slate-600 dark:text-slate-400">
            <div class="flex items-center gap-1.5">
              <span class="font-bold text-slate-700 dark:text-slate-300 text-xs">{{ languageStore.t('Per page:', 'Items per page:') }}</span>
              <select
                :value="filters.per_page"
                @change="changePerPage"
                class="px-2 py-1 rounded-xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 text-slate-900 dark:text-white font-black text-xs focus:outline-none cursor-pointer shadow-2xs"
              >
                <option :value="5">5</option>
                <option :value="10">10</option>
                <option :value="20">20</option>
                <option :value="50">50</option>
              </select>
            </div>

            <div class="text-[11px] font-medium">
              {{ languageStore.t('Showing', 'Showing') }} <span class="font-extrabold text-slate-900 dark:text-white">{{ cashierStore.pagination.from || 0 }}</span> {{ languageStore.t('to', 'to') }}
              <span class="font-extrabold text-slate-900 dark:text-white">{{ cashierStore.pagination.to || 0 }}</span> {{ languageStore.t('of', 'of') }}
              <span class="font-extrabold text-slate-900 dark:text-white">{{ cashierStore.pagination.total || 0 }}</span> {{ languageStore.t('Payments', 'payments') }}
            </div>
          </div>

          <!-- Right Side: Page Controls -->
          <div class="flex items-center gap-1">
            <button
              @click="previousPage"
              :disabled="cashierStore.pagination.current_page <= 1"
              class="p-1.5 rounded-xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 text-slate-700 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800 disabled:opacity-40 disabled:cursor-not-allowed transition cursor-pointer flex items-center gap-1 font-bold text-xs"
              :title="languageStore.t('Previous', 'Previous Page')"
            >
              <ChevronLeft class="w-3.5 h-3.5" />
              <span class="hidden sm:inline">{{ languageStore.t('Previous', 'Prev') }}</span>
            </button>

            <div class="flex items-center gap-1">
              <button
                v-for="p in paginationPages"
                :key="p"
                @click="goToPage(p)"
                :class="[
                  'w-7 h-7 rounded-xl font-black text-xs transition cursor-pointer flex items-center justify-center border',
                  cashierStore.pagination.current_page === p
                    ? 'bg-amber-500 border-amber-500 text-slate-950 font-black shadow-xs'
                    : 'bg-white dark:bg-slate-900 border-slate-200 dark:border-slate-800 text-slate-700 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800'
                ]"
              >
                {{ p }}
              </button>
            </div>

            <button
              @click="nextPage"
              :disabled="cashierStore.pagination.current_page >= cashierStore.pagination.last_page"
              class="p-2 rounded-xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 text-slate-700 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800 disabled:opacity-40 disabled:cursor-not-allowed transition cursor-pointer flex items-center gap-1 font-bold text-xs"
              :title="languageStore.t('Next', 'Next Page')"
            >
              <span class="hidden sm:inline">{{ languageStore.t('Next', 'Next') }}</span>
              <ChevronRight class="w-3.5 h-3.5" />
            </button>
          </div>
        </div>
      </div>
    </div>
  </DashboardLayout>
</template>

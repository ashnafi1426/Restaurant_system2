import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import cashierService, { type PaymentFilters, type ReportFilters } from '@/services/cashierService'

export interface DashboardStats {
  today_revenue: number
  weekly_revenue: number
  monthly_revenue: number
  pending_payments: number
  completed_payments: number
  failed_payments: number
  refund_requests: number
  total_transactions: number
  today_transactions?: number
}

export interface Payment {
  id: string
  tx_ref: string
  chapa_transaction_id?: string
  amount: number
  currency: string
  formatted_amount?: string
  customer_name: string
  first_name?: string
  last_name?: string
  email: string
  phone?: string
  status: string
  payment_provider?: string
  payment_method?: string
  type: string
  reference_id?: string
  guest?: {
    id: string
    name: string
    email: string
  }
  reservation?: {
    id?: string
    booking_reference?: string
    check_in_date?: string
    check_out_date?: string
    number_of_guests?: number
    room?: {
      id?: string | number
      room_number?: string | number
      floor?: number
      [key: string]: any
    }
    [key: string]: any
  } | null
  order?: {
    id?: string | number
    order_number?: string
    status?: string
    items_count?: number
    total_amount?: number
    [key: string]: any
  } | null
  paid_at?: string
  verified_at?: string
  created_at: string
  updated_at?: string
}

export interface Pagination {
  current_page: number
  last_page: number
  per_page: number
  total: number
  from: number | null
  to: number | null
}

const DEFAULT_PAGINATION: Pagination = {
  current_page: 1,
  last_page: 1,
  per_page: 15,
  total: 0,
  from: null,
  to: null,
}

export const useCashierStore = defineStore('cashier', () => {
  const dashboardStats = ref<DashboardStats | null>(null)
  const recentPayments = ref<Payment[]>([])
  const pendingPayments = ref<Payment[]>([])
  const recentTransactions = ref<Payment[]>([])
  const revenueChartData = ref<any[]>([])
  const paymentMethodChartData = ref<any[]>([])
  const refundRequests = ref<Payment[]>([])
  const activeOrders = ref<any[]>([])
  const orderCounts = ref<{
    total_active: number
    paid_orders: number
    unpaid_orders: number
    cleared_today: number
  }>({
    total_active: 0,
    paid_orders: 0,
    unpaid_orders: 0,
    cleared_today: 0,
  })

  const orderPagination = ref<{
    current_page: number
    last_page: number
    per_page: number
    total: number
    from: number | null
    to: number | null
  }>({
    current_page: 1,
    last_page: 1,
    per_page: 10,
    total: 0,
    from: null,
    to: null,
  })

  const payments = ref<Payment[]>([])
  const selectedPayment = ref<Payment | null>(null)
  const pagination = ref<Pagination>({ ...DEFAULT_PAGINATION })

  const loading = ref(false)
  const error = ref<string | null>(null)

  const isLoading = computed(() => loading.value)
  const hasError = computed(() => error.value !== null)
  const todayRevenue = computed(() => dashboardStats.value?.today_revenue ?? 0)
  const weeklyRevenue = computed(() => dashboardStats.value?.weekly_revenue ?? 0)
  const monthlyRevenue = computed(() => dashboardStats.value?.monthly_revenue ?? 0)

  async function fetchReport(
    fetcher: (filters?: ReportFilters) => Promise<{ success: boolean; data?: any }>,
    filters?: ReportFilters,
    fallbackError = 'Failed to fetch report',
  ) {
    try {
      loading.value = true
      error.value = null
      const response = await fetcher(filters)
      return response.success ? response.data : null
    } catch (err: any) {
      console.error(`[cashierStore] ${fallbackError}:`, err)
      error.value = err?.message || fallbackError
      return null
    } finally {
      loading.value = false
    }
  }

  // Actions
  async function fetchDashboardStats() {
    try {
      loading.value = true
      error.value = null
      const response = await cashierService.getDashboardStats()
      if (response.success) {
        dashboardStats.value = response.data
      }
    } catch (err: any) {
      console.error('[cashierStore] Failed to fetch dashboard stats:', err)
      error.value = err.message || 'Failed to fetch dashboard statistics'
    } finally {
      loading.value = false
    }
  }

  async function fetchRecentPayments() {
    try {
      const response = await cashierService.getRecentPayments()
      if (response.success) {
        recentPayments.value = response.data
      }
    } catch (err: any) {
      console.error('[cashierStore] Failed to fetch recent payments:', err)
    }
  }

  async function fetchPendingPayments() {
    try {
      const response = await cashierService.getPendingPayments()
      if (response.success) {
        pendingPayments.value = response.data
      }
    } catch (err: any) {
      console.error('[cashierStore] Failed to fetch pending payments:', err)
    }
  }

  async function fetchRecentTransactions() {
    try {
      const response = await cashierService.getRecentTransactions()
      if (response.success) {
        recentTransactions.value = response.data
      }
    } catch (err: any) {
      console.error('[cashierStore] Failed to fetch recent transactions:', err)
    }
  }

  async function fetchRevenueChart() {
    try {
      const response = await cashierService.getRevenueChart()
      if (response.success) {
        revenueChartData.value = response.data
      }
    } catch (err: any) {
      console.error('[cashierStore] Failed to fetch revenue chart:', err)
    }
  }

  async function fetchPaymentMethodChart() {
    try {
      const response = await cashierService.getPaymentMethodChart()
      if (response.success) {
        paymentMethodChartData.value = response.data
      }
    } catch (err: any) {
      console.error('[cashierStore] Failed to fetch payment method chart:', err)
    }
  }

  async function fetchRefundRequests() {
    try {
      const response = await cashierService.getRefundRequests()
      if (response.success) {
        refundRequests.value = response.data
      }
    } catch (err: any) {
      console.error('[cashierStore] Failed to fetch refund requests:', err)
    }
  }

  async function fetchOrders(filters?: {
    filter?: string
    search?: string
    page?: number
    per_page?: number
  }) {
    try {
      const params = {
        page: filters?.page ?? orderPagination.value.current_page,
        per_page: filters?.per_page ?? orderPagination.value.per_page,
        filter: filters?.filter,
        search: filters?.search,
        payment_status: filters?.payment_status,
        order_status: filters?.order_status,
        order_type: filters?.order_type,
        payment_method: filters?.payment_method,
      }
      const response = await cashierService.getActiveOrders(params)
      if (response.success) {
        activeOrders.value = response.data || []
        if (response.counts) {
          orderCounts.value = response.counts
        }
        if (response.pagination) {
          orderPagination.value = response.pagination
        }
      }
    } catch (err: any) {
      console.error('[cashierStore] Failed to fetch active orders:', err)
    }
  }

  async function clearOrder(
    id: string,
    payload?: { mark_as_paid?: boolean; payment_method?: string },
  ) {
    try {
      loading.value = true
      const response = await cashierService.clearOrder(id, payload)
      if (response.success) {
        await Promise.all([fetchOrders(), fetchDashboardStats(), fetchRecentPayments()])
        return response
      }
      throw new Error(response.message || 'Failed to clear order')
    } catch (err: any) {
      console.error('[cashierStore] Failed to clear order:', err)
      throw err
    } finally {
      loading.value = false
    }
  }

  async function loadDashboard() {
    loading.value = true
    error.value = null
    try {
      const [
        statsRes,
        recentRes,
        pendingRes,
        transRes,
        revRes,
        payMethodRes,
        refundRes,
        ordersRes,
      ] = await Promise.allSettled([
        cashierService.getDashboardStats(),
        cashierService.getRecentPayments(),
        cashierService.getPendingPayments(),
        cashierService.getRecentTransactions(),
        cashierService.getRevenueChart(),
        cashierService.getPaymentMethodChart(),
        cashierService.getRefundRequests(),
        cashierService.getActiveOrders(),
      ])

      if (statsRes.status === 'fulfilled' && statsRes.value?.success) {
        dashboardStats.value = statsRes.value.data
      }
      if (recentRes.status === 'fulfilled' && recentRes.value?.success) {
        recentPayments.value = recentRes.value.data
      }
      if (pendingRes.status === 'fulfilled' && pendingRes.value?.success) {
        pendingPayments.value = pendingRes.value.data
      }
      if (transRes.status === 'fulfilled' && transRes.value?.success) {
        recentTransactions.value = transRes.value.data
      }
      if (revRes.status === 'fulfilled' && revRes.value?.success) {
        revenueChartData.value = revRes.value.data
      }
      if (payMethodRes.status === 'fulfilled' && payMethodRes.value?.success) {
        paymentMethodChartData.value = payMethodRes.value.data
      }
      if (refundRes.status === 'fulfilled' && refundRes.value?.success) {
        refundRequests.value = refundRes.value.data
      }
      if (ordersRes.status === 'fulfilled' && ordersRes.value?.success) {
        activeOrders.value = ordersRes.value.data || []
        if (ordersRes.value.counts) {
          orderCounts.value = ordersRes.value.counts
        }
      }
    } catch (err: any) {
      console.error('[cashierStore] Error loading dashboard:', err)
      error.value = err?.message || 'Failed to load cashier dashboard'
    } finally {
      loading.value = false
    }
  }

  async function fetchPayments(filters?: PaymentFilters) {
    try {
      loading.value = true
      error.value = null
      const response = await cashierService.getPayments(filters)
      if (response.success) {
        payments.value = response.data
        pagination.value = response.pagination
      }
    } catch (err: any) {
      console.error('[cashierStore] Failed to fetch payments:', err)
      error.value = err.message || 'Failed to fetch payments'
    } finally {
      loading.value = false
    }
  }

  async function fetchPaymentById(id: string) {
    try {
      loading.value = true
      error.value = null
      const response = await cashierService.getPaymentById(id)
      if (response.success) {
        selectedPayment.value = response.data
      }
    } catch (err: any) {
      console.error('[cashierStore] Failed to fetch payment details:', err)
      error.value = err.message || 'Failed to fetch payment details'
    } finally {
      loading.value = false
    }
  }

  async function processRefund(id: string) {
    try {
      loading.value = true
      error.value = null
      const response = await cashierService.refundPayment(id)
      if (response.success) {
        const item = payments.value.find((p) => p.id === id)
        if (item) {
          item.status = 'refunded'
        }
        if (selectedPayment.value?.id === id) {
          selectedPayment.value.status = 'refunded'
        }
        return true
      }
      return false
    } catch (err: any) {
      console.error('[cashierStore] Failed to process refund:', err)
      error.value = err.message || 'Failed to process refund'
      return false
    } finally {
      loading.value = false
    }
  }

  async function fetchRevenueReport(filters?: ReportFilters) {
    return fetchReport(cashierService.getRevenueReport, filters, 'Failed to fetch revenue report')
  }

  async function fetchPaymentReport(filters?: ReportFilters) {
    return fetchReport(cashierService.getPaymentReport, filters, 'Failed to fetch payment report')
  }

  async function fetchRefundReport(filters?: ReportFilters) {
    return fetchReport(cashierService.getRefundReport, filters, 'Failed to fetch refund report')
  }

  function clearError() {
    error.value = null
  }

  function clearSelectedPayment() {
    selectedPayment.value = null
  }

  return {
    dashboardStats,
    recentPayments,
    pendingPayments,
    recentTransactions,
    revenueChartData,
    paymentMethodChartData,
    refundRequests,
    payments,
    selectedPayment,
    pagination,
    loading,
    error,

    isLoading,
    hasError,
    todayRevenue,
    weeklyRevenue,
    monthlyRevenue,

    fetchDashboardStats,
    fetchRecentPayments,
    fetchPendingPayments,
    fetchRecentTransactions,
    fetchRevenueChart,
    fetchPaymentMethodChart,
    fetchRefundRequests,
    loadDashboard,

    fetchPayments,
    fetchPaymentById,
    processRefund,

    fetchRevenueReport,
    fetchPaymentReport,
    fetchRefundReport,

    activeOrders,
    orderCounts,
    orderPagination,
    fetchOrders,
    clearOrder,

    clearError,
    clearSelectedPayment,
  }
})

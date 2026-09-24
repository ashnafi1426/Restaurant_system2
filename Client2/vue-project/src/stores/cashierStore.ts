import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import cashierService, { type PaymentFilters, type ReportFilters } from '@/services/cashierService'

interface DashboardStats {
  today_revenue: number
  weekly_revenue: number
  monthly_revenue: number
  pending_payments: number
  completed_payments: number
  failed_payments: number
  refund_requests: number
  total_transactions: number
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

interface Pagination {
  current_page: number
  last_page: number
  per_page: number
  total: number
  from: number | null
  to: number | null
}

export const useCashierStore = defineStore('cashier', () => {
  const dashboardStats = ref<DashboardStats | null>(null)
  const recentPayments = ref<Payment[]>([])
  const pendingPayments = ref<Payment[]>([])
  const recentTransactions = ref<Payment[]>([])
  const revenueChartData = ref<any[]>([])
  const paymentMethodChartData = ref<any[]>([])
  const refundRequests = ref<Payment[]>([])

  const payments = ref<Payment[]>([])
  const selectedPayment = ref<Payment | null>(null)
  const pagination = ref<Pagination>({
    current_page: 1,
    last_page: 1,
    per_page: 15,
    total: 0,
    from: null,
    to: null,
  })

  const loading = ref(false)
  const error = ref<string | null>(null)

  const isLoading = computed(() => loading.value)
  const hasError = computed(() => error.value !== null)
  const todayRevenue = computed(() => dashboardStats.value?.today_revenue ?? 0)
  const weeklyRevenue = computed(() => dashboardStats.value?.weekly_revenue ?? 0)
  const monthlyRevenue = computed(() => dashboardStats.value?.monthly_revenue ?? 0)

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

  async function loadDashboard() {
    loading.value = true
    try {
      await Promise.all([
        fetchDashboardStats(),
        fetchRecentPayments(),
        fetchPendingPayments(),
        fetchRecentTransactions(),
        fetchRevenueChart(),
        fetchPaymentMethodChart(),
        fetchRefundRequests(),
      ])
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
        const index = payments.value.findIndex((p) => p.id === id)
        if (index !== -1) {
          payments.value[index].status = 'refunded'
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
    try {
      loading.value = true
      error.value = null
      const response = await cashierService.getRevenueReport(filters)
      return response.success ? response.data : null
    } catch (err: any) {
      console.error('[cashierStore] Failed to fetch revenue report:', err)
      error.value = err.message || 'Failed to fetch revenue report'
      return null
    } finally {
      loading.value = false
    }
  }

  async function fetchPaymentReport(filters?: ReportFilters) {
    try {
      loading.value = true
      error.value = null
      const response = await cashierService.getPaymentReport(filters)
      return response.success ? response.data : null
    } catch (err: any) {
      console.error('[cashierStore] Failed to fetch payment report:', err)
      error.value = err.message || 'Failed to fetch payment report'
      return null
    } finally {
      loading.value = false
    }
  }

  async function fetchRefundReport(filters?: ReportFilters) {
    try {
      loading.value = true
      error.value = null
      const response = await cashierService.getRefundReport(filters)
      return response.success ? response.data : null
    } catch (err: any) {
      console.error('[cashierStore] Failed to fetch refund report:', err)
      error.value = err.message || 'Failed to fetch refund report'
      return null
    } finally {
      loading.value = false
    }
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

    clearError,
    clearSelectedPayment,
  }
})

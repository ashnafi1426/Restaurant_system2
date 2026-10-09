import api from '@/api/auth'

const API_BASE_URL = 'http://127.0.0.1:8000/api'

export async function getDashboardStats() {
  const response = await api.get(`/cashier/dashboard`)
  return response.data
}

export async function getRecentPayments() {
  const response = await api.get(`/cashier/dashboard/recent-payments`)
  return response.data
}

export async function getPendingPayments() {
  const response = await api.get(`/cashier/dashboard/pending-payments`)
  return response.data
}

export async function getRecentTransactions() {
  const response = await api.get(`/cashier/dashboard/recent-transactions`)
  return response.data
}

export async function getRevenueChart() {
  const response = await api.get(`/cashier/dashboard/revenue-chart`)
  return response.data
}

export async function getPaymentMethodChart() {
  const response = await api.get(`/cashier/dashboard/payment-method-chart`)
  return response.data
}

export async function getRefundRequests() {
  const response = await api.get(`/cashier/dashboard/refund-requests`)
  return response.data
}

export interface PaymentFilters {
  search?: string
  status?: string
  provider?: string
  type?: string
  date_from?: string
  date_to?: string
  filter?: 'today' | 'week' | 'month' | 'paid' | 'pending' | 'failed' | 'refunded'
  sort_by?: string
  sort_order?: 'asc' | 'desc'
  per_page?: number
  page?: number
}

export async function getPayments(filters?: PaymentFilters) {
  const response = await api.get(`/cashier/payments`, {
    params: filters,
  })
  return response.data
}

export async function getPaymentById(id: string) {
  const response = await api.get(`/cashier/payments/${id}`)
  return response.data
}

export async function refundPayment(id: string) {
  const response = await api.post(`/cashier/payments/${id}/refund`, {})
  return response.data
}
export interface ReportFilters {
  period?: 'daily' | 'weekly' | 'monthly' | 'yearly'
  date_from?: string
  date_to?: string
}
export async function getRevenueReport(filters?: ReportFilters) {
  const response = await api.get(`/cashier/reports/revenue`, {
    params: filters,
  })
  return response.data
}

export async function getPaymentReport(filters?: ReportFilters) {
  const response = await api.get(`/cashier/reports/payment`, {
    params: filters,
  })
  return response.data
}

export async function getRefundReport(filters?: ReportFilters) {
  const response = await api.get(`/cashier/reports/refund`, {
    params: filters,
  })
  return response.data
}

export interface CashierOrderFilters {
  filter?: 'all' | 'paid' | 'unpaid' | 'cleared' | 'pending_clear'
  search?: string
  page?: number
  per_page?: number
  payment_status?: string
  order_status?: string
  order_type?: string
  payment_method?: string
}

export async function getActiveOrders(filters?: CashierOrderFilters) {
  const response = await api.get(`/cashier/dashboard/orders`, {
    params: filters,
  })
  return response.data
}

export async function clearOrder(id: string, payload?: { mark_as_paid?: boolean; payment_method?: string }) {
  const response = await api.post(`/cashier/dashboard/orders/${id}/clear`, payload || {})
  return response.data
}

export default {
  getDashboardStats,
  getRecentPayments,
  getPendingPayments,
  getRecentTransactions,
  getRevenueChart,
  getPaymentMethodChart,
  getRefundRequests,
  getActiveOrders,
  clearOrder,

  getPayments,
  getPaymentById,
  refundPayment,

  getRevenueReport,
  getPaymentReport,
  getRefundReport,
}

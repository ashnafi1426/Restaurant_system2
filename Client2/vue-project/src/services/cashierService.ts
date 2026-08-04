import axios from 'axios'

const API_BASE_URL = 'http://127.0.0.1:8000/api'
const getAuthHeader = () => {
  const token = localStorage.getItem('token')
  return token ? { Authorization: `Bearer ${token}` } : {}
}
export async function getDashboardStats() {
  const response = await axios.get(`${API_BASE_URL}/cashier/dashboard`, {
    headers: getAuthHeader(),
  })
  return response.data
}
export async function getRecentPayments() {
  const response = await axios.get(`${API_BASE_URL}/cashier/dashboard/recent-payments`, {
    headers: getAuthHeader(),
  })
  return response.data
}

export async function getPendingPayments() {
  const response = await axios.get(`${API_BASE_URL}/cashier/dashboard/pending-payments`, {
    headers: getAuthHeader(),
  })
  return response.data
}

export async function getRecentTransactions() {
  const response = await axios.get(`${API_BASE_URL}/cashier/dashboard/recent-transactions`, {
    headers: getAuthHeader(),
  })
  return response.data
}

export async function getRevenueChart() {
  const response = await axios.get(`${API_BASE_URL}/cashier/dashboard/revenue-chart`, {
    headers: getAuthHeader(),
  })
  return response.data
}

export async function getPaymentMethodChart() {
  const response = await axios.get(`${API_BASE_URL}/cashier/dashboard/payment-method-chart`, {
    headers: getAuthHeader(),
  })
  return response.data
}

export async function getRefundRequests() {
  const response = await axios.get(`${API_BASE_URL}/cashier/dashboard/refund-requests`, {
    headers: getAuthHeader(),
  })
  return response.data
}

// ============================================================================
// Payments
// ============================================================================

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
  const response = await axios.get(`${API_BASE_URL}/cashier/payments`, {
    headers: getAuthHeader(),
    params: filters,
  })
  return response.data
}

export async function getPaymentById(id: string) {
  const response = await axios.get(`${API_BASE_URL}/cashier/payments/${id}`, {
    headers: getAuthHeader(),
  })
  return response.data
}

export async function refundPayment(id: string) {
  const response = await axios.post(`${API_BASE_URL}/cashier/payments/${id}/refund`, {}, {
    headers: getAuthHeader(),
  })
  return response.data
}
export interface ReportFilters {
  period?: 'daily' | 'weekly' | 'monthly' | 'yearly'
  date_from?: string
  date_to?: string
}
export async function getRevenueReport(filters?: ReportFilters) {
  const response = await axios.get(`${API_BASE_URL}/cashier/reports/revenue`, {
    headers: getAuthHeader(),
    params: filters,
  })
  return response.data
}

export async function getPaymentReport(filters?: ReportFilters) {
  const response = await axios.get(`${API_BASE_URL}/cashier/reports/payment`, {
    headers: getAuthHeader(),
    params: filters,
  })
  return response.data
}

export async function getRefundReport(filters?: ReportFilters) {
  const response = await axios.get(`${API_BASE_URL}/cashier/reports/refund`, {
    headers: getAuthHeader(),
    params: filters,
  })
  return response.data
}

export default {
  // Dashboard
  getDashboardStats,
  getRecentPayments,
  getPendingPayments,
  getRecentTransactions,
  getRevenueChart,
  getPaymentMethodChart,
  getRefundRequests,

  // Payments
  getPayments,
  getPaymentById,
  refundPayment,

  // Reports
  getRevenueReport,
  getPaymentReport,
  getRefundReport,
}

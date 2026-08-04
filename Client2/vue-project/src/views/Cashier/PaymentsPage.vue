<script setup lang="ts">
import { ref, onMounted, watch } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import DashboardLayout from '../../layouts/DashboardLayout.vue'
import { useCashierStore } from '@/stores/cashierStore'
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
} from 'lucide-vue-next'

const router = useRouter()
const route = useRoute()
const cashierStore = useCashierStore()

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
  per_page: 15,
  page: 1,
})

const showFilters = ref(false)

// Load payments
onMounted(() => {
  loadPayments()
})

// Watch for query parameter changes
watch(() => route.query.filter, (newFilter) => {
  if (newFilter) {
    filters.value.filter = newFilter as string
    loadPayments()
  }
})

const loadPayments = async () => {
  await cashierStore.fetchPayments(filters.value)
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
    per_page: 15,
    page: 1,
  }
  loadPayments()
}

const setQuickFilter = (filter: string) => {
  filters.value.filter = filter
  filters.value.page = 1
  loadPayments()
}

// Pagination handlers
const goToPage = (page: number) => {
  filters.value.page = page
  loadPayments()
}

const nextPage = () => {
  if (filters.value.page < cashierStore.pagination.last_page) {
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

// Format helpers
const formatCurrency = (amount: number | string) => {
  const numAmount = typeof amount === 'string' ? parseFloat(amount) : amount
  return `${numAmount.toFixed(2)} ETB`
}

const formatDate = (date: string) => {
  return new Date(date).toLocaleDateString('en-US', {
    month: 'short',
    day: 'numeric',
    year: 'numeric',
    hour: '2-digit',
    minute: '2-digit',
  })
}

const getStatusColor = (status: string) => {
  const statusColors: Record<string, string> = {
    paid: 'bg-green-100 text-green-700 dark:bg-green-900 dark:text-green-300',
    verified: 'bg-green-100 text-green-700 dark:bg-green-900 dark:text-green-300',
    pending: 'bg-yellow-100 text-yellow-700 dark:bg-yellow-900 dark:text-yellow-300',
    initialized: 'bg-blue-100 text-blue-700 dark:bg-blue-900 dark:text-blue-300',
    failed: 'bg-red-100 text-red-700 dark:bg-red-900 dark:text-red-300',
    refunded: 'bg-purple-100 text-purple-700 dark:bg-purple-900 dark:text-purple-300',
  }
  return statusColors[status.toLowerCase()] || 'bg-gray-100 text-gray-700'
}

// View payment details
const viewPayment = (id: string) => {
  router.push({ name: 'cashier-payment-detail', params: { id } })
}
</script>

<template>
  <DashboardLayout>
    <div class="space-y-6">
      <!-- Header -->
      <div class="flex justify-between items-center">
        <div>
          <h1 class="text-3xl font-bold text-slate-800 dark:text-white">Payments</h1>
          <p class="text-slate-500 dark:text-slate-400 mt-1">
            View and manage all payment transactions
          </p>
        </div>
        <button
          @click="loadPayments"
          class="flex items-center gap-2 px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg transition-colors"
        >
          <RefreshCw :size="18" />
          Refresh
        </button>
      </div>

      <!-- Quick Filters -->
      <div class="flex flex-wrap gap-2">
        <button
          @click="setQuickFilter('')"
          :class="[
            'px-4 py-2 rounded-lg transition-colors',
            filters.filter === ''
              ? 'bg-blue-600 text-white'
              : 'bg-white dark:bg-slate-800 text-slate-700 dark:text-slate-300 border dark:border-slate-700 hover:bg-slate-50 dark:hover:bg-slate-700',
          ]"
        >
          All
        </button>
        <button
          @click="setQuickFilter('today')"
          :class="[
            'px-4 py-2 rounded-lg transition-colors',
            filters.filter === 'today'
              ? 'bg-blue-600 text-white'
              : 'bg-white dark:bg-slate-800 text-slate-700 dark:text-slate-300 border dark:border-slate-700 hover:bg-slate-50 dark:hover:bg-slate-700',
          ]"
        >
          Today
        </button>
        <button
          @click="setQuickFilter('week')"
          :class="[
            'px-4 py-2 rounded-lg transition-colors',
            filters.filter === 'week'
              ? 'bg-blue-600 text-white'
              : 'bg-white dark:bg-slate-800 text-slate-700 dark:text-slate-300 border dark:border-slate-700 hover:bg-slate-50 dark:hover:bg-slate-700',
          ]"
        >
          This Week
        </button>
        <button
          @click="setQuickFilter('month')"
          :class="[
            'px-4 py-2 rounded-lg transition-colors',
            filters.filter === 'month'
              ? 'bg-blue-600 text-white'
              : 'bg-white dark:bg-slate-800 text-slate-700 dark:text-slate-300 border dark:border-slate-700 hover:bg-slate-50 dark:hover:bg-slate-700',
          ]"
        >
          This Month
        </button>
        <button
          @click="setQuickFilter('paid')"
          :class="[
            'px-4 py-2 rounded-lg transition-colors',
            filters.filter === 'paid'
              ? 'bg-green-600 text-white'
              : 'bg-white dark:bg-slate-800 text-slate-700 dark:text-slate-300 border dark:border-slate-700 hover:bg-slate-50 dark:hover:bg-slate-700',
          ]"
        >
          Paid
        </button>
        <button
          @click="setQuickFilter('pending')"
          :class="[
            'px-4 py-2 rounded-lg transition-colors',
            filters.filter === 'pending'
              ? 'bg-yellow-600 text-white'
              : 'bg-white dark:bg-slate-800 text-slate-700 dark:text-slate-300 border dark:border-slate-700 hover:bg-slate-50 dark:hover:bg-slate-700',
          ]"
        >
          Pending
        </button>
        <button
          @click="setQuickFilter('failed')"
          :class="[
            'px-4 py-2 rounded-lg transition-colors',
            filters.filter === 'failed'
              ? 'bg-red-600 text-white'
              : 'bg-white dark:bg-slate-800 text-slate-700 dark:text-slate-300 border dark:border-slate-700 hover:bg-slate-50 dark:hover:bg-slate-700',
          ]"
        >
          Failed
        </button>
        <button
          @click="setQuickFilter('refunded')"
          :class="[
            'px-4 py-2 rounded-lg transition-colors',
            filters.filter === 'refunded'
              ? 'bg-purple-600 text-white'
              : 'bg-white dark:bg-slate-800 text-slate-700 dark:text-slate-300 border dark:border-slate-700 hover:bg-slate-50 dark:hover:bg-slate-700',
          ]"
        >
          Refunded
        </button>
      </div>

      <!-- Search and Filters -->
      <div class="bg-white dark:bg-slate-800 rounded-xl border dark:border-slate-700 shadow-sm p-4">
        <div class="flex gap-4">
          <div class="flex-1 relative">
            <Search
              :size="20"
              class="absolute left-3 top-1/2 transform -translate-y-1/2 text-slate-400"
            />
            <input
              v-model="filters.search"
              @keyup.enter="handleSearch"
              type="text"
              placeholder="Search by transaction ref, email, or name..."
              class="w-full pl-10 pr-4 py-2 border dark:border-slate-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent dark:bg-slate-700 dark:text-white"
            />
          </div>
          <button
            @click="handleSearch"
            class="px-6 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg transition-colors"
          >
            Search
          </button>
          <button
            @click="showFilters = !showFilters"
            class="flex items-center gap-2 px-4 py-2 border dark:border-slate-600 rounded-lg hover:bg-slate-50 dark:hover:bg-slate-700 transition-colors dark:text-white"
          >
            <Filter :size="18" />
            Filters
          </button>
        </div>

        <!-- Advanced Filters -->
        <div v-if="showFilters" class="mt-4 pt-4 border-t dark:border-slate-700 grid grid-cols-1 md:grid-cols-3 gap-4">
          <div>
            <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">
              Status
            </label>
            <select
              v-model="filters.status"
              class="w-full px-4 py-2 border dark:border-slate-600 rounded-lg focus:ring-2 focus:ring-blue-500 dark:bg-slate-700 dark:text-white"
            >
              <option value="">All Statuses</option>
              <option value="paid">Paid</option>
              <option value="verified">Verified</option>
              <option value="pending">Pending</option>
              <option value="initialized">Initialized</option>
              <option value="failed">Failed</option>
              <option value="refunded">Refunded</option>
            </select>
          </div>
          <div>
            <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">
              Type
            </label>
            <select
              v-model="filters.type"
              class="w-full px-4 py-2 border dark:border-slate-600 rounded-lg focus:ring-2 focus:ring-blue-500 dark:bg-slate-700 dark:text-white"
            >
              <option value="">All Types</option>
              <option value="reservation">Reservation</option>
              <option value="order">Restaurant Order</option>
            </select>
          </div>
          <div>
            <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">
              Provider
            </label>
            <select
              v-model="filters.provider"
              class="w-full px-4 py-2 border dark:border-slate-600 rounded-lg focus:ring-2 focus:ring-blue-500 dark:bg-slate-700 dark:text-white"
            >
              <option value="">All Providers</option>
              <option value="chapa">Chapa</option>
            </select>
          </div>
          <div>
            <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">
              Date From
            </label>
            <input
              v-model="filters.date_from"
              type="date"
              class="w-full px-4 py-2 border dark:border-slate-600 rounded-lg focus:ring-2 focus:ring-blue-500 dark:bg-slate-700 dark:text-white"
            />
          </div>
          <div>
            <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">
              Date To
            </label>
            <input
              v-model="filters.date_to"
              type="date"
              class="w-full px-4 py-2 border dark:border-slate-600 rounded-lg focus:ring-2 focus:ring-blue-500 dark:bg-slate-700 dark:text-white"
            />
          </div>
          <div class="flex items-end gap-2">
            <button
              @click="applyFilters"
              class="flex-1 px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg transition-colors"
            >
              Apply Filters
            </button>
            <button
              @click="clearFilters"
              class="px-4 py-2 border dark:border-slate-600 rounded-lg hover:bg-slate-50 dark:hover:bg-slate-700 transition-colors"
            >
              <X :size="18" class="dark:text-white" />
            </button>
          </div>
        </div>
      </div>

      <!-- Payments Table -->
      <div class="bg-white dark:bg-slate-800 rounded-xl border dark:border-slate-700 shadow-sm">
        <div class="overflow-x-auto">
          <table class="w-full">
            <thead class="bg-slate-50 dark:bg-slate-900">
              <tr>
                <th
                  @click="sortBy('tx_ref')"
                  class="text-left p-4 text-sm font-semibold text-slate-700 dark:text-slate-300 cursor-pointer hover:bg-slate-100 dark:hover:bg-slate-800"
                >
                  Transaction Ref
                </th>
                <th
                  @click="sortBy('customer_name')"
                  class="text-left p-4 text-sm font-semibold text-slate-700 dark:text-slate-300 cursor-pointer hover:bg-slate-100 dark:hover:bg-slate-800"
                >
                  Customer
                </th>
                <th
                  @click="sortBy('amount')"
                  class="text-left p-4 text-sm font-semibold text-slate-700 dark:text-slate-300 cursor-pointer hover:bg-slate-100 dark:hover:bg-slate-800"
                >
                  Amount
                </th>
                <th class="text-left p-4 text-sm font-semibold text-slate-700 dark:text-slate-300">
                  Type
                </th>
                <th class="text-left p-4 text-sm font-semibold text-slate-700 dark:text-slate-300">
                  Method
                </th>
                <th
                  @click="sortBy('status')"
                  class="text-left p-4 text-sm font-semibold text-slate-700 dark:text-slate-300 cursor-pointer hover:bg-slate-100 dark:hover:bg-slate-800"
                >
                  Status
                </th>
                <th
                  @click="sortBy('created_at')"
                  class="text-left p-4 text-sm font-semibold text-slate-700 dark:text-slate-300 cursor-pointer hover:bg-slate-100 dark:hover:bg-slate-800"
                >
                  Date
                </th>
                <th class="text-left p-4 text-sm font-semibold text-slate-700 dark:text-slate-300">
                  Actions
                </th>
              </tr>
            </thead>
            <tbody>
              <tr
                v-if="cashierStore.isLoading"
                v-for="i in 5"
                :key="i"
                class="border-t dark:border-slate-700"
              >
                <td colspan="8" class="p-4">
                  <div class="h-6 bg-slate-200 dark:bg-slate-700 rounded animate-pulse"></div>
                </td>
              </tr>

              <tr v-else-if="cashierStore.payments.length === 0" class="border-t dark:border-slate-700">
                <td colspan="8" class="p-12 text-center text-slate-500 dark:text-slate-400">
                  No payments found
                </td>
              </tr>

              <tr
                v-else
                v-for="payment in cashierStore.payments"
                :key="payment.id"
                class="border-t dark:border-slate-700 hover:bg-slate-50 dark:hover:bg-slate-900 transition-colors cursor-pointer"
                @click="viewPayment(payment.id)"
              >
                <td class="p-4 text-sm text-slate-700 dark:text-slate-300 font-mono">
                  {{ payment.tx_ref }}
                </td>
                <td class="p-4">
                  <div class="text-sm text-slate-800 dark:text-white font-medium">
                    {{ payment.customer_name }}
                  </div>
                  <div class="text-xs text-slate-500 dark:text-slate-400">
                    {{ payment.email }}
                  </div>
                </td>
                <td class="p-4 text-sm font-semibold text-slate-800 dark:text-white">
                  {{ formatCurrency(payment.amount) }}
                </td>
                <td class="p-4 text-sm text-slate-600 dark:text-slate-400">
                  {{ payment.type }}
                </td>
                <td class="p-4 text-sm text-slate-600 dark:text-slate-400 capitalize">
                  {{ payment.payment_method || '-' }}
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
                    @click.stop="viewPayment(payment.id)"
                    class="flex items-center gap-1 text-sm text-blue-600 hover:text-blue-700 dark:text-blue-400"
                  >
                    <Eye :size="16" />
                    View
                  </button>
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <!-- Pagination -->
        <div
          v-if="cashierStore.pagination.total > 0"
          class="px-6 py-4 border-t dark:border-slate-700 flex items-center justify-between"
        >
          <div class="text-sm text-slate-600 dark:text-slate-400">
            Showing {{ cashierStore.pagination.from }} to {{ cashierStore.pagination.to }} of
            {{ cashierStore.pagination.total }} payments
          </div>
          <div class="flex items-center gap-2">
            <button
              @click="previousPage"
              :disabled="cashierStore.pagination.current_page === 1"
              class="p-2 border dark:border-slate-600 rounded-lg hover:bg-slate-50 dark:hover:bg-slate-700 disabled:opacity-50 disabled:cursor-not-allowed transition-colors"
            >
              <ChevronLeft :size="18" class="dark:text-white" />
            </button>
            <div class="flex gap-1">
              <button
                v-for="page in cashierStore.pagination.last_page"
                :key="page"
                @click="goToPage(page)"
                :class="[
                  'px-3 py-1 rounded-lg transition-colors',
                  page === cashierStore.pagination.current_page
                    ? 'bg-blue-600 text-white'
                    : 'border dark:border-slate-600 hover:bg-slate-50 dark:hover:bg-slate-700 dark:text-white',
                ]"
              >
                {{ page }}
              </button>
            </div>
            <button
              @click="nextPage"
              :disabled="cashierStore.pagination.current_page === cashierStore.pagination.last_page"
              class="p-2 border dark:border-slate-600 rounded-lg hover:bg-slate-50 dark:hover:bg-slate-700 disabled:opacity-50 disabled:cursor-not-allowed transition-colors"
            >
              <ChevronRight :size="18" class="dark:text-white" />
            </button>
          </div>
        </div>
      </div>
    </div>
  </DashboardLayout>
</template>

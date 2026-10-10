<script setup lang="ts">
import { ref, onMounted, computed, watch } from 'vue'
import DashboardLayout from '../../Layouts/DashboardLayout.vue'
import { useCashierStore } from '@/stores/cashierStore'
import { useHotelStore } from '@/stores/hotelStore'
import { useLanguageStore } from '@/stores/language'
import {
  TrendingUp,
  DollarSign,
  Download,
  Calendar,
  CreditCard,
  RefreshCw,
  BarChart3,
  PieChart,
  ArrowUpRight,
  Filter,
  FileSpreadsheet,
  Printer,
  Building,
  CheckCircle,
  Clock,
  XCircle,
  ChevronLeft,
  ChevronRight,
  Building2,
} from 'lucide-vue-next'

const cashierStore = useCashierStore()
const hotelStore = useHotelStore()
const languageStore = useLanguageStore()
const activeTab = ref<'revenue' | 'payment' | 'refund'>('revenue')
const dateFrom = ref(new Date(new Date().setDate(1)).toISOString().split('T')[0])
const dateTo = ref(new Date().toISOString().split('T')[0])
const period = ref<'daily' | 'weekly' | 'monthly' | 'yearly'>('daily')
const revenueReport = ref<any>(null)
const paymentReport = ref<any>(null)
const refundReport = ref<any>(null)
const loading = ref(false)
const showFilters = ref(false)

const currentPage = ref(1)
const perPage = ref(10)

const quickDateFilters = computed(() => [
  { label: languageStore.t('today', 'Today'), value: 'today' },
  { label: languageStore.t('this_week', 'This Week'), value: 'week' },
  { label: languageStore.t('this_month', 'This Month'), value: 'month' },
  { label: languageStore.t('this_year', 'This Year'), value: 'year' },
])

const paginatedDailyBreakdown = computed(() => {
  if (!revenueReport.value?.daily_breakdown) return []
  const start = (currentPage.value - 1) * perPage.value
  const end = start + perPage.value
  return revenueReport.value.daily_breakdown.slice(start, end)
})

const totalPages = computed(() => {
  if (!revenueReport.value?.daily_breakdown) return 0
  return Math.ceil(revenueReport.value.daily_breakdown.length / perPage.value) || 1
})

const showingFrom = computed(() => {
  const total = revenueReport.value?.daily_breakdown?.length || 0
  if (total === 0) return 0
  return (currentPage.value - 1) * perPage.value + 1
})

const showingTo = computed(() => {
  const total = revenueReport.value?.daily_breakdown?.length || 0
  return Math.min(currentPage.value * perPage.value, total)
})

const paginationPages = computed(() => {
  const pages: number[] = []
  const max = totalPages.value
  const cur = currentPage.value

  for (let i = Math.max(1, cur - 2); i <= Math.min(max, cur + 2); i++) {
    pages.push(i)
  }
  return pages
})

const changePerPage = (event: Event) => {
  const target = event.target as HTMLSelectElement
  perPage.value = Number(target.value)
  currentPage.value = 1
}

const nextPage = () => {
  if (currentPage.value < totalPages.value) {
    currentPage.value++
  }
}

const previousPage = () => {
  if (currentPage.value > 1) {
    currentPage.value--
  }
}

const goToPage = (p: number) => {
  if (p >= 1 && p <= totalPages.value) {
    currentPage.value = p
  }
}

const refundCurrentPage = ref(1)
const refundPerPage = ref(10)

const paginatedRefundsList = computed(() => {
  if (!refundReport.value?.refunds_list) return []
  const start = (refundCurrentPage.value - 1) * refundPerPage.value
  const end = start + refundPerPage.value
  return refundReport.value.refunds_list.slice(start, end)
})

const totalRefundPages = computed(() => {
  if (!refundReport.value?.refunds_list) return 0
  return Math.ceil(refundReport.value.refunds_list.length / refundPerPage.value) || 1
})

const changeRefundPerPage = (event: Event) => {
  const target = event.target as HTMLSelectElement
  refundPerPage.value = Number(target.value)
  refundCurrentPage.value = 1
}

const resetPagination = () => {
  currentPage.value = 1
  refundCurrentPage.value = 1
}

onMounted(() => {
  loadReports()
})

watch(
  () => hotelStore.hotelId,
  () => {
    loadReports()
  },
)

const loadReports = async () => {
  loading.value = true
  try {
    await Promise.all([loadRevenueReport(), loadPaymentReport(), loadRefundReport()])
    resetPagination()
  } catch (error) {
    console.error('[Cashier ReportsPage] Error loading reports:', error)
  } finally {
    loading.value = false
  }
}

const loadRevenueReport = async () => {
  revenueReport.value = await cashierStore.fetchRevenueReport({
    period: period.value,
    date_from: dateFrom.value,
    date_to: dateTo.value,
  })
}

const loadPaymentReport = async () => {
  paymentReport.value = await cashierStore.fetchPaymentReport({
    date_from: dateFrom.value,
    date_to: dateTo.value,
  })
}

const loadRefundReport = async () => {
  refundReport.value = await cashierStore.fetchRefundReport({
    date_from: dateFrom.value,
    date_to: dateTo.value,
  })
}

const applyFilters = () => {
  loadReports()
}

const setQuickDateFilter = (filter: string) => {
  const today = new Date()
  switch (filter) {
    case 'today':
      dateFrom.value = today.toISOString().split('T')[0]
      dateTo.value = today.toISOString().split('T')[0]
      break
    case 'week':
      const weekStart = new Date(today.setDate(today.getDate() - today.getDay()))
      dateFrom.value = weekStart.toISOString().split('T')[0]
      dateTo.value = new Date().toISOString().split('T')[0]
      break
    case 'month':
      dateFrom.value = new Date(today.getFullYear(), today.getMonth(), 1)
        .toISOString()
        .split('T')[0]
      dateTo.value = new Date().toISOString().split('T')[0]
      break
    case 'year':
      dateFrom.value = new Date(today.getFullYear(), 0, 1).toISOString().split('T')[0]
      dateTo.value = new Date().toISOString().split('T')[0]
      break
  }
  loadReports()
}

const revenueAnalytics = computed(() => {
  if (!revenueReport.value) return null
  const total = revenueReport.value.total_revenue || 1
  return {
    reservation_percentage: revenueReport.value.reservation_revenue
      ? ((revenueReport.value.reservation_revenue / total) * 100).toFixed(1)
      : '0.0',
    order_percentage: revenueReport.value.order_revenue
      ? ((revenueReport.value.order_revenue / total) * 100).toFixed(1)
      : '0.0',
  }
})

const currency = computed(() => hotelStore.currentHotel?.currency || 'ETB')

const formatCurrency = (amount: number | string) => {
  const numAmount = typeof amount === 'string' ? parseFloat(amount) : amount
  return `${(numAmount || 0).toFixed(2)} ${currency.value}`
}

const formatDate = (date: string) => {
  if (!date) return '-'
  return new Date(date).toLocaleDateString('en-US', {
    month: 'short',
    day: 'numeric',
    year: 'numeric',
  })
}

const printPage = () => {
  window.print()
}

const downloadExcel = () => {
  let csvContent = ''
  let filename = ''

  if (activeTab.value === 'revenue' && revenueReport.value) {
    filename = `revenue_report_${dateFrom.value}_to_${dateTo.value}.csv`
    const hotelBrand = hotelStore.hotelName ? `Hotel: ${hotelStore.hotelName}\n` : ''
    csvContent = `Revenue Report\n${hotelBrand}\n`
    csvContent += `Period:,${dateFrom.value} to ${dateTo.value}\n\n`
    csvContent += 'Metric,Value\n'
    csvContent += `Total Revenue,${revenueReport.value.total_revenue}\n`
    csvContent += `Total Transactions,${revenueReport.value.total_transactions}\n`
    csvContent += `Average Transaction,${revenueReport.value.average_transaction}\n\n`

    if (revenueReport.value.daily_breakdown) {
      csvContent += 'Daily Breakdown\n'
      csvContent += 'Date,Revenue,Transactions\n'
      revenueReport.value.daily_breakdown.forEach((day: any) => {
        csvContent += `${day.date},${day.revenue},${day.transactions}\n`
      })
    }
  }

  const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' })
  const link = document.createElement('a')
  link.href = URL.createObjectURL(blob)
  link.download = filename
  link.click()
}
</script>

<template>
  <DashboardLayout>
    <div
      class="space-y-6 bg-slate-50 dark:bg-slate-950 min-h-screen p-4 sm:p-6 max-w-full font-sans"
    >
      <div
        class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 p-6 rounded-3xl shadow-xs flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4"
      >
        <div>
          <div class="flex items-center gap-2">
            <h1 class="text-2xl font-black text-slate-900 dark:text-white tracking-tight">
              {{ languageStore.t('financial_reports', 'Financial Reports') }}
            </h1>
            <span
              v-if="hotelStore.hotelName"
              class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-xs font-bold bg-blue-100 dark:bg-blue-900/30 text-blue-800 dark:text-blue-300 border border-blue-200 dark:border-blue-700/50"
            >
              <Building2 class="w-3 h-3" />
              {{ hotelStore.hotelName }}
            </span>
          </div>
          <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">
            {{
              languageStore.t(
                'financial_reports_sub',
                'Comprehensive financial analytics, revenue breakdowns, and transaction insights.',
              )
            }}
          </p>
        </div>

        <div class="flex flex-wrap items-center gap-2">
          <button
            @click="printPage"
            class="px-3.5 py-2 rounded-xl bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 dark:hover:bg-slate-700 text-slate-800 dark:text-slate-200 text-xs font-bold transition flex items-center gap-1.5 cursor-pointer border border-slate-200 dark:border-slate-700"
          >
            <Printer class="w-3.5 h-3.5" />
            <span>{{ languageStore.t('print', 'Print') }}</span>
          </button>

          <button
            @click="loadReports"
            :disabled="loading"
            class="px-3.5 py-2 rounded-xl bg-blue-600 hover:bg-blue-700 text-white font-extrabold text-xs shadow-md shadow-blue-600/20 transition flex items-center gap-1.5 cursor-pointer disabled:opacity-50"
          >
            <RefreshCw :class="['w-3.5 h-3.5', loading && 'animate-spin']" />
            <span>{{ languageStore.t('refresh', 'Refresh') }}</span>
          </button>
        </div>
      </div>

      <div
        class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-3xl p-4 shadow-xs space-y-3"
      >
        <div class="flex items-center gap-2">
          <Calendar class="w-4 h-4 text-blue-500" />
          <h3
            class="text-xs font-extrabold text-slate-900 dark:text-white uppercase tracking-wider"
          >
            {{ languageStore.t('quick_date_filters', 'Quick Date Filters') }}
          </h3>
        </div>
        <div class="flex flex-wrap items-center gap-2">
          <button
            v-for="filter in quickDateFilters"
            :key="filter.value"
            @click="setQuickDateFilter(filter.value)"
            class="px-3.5 py-1.5 rounded-xl bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 dark:hover:bg-slate-700 text-slate-800 dark:text-slate-200 text-xs font-black transition cursor-pointer border border-slate-200 dark:border-slate-700"
          >
            {{ filter.label }}
          </button>
        </div>
      </div>

      <div
        class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-3xl overflow-hidden shadow-xs"
      >
        <div
          class="p-4 border-b border-slate-200 dark:border-slate-800 flex items-center justify-between bg-slate-50/50 dark:bg-slate-950/50"
        >
          <h3
            class="text-xs font-extrabold text-slate-900 dark:text-white flex items-center gap-2 uppercase tracking-wider"
          >
            <Filter class="w-4 h-4 text-blue-500" />
            {{ languageStore.t('advanced_date_filters', 'Advanced Date & Period Filters') }}
          </h3>
          <button
            @click="showFilters = !showFilters"
            class="text-xs font-bold text-blue-600 dark:text-blue-400 hover:underline cursor-pointer"
          >
            {{
              showFilters
                ? languageStore.t('hide_filters', 'Hide Filters')
                : languageStore.t('show_filters', 'Show Filters')
            }}
          </button>
        </div>

        <div v-if="showFilters" class="p-5 border-t border-slate-100 dark:border-slate-800">
          <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-4">
            <div>
              <label
                class="block text-[10px] font-bold uppercase text-slate-500 dark:text-slate-400 mb-1"
                >{{ languageStore.t('date_from', 'Date From') }}</label
              >
              <input
                v-model="dateFrom"
                type="date"
                class="w-full px-3 py-2 rounded-xl bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 text-slate-900 dark:text-white text-xs font-semibold focus:outline-none"
              />
            </div>
            <div>
              <label
                class="block text-[10px] font-bold uppercase text-slate-500 dark:text-slate-400 mb-1"
                >{{ languageStore.t('date_to', 'Date To') }}</label
              >
              <input
                v-model="dateTo"
                type="date"
                class="w-full px-3 py-2 rounded-xl bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 text-slate-900 dark:text-white text-xs font-semibold focus:outline-none"
              />
            </div>
            <div>
              <label
                class="block text-[10px] font-bold uppercase text-slate-500 dark:text-slate-400 mb-1"
                >{{ languageStore.t('period', 'Period') }}</label
              >
              <select
                v-model="period"
                class="w-full px-3 py-2 rounded-xl bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 text-slate-900 dark:text-white text-xs font-semibold focus:outline-none"
              >
                <option value="daily">{{ languageStore.t('daily', 'Daily') }}</option>
                <option value="weekly">{{ languageStore.t('weekly', 'Weekly') }}</option>
                <option value="monthly">{{ languageStore.t('monthly', 'Monthly') }}</option>
                <option value="yearly">{{ languageStore.t('yearly', 'Yearly') }}</option>
              </select>
            </div>
            <div class="flex items-end">
              <button
                @click="applyFilters"
                :disabled="loading"
                class="w-full py-2 bg-blue-600 hover:bg-blue-700 text-white font-extrabold text-xs rounded-xl shadow-md shadow-blue-600/20 transition cursor-pointer disabled:opacity-50"
              >
                {{ languageStore.t('apply_filters', 'Apply Filters') }}
              </button>
            </div>
          </div>
        </div>
      </div>

      <div
        class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-3xl p-1.5 shadow-xs"
      >
        <div class="grid grid-cols-3 gap-1">
          <button
            @click="activeTab = 'revenue'"
            :class="[
              'py-2.5 px-4 font-black text-xs rounded-2xl transition cursor-pointer flex items-center justify-center gap-2',
              activeTab === 'revenue'
                ? 'bg-blue-600 text-white shadow-md shadow-blue-600/20'
                : 'text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800',
            ]"
          >
            <TrendingUp class="w-4 h-4" />
            <span>{{ languageStore.t('revenue_report', 'Revenue Report') }}</span>
          </button>

          <button
            @click="activeTab = 'payment'"
            :class="[
              'py-2.5 px-4 font-black text-xs rounded-2xl transition cursor-pointer flex items-center justify-center gap-2',
              activeTab === 'payment'
                ? 'bg-blue-600 text-white shadow-md shadow-blue-600/20'
                : 'text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800',
            ]"
          >
            <CreditCard class="w-4 h-4" />
            <span>{{ languageStore.t('payment_report', 'Payment Report') }}</span>
          </button>

          <button
            @click="activeTab = 'refund'"
            :class="[
              'py-2.5 px-4 font-black text-xs rounded-2xl transition cursor-pointer flex items-center justify-center gap-2',
              activeTab === 'refund'
                ? 'bg-blue-600 text-white shadow-md shadow-blue-600/20'
                : 'text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800',
            ]"
          >
            <RefreshCw class="w-4 h-4" />
            <span>{{ languageStore.t('refund_report', 'Refund Report') }}</span>
          </button>
        </div>
      </div>

      <div
        v-if="loading && !revenueReport"
        class="bg-white dark:bg-slate-900 rounded-3xl border border-slate-200 dark:border-slate-800 p-16 text-center space-y-3"
      >
        <RefreshCw class="w-8 h-8 text-amber-500 animate-spin mx-auto" />
        <p class="text-xs font-bold text-slate-500 dark:text-slate-400">
          {{ languageStore.t('loading_reports', 'Loading financial reports...') }}
        </p>
      </div>

      <div v-else-if="activeTab === 'revenue' && revenueReport" class="space-y-6">
        <div class="flex justify-end gap-2">
          <button
            @click="printPage"
            class="px-3.5 py-2 rounded-xl bg-rose-600 hover:bg-rose-700 text-white text-xs font-extrabold shadow-xs transition flex items-center gap-1.5 cursor-pointer"
          >
            <Download class="w-3.5 h-3.5" />
            <span>{{ languageStore.t('export_pdf', 'Export PDF') }}</span>
          </button>
          <button
            @click="downloadExcel"
            class="px-3.5 py-2 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-extrabold shadow-xs transition flex items-center gap-1.5 cursor-pointer"
          >
            <FileSpreadsheet class="w-3.5 h-3.5" />
            <span>{{ languageStore.t('export_excel', 'Export Excel') }}</span>
          </button>
        </div>

        <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
          <div
            class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-3xl p-5 shadow-xs"
          >
            <div class="flex items-center justify-between mb-3">
              <div
                class="p-2.5 bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 border border-emerald-500/20 rounded-2xl"
              >
                <DollarSign class="w-5 h-5" />
              </div>
              <div
                class="flex items-center gap-0.5 text-emerald-600 dark:text-emerald-400 font-extrabold text-xs"
              >
                <ArrowUpRight class="w-3.5 h-3.5" />
                <span>+12.5%</span>
              </div>
            </div>
            <p
              class="text-[10px] font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider"
            >
              {{ languageStore.t('total_revenue', 'Total Revenue') }}
            </p>
            <p class="text-2xl font-black text-slate-900 dark:text-white mt-1">
              {{ formatCurrency(revenueReport.total_revenue) }}
            </p>
          </div>

          <div
            class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-3xl p-5 shadow-xs"
          >
            <div class="flex items-center justify-between mb-3">
              <div
                class="p-2.5 bg-blue-500/10 text-blue-600 dark:text-blue-400 border border-blue-500/20 rounded-2xl"
              >
                <BarChart3 class="w-5 h-5" />
              </div>
              <div
                class="flex items-center gap-0.5 text-blue-600 dark:text-blue-400 font-extrabold text-xs"
              >
                <ArrowUpRight class="w-3.5 h-3.5" />
                <span>+8.3%</span>
              </div>
            </div>
            <p
              class="text-[10px] font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider"
            >
              {{ languageStore.t('total_transactions', 'Total Transactions') }}
            </p>
            <p class="text-2xl font-black text-slate-900 dark:text-white mt-1">
              {{ revenueReport.total_transactions }}
            </p>
          </div>

          <div
            class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-3xl p-5 shadow-xs"
          >
            <div class="flex items-center justify-between mb-3">
              <div
                class="p-2.5 bg-purple-500/10 text-purple-600 dark:text-purple-400 border border-purple-500/20 rounded-2xl"
              >
                <TrendingUp class="w-5 h-5" />
              </div>
              <div
                class="flex items-center gap-0.5 text-purple-600 dark:text-purple-400 font-extrabold text-xs"
              >
                <ArrowUpRight class="w-3.5 h-3.5" />
                <span>+5.2%</span>
              </div>
            </div>
            <p
              class="text-[10px] font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider"
            >
              {{ languageStore.t('avg_transaction', 'Avg. Transaction') }}
            </p>
            <p class="text-2xl font-black text-slate-900 dark:text-white mt-1">
              {{ formatCurrency(revenueReport.average_transaction) }}
            </p>
          </div>

          <div
            class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-3xl p-5 shadow-xs"
          >
            <div class="flex items-center justify-between mb-3">
              <div
                class="p-2.5 bg-amber-500/10 text-amber-600 dark:text-amber-400 border border-amber-500/20 rounded-2xl"
              >
                <PieChart class="w-5 h-5" />
              </div>
            </div>
            <p
              class="text-[10px] font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider"
            >
              {{ languageStore.t('report_period', 'Report Period') }}
            </p>
            <p class="text-xs font-black text-slate-900 dark:text-white mt-1">
              {{ formatDate(dateFrom) }}
            </p>
            <p class="text-[10px] text-slate-500 dark:text-slate-400 font-semibold">
              {{ languageStore.t('to', 'to') }} {{ formatDate(dateTo) }}
            </p>
          </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
          <div
            class="bg-white dark:bg-slate-900 rounded-3xl border border-slate-200 dark:border-slate-800 p-5 shadow-xs space-y-4"
          >
            <h3
              class="text-sm font-extrabold text-slate-900 dark:text-white flex items-center gap-2"
            >
              <PieChart class="w-4 h-4 text-blue-500" />
              <span>{{ languageStore.t('revenue_by_category', 'Revenue by Category') }}</span>
            </h3>

            <div class="space-y-4">
              <div>
                <div class="flex justify-between items-center mb-1 text-xs">
                  <span class="font-bold text-slate-700 dark:text-slate-300">{{
                    languageStore.t('reservations', 'Reservations')
                  }}</span>
                  <div class="text-right">
                    <span class="font-black text-slate-900 dark:text-white mr-1.5">{{
                      formatCurrency(revenueReport.reservation_revenue)
                    }}</span>
                    <span class="text-[10px] text-slate-400 font-bold" v-if="revenueAnalytics"
                      >({{ revenueAnalytics.reservation_percentage }}%)</span
                    >
                  </div>
                </div>
                <div
                  class="w-full bg-slate-100 dark:bg-slate-800 rounded-full h-2 overflow-hidden p-0.5"
                >
                  <div
                    class="bg-emerald-500 h-full rounded-full transition-all duration-300"
                    :style="{
                      width: revenueAnalytics
                        ? `${revenueAnalytics.reservation_percentage}%`
                        : '0%',
                    }"
                  ></div>
                </div>
              </div>

              <div>
                <div class="flex justify-between items-center mb-1 text-xs">
                  <span class="font-bold text-slate-700 dark:text-slate-300">{{
                    languageStore.t('orders', 'Restaurant Orders')
                  }}</span>
                  <div class="text-right">
                    <span class="font-black text-slate-900 dark:text-white mr-1.5">{{
                      formatCurrency(revenueReport.order_revenue)
                    }}</span>
                    <span class="text-[10px] text-slate-400 font-bold" v-if="revenueAnalytics"
                      >({{ revenueAnalytics.order_percentage }}%)</span
                    >
                  </div>
                </div>
                <div
                  class="w-full bg-slate-100 dark:bg-slate-800 rounded-full h-2 overflow-hidden p-0.5"
                >
                  <div
                    class="bg-blue-500 h-full rounded-full transition-all duration-300"
                    :style="{
                      width: revenueAnalytics ? `${revenueAnalytics.order_percentage}%` : '0%',
                    }"
                  ></div>
                </div>
              </div>
            </div>
          </div>

          <div
            class="bg-white dark:bg-slate-900 rounded-3xl border border-slate-200 dark:border-slate-800 p-5 shadow-xs space-y-3"
          >
            <h3
              class="text-sm font-extrabold text-slate-900 dark:text-white flex items-center gap-2"
            >
              <CreditCard class="w-4 h-4 text-blue-500" />
              <span>{{ languageStore.t('revenue_by_method', 'Revenue by Payment Method') }}</span>
            </h3>

            <div class="space-y-2">
              <div
                v-for="(item, index) in revenueReport.revenue_by_method"
                :key="item.method"
                class="flex items-center justify-between p-3 bg-slate-50 dark:bg-slate-950 rounded-2xl border border-slate-200 dark:border-slate-800 text-xs"
              >
                <div class="flex items-center gap-2.5">
                  <div
                    class="w-8 h-8 rounded-xl bg-purple-500/10 text-purple-600 dark:text-purple-400 border border-purple-500/20 flex items-center justify-center font-bold"
                  >
                    <CreditCard class="w-4 h-4" />
                  </div>
                  <div>
                    <p class="font-extrabold text-slate-900 dark:text-white capitalize">
                      {{ item.method || 'Other' }}
                    </p>
                    <p class="text-[10px] text-slate-400 font-medium">
                      {{ languageStore.t('gateway', 'Gateway') }}
                    </p>
                  </div>
                </div>

                <div class="text-right">
                  <p class="font-black text-slate-900 dark:text-white">
                    {{ formatCurrency(item.total) }}
                  </p>
                  <p
                    class="text-[10px] text-slate-400 font-bold"
                    v-if="revenueReport.total_revenue"
                  >
                    {{ ((item.total / revenueReport.total_revenue) * 100).toFixed(1) }}%
                  </p>
                </div>
              </div>
            </div>
          </div>
        </div>

        <div
          v-if="revenueReport.daily_breakdown && revenueReport.daily_breakdown.length > 0"
          class="bg-white dark:bg-slate-900 rounded-3xl border border-slate-200 dark:border-slate-800 overflow-hidden shadow-xs"
        >
          <div
            class="p-4 border-b border-slate-200 dark:border-slate-800 flex items-center justify-between bg-slate-50/50 dark:bg-slate-950/50"
          >
            <h3
              class="text-sm font-extrabold text-slate-900 dark:text-white flex items-center gap-2"
            >
              <Calendar class="w-4 h-4 text-blue-500" />
              <span>{{
                languageStore.t('daily_revenue_breakdown', 'Daily Revenue Breakdown')
              }}</span>
            </h3>
            <span
              class="px-3 py-1 bg-slate-200 dark:bg-slate-800 text-slate-700 dark:text-slate-300 text-xs font-black rounded-full border border-slate-300/60 dark:border-slate-700"
            >
              {{ revenueReport.daily_breakdown.length }} {{ languageStore.t('days', 'Days') }}
            </span>
          </div>

          <div class="overflow-x-auto w-full">
            <table class="w-full text-left border-collapse">
              <thead>
                <tr
                  class="bg-slate-50 dark:bg-slate-950/80 border-b border-slate-200 dark:border-slate-800 text-[10px] font-black uppercase text-slate-500 dark:text-slate-400 tracking-wider"
                >
                  <th class="px-3.5 py-3 whitespace-nowrap">
                    {{ languageStore.t('date', 'Date') }}
                  </th>
                  <th class="px-3.5 py-3 text-right whitespace-nowrap">
                    {{ languageStore.t('revenue', 'Revenue') }}
                  </th>
                  <th class="px-3.5 py-3 text-right whitespace-nowrap">
                    {{ languageStore.t('transactions', 'Transactions') }}
                  </th>
                  <th class="px-3.5 py-3 text-right whitespace-nowrap pr-6">
                    {{ languageStore.t('avg_value', 'Avg. Value') }}
                  </th>
                </tr>
              </thead>
              <tbody class="divide-y divide-slate-100 dark:divide-slate-800/60 text-xs">
                <tr
                  v-for="day in paginatedDailyBreakdown"
                  :key="day.date"
                  class="hover:bg-slate-50/70 dark:hover:bg-slate-800/40 transition"
                >
                  <td
                    class="px-3.5 py-3 whitespace-nowrap font-extrabold text-slate-900 dark:text-white"
                  >
                    {{ formatDate(day.date) }}
                  </td>
                  <td
                    class="px-3.5 py-3 text-right whitespace-nowrap font-black text-emerald-600 dark:text-emerald-400"
                  >
                    {{ formatCurrency(day.revenue) }}
                  </td>
                  <td
                    class="px-3.5 py-3 text-right whitespace-nowrap font-bold text-slate-700 dark:text-slate-300"
                  >
                    {{ day.transactions }}
                  </td>
                  <td
                    class="px-3.5 py-3 text-right whitespace-nowrap font-bold text-slate-600 dark:text-slate-400 pr-6"
                  >
                    {{ formatCurrency(day.transactions ? day.revenue / day.transactions : 0) }}
                  </td>
                </tr>
              </tbody>
            </table>
          </div>

          <div
            v-if="revenueReport.daily_breakdown.length > 0"
            class="flex flex-col sm:flex-row items-center justify-between gap-4 border-t border-slate-200 dark:border-slate-800 bg-slate-50/80 dark:bg-slate-950 p-4 text-xs font-sans"
          >
            <div class="flex flex-wrap items-center gap-4 text-slate-600 dark:text-slate-400">
              <div class="flex items-center gap-2">
                <span class="font-bold text-slate-700 dark:text-slate-300">{{
                  languageStore.t('items_per_page', 'Items per page:')
                }}</span>
                <select
                  :value="perPage"
                  @change="changePerPage"
                  class="px-2.5 py-1.5 rounded-xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 text-slate-900 dark:text-white font-black focus:outline-none focus:border-amber-500 cursor-pointer shadow-2xs"
                >
                  <option :value="5">5</option>
                  <option :value="10">10</option>
                  <option :value="20">20</option>
                  <option :value="50">50</option>
                </select>
              </div>

              <div class="text-xs font-medium">
                {{ languageStore.t('showing', 'Showing') }}
                <span class="font-extrabold text-slate-900 dark:text-white">{{ showingFrom }}</span>
                {{ languageStore.t('to', 'to') }}
                <span class="font-extrabold text-slate-900 dark:text-white">{{ showingTo }}</span>
                {{ languageStore.t('of', 'of') }}
                <span class="font-extrabold text-slate-900 dark:text-white">{{
                  revenueReport.daily_breakdown.length
                }}</span>
                {{ languageStore.t('entries', 'entries') }}
              </div>
            </div>

            <div class="flex items-center gap-1.5">
              <button
                @click="previousPage"
                :disabled="currentPage <= 1"
                class="p-2 rounded-xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 text-slate-700 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800 disabled:opacity-40 disabled:cursor-not-allowed transition cursor-pointer flex items-center gap-1 font-bold"
                :title="languageStore.t('previous_page', 'Previous Page')"
              >
                <ChevronLeft class="w-4 h-4" />
                <span class="hidden sm:inline">{{ languageStore.t('prev', 'Prev') }}</span>
              </button>

              <div class="flex items-center gap-1">
                <button
                  v-for="p in paginationPages"
                  :key="p"
                  @click="goToPage(p)"
                  :class="[
                    'w-8 h-8 rounded-xl font-black text-xs transition cursor-pointer flex items-center justify-center border',
                    currentPage === p
                      ? 'bg-amber-500 border-amber-500 text-slate-950 font-black shadow-md shadow-amber-500/20'
                      : 'bg-white dark:bg-slate-900 border-slate-200 dark:border-slate-800 text-slate-700 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800',
                  ]"
                >
                  {{ p }}
                </button>
              </div>

              <button
                @click="nextPage"
                :disabled="currentPage >= totalPages"
                class="p-2 rounded-xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 text-slate-700 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800 disabled:opacity-40 disabled:cursor-not-allowed transition cursor-pointer flex items-center gap-1 font-bold"
                :title="languageStore.t('next_page', 'Next Page')"
              >
                <span class="hidden sm:inline">{{ languageStore.t('next', 'Next') }}</span>
                <ChevronRight class="w-4 h-4" />
              </button>
            </div>
          </div>
        </div>
      </div>

      <div v-else-if="activeTab === 'payment' && paymentReport" class="space-y-6">
        <div
          class="bg-white dark:bg-slate-900 rounded-3xl border border-slate-200 dark:border-slate-800 p-5 shadow-xs space-y-4"
        >
          <h3 class="text-sm font-extrabold text-slate-900 dark:text-white flex items-center gap-2">
            <BarChart3 class="w-4 h-4 text-blue-500" />
            <span>{{
              languageStore.t('payment_status_breakdown', 'Payment Status Breakdown')
            }}</span>
          </h3>

          <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
            <div
              v-for="status in paymentReport.status_breakdown"
              :key="status.status"
              class="p-4 rounded-2xl bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 space-y-2"
            >
              <span
                :class="[
                  'px-2.5 py-0.5 rounded-full text-[10px] font-black uppercase tracking-wider border',
                  (status.status === 'paid' || status.status === 'verified') &&
                    'bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 border-emerald-500/20',
                  (status.status === 'pending' || status.status === 'initialized') &&
                    'bg-amber-500/10 text-amber-600 dark:text-amber-400 border-amber-500/20',
                  status.status === 'failed' &&
                    'bg-rose-500/10 text-rose-600 dark:text-rose-400 border-rose-500/20',
                  status.status === 'refunded' &&
                    'bg-purple-500/10 text-purple-600 dark:text-purple-400 border-purple-500/20',
                ]"
              >
                {{ languageStore.t(status.status, status.status) }}
              </span>
              <p class="text-2xl font-black text-slate-900 dark:text-white">{{ status.count }}</p>
              <p class="text-xs font-bold text-slate-600 dark:text-slate-400">
                {{ formatCurrency(status.total) }}
              </p>
            </div>
          </div>
        </div>
      </div>

      <div v-else-if="activeTab === 'refund' && refundReport" class="space-y-6">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
          <div
            class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-3xl p-5 shadow-xs"
          >
            <p
              class="text-[10px] font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider"
            >
              {{ languageStore.t('total_refunded', 'Total Refunded') }}
            </p>
            <p class="text-2xl font-black text-rose-600 dark:text-rose-400 mt-1">
              {{ formatCurrency(refundReport.total_refunded) }}
            </p>
          </div>

          <div
            class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-3xl p-5 shadow-xs"
          >
            <p
              class="text-[10px] font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider"
            >
              {{ languageStore.t('total_refunds_count', 'Total Refunds Count') }}
            </p>
            <p class="text-2xl font-black text-slate-900 dark:text-white mt-1">
              {{ refundReport.total_count }}
            </p>
          </div>

          <div
            class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-3xl p-5 shadow-xs"
          >
            <p
              class="text-[10px] font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider"
            >
              {{ languageStore.t('average_refund', 'Average Refund') }}
            </p>
            <p class="text-2xl font-black text-slate-900 dark:text-white mt-1">
              {{
                formatCurrency(
                  refundReport.total_count
                    ? refundReport.total_refunded / refundReport.total_count
                    : 0,
                )
              }}
            </p>
          </div>
        </div>

        <div
          v-if="refundReport.refunds_list && refundReport.refunds_list.length > 0"
          class="bg-white dark:bg-slate-900 rounded-3xl border border-slate-200 dark:border-slate-800 overflow-hidden shadow-xs"
        >
          <div
            class="p-4 border-b border-slate-200 dark:border-slate-800 flex items-center justify-between bg-slate-50/50 dark:bg-slate-950/50"
          >
            <h3 class="text-sm font-extrabold text-slate-900 dark:text-white">
              {{ languageStore.t('recent_refunds_log', 'Recent Refunds Log') }}
            </h3>
            <span
              class="px-3 py-1 bg-slate-200 dark:bg-slate-800 text-slate-700 dark:text-slate-300 text-xs font-black rounded-full border border-slate-300/60 dark:border-slate-700"
            >
              {{ refundReport.refunds_list.length }} {{ languageStore.t('records', 'Records') }}
            </span>
          </div>

          <div class="overflow-x-auto w-full">
            <table class="w-full text-left border-collapse">
              <thead>
                <tr
                  class="bg-slate-50 dark:bg-slate-950/80 border-b border-slate-200 dark:border-slate-800 text-[10px] font-black uppercase text-slate-500 dark:text-slate-400 tracking-wider"
                >
                  <th class="px-3.5 py-3 whitespace-nowrap">
                    {{ languageStore.t('transaction_ref', 'Transaction Ref') }}
                  </th>
                  <th class="px-3.5 py-3 whitespace-nowrap">
                    {{ languageStore.t('customer', 'Customer') }}
                  </th>
                  <th class="px-3.5 py-3 whitespace-nowrap">
                    {{ languageStore.t('type', 'Type') }}
                  </th>
                  <th class="px-3.5 py-3 text-right whitespace-nowrap">
                    {{ languageStore.t('amount', 'Amount') }}
                  </th>
                  <th class="px-3.5 py-3 text-right whitespace-nowrap pr-6">
                    {{ languageStore.t('refunded_at', 'Refunded At') }}
                  </th>
                </tr>
              </thead>
              <tbody class="divide-y divide-slate-100 dark:divide-slate-800/60 text-xs">
                <tr
                  v-for="refund in paginatedRefundsList"
                  :key="refund.id"
                  class="hover:bg-slate-50/70 dark:hover:bg-slate-800/40 transition"
                >
                  <td
                    class="px-3.5 py-3 whitespace-nowrap font-mono text-[11px] font-bold text-slate-900 dark:text-white"
                  >
                    {{ refund.tx_ref }}
                  </td>
                  <td
                    class="px-3.5 py-3 whitespace-nowrap font-bold text-slate-900 dark:text-white"
                  >
                    {{ refund.customer_name }}
                  </td>
                  <td class="px-3.5 py-3 whitespace-nowrap">
                    <span
                      class="px-2.5 py-0.5 bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 rounded-full text-[10px] font-black uppercase border border-slate-200 dark:border-slate-700"
                    >
                      {{ refund.type }}
                    </span>
                  </td>
                  <td
                    class="px-3.5 py-3 text-right whitespace-nowrap font-black text-rose-600 dark:text-rose-400"
                  >
                    {{ formatCurrency(refund.amount) }}
                  </td>
                  <td
                    class="px-3.5 py-3 text-right whitespace-nowrap text-slate-500 dark:text-slate-400 text-[11px] pr-6"
                  >
                    {{ formatDate(refund.refunded_at) }}
                  </td>
                </tr>
              </tbody>
            </table>
          </div>

          <div
            v-if="refundReport.refunds_list.length > 0"
            class="flex flex-col sm:flex-row items-center justify-between gap-4 border-t border-slate-200 dark:border-slate-800 bg-slate-50/80 dark:bg-slate-950 p-4 text-xs font-sans"
          >
            <div class="flex items-center gap-2 text-slate-600 dark:text-slate-400">
              <span class="font-bold text-slate-700 dark:text-slate-300">{{
                languageStore.t('items_per_page', 'Items per page:')
              }}</span>
              <select
                :value="refundPerPage"
                @change="changeRefundPerPage"
                class="px-2.5 py-1.5 rounded-xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 text-slate-900 dark:text-white font-black cursor-pointer shadow-2xs"
              >
                <option :value="5">5</option>
                <option :value="10">10</option>
                <option :value="20">20</option>
                <option :value="50">50</option>
              </select>
            </div>

            <div class="flex items-center gap-1.5">
              <button
                @click="refundCurrentPage > 1 && refundCurrentPage--"
                :disabled="refundCurrentPage <= 1"
                class="p-2 rounded-xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 text-slate-700 dark:text-slate-300 disabled:opacity-40 font-bold"
              >
                <ChevronLeft class="w-4 h-4" />
              </button>
              <span class="font-black text-slate-900 dark:text-white px-2">
                {{ languageStore.t('page', 'Page') }} {{ refundCurrentPage }}
                {{ languageStore.t('of', 'of') }} {{ totalRefundPages }}
              </span>
              <button
                @click="refundCurrentPage < totalRefundPages && refundCurrentPage++"
                :disabled="refundCurrentPage >= totalRefundPages"
                class="p-2 rounded-xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 text-slate-700 dark:text-slate-300 disabled:opacity-40 font-bold"
              >
                <ChevronRight class="w-4 h-4" />
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>
  </DashboardLayout>
</template>

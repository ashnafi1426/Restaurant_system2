<script setup lang="ts">
import { ref, onMounted, computed } from 'vue'
import DashboardLayout from '../../layouts/DashboardLayout.vue'
import { useCashierStore } from '@/stores/cashierStore'
import {
  TrendingUp, DollarSign, Download, Calendar, CreditCard, RefreshCw,
  BarChart3, PieChart, ArrowUpRight, Filter, FileSpreadsheet, Printer,
  Building, CheckCircle, Clock, XCircle
} from 'lucide-vue-next'

const cashierStore = useCashierStore()
const activeTab = ref<'revenue' | 'payment' | 'refund'>('revenue')
const dateFrom = ref(new Date(new Date().setDate(1)).toISOString().split('T')[0])
const dateTo = ref(new Date().toISOString().split('T')[0])
const period = ref<'daily' | 'weekly' | 'monthly' | 'yearly'>('daily')
const revenueReport = ref<any>(null)
const paymentReport = ref<any>(null)
const refundReport = ref<any>(null)
const loading = ref(false)
const showFilters = ref(false)

const quickDateFilters = [
  { label: 'Today', value: 'today' }, { label: 'This Week', value: 'week' },
  { label: 'This Month', value: 'month' }, { label: 'This Year', value: 'year' }
]

onMounted(() => { loadReports() })

const loadReports = async () => {
  loading.value = true
  try {
    await Promise.all([
      loadRevenueReport(), loadPaymentReport(), loadRefundReport()
    ])
  } finally { loading.value = false }
}

const loadRevenueReport = async () => {
  revenueReport.value = await cashierStore.fetchRevenueReport({
    period: period.value, date_from: dateFrom.value, date_to: dateTo.value
  })
}

const loadPaymentReport = async () => {
  paymentReport.value = await cashierStore.fetchPaymentReport({
    date_from: dateFrom.value, date_to: dateTo.value
  })
}

const loadRefundReport = async () => {
  refundReport.value = await cashierStore.fetchRefundReport({
    date_from: dateFrom.value, date_to: dateTo.value
  })
}

const applyFilters = () => { loadReports() }

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
      dateFrom.value = new Date(today.getFullYear(), today.getMonth(), 1).toISOString().split('T')[0]
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
  const total = revenueReport.value.total_revenue
  return {
    reservation_percentage: revenueReport.value.reservation_revenue 
      ? ((revenueReport.value.reservation_revenue / total) * 100).toFixed(1) : 0,
    order_percentage: revenueReport.value.order_revenue
      ? ((revenueReport.value.order_revenue / total) * 100).toFixed(1) : 0,
  }
})

const formatCurrency = (amount: number | string) => {
  const numAmount = typeof amount === 'string' ? parseFloat(amount) : amount
  return `${numAmount.toFixed(2)} ETB`
}

const formatDate = (date: string) => {
  return new Date(date).toLocaleDateString('en-US', {
    month: 'short', day: 'numeric', year: 'numeric'
  })
}

const downloadPDF = () => {
  window.print()
}

const downloadExcel = () => {
  let csvContent = ''
  let filename = ''
  
  if (activeTab.value === 'revenue' && revenueReport.value) {
    filename = `revenue_report_${dateFrom.value}_to_${dateTo.value}.csv`
    csvContent = 'Revenue Report\n\n'
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
    <div class="space-y-6">
      <!-- Header -->
      <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
        <div>
          <h1 class="text-3xl font-bold text-slate-800 dark:text-white">Financial Reports</h1>
          <p class="text-slate-500 dark:text-slate-400 mt-1">Comprehensive financial analytics and insights</p>
        </div>
        <div class="flex gap-2">
          <button @click="window.print()" class="flex items-center gap-2 px-4 py-2 bg-slate-600 hover:bg-slate-700 text-white rounded-lg transition-colors">
            <Printer :size="18" />
            Print
          </button>
          <button @click="loadReports" :disabled="loading" class="flex items-center gap-2 px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg transition-colors disabled:opacity-50">
            <RefreshCw :size="18" :class="{ 'animate-spin': loading }" />
            Refresh
          </button>
        </div>
      </div>

      <!-- Quick Filters -->
      <div class="bg-white dark:bg-slate-800 rounded-xl border dark:border-slate-700 shadow-sm p-4">
        <div class="flex items-center gap-2 mb-3">
          <Calendar :size="18" class="text-blue-600" />
          <h3 class="font-semibold text-slate-800 dark:text-white">Quick Date Filters</h3>
        </div>
        <div class="flex flex-wrap gap-2">
          <button v-for="filter in quickDateFilters" :key="filter.value" @click="setQuickDateFilter(filter.value)" 
            class="px-4 py-2 text-sm bg-slate-100 dark:bg-slate-700 hover:bg-slate-200 dark:hover:bg-slate-600 text-slate-700 dark:text-slate-300 rounded-lg transition-colors">
            {{ filter.label }}
          </button>
        </div>
      </div>

      <!-- Advanced Filters -->
      <div class="bg-white dark:bg-slate-800 rounded-xl border dark:border-slate-700 shadow-sm">
        <div class="px-6 py-4 border-b dark:border-slate-700 flex justify-between items-center">
          <h3 class="text-lg font-semibold text-slate-800 dark:text-white flex items-center gap-2">
            <Filter :size="18" class="text-blue-600" />
            Advanced Filters
          </h3>
          <button @click="showFilters = !showFilters" class="text-sm text-blue-600 hover:text-blue-700">
            {{ showFilters ? 'Hide' : 'Show' }} Filters
          </button>
        </div>
        
        <div v-if="showFilters" class="p-6">
          <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <div>
              <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">Date From</label>
              <input v-model="dateFrom" type="date" class="w-full px-4 py-2 border dark:border-slate-600 rounded-lg focus:ring-2 focus:ring-blue-500 dark:bg-slate-700 dark:text-white" />
            </div>
            <div>
              <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">Date To</label>
              <input v-model="dateTo" type="date" class="w-full px-4 py-2 border dark:border-slate-600 rounded-lg focus:ring-2 focus:ring-blue-500 dark:bg-slate-700 dark:text-white" />
            </div>
            <div>
              <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">Period</label>
              <select v-model="period" class="w-full px-4 py-2 border dark:border-slate-600 rounded-lg focus:ring-2 focus:ring-blue-500 dark:bg-slate-700 dark:text-white">
                <option value="daily">Daily</option>
                <option value="weekly">Weekly</option>
                <option value="monthly">Monthly</option>
                <option value="yearly">Yearly</option>
              </select>
            </div>
            <div class="flex items-end">
              <button @click="applyFilters" :disabled="loading" class="w-full px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg transition-colors disabled:opacity-50">
                Apply Filters
              </button>
            </div>
          </div>
        </div>
      </div>

      <!-- Tabs -->
      <div class="bg-white dark:bg-slate-800 rounded-xl border dark:border-slate-700 shadow-sm">
        <div class="flex gap-1 p-2">
          <button @click="activeTab = 'revenue'" :class="['flex-1 px-6 py-3 font-medium rounded-lg transition-all', activeTab === 'revenue' ? 'bg-blue-600 text-white shadow-lg' : 'text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-700']">
            <div class="flex items-center justify-center gap-2">
              <TrendingUp :size="18" />
              Revenue Report
            </div>
          </button>
          <button @click="activeTab = 'payment'" :class="['flex-1 px-6 py-3 font-medium rounded-lg transition-all', activeTab === 'payment' ? 'bg-blue-600 text-white shadow-lg' : 'text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-700']">
            <div class="flex items-center justify-center gap-2">
              <CreditCard :size="18" />
              Payment Report
            </div>
          </button>
          <button @click="activeTab = 'refund'" :class="['flex-1 px-6 py-3 font-medium rounded-lg transition-all', activeTab === 'refund' ? 'bg-blue-600 text-white shadow-lg' : 'text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-700']">
            <div class="flex items-center justify-center gap-2">
              <RefreshCw :size="18" />
              Refund Report
            </div>
          </button>
        </div>
      </div>

      <!-- Loading State -->
      <div v-if="loading && !revenueReport" class="bg-white dark:bg-slate-800 rounded-xl border dark:border-slate-700 shadow-sm p-12 text-center">
        <RefreshCw :size="48" class="mx-auto text-blue-600 animate-spin mb-4" />
        <p class="text-slate-600 dark:text-slate-400">Loading reports...</p>
      </div>

      <!-- Revenue Report -->
      <div v-else-if="activeTab === 'revenue' && revenueReport" class="space-y-6">
        <!-- Export Actions -->
        <div class="flex justify-end gap-2">
          <button @click="downloadPDF" class="flex items-center gap-2 px-4 py-2 bg-red-600 hover:bg-red-700 text-white rounded-lg transition-colors">
            <Download :size="18" />
            Export PDF
          </button>
          <button @click="downloadExcel" class="flex items-center gap-2 px-4 py-2 bg-green-600 hover:bg-green-700 text-white rounded-lg transition-colors">
            <FileSpreadsheet :size="18" />
            Export Excel
          </button>
        </div>

        <!-- Key Metrics -->
        <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-6">
          <div class="bg-gradient-to-br from-green-500 to-green-600 rounded-xl shadow-lg p-6 text-white">
            <div class="flex items-center justify-between mb-4">
              <div class="p-3 bg-white/20 rounded-lg">
                <DollarSign :size="24" />
              </div>
              <div class="flex items-center gap-1 text-green-100">
                <ArrowUpRight :size="16" />
                <span class="text-sm font-medium">+12.5%</span>
              </div>
            </div>
            <p class="text-green-100 text-sm mb-1">Total Revenue</p>
            <p class="text-3xl font-bold">{{ formatCurrency(revenueReport.total_revenue) }}</p>
          </div>

          <div class="bg-gradient-to-br from-blue-500 to-blue-600 rounded-xl shadow-lg p-6 text-white">
            <div class="flex items-center justify-between mb-4">
              <div class="p-3 bg-white/20 rounded-lg">
                <BarChart3 :size="24" />
              </div>
              <div class="flex items-center gap-1 text-blue-100">
                <ArrowUpRight :size="16" />
                <span class="text-sm font-medium">+8.3%</span>
              </div>
            </div>
            <p class="text-blue-100 text-sm mb-1">Total Transactions</p>
            <p class="text-3xl font-bold">{{ revenueReport.total_transactions }}</p>
          </div>

          <div class="bg-gradient-to-br from-purple-500 to-purple-600 rounded-xl shadow-lg p-6 text-white">
            <div class="flex items-center justify-between mb-4">
              <div class="p-3 bg-white/20 rounded-lg">
                <TrendingUp :size="24" />
              </div>
              <div class="flex items-center gap-1 text-purple-100">
                <ArrowUpRight :size="16" />
                <span class="text-sm font-medium">+5.2%</span>
              </div>
            </div>
            <p class="text-purple-100 text-sm mb-1">Average Transaction</p>
            <p class="text-3xl font-bold">{{ formatCurrency(revenueReport.average_transaction) }}</p>
          </div>

          <div class="bg-gradient-to-br from-orange-500 to-orange-600 rounded-xl shadow-lg p-6 text-white">
            <div class="flex items-center justify-between mb-4">
              <div class="p-3 bg-white/20 rounded-lg">
                <PieChart :size="24" />
              </div>
            </div>
            <p class="text-orange-100 text-sm mb-1">Report Period</p>
            <p class="text-xl font-bold">{{ formatDate(dateFrom) }}</p>
            <p class="text-sm text-orange-100">to {{ formatDate(dateTo) }}</p>
          </div>
        </div>

        <!-- Revenue Distribution -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
          <!-- Revenue by Type -->
          <div class="bg-white dark:bg-slate-800 rounded-xl border dark:border-slate-700 shadow-sm p-6">
            <h3 class="text-lg font-semibold text-slate-800 dark:text-white mb-6 flex items-center gap-2">
              <PieChart :size="20" class="text-blue-600" />
              Revenue by Type
            </h3>
            <div class="space-y-4">
              <div class="relative">
                <div class="flex justify-between items-center mb-2">
                  <div class="flex items-center gap-2">
                    <div class="w-3 h-3 bg-green-500 rounded-full"></div>
                    <span class="text-sm text-slate-600 dark:text-slate-400">Reservations</span>
                  </div>
                  <div class="text-right">
                    <p class="font-semibold text-slate-800 dark:text-white">
                      {{ formatCurrency(revenueReport.reservation_revenue) }}
                    </p>
                    <p class="text-xs text-slate-500" v-if="revenueAnalytics">
                      {{ revenueAnalytics.reservation_percentage }}%
                    </p>
                  </div>
                </div>
                <div class="w-full bg-slate-200 dark:bg-slate-700 rounded-full h-3 overflow-hidden">
                  <div class="bg-gradient-to-r from-green-500 to-green-600 h-full rounded-full transition-all duration-500"
                    :style="{ width: revenueAnalytics ? `${revenueAnalytics.reservation_percentage}%` : '0%' }">
                  </div>
                </div>
              </div>

              <div class="relative">
                <div class="flex justify-between items-center mb-2">
                  <div class="flex items-center gap-2">
                    <div class="w-3 h-3 bg-blue-500 rounded-full"></div>
                    <span class="text-sm text-slate-600 dark:text-slate-400">Restaurant Orders</span>
                  </div>
                  <div class="text-right">
                    <p class="font-semibold text-slate-800 dark:text-white">
                      {{ formatCurrency(revenueReport.order_revenue) }}
                    </p>
                    <p class="text-xs text-slate-500" v-if="revenueAnalytics">
                      {{ revenueAnalytics.order_percentage }}%
                    </p>
                  </div>
                </div>
                <div class="w-full bg-slate-200 dark:bg-slate-700 rounded-full h-3 overflow-hidden">
                  <div class="bg-gradient-to-r from-blue-500 to-blue-600 h-full rounded-full transition-all duration-500"
                    :style="{ width: revenueAnalytics ? `${revenueAnalytics.order_percentage}%` : '0%' }">
                  </div>
                </div>
              </div>
            </div>

            <div class="mt-6 pt-6 border-t dark:border-slate-700 grid grid-cols-2 gap-4">
              <div class="text-center p-4 bg-green-50 dark:bg-green-900/20 rounded-lg">
                <p class="text-sm text-slate-600 dark:text-slate-400 mb-1">Reservation Rev.</p>
                <p class="text-xl font-bold text-green-600">
                  {{ formatCurrency(revenueReport.reservation_revenue) }}
                </p>
              </div>
              <div class="text-center p-4 bg-blue-50 dark:bg-blue-900/20 rounded-lg">
                <p class="text-sm text-slate-600 dark:text-slate-400 mb-1">Restaurant Rev.</p>
                <p class="text-xl font-bold text-blue-600">
                  {{ formatCurrency(revenueReport.order_revenue) }}
                </p>
              </div>
            </div>
          </div>

          <!-- Revenue by Payment Method -->
          <div class="bg-white dark:bg-slate-800 rounded-xl border dark:border-slate-700 shadow-sm p-6">
            <h3 class="text-lg font-semibold text-slate-800 dark:text-white mb-6 flex items-center gap-2">
              <CreditCard :size="20" class="text-blue-600" />
              Revenue by Payment Method
            </h3>
            <div class="space-y-3">
              <div v-for="(item, index) in revenueReport.revenue_by_method" :key="item.method"
                class="flex items-center justify-between p-4 bg-slate-50 dark:bg-slate-700/50 rounded-lg hover:shadow-md transition-shadow">
                <div class="flex items-center gap-3">
                  <div :class="['w-10 h-10 rounded-lg flex items-center justify-center',
                    index === 0 ? 'bg-purple-100 dark:bg-purple-900/30' :
                    index === 1 ? 'bg-blue-100 dark:bg-blue-900/30' : 'bg-slate-100 dark:bg-slate-800']">
                    <CreditCard :size="20" :class="[
                      index === 0 ? 'text-purple-600' :
                      index === 1 ? 'text-blue-600' : 'text-slate-600']" />
                  </div>
                  <div>
                    <p class="font-medium text-slate-800 dark:text-white capitalize">
                      {{ item.method || 'Other' }}
                    </p>
                    <p class="text-xs text-slate-500 dark:text-slate-400">Payment Gateway</p>
                  </div>
                </div>
                <div class="text-right">
                  <p class="text-lg font-bold text-slate-800 dark:text-white">
                    {{ formatCurrency(item.total) }}
                  </p>
                  <p class="text-xs text-slate-500">
                    {{ ((item.total / revenueReport.total_revenue) * 100).toFixed(1) }}%
                  </p>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Daily Breakdown -->
        <div v-if="revenueReport.daily_breakdown && revenueReport.daily_breakdown.length > 0"
          class="bg-white dark:bg-slate-800 rounded-xl border dark:border-slate-700 shadow-sm">
          <div class="px-6 py-4 border-b dark:border-slate-700">
            <h3 class="text-lg font-semibold text-slate-800 dark:text-white flex items-center gap-2">
              <Calendar :size="20" class="text-blue-600" />
              Daily Revenue Breakdown
            </h3>
          </div>
          <div class="overflow-x-auto">
            <table class="w-full">
              <thead class="bg-slate-50 dark:bg-slate-900">
                <tr>
                  <th class="text-left p-4 text-sm font-semibold text-slate-700 dark:text-slate-300">Date</th>
                  <th class="text-right p-4 text-sm font-semibold text-slate-700 dark:text-slate-300">Revenue</th>
                  <th class="text-right p-4 text-sm font-semibold text-slate-700 dark:text-slate-300">Transactions</th>
                  <th class="text-right p-4 text-sm font-semibold text-slate-700 dark:text-slate-300">Avg. Value</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="day in revenueReport.daily_breakdown" :key="day.date"
                  class="border-t dark:border-slate-700 hover:bg-slate-50 dark:hover:bg-slate-900 transition-colors">
                  <td class="p-4 text-slate-800 dark:text-white font-medium">{{ formatDate(day.date) }}</td>
                  <td class="p-4 text-right font-semibold text-slate-800 dark:text-white">
                    {{ formatCurrency(day.revenue) }}
                  </td>
                  <td class="p-4 text-right text-slate-600 dark:text-slate-400">{{ day.transactions }}</td>
                  <td class="p-4 text-right text-slate-600 dark:text-slate-400">
                    {{ formatCurrency(day.revenue / day.transactions) }}
                  </td>
                </tr>
              </tbody>
              <tfoot class="bg-slate-50 dark:bg-slate-900 font-semibold">
                <tr>
                  <td class="p-4 text-slate-800 dark:text-white">Total</td>
                  <td class="p-4 text-right text-slate-800 dark:text-white">
                    {{ formatCurrency(revenueReport.total_revenue) }}
                  </td>
                  <td class="p-4 text-right text-slate-800 dark:text-white">
                    {{ revenueReport.total_transactions }}
                  </td>
                  <td class="p-4 text-right text-slate-800 dark:text-white">
                    {{ formatCurrency(revenueReport.average_transaction) }}
                  </td>
                </tr>
              </tfoot>
            </table>
          </div>
        </div>
      </div>

      <!-- Payment Report -->
      <div v-else-if="activeTab === 'payment' && paymentReport" class="space-y-6">
        <!-- Export Actions -->
        <div class="flex justify-end gap-2">
          <button @click="downloadPDF" class="flex items-center gap-2 px-4 py-2 bg-red-600 hover:bg-red-700 text-white rounded-lg transition-colors">
            <Download :size="18" />
            Export PDF
          </button>
          <button @click="downloadExcel" class="flex items-center gap-2 px-4 py-2 bg-green-600 hover:bg-green-700 text-white rounded-lg transition-colors">
            <FileSpreadsheet :size="18" />
            Export Excel
          </button>
        </div>

        <!-- Status Breakdown -->
        <div class="bg-white dark:bg-slate-800 rounded-xl border dark:border-slate-700 shadow-sm p-6">
          <h3 class="text-lg font-semibold text-slate-800 dark:text-white mb-6 flex items-center gap-2">
            <BarChart3 :size="20" class="text-blue-600" />
            Payment Status Breakdown
          </h3>
          <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
            <div v-for="status in paymentReport.status_breakdown" :key="status.status"
              class="relative overflow-hidden bg-gradient-to-br from-slate-50 to-slate-100 dark:from-slate-700 dark:to-slate-800 rounded-xl p-6 border-l-4"
              :class="{
                'border-green-500': status.status === 'paid' || status.status === 'verified',
                'border-yellow-500': status.status === 'pending' || status.status === 'initialized',
                'border-red-500': status.status === 'failed',
                'border-purple-500': status.status === 'refunded'
              }">
              <div class="flex items-start justify-between mb-3">
                <component :is="status.status === 'paid' || status.status === 'verified' ? CheckCircle :
                            status.status === 'pending' || status.status === 'initialized' ? Clock :
                            status.status === 'failed' ? XCircle : RefreshCw"
                  :size="24" :class="{
                    'text-green-600': status.status === 'paid' || status.status === 'verified',
                    'text-yellow-600': status.status === 'pending' || status.status === 'initialized',
                    'text-red-600': status.status === 'failed',
                    'text-purple-600': status.status === 'refunded'
                  }" />
              </div>
              <p class="text-sm text-slate-600 dark:text-slate-400 mb-1 capitalize">{{ status.status }}</p>
              <p class="text-3xl font-bold text-slate-800 dark:text-white mb-2">{{ status.count }}</p>
              <p class="text-sm font-semibold text-slate-700 dark:text-slate-300">
                {{ formatCurrency(status.total) }}
              </p>
            </div>
          </div>
        </div>

        <!-- Provider and Method Breakdown -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
          <!-- Provider Breakdown -->
          <div class="bg-white dark:bg-slate-800 rounded-xl border dark:border-slate-700 shadow-sm p-6">
            <h3 class="text-lg font-semibold text-slate-800 dark:text-white mb-6 flex items-center gap-2">
              <Building :size="20" class="text-blue-600" />
              By Payment Provider
            </h3>
            <div class="space-y-3">
              <div v-for="provider in paymentReport.provider_breakdown" :key="provider.provider"
                class="flex items-center justify-between p-4 bg-slate-50 dark:bg-slate-700/50 rounded-lg">
                <div>
                  <p class="font-medium text-slate-800 dark:text-white capitalize">{{ provider.provider }}</p>
                  <p class="text-sm text-slate-500 dark:text-slate-400">{{ provider.count }} transactions</p>
                </div>
                <p class="text-lg font-bold text-slate-800 dark:text-white">
                  {{ formatCurrency(provider.total) }}
                </p>
              </div>
            </div>
          </div>

          <!-- Method Breakdown -->
          <div class="bg-white dark:bg-slate-800 rounded-xl border dark:border-slate-700 shadow-sm p-6">
            <h3 class="text-lg font-semibold text-slate-800 dark:text-white mb-6 flex items-center gap-2">
              <CreditCard :size="20" class="text-blue-600" />
              By Payment Method
            </h3>
            <div class="space-y-3">
              <div v-for="method in paymentReport.method_breakdown" :key="method.method"
                class="flex items-center justify-between p-4 bg-slate-50 dark:bg-slate-700/50 rounded-lg">
                <div>
                  <p class="font-medium text-slate-800 dark:text-white capitalize">{{ method.method || 'Not Specified' }}</p>
                  <p class="text-sm text-slate-500 dark:text-slate-400">{{ method.count }} transactions</p>
                </div>
                <p class="text-lg font-bold text-slate-800 dark:text-white">
                  {{ formatCurrency(method.total) }}
                </p>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Refund Report -->
      <div v-else-if="activeTab === 'refund' && refundReport" class="space-y-6">
        <!-- Export Actions -->
        <div class="flex justify-end gap-2">
          <button @click="downloadPDF" class="flex items-center gap-2 px-4 py-2 bg-red-600 hover:bg-red-700 text-white rounded-lg transition-colors">
            <Download :size="18" />
            Export PDF
          </button>
          <button @click="downloadExcel" class="flex items-center gap-2 px-4 py-2 bg-green-600 hover:bg-green-700 text-white rounded-lg transition-colors">
            <FileSpreadsheet :size="18" />
            Export Excel
          </button>
        </div>

        <!-- Summary Cards -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
          <div class="bg-gradient-to-br from-red-500 to-red-600 rounded-xl shadow-lg p-6 text-white">
            <div class="flex items-center justify-between mb-4">
              <div class="p-3 bg-white/20 rounded-lg">
                <RefreshCw :size="24" />
              </div>
            </div>
            <p class="text-red-100 text-sm mb-1">Total Refunded</p>
            <p class="text-3xl font-bold">{{ formatCurrency(refundReport.total_refunded) }}</p>
          </div>

          <div class="bg-gradient-to-br from-orange-500 to-orange-600 rounded-xl shadow-lg p-6 text-white">
            <div class="flex items-center justify-between mb-4">
              <div class="p-3 bg-white/20 rounded-lg">
                <BarChart3 :size="24" />
              </div>
            </div>
            <p class="text-orange-100 text-sm mb-1">Total Refunds</p>
            <p class="text-3xl font-bold">{{ refundReport.total_count }}</p>
          </div>

          <div class="bg-gradient-to-br from-purple-500 to-purple-600 rounded-xl shadow-lg p-6 text-white">
            <div class="flex items-center justify-between mb-4">
              <div class="p-3 bg-white/20 rounded-lg">
                <DollarSign :size="24" />
              </div>
            </div>
            <p class="text-purple-100 text-sm mb-1">Average Refund</p>
            <p class="text-3xl font-bold">
              {{ formatCurrency(refundReport.total_refunded / refundReport.total_count) }}
            </p>
          </div>
        </div>

        <!-- Refunds by Type -->
        <div class="bg-white dark:bg-slate-800 rounded-xl border dark:border-slate-700 shadow-sm p-6">
          <h3 class="text-lg font-semibold text-slate-800 dark:text-white mb-6 flex items-center gap-2">
            <PieChart :size="20" class="text-blue-600" />
            Refunds by Transaction Type
          </h3>
          <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div class="bg-gradient-to-br from-red-50 to-red-100 dark:from-red-900/20 dark:to-red-800/20 rounded-xl p-8 border border-red-200 dark:border-red-800">
              <div class="flex items-center gap-3 mb-4">
                <div class="p-3 bg-red-500 rounded-lg">
                  <RefreshCw :size="24" class="text-white" />
                </div>
                <p class="text-sm font-medium text-slate-700 dark:text-slate-300">Reservation Refunds</p>
              </div>
              <p class="text-4xl font-bold text-red-600 dark:text-red-400">
                {{ formatCurrency(refundReport.refunds_by_type.reservation || 0) }}
              </p>
            </div>

            <div class="bg-gradient-to-br from-orange-50 to-orange-100 dark:from-orange-900/20 dark:to-orange-800/20 rounded-xl p-8 border border-orange-200 dark:border-orange-800">
              <div class="flex items-center gap-3 mb-4">
                <div class="p-3 bg-orange-500 rounded-lg">
                  <RefreshCw :size="24" class="text-white" />
                </div>
                <p class="text-sm font-medium text-slate-700 dark:text-slate-300">Restaurant Refunds</p>
              </div>
              <p class="text-4xl font-bold text-orange-600 dark:text-orange-400">
                {{ formatCurrency(refundReport.refunds_by_type.order || 0) }}
              </p>
            </div>
          </div>
        </div>

        <!-- Refunds List -->
        <div v-if="refundReport.refunds_list && refundReport.refunds_list.length > 0"
          class="bg-white dark:bg-slate-800 rounded-xl border dark:border-slate-700 shadow-sm">
          <div class="px-6 py-4 border-b dark:border-slate-700">
            <h3 class="text-lg font-semibold text-slate-800 dark:text-white">Recent Refunds</h3>
          </div>
          <div class="overflow-x-auto">
            <table class="w-full">
              <thead class="bg-slate-50 dark:bg-slate-900">
                <tr>
                  <th class="text-left p-4 text-sm font-semibold text-slate-700 dark:text-slate-300">Transaction Ref</th>
                  <th class="text-left p-4 text-sm font-semibold text-slate-700 dark:text-slate-300">Customer</th>
                  <th class="text-left p-4 text-sm font-semibold text-slate-700 dark:text-slate-300">Type</th>
                  <th class="text-right p-4 text-sm font-semibold text-slate-700 dark:text-slate-300">Amount</th>
                  <th class="text-left p-4 text-sm font-semibold text-slate-700 dark:text-slate-300">Refunded At</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="refund in refundReport.refunds_list" :key="refund.id"
                  class="border-t dark:border-slate-700 hover:bg-slate-50 dark:hover:bg-slate-900 transition-colors">
                  <td class="p-4 font-mono text-sm text-slate-700 dark:text-slate-300">{{ refund.tx_ref }}</td>
                  <td class="p-4 text-slate-800 dark:text-white font-medium">{{ refund.customer_name }}</td>
                  <td class="p-4">
                    <span class="px-3 py-1 bg-slate-100 dark:bg-slate-700 text-slate-700 dark:text-slate-300 rounded-full text-xs font-medium capitalize">
                      {{ refund.type }}
                    </span>
                  </td>
                  <td class="p-4 text-right font-semibold text-red-600 dark:text-red-400">
                    {{ formatCurrency(refund.amount) }}
                  </td>
                  <td class="p-4 text-slate-600 dark:text-slate-400">{{ formatDate(refund.refunded_at) }}</td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </div>
  </DashboardLayout>
</template>

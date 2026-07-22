<script setup lang="ts">
import { ref, onMounted, watch } from 'vue'
import {
  Chart as ChartJS,
  CategoryScale,
  LinearScale,
  BarElement,
  Title,
  Tooltip,
  Legend,
} from 'chart.js'
import { Bar } from 'vue-chartjs'
import type { MonthlyRevenueData } from '../../types/dashboard'
import { getDashboard } from '../../services/dashboardService'
import api from '../../api/auth'

ChartJS.register(CategoryScale, LinearScale, BarElement, Title, Tooltip, Legend)

interface Props {
  data?: MonthlyRevenueData[]
  timeframe?: 'week' | 'month' | 'year'
}

const props = withDefaults(defineProps<Props>(), {
  data: () => [],
  timeframe: 'month',
})

// State for revenue data
const revenueData = ref<MonthlyRevenueData[]>([])

const loading = ref(false)
const error = ref<string | null>(null)
const selectedTimeframe = ref<'week' | 'month' | 'year'>(props.timeframe)

// Sample data for different timeframes
const sampleData = {
  week: [
    { month: 'Mon', revenue: 340 },
    { month: 'Tue', revenue: 450 },
    { month: 'Wed', revenue: 520 },
    { month: 'Thu', revenue: 380 },
    { month: 'Fri', revenue: 620 },
    { month: 'Sat', revenue: 850 },
    { month: 'Sun', revenue: 700 },
  ],
  month: [
    { month: 'Week 1', revenue: 2400 },
    { month: 'Week 2', revenue: 2800 },
    { month: 'Week 3', revenue: 3200 },
    { month: 'Week 4', revenue: 2900 },
  ],
  year: [
    { month: 'Jan', revenue: 2400 },
    { month: 'Feb', revenue: 2800 },
    { month: 'Mar', revenue: 3200 },
    { month: 'Apr', revenue: 2900 },
    { month: 'May', revenue: 3600 },
    { month: 'Jun', revenue: 4100 },
    { month: 'Jul', revenue: 3800 },
    { month: 'Aug', revenue: 4200 },
    { month: 'Sep', revenue: 3900 },
    { month: 'Oct', revenue: 4400 },
    { month: 'Nov', revenue: 4600 },
    { month: 'Dec', revenue: 5000 },
  ],
}

const chartData = ref({
  labels: [] as string[],
  datasets: [
    {
      label: 'Revenue',
      data: [] as number[],
      backgroundColor: [
        'rgba(13, 148, 136, 0.7)',
        'rgba(13, 148, 136, 0.75)',
        'rgba(13, 148, 136, 0.8)',
        'rgba(13, 148, 136, 0.85)',
        'rgba(13, 148, 136, 0.9)',
        'rgba(13, 148, 136, 0.95)',
        'rgba(13, 148, 136, 1)',
        'rgba(13, 148, 136, 0.95)',
        'rgba(13, 148, 136, 0.9)',
        'rgba(13, 148, 136, 0.85)',
        'rgba(13, 148, 136, 0.8)',
        'rgba(13, 148, 136, 0.75)',
      ],
      borderColor: '#0d9488',
      borderWidth: 2,
      borderRadius: 6,
      hoverBackgroundColor: 'rgba(13, 148, 136, 1)',
    },
  ],
})

const chartOptions = {
  responsive: true,
  maintainAspectRatio: true,
  indexAxis: 'x' as const,
  plugins: {
    legend: {
      display: true,
      position: 'top' as const,
      labels: {
        boxWidth: 12,
        padding: 15,
        font: {
          size: 12,
          weight: '600' as const,
        },
        color: '#64748b',
      },
    },
    tooltip: {
      backgroundColor: 'rgba(15, 23, 42, 0.9)',
      padding: 12,
      cornerRadius: 8,
      titleFont: { size: 13, weight: 'bold' as const },
      bodyFont: { size: 12 },
      callbacks: {
        label: function (context: any) {
          return `Revenue: $${context.parsed.y.toLocaleString()}`
        },
        afterLabel: function (context: any) {
          const total = context.dataset.data.reduce((a: number, b: number) => a + b, 0)
          const percentage = ((context.parsed.y / total) * 100).toFixed(1)
          return `Share: ${percentage}%`
        },
      },
    },
  },
  scales: {
    y: {
      beginAtZero: true,
      ticks: {
        callback: function (value: any) {
          return '$' + (value / 1000).toFixed(0) + 'k'
        },
        font: {
          size: 11,
        },
        color: '#94a3b8',
      },
      grid: {
        color: 'rgba(0, 0, 0, 0.05)',
        drawBorder: false,
      },
    },
    x: {
      grid: {
        display: false,
      },
      ticks: {
        font: {
          size: 11,
        },
        color: '#64748b',
      },
    },
  },
}

const totalRevenue = ref(0)
const averageRevenue = ref(0)
const maxRevenue = ref(0)

// Fetch revenue data from backend based on timeframe
const fetchRevenueData = async (timeframe: 'week' | 'month' | 'year') => {
  try {
    loading.value = true
    error.value = null
    
    // Try to fetch from backend API with timeframe parameter
    const response = await api.get('/admin/dashboard/revenue', {
      params: {
        timeframe: timeframe,
      },
      headers: {
        Authorization: `Bearer ${localStorage.getItem('token')}`,
      },
    })
    
    // Extract revenue data from response
    if (response.data && Array.isArray(response.data.data)) {
      revenueData.value = response.data.data
    } else if (response.data && Array.isArray(response.data)) {
      revenueData.value = response.data
    } else {
      // Fallback to sample data if backend doesn't have the endpoint
      revenueData.value = sampleData[timeframe]
    }
    
    updateChart()
  } catch (err: any) {
    console.warn(`Failed to fetch ${timeframe} revenue data from backend, using sample data:`, err)
    // Use sample data as fallback
    revenueData.value = sampleData[timeframe]
    updateChart()
  } finally {
    loading.value = false
  }
}

const updateChart = () => {
  chartData.value.labels = revenueData.value.map((d) => d.month)
  chartData.value.datasets[0].data = revenueData.value.map((d) => d.revenue)
  
  const revenues = revenueData.value.map((d) => d.revenue)
  totalRevenue.value = revenues.reduce((a, b) => a + b, 0)
  averageRevenue.value = revenues.length > 0 ? Math.round(totalRevenue.value / revenues.length) : 0
  maxRevenue.value = revenues.length > 0 ? Math.max(...revenues) : 0
}

// Set timeframe and fetch data
const setTimeframe = (timeframe: 'week' | 'month' | 'year') => {
  selectedTimeframe.value = timeframe
  fetchRevenueData(timeframe)
}

// Fetch data on component mount
onMounted(() => {
  if (props.data && props.data.length > 0) {
    revenueData.value = props.data
    updateChart()
  } else {
    fetchRevenueData(selectedTimeframe.value)
  }
})

// Watch for prop changes
watch(
  () => props.data,
  () => {
    if (props.data && props.data.length > 0) {
      revenueData.value = props.data
      updateChart()
    }
  },
  { deep: true },
)
</script>

<template>
  <div
    class="bg-white rounded-2xl shadow-sm border border-gray-100 p-4 sm:p-6 lg:p-8 hover:shadow-md transition-shadow duration-300"
  >
    <!-- Header Section -->
    <div
      class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-3 sm:gap-4 mb-6 sm:mb-8 pb-4 sm:pb-5 border-b border-gray-100"
    >
      <div class="flex-1">
        <h3 class="text-xl sm:text-2xl font-bold text-slate-900">
          Revenue Analytics
        </h3>
        <p class="text-sm text-slate-600 mt-1.5">
          Financial performance and revenue trends
        </p>
      </div>
      <div class="flex gap-2 flex-wrap">
        <button
          @click="setTimeframe('week')"
          class="px-3 py-2 text-xs font-semibold rounded-lg border border-gray-200 text-gray-700 hover:bg-gray-50 transition-colors"
          :class="selectedTimeframe === 'week' ? 'bg-blue-50 border-blue-200 text-blue-700' : ''"
        >
          Week
        </button>
        <button
          @click="setTimeframe('month')"
          class="px-3 py-2 text-xs font-semibold rounded-lg border border-gray-200 text-gray-700 hover:bg-gray-50 transition-colors"
          :class="selectedTimeframe === 'month' ? 'bg-blue-50 border-blue-200 text-blue-700' : ''"
        >
          Month
        </button>
        <button
          @click="setTimeframe('year')"
          class="px-3 py-2 text-xs font-semibold rounded-lg border border-gray-200 text-gray-700 hover:bg-gray-50 transition-colors"
          :class="selectedTimeframe === 'year' ? 'bg-blue-50 border-blue-200 text-blue-700' : ''"
        >
          Year
        </button>
      </div>
    </div>

    <!-- Error State -->
    <div v-if="error && !loading" class="p-4 bg-amber-50 border border-amber-200 rounded-lg mb-6">
      <div class="flex items-start gap-3">
        <span class="material-symbols-rounded text-amber-600 flex-shrink-0">warning</span>
        <div>
          <p class="text-sm font-medium text-amber-800">{{ error }}</p>
          <p class="text-xs text-amber-700 mt-1">Using cached data</p>
        </div>
      </div>
    </div>

    <!-- Stats Cards -->
    <div class="grid grid-cols-2 sm:grid-cols-3 gap-3 sm:gap-4 mb-6 sm:mb-8">
      <div class="p-3 sm:p-4 bg-gradient-to-br from-teal-50 to-teal-100/30 rounded-lg border border-teal-200">
        <p class="text-xs text-teal-700 font-semibold uppercase tracking-wide">Total Revenue</p>
        <p class="text-xl sm:text-2xl font-bold text-teal-900 mt-1">
          <span v-if="loading" class="text-base">Loading...</span>
          <span v-else>${{ (totalRevenue / 1000).toFixed(1) }}k</span>
        </p>
      </div>
      <div class="p-3 sm:p-4 bg-gradient-to-br from-blue-50 to-blue-100/30 rounded-lg border border-blue-200">
        <p class="text-xs text-blue-700 font-semibold uppercase tracking-wide">Average</p>
        <p class="text-xl sm:text-2xl font-bold text-blue-900 mt-1">
          <span v-if="loading" class="text-base">Loading...</span>
          <span v-else>${{ (averageRevenue / 1000).toFixed(1) }}k</span>
        </p>
      </div>
      <div class="p-3 sm:p-4 bg-gradient-to-br from-emerald-50 to-emerald-100/30 rounded-lg border border-emerald-200">
        <p class="text-xs text-emerald-700 font-semibold uppercase tracking-wide">Peak Revenue</p>
        <p class="text-xl sm:text-2xl font-bold text-emerald-900 mt-1">
          <span v-if="loading" class="text-base">Loading...</span>
          <span v-else>${{ (maxRevenue / 1000).toFixed(1) }}k</span>
        </p>
      </div>
    </div>
    
    <!-- Chart Container -->
    <div class="overflow-x-auto -mx-4 sm:-mx-6 lg:-mx-8">
      <div class="px-4 sm:px-6 lg:px-8">
        <div class="min-h-80 sm:min-h-96">
          <div v-if="loading" class="flex items-center justify-center h-full">
            <div class="text-center">
              <div class="animate-spin rounded-full h-10 w-10 border-b-2 border-teal-600 mx-auto mb-3"></div>
              <p class="text-slate-600 font-medium">Loading chart data...</p>
            </div>
          </div>
          <Bar v-else :data="chartData" :options="chartOptions" />
        </div>
      </div>
    </div>
  </div>
</template>

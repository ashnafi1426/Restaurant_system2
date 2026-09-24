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

import { useLanguageStore } from '../../stores/language'
const languageStore = useLanguageStore()

const revenueData = ref<MonthlyRevenueData[]>([])
const loading = ref(false)
const selectedTimeframe = ref<'week' | 'month' | 'year'>(props.timeframe)

const sampleData = {
  week: [
    { month: 'Mon', revenue: 34000 },
    { month: 'Tue', revenue: 45000 },
    { month: 'Wed', revenue: 52000 },
    { month: 'Thu', revenue: 38000 },
    { month: 'Fri', revenue: 62000 },
    { month: 'Sat', revenue: 85000 },
    { month: 'Sun', revenue: 70000 },
  ],
  month: [
    { month: 'Week 1', revenue: 120000 },
    { month: 'Week 2', revenue: 145000 },
    { month: 'Week 3', revenue: 160000 },
    { month: 'Week 4', revenue: 138000 },
  ],
  year: [
    { month: 'Jan', revenue: 120000 },
    { month: 'Feb', revenue: 140000 },
    { month: 'Mar', revenue: 160000 },
    { month: 'Apr', revenue: 145000 },
    { month: 'May', revenue: 180000 },
    { month: 'Jun', revenue: 210000 },
    { month: 'Jul', revenue: 190000 },
    { month: 'Aug', revenue: 220000 },
    { month: 'Sep', revenue: 195000 },
    { month: 'Oct', revenue: 230000 },
    { month: 'Nov', revenue: 240000 },
    { month: 'Dec', revenue: 260000 },
  ],
}

const chartData = ref({
  labels: [] as string[],
  datasets: [
    {
      label: 'Revenue',
      data: [] as number[],
      backgroundColor: [
        '#3b82f6', '#3b82f6', '#3b82f6', '#3b82f6',
        '#3b82f6', '#3b82f6', '#3b82f6', '#3b82f6',
        '#3b82f6', '#3b82f6', '#3b82f6', '#3b82f6'
      ],
      borderColor: '#2563eb',
      borderWidth: 1.5,
      borderRadius: 8,
      hoverBackgroundColor: '#60a5fa',
    },
  ],
})

const chartOptions = ref({
  responsive: true,
  maintainAspectRatio: false,
  plugins: {
    legend: { display: false },
    tooltip: {
      backgroundColor: '#0f172a',
      padding: 12,
      cornerRadius: 12,
      titleFont: { size: 12, weight: 'bold' as const },
      bodyFont: { size: 11 },
      callbacks: {
        label: function (context: any) {
          return `Revenue: ${context.parsed.y.toLocaleString()} ETB`
        },
      },
    },
  },
  scales: {
    y: {
      beginAtZero: true,
      ticks: {
        callback: function (value: any) {
          const val = Number(value)
          if (val === 0) return '0 ETB'
          if (val >= 1000) {
            const k = val / 1000
            return (k % 1 === 0 ? k.toFixed(0) : k.toFixed(1)) + 'k ETB'
          }
          return val + ' ETB'
        },
        font: { size: 11, weight: '600' as const },
        color: '#94a3b8',
      },
      grid: {
        color: 'rgba(148, 163, 184, 0.1)',
        drawBorder: false,
      },
    },
    x: {
      grid: { display: false },
      ticks: {
        font: { size: 11, weight: '600' as const },
        color: '#94a3b8',
      },
    },
  },
})

const totalRevenue = ref(0)
const averageRevenue = ref(0)
const maxRevenue = ref(0)

const fetchRevenueData = async (timeframe: 'week' | 'month' | 'year') => {
  loading.value = true
  try {
    const response = await api.get('/admin/dashboard/revenue', {
      params: { timeframe },
      headers: { Authorization: `Bearer ${localStorage.getItem('token')}` },
    })

    if (response.data && Array.isArray(response.data.data) && response.data.data.length > 0) {
      revenueData.value = response.data.data
    } else {
      revenueData.value = sampleData[timeframe]
    }
  } catch (err) {
    console.error('[MonthlyRevenueChart] Failed to fetch revenue, using sample data:', err)
    revenueData.value = sampleData[timeframe]
  } finally {
    updateChart()
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

const setTimeframe = (tf: 'week' | 'month' | 'year') => {
  selectedTimeframe.value = tf
  fetchRevenueData(tf)
}

onMounted(() => {
  if (props.data && props.data.length > 0) {
    revenueData.value = props.data
    updateChart()
  } else {
    fetchRevenueData(selectedTimeframe.value)
  }
})

watch(() => props.data, () => {
  if (props.data && props.data.length > 0) {
    revenueData.value = props.data
    updateChart()
  }
}, { deep: true })
</script>

<template>
  <div class="bg-white dark:bg-slate-900 rounded-3xl border border-slate-200 dark:border-slate-800 p-5 shadow-xs space-y-5">
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-3 pb-3 border-b border-slate-100 dark:border-slate-800">
      <div>
        <h3 class="text-base font-black text-slate-900 dark:text-white">{{ languageStore.t('revenue_analytics', 'Revenue Analytics') }}</h3>
        <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">{{ languageStore.t('revenue_analytics_desc', 'Financial performance & revenue trends') }}</p>
      </div>

      <div class="flex items-center gap-1 bg-slate-100 dark:bg-slate-950 p-1 rounded-2xl border border-slate-200 dark:border-slate-800">
        <button
          @click="setTimeframe('week')"
          :class="[
            'px-3 py-1 text-xs font-black rounded-xl transition cursor-pointer',
            selectedTimeframe === 'week' ? 'bg-blue-600 text-white shadow-xs' : 'text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white'
          ]"
        >
          {{ languageStore.t('Week', 'Week') }}
        </button>
        <button
          @click="setTimeframe('month')"
          :class="[
            'px-3 py-1 text-xs font-black rounded-xl transition cursor-pointer',
            selectedTimeframe === 'month' ? 'bg-blue-600 text-white shadow-xs' : 'text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white'
          ]"
        >
          {{ languageStore.t('Month', 'Month') }}
        </button>
        <button
          @click="setTimeframe('year')"
          :class="[
            'px-3 py-1 text-xs font-black rounded-xl transition cursor-pointer',
            selectedTimeframe === 'year' ? 'bg-blue-600 text-white shadow-xs' : 'text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white'
          ]"
        >
          {{ languageStore.t('Year', 'Year') }}
        </button>
      </div>
    </div>

    <div class="grid grid-cols-3 gap-3">
      <div class="p-3 bg-emerald-500/10 border border-emerald-500/20 rounded-2xl">
        <p class="text-[10px] font-bold uppercase text-emerald-600 dark:text-emerald-400">{{ languageStore.t('total_revenue', 'Total Revenue') }}</p>
        <p class="text-lg font-black text-slate-900 dark:text-white mt-0.5">
          {{ (totalRevenue / 1000).toFixed(1) }}k ETB
        </p>
      </div>

      <div class="p-3 bg-blue-500/10 border border-blue-500/20 rounded-2xl">
        <p class="text-[10px] font-bold uppercase text-blue-600 dark:text-blue-400">{{ languageStore.t('average', 'Average') }}</p>
        <p class="text-lg font-black text-slate-900 dark:text-white mt-0.5">
          {{ (averageRevenue / 1000).toFixed(1) }}k ETB
        </p>
      </div>

      <div class="p-3 bg-purple-500/10 border border-purple-500/20 rounded-2xl">
        <p class="text-[10px] font-bold uppercase text-purple-600 dark:text-purple-400">{{ languageStore.t('peak_revenue', 'Peak Revenue') }}</p>
        <p class="text-lg font-black text-slate-900 dark:text-white mt-0.5">
          {{ (maxRevenue / 1000).toFixed(1) }}k ETB
        </p>
      </div>
    </div>

    <div class="h-64 relative w-full">
      <Bar v-if="!loading" :data="chartData" :options="chartOptions" />
      <div v-else class="flex items-center justify-center h-full text-xs font-bold text-slate-400">
        Loading chart...
      </div>
    </div>
  </div>
</template>

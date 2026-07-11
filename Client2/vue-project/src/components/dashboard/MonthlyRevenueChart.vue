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

ChartJS.register(CategoryScale, LinearScale, BarElement, Title, Tooltip, Legend)

interface Props {
  data?: MonthlyRevenueData[]
}

const props = withDefaults(defineProps<Props>(), {
  data: () => [
    { month: 'Jan', revenue: 2400 },
    { month: 'Feb', revenue: 2800 },
    { month: 'Mar', revenue: 3200 },
    { month: 'Apr', revenue: 2900 },
    { month: 'May', revenue: 3600 },
    { month: 'Jun', revenue: 4100 },
  ],
})

const chartData = ref({
  labels: [] as string[],
  datasets: [
    {
      label: 'Monthly Revenue ($)',
      data: [] as number[],
      backgroundColor: [
        'rgba(167, 243, 208, 0.6)',
        'rgba(167, 243, 208, 0.7)',
        'rgba(134, 239, 172, 0.7)',
        'rgba(110, 231, 183, 0.7)',
        'rgba(45, 212, 191, 0.8)',
        'rgba(20, 184, 166, 0.9)',
      ],
      borderColor: '#0f766e',
      borderWidth: 2,
      borderRadius: 4,
    },
  ],
})

const chartOptions = {
  responsive: true,
  maintainAspectRatio: true,
  plugins: {
    legend: {
      display: true,
      position: 'top' as const,
      labels: {
        boxWidth: 12,
        padding: 15,
        font: {
          size: 12,
          weight: '500' as const,
        },
      },
    },
    tooltip: {
      backgroundColor: 'rgba(0, 0, 0, 0.8)',
      padding: 12,
      titleFont: { size: 13, weight: 'bold' as const },
      bodyFont: { size: 12 },
      callbacks: {
        label: function (context: any) {
          return `$${context.parsed.y.toLocaleString()}`
        },
      },
    },
  },
  scales: {
    y: {
      beginAtZero: true,
      ticks: {
        callback: function (value: any) {
          return '$' + (value / 1000).toFixed(1) + 'k'
        },
      },
      grid: {
        color: 'rgba(0, 0, 0, 0.05)',
      },
    },
    x: {
      grid: {
        display: false,
      },
    },
  },
}

const updateChart = () => {
  chartData.value.labels = props.data.map((d) => d.month)
  chartData.value.datasets[0].data = props.data.map((d) => d.revenue)
}

onMounted(() => {
  updateChart()
})

watch(
  () => props.data,
  () => {
    updateChart()
  },
  { deep: true },
)
</script>

<template>
  <div class="bg-white rounded-lg border border-gray-200 px-4 sm:px-6 md:px-8 lg:px-10 py-4 sm:py-5 md:py-6 lg:py-8">
    <div class="flex flex-col sm:flex-row justify-between items-start gap-3 sm:gap-4 mb-4 sm:mb-5 md:mb-6">
      <div>
        <h3 class="text-base sm:text-lg md:text-xl lg:text-2xl font-semibold text-gray-900">Monthly Revenue</h3>
        <p class="text-xs sm:text-sm md:text-base text-gray-600 mt-1">Annual comparison of fiscal performance</p>
      </div>
      <button
        class="px-3 sm:px-4 py-1.5 sm:py-2 bg-gray-100 text-gray-700 text-xs sm:text-sm font-medium rounded hover:bg-gray-200 transition-colors whitespace-nowrap"
      >
        Last 6 Months
      </button>
    </div>
    <div class="overflow-x-auto">
      <Bar :data="chartData" :options="chartOptions" />
    </div>
  </div>
</template>

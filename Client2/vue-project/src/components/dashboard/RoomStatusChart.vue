<script setup lang="ts">
import { ref, watch } from 'vue'
import { Chart as ChartJS, ArcElement, Tooltip, Legend } from 'chart.js'
import { Doughnut } from 'vue-chartjs'

ChartJS.register(ArcElement, Tooltip, Legend)

interface Props {
  occupied?: number
  available?: number
  reserved?: number
  maintenance?: number
}

const props = withDefaults(defineProps<Props>(), {
  occupied: 0,
  available: 0,
  reserved: 0,
  maintenance: 0,
})

const chartData = ref({
  labels: ['Occupied', 'Available', 'Reserved', 'Maintenance'],
  datasets: [
    {
      data: [props.occupied, props.available, props.reserved, props.maintenance],
      backgroundColor: ['#0d9488', '#bfdbfe', '#f59e0b', '#fbbf24'],
      borderColor: ['#ffffff', '#ffffff', '#ffffff', '#ffffff'],
      borderWidth: 3,
    },
  ],
})

const chartOptions = {
  responsive: true,
  maintainAspectRatio: true,
  plugins: {
    legend: {
      display: false,
    },
    tooltip: {
      backgroundColor: 'rgba(0, 0, 0, 0.8)',
      padding: 12,
      callbacks: {
        label: function (context: any) {
          const label = context.label || ''
          const value = context.parsed || 0
          return `${label}: ${value} rooms`
        },
      },
    },
  },
}

const total = ref(0)

const updateChart = () => {
  chartData.value.datasets[0].data = [
    props.occupied,
    props.available,
    props.reserved,
    props.maintenance,
  ]
  total.value = props.occupied + props.available + props.reserved + props.maintenance
}

watch(
  () => [props.occupied, props.available, props.reserved, props.maintenance],
  () => {
    updateChart()
  },
)

updateChart()
</script>

<template>
  <div class="bg-white rounded-lg border border-gray-200 px-4 sm:px-6 md:px-8 lg:px-10 py-4 sm:py-5 md:py-6 lg:py-8">
    <h3 class="text-base sm:text-lg md:text-xl lg:text-2xl font-semibold text-gray-900 mb-4 sm:mb-5 md:mb-6">Room Status</h3>

    <div class="flex flex-col items-center justify-center">
      <!-- Chart -->
      <div class="w-full max-w-xs h-40 sm:h-48 md:h-56 flex items-center justify-center">
        <div class="relative w-32 sm:w-40 md:w-48 h-32 sm:h-40 md:h-48">
          <Doughnut :data="chartData" :options="chartOptions" />
          <!-- Center text -->
          <div
            class="absolute inset-0 flex flex-col items-center justify-center pointer-events-none"
          >
            <p class="text-2xl sm:text-3xl md:text-4xl font-bold text-gray-900">{{ total }}</p>
            <p class="text-xs sm:text-xs md:text-sm text-gray-600 mt-1">Total Rooms</p>
          </div>
        </div>
      </div>

      <!-- Statistics -->
      <div class="w-full space-y-2 mt-4 sm:mt-5 md:mt-6">
        <div class="flex items-center justify-between p-2 sm:p-3 md:p-4 bg-gray-50 rounded-lg">
          <div class="flex items-center gap-2">
            <div class="w-2 h-2 sm:w-3 sm:h-3 rounded-full bg-teal-600 flex-shrink-0"></div>
            <span class="text-xs sm:text-sm font-medium text-gray-700 truncate">Occupied</span>
          </div>
          <span class="text-xs sm:text-sm font-bold text-gray-900 flex-shrink-0">{{ props.occupied }}</span>
        </div>

        <div class="flex items-center justify-between p-2 sm:p-3 md:p-4 bg-gray-50 rounded-lg">
          <div class="flex items-center gap-2">
            <div class="w-2 h-2 sm:w-3 sm:h-3 rounded-full bg-blue-300 flex-shrink-0"></div>
            <span class="text-xs sm:text-sm font-medium text-gray-700 truncate">Available</span>
          </div>
          <span class="text-xs sm:text-sm font-bold text-gray-900 flex-shrink-0">{{ props.available }}</span>
        </div>

        <div class="flex items-center justify-between p-2 sm:p-3 md:p-4 bg-gray-50 rounded-lg">
          <div class="flex items-center gap-2">
            <div class="w-2 h-2 sm:w-3 sm:h-3 rounded-full bg-amber-500 flex-shrink-0"></div>
            <span class="text-xs sm:text-sm font-medium text-gray-700 truncate">Reserved</span>
          </div>
          <span class="text-xs sm:text-sm font-bold text-gray-900 flex-shrink-0">{{ props.reserved }}</span>
        </div>

        <div class="flex items-center justify-between p-2 sm:p-3 md:p-4 bg-gray-50 rounded-lg">
          <div class="flex items-center gap-2">
            <div class="w-2 h-2 sm:w-3 sm:h-3 rounded-full bg-amber-400 flex-shrink-0"></div>
            <span class="text-xs sm:text-sm font-medium text-gray-700 truncate">Maintenance</span>
          </div>
          <span class="text-xs sm:text-sm font-bold text-gray-900 flex-shrink-0">{{ props.maintenance }}</span>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, watch, onMounted } from 'vue'
import { Chart as ChartJS, ArcElement, Tooltip, Legend } from 'chart.js'
import { Doughnut } from 'vue-chartjs'
import { roomService } from '../../services/roomService'

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

// State for fetched data
const roomStats = ref({
  occupied: props.occupied,
  available: props.available,
  reserved: props.reserved,
  maintenance: props.maintenance,
})

const loading = ref(false)
const error = ref<string | null>(null)

const chartData = ref({
  labels: ['Occupied', 'Available', 'Reserved', 'Maintenance'],
  datasets: [
    {
      data: [
        roomStats.value.occupied,
        roomStats.value.available,
        roomStats.value.reserved,
        roomStats.value.maintenance,
      ],
      backgroundColor: ['#0d9488', '#60a5fa', '#f59e0b', '#ef4444'],
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
      backgroundColor: 'rgba(15, 23, 42, 0.9)',
      padding: 12,
      cornerRadius: 8,
      titleFont: { size: 13, weight: 'bold' as const },
      bodyFont: { size: 12 },
      callbacks: {
        label: function (context: any) {
          const label = context.label || ''
          const value = context.parsed || 0
          const total = context.dataset.data.reduce((a: number, b: number) => a + b, 0)
          const percentage = ((value / total) * 100).toFixed(1)
          return `${label}: ${value} (${percentage}%)`
        },
      },
    },
  },
}

const total = ref(0)

// Fetch room statistics from backend
const fetchRoomStats = async () => {
  try {
    loading.value = true
    error.value = null
    
    // Fetch all rooms
    const response = await roomService.getRooms()
    const rooms = response.data || []
    
    // Calculate stats based on room statuses
    let occupied = 0
    let available = 0
    let reserved = 0
    let maintenance = 0
    
    rooms.forEach((room: any) => {
      const status = room.status?.toLowerCase() || 'available'
      
      switch (status) {
        case 'occupied':
          occupied++
          break
        case 'available':
          available++
          break
        case 'reserved':
          reserved++
          break
        case 'maintenance':
          maintenance++
          break
        default:
          available++
      }
    })
    
    roomStats.value = {
      occupied,
      available,
      reserved,
      maintenance,
    }
    
    updateChart()
  } catch (err: any) {
    console.error('Failed to fetch room statistics:', err)
    error.value = err.response?.data?.message || 'Failed to fetch room data'
  } finally {
    loading.value = false
  }
}

const updateChart = () => {
  chartData.value.datasets[0].data = [
    roomStats.value.occupied,
    roomStats.value.available,
    roomStats.value.reserved,
    roomStats.value.maintenance,
  ]
  total.value =
    roomStats.value.occupied +
    roomStats.value.available +
    roomStats.value.reserved +
    roomStats.value.maintenance
}

// Fetch data on component mount
onMounted(() => {
  if (props.occupied === 0 && props.available === 0 && props.reserved === 0 && props.maintenance === 0) {
    fetchRoomStats()
  } else {
    updateChart()
  }
})

// Watch for prop changes
watch(
  () => [props.occupied, props.available, props.reserved, props.maintenance],
  () => {
    roomStats.value = {
      occupied: props.occupied,
      available: props.available,
      reserved: props.reserved,
      maintenance: props.maintenance,
    }
    updateChart()
  },
)
</script>

<template>
  <div
    class="bg-white rounded-2xl shadow-sm border border-gray-100 p-4 sm:p-6 hover:shadow-md transition-shadow duration-300"
  >
    <!-- Title Section -->
    <div class="mb-4 sm:mb-6 pb-3 sm:pb-4 border-b border-gray-100">
      <h3 class="text-lg sm:text-xl font-bold text-slate-900">Room Status Overview</h3>
      <p class="text-xs sm:text-sm text-slate-600 mt-1">Real-time availability</p>
    </div>

    <!-- Loading State -->
    <div v-if="loading" class="flex items-center justify-center py-8">
      <div class="text-center">
        <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-teal-600 mx-auto mb-3"></div>
        <p class="text-slate-600 text-sm font-medium">Loading...</p>
      </div>
    </div>

    <!-- Error State -->
    <div v-else-if="error" class="flex items-center justify-center py-8">
      <div class="text-center">
        <span class="material-symbols-rounded text-red-600 text-2xl mb-2 block">error</span>
        <p class="text-red-600 text-sm font-medium">{{ error }}</p>
      </div>
    </div>

    <!-- Data Display - Compact Layout -->
    <div v-else class="flex flex-col lg:flex-row items-start gap-4 lg:gap-6">
      <!-- Chart Section - Minimized -->
      <div class="w-full lg:w-48">
        <div class="relative w-full flex justify-center">
          <div class="relative w-40 h-40 sm:w-48 sm:h-48">
            <Doughnut :data="chartData" :options="chartOptions" />
            <!-- Center text -->
            <div class="absolute inset-0 flex flex-col items-center justify-center pointer-events-none">
              <p class="text-3xl sm:text-4xl font-bold text-slate-900">{{ total }}</p>
              <p class="text-xs text-slate-500 mt-1 font-semibold">Rooms</p>
            </div>
          </div>
        </div>
      </div>

      <!-- Statistics Section - Compact Right Sidebar -->
      <div class="w-full lg:flex-1 grid grid-cols-2 lg:grid-cols-1 gap-2 sm:gap-3">
        <!-- Occupied -->
        <div class="flex items-center justify-between p-2.5 sm:p-3 bg-gradient-to-br from-teal-50 to-teal-100/40 rounded-lg border border-teal-200 hover:border-teal-300 transition-all group">
          <div class="flex items-center gap-2 flex-1 min-w-0">
            <div class="w-2.5 h-2.5 rounded-full bg-teal-600 flex-shrink-0"></div>
            <span class="text-xs sm:text-sm font-semibold text-slate-700 truncate">Occupied</span>
          </div>
          <div class="text-right flex-shrink-0 ml-2">
            <span class="text-sm sm:text-base font-bold text-teal-700 block">{{ roomStats.occupied }}</span>
            <span class="text-xs text-teal-600 font-medium">
              {{ total > 0 ? Math.round((roomStats.occupied / total) * 100) : 0 }}%
            </span>
          </div>
        </div>

        <!-- Available -->
        <div class="flex items-center justify-between p-2.5 sm:p-3 bg-gradient-to-br from-blue-50 to-blue-100/40 rounded-lg border border-blue-200 hover:border-blue-300 transition-all group">
          <div class="flex items-center gap-2 flex-1 min-w-0">
            <div class="w-2.5 h-2.5 rounded-full bg-blue-500 flex-shrink-0"></div>
            <span class="text-xs sm:text-sm font-semibold text-slate-700 truncate">Available</span>
          </div>
          <div class="text-right flex-shrink-0 ml-2">
            <span class="text-sm sm:text-base font-bold text-blue-700 block">{{ roomStats.available }}</span>
            <span class="text-xs text-blue-600 font-medium">
              {{ total > 0 ? Math.round((roomStats.available / total) * 100) : 0 }}%
            </span>
          </div>
        </div>

        <!-- Reserved -->
        <div class="flex items-center justify-between p-2.5 sm:p-3 bg-gradient-to-br from-amber-50 to-amber-100/40 rounded-lg border border-amber-200 hover:border-amber-300 transition-all group">
          <div class="flex items-center gap-2 flex-1 min-w-0">
            <div class="w-2.5 h-2.5 rounded-full bg-amber-500 flex-shrink-0"></div>
            <span class="text-xs sm:text-sm font-semibold text-slate-700 truncate">Reserved</span>
          </div>
          <div class="text-right flex-shrink-0 ml-2">
            <span class="text-sm sm:text-base font-bold text-amber-700 block">{{ roomStats.reserved }}</span>
            <span class="text-xs text-amber-600 font-medium">
              {{ total > 0 ? Math.round((roomStats.reserved / total) * 100) : 0 }}%
            </span>
          </div>
        </div>

        <!-- Maintenance -->
        <div class="flex items-center justify-between p-2.5 sm:p-3 bg-gradient-to-br from-red-50 to-red-100/40 rounded-lg border border-red-200 hover:border-red-300 transition-all group">
          <div class="flex items-center gap-2 flex-1 min-w-0">
            <div class="w-2.5 h-2.5 rounded-full bg-red-500 flex-shrink-0"></div>
            <span class="text-xs sm:text-sm font-semibold text-slate-700 truncate">Maintenance</span>
          </div>
          <div class="text-right flex-shrink-0 ml-2">
            <span class="text-sm sm:text-base font-bold text-red-700 block">{{ roomStats.maintenance }}</span>
            <span class="text-xs text-red-600 font-medium">
              {{ total > 0 ? Math.round((roomStats.maintenance / total) * 100) : 0 }}%
            </span>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

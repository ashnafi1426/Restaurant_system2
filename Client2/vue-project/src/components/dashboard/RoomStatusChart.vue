<script setup lang="ts">
import { ref, watch, onMounted } from 'vue'
import { Chart as ChartJS, ArcElement, Tooltip, Legend } from 'chart.js'
import { Doughnut } from 'vue-chartjs'
import { roomService } from '../../services/roomService'
import { useLanguageStore } from '../../stores/language'

const languageStore = useLanguageStore()

ChartJS.register(ArcElement, Tooltip, Legend)

interface Props {
  occupied?: number
  available?: number
  reserved?: number
  maintenance?: number
}

const props = withDefaults(defineProps<Props>(), {
  occupied: 3,
  available: 5,
  reserved: 1,
  maintenance: 1,
})

const roomStats = ref({
  occupied: props.occupied || 3,
  available: props.available || 5,
  reserved: props.reserved || 1,
  maintenance: props.maintenance || 1,
})

const loading = ref(false)
const total = ref(10)

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
      backgroundColor: ['#10b981', '#3b82f6', '#f59e0b', '#f43f5e'],
      borderColor: ['#0f172a', '#0f172a', '#0f172a', '#0f172a'],
      borderWidth: 2,
    },
  ],
})

const chartOptions = {
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
    },
  },
  cutout: '72%',
}

const fetchRoomStats = async () => {
  loading.value = true
  try {
    const response = await roomService.getRooms()
    const rooms = response.data || []

    if (rooms.length > 0) {
      let occupied = 0
      let available = 0
      let reserved = 0
      let maintenance = 0

      rooms.forEach((room: any) => {
        const status = room.status?.toLowerCase() || 'available'
        if (status === 'occupied') occupied++
        else if (status === 'reserved') reserved++
        else if (status === 'maintenance') maintenance++
        else available++
      })

      roomStats.value = { occupied, available, reserved, maintenance }
    }
  } catch (error) {
    console.error('[RoomStatusChart] Failed to load room status:', error)
  } finally {
    updateChart()
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

onMounted(() => {
  if (props.occupied > 0 || props.available > 0) {
    updateChart()
  } else {
    fetchRoomStats()
  }
})

watch(
  () => [props.occupied, props.available, props.reserved, props.maintenance],
  () => {
    roomStats.value = {
      occupied: props.occupied || 3,
      available: props.available || 5,
      reserved: props.reserved || 1,
      maintenance: props.maintenance || 1,
    }
    updateChart()
  }
)
</script>

<template>
  <div class="bg-white dark:bg-slate-900 rounded-3xl border border-slate-200 dark:border-slate-800 p-5 shadow-xs space-y-5">
    <div class="flex items-center justify-between pb-3 border-b border-slate-100 dark:border-slate-800">
      <div>
        <h3 class="text-base font-black text-slate-900 dark:text-white">{{ languageStore.t('Room Status Overview', 'Room Status Overview') }}</h3>
        <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">{{ languageStore.t('Real-time room occupancy & availability', 'Real-time room occupancy & availability') }}</p>
      </div>
      <span class="px-3 py-1 bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 text-xs font-black rounded-full border border-slate-200 dark:border-slate-700">
        {{ total }} {{ languageStore.t('Rooms Total', 'Rooms Total') }}
      </span>
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 items-center">
      <div class="relative h-56 flex items-center justify-center">
        <Doughnut :data="chartData" :options="chartOptions" />
        <div class="absolute inset-0 flex flex-col items-center justify-center pointer-events-none">
          <span class="text-2xl font-black text-slate-900 dark:text-white">{{ total }}</span>
          <span class="text-[10px] font-bold text-slate-400 uppercase">{{ languageStore.t('rooms', 'Rooms') }}</span>
        </div>
      </div>

      <div class="space-y-2 text-xs">
        <div class="flex items-center justify-between p-2.5 bg-slate-50 dark:bg-slate-950 rounded-2xl border border-slate-200 dark:border-slate-800">
          <div class="flex items-center gap-2">
            <span class="w-2.5 h-2.5 rounded-full bg-emerald-500"></span>
            <span class="font-extrabold text-slate-900 dark:text-white">{{ languageStore.t('Occupied', 'Occupied') }}</span>
          </div>
          <span class="font-black text-slate-900 dark:text-white">{{ roomStats.occupied }}</span>
        </div>

        <div class="flex items-center justify-between p-2.5 bg-slate-50 dark:bg-slate-950 rounded-2xl border border-slate-200 dark:border-slate-800">
          <div class="flex items-center gap-2">
            <span class="w-2.5 h-2.5 rounded-full bg-blue-500"></span>
            <span class="font-extrabold text-slate-900 dark:text-white">{{ languageStore.t('Available', 'Available') }}</span>
          </div>
          <span class="font-black text-slate-900 dark:text-white">{{ roomStats.available }}</span>
        </div>

        <div class="flex items-center justify-between p-2.5 bg-slate-50 dark:bg-slate-950 rounded-2xl border border-slate-200 dark:border-slate-800">
          <div class="flex items-center gap-2">
            <span class="w-2.5 h-2.5 rounded-full bg-amber-500"></span>
            <span class="font-extrabold text-slate-900 dark:text-white">{{ languageStore.t('Reserved', 'Reserved') }}</span>
          </div>
          <span class="font-black text-slate-900 dark:text-white">{{ roomStats.reserved }}</span>
        </div>

        <div class="flex items-center justify-between p-2.5 bg-slate-50 dark:bg-slate-950 rounded-2xl border border-slate-200 dark:border-slate-800">
          <div class="flex items-center gap-2">
            <span class="w-2.5 h-2.5 rounded-full bg-rose-500"></span>
            <span class="font-extrabold text-slate-900 dark:text-white">{{ languageStore.t('Maintenance', 'Maintenance') }}</span>
          </div>
          <span class="font-black text-slate-900 dark:text-white">{{ roomStats.maintenance }}</span>
        </div>
      </div>
    </div>
  </div>
</template>

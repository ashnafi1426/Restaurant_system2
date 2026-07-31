import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import managerService from '@/services/managerService'
import type { OccupancySummary, OccupancyChartItem, ReservationSummary } from '@/types/manager'

export const useManagerOccupancyStore = defineStore('managerOccupancy', () => {
  /*
  |--------------------------------------------------------------------------
  | STATE
  |--------------------------------------------------------------------------
  */

  const occupancySummary = ref<OccupancySummary | null>(null)
  const occupancyChart = ref<OccupancyChartItem[]>([])
  const reservationSummary = ref<ReservationSummary | null>(null)
  const loading = ref(false)
  const error = ref<string | null>(null)

  /*
  |--------------------------------------------------------------------------
  | COMPUTED
  |--------------------------------------------------------------------------
  */

  const occupancy = computed(
    () =>
      occupancySummary.value || {
        totalRooms: 0,
        occupiedRooms: 0,
        availableRooms: 0,
        reservedRooms: 0,
        maintenanceRooms: 0,
        occupancyRate: 0,
      },
  )

  const occupancyData = computed(() => ({
    totalRooms: occupancySummary.value?.totalRooms ?? 0,
    occupiedRooms: occupancySummary.value?.occupiedRooms ?? 0,
    availableRooms: occupancySummary.value?.availableRooms ?? 0,
    todayCheckIns: 0,
    todayCheckOuts: 0,
  }))

  /*
  |--------------------------------------------------------------------------
  | ACTIONS
  |--------------------------------------------------------------------------
  */

  async function loadSummary() {
    try {
      loading.value = true
      error.value = null
      occupancySummary.value = await managerService.getOccupancySummary()
    } catch (err: any) {
      error.value = err.message
    } finally {
      loading.value = false
    }
  }

  async function loadChart() {
    try {
      occupancyChart.value = await managerService.getOccupancyChart()
    } catch (err: any) {
      error.value = err.message
    }
  }

  async function loadReservations() {
    try {
      reservationSummary.value = await managerService.getReservationSummary()
    } catch (err: any) {
      error.value = err.message
    }
  }

  async function initialize() {
    await Promise.all([loadSummary(), loadChart(), loadReservations()])
  }

  function reset() {
    occupancySummary.value = null
    occupancyChart.value = []
    reservationSummary.value = null
    error.value = null
  }

  return {
    occupancySummary,
    occupancyChart,
    reservationSummary,
    loading,
    error,
    occupancy,
    occupancyData,
    loadSummary,
    loadChart,
    loadReservations,
    initialize,
    reset,
  }
})

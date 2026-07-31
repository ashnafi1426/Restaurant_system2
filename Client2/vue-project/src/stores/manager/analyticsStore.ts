import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import managerService from '@/services/managerService'
import type {
  DashboardStatistics,
  RevenueSummary,
  OccupancySummary,
  ReservationSummary,
} from '@/types/manager'

export const useManagerAnalyticsStore = defineStore('managerAnalytics', () => {
  /*
  |--------------------------------------------------------------------------
  | STATE
  |--------------------------------------------------------------------------
  */

  const statistics = ref<DashboardStatistics | null>(null)
  const revenueSummary = ref<RevenueSummary | null>(null)
  const occupancySummary = ref<OccupancySummary | null>(null)
  const reservationSummary = ref<ReservationSummary | null>(null)
  const loading = ref(false)
  const error = ref<string | null>(null)

  /*
  |--------------------------------------------------------------------------
  | COMPUTED
  |--------------------------------------------------------------------------
  */

  const safeStatistics = computed(
    () =>
      statistics.value || {
        totalRooms: 0,
        occupiedRooms: 0,
        availableRooms: 0,
        reservedRooms: 0,
        maintenanceRooms: 0,
        totalGuests: 0,
        checkedInGuests: 0,
        guestCheckouts: 0,
        todayReservations: 0,
        pendingOrders: 0,
        preparingOrders: 0,
        completedOrders: 0,
        pendingLaundry: 0,
        pendingHousekeeping: 0,
        activeStaff: 0,
        pendingTasks: 0,
        todayRevenue: 0,
        monthlyRevenue: 0,
      },
  )

  const analyticsData = computed(() => ({
    statistics: safeStatistics.value,
    revenue: revenueSummary.value,
    occupancy: occupancySummary.value,
    reservations: reservationSummary.value,
  }))

  /*
  |--------------------------------------------------------------------------
  | ACTIONS
  |--------------------------------------------------------------------------
  */

  async function loadStatistics() {
    try {
      statistics.value = await managerService.getStatistics()
    } catch (err: any) {
      error.value = err.message
    }
  }

  async function loadRevenueSummary() {
    try {
      revenueSummary.value = await managerService.getRevenueSummary()
    } catch (err: any) {
      error.value = err.message
    }
  }

  async function loadOccupancySummary() {
    try {
      occupancySummary.value = await managerService.getOccupancySummary()
    } catch (err: any) {
      error.value = err.message
    }
  }

  async function loadReservationSummary() {
    try {
      reservationSummary.value = await managerService.getReservationSummary()
    } catch (err: any) {
      error.value = err.message
    }
  }

  async function initialize() {
    try {
      loading.value = true
      error.value = null
      await Promise.all([
        loadStatistics(),
        loadRevenueSummary(),
        loadOccupancySummary(),
        loadReservationSummary(),
      ])
    } finally {
      loading.value = false
    }
  }

  function reset() {
    statistics.value = null
    revenueSummary.value = null
    occupancySummary.value = null
    reservationSummary.value = null
    error.value = null
  }

  return {
    statistics,
    revenueSummary,
    occupancySummary,
    reservationSummary,
    loading,
    error,
    safeStatistics,
    analyticsData,
    loadStatistics,
    loadRevenueSummary,
    loadOccupancySummary,
    loadReservationSummary,
    initialize,
    reset,
  }
})

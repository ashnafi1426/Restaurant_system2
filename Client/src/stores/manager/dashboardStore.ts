import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import managerService from '@/services/managerService'
import type { ManagerDashboardResponse, DashboardStatistics } from '@/types/manager'

export const useManagerDashboardStore = defineStore('managerDashboard', () => {
  const dashboard = ref<ManagerDashboardResponse | null>(null)
  const statistics = ref<DashboardStatistics | null>(null)
  const loading = ref(false)
  const error = ref<string | null>(null)

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

  async function loadDashboard() {
    try {
      loading.value = true
      error.value = null
      const response = await managerService.getDashboard()
      dashboard.value = response
    } catch (err: any) {
      console.error('[manager/dashboardStore] Failed loading dashboard:', err)
      error.value = err.message || 'Failed loading dashboard'
    } finally {
      loading.value = false
    }
  }

  async function loadStatistics() {
    try {
      loading.value = true
      error.value = null
      const response = await managerService.getStatistics()
      statistics.value = response
    } catch (err: any) {
      console.error('[manager/dashboardStore] Failed loading dashboard statistics:', err)
      if (err.response?.status === 401) {
        error.value = 'Authentication failed - please log in again'
      } else if (err.response?.status === 403) {
        error.value = 'Access denied - insufficient permissions for manager'
      } else if (err.code === 'ECONNABORTED') {
        error.value = 'Request timeout - server not responding'
      } else if (!err.response) {
        error.value = 'Cannot connect to server'
      } else {
        error.value = err.message || 'Failed to load dashboard statistics'
      }
    } finally {
      loading.value = false
    }
  }

  async function initialize() {
    try {
      await Promise.all([loadDashboard(), loadStatistics()])
    } catch (err: any) {
      console.error('[ManagerDashboardStore] Error initializing dashboard:', err)
    }
  }

  function reset() {
    dashboard.value = null
    statistics.value = null
    error.value = null
  }

  return {
    dashboard,
    statistics,
    loading,
    error,
    safeStatistics,
    loadDashboard,
    loadStatistics,
    initialize,
    reset,
  }
})

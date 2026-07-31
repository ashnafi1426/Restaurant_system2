import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import managerService from '@/services/managerService'
import type { ManagerDashboardResponse, DashboardStatistics } from '@/types/manager'

export const useManagerDashboardStore = defineStore('managerDashboard', () => {
  /*
  |--------------------------------------------------------------------------
  | STATE
  |--------------------------------------------------------------------------
  */

  const dashboard = ref<ManagerDashboardResponse | null>(null)
  const statistics = ref<DashboardStatistics | null>(null)
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

  /*
  |--------------------------------------------------------------------------
  | ACTIONS
  |--------------------------------------------------------------------------
  */

  async function loadDashboard() {
    try {
      loading.value = true
      error.value = null
      const response = await managerService.getDashboard()
      dashboard.value = response
    } catch (err: any) {
      error.value = err.message || 'Failed loading dashboard'
    } finally {
      loading.value = false
    }
  }

  async function loadStatistics() {
    try {
      loading.value = true
      error.value = null
      
      console.log('[dashboardStore.loadStatistics] Loading statistics...')
      const response = await managerService.getStatistics()
      
      console.log('[dashboardStore.loadStatistics]  Statistics received from service:', response)
      console.log('[dashboardStore.loadStatistics] Setting statistics.value...')
      
      statistics.value = response
      
      console.log('[dashboardStore.loadStatistics]  statistics.value is now:', statistics.value)
      console.log('[dashboardStore.loadStatistics] Statistics stored successfully')
    } catch (err: any) {
      console.error('[dashboardStore.loadStatistics] ❌ Failed to load statistics:', err)
      
      // Better error messages based on error type
      if (err.response?.status === 401) {
        error.value = 'Authentication failed - please log in again'
        console.error('[dashboardStore.loadStatistics] 401 Unauthorized - no valid token')
      } else if (err.response?.status === 403) {
        error.value = 'Access denied - insufficient permissions for manager'
        console.error('[dashboardStore.loadStatistics] 403 Forbidden - permission denied')
      } else if (err.code === 'ECONNABORTED') {
        error.value = 'Request timeout - server not responding'
      } else if (!err.response) {
        error.value = 'Cannot connect to server'
        console.error('[dashboardStore.loadStatistics] Network error - server may be down')
      } else {
        error.value = err.message || 'Failed to load dashboard statistics'
      }
    } finally {
      loading.value = false
    }
  }

  async function initialize() {
    console.log('[dashboardStore.initialize] Starting dashboard initialization...')
    
    try {
      await Promise.all([loadDashboard(), loadStatistics()])
      console.log('[dashboardStore.initialize]  Dashboard initialization complete')
    } catch (err: any) {
      console.error('[dashboardStore.initialize] ❌ Initialization failed:', err)
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

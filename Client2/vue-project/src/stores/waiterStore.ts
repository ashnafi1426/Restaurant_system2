import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import { useAuthStore } from './auth'
import waiterService from '@/services/waiterService'
import type {
  WaiterAssignment,
  WaiterDashboard,
  WaiterProfile,
  DeliveryLog,
  WaiterPerformance,
  QuickStats,
  PerformanceMetrics,
} from '@/types/waiter'

export const useWaiterStore = defineStore('waiter', () => {
  // State with proper initialization
  const dashboard = ref<WaiterDashboard | null>({
    today_stats: {
      total_assignments: 0,
      completed_deliveries: 0,
      failed_deliveries: 0,
      rejected_assignments: 0,
      pending_assignments: 0,
      active_assignments: 0,
      average_delivery_time: 0,
      completion_rate: 0,
    },
    performance: [],
    recent_assignments: [],
    pending_count: 0,
    active_count: 0,
  })
  const assignments = ref<WaiterAssignment[]>([])
  const currentAssignment = ref<WaiterAssignment | null>(null)
  const profile = ref<WaiterProfile | null>(null)
  const deliveryHistory = ref<DeliveryLog[]>([])
  const performance = ref<any>(null)
  const quickStats = ref<QuickStats>({
    pending: 0,
    active: 0,
    completed: 0,
    failed: 0,
  })

  const isLoading = ref(false)
  const error = ref<string | null>(null)

  // Computed
  const hasPendingAssignments = computed(() => quickStats.value.pending > 0)
  const hasActiveDeliveries = computed(() => quickStats.value.active > 0)

  // Actions
  const fetchDashboard = async () => {
    isLoading.value = true
    error.value = null
    try {
      console.log('🔵 [STORE] fetchDashboard called')
      dashboard.value = await waiterService.getDashboard()
      console.log(' [STORE] Dashboard received:', dashboard.value)
    } catch (err: any) {
      error.value = err.message || 'Failed to fetch dashboard'
      console.error('❌ [STORE] Dashboard fetch error:', err)
      // Set default data to prevent blank screens
      dashboard.value = {
        today_stats: {
          total_assignments: 0,
          completed_deliveries: 0,
          failed_deliveries: 0,
          rejected_assignments: 0,
          pending_assignments: 0,
          active_assignments: 0,
          average_delivery_time: 0,
          completion_rate: 0,
        },
        performance: [],
        recent_assignments: [],
        pending_count: 0,
        active_count: 0,
      }
    } finally {
      isLoading.value = false
    }
  }

  const fetchQuickStats = async () => {
    try {
      console.log('🔵 [STORE] fetchQuickStats called')
      console.log('📋 [STORE] Current user from auth:', {
        id: useAuthStore().user?.id,
        email: useAuthStore().user?.email,
        role: useAuthStore().user?.role,
      })
      
      quickStats.value = await waiterService.getQuickStats()
      console.log(' [STORE] Quick stats received:', quickStats.value)
    } catch (err: any) {
      console.error('❌ [STORE] Quick stats fetch error:', err)
    }
  }

  const fetchAssignments = async (params: any = {}) => {
    console.log('🔵 [STORE] fetchAssignments called with params:', params)
    isLoading.value = true
    error.value = null
    try {
      const result = await waiterService.getAssignments(params)
      console.log(' [STORE] Assignments received:', result)
      assignments.value = result.data
      console.log('📊 [STORE] Total assignments in store:', assignments.value.length)
    } catch (err: any) {
      error.value = err.message || 'Failed to fetch assignments'
      console.error('❌ [STORE] Assignments fetch error:', err)
    } finally {
      isLoading.value = false
    }
  }

  const fetchPendingAssignments = async () => {
    try {
      assignments.value = await waiterService.getPendingAssignments()
    } catch (err: any) {
      console.error('Pending assignments fetch error:', err)
    }
  }

  const fetchActiveAssignments = async () => {
    try {
      assignments.value = await waiterService.getActiveAssignments()
    } catch (err: any) {
      console.error('Active assignments fetch error:', err)
    }
  }

  const fetchReadyForPickup = async () => {
    console.log('🔵 [STORE] fetchReadyForPickup called')
    isLoading.value = true
    error.value = null
    try {
      assignments.value = await waiterService.getReadyForPickup()
      console.log(' [STORE] Ready for pickup received:', assignments.value.length, 'items')
    } catch (err: any) {
      console.error('❌ [STORE] Ready for pickup fetch error:', err)
      error.value = err.message || 'Failed to fetch ready orders'
      assignments.value = []
    } finally {
      isLoading.value = false
    }
  }

  const fetchPendingPickupOrders = async () => {
    console.log('🔵 [STORE] fetchPendingPickupOrders called')
    isLoading.value = true
    error.value = null
    try {
      assignments.value = await waiterService.getPendingPickupOrders()
      console.log(' [STORE] Pending pickup orders received:', assignments.value.length, 'items')
    } catch (err: any) {
      console.error('❌ [STORE] Pending pickup orders fetch error:', err)
      error.value = err.message || 'Failed to fetch pending pickup orders'
      assignments.value = []
    } finally {
      isLoading.value = false
    }
  }

  // Merged method for ReadyPickup page that loads both ready and pending
  const fetchKitchenOrders = async () => {
    console.log('🔵 [STORE] fetchKitchenOrders called (merged ready + pending)')
    isLoading.value = true
    error.value = null
    try {
      console.log('📋 [STORE] Fetching ready orders...')
      const readyOrders = await waiterService.getReadyForPickup()
      console.log(' [STORE] Ready orders received:', readyOrders.length, 'items')
      
      console.log('📋 [STORE] Fetching pending orders...')
      const pendingOrders = await waiterService.getPendingPickupOrders()
      console.log(' [STORE] Pending orders received:', pendingOrders.length, 'items')
      
      // Merge results into assignments array
      assignments.value = [...readyOrders, ...pendingOrders]
      console.log(' [STORE] Total kitchen orders:', assignments.value.length)
    } catch (err: any) {
      console.error('❌ [STORE] Kitchen orders fetch error:', err)
      error.value = err.message || 'Failed to fetch kitchen orders'
      assignments.value = []
    } finally {
      isLoading.value = false
    }
  }

  const fetchOnDelivery = async () => {
    console.log('🔵 [STORE] fetchOnDelivery called')
    isLoading.value = true
    error.value = null
    try {
      assignments.value = await waiterService.getOnDelivery()
      console.log(' [STORE] On delivery assignments received:', assignments.value.length, 'items')
    } catch (err: any) {
      console.error('❌ [STORE] On delivery fetch error:', err)
      error.value = err.message || 'Failed to fetch on-delivery orders'
      assignments.value = []
    } finally {
      isLoading.value = false
    }
  }

  const fetchCompletedDeliveries = async () => {
    console.log('🔵 [STORE] fetchCompletedDeliveries called')
    isLoading.value = true
    error.value = null
    try {
      assignments.value = await waiterService.getCompletedDeliveries()
      console.log(' [STORE] Completed deliveries received:', assignments.value.length, 'items')
    } catch (err: any) {
      console.error('❌ [STORE] Completed deliveries fetch error:', err)
      error.value = err.message || 'Failed to fetch completed deliveries'
      assignments.value = []
    } finally {
      isLoading.value = false
    }
  }

  const fetchFailedDeliveries = async () => {
    console.log('🔵 [STORE] fetchFailedDeliveries called')
    isLoading.value = true
    error.value = null
    try {
      assignments.value = await waiterService.getFailedDeliveries()
      console.log(' [STORE] Failed deliveries received:', assignments.value.length, 'items')
    } catch (err: any) {
      console.error('❌ [STORE] Failed deliveries fetch error:', err)
      error.value = err.message || 'Failed to fetch failed deliveries'
      assignments.value = []
    } finally {
      isLoading.value = false
    }
  }

  const fetchProfile = async () => {
    try {
      profile.value = await waiterService.getProfile()
    } catch (err: any) {
      console.error('Profile fetch error:', err)
    }
  }

  const fetchPerformance = async () => {
    try {
      performance.value = await waiterService.getPerformance()
    } catch (err: any) {
      console.error('Performance fetch error:', err)
    }
  }

  const fetchHistory = async (params: any = {}) => {
    console.log('🔵 [STORE] fetchHistory called with params:', params)
    isLoading.value = true
    error.value = null
    try {
      const result = await waiterService.getHistory(params)
      deliveryHistory.value = result.data
      console.log(' [STORE] Delivery history received:', deliveryHistory.value.length, 'items')
    } catch (err: any) {
      console.error('❌ [STORE] History fetch error:', err)
      error.value = err.message || 'Failed to fetch delivery history'
      deliveryHistory.value = []
    } finally {
      isLoading.value = false
    }
  }

  const acceptAssignment = async (id: string) => {
    // Validate assignment exists and is in pending status
    const assignment = assignments.value.find(a => a.id === id)
    if (!assignment) {
      error.value = 'Assignment not found'
      return false
    }
    if (assignment.status !== 'pending') {
      error.value = `Cannot accept assignment with status: ${assignment.status}`
      return false
    }

    isLoading.value = true
    error.value = null
    try {
      currentAssignment.value = await waiterService.acceptAssignment(id)
      await Promise.all([fetchDashboard(), fetchAssignments()])
      return true
    } catch (err: any) {
      error.value = err.message || 'Failed to accept assignment'
      console.error('Accept assignment error:', err)
      return false
    } finally {
      isLoading.value = false
    }
  }

  const rejectAssignment = async (id: string, reason?: string) => {
    // Validate assignment exists and is in pending status
    const assignment = assignments.value.find(a => a.id === id)
    if (!assignment) {
      error.value = 'Assignment not found'
      return false
    }
    if (assignment.status !== 'pending') {
      error.value = `Cannot reject assignment with status: ${assignment.status}`
      return false
    }

    isLoading.value = true
    error.value = null
    try {
      currentAssignment.value = await waiterService.rejectAssignment(id, reason)
      await Promise.all([fetchDashboard(), fetchAssignments()])
      return true
    } catch (err: any) {
      error.value = err.message || 'Failed to reject assignment'
      console.error('Reject assignment error:', err)
      return false
    } finally {
      isLoading.value = false
    }
  }

  const pickupOrder = async (id: string) => {
    isLoading.value = true
    error.value = null
    try {
      currentAssignment.value = await waiterService.pickupOrder(id)
      await fetchDashboard()
      return true
    } catch (err: any) {
      error.value = err.message || 'Failed to pickup order'
      console.error('Pickup order error:', err)
      return false
    } finally {
      isLoading.value = false
    }
  }

  const startDelivery = async (id: string) => {
    isLoading.value = true
    error.value = null
    try {
      currentAssignment.value = await waiterService.startDelivery(id)
      await fetchDashboard()
      return true
    } catch (err: any) {
      error.value = err.message || 'Failed to start delivery'
      console.error('Start delivery error:', err)
      return false
    } finally {
      isLoading.value = false
    }
  }

  const deliverOrder = async (id: string, remarks?: string) => {
    isLoading.value = true
    error.value = null
    try {
      currentAssignment.value = await waiterService.deliverOrder(id, remarks)
      await fetchDashboard()
      return true
    } catch (err: any) {
      error.value = err.message || 'Failed to deliver order'
      console.error('Deliver order error:', err)
      return false
    } finally {
      isLoading.value = false
    }
  }

  const failDelivery = async (id: string, reason: string, remarks?: string) => {
    isLoading.value = true
    error.value = null
    try {
      currentAssignment.value = await waiterService.failDelivery(id, reason, remarks)
      await fetchDashboard()
      return true
    } catch (err: any) {
      error.value = err.message || 'Failed to mark delivery as failed'
      console.error('Fail delivery error:', err)
      return false
    } finally {
      isLoading.value = false
    }
  }

  const updateProfile = async (data: any) => {
    isLoading.value = true
    error.value = null
    try {
      profile.value = await waiterService.updateProfile(data)
      return true
    } catch (err: any) {
      error.value = err.message || 'Failed to update profile'
      console.error('Update profile error:', err)
      return false
    } finally {
      isLoading.value = false
    }
  }

  const exportHistory = async (filters?: any) => {
    try {
      const blob = await waiterService.exportHistory(filters)
      const url = URL.createObjectURL(blob)
      const link = document.createElement('a')
      link.href = url
      link.download = `delivery-history-${new Date().toISOString().split('T')[0]}.csv`
      link.click()
      URL.revokeObjectURL(url)
    } catch (err: any) {
      console.error('Export history error:', err)
    }
  }

  const refreshDashboard = async () => {
    await fetchDashboard()
    await fetchQuickStats()
  }

  const clearError = () => {
    error.value = null
  }

  return {
    // State
    dashboard,
    assignments,
    currentAssignment,
    profile,
    deliveryHistory,
    performance,
    quickStats,
    isLoading,
    error,

    // Computed
    hasPendingAssignments,
    hasActiveDeliveries,

    // Actions
    fetchDashboard,
    fetchQuickStats,
    fetchAssignments,
    fetchPendingAssignments,
    fetchActiveAssignments,
    fetchReadyForPickup,
    fetchPendingPickupOrders,
    fetchKitchenOrders,
    fetchOnDelivery,
    fetchCompletedDeliveries,
    fetchFailedDeliveries,
    fetchProfile,
    fetchPerformance,
    fetchHistory,
    acceptAssignment,
    rejectAssignment,
    pickupOrder,
    startDelivery,
    deliverOrder,
    failDelivery,
    updateProfile,
    exportHistory,
    refreshDashboard,
    clearError,
  }
})

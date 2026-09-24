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

  const hasPendingAssignments = computed(() => quickStats.value.pending > 0)
  const hasActiveDeliveries = computed(() => quickStats.value.active > 0)

  const fetchDashboard = async () => {
    isLoading.value = true
    error.value = null
    try {
      dashboard.value = await waiterService.getDashboard()
    } catch (err: any) {
      console.error('[WaiterStore] Error fetching dashboard:', err)
      error.value = err.message || 'Failed to fetch dashboard'
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
      quickStats.value = await waiterService.getQuickStats()
    } catch (err: any) {
      console.error('[WaiterStore] Error fetching quick stats:', err)
    }
  }

  const fetchAssignments = async (params: any = {}) => {
    isLoading.value = true
    error.value = null
    try {
      const result = await waiterService.getAssignments(params)
      assignments.value = result.data
    } catch (err: any) {
      console.error('[WaiterStore] Error fetching assignments:', err)
      error.value = err.message || 'Failed to fetch assignments'
    } finally {
      isLoading.value = false
    }
  }

  const fetchPendingAssignments = async () => {
    try {
      assignments.value = await waiterService.getPendingAssignments()
    } catch (err: any) {
      console.error('[WaiterStore] Error fetching pending assignments:', err)
    }
  }

  const fetchActiveAssignments = async () => {
    try {
      assignments.value = await waiterService.getActiveAssignments()
    } catch (err: any) {
      console.error('[WaiterStore] Error fetching active assignments:', err)
    }
  }

  const fetchReadyForPickup = async () => {
    isLoading.value = true
    error.value = null
    try {
      assignments.value = await waiterService.getReadyForPickup()
    } catch (err: any) {
      console.error('[WaiterStore] Error fetching ready orders:', err)
      error.value = err.message || 'Failed to fetch ready orders'
      assignments.value = []
    } finally {
      isLoading.value = false
    }
  }

  const fetchPendingPickupOrders = async () => {
    isLoading.value = true
    error.value = null
    try {
      assignments.value = await waiterService.getPendingPickupOrders()
    } catch (err: any) {
      console.error('[WaiterStore] Error fetching pending pickup orders:', err)
      error.value = err.message || 'Failed to fetch pending pickup orders'
      assignments.value = []
    } finally {
      isLoading.value = false
    }
  }

  const fetchKitchenOrders = async () => {
    isLoading.value = true
    error.value = null
    try {
      const readyOrders = await waiterService.getReadyForPickup()
      const pendingOrders = await waiterService.getPendingPickupOrders()
      assignments.value = [...readyOrders, ...pendingOrders]
    } catch (err: any) {
      console.error('[WaiterStore] Error fetching kitchen orders:', err)
      error.value = err.message || 'Failed to fetch kitchen orders'
      assignments.value = []
    } finally {
      isLoading.value = false
    }
  }

  const fetchOnDelivery = async () => {
    isLoading.value = true
    error.value = null
    try {
      assignments.value = await waiterService.getOnDelivery()
    } catch (err: any) {
      console.error('[WaiterStore] Error fetching on-delivery orders:', err)
      error.value = err.message || 'Failed to fetch on-delivery orders'
      assignments.value = []
    } finally {
      isLoading.value = false
    }
  }

  const fetchCompletedDeliveries = async () => {
    isLoading.value = true
    error.value = null
    try {
      assignments.value = await waiterService.getCompletedDeliveries()
    } catch (err: any) {
      console.error('[WaiterStore] Error fetching completed deliveries:', err)
      error.value = err.message || 'Failed to fetch completed deliveries'
      assignments.value = []
    } finally {
      isLoading.value = false
    }
  }

  const fetchFailedDeliveries = async () => {
    isLoading.value = true
    error.value = null
    try {
      assignments.value = await waiterService.getFailedDeliveries()
    } catch (err: any) {
      console.error('[WaiterStore] Error fetching failed deliveries:', err)
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
      console.error('[WaiterStore] Error fetching profile:', err)
    }
  }

  const fetchPerformance = async () => {
    try {
      performance.value = await waiterService.getPerformance()
    } catch (err: any) {
      console.error('[WaiterStore] Error fetching performance:', err)
    }
  }

  const fetchHistory = async (params: any = {}) => {
    isLoading.value = true
    error.value = null
    try {
      const result = await waiterService.getHistory(params)
      deliveryHistory.value = result.data
    } catch (err: any) {
      console.error('[WaiterStore] Error fetching history:', err)
      error.value = err.message || 'Failed to fetch delivery history'
      deliveryHistory.value = []
    } finally {
      isLoading.value = false
    }
  }

  const acceptAssignment = async (id: string) => {
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
      console.error('[WaiterStore] Error accepting assignment:', err)
      error.value = err.message || 'Failed to accept assignment'
      return false
    } finally {
      isLoading.value = false
    }
  }

  const rejectAssignment = async (id: string, reason?: string) => {
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
      console.error('[WaiterStore] Error rejecting assignment:', err)
      error.value = err.message || 'Failed to reject assignment'
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
      console.error('[WaiterStore] Error picking up order:', err)
      error.value = err.message || 'Failed to pickup order'
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
      console.error('[WaiterStore] Error starting delivery:', err)
      error.value = err.message || 'Failed to start delivery'
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
      console.error('[WaiterStore] Error delivering order:', err)
      error.value = err.message || 'Failed to deliver order'
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
      console.error('[WaiterStore] Error failing delivery:', err)
      error.value = err.message || 'Failed to mark delivery as failed'
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
      console.error('[WaiterStore] Error updating profile:', err)
      error.value = err.message || 'Failed to update profile'
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
      console.error('[WaiterStore] Error exporting history:', err)
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
    dashboard,
    assignments,
    currentAssignment,
    profile,
    deliveryHistory,
    performance,
    quickStats,
    isLoading,
    error,

    hasPendingAssignments,
    hasActiveDeliveries,

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

import { defineStore } from 'pinia'
import { ref } from 'vue'
import deliveryManagementService, { type DeliveryTask } from '@/services/manager/deliveryManagementService'

export const useDeliveryManagementStore = defineStore('deliveryManagement', () => {
  const deliveries = ref<DeliveryTask[]>([])
  const selectedDelivery = ref<DeliveryTask | null>(null)
  const todaySummary = ref<any>(null)
  const deliveryReport = ref<any>(null)
  const isLoading = ref(false)
  const error = ref<string | null>(null)
  const currentPage = ref(1)
  const perPage = ref(20)
  const totalDeliveries = ref(0)

  // Filters
  const filterStatus = ref<string | null>(null)
  const filterWaiterId = ref<string | null>(null)
  const filterFloorId = ref<string | null>(null)
  const filterType = ref<string | null>(null)
  const startDate = ref<string | null>(null)
  const endDate = ref<string | null>(null)

  // Actions
  async function fetchDeliveries(page = 1) {
    isLoading.value = true
    error.value = null
    try {
      const response = await deliveryManagementService.getDeliveries({
        page,
        per_page: perPage.value,
        status: filterStatus.value,
        waiter_id: filterWaiterId.value,
        floor_id: filterFloorId.value,
        assignment_type: filterType.value,
        start_date: startDate.value,
        end_date: endDate.value,
      })

      deliveries.value = response.data
      currentPage.value = response.pagination.current_page
      totalDeliveries.value = response.pagination.total
    } catch (err: any) {
      error.value = err.response?.data?.message || 'Failed to fetch deliveries'
      console.error('Error fetching deliveries:', err)
    } finally {
      isLoading.value = false
    }
  }

  async function getDelivery(deliveryId: string) {
    isLoading.value = true
    error.value = null
    try {
      selectedDelivery.value = await deliveryManagementService.getDelivery(deliveryId)
    } catch (err: any) {
      error.value = err.response?.data?.message || 'Failed to fetch delivery'
      console.error('Error fetching delivery:', err)
    } finally {
      isLoading.value = false
    }
  }

  async function reassignDelivery(
    deliveryId: string,
    newWaiterId: string,
    currentWaiterId: string,
    reason?: string
  ) {
    isLoading.value = true
    error.value = null
    try {
      const updated = await deliveryManagementService.reassignDelivery(
        deliveryId,
        newWaiterId,
        currentWaiterId,
        reason
      )

      const index = deliveries.value.findIndex(d => d.id === deliveryId)
      if (index !== -1) {
        deliveries.value[index] = updated
      }

      if (selectedDelivery.value?.id === deliveryId) {
        selectedDelivery.value = updated
      }

      return updated
    } catch (err: any) {
      error.value = err.response?.data?.message || 'Failed to reassign delivery'
      throw err
    } finally {
      isLoading.value = false
    }
  }

  async function cancelDelivery(deliveryId: string, reason?: string) {
    isLoading.value = true
    try {
      await deliveryManagementService.cancelDelivery(deliveryId, reason)
      deliveries.value = deliveries.value.filter(d => d.id !== deliveryId)
      totalDeliveries.value -= 1
    } catch (err: any) {
      error.value = err.response?.data?.message || 'Failed to cancel delivery'
      throw err
    } finally {
      isLoading.value = false
    }
  }

  async function fetchTodaySummary() {
    try {
      todaySummary.value = await deliveryManagementService.getTodaySummary()
    } catch (err: any) {
      console.error('Error fetching today\'s summary:', err)
    }
  }

  async function fetchDeliveryReport(startDate?: string, endDate?: string) {
    isLoading.value = true
    try {
      deliveryReport.value = await deliveryManagementService.getDeliveryReport({
        start_date: startDate,
        end_date: endDate,
      })
    } catch (err: any) {
      error.value = err.response?.data?.message || 'Failed to fetch report'
      console.error('Error fetching report:', err)
    } finally {
      isLoading.value = false
    }
  }

  function clearSelection() {
    selectedDelivery.value = null
  }

  function clearError() {
    error.value = null
  }

  return {
    // State
    deliveries,
    selectedDelivery,
    todaySummary,
    deliveryReport,
    isLoading,
    error,
    currentPage,
    perPage,
    totalDeliveries,
    filterStatus,
    filterWaiterId,
    filterFloorId,
    filterType,
    startDate,
    endDate,

    // Actions
    fetchDeliveries,
    getDelivery,
    reassignDelivery,
    cancelDelivery,
    fetchTodaySummary,
    fetchDeliveryReport,
    clearSelection,
    clearError,
  }
})

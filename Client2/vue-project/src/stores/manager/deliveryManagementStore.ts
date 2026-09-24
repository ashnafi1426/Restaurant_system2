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

  const filterStatus = ref<string | null>(null)
  const filterWaiterId = ref<string | null>(null)
  const filterFloorId = ref<string | null>(null)
  const filterType = ref<string | null>(null)
  const startDate = ref<string | null>(null)
  const endDate = ref<string | null>(null)

  async function fetchDeliveries(page = 1) {
    isLoading.value = true
    error.value = null
    try {
      const params: Record<string, any> = {
        page,
        per_page: perPage.value,
      }
      if (filterStatus.value) params.status = filterStatus.value
      if (filterWaiterId.value) params.waiter_id = filterWaiterId.value
      if (filterFloorId.value) params.floor_id = filterFloorId.value
      if (filterType.value) params.assignment_type = filterType.value
      if (startDate.value) params.start_date = startDate.value
      if (endDate.value) params.end_date = endDate.value

      const response = await deliveryManagementService.getDeliveries(params)
      const responseData = response.data || response

      if (responseData.pagination) {
        deliveries.value = Array.isArray(responseData.data) ? responseData.data : []
        currentPage.value = responseData.pagination.current_page || 1
        totalDeliveries.value = responseData.pagination.total || 0
        perPage.value = responseData.pagination.per_page || perPage.value
      } else if (responseData.data && Array.isArray(responseData.data) && (responseData.current_page || responseData.total)) {
        deliveries.value = responseData.data
        currentPage.value = responseData.current_page || page
        totalDeliveries.value = responseData.total || 0
        perPage.value = responseData.per_page || perPage.value
      } else if (Array.isArray(responseData)) {
        deliveries.value = responseData
        currentPage.value = page
        totalDeliveries.value = responseData.length
      } else if (responseData.data && Array.isArray(responseData.data)) {
        deliveries.value = responseData.data
        currentPage.value = page
        totalDeliveries.value = responseData.data.length
      } else {
        deliveries.value = []
      }
    } catch (err: any) {
      console.error('[DeliveryManagementStore] Error fetching deliveries:', err)
      error.value = err.response?.data?.message || 'Failed to fetch deliveries'
      deliveries.value = []
      currentPage.value = 1
      totalDeliveries.value = 0
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
      console.error('[DeliveryManagementStore] Error fetching delivery:', err)
      error.value = err.response?.data?.message || 'Failed to fetch delivery'
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
      console.error('[DeliveryManagementStore] Error reassigning delivery:', err)
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
      console.error('[DeliveryManagementStore] Error cancelling delivery:', err)
      error.value = err.response?.data?.message || 'Failed to cancel delivery'
      throw err
    } finally {
      isLoading.value = false
    }
  }

  async function fetchTodaySummary() {
    try {
      const response = await deliveryManagementService.getTodaySummary()
      todaySummary.value = response || {
        total_deliveries: 0,
        completed: 0,
        in_progress: 0,
        failed: 0,
        pending: 0,
        average_delivery_time: 0,
      }
    } catch (err: any) {
      console.error('[DeliveryManagementStore] Error fetching today summary:', err)
      todaySummary.value = {
        total_deliveries: 0,
        completed: 0,
        in_progress: 0,
        failed: 0,
        pending: 0,
        average_delivery_time: 0,
      }
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
      console.error('[DeliveryManagementStore] Error fetching delivery report:', err)
      error.value = err.response?.data?.message || 'Failed to fetch report'
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

export default useDeliveryManagementStore

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
      console.log('=== STORE: fetchDeliveries called ===')
      console.log('Page parameter:', page)
      console.log('perPage.value:', perPage.value)
      
      const params = {
        page,
        per_page: perPage.value,
        status: filterStatus.value,
        waiter_id: filterWaiterId.value,
        floor_id: filterFloorId.value,
        assignment_type: filterType.value,
        start_date: startDate.value,
        end_date: endDate.value,
      }
      
      console.log('=== STORE: Parameters object about to send ===')
      console.log('Full params object:', params)
      console.log('params.per_page:', params.per_page)
      console.log('params.page:', params.page)
      
      const response = await deliveryManagementService.getDeliveries(params)

      console.log('=== STORE: Response received back ===')
      console.log('Store received full response:', response)
      // Handle response format: { success, data: [...], pagination: {...} }
      const responseData = response.data || response
      
      console.log('Checking response structure:')
      console.log('  - Has pagination?', !!responseData.pagination)
      console.log('  - Has data.length?', responseData.data && responseData.data.length !== undefined)
      console.log('  - Is Array?', Array.isArray(responseData))
      
      // Priority 1: If response has pagination object, use it (our API format)
      if (responseData.pagination) {
        console.log('Processing paginated response format')
        deliveries.value = Array.isArray(responseData.data) ? responseData.data : []
        currentPage.value = responseData.pagination.current_page || 1
        totalDeliveries.value = responseData.pagination.total || 0
        perPage.value = responseData.pagination.per_page || perPage.value
        console.log('After pagination - perPage:', perPage.value, 'deliveries count:', deliveries.value.length, 'total:', totalDeliveries.value)
      } 
      // Priority 2: If response has data array and pagination info at root (Laravel pagination format)
      else if (responseData.data && Array.isArray(responseData.data) && (responseData.current_page || responseData.total)) {
        console.log('Processing Laravel paginate format')
        deliveries.value = responseData.data
        currentPage.value = responseData.current_page || page
        totalDeliveries.value = responseData.total || 0
        perPage.value = responseData.per_page || perPage.value
        console.log('After Laravel format - perPage:', perPage.value, 'deliveries count:', deliveries.value.length, 'total:', totalDeliveries.value)
      }
      // Priority 3: If response is just an array (fallback)
      else if (Array.isArray(responseData)) {
        console.log('Processing array response format')
        deliveries.value = responseData
        currentPage.value = page
        totalDeliveries.value = responseData.length
        console.log('After array format - deliveries count:', deliveries.value.length, 'total:', totalDeliveries.value)
      }
      // Priority 4: If response.data exists and is an array (our custom format)
      else if (responseData.data && Array.isArray(responseData.data)) {
        console.log('Processing custom data array format')
        deliveries.value = responseData.data
        currentPage.value = page
        totalDeliveries.value = responseData.data.length
        console.log('After custom format - deliveries count:', deliveries.value.length, 'total:', totalDeliveries.value)
      }
      else {
        console.log('No valid response format found')
        deliveries.value = []
      }
    } catch (err: any) {
      error.value = err.response?.data?.message || 'Failed to fetch deliveries'
      console.error('Error fetching deliveries:', err)
      // Set sensible defaults on error
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
      const response = await deliveryManagementService.getTodaySummary()
      console.log('Store received response:', response)
      // Response should already be the data object from the API service
      todaySummary.value = response || {
        total_deliveries: 0,
        completed: 0,
        in_progress: 0,
        failed: 0,
        pending: 0,
        average_delivery_time: 0,
      }
      console.log('Store todaySummary set to:', todaySummary.value)
    } catch (err: any) {
      console.error('Error fetching today\'s summary:', err)
      // Set defaults on error
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

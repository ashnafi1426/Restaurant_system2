import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import waiterManagementService, { type Waiter } from '@/services/manager/waiterManagementService'

export const useWaiterManagementStore = defineStore('waiterManagement', () => {
  const waiters = ref<Waiter[]>([])
  const selectedWaiter = ref<Waiter | null>(null)
  const isLoading = ref(false)
  const error = ref<string | null>(null)
  const searchQuery = ref('')
  const filterStatus = ref<string | null>(null)
  const filterAvailability = ref<string | null>(null)
  const currentPage = ref(1)
  const perPage = ref(15)
  const totalWaiters = ref(0)
  const totalPages = ref(0)

  // Computed
  const activeWaiters = computed(() => waiters.value.filter(w => w.status === 'active'))
  const inactiveWaiters = computed(() => waiters.value.filter(w => w.status === 'inactive'))
  const suspendedWaiters = computed(() => waiters.value.filter(w => w.status === 'suspended'))
  const busyWaiters = computed(() => waiters.value.filter(w => w.is_busy))
  const availableWaiters = computed(() =>
    waiters.value.filter(w => w.status === 'active' && w.availability === 'available' && !w.is_busy)
  )

  // Actions
  async function fetchWaiters(page = 1, search = '', status: string | null = null) {
    isLoading.value = true
    error.value = null
    try {
      const response = await waiterManagementService.getWaiters({
        page,
        per_page: perPage.value,
        search: search || searchQuery.value,
        status: status || filterStatus.value,
        availability: filterAvailability.value,
      })
      
      // Ensure we have valid data
      const waiterData = Array.isArray(response.data) ? response.data : (response.data?.data || [])
      waiters.value = waiterData
      
      if (response.pagination) {
        currentPage.value = response.pagination.current_page || 1
        totalWaiters.value = response.pagination.total || waiterData.length
        totalPages.value = response.pagination.last_page || 1
      } else {
        currentPage.value = 1
        totalWaiters.value = waiterData.length
        totalPages.value = 1
      }
    } catch (err: any) {
      error.value = err.response?.data?.message || 'Failed to fetch waiters'
      waiters.value = []
      console.error('Error fetching waiters:', err)
    } finally {
      isLoading.value = false
    }
  }

  async function getWaiter(waiterId: string) {
    isLoading.value = true
    error.value = null
    try {
      selectedWaiter.value = await waiterManagementService.getWaiter(waiterId)
    } catch (err: any) {
      error.value = err.response?.data?.message || 'Failed to fetch waiter'
      console.error('Error fetching waiter:', err)
    } finally {
      isLoading.value = false
    }
  }

  async function registerWaiter(data: any) {
    isLoading.value = true
    error.value = null
    try {
      const newWaiter = await waiterManagementService.registerWaiter(data)
      waiters.value.unshift(newWaiter)
      totalWaiters.value += 1
      return newWaiter
    } catch (err: any) {
      error.value = err.response?.data?.message || 'Failed to register waiter'
      throw err
    } finally {
      isLoading.value = false
    }
  }

  async function updateWaiter(waiterId: string, data: any) {
    isLoading.value = true
    error.value = null
    try {
      const updated = await waiterManagementService.updateWaiter(waiterId, data)
      const index = waiters.value.findIndex(w => w.id === waiterId)
      if (index !== -1) {
        waiters.value[index] = updated
      }
      if (selectedWaiter.value?.id === waiterId) {
        selectedWaiter.value = updated
      }
      return updated
    } catch (err: any) {
      error.value = err.response?.data?.message || 'Failed to update waiter'
      throw err
    } finally {
      isLoading.value = false
    }
  }

  async function deactivateWaiter(waiterId: string) {
    isLoading.value = true
    try {
      const updated = await waiterManagementService.deactivateWaiter(waiterId)
      const index = waiters.value.findIndex(w => w.id === waiterId)
      if (index !== -1) {
        waiters.value[index] = updated
      }
      return updated
    } catch (err: any) {
      error.value = err.response?.data?.message || 'Failed to deactivate waiter'
      throw err
    } finally {
      isLoading.value = false
    }
  }

  async function reactivateWaiter(waiterId: string) {
    isLoading.value = true
    try {
      const updated = await waiterManagementService.reactivateWaiter(waiterId)
      const index = waiters.value.findIndex(w => w.id === waiterId)
      if (index !== -1) {
        waiters.value[index] = updated
      }
      return updated
    } catch (err: any) {
      error.value = err.response?.data?.message || 'Failed to reactivate waiter'
      throw err
    } finally {
      isLoading.value = false
    }
  }

  async function suspendWaiter(waiterId: string, reason: string) {
    isLoading.value = true
    try {
      const updated = await waiterManagementService.suspendWaiter(waiterId, reason)
      const index = waiters.value.findIndex(w => w.id === waiterId)
      if (index !== -1) {
        waiters.value[index] = updated
      }
      return updated
    } catch (err: any) {
      error.value = err.response?.data?.message || 'Failed to suspend waiter'
      throw err
    } finally {
      isLoading.value = false
    }
  }

  async function changeAvailability(
    waiterId: string,
    availability: 'available' | 'busy' | 'break' | 'offline'
  ) {
    isLoading.value = true
    try {
      const updated = await waiterManagementService.changeAvailability(waiterId, availability)
      const index = waiters.value.findIndex(w => w.id === waiterId)
      if (index !== -1) {
        waiters.value[index] = updated
      }
      return updated
    } catch (err: any) {
      error.value = err.response?.data?.message || 'Failed to change availability'
      throw err
    } finally {
      isLoading.value = false
    }
  }

  async function deleteWaiter(waiterId: string) {
    isLoading.value = true
    try {
      await waiterManagementService.deleteWaiter(waiterId)
      waiters.value = waiters.value.filter(w => w.id !== waiterId)
      totalWaiters.value -= 1
    } catch (err: any) {
      error.value = err.response?.data?.message || 'Failed to delete waiter'
      throw err
    } finally {
      isLoading.value = false
    }
  }

  function clearSelection() {
    selectedWaiter.value = null
  }

  function clearError() {
    error.value = null
  }

  return {
    // State
    waiters,
    selectedWaiter,
    isLoading,
    error,
    searchQuery,
    filterStatus,
    filterAvailability,
    currentPage,
    perPage,
    totalWaiters,
    totalPages,

    // Computed
    activeWaiters,
    inactiveWaiters,
    suspendedWaiters,
    busyWaiters,
    availableWaiters,

    // Actions
    fetchWaiters,
    getWaiter,
    registerWaiter,
    updateWaiter,
    deactivateWaiter,
    reactivateWaiter,
    suspendWaiter,
    changeAvailability,
    deleteWaiter,
    clearSelection,
    clearError,
  }
})

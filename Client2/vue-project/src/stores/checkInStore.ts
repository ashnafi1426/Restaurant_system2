import { defineStore } from 'pinia'
import { ref } from 'vue'
import checkInService from '@/services/checkInService'
import type { CheckIn, CheckInStatistics } from '@/types/checkIn'

export interface CheckInPagination {
  current_page: number
  total: number
  per_page: number
  last_page: number
}

const DEFAULT_PAGINATION: CheckInPagination = {
  current_page: 1,
  total: 0,
  per_page: 10,
  last_page: 1,
}

const DEFAULT_STATISTICS: CheckInStatistics = {
  total_check_ins: 0,
  today_check_ins: 0,
  active_guests: 0,
  expected_today: 0,
}

export const useCheckInStore = defineStore('checkIn', () => {
  // State
  const checkIns = ref<CheckIn[]>([])
  const selectedCheckIn = ref<CheckIn | null>(null)
  const statistics = ref<CheckInStatistics>({ ...DEFAULT_STATISTICS })
  const loading = ref(false)
  const error = ref<string | null>(null)
  const pagination = ref<CheckInPagination>({ ...DEFAULT_PAGINATION })

  // Actions
  async function fetchCheckIns(params = {}) {
    loading.value = true
    error.value = null

    try {
      const response = await checkInService.getAll(params)
      const data = response?.data

      if (data && typeof data === 'object') {
        if (Array.isArray(data.data)) {
          checkIns.value = data.data
          pagination.value = {
            current_page: data.current_page || 1,
            total: data.total || data.data.length,
            per_page: data.per_page || 10,
            last_page: data.last_page || 1,
          }
        } else if (Array.isArray(data)) {
          checkIns.value = data
          pagination.value = {
            ...DEFAULT_PAGINATION,
            total: data.length,
          }
        } else {
          checkIns.value = [data]
          pagination.value = {
            ...DEFAULT_PAGINATION,
            total: 1,
          }
        }
      } else {
        checkIns.value = []
        pagination.value = { ...DEFAULT_PAGINATION }
      }
    } catch (err: any) {
      console.error('[checkInStore] Failed to fetch check-ins:', err)
      error.value = err?.message || 'Failed to fetch check-ins'
      checkIns.value = []
    } finally {
      loading.value = false
    }
  }

  async function fetchStatistics() {
    try {
      const response = await checkInService.getStatistics()
      if (response?.data) {
        statistics.value = response.data
      }
    } catch (err: any) {
      console.error('[checkInStore] Failed to fetch check-in statistics:', err)
    }
  }

  async function viewCheckIn(id: string) {
    try {
      const response = await checkInService.getById(id)
      selectedCheckIn.value = response?.data?.data || response?.data || null
    } catch (err: any) {
      console.error('[checkInStore] Failed to view check-in:', err)
    }
  }

  async function refreshDataAfterMutation(action: () => Promise<any>, errorMessage: string) {
    try {
      await action()
      await Promise.all([fetchCheckIns(), fetchStatistics()])
    } catch (err: any) {
      console.error(`[checkInStore] ${errorMessage}:`, err)
      error.value = err?.message || errorMessage
      throw err
    }
  }

  async function checkInGuest(reservationId: string) {
    return refreshDataAfterMutation(
      () => checkInService.checkIn(reservationId),
      'Failed to check in guest',
    )
  }

  async function checkOutGuest(checkInId: string) {
    return refreshDataAfterMutation(
      () => checkInService.checkOut(checkInId),
      'Failed to check out guest',
    )
  }

  async function deleteCheckIn(id: string) {
    return refreshDataAfterMutation(() => checkInService.delete(id), 'Failed to delete check-in')
  }

  return {
    checkIns,
    selectedCheckIn,
    statistics,
    loading,
    error,
    pagination,

    fetchCheckIns,
    fetchStatistics,
    viewCheckIn,
    checkInGuest,
    checkOutGuest,
    deleteCheckIn,
  }
})

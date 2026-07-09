import { defineStore } from 'pinia'

import checkInService from '../services/checkInService'

import type { CheckIn, CheckInStatistics } from '@/types/checkIn'
interface State {
  checkIns: CheckIn[]

  selectedCheckIn: CheckIn | null

  statistics: CheckInStatistics

  loading: boolean
  error: string | null
  pagination: {
    current_page: number
    total: number
    per_page: number
    last_page: number
  }
}

export const useCheckInStore = defineStore('checkIn', {
  state: (): State => ({
    checkIns: [],

    selectedCheckIn: null,

    loading: false,

    error: null,

    pagination: {
      current_page: 1,
      total: 0,
      per_page: 10,
      last_page: 1,
    },

    statistics: {
      total_check_ins: 0,

      today_check_ins: 0,

      active_guests: 0,

      expected_today: 0,
    },
  }),

  actions: {
    async fetchCheckIns(params = {}) {
      this.loading = true
      this.error = null

      try {
        console.log(' [STORE] fetchCheckIns called with params:', params)
        const response = await checkInService.getAll(params)

        console.log(' [STORE] API Response received:', response)
        console.log(' [STORE] Response.data structure:', Object.keys(response.data))

        // Handle paginated response
        if (response.data && typeof response.data === 'object') {
          if (response.data.data) {
            // Laravel paginated response
            console.log(' [STORE] Detected Laravel paginated response')
            console.log(' [STORE] Data count:', response.data.data.length)
            console.log('📄 [STORE] First record:', response.data.data[0])

            this.checkIns = response.data.data
            this.pagination = {
              current_page: response.data.current_page || 1,
              total: response.data.total || response.data.data.length,
              per_page: response.data.per_page || 10,
              last_page: response.data.last_page || 1,
            }
            console.log(' [STORE] Pagination set:', this.pagination)
          } else if (Array.isArray(response.data)) {
            // Simple array response
            console.log(' [STORE] Detected simple array response')
            this.checkIns = response.data
            this.pagination.total = response.data.length
          } else {
            // Single object or other format
            console.log(' [STORE] Detected single object response')
            this.checkIns = [response.data]
            this.pagination.total = 1
          }
        } else {
          console.warn(' [STORE] Unexpected response format:', response.data)
          this.checkIns = []
          this.pagination.total = 0
        }

        console.log(' [STORE] Store state after fetch:')
        console.log('  - checkIns.length:', this.checkIns.length)
        console.log('  - pagination:', this.pagination)
        console.log('  - loading:', this.loading)
      } catch (error: any) {
        console.error(' [STORE] Error fetching check-ins:', error)
        console.error(' [STORE] Error message:', error.message)
        console.error(' [STORE] Error response:', error.response?.data)
        console.error(' [STORE] Error status:', error.response?.status)

        this.error = error.message || 'Failed to fetch check-ins'
        this.checkIns = []
      } finally {
        this.loading = false
        console.log(' [STORE] Loading state set to false')
      }
    },

    async fetchStatistics() {
      try {
        const response = await checkInService.getStatistics()
        this.statistics = response.data
      } catch (error: any) {
        console.error(' [CHECK-IN] Error fetching statistics:', error)
      }
    },

    async viewCheckIn(id: string) {
      try {
        const response = await checkInService.getById(id)
        this.selectedCheckIn = response.data.data || response.data
      } catch (error: any) {
        console.error(' [CHECK-IN] Error viewing check-in:', error)
      }
    },

    async checkInGuest(reservationId: string) {
      try {
        await checkInService.checkIn(reservationId)
        await this.fetchCheckIns()
        await this.fetchStatistics()
      } catch (error: any) {
        console.error(' [CHECK-IN] Error checking in guest:', error)
        this.error = error.message || 'Failed to check in guest'
        throw error
      }
    },

    async checkOutGuest(checkInId: string) {
      try {
        await checkInService.checkOut(checkInId)
        await this.fetchCheckIns()
        await this.fetchStatistics()
      } catch (error: any) {
        console.error(' [CHECK-IN] Error checking out guest:', error)
        this.error = error.message || 'Failed to check out guest'
        throw error
      }
    },

    async deleteCheckIn(id: string) {
      try {
        await checkInService.delete(id)
        await this.fetchCheckIns()
        await this.fetchStatistics()
      } catch (error: any) {
        console.error(' [CHECK-IN] Error deleting check-in:', error)
        this.error = error.message || 'Failed to delete check-in'
        throw error
      }
    },
  },
})

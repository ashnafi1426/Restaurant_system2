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
        const response = await checkInService.getAll(params)

        if (response.data && typeof response.data === 'object') {
          if (response.data.data) {
            this.checkIns = response.data.data
            this.pagination = {
              current_page: response.data.current_page || 1,
              total: response.data.total || response.data.data.length,
              per_page: response.data.per_page || 10,
              last_page: response.data.last_page || 1,
            }
          } else if (Array.isArray(response.data)) {
            this.checkIns = response.data
            this.pagination.total = response.data.length
          } else {
            this.checkIns = [response.data]
            this.pagination.total = 1
          }
        } else {
          this.checkIns = []
          this.pagination.total = 0
        }
      } catch (error: any) {
        console.error('[checkInStore] Failed to fetch check-ins:', error)
        this.error = error.message || 'Failed to fetch check-ins'
        this.checkIns = []
      } finally {
        this.loading = false
      }
    },

    async fetchStatistics() {
      try {
        const response = await checkInService.getStatistics()
        this.statistics = response.data
      } catch (error: any) {
        console.error('[checkInStore] Failed to fetch check-in statistics:', error)
      }
    },

    async viewCheckIn(id: string) {
      try {
        const response = await checkInService.getById(id)
        this.selectedCheckIn = response.data.data || response.data
      } catch (error: any) {
        console.error('[checkInStore] Failed to view check-in:', error)
      }
    },

    async checkInGuest(reservationId: string) {
      try {
        await checkInService.checkIn(reservationId)
        await this.fetchCheckIns()
        await this.fetchStatistics()
      } catch (error: any) {
        console.error('[checkInStore] Failed to check in guest:', error)
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
        console.error('[checkInStore] Failed to check out guest:', error)
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
        console.error('[checkInStore] Failed to delete check-in:', error)
        this.error = error.message || 'Failed to delete check-in'
        throw error
      }
    },
  },
})

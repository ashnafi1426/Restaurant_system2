import { defineStore } from 'pinia'
import checkInService from '../services/checkInService'
import type { CheckIn, CheckInStatistics } from '@/types/checkIn'

interface CachedResponse<T> {
  data: T
  timestamp: number
  params: string
}

const CACHE_TTL = 3 * 60 * 1000 // 3 minutes cache

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
  cache: CachedResponse<CheckIn[]> | null
  statsCache: CachedResponse<CheckInStatistics> | null
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
    cache: null,
    statsCache: null,
  }),

  actions: {
    isCacheValid(params: any): boolean {
      if (!this.cache) return false
      
      const paramsKey = JSON.stringify(params)
      const now = Date.now()
      const cacheAge = now - this.cache.timestamp
      
      return this.cache.params === paramsKey && cacheAge < CACHE_TTL
    },

    isStatsCacheValid(): boolean {
      if (!this.statsCache) return false
      
      const now = Date.now()
      const cacheAge = now - this.statsCache.timestamp
      
      return cacheAge < CACHE_TTL
    },

    invalidateCache() {
      this.cache = null
      this.statsCache = null
    },

    async fetchCheckIns(params = {}) {
      // Check cache first
      if (this.isCacheValid(params)) {
        this.checkIns = this.cache!.data
        return
      }

      this.loading = true
      this.error = null

      try {
        const response = await checkInService.getAll(params)

        if (response.data && typeof response.data === 'object') {
          if (response.data.data) {
            this.checkIns = response.data.data
          } else {
            this.checkIns = response.data
          }

          // Cache the response
          this.cache = {
            data: this.checkIns,
            timestamp: Date.now(),
            params: JSON.stringify(params),
          }

          if (response.data.meta || response.data.current_page !== undefined) {
            const meta = response.data.meta || response.data
            this.pagination = {
              current_page: meta.current_page || 1,
              total: meta.total || 0,
              per_page: meta.per_page || 10,
              last_page: meta.last_page || 1,
            }
          }
        } else if (Array.isArray(response.data)) {
          this.checkIns = response.data
          this.cache = {
            data: response.data,
            timestamp: Date.now(),
            params: JSON.stringify(params),
          }
        }
      } catch (err: any) {
        this.error = err.message || 'Failed to fetch check-ins'
        console.error('[CheckInStore] Error fetching check-ins:', err)
      } finally {
        this.loading = false
      }
    },

    async fetchStatistics() {
      // Check stats cache first
      if (this.isStatsCacheValid()) {
        this.statistics = this.statsCache!.data
        return
      }

      try {
        const response = await checkInService.getStatistics()
        this.statistics = response.data || response

        // Cache statistics
        this.statsCache = {
          data: this.statistics,
          timestamp: Date.now(),
          params: '',
        }
      } catch (err: any) {
        console.error('[CheckInStore] Error fetching statistics:', err)
      }
    },

    async createCheckIn(reservationId: string) {
      this.loading = true
      this.error = null

      try {
        const response = await checkInService.create({ reservation_id: reservationId })
        this.invalidateCache() // Clear cache on create
        return response
      } catch (err: any) {
        this.error = err.message || 'Failed to create check-in'
        throw err
      } finally {
        this.loading = false
      }
    },

    async checkout(checkInId: string) {
      this.loading = true
      this.error = null

      try {
        const response = await checkInService.checkout(checkInId)
        this.invalidateCache() // Clear cache on checkout
        return response
      } catch (err: any) {
        this.error = err.message || 'Failed to checkout'
        throw err
      } finally {
        this.loading = false
      }
    },

    async deleteCheckIn(checkInId: string) {
      this.loading = true
      this.error = null

      try {
        await checkInService.delete(checkInId)
        this.invalidateCache() // Clear cache on delete
      } catch (err: any) {
        this.error = err.message || 'Failed to delete check-in'
        throw err
      } finally {
        this.loading = false
      }
    },
  },
})
import { defineStore } from 'pinia'
import { roomTypeService } from '../services/roomtypeService'
import type { RoomType } from '../types/roomType'

interface CachedResponse<T> {
  data: T
  timestamp: number
  params: string
}

const CACHE_TTL = 5 * 60 * 1000 // 5 minutes

export const useRoomTypeStore = defineStore('roomTypes', {
  state: () => ({
    roomTypes: [] as RoomType[],
    loading: false,
    error: null as string | null,
    cache: null as CachedResponse<RoomType[]> | null,
  }),

  actions: {
    isCacheValid(params: any): boolean {
      if (!this.cache) return false
      
      const paramsKey = JSON.stringify(params)
      const now = Date.now()
      const cacheAge = now - this.cache.timestamp
      
      return this.cache.params === paramsKey && cacheAge < CACHE_TTL
    },

    invalidateCache() {
      this.cache = null
    },

    async fetchRoomTypes(params: any = {}) {
      // Check cache first
      if (this.isCacheValid(params)) {
        this.roomTypes = this.cache!.data
        return
      }

      this.loading = true

      try {
        const res = await roomTypeService.getRoomTypes(params)
        const roomTypesData = res.data.data
        
        // Update state
        this.roomTypes = roomTypesData
        
        // Cache the response
        this.cache = {
          data: roomTypesData,
          timestamp: Date.now(),
          params: JSON.stringify(params),
        }
      } catch (e) {
        console.error('[RoomTypeStore] Error fetching room types:', e)
        this.error = 'Failed to load room types'
      } finally {
        this.loading = false
      }
    },

    async createRoomType(data: RoomType) {
      await roomTypeService.createRoomType(data)
      this.invalidateCache()
      await this.fetchRoomTypes()
    },

    async updateRoomType(id: string | number, data: RoomType) {
      await roomTypeService.updateRoomType(id, data)
      this.invalidateCache()
      await this.fetchRoomTypes()
    },

    async deleteRoomType(id: number | string) {
      await roomTypeService.deleteRoomType(id)
      this.invalidateCache()
      await this.fetchRoomTypes()
    },
  },
})
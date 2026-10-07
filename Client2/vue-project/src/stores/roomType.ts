import { defineStore } from 'pinia'
import { roomTypeService } from '@/services/roomtypeService'
import type { RoomType } from '@/types/roomType'

export const useRoomTypeStore = defineStore('roomTypes', {
  state: () => ({
    roomTypes: [] as RoomType[],
    loading: false,
    error: null as string | null,
  }),

  actions: {
    async fetchRoomTypes(params: any = {}) {
      this.loading = true
      this.error = null

      try {
        const res = await roomTypeService.getRoomTypes(params)
        this.roomTypes = Array.isArray(res.data) ? res.data : res.data?.data || []
      } catch (e: any) {
        console.error('[RoomTypeStore] Error fetching room types:', e)
        this.error = e.message || 'Failed to load room types'
      } finally {
        this.loading = false
      }
    },

    async createRoomType(data: RoomType) {
      await roomTypeService.createRoomType(data)
      await this.fetchRoomTypes()
    },

    async updateRoomType(id: string | number, data: RoomType) {
      await roomTypeService.updateRoomType(id, data)
      await this.fetchRoomTypes()
    },

    async deleteRoomType(id: number | string) {
      await roomTypeService.deleteRoomType(id)
      await this.fetchRoomTypes()
    },
  },
})

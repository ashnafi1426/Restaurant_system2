import { defineStore } from 'pinia'
import { roomTypeService } from '../services/roomtypeService'
import type { RoomType } from '../types/roomType'

export const useRoomTypeStore = defineStore('roomTypes', {
  state: () => ({
    roomTypes: [] as RoomType[],
    loading: false,
    error: null as string | null,
  }),

  actions: {
    async fetchRoomTypes(params: any = {}) {
      this.loading = true

      try {
        const res = await roomTypeService.getRoomTypes(params)
        this.roomTypes = res.data.data
      } catch (e) {
        this.error = 'Failed to load room types'
      } finally {
        this.loading = false
      }
    },

    async createRoomType(data: RoomType) {
      await roomTypeService.createRoomType(data)
      await this.fetchRoomTypes()
    },

    async updateRoomType(id: string, data: RoomType) {
      await roomTypeService.updateRoomType(id, data)
      await this.fetchRoomTypes()
    },

    async deleteRoomType(id: number) {
      await roomTypeService.deleteRoomType(id)
      await this.fetchRoomTypes()
    },
  },
})

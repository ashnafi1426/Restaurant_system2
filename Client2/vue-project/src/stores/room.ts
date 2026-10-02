import { defineStore } from 'pinia'
import { roomService } from '../services/roomService'
import type { Room } from '../types/room'

export const useRoomStore = defineStore('rooms', {
  state: () => ({
    rooms: [] as Room[],
    loading: false,
    error: null as string | null,
  }),

  actions: {
    async fetchRooms(params: Record<string, any> = {}) {
      this.loading = true
      this.error = null

      try {
        const response = await roomService.getRooms(params)
        const responseData = response.data

        if (responseData && Array.isArray(responseData.data)) {
          this.rooms = responseData.data
        } else if (Array.isArray(responseData)) {
          this.rooms = responseData
        } else {
          this.rooms = []
        }

        return this.rooms
      } catch (error: any) {
        console.error('[RoomStore] Error fetching rooms:', error)
        const statusCode = error.response?.status
        const message = error.response?.data?.message || error.message

        if (statusCode === 403) {
          this.error = 'Permission Denied: You do not have access to view rooms.'
        } else if (statusCode === 401) {
          this.error = 'Session Expired: Please log in again.'
        } else {
          this.error = `Error fetching rooms: ${message}`
        }
        this.rooms = []
        throw error
      } finally {
        this.loading = false
      }
    },

    async searchRooms(searchTerm: string, params: Record<string, any> = {}) {
      this.loading = true
      this.error = null

      try {
        const response = await roomService.searchRooms(searchTerm, params)
        const responseData = response.data

        if (responseData && Array.isArray(responseData.data)) {
          this.rooms = responseData.data
        } else if (Array.isArray(responseData)) {
          this.rooms = responseData
        } else {
          this.rooms = []
        }

        return this.rooms
      } catch (error: any) {
        console.error('[RoomStore] Error searching rooms:', error)
        const message = error.response?.data?.message || error.message
        this.error = `Error searching rooms: ${message}`
        this.rooms = []
        throw error
      } finally {
        this.loading = false
      }
    },

    async createRoom(room: Room) {
      try {
        const response = await roomService.createRoom(room)
        await this.fetchRooms()
        return response.data
      } catch (error: any) {
        console.error('[RoomStore] Error creating room:', error)
        this.error = error.response?.data?.message || 'Failed to create room'
        throw error
      }
    },

    async updateRoom(id: string, room: Room) {
      try {
        const response = await roomService.updateRoom(id, room)
        await this.fetchRooms()
        return response.data
      } catch (error: any) {
        console.error('[RoomStore] Error updating room:', error)
        this.error = error.response?.data?.message || 'Failed to update room'
        throw error
      }
    },

    async deleteRoom(id: string, force: boolean = false) {
      try {
        const response = await roomService.deleteRoom(id, force)
        await this.fetchRooms()
        this.error = null
        return response.data
      } catch (error: any) {
        console.error('[RoomStore] Error deleting room:', error)
        this.error = error.response?.data?.message || 'Failed to delete room'
        throw error
      }
    },

    async toggleStatus(id: string) {
      try {
        const response = await roomService.toggleStatus(id)
        await this.fetchRooms()
        this.error = null
        return response.data
      } catch (error: any) {
        console.error('[RoomStore] Error toggling room status:', error)
        this.error = error.response?.data?.message || 'Failed to update room status'
        throw error
      }
    },
  },
})

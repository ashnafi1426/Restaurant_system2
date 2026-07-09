import { defineStore } from 'pinia'
import { roomService } from '../services/roomService.ts'
import type { Room } from '../types/room'

export const useRoomStore = defineStore('rooms', {
  state: () => ({
    rooms: [] as Room[],
    loading: false,
    error: null as string | null,
  }),

  actions: {
    async fetchRooms(params: any = {}) {
      this.loading = true
      this.error = null
      try {
        console.log(' [ROOM STORE] Fetching rooms with params:', params)
        const response = await roomService.getRooms(params)
        console.log('📡 [ROOM STORE] Full API response:', response)
        console.log('[ROOM STORE] response.data:', response.data)
        console.log('📋 [ROOM STORE] response.data.data:', response.data.data)

        const roomsData = response.data.data || response.data
        console.log('[ROOM STORE] Rooms to assign:', roomsData)
        console.log(
          ' [ROOM STORE] Number of rooms:',
          Array.isArray(roomsData) ? roomsData.length : 'NOT AN ARRAY',
        )

        if (Array.isArray(roomsData) && roomsData.length > 0) {
          console.log('[ROOM STORE] First room:', roomsData[0])
          console.log('🆔 [ROOM STORE] First room ID:', roomsData[0].id)
        }

        this.rooms = roomsData
        console.log(' [ROOM STORE] Rooms assigned, current rooms:', this.rooms)
      } catch (error: any) {
        const statusCode = error.response?.status
        const message = error.response?.data?.message || error.message

        if (statusCode === 403) {
          this.error = ' Permission Denied: You do not have access to view rooms.'
        } else if (statusCode === 401) {
          this.error = '🔑 Session Expired: Your login has expired. Please log in again.'
        } else if (statusCode === 404) {
          this.error = ' No rooms available. Contact administrator.'
        } else {
          this.error = ` Error fetching rooms: ${message}`
        }
        console.error(' [ROOM STORE] Error fetching rooms:', error)
        this.rooms = []
        throw error
      } finally {
        this.loading = false
      }
    },

    async createRoom(room: Room) {
      try {
        await roomService.createRoom(room)
        await this.fetchRooms()
      } catch (error: any) {
        this.error = 'Failed to create room'
        console.error('Error creating room:', error)
        throw error
      }
    },

    async updateRoom(id: string, room: Room) {
      try {
        await roomService.updateRoom(id, room)
        await this.fetchRooms()
      } catch (error: any) {
        this.error = 'Failed to update room'
        console.error('Error updating room:', error)
        throw error
      }
    },

    async deleteRoom(id: string) {
      try {
        await roomService.deleteRoom(id)
        await this.fetchRooms()
      } catch (error: any) {
        this.error = 'Failed to delete room'
        console.error('Error deleting room:', error)
        throw error
      }
    },
  },
})

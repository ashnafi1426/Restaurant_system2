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
        const response = await roomService.getRooms(params)

        let responseData = response.data
        if (typeof responseData === 'string') {
          const jsonStart = responseData.indexOf('{')
          const arrayStart = responseData.indexOf('[')
          let startIdx = -1
          if (jsonStart !== -1 && (arrayStart === -1 || jsonStart < arrayStart)) {
            startIdx = jsonStart
          } else if (arrayStart !== -1) {
            startIdx = arrayStart
          }
          if (startIdx !== -1) {
            try {
              responseData = JSON.parse(responseData.slice(startIdx))
            } catch (e) {
              console.error('[RoomStore] Error parsing JSON string:', e)
            }
          }
        }

        let roomsData = responseData

        if (responseData && responseData.data && Array.isArray(responseData.data)) {
          roomsData = responseData.data
        }
        else if (Array.isArray(responseData)) {
          roomsData = responseData
        }

        this.rooms = Array.isArray(roomsData) ? roomsData : []
      } catch (error: any) {
        console.error('[RoomStore] Error fetching rooms:', error)
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
        this.rooms = []
        throw error
      } finally {
        this.loading = false
      }
    },

    async searchRooms(searchTerm: string, params: any = {}) {
      this.loading = true
      this.error = null
      try {
        const response = await roomService.searchRooms(searchTerm, params)

        let responseData = response.data
        if (typeof responseData === 'string') {
          const jsonStart = responseData.indexOf('{')
          const arrayStart = responseData.indexOf('[')
          let startIdx = -1
          if (jsonStart !== -1 && (arrayStart === -1 || jsonStart < arrayStart)) {
            startIdx = jsonStart
          } else if (arrayStart !== -1) {
            startIdx = arrayStart
          }
          if (startIdx !== -1) {
            try {
              responseData = JSON.parse(responseData.slice(startIdx))
            } catch (e) {
              console.error('[RoomStore] Error parsing search JSON string:', e)
            }
          }
        }

        const rawData = responseData?.data || responseData
        const roomsData = Array.isArray(rawData) ? rawData : (Array.isArray(rawData?.data) ? rawData.data : [])
        this.rooms = roomsData
      } catch (error: any) {
        console.error('[RoomStore] Error searching rooms:', error)
        const message = error.response?.data?.message || error.message
        this.error = ` Error searching rooms: ${message}`
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
        console.error('[RoomStore] Error creating room:', error)
        this.error = 'Failed to create room'
        throw error
      }
    },

    async updateRoom(id: string, room: Room) {
      try {
        await roomService.updateRoom(id, room)
        await this.fetchRooms()
      } catch (error: any) {
        console.error('[RoomStore] Error updating room:', error)
        this.error = 'Failed to update room'
        throw error
      }
    },

    async deleteRoom(id: string) {
      try {
        await roomService.deleteRoom(id)
        await this.fetchRooms()
      } catch (error: any) {
        console.error('[RoomStore] Error deleting room:', error)
        this.error = 'Failed to delete room'
        throw error
      }
    },
  },
})

import { defineStore } from 'pinia'
import { ref } from 'vue'
import { roomService } from '@/services/roomService'
import type { Room } from '@/types/room'
import type { ReservationFormData } from '@/types/reservation'

export interface GuestPublicPagination {
  current_page: number
  last_page: number
  per_page: number
  total: number
}

const DEFAULT_PAGINATION: GuestPublicPagination = {
  current_page: 1,
  last_page: 1,
  per_page: 9,
  total: 0,
}

export const useGuestPublicStore = defineStore('guestPublic', () => {
  const rooms = ref<Room[]>([])
  const selectedRoom = ref<Room | null>(null)
  const loading = ref(false)
  const error = ref('')
  const pagination = ref<GuestPublicPagination>({ ...DEFAULT_PAGINATION })

  const fetchPublicRooms = async (params: any = {}) => {
    loading.value = true
    error.value = ''

    try {
      const response = await roomService.getPublicRooms(params)
      let roomsData = response.data

      if (response.data && response.data.data && Array.isArray(response.data.data)) {
        roomsData = response.data.data
        if (response.data.meta) {
          pagination.value = {
            current_page: response.data.meta.current_page || 1,
            last_page: response.data.meta.last_page || 1,
            per_page: response.data.meta.per_page || 9,
            total: response.data.meta.total || roomsData.length,
          }
        }
      } else if (Array.isArray(response.data)) {
        roomsData = response.data
        pagination.value.total = roomsData.length
      } else if (response.data?.data) {
        roomsData = response.data.data
        pagination.value.total = roomsData.length
      }

      rooms.value = roomsData || []
      return rooms.value
    } catch (err: any) {
      console.error('[guestPublicStore] Failed to load public rooms:', err)
      error.value = err.response?.data?.message ?? 'Failed to load rooms.'
      rooms.value = []
      throw err
    } finally {
      loading.value = false
    }
  }

  const fetchPublicRoom = async (id: string) => {
    loading.value = true
    error.value = ''

    try {
      const response = await roomService.getPublicRoom(id)
      selectedRoom.value = response.data || response
      return selectedRoom.value
    } catch (err: any) {
      console.error('[guestPublicStore] Failed to load public room:', err)
      error.value = err.response?.data?.message ?? 'Room not found.'
      throw err
    } finally {
      loading.value = false
    }
  }

  const createPublicReservation = async (data: ReservationFormData) => {
    loading.value = true
    error.value = ''

    try {
      const response = await roomService.publicApi.post('/reservations', data)
      return response.data
    } catch (err: any) {
      console.error('[guestPublicStore] Failed to create public reservation:', err)
      error.value = err.response?.data?.message ?? 'Failed to create reservation.'
      throw err
    } finally {
      loading.value = false
    }
  }

  return {
    rooms,
    selectedRoom,
    loading,
    error,
    pagination,

    fetchPublicRooms,
    fetchPublicRoom,
    createPublicReservation,
  }
})

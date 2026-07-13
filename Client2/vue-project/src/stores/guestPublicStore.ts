import { defineStore } from 'pinia'
import { ref } from 'vue'
import { roomService } from '../services/roomService'
import reservationService from '../services/reservationService'
import type { Room } from '../types/room'
import type { ReservationFormData } from '../types/reservation'

/**
 * Guest Public Store - For public (unauthenticated) guest operations
 * Used for:
 * - Viewing available rooms
 * - Making reservations without login
 */
export const useGuestPublicStore = defineStore('guestPublic', () => {
  // Rooms listing
  const rooms = ref<Room[]>([])
  const selectedRoom = ref<Room | null>(null)
  const loading = ref(false)
  const error = ref('')
  const pagination = ref({
    current_page: 1,
    last_page: 1,
    per_page: 9,
    total: 0,
  })

  /**
   * Fetch all public rooms
   */
  const fetchPublicRooms = async (params: any = {}) => {
    loading.value = true
    error.value = ''

    try {
      console.log('[GUEST PUBLIC STORE] Fetching public rooms...')
      const response = await roomService.getPublicRooms(params)

      console.log('[GUEST PUBLIC STORE] API Response:', response)

      // Handle paginated response
      let roomsData = response.data

      if (response.data && response.data.data && Array.isArray(response.data.data)) {
        console.log('[GUEST PUBLIC STORE] Detected paginated response')
        roomsData = response.data.data
        // Set pagination metadata if available
        if (response.data.meta) {
          pagination.value = {
            current_page: response.data.meta.current_page || 1,
            last_page: response.data.meta.last_page || 1,
            per_page: response.data.meta.per_page || 9,
            total: response.data.meta.total || roomsData.length,
          }
        }
      } else if (Array.isArray(response.data)) {
        console.log('[GUEST PUBLIC STORE] Detected direct array response')
        roomsData = response.data
        pagination.value.total = roomsData.length
      } else if (response.data?.data) {
        roomsData = response.data.data
        pagination.value.total = roomsData.length
      }

      rooms.value = roomsData || []
      console.log('[GUEST PUBLIC STORE] Rooms loaded:', rooms.value.length)
      return rooms.value
    } catch (err: any) {
      console.error('[GUEST PUBLIC STORE] Error fetching rooms:', err)
      error.value = err.response?.data?.message ?? 'Failed to load rooms.'
      rooms.value = []
      throw err
    } finally {
      loading.value = false
    }
  }

  /**
   * Fetch single public room
   */
  const fetchPublicRoom = async (id: string) => {
    loading.value = true
    error.value = ''

    try {
      console.log('[GUEST PUBLIC STORE] Fetching room:', id)
      const response = await roomService.getPublicRoom(id)

      console.log('[GUEST PUBLIC STORE] Room response:', response)
      selectedRoom.value = response.data || response
      return selectedRoom.value
    } catch (err: any) {
      console.error('[GUEST PUBLIC STORE] Error fetching room:', err)
      error.value = err.response?.data?.message ?? 'Room not found.'
      throw err
    } finally {
      loading.value = false
    }
  }

  /**
   * Create reservation (public - no auth required)
   */
  const createPublicReservation = async (data: ReservationFormData) => {
    loading.value = true
    error.value = ''

    try {
      console.log('[GUEST PUBLIC STORE] Creating public reservation...')
      console.log('[GUEST PUBLIC STORE] Reservation data:', data)

      // Use the public endpoint to create reservation
      const response = await roomService.publicApi.post('/reservations', data)

      console.log('[GUEST PUBLIC STORE] Reservation created:', response.data)
      return response.data
    } catch (err: any) {
      console.error('[GUEST PUBLIC STORE] Error creating reservation:', err)
      error.value = err.response?.data?.message ?? 'Failed to create reservation.'
      throw err
    } finally {
      loading.value = false
    }
  }

  return {
    // State
    rooms,
    selectedRoom,
    loading,
    error,
    pagination,

    // Actions
    fetchPublicRooms,
    fetchPublicRoom,
    createPublicReservation,
  }
})

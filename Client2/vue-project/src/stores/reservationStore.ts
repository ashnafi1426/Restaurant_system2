import { defineStore } from 'pinia'
import { ref } from 'vue'
import reservationService from '../services/reservationService'
import type {
  Reservation,
  ReservationFilter,
  ReservationFormData,
  Pagination,
} from '../types/reservation'

export const useReservationStore = defineStore('reservation', () => {
  const reservations = ref<Reservation[]>([])
  const reservation = ref<Reservation | null>(null)
  const loading = ref(false)
  const pagination = ref<Pagination>({
    current_page: 1,
    last_page: 1,
    per_page: 10,
    total: 0,
    from: 0,
    to: 0,
  })

  const fetchReservations = async (filters?: ReservationFilter) => {
    loading.value = true

    try {
      const response = await reservationService.getReservations(filters)

      reservations.value = response.data || response

      const meta = response.meta || {
        current_page: 1,
        total: Array.isArray(response.data) ? response.data.length : 0,
        per_page: 10,
        last_page: 1,
      }

      pagination.value = {
        ...meta,
        total:
          typeof meta.total === 'number'
            ? meta.total
            : Array.isArray(meta.total)
              ? meta.total[0]
              : 0,
      }
    } catch (error: any) {
      console.error('[reservationStore] Failed to fetch reservations:', error)
      reservations.value = []
    } finally {
      loading.value = false
    }
  }

  const fetchReservation = async (id: string) => {
    loading.value = true

    try {
      const response = await reservationService.getReservation(id)
      reservation.value = response.data
    } finally {
      loading.value = false
    }
  }

  const createReservation = async (data: ReservationFormData) => {
    loading.value = true

    try {
      return await reservationService.createReservation(data)
    } finally {
      loading.value = false
    }
  }

  const updateReservation = async (id: string, data: ReservationFormData) => {
    loading.value = true

    try {
      return await reservationService.updateReservation(id, data)
    } finally {
      loading.value = false
    }
  }

  const deleteReservation = async (id: string) => {
    loading.value = true

    try {
      return await reservationService.deleteReservation(id)
    } finally {
      loading.value = false
    }
  }

  const confirmReservation = async (id: string) => {
    try {
      const index = reservations.value.findIndex((r) => r.id === id)
      if (index === -1) throw new Error('Reservation not found')

      reservations.value[index].status = 'confirmed'

      const response = await reservationService.confirmReservation(id)
      const reservationData = response?.data || response

      if (reservationData) {
        reservations.value[index] = reservationData
      }

      return response
    } catch (error: any) {
      console.error('[reservationStore] Failed to confirm reservation:', error)
      const index = reservations.value.findIndex((r) => r.id === id)
      if (index !== -1) {
        reservations.value[index].status = 'pending'
      }

      throw error
    }
  }

  const checkInReservation = async (id: string) => {
    try {
      const index = reservations.value.findIndex((r) => r.id === id)
      if (index === -1) throw new Error('Reservation not found')

      reservations.value[index].status = 'checked_in'

      const response = await reservationService.checkInReservation(id)
      const reservationData = response?.data || response

      if (reservationData) {
        reservations.value[index] = reservationData
      }

      return response
    } catch (error: any) {
      console.error('[reservationStore] Failed to check in reservation:', error)
      const index = reservations.value.findIndex((r) => r.id === id)
      if (index !== -1) {
        reservations.value[index].status = 'confirmed'
      }

      throw error
    }
  }

  const checkOutReservation = async (id: string) => {
    try {
      const index = reservations.value.findIndex((r) => r.id === id)
      if (index === -1) throw new Error('Reservation not found')

      reservations.value[index].status = 'checked_out'

      const response = await reservationService.checkOutReservation(id)
      const reservationData = response?.data || response

      if (reservationData) {
        reservations.value[index] = reservationData
      }

      return response
    } catch (error: any) {
      console.error('[reservationStore] Failed to check out reservation:', error)
      const index = reservations.value.findIndex((r) => r.id === id)
      if (index !== -1) {
        reservations.value[index].status = 'checked_in'
      }

      throw error
    }
  }

  const cancelReservationAction = async (id: string) => {
    try {
      const index = reservations.value.findIndex((r) => r.id === id)
      if (index === -1) throw new Error('Reservation not found')

      reservations.value[index].status = 'cancelled'

      const response = await reservationService.cancelReservation(id)
      const reservationData = response?.data || response

      if (reservationData) {
        reservations.value[index] = reservationData
      }

      return response
    } catch (error: any) {
      console.error('[reservationStore] Failed to cancel reservation:', error)
      const index = reservations.value.findIndex((r) => r.id === id)
      if (index !== -1) {
        reservations.value[index].status = 'confirmed'
      }

      throw error
    }
  }

  const checkAvailability = async (params: {
    check_in_date: string
    check_out_date: string
    room_id?: string
    room_type_id?: string
    capacity?: number
  }) => {
    return await reservationService.checkAvailability(params)
  }

  return {
    reservations,
    reservation,
    loading,
    pagination,
    fetchReservations,
    fetchReservation,
    createReservation,
    updateReservation,
    deleteReservation,
    confirmReservation,
    checkInReservation,
    checkOutReservation,
    cancelReservation: cancelReservationAction,
    cancelReservationAction,
    checkAvailability,
  }
})

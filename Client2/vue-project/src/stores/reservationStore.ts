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
      console.log(' [RESERVATION STORE] fetchReservations called with filters:', filters)
      const response = await reservationService.getReservations(filters)

      console.log(' [RESERVATION STORE] API Response received:', response)
      console.log(' [RESERVATION STORE] Response keys:', Object.keys(response))
      console.log(' [RESERVATION STORE] Response.data:', response.data)
      console.log(' [RESERVATION STORE] Response.meta:', response.meta)

      reservations.value = response.data || response

      // Ensure pagination is properly parsed and total is always a number
      const meta = response.meta || {
        current_page: 1,
        total: Array.isArray(response.data) ? response.data.length : 0,
        per_page: 10,
        last_page: 1,
      }

      // Ensure total is a number, not an array
      pagination.value = {
        ...meta,
        total:
          typeof meta.total === 'number'
            ? meta.total
            : Array.isArray(meta.total)
              ? meta.total[0]
              : 0,
      }

      console.log(' [RESERVATION STORE] Store updated:')
      console.log('   - reservations.length:', reservations.value.length)
      console.log('   - pagination.total:', pagination.value.total)
      console.log('   - pagination:', pagination.value)
    } catch (error: any) {
      console.error(' [RESERVATION STORE] Error fetching reservations:', error)
      console.error(' [RESERVATION STORE] Error message:', error.message)
      console.error(' [RESERVATION STORE] Error response:', error.response?.data)
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
      console.log(' [STORE] Confirming reservation:', id)

      // Find the reservation index
      const index = reservations.value.findIndex((r) => r.id === id)
      if (index === -1) throw new Error('Reservation not found')

      // Optimistic update - update immediately
      reservations.value[index].status = 'confirmed'

      const response = await reservationService.confirmReservation(id)
      console.log(' [STORE] Confirm response:', response)
      console.log(' [STORE] Response type:', typeof response)
      console.log(' [STORE] Response.data:', response?.data)

      // Backend returns { message, data: ReservationResource }
      // axios wraps it in response.data, so we get { message, data }
      const reservationData = response?.data || response

      if (reservationData) {
        reservations.value[index] = reservationData
        console.log(' [STORE] Reservation updated:', reservationData)
      }

      return response
    } catch (error: any) {
      console.error(' [STORE] Confirm error:', error.message)
      console.error(' [STORE] Confirm error response:', error.response?.data)

      // Rollback on error - revert status
      const index = reservations.value.findIndex((r) => r.id === id)
      if (index !== -1) {
        reservations.value[index].status = 'pending'
      }

      throw error
    }
  }

  const checkInReservation = async (id: string) => {
    try {
      console.log('🔓 [STORE] Checking in reservation:', id)

      // Find the reservation index
      const index = reservations.value.findIndex((r) => r.id === id)
      if (index === -1) throw new Error('Reservation not found')

      // Optimistic update
      reservations.value[index].status = 'checked_in'

      const response = await reservationService.checkInReservation(id)
      console.log('🔓 [STORE] Check-in response:', response)

      const reservationData = response?.data || response

      if (reservationData) {
        reservations.value[index] = reservationData
      }

      return response
    } catch (error: any) {
      console.error(' [STORE] Check-in error:', error.message)
      console.error(' [STORE] Check-in error response:', error.response?.data)

      // Rollback
      const index = reservations.value.findIndex((r) => r.id === id)
      if (index !== -1) {
        reservations.value[index].status = 'confirmed'
      }

      throw error
    }
  }

  const checkOutReservation = async (id: string) => {
    try {
      console.log('🚪 [STORE] Checking out reservation:', id)

      // Find the reservation index
      const index = reservations.value.findIndex((r) => r.id === id)
      if (index === -1) throw new Error('Reservation not found')

      // Optimistic update
      reservations.value[index].status = 'checked_out'

      const response = await reservationService.checkOutReservation(id)
      console.log('🚪 [STORE] Check-out response:', response)

      const reservationData = response?.data || response

      if (reservationData) {
        reservations.value[index] = reservationData
      }

      return response
    } catch (error: any) {
      console.error(' [STORE] Check-out error:', error.message)
      console.error(' [STORE] Check-out error response:', error.response?.data)

      // Rollback
      const index = reservations.value.findIndex((r) => r.id === id)
      if (index !== -1) {
        reservations.value[index].status = 'checked_in'
      }

      throw error
    }
  }

  const cancelReservationAction = async (id: string) => {
    try {
      console.log(' [STORE] Cancelling reservation:', id)

      // Find the reservation index
      const index = reservations.value.findIndex((r) => r.id === id)
      if (index === -1) throw new Error('Reservation not found')

      // Optimistic update
      reservations.value[index].status = 'cancelled'

      const response = await reservationService.cancelReservation(id)
      console.log(' [STORE] Cancel response:', response)

      const reservationData = response?.data || response

      if (reservationData) {
        reservations.value[index] = reservationData
      }

      return response
    } catch (error: any) {
      console.error(' [STORE] Cancel error:', error.message)
      console.error(' [STORE] Cancel error response:', error.response?.data)

      // Rollback
      const index = reservations.value.findIndex((r) => r.id === id)
      if (index !== -1) {
        reservations.value[index].status = 'confirmed'
      }

      throw error
    }
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
    cancelReservationAction,
  }
})

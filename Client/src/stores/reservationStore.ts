import { defineStore } from 'pinia'
import { ref } from 'vue'
import reservationService from '@/services/reservationService'
import type {
  Reservation,
  ReservationFilter,
  ReservationFormData,
  Pagination,
} from '@/types/reservation'

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
      return response.data
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

  /**
   * Helper to execute status transitions with optimistic update and rollback on failure.
   */
  const executeStatusTransition = async (
    id: string,
    targetStatus: Reservation['status'],
    serviceAction: (id: string) => Promise<any>,
  ) => {
    const index = reservations.value.findIndex((r) => r.id === id)
    if (index === -1) throw new Error('Reservation not found')

    const previousStatus = reservations.value[index].status
    reservations.value[index].status = targetStatus

    try {
      const response = await serviceAction(id)
      const reservationData = response?.data || response
      if (reservationData) {
        reservations.value[index] = reservationData
      }
      return response
    } catch (error: any) {
      console.error(
        `[reservationStore] Failed to change reservation status to ${targetStatus}:`,
        error,
      )
      reservations.value[index].status = previousStatus
      throw error
    }
  }

  const confirmReservation = (id: string) =>
    executeStatusTransition(id, 'confirmed', reservationService.confirmReservation)

  const checkInReservation = (id: string) =>
    executeStatusTransition(id, 'checked_in', reservationService.checkInReservation)

  const checkOutReservation = (id: string) =>
    executeStatusTransition(id, 'checked_out', reservationService.checkOutReservation)

  const cancelReservation = (id: string) =>
    executeStatusTransition(id, 'cancelled', reservationService.cancelReservation)

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
    cancelReservation,
    cancelReservationAction: cancelReservation,
    checkAvailability,
  }
})

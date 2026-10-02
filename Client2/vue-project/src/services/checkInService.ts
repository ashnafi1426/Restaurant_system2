import api from '../api/auth'

export interface CheckInGuest {
  id?: string
  first_name?: string
  last_name?: string
  full_name?: string
  phone?: string
  email?: string
}

export interface CheckInRoom {
  id?: string
  room_number?: string
  status?: string
  room_type?: {
    id?: string
    name?: string
  } | null
}

export interface CheckInReservation {
  id?: string
  reservation_number?: string
  booking_reference?: string
  status?: string
  check_in_date?: string
  check_out_date?: string
}

export interface CheckInRecord {
  id: string
  reservation_id: string
  guest_id?: string
  room_id?: string
  checked_in_at: string
  expected_check_out_at?: string
  checked_out_at?: string | null
  guest?: CheckInGuest
  room?: CheckInRoom
  reservation?: CheckInReservation
  created_at?: string
}

export interface CheckInStatistics {
  total_check_ins: number
  today_check_ins: number
  active_guests: number
  expected_today: number
}

export interface CheckInFilterParams {
  search?: string
  guest_id?: string
  room_id?: string
  status?: string
  page?: number
  per_page?: number
}

export default {
  getAll(params?: CheckInFilterParams) {
    const queryParams: Record<string, string | number> = {}
    if (params?.search) {
      queryParams.search = params.search
    }
    if (params?.guest_id) queryParams.guest_id = params.guest_id
    if (params?.room_id) queryParams.room_id = params.room_id
    if (params?.status) queryParams.status = params.status
    if (params?.page) queryParams.page = params.page
    if (params?.per_page) queryParams.per_page = params.per_page

    return api
      .get('/check-ins', { params: queryParams })
      .catch((error) => {
        console.error('[checkInService] Failed to fetch check-ins:', error)
        throw error
      })
  },

  getStatistics() {
    return api
      .get('/check-ins/statistics')
      .catch((error) => {
        console.error('[checkInService] Failed to fetch check-in statistics:', error)
        throw error
      })
  },

  getById(id: string) {
    return api.get(`/check-ins/${id}`)
  },

  checkIn(reservation_id: string) {
    return api
      .post('/check-ins', { reservation_id })
      .catch((error) => {
        console.error('[checkInService] Check-in failed:', error)
        let errorMsg = error.response?.data?.message || error.message || 'Check-in failed'

        if (error.response?.data?.errors) {
          const errors = error.response.data.errors
          const errorList = Object.entries(errors)
            .map(([key, msgs]) => `${key}: ${Array.isArray(msgs) ? msgs.join(', ') : msgs}`)
            .join(' | ')
          errorMsg = `${errorMsg}. Validation errors: ${errorList}`
        }

        throw new Error(errorMsg)
      })
  },

  checkOut(id: string) {
    return api.post(`/check-ins/${id}/checkout`)
  },

  delete(id: string) {
    return api.delete(`/check-ins/${id}`)
  },
}

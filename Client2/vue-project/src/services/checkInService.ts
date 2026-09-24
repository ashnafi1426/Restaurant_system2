import api from '../api/auth'

export default {
  getAll(params?: any) {
    const queryParams: any = {}
    if (params?.search) {
      queryParams.guest = params.search
      queryParams.room = params.search
      queryParams.reservation = params.search
    }
    if (params?.guest_id) queryParams.guest = params.guest_id
    if (params?.room_id) queryParams.room = params.room_id
    if (params?.page) queryParams.page = params.page
    if (params?.per_page) queryParams.per_page = params.per_page

    return api
      .get('/check-ins', {
        params: queryParams,
      })
      .then((response) => {
        return response
      })
      .catch((error) => {
        console.error('[checkInService] Failed to fetch check-ins:', error)
        throw error
      })
  },

  getStatistics() {
    return api
      .get('/check-ins/statistics')
      .then((response) => {
        return response
      })
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
      .post('/check-ins', {
        reservation_id,
      })
      .then((response) => {
        return response
      })
      .catch((error) => {
        console.error('[checkInService] Check-in failed:', error)
        let errorMsg = error.response?.data?.message || error.message || 'Check-in failed'

        if (error.response?.data?.errors) {
          const errors = error.response.data.errors
          const errorList = Object.entries(errors)
            .map(([key, msgs]: any) => `${key}: ${Array.isArray(msgs) ? msgs.join(', ') : msgs}`)
            .join(' | ')
          errorMsg = `${errorMsg}. Validation errors: ${errorList}`
        }

        const customError = new Error(errorMsg)
        throw customError
      })
  },

  checkOut(id: string) {
    return api.post(`/check-ins/${id}/checkout`)
  },

  delete(id: string) {
    return api.delete(`/check-ins/${id}`)
  },
}

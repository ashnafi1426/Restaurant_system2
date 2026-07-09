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

    console.log(' [SERVICE] getAll called with params:', params)
    console.log(' [SERVICE] Transformed query params:', queryParams)
    console.log(' [SERVICE] Request URL: /check-ins')
    console.log(
      ' [SERVICE] Full URL:',
      `${api.defaults.baseURL}/check-ins?${new URLSearchParams(queryParams).toString()}`,
    )

    return api
      .get('/check-ins', {
        params: queryParams,
      })
      .then((response) => {
        console.log(' [SERVICE] API request successful')
        console.log(' [SERVICE] Response status:', response.status)
        console.log(' [SERVICE] Response headers:', response.headers)
        return response
      })
      .catch((error) => {
        console.error(' [SERVICE] API request failed')
        console.error(' [SERVICE] Error status:', error.response?.status)
        console.error(' [SERVICE] Error data:', error.response?.data)
        console.error(' [SERVICE] Error message:', error.message)
        throw error
      })
  },

  getStatistics() {
    console.log(' [SERVICE] getStatistics called')
    return api
      .get('/check-ins/statistics')
      .then((response) => {
        console.log(' [SERVICE] Statistics loaded:', response.data)
        return response
      })
      .catch((error) => {
        console.error(' [SERVICE] Statistics error:', error.message)
        throw error
      })
  },

  getById(id: string) {
    console.log(' [SERVICE] getById called with id:', id)
    return api.get(`/check-ins/${id}`)
  },

  checkIn(reservation_id: string) {
    console.log(' [SERVICE] checkIn called with reservation_id:', reservation_id)
    console.log(' [SERVICE] Payload being sent:', { reservation_id })

    return api
      .post('/check-ins', {
        reservation_id,
      })
      .then((response) => {
        console.log(' [SERVICE] Check-in successful')
        console.log(' [SERVICE] Response status:', response.status)
        console.log(' [SERVICE] Response data:', response.data)
        return response
      })
      .catch((error) => {
        console.error(' [SERVICE] Check-in failed')
        console.error(' [SERVICE] Error status:', error.response?.status)
        console.error(' [SERVICE] Error data:', error.response?.data)
        console.error(' [SERVICE] Error message:', error.response?.data?.message || error.message)
        console.error(' [SERVICE] Full error:', error)

        // Throw error with better message
        const errorMsg = error.response?.data?.message || error.message || 'Check-in failed'
        const customError = new Error(errorMsg)
        throw customError
      })
  },

  checkOut(id: string) {
    console.log(' [SERVICE] checkOut called with id:', id)
    return api.post(`/check-ins/${id}/checkout`)
  },

  delete(id: string) {
    console.log(' [SERVICE] delete called with id:', id)
    return api.delete(`/check-ins/${id}`)
  },
}

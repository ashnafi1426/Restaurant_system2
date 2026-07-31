import api from '../api/auth'
import type { Reservation, ReservationFormData, ReservationFilter } from '../types/reservation'

export default {
  async getReservations(filters?: ReservationFilter) {
    const response = await api.get('/reservations', {
      params: {
        ...filters,
        include: 'guest,room', // Include guest and room relationships
      },
    })
    return response.data
  },

  async getReservation(id: string) {
    const response = await api.get(`/reservations/${id}`, {
      params: {
        include: 'guest,room', // Include guest and room relationships
      },
    })
    return response.data
  },

  async createReservation(data: ReservationFormData) {
    const response = await api.post('/reservations', data)
    return response.data
  },

  async updateReservation(id: string, data: ReservationFormData) {
    const response = await api.put(`/reservations/${id}`, data)
    return response.data
  },

  async deleteReservation(id: string) {
    const response = await api.delete(`/reservations/${id}`)
    return response.data
  },

  async confirmReservation(id: string) {
    const response = await api.post(`/admin-reservations/${id}/confirm`)
    console.log(' [SERVICE] Confirm response structure:', response)
    console.log(' [SERVICE] Response.data:', response.data)
    // Backend returns { message, data } structure
    return response.data
  },

  async checkInReservation(id: string) {
    const response = await api.post(`/admin-reservations/${id}/check-in`)
    console.log(' [SERVICE] Check-in response structure:', response)
    return response.data
  },

  async checkOutReservation(id: string) {
    const response = await api.post(`/admin-reservations/${id}/check-out`)
    console.log(' [SERVICE] Check-out response structure:', response)
    return response.data
  },

  async cancelReservation(id: string) {
    const response = await api.post(`/admin-reservations/${id}/cancel`)
    console.log(' [SERVICE] Cancel response structure:', response)
    return response.data
  },
}

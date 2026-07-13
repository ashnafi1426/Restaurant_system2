import api from '../api/auth'
import type { Reservation, ReservationFormData, ReservationFilter } from '../types/reservation'

export default {
  async getReservations(filters?: ReservationFilter) {
    const response = await api.get('/reservations', {
      params: filters,
    })
    return response.data
  },

  async getReservation(id: string) {
    const response = await api.get(`/reservations/${id}`)
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
    return response.data
  },

  async checkInReservation(id: string) {
    const response = await api.post(`/admin-reservations/${id}/check-in`)
    return response.data
  },

  async checkOutReservation(id: string) {
    const response = await api.post(`/admin-reservations/${id}/check-out`)
    return response.data
  },

  async cancelReservation(id: string) {
    const response = await api.post(`/admin-reservations/${id}/cancel`)
    return response.data
  },
}

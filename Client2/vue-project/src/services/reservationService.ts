import api from '../api/auth'
import type { Reservation, ReservationFormData, ReservationFilter } from '../types/reservation'

export default {
  async getReservations(filters?: ReservationFilter) {
    const cleanParams: Record<string, any> = {}
    if (filters) {
      Object.entries(filters).forEach(([key, value]) => {
        if (value !== '' && value !== null && value !== undefined) {
          cleanParams[key] = value
        }
      })
    }
    const response = await api.get('/reservations', {
      params: {
        ...cleanParams,
        include: 'guest,room',
      },
    })
    return response.data
  },

  async getReservation(id: string) {
    const response = await api.get(`/reservations/${id}`, {
      params: {
        include: 'guest,room',
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
    const response = await api.delete(`/admin-reservations/${id}`)
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

  async checkAvailability(params: {
    check_in_date: string
    check_out_date: string
    room_id?: string
    room_type_id?: string
    capacity?: number
  }) {
    const response = await api.get('/reservations/availability', { params })
    return response.data
  },
}

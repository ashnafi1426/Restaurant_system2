import api from '../api/auth'
import { publicAxios } from './axios'
import type { Room } from '../types/room'

function getGuestHotelHeaders() {
  try {
    const hotelData = localStorage.getItem('guest_current_hotel')
    if (hotelData) {
      const hotel = JSON.parse(hotelData)
      if (hotel && hotel.id) {
        return { 'X-Hotel-ID': String(hotel.id) }
      }
    }
  } catch (err: any) {
    console.error('[RoomService] Error parsing guest_current_hotel:', err)
  }
  return {}
}

export const roomService = {
  getRooms(params: any = {}) {
    const token = localStorage.getItem('token')
    if (token) {
      return api.get('/rooms', { params })
    }
    return publicAxios.get('/rooms', { params, headers: getGuestHotelHeaders() })
  },

  getAllRooms(hotelId?: string) {
    const token = localStorage.getItem('token')
    const params: any = { per_page: 1000 }
    if (hotelId) params.hotel_id = hotelId
    if (token) {
      return api.get('/rooms', { params })
    }
    return publicAxios.get('/rooms', { params, headers: getGuestHotelHeaders() })
  },

  searchRooms(searchTerm: string, params: any = {}) {
    const token = localStorage.getItem('token')
    if (token) {
      return api.get('/rooms', { params: { ...params, search: searchTerm } })
    }
    return publicAxios.get('/rooms', { params: { ...params, search: searchTerm }, headers: getGuestHotelHeaders() })
  },

  getRoom(id: string) {
    const token = localStorage.getItem('token')
    if (token) {
      return api.get(`/rooms/${String(id)}`)
    }
    return publicAxios.get(`/rooms/${String(id)}`, { headers: getGuestHotelHeaders() })
  },

  createRoom(room: Room) {
    return api.post('/rooms', room)
  },

  updateRoom(id: string, room: Room) {
    return api.put(`/rooms/${String(id)}`, room)
  },

  deleteRoom(id: string, force: boolean = false) {
    const url = `/rooms/${encodeURIComponent(String(id))}${force ? '?force=1' : ''}`
    return api.delete(url, {
      params: force ? { force: 1 } : {},
      data: force ? { force: true } : {},
    })
  },

  toggleStatus(id: string) {
    return api.patch(`/rooms/${String(id)}/toggle-status`)
  },
}

export default roomService

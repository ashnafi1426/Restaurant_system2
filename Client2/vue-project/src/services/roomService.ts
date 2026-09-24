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
    return publicAxios.get('/rooms', { params, headers: getGuestHotelHeaders() })
  },

  getAllRooms() {
    return publicAxios.get('/rooms', { params: { per_page: 1000 }, headers: getGuestHotelHeaders() })
  },

  searchRooms(searchTerm: string, params: any = {}) {
    return publicAxios.get('/rooms', { params: { ...params, search: searchTerm }, headers: getGuestHotelHeaders() })
  },

  getRoom(id: string) {
    return publicAxios.get(`/rooms/${String(id)}`, { headers: getGuestHotelHeaders() })
  },

  createRoom(room: Room) {
    return api.post('/rooms', room)
  },

  updateRoom(id: string, room: Room) {
    return api.put(`/rooms/${String(id)}`, room)
  },

  deleteRoom(id: string) {
    return api.delete(`/rooms/${String(id)}`)
  },
}

export default roomService

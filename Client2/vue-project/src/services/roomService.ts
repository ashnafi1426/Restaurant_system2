import api from '../api/auth'
import { publicAxios } from './axios'
import type { Room } from '../types/room'

export const roomService = {
  getRooms(params: any = {}) {
    return publicAxios.get('/rooms', { params })
  },

  getAllRooms() {
    return publicAxios.get('/rooms', { params: { per_page: 1000 } })
  },

  searchRooms(searchTerm: string, params: any = {}) {
    return publicAxios.get('/rooms', { params: { ...params, search: searchTerm } })
  },

  getRoom(id: string) {
    return publicAxios.get(`/rooms/${String(id)}`)
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

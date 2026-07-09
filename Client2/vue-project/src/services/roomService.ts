import api from '../api/auth'
import type { Room } from '../types/room'

export const roomService = {
  getRooms(params: any = {}) {
    return api.get('/rooms', { params })
  },

  getRoom(id: string) {
    return api.get(`/rooms/${String(id)}`)
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

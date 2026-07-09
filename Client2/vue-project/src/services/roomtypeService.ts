import api from '../api/auth'
import type { RoomType } from '../types/roomType'

export const roomTypeService = {
  getRoomTypes(params: any = {}) {
    return api.get('/room-types', { params })
  },
  getRoomType(id: number) {
    return api.get(`/room-types/${id}`)
  },
  createRoomType(data: RoomType) {
    return api.post('/room-types', data)
  },
  updateRoomType(id: string, data: RoomType) {
    return api.put(`/room-types/${id}`, data)
  },
  deleteRoomType(id: number) {
    return api.delete(`/room-types/${id}`)
  },
}

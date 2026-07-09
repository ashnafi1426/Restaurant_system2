import type { RoomType } from '../types/roomType'

export function toRoomTypeApi(data: RoomType) {
  return {
    name: data.name,
    description: data.description,
    base_price_per_night: data.base_price_per_night,
    capacity: data.capacity,
    amenities: data.amenities,
    is_active: data.is_active,
  }
}

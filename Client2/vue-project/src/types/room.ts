export type RoomStatus = 'available' | 'occupied' | 'reserved' | 'maintenance'

export interface Room {
  id: string
  room_number: string
  room_type_id: number
  room_type?: {
    id?: string
    name: string
    capacity: number
    base_price_per_night: number
  }
  floor: number
  description?: string
  status: RoomStatus
  is_active?: boolean
  status_label?: string
  created_at?: string
  updated_at?: string
}

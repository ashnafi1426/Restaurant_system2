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
  qr_token?: string
  qr_image_path?: string
  qr_code_url?: string
  qr_generated_at?: string
  created_at?: string
  updated_at?: string
}

export type RoomStatus = 'available' | 'occupied' | 'reserved' | 'maintenance'
export interface Room {
  id: string
  hotel_id?: string | number
  room_number: string
  room_type_id: number | string
  capacity?: number
  price_per_night?: number | string
  room_type?: {
    id?: string
    name: string
    capacity: number
    base_price_per_night: number
  }
  floor_id?: string
  floor?: number
  floor_name?: string
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

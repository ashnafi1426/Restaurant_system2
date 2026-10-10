export type RoomStatus = 'available' | 'occupied' | 'reserved' | 'maintenance'

export interface RoomType {
  id?: string
  name: string
  capacity: number
  max_occupancy?: number
  base_price_per_night: number
}

export interface Room {
  id: string
  hotel_id?: string | number
  room_number: string
  room_type_id: number | string
  capacity?: number
  price_per_night?: number | string
  room_type?: RoomType
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
  images?: string[] | Array<{ url: string; alt?: string }>
  amenities?: string[] | Array<{ id: string | number; name: string }>
  created_at?: string
  updated_at?: string
}

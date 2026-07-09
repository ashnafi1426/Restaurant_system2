export interface RoomType {
  id?: string
  name: string
  description: string
  base_price_per_night: number
  capacity: number
  amenities: string[]
  is_active: boolean
  status?: string
  created_at?: string
  updated_at?: string
}

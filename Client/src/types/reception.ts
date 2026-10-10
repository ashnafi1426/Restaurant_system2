export interface ReceptionStatistics {
  today_check_ins: number
  active_guests: number
  available_rooms: number
  pending_reservations: number
  confirmed_reservations: number
}

export interface RoomType {
  id: string
  name: string
  description: string
  base_price_per_night: string | number
  capacity: number
  amenities: any
  is_active: boolean
  created_at: string
  updated_at: string
}

export interface RoomInMatrix {
  id: string
  room_number: string
  floor: number
  status: 'available' | 'reserved' | 'occupied' | 'cleaning' | 'maintenance'
  room_type_id: string
  room_type: RoomType
}

export interface GuestInfo {
  id: string
  first_name: string
  last_name: string
  email: string
  phone: string
  address?: string
  nationality?: string
  passport_number?: string
  date_of_birth?: string
  preferences?: any
  created_at: string
  updated_at: string
  deleted_at?: string
}

export interface ReservationInfo {
  id: string
  booking_reference: string
  guest_id: string
  room_id: string
  check_in_date: string
  check_out_date: string
  number_of_guests: number
  total_nights: number
  status: 'pending' | 'confirmed' | 'checked_in' | 'checked_out' | 'cancelled'
  special_requests?: string
  cancelled_at?: string
  created_by: string
  created_at: string
  updated_at: string
  guest?: GuestInfo
  room?: any
}

export interface CheckInInfo {
  id: string
  reservation_id: string
  guest_id: string
  room_id: string
  checked_in_at: string
  expected_check_out_at: string
  checked_out_at?: string
  created_at: string
  updated_at: string
  guest?: GuestInfo
  room?: any
}

export interface ReceptionDashboardData {
  statistics: ReceptionStatistics
  today_arrivals: ReservationInfo[]
  today_departures: CheckInInfo[]
  room_matrix: RoomInMatrix[]
  recent_guests: GuestInfo[]
  recent_reservations: ReservationInfo[]
  active_check_ins: CheckInInfo[]
}

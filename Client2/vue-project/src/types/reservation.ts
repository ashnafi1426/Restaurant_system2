export interface Guest {
  id: string
  first_name: string
  last_name: string
  email: string
  phone: string
}
export interface Room {
  id: string
  room_number: string
  room_type?: string
  floor?: number
  status?: string
}
export interface Reservation {
  id: string
  booking_reference: string
  guest_id: string
  room_id: string
  guest?: Guest
  room?: Room
  check_in_date: string
  check_out_date: string
  total_nights?: number
  number_of_guests: number
  status: 'pending' | 'confirmed' | 'checked_in' | 'checked_out' | 'cancelled'
  special_requests?: string | null
  cancelled_at?: string | null

  created_by?: string | null

  created_at?: string

  updated_at?: string
}

export interface ReservationFormData {
  guest_id: string

  room_id: string

  check_in_date: string

  check_out_date: string

  number_of_guests: number

  status: 'pending' | 'confirmed' | 'checked_in' | 'checked_out' | 'cancelled'

  special_requests?: string
}

export interface ReservationFilter {
  search?: string

  status?: string

  guest_id?: string

  room_id?: string

  page?: number

  per_page?: number
}

export interface Pagination {
  current_page: number

  last_page: number

  per_page: number

  total: number

  from: number

  to: number
}

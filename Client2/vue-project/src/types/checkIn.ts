export interface Guest {
  id: string
  full_name: string
  phone: string
  email: string
}

export interface Room {
  id: string
  room_number: string
  status: string
}

export interface Reservation {
  id: string
  reservation_number: string
  status: string
}

export interface CheckIn {
  id: string
  reservation_id: string
  guest_id: string
  room_id: string

  checked_in_at: string
  expected_check_out_at: string
  checked_out_at: string | null

  guest: Guest
  room: Room
  reservation: Reservation
}

export interface CheckInStatistics {
  total_check_ins: number
  today_check_ins: number
  active_guests: number
  expected_today: number
}

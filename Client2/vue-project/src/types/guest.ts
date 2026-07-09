// Guest information returned by the API
export interface Guest {
  id: string
  first_name: string
  last_name: string
  full_name: string
  email: string | null
  phone: string
  address: string | null
  nationality: string | null
  passport_number: string | null
  date_of_birth: string | null
  preferences: string[] | null
  created_at: string
  updated_at: string
}

// Form data for creating/updating guests
export interface GuestForm {
  first_name: string
  last_name: string
  email: string
  phone: string
  address: string
  nationality: string
  passport_number: string
  date_of_birth: string
  preferences: string[]
}

// API response for a paginated list
export interface GuestListResponse {
  data: Guest[]
  meta: {
    current_page: number
    last_page: number
    per_page: number
    total: number
  }
}

// API response for a single guest
export interface GuestResponse {
  data: Guest
  message?: string
}

// Search/filter parameters
export interface GuestFilter {
  search?: string
  nationality?: string
  page?: number
  per_page?: number
}

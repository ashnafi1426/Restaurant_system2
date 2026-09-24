export interface RestaurantTable {
  id: string
  table_number: string
  table_name: string | null
  capacity: number
  location: string | null
  status: 'available' | 'occupied' | 'reserved' | 'cleaning' | 'out_of_service'
  is_active: boolean
  qr_token: string
  qr_image_path: string | null
  qr_code_url: string | null
  qr_generated_at: string | null
  created_at: string
  updated_at: string
  deleted_at?: string | null
}

export interface CreateTableRequest {
  table_number: string
  table_name?: string | null
  capacity?: number
  location?: string | null
  status?: 'available' | 'occupied' | 'reserved' | 'cleaning' | 'out_of_service'
  is_active?: boolean
}

export interface UpdateTableRequest {
  table_number?: string
  table_name?: string | null
  capacity?: number
  location?: string | null
  status?: 'available' | 'occupied' | 'reserved' | 'cleaning' | 'out_of_service'
  is_active?: boolean
}

export interface TableFilters {
  search?: string
  status?: 'available' | 'occupied' | 'reserved' | 'cleaning' | 'out_of_service' | ''
  location?: string
  is_active?: boolean | null
  sort_by?: string
  sort_order?: 'asc' | 'desc'
  per_page?: number
  page?: number
}

export interface TableStatistics {
  total: number
  active: number
  available: number
  occupied: number
  reserved: number
  cleaning: number
  out_of_service: number
}

export interface PaginatedTablesResponse {
  current_page: number
  data: RestaurantTable[]
  first_page_url: string
  from: number
  last_page: number
  last_page_url: string
  links: Array<{ url: string | null; label: string; active: boolean }>
  next_page_url: string | null
  path: string
  per_page: number
  prev_page_url: string | null
  to: number
  total: number
}

export interface OrderContext {
  type: 'room' | 'table'
  id: string
  displayName: string
  paymentOptions: Array<{ value: string; label: string }>
}

export interface RestaurantSection {
  id: string
  hotel_id?: string | null
  name: string
  description?: string | null
  is_active: boolean
  tables_count?: number
  created_at?: string
  updated_at?: string
}

export interface CreateSectionRequest {
  name: string
  description?: string | null
  is_active?: boolean
}

export interface UpdateSectionRequest {
  name?: string
  description?: string | null
  is_active?: boolean
}

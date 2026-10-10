export interface MenuCategory {
  id: string
  name: string
}

export interface MenuItem {
  id: string | number
  name: string
  description: string
  image?: string | null
  image_url?: string | null
  price: number
  formatted_price?: string
  base_price?: number
  tax_amount?: number
  total_price?: number
  formatted_total_price?: string
  category: string
  is_available?: boolean
  rating?: number | null
  average_rating?: number | null
  review_count?: number
  badge?: string
  dietary?: string[]
  calories?: number
  preparationTime?: number
  tax_rate_id?: string | null
  tax_included?: boolean
  tax_rate?: {
    id?: string | number
    name?: string
    code?: string
    rate: number
    type?: string
  } | null
  dietary_tags?: string[]
  status?: string
  created_at?: string
  updated_at?: string
}

export interface MenuStatistics {
  total_items: number
  available_items: number
  unavailable_items: number
  breakfast_items: number
  lunch_items: number
  dinner_items: number
  drink_items: number
  dessert_items: number
}

export interface MenuPagination {
  current_page: number
  last_page: number
  per_page: number
  total: number
  from?: number
  to?: number
}

export interface MenuPaginatedResponse {
  data: MenuItem[]
  meta: MenuPagination & {
    from: number
    to: number
  }
  links: {
    first: string
    last: string
    next?: string
    prev?: string
  }
}

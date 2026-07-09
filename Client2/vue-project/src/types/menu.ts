export interface MenuCategory {
  id: string
  name: string
}

export interface MenuItem {
  id: string
  name: string
  description: string
  image?: string
  image_url?: string
  price: number
  formatted_price?: string
  category: string
  is_available: boolean
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

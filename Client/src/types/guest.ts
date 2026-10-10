export interface Guest {
  id: string
  first_name: string
  last_name: string
  full_name: string
  name?: string
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

export interface GuestListResponse {
  data: Guest[]
  meta: {
    current_page: number
    last_page: number
    per_page: number
    total: number
  }
}

export interface GuestResponse {
  data: Guest
  message?: string
}

export interface GuestFilter {
  search?: string
  nationality?: string
  page?: number
  per_page?: number
}

export interface MenuItem {
  id: string
  name: string
  description: string
  price: number
  base_price?: number
  total_price?: number
  formatted_price?: string
  formatted_total_price?: string
  tax_amount?: number
  tax_rate?: number
  tax_included?: boolean
  tax_rate_id?: string | null
  image: string | null
  image_url?: string
  category: string
  is_available: boolean
  dietary_tags?: string[]
  status?: string
  created_at?: string
  updated_at?: string
}

export interface Order {
  id: string | number
  order_number?: string
  room_number?: string
  total: number
  status: 'pending' | 'confirmed' | 'preparing' | 'ready' | 'delivered'
  items?: OrderItem[]
  created_at?: string
}

export interface OrderItem {
  menu_item_id: string
  quantity: number
  item_price_at_order?: number
  line_total?: number
}

export interface CartItem extends MenuItem {
  quantity: number
}

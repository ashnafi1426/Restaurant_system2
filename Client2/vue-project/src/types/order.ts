export interface OrderItem {
  id?: string
  menu_item_id: string
  quantity: number
  item_price_at_order?: number
  price?: number
  line_total?: number
  notes?: string
  name?: string
  menu_item?: {
    id: string
    name: string
  }
}

export interface CreateOrderRequest {
  reservation_id: string
  guest_id: string
  room_id: string
  payment_type?: string
  notes?: string
  items: Array<{
    menu_item_id: string
    quantity: number
    notes?: string
  }>
}

export interface UpdateOrderRequest extends CreateOrderRequest {
  id?: string
}

export interface OrderResponse {
  success: boolean
  data: Order
}

export interface OrderCollectionResponse {
  data: Order[]
  meta?: {
    current_page: number
    last_page: number
    per_page: number
    total: number
  }
}

export type OrderStatus = 'pending' | 'preparing' | 'ready' | 'served' | 'cancelled'

export interface OrderFilters {
  search?: string
  status?: string
  payment_type?: string
  room_id?: string
  date_from?: string
  date_to?: string
  page?: number
  per_page?: number
}

export interface OrderStatistics {
  total_orders: number
  pending_orders: number
  preparing_orders: number
  ready_orders: number
  served_orders: number
  cancelled_orders: number
  total_revenue: number
}

export interface Order {
  id: string
  order_number: string
  reservation_id: string
  guest_id: string
  room_id: string
  order_time: string
  status: 'pending' | 'preparing' | 'ready' | 'served' | 'cancelled'
  payment_type: 'room_charge' | 'cash' | 'card'
  subtotal: number
  tax: number
  discount: number
  total: number
  notes?: string
  served_at?: string | null
  cancelled_at?: string | null
  created_at: string
  updated_at: string
  items?: OrderItem[]
  guest?: {
    id: string
    first_name: string
    last_name: string
    email?: string
    phone?: string
    full_name?: string
  }
  room?: {
    id: string
    room_number: string
    room_type?: {
      id: string
      name: string
    }
  }
  reservation?: {
    id: string
  }
}

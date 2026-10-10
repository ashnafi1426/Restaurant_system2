export interface OrderItem {
  id?: string | number
  menu_item_id: string | number
  item_name?: string
  quantity: number
  item_price_at_order?: number
  price?: number
  tax_rate?: number
  tax_amount?: number
  line_total?: number
  subtotal?: number
  total?: number
  notes?: string
  name?: string
  menu_item?: {
    id: string | number
    name: string
  }
}

export interface CreateOrderRequest {
  order_type?: 'room_service' | 'dine_in' | 'walk_in'
  reservation_id?: string
  guest_id?: string
  room_id?: string
  table_id?: string
  payment_type?: string
  discount?: number
  service_charge_rate?: number
  service_charge_amount?: number
  notes?: string
  items: Array<{
    menu_item_id: string | number
    quantity: number
    notes?: string
  }>
}

export interface UpdateOrderRequest {
  id?: string
  order_type?: 'room_service' | 'dine_in' | 'walk_in'
  reservation_id?: string
  guest_id?: string
  room_id?: string
  table_id?: string
  payment_type?: string
  discount?: number
  service_charge_rate?: number
  service_charge_amount?: number
  notes?: string
  items?: Array<{
    id?: string
    menu_item_id: string | number
    quantity: number
    notes?: string
  }>
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
  statistics?: OrderStatistics
}

export type OrderStatus = 'pending' | 'preparing' | 'ready' | 'served' | 'cancelled'

export interface OrderFilters {
  search?: string
  status?: string
  payment_type?: string
  order_type?: string
  room_id?: string
  table_id?: string
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
  order_type?: 'room_service' | 'dine_in' | 'walk_in'
  reservation_id?: string | null
  guest_id?: string | null
  room_id?: string | null
  table_id?: string | null
  order_time: string
  status: 'pending' | 'preparing' | 'ready' | 'served' | 'cancelled'
  payment_type: 'room_charge' | 'cash' | 'card' | 'chapa' | 'online'
  subtotal: number
  taxable_amount?: number
  tax: number
  service_charge_rate?: number
  service_charge_amount?: number
  discount: number
  total: number
  notes?: string
  served_at?: string | null
  cancelled_at?: string | null
  created_at: string
  updated_at: string
  items?: OrderItem[]
  order_items?: OrderItem[]
  guest?: {
    id: string
    first_name?: string
    last_name?: string
    name?: string
    full_name?: string
    email?: string
    phone?: string
  }
  room?: {
    id: string
    room_number: string
    room_type?: {
      id: string
      name: string
    }
  }
  table?: {
    id: string
    table_number: string
    section?: {
      id: string
      name: string
    }
  }
  reservation?: {
    id: string
    booking_reference?: string
    check_in_date?: string
    check_out_date?: string
  }
}

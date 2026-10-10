export type KitchenOrderStatus = 'pending' | 'preparing' | 'ready' | 'served' | 'cancelled'

export interface KitchenGuest {
  id: string

  first_name: string

  last_name: string

  full_name: string
}

export interface KitchenRoom {
  id: string

  room_number: string
}

export interface KitchenTable {
  id: string
  table_number: string | number
  table_name?: string
}

export interface KitchenReservation {
  id: string

  booking_reference: string
}

export interface KitchenMenuItem {
  id: string

  menu_item_id: string

  name: string

  category: string

  quantity: number

  unit_price: number

  line_total: number

  notes?: string | null
  note?: string | null

  image?: string | null
}

export interface KitchenOrder {
  id: string

  order_number: string

  order_type?: 'room_service' | 'walk_in' | string

  status: KitchenOrderStatus

  order_time: string

  guest: KitchenGuest

  room?: KitchenRoom | null

  table?: KitchenTable | null

  reservation?: KitchenReservation | null

  items: KitchenMenuItem[]

  subtotal: number

  tax: number

  discount: number

  total: number

  notes?: string | null

  created_at: string

  updated_at: string
}

export interface KitchenStatistics {
  pending_orders: number

  preparing_orders: number

  ready_orders: number

  served_orders: number

  total_orders: number

  today_orders: number

  today_pending: number

  today_preparing: number

  today_ready: number

  today_served: number
  avg_prep_time_minutes?: number
}

export interface KitchenDashboardResponse {
  pending: KitchenOrder[]

  preparing: KitchenOrder[]

  ready: KitchenOrder[]

  served: KitchenOrder[]
}
export interface KitchenApiResponse<T> {
  success: boolean

  message: string

  data: T
}
export interface KitchenFilters {
  search: string
  status: KitchenOrderStatus | ''
  room: string
  guest: string
}
export interface KitchenState {
  pendingOrders: KitchenOrder[]
  preparingOrders: KitchenOrder[]
  readyOrders: KitchenOrder[]
  servedOrders: KitchenOrder[]
  statistics: KitchenStatistics | null
  filters: KitchenFilters
  loading: boolean
  processing: boolean
  error: string | null
}
export interface KitchenActionPayload {
  orderId: string
}
export interface KitchenCard {
  title: string
  value: number
  color: string
  icon: string
}
export interface KitchenQueue {
  pending: KitchenOrder[]
  preparing: KitchenOrder[]
  ready: KitchenOrder[]
  served: KitchenOrder[]
}

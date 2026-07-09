export type OrderStatus =
  | 'pending'
  | 'preparing'
  | 'ready'
  | 'served'
  | 'cancelled'

export type PaymentType =
  | 'room_charge'
  | 'cash'
  | 'card'
export interface OrderGuest {
  id: string
  name: string
  email?: string
  phone?: string
}
export interface OrderRoom {
  id: string
  room_number: string
}
export interface OrderReservation {
  id: string
}

export interface OrderMenuItem {
  id: string
  name: string
  image?: string | null
  price: number
}

export interface OrderItem {
  id: string

  menu_item_id: string

  menu_item_name?: string

  menu_item_image?: string | null

  quantity: number

  item_price: number

  line_total: number

  notes?: string | null
}
export interface Order {
  id: string

  order_number: string

  status: OrderStatus

  payment_type: PaymentType

  reservation: OrderReservation

  guest: OrderGuest

  room: OrderRoom

  subtotal: number

  tax: number

  discount: number

  total: number

  notes?: string | null

  order_time: string

  served_at?: string | null

  cancelled_at?: string | null

  created_at: string

  updated_at: string

  items: OrderItem[]
}
export interface CreateOrderItemRequest {
  menu_item_id: string

  quantity: number

  notes?: string
}

export interface CreateOrderRequest {
  reservation_id: string

  guest_id: string

  room_id: string

  notes?: string

  items: CreateOrderItemRequest[]
}
export interface UpdateOrderItemRequest {
  id?: string

  menu_item_id: string

  quantity: number

  notes?: string
}

export interface UpdateOrderRequest {
  reservation_id: string

  guest_id: string

  room_id: string

  notes?: string

  items: UpdateOrderItemRequest[]
}
export interface ChangeOrderStatusRequest {
  status: OrderStatus
}
export interface PaginationMeta {
  current_page: number
  last_page: number
  per_page: number
  total: number
}
export interface OrderCollectionResponse {
  success: boolean
  message: string
  data: Order[]
  meta: PaginationMeta
}
export interface OrderResponse {
  success: boolean

  message: string

  data: Order
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

export interface OrderFilters {
  search: string
  status: OrderStatus | ''
  payment_type: PaymentType | ''
  room_id: string
  date_from: string
  date_to: string
  page: number
  per_page: number
}

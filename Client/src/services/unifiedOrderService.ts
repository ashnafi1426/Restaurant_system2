import { publicAxios as axios } from './axios'

export interface OrderItem {
  menu_item_id: string
  quantity: number
}

export interface CreateOrderRequest {
  qr_token: string
  items: OrderItem[]
  special_requests?: string
  payment_type?: 'room_charge' | 'cash' | 'card'
}
export interface WalkInPaymentRequest {
  table_id: string
  qr_token: string
  items: OrderItem[]
  special_requests?: string
  first_name: string
  last_name: string
  email: string
  phone: string
}
export interface OrderResponse {
  success: boolean
  message: string
  data: {
    order_id: string
    id?: string
    order_number: string
    order_type: 'room_service' | 'dine_in' | 'walk_in'
    room_number?: string
    table_number?: string
    hotel_id?: string
    total: number
    status: string
    created_at: string
  }
}

export interface PaymentInitializeResponse {
  success: boolean
  message: string
  payment_id?: string
  checkout_url?: string
  tx_ref?: string
  amount?: number
  calculation?: any
  errors?: any
}

export const unifiedOrderService = {
  async createOrder(orderData: CreateOrderRequest): Promise<OrderResponse> {
    try {
      const response = await axios.post('/guest/unified-orders', orderData)
      return response.data
    } catch (error: any) {
      console.error('[UnifiedOrderService] Error creating order:', error)
      throw {
        success: false,
        message: error.response?.data?.message || 'Failed to create order',
        errors: error.response?.data?.errors || {},
      }
    }
  },

  async initializeWalkInPayment(
    paymentData: WalkInPaymentRequest,
  ): Promise<PaymentInitializeResponse> {
    try {
      const response = await axios.post('/walk-in-payments/initialize', paymentData)
      return response.data
    } catch (error: any) {
      console.error('[UnifiedOrderService] Error initializing walk-in payment:', error)
      throw {
        success: false,
        message: error.response?.data?.message || 'Failed to initialize payment',
        errors: error.response?.data?.errors || {},
      }
    }
  },

  async getOrderByPayment(txRef: string): Promise<any> {
    try {
      const response = await axios.get(`/walk-in-payments/${txRef}`)
      return response.data
    } catch (error: any) {
      console.error('[UnifiedOrderService] Error getting order by payment:', error)
      throw {
        success: false,
        message: error.response?.data?.message || 'Failed to get order',
        errors: error.response?.data?.errors || {},
      }
    }
  },
}

export default unifiedOrderService

import api from '@/api/auth'
import type { AxiosResponse } from 'axios'

export interface MenuItemResponse {
  id: string | number
  name: string
  description: string
  price: number
  image?: string | null
  category: string
  is_available?: boolean
  is_active?: boolean
}

export interface CartItem {
  menu_item_id: string | number
  quantity: number
}

export interface OrderRequest {
  qr_token?: string
  table_id?: string
  items: CartItem[]
  special_requests?: string
  notes?: string
}

export interface OrderResponse {
  id: string
  order_number: string
  payment_status: string
  order_status: string
  estimated_time?: number
  total?: number
}

export interface SessionResponse {
  id: string
  session_number: string
  table_id: string
  customer_type: 'walk_in'
  customer_name?: string
  customer_phone?: string
  status: string
  started_at: string
}

class RestaurantService {
  async getMenuItems(): Promise<AxiosResponse<{ data: MenuItemResponse[] }>> {
    return api.get('/guest/menu/items')
  }

  async getMenuByQRToken(qrToken: string): Promise<AxiosResponse<any>> {
    return api.get(`/guest/menu/${qrToken}`)
  }

  async createGuestOrder(orderData: {
    qr_token: string
    items: CartItem[]
    special_requests?: string
  }): Promise<AxiosResponse<{ data: OrderResponse }>> {
    return api.post('/guest/orders', orderData)
  }

  async initializeWalkInSession(qrToken: string): Promise<AxiosResponse<{ data: SessionResponse }>> {
    return api.post('/walk-in/session/initialize', {
      qr_token: qrToken,
    })
  }

  async getWalkInSession(sessionId: string): Promise<AxiosResponse<{ data: SessionResponse }>> {
    return api.get(`/walk-in/session/${sessionId}`)
  }

  async createWalkInOrder(orderData: {
    table_id?: string
    session_id?: string
    items: CartItem[]
    notes?: string
  }): Promise<AxiosResponse<{ data: OrderResponse }>> {
    return api.post('/walk-in/orders', orderData)
  }

  async getOrder(orderId: string): Promise<AxiosResponse<{ data: OrderResponse }>> {
    return api.get(`/walk-in/orders/${orderId}`)
  }

  async initializeChapaPayment(paymentData: {
    order_id: string
    amount: number
    email?: string
    phone?: string
  }): Promise<AxiosResponse<any>> {
    return api.post('/walk-in/payment/initialize', paymentData)
  }

  async verifyChapaPayment(txRef: string): Promise<AxiosResponse<any>> {
    return api.get(`/walk-in/payment/verify/${txRef}`)
  }

  async getTodayStats(): Promise<AxiosResponse<{ data: any }>> {
    return api.get('/walk-in/orders/today/stats')
  }

  async getTodayOrders(): Promise<AxiosResponse<{ data: OrderResponse[] }>> {
    return api.get('/walk-in/orders/today')
  }

  async endWalkInSession(sessionId: string): Promise<AxiosResponse<{ data: SessionResponse }>> {
    return api.post(`/walk-in/session/${sessionId}/end`, {})
  }
}

export default new RestaurantService()

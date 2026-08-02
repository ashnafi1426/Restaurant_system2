/**
 * Restaurant Service
 * Handles all restaurant-related API calls for unified QR ordering system
 * Used by both hotel guests and walk-in customers
 */

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
  /**
   * Get all menu items (shared endpoint used by both customer types)
   * GET /api/guest/menu/items
   */
  async getMenuItems(): Promise<AxiosResponse<{ data: MenuItemResponse[] }>> {
    return api.get('/guest/menu/items')
  }

  /**
   * Get menu items for specific QR token (hotel guest only)
   * GET /api/guest/menu/{qrToken}
   */
  async getMenuByQRToken(qrToken: string): Promise<AxiosResponse<any>> {
    return api.get(`/guest/menu/${qrToken}`)
  }

  /**
   * Create hotel guest order
   * POST /api/guest/orders
   */
  async createGuestOrder(orderData: {
    qr_token: string
    items: CartItem[]
    special_requests?: string
  }): Promise<AxiosResponse<{ data: OrderResponse }>> {
    return api.post('/guest/orders', orderData)
  }

  /**
   * Initialize walk-in customer session
   * POST /api/walk-in/session/initialize
   */
  async initializeWalkInSession(qrToken: string): Promise<AxiosResponse<{ data: SessionResponse }>> {
    return api.post('/walk-in/session/initialize', {
      qr_token: qrToken,
    })
  }

  /**
   * Get walk-in session details
   * GET /api/walk-in/session/{sessionId}
   */
  async getWalkInSession(sessionId: string): Promise<AxiosResponse<{ data: SessionResponse }>> {
    return api.get(`/walk-in/session/${sessionId}`)
  }

  /**
   * Create walk-in customer order
   * POST /api/walk-in/orders
   */
  async createWalkInOrder(orderData: {
    table_id?: string
    session_id?: string
    items: CartItem[]
    notes?: string
  }): Promise<AxiosResponse<{ data: OrderResponse }>> {
    return api.post('/walk-in/orders', orderData)
  }

  /**
   * Get order details
   * GET /api/walk-in/orders/{orderId}
   */
  async getOrder(orderId: string): Promise<AxiosResponse<{ data: OrderResponse }>> {
    return api.get(`/walk-in/orders/${orderId}`)
  }

  /**
   * Initialize Chapa payment for walk-in order
   * POST /api/walk-in/payment/initialize
   */
  async initializeChapaPayment(paymentData: {
    order_id: string
    amount: number
    email?: string
    phone?: string
  }): Promise<AxiosResponse<any>> {
    return api.post('/walk-in/payment/initialize', paymentData)
  }

  /**
   * Verify Chapa payment
   * GET /api/walk-in/payment/verify/{txRef}
   */
  async verifyChapaPayment(txRef: string): Promise<AxiosResponse<any>> {
    return api.get(`/walk-in/payment/verify/${txRef}`)
  }

  /**
   * Get today's order statistics
   * GET /api/walk-in/orders/today/stats
   */
  async getTodayStats(): Promise<AxiosResponse<{ data: any }>> {
    return api.get('/walk-in/orders/today/stats')
  }

  /**
   * Get today's orders
   * GET /api/walk-in/orders/today
   */
  async getTodayOrders(): Promise<AxiosResponse<{ data: OrderResponse[] }>> {
    return api.get('/walk-in/orders/today')
  }

  /**
   * End walk-in session
   * POST /api/walk-in/session/{sessionId}/end
   */
  async endWalkInSession(sessionId: string): Promise<AxiosResponse<{ data: SessionResponse }>> {
    return api.post(`/walk-in/session/${sessionId}/end`, {})
  }
}

export default new RestaurantService()

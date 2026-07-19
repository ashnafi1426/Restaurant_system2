import api from '../api/auth'

import type {
  KitchenApiResponse,
  KitchenDashboardResponse,
  KitchenOrder,
  KitchenStatistics,
} from '@/types/kitchen'

class KitchenService {
  /*
  |--------------------------------------------------------------------------
  | Dashboard Orders
  |--------------------------------------------------------------------------
  */

  async getOrders(): Promise<KitchenDashboardResponse> {
    const response = await api.get<KitchenApiResponse<KitchenDashboardResponse>>('/kitchen/orders')

    return response.data.data
  }

  /*
  |--------------------------------------------------------------------------
  | Statistics
  |--------------------------------------------------------------------------
  */

  async getStatistics(): Promise<KitchenStatistics> {
    const response = await api.get<KitchenApiResponse<KitchenStatistics>>('/kitchen/statistics')

    return response.data.data
  }

  /*
  |--------------------------------------------------------------------------
  | Start Preparing
  |--------------------------------------------------------------------------
  */

  async startPreparing(orderId: string): Promise<KitchenOrder> {
    const endpoint = `/kitchen/orders/${orderId}/start`
    console.log(`🌐 [SERVICE] Making PATCH request to: ${endpoint}`)
    console.log(`📦 [SERVICE] Order ID: ${orderId}`)
    
    try {
      const response = await api.patch<KitchenApiResponse<KitchenOrder>>(endpoint)
      
      console.log(`✅ [SERVICE] Response status: ${response.status}`)
      console.log(`✅ [SERVICE] Response data:`, response.data)
      
      if (!response.data.data) {
        throw new Error('No data in response')
      }
      
      console.log(`✅ [SERVICE] Order data extracted, status: ${response.data.data.status}`)
      return response.data.data
    } catch (err: any) {
      console.error(`❌ [SERVICE] API Error:`, {
        endpoint,
        orderId,
        status: err?.response?.status,
        message: err?.response?.data?.message,
        error: err?.response?.data?.error,
        fullError: err.message,
      })
      throw err
    }
  }

  /*
  |--------------------------------------------------------------------------
  | Mark Ready
  |--------------------------------------------------------------------------
  */

  async markReady(orderId: string): Promise<KitchenOrder> {
    const endpoint = `/kitchen/orders/${orderId}/ready`
    console.log(`🌐 [SERVICE] Making PATCH request to: ${endpoint}`)
    
    try {
      const response = await api.patch<KitchenApiResponse<KitchenOrder>>(endpoint)
      
      console.log(`✅ [SERVICE] Response status: ${response.status}`)
      if (!response.data.data) {
        throw new Error('No data in response')
      }
      console.log(`✅ [SERVICE] Order data extracted, status: ${response.data.data.status}`)
      return response.data.data
    } catch (err: any) {
      console.error(`❌ [SERVICE] API Error:`, {
        endpoint,
        orderId,
        status: err?.response?.status,
        message: err?.response?.data?.message,
      })
      throw err
    }
  }

  /*
  |--------------------------------------------------------------------------
  | Mark Served
  |--------------------------------------------------------------------------
  */

  async markServed(orderId: string): Promise<KitchenOrder> {
    const endpoint = `/kitchen/orders/${orderId}/complete`
    console.log(`🌐 [SERVICE] Making PATCH request to: ${endpoint}`)
    
    try {
      const response = await api.patch<KitchenApiResponse<KitchenOrder>>(endpoint)
      
      console.log(`✅ [SERVICE] Response status: ${response.status}`)
      if (!response.data.data) {
        throw new Error('No data in response')
      }
      console.log(`✅ [SERVICE] Order data extracted, status: ${response.data.data.status}`)
      return response.data.data
    } catch (err: any) {
      console.error(`❌ [SERVICE] API Error:`, {
        endpoint,
        orderId,
        status: err?.response?.status,
        message: err?.response?.data?.message,
      })
      throw err
    }
  }

  async refresh() {
    const [orders, statistics] = await Promise.all([this.getOrders(), this.getStatistics()])

    return {
      orders,
      statistics,
    }
  }
}

export default new KitchenService()

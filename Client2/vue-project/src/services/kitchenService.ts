import api from '../api/auth'

import type {
  KitchenApiResponse,
  KitchenDashboardResponse,
  KitchenOrder,
  KitchenStatistics,
} from '@/types/kitchen'

class KitchenService {
  async getOrders(): Promise<KitchenDashboardResponse> {
    const response = await api.get<KitchenApiResponse<KitchenDashboardResponse>>('/kitchen/orders')

    return response.data.data
  }

  async getStatistics(): Promise<KitchenStatistics> {
    const response = await api.get<KitchenApiResponse<KitchenStatistics>>('/kitchen/statistics')

    return response.data.data
  }

  async startPreparing(orderId: string): Promise<KitchenOrder> {
    const endpoint = `/kitchen/orders/${orderId}/start`

    try {
      const response = await api.patch<KitchenApiResponse<KitchenOrder>>(endpoint)

      if (!response.data.data) {
        throw new Error('No data in response')
      }

      return response.data.data
    } catch (err: any) {
      throw err
    }
  }

  async markReady(orderId: string): Promise<KitchenOrder> {
    const endpoint = `/kitchen/orders/${orderId}/ready`

    try {
      const response = await api.patch<KitchenApiResponse<KitchenOrder>>(endpoint)

      if (!response.data.data) {
        throw new Error('No data in response')
      }
      return response.data.data
    } catch (err: any) {
      throw err
    }
  }

  async markServed(orderId: string): Promise<KitchenOrder> {
    const endpoint = `/kitchen/orders/${orderId}/complete`

    try {
      const response = await api.patch<KitchenApiResponse<KitchenOrder>>(endpoint)

      if (!response.data.data) {
        throw new Error('No data in response')
      }
      return response.data.data
    } catch (err: any) {
      throw err
    }
  }

  async refresh() {
    const [ordersResult, statsResult] = await Promise.allSettled([
      this.getOrders(),
      this.getStatistics(),
    ])

    const orders =
      ordersResult.status === 'fulfilled'
        ? ordersResult.value
        : { pending: [], preparing: [], ready: [], served: [] }

    const statistics = statsResult.status === 'fulfilled' ? statsResult.value : null

    return {
      orders,
      statistics,
    }
  }
}

export default new KitchenService()

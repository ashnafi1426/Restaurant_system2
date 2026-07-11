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
    const response =
      await api.get<
        KitchenApiResponse<KitchenDashboardResponse>
      >('/kitchen/orders')

    return response.data.data
  }

  /*
  |--------------------------------------------------------------------------
  | Statistics
  |--------------------------------------------------------------------------
  */

  async getStatistics(): Promise<KitchenStatistics> {
    const response =
      await api.get<
        KitchenApiResponse<KitchenStatistics>
      >('/kitchen/statistics')

    return response.data.data
  }

  /*
  |--------------------------------------------------------------------------
  | Start Preparing
  |--------------------------------------------------------------------------
  */

  async startPreparing(
    orderId: string,
  ): Promise<KitchenOrder> {
    const response =
      await api.patch<
        KitchenApiResponse<KitchenOrder>
      >(`/kitchen/orders/${orderId}/start`)

    return response.data.data
  }

  /*
  |--------------------------------------------------------------------------
  | Mark Ready
  |--------------------------------------------------------------------------
  */

  async markReady(
    orderId: string,
  ): Promise<KitchenOrder> {
    const response =
      await api.patch<
        KitchenApiResponse<KitchenOrder>
      >(`/kitchen/orders/${orderId}/ready`)

    return response.data.data
  }

  /*
  |--------------------------------------------------------------------------
  | Mark Served
  |--------------------------------------------------------------------------
  */

  async markServed(
    orderId: string,
  ): Promise<KitchenOrder> {
    const response =
      await api.patch<
        KitchenApiResponse<KitchenOrder>
      >(`/kitchen/orders/${orderId}/complete`)

    return response.data.data
  }

  /*
  |--------------------------------------------------------------------------
  | Refresh Dashboard
  |--------------------------------------------------------------------------
  */

  async refresh() {
    const [orders, statistics] =
      await Promise.all([
        this.getOrders(),
        this.getStatistics(),
      ])

    return {
      orders,
      statistics,
    }
  }
}

export default new KitchenService()
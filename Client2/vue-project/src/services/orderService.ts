import api from '../api/auth'
import type {
  OrderFilters,
  OrderCollectionResponse,
  OrderResponse,
  CreateOrderRequest,
  UpdateOrderRequest,
} from '@/types/order'
class OrderService {
  async getOrders(
    filters?: Partial<OrderFilters>
  ): Promise<OrderCollectionResponse> {
    const response = await api.get<OrderCollectionResponse>(
      '/orders',
      {
        params: filters,
      }
    )

    return response.data
  }

  
  async getOrder(
    id: string
  ): Promise<OrderResponse> {
    const response = await api.get<OrderResponse>(
      `/orders/${id}`
    )

    return response.data
  }
async createOrder(
  payload: CreateOrderRequest
): Promise<OrderResponse> {
  const response = await api.post<OrderResponse>(
    '/orders',
    payload
  )

  return response.data
}
async updateOrder(
  id: string,
  payload: UpdateOrderRequest
): Promise<OrderResponse> {
  const response = await api.put<OrderResponse>(
    `/orders/${id}`,
    payload
  )

  return response.data
}
async deleteOrder(
  id: string
): Promise<void> {
  await api.delete(
    `/orders/${id}`
  )
}

}
export default new OrderService()
import api from '@/api/auth'

export interface DeliveryTask {
  id: string
  order_id: string
  reservation_id?: string
  room_id?: string
  floor_id?: string
  waiter?: {
    id?: string
    name?: string
    full_name?: string
    user?: { name: string; email: string }
    current_orders?: number
    maximum_orders?: number
  } | null
  floor?:
    | {
        id?: string
        name?: string
        floor_number?: number
      }
    | string
    | number
    | null
  room?: {
    id?: string
    room_number?: string
    floor?: string | number
  } | null
  order?: {
    id?: string
    order_number?: string
  } | null
  assigned_by?: {
    id?: string
    name?: string
  } | null
  assignment_type?: 'automatic' | 'manual'
  status: 'assigned' | 'accepted' | 'picked_up' | 'on_delivery' | 'delivered' | 'cancelled'
  assigned_at?: string
  accepted_at?: string | null
  picked_up_at?: string | null
  delivered_at?: string | null
  completed_at?: string | null
  rejection_reason?: string | null
  delivery_notes?: string | null
  created_at?: string
}

export interface DeliverySummary {
  total: number
  completed: number
  in_progress: number
  failed: number
  pending: number
}

export interface DeliveryReport {
  total_deliveries: number
  completed_deliveries: number
  failed_deliveries: number
  pending_deliveries: number
  automatic_assignments: number
  manual_reassignments: number
  avg_delivery_time: string
  by_waiter: Array<{
    waiter_id: string
    waiter_name: string
    total: number
    completed: number
    failed: number
  }>
  by_floor: Array<{
    floor_id: string
    floor_name: string
    total: number
    completed: number
  }>
  by_status: Array<{
    status: string
    count: number
  }>
}

class DeliveryManagementService {
  async getDeliveries(params?: {
    page?: number
    per_page?: number
    status?: string
    waiter_id?: string
    floor_id?: string
    assignment_type?: string
    start_date?: string
    end_date?: string
    sort_by?: string
    sort_order?: 'asc' | 'desc'
  }): Promise<any> {
    const response = await api.get('/manager/deliveries', { params })
    return response.data
  }

  async getDelivery(deliveryId: string): Promise<DeliveryTask> {
    const response = await api.get(`/manager/deliveries/${deliveryId}`)
    return response.data.data
  }

  async reassignDelivery(
    deliveryId: string,
    newWaiterId: string,
    currentWaiterId: string,
    reason?: string,
  ): Promise<DeliveryTask> {
    const response = await api.patch(`/manager/deliveries/${deliveryId}/reassign`, {
      waiter_id: newWaiterId,
      current_waiter_id: currentWaiterId,
      reason,
    })
    return response.data.data
  }

  async cancelDelivery(deliveryId: string, reason?: string): Promise<void> {
    await api.delete(`/manager/deliveries/${deliveryId}`, {
      data: { reason },
    })
  }

  async getTodaySummary(): Promise<DeliverySummary> {
    const response = await api.get('/manager/deliveries/summary/today')
    const data = response.data?.data || response.data
    return data
  }

  async getDeliveryReport(params?: {
    start_date?: string
    end_date?: string
  }): Promise<DeliveryReport> {
    const response = await api.get('/manager/deliveries/report', { params })
    return response.data.data
  }
}

export default new DeliveryManagementService()

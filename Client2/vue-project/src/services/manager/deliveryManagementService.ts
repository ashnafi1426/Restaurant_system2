import api from '@/api/auth'

export interface DeliveryTask {
  id: string
  order_id: string
  reservation_id: string
  room_id: string
  floor_id: string
  waiter: {
    id: string
    user: { name: string; email: string }
    current_orders: number
    maximum_orders: number
  }
  floor: {
    id: string
    name: string
    floor_number: number
  }
  assigned_by: {
    id: string
    name: string
  }
  assignment_type: 'automatic' | 'manual'
  status:
    | 'assigned'
    | 'accepted'
    | 'picked_up'
    | 'on_delivery'
    | 'delivered'
    | 'cancelled'
  assigned_at: string
  accepted_at: string | null
  picked_up_at: string | null
  delivered_at: string | null
  completed_at: string | null
  rejection_reason: string | null
  delivery_notes: string | null
  created_at: string
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
  /**
   * Get all deliveries with filters
   */
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

  /**
   * Get delivery details
   */
  async getDelivery(deliveryId: string): Promise<DeliveryTask> {
    const response = await api.get(`/manager/deliveries/${deliveryId}`)
    return response.data.data
  }

  /**
   * Manually reassign delivery to different waiter
   */
  async reassignDelivery(
    deliveryId: string,
    newWaiterId: string,
    currentWaiterId: string,
    reason?: string
  ): Promise<DeliveryTask> {
    const response = await api.patch(`/manager/deliveries/${deliveryId}/reassign`, {
      waiter_id: newWaiterId,
      current_waiter_id: currentWaiterId,
      reason,
    })
    return response.data.data
  }

  /**
   * Cancel delivery
   */
  async cancelDelivery(deliveryId: string, reason?: string): Promise<void> {
    await api.delete(`/manager/deliveries/${deliveryId}`, {
      data: { reason },
    })
  }

  /**
   * Get today's delivery summary
   */
  async getTodaySummary(): Promise<DeliverySummary> {
    const response = await api.get('/manager/deliveries/summary/today')
    return response.data.data
  }

  /**
   * Generate delivery report
   */
  async getDeliveryReport(params?: {
    start_date?: string
    end_date?: string
  }): Promise<DeliveryReport> {
    const response = await api.get('/manager/deliveries/report', { params })
    return response.data.data
  }
}

export default new DeliveryManagementService()

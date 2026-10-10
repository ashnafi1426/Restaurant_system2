import api from '@/api/auth'

export interface Waiter {
  id: string
  user_id: string
  user: {
    id: string
    name: string
    email: string
    phone: string
  }
  employee_number: string
  phone: string
  employment_type: string
  hire_date: string
  status: 'active' | 'inactive' | 'suspended'
  availability: 'available' | 'busy' | 'break' | 'offline'
  current_orders: number
  maximum_orders: number
  profile_photo: string | null
  is_busy: boolean
  created_at: string
  updated_at: string
}

export interface WaiterStats {
  total_deliveries: number
  completed: number
  failed: number
  avg_delivery_time: string
  rating: number
}

export interface PaginatedResponse<T> {
  data: T[]
  pagination: {
    total: number
    per_page: number
    current_page: number
    last_page: number
  }
}

class WaiterManagementService {
  async getWaiters(params?: {
    page?: number
    per_page?: number
    search?: string
    status?: string
    availability?: string
  }): Promise<PaginatedResponse<Waiter>> {
    const response = await api.get('/manager/waiters', { params })
    const data = response.data.data || response.data
    const paginationData = response.data.pagination || {
      total: Array.isArray(data) ? data.length : 0,
      per_page: params?.per_page || 15,
      current_page: 1,
      last_page: 1,
    }

    return {
      data: Array.isArray(data) ? data : data.data || [],
      pagination: paginationData,
    }
  }

  async getWaiter(waiterId: string): Promise<Waiter> {
    const response = await api.get(`/manager/waiters/${waiterId}`)
    return response.data.data
  }

  async registerWaiter(data: {
    first_name: string
    last_name: string
    email: string
    phone: string
    password: string
    password_confirmation: string
    employment_type: 'full_time' | 'part_time' | 'contract'
    hire_date: string
    maximum_orders: number
    employee_number?: string
  }): Promise<Waiter> {
    const response = await api.post('/manager/waiters', data)
    return response.data.data
  }

  async updateWaiter(
    waiterId: string,
    data: {
      phone?: string
      employment_type?: 'full_time' | 'part_time' | 'contract'
      maximum_orders?: number
      status?: 'active' | 'inactive' | 'suspended'
      availability?: 'available' | 'busy' | 'break' | 'offline'
    },
  ): Promise<Waiter> {
    const response = await api.put(`/manager/waiters/${waiterId}`, data)
    return response.data.data
  }

  async deactivateWaiter(waiterId: string): Promise<Waiter> {
    const response = await api.patch(`/manager/waiters/${waiterId}/deactivate`)
    return response.data.data
  }

  async reactivateWaiter(waiterId: string): Promise<Waiter> {
    const response = await api.patch(`/manager/waiters/${waiterId}/reactivate`)
    return response.data.data
  }

  async suspendWaiter(waiterId: string, reason: string): Promise<Waiter> {
    const response = await api.patch(`/manager/waiters/${waiterId}/suspend`, {
      reason,
    })
    return response.data.data
  }

  async changeAvailability(
    waiterId: string,
    availability: 'available' | 'busy' | 'break' | 'offline',
  ): Promise<Waiter> {
    const response = await api.patch(`/manager/waiters/${waiterId}/availability`, {
      availability,
    })
    return response.data.data
  }

  async getWaiterStats(waiterId: string): Promise<WaiterStats> {
    const response = await api.get(`/manager/waiters/${waiterId}/stats`)
    return response.data.data
  }

  async deleteWaiter(waiterId: string): Promise<void> {
    await api.delete(`/manager/waiters/${waiterId}`)
  }
}

export default new WaiterManagementService()

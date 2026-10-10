import api from '@/api/auth'
import type {
  WaiterAssignment,
  WaiterDashboard,
  DeliveryLog,
  WaiterPerformance,
  WaiterProfile,
} from '@/types/waiter'
class WaiterService {
  private getTenantParams(extra: Record<string, any> = {}) {
    const hotelId = typeof localStorage !== 'undefined' ? localStorage.getItem('hotel_id') : null
    return hotelId ? { hotel_id: hotelId, ...extra } : extra
  }

  async getDashboard(customParams?: any): Promise<WaiterDashboard> {
    try {
      const response = await api.get('/waiter/dashboard', {
        params: this.getTenantParams(customParams),
      })
      if (response.data && response.data.data) {
        return response.data.data
      }
      return response.data
    } catch (err: any) {
      console.error('[WaiterService] Error fetching dashboard data:', err)
      throw err
    }
  }
  async getTodayStats(): Promise<any> {
    const response = await api.get('/waiter/dashboard/today', {
      params: this.getTenantParams(),
    })
    return response.data.data
  }
  async getPerformance(): Promise<any> {
    const response = await api.get('/waiter/dashboard/performance', {
      params: this.getTenantParams(),
    })
    return response.data.data
  }
  async getRecentAssignments(limit: number = 10): Promise<WaiterAssignment[]> {
    const response = await api.get('/waiter/dashboard/recent-assignments', {
      params: this.getTenantParams({ limit }),
    })
    return response.data.data
  }
  async getReadyForPickup(customParams?: any): Promise<WaiterAssignment[]> {
    const response = await api.get('/waiter/dashboard/ready-pickup', {
      params: this.getTenantParams(customParams),
    })
    return response.data.data
  }

  async getReadyForPickupOrders(customParams?: any): Promise<WaiterAssignment[]> {
    return this.getReadyForPickup(customParams)
  }
  async getPendingPickupOrders(): Promise<WaiterAssignment[]> {
    const response = await api.get('/waiter/dashboard/pending-pickup', {
      params: this.getTenantParams(),
    })
    return response.data.data
  }

  async getOnDelivery(): Promise<WaiterAssignment[]> {
    const response = await api.get('/waiter/dashboard/on-delivery', {
      params: this.getTenantParams(),
    })
    return response.data.data
  }

  async getActiveDeliveries(): Promise<WaiterAssignment[]> {
    return this.getOnDelivery()
  }

  async getDeliveryHistory(params: any = {}): Promise<any> {
    const res = await this.getHistory(params)
    return res.data || res
  }

  async getCompletedDeliveries(limit: number = 10): Promise<WaiterAssignment[]> {
    const response = await api.get('/waiter/dashboard/completed', {
      params: this.getTenantParams({ limit }),
    })
    return response.data.data
  }
  async getFailedDeliveries(limit: number = 10): Promise<WaiterAssignment[]> {
    const response = await api.get('/waiter/dashboard/failed', {
      params: this.getTenantParams({ limit }),
    })
    return response.data.data
  }

  async getDeliveryTimeline(): Promise<DeliveryLog[]> {
    const response = await api.get('/waiter/dashboard/timeline', {
      params: this.getTenantParams(),
    })
    return response.data.data
  }

  async getQuickStats(): Promise<any> {
    try {
      const response = await api.get('/waiter/dashboard/quick-stats', {
        params: this.getTenantParams(),
      })
      return response.data.data
    } catch (err: any) {
      console.error('[WaiterService] Error fetching quick stats:', err)
      throw err
    }
  }

  async getAssignments(
    params: {
      status?: string
      date?: string
      search?: string
      sort_by?: string
      sort_order?: string
      per_page?: number
    } = {},
  ): Promise<{ data: WaiterAssignment[]; pagination: any }> {
    const response = await api.get('/waiter/assignments', {
      params: this.getTenantParams(params),
    })
    return {
      data: response.data.data,
      pagination: response.data.pagination,
    }
  }

  async getAssignment(id: string): Promise<WaiterAssignment> {
    const response = await api.get(`/waiter/assignments/${id}`, {
      params: this.getTenantParams(),
    })
    return response.data.data
  }

  async getPendingAssignments(): Promise<WaiterAssignment[]> {
    const response = await api.get('/waiter/assignments/pending/list', {
      params: this.getTenantParams(),
    })
    return response.data.data
  }

  async getActiveAssignments(): Promise<WaiterAssignment[]> {
    const response = await api.get('/waiter/assignments/active/list', {
      params: this.getTenantParams(),
    })
    return response.data.data
  }

  async getTodayAssignments(): Promise<WaiterAssignment[]> {
    const response = await api.get('/waiter/assignments/today/list', {
      params: this.getTenantParams(),
    })
    return response.data.data
  }

  async acceptAssignment(id: string): Promise<WaiterAssignment> {
    const response = await api.patch(`/waiter/assignments/${id}/accept`)
    return response.data.data
  }

  async rejectAssignment(id: string, reason?: string): Promise<WaiterAssignment> {
    const response = await api.patch(`/waiter/assignments/${id}/reject`, { reason })
    return response.data.data
  }

  async pickupOrder(id: string): Promise<WaiterAssignment> {
    const response = await api.patch(`/waiter/assignments/${id}/pickup`)
    return response.data.data
  }

  async startDelivery(id: string): Promise<WaiterAssignment> {
    const response = await api.patch(`/waiter/assignments/${id}/start-delivery`)
    return response.data.data
  }

  async deliverOrder(id: string, remarks?: string): Promise<WaiterAssignment> {
    const response = await api.patch(`/waiter/assignments/${id}/deliver`, { remarks })
    return response.data.data
  }

  async completeDelivery(id: string, remarks?: string): Promise<WaiterAssignment> {
    return this.deliverOrder(id, remarks)
  }

  async failDelivery(id: string, reason: string, remarks?: string): Promise<WaiterAssignment> {
    const response = await api.patch(`/waiter/assignments/${id}/failed`, {
      reason,
      remarks,
    })
    return response.data.data
  }

  async getHistory(
    params: {
      date?: string
      action?: string
      start_date?: string
      end_date?: string
      sort_by?: string
      sort_order?: string
      per_page?: number
    } = {},
  ): Promise<{ data: DeliveryLog[]; pagination: any }> {
    const response = await api.get('/waiter/history', { params })
    return {
      data: response.data.data,
      pagination: response.data.pagination,
    }
  }

  async exportHistory(filters: any = {}): Promise<Blob> {
    const response = await api.get('/waiter/history/export', {
      params: filters,
      responseType: 'blob',
    })
    return response.data
  }
  async getPerformanceHistory(params: {
    start_date: string
    end_date: string
    per_page?: number
  }): Promise<{ data: WaiterPerformance[]; pagination: any }> {
    const response = await api.get('/waiter/performance-history', { params })
    return {
      data: response.data.data,
      pagination: response.data.pagination,
    }
  }

  async getPerformanceReport(params: { start_date: string; end_date: string }): Promise<any> {
    const response = await api.get('/waiter/report/performance', { params })
    return response.data.data
  }

  async exportPerformanceReport(params: { start_date: string; end_date: string }): Promise<Blob> {
    const response = await api.get('/waiter/report/performance/export', {
      params,
      responseType: 'blob',
    })
    return response.data
  }

  async getPerformanceTrend(days: number = 7): Promise<any[]> {
    const response = await api.get('/waiter/report/trend', {
      params: { days },
    })
    return response.data.data
  }

  async getDeliveryTimeDistribution(date?: string): Promise<any> {
    const response = await api.get('/waiter/report/delivery-time-distribution', {
      params: { date },
    })
    return response.data.data
  }

  async getMonthlyAverage(month?: string): Promise<any> {
    const response = await api.get('/waiter/report/monthly-average', {
      params: { month },
    })
    return response.data.data
  }

  async getStatistics(): Promise<any> {
    const response = await api.get('/waiter/stats')
    return response.data.data
  }

  async getProfile(): Promise<WaiterProfile> {
    const response = await api.get('/waiter/profile')
    return response.data.data
  }

  async updateProfile(data: {
    name?: string
    email?: string
    phone?: string
    shift?: string
  }): Promise<WaiterProfile> {
    const response = await api.put('/waiter/profile', data)
    return response.data.data
  }

  async getProfilePerformance(): Promise<any> {
    const response = await api.get('/waiter/profile/performance')
    return response.data.data
  }

  async getRatings(days: number = 30): Promise<any[]> {
    const response = await api.get('/waiter/profile/ratings', {
      params: { days },
    })
    return response.data.data
  }

  async changePassword(data: {
    current_password: string
    new_password: string
    new_password_confirmation: string
  }): Promise<void> {
    await api.post('/waiter/profile/change-password', data)
  }

  async getShift(): Promise<any> {
    const response = await api.get('/waiter/profile/shift')
    return response.data.data
  }

  async getAvailability(): Promise<any> {
    const response = await api.get('/waiter/profile/availability')
    return response.data.data
  }

  async getSettings(): Promise<any> {
    const response = await api.get('/waiter/settings')
    return response.data.data
  }

  async updateSettings(data: {
    notifications_enabled?: boolean
    email_notifications?: boolean
    sms_notifications?: boolean
    theme?: string
    language?: string
  }): Promise<any> {
    const response = await api.put('/waiter/settings', data)
    return response.data.data
  }
}

export default new WaiterService()

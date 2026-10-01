import api from '@/api/auth'
import type {
  ManagerDashboardResponse,
  DashboardStatistics,
  RevenueSummary,
  OccupancySummary,
  ReservationSummary,
  StaffSummary,
  OrderSummary,
  RoomServiceDelivery,
  HousekeepingTask,
  LaundryRequest,
  NotificationItem,
  RecentActivity,
  RevenueChartItem,
  OccupancyChartItem,
} from '@/types/manager'

class ManagerService {
  async getDashboard(): Promise<ManagerDashboardResponse> {
    const response = await api.get('/manager/dashboard')
    return response.data.data
  }

  async getStatistics(): Promise<DashboardStatistics> {
    try {
      const response = await api.get('/manager/dashboard/statistics')

      const data = response.data?.data
      if (!data) {
        throw new Error('Invalid response structure - no data field')
      }

      const result = {
        totalReservations: data.reception?.total_reservations ?? 0,
        todayCheckIns: data.reception?.today_check_ins ?? 0,
        todayCheckOuts: data.reception?.today_check_outs ?? 0,
        availableRooms: data.reception?.available_rooms ?? 0,
        occupiedRooms: data.reception?.occupied_rooms ?? 0,

        totalRooms: data.occupancy?.total_rooms ?? 0,
        reservedRooms: 0,
        maintenanceRooms: 0,

        totalGuests: data.occupancy?.checked_in_guests ?? 0,
        checkedInGuests: data.occupancy?.checked_in_guests ?? 0,
        guestCheckouts: data.occupancy?.checked_out_guests ?? 0,

        pendingOrders: data.orders?.pending_orders ?? 0,
        preparingOrders: data.kitchen?.preparing_orders ?? 0,
        completedOrders: data.orders?.completed_orders ?? 0,

        pendingLaundry: data.laundry?.pending_requests ?? 0,
        pendingHousekeeping: data.housekeeping?.dirty_rooms ?? 0,

        activeStaff: data.waiters?.active_waiters ?? 0,
        pendingTasks: 0,

        todayRevenue: data.revenue?.daily_revenue ?? 0,
        monthlyRevenue: data.revenue?.monthly_revenue ?? 0,
      }

      return result
    } catch (err: any) {
      console.error('[ManagerService] Error fetching dashboard data:', err)
      throw err
    }
  }

  async getRevenueSummary(): Promise<RevenueSummary> {
    const response = await api.get('/manager/revenue/summary')
    return response.data.data
  }

  async getRevenueChart(
    period: 'weekly' | 'monthly' | 'yearly' = 'monthly',
  ): Promise<RevenueChartItem[]> {
    const response = await api.get('/manager/revenue/chart', {
      params: { period },
    })

    return response.data.data
  }

  async getOccupancySummary(): Promise<OccupancySummary> {
    const response = await api.get('/manager/occupancy/summary')
    return response.data.data
  }

  async getOccupancyChart(): Promise<OccupancyChartItem[]> {
    const response = await api.get('/manager/occupancy/chart')
    return response.data.data
  }

  async getReservationSummary(): Promise<ReservationSummary> {
    const response = await api.get('/manager/occupancy/reservations')
    return response.data.data
  }

  async getStaff(): Promise<StaffSummary[]> {
    const response = await api.get('/manager/staff')
    return response.data.data
  }

  async getRecentOrders(): Promise<OrderSummary[]> {
    const response = await api.get('/manager/operations/orders')
    return response.data.data
  }

  async getDeliveries(): Promise<RoomServiceDelivery[]> {
    const response = await api.get('/manager/operations/deliveries')
    return response.data.data
  }

  async getHousekeeping(): Promise<HousekeepingTask[]> {
    const response = await api.get('/manager/operations/housekeeping')
    return response.data.data
  }

  async getLaundryRequests(): Promise<LaundryRequest[]> {
    const response = await api.get('/manager/operations/laundry')
    return response.data.data
  }

  async getNotifications(): Promise<NotificationItem[]> {
    const response = await api.get('/manager/notifications')
    return response.data.data
  }

  async markNotificationAsRead(id: string): Promise<void> {
    await api.patch(`/manager/notifications/${id}/read`)
  }

  async getRecentActivities(): Promise<RecentActivity[]> {
    try {
      const response = await api.get('/manager/activities')
      const activities = response.data.data || []
      return activities
    } catch (err: any) {
      console.error('[ManagerService] Error fetching recent activities:', err)
      return []
    }
  }

  async refreshDashboard(): Promise<ManagerDashboardResponse> {
    const response = await api.get('/manager/dashboard')
    return response.data.data
  }

  async getWaiters(): Promise<any[]> {
    try {
      const response = await api.get('/manager/waiters')
      return response.data.data
    } catch (error: any) {
      console.error('[ManagerService] Error fetching waiters:', error)
      throw error
    }
  }

  async getWaiter(waiterId: string): Promise<any> {
    const response = await api.get(`/manager/waiters/${waiterId}`)
    return response.data.data
  }
  async createWaiter(data: {
    user_id?: string
    section: string
    shift: string
    status: string
    experience_level: string
    first_name?: string
    last_name?: string
    email?: string
    phone?: string
    password?: string
  }): Promise<any> {
    const response = await api.post('/manager/waiters', data)
    return response.data.data
  }
  async updateWaiter(waiterId: string, data: any): Promise<any> {
    const response = await api.put(`/manager/waiters/${waiterId}`, data)
    return response.data.data
  }

  async updateWaiterStatus(waiterId: string, status: string): Promise<void> {
    await api.patch(`/manager/waiters/${waiterId}/status`, { status })
  }

  async deleteWaiter(waiterId: string): Promise<void> {
    await api.delete(`/manager/waiters/${waiterId}`)
  }

  async getWaiterAssignments(waiterId: string): Promise<any[]> {
    const response = await api.get(`/manager/waiters/${waiterId}/assignments`)
    return response.data.data
  }

  async getWaiterPerformance(waiterId: string): Promise<any> {
    const response = await api.get(`/manager/waiters/${waiterId}/performance`)
    return response.data.data
  }

  async getAvailableUsers(): Promise<any[]> {
    const response = await api.get('/manager/waiters/available-users')
    return response.data.data
  }

  async assignWaiterToDelivery(waiterId: string, deliveryId: string): Promise<void> {
    await api.patch(`/manager/waiters/${waiterId}/assign`, { deliveryId })
  }
}

export default new ManagerService()

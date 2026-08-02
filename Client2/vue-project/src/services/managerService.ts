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
  /*
  |--------------------------------------------------------------------------
  | Dashboard
  |--------------------------------------------------------------------------
  */

  async getDashboard(): Promise<ManagerDashboardResponse> {
    const response = await api.get('/manager/dashboard')
    return response.data.data
  }

  /*
  |--------------------------------------------------------------------------
  | Statistics
  |--------------------------------------------------------------------------
  */

  async getStatistics(): Promise<DashboardStatistics> {
    try {
      console.log('\n>>> [managerService.getStatistics] START')
      console.log('[managerService] 🚀 Starting fetch from /manager/dashboard/statistics')
      const response = await api.get('/manager/dashboard/statistics')

      console.log('[managerService] ✅ Raw response received from API')
      console.log('[managerService] Response status:', response.status)
      console.log('[managerService] Response config URL:', response.config.url)
      console.log('[managerService] Response data type:', typeof response.data)
      console.log('[managerService] Response data keys:', Object.keys(response.data))
      console.log('[managerService] Full response.data:', response.data)

      const data = response.data?.data
      if (!data) {
        console.error('[managerService] ❌ No data in response:', response.data)
        throw new Error('Invalid response structure - no data field')
      }

      console.log('[managerService] ✅ Data extracted from response')
      console.log('[managerService] Data structure:', {
        hasReception: !!data.reception,
        hasOccupancy: !!data.occupancy,
        hasRevenue: !!data.revenue,
        hasOrders: !!data.orders,
        hasKitchen: !!data.kitchen,
        hasWaiters: !!data.waiters,
      })

      const result = {
        // Reception Monitoring (5 Key Metrics)
        totalReservations: data.reception?.total_reservations ?? 0,
        todayCheckIns: data.reception?.today_check_ins ?? 0,
        todayCheckOuts: data.reception?.today_check_outs ?? 0,
        availableRooms: data.reception?.available_rooms ?? 0,
        occupiedRooms: data.reception?.occupied_rooms ?? 0,

        // Room Statistics
        totalRooms: data.occupancy?.total_rooms ?? 0,
        reservedRooms: 0,
        maintenanceRooms: 0,

        // Guest Statistics
        totalGuests: data.occupancy?.checked_in_guests ?? 0,
        checkedInGuests: data.occupancy?.checked_in_guests ?? 0,
        guestCheckouts: data.occupancy?.checked_out_guests ?? 0,

        // Order Statistics
        pendingOrders: data.orders?.pending_orders ?? 0,
        preparingOrders: data.kitchen?.preparing_orders ?? 0,
        completedOrders: data.orders?.completed_orders ?? 0,

        // Operational Statistics
        pendingLaundry: data.laundry?.pending_requests ?? 0,
        pendingHousekeeping: data.housekeeping?.dirty_rooms ?? 0,

        // Staff Statistics
        activeStaff: data.waiters?.active_waiters ?? 0,
        pendingTasks: 0,

        // Revenue
        todayRevenue: data.revenue?.daily_revenue ?? 0,
        monthlyRevenue: data.revenue?.monthly_revenue ?? 0,
      }

      console.log('[managerService] ✅ Mapped result:')
      console.log('[managerService] result =', result)
      console.log('>>> [managerService.getStatistics] COMPLETE\n')
      return result
    } catch (err: any) {
      console.error('\n>>> [managerService.getStatistics] ERROR')
      console.error('[managerService] ❌ Error occurred:', err)
      console.error('[managerService] Error type:', err.constructor.name)
      console.error('[managerService] Error message:', err.message)
      console.error('[managerService] Error stack:', err.stack)
      if (err.response) {
        console.error('[managerService] Response status:', err.response.status)
        console.error('[managerService] Response data:', err.response.data)
      }
      console.log('>>> [managerService.getStatistics] ERROR END\n')
      throw err
    }
  }

  /*
  |--------------------------------------------------------------------------
  | Revenue
  |--------------------------------------------------------------------------
  */

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

  /*
  |--------------------------------------------------------------------------
  | Occupancy
  |--------------------------------------------------------------------------
  */

  async getOccupancySummary(): Promise<OccupancySummary> {
    const response = await api.get('/manager/occupancy/summary')
    return response.data.data
  }

  async getOccupancyChart(): Promise<OccupancyChartItem[]> {
    const response = await api.get('/manager/occupancy/chart')
    return response.data.data
  }

  /*
  |--------------------------------------------------------------------------
  | Reservations
  |--------------------------------------------------------------------------
  */

  async getReservationSummary(): Promise<ReservationSummary> {
    const response = await api.get('/manager/occupancy/reservations')
    return response.data.data
  }

  /*
  |--------------------------------------------------------------------------
  | Staff Monitoring
  |--------------------------------------------------------------------------
  */

  async getStaff(): Promise<StaffSummary[]> {
    const response = await api.get('/manager/staff')
    return response.data.data
  }

  /*
  |--------------------------------------------------------------------------
  | Restaurant Orders
  |--------------------------------------------------------------------------
  */

  async getRecentOrders(): Promise<OrderSummary[]> {
    const response = await api.get('/manager/operations/orders')
    return response.data.data
  }

  /*
  |--------------------------------------------------------------------------
  | Room Service Delivery
  |--------------------------------------------------------------------------
  */

  async getDeliveries(): Promise<RoomServiceDelivery[]> {
    const response = await api.get('/manager/operations/deliveries')
    return response.data.data
  }

  /*
  |--------------------------------------------------------------------------
  | Housekeeping
  |--------------------------------------------------------------------------
  */

  async getHousekeeping(): Promise<HousekeepingTask[]> {
    const response = await api.get('/manager/operations/housekeeping')
    return response.data.data
  }

  /*
  |--------------------------------------------------------------------------
  | Laundry
  |--------------------------------------------------------------------------
  */

  async getLaundryRequests(): Promise<LaundryRequest[]> {
    const response = await api.get('/manager/operations/laundry')
    return response.data.data
  }

  /*
  |--------------------------------------------------------------------------
  | Notifications
  |--------------------------------------------------------------------------
  */

  async getNotifications(): Promise<NotificationItem[]> {
    const response = await api.get('/manager/notifications')
    return response.data.data
  }

  async markNotificationAsRead(id: string): Promise<void> {
    await api.patch(`/manager/notifications/${id}/read`)
  }

  /*
  |--------------------------------------------------------------------------
  | Activities
  |--------------------------------------------------------------------------
  */

  async getRecentActivities(): Promise<RecentActivity[]> {
    try {
      console.log('\n>>> [managerService.getRecentActivities] START')
      console.log('[managerService] 🚀 Fetching activities from /manager/activities...')
      const response = await api.get('/manager/activities')
      console.log('[managerService] ✅ Response received')
      console.log('[managerService] Response status:', response.status)
      console.log('[managerService] Response data:', response.data)
      const activities = response.data.data || []
      console.log('[managerService] ✅ Activities extracted:', activities.length, 'items')
      console.log('[managerService] Activities:', activities)
      console.log('>>> [managerService.getRecentActivities] COMPLETE\n')
      return activities
    } catch (err: any) {
      console.error('\n>>> [managerService.getRecentActivities] ERROR')
      console.error('[managerService] ❌ Error:', err)
      console.error('[managerService] Error message:', err.message)
      if (err.response) {
        console.error('[managerService] Response status:', err.response.status)
        console.error('[managerService] Response data:', err.response.data)
      }
      console.log('>>> [managerService.getRecentActivities] ERROR END\n')
      return []
    }
  }

  /*
  |--------------------------------------------------------------------------
  | Refresh Dashboard
  |--------------------------------------------------------------------------
  */

  async refreshDashboard(): Promise<ManagerDashboardResponse> {
    const response = await api.get('/manager/dashboard')
    return response.data.data
  }

  /*
  |--------------------------------------------------------------------------
  | Waiters
  |--------------------------------------------------------------------------
  */

  async getWaiters(): Promise<any[]> {
    try {
      console.log('[ManagerService] getWaiters() - calling /manager/waiters endpoint')
      const response = await api.get('/manager/waiters')
      console.log('[ManagerService] getWaiters() response:', response.data)
      return response.data.data
    } catch (error: any) {
      console.error('[ManagerService] getWaiters() error:', error)
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
    console.log('[ManagerService] Creating waiter with data:', data)
    console.log('[ManagerService] Data keys:', Object.keys(data))
    console.log('[ManagerService] Posting to:', '/manager/waiters')
    const response = await api.post('manager/waiters', data)
    console.log('[ManagerService] Response:', response.data)
    return response.data.data
  }
  async updateWaiter(waiterId: string, data: {
    section?: string
    shift?: string
    status?: string
    experience_level?: string
  }): Promise<any> {
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

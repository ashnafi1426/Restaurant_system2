import { defineStore } from 'pinia'
import { ref, computed } from 'vue'

import managerService from '@/services/managerService'
import { getErrorMessage } from '@/utils/error'

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
  Waiter,
  WaiterStatus,
} from '@/types/manager'

function createEmptyDashboardStats() {
  return {
    totalReservations: 0,
    todayCheckIns: 0,
    todayCheckOuts: 0,
    availableRooms: 0,
    occupiedRooms: 0,
    totalRooms: 0,
    activeStaff: 0,
    todayRevenue: 0,
    preparingOrders: 0,
    completedOrders: 0,
  }
}

export const useManagerStore = defineStore('manager', () => {
  const dashboard = ref<ManagerDashboardResponse | null>(null)
  const statistics = ref<DashboardStatistics | null>(null)
  const revenueSummary = ref<RevenueSummary | null>(null)
  const occupancySummary = ref<OccupancySummary | null>(null)
  const reservationSummary = ref<ReservationSummary | null>(null)
  const revenueChart = ref<RevenueChartItem[]>([])
  const occupancyChart = ref<OccupancyChartItem[]>([])
  const staff = ref<StaffSummary[]>([])
  const orders = ref<OrderSummary[]>([])
  const deliveries = ref<RoomServiceDelivery[]>([])
  const housekeeping = ref<HousekeepingTask[]>([])
  const laundryRequests = ref<LaundryRequest[]>([])
  const notifications = ref<NotificationItem[]>([])
  const activities = ref<RecentActivity[]>([])
  const waiters = ref<Waiter[]>([])

  const dashboardStats = ref(createEmptyDashboardStats())
  const dashboardActivities = ref<any[]>([])

  const loading = ref(false)
  const loadingRevenue = ref(false)
  const loadingStaff = ref(false)
  const loadingOrders = ref(false)
  const loadingNotifications = ref(false)
  const dashboardLoading = ref(false)
  const dashboardActivityLoading = ref(false)
  const error = ref<string | null>(null)
  const dashboardError = ref<string | null>(null)

  const unreadNotifications = computed(() =>
    notifications.value.filter((notification) => !notification.read_at),
  )

  const pendingOrders = computed(() => orders.value.filter((order) => order.status === 'pending'))

  const preparingOrders = computed(() =>
    orders.value.filter((order) => order.status === 'preparing'),
  )

  const readyOrders = computed(() => orders.value.filter((order) => order.status === 'ready'))

  const activeDeliveries = computed(() =>
    deliveries.value.filter((delivery) => delivery.status !== 'delivered'),
  )

  const pendingLaundry = computed(() =>
    laundryRequests.value.filter((item) => item.status !== 'completed'),
  )

  const availableStaffCount = computed(
    () => staff.value.filter((employee) => employee.status === 'active').length,
  )

  const occupancy = computed(
    () =>
      occupancySummary.value || {
        totalRooms: 0,
        occupiedRooms: 0,
        availableRooms: 0,
        reservedRooms: 0,
        maintenanceRooms: 0,
        occupancyRate: 0,
      },
  )

  const safeStatistics = computed(
    () =>
      statistics.value || {
        totalReservations: 0,
        todayCheckIns: 0,
        todayCheckOuts: 0,
        availableRooms: 0,
        occupiedRooms: 0,
        totalRooms: 0,
        reservedRooms: 0,
        maintenanceRooms: 0,
        totalGuests: 0,
        checkedInGuests: 0,
        guestCheckouts: 0,
        pendingOrders: 0,
        preparingOrders: 0,
        completedOrders: 0,
        pendingLaundry: 0,
        pendingHousekeeping: 0,
        activeStaff: 0,
        pendingTasks: 0,
        todayRevenue: 0,
        monthlyRevenue: 0,
      },
  )

  const occupancyData = computed(() => ({
    totalRooms: occupancySummary.value?.totalRooms ?? 0,
    occupiedRooms: occupancySummary.value?.occupiedRooms ?? 0,
    availableRooms: occupancySummary.value?.availableRooms ?? 0,
    todayCheckIns: 0,
    todayCheckOuts: 0,
  }))

  const revenue = computed(() => ({
    today: revenueSummary.value?.today ?? 0,
    yesterday: revenueSummary.value?.yesterday ?? 0,
    week: revenueSummary.value?.thisWeek ?? 0,
    month: revenueSummary.value?.thisMonth ?? 0,
    year: revenueSummary.value?.thisYear ?? 0,
    rooms: 0,
    restaurant: 0,
    roomService: 0,
    laundry: 0,
  }))

  async function loadDashboard() {
    try {
      loading.value = true
      error.value = null
      const response = await managerService.getDashboard()
      dashboard.value = response
    } catch (err: any) {
      console.error('[managerStore] Failed loading dashboard:', err)
      error.value = err.message || 'Failed loading dashboard'
    } finally {
      loading.value = false
    }
  }

  async function loadStatistics() {
    try {
      const response = await managerService.getStatistics()

      dashboardStats.value = {
        totalReservations: response.totalReservations,
        todayCheckIns: response.todayCheckIns,
        todayCheckOuts: response.todayCheckOuts,
        availableRooms: response.availableRooms,
        occupiedRooms: response.occupiedRooms,
        totalRooms: response.totalRooms,
        activeStaff: response.activeStaff,
        todayRevenue: response.todayRevenue,
        preparingOrders: response.preparingOrders,
        completedOrders: response.completedOrders,
      }

      statistics.value = response
    } catch (err: any) {
      console.error('[managerStore] Failed loading statistics:', err)
      dashboardError.value = getErrorMessage(err, 'Failed to load manager dashboard statistics')
    }
  }

  async function loadRevenueSummary() {
    try {
      loadingRevenue.value = true
      revenueSummary.value = await managerService.getRevenueSummary()
    } finally {
      loadingRevenue.value = false
    }
  }

  async function loadRevenueChart(period: 'weekly' | 'monthly' | 'yearly' = 'monthly') {
    revenueChart.value = await managerService.getRevenueChart(period)
  }

  async function loadOccupancy() {
    occupancySummary.value = await managerService.getOccupancySummary()
    occupancyChart.value = await managerService.getOccupancyChart()
  }

  async function loadReservations() {
    reservationSummary.value = await managerService.getReservationSummary()
  }

  async function loadStaff() {
    try {
      loadingStaff.value = true
      staff.value = await managerService.getStaff()
    } finally {
      loadingStaff.value = false
    }
  }

  async function loadOrders() {
    try {
      loadingOrders.value = true
      orders.value = await managerService.getRecentOrders()
    } finally {
      loadingOrders.value = false
    }
  }

  async function loadDeliveries() {
    deliveries.value = await managerService.getDeliveries()
  }

  async function loadHousekeeping() {
    housekeeping.value = await managerService.getHousekeeping()
  }

  async function loadLaundry() {
    laundryRequests.value = await managerService.getLaundryRequests()
  }

  async function loadNotifications() {
    try {
      loadingNotifications.value = true
      notifications.value = await managerService.getNotifications()
    } finally {
      loadingNotifications.value = false
    }
  }

  async function markNotificationRead(id: string) {
    await managerService.markNotificationAsRead(id)
    const notification = notifications.value.find((item) => item.id === id)
    if (notification) {
      notification.read_at = new Date().toISOString()
    }
  }

  async function loadActivities() {
    try {
      dashboardActivityLoading.value = true
      dashboardActivities.value = await managerService.getRecentActivities()
      activities.value = dashboardActivities.value
    } catch (err: any) {
      console.error('[managerStore] Failed loading activities:', err)
      dashboardError.value = getErrorMessage(err, 'Failed to load recent activities')
    } finally {
      dashboardActivityLoading.value = false
    }
  }

  async function loadWaiters() {
    try {
      waiters.value = await managerService.getWaiters()
    } catch (err: any) {
      console.error('[managerStore] Failed loading waiters:', err)
      error.value = getErrorMessage(err, 'Failed to load waiters')
    }
  }

  async function addWaiter(data: {
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
  }) {
    try {
      const newWaiter = await managerService.createWaiter(data)
      waiters.value.push(newWaiter)
    } catch (err: any) {
      console.error('[managerStore] Failed creating waiter:', err)
      error.value = getErrorMessage(err, 'Failed to create waiter')
      throw err
    }
  }

  async function updateWaiterStatus(waiterId: string, status: WaiterStatus | string) {
    try {
      await managerService.updateWaiterStatus(waiterId, status)
      const waiter = waiters.value.find((w) => w.id === waiterId)
      if (waiter) {
        waiter.status = status as WaiterStatus
      }
    } catch (err: any) {
      console.error('[managerStore] Failed updating waiter status:', err)
      error.value = getErrorMessage(err, 'Failed to update waiter status')
      throw err
    }
  }

  async function deleteWaiter(waiterId: string) {
    try {
      await managerService.deleteWaiter(waiterId)
      waiters.value = waiters.value.filter((w) => w.id !== waiterId)
    } catch (err: any) {
      console.error('[managerStore] Failed deleting waiter:', err)
      error.value = getErrorMessage(err, 'Failed to delete waiter')
      throw err
    }
  }

  async function initializeManagerDashboard() {
    try {
      dashboardLoading.value = true
      dashboardError.value = null

      await loadStatistics()
      await loadActivities()
    } catch (err: any) {
      console.error('[managerStore] Failed initializing manager dashboard:', err)
      dashboardError.value = getErrorMessage(err, 'Failed to initialize manager dashboard')
    } finally {
      dashboardLoading.value = false
    }
  }

  async function loadAllManagerData() {
    await Promise.allSettled([
      loadDashboard(),
      loadStatistics(),
      loadRevenueSummary(),
      loadRevenueChart(),
      loadOccupancy(),
      loadReservations(),
      loadStaff(),
      loadOrders(),
      loadDeliveries(),
      loadHousekeeping(),
      loadLaundry(),
      loadNotifications(),
      loadActivities(),
      loadWaiters(),
    ])
  }

  async function refresh() {
    await initializeManagerDashboard()
  }

  function reset() {
    dashboard.value = null
    statistics.value = null
    revenueSummary.value = null
    occupancySummary.value = null
    reservationSummary.value = null
    revenueChart.value = []
    occupancyChart.value = []
    staff.value = []
    orders.value = []
    deliveries.value = []
    housekeeping.value = []
    laundryRequests.value = []
    notifications.value = []
    activities.value = []
    waiters.value = []
    dashboardStats.value = createEmptyDashboardStats()
    dashboardActivities.value = []
    error.value = null
    dashboardError.value = null
  }

  return {
    dashboard,
    statistics,
    revenueSummary,
    occupancySummary,
    reservationSummary,
    revenueChart,
    occupancyChart,
    staff,
    orders,
    deliveries,
    housekeeping,
    laundryRequests,
    notifications,
    activities,
    waiters,

    dashboardStats,
    dashboardActivities,

    loading,
    loadingRevenue,
    loadingStaff,
    loadingOrders,
    loadingNotifications,
    dashboardLoading,
    dashboardActivityLoading,
    error,
    dashboardError,

    unreadNotifications,
    pendingOrders,
    preparingOrders,
    preparingOrdersComputed: preparingOrders,
    readyOrders,
    activeDeliveries,
    pendingLaundry,
    availableStaffCount,
    occupancy,
    occupancyData,
    revenue,
    safeStatistics,
    statisticsWithDefaults: safeStatistics,

    loadDashboard,
    loadStatistics,
    loadRevenueSummary,
    loadRevenueChart,
    loadOccupancy,
    loadReservations,
    loadStaff,
    loadOrders,
    loadDeliveries,
    loadHousekeeping,
    loadLaundry,
    loadNotifications,
    markNotificationRead,
    loadActivities,
    loadWaiters,
    addWaiter,
    updateWaiterStatus,
    deleteWaiter,
    initializeManagerDashboard,
    loadAllManagerData,
    initializeFullManager: loadAllManagerData,
    refresh,
    reset,
  }
})

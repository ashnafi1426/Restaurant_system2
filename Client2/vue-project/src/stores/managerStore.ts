import { defineStore } from 'pinia'
import { ref, computed } from 'vue'

import managerService from '@/services/managerService'

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
} from '@/types/manager'

export const useManagerStore = defineStore('manager', () => {
  /*
  |--------------------------------------------------------------------------
  | STATE
  |--------------------------------------------------------------------------
  */

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

  /*
  |--------------------------------------------------------------------------
  | DASHBOARD SPECIFIC STATE
  |--------------------------------------------------------------------------
  */

  const dashboardStats = ref({
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
  })

  const dashboardActivities = ref<any[]>([])

  /*
  |--------------------------------------------------------------------------
  | UI STATES
  |--------------------------------------------------------------------------
  */

  const loading = ref(false)
  const loadingRevenue = ref(false)
  const loadingStaff = ref(false)
  const loadingOrders = ref(false)
  const loadingNotifications = ref(false)
  const dashboardLoading = ref(false)
  const dashboardActivityLoading = ref(false)
  const error = ref<string | null>(null)
  const dashboardError = ref<string | null>(null)

  /*
  |--------------------------------------------------------------------------
  | COMPUTED
  |--------------------------------------------------------------------------
  */

  const unreadNotifications = computed(() =>
    notifications.value.filter((notification) => !notification.read_at),
  )

  const pendingOrders = computed(() => orders.value.filter((order) => order.status === 'pending'))

  const preparingOrdersComputed = computed(() =>
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
    week: revenueSummary.value?.thisWeek ?? 0,
    month: revenueSummary.value?.thisMonth ?? 0,
    rooms: 0,
    restaurant: 0,
    roomService: 0,
    laundry: 0,
  }))

  /*
  |--------------------------------------------------------------------------
  | LOAD COMPLETE DASHBOARD
  |--------------------------------------------------------------------------
  */

  async function loadDashboard() {
    try {
      loading.value = true
      error.value = null
      const response = await managerService.getDashboard()
      dashboard.value = response
    } catch (err: any) {
      error.value = err.message || 'Failed loading dashboard'
    } finally {
      loading.value = false
    }
  }

  /*
  |--------------------------------------------------------------------------
  | LOAD STATISTICS
  |--------------------------------------------------------------------------
  */

  async function loadStatistics() {
    try {
      console.log('\n>>> [managerStore.loadStatistics] START')
      console.log('[managerStore] 🚀 Loading statistics from service...')
      
      const response = await managerService.getStatistics()

      console.log('[managerStore] ✅ Response received from service')
      console.log('[managerStore] Response type:', typeof response)
      console.log('[managerStore] Response keys:', Object.keys(response))
      console.log('[managerStore] Full response:', response)

      // Update dashboard stats ref
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

      // Also update statistics ref for backward compatibility
      statistics.value = response

      console.log('[managerStore] ✅ Dashboard stats updated:')
      console.log('[managerStore] dashboardStats.value =', dashboardStats.value)
      console.log('[managerStore] statistics.value =', statistics.value)
      console.log('>>> [managerStore.loadStatistics] COMPLETE\n')
    } catch (err: any) {
      console.error('[managerStore] ❌ Error loading statistics:', err)
      console.error('[managerStore] Error message:', err.message)
      console.error('[managerStore] Error stack:', err.stack)
      dashboardError.value = err.message
    }
  }

  /*
  |--------------------------------------------------------------------------
  | REVENUE
  |--------------------------------------------------------------------------
  */

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

  /*
  |--------------------------------------------------------------------------
  | OCCUPANCY
  |--------------------------------------------------------------------------
  */

  async function loadOccupancy() {
    occupancySummary.value = await managerService.getOccupancySummary()
    occupancyChart.value = await managerService.getOccupancyChart()
  }

  /*
  |--------------------------------------------------------------------------
  | RESERVATION
  |--------------------------------------------------------------------------
  */

  async function loadReservations() {
    reservationSummary.value = await managerService.getReservationSummary()
  }

  /*
  |--------------------------------------------------------------------------
  | STAFF
  |--------------------------------------------------------------------------
  */

  async function loadStaff() {
    try {
      loadingStaff.value = true
      staff.value = await managerService.getStaff()
    } finally {
      loadingStaff.value = false
    }
  }

  /*
  |--------------------------------------------------------------------------
  | RESTAURANT ORDERS
  |--------------------------------------------------------------------------
  */

  async function loadOrders() {
    try {
      loadingOrders.value = true
      orders.value = await managerService.getRecentOrders()
    } finally {
      loadingOrders.value = false
    }
  }

  /*
  |--------------------------------------------------------------------------
  | ROOM SERVICE DELIVERY
  |--------------------------------------------------------------------------
  */

  async function loadDeliveries() {
    deliveries.value = await managerService.getDeliveries()
  }

  /*
  |--------------------------------------------------------------------------
  | HOUSEKEEPING
  |--------------------------------------------------------------------------
  */

  async function loadHousekeeping() {
    housekeeping.value = await managerService.getHousekeeping()
  }

  /*
  |--------------------------------------------------------------------------
  | LAUNDRY
  |--------------------------------------------------------------------------
  */

  async function loadLaundry() {
    laundryRequests.value = await managerService.getLaundryRequests()
  }

  /*
  |--------------------------------------------------------------------------
  | NOTIFICATIONS
  |--------------------------------------------------------------------------
  */

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

  /*
  |--------------------------------------------------------------------------
  | ACTIVITIES
  |--------------------------------------------------------------------------
  */

  async function loadActivities() {
    try {
      console.log('\n>>> [managerStore.loadActivities] START')
      console.log('[managerStore] 🚀 Loading activities...')
      dashboardActivityLoading.value = true
      dashboardActivities.value = await managerService.getRecentActivities()
      activities.value = dashboardActivities.value
      console.log('[managerStore] ✅ Activities loaded:', dashboardActivities.value.length, 'items')
      console.log('[managerStore] Activities:', dashboardActivities.value)
      console.log('>>> [managerStore.loadActivities] COMPLETE\n')
    } catch (err: any) {
      console.error('[managerStore] ❌ Error loading activities:', err)
      dashboardError.value = err.message
    } finally {
      dashboardActivityLoading.value = false
    }
  }

  /*
  |--------------------------------------------------------------------------
  | WAITERS
  |--------------------------------------------------------------------------
  */

  async function loadWaiters() {
    try {
      waiters.value = await managerService.getWaiters()
    } catch (err: any) {
      error.value = err.message
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
      error.value = err.message
    }
  }

  async function updateWaiterStatus(waiterId: string, status: string) {
    try {
      await managerService.updateWaiterStatus(waiterId, status)
      const waiter = waiters.value.find((w) => w.id === waiterId)
      if (waiter) {
        waiter.status = status
      }
    } catch (err: any) {
      error.value = err.message
    }
  }

  async function deleteWaiter(waiterId: string) {
    try {
      await managerService.deleteWaiter(waiterId)
      waiters.value = waiters.value.filter((w) => w.id !== waiterId)
    } catch (err: any) {
      error.value = err.message
    }
  }

  /*
  |--------------------------------------------------------------------------
  | LOAD DASHBOARD ONLY (for ManagerDashboard component)
  |--------------------------------------------------------------------------
  */

  async function initializeManagerDashboard() {
    try {
      console.log('\n========== [managerStore.initializeManagerDashboard] START ==========')
      console.log('[managerStore] 🚀 Initializing dashboard...')
      dashboardLoading.value = true
      dashboardError.value = null

      console.log('[managerStore] Step 1/2: Loading statistics...')
      await loadStatistics()
      console.log('[managerStore] ✅ Statistics loaded')

      console.log('[managerStore] Step 2/2: Loading activities...')
      await loadActivities()
      console.log('[managerStore] ✅ Activities loaded')

      console.log('[managerStore] ✅ Dashboard initialized successfully')
      console.log('========== [managerStore.initializeManagerDashboard] COMPLETE ==========\n')
    } catch (err: any) {
      console.error('\n========== [managerStore.initializeManagerDashboard] ERROR ==========')
      console.error('[managerStore] ❌ Error initializing dashboard:', err)
      console.error('[managerStore] Error message:', err.message)
      dashboardError.value = err.message
      console.log('========== [managerStore.initializeManagerDashboard] ERROR END ==========\n')
    } finally {
      dashboardLoading.value = false
    }
  }

  /*
  |--------------------------------------------------------------------------
  | LOAD EVERYTHING
  |--------------------------------------------------------------------------
  */

  async function initializeFullManager() {
    await Promise.all([
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

  /*
  |--------------------------------------------------------------------------
  | REFRESH
  |--------------------------------------------------------------------------
  */

  async function refresh() {
    await initializeManagerDashboard()
  }

  /*
  |--------------------------------------------------------------------------
  | RESET STORE
  |--------------------------------------------------------------------------
  */

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
    dashboardStats.value = {
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
    dashboardActivities.value = []
    error.value = null
    dashboardError.value = null
  }

  return {
    // state
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

    // dashboard specific
    dashboardStats,
    dashboardActivities,

    // loading
    loading,
    loadingRevenue,
    loadingStaff,
    loadingOrders,
    loadingNotifications,
    dashboardLoading,
    dashboardActivityLoading,
    error,
    dashboardError,

    // computed
    unreadNotifications,
    pendingOrders,
    preparingOrdersComputed,
    readyOrders,
    activeDeliveries,
    pendingLaundry,
    availableStaffCount,
    occupancy,
    occupancyData,
    revenue,
    safeStatistics,

    // actions
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
    initializeFullManager,
    refresh,
    reset,
  }
})

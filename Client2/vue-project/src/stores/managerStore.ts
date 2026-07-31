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

export const useManagerStore = defineStore(
  'manager',
  () => {
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
    | UI STATES
    |--------------------------------------------------------------------------
    */

    const loading = ref(false)

    const loadingRevenue = ref(false)

    const loadingStaff = ref(false)

    const loadingOrders = ref(false)

    const loadingNotifications = ref(false)

    const error = ref<string | null>(null)

    /*
    |--------------------------------------------------------------------------
    | COMPUTED
    |--------------------------------------------------------------------------
    */

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

    // Expose occupancy at top level for components
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

    // Expose statistics at top level for components with safe defaults
    const safeStatistics = computed(
      () =>
        statistics.value || {
          // Reception Monitoring (5 Key Metrics)
          totalReservations: 0,
          todayCheckIns: 0,
          todayCheckOuts: 0,
          availableRooms: 0,
          occupiedRooms: 0,
          // Room Statistics
          totalRooms: 0,
          reservedRooms: 0,
          maintenanceRooms: 0,
          // Guest Statistics
          totalGuests: 0,
          checkedInGuests: 0,
          guestCheckouts: 0,
          // Order Statistics
          pendingOrders: 0,
          preparingOrders: 0,
          completedOrders: 0,
          // Operational Statistics
          pendingLaundry: 0,
          pendingHousekeeping: 0,
          // Staff Statistics
          activeStaff: 0,
          pendingTasks: 0,
          // Revenue
          todayRevenue: 0,
          monthlyRevenue: 0,
        },
    )

    // Map occupancy data to expected format for components
    const occupancyData = computed(() => ({
      totalRooms: occupancySummary.value?.totalRooms ?? 0,
      occupiedRooms: occupancySummary.value?.occupiedRooms ?? 0,
      availableRooms: occupancySummary.value?.availableRooms ?? 0,
      todayCheckIns: 0, // To be populated from API
      todayCheckOuts: 0, // To be populated from API
    }))

    // Expose revenue at top level for components
    const revenue = computed(() => ({
      today: revenueSummary.value?.today ?? 0,
      week: revenueSummary.value?.thisWeek ?? 0,
      month: revenueSummary.value?.thisMonth ?? 0,
      rooms: 0, // To be populated from API
      restaurant: 0, // To be populated from API
      roomService: 0, // To be populated from API
      laundry: 0, // To be populated from API
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
        console.log('[managerStore.loadStatistics] Loading statistics from service...')
        const response = await managerService.getStatistics()

        console.log('[managerStore.loadStatistics] Response received:', response)
        statistics.value = response
        console.log('[managerStore.loadStatistics]  Statistics stored in manager store:', statistics.value)
      } catch (err: any) {
        console.error('[managerStore.loadStatistics] ❌ Error loading statistics:', err)
        error.value = err.message
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
      activities.value = await managerService.getRecentActivities()
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

    async function addWaiter(data: { name: string; phone: string; shift: string }) {
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
    | LOAD EVERYTHING
    |--------------------------------------------------------------------------
    */

    async function initializeManagerDashboard() {
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

      error.value = null
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

      // loading

      loading,

      loadingRevenue,

      loadingStaff,

      loadingOrders,

      loadingNotifications,

      error,

      // computed

      unreadNotifications,

      pendingOrders,

      preparingOrders,

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

      refresh,

      reset,
    }
  },

  {
    persist: true,
  },
)

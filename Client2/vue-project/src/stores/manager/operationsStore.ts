import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import managerService from '@/services/managerService'
import type {
  OrderSummary,
  RoomServiceDelivery,
  HousekeepingTask,
  LaundryRequest,
} from '@/types/manager'

export const useManagerOperationsStore = defineStore('managerOperations', () => {
  const orders = ref<OrderSummary[]>([])
  const deliveries = ref<RoomServiceDelivery[]>([])
  const housekeeping = ref<HousekeepingTask[]>([])
  const laundryRequests = ref<LaundryRequest[]>([])
  const loading = ref(false)
  const error = ref<string | null>(null)
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

  const operationsStats = computed(() => ({
    totalOrders: orders.value.length,
    pendingOrders: pendingOrders.value.length,
    preparingOrders: preparingOrders.value.length,
    readyOrders: readyOrders.value.length,
    activeDeliveries: activeDeliveries.value.length,
    housekeepingTasks: housekeeping.value.length,
    laundryRequests: laundryRequests.value.length,
    pendingLaundry: pendingLaundry.value.length,
  }))

  /*
  |--------------------------------------------------------------------------
  | ACTIONS
  |--------------------------------------------------------------------------
  */

  async function loadOrders() {
    try {
      loading.value = true
      error.value = null
      orders.value = await managerService.getRecentOrders()
    } catch (err: any) {
      error.value = err.message
    } finally {
      loading.value = false
    }
  }

  async function loadDeliveries() {
    try {
      deliveries.value = await managerService.getDeliveries()
    } catch (err: any) {
      error.value = err.message
    }
  }

  async function loadHousekeeping() {
    try {
      housekeeping.value = await managerService.getHousekeeping()
    } catch (err: any) {
      error.value = err.message
    }
  }

  async function loadLaundry() {
    try {
      laundryRequests.value = await managerService.getLaundryRequests()
    } catch (err: any) {
      error.value = err.message
    }
  }

  async function initialize() {
    await Promise.all([loadOrders(), loadDeliveries(), loadHousekeeping(), loadLaundry()])
  }

  function reset() {
    orders.value = []
    deliveries.value = []
    housekeeping.value = []
    laundryRequests.value = []
    error.value = null
  }

  return {
    orders,
    deliveries,
    housekeeping,
    laundryRequests,
    loading,
    error,
    pendingOrders,
    preparingOrders,
    readyOrders,
    activeDeliveries,
    pendingLaundry,
    operationsStats,
    loadOrders,
    loadDeliveries,
    loadHousekeeping,
    loadLaundry,
    initialize,
    reset,
  }
})

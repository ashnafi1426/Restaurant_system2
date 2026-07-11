import { defineStore } from 'pinia'
import { computed, ref } from 'vue'

import orderService from '@/services/orderService'

import type {
  Order,
  OrderFilters,
  OrderStatistics,
  OrderStatus,
  CreateOrderRequest,
  UpdateOrderRequest,
} from '@/types/order'

export const useOrderStore = defineStore('order', () => {
  const orders = ref<Order[]>([])
  const selectedOrder = ref<Order | null>(null)

  const loading = ref(false)

  const submitting = ref(false)

  const deleting = ref(false)

  const changingStatus = ref(false)

  const currentPage = ref(1)

  const lastPage = ref(1)

  const perPage = ref(10)

  const total = ref(0)
  const filters = ref<OrderFilters>({
    search: '',
    status: '',
    payment_type: '',
    room_id: '',
    date_from: '',
    date_to: '',
    page: 1,
    per_page: 10,
  })
  const statistics = ref<OrderStatistics>({
    total_orders: 0,
    pending_orders: 0,
    preparing_orders: 0,
    ready_orders: 0,
    served_orders: 0,
    cancelled_orders: 0,
    total_revenue: 0,
  })
  const hasOrders = computed(() => {
    return orders.value.length > 0
  })
  const pendingOrders = computed(() => {
    return orders.value.filter((order) => order.status === 'pending')
  })

  const preparingOrders = computed(() => {
    return orders.value.filter((order) => order.status === 'preparing')
  })

  const readyOrders = computed(() => {
    return orders.value.filter((order) => order.status === 'ready')
  })
  const servedOrders = computed(() => {
    return orders.value.filter((order) => order.status === 'served')
  })
  const cancelledOrders = computed(() => {
    return orders.value.filter((order) => order.status === 'cancelled')
  })
  const totalRevenue = computed(() => {
    return orders.value.reduce((sum, order) => sum + Number(order.total), 0)
  })
  async function fetchOrders(): Promise<void> {
    loading.value = true
    try {
      const response = await orderService.getOrders(filters.value)
      orders.value = response.data
      if (response.meta) {
        currentPage.value = response.meta.current_page
        lastPage.value = response.meta.last_page
        perPage.value = response.meta.per_page
        total.value = response.meta.total
      }
      calculateStatistics()
    } finally {
      loading.value = false
    }
  }

  async function fetchOrder(id: string): Promise<Order> {
    loading.value = true

    try {
      const response = await orderService.getOrder(id)

      selectedOrder.value = response.data

      return response.data
    } finally {
      loading.value = false
    }
  }

  async function refresh(): Promise<void> {
    await fetchOrders()
  }
  /**
   * Calculate dashboard statistics.
   */
  function calculateStatistics(): void {
    statistics.value.total_orders = orders.value.length

    statistics.value.pending_orders = orders.value.filter(
      (order) => order.status === 'pending',
    ).length

    statistics.value.preparing_orders = orders.value.filter(
      (order) => order.status === 'preparing',
    ).length

    statistics.value.ready_orders = orders.value.filter((order) => order.status === 'ready').length

    statistics.value.served_orders = orders.value.filter(
      (order) => order.status === 'served',
    ).length

    statistics.value.cancelled_orders = orders.value.filter(
      (order) => order.status === 'cancelled',
    ).length

    statistics.value.total_revenue = orders.value.reduce(
      (sum, order) => sum + Number(order.total),
      0,
    )
  }
  async function createOrder(payload: CreateOrderRequest): Promise<Order> {
    submitting.value = true

    try {
      const response = await orderService.createOrder(payload)

      await fetchOrders()

      return response.data
    } finally {
      submitting.value = false
    }
  }
  async function updateOrder(id: string, payload: UpdateOrderRequest): Promise<Order> {
    submitting.value = true

    try {
      const response = await orderService.updateOrder(id, payload)

      await fetchOrders()

      selectedOrder.value = response.data

      return response.data
    } finally {
      submitting.value = false
    }
  }
  async function deleteOrder(id: string): Promise<void> {
    deleting.value = true

    try {
      await orderService.deleteOrder(id)

      await fetchOrders()

      if (selectedOrder.value?.id === id) {
        selectedOrder.value = null
      }
    } finally {
      deleting.value = false
    }
  }

  async function changeStatus(id: string, status: OrderStatus): Promise<Order> {
    changingStatus.value = true

    try {
      const response = await orderService.changeStatus(id, {
        status,
      })

      await fetchOrders()

      selectedOrder.value = response.data

      return response.data
    } finally {
      changingStatus.value = false
    }
  }
  return {
    orders,
    selectedOrder,
    loading,
    submitting,
    deleting,
    changingStatus,
    currentPage,
    lastPage,
    perPage,
    total,
    filters,
    statistics,
    hasOrders,
    pendingOrders,
    preparingOrders,
    readyOrders,
    servedOrders,
    cancelledOrders,
    totalRevenue,
    fetchOrders,
    fetchOrder,
    refresh,
    createOrder,

    updateOrder,

    deleteOrder,
    changeStatus,
  }
})

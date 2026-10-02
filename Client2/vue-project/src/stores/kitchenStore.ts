import { defineStore } from 'pinia'
import { ref, computed } from 'vue'

import kitchenService from '../services/kitchenService'

import type { KitchenOrder, KitchenStatistics } from '@/types/kitchen'

export const useKitchenStore = defineStore('kitchen', () => {
  const orders = ref<KitchenOrder[]>([])

  const statistics = ref<KitchenStatistics | null>(null)

  const loading = ref(false)

  const actionLoading = ref<string | null>(null)

  const error = ref<string | null>(null)

  const pendingOrders = computed(() => orders.value.filter((order) => order.status === 'pending'))

  const preparingOrders = computed(() =>
    orders.value.filter((order) => order.status === 'preparing'),
  )

  const readyOrders = computed(() => orders.value.filter((order) => order.status === 'ready'))

  const completedOrders = computed(() => orders.value.filter((order) => order.status === 'served'))

  async function fetchDashboard() {
    loading.value = true

    error.value = null

    try {
      const data = await kitchenService.refresh()

      const ordersObj = data?.orders || (data as any) || {}
      const pending = Array.isArray(ordersObj.pending) ? ordersObj.pending : []
      const preparing = Array.isArray(ordersObj.preparing) ? ordersObj.preparing : []
      const ready = Array.isArray(ordersObj.ready) ? ordersObj.ready : []
      const served = Array.isArray(ordersObj.served) ? ordersObj.served : []

      orders.value = [
        ...pending,
        ...preparing,
        ...ready,
        ...served,
      ]

      statistics.value = data?.statistics || null
    } catch (err: any) {
      console.error('[kitchenStore] Failed to load kitchen dashboard:', err)
      error.value =
        err?.response?.data?.message ?? err.message ?? 'Failed to load kitchen dashboard'
    } finally {
      loading.value = false
    }
  }

  const refreshDashboard = fetchDashboard

  async function startPreparing(orderId: string) {
    actionLoading.value = orderId
    error.value = null

    try {
      const updatedOrder = await kitchenService.startPreparing(orderId)
      updateOrder(updatedOrder)
      syncStatistics()
      return updatedOrder
    } catch (err: any) {
      console.error('[kitchenStore] Failed to start preparing order:', err)
      const errorMsg =
        err?.response?.data?.message ?? err.message ?? 'Failed to start preparing order'
      error.value = errorMsg
      throw err
    } finally {
      actionLoading.value = null
    }
  }

  async function markReady(orderId: string) {
    actionLoading.value = orderId
    error.value = null

    try {
      const updatedOrder = await kitchenService.markReady(orderId)
      updateOrder(updatedOrder)
      syncStatistics()
      return updatedOrder
    } catch (err: any) {
      console.error('[kitchenStore] Failed to mark order ready:', err)
      const errorMsg = err?.response?.data?.message ?? err.message ?? 'Failed to mark order ready'
      error.value = errorMsg
      throw err
    } finally {
      actionLoading.value = null
    }
  }

  async function markServed(orderId: string) {
    actionLoading.value = orderId
    error.value = null

    try {
      const updatedOrder = await kitchenService.markServed(orderId)
      updateOrder(updatedOrder)
      syncStatistics()
      return updatedOrder
    } catch (err: any) {
      console.error('[kitchenStore] Failed to complete order:', err)
      const errorMsg = err?.response?.data?.message ?? err.message ?? 'Failed to complete order'
      error.value = errorMsg
      throw err
    } finally {
      actionLoading.value = null
    }
  }

  function syncStatistics() {
    kitchenService.getStatistics().then((stats) => {
      if (stats) statistics.value = stats
    }).catch(() => {})
  }

  function updateOrder(updatedOrder: KitchenOrder) {
    const index = orders.value.findIndex((order) => order.id === updatedOrder.id)

    if (index !== -1) {
      orders.value[index] = updatedOrder
    } else {
      orders.value.unshift(updatedOrder)
    }
  }

  function clearStore() {
    orders.value = []

    statistics.value = null

    error.value = null

    loading.value = false

    actionLoading.value = null
  }

  return {
    orders,

    statistics,

    loading,

    actionLoading,

    error,

    pendingOrders,

    preparingOrders,
    readyOrders,
    completedOrders,
    fetchDashboard,
    refreshDashboard,
    startPreparing,
    markReady,
    markServed,
    updateOrder,
    clearStore,
  }
})

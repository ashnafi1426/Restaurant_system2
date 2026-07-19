import { defineStore } from 'pinia'
import { ref, computed } from 'vue'

import kitchenService from '../services/kitchenService'

import type { KitchenOrder, KitchenStatistics } from '@/types/kitchen'

export const useKitchenStore = defineStore('kitchen', () => {
  /*
    |--------------------------------------------------------------------------
    | State
    |--------------------------------------------------------------------------
    */

  /**
   * All kitchen orders
   */
  const orders = ref<KitchenOrder[]>([])

  /**
   * Kitchen dashboard statistics
   */
  const statistics = ref<KitchenStatistics | null>(null)

  /**
   * Initial dashboard loading
   */
  const loading = ref(false)

  /**
   * Button/action loading
   * Stores current processing order id
   */
  const actionLoading = ref<string | null>(null)

  /**
   * API errors
   */
  const error = ref<string | null>(null)

  /*
    |--------------------------------------------------------------------------
    | Computed Order Groups
    |--------------------------------------------------------------------------
    */

  const pendingOrders = computed(() => orders.value.filter((order) => order.status === 'pending'))

  const preparingOrders = computed(() =>
    orders.value.filter((order) => order.status === 'preparing'),
  )

  const readyOrders = computed(() => orders.value.filter((order) => order.status === 'ready'))

  const completedOrders = computed(() => orders.value.filter((order) => order.status === 'served'))

  /*
    |--------------------------------------------------------------------------
    | Load Kitchen Dashboard
    |--------------------------------------------------------------------------
    */

  async function fetchDashboard() {
    loading.value = true

    error.value = null

    try {
      const data = await kitchenService.refresh()

      /**
       * Flatten all orders from the response
       * API returns { pending, preparing, ready, served }
       */
      orders.value = [
        ...data.orders.pending,
        ...data.orders.preparing,
        ...data.orders.ready,
        ...data.orders.served,
      ]

      /**
       * Statistics
       */
      statistics.value = data.statistics
    } catch (err: any) {
      error.value =
        err?.response?.data?.message ?? err.message ?? 'Failed to load kitchen dashboard'
    } finally {
      loading.value = false
    }
  }

  /*
    |--------------------------------------------------------------------------
    | Alias: refreshDashboard (for consistency)
    |--------------------------------------------------------------------------
    */

  const refreshDashboard = fetchDashboard

  /*
    |--------------------------------------------------------------------------
    | Start Preparing Order
    |--------------------------------------------------------------------------
    */

  async function startPreparing(orderId: string) {
    console.log(`📍 [STORE] startPreparing called for order: ${orderId}`)
    actionLoading.value = orderId
    error.value = null

    try {
      console.log(`🔵 [STORE] Calling API for order ${orderId}...`)
      const updatedOrder = await kitchenService.startPreparing(orderId)
      
      console.log(`✅ [STORE] API Response received:`, updatedOrder)
      console.log(`📊 [STORE] Order status changed to: ${updatedOrder.status}`)
      
      updateOrder(updatedOrder)
      console.log(`✅ [STORE] Order updated in local state`)
    } catch (err: any) {
      const errorMsg = err?.response?.data?.message ?? err.message ?? 'Failed to start preparing order'
      error.value = errorMsg
      console.error(`❌ [STORE] Error occurred:`, {
        message: errorMsg,
        status: err?.response?.status,
        data: err?.response?.data,
        fullError: err,
      })
    } finally {
      actionLoading.value = null
    }
  }

  /*
    |--------------------------------------------------------------------------
    | Mark Order Ready
    |--------------------------------------------------------------------------
    */

  async function markReady(orderId: string) {
    console.log(`📍 [STORE] markReady called for order: ${orderId}`)
    actionLoading.value = orderId

    error.value = null

    try {
      console.log(`🔵 [STORE] Calling API for order ${orderId}...`)
      const updatedOrder = await kitchenService.markReady(orderId)
      
      console.log(`✅ [STORE] API Response received, order status: ${updatedOrder.status}`)
      updateOrder(updatedOrder)
    } catch (err: any) {
      const errorMsg = err?.response?.data?.message ?? err.message ?? 'Failed to mark order ready'
      error.value = errorMsg
      console.error(`❌ [STORE] Error occurred:`, errorMsg)
    } finally {
      actionLoading.value = null
    }
  }

  /*
    |--------------------------------------------------------------------------
    | Mark Order Served / Completed
    |--------------------------------------------------------------------------
    */

  async function markServed(orderId: string) {
    console.log(`📍 [STORE] markServed called for order: ${orderId}`)
    actionLoading.value = orderId

    error.value = null

    try {
      console.log(`🔵 [STORE] Calling API for order ${orderId}...`)
      const updatedOrder = await kitchenService.markServed(orderId)
      
      console.log(`✅ [STORE] API Response received, order status: ${updatedOrder.status}`)
      updateOrder(updatedOrder)
    } catch (err: any) {
      const errorMsg = err?.response?.data?.message ?? err.message ?? 'Failed to complete order'
      error.value = errorMsg
      console.error(`❌ [STORE] Error occurred:`, errorMsg)
    } finally {
      actionLoading.value = null
    }
  }

  /*
    |--------------------------------------------------------------------------
    | Update Single Order Locally
    |--------------------------------------------------------------------------
    */

  function updateOrder(updatedOrder: KitchenOrder) {
    const index = orders.value.findIndex((order) => order.id === updatedOrder.id)

    if (index !== -1) {
      orders.value[index] = updatedOrder
    }
  }

  /*
    |--------------------------------------------------------------------------
    | Clear Store
    |--------------------------------------------------------------------------
    */

  function clearStore() {
    orders.value = []

    statistics.value = null

    error.value = null

    loading.value = false

    actionLoading.value = null
  }

  return {
    // state

    orders,

    statistics,

    loading,

    actionLoading,

    error,

    // computed

    pendingOrders,

    preparingOrders,
    readyOrders,
    completedOrders,
    // actions
    fetchDashboard,
    refreshDashboard,
    startPreparing,
    markReady,
    markServed,
    updateOrder,
    clearStore,
  }
})

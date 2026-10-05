/**
 * useOrderStatus Composable
 * 
 * Manages real-time order status tracking via WebSocket for customer order status page.
 * 
 * Features:
 * - Fetches initial order data from API
 * - Subscribes to private WebSocket channel: orders.{hotelId}.{orderId}
 * - Receives real-time updates: OrderStatusUpdated, PaymentStatusUpdated, OrderCancelled
 * - Handles reconnection automatically
 * - Cleans up subscription on component unmount
 * 
 * NO POLLING - updates only via WebSocket events
 */

import { ref, onMounted, onUnmounted, computed } from 'vue'
import axios from 'axios'

export interface OrderItem {
  id: string
  name: string
  quantity: number
  price: number
  total: number
}

export interface OrderData {
  order_id: string
  order_number: string
  hotel_id: string
  status: 'pending' | 'preparing' | 'ready' | 'served' | 'cancelled'
  order_type: string
  order_time: string
  room_number?: string
  table_number?: string
  customer_name?: string
  items: OrderItem[]
  subtotal: number
  tax: number
  service_charge: number
  total: number
  payment_type?: string
  payment_status?: string
  chef_id?: string
  chef_name?: string
  created_at: string
  updated_at: string
  served_at?: string
  notes?: string
}

export interface OrderStatusEvent {
  order_id: string
  hotel_id: string
  status: string
  previous_status?: string
  order_number: string
  message?: string
  estimated_completion_minutes?: number
  updated_at: string
}

export interface PaymentStatusEvent {
  order_id: string
  hotel_id: string
  order_number: string
  payment_status: string
  payment_method?: string
  payment_type?: string
  amount: number
  transaction_ref?: string
  updated_at: string
}

export function useOrderStatus(orderId: string, hotelId: string) {
  const orderData = ref<OrderData | null>(null)
  const status = ref<string>('loading')
  const paymentStatus = ref<string>('pending')
  const isConnected = ref<boolean>(false)
  const isLoading = ref<boolean>(true)
  const error = ref<string | null>(null)
  const lastUpdate = ref<string>('')
  
  let channel: any = null
  let reconnectAttempts = 0
  const maxReconnectAttempts = 5

  /**
   * Fetch initial order data from API
   */
  const fetchOrderData = async (): Promise<void> => {
    try {
      isLoading.value = true
      error.value = null

      const apiBaseUrl = import.meta.env.VITE_API_BASE_URL || 'http://127.0.0.1:8000'
      const response = await axios.get(`${apiBaseUrl}/api/guest/orders/${orderId}/status`, {
        headers: {
          'X-Hotel-ID': hotelId,
          'Accept': 'application/json'
        }
      })

      if (response.data.success && response.data.data) {
        orderData.value = response.data.data
        status.value = response.data.data.status
        paymentStatus.value = response.data.data.payment_status || 'pending'
        lastUpdate.value = response.data.data.updated_at
      } else {
        throw new Error(response.data.message || 'Failed to fetch order data')
      }
    } catch (err: any) {
      console.error('[useOrderStatus] Error fetching order data:', err)
      error.value = err.response?.data?.message || err.message || 'Failed to load order'
    } finally {
      isLoading.value = false
    }
  }

  /**
   * Subscribe to WebSocket channel for real-time updates
   */
  const subscribeToChannel = (): void => {
    if (!window.Echo) {
      console.error('[useOrderStatus] Echo not initialized')
      error.value = 'WebSocket not available'
      return
    }

    try {
      const channelName = `orders.${hotelId}.${orderId}`
      console.log(`[useOrderStatus] Subscribing to channel: ${channelName}`)

      channel = window.Echo.private(channelName)

      // Listen for OrderStatusUpdated events
      channel.listen('.OrderStatusUpdated', (event: OrderStatusEvent) => {
        console.log('[useOrderStatus] OrderStatusUpdated received:', event)
        
        status.value = event.status
        lastUpdate.value = event.updated_at
        
        // Update order data status
        if (orderData.value) {
          orderData.value.status = event.status as any
          orderData.value.updated_at = event.updated_at
        }
      })

      // Listen for PaymentStatusUpdated events
      channel.listen('.PaymentStatusUpdated', (event: PaymentStatusEvent) => {
        console.log('[useOrderStatus] PaymentStatusUpdated received:', event)
        
        paymentStatus.value = event.payment_status
        lastUpdate.value = event.updated_at
        
        // Update order data payment status
        if (orderData.value) {
          orderData.value.payment_status = event.payment_status
          orderData.value.updated_at = event.updated_at
        }
      })

      // Listen for OrderCancelled events
      channel.listen('.OrderCancelled', (event: any) => {
        console.log('[useOrderStatus] OrderCancelled received:', event)
        
        status.value = 'cancelled'
        lastUpdate.value = event.cancelled_at
        
        if (orderData.value) {
          orderData.value.status = 'cancelled'
          orderData.value.updated_at = event.cancelled_at
        }
      })

      // Connection state handling
      if (window.Echo.connector?.pusher) {
        window.Echo.connector.pusher.connection.bind('connected', () => {
          console.log('[useOrderStatus] WebSocket connected')
          isConnected.value = true
          reconnectAttempts = 0
        })

        window.Echo.connector.pusher.connection.bind('disconnected', () => {
          console.log('[useOrderStatus] WebSocket disconnected')
          isConnected.value = false
          handleReconnect()
        })

        window.Echo.connector.pusher.connection.bind('error', (err: any) => {
          console.error('[useOrderStatus] WebSocket error:', err)
          isConnected.value = false
        })

        // Check initial connection state
        const connectionState = window.Echo.connector.pusher.connection.state
        isConnected.value = connectionState === 'connected'
      }

    } catch (err: any) {
      console.error('[useOrderStatus] Error subscribing to channel:', err)
      error.value = 'Failed to subscribe to order updates'
    }
  }

  /**
   * Handle reconnection with exponential backoff
   */
  const handleReconnect = (): void => {
    if (reconnectAttempts >= maxReconnectAttempts) {
      console.error('[useOrderStatus] Max reconnection attempts reached')
      error.value = 'Connection lost. Please refresh the page.'
      return
    }

    reconnectAttempts++
    const delay = Math.min(1000 * Math.pow(2, reconnectAttempts), 10000)
    
    console.log(`[useOrderStatus] Reconnecting in ${delay}ms (attempt ${reconnectAttempts})`)
    
    setTimeout(async () => {
      // Re-sync data from API after reconnection
      await fetchOrderData()
      
      if (window.Echo.connector?.pusher?.connection?.state !== 'connected') {
        handleReconnect()
      }
    }, delay)
  }

  /**
   * Manual refresh function
   */
  const refresh = async (): Promise<void> => {
    await fetchOrderData()
  }

  /**
   * Cleanup function
   */
  const cleanup = (): void => {
    if (channel) {
      const channelName = `orders.${hotelId}.${orderId}`
      console.log(`[useOrderStatus] Leaving channel: ${channelName}`)
      window.Echo.leave(channelName)
      channel = null
    }
  }

  // Computed properties
  const isPending = computed(() => status.value === 'pending')
  const isPreparing = computed(() => status.value === 'preparing')
  const isReady = computed(() => status.value === 'ready')
  const isServed = computed(() => status.value === 'served')
  const isCancelled = computed(() => status.value === 'cancelled')
  const isPaymentPending = computed(() => paymentStatus.value === 'pending')
  const isPaymentPaid = computed(() => paymentStatus.value === 'paid')

  // Lifecycle hooks
  onMounted(async () => {
    await fetchOrderData()
    subscribeToChannel()
  })

  onUnmounted(() => {
    cleanup()
  })

  return {
    orderData,
    status,
    paymentStatus,
    isConnected,
    isLoading,
    error,
    lastUpdate,
    isPending,
    isPreparing,
    isReady,
    isServed,
    isCancelled,
    isPaymentPending,
    isPaymentPaid,
    refresh,
  }
}

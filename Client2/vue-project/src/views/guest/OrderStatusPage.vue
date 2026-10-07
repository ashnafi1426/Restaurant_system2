<template>
  <div class="min-h-screen bg-gray-50">
    <!-- Header -->
    <div class="bg-white border-b border-gray-200 px-4 py-4 flex items-center gap-3 sticky top-0 z-10 shadow-sm">
      <button @click="goBack" class="text-gray-600 hover:text-gray-900 p-1 rounded-lg hover:bg-gray-100 transition-colors">
        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
        </svg>
      </button>
      <div class="flex-1">
        <h1 class="text-lg font-bold text-gray-900">Order Status</h1>
        <p class="text-xs text-gray-500">
          {{orderData?.table_number ? 'Table ' + orderData.table_number : orderData?.room_number ? 'Room ' + orderData.room_number : ''}}
        </p>
      </div>
      <!-- Live connection dot -->
      <div class="flex items-center gap-1.5">
        <div
          class="w-2 h-2 rounded-full"
          :class="isConnected ? 'bg-green-500 animate-pulse' : 'bg-gray-400'"
        ></div>
        <span class="text-xs" :class="isConnected ? 'text-green-600' : 'text-gray-400'">
          {{isConnected ? 'Live' : 'Offline'}}
        </span>
      </div>
    </div>

    <!-- Loading -->
    <div v-if="isLoading" class="flex flex-col items-center justify-center py-24 gap-3">
      <div class="animate-spin rounded-full h-10 w-10 border-b-2 border-amber-500"></div>
      <p class="text-gray-500 text-sm">Loading your order...</p>
    </div>

    <!-- Error State -->
    <div v-else-if="error" class="p-4">
      <div class="rounded-xl p-6 text-center bg-red-50 border border-red-200">
        <div class="text-4xl mb-3">⚠️</div>
        <h2 class="font-bold mb-1 text-red-800">
          Unable to Load Order
        </h2>
        <p class="text-sm mb-4 text-red-600">
          {{ error }}
        </p>
        <button
          @click="refresh"
          class="px-5 py-2 bg-red-600 text-white rounded-lg text-sm font-medium hover:bg-red-700 transition-colors cursor-pointer"
        >
          Try Again
        </button>
      </div>
    </div>

    <!-- Content -->
    <div v-else-if="orderData" class="p-4 space-y-4 pb-8">
      <!-- Status Card -->
      <div
        class="rounded-xl p-4 flex items-start gap-3"
        :class="{
          'bg-yellow-50 border border-yellow-200': isPending,
          'bg-orange-50 border border-orange-200': isPreparing,
          'bg-green-50 border border-green-200': isReady || isServed,
          'bg-red-50 border border-red-200': isCancelled
        }"
      >
        <div
          class="w-11 h-11 rounded-full flex items-center justify-center flex-shrink-0 text-2xl"
          :class="{
            'bg-yellow-400': isPending,
            'bg-orange-400': isPreparing,
            'bg-green-400': isReady || isServed,
            'bg-red-400': isCancelled
          }"
        >
          {{statusIcon}}
        </div>
        <div>
          <h2 class="font-bold text-gray-900 text-base">{{statusTitle}}</h2>
          <p class="text-sm text-gray-600 mt-0.5">{{statusMessage}}</p>
        </div>
      </div>

      <!-- Order Info + Items Card -->
      <div class="bg-white rounded-xl shadow-sm overflow-hidden">
        <!-- Order header row -->
        <div class="flex items-center justify-between px-4 py-3 border-b border-gray-100">
          <span class="font-semibold text-gray-800 text-sm">Order #{{orderData.order_number || 'N/A'}}</span>
          <span class="text-xs text-gray-500">{{formatTime(orderData.created_at || orderData.order_time)}}</span>
        </div>

        <!-- Items -->
        <div class="divide-y divide-gray-50">
          <div
            v-for="(item, index) in (orderData?.items || [])"
            :key="item.id || index"
            class="flex items-center justify-between px-4 py-3"
          >
            <span class="text-sm text-gray-800 flex-1">
              {{item.quantity}}x {{item.name}}
            </span>
            <div class="flex items-center gap-2 ml-3">
              <span class="text-sm font-semibold text-gray-900">
                ETB {{((item.price || 0) * item.quantity).toFixed(2)}}
              </span>
              <span
                class="px-2 py-0.5 text-xs rounded-full font-medium"
                :class="{
                  'bg-yellow-100 text-yellow-800': isPending || isPreparing,
                  'bg-green-100 text-green-800': isReady || isServed,
                  'bg-red-100 text-red-800': isCancelled
                }"
              >
                {{status === 'pending' ? 'Pending' : status === 'preparing' ? 'Preparing' : status === 'ready' ? 'Ready' : status === 'served' ? 'Served' : 'Cancelled'}}
              </span>
            </div>
          </div>
        </div>

        <!-- Total -->
        <div class="flex items-center justify-between px-4 py-3 border-t-2 border-gray-200 bg-gray-50">
          <span class="font-bold text-gray-900">Total</span>
          <span class="text-lg font-bold text-red-600">ETB {{(orderData.total || 0).toFixed(2)}}</span>
        </div>
      </div>

      <!-- Payment Info -->
      <div class="bg-white rounded-xl shadow-sm px-4 py-3 space-y-2">
        <h3 class="font-semibold text-gray-800 text-sm mb-1">Payment</h3>
        <div class="flex items-center justify-between text-sm">
          <span class="text-gray-500">Status</span>
          <span
            class="font-medium"
            :class="paymentStatus === 'paid' ? 'text-green-600' : 'text-amber-600'"
          >
            {{paymentStatus === 'paid' ? ' Paid' : '⏳ Pending'}}
          </span>
        </div>
        <div class="flex items-center justify-between text-sm">
          <span class="text-gray-500">Method</span>
          <span class="font-medium text-gray-800">
            {{orderData.payment_type === 'room_charge' ? 'Room Charge' : orderData.payment_type === 'cash' ? 'Cash' : 'Online'}}
          </span>
        </div>
      </div>

      <!-- Pay Now Button - Chapa Integration -->
      <button
        v-if="isPaymentPending"
        @click="handlePayNow"
        :disabled="isProcessingPayment"
        class="w-full py-4 rounded-xl font-bold text-white text-base shadow-lg transition-all disabled:opacity-50 disabled:cursor-not-allowed flex items-center justify-center gap-2"
        style="background: linear-gradient(135deg, #8B5CF6 0%, #EC4899 100%)"
      >
        <span v-if="isProcessingPayment">Processing...</span>
        <template v-else>
          <span>💳</span>
          <span>Pay Now with Chapa - ETB {{(orderData.total || 0).toFixed(2)}}</span>
        </template>
      </button>

      <!-- Payment Completed -->
      <div
        v-else-if="isPaymentPaid"
        class="w-full py-4 rounded-xl font-bold text-white text-base text-center"
        style="background: linear-gradient(135deg, #10B981 0%, #059669 100%)"
      >
         Payment Completed
      </div>

      <!-- Room charge note -->
      <div
        v-if="orderData.payment_type === 'room_charge'"
        class="bg-blue-50 border border-blue-200 rounded-xl px-4 py-3 text-sm text-blue-700"
      >
        <span class="font-medium">Room Charge</span> — This will be added to your room bill at checkout.
      </div>

      <!-- Refresh -->
      <div class="text-center">
        <button
          @click="refresh"
          :disabled="isLoading"
          class="text-sm text-gray-400 hover:text-gray-600 transition-colors disabled:opacity-40"
        >
           Refresh
        </button>
        <p v-if="lastUpdate" class="text-xs text-gray-400 mt-1">
          Last updated: {{formatTime(lastUpdate)}}
        </p>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, watch, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useOrderStatus } from '@/composables/useOrderStatus'

const route = useRoute()
const router = useRouter()

const orderId = ref(route.params.orderId as string)

const hotelId = ref(
  (localStorage.getItem('hotel_id') ||
  localStorage.getItem('active_hotel_id') ||
  route.query.hotel_id as string ||
  '').toString()
)

// Persist qr_token from URL or storage
const qrToken = (route.query.qr_token as string) || localStorage.getItem('guest_qr_token') || ''
if (route.query.qr_token) {
  localStorage.setItem('guest_qr_token', route.query.qr_token as string)
} else if (qrToken && !localStorage.getItem('guest_qr_token')) {
  localStorage.setItem('guest_qr_token', qrToken)
}

const {
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
} = useOrderStatus(orderId.value, hotelId.value, qrToken)

const isProcessingPayment = ref(false)

// Auto-verify if returning from Chapa or if a pending tx_ref is saved for this order
onMounted(async () => {
  const pendingTxRef = (route.query.tx_ref as string) || 
                       (route.query.trx_ref as string) || 
                       (route.query.transaction_ref as string) ||
                       localStorage.getItem('pending_order_tx_ref') || 
                       sessionStorage.getItem('pending_order_tx_ref')

  if (pendingTxRef) {
    try {
      console.log('[OrderStatus] Checking/verifying pending payment:', pendingTxRef)
      const res = await fetch(`${import.meta.env.VITE_API_BASE_URL}/api/payments/verify/${pendingTxRef}`)
      const data = await res.json()
      console.log('[OrderStatus] Payment verification response:', data)
      if (data.success) {
        localStorage.removeItem('pending_order_tx_ref')
        sessionStorage.removeItem('pending_order_tx_ref')
        await refresh()
      }
    } catch (e) {
      console.warn('[OrderStatus] Payment verification error:', e)
    }
  }
})

// Navigation
const goBack = () => router.back()

// Payment handler - Navigate to Pay Your Order page
const handlePayNow = () => {
  if (!orderData.value) return
  
  try {
    const rawItems = orderData.value.items || orderData.value.order_items || []
    const formattedItems = rawItems.map((item: any, idx: number) => ({
      id: item.id || item.menu_item_id || `item-${idx}`,
      name: item.name || item.item_name || item.menu_item?.name || 'Item',
      quantity: Number(item.quantity) || 1,
      price: Number(item.price ?? item.item_price_at_order ?? item.unit_price ?? 0)
    }))

    const isRoom = !orderData.value.table_number && (!!orderData.value.room_number || orderData.value.order_type === 'room_service')
    const locationLabel = orderData.value.room_number 
      ? `Room ${orderData.value.room_number}` 
      : (orderData.value.table_number ? `Table ${orderData.value.table_number}` : 'Order')

    const currentQrToken = (typeof qrToken === 'string' ? qrToken : '') || 
                          localStorage.getItem('guest_qr_token') || 
                          (route.query.qr_token as string) || ''
    const currentHotelId = hotelId.value || orderData.value.hotel_id || localStorage.getItem('hotel_id') || ''
    const currentOrderId = orderData.value.order_id || orderData.value.id || orderId.value

    const paymentPayload = {
      order_id: currentOrderId,
      order_number: orderData.value.order_number,
      is_existing_order: true,
      is_room_order: isRoom,
      room_number: orderData.value.room_number || null,
      table_number: locationLabel,
      table_id: orderData.value.table_id || null,
      room_id: orderData.value.room_id || null,
      order_type: orderData.value.order_type || (isRoom ? 'room_service' : 'dine_in'),
      qr_token: currentQrToken,
      hotel_id: currentHotelId,
      items: formattedItems,
      calculation: {
        subtotal: Number(orderData.value.subtotal || orderData.value.total || 0),
        tax: Number(orderData.value.tax || 0),
        service_charge: Number(orderData.value.service_charge || 0),
        total: Number(orderData.value.total || 0),
        tip: 0
      },
      amount: Number(orderData.value.total || 0)
    }

    // Persist payment data
    localStorage.setItem('walk_in_payment_data', JSON.stringify(paymentPayload))
    localStorage.setItem('order_payment_data', JSON.stringify(paymentPayload))
    sessionStorage.setItem('walk_in_payment_data', JSON.stringify(paymentPayload))
    sessionStorage.setItem('order_payment_data', JSON.stringify(paymentPayload))

    if (currentQrToken) {
      localStorage.setItem('guest_qr_token', currentQrToken)
      sessionStorage.setItem('guest_qr_token', currentQrToken)
    }

    if (currentHotelId) {
      localStorage.setItem('hotel_id', currentHotelId)
    }

    console.log('[OrderStatus] Stored order data for payment page, navigating to /order/payment:', paymentPayload)

    // Navigate to Pay Your Order page (Screenshot 2)
    router.push({
      path: '/order/payment',
      query: {
        order_id: currentOrderId,
        qr_token: currentQrToken || undefined,
        hotel_id: currentHotelId || undefined
      }
    })
  } catch (err: any) {
    console.error('[OrderStatus] Failed to prepare payment navigation:', err)
  }
}
const statusIcon = computed(() => {
  if (isPending.value) return ''
  if (isPreparing.value) return '\u200d'
  if (isReady.value) return ''
  if (isServed.value) return ''
  if (isCancelled.value) return ''
  return ''
})

const statusTitle = computed(() => {
  if (isPending.value) return 'Order Received'
  if (isPreparing.value) return 'Preparing Your Order'
  if (isReady.value) return 'Order Ready!'
  if (isServed.value) return 'Order Completed'
  if (isCancelled.value) return 'Order Cancelled'
  return 'Order Status'
})

const statusMessage = computed(() => {
  if (isPending.value) return 'Your order has been received and will be prepared shortly.'
  if (isPreparing.value) return 'Our chef is preparing your delicious meal right now.'
  if (isReady.value) return 'Your order is ready! Please wait for delivery.'
  if (isServed.value) return 'Your order has been completed. Enjoy your meal!'
  if (isCancelled.value) return 'This order has been cancelled.'
  return ''
})

// Format time helper
const formatTime = (isoString: string): string => {
  if (!isoString) return 'N/A'
  try {
    return new Date(isoString).toLocaleTimeString('en-US', {
      hour: '2-digit',
      minute: '2-digit',
      hour12: true
    })
  } catch {
    return 'N/A'
  }
}

// Watch status for notifications
watch(status, (newStatus, oldStatus) => {
  if (oldStatus && newStatus !== oldStatus) {
    if ('Notification' in window && Notification.permission === 'granted') {
      new Notification('Order Status Updated', {
        body: statusMessage.value,
        icon: '/favicon.ico'
      })
    }
  }
})
</script>

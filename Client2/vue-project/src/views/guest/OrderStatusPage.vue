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

    <!-- Error -->
    <div v-else-if="error" class="p-4">
      <div class="bg-red-50 border border-red-200 rounded-xl p-6 text-center">
        <div class="text-4xl mb-3">⚠️</div>
        <h2 class="font-bold text-red-800 mb-1">Unable to Load Order</h2>
        <p class="text-sm text-red-600 mb-4">{{error}}</p>
        <button
          @click="refresh"
          class="px-5 py-2 bg-red-600 text-white rounded-lg text-sm font-medium hover:bg-red-700 transition-colors"
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
            v-for="(item, index) in orderData.items"
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
            {{paymentStatus === 'paid' ? '✅ Paid' : '⏳ Pending'}}
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
        ✅ Payment Completed
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
          🔄 Refresh
        </button>
        <p v-if="lastUpdate" class="text-xs text-gray-400 mt-1">
          Last updated: {{formatTime(lastUpdate)}}
        </p>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, watch } from 'vue'
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

// Persist qr_token from URL so the composable can use it
const qrToken = (route.query.qr_token as string) || localStorage.getItem('guest_qr_token') || ''
if (qrToken && !localStorage.getItem('guest_qr_token')) {
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
} = useOrderStatus(orderId.value, hotelId.value)

const isProcessingPayment = ref(false)

// Navigation
const goBack = () => router.back()

// Payment handler - Initialize Chapa payment for this order
const handlePayNow = async () => {
  if (isProcessingPayment.value || !orderData.value) return
  
  isProcessingPayment.value = true
  
  try {
    // Get guest info from localStorage or use defaults
    const guestInfo = {
      first_name: localStorage.getItem('guest_first_name') || 'Guest',
      last_name: localStorage.getItem('guest_last_name') || 'User',
      email: localStorage.getItem('guest_email') || `guest${Date.now()}@hotel.com`,
      phone: localStorage.getItem('guest_phone') || '+251911000000'
    }
    
    // Prepare payment initialization request
    const paymentPayload = {
      order_id: orderData.value.order_id || orderData.value.id,
      ...guestInfo
    }
    
    console.log('[OrderStatus] Initializing Chapa payment:', paymentPayload)
    
    // Call backend to initialize Chapa payment for existing order
    const response = await fetch(`${import.meta.env.VITE_API_BASE_URL}/api/order-payments/initialize-existing`, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
        'X-Hotel-ID': hotelId.value
      },
      body: JSON.stringify(paymentPayload)
    })
    
    const result = await response.json()
    
    if (result.success && result.checkout_url) {
      console.log('[OrderStatus] Redirecting to Chapa checkout:', result.checkout_url)
      // Redirect to Chapa checkout page
      window.location.href = result.checkout_url
    } else {
      throw new Error(result.message || 'Failed to initialize payment')
    }
  } catch (error: any) {
    console.error('[OrderStatus] Payment initialization error:', error)
    alert(`Payment Error: ${error.message || 'Failed to initialize payment. Please try again.'}`)
  } finally {
    isProcessingPayment.value = false
  }
}
const statusIcon = computed(() => {
  if (isPending.value) return '📝'
  if (isPreparing.value) return '👨\u200d🍳'
  if (isReady.value) return '✅'
  if (isServed.value) return '🎉'
  if (isCancelled.value) return '❌'
  return '📦'
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

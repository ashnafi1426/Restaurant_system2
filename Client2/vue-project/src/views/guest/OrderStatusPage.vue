<template>
  <div class="min-h-screen bg-gradient-to-br from-slate-50 to-slate-100 py-8 px-4">
    <div class="max-w-4xl mx-auto">
      <!-- Loading State -->
      <div v-if="isLoading" class="flex items-center justify-center min-h-[60vh]">
        <div class="text-center">
          <div class="animate-spin rounded-full h-16 w-16 border-b-4 border-amber-600 mx-auto"></div>
          <p class="mt-4 text-slate-600 text-lg">Loading your order...</p>
        </div>
      </div>

      <!-- Error State -->
      <div v-else-if="error" class="bg-white rounded-2xl shadow-xl p-8 text-center">
        <div class="text-red-500 text-6xl mb-4">⚠️</div>
        <h2 class="text-2xl font-bold text-slate-800 mb-2">Unable to Load Order</h2>
        <p class="text-slate-600 mb-6">{{ error }}</p>
        <button
          @click="refresh"
          class="px-6 py-3 bg-amber-600 text-white rounded-lg font-semibold hover:bg-amber-700 transition-colors"
        >
          Try Again
        </button>
      </div>

      <!-- Order Status Content -->
      <div v-else-if="orderData" class="space-y-6">
        <!-- Header Card -->
        <div class="bg-white rounded-2xl shadow-xl overflow-hidden">
          <!-- Status Header -->
          <div class="bg-gradient-to-r from-amber-500 to-amber-600 px-6 py-5 text-white">
            <div class="flex items-center justify-between">
              <div>
                <h1 class="text-2xl font-bold">Order Status</h1>
                <p class="text-amber-100 text-sm mt-1">Track your order in real-time</p>
              </div>
              <div class="flex items-center gap-2">
                <!-- Connection Indicator -->
                <div class="flex items-center gap-2 bg-white/20 px-3 py-2 rounded-lg">
                  <div
                    class="w-2 h-2 rounded-full"
                    :class="isConnected ? 'bg-green-400 animate-pulse' : 'bg-red-400'"
                  ></div>
                  <span class="text-sm font-medium">
                    {{ isConnected ? 'Live' : 'Reconnecting...' }}
                  </span>
                </div>
              </div>
            </div>
          </div>

          <!-- Order Info -->
          <div class="px-6 py-5 border-b border-slate-200 bg-slate-50">
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
              <div>
                <p class="text-xs uppercase text-slate-500 font-semibold">Order Number</p>
                <p class="font-bold text-slate-900 mt-1 text-lg">#{{ orderData.order_number }}</p>
              </div>
              <div v-if="orderData.room_number">
                <p class="text-xs uppercase text-slate-500 font-semibold">Room</p>
                <p class="font-bold text-slate-900 mt-1 text-lg">{{ orderData.room_number }}</p>
              </div>
              <div v-if="orderData.table_number">
                <p class="text-xs uppercase text-slate-500 font-semibold">Table</p>
                <p class="font-bold text-slate-900 mt-1 text-lg">{{ orderData.table_number }}</p>
              </div>
              <div>
                <p class="text-xs uppercase text-slate-500 font-semibold">Order Time</p>
                <p class="font-medium text-slate-700 mt-1">{{ formatTime(orderData.order_time) }}</p>
              </div>
              <div v-if="orderData.customer_name">
                <p class="text-xs uppercase text-slate-500 font-semibold">Customer</p>
                <p class="font-medium text-slate-700 mt-1">{{ orderData.customer_name }}</p>
              </div>
            </div>
          </div>

          <!-- Status Timeline -->
          <div class="px-6 py-8">
            <OrderStatusTimeline
              :order-number="orderData.order_number"
              :status="mapStatusToTimeline(status)"
              :estimated-minutes="estimatedMinutes"
              :created-at="orderData.created_at"
            />
          </div>
        </div>

        <!-- Order Items Card -->
        <div class="bg-white rounded-2xl shadow-xl overflow-hidden">
          <div class="px-6 py-4 border-b border-slate-200 bg-slate-50">
            <h2 class="text-xl font-bold text-slate-900">Order Items</h2>
          </div>
          <div class="divide-y divide-slate-200">
            <div
              v-for="item in orderData.items"
              :key="item.id"
              class="px-6 py-4 flex items-center justify-between hover:bg-slate-50 transition-colors"
            >
              <div class="flex-1">
                <h3 class="font-semibold text-slate-900">{{ item.name }}</h3>
                <p class="text-sm text-slate-500 mt-1">Quantity: {{ item.quantity }}</p>
              </div>
              <div class="text-right">
                <p class="font-bold text-amber-600">${{ item.total.toFixed(2) }}</p>
                <p class="text-xs text-slate-500">${{ item.price.toFixed(2) }} each</p>
              </div>
            </div>
          </div>

          <!-- Order Summary -->
          <div class="px-6 py-4 bg-slate-50 border-t border-slate-200 space-y-2">
            <div class="flex justify-between text-slate-700">
              <span>Subtotal</span>
              <span class="font-medium">${{ orderData.subtotal.toFixed(2) }}</span>
            </div>
            <div class="flex justify-between text-slate-700">
              <span>Tax</span>
              <span class="font-medium">${{ orderData.tax.toFixed(2) }}</span>
            </div>
            <div class="flex justify-between text-slate-700">
              <span>Service Charge</span>
              <span class="font-medium">${{ orderData.service_charge.toFixed(2) }}</span>
            </div>
            <div class="flex justify-between text-xl font-bold text-slate-900 pt-2 border-t-2 border-slate-300">
              <span>Total</span>
              <span class="text-amber-600">${{ orderData.total.toFixed(2) }}</span>
            </div>
          </div>
        </div>

        <!-- Payment Card -->
        <div
          v-if="showPaymentSection"
          class="bg-white rounded-2xl shadow-xl overflow-hidden"
        >
          <div class="px-6 py-4 border-b border-slate-200 bg-slate-50">
            <h2 class="text-xl font-bold text-slate-900">Payment</h2>
          </div>
          <div class="px-6 py-6">
            <div class="flex items-center justify-between mb-4">
              <div>
                <p class="text-sm text-slate-600">Payment Status</p>
                <p class="text-lg font-bold" :class="paymentStatusColor">
                  {{ paymentStatusText }}
                </p>
              </div>
              <div
                v-if="paymentStatus === 'paid'"
                class="text-4xl"
              >
                ✅
              </div>
            </div>

            <!-- Pay Now Button -->
            <button
              v-if="isPaymentPending && orderData.payment_type !== 'room_charge'"
              @click="handlePayNow"
              :disabled="isProcessingPayment"
              class="w-full px-6 py-4 bg-gradient-to-r from-amber-500 to-amber-600 text-white rounded-xl font-bold text-lg hover:shadow-lg transition-shadow disabled:opacity-50 disabled:cursor-not-allowed flex items-center justify-center gap-2"
            >
              <span v-if="!isProcessingPayment">💳 Pay Now with Chapa</span>
              <span v-else>Processing...</span>
            </button>

            <!-- Room Charge Info -->
            <div
              v-if="orderData.payment_type === 'room_charge'"
              class="bg-blue-50 border border-blue-200 rounded-lg p-4 text-sm text-blue-800"
            >
              <p class="font-medium">Room Charge</p>
              <p class="mt-1">This charge will be added to your room bill and settled at checkout.</p>
            </div>
          </div>
        </div>

        <!-- Status Messages -->
        <div
          v-if="statusMessage"
          class="bg-gradient-to-r from-teal-50 to-teal-100 border-l-4 border-teal-500 rounded-lg p-6 shadow-md"
        >
          <div class="flex items-start gap-4">
            <div class="text-4xl">{{ statusIcon }}</div>
            <div class="flex-1">
              <h3 class="font-bold text-teal-900 text-lg">{{ statusTitle }}</h3>
              <p class="text-teal-800 mt-2">{{ statusMessage }}</p>
              <p v-if="estimatedMinutes > 0" class="text-teal-700 mt-2 font-medium">
                Estimated time: {{ estimatedMinutes }} minutes
              </p>
            </div>
          </div>
        </div>

        <!-- Notes -->
        <div
          v-if="orderData.notes"
          class="bg-amber-50 border border-amber-200 rounded-lg p-4"
        >
          <p class="text-sm font-semibold text-amber-900">Special Instructions:</p>
          <p class="text-amber-800 mt-1">{{ orderData.notes }}</p>
        </div>

        <!-- Refresh Button -->
        <div class="text-center">
          <button
            @click="refresh"
            :disabled="isLoading"
            class="px-6 py-2 bg-white border-2 border-slate-300 text-slate-700 rounded-lg font-medium hover:bg-slate-50 transition-colors disabled:opacity-50"
          >
            🔄 Refresh Order
          </button>
          <p class="text-xs text-slate-500 mt-2">
            Last updated: {{ lastUpdate ? formatTime(lastUpdate) : 'Just now' }}
          </p>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, watch } from 'vue'
import { useRoute } from 'vue-router'
import { useOrderStatus } from '@/composables/useOrderStatus'
import OrderStatusTimeline from '@/components/guest/OrderStatusTimeline.vue'

const route = useRoute()
const orderId = ref(route.params.orderId as string)

// Get hotel_id from localStorage (set during QR menu session)
const hotelId = ref(
  localStorage.getItem('hotel_id') ||
  localStorage.getItem('active_hotel_id') ||
  ''
)

// Initialize composable
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
const estimatedMinutes = ref(20)

// Computed properties
const showPaymentSection = computed(() => {
  return orderData.value && orderData.value.payment_type
})

const paymentStatusText = computed(() => {
  if (paymentStatus.value === 'paid') return 'Paid'
  if (paymentStatus.value === 'pending') return 'Pending Payment'
  if (paymentStatus.value === 'failed') return 'Payment Failed'
  return 'Unknown'
})

const paymentStatusColor = computed(() => {
  if (paymentStatus.value === 'paid') return 'text-green-600'
  if (paymentStatus.value === 'pending') return 'text-amber-600'
  if (paymentStatus.value === 'failed') return 'text-red-600'
  return 'text-slate-600'
})

const statusIcon = computed(() => {
  if (isPending.value) return '📝'
  if (isPreparing.value) return '👨‍🍳'
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
  if (isReady.value) return 'Your order is ready! Please proceed to pick it up or wait for delivery.'
  if (isServed.value) return 'Your order has been completed. Enjoy your meal!'
  if (isCancelled.value) return 'This order has been cancelled.'
  return ''
})

// Map status to OrderStatusTimeline component format
const mapStatusToTimeline = (currentStatus: string): string => {
  // OrderStatusTimeline expects: 'pending' | 'confirmed' | 'preparing' | 'ready' | 'delivered'
  if (currentStatus === 'pending') return 'pending'
  if (currentStatus === 'preparing') return 'preparing'
  if (currentStatus === 'ready') return 'ready'
  if (currentStatus === 'served') return 'delivered'
  if (currentStatus === 'cancelled') return 'pending' // Show as first step
  return 'pending'
}

// Format time helper
const formatTime = (isoString: string): string => {
  try {
    const date = new Date(isoString)
    return date.toLocaleTimeString('en-US', {
      hour: '2-digit',
      minute: '2-digit',
      hour12: true
    })
  } catch {
    return 'N/A'
  }
}

// Handle payment button click
const handlePayNow = () => {
  isProcessingPayment.value = true
  // Redirect to payment initialization or open payment modal
  // This will integrate with existing Chapa payment flow
  alert('Payment integration: Redirect to Chapa payment flow')
  setTimeout(() => {
    isProcessingPayment.value = false
  }, 2000)
}

// Watch for status changes and trigger animations/notifications
watch(status, (newStatus, oldStatus) => {
  if (oldStatus && newStatus !== oldStatus) {
    console.log(`Order status changed: ${oldStatus} → ${newStatus}`)
    
    // Update estimated time based on status
    if (newStatus === 'preparing') {
      estimatedMinutes.value = 20
    } else if (newStatus === 'ready') {
      estimatedMinutes.value = 0
    }
    
    // Could trigger browser notification here
    if ('Notification' in window && Notification.permission === 'granted') {
      new Notification('Order Status Updated', {
        body: statusMessage.value,
        icon: '/favicon.ico'
      })
    }
  }
})
</script>

<style scoped>
/* Add smooth transitions for status changes */
.transition-all {
  transition: all 0.3s ease-in-out;
}

/* Pulse animation for live indicator */
@keyframes pulse {
  0%, 100% {
    opacity: 1;
  }
  50% {
    opacity: 0.5;
  }
}

.animate-pulse {
  animation: pulse 2s cubic-bezier(0.4, 0, 0.6, 1) infinite;
}
</style>

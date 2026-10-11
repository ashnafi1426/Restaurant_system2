<template>
  <div class="min-h-screen bg-slate-50 dark:bg-slate-950 text-slate-900 dark:text-slate-100">
    <div
      class="bg-white dark:bg-slate-900 border-b border-slate-200 dark:border-slate-800 px-4 py-4 flex items-center gap-3 sticky top-0 z-10 shadow-xs"
    >
      <button
        @click="goBack"
        class="text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white p-1 rounded-lg hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors"
      >
        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path
            stroke-linecap="round"
            stroke-linejoin="round"
            stroke-width="2"
            d="M15 19l-7-7 7-7"
          ></path>
        </svg>
      </button>
      <div class="flex-1">
        <h1 class="text-lg font-bold text-slate-900 dark:text-slate-100">Order Status</h1>
        <p class="text-xs text-slate-500 dark:text-slate-400 font-medium">
          {{
            orderData?.table_number
              ? 'Table ' + orderData.table_number
              : orderData?.room_number
                ? 'Room ' + orderData.room_number
                : orderData?.order_type === 'walk_in'
                  ? 'Walk-in Dining'
                  : orderData?.order_type === 'takeaway'
                    ? 'Takeaway Order'
                    : 'Restaurant Order'
          }}
        </p>
      </div>

      <div class="flex items-center gap-1.5">
        <div
          class="w-2 h-2 rounded-full"
          :class="isConnected ? 'bg-emerald-500 animate-pulse' : 'bg-slate-400'"
        ></div>
        <span
          class="text-xs"
          :class="
            isConnected ? 'text-emerald-600 dark:text-emerald-400 font-medium' : 'text-slate-400'
          "
        >
          {{ isConnected ? 'Live' : 'Offline' }}
        </span>
      </div>
    </div>

    <div v-if="isLoading" class="flex flex-col items-center justify-center py-24 gap-3">
      <div class="animate-spin rounded-full h-10 w-10 border-b-2 border-primary"></div>
      <p class="text-slate-500 dark:text-slate-400 text-sm">Loading your order...</p>
    </div>

    <div v-else-if="error" class="p-4">
      <div
        class="rounded-xl p-6 text-center bg-rose-50 dark:bg-rose-950/40 border border-rose-200 dark:border-rose-800"
      >
        <h2 class="font-bold mb-1 text-rose-800 dark:text-rose-200">Unable to Load Order</h2>
        <p class="text-sm mb-4 text-rose-600 dark:text-rose-400">
          {{ error }}
        </p>
        <button
          @click="refresh"
          class="px-5 py-2 bg-rose-600 hover:bg-rose-700 text-white rounded-lg text-sm font-medium transition-colors cursor-pointer"
        >
          Try Again
        </button>
      </div>
    </div>

    <div v-else-if="orderData" class="p-4 space-y-4 pb-8">
      <div
        class="rounded-xl p-4 flex items-start gap-3 shadow-xs transition-all border"
        :class="{
          'bg-amber-50 dark:bg-amber-950/30 border-amber-200 dark:border-amber-800/50': isPending,
          'bg-blue-50 dark:bg-blue-950/30 border-blue-200 dark:border-blue-800/50': isPreparing,
          'bg-emerald-50 dark:bg-emerald-950/30 border-emerald-200 dark:border-emerald-800/50':
            isReady,
          'bg-teal-50 dark:bg-teal-950/30 border-teal-200 dark:border-teal-800/50': isServed,
          'bg-rose-50 dark:bg-rose-950/30 border-rose-200 dark:border-rose-800/50': isCancelled,
        }"
      >
        <div
          class="w-11 h-11 rounded-full flex items-center justify-center flex-shrink-0 text-white shadow-xs"
          :class="{
            'bg-amber-500': isPending,
            'bg-blue-600': isPreparing,
            'bg-emerald-600': isReady,
            'bg-teal-600': isServed,
            'bg-rose-500': isCancelled,
          }"
        >
          <component :is="statusIconComponent" class="w-6 h-6 text-white" />
        </div>
        <div class="flex-1 min-w-0">
          <div class="flex items-center gap-2">
            <h2 class="font-bold text-slate-900 dark:text-slate-100 text-base">
              {{ statusTitle }}
            </h2>
            <span
              v-if="isServed"
              class="px-2 py-0.5 rounded-full text-[10px] font-extrabold uppercase tracking-wider bg-teal-600 text-white flex-shrink-0"
            >
              Cleared
            </span>
          </div>
          <p class="text-sm text-slate-600 dark:text-slate-300 mt-0.5">{{ statusMessage }}</p>
        </div>
      </div>

      <div
        class="bg-white dark:bg-slate-900 rounded-xl shadow-xs border border-slate-200 dark:border-slate-800 overflow-hidden"
      >
        <div
          class="flex items-center justify-between px-4 py-3 border-b border-slate-100 dark:border-slate-800"
        >
          <span class="font-semibold text-slate-800 dark:text-slate-200 text-sm"
            >Order #{{ orderData.order_number || 'N/A' }}</span
          >
          <span class="text-xs text-slate-500 dark:text-slate-400">{{
            formatTime(orderData.created_at || orderData.order_time)
          }}</span>
        </div>

        <div class="divide-y divide-slate-100 dark:divide-slate-800">
          <div
            v-for="(item, index) in orderData?.items || []"
            :key="item.id || index"
            class="flex items-center justify-between px-4 py-3"
          >
            <span class="text-sm text-slate-800 dark:text-slate-200 flex-1">
              {{ item.quantity }}x {{ item.name }}
            </span>
            <div class="flex items-center gap-2 ml-3">
              <span class="text-sm font-semibold text-slate-900 dark:text-slate-100">
                ETB {{ getItemPrice(item) }}
              </span>
              <span
                class="px-2 py-0.5 text-xs rounded-full font-bold inline-flex items-center gap-1"
                :class="{
                  'bg-amber-100 text-amber-800 dark:bg-amber-950/50 dark:text-amber-300': isPending,
                  'bg-blue-100 text-blue-800 dark:bg-blue-950/50 dark:text-blue-300': isPreparing,
                  'bg-emerald-100 text-emerald-800 dark:bg-emerald-950/50 dark:text-emerald-300':
                    isReady,
                  'bg-teal-100 text-teal-800 dark:bg-teal-950/50 dark:text-teal-300': isServed,
                  'bg-rose-100 text-rose-800 dark:bg-rose-950/50 dark:text-rose-300': isCancelled,
                }"
              >
                <Check v-if="isServed" class="w-3 h-3 text-teal-600 dark:text-teal-400" />
                <span>{{
                  isServed
                    ? 'Cleared'
                    : status === 'pending'
                      ? 'Pending'
                      : status === 'preparing'
                        ? 'Preparing'
                        : status === 'ready'
                          ? 'Ready'
                          : 'Cancelled'
                }}</span>
              </span>
            </div>
          </div>
        </div>

        <div
          class="border-t-2 border-slate-100 dark:border-slate-800 px-4 py-3 bg-slate-50 dark:bg-slate-900/50 space-y-1.5"
        >
          <div class="flex items-center justify-between text-sm text-slate-600 dark:text-slate-400">
            <span>Order Total</span>
            <span class="font-semibold text-slate-900 dark:text-slate-100">
              ETB {{ Number(orderData.total || orderData.order_total || 0).toFixed(2) }}
            </span>
          </div>

          <div
            v-if="orderTip > 0"
            class="flex items-center justify-between text-sm text-amber-600 dark:text-amber-400"
          >
            <span class="flex items-center gap-1 font-medium">
              <span>★</span>
              <span>Tip</span>
            </span>
            <span class="font-bold text-amber-700 dark:text-amber-300">
              + ETB {{ orderTip.toFixed(2) }}
            </span>
          </div>

          <div
            class="flex items-center justify-between pt-2 border-t border-slate-200 dark:border-slate-800"
          >
            <span class="font-bold text-slate-900 dark:text-slate-100">Total</span>
            <span class="text-xl font-bold text-slate-900 dark:text-white">
              ETB {{ orderTotalWithTip.toFixed(2) }}
            </span>
          </div>
        </div>
      </div>

      <div
        class="bg-white dark:bg-slate-900 rounded-xl shadow-xs border border-slate-200 dark:border-slate-800 px-4 py-3 space-y-2"
      >
        <h3 class="font-semibold text-slate-800 dark:text-slate-200 text-sm mb-1">Payment</h3>
        <div class="flex items-center justify-between text-sm">
          <span class="text-slate-500 dark:text-slate-400">Status</span>
          <span
            class="font-medium inline-flex items-center gap-1"
            :class="
              isPaymentPaid || isServed
                ? 'text-emerald-600 dark:text-emerald-400'
                : 'text-amber-600 dark:text-amber-400'
            "
          >
            <CheckCircle2
              v-if="isPaymentPaid || isServed"
              class="w-4 h-4 text-emerald-600 dark:text-emerald-400"
            />
            <span>{{ isPaymentPaid || isServed ? 'Paid & Settled' : '⏳ Pending' }}</span>
          </span>
        </div>
        <div class="flex items-center justify-between text-sm">
          <span class="text-slate-500 dark:text-slate-400">Method</span>
          <span class="font-medium text-slate-800 dark:text-slate-200 capitalize">
            {{
              orderData.payment_type === 'room_charge'
                ? 'Room Charge'
                : orderData.payment_method || orderData.payment_type || 'Cash'
            }}
          </span>
        </div>
        <div v-if="isPaymentPaid || isServed" class="flex items-center justify-between text-sm">
          <span class="text-slate-500 dark:text-slate-400">Amount Paid</span>
          <span class="font-bold text-emerald-600 dark:text-emerald-400">
            ETB {{ (Number(orderData.amount_paid) || orderTotalWithTip).toFixed(2) }}
          </span>
        </div>
        <div
          v-if="orderData.tx_ref"
          class="flex items-center justify-between text-xs pt-2 border-t border-slate-100 dark:border-slate-800"
        >
          <span class="text-slate-400 dark:text-slate-500">Transaction ID</span>
          <span class="font-mono text-slate-600 dark:text-slate-300 font-semibold">{{
            orderData.tx_ref
          }}</span>
        </div>
      </div>

      <button
        v-if="isPaymentPending"
        @click="handlePayNow"
        :disabled="isProcessingPayment"
        class="w-full py-4 rounded-xl font-bold text-slate-950 text-base shadow-lg transition-all disabled:opacity-50 disabled:cursor-not-allowed flex items-center justify-center gap-2 bg-primary hover:bg-primary-hover"
      >
        <span v-if="isProcessingPayment">Processing...</span>
        <template v-else>
          <span>💳</span>
          <span>Pay Now with Chapa - ETB {{ orderTotalWithTip.toFixed(2) }}</span>
        </template>
      </button>

      <div
        v-else-if="isPaymentPaid || isServed"
        class="w-full py-3.5 rounded-xl font-bold text-white text-base text-center flex items-center justify-center gap-2 shadow-sm bg-gradient-to-r from-emerald-600 to-teal-600"
      >
        <CheckCircle2 class="w-5 h-5 text-white" />
        <span>Payment Completed & Cleared</span>
      </div>

      <div
        v-if="orderData.payment_type === 'room_charge'"
        class="bg-blue-50 dark:bg-blue-950/40 border border-blue-200 dark:border-blue-800 rounded-xl px-4 py-3 text-sm text-blue-700 dark:text-blue-300"
      >
        <span class="font-medium">Room Charge</span> — This will be added to your room bill at
        checkout.
      </div>

      <div class="text-center">
        <button
          @click="refresh"
          :disabled="isLoading"
          class="text-sm text-slate-400 dark:text-slate-500 hover:text-slate-600 dark:hover:text-slate-300 transition-colors disabled:opacity-40"
        >
          Refresh
        </button>
        <p v-if="lastUpdate" class="text-xs text-slate-400 dark:text-slate-500 mt-1">
          Last updated: {{ formatTime(lastUpdate) }}
        </p>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, watch, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useOrderStatus } from '@/composables/useOrderStatus'
import {
  Clock,
  ChefHat,
  Bell,
  CheckCircle2,
  XCircle,
  Check,
  CreditCard,
  Utensils,
  Receipt,
  Sparkles,
} from 'lucide-vue-next'

const route = useRoute()
const router = useRouter()

const orderId = ref(route.params.orderId as string)

const hotelId = ref(
  (
    localStorage.getItem('hotel_id') ||
    localStorage.getItem('active_hotel_id') ||
    (route.query.hotel_id as string) ||
    ''
  ).toString(),
)

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

onMounted(async () => {
  const pendingTxRef =
    (route.query.tx_ref as string) ||
    (route.query.trx_ref as string) ||
    (route.query.transaction_ref as string) ||
    localStorage.getItem('pending_order_tx_ref') ||
    sessionStorage.getItem('pending_order_tx_ref')

  if (pendingTxRef) {
    try {
      console.log('[OrderStatus] Checking/verifying pending payment:', pendingTxRef)
      const res = await fetch(
        `${import.meta.env.VITE_API_BASE_URL}/api/payments/verify/${pendingTxRef}`,
      )
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

// Format item price taking line total (including tax) into account
const getItemPrice = (item: any): string => {
  if (item.total != null && Number(item.total) > 0) {
    return Number(item.total).toFixed(2)
  }
  const qty = Number(item.quantity) || 1
  const price = Number(item.price ?? item.unit_price ?? item.item_price_at_order ?? 0)
  return (price * qty).toFixed(2)
}

// Compute tip added by the customer
const orderTip = computed(() => {
  if (orderData.value?.tip != null && Number(orderData.value.tip) > 0) {
    return Number(orderData.value.tip)
  }
  if (orderData.value?.tip_amount != null && Number(orderData.value.tip_amount) > 0) {
    return Number(orderData.value.tip_amount)
  }
  try {
    const walkInData =
      localStorage.getItem('walk_in_payment_data') ||
      sessionStorage.getItem('walk_in_payment_data') ||
      localStorage.getItem('order_payment_data') ||
      sessionStorage.getItem('order_payment_data')
    if (walkInData) {
      const parsed = JSON.parse(walkInData)
      if (parsed.tip_amount && Number(parsed.tip_amount) > 0) {
        return Number(parsed.tip_amount)
      }
      if (parsed.calculation?.tip && Number(parsed.calculation.tip) > 0) {
        return Number(parsed.calculation.tip)
      }
    }
  } catch (_) {}
  return 0
})

// Compute final total including tip
const orderTotalWithTip = computed(() => {
  if (orderData.value?.final_total != null && Number(orderData.value.final_total) > 0) {
    return Number(orderData.value.final_total)
  }
  if (orderData.value?.amount_paid != null && Number(orderData.value.amount_paid) > 0) {
    return Number(orderData.value.amount_paid)
  }
  const base = Number(orderData.value?.total || orderData.value?.order_total || 0)
  return base + orderTip.value
})

// Navigation
const goBack = () => router.back()

// Payment handler - Navigate to Pay Your Order page
const handlePayNow = () => {
  if (!orderData.value) return

  try {
    const rawItems = orderData.value.items || orderData.value.order_items || []
    const formattedItems = rawItems.map((item: any, idx: number) => {
      const lineTotal = item.total != null ? Number(item.total) : null
      const qty = Number(item.quantity) || 1
      const effectiveUnitPrice = lineTotal
        ? lineTotal / qty
        : Number(item.price ?? item.item_price_at_order ?? item.unit_price ?? 0)
      return {
        id: item.id || item.menu_item_id || `item-${idx}`,
        name: item.name || item.item_name || item.menu_item?.name || 'Item',
        quantity: qty,
        price: effectiveUnitPrice,
        total: lineTotal ?? effectiveUnitPrice * qty,
      }
    })

    const isRoom =
      !orderData.value.table_number &&
      (!!orderData.value.room_number || orderData.value.order_type === 'room_service')
    const locationLabel = orderData.value.room_number
      ? `Room ${orderData.value.room_number}`
      : orderData.value.table_number
        ? `Table ${orderData.value.table_number}`
        : 'Order'

    const currentQrToken =
      (typeof qrToken === 'string' ? qrToken : '') ||
      localStorage.getItem('guest_qr_token') ||
      (route.query.qr_token as string) ||
      ''
    const currentHotelId =
      hotelId.value || orderData.value.hotel_id || localStorage.getItem('hotel_id') || ''
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
        tip: orderTip.value || 0,
      },
      amount: Number(orderData.value.total || 0) + (orderTip.value || 0),
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

    console.log(
      '[OrderStatus] Stored order data for payment page, navigating to /order/payment:',
      paymentPayload,
    )

    // Navigate to Pay Your Order page (Screenshot 2)
    router.push({
      path: '/order/payment',
      query: {
        order_id: currentOrderId,
        qr_token: currentQrToken || undefined,
        hotel_id: currentHotelId || undefined,
      },
    })
  } catch (err: any) {
    console.error('[OrderStatus] Failed to prepare payment navigation:', err)
  }
}
const statusIconComponent = computed(() => {
  if (isCancelled.value) return XCircle
  if (isServed.value) return CheckCircle2
  if (isReady.value) return Bell
  if (isPreparing.value) return ChefHat
  return Clock
})

const statusTitle = computed(() => {
  if (isPending.value) return 'Order Received'
  if (isPreparing.value) return 'Preparing Your Order'
  if (isReady.value) return 'Order Ready!'
  if (isServed.value) return 'Order Cleared & Completed'
  if (isCancelled.value) return 'Order Cancelled'
  return 'Order Status'
})

const statusMessage = computed(() => {
  if (isPending.value) return 'Your order has been received and will be prepared shortly.'
  if (isPreparing.value) return 'Our chef is preparing your delicious meal right now.'
  if (isReady.value) return 'Your order is ready! Please wait for delivery.'
  if (isServed.value)
    return 'Your order has been served, paid, and cleared. Thank you for dining with us!'
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
      hour12: true,
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
        icon: '/favicon.ico',
      })
    }
  }
})
</script>

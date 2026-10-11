<template>
  <div class="min-h-screen bg-slate-50 dark:bg-slate-950 p-4 transition-colors">
    <div class="max-w-md mx-auto py-6">
      <div
        class="bg-emerald-600 dark:bg-emerald-700 rounded-2xl p-4 mb-6 shadow-lg flex items-center gap-3"
      >
        <div
          class="w-10 h-10 bg-white/20 rounded-full flex items-center justify-center flex-shrink-0"
        >
          <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path
              stroke-linecap="round"
              stroke-linejoin="round"
              stroke-width="3"
              d="M5 13l4 4L19 7"
            ></path>
          </svg>
        </div>
        <span class="text-white font-semibold text-lg">Payment successful!</span>
      </div>

      <div
        class="bg-white dark:bg-slate-900 rounded-3xl shadow-lg border border-slate-200 dark:border-slate-800 overflow-hidden"
      >
        <div class="p-6 text-center border-b border-slate-100 dark:border-slate-800">
          <h1 class="text-2xl font-bold text-slate-900 dark:text-white mb-2">
            Payment Successful!
          </h1>
          <p class="text-sm text-slate-600 dark:text-slate-400">
            Thank you for your payment. Your order has been confirmed.
          </p>
        </div>

        <div class="p-6 space-y-6">
          <div class="flex items-center gap-2 text-slate-800 dark:text-slate-200 mb-4">
            <svg
              class="w-5 h-5 text-emerald-600 dark:text-emerald-400"
              fill="currentColor"
              viewBox="0 0 20 20"
            >
              <path d="M9 2a1 1 0 000 2h2a1 1 0 100-2H9z"></path>
              <path
                fill-rule="evenodd"
                d="M4 5a2 2 0 012-2 3 3 0 003 3h2a3 3 0 003-3 2 2 0 012 2v11a2 2 0 01-2 2H6a2 2 0 01-2-2V5zm3 4a1 1 0 000 2h.01a1 1 0 100-2H7zm3 0a1 1 0 000 2h3a1 1 0 100-2h-3zm-3 4a1 1 0 100 2h.01a1 1 0 100-2H7zm3 0a1 1 0 100 2h3a1 1 0 100-2h-3z"
                clip-rule="evenodd"
              ></path>
            </svg>
            <h2 class="text-lg font-bold">Payment Receipt</h2>
          </div>

          <div class="space-y-3 text-sm">
            <div
              class="flex justify-between items-center py-2 border-b border-slate-100 dark:border-slate-800"
            >
              <span class="text-slate-500 dark:text-slate-400">Transaction ID</span>
              <span class="font-mono text-slate-900 dark:text-slate-100 font-semibold">{{
                transactionId || '—'
              }}</span>
            </div>

            <div
              class="flex justify-between items-center py-2 border-b border-slate-100 dark:border-slate-800"
            >
              <span class="text-slate-500 dark:text-slate-400">Status</span>
              <span class="font-semibold text-emerald-600 dark:text-emerald-400">Paid</span>
            </div>

            <div
              class="flex justify-between items-center py-2 border-b border-slate-100 dark:border-slate-800"
            >
              <span class="text-slate-500 dark:text-slate-400">Order Number</span>
              <span class="font-semibold text-slate-900 dark:text-slate-100">{{
                orderNumber ? `#${orderNumber}` : '—'
              }}</span>
            </div>

            <div
              class="flex justify-between items-center py-2 border-b border-slate-100 dark:border-slate-800"
            >
              <span class="text-slate-500 dark:text-slate-400">Table / Room</span>
              <span class="font-semibold text-slate-900 dark:text-slate-100">{{
                tableNumber || roomNumber || '—'
              }}</span>
            </div>

            <div class="flex justify-between items-center py-3">
              <span class="text-slate-500 dark:text-slate-400">Amount Paid</span>
              <span class="text-2xl font-black text-slate-900 dark:text-white"
                >ETB {{ (amount || 0).toFixed(2) }}</span
              >
            </div>
          </div>

          <button
            @click="trackOrder"
            class="w-full py-4 bg-amber-500 hover:bg-amber-600 text-slate-950 rounded-2xl font-bold text-lg shadow-lg hover:shadow-amber-500/20 transition-all flex items-center justify-center gap-2 cursor-pointer"
          >
            Track My Order
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path
                stroke-linecap="round"
                stroke-linejoin="round"
                stroke-width="2"
                d="M13 7l5 5m0 0l-5 5m5-5H6"
              ></path>
            </svg>
          </button>

          <button
            @click="backToMenu"
            class="w-full py-3.5 bg-white dark:bg-slate-800 hover:bg-slate-50 dark:hover:bg-slate-700 text-slate-900 dark:text-slate-100 border-2 border-slate-200 dark:border-slate-700 rounded-2xl font-semibold text-base shadow-sm transition-all cursor-pointer"
          >
            Back to Menu
          </button>
        </div>
      </div>

      <div class="text-center mt-6 px-4">
        <p class="text-sm text-slate-500 dark:text-slate-400">
          Thank you for ordering with us! Need assistance? Our team is always ready to serve you.
        </p>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { useRouter, useRoute } from 'vue-router'

const router = useRouter()
const route = useRoute()

const transactionId = ref('')
const orderNumber = ref('')
const amount = ref(0)
const tableNumber = ref('')
const roomNumber = ref('')

const trackOrder = () => {
  const storedOrder = localStorage.getItem('pending_order_data')
  let orderId = (route.query.order_id as string) || localStorage.getItem('last_order_id')

  if (storedOrder && !orderId) {
    try {
      const orderData = JSON.parse(storedOrder)
      orderId = orderData.id || orderData.order_id
      console.log('[PaymentSuccess] Found order ID from stored data:', orderId)
    } catch (error) {
      console.error('[PaymentSuccess] Error parsing stored order:', error)
    }
  }

  if (orderId) {
    const qrToken = (route.query.qr_token as string) || localStorage.getItem('guest_qr_token')
    if (qrToken) {
      localStorage.setItem('guest_qr_token', qrToken)
    }
    const hotelId =
      (route.query.hotel_id as string) ||
      localStorage.getItem('hotel_id') ||
      localStorage.getItem('active_hotel_id')

    console.log('[PaymentSuccess] Navigating to order status:', {
      orderId,
      qrToken: qrToken ? qrToken.substring(0, 4) + '****' : 'none',
      hotelId: hotelId ? hotelId.substring(0, 8) + '...' : 'none',
    })

    router.push({
      name: 'order-status',
      params: { orderId },
      query: {
        qr_token: qrToken || undefined,
        hotel_id: hotelId || undefined,
        order_number: orderNumber.value || undefined,
      },
    })
  } else {
    console.error('[PaymentSuccess] No order ID found!')
    alert('Unable to track order. Order ID not found. Please check your order history.')
  }
}

const backToMenu = () => {
  const qrToken = localStorage.getItem('guest_qr_token')
  if (qrToken) {
    router.push({
      name: 'qr-menu',
      params: { token: qrToken },
    })
  } else {
    router.push('/')
  }
}

onMounted(() => {
  console.log('[PaymentSuccess] Page mounted')

  transactionId.value = (route.query.tx_ref as string) || ''
  orderNumber.value = (route.query.order_number as string) || ''
  amount.value = parseFloat((route.query.amount as string) || '0')

  const queryOrderId = route.query.order_id as string
  if (queryOrderId) {
    localStorage.setItem('last_order_id', queryOrderId)
    console.log('[PaymentSuccess] Stored order ID from query:', queryOrderId)
  }

  const storedOrder = localStorage.getItem('pending_order_data')
  if (storedOrder) {
    try {
      const orderData = JSON.parse(storedOrder)
      orderNumber.value = orderData.order_number || orderNumber.value
      amount.value = orderData.total || amount.value
      roomNumber.value = orderData.room_number || ''
      tableNumber.value = orderData.table_number || ''

      if (orderData.id || orderData.order_id) {
        const orderId = orderData.id || orderData.order_id
        localStorage.setItem('last_order_id', orderId)
        console.log('[PaymentSuccess] Stored order ID from order data:', orderId)
      }
    } catch (error) {
      console.error('[PaymentSuccess] Error parsing stored order:', error)
    }
  }

  console.log('[PaymentSuccess] Data loaded:', {
    transactionId: transactionId.value,
    orderNumber: orderNumber.value,
    amount: amount.value,
    tableNumber: tableNumber.value,
    roomNumber: roomNumber.value,
    hasOrderId: !!localStorage.getItem('last_order_id'),
  })
})
</script>

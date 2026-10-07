<template>
  <div class="min-h-screen bg-[#f5f0e8] p-4">
    <div class="max-w-md mx-auto py-6">
      
      <!-- Success Notification Toast -->
      <div class="bg-[#3d4f3d] rounded-2xl p-4 mb-6 shadow-lg flex items-center gap-3">
        <div class="w-10 h-10 bg-green-500 rounded-full flex items-center justify-center flex-shrink-0">
          <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"></path>
          </svg>
        </div>
        <span class="text-white font-semibold text-lg">Payment successful!</span>
      </div>

      <!-- Main Success Card -->
      <div class="bg-white rounded-3xl shadow-lg overflow-hidden">
        
        <!-- Header -->
        <div class="p-6 text-center border-b border-gray-200">
          <h1 class="text-2xl font-bold text-gray-900 mb-2">Payment Successful!</h1>
          <p class="text-sm text-gray-600">Thank you for your payment. Your order has been confirmed.</p>
        </div>

        <!-- Payment Receipt Section -->
        <div class="p-6 space-y-6">
          
          <!-- Receipt Header -->
          <div class="flex items-center gap-2 text-gray-700 mb-4">
            <svg class="w-5 h-5 text-red-600" fill="currentColor" viewBox="0 0 20 20">
              <path d="M9 2a1 1 0 000 2h2a1 1 0 100-2H9z"></path>
              <path fill-rule="evenodd" d="M4 5a2 2 0 012-2 3 3 0 003 3h2a3 3 0 003-3 2 2 0 012 2v11a2 2 0 01-2 2H6a2 2 0 01-2-2V5zm3 4a1 1 0 000 2h.01a1 1 0 100-2H7zm3 0a1 1 0 000 2h3a1 1 0 100-2h-3zm-3 4a1 1 0 100 2h.01a1 1 0 100-2H7zm3 0a1 1 0 100 2h3a1 1 0 100-2h-3z" clip-rule="evenodd"></path>
            </svg>
            <h2 class="text-lg font-bold">Payment Receipt</h2>
          </div>

          <!-- Receipt Details -->
          <div class="space-y-3 text-sm">
            <div class="flex justify-between items-center py-2 border-b border-gray-100">
              <span class="text-gray-500">Transaction ID</span>
              <span class="font-mono text-gray-900 font-semibold">{{ transactionId || 'AP5AFGGSXZT9' }}</span>
            </div>
            
            <div class="flex justify-between items-center py-2 border-b border-gray-100">
              <span class="text-gray-500">Status</span>
              <span class="font-semibold text-green-600">Paid</span>
            </div>
            
            <div class="flex justify-between items-center py-2 border-b border-gray-100">
              <span class="text-gray-500">Order Number</span>
              <span class="font-semibold text-gray-900">#{{ orderNumber || '160' }}</span>
            </div>
            
            <div class="flex justify-between items-center py-2 border-b border-gray-100">
              <span class="text-gray-500">Table</span>
              <span class="font-semibold text-gray-900">{{ tableNumber || roomNumber || 'Table 11' }}</span>
            </div>
            
            <div class="flex justify-between items-center py-3">
              <span class="text-gray-500">Amount Paid</span>
              <span class="text-2xl font-bold text-red-600">ETB {{ (amount || 540).toFixed(2) }}</span>
            </div>
          </div>

          <!-- Track My Order Button - PRIMARY CTA -->
          <button
            @click="trackOrder"
            class="w-full py-4 bg-gradient-to-r from-red-600 to-red-500 hover:from-red-700 hover:to-red-600 text-white rounded-2xl font-bold text-lg shadow-lg transition-all flex items-center justify-center gap-2"
          >
            Track My Order
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"></path>
            </svg>
          </button>

          <!-- Back to Menu Button -->
          <button
            @click="backToMenu"
            class="w-full py-3.5 bg-white hover:bg-gray-50 text-gray-900 border-2 border-gray-300 rounded-2xl font-semibold text-base shadow-sm transition-all"
          >
            Back to Menu
          </button>

        </div>
      </div>

      <!-- Footer Message -->
      <div class="text-center mt-6 px-4">
        <p class="text-sm text-gray-600">
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
  // Get order ID from multiple sources
  const storedOrder = localStorage.getItem('pending_order_data')
  let orderId = route.query.order_id as string || localStorage.getItem('last_order_id')
  
  // Try to get from stored order data
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
    const hotelId = (route.query.hotel_id as string) || localStorage.getItem('hotel_id') || localStorage.getItem('active_hotel_id')
    
    console.log('[PaymentSuccess] Navigating to order status:', {
      orderId,
      qrToken: qrToken ? qrToken.substring(0, 4) + '****' : 'none',
      hotelId: hotelId ? hotelId.substring(0, 8) + '...' : 'none'
    })
    
    router.push({
      name: 'order-status',
      params: { orderId },
      query: {
        qr_token: qrToken || undefined,
        hotel_id: hotelId || undefined,
        order_number: orderNumber.value || undefined
      }
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
      params: { token: qrToken }
    })
  } else {
    router.push('/')
  }
}

onMounted(() => {
  console.log('[PaymentSuccess] Page mounted')
  
  // Get data from route query parameters (from Chapa redirect)
  transactionId.value = (route.query.tx_ref as string) || ''
  orderNumber.value = (route.query.order_number as string) || ''
  amount.value = parseFloat((route.query.amount as string) || '540')
  
  // Get order ID from query
  const queryOrderId = route.query.order_id as string
  if (queryOrderId) {
    localStorage.setItem('last_order_id', queryOrderId)
    console.log('[PaymentSuccess] Stored order ID from query:', queryOrderId)
  }
  
  // Get data from localStorage (from order creation)
  const storedOrder = localStorage.getItem('pending_order_data')
  if (storedOrder) {
    try {
      const orderData = JSON.parse(storedOrder)
      orderNumber.value = orderData.order_number || orderNumber.value
      amount.value = orderData.total || amount.value
      roomNumber.value = orderData.room_number || ''
      tableNumber.value = orderData.table_number || ''
      
      // Store order ID for tracking
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
    hasOrderId: !!localStorage.getItem('last_order_id')
  })
})
</script>

<template>
  <div class="min-h-screen bg-[#f5f0e8] p-4">
    <div class="max-w-md mx-auto py-6">
      
      <!-- Verifying Payment Loading State -->
      <div v-if="isVerifying" class="min-h-[60vh] flex flex-col items-center justify-center">
        <div class="bg-white rounded-3xl shadow-lg p-8 text-center max-w-sm w-full">
          <!-- Animated Checkmark Circle -->
          <div class="mb-6 flex justify-center">
            <div class="relative">
              <div class="w-20 h-20 border-4 border-green-200 border-t-green-600 rounded-full animate-spin"></div>
              <div class="absolute inset-0 flex items-center justify-center">
                <svg class="w-10 h-10 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"></path>
                </svg>
              </div>
            </div>
          </div>
          
          <h2 class="text-2xl font-bold text-gray-900 mb-3">Verifying payment...</h2>
          <p class="text-gray-600 text-sm mb-4">Please wait while we confirm your payment</p>
          
          <div class="flex items-center justify-center gap-1">
            <div class="w-2 h-2 bg-green-500 rounded-full animate-bounce" style="animation-delay: 0ms"></div>
            <div class="w-2 h-2 bg-green-500 rounded-full animate-bounce" style="animation-delay: 150ms"></div>
            <div class="w-2 h-2 bg-green-500 rounded-full animate-bounce" style="animation-delay: 300ms"></div>
          </div>
        </div>
      </div>

      <!-- Success State (shown after verification completes) -->
      <div v-else>
        <!-- Demo Mode Warning (only shows when no tx_ref) -->
        <div v-if="!txRef" class="bg-yellow-100 border-2 border-yellow-400 rounded-2xl p-4 mb-4 shadow-lg">
          <div class="flex items-start gap-3">
            <div class="text-2xl flex-shrink-0">⚠️</div>
            <div class="text-sm">
              <p class="font-bold text-yellow-900 mb-1">Demo Mode Active</p>
              <p class="text-yellow-800">You're viewing demo data because you navigated directly to this page. To test the real payment flow, start from the QR Menu page and complete a payment.</p>
            </div>
          </div>
        </div>
      
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
              <span class="font-mono text-gray-900 font-semibold">{{ txRef || 'AP5AFGGSXZT9' }}</span>
            </div>
            
            <div class="flex justify-between items-center py-2 border-b border-gray-100">
              <span class="text-gray-500">Status</span>
              <span class="font-semibold text-green-600">Paid</span>
            </div>
            
            <div class="flex justify-between items-center py-2 border-b border-gray-100">
              <span class="text-gray-500">Order Number</span>
              <span class="font-semibold text-gray-900">#{{ orderData?.order_number || '160' }}</span>
            </div>
            
            <div class="flex justify-between items-center py-2 border-b border-gray-100">
              <span class="text-gray-500">{{ orderData?.is_walk_in ? 'Table' : 'Room' }}</span>
              <span class="font-semibold text-gray-900">{{ orderData?.is_walk_in ? (orderData?.table_number || 'Table 11') : (orderData?.room_number || roomNumber || 'Room Guest') }}</span>
            </div>
            
            <div class="flex justify-between items-center py-3">
              <span class="text-gray-500">Amount Paid</span>
              <span class="text-2xl font-bold text-red-600">ETB {{ formatAmountSimple(orderData?.calculation?.total || 540) }}</span>
            </div>
          </div>

          <!-- Track My Order Button - PRIMARY CTA -->
          <button
            @click="trackOrder"
            v-if="orderData?.is_walk_in !== false"
            class="w-full py-4 bg-gradient-to-r from-red-600 to-red-500 hover:from-red-700 hover:to-red-600 text-white rounded-2xl font-bold text-lg shadow-lg transition-all flex items-center justify-center gap-2"
          >
            Track My Order
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"></path>
            </svg>
          </button>
          
          <!-- Debug: Manual Complete Button (only shows if tx_ref exists but order not completed) -->
          <button
            v-if="txRef && !orderData?.id"
            @click="manualComplete"
            class="w-full py-3 bg-blue-600 hover:bg-blue-700 text-white rounded-2xl font-semibold text-sm shadow-sm transition-all"
          >
            🔧 Manual Complete Order (Debug)
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
      <!-- End of v-else (success state) -->

    </div>
  </div>
</template>

<script setup lang="ts">
import { onMounted, ref } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import { generateAndDownloadReceipt } from '@/services/receiptService'

const router = useRouter()
const route = useRoute()

const txRef = ref<string>('')
const orderData = ref<any>(null)
const roomNumber = ref<string>('')
const isLoading = ref(false)
const isVerifying = ref(true)  // Add this - for loading state

onMounted(async () => {
  console.log('[OrderPaymentSuccess] Mounted')
  console.log('[OrderPaymentSuccess] Query params:', route.query)
  
  // Try to get tx_ref from multiple sources
  txRef.value = (route.query.tx_ref as string) || 
                (route.query.trx_ref as string) || 
                (route.query.transaction_ref as string) || 
                localStorage.getItem('pending_order_tx_ref') || 
                sessionStorage.getItem('pending_order_tx_ref') || ''
  
  console.log('[OrderPaymentSuccess] tx_ref resolved:', txRef.value)

  // Check BOTH localStorage AND sessionStorage (localStorage persists through redirects)
  const walkInData = localStorage.getItem('walk_in_payment_data') || sessionStorage.getItem('walk_in_payment_data')
  console.log('[OrderPaymentSuccess] walk_in_payment_data (localStorage):', localStorage.getItem('walk_in_payment_data'))
  console.log('[OrderPaymentSuccess] walk_in_payment_data (sessionStorage):', sessionStorage.getItem('walk_in_payment_data'))
  const isWalkInOrder = !!walkInData
  
  if (walkInData) {
    try {
      const data = JSON.parse(walkInData)
      console.log('[OrderPaymentSuccess] Parsed walk-in data:', data)
      orderData.value = {
        ...data,
        is_walk_in: true,
        room_number: null,
      }
      
      // Sync QR token and hotel ID to storage for subsequent status and websocket calls
      if (data.qr_token) {
        localStorage.setItem('guest_qr_token', data.qr_token)
        sessionStorage.setItem('guest_qr_token', data.qr_token)
      }
      if (data.hotel_id) {
        localStorage.setItem('hotel_id', data.hotel_id)
      }
      
      // Get tx_ref from sessionStorage if not in URL
      if (!txRef.value && data.tx_ref) {
        txRef.value = data.tx_ref
        console.log('[OrderPaymentSuccess] Using tx_ref from sessionStorage:', txRef.value)
      }
      
      // Store order ID if present in walk-in data
      if (data.order_id) {
        localStorage.setItem('last_order_id', data.order_id)
        console.log('[OrderPaymentSuccess] Stored order ID from walk-in data:', data.order_id)
      }
    } catch (error) {
      console.error('[OrderPaymentSuccess] Error parsing walk-in data:', error)
    }
  } else {
    const roomServiceData = localStorage.getItem('order_payment_data') || sessionStorage.getItem('order_payment_data')
    console.log('[OrderPaymentSuccess] order_payment_data (localStorage):', localStorage.getItem('order_payment_data'))
    console.log('[OrderPaymentSuccess] order_payment_data (sessionStorage):', sessionStorage.getItem('order_payment_data'))
    if (roomServiceData) {
      try {
        const data = JSON.parse(roomServiceData)
        console.log('[OrderPaymentSuccess] Parsed room service data:', data)
        orderData.value = {
          ...data,
          is_walk_in: false,
        }
        roomNumber.value = data.room_number || 'N/A'
        
        // Sync QR token and hotel ID to storage
        if (data.qr_token) {
          localStorage.setItem('guest_qr_token', data.qr_token)
          sessionStorage.setItem('guest_qr_token', data.qr_token)
        }
        if (data.hotel_id) {
          localStorage.setItem('hotel_id', data.hotel_id)
        }
        
        // Get tx_ref from sessionStorage if not in URL
        if (!txRef.value && data.tx_ref) {
          txRef.value = data.tx_ref
          console.log('[OrderPaymentSuccess] Using tx_ref from room service sessionStorage:', txRef.value)
        }
        
        // Store order ID if present in room service data
        if (data.order_id) {
          localStorage.setItem('last_order_id', data.order_id)
          console.log('[OrderPaymentSuccess] Stored order ID from room service data:', data.order_id)
        }
      } catch (error) {
        console.error('[OrderPaymentSuccess] Error parsing room service data:', error)
      }
    }
  }

  // Also sync QR token from query parameter if present
  if (route.query.qr_token) {
    localStorage.setItem('guest_qr_token', route.query.qr_token as string)
    sessionStorage.setItem('guest_qr_token', route.query.qr_token as string)
  }

  // Provide realistic fallback if visited directly or after session clear
  if (!orderData.value) {
    const demoOrderId = Math.floor(Math.random() * 1000) + 1 // Random order ID between 1-1000
    orderData.value = {
      id: demoOrderId, // Add the missing ID field
      order_id: demoOrderId, // Alternative field for compatibility
      order_number: 'ORD-' + (txRef.value ? txRef.value.substring(0, 8).toUpperCase() : 'DEMO' + Math.floor(1000 + Math.random() * 9000)),
      is_walk_in: true,
      table_number: 'Table 4',
      room_number: null,
      estimated_time: 30,
      items: [
        { name: 'Special Tibs', quantity: 1, total: 420 },
        { name: 'Shiro Tegabino', quantity: 1, total: 220 },
        { name: 'Fresh Juice', quantity: 2, total: 160 },
      ],
      calculation: {
        subtotal: 800,
        tax: 120,
        service_charge: 80,
        total: 1000,
      }
    }
    
    // Store in localStorage so it persists
    localStorage.setItem('last_order_id', demoOrderId.toString())
    console.log('[OrderPaymentSuccess] Demo order created with ID:', demoOrderId)
  }
  
  console.log('[OrderPaymentSuccess] After loading session data:')
  console.log('- txRef:', txRef.value)
  console.log('- orderData:', orderData.value)
  console.log('- localStorage keys:', Object.keys(localStorage))
  console.log('- sessionStorage keys:', Object.keys(sessionStorage))
  console.log('- localStorage.walk_in_payment_data:', localStorage.getItem('walk_in_payment_data'))
  console.log('- sessionStorage.walk_in_payment_data:', sessionStorage.getItem('walk_in_payment_data'))
  
  // If still no tx_ref, we can't proceed with payment verification
  if (!txRef.value) {
    console.warn('[OrderPaymentSuccess] ⚠️ No tx_ref found - entering demo mode')
    console.info('[OrderPaymentSuccess] 💡 To test real payments, start from QR Menu and complete checkout')
    
    // Check if we have order_id directly in URL or storage
    const directOrderId = (route.query.order_id as string) || localStorage.getItem('last_order_id')
    if (directOrderId) {
      console.log('[OrderPaymentSuccess] Found direct order ID, storing:', directOrderId)
      orderData.value = { ...orderData.value, id: directOrderId, order_id: directOrderId }
      localStorage.setItem('last_order_id', directOrderId)
    }
    
    isVerifying.value = false // Stop showing loading state
    return // Skip payment verification if no tx_ref
  }

  if (txRef.value) {
    try {
      console.log('[OrderPaymentSuccess] Verifying payment with tx_ref:', txRef.value)
      const verifyResponse = await fetch(
        `http://127.0.0.1:8000/api/payments/verify/${txRef.value}`,
        {
          method: 'GET',
          headers: {
            'Content-Type': 'application/json',
            'Accept': 'application/json',
          },
        }
      )

      const verifyData = await verifyResponse.json()
      console.log('[OrderPaymentSuccess] Verify response:', verifyData)

      if (verifyResponse.ok && verifyData.success) {
        let completeEndpoint = ''
        if (isWalkInOrder) {
          completeEndpoint = `http://127.0.0.1:8000/api/walk-in-payments/complete/${txRef.value}`
        } else {
          completeEndpoint = `http://127.0.0.1:8000/api/order-payments/complete/${txRef.value}`
        }

        console.log('[OrderPaymentSuccess] Completing payment at:', completeEndpoint)
        const completeResponse = await fetch(completeEndpoint, {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
          },
        })

        const completeData = await completeResponse.json()
        console.log('[OrderPaymentSuccess] Complete response:', completeData)

        if (completeResponse.ok && completeData.success && completeData.order) {
          console.log('[OrderPaymentSuccess] Order completed, ID:', completeData.order.id)
          
          if (isWalkInOrder && walkInData) {
            const walkInPaymentData = JSON.parse(walkInData)
            orderData.value = {
              ...orderData.value,
              id: completeData.order.id, // Store order ID
              order_id: completeData.order.id, // Alternative field
              order_number: completeData.order.order_number,
              table_number: walkInPaymentData.table_number || completeData.order.table_number,
              room_number: null,
              estimated_time: completeData.order.estimated_time || 30,
              items: walkInPaymentData.items || orderData.value?.items || [],
              calculation: walkInPaymentData.calculation || orderData.value?.calculation,
              is_walk_in: true,
            }
          } else {
            orderData.value = {
              ...orderData.value,
              id: completeData.order.id, // Store order ID
              order_id: completeData.order.id, // Alternative field
              order_number: completeData.order.order_number,
              room_number: completeData.order.room?.room_number || orderData.value?.room_number,
              estimated_time: completeData.order.estimated_time || 30,
              items: completeData.order.order_items?.map((item: any) => ({
                name: item.menu_item?.name || 'Unknown',
                quantity: item.quantity,
                total: item.line_total,
              })) || orderData.value?.items || [],
              calculation: orderData.value?.calculation,
              is_walk_in: false,
            }
          }
          
          // Store order ID in localStorage for tracking
          if (completeData.order.id) {
            localStorage.setItem('last_order_id', completeData.order.id)
            console.log('[OrderPaymentSuccess] Stored order ID in localStorage:', completeData.order.id)
          }
        }
      }
    } catch (error) {
      console.error('[OrderPaymentSuccess] Error verifying/completing payment:', error)
    } finally {
      // Always stop loading state after verification attempt
      isVerifying.value = false
    }
  }
  
  console.log('[OrderPaymentSuccess] Final orderData:', orderData.value)
})

function formatAmountSimple(price?: number): string {
  return Number(price || 0).toFixed(2)
}

function formatPrice(price?: number): string {
  return `${Number(price || 0).toLocaleString()} ETB`
}

function trackOrder(): void {
  console.log('[OrderPaymentSuccess] trackOrder called')
  console.log('[OrderPaymentSuccess] orderData:', orderData.value)
  
  // PRIORITY 1: Get order ID directly from orderData (from payment completion API)
  let orderId = orderData.value?.id || orderData.value?.order_id
  console.log('[OrderPaymentSuccess] Order ID from orderData:', orderId)
  
  // PRIORITY 2: Check localStorage last_order_id
  if (!orderId) {
    orderId = localStorage.getItem('last_order_id')
    console.log('[OrderPaymentSuccess] Order ID from localStorage last_order_id:', orderId)
  }
  
  // PRIORITY 3: Check pending_order_data in localStorage
  if (!orderId) {
    const storedOrder = localStorage.getItem('pending_order_data')
    if (storedOrder) {
      try {
        const orderDataParsed = JSON.parse(storedOrder)
        orderId = orderDataParsed.id || orderDataParsed.order_id
        console.log('[OrderPaymentSuccess] Order ID from pending_order_data:', orderId)
      } catch (error) {
        console.error('[OrderPaymentSuccess] Error parsing stored order:', error)
      }
    }
  }
  
  // PRIORITY 4: Check walk_in_payment_data in localStorage OR sessionStorage
  if (!orderId) {
    const walkInData = localStorage.getItem('walk_in_payment_data') || sessionStorage.getItem('walk_in_payment_data')
    if (walkInData) {
      try {
        const data = JSON.parse(walkInData)
        orderId = data.order_id || data.id
        console.log('[OrderPaymentSuccess] Order ID from walk_in_payment_data:', orderId)
      } catch (error) {
        console.error('[OrderPaymentSuccess] Error parsing walk-in data:', error)
      }
    }
  }
  
  // PRIORITY 5: Fetch order by transaction reference as last resort
  if (!orderId && txRef.value) {
    console.log('[OrderPaymentSuccess] Attempting to fetch order by tx_ref:', txRef.value)
    fetchOrderByTxRef(txRef.value)
    return
  }
  
  console.log('[OrderPaymentSuccess] Final Order ID:', orderId)
  
  if (orderId) {
    const qrToken = orderData.value?.qr_token || 
                    (route.query.qr_token as string) || 
                    localStorage.getItem('guest_qr_token') || 
                    sessionStorage.getItem('guest_qr_token')
    
    if (qrToken) {
      localStorage.setItem('guest_qr_token', qrToken)
    }

    const hotelId = orderData.value?.hotel_id || 
                    (route.query.hotel_id as string) || 
                    localStorage.getItem('hotel_id') || 
                    localStorage.getItem('active_hotel_id')
    
    console.log('[OrderPaymentSuccess] Navigating to order status with:', {
      orderId,
      qrToken: qrToken ? qrToken.substring(0, 8) + '...' : 'none',
      hotelId: hotelId ? hotelId.substring(0, 8) + '...' : 'none'
    })
    
    router.push({
      name: 'order-status',
      params: { orderId },
      query: {
        qr_token: qrToken || undefined,
        hotel_id: hotelId || undefined,
        order_number: orderData.value?.order_number || undefined
      }
    })
  } else {
    console.error('[OrderPaymentSuccess] No order ID found after checking all sources!')
    console.error('[OrderPaymentSuccess] Debug info:', {
      orderData: orderData.value,
      localStorage_last_order_id: localStorage.getItem('last_order_id'),
      localStorage_pending_order_data: localStorage.getItem('pending_order_data'),
      localStorage_walk_in: localStorage.getItem('walk_in_payment_data'),
      sessionStorage_walk_in: sessionStorage.getItem('walk_in_payment_data'),
      txRef: txRef.value,
      query_params: route.query
    })
    alert('Unable to track order. Order ID not found. Please contact staff or check your order history.')
  }
}

async function fetchOrderByTxRef(txRefValue: string): Promise<void> {
  try {
    console.log('[OrderPaymentSuccess] Fetching order by tx_ref:', txRefValue)
    const response = await fetch(`http://127.0.0.1:8000/api/order-payments/${txRefValue}`, {
      headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json'
      }
    })
    
    if (response.ok) {
      const data = await response.json()
      console.log('[OrderPaymentSuccess] Fetched order data:', data)
      
      if (data.success && data.order?.id) {
        const orderId = data.order.id
        localStorage.setItem('last_order_id', orderId)
        console.log('[OrderPaymentSuccess] Got order ID from API, redirecting:', orderId)
        
        const qrToken = data.order?.qr_token || 
                        data.order?.table?.qr_token || 
                        data.order?.room?.qr_token || 
                        orderData.value?.qr_token || 
                        localStorage.getItem('guest_qr_token')
        if (qrToken) {
          localStorage.setItem('guest_qr_token', qrToken)
        }
        
        const hotelId = data.order?.hotel_id || 
                        orderData.value?.hotel_id || 
                        localStorage.getItem('hotel_id') || 
                        localStorage.getItem('active_hotel_id')
        
        router.push({
          name: 'order-status',
          params: { orderId },
          query: {
            qr_token: qrToken || undefined,
            hotel_id: hotelId || undefined,
            order_number: data.order.order_number || undefined
          }
        })
        return
      }
    }
    
    throw new Error('Failed to fetch order by transaction reference')
  } catch (error) {
    console.error('[OrderPaymentSuccess] Error fetching order by tx_ref:', error)
    alert('Unable to track order. Please contact staff for assistance.')
  }
}

function backToMenu(): void {
  const qrToken = orderData.value?.qr_token || localStorage.getItem('guest_qr_token')
  if (qrToken) {
    router.push({
      name: 'qr-menu',
      params: { token: qrToken }
    })
  } else {
    router.push('/')
  }
}

async function manualComplete(): Promise<void> {
  if (!txRef.value) {
    alert('No tx_ref found. Please complete a payment first.')
    return
  }
  
  try {
    console.log('[OrderPaymentSuccess] Manual complete triggered')
    console.log('[OrderPaymentSuccess] tx_ref:', txRef.value)
    
    // Get walk-in data
    const walkInDataString = localStorage.getItem('walk_in_payment_data') || sessionStorage.getItem('walk_in_payment_data')
    const isWalkInOrder = !!walkInDataString
    
    // Verify payment
    const verifyResponse = await fetch(`http://127.0.0.1:8000/api/payments/verify/${txRef.value}`, {
      headers: { 'Accept': 'application/json' }
    })
    const verifyData = await verifyResponse.json()
    console.log('[Manual] Verify response:', verifyData)
    
    if (!verifyResponse.ok || !verifyData.success) {
      alert('Payment verification failed: ' + (verifyData.message || 'Unknown error'))
      return
    }
    
    // Complete order
    const completeEndpoint = isWalkInOrder 
      ? `http://127.0.0.1:8000/api/walk-in-payments/complete/${txRef.value}`
      : `http://127.0.0.1:8000/api/order-payments/complete/${txRef.value}`
    
    console.log('[Manual] Completing at:', completeEndpoint)
    const completeResponse = await fetch(completeEndpoint, {
      method: 'POST',
      headers: { 'Accept': 'application/json', 'Content-Type': 'application/json' }
    })
    const completeData = await completeResponse.json()
    console.log('[Manual] Complete response:', completeData)
    
    if (completeResponse.ok && completeData.success && completeData.order) {
      // Update orderData with real order
      orderData.value = {
        ...orderData.value,
        id: completeData.order.id,
        order_id: completeData.order.id,
        order_number: completeData.order.order_number
      }
      
      localStorage.setItem('last_order_id', completeData.order.id)
      
      alert('Order completed successfully! Order ID: ' + completeData.order.id)
      console.log('[Manual] Order ID stored:', completeData.order.id)
    } else {
      alert('Order completion failed: ' + (completeData.message || 'Unknown error'))
    }
  } catch (error: any) {
    console.error('[Manual] Error:', error)
    alert('Error: ' + error.message)
  }
}

async function downloadReceipt(): Promise<void> {
  if (!orderData.value) {
    alert('Error: Order details not found. Please refresh the page and try again.')
    return
  }

  const referenceId = txRef.value || orderData.value.tx_ref || 'ORDER-' + Date.now()

  try {
    isLoading.value = true
    
    await generateAndDownloadReceipt({
      booking_reference: orderData.value.order_number || 'ORD-' + referenceId.substring(0, 8).toUpperCase(),
      first_name: orderData.value.is_walk_in ? 'Table' : 'Room',
      last_name: orderData.value.is_walk_in ? String(orderData.value.table_number || '') : String(orderData.value.room_number || roomNumber.value || 'Guest'),
      email: orderData.value.email || 'guest@hotel.com',
      phone: orderData.value.phone || 'N/A',
      check_in_date: new Date().toISOString().split('T')[0],
      check_out_date: new Date().toISOString().split('T')[0],
      room_number: orderData.value.is_walk_in ? `Table ${orderData.value.table_number || ''}` : (orderData.value.room_number || roomNumber.value || 'N/A'),
      number_of_guests: 1,
      total_amount: orderData.value.calculation?.total || 0,
      currency: 'ETB',
      status: 'Confirmed',
      tx_ref: referenceId,
      payment_date: new Date().toISOString(),
      special_requests: `Order Items:\n${orderData.value.items?.map((item: any) => `• ${item.name} x${item.quantity} - ${formatPrice(item.total)}`).join('\n') || 'N/A'}`,
    })
  } catch (error: any) {
    console.error('[OrderPaymentSuccess] Failed to generate receipt:', error)
    alert('Failed to generate receipt: ' + error.message)
  } finally {
    isLoading.value = false
  }
}

function goToMenu(): void {
  const qrToken = orderData.value?.qr_token || localStorage.getItem('guest_qr_token')
  if (qrToken) {
    router.push({
      name: 'qr-menu',
      params: { token: qrToken }
    })
  } else {
    router.push('/')
  }
}

function goHome(): void {
  sessionStorage.removeItem('order_payment_data')
  sessionStorage.removeItem('walk_in_payment_data')
  router.push('/')
}
</script>

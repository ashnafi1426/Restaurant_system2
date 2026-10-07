<template>
  <div class="min-h-screen bg-gradient-to-br from-slate-900 via-slate-800 to-slate-900">
    <!-- Header -->
    <header class="sticky top-0 z-30 bg-slate-900/95 backdrop-blur-md border-b border-slate-800/80">
      <div class="max-w-md mx-auto px-4 py-4 flex items-center gap-3">
        <button
          @click="goBack"
          class="p-2 -ml-2 rounded-xl text-red-500 hover:text-red-400 hover:bg-slate-800 transition-colors flex items-center justify-center"
          title="Back"
          aria-label="Back"
        >
          <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M15 19l-7-7 7-7" />
          </svg>
        </button>
        <div class="flex-1 min-w-0">
          <h1 class="text-2xl font-black text-red-500 tracking-tight leading-tight">Pay Your Order</h1>
          <p class="text-xs text-slate-400 font-medium flex items-center gap-1.5 mt-0.5">
            <svg class="w-3.5 h-3.5 text-slate-400 flex-shrink-0" fill="currentColor" viewBox="0 0 24 24">
              <path d="M11 9H9V2H7v7H5V2H3v7c0 2.12 1.66 3.84 3.75 3.97V22h2.5v-9.03C11.34 12.84 13 11.12 13 9V2h-2v7zm5-3v8h2.5v8H21V2c-2.76 0-5 2.24-5 4z"/>
            </svg>
            <span class="truncate">{{ displayLocation }}</span>
          </p>
        </div>
      </div>
    </header>

    <div class="max-w-md mx-auto px-4 py-6 space-y-4">
      <!-- Your Order Card -->
      <div class="bg-slate-800 rounded-2xl p-5 shadow-xl border border-slate-700">
        <div class="flex items-center justify-between mb-4">
          <div class="flex items-center gap-3">
            <div class="bg-red-500/20 p-2.5 rounded-xl border border-red-500/20">
              <svg class="w-5 h-5 text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <rect x="2" y="5" width="20" height="14" rx="2" stroke-width="2" />
                <line x1="2" y1="10" x2="22" y2="10" stroke-width="2" />
              </svg>
            </div>
            <div>
              <h2 class="text-white font-bold">Your Order</h2>
              <p class="text-slate-400 text-sm">{{ items.length }} {{ items.length === 1 ? 'item' : 'items' }}</p>
            </div>
          </div>
          <div class="text-right">
            <p class="text-2xl font-black text-red-400">ETB {{ subtotal.toFixed(2) }}</p>
          </div>
        </div>

        <!-- Order Items -->
        <div class="space-y-3 border-t border-slate-700 pt-4">
          <div
            v-for="item in items"
            :key="item.id"
            class="flex items-start justify-between gap-3 pb-3 border-b border-slate-700/50 last:border-0"
          >
            <div class="flex-1">
              <h3 class="text-white font-bold text-sm">{{ item.name }}</h3>
              <p class="text-slate-400 text-xs">Qty: {{ item.quantity }}</p>
            </div>
            <p class="text-red-400 font-black text-sm whitespace-nowrap">ETB {{ (item.price * item.quantity).toFixed(2) }}</p>
          </div>
        </div>
      </div>

      <!-- Add Tip Card -->
      <div class="bg-slate-800 rounded-2xl p-5 shadow-xl border border-slate-700">
        <div class="flex items-center gap-2 mb-4">
          <svg class="w-5 h-5 text-yellow-400" fill="currentColor" viewBox="0 0 20 20">
            <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" />
          </svg>
          <h2 class="text-white font-bold text-base">Add Tip?</h2>
        </div>

        <!-- Tip Buttons with Real SVG Icons -->
        <div class="grid grid-cols-4 gap-2 mb-3">
          <button
            v-for="tipOption in tipOptions"
            :key="tipOption.value"
            @click="selectTip(tipOption.value)"
            :class="[
              'flex flex-col items-center justify-center py-3 rounded-xl font-bold text-xs transition-all border-2',
              selectedTip === tipOption.value
                ? 'bg-yellow-500/20 border-yellow-500 text-yellow-400'
                : 'bg-slate-700/50 border-slate-600 text-slate-300 hover:bg-slate-700 hover:border-slate-500'
            ]"
          >
            <!-- 10% Thumbs Up -->
            <svg
              v-if="tipOption.icon === 'thumbs-up'"
              class="w-6 h-6 mb-1 text-yellow-400"
              fill="none"
              stroke="currentColor"
              stroke-width="2"
              viewBox="0 0 24 24"
            >
              <path stroke-linecap="round" stroke-linejoin="round" d="M14 10h4.764a2 2 0 011.789 2.894l-3.5 7A2 2 0 0115.263 21h-4.017c-.163 0-.326-.02-.485-.06L7 20m7-10V5a2 2 0 00-2-2h-.095c-.5 0-.905.405-.905.905 0 .714-.211 1.412-.608 2.006L7 11v9m7-10h-2M7 20H5a2 2 0 01-2-2v-6a2 2 0 012-2h2" />
            </svg>

            <!-- 15% Smile Face -->
            <svg
              v-else-if="tipOption.icon === 'smile'"
              class="w-6 h-6 mb-1 text-yellow-400"
              fill="none"
              stroke="currentColor"
              stroke-width="2"
              viewBox="0 0 24 24"
            >
              <circle cx="12" cy="12" r="9" />
              <path stroke-linecap="round" stroke-linejoin="round" d="M9 10h.01M15 10h.01M9.5 15a3.5 3.5 0 005 0" />
            </svg>

            <!-- 20% Sparkles -->
            <svg
              v-else-if="tipOption.icon === 'sparkles'"
              class="w-6 h-6 mb-1 text-yellow-400"
              fill="none"
              stroke="currentColor"
              stroke-width="2"
              viewBox="0 0 24 24"
            >
              <path stroke-linecap="round" stroke-linejoin="round" d="M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z" />
            </svg>

            <!-- 25% Heart -->
            <svg
              v-else-if="tipOption.icon === 'heart'"
              class="w-6 h-6 mb-1 text-rose-500"
              fill="currentColor"
              viewBox="0 0 24 24"
            >
              <path d="M12 21.35l-1.45-1.32C5.4 15.36 2 12.28 2 8.5 2 5.42 4.42 3 7.5 3c1.74 0 3.41.81 4.5 2.09C13.09 3.81 14.76 3 16.5 3 19.58 3 22 5.42 22 8.5c0 3.78-3.4 6.86-8.55 11.54L12 21.35z"/>
            </svg>

            <span>{{ tipOption.label }}</span>
            <span class="text-[10px] text-slate-400 mt-0.5">ETB {{ calculateTipAmount(tipOption.value).toFixed(2) }}</span>
          </button>
        </div>

        <!-- Custom & No Tip Buttons with Real SVG Icons -->
        <div class="grid grid-cols-2 gap-2">
          <button
            @click="openCustomTip"
            :class="[
              'py-3 rounded-xl font-bold text-sm transition-all border-2 flex items-center justify-center gap-2',
              selectedTip === 'custom'
                ? 'bg-yellow-500/20 border-yellow-500 text-yellow-400'
                : 'bg-slate-700/50 border-slate-600 text-slate-300 hover:bg-slate-700'
            ]"
          >
            <svg class="w-4 h-4 text-emerald-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
              <rect x="2" y="6" width="20" height="12" rx="2" />
              <circle cx="12" cy="12" r="3" />
              <path stroke-linecap="round" d="M6 12h.01M18 12h.01" />
            </svg>
            <span>Custom</span>
          </button>
          <button
            @click="selectTip(0)"
            :class="[
              'py-3 rounded-xl font-bold text-sm transition-all border-2 flex items-center justify-center gap-2',
              selectedTip === 0
                ? 'bg-slate-600 border-slate-500 text-white'
                : 'bg-slate-700/50 border-slate-600 text-slate-300 hover:bg-slate-700'
            ]"
          >
            <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
              <circle cx="12" cy="12" r="9" />
              <path stroke-linecap="round" d="M5.636 5.636l12.728 12.728" />
            </svg>
            <span>No Tip</span>
          </button>
        </div>
      </div>

      <!-- Total Card -->
      <div class="bg-gradient-to-br from-slate-800 to-slate-900 rounded-2xl p-5 shadow-xl border-2 border-slate-700">
        <div class="space-y-2 mb-4">
          <div class="flex justify-between text-slate-300 text-sm">
            <span>Order Total</span>
            <span class="font-bold">ETB {{ subtotal.toFixed(2) }}</span>
          </div>
          <div v-if="tipAmount > 0" class="flex justify-between text-yellow-400 text-sm">
            <span>Tip ({{ typeof selectedTip === 'number' && selectedTip > 0 ? selectedTip + '%' : 'Custom' }})</span>
            <span class="font-bold">ETB {{ tipAmount.toFixed(2) }}</span>
          </div>
        </div>

        <div class="border-t-2 border-slate-600 pt-4 mb-4">
          <div class="flex justify-between items-center">
            <h3 class="text-white text-2xl font-black">Total</h3>
            <p class="text-red-400 text-3xl font-black">ETB {{ total.toFixed(2) }}</p>
          </div>
          <p class="text-slate-400 text-xs mt-1">Secure with Chapa</p>
        </div>

        <!-- Pay Now Button -->
        <button
          @click="proceedToPayment"
          :disabled="isProcessing"
          class="w-full bg-gradient-to-r from-red-500 to-red-600 hover:from-red-600 hover:to-red-700 text-white font-black text-lg py-4 rounded-2xl shadow-lg transition-all disabled:opacity-50 disabled:cursor-not-allowed flex items-center justify-center gap-2"
        >
          <svg v-if="!isProcessing" class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"></path>
          </svg>
          <svg v-else class="w-6 h-6 animate-spin" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
          </svg>
          <span>{{ isProcessing ? 'Processing...' : 'Pay Now' }}</span>
        </button>

        <p class="text-center text-slate-400 text-xs mt-3 flex items-center justify-center gap-2">
          <span class="w-2 h-2 rounded-full bg-emerald-500 inline-block"></span>
          <span>Secure Payment • Double-click Protected</span>
        </p>
      </div>
    </div>

    <!-- Custom Tip Modal -->
    <Teleport to="body">
      <Transition name="fade">
        <div
          v-if="showCustomTipModal"
          class="fixed inset-0 bg-black/80 z-50 flex items-center justify-center p-4"
          @click.self="showCustomTipModal = false"
        >
          <div class="bg-slate-800 rounded-2xl p-6 max-w-sm w-full border border-slate-700">
            <h3 class="text-xl font-bold text-white mb-4">Enter Custom Tip</h3>
            <input
              v-model.number="customTipAmount"
              type="number"
              min="0"
              step="0.01"
              placeholder="Enter amount in ETB"
              class="w-full px-4 py-3 bg-slate-900 border border-slate-600 rounded-xl text-white placeholder-slate-400 focus:outline-none focus:border-yellow-500 transition"
            />
            <div class="flex gap-3 mt-4">
              <button
                @click="showCustomTipModal = false"
                class="flex-1 px-4 py-3 bg-slate-700 hover:bg-slate-600 text-white font-bold rounded-xl transition"
              >
                Cancel
              </button>
              <button
                @click="applyCustomTip"
                class="flex-1 px-4 py-3 bg-yellow-500 hover:bg-yellow-600 text-slate-900 font-bold rounded-xl transition"
              >
                Apply
              </button>
            </div>
          </div>
        </div>
      </Transition>
    </Teleport>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import { useRouter, useRoute } from 'vue-router'

const router = useRouter()
const route = useRoute()

// Data from route/localStorage
const items = ref<any[]>([])
const tableNumber = ref<string>('')
const qrToken = ref<string>('')
const hotelId = ref<string>('')

const displayLocation = computed(() => {
  if (!tableNumber.value) return 'Table 11'
  const t = tableNumber.value.trim()
  if (t.toLowerCase().startsWith('table') || t.toLowerCase().startsWith('room')) {
    return t
  }
  return `Table ${t}`
})

// Tip state
const selectedTip = ref<number | 'custom'>(0)
const customTipAmount = ref<number>(0)
const showCustomTipModal = ref(false)
const isProcessing = ref(false)

// Tip options
const tipOptions = [
  { value: 10, label: '10%', icon: 'thumbs-up' },
  { value: 15, label: '15%', icon: 'smile' },
  { value: 20, label: '20%', icon: 'sparkles' },
  { value: 25, label: '25%', icon: 'heart' },
]

// Calculations
const subtotal = computed(() => {
  return items.value.reduce((sum, item) => sum + (item.price * item.quantity), 0)
})

const tipAmount = computed(() => {
  if (selectedTip.value === 'custom') {
    return customTipAmount.value
  } else if (typeof selectedTip.value === 'number' && selectedTip.value > 0) {
    return (subtotal.value * selectedTip.value) / 100
  }
  return 0
})

const total = computed(() => {
  return subtotal.value + tipAmount.value
})

const calculateTipAmount = (percentage: number) => {
  return (subtotal.value * percentage) / 100
}

// Methods
const goBack = () => {
  router.back()
}

const selectTip = (value: number) => {
  selectedTip.value = value
  customTipAmount.value = 0
}

const openCustomTip = () => {
  showCustomTipModal.value = true
  customTipAmount.value = 0
}

const applyCustomTip = () => {
  if (customTipAmount.value > 0) {
    selectedTip.value = 'custom'
  }
  showCustomTipModal.value = false
}

const proceedToPayment = async () => {
  if (isProcessing.value) return
  
  isProcessing.value = true

  try {
    // Get payment data from localStorage
    const paymentDataStr = localStorage.getItem('walk_in_payment_data')
    if (!paymentDataStr) {
      console.error('[OrderPayment] No payment data found')
      alert('Payment data not found. Please try again.')
      router.push('/qr-menu/' + qrToken.value)
      return
    }

    const paymentData = JSON.parse(paymentDataStr)
    
    // Add tip to payment data
    const updatedPaymentData = {
      ...paymentData,
      tip_amount: tipAmount.value,
      tip_percentage: typeof selectedTip.value === 'number' ? selectedTip.value : null,
      amount: total.value, // Update total amount with tip
      calculation: {
        ...paymentData.calculation,
        tip: tipAmount.value,
        total: total.value
      }
    }

    // Update localStorage
    localStorage.setItem('walk_in_payment_data', JSON.stringify(updatedPaymentData))

    console.log('[OrderPayment] Proceeding to payment with tip:', {
      subtotal: subtotal.value,
      tip: tipAmount.value,
      total: total.value
    })

    const apiBase = import.meta.env.VITE_API_BASE_URL || 'http://127.0.0.1:8000'
    let endpoint = `${apiBase}/api/walk-in-payments/initialize`
    let bodyPayload: any = {}

    const isExistingOrder = !!paymentData.is_existing_order || !!paymentData.order_id
    if (isExistingOrder) {
      const isRoomOrder = !!paymentData.is_room_order || !!paymentData.room_number || paymentData.order_type === 'room_service'
      if (isRoomOrder) {
        endpoint = `${apiBase}/api/order-payments/initialize-existing`
      } else {
        endpoint = `${apiBase}/api/walk-in-payments/initialize-for-order`
      }
      bodyPayload = {
        order_id: paymentData.order_id || paymentData.id,
        tip: tipAmount.value,
        first_name: localStorage.getItem('guest_first_name') || paymentData.first_name || 'Guest',
        last_name: localStorage.getItem('guest_last_name') || paymentData.last_name || 'Customer',
        email: localStorage.getItem('guest_email') || paymentData.email || 'guest@restaurant.com',
        phone: localStorage.getItem('guest_phone') || paymentData.phone || '+251900000000'
      }
    } else {
      bodyPayload = {
        table_id: paymentData.table_id || '',
        qr_token: qrToken.value,
        items: items.value.map(item => ({
          menu_item_id: item.id || item.menu_item_id,
          quantity: item.quantity
        })),
        special_requests: '',
        tip: tipAmount.value,
        first_name: 'Guest',
        last_name: 'Customer',
        email: 'guest@restaurant.com',
        phone: '+251900000000'
      }
    }

    console.log('[OrderPayment] Initializing payment via:', endpoint, bodyPayload)

    // Initialize payment with backend
    const response = await fetch(endpoint, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
        'X-Hotel-ID': hotelId.value
      },
      body: JSON.stringify(bodyPayload)
    })

    const result = await response.json()

    if (result.success && result.checkout_url) {
      if (result.tx_ref) {
        localStorage.setItem('pending_order_tx_ref', result.tx_ref)
        sessionStorage.setItem('pending_order_tx_ref', result.tx_ref)
      }
      console.log('[OrderPayment] Redirecting to Chapa:', result.checkout_url)
      // Redirect to Chapa payment
      window.location.href = result.checkout_url
    } else {
      throw new Error(result.message || 'Failed to initialize payment')
    }
  } catch (error: any) {
    console.error('[OrderPayment] Payment error:', error)
    alert(`Payment Error: ${error.message || 'Failed to process payment'}`)
    isProcessing.value = false
  }
}

// Load data on mount
onMounted(() => {
  // Get QR token from route
  qrToken.value = (route.query.qr_token as string) || localStorage.getItem('guest_qr_token') || ''
  hotelId.value = localStorage.getItem('hotel_id') || localStorage.getItem('active_hotel_id') || ''
  
  // Get payment data from localStorage or sessionStorage
  const paymentDataStr = localStorage.getItem('walk_in_payment_data') ||
                         sessionStorage.getItem('walk_in_payment_data') ||
                         localStorage.getItem('order_payment_data') ||
                         sessionStorage.getItem('order_payment_data')
  if (paymentDataStr) {
    const paymentData = JSON.parse(paymentDataStr)
    items.value = paymentData.items || []
    tableNumber.value = paymentData.table_number || (paymentData.room_number ? `Room ${paymentData.room_number}` : '')
    
    console.log('[OrderPayment] Loaded payment data:', {
      items: items.value.length,
      table: tableNumber.value,
      subtotal: subtotal.value
    })
  } else {
    console.error('[OrderPayment] No payment data found in localStorage')
    // Redirect back to menu if no data
    router.push('/qr-menu/' + qrToken.value)
  }
})
</script>

<style scoped>
.fade-enter-active,
.fade-leave-active {
  transition: opacity 0.3s ease;
}

.fade-enter-from,
.fade-leave-to {
  opacity: 0;
}
</style>
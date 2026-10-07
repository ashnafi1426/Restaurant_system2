<template>
  <div class="min-h-screen bg-gradient-to-br from-slate-900 via-slate-800 to-slate-900">
    <!-- Header -->
    <div class="bg-gradient-to-r from-red-600 to-red-700 px-4 py-4 flex items-center gap-3 shadow-lg">
      <button @click="goBack" class="text-white hover:bg-white/20 p-2 rounded-lg transition-colors">
        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
        </svg>
      </button>
      <div class="flex-1">
        <h1 class="text-xl font-bold text-white">Pay Your Order</h1>
        <p class="text-sm text-red-100 flex items-center gap-1">
          <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
            <path d="M10.394 2.08a1 1 0 00-.788 0l-7 3a1 1 0 000 1.84L5.25 8.051a.999.999 0 01.356-.257l4-1.714a1 1 0 11.788 1.838L7.667 9.088l1.94.831a1 1 0 00.787 0l7-3a1 1 0 000-1.838l-7-3zM3.31 9.397L5 10.12v4.102a8.969 8.969 0 00-1.05-.174 1 1 0 01-.89-.89 11.115 11.115 0 01.25-3.762zM9.3 16.573A9.026 9.026 0 007 14.935v-3.957l1.818.78a3 3 0 002.364 0l5.508-2.361a11.026 11.026 0 01.25 3.762 1 1 0 01-.89.89 8.968 8.968 0 00-5.35 2.524 1 1 0 01-1.4 0zM6 18a1 1 0 001-1v-2.065a8.935 8.935 0 00-2-.712V17a1 1 0 001 1z"></path>
          </svg>
          {{ tableNumber || 'Table 11' }}
        </p>
      </div>
    </div>

    <div class="max-w-md mx-auto px-4 py-6 space-y-4">
      <!-- Your Order Card -->
      <div class="bg-slate-800 rounded-2xl p-5 shadow-xl border border-slate-700">
        <div class="flex items-center justify-between mb-4">
          <div class="flex items-center gap-3">
            <div class="bg-red-500/20 p-2 rounded-lg">
              <svg class="w-5 h-5 text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path>
              </svg>
            </div>
            <div>
              <h2 class="text-white font-bold">Your Order</h2>
              <p class="text-slate-400 text-sm">{{ items.length }} items</p>
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
            <path fill-rule="evenodd" d="M5 2a1 1 0 011 1v1h1a1 1 0 010 2H6v1a1 1 0 01-2 0V6H3a1 1 0 010-2h1V3a1 1 0 011-1zm0 10a1 1 0 011 1v1h1a1 1 0 110 2H6v1a1 1 0 11-2 0v-1H3a1 1 0 110-2h1v-1a1 1 0 011-1zM12 2a1 1 0 01.967.744L14.146 7.2 17.5 9.134a1 1 0 010 1.732l-3.354 1.935-1.18 4.455a1 1 0 01-1.933 0L9.854 12.8 6.5 10.866a1 1 0 010-1.732l3.354-1.935 1.18-4.455A1 1 0 0112 2z" clip-rule="evenodd"></path>
          </svg>
          <h2 class="text-white font-bold">Add Tip?</h2>
        </div>

        <!-- Tip Buttons -->
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
            <span class="text-2xl mb-1">{{ tipOption.emoji }}</span>
            <span>{{ tipOption.label }}</span>
            <span class="text-[10px] text-slate-400">ETB {{ calculateTipAmount(tipOption.value).toFixed(2) }}</span>
          </button>
        </div>

        <!-- Custom & No Tip Buttons -->
        <div class="grid grid-cols-2 gap-2">
          <button
            @click="openCustomTip"
            :class="[
              'py-3 rounded-xl font-bold text-sm transition-all border-2',
              selectedTip === 'custom'
                ? 'bg-yellow-500/20 border-yellow-500 text-yellow-400'
                : 'bg-slate-700/50 border-slate-600 text-slate-300 hover:bg-slate-700'
            ]"
          >
            💵 Custom
          </button>
          <button
            @click="selectTip(0)"
            :class="[
              'py-3 rounded-xl font-bold text-sm transition-all border-2',
              selectedTip === 0
                ? 'bg-slate-600 border-slate-500 text-white'
                : 'bg-slate-700/50 border-slate-600 text-slate-300 hover:bg-slate-700'
            ]"
          >
            No Tip
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

        <p class="text-center text-slate-500 text-xs mt-3">
          Secure Payment • Double-click Protected
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

// Tip state
const selectedTip = ref<number | 'custom'>(0)
const customTipAmount = ref<number>(0)
const showCustomTipModal = ref(false)
const isProcessing = ref(false)

// Tip options
const tipOptions = [
  { value: 10, label: '10%', emoji: '👍' },
  { value: 15, label: '15%', emoji: '😊' },
  { value: 20, label: '20%', emoji: '✨' },
  { value: 25, label: '25%', emoji: '❤️' },
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

    // Initialize payment with backend
    const response = await fetch('http://127.0.0.1:8000/api/walk-in-payments/initialize', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
        'X-Hotel-ID': hotelId.value
      },
      body: JSON.stringify({
        table_id: paymentData.table_id || '',
        qr_token: qrToken.value,
        items: items.value.map(item => ({
          menu_item_id: item.id,
          quantity: item.quantity
        })),
        special_requests: '',
        tip: tipAmount.value,
        first_name: 'Guest',
        last_name: 'Customer',
        email: 'guest@restaurant.com',
        phone: '+251900000000'
      })
    })

    const result = await response.json()

    if (result.success && result.checkout_url) {
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
  
  // Get payment data from localStorage
  const paymentDataStr = localStorage.getItem('walk_in_payment_data')
  if (paymentDataStr) {
    const paymentData = JSON.parse(paymentDataStr)
    items.value = paymentData.items || []
    tableNumber.value = paymentData.table_number || ''
    
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
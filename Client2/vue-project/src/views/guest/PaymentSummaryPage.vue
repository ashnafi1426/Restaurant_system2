<template>
  <div class="min-h-screen bg-gray-900 text-white">
    <!-- Header -->
    <div class="bg-gray-800 px-4 py-4 flex items-center gap-3 sticky top-0 z-10">
      <button @click="goBack" class="text-white hover:text-gray-300 p-1">
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
        <h1 class="text-xl font-bold text-red-500">Pay Your Order</h1>
        <p class="text-sm text-gray-400 flex items-center gap-1">
          <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
            <path
              d="M10.707 2.293a1 1 0 00-1.414 0l-7 7a1 1 0 001.414 1.414L4 10.414V17a1 1 0 001 1h2a1 1 0 001-1v-2a1 1 0 011-1h2a1 1 0 011 1v2a1 1 0 001 1h2a1 1 0 001-1v-6.586l.293.293a1 1 0 001.414-1.414l-7-7z"
            ></path>
          </svg>
          {{
            orderData?.room_number
              ? 'Room ' + orderData.room_number
              : orderData?.table_number
                ? 'Table ' + orderData.table_number
                : 'Table'
          }}
        </p>
      </div>
    </div>

    <!-- Loading -->
    <div v-if="isLoading" class="flex justify-center items-center py-20">
      <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-red-500"></div>
    </div>

    <!-- Content -->
    <div v-else-if="orderData" class="p-4 space-y-4 pb-8">
      <!-- Your Order Summary Card -->
      <div class="bg-gradient-to-r from-red-700 to-red-600 rounded-2xl p-5 shadow-xl">
        <div class="flex items-center justify-between">
          <div class="flex items-center gap-3">
            <div class="w-14 h-14 bg-red-800/50 rounded-xl flex items-center justify-center">
              <svg class="w-8 h-8 text-white" fill="currentColor" viewBox="0 0 20 20">
                <path
                  d="M3 1a1 1 0 000 2h1.22l.305 1.222a.997.997 0 00.01.042l1.358 5.43-.893.892C3.74 11.846 4.632 14 6.414 14H15a1 1 0 000-2H6.414l1-1H14a1 1 0 00.894-.553l3-6A1 1 0 0017 3H6.28l-.31-1.243A1 1 0 005 1H3zM16 16.5a1.5 1.5 0 11-3 0 1.5 1.5 0 013 0zM6.5 18a1.5 1.5 0 100-3 1.5 1.5 0 000 3z"
                ></path>
              </svg>
            </div>
            <div>
              <h2 class="text-lg font-bold text-white">Your Order</h2>
              <p class="text-white/90 text-sm">{{ orderData.items?.length || 0 }} items</p>
            </div>
          </div>
          <div class="text-right">
            <p class="text-3xl font-bold text-white">ETB {{ (orderData.total || 0).toFixed(2) }}</p>
          </div>
        </div>
      </div>

      <!-- Order Items -->
      <div class="bg-gray-800 rounded-2xl overflow-hidden shadow-lg">
        <div
          v-for="(item, index) in orderData.items"
          :key="item.id || index"
          class="px-4 py-4 border-b border-gray-700 last:border-b-0"
        >
          <div class="flex items-start justify-between">
            <div class="flex-1">
              <h3 class="font-semibold text-white text-base">{{ item.name }}</h3>
              <p class="text-sm text-gray-400 mt-1">Qty: {{ item.quantity }}</p>
            </div>
            <div class="text-right ml-4">
              <p class="text-lg font-bold text-red-500">
                ETB {{ ((item.price || 0) * item.quantity).toFixed(2) }}
              </p>
            </div>
          </div>
        </div>
      </div>

      <!-- Tip Section -->
      <div class="bg-gray-800 rounded-2xl p-6 shadow-lg">
        <div class="flex items-center justify-between mb-4">
          <h3 class="text-lg font-semibold text-white">🎁 Add Tip?</h3>
        </div>

        <!-- Tip Options -->
        <div class="grid grid-cols-4 gap-2 mb-3">
          <button
            v-for="tipOption in [10, 15, 20]"
            :key="tipOption"
            @click="selectTip(tipOption)"
            :class="[
              'py-3 rounded-xl text-center transition-all',
              selectedTip === tipOption
                ? 'bg-yellow-500 text-gray-900'
                : 'bg-gray-700 text-gray-300 hover:bg-gray-600',
            ]"
          >
            <div class="text-2xl mb-1">
              {{ tipOption === 10 ? '👍' : tipOption === 15 ? '😊' : '🌟' }}
            </div>
            <div class="text-xs font-semibold">{{ tipOption }}%</div>
            <div class="text-xs">
              ETB {{ (((orderData.total || 0) * tipOption) / 100).toFixed(2) }}
            </div>
          </button>

          <button
            @click="selectTip(0)"
            :class="[
              'py-3 rounded-xl text-center transition-all',
              selectedTip === 0
                ? 'bg-red-500 text-white'
                : 'bg-gray-700 text-gray-300 hover:bg-gray-600',
            ]"
          >
            <div class="text-sm font-semibold">No Tip</div>
          </button>
        </div>

        <!-- Custom Tip -->
        <button
          @click="showCustomTip = !showCustomTip"
          class="w-full py-2 border-2 border-gray-600 rounded-xl text-gray-300 hover:border-red-500 transition-colors"
        >
          $ Custom
        </button>

        <div v-if="showCustomTip" class="mt-3">
          <input
            v-model.number="customTipAmount"
            type="number"
            placeholder="Enter custom tip amount"
            class="w-full px-4 py-3 bg-gray-700 text-white rounded-xl border-2 border-gray-600 focus:border-red-500 outline-none"
            @input="selectCustomTip"
          />
        </div>
      </div>

      <!-- Order Total -->
      <div class="bg-gray-800 rounded-2xl p-6 shadow-lg space-y-3">
        <div class="flex justify-between text-gray-300">
          <span>Order Total</span>
          <span class="font-semibold">ETB {{ (orderData.total || 0).toFixed(2) }}</span>
        </div>

        <div v-if="tipAmount > 0" class="flex justify-between text-gray-300">
          <span>Tip ({{ selectedTip }}%)</span>
          <span class="font-semibold">ETB {{ tipAmount.toFixed(2) }}</span>
        </div>

        <div class="pt-3 border-t-2 border-gray-700 flex justify-between items-center">
          <div>
            <p class="text-2xl font-bold text-white">Total</p>
            <p class="text-sm text-gray-400">Secure with Chapa</p>
          </div>
          <p class="text-3xl font-bold text-red-500">ETB {{ finalTotal.toFixed(2) }}</p>
        </div>
      </div>

      <!-- Pay Now Button -->
      <button
        @click="proceedToPayment"
        :disabled="isProcessing"
        class="w-full py-5 bg-gradient-to-r from-red-600 to-orange-600 rounded-2xl font-bold text-white text-lg shadow-xl hover:shadow-2xl transition-all disabled:opacity-50 disabled:cursor-not-allowed flex items-center justify-center gap-2"
      >
        <svg v-if="!isProcessing" class="w-6 h-6" fill="currentColor" viewBox="0 0 20 20">
          <path d="M4 4a2 2 0 00-2 2v1h16V6a2 2 0 00-2-2H4z"></path>
          <path
            fill-rule="evenodd"
            d="M18 9H2v5a2 2 0 002 2h12a2 2 0 002-2V9zM4 13a1 1 0 011-1h1a1 1 0 110 2H5a1 1 0 01-1-1zm5-1a1 1 0 100 2h1a1 1 0 100-2H9z"
            clip-rule="evenodd"
          ></path>
        </svg>
        <span v-if="isProcessing">Processing...</span>
        <span v-else>Pay Now</span>
      </button>

      <!-- Security Badge -->
      <div class="flex items-center justify-center gap-2 text-sm text-gray-400">
        <svg class="w-5 h-5 text-green-500" fill="currentColor" viewBox="0 0 20 20">
          <path
            fill-rule="evenodd"
            d="M2.166 4.999A11.954 11.954 0 0010 1.944 11.954 11.954 0 0017.834 5c.11.65.166 1.32.166 2.001 0 5.225-3.34 9.67-8 11.317C5.34 16.67 2 12.225 2 7c0-.682.057-1.35.166-2.001zm11.541 3.708a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"
            clip-rule="evenodd"
          ></path>
        </svg>
        <span>Secure Payment • Double-click Protected</span>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import { useRouter, useRoute } from 'vue-router'

const router = useRouter()
const route = useRoute()

const orderData = ref<any>(null)
const isLoading = ref(true)
const isProcessing = ref(false)
const selectedTip = ref(0)
const showCustomTip = ref(false)
const customTipAmount = ref(0)

const orderId = route.params.orderId as string
const hotelId = ref(
  localStorage.getItem('hotel_id') || localStorage.getItem('active_hotel_id') || '',
)

// Computed
const tipAmount = computed(() => {
  if (selectedTip.value === -1) {
    return customTipAmount.value
  }
  return ((orderData.value?.total || 0) * selectedTip.value) / 100
})

const finalTotal = computed(() => {
  return (orderData.value?.total || 0) + tipAmount.value
})

// Methods
const goBack = () => router.back()

const selectTip = (percentage: number) => {
  selectedTip.value = percentage
  showCustomTip.value = false
  customTipAmount.value = 0
}

const selectCustomTip = () => {
  selectedTip.value = -1
}

const proceedToPayment = async () => {
  if (isProcessing.value) return

  isProcessing.value = true

  try {
    const guestInfo = {
      first_name: localStorage.getItem('guest_first_name') || 'Guest',
      last_name: localStorage.getItem('guest_last_name') || 'User',
      email: localStorage.getItem('guest_email') || `guest${Date.now()}@hotel.com`,
      phone: localStorage.getItem('guest_phone') || '+251911000000',
    }

    const paymentPayload = {
      order_id: orderData.value.id || orderData.value.order_id,
      ...guestInfo,
    }

    console.log('[PaymentSummary] Initializing payment:', paymentPayload)

    const response = await fetch(
      `${import.meta.env.VITE_API_BASE_URL}/api/order-payments/initialize-existing`,
      {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          Accept: 'application/json',
          'X-Hotel-ID': hotelId.value,
        },
        body: JSON.stringify(paymentPayload),
      },
    )

    const result = await response.json()

    if (result.success && result.checkout_url) {
      console.log('[PaymentSummary] Redirecting to Chapa:', result.checkout_url)
      // Redirect to Chapa checkout
      window.location.href = result.checkout_url
    } else {
      throw new Error(result.message || 'Failed to initialize payment')
    }
  } catch (error: any) {
    console.error('[PaymentSummary] Payment error:', error)
    alert(`Payment Error: ${error.message || 'Failed to initialize payment'}`)
  } finally {
    isProcessing.value = false
  }
}

// Lifecycle
onMounted(async () => {
  try {
    // Try to get order data from localStorage first
    const storedData = localStorage.getItem('pending_order_data')
    if (storedData) {
      const parsedData = JSON.parse(storedData)
      if (parsedData.id === orderId || parsedData.order_id === orderId) {
        orderData.value = parsedData
        isLoading.value = false
        return
      }
    }

    // If not in localStorage, fetch from API
    const qrToken = localStorage.getItem('guest_qr_token')
    if (qrToken) {
      const response = await fetch(
        `${import.meta.env.VITE_API_BASE_URL}/api/guest/orders/${orderId}/realtime-status?qr_token=${qrToken}`,
        {
          headers: {
            'X-Hotel-ID': hotelId.value,
            Accept: 'application/json',
          },
        },
      )

      const result = await response.json()
      if (result.success && result.data) {
        orderData.value = result.data
      }
    }
  } catch (error) {
    console.error('[PaymentSummary] Error loading order:', error)
  } finally {
    isLoading.value = false
  }
})
</script>

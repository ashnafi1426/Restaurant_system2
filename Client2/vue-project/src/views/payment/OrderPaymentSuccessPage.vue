<template>
  <div class="success-container min-h-screen bg-gradient-to-br from-green-50 to-emerald-100 py-12 flex items-center justify-center">
    <!-- Main Success Card -->
    <div class="max-w-2xl w-full mx-auto px-4">
      <div class="bg-white rounded-2xl shadow-2xl overflow-hidden">
        <!-- Success Header -->
        <div class="bg-gradient-to-r from-green-500 to-emerald-600 px-8 py-12 text-center transition-all duration-500" :class="showHeader ? 'opacity-100 scale-100' : 'opacity-0 scale-95'">
          <!-- Success Icon -->
          <div class="flex justify-center mb-6">
            <div class="w-20 h-20 bg-white rounded-full flex items-center justify-center animate-pulse-success">
              <svg
                class="w-12 h-12 text-green-600"
                fill="currentColor"
                viewBox="0 0 20 20"
              >
                <path
                  fill-rule="evenodd"
                  d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"
                  clip-rule="evenodd"
                />
              </svg>
            </div>
          </div>

          <h1 class="text-4xl font-bold text-white mb-3">Payment Successful!</h1>
          <p class="text-green-50 text-lg">
            Your order has been confirmed and sent to the kitchen
          </p>
        </div>

        <!-- Content -->
        <div class="p-8">
          <!-- Success Message -->
          <div class="mb-8 text-center transition-all duration-500" :class="showHeader ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-4'">
            <h2 class="text-2xl font-semibold text-slate-900 mb-3">
              Thank you for your order
            </h2>
            <p class="text-slate-600">
              Your order has been successfully placed and payment has been processed. Our kitchen is preparing your meal!
            </p>
          </div>

          <!-- Success Alert -->
          <div class="bg-green-50 border border-green-200 rounded-lg p-4 mb-8 transition-all duration-500" :class="showSuccess ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-4'">
            <div class="flex items-start gap-3">
              <svg
                class="w-5 h-5 text-green-600 mt-0.5 flex-shrink-0"
                fill="currentColor"
                viewBox="0 0 20 20"
              >
                <path
                  fill-rule="evenodd"
                  d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"
                  clip-rule="evenodd"
                />
              </svg>
              <div>
                <h3 class="font-semibold text-green-900">Payment Verified</h3>
                <p class="text-green-700 text-sm mt-1">
                  Your payment has been securely processed and your order is confirmed.
                </p>
              </div>
            </div>
          </div>

          <!-- Order Details -->
          <div v-if="orderData" class="space-y-6 transition-all duration-500" :class="showDetails ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-4'">
            <!-- Order Confirmation Section Header -->
            <div class="bg-gradient-to-r from-green-50 to-emerald-50 rounded-lg p-4 border border-green-200">
              <h3 class="font-bold text-green-900 text-lg mb-2">✓ ORDER CONFIRMED</h3>
              <p class="text-green-700 text-sm">Your order is being prepared by our kitchen</p>
            </div>

            <!-- Order Details -->
            <div class="border-b pb-6">
              <h3 class="font-semibold text-slate-900 mb-4 flex items-center gap-2">
                <svg class="w-5 h-5 text-green-600" fill="currentColor" viewBox="0 0 20 20">
                  <path d="M3 1a1 1 0 000 2h1.22l.305 1.222a.997.997 0 00.01.042l1.358 5.43-.893.892C3.74 11.846 4.632 14 6.414 14H15a1 1 0 000-2H6.414l1-1H14a1 1 0 00.894-.553l3-6A1 1 0 0017 3H6.28l-.31-1.243A1 1 0 005 1H3zM16 16.5a1.5 1.5 0 11-3 0 1.5 1.5 0 013 0zM6.5 18a1.5 1.5 0 100-3 1.5 1.5 0 000 3z"/>
                </svg>
                Order Details
              </h3>
              <div class="grid grid-cols-2 gap-4">
                <!-- Order Reference -->
                <div class="bg-slate-50 rounded-lg p-4 border border-slate-200">
                  <p class="text-slate-600 text-xs font-semibold uppercase tracking-wide mb-2">Order Number</p>
                  <p class="text-slate-900 font-mono text-sm font-bold break-all">
                    {{ orderData.order_number || 'ORD-' + txRef?.substring(0, 8).toUpperCase() }}
                  </p>
                </div>

                <!-- Status -->
                <div class="bg-slate-50 rounded-lg p-4 border border-slate-200">
                  <p class="text-slate-600 text-xs font-semibold uppercase tracking-wide mb-2">Status</p>
                  <span class="inline-block px-4 py-2 bg-green-100 text-green-800 rounded-full text-sm font-bold">
                    ✓ CONFIRMED
                  </span>
                </div>

                <!-- Room -->
                <div class="bg-slate-50 rounded-lg p-4 border border-slate-200">
                  <p class="text-slate-600 text-xs font-semibold uppercase tracking-wide mb-2">Room Number</p>
                  <p class="text-slate-900 font-medium text-base">
                    {{ orderData.room_number || roomNumber }}
                  </p>
                </div>

                <!-- Estimated Time -->
                <div class="bg-slate-50 rounded-lg p-4 border border-slate-200">
                  <p class="text-slate-600 text-xs font-semibold uppercase tracking-wide mb-2">Estimated Delivery</p>
                  <p class="text-slate-900 font-medium text-base">
                    {{ orderData.estimated_time || 30 }} minutes
                  </p>
                </div>
              </div>
            </div>

            <!-- Order Items -->
            <div class="border-b pb-6">
              <h3 class="font-semibold text-slate-900 mb-4 flex items-center gap-2">
                <svg class="w-5 h-5 text-amber-600" fill="currentColor" viewBox="0 0 20 20">
                  <path d="M9 2a1 1 0 000 2h2a1 1 0 100-2H9z"/>
                  <path fill-rule="evenodd" d="M4 5a2 2 0 012-2 3 3 0 003 3h2a3 3 0 003-3 2 2 0 012 2v11a2 2 0 01-2 2H6a2 2 0 01-2-2V5zm3 4a1 1 0 000 2h.01a1 1 0 100-2H7zm3 0a1 1 0 000 2h3a1 1 0 100-2h-3zm-3 4a1 1 0 100 2h.01a1 1 0 100-2H7zm3 0a1 1 0 100 2h3a1 1 0 100-2h-3z" clip-rule="evenodd"/>
                </svg>
                Your Order Items
              </h3>
              <div class="space-y-3">
                <div
                  v-for="(item, index) in orderData.items"
                  :key="index"
                  class="bg-amber-50 rounded-lg p-4 border border-amber-200 flex justify-between items-center"
                >
                  <div>
                    <p class="font-medium text-slate-900">{{ item.name }}</p>
                    <p class="text-sm text-slate-600">Quantity: {{ item.quantity }}</p>
                  </div>
                  <p class="font-bold text-amber-600">{{ formatPrice(item.total) }}</p>
                </div>
              </div>
            </div>

            <!-- Payment Information -->
            <div class="border-b pb-6 transition-all duration-500" :class="showPayment ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-4'">
              <h3 class="font-semibold text-slate-900 mb-4 flex items-center gap-2">
                <svg class="w-5 h-5 text-orange-600" fill="currentColor" viewBox="0 0 20 20">
                  <path d="M4 4a2 2 0 00-2 2v4a2 2 0 002 2V6h10a2 2 0 00-2-2H4zm2 6a2 2 0 012-2h8a2 2 0 012 2v4a2 2 0 01-2 2H8a2 2 0 01-2-2v-4zm6 4a2 2 0 100-4 2 2 0 000 4z"/>
                </svg>
                Payment Information
              </h3>
              <div class="grid grid-cols-1 gap-4">
                <!-- Transaction Reference -->
                <div class="bg-orange-50 rounded-lg p-5 border border-orange-200">
                  <p class="text-orange-600 text-xs font-semibold uppercase tracking-wide mb-2">Transaction Reference</p>
                  <p class="text-slate-900 font-mono text-sm break-all font-bold bg-white rounded px-3 py-2">
                    {{ txRef }}
                  </p>
                </div>

                <!-- Price Breakdown -->
                <div class="bg-slate-50 rounded-lg p-5 border border-slate-200 space-y-2">
                  <div class="flex justify-between text-sm">
                    <span class="text-slate-600">Subtotal:</span>
                    <span class="font-medium">{{ formatPrice(orderData.calculation?.subtotal || 0) }}</span>
                  </div>
                  <div class="flex justify-between text-sm">
                    <span class="text-slate-600">Tax (15%):</span>
                    <span class="font-medium">{{ formatPrice(orderData.calculation?.tax || 0) }}</span>
                  </div>
                  <div class="flex justify-between text-sm">
                    <span class="text-slate-600">Service Charge (10%):</span>
                    <span class="font-medium">{{ formatPrice(orderData.calculation?.service_charge || 0) }}</span>
                  </div>
                </div>

                <!-- Amount Paid - Prominent -->
                <div class="bg-gradient-to-r from-orange-100 to-amber-100 rounded-lg p-6 border-2 border-orange-300">
                  <p class="text-orange-600 text-xs font-semibold uppercase tracking-wider mb-3">Total Amount Paid</p>
                  <div class="flex items-baseline justify-between">
                    <p class="text-5xl font-bold text-orange-600">
                      {{ formatPrice(orderData.calculation?.total || 0) }}
                    </p>
                  </div>
                  <p class="text-orange-700 text-sm mt-2 font-medium">✓ Payment Confirmed and Secure</p>
                </div>

                <!-- Payment Details Grid -->
                <div class="grid grid-cols-2 gap-4">
                  <div class="bg-slate-50 rounded-lg p-4 border border-slate-200">
                    <p class="text-slate-600 text-xs font-semibold uppercase tracking-wide mb-2">Payment Gateway</p>
                    <p class="text-slate-900 font-medium text-base">Chapa</p>
                  </div>
                  <div class="bg-slate-50 rounded-lg p-4 border border-slate-200">
                    <p class="text-slate-600 text-xs font-semibold uppercase tracking-wide mb-2">Payment Status</p>
                    <p class="text-green-700 font-bold text-base">✓ PAID</p>
                  </div>
                  <div class="bg-slate-50 rounded-lg p-4 border border-slate-200">
                    <p class="text-slate-600 text-xs font-semibold uppercase tracking-wide mb-2">Payment Date</p>
                    <p class="text-slate-900 font-medium text-sm">
                      {{ new Date().toLocaleDateString('en-ET', { year: 'numeric', month: 'long', day: 'numeric' }) }}
                    </p>
                  </div>
                  <div class="bg-slate-50 rounded-lg p-4 border border-slate-200">
                    <p class="text-slate-600 text-xs font-semibold uppercase tracking-wide mb-2">Payment Time</p>
                    <p class="text-slate-900 font-medium text-sm">
                      {{ new Date().toLocaleTimeString('en-ET', { hour: '2-digit', minute: '2-digit', second: '2-digit' }) }}
                    </p>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- What's Next -->
          <div class="mb-8 transition-all duration-500" :class="showNextSteps ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-4'">
            <h3 class="font-semibold text-slate-900 mb-4 flex items-center gap-2">
              <svg class="w-5 h-5 text-purple-600" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-12a1 1 0 10-2 0v4a1 1 0 00.293.707l2.828 2.829a1 1 0 101.415-1.415L11 9.586V6z" clip-rule="evenodd"/>
              </svg>
              What's Next?
            </h3>
            <div class="space-y-3">
              <div class="flex items-start gap-3 bg-gradient-to-r from-amber-50 to-orange-50 rounded-lg p-4 border border-amber-200">
                <div class="w-8 h-8 bg-amber-500 rounded-full flex items-center justify-center text-white text-sm font-bold flex-shrink-0 mt-1">
                  1
                </div>
                <div>
                  <p class="font-semibold text-slate-900">Kitchen Preparing</p>
                  <p class="text-slate-600 text-sm">Our chefs are preparing your delicious meal right now</p>
                </div>
              </div>

              <div class="flex items-start gap-3 bg-gradient-to-r from-blue-50 to-cyan-50 rounded-lg p-4 border border-blue-200">
                <div class="w-8 h-8 bg-blue-500 rounded-full flex items-center justify-center text-white text-sm font-bold flex-shrink-0 mt-1">
                  2
                </div>
                <div>
                  <p class="font-semibold text-slate-900">Waiter Assignment</p>
                  <p class="text-slate-600 text-sm">A waiter will be assigned to deliver your order to your room</p>
                </div>
              </div>

              <div class="flex items-start gap-3 bg-gradient-to-r from-green-50 to-emerald-50 rounded-lg p-4 border border-green-200">
                <div class="w-8 h-8 bg-green-500 rounded-full flex items-center justify-center text-white text-sm font-bold flex-shrink-0 mt-1">
                  3
                </div>
                <div>
                  <p class="font-semibold text-slate-900">Delivery to Your Room</p>
                  <p class="text-slate-600 text-sm">Your order will be delivered directly to Room {{ roomNumber }} within 30 minutes</p>
                </div>
              </div>
            </div>
          </div>

          <!-- Info Box -->
          <div class="bg-gradient-to-r from-blue-50 to-indigo-50 rounded-lg p-5 border-2 border-blue-300 mb-8 transition-all duration-500" :class="showNextSteps ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-4'">
            <h4 class="font-bold text-blue-900 mb-4 flex items-center gap-2 text-lg">
              <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/>
              </svg>
              Important Information
            </h4>
            <ul class="text-blue-800 text-sm space-y-3">
              <li class="flex items-start gap-3">
                <span class="text-blue-600 font-bold flex-shrink-0">✓</span>
                <span>Your order confirmation has been sent to your email</span>
              </li>
              <li class="flex items-start gap-3">
                <span class="text-blue-600 font-bold flex-shrink-0">✓</span>
                <span>Estimated delivery time: <strong>30 minutes</strong></span>
              </li>
              <li class="flex items-start gap-3">
                <span class="text-blue-600 font-bold flex-shrink-0">✓</span>
                <span>Your order has been <strong>paid in full</strong> - no additional charges</span>
              </li>
              <li class="flex items-start gap-3">
                <span class="text-blue-600 font-bold flex-shrink-0">✓</span>
                <span>For any issues, please contact reception or room service</span>
              </li>
            </ul>
          </div>

          <!-- Action Buttons -->
          <div class="space-y-3 transition-all duration-500" :class="showButtons ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-4'">
            <button
              @click="downloadReceipt"
              :disabled="isLoading"
              class="w-full bg-green-600 hover:bg-green-700 disabled:bg-green-400 disabled:cursor-not-allowed text-white font-semibold py-3 rounded-lg transition flex items-center justify-center gap-2"
            >
              <svg
                v-if="isLoading"
                class="animate-spin h-5 w-5 text-white"
                xmlns="http://www.w3.org/2000/svg"
                fill="none"
                viewBox="0 0 24 24"
              >
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
              </svg>
              {{ isLoading ? 'Generating Receipt...' : '💳 Download Receipt' }}
            </button>
            <button
              @click="goToMenu"
              class="w-full bg-amber-600 hover:bg-amber-700 text-white font-semibold py-3 rounded-lg transition flex items-center justify-center gap-2"
            >
              🍽️ Order More Food
            </button>
            <button
              @click="goHome"
              class="w-full bg-slate-200 hover:bg-slate-300 text-slate-900 font-semibold py-3 rounded-lg transition"
            >
              Back to Home
            </button>
          </div>
        </div>
      </div>

      <!-- Footer -->
      <div class="mt-6 text-center text-slate-600 text-sm">
        <p>Thank you for ordering from our restaurant!</p>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { onMounted, ref } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import { generateAndDownloadReceipt } from '@/services/receiptService'

// ============================================================================
// Setup
// ============================================================================

const router = useRouter()
const route = useRoute()

// ============================================================================
// State
// ============================================================================

const txRef = ref<string>('')
const orderData = ref<any>(null)
const roomNumber = ref<string>('')
const isLoading = ref(false)

// Reveal animation states
const showHeader = ref(false)
const showSuccess = ref(false)
const showDetails = ref(false)
const showPayment = ref(false)
const showNextSteps = ref(false)
const showButtons = ref(false)

// ============================================================================
// Lifecycle
// ============================================================================

onMounted(async () => {
  console.clear()
  console.log('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━')
  console.log('🎉 [ORDER PAYMENT SUCCESS] PAGE MOUNTED AT:', new Date().toLocaleTimeString())
  console.log('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━')
  
  // Get tx_ref from URL first
  txRef.value = route.query.tx_ref as string
  console.log('📋 [ORDER PAYMENT SUCCESS] TX Ref from URL:', txRef.value)

  // Try to get order data from storage FIRST
  console.log('📦 [ORDER PAYMENT SUCCESS] Reading from sessionStorage...')
  const storedData = sessionStorage.getItem('order_payment_data')
  if (storedData) {
    try {
      const data = JSON.parse(storedData)
      orderData.value = data
      roomNumber.value = data.room_number || 'N/A'
      
      // IMPORTANT: If tx_ref not in URL, try to get it from stored data
      if (!txRef.value && data.tx_ref) {
        txRef.value = data.tx_ref
        console.log('📋 [ORDER PAYMENT SUCCESS] TX Ref from sessionStorage:', txRef.value)
      }
      
      console.log('✅ [ORDER PAYMENT SUCCESS] Got data from sessionStorage:', orderData.value)
    } catch (error) {
      console.error('❌ [ORDER PAYMENT SUCCESS] Failed to parse stored data:', error)
    }
  }

  // ============================================================================
  // 🔥 CRITICAL: VERIFY PAYMENT AND COMPLETE ORDER IN DATABASE
  // ============================================================================
  // The payment must be verified first, then the order is completed on the backend
  // so it becomes visible to the chef.
  if (txRef.value) {
    console.log('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━')
    console.log('🔥 [CRITICAL] VERIFYING PAYMENT AND COMPLETING ORDER...')
    console.log('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━')
    
    try {
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
      console.log('📡 [VERIFY] Response received:', verifyData)

      if (verifyResponse.ok && verifyData.success) {
        console.log('✅ [ORDER PAYMENT SUCCESS] Payment verified, now completing order...')

        const completeResponse = await fetch(
          `http://127.0.0.1:8000/api/order-payments/complete/${txRef.value}`,
          {
            method: 'POST',
            headers: {
              'Content-Type': 'application/json',
            },
          }
        )

        const completeData = await completeResponse.json()
        console.log('📡 [ORDER COMPLETE] Response received:', completeData)

        if (completeResponse.ok && completeData.success) {
          console.log('✅✅✅ [ORDER CREATED] Order created in database and sent to chef!')
          console.log('📦 [ORDER CREATED] Payment data:', completeData.payment)
          if (completeData.order) {
            orderData.value = {
              ...orderData.value,
              order_number: completeData.order.order_number,
              room_number: completeData.order.room_number,
              estimated_time: completeData.order.estimated_time || 30,
              items: completeData.order.items || orderData.value?.items || [],
              calculation: orderData.value?.calculation,
            }
          }
        } else {
          console.error('❌ [ORDER COMPLETE FAILED] Order completion failed:', completeData.message)
          console.warn('⚠️ Order may not have been created in database')
        }
      } else {
        console.error('❌ [VERIFY FAILED] Payment verification failed:', verifyData.message)
        // Still show success page but log the error
        console.warn('⚠️ Order may not have been created in database')
      }
    } catch (error) {
      console.error('❌ [VERIFY ERROR] Failed to verify payment:', error)
      console.warn('⚠️ Order may not have been created in database')
    }
  } else {
    console.error('❌ [CRITICAL ERROR] No transaction reference found!')
    console.error('❌ Cannot verify payment or create order')
  }

  // Show sections with staggered animation
  console.log('🎬 [ORDER PAYMENT SUCCESS] Starting animations...')
  
  setTimeout(() => {
    showHeader.value = true
  }, 200)

  setTimeout(() => {
    showSuccess.value = true
  }, 400)

  setTimeout(() => {
    showDetails.value = true
  }, 600)

  setTimeout(() => {
    showPayment.value = true
  }, 800)

  setTimeout(() => {
    showNextSteps.value = true
  }, 1000)

  setTimeout(() => {
    showButtons.value = true
    console.log('✅ [ORDER PAYMENT SUCCESS] All sections visible')
  }, 1200)

  // Fetch order details in background (after verification completes)
  if (txRef.value) {
    setTimeout(() => {
      fetchOrderDetails()
    }, 2000) // Increased delay to allow verification to complete
  }
})

// ============================================================================
// Methods
// ============================================================================

/**
 * Fetch order details from backend
 */
async function fetchOrderDetails(): Promise<void> {
  try {
    console.log('📡 [ORDER PAYMENT SUCCESS] Fetching order details...')
    
    const response = await fetch(
      `http://127.0.0.1:8000/api/order-payments/${txRef.value}`,
      {
        headers: {
          'Content-Type': 'application/json',
        },
      }
    )

    if (response.ok) {
      const data = await response.json()
      console.log('✅ [ORDER PAYMENT SUCCESS] Order details fetched:', data)
      
      if (data.success && data.order) {
        orderData.value = {
          ...orderData.value,
          order_number: data.order.order_number,
          room_number: data.order.room_number,
          estimated_time: data.order.estimated_time || 30,
          items: data.order.items || [],
          calculation: data.payment?.metadata?.calculation,
        }
      }
    } else {
      console.warn('[ORDER PAYMENT SUCCESS] Failed to fetch order details')
    }
  } catch (error) {
    console.error('[ORDER PAYMENT SUCCESS] Error fetching order details:', error)
  }
}

/**
 * Format price for display
 */
function formatPrice(price: number): string {
  return `$${price.toFixed(2)}`
}

/**
 * Download order receipt (PDF)
 */
async function downloadReceipt(): Promise<void> {
  console.log('📥 [ORDER RECEIPT] Receipt download requested')
  console.log('📦 [ORDER RECEIPT] Current order data:', orderData.value)
  console.log('📋 [ORDER RECEIPT] Current tx_ref:', txRef.value)
  
  if (!orderData.value) {
    console.error('❌ [ORDER RECEIPT] No order data available')
    alert('Error: Order details not found. Please refresh the page and try again.')
    return
  }

  // tx_ref is preferred but not required - we can use order_number as fallback
  const referenceId = txRef.value || orderData.value.tx_ref || 'ORDER-' + Date.now()
  console.log('📋 [ORDER RECEIPT] Using reference ID:', referenceId)

  try {
    console.log('💾 [ORDER RECEIPT] Starting receipt generation...')
    console.log('📊 [ORDER RECEIPT] Data being sent to receipt service:', {
      order_reference: orderData.value.order_number || 'ORD-' + referenceId.substring(0, 8).toUpperCase(),
      room_number: orderData.value.room_number || roomNumber.value,
      items: orderData.value.items,
      calculation: orderData.value.calculation,
      tx_ref: referenceId,
    })
    
    isLoading.value = true
    
    // Build receipt data for order (different from booking receipt)
    await generateAndDownloadReceipt({
      // Use order_number as booking_reference for consistency with receiptService
      booking_reference: orderData.value.order_number || 'ORD-' + referenceId.substring(0, 8).toUpperCase(),
      first_name: 'Room',
      last_name: orderData.value.room_number || roomNumber.value || 'Guest',
      email: orderData.value.email || 'guest@hotel.com',
      phone: orderData.value.phone || 'N/A',
      // For orders, we use special fields
      check_in_date: new Date().toISOString().split('T')[0], // Order date
      check_out_date: new Date().toISOString().split('T')[0], // Same as order date
      room_number: orderData.value.room_number || roomNumber.value || 'TBD',
      number_of_guests: 1,
      total_amount: orderData.value.calculation?.total || 0,
      currency: 'ETB',
      status: 'Confirmed',
      tx_ref: referenceId,
      payment_date: new Date().toISOString(),
      special_requests: `Order Items:\n${orderData.value.items?.map((item: any) => `• ${item.name} x${item.quantity} - ${formatPrice(item.total)}`).join('\n') || 'N/A'}`,
    })
    
    console.log('✅ [ORDER RECEIPT] Receipt generated and downloaded successfully!')
  } catch (error: any) {
    console.error('❌ [ORDER RECEIPT] Error downloading receipt:', error)
    console.error('❌ [ORDER RECEIPT] Error message:', error.message)
    console.error('❌ [ORDER RECEIPT] Error stack:', error.stack)
    alert('Failed to generate receipt: ' + error.message)
  } finally {
    isLoading.value = false
  }
}

/**
 * Go to menu page
 */
function goToMenu(): void {
  const qrToken = orderData.value?.qr_token
  if (qrToken) {
    router.push(`/qr-menu/${qrToken}`)
  } else {
    router.push('/')
  }
}

/**
 * Go to home page
 */
function goHome(): void {
  sessionStorage.removeItem('order_payment_data')
  router.push('/')
}
</script>

<style scoped>
@keyframes pulse-success {
  0%,
  100% {
    opacity: 1;
    transform: scale(1);
  }
  50% {
    opacity: 0.9;
    transform: scale(1.05);
  }
}

.animate-pulse-success {
  animation: pulse-success 2s cubic-bezier(0.4, 0, 0.6, 1) infinite;
}
</style>

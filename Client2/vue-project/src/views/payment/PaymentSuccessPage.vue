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
            Your reservation has been confirmed
          </p>
        </div>

        <!-- Content -->
        <div class="p-8">
          <!-- Success Message -->
          <div class="mb-8 text-center transition-all duration-500" :class="showHeader ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-4'">
            <h2 class="text-2xl font-semibold text-slate-900 mb-3">
              Thank you for your booking
            </h2>
            <p class="text-slate-600">
              Your reservation has been successfully created and payment has been processed.
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
                  Your payment has been securely processed and your reservation is confirmed.
                </p>
              </div>
            </div>
          </div>

          <!-- Reservation Details -->
          <div v-if="reservationData" class="space-y-6 transition-all duration-500" :class="showDetails ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-4'">
            <!-- Booking Confirmation Section Header -->
            <div class="bg-gradient-to-r from-green-50 to-emerald-50 rounded-lg p-4 border border-green-200">
              <h3 class="font-bold text-green-900 text-lg mb-2">✓ BOOKING CONFIRMED</h3>
              <p class="text-green-700 text-sm">Your reservation has been successfully created</p>
            </div>

            <!-- Booking Details -->
            <div class="border-b pb-6">
              <h3 class="font-semibold text-slate-900 mb-4 flex items-center gap-2">
                <svg class="w-5 h-5 text-green-600" fill="currentColor" viewBox="0 0 20 20">
                  <path d="M4 4a2 2 0 012-2h4.586A2 2 0 0112 2.586L15.414 6A2 2 0 0116 7.414V16a2 2 0 01-2 2H6a2 2 0 01-2-2V4z"/>
                </svg>
                Booking Details
              </h3>
              <div class="grid grid-cols-2 gap-4">
                <!-- Booking Reference -->
                <div class="bg-slate-50 rounded-lg p-4 border border-slate-200">
                  <p class="text-slate-600 text-xs font-semibold uppercase tracking-wide mb-2">Reference Number</p>
                  <p class="text-slate-900 font-mono text-sm font-bold break-all">
                    {{ reservationData.booking_reference || 'REF-' + txRef?.substring(0, 8).toUpperCase() }}
                  </p>
                </div>

                <!-- Status -->
                <div class="bg-slate-50 rounded-lg p-4 border border-slate-200">
                  <p class="text-slate-600 text-xs font-semibold uppercase tracking-wide mb-2">Status</p>
                  <span class="inline-block px-4 py-2 bg-green-100 text-green-800 rounded-full text-sm font-bold">
                    ✓ CONFIRMED
                  </span>
                </div>

                <!-- Check-in Date -->
                <div class="bg-slate-50 rounded-lg p-4 border border-slate-200">
                  <p class="text-slate-600 text-xs font-semibold uppercase tracking-wide mb-2">Check-in Date</p>
                  <p class="text-slate-900 font-medium text-base">
                    {{ formatDate(reservationData.check_in_date) }}
                  </p>
                </div>

                <!-- Check-out Date -->
                <div class="bg-slate-50 rounded-lg p-4 border border-slate-200">
                  <p class="text-slate-600 text-xs font-semibold uppercase tracking-wide mb-2">Check-out Date</p>
                  <p class="text-slate-900 font-medium text-base">
                    {{ formatDate(reservationData.check_out_date) }}
                  </p>
                </div>

                <!-- Room -->
                <div class="bg-slate-50 rounded-lg p-4 border border-slate-200">
                  <p class="text-slate-600 text-xs font-semibold uppercase tracking-wide mb-2">Room Number</p>
                  <p class="text-slate-900 font-medium text-base">
                    {{ reservationData.room_number || 'Room ' + reservationData.room_id?.substring(0, 4).toUpperCase() }}
                  </p>
                </div>

                <!-- Guests -->
                <div class="bg-slate-50 rounded-lg p-4 border border-slate-200">
                  <p class="text-slate-600 text-xs font-semibold uppercase tracking-wide mb-2">Number of Guests</p>
                  <p class="text-slate-900 font-medium text-base">
                    {{ reservationData.number_of_guests || 1 }} {{ reservationData.number_of_guests === 1 ? 'Guest' : 'Guests' }}
                  </p>
                </div>
              </div>
            </div>

            <!-- Guest Information -->
            <div class="border-b pb-6">
              <h3 class="font-semibold text-slate-900 mb-4 flex items-center gap-2">
                <svg class="w-5 h-5 text-blue-600" fill="currentColor" viewBox="0 0 20 20">
                  <path d="M10 9a3 3 0 100-6 3 3 0 000 6zm-7 9a7 7 0 1114 0H3z"/>
                </svg>
                Guest Information
              </h3>
              <div class="grid grid-cols-2 gap-4">
                <div class="bg-blue-50 rounded-lg p-4 border border-blue-200">
                  <p class="text-blue-600 text-xs font-semibold uppercase tracking-wide mb-2">Guest Name</p>
                  <p class="text-slate-900 font-medium text-base">
                    {{ reservationData.first_name }} {{ reservationData.last_name }}
                  </p>
                </div>
                <div class="bg-blue-50 rounded-lg p-4 border border-blue-200">
                  <p class="text-blue-600 text-xs font-semibold uppercase tracking-wide mb-2">Email Address</p>
                  <p class="text-slate-900 break-all text-sm">
                    {{ reservationData.email }}
                  </p>
                </div>
                <div class="bg-blue-50 rounded-lg p-4 border border-blue-200">
                  <p class="text-blue-600 text-xs font-semibold uppercase tracking-wide mb-2">Phone Number</p>
                  <p class="text-slate-900 font-medium text-base">
                    {{ reservationData.phone }}
                  </p>
                </div>
                <div v-if="reservationData.special_requests" class="bg-blue-50 rounded-lg p-4 border border-blue-200">
                  <p class="text-blue-600 text-xs font-semibold uppercase tracking-wide mb-2">Special Requests</p>
                  <p class="text-slate-900 text-sm">
                    {{ reservationData.special_requests }}
                  </p>
                </div>
                <div v-else class="bg-blue-50 rounded-lg p-4 border border-blue-200">
                  <p class="text-blue-600 text-xs font-semibold uppercase tracking-wide mb-2">Special Requests</p>
                  <p class="text-slate-500 italic text-sm">
                    None specified
                  </p>
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
                  <p class="text-orange-600 text-xs font-semibold uppercase tracking-wide mb-2">Transaction Reference (RCAPItMNbaOjN0qe)</p>
                  <p class="text-slate-900 font-mono text-sm break-all font-bold bg-white rounded px-3 py-2">
                    {{ txRef }}
                  </p>
                </div>

                <!-- Amount Paid - Prominent -->
                <div class="bg-gradient-to-r from-orange-100 to-amber-100 rounded-lg p-6 border-2 border-orange-300">
                  <p class="text-orange-600 text-xs font-semibold uppercase tracking-wider mb-3">Total Amount Paid</p>
                  <div class="flex items-baseline justify-between">
                    <p class="text-5xl font-bold text-orange-600">
                      {{ reservationData.total_amount || 0 }}
                    </p>
                    <span class="text-2xl font-bold text-orange-600">ETB</span>
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
                <path fill-rule="evenodd" d="M5 2a1 1 0 011 1v1h1a1 1 0 010 2H6v1a1 1 0 01-2 0V6H3a1 1 0 010-2h1V3a1 1 0 011-1zm0 10a1 1 0 011 1v1h1a1 1 0 110 2H6v1a1 1 0 11-2 0v-1H3a1 1 0 110-2h1v-1a1 1 0 011-1zM14 4a1 1 0 01.82.4l2.763 3.627a1 1 0 11-1.64 1.246L13.86 5.5a1 1 0 010-1.5zm2.05 5.463a1 1 0 00-1.415 0l-8.667 8.667a1 1 0 001.414 1.414l8.667-8.667a1 1 0 000-1.414z" clip-rule="evenodd"/>
              </svg>
              What's Next?
            </h3>
            <div class="space-y-3">
              <div class="flex items-start gap-3 bg-gradient-to-r from-green-50 to-emerald-50 rounded-lg p-4 border border-green-200">
                <div class="w-8 h-8 bg-green-500 rounded-full flex items-center justify-center text-white text-sm font-bold flex-shrink-0 mt-1">
                  1
                </div>
                <div>
                  <p class="font-semibold text-slate-900">Confirmation Email</p>
                  <p class="text-slate-600 text-sm">Check your email for booking confirmation and receipt (usually arrives within minutes)</p>
                </div>
              </div>

              <div class="flex items-start gap-3 bg-gradient-to-r from-blue-50 to-cyan-50 rounded-lg p-4 border border-blue-200">
                <div class="w-8 h-8 bg-blue-500 rounded-full flex items-center justify-center text-white text-sm font-bold flex-shrink-0 mt-1">
                  2
                </div>
                <div>
                  <p class="font-semibold text-slate-900">Check-in Instructions</p>
                  <p class="text-slate-600 text-sm">You will receive detailed check-in instructions before your arrival date</p>
                </div>
              </div>

              <div class="flex items-start gap-3 bg-gradient-to-r from-purple-50 to-pink-50 rounded-lg p-4 border border-purple-200">
                <div class="w-8 h-8 bg-purple-500 rounded-full flex items-center justify-center text-white text-sm font-bold flex-shrink-0 mt-1">
                  3
                </div>
                <div>
                  <p class="font-semibold text-slate-900">Enjoy Your Stay</p>
                  <p class="text-slate-600 text-sm">We look forward to welcoming you to our hotel. Have a wonderful experience!</p>
                </div>
              </div>
            </div>
          </div>

          <!-- Info Box -->
          <div class="bg-gradient-to-r from-blue-50 to-indigo-50 rounded-lg p-5 border-2 border-blue-300 mb-8 transition-all duration-500" :class="showNextSteps ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-4'">
            <h4 class="font-bold text-blue-900 mb-4 flex items-center gap-2 text-lg">
              <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M18 5v8a2 2 0 01-2 2h-5l-5 4v-4H4a2 2 0 01-2-2V5a2 2 0 012-2h12a2 2 0 012 2zm-11-1a1 1 0 11-2 0 1 1 0 012 0zM8 8a1 1 0 000 2h6a1 1 0 000-2H8zm0 3a1 1 0 000 2h6a1 1 0 000-2H8z" clip-rule="evenodd"/>
              </svg>
              Important Information
            </h4>
            <ul class="text-blue-800 text-sm space-y-3">
              <li class="flex items-start gap-3">
                <span class="text-blue-600 font-bold flex-shrink-0">✓</span>
                <span>Your booking reference has been sent to your email address</span>
              </li>
              <li class="flex items-start gap-3">
                <span class="text-blue-600 font-bold flex-shrink-0">✓</span>
                <span>Please arrive <strong>30 minutes before your check-in time</strong></span>
              </li>
              <li class="flex items-start gap-3">
                <span class="text-blue-600 font-bold flex-shrink-0">✓</span>
                <span>Keep your <strong>booking reference</strong> handy for check-in</span>
              </li>
              <li class="flex items-start gap-3">
                <span class="text-blue-600 font-bold flex-shrink-0">✓</span>
                <span>For cancellations or changes, contact us <strong>at least 48 hours</strong> before check-in</span>
              </li>
              <li class="flex items-start gap-3">
                <span class="text-blue-600 font-bold flex-shrink-0">✓</span>
                <span>Your receipt is available for download and can also be printed</span>
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
              <svg v-if="!isLoading" class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                <path d="M4 4a2 2 0 012-2h4.586A2 2 0 0112 2.586L15.414 6A2 2 0 0116 7.414V16a2 2 0 01-2 2H6a2 2 0 01-2-2V4z"/>
              </svg>
              <svg v-else class="w-5 h-5 animate-spin" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M4.293 5.293a1 1 0 011.414 0A7 7 0 0116.414 11a1 1 0 11-1.415 1.414A5 5 0 105.707 6.707a1 1 0 010-1.414z" clip-rule="evenodd" />
              </svg>
              {{ isLoading ? 'Generating Receipt...' : '💳 Download Receipt' }}
            </button>
            <button
              @click="goHome"
              :disabled="isLoading"
              class="w-full bg-slate-200 hover:bg-slate-300 disabled:bg-slate-100 disabled:cursor-not-allowed text-slate-900 font-semibold py-3 rounded-lg transition"
            >
              Back to Home
            </button>
          </div>
        </div>
      </div>

      <!-- Footer -->
      <div class="mt-6 text-center text-slate-600 text-sm">
        <p>Thank you for choosing our hotel for your stay!</p>
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
const reservationData = ref<any>(null)
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
  // CRITICAL: Log immediately to verify page is being mounted
  console.clear()
  console.log('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━')
  console.log('🎉 [PAYMENT SUCCESS] PAGE MOUNTED AT:', new Date().toLocaleTimeString())
  console.log('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━')
  console.log('🔒 [PAYMENT SUCCESS] User is on /payment/success - KEEP THEM HERE')
  console.log('📍 [PAYMENT SUCCESS] Current URL:', window.location.href)
  
  txRef.value = route.query.tx_ref as string
  console.log('📋 [PAYMENT SUCCESS] TX Ref from URL:', txRef.value)

  // Try to get reservation data from storage FIRST (before showing)
  console.log('📦 [PAYMENT SUCCESS] STEP 1: Reading from sessionStorage...')
  
  // Try both possible keys (legacy and new)
  let storedData = sessionStorage.getItem('reservationPaymentData')
  if (!storedData) {
    storedData = sessionStorage.getItem('booking_session')
    console.log('📦 [PAYMENT SUCCESS] Trying legacy key: booking_session')
  }
  
  if (storedData) {
    try {
      const parsed = JSON.parse(storedData)
      reservationData.value = parsed
      
      // If txRef wasn't in URL, try to get it from stored data
      if (!txRef.value && parsed.tx_ref) {
        txRef.value = parsed.tx_ref
        console.log('✅ [PAYMENT SUCCESS] Got tx_ref from sessionStorage:', txRef.value)
      }
      
      console.log('✅ [PAYMENT SUCCESS] Got data from sessionStorage:', reservationData.value)
    } catch (error) {
      console.error('❌ [PAYMENT SUCCESS] Failed to parse stored data:', error)
    }
  }

  // Show sections with nice staggered animation (200ms each)
  console.log('🎬 [PAYMENT SUCCESS] Starting staggered animations...')
  
  setTimeout(() => {
    showHeader.value = true
    console.log('✅ Stage 1: Header visible')
  }, 200)

  setTimeout(() => {
    showSuccess.value = true
    console.log('✅ Stage 2: Success message visible')
  }, 400)

  setTimeout(() => {
    showDetails.value = true
    console.log('✅ Stage 3: Booking details visible')
  }, 600)

  setTimeout(() => {
    showPayment.value = true
    console.log('✅ Stage 4: Payment info visible')
  }, 800)

  setTimeout(() => {
    showNextSteps.value = true
    console.log('✅ Stage 5: Next steps visible')
  }, 1000)

  setTimeout(() => {
    showButtons.value = true
    console.log('✅ Stage 6: Action buttons visible')
    console.log('🎉 [PAYMENT SUCCESS] All sections now visible - user can interact!')
  }, 1200)

  // Fetch fresh data in background (after animations start)
  if (txRef.value) {
    console.log('📡 [PAYMENT SUCCESS] STEP 2: Fetching fresh data in background...')
    // Start after 1.5 seconds to let user see initial content
    setTimeout(() => {
      completeReservationAndFetchDetails()
        .then(() => {
          console.log('✅ [PAYMENT SUCCESS] Background data fetch complete')
        })
        .catch((err) => {
          console.error('⚠️ [PAYMENT SUCCESS] Background fetch error (not critical):', err)
        })
    }, 1500)
  }
  
  console.log('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━')
  console.log('✅ [PAYMENT SUCCESS] Page setup complete - animations starting!')
  console.log('✅ [PAYMENT SUCCESS] Page WILL NOT redirect - stay here as long as you want')
  console.log('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━')
})

// ============================================================================
// Methods
// ============================================================================

/**
 * Start animations for sequential reveal
 */
function startAnimations(): void {
  // Animations are now handled in onMounted with staggered timeouts
  // This function is kept for reference but not used
  console.log('🎬 [PAYMENT SUCCESS] Animations already started in onMounted')
}

/**
 * Complete reservation and fetch details
 */
async function completeReservationAndFetchDetails(): Promise<void> {
  try {
    isLoading.value = true

    console.log('🔍 [PAYMENT SUCCESS] STEP 1: Verify payment with tx_ref:', txRef.value)
    
    // First, verify the payment with Chapa
    const verifyResponse = await fetch(
      `http://127.0.0.1:8000/api/payments/verify/${txRef.value}`,
      {
        method: 'GET',
        headers: {
          'Content-Type': 'application/json',
        },
      }
    )

    if (verifyResponse.ok) {
      const verifyData = await verifyResponse.json()
      console.log('✅ [PAYMENT SUCCESS] Payment verified:', verifyData)
    } else {
      console.warn('⚠️ [PAYMENT SUCCESS] Payment verification returned non-OK status, but continuing...')
    }

    // Now try to complete the reservation
    console.log('📝 [PAYMENT SUCCESS] STEP 2: Completing reservation with tx_ref:', txRef.value)
    const completeResponse = await fetch(
      `http://127.0.0.1:8000/api/reservation-payments/complete/${txRef.value}`,
      {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
        },
      }
    )

    if (completeResponse.ok) {
      const completeData = await completeResponse.json()
      console.log('✅ [PAYMENT SUCCESS] Reservation completed:', completeData)
      
      if (completeData.success && completeData.reservation) {
        // Extract guest details from reservation
        reservationData.value = {
          booking_reference: completeData.reservation.booking_reference || 'REF-' + txRef.value?.substring(0, 8).toUpperCase(),
          check_in_date: completeData.reservation.check_in_date,
          check_out_date: completeData.reservation.check_out_date,
          room_number: completeData.reservation.room_number || completeData.reservation.room?.room_number,
          room_id: completeData.reservation.room_id,
          number_of_guests: completeData.reservation.number_of_guests,
          first_name: completeData.reservation.first_name,
          last_name: completeData.reservation.last_name,
          email: completeData.reservation.email,
          phone: completeData.reservation.phone,
          special_requests: completeData.reservation.special_requests,
          total_amount: completeData.reservation.total_amount || completeData.payment?.amount,
        }
        console.log('✅ [PAYMENT SUCCESS] Reservation data extracted:', reservationData.value)
        return
      }
    } else {
      console.warn('⚠️ [PAYMENT SUCCESS] Failed to complete reservation, trying direct fetch')
    }

    // If completion failed, try to fetch reservation details directly
    console.log('📡 [PAYMENT SUCCESS] Fetching reservation details via GET')
    await fetchReservationDetails()

  } catch (error) {
    console.error('[PAYMENT SUCCESS] Error in complete reservation and fetch:', error)
    // Continue anyway - we might have data from sessionStorage
  } finally {
    isLoading.value = false
  }
}

/**
 * Fetch reservation details from backend
 */
async function fetchReservationDetails(): Promise<void> {
  try {
    isLoading.value = true

    const response = await fetch(
      `http://127.0.0.1:8000/api/reservation-payments/${txRef.value}`,
      {
        headers: {
          'Content-Type': 'application/json',
        },
      }
    )

    if (response.ok) {
      const data = await response.json()
      if (data.success && data.reservation) {
        reservationData.value = {
          ...reservationData.value,
          booking_reference: data.reservation.booking_reference || 'REF-' + txRef.value?.substring(0, 8).toUpperCase(),
          check_in_date: data.reservation.check_in_date,
          check_out_date: data.reservation.check_out_date,
          room_number: data.reservation.room_number || data.reservation.room?.room_number,
          room_id: data.reservation.room_id,
          number_of_guests: data.reservation.number_of_guests,
          first_name: data.reservation.first_name,
          last_name: data.reservation.last_name,
          email: data.reservation.email,
          phone: data.reservation.phone,
          special_requests: data.reservation.special_requests,
          total_amount: data.reservation.total_amount || data.payment?.amount,
        }
        console.log('✅ [PAYMENT SUCCESS] Reservation data fetched:', reservationData.value)
      }
    } else {
      console.warn('[PAYMENT SUCCESS] Failed to fetch reservation details, status:', response.status)
    }
  } catch (error) {
    console.error('[PAYMENT SUCCESS] Failed to fetch reservation details:', error)
  } finally {
    isLoading.value = false
  }
}

/**
 * Format date for display
 */
function formatDate(dateString: string): string {
  if (!dateString) return 'N/A'
  return new Intl.DateTimeFormat('en-ET', {
    year: 'numeric',
    month: 'long',
    day: 'numeric',
  }).format(new Date(dateString))
}

/**
 * Go to home page
 */
function goHome(): void {
  sessionStorage.removeItem('reservationPaymentData')
  router.push('/')
}

/**
 * Download receipt (with detailed error handling)
 */
async function downloadReceipt(): Promise<void> {
  console.log('📥 [DOWNLOAD] Receipt download requested')
  console.log('📦 [DOWNLOAD] Current reservation data:', reservationData.value)
  console.log('📋 [DOWNLOAD] Current tx_ref:', txRef.value)
  
  if (!txRef.value) {
    console.error('❌ [DOWNLOAD] No transaction reference available')
    alert('Error: Transaction reference not found. Please refresh the page.')
    return
  }

  if (!reservationData.value) {
    console.error('❌ [DOWNLOAD] No reservation data available')
    alert('Error: Reservation details not found. Please refresh the page and try again.')
    return
  }

  try {
    console.log('💾 [DOWNLOAD] Starting receipt generation...')
    console.log('📊 [DOWNLOAD] Data being sent to receipt service:', {
      booking_reference: reservationData.value.booking_reference,
      first_name: reservationData.value.first_name,
      last_name: reservationData.value.last_name,
      email: reservationData.value.email,
      phone: reservationData.value.phone,
      check_in_date: reservationData.value.check_in_date,
      check_out_date: reservationData.value.check_out_date,
      room_number: reservationData.value.room_number,
      number_of_guests: reservationData.value.number_of_guests,
      total_amount: reservationData.value.total_amount,
      tx_ref: txRef.value,
    })
    
    isLoading.value = true
    
    await generateAndDownloadReceipt({
      booking_reference: reservationData.value.booking_reference || 'REF-' + txRef.value?.substring(0, 8).toUpperCase(),
      first_name: reservationData.value.first_name || 'Guest',
      last_name: reservationData.value.last_name || '',
      email: reservationData.value.email || 'N/A',
      phone: reservationData.value.phone || 'N/A',
      check_in_date: reservationData.value.check_in_date,
      check_out_date: reservationData.value.check_out_date,
      room_number: reservationData.value.room_number || 'TBD',
      number_of_guests: reservationData.value.number_of_guests || 1,
      total_amount: reservationData.value.total_amount || 0,
      currency: 'ETB',
      status: 'Confirmed',
      tx_ref: txRef.value,
      payment_date: new Date().toISOString(),
      special_requests: reservationData.value.special_requests,
    })
    
    console.log('✅ [DOWNLOAD] Receipt generated and downloaded successfully!')
  } catch (error: any) {
    console.error('❌ [DOWNLOAD] Error downloading receipt:', error)
    console.error('❌ [DOWNLOAD] Error message:', error.message)
    console.error('❌ [DOWNLOAD] Error stack:', error.stack)
    alert('Failed to generate receipt: ' + error.message)
  } finally {
    isLoading.value = false
  }
}
</script>

<style scoped>
/* Pulse success animation */
@keyframes pulse-success {
  0%,
  100% {
    opacity: 1;
    transform: scale(1);
  }
  50% {
    opacity: 0.8;
    transform: scale(1.05);
  }
}

.animate-pulse-success {
  animation: pulse-success 2s ease-in-out infinite;
}

/* Fade and slide in animation */
@keyframes fadeSlideIn {
  from {
    opacity: 0;
    transform: translateY(1rem);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}

.transition-all {
  transition: all 0.5s cubic-bezier(0.4, 0, 0.2, 1);
}

.duration-500 {
  transition-duration: 500ms;
}

.scale-95 {
  transform: scale(0.95);
}

.scale-100 {
  transform: scale(1);
}

.translate-y-0 {
  transform: translateY(0);
}

.translate-y-4 {
  transform: translateY(1rem);
}

.opacity-0 {
  opacity: 0;
}

.opacity-100 {
  opacity: 1;
}
</style>

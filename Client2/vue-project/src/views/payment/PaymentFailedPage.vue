<template>
  <div class="failed-container min-h-screen bg-gradient-to-br from-red-50 to-orange-100 py-12 flex items-center justify-center">
    <!-- Main Failed Card -->
    <div class="max-w-2xl w-full mx-auto px-4">
      <div class="bg-white rounded-2xl shadow-2xl overflow-hidden">
        <!-- Failed Header -->
        <div class="bg-gradient-to-r from-red-500 to-orange-600 px-8 py-12 text-center">
          <!-- Failed Icon -->
          <div class="flex justify-center mb-6">
            <div class="w-20 h-20 bg-white rounded-full flex items-center justify-center">
              <svg
                class="w-12 h-12 text-red-600"
                fill="currentColor"
                viewBox="0 0 20 20"
              >
                <path
                  fill-rule="evenodd"
                  d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z"
                  clip-rule="evenodd"
                />
              </svg>
            </div>
          </div>

          <h1 class="text-4xl font-bold text-white mb-3">Payment Failed</h1>
          <p class="text-red-50 text-lg">
            Your payment could not be processed
          </p>
        </div>

        <!-- Content -->
        <div class="p-8">
          <!-- Error Message -->
          <div class="mb-8 text-center">
            <h2 class="text-2xl font-semibold text-slate-900 mb-3">
              Transaction Could Not Be Completed
            </h2>
            <p class="text-slate-600">
              Unfortunately, your payment was not successful. Please review the details below and try again.
            </p>
          </div>

          <!-- Error Alert -->
          <div class="bg-red-50 border border-red-200 rounded-lg p-4 mb-8">
            <div class="flex items-start gap-3">
              <svg
                class="w-5 h-5 text-red-600 mt-0.5 flex-shrink-0"
                fill="currentColor"
                viewBox="0 0 20 20"
              >
                <path
                  fill-rule="evenodd"
                  d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z"
                  clip-rule="evenodd"
                />
              </svg>
              <div>
                <h3 class="font-semibold text-red-900">Payment Unsuccessful</h3>
                <p class="text-red-700 text-sm mt-1">
                  {{ failureReason || 'Your payment was declined. Please try again with a different payment method.' }}
                </p>
              </div>
            </div>
          </div>

          <!-- Failure Details -->
          <div v-if="txRef" class="space-y-6">
            <!-- Transaction Info -->
            <div class="border-b pb-6">
              <h3 class="font-semibold text-slate-900 mb-4">Transaction Details</h3>
              <div class="grid grid-cols-2 gap-4">
                <!-- Transaction ID -->
                <div class="bg-slate-50 rounded-lg p-4">
                  <p class="text-slate-600 text-sm font-medium mb-2">Transaction ID</p>
                  <p class="text-slate-900 font-mono text-sm break-all">
                    {{ txRef }}
                  </p>
                </div>

                <!-- Status -->
                <div class="bg-slate-50 rounded-lg p-4">
                  <p class="text-slate-600 text-sm font-medium mb-2">Status</p>
                  <span
                    class="inline-block px-3 py-1 bg-red-100 text-red-800 rounded-full text-sm font-semibold uppercase"
                  >
                    Failed
                  </span>
                </div>
              </div>
            </div>
          </div>

          <!-- Why Did This Happen? -->
          <div class="mb-8">
            <h3 class="font-semibold text-slate-900 mb-4">Common Reasons for Payment Failure</h3>
            <div class="space-y-3">
              <div class="flex items-start gap-3">
                <svg
                  class="w-5 h-5 text-slate-400 mt-0.5 flex-shrink-0"
                  fill="currentColor"
                  viewBox="0 0 20 20"
                >
                  <path
                    fill-rule="evenodd"
                    d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z"
                    clip-rule="evenodd"
                  />
                </svg>
                <div>
                  <p class="font-medium text-slate-900">Insufficient Funds</p>
                  <p class="text-slate-600 text-sm">Your account doesn't have enough balance</p>
                </div>
              </div>

              <div class="flex items-start gap-3">
                <svg
                  class="w-5 h-5 text-slate-400 mt-0.5 flex-shrink-0"
                  fill="currentColor"
                  viewBox="0 0 20 20"
                >
                  <path
                    fill-rule="evenodd"
                    d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z"
                    clip-rule="evenodd"
                  />
                </svg>
                <div>
                  <p class="font-medium text-slate-900">Incorrect Payment Details</p>
                  <p class="text-slate-600 text-sm">Payment method information was incorrect</p>
                </div>
              </div>

              <div class="flex items-start gap-3">
                <svg
                  class="w-5 h-5 text-slate-400 mt-0.5 flex-shrink-0"
                  fill="currentColor"
                  viewBox="0 0 20 20"
                >
                  <path
                    fill-rule="evenodd"
                    d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z"
                    clip-rule="evenodd"
                  />
                </svg>
                <div>
                  <p class="font-medium text-slate-900">Card Declined</p>
                  <p class="text-slate-600 text-sm">Your card was declined by the bank</p>
                </div>
              </div>

              <div class="flex items-start gap-3">
                <svg
                  class="w-5 h-5 text-slate-400 mt-0.5 flex-shrink-0"
                  fill="currentColor"
                  viewBox="0 0 20 20"
                >
                  <path
                    fill-rule="evenodd"
                    d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z"
                    clip-rule="evenodd"
                  />
                </svg>
                <div>
                  <p class="font-medium text-slate-900">Network Error</p>
                  <p class="text-slate-600 text-sm">A temporary connection issue occurred</p>
                </div>
              </div>
            </div>
          </div>

          <!-- Info Box -->
          <div class="bg-blue-50 rounded-lg p-4 border border-blue-200 mb-8">
            <h4 class="font-semibold text-blue-900 mb-3">What to Do Next?</h4>
            <ul class="text-blue-700 text-sm space-y-2">
              <li class="flex items-start gap-2">
                <span class="text-blue-400 mt-1">•</span>
                <span>Verify your payment information and try again</span>
              </li>
              <li class="flex items-start gap-2">
                <span class="text-blue-400 mt-1">•</span>
                <span>Try with a different payment method</span>
              </li>
              <li class="flex items-start gap-2">
                <span class="text-blue-400 mt-1">•</span>
                <span>Contact your bank if the issue persists</span>
              </li>
              <li class="flex items-start gap-2">
                <span class="text-blue-400 mt-1">•</span>
                <span>Contact our support team for assistance</span>
              </li>
            </ul>
          </div>

          <!-- Action Buttons -->
          <div class="space-y-3">
            <button
              @click="retryPayment"
              class="w-full bg-blue-600 hover:bg-blue-700 text-white font-semibold py-3 rounded-lg transition flex items-center justify-center gap-2"
            >
              <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M4 2a1 1 0 011 1v2.101a7.002 7.002 0 1119.414 5.414 1 1 0 11-1.414-1.414A5.002 5.002 0 005.659 5.242V4a1 1 0 01-1-1H4zm6 14a1 1 0 01-1-1v-2.101a7.002 7.002 0 1119.414-5.414 1 1 0 011.414 1.414A5.002 5.002 0 015 14.5v2a1 1 0 01-1 1z"/>
              </svg>
              💳 Try Payment Again
            </button>
            <button
              @click="goHome"
              class="w-full bg-slate-200 hover:bg-slate-300 text-slate-900 font-semibold py-3 rounded-lg transition"
            >
              Back to Home
            </button>
          </div>

          <!-- Support Contact -->
          <div class="mt-8 text-center">
            <p class="text-slate-600 mb-3">Need help?</p>
            <p class="text-slate-700">
              <span class="font-medium">Email:</span> support@hotel.com<br>
              <span class="font-medium">Phone:</span> +1-800-000-0000
            </p>
          </div>
        </div>
      </div>

      <!-- Footer -->
      <div class="mt-6 text-center text-slate-600 text-sm">
        <p>We apologize for the inconvenience. Please try again or contact our support team.</p>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { useRouter, useRoute } from 'vue-router'

// ============================================================================
// Setup
// ============================================================================

const router = useRouter()
const route = useRoute()

// ============================================================================
// State
// ============================================================================

const txRef = ref<string>('')
const failureReason = ref<string>('')

// ============================================================================
// Lifecycle
// ============================================================================

onMounted(() => {
  txRef.value = route.query.tx_ref as string

  if (!txRef.value) {
    router.push('/')
    return
  }

  // Get failure reason from query params or sessionStorage
  const reason = route.query.reason as string
  if (reason) {
    failureReason.value = decodeURIComponent(reason)
  }
})

// ============================================================================
// Methods
// ============================================================================

/**
 * Go to home page
 */
function goHome(): void {
  sessionStorage.removeItem('reservationPaymentData')
  router.push('/')
}

/**
 * Retry payment
 */
function retryPayment(): void {
  sessionStorage.removeItem('reservationPaymentData')
  router.push('/rooms')
}
</script>

<style scoped>
/* Animations are defined inline */
</style>

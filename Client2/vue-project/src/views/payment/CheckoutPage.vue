<template>
  <div class="checkout-container min-h-screen bg-gradient-to-br from-slate-50 to-slate-100 py-12">
    <!-- Main Checkout Card -->
    <div class="max-w-2xl mx-auto px-4">
      <div class="bg-white rounded-2xl shadow-xl overflow-hidden">
        <!-- Header -->
        <div class="bg-gradient-to-r from-blue-600 to-blue-800 px-8 py-6">
          <h1 class="text-3xl font-bold text-white">Payment Checkout</h1>
          <p class="text-blue-100 mt-2">Complete your payment securely</p>
        </div>

        <!-- Content -->
        <div class="p-8">
          <!-- Error Alert -->
          <div
            v-if="paymentStore.error"
            class="mb-6 p-4 bg-red-50 border border-red-200 rounded-lg flex items-start gap-3"
          >
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
              <h3 class="font-semibold text-red-900">Error</h3>
              <p class="text-red-700 text-sm mt-1">{{ paymentStore.error }}</p>
            </div>
          </div>

          <!-- Payment Form -->
          <form @submit.prevent="submitPayment" class="space-y-6">
            <!-- Customer Information Section -->
            <div class="border-b pb-6">
              <h2 class="text-lg font-semibold text-slate-900 mb-4">
                Customer Information
              </h2>

              <!-- Name Fields -->
              <div class="grid grid-cols-2 gap-4 mb-4">
                <div>
                  <label class="block text-sm font-medium text-slate-700 mb-2">
                    First Name
                  </label>
                  <input
                    v-model="formData.first_name"
                    type="text"
                    required
                    class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent outline-none transition"
                    placeholder="John"
                  />
                </div>
                <div>
                  <label class="block text-sm font-medium text-slate-700 mb-2">
                    Last Name
                  </label>
                  <input
                    v-model="formData.last_name"
                    type="text"
                    required
                    class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent outline-none transition"
                    placeholder="Doe"
                  />
                </div>
              </div>

              <!-- Email and Phone -->
              <div class="grid grid-cols-2 gap-4">
                <div>
                  <label class="block text-sm font-medium text-slate-700 mb-2">
                    Email Address
                  </label>
                  <input
                    v-model="formData.email"
                    type="email"
                    required
                    class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent outline-none transition"
                    placeholder="john@example.com"
                  />
                </div>
                <div>
                  <label class="block text-sm font-medium text-slate-700 mb-2">
                    Phone Number
                  </label>
                  <input
                    v-model="formData.phone"
                    type="tel"
                    required
                    class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent outline-none transition"
                    placeholder="+251 912345678"
                  />
                </div>
              </div>
            </div>

            <!-- Payment Amount Section -->
            <div class="border-b pb-6">
              <h2 class="text-lg font-semibold text-slate-900 mb-4">
                Payment Amount
              </h2>

              <div
                class="bg-slate-50 rounded-lg p-6 flex items-center justify-between"
              >
                <div>
                  <p class="text-slate-600 text-sm mb-1">Total Amount</p>
                  <p class="text-3xl font-bold text-slate-900">
                    {{ formatAmount(formData.amount) }}
                  </p>
                </div>
                <div class="text-right">
                  <p class="text-slate-600 text-sm mb-1">Currency</p>
                  <p class="text-2xl font-semibold text-blue-600">ETB</p>
                </div>
              </div>

              <div class="mt-4">
                <label class="block text-sm font-medium text-slate-700 mb-2">
                  Amount (ETB)
                </label>
                <input
                  v-model.number="formData.amount"
                  type="number"
                  min="0.01"
                  step="0.01"
                  required
                  class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent outline-none transition"
                  placeholder="1000.00"
                  disabled
                />
                <p class="text-xs text-slate-500 mt-2">
                  Amount is pre-configured for this transaction
                </p>
              </div>
            </div>

            <!-- Payment Method Section -->
            <div class="bg-blue-50 rounded-lg p-4 border border-blue-200">
              <div class="flex items-start gap-3">
                <svg
                  class="w-5 h-5 text-blue-600 mt-0.5 flex-shrink-0"
                  fill="currentColor"
                  viewBox="0 0 20 20"
                >
                  <path
                    d="M9 6a3 3 0 11-6 0 3 3 0 016 0zM9 12a6 6 0 11-12 0 6 6 0 0112 0z"
                  />
                </svg>
                <div>
                  <h3 class="font-semibold text-blue-900">Secure Payment</h3>
                  <p class="text-blue-700 text-sm mt-1">
                    Your payment will be processed securely through Chapa Payment
                    Gateway
                  </p>
                </div>
              </div>
            </div>

            <!-- Submit Button -->
            <div class="pt-4">
              <button
                type="submit"
                :disabled="paymentStore.isInitializing || paymentStore.isLoading"
                class="w-full bg-gradient-to-r from-blue-600 to-blue-700 hover:from-blue-700 hover:to-blue-800 text-white font-semibold py-3 rounded-lg transition disabled:opacity-50 disabled:cursor-not-allowed flex items-center justify-center gap-2"
              >
                <span
                  v-if="paymentStore.isInitializing || paymentStore.isLoading"
                  class="w-4 h-4 border-2 border-white border-t-transparent rounded-full animate-spin"
                ></span>
                {{
                  paymentStore.isInitializing || paymentStore.isLoading
                    ? 'Processing...'
                    : `Proceed to Payment (${formatAmount(formData.amount)})`
                }}
              </button>
            </div>

            <!-- Security Notice -->
            <p class="text-center text-xs text-slate-500">
              🔒 Your payment information is encrypted and secure. We never store your
              credit card details.
            </p>
          </form>
        </div>
      </div>

      <!-- Info Card -->
      <div class="mt-8 bg-white rounded-lg shadow p-6 border-l-4 border-blue-600">
        <h3 class="font-semibold text-slate-900 mb-2">Payment Information</h3>
        <ul class="text-sm text-slate-600 space-y-2">
          <li class="flex items-start gap-2">
            <span class="text-blue-600 mt-1">✓</span>
            <span>Payment is processed in Ethiopian Birr (ETB)</span>
          </li>
          <li class="flex items-start gap-2">
            <span class="text-blue-600 mt-1">✓</span>
            <span>You will be redirected to Chapa for secure payment processing</span>
          </li>
          <li class="flex items-start gap-2">
            <span class="text-blue-600 mt-1">✓</span>
            <span>After successful payment, you will be redirected back</span>
          </li>
          <li class="flex items-start gap-2">
            <span class="text-blue-600 mt-1">✓</span>
            <span>Keep your transaction reference for your records</span>
          </li>
        </ul>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue';
import { useRouter, useRoute } from 'vue-router';
import { usePaymentStore } from '@/stores/paymentStore';
import paymentService from '@/services/paymentService';

// ============================================================================
// Setup
// ============================================================================

const router = useRouter();
const route = useRoute();
const paymentStore = usePaymentStore();

// ============================================================================
// State
// ============================================================================

const isLoading = ref(true);
const error = ref<string | null>(null);

// Initialize formData from session storage or with defaults
const bookingSessionData = JSON.parse(sessionStorage.getItem('booking_session') || '{}');

const formData = ref({
  first_name: bookingSessionData.first_name || '',
  last_name: bookingSessionData.last_name || '',
  email: bookingSessionData.email || '',
  phone: bookingSessionData.phone || '',
  amount: bookingSessionData.price_breakdown?.total || 0,
});

// ============================================================================
// Lifecycle - AUTO REDIRECT TO CHAPA
// ============================================================================

/**
 * When guest is redirected here from BookingModal:
 * - BookingModal has already initialized payment via API
 * - We receive payment_id and tx_ref as query params
 * - We fetch the payment details to get checkout URL
 * - We display the checkout form with payment details
 * - User clicks "Proceed to Payment" to go to Chapa
 */
onMounted(async () => {
  try {
    const paymentId = route.query.payment_id as string;
    const txRef = route.query.tx_ref as string;
    let checkoutUrl = route.query.checkout_url as string;

    console.log('💳 [CHECKOUT] Received payment details:');
    console.log('   - payment_id:', paymentId);
    console.log('   - tx_ref:', txRef);
    console.log('   - checkout_url from query:', checkoutUrl);

    // If checkout_url not in query params, try sessionStorage (fallback)
    if (!checkoutUrl) {
      checkoutUrl = sessionStorage.getItem('chapa_checkout_url') || '';
      console.log('📦 [CHECKOUT] Retrieved checkout_url from sessionStorage:', checkoutUrl);
    }

    // Validate required fields
    if (!txRef) {
      throw new Error('Missing transaction reference. Please try again from the booking form.');
    }

    if (!checkoutUrl) {
      console.warn('⚠️ [CHECKOUT] WARNING: Checkout URL is empty. This may cause payment to fail.');
    }

    console.log('✅ [CHECKOUT] Payment information loaded');
    
    // Store the payment details
    const payment = {
      id: paymentId || '',
      tx_ref: txRef,
      checkout_url: checkoutUrl || '',
      amount: bookingSessionData?.price_breakdown?.total || 0,
    };
    
    paymentStore.setCurrentPayment(payment);
    
    console.log('💾 [CHECKOUT] Payment stored in paymentStore');
    console.log('🔗 [CHECKOUT] Stored Checkout URL:', paymentStore.currentCheckoutUrl);
    
    // Update formData with payment amount
    formData.value.amount = bookingSessionData?.price_breakdown?.total || 0;
    
    console.log('✅ [CHECKOUT] Form data updated with amount:', formData.value.amount);
    
    isLoading.value = false;

  } catch (err: any) {
    console.error('❌ [CHECKOUT] Error in onMounted:', err);
    error.value = err.message || 'Failed to process payment';
    isLoading.value = false;
  }
});

// ============================================================================
// Submit Payment Handler
// ============================================================================

/**
 * Handle "Proceed to Payment" button click
 * This should trigger the redirect to Chapa
 */
function submitPayment(): void {
  try {
    console.log('💳 [CHECKOUT] Submit Payment clicked');
    console.log('💾 [CHECKOUT] Current Checkout URL from Store:', paymentStore.currentCheckoutUrl);
    
    // Get checkout URL from store
    const checkoutUrl = paymentStore.currentCheckoutUrl;
    
    if (!checkoutUrl) {
      throw new Error('Checkout URL not available. Please try again or refresh the page.');
    }

    console.log('� [CHECKOUT] Redirecting to Chapa checkout at:', checkoutUrl);
    window.location.href = checkoutUrl;
  } catch (err: any) {
    console.error('❌ [CHECKOUT] Submit payment error:', err);
    error.value = err.message || 'Failed to proceed to payment';
  }
}

// ============================================================================
// Format Helper
// ============================================================================

function formatAmount(amount: number): string {
  return new Intl.NumberFormat('en-ET', {
    style: 'currency',
    currency: 'ETB',
  }).format(amount);
}
</script>

<style scoped>
/* Smooth transitions */
input:focus {
  @apply shadow-lg;
}

button:hover:not(:disabled) {
  @apply shadow-lg;
}

/* Loading spinner */
@keyframes spin {
  to {
    transform: rotate(360deg);
  }
}

.animate-spin {
  animation: spin 1s linear infinite;
}
</style>

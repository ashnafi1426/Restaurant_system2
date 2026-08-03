<template>
  <div class="pending-container min-h-screen bg-gradient-to-br from-amber-50 to-orange-100 py-12 flex items-center justify-center">
    <!-- Main Pending Card -->
    <div class="max-w-2xl w-full mx-auto px-4">
      <div class="bg-white rounded-2xl shadow-2xl overflow-hidden">
        <!-- Pending Header -->
        <div class="bg-gradient-to-r from-amber-500 to-orange-600 px-8 py-12 text-center">
          <!-- Pending Icon -->
          <div class="flex justify-center mb-6">
            <div class="w-20 h-20 bg-white rounded-full flex items-center justify-center animate-bounce">
              <svg
                class="w-12 h-12 text-amber-600"
                fill="none"
                stroke="currentColor"
                viewBox="0 0 24 24"
              >
                <path
                  stroke-linecap="round"
                  stroke-linejoin="round"
                  stroke-width="2"
                  d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"
                />
              </svg>
            </div>
          </div>

          <h1 class="text-4xl font-bold text-white mb-3">Payment Pending</h1>
          <p class="text-amber-50 text-lg">
            Your payment is being processed
          </p>
        </div>

        <!-- Content -->
        <div class="p-8">
          <!-- Status Message -->
          <div class="mb-8 text-center">
            <div class="inline-block mb-6">
              <div class="w-16 h-16 border-4 border-amber-200 border-t-amber-600 rounded-full animate-spin"></div>
            </div>
            <h2 class="text-2xl font-semibold text-slate-900 mb-3">
              Processing Your Payment
            </h2>
            <p class="text-slate-600">
              Please do not close this page or press the back button while we process your payment.
            </p>
          </div>

          <!-- Info Alert -->
          <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 mb-8">
            <div class="flex items-start gap-3">
              <svg
                class="w-5 h-5 text-blue-600 mt-0.5 flex-shrink-0"
                fill="currentColor"
                viewBox="0 0 20 20"
              >
                <path
                  fill-rule="evenodd"
                  d="M18 5v8a2 2 0 01-2 2h-5l-5 4v-4H4a2 2 0 01-2-2V5a2 2 0 012-2h12a2 2 0 012 2zm-11-1a1 1 0 100-2 1 1 0 000 2zm3 0a1 1 0 100-2 1 1 0 000 2zm3 0a1 1 0 100-2 1 1 0 000 2z"
                  clip-rule="evenodd"
                />
              </svg>
              <div>
                <h3 class="font-semibold text-blue-900">Processing...</h3>
                <p class="text-blue-700 text-sm mt-1">
                  We're securely processing your payment. You will be notified once the payment is complete.
                </p>
              </div>
            </div>
          </div>

          <!-- Payment Details -->
          <div v-if="paymentStore.currentPayment" class="space-y-6">
            <!-- Status Info Grid -->
            <div class="border-b pb-6">
              <h3 class="font-semibold text-slate-900 mb-4">Payment Details</h3>
              <div class="grid grid-cols-2 gap-4">
                <!-- Transaction ID -->
                <div class="bg-slate-50 rounded-lg p-4">
                  <p class="text-slate-600 text-sm font-medium mb-2">Transaction ID</p>
                  <p class="text-slate-900 font-mono text-sm break-all">
                    {{ paymentStore.currentPayment.tx_ref }}
                  </p>
                </div>

                <!-- Status -->
                <div class="bg-slate-50 rounded-lg p-4">
                  <p class="text-slate-600 text-sm font-medium mb-2">Status</p>
                  <span
                    class="inline-block px-3 py-1 bg-amber-100 text-amber-800 rounded-full text-sm font-semibold uppercase"
                  >
                    {{ paymentStore.currentPayment.status }}
                  </span>
                </div>

                <!-- Amount -->
                <div class="bg-slate-50 rounded-lg p-4">
                  <p class="text-slate-600 text-sm font-medium mb-2">Amount</p>
                  <p class="text-2xl font-bold text-slate-900">
                    {{ paymentStore.currentPayment.formatted_amount }}
                  </p>
                </div>

                <!-- Started -->
                <div class="bg-slate-50 rounded-lg p-4">
                  <p class="text-slate-600 text-sm font-medium mb-2">Started</p>
                  <p class="text-slate-900">
                    {{ formatDate(paymentStore.currentPayment.created_at) }}
                  </p>
                </div>
              </div>
            </div>

            <!-- Customer Info -->
            <div class="border-b pb-6">
              <h3 class="font-semibold text-slate-900 mb-4">Customer Information</h3>
              <div class="grid grid-cols-2 gap-4">
                <div>
                  <p class="text-slate-600 text-sm mb-1">Name</p>
                  <p class="text-slate-900 font-medium">
                    {{ paymentStore.currentPayment.customer.name }}
                  </p>
                </div>
                <div>
                  <p class="text-slate-600 text-sm mb-1">Email</p>
                  <p class="text-slate-900 break-all">
                    {{ paymentStore.currentPayment.customer.email }}
                  </p>
                </div>
              </div>
            </div>
          </div>

          <!-- Progress Steps -->
          <div class="mb-8">
            <h3 class="font-semibold text-slate-900 mb-4">Processing Steps</h3>
            <div class="space-y-3">
              <div class="flex items-start gap-3">
                <div class="w-8 h-8 bg-green-500 rounded-full flex items-center justify-center text-white text-sm font-bold flex-shrink-0">
                  ✓
                </div>
                <div>
                  <p class="font-medium text-slate-900">Payment Initialized</p>
                  <p class="text-slate-600 text-sm">Your payment details have been verified</p>
                </div>
              </div>

              <div class="flex items-start gap-3">
                <div class="w-8 h-8 bg-amber-500 rounded-full flex items-center justify-center text-white text-sm font-bold flex-shrink-0 animate-pulse">
                  ⏳
                </div>
                <div>
                  <p class="font-medium text-slate-900">Processing with Payment Gateway</p>
                  <p class="text-slate-600 text-sm">Your payment is being securely processed</p>
                </div>
              </div>

              <div class="flex items-start gap-3">
                <div class="w-8 h-8 bg-slate-300 rounded-full flex items-center justify-center text-slate-600 text-sm font-bold flex-shrink-0">
                  •
                </div>
                <div>
                  <p class="font-medium text-slate-900">Verification</p>
                  <p class="text-slate-600 text-sm">Payment will be verified and confirmed</p>
                </div>
              </div>

              <div class="flex items-start gap-3">
                <div class="w-8 h-8 bg-slate-300 rounded-full flex items-center justify-center text-slate-600 text-sm font-bold flex-shrink-0">
                  •
                </div>
                <div>
                  <p class="font-medium text-slate-900">Completion</p>
                  <p class="text-slate-600 text-sm">You will be redirected to confirmation page</p>
                </div>
              </div>
            </div>
          </div>

          <!-- Helpful Info -->
          <div class="bg-slate-50 rounded-lg p-4 border border-slate-200">
            <h4 class="font-semibold text-slate-900 mb-3">Important Information</h4>
            <ul class="text-slate-600 text-sm space-y-2">
              <li class="flex items-start gap-2">
                <span class="text-slate-400 mt-1">•</span>
                <span>Do not close this window or navigate away</span>
              </li>
              <li class="flex items-start gap-2">
                <span class="text-slate-400 mt-1">•</span>
                <span>Do not press the back button</span>
              </li>
              <li class="flex items-start gap-2">
                <span class="text-slate-400 mt-1">•</span>
                <span>Processing typically takes 10-30 seconds</span>
              </li>
              <li class="flex items-start gap-2">
                <span class="text-slate-400 mt-1">•</span>
                <span>You will receive a confirmation email</span>
              </li>
            </ul>
          </div>

          <!-- Cancel Action -->
          <div class="mt-8">
            <button
              @click="cancelPayment"
              class="w-full bg-slate-200 hover:bg-slate-300 text-slate-900 font-semibold py-3 rounded-lg transition"
            >
              Cancel Payment
            </button>
            <p class="text-center text-slate-500 text-sm mt-2">
              You can cancel this payment and try again later
            </p>
          </div>
        </div>
      </div>

      <!-- Auto-refresh Info -->
      <div class="mt-6 text-center text-slate-600 text-sm">
        <p>Page will auto-refresh to check payment status...</p>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { onMounted, onUnmounted, ref } from 'vue';
import { useRouter, useRoute } from 'vue-router';
import { usePaymentStore } from '@/stores/paymentStore';

// ============================================================================
// Setup
// ============================================================================

const router = useRouter();
const route = useRoute();
const paymentStore = usePaymentStore();

// ============================================================================
// State
// ============================================================================

let pollInterval: NodeJS.Timeout | null = null;
const maxChecks = ref(60); // 60 checks = 2 minutes
const checksPerformed = ref(0);

// ============================================================================
// Lifecycle
// ============================================================================

onMounted(async () => {
  const txRef = route.query.tx_ref as string;

  if (!txRef) {
    router.push('/');
    return;
  }

  try {
    // Initial fetch
    await paymentStore.verifyPayment(txRef);

    // Start polling
    startPolling(txRef);
  } catch (error) {
    console.error('Failed to fetch payment:', error);
  }
});

onUnmounted(() => {
  if (pollInterval) {
    clearInterval(pollInterval);
  }
});

// ============================================================================
// Methods
// ============================================================================

/**
 * Start polling payment status
 */
function startPolling(txRef: string): void {
  pollInterval = setInterval(async () => {
    try {
      checksPerformed.value++;

      // Check payment status
      await paymentStore.verifyPayment(txRef);

      // Payment verified or failed
      if (
        paymentStore.currentPayment?.is_verified ||
        paymentStore.currentPayment?.is_failed
      ) {
        if (pollInterval) {
          clearInterval(pollInterval);
        }

        // Redirect to appropriate page
        if (paymentStore.currentPayment.is_verified) {
          router.push(
            `/payment/success?tx_ref=${txRef}`
          );
        } else {
          router.push(
            `/payment/failed?tx_ref=${txRef}`
          );
        }
      }

      // Max checks reached
      if (checksPerformed.value >= maxChecks.value) {
        if (pollInterval) {
          clearInterval(pollInterval);
        }
        router.push(`/payment/failed?tx_ref=${txRef}`);
      }
    } catch (error) {
      console.error('Polling error:', error);
    }
  }, 2000); // Poll every 2 seconds
}

/**
 * Format date for display
 */
function formatDate(dateString: string): string {
  return new Intl.DateTimeFormat('en-ET', {
    year: 'numeric',
    month: 'long',
    day: 'numeric',
    hour: '2-digit',
    minute: '2-digit',
  }).format(new Date(dateString));
}

/**
 * Cancel payment
 */
function cancelPayment(): void {
  if (pollInterval) {
    clearInterval(pollInterval);
  }
  router.push('/');
}
</script>

<style scoped>
/* Bounce animation for icon */
@keyframes bounce {
  0%,
  100% {
    transform: translateY(0);
  }
  50% {
    transform: translateY(-10px);
  }
}

.animate-bounce {
  animation: bounce 2s infinite;
}

/* Spin animation */
@keyframes spin {
  to {
    transform: rotate(360deg);
  }
}

.animate-spin {
  animation: spin 1s linear infinite;
}

/* Pulse animation */
@keyframes pulse {
  0%,
  100% {
    opacity: 1;
  }
  50% {
    opacity: 0.5;
  }
}

.animate-pulse {
  animation: pulse 2s cubic-bezier(0.4, 0, 0.6, 1) infinite;
}
</style>

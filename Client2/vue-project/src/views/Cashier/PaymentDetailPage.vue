<script setup lang="ts">
import { ref, onMounted, computed } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import DashboardLayout from '../../layouts/DashboardLayout.vue'
import { useCashierStore } from '@/stores/cashierStore'
import {
  ArrowLeft,
  DollarSign,
  CreditCard,
  User,
  Mail,
  Phone,
  Calendar,
  Hash,
  Building,
  CheckCircle,
  XCircle,
  Clock,
  RefreshCw,
  AlertCircle,
  Home,
  UtensilsCrossed,
} from 'lucide-vue-next'

const router = useRouter()
const route = useRoute()
const cashierStore = useCashierStore()

const paymentId = route.params.id as string
const showRefundModal = ref(false)
const refundProcessing = ref(false)

onMounted(() => {
  loadPaymentDetails()
})

const loadPaymentDetails = async () => {
  await cashierStore.fetchPaymentById(paymentId)
}

const payment = computed(() => cashierStore.selectedPayment)

// Format helpers
const formatCurrency = (amount: number | string) => {
  const numAmount = typeof amount === 'string' ? parseFloat(amount) : amount
  return `${numAmount.toFixed(2)} ETB`
}

const formatDate = (date: string | null | undefined) => {
  if (!date) return 'N/A'
  return new Date(date).toLocaleDateString('en-US', {
    month: 'long',
    day: 'numeric',
    year: 'numeric',
    hour: '2-digit',
    minute: '2-digit',
  })
}

const getStatusColor = (status: string) => {
  const statusColors: Record<string, string> = {
    paid: 'bg-green-100 text-green-700 dark:bg-green-900 dark:text-green-300',
    verified: 'bg-green-100 text-green-700 dark:bg-green-900 dark:text-green-300',
    pending: 'bg-yellow-100 text-yellow-700 dark:bg-yellow-900 dark:text-yellow-300',
    initialized: 'bg-blue-100 text-blue-700 dark:bg-blue-900 dark:text-blue-300',
    failed: 'bg-red-100 text-red-700 dark:bg-red-900 dark:text-red-300',
    refunded: 'bg-purple-100 text-purple-700 dark:bg-purple-900 dark:text-purple-300',
  }
  return statusColors[status?.toLowerCase()] || 'bg-gray-100 text-gray-700'
}

const getStatusIcon = (status: string) => {
  const icons: Record<string, any> = {
    paid: CheckCircle,
    verified: CheckCircle,
    pending: Clock,
    initialized: Clock,
    failed: XCircle,
    refunded: RefreshCw,
  }
  return icons[status?.toLowerCase()] || AlertCircle
}

const canRefund = computed(() => {
  if (!payment.value) return false
  return ['paid', 'verified'].includes(payment.value.status.toLowerCase())
})

const goBack = () => {
  router.push({ name: 'cashier-payments' })
}

const handleRefund = async () => {
  if (!payment.value) return

  refundProcessing.value = true
  try {
    const success = await cashierStore.processRefund(payment.value.id)
    if (success) {
      showRefundModal.value = false
      // Reload payment details
      await loadPaymentDetails()
    }
  } finally {
    refundProcessing.value = false
  }
}
</script>

<template>
  <DashboardLayout>
    <div class="space-y-6">
      <!-- Header -->
      <div class="flex items-center justify-between">
        <div class="flex items-center gap-4">
          <button
            @click="goBack"
            class="p-2 hover:bg-slate-100 dark:hover:bg-slate-700 rounded-lg transition-colors"
          >
            <ArrowLeft :size="20" class="text-slate-700 dark:text-slate-300" />
          </button>
          <div>
            <h1 class="text-3xl font-bold text-slate-800 dark:text-white">Payment Details</h1>
            <p class="text-slate-500 dark:text-slate-400 mt-1">
              View complete payment information
            </p>
          </div>
        </div>
        <button
          v-if="canRefund"
          @click="showRefundModal = true"
          class="flex items-center gap-2 px-4 py-2 bg-red-600 hover:bg-red-700 text-white rounded-lg transition-colors"
        >
          <RefreshCw :size="18" />
          Process Refund
        </button>
      </div>

      <!-- Loading State -->
      <div v-if="cashierStore.isLoading && !payment" class="space-y-6">
        <div class="bg-white dark:bg-slate-800 rounded-xl border dark:border-slate-700 shadow-sm p-6 animate-pulse">
          <div class="h-8 bg-slate-200 dark:bg-slate-700 rounded w-1/3 mb-4"></div>
          <div class="h-4 bg-slate-200 dark:bg-slate-700 rounded w-1/2"></div>
        </div>
      </div>

      <!-- Payment Not Found -->
      <div v-else-if="!payment && !cashierStore.isLoading" class="bg-white dark:bg-slate-800 rounded-xl border dark:border-slate-700 shadow-sm p-12 text-center">
        <AlertCircle :size="48" class="mx-auto text-slate-400 mb-4" />
        <h3 class="text-xl font-semibold text-slate-800 dark:text-white mb-2">
          Payment Not Found
        </h3>
        <p class="text-slate-500 dark:text-slate-400 mb-6">
          The payment you're looking for doesn't exist or has been removed.
        </p>
        <button
          @click="goBack"
          class="px-6 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg transition-colors"
        >
          Back to Payments
        </button>
      </div>

      <!-- Payment Details -->
      <template v-else-if="payment">
        <!-- Status Card -->
        <div class="bg-white dark:bg-slate-800 rounded-xl border dark:border-slate-700 shadow-sm p-6">
          <div class="flex items-center justify-between">
            <div class="flex items-center gap-4">
              <div :class="[getStatusColor(payment.status), 'p-4 rounded-xl']">
                <component :is="getStatusIcon(payment.status)" :size="32" />
              </div>
              <div>
                <h2 class="text-2xl font-bold text-slate-800 dark:text-white capitalize">
                  {{ payment.status }}
                </h2>
                <p class="text-slate-500 dark:text-slate-400">Payment Status</p>
              </div>
            </div>
            <div class="text-right">
              <p class="text-3xl font-bold text-slate-800 dark:text-white">
                {{ formatCurrency(payment.amount) }}
              </p>
              <p class="text-slate-500 dark:text-slate-400">{{ payment.currency }}</p>
            </div>
          </div>
        </div>

        <!-- Transaction Information -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
          <!-- Payment Info -->
          <div class="bg-white dark:bg-slate-800 rounded-xl border dark:border-slate-700 shadow-sm p-6">
            <h3 class="text-lg font-semibold text-slate-800 dark:text-white mb-4 flex items-center gap-2">
              <CreditCard :size="20" class="text-blue-600" />
              Payment Information
            </h3>
            <div class="space-y-4">
              <div class="flex items-start gap-3">
                <Hash :size="20" class="text-slate-400 mt-0.5" />
                <div class="flex-1">
                  <p class="text-sm text-slate-500 dark:text-slate-400">Transaction Reference</p>
                  <p class="font-mono text-slate-800 dark:text-white font-medium">
                    {{ payment.tx_ref }}
                  </p>
                </div>
              </div>
              <div v-if="payment.chapa_transaction_id" class="flex items-start gap-3">
                <Hash :size="20" class="text-slate-400 mt-0.5" />
                <div class="flex-1">
                  <p class="text-sm text-slate-500 dark:text-slate-400">Chapa Transaction ID</p>
                  <p class="font-mono text-slate-800 dark:text-white font-medium">
                    {{ payment.chapa_transaction_id }}
                  </p>
                </div>
              </div>
              <div class="flex items-start gap-3">
                <Building :size="20" class="text-slate-400 mt-0.5" />
                <div class="flex-1">
                  <p class="text-sm text-slate-500 dark:text-slate-400">Payment Provider</p>
                  <p class="text-slate-800 dark:text-white font-medium capitalize">
                    {{ payment.payment_provider || 'N/A' }}
                  </p>
                </div>
              </div>
              <div v-if="payment.payment_method" class="flex items-start gap-3">
                <CreditCard :size="20" class="text-slate-400 mt-0.5" />
                <div class="flex-1">
                  <p class="text-sm text-slate-500 dark:text-slate-400">Payment Method</p>
                  <p class="text-slate-800 dark:text-white font-medium capitalize">
                    {{ payment.payment_method }}
                  </p>
                </div>
              </div>
              <div class="flex items-start gap-3">
                <DollarSign :size="20" class="text-slate-400 mt-0.5" />
                <div class="flex-1">
                  <p class="text-sm text-slate-500 dark:text-slate-400">Amount</p>
                  <p class="text-slate-800 dark:text-white font-medium">
                    {{ formatCurrency(payment.amount) }}
                  </p>
                </div>
              </div>
            </div>
          </div>

          <!-- Customer Info -->
          <div class="bg-white dark:bg-slate-800 rounded-xl border dark:border-slate-700 shadow-sm p-6">
            <h3 class="text-lg font-semibold text-slate-800 dark:text-white mb-4 flex items-center gap-2">
              <User :size="20" class="text-blue-600" />
              Customer Information
            </h3>
            <div class="space-y-4">
              <div class="flex items-start gap-3">
                <User :size="20" class="text-slate-400 mt-0.5" />
                <div class="flex-1">
                  <p class="text-sm text-slate-500 dark:text-slate-400">Full Name</p>
                  <p class="text-slate-800 dark:text-white font-medium">
                    {{ payment.first_name }} {{ payment.last_name }}
                  </p>
                </div>
              </div>
              <div class="flex items-start gap-3">
                <Mail :size="20" class="text-slate-400 mt-0.5" />
                <div class="flex-1">
                  <p class="text-sm text-slate-500 dark:text-slate-400">Email</p>
                  <p class="text-slate-800 dark:text-white font-medium">{{ payment.email }}</p>
                </div>
              </div>
              <div v-if="payment.phone" class="flex items-start gap-3">
                <Phone :size="20" class="text-slate-400 mt-0.5" />
                <div class="flex-1">
                  <p class="text-sm text-slate-500 dark:text-slate-400">Phone</p>
                  <p class="text-slate-800 dark:text-white font-medium">{{ payment.phone }}</p>
                </div>
              </div>
              <div v-if="payment.guest" class="flex items-start gap-3">
                <User :size="20" class="text-slate-400 mt-0.5" />
                <div class="flex-1">
                  <p class="text-sm text-slate-500 dark:text-slate-400">Guest Record</p>
                  <p class="text-slate-800 dark:text-white font-medium">
                    {{ payment.guest.name }}
                  </p>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Booking/Order Details -->
        <div v-if="payment.reservation || payment.order" class="bg-white dark:bg-slate-800 rounded-xl border dark:border-slate-700 shadow-sm p-6">
          <!-- Reservation Details -->
          <div v-if="payment.reservation">
            <h3 class="text-lg font-semibold text-slate-800 dark:text-white mb-4 flex items-center gap-2">
              <Home :size="20" class="text-blue-600" />
              Reservation Details
            </h3>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
              <div>
                <p class="text-sm text-slate-500 dark:text-slate-400 mb-1">Check-in Date</p>
                <p class="text-slate-800 dark:text-white font-medium">
                  {{ formatDate(payment.reservation.check_in_date) }}
                </p>
              </div>
              <div>
                <p class="text-sm text-slate-500 dark:text-slate-400 mb-1">Check-out Date</p>
                <p class="text-slate-800 dark:text-white font-medium">
                  {{ formatDate(payment.reservation.check_out_date) }}
                </p>
              </div>
              <div>
                <p class="text-sm text-slate-500 dark:text-slate-400 mb-1">Number of Guests</p>
                <p class="text-slate-800 dark:text-white font-medium">
                  {{ payment.reservation.number_of_guests }}
                </p>
              </div>
              <div v-if="payment.reservation.room">
                <p class="text-sm text-slate-500 dark:text-slate-400 mb-1">Room Number</p>
                <p class="text-slate-800 dark:text-white font-medium">
                  {{ payment.reservation.room.room_number }}
                </p>
              </div>
              <div v-if="payment.reservation.room">
                <p class="text-sm text-slate-500 dark:text-slate-400 mb-1">Floor</p>
                <p class="text-slate-800 dark:text-white font-medium">
                  {{ payment.reservation.room.floor }}
                </p>
              </div>
            </div>
          </div>

          <!-- Order Details -->
          <div v-if="payment.order">
            <h3 class="text-lg font-semibold text-slate-800 dark:text-white mb-4 flex items-center gap-2">
              <UtensilsCrossed :size="20" class="text-blue-600" />
              Restaurant Order Details
            </h3>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
              <div>
                <p class="text-sm text-slate-500 dark:text-slate-400 mb-1">Order ID</p>
                <p class="text-slate-800 dark:text-white font-medium">{{ payment.order.id }}</p>
              </div>
              <div>
                <p class="text-sm text-slate-500 dark:text-slate-400 mb-1">Items Count</p>
                <p class="text-slate-800 dark:text-white font-medium">
                  {{ payment.order.items_count }}
                </p>
              </div>
              <div>
                <p class="text-sm text-slate-500 dark:text-slate-400 mb-1">Order Status</p>
                <p class="text-slate-800 dark:text-white font-medium capitalize">
                  {{ payment.order.status }}
                </p>
              </div>
            </div>
          </div>
        </div>

        <!-- Timeline -->
        <div class="bg-white dark:bg-slate-800 rounded-xl border dark:border-slate-700 shadow-sm p-6">
          <h3 class="text-lg font-semibold text-slate-800 dark:text-white mb-4 flex items-center gap-2">
            <Calendar :size="20" class="text-blue-600" />
            Payment Timeline
          </h3>
          <div class="space-y-4">
            <div class="flex items-start gap-4">
              <div class="p-2 bg-blue-100 dark:bg-blue-900 rounded-lg">
                <Calendar :size="20" class="text-blue-600 dark:text-blue-300" />
              </div>
              <div>
                <p class="text-sm text-slate-500 dark:text-slate-400">Created</p>
                <p class="text-slate-800 dark:text-white font-medium">
                  {{ formatDate(payment.created_at) }}
                </p>
              </div>
            </div>
            <div v-if="payment.paid_at" class="flex items-start gap-4">
              <div class="p-2 bg-green-100 dark:bg-green-900 rounded-lg">
                <CheckCircle :size="20" class="text-green-600 dark:text-green-300" />
              </div>
              <div>
                <p class="text-sm text-slate-500 dark:text-slate-400">Paid</p>
                <p class="text-slate-800 dark:text-white font-medium">
                  {{ formatDate(payment.paid_at) }}
                </p>
              </div>
            </div>
            <div v-if="payment.verified_at" class="flex items-start gap-4">
              <div class="p-2 bg-green-100 dark:bg-green-900 rounded-lg">
                <CheckCircle :size="20" class="text-green-600 dark:text-green-300" />
              </div>
              <div>
                <p class="text-sm text-slate-500 dark:text-slate-400">Verified</p>
                <p class="text-slate-800 dark:text-white font-medium">
                  {{ formatDate(payment.verified_at) }}
                </p>
              </div>
            </div>
            <div class="flex items-start gap-4">
              <div class="p-2 bg-slate-100 dark:bg-slate-700 rounded-lg">
                <Calendar :size="20" class="text-slate-600 dark:text-slate-400" />
              </div>
              <div>
                <p class="text-sm text-slate-500 dark:text-slate-400">Last Updated</p>
                <p class="text-slate-800 dark:text-white font-medium">
                  {{ formatDate(payment.updated_at) }}
                </p>
              </div>
            </div>
          </div>
        </div>
      </template>

      <!-- Refund Modal -->
      <div
        v-if="showRefundModal"
        class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50"
        @click.self="showRefundModal = false"
      >
        <div class="bg-white dark:bg-slate-800 rounded-xl shadow-xl p-6 max-w-md w-full mx-4">
          <h3 class="text-xl font-semibold text-slate-800 dark:text-white mb-4">
            Confirm Refund
          </h3>
          <p class="text-slate-600 dark:text-slate-400 mb-6">
            Are you sure you want to refund this payment? This action cannot be undone.
          </p>
          <div class="bg-slate-100 dark:bg-slate-700 rounded-lg p-4 mb-6">
            <p class="text-sm text-slate-600 dark:text-slate-400 mb-1">Refund Amount</p>
            <p class="text-2xl font-bold text-slate-800 dark:text-white">
              {{ payment ? formatCurrency(payment.amount) : '' }}
            </p>
          </div>
          <div class="flex gap-3">
            <button
              @click="showRefundModal = false"
              :disabled="refundProcessing"
              class="flex-1 px-4 py-2 border dark:border-slate-600 rounded-lg hover:bg-slate-50 dark:hover:bg-slate-700 transition-colors disabled:opacity-50"
            >
              Cancel
            </button>
            <button
              @click="handleRefund"
              :disabled="refundProcessing"
              class="flex-1 px-4 py-2 bg-red-600 hover:bg-red-700 text-white rounded-lg transition-colors disabled:opacity-50 flex items-center justify-center gap-2"
            >
              <RefreshCw v-if="refundProcessing" :size="18" class="animate-spin" />
              {{ refundProcessing ? 'Processing...' : 'Process Refund' }}
            </button>
          </div>
        </div>
      </div>
    </div>
  </DashboardLayout>
</template>

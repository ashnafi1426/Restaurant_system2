import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import paymentService from '@/services/paymentService'

export interface Payment {
  id?: string
  tx_ref: string
  amount?: number
  currency?: string
  formatted_amount?: string
  first_name?: string
  last_name?: string
  email?: string
  phone?: string
  status?: string
  is_verified?: boolean
  is_failed?: boolean
  created_at?: string
  customer?: {
    name?: string
    email?: string
    phone?: string
  }
  checkout_url?: string
  payment_provider?: string
  metadata?: any
}

export const usePaymentStore = defineStore('payment', () => {
  const currentPayment = ref<Payment | null>(null)
  const error = ref<string | null>(null)
  const isInitializing = ref(false)
  const isLoading = ref(false)
  const isVerifying = ref(false)

  const currentCheckoutUrl = computed(() => currentPayment.value?.checkout_url)
  const currentTxRef = computed(() => currentPayment.value?.tx_ref)
  const currentAmount = computed(() => currentPayment.value?.amount)

  function setCurrentPayment(payment: Payment): void {
    currentPayment.value = payment
    error.value = null
  }

  function clearCurrentPayment(): void {
    currentPayment.value = null
    error.value = null
  }

  function setError(err: string | null): void {
    error.value = err
  }

  function setInitializing(state: boolean): void {
    isInitializing.value = state
  }

  function setLoading(state: boolean): void {
    isLoading.value = state
  }

  function setVerifying(state: boolean): void {
    isVerifying.value = state
  }

  async function verifyPayment(txRef: string): Promise<Payment> {
    isVerifying.value = true
    try {
      const res = await paymentService.verifyPayment(txRef)
      const paymentData = res.payment || res
      const isVerified =
        res.status === 'success' ||
        res.status === 'completed' ||
        paymentData.status === 'success' ||
        paymentData.status === 'completed'
      const isFailed =
        res.status === 'failed' ||
        res.status === 'cancelled' ||
        paymentData.status === 'failed' ||
        paymentData.status === 'cancelled'

      const mapped: Payment = {
        tx_ref: txRef,
        ...paymentData,
        formatted_amount:
          paymentData.formatted_amount ||
          (paymentData.amount ? `ETB ${paymentData.amount}` : undefined),
        customer: paymentData.customer || {
          name:
            paymentData.customer_name ||
            `${paymentData.first_name || ''} ${paymentData.last_name || ''}`.trim() ||
            'Guest',
          email: paymentData.email || '',
          phone: paymentData.phone || '',
        },
        is_verified: isVerified,
        is_failed: isFailed,
      }
      currentPayment.value = mapped
      return mapped
    } catch (err: any) {
      error.value = err?.message || 'Verification failed'
      throw err
    } finally {
      isVerifying.value = false
    }
  }

  return {
    currentPayment,
    error,
    isInitializing,
    isLoading,
    isVerifying,

    currentCheckoutUrl,
    currentTxRef,
    currentAmount,

    setCurrentPayment,
    clearCurrentPayment,
    setError,
    setInitializing,
    setLoading,
    setVerifying,
    verifyPayment,
  }
})

import { defineStore } from 'pinia'
import { ref, computed } from 'vue'

interface Payment {
  id?: string
  tx_ref: string
  amount?: number
  currency?: string
  first_name?: string
  last_name?: string
  email?: string
  phone?: string
  status?: string
  checkout_url?: string
  payment_provider?: string
  metadata?: any
}

export const usePaymentStore = defineStore('payment', () => {
  // State
  const currentPayment = ref<Payment | null>(null)
  const error = ref<string | null>(null)
  const isInitializing = ref(false)
  const isLoading = ref(false)
  const isVerifying = ref(false)

  // Computed
  const currentCheckoutUrl = computed(() => currentPayment.value?.checkout_url)
  const currentTxRef = computed(() => currentPayment.value?.tx_ref)
  const currentAmount = computed(() => currentPayment.value?.amount)

  // Methods
  function setCurrentPayment(payment: Payment): void {
    console.log('💾 [PAYMENT STORE] Setting current payment:', payment)
    currentPayment.value = payment
    error.value = null
    console.log('💾 [PAYMENT STORE] Updated currentCheckoutUrl:', currentCheckoutUrl.value)
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

  return {
    // State
    currentPayment,
    error,
    isInitializing,
    isLoading,
    isVerifying,

    // Computed
    currentCheckoutUrl,
    currentTxRef,
    currentAmount,

    // Methods
    setCurrentPayment,
    clearCurrentPayment,
    setError,
    setInitializing,
    setLoading,
    setVerifying,
  }
})

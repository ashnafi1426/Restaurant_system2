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
  }
})

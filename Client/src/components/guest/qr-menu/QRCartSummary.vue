<template>
  <div class="flex-shrink-0 bg-white border-t border-gray-200 px-4 py-4 space-y-2">
    <div class="flex items-center justify-between text-gray-700 text-sm">
      <span>{{ languageStore.t('subtotal', 'Subtotal') }}</span>
      <span class="font-semibold">{{ formatPrice(subtotal) }}</span>
    </div>

    <div
      class="flex items-center justify-between text-gray-900 text-lg font-bold pt-2 border-t border-gray-200"
    >
      <span>{{ languageStore.t('total', 'Total') }}</span>
      <span class="text-red-600">{{ formatPrice(total) }}</span>
    </div>

    <div class="flex flex-col gap-2 pt-3">
      <button
        @click="$emit('place-order-room-charge')"
        :disabled="isPlacingOrder || cartItemsCount === 0"
        class="w-full px-4 py-3 bg-red-500 hover:bg-red-600 text-white rounded-lg font-bold text-base transition-colors disabled:opacity-50 disabled:cursor-not-allowed flex items-center justify-center gap-2 cursor-pointer"
      >
        <svg
          v-if="!isPlacingOrder"
          class="w-6 h-6"
          fill="none"
          stroke="currentColor"
          viewBox="0 0 24 24"
        >
          <path
            stroke-linecap="round"
            stroke-linejoin="round"
            stroke-width="2"
            d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m-3-6h6"
          ></path>
        </svg>
        <svg
          v-else
          class="w-6 h-6 animate-spin"
          fill="none"
          stroke="currentColor"
          viewBox="0 0 24 24"
        >
          <path
            stroke-linecap="round"
            stroke-linejoin="round"
            stroke-width="2"
            d="M12 2v20m0-20a9.978 9.978 0 00-9 18m18 0a9.978 9.978 0 00-9-18"
          ></path>
        </svg>
        🍽️ {{ languageStore.t('order_now_pay_after', 'Order Now (Pay After Meal)') }}
      </button>

      <button
        @click="$emit('open-payment-dialog')"
        :disabled="isPlacingOrder || cartItemsCount === 0"
        class="w-full px-4 py-3 bg-yellow-500 hover:bg-yellow-600 text-white rounded-lg font-bold text-base transition-colors disabled:opacity-50 disabled:cursor-not-allowed flex items-center justify-center gap-2 cursor-pointer border-2 border-yellow-600"
      >
        <svg
          v-if="!isPlacingOrder"
          class="w-6 h-6"
          fill="none"
          stroke="currentColor"
          viewBox="0 0 24 24"
        >
          <path
            stroke-linecap="round"
            stroke-linejoin="round"
            stroke-width="2"
            d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"
          ></path>
        </svg>
        💳 {{ languageStore.t('pay_now_with_chapa', 'Pay Now with Chapa') }}
      </button>

      <button
        @click="$emit('continue-shopping')"
        class="w-full px-4 py-2 border-2 border-gray-300 rounded-lg font-semibold text-gray-700 hover:bg-gray-50 transition-colors cursor-pointer"
      >
        {{ languageStore.t('continue_shopping', 'Continue Shopping') }}
      </button>

      <p class="text-xs text-gray-500 text-center mt-2">
        {{
          languageStore.t(
            'payment_choice_desc',
            'Choose: Order now and pay after eating, or pay online now',
          )
        }}
      </p>
    </div>
  </div>
</template>

<script setup lang="ts">
import { useLanguageStore } from '@/stores/language'

const languageStore = useLanguageStore()

interface Props {
  subtotal: number
  tax: number
  serviceCharge: number
  total: number
  canOrder: boolean
  isPlacingOrder: boolean
  cartItemsCount: number
}

defineProps<Props>()

defineEmits<{
  'place-order-room-charge': []
  'open-payment-dialog': []
  'continue-shopping': []
}>()

const formatPrice = (price: number): string => {
  return `ETB ${price.toFixed(2)}`
}
</script>

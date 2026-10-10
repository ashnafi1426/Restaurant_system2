<template>
  <Teleport to="body">
    <Transition name="fade">
      <div
        v-if="show"
        class="fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4"
        @click.self="$emit('close')"
      >
        <div class="bg-white rounded-2xl shadow-2xl max-w-md w-full max-h-[85vh] flex flex-col">
          <div
            class="bg-gradient-to-r from-amber-500 to-amber-600 px-5 py-4 text-white flex-shrink-0 rounded-t-2xl"
          >
            <h3 class="text-xl font-bold mb-1">
              💳 {{ languageStore.t('payment_confirmation', 'Payment Confirmation') }}
            </h3>
            <p class="text-amber-100 text-sm">
              {{
                orderContext?.type === 'table'
                  ? languageStore.t('enter_details_proceed', 'Enter details to proceed')
                  : languageStore.t(
                      'review_order_before_payment',
                      'Review your order before payment',
                    )
              }}
            </p>
          </div>

          <div class="p-5 space-y-3 overflow-y-auto flex-1">
            <QRPaymentForm
              v-if="orderContext?.type === 'table'"
              v-model="localPaymentForm"
              :order-context="orderContext"
            />

            <div>
              <h4 class="font-semibold text-sm mb-2">
                {{ languageStore.t('order_summary', 'Order Summary') }}
              </h4>
              <div class="space-y-2 text-xs">
                <div class="flex justify-between">
                  <span class="text-slate-600">{{ languageStore.t('room', 'Room') }}:</span>
                  <span class="font-medium">{{ roomNumber }}</span>
                </div>
                <div class="flex justify-between">
                  <span class="text-slate-600">{{ languageStore.t('items', 'Items') }}:</span>
                  <span class="font-medium">{{ cartItems.length }}</span>
                </div>
                <div class="flex justify-between">
                  <span class="text-slate-600">{{ languageStore.t('guest', 'Guest') }}:</span>
                  <span class="font-medium">{{ guestName }}</span>
                </div>
              </div>
            </div>

            <div class="border-t pt-3">
              <h4 class="font-semibold text-sm mb-2">
                {{ languageStore.t('your_items', 'Your Items') }}
              </h4>
              <div class="space-y-2">
                <div v-for="item in cartItems" :key="item.id" class="flex justify-between text-xs">
                  <span class="text-slate-700">{{ item.name }} × {{ item.quantity }}</span>
                  <span class="font-medium">{{ formatPrice(item.price * item.quantity) }}</span>
                </div>
              </div>
            </div>

            <div class="border-t pt-3 space-y-1.5">
              <div class="flex justify-between text-xs">
                <span class="text-slate-600">{{ languageStore.t('subtotal', 'Subtotal') }}:</span>
                <span class="font-medium">{{ formatPrice(subtotal) }}</span>
              </div>
              <div class="flex justify-between text-xs">
                <span class="text-slate-600">{{ languageStore.t('tax', 'Tax') }} (15%):</span>
                <span class="font-medium">{{ formatPrice(tax) }}</span>
              </div>
              <div class="flex justify-between text-xs">
                <span class="text-slate-600"
                  >{{ languageStore.t('service_charge', 'Service Charge') }} (10%):</span
                >
                <span class="font-medium">{{ formatPrice(serviceCharge) }}</span>
              </div>
              <div class="flex justify-between text-sm font-bold pt-1.5 border-t">
                <span>{{ languageStore.t('total', 'Total') }}:</span>
                <span class="text-amber-600">{{ formatPrice(total) }}</span>
              </div>
            </div>

            <div
              v-if="orderContext?.type === 'table'"
              class="bg-amber-50 border border-amber-200 rounded-lg p-2 text-xs text-amber-700"
            >
              {{ languageStore.t('walk_in_order_for', 'Walk-in order for') }}
              {{ orderContext.displayName }}
            </div>

            <div class="bg-blue-50 border border-blue-200 rounded-lg p-2 text-xs text-blue-700">
              ✓ {{ languageStore.t('secure_payment_notice', 'Secure payment via Chapa gateway') }}
            </div>
          </div>

          <div class="bg-slate-50 px-5 py-3 flex gap-2.5 flex-shrink-0 border-t rounded-b-2xl">
            <button
              @click="$emit('close')"
              :disabled="isPlacingOrder"
              class="flex-1 px-4 py-2 text-sm font-medium border border-slate-300 rounded-lg hover:bg-slate-100 disabled:opacity-50 transition-colors cursor-pointer"
            >
              {{ languageStore.t('cancel', 'Cancel') }}
            </button>
            <button
              @click="$emit('proceed')"
              :disabled="isPlacingOrder"
              class="flex-1 px-4 py-2 text-sm font-medium bg-amber-600 text-white rounded-lg hover:bg-amber-700 disabled:opacity-50 flex items-center justify-center gap-2 transition-colors cursor-pointer"
            >
              <span v-if="isPlacingOrder"
                >⌛ {{ languageStore.t('processing', 'Processing...') }}</span
              >
              <span v-else>💳 {{ languageStore.t('pay_now', 'Pay Now') }}</span>
            </button>
          </div>
        </div>
      </div>
    </Transition>
  </Teleport>
</template>

<script setup lang="ts">
import { computed } from 'vue'
import type { CartItem, OrderContext, PaymentForm } from '@/types/qrMenu'
import { useLanguageStore } from '@/stores/language'
import QRPaymentForm from './QRPaymentForm.vue'

const languageStore = useLanguageStore()

interface Props {
  show: boolean
  cartItems: CartItem[]
  subtotal: number
  tax: number
  serviceCharge: number
  total: number
  orderContext: OrderContext | null
  paymentForm: PaymentForm
  roomNumber: string
  guestName: string
  isPlacingOrder: boolean
}

const props = defineProps<Props>()

const emit = defineEmits<{
  close: []
  proceed: []
  'update:payment-form': [value: PaymentForm]
}>()

const localPaymentForm = computed({
  get: () => props.paymentForm,
  set: (val) => emit('update:payment-form', val),
})

const formatPrice = (price: number): string => {
  return `ETB ${price.toFixed(2)}`
}
</script>

<style scoped>
.fade-enter-active,
.fade-leave-active {
  transition: opacity 0.3s ease;
}

.fade-enter-from,
.fade-leave-to {
  opacity: 0;
}
</style>

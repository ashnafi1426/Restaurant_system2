<template>
  <Teleport to="body">
    <Transition name="fade">
      <div
        v-if="show"
        class="fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4"
        @click.self="$emit('close')"
      >
        <div class="bg-white rounded-xl shadow-2xl max-w-2xl w-full max-h-[90vh] flex flex-col">
          <div
            class="flex-shrink-0 bg-gradient-to-r from-amber-500 to-amber-600 text-white px-6 py-4 flex items-center justify-between border-b border-amber-600 rounded-t-xl"
          >
            <h2 class="text-2xl font-bold flex items-center gap-2">
              <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path
                  stroke-linecap="round"
                  stroke-linejoin="round"
                  stroke-width="2"
                  d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"
                ></path>
              </svg>
              {{ languageStore.t('your_cart', 'Your Cart') }}
            </h2>
            <button
              @click="$emit('close')"
              class="text-white hover:bg-white/20 p-2 rounded-lg transition-colors cursor-pointer"
            >
              <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path
                  stroke-linecap="round"
                  stroke-linejoin="round"
                  stroke-width="2"
                  d="M6 18L18 6M6 6l12 12"
                ></path>
              </svg>
            </button>
          </div>

          <div class="flex-1 overflow-y-auto bg-white">
            <div v-if="cartItems.length === 0" class="px-6 py-12 text-center">
              <svg
                class="w-16 h-16 text-gray-300 mx-auto mb-4"
                fill="none"
                stroke="currentColor"
                viewBox="0 0 24 24"
              >
                <path
                  stroke-linecap="round"
                  stroke-linejoin="round"
                  stroke-width="1.5"
                  d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"
                ></path>
              </svg>
              <p class="text-gray-500 text-lg font-medium">
                {{ languageStore.t('cart_empty', 'Your cart is empty') }}
              </p>
              <p class="text-gray-400 text-sm mt-1">
                {{
                  languageStore.t('add_items_from_menu', 'Add items from the menu to get started')
                }}
              </p>
              <button
                @click="$emit('close')"
                class="mt-4 inline-flex items-center gap-2 bg-amber-100 text-amber-700 px-6 py-2 rounded-lg font-medium hover:bg-amber-200 transition-colors cursor-pointer"
              >
                {{ languageStore.t('continue_shopping', 'Continue Shopping') }}
              </button>
            </div>

            <div v-if="cartItems.length > 0" class="divide-y divide-gray-200">
              <QRCartItem
                v-for="(item, index) in cartItems"
                :key="`cart-item-${item.id}-${index}`"
                :item="item"
                :can-order="canOrder"
                @increment="$emit('increment', item.id)"
                @decrement="$emit('decrement', item.id)"
                @remove="$emit('remove', item.id)"
              />
            </div>
          </div>

          <QRCartSummary
            :subtotal="subtotal"
            :tax="tax"
            :service-charge="serviceCharge"
            :total="total"
            :can-order="canOrder"
            :is-placing-order="isPlacingOrder"
            :cart-items-count="cartItems.length"
            @place-order-room-charge="$emit('place-order-room-charge')"
            @open-payment-dialog="$emit('open-payment-dialog')"
            @continue-shopping="$emit('close')"
          />
        </div>
      </div>
    </Transition>
  </Teleport>
</template>

<script setup lang="ts">
import type { CartItem } from '@/types/qrMenu'
import { useLanguageStore } from '@/stores/language'
import QRCartItem from './QRCartItem.vue'
import QRCartSummary from './QRCartSummary.vue'

const languageStore = useLanguageStore()

interface Props {
  show: boolean
  cartItems: CartItem[]
  subtotal: number
  tax: number
  serviceCharge: number
  total: number
  canOrder: boolean
  isPlacingOrder: boolean
}

defineProps<Props>()

defineEmits<{
  close: []
  'place-order-room-charge': []
  'open-payment-dialog': []
  increment: [itemId: string | number]
  decrement: [itemId: string | number]
  remove: [itemId: string | number]
}>()
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

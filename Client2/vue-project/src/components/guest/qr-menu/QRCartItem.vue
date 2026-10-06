<template>
  <div class="px-4 py-4 flex gap-3 bg-white hover:bg-gray-50 transition-colors">
    <!-- Item Image -->
    <img
      :src="item.image || '/images/placeholder.png'"
      :alt="item.name"
      class="w-16 h-16 rounded-lg object-cover flex-shrink-0"
    />

    <!-- Item Details -->
    <div class="flex-1 min-w-0">
      <!-- Item Name & Price -->
      <div class="flex items-start justify-between mb-2">
        <div class="flex-1">
          <h3 class="font-bold text-gray-900 text-base">{{ item.name }}</h3>
          <p class="text-xs text-gray-500 mt-0.5">{{ item.description || 'ETB' }}</p>
          <p class="text-sm font-bold text-gray-900 mt-1">{{ formatPrice(item.price) }}</p>
        </div>
        
        <!-- Remove/Delete Button -->
        <button
          @click="$emit('remove')"
          class="text-red-500 hover:text-red-700 transition-colors p-1 cursor-pointer"
          :title="languageStore.t('remove', 'Remove')"
        >
          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path
              stroke-linecap="round"
              stroke-linejoin="round"
              stroke-width="2"
              d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"
            ></path>
          </svg>
        </button>
      </div>
      
      <!-- Quantity Controls + Total Price (Inline) -->
      <div class="flex items-center justify-between mt-2">
        <!-- -  1  + Controls -->
        <div class="flex items-center gap-2 bg-gray-100 rounded-lg px-2 py-1">
          <button
            @click="$emit('decrement')"
            class="w-8 h-8 flex items-center justify-center text-gray-700 hover:bg-gray-200 rounded transition-colors cursor-pointer font-bold text-lg"
            :title="item.quantity === 1 ? 'Remove item' : 'Decrease'"
          >
            −
          </button>
          
          <span class="text-lg font-bold text-gray-900 px-3 min-w-[2rem] text-center">{{ item.quantity }}</span>
          
          <button
            @click="$emit('increment')"
            class="w-8 h-8 flex items-center justify-center text-gray-700 hover:bg-gray-200 rounded transition-colors cursor-pointer font-bold text-lg"
            title="Increase"
          >
            +
          </button>
        </div>
        <!-- Item Total Price -->
        <span class="text-base font-bold text-gray-900">{{ formatPrice(item.price * item.quantity) }}</span>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import type { CartItem } from '@/types/qrMenu'
import { useLanguageStore } from '@/stores/language'

const languageStore = useLanguageStore()

interface Props {
  item: CartItem
  canOrder?: boolean
}

withDefaults(defineProps<Props>(), {
  canOrder: true
})

defineEmits<{
  increment: []
  decrement: []
  remove: []
}>()

const formatPrice = (price: number): string => {
  return `ETB ${price.toFixed(2)}`
}
</script>

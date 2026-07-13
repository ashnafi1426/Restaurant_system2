<template>
  <div
    class="group bg-gray-50 dark:bg-gray-700/50 rounded-lg p-3 sm:p-4 border border-gray-200 dark:border-gray-600 hover:border-gray-300 dark:hover:border-gray-500 transition-all duration-200"
    :class="{ 'border-l-4 border-l-orange-500': order.priority }"
  >
    <div class="flex flex-col sm:flex-row items-start justify-between gap-2 sm:gap-3 mb-2 sm:mb-3">
      <div class="flex items-center gap-2 sm:gap-3 min-w-0">
        <div class="flex items-center gap-1 sm:gap-2 flex-wrap">
          <span
            v-if="order.priority"
            class="text-xs font-bold text-orange-600 dark:text-orange-400 bg-orange-50 dark:bg-orange-900/20 px-1.5 sm:px-2 py-0.5 rounded whitespace-nowrap"
          >
            PRIORITY
          </span>
          <span
            class="text-xs sm:text-sm font-semibold text-gray-700 dark:text-gray-300 whitespace-nowrap"
          >
            ROOM {{ order.room }}
          </span>
          <span v-if="order.customer" class="text-xs text-gray-400 hidden sm:inline truncate"
            >• {{ order.customer }}</span
          >
        </div>
      </div>
      <div class="flex items-center gap-2 sm:gap-3 flex-shrink-0">
        <span
          class="text-xs font-medium text-gray-500 dark:text-gray-400 bg-white dark:bg-gray-600 px-1.5 sm:px-2 py-0.5 rounded-full shadow-sm whitespace-nowrap"
        >
          ⏱ {{ order.time }}
        </span>
        <button
          @click="$emit('view-details', order)"
          class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 transition flex-shrink-0"
        >
          <MoreVertical class="w-3 h-3 sm:w-4 sm:h-4" />
        </button>
      </div>
    </div>

    <!-- Order Items -->
    <div class="space-y-1 sm:space-y-2">
      <div v-for="(item, index) in order.items" :key="index" class="flex items-start gap-2">
        <span
          class="text-xs sm:text-sm font-medium text-gray-900 dark:text-gray-100 min-w-[24px] flex-shrink-0"
        >
          {{ item.quantity }}x
        </span>
        <div class="flex-1 min-w-0">
          <p class="text-xs sm:text-sm font-medium text-gray-800 dark:text-gray-200 truncate">
            {{ item.name }}
          </p>
          <p v-if="item.note" class="text-xs text-gray-500 dark:text-gray-400 mt-0.5 truncate">
            {{ item.note }}
          </p>
        </div>
      </div>
    </div>

    <!-- Actions -->
    <div
      class="flex items-center gap-1 sm:gap-2 mt-3 sm:mt-4 pt-2 sm:pt-3 border-t border-gray-200 dark:border-gray-600 flex-wrap"
    >
      <template v-if="order.status === 'pending'">
        <button
          @click="$emit('start-preparing', order)"
          class="flex-1 min-w-[120px] inline-flex items-center justify-center gap-1 sm:gap-1.5 px-2 sm:px-3 md:px-4 py-1.5 sm:py-2 text-xs sm:text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 rounded-lg transition"
        >
          <CookingPot class="w-3 h-3 sm:w-4 sm:h-4" />
          <span class="hidden sm:inline">START PREPARING</span><span class="sm:hidden">START</span>
        </button>
      </template>

      <template v-else-if="order.status === 'preparing'">
        <button
          @click="$emit('mark-ready', order)"
          class="flex-1 min-w-[120px] inline-flex items-center justify-center gap-1 sm:gap-1.5 px-2 sm:px-3 md:px-4 py-1.5 sm:py-2 text-xs sm:text-sm font-medium text-white bg-green-600 hover:bg-green-700 rounded-lg transition"
        >
          <CheckCircle2 class="w-3 h-3 sm:w-4 sm:h-4" />
          <span class="hidden sm:inline">MARK READY</span><span class="sm:hidden">READY</span>
        </button>
      </template>

      <template v-else-if="order.status === 'ready'">
        <button
          @click="$emit('mark-served', order)"
          class="flex-1 min-w-[120px] inline-flex items-center justify-center gap-1 sm:gap-1.5 px-2 sm:px-3 md:px-4 py-1.5 sm:py-2 text-xs sm:text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 rounded-lg transition"
        >
          <CheckCircle2 class="w-3 h-3 sm:w-4 sm:h-4" />
          <span class="hidden sm:inline">MARK SERVED</span><span class="sm:hidden">SERVED</span>
        </button>
      </template>

      <button
        class="p-1.5 sm:p-2 text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-600 rounded-lg transition flex-shrink-0"
      >
        <Clock class="w-3 h-3 sm:w-4 sm:h-4" />
      </button>
    </div>
  </div>
</template>

<script setup lang="ts">
import { MoreVertical, CookingPot, CheckCircle2, Clock } from 'lucide-vue-next'

defineProps<{
  order: {
    id: number
    room: string
    priority: boolean
    time: string
    items: Array<{
      name: string
      quantity: number
      note?: string
    }>
    status: 'pending' | 'preparing' | 'ready' | 'served'
    customer?: string
  }
}>()

defineEmits<{
  (e: 'view-details', order: any): void
  (e: 'start-preparing', order: any): void
  (e: 'mark-ready', order: any): void
  (e: 'mark-served', order: any): void
}>()
</script>

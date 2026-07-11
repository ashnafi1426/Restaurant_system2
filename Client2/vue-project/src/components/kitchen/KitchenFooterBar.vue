<script setup lang="ts">
defineProps<{
  statistics?: {
    pending_orders: number
    preparing_orders: number
    ready_orders: number
    served_orders: number
    total_orders: number
    today_orders: number
    today_served: number
    today_pending: number
    today_preparing: number
    today_ready: number
  }
}>()

import { computed } from 'vue'

const ticketsNeeding = computed(() => {
  return 4 // This would come from statistics
})
</script>

<template>
  <div class="border-t border-slate-200 bg-white px-4 sm:px-6 md:px-8 py-3 sm:py-4 shadow-lg">
    <div class="flex flex-col items-center justify-between gap-3 sm:gap-4 md:flex-row">
      <!-- Live Status -->
      <div class="flex items-center gap-2 flex-wrap justify-center md:justify-start">
        <span class="h-2 sm:h-3 w-2 sm:w-3 animate-pulse rounded-full bg-green-500 flex-shrink-0"></span>
        <p class="text-xs sm:text-sm font-semibold text-slate-900 whitespace-nowrap">LIVE SYNCING</p>
        <p class="text-xs sm:text-sm text-slate-500 hidden sm:inline">
          {{ ticketsNeeding }} Tickets needing immediate attention
        </p>
      </div>

      <!-- Action Buttons -->
      <div class="flex gap-2 sm:gap-3 flex-wrap justify-center">
        <button class="flex items-center gap-1 sm:gap-2 rounded-lg border border-slate-300 px-2 sm:px-3 md:px-4 py-1.5 sm:py-2 font-semibold text-xs sm:text-sm text-slate-700 transition hover:bg-slate-50 whitespace-nowrap">
          <span class="text-xs sm:text-sm">🖨️</span> <span class="hidden sm:inline">PRINT ALL TICKETS</span><span class="sm:hidden">PRINT</span>
        </button>
        <button class="flex items-center gap-1 sm:gap-2 rounded-lg bg-slate-900 px-2 sm:px-3 md:px-4 py-1.5 sm:py-2 font-semibold text-xs sm:text-sm text-white transition hover:bg-black whitespace-nowrap">
          <span class="text-xs sm:text-sm">🔔</span> <span class="hidden sm:inline">MUTE ALERTS</span><span class="sm:hidden">MUTE</span>
        </button>
      </div>
    </div>
  </div>
</template>

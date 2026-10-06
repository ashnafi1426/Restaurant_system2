<script setup lang="ts">
import { computed } from 'vue'
import { Printer, BellOff } from 'lucide-vue-next'
import { useLanguageStore } from '@/stores/language'

const languageStore = useLanguageStore()

const props = defineProps<{
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

const ticketsNeeding = computed(() => {
  return props.statistics?.pending_orders ?? 0
})
</script>

<template>
  <div class="border-t border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 px-4 sm:px-6 md:px-8 py-3 sm:py-4 shadow-lg rounded-2xl transition-colors duration-300">
    <div class="flex flex-col items-center justify-between gap-3 sm:gap-4 md:flex-row">
      <div class="flex items-center gap-2 flex-wrap justify-center md:justify-start">
        <span
          class="h-2.5 w-2.5 animate-pulse rounded-full bg-emerald-500 flex-shrink-0"
        ></span>
        <p class="text-xs sm:text-sm font-black text-slate-900 dark:text-white uppercase tracking-wider">
          {{ languageStore.t('live_syncing', 'LIVE SYNCING') }}
        </p>
        <p class="text-xs sm:text-sm text-slate-500 dark:text-slate-400 font-medium hidden sm:inline">
          • <span class="font-extrabold text-amber-500">{{ ticketsNeeding }}</span> {{ languageStore.t('tickets_needing_attention', 'Tickets needing immediate attention') }}
        </p>
      </div>

      <div class="flex gap-2 sm:gap-3 flex-wrap justify-center">
        <button
          class="flex items-center gap-2 rounded-xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-800/80 px-3 sm:px-4 py-2 font-bold text-xs sm:text-sm text-slate-700 dark:text-slate-200 transition hover:bg-slate-50 dark:hover:bg-slate-700 whitespace-nowrap cursor-pointer"
        >
          <Printer class="w-4 h-4 text-slate-500 dark:text-slate-400" />
          <span class="hidden sm:inline">{{ languageStore.t('print_all_tickets', 'PRINT ALL TICKETS') }}</span>
          <span class="sm:hidden">{{ languageStore.t('print', 'PRINT') }}</span>
        </button>
        <button
          class="flex items-center gap-2 rounded-xl bg-slate-900 dark:bg-slate-800 px-3 sm:px-4 py-2 font-bold text-xs sm:text-sm text-white transition hover:bg-slate-800 dark:hover:bg-slate-700 whitespace-nowrap cursor-pointer"
        >
          <BellOff class="w-4 h-4 text-amber-400" />
          <span class="hidden sm:inline">{{ languageStore.t('mute_alerts', 'MUTE ALERTS') }}</span>
          <span class="sm:hidden">{{ languageStore.t('mute', 'MUTE') }}</span>
        </button>
      </div>
    </div>
  </div>
</template>

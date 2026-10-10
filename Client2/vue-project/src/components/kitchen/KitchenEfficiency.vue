<template>
  <div
    class="rounded-2xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 text-slate-900 dark:text-white shadow-md overflow-hidden"
  >
    <div
      class="border-b border-slate-200 dark:border-slate-800 px-4 sm:px-5 py-3 sm:py-3.5 bg-slate-50 dark:bg-slate-800/80"
    >
      <h2
        class="text-xs sm:text-sm font-black uppercase tracking-wider text-slate-900 dark:text-white flex items-center gap-2"
      >
        <Clock class="w-4 h-4 text-amber-500" />
        <span>{{ languageStore.t('kitchen_efficiency', 'Kitchen Efficiency') }}</span>
      </h2>
    </div>

    <div class="space-y-4 sm:space-y-5 px-4 sm:px-5 py-4">
      <div>
        <p
          class="text-[10px] font-black uppercase tracking-wider text-slate-500 dark:text-slate-400"
        >
          {{ languageStore.t('avg_prep_time', 'Avg. Prep Time') }}
        </p>
        <p class="mt-1 text-3xl font-black text-slate-900 dark:text-white" v-if="avgPrepTime">
          {{ avgPrepTimeMinutes }}<span class="text-xl">:{{ avgPrepTimeSeconds }}</span>
          <span class="text-xs text-slate-500 dark:text-slate-400 ml-1 font-bold">{{
            languageStore.t('min', 'min')
          }}</span>
        </p>
        <p v-else class="mt-1 text-sm text-slate-400">
          -- {{ languageStore.t('no_data', 'No data') }}
        </p>
        <p
          v-if="prepTimeTrend"
          class="mt-1 text-xs font-bold flex items-center gap-1"
          :class="prepTimeTrend > 0 ? 'text-rose-500' : 'text-emerald-500'"
        >
          <span
            >{{ prepTimeTrend > 0 ? '↑' : '↓' }} {{ Math.abs(prepTimeTrend) }}%
            {{ languageStore.t('vs_yesterday', 'vs. yesterday') }}</span
          >
        </p>
      </div>

      <div>
        <p
          class="text-[10px] font-black uppercase tracking-wider text-slate-500 dark:text-slate-400"
        >
          {{ languageStore.t('in_progress', 'In Progress') }}
        </p>
        <p class="mt-1 text-xl font-black text-slate-900 dark:text-white flex items-center gap-2">
          <span class="text-amber-500 text-2xl font-black">{{
            statistics?.preparing_orders || 0
          }}</span>
          <span class="text-slate-500 dark:text-slate-400 text-xs font-bold uppercase">{{
            languageStore.t('preparing', 'preparing')
          }}</span>
        </p>
      </div>

      <button
        class="w-full rounded-xl bg-amber-500 hover:bg-amber-600 text-slate-950 px-3.5 py-2 font-black text-xs transition cursor-pointer shadow-md shadow-amber-500/20"
      >
        {{ languageStore.t('view_details', 'VIEW DETAILS') }}
      </button>
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue'
import { Clock } from 'lucide-vue-next'
import { useLanguageStore } from '@/stores/language'

const languageStore = useLanguageStore()

const props = defineProps<{
  statistics?: {
    pending_orders: number
    preparing_orders: number
    ready_orders: number
    served_orders: number
    total_orders: number
    today_orders?: number
    today_served?: number
    today_pending?: number
    today_preparing?: number
    today_ready?: number
    avg_prep_time_minutes?: number
  }
}>()

const avgPrepTime = computed(() => {
  return Number(props.statistics?.avg_prep_time_minutes ?? 0)
})

const avgPrepTimeMinutes = computed(() => Math.floor(avgPrepTime.value))
const avgPrepTimeSeconds = computed(() =>
  Math.floor((avgPrepTime.value % 1) * 60)
    .toString()
    .padStart(2, '0'),
)

const prepTimeTrend = computed(() => null)
</script>

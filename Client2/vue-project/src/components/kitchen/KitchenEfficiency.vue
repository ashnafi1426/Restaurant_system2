<template>
  <div class="rounded-lg bg-gradient-to-br from-slate-900 to-slate-800 text-white shadow-lg">
    <div class="px-4 sm:px-5 py-3 sm:py-4">
      <h2 class="text-base sm:text-lg font-bold">Kitchen Efficiency</h2>
    </div>

    <div class="space-y-4 sm:space-y-5 md:space-y-6 px-4 sm:px-5 py-3 sm:py-4">
      <!-- Avg Prep Time -->
      <div>
        <p class="text-xs font-semibold uppercase tracking-wider text-slate-400">Avg. Prep Time</p>
        <p class="mt-1 text-3xl sm:text-4xl font-bold text-white" v-if="avgPrepTime">
          {{ avgPrepTimeMinutes }}<span class="text-lg sm:text-2xl">:{{ avgPrepTimeSeconds }}</span>
          <span class="text-sm sm:text-lg text-slate-400">min</span>
        </p>
        <p v-else class="mt-1 text-sm sm:text-lg text-slate-400">-- No data</p>
        <p
          v-if="prepTimeTrend"
          class="mt-1 text-xs"
          :class="prepTimeTrend > 0 ? 'text-red-400' : 'text-green-400'"
        >
          {{ prepTimeTrend > 0 ? '↑' : '↓' }} {{ Math.abs(prepTimeTrend) }}% vs. yesterday
        </p>
      </div>

      <!-- Orders in Progress -->
      <div>
        <p class="text-xs font-semibold uppercase tracking-wider text-slate-400">In Progress</p>
        <p class="mt-2 text-xl sm:text-2xl font-bold text-white">
          <span class="text-blue-400">{{ statistics?.preparing_orders || 0 }}</span>
          <span class="text-slate-400 text-sm sm:text-lg ml-2">preparing</span>
        </p>
      </div>

      <!-- Action Button -->
      <button
        class="w-full rounded-lg bg-teal-600 px-3 sm:px-4 py-2 sm:py-2.5 font-bold text-xs sm:text-sm text-white transition hover:bg-teal-700"
      >
        VIEW DETAILS
      </button>
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue'

defineProps<{
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
  }
}>()

/**
 * Placeholder average prep time
 * In a real scenario, this would come from statistics endpoint
 * or be calculated from order preparation timestamps
 */
const avgPrepTime = computed(() => 14.37) // 14 minutes 22 seconds

const avgPrepTimeMinutes = computed(() => Math.floor(avgPrepTime.value))
const avgPrepTimeSeconds = computed(() =>
  Math.floor((avgPrepTime.value % 1) * 60)
    .toString()
    .padStart(2, '0'),
)

/**
 * Prep time trend (hardcoded as placeholder)
 * Would come from comparison with yesterday's data
 */
const prepTimeTrend = computed(() => -12) // negative = improvement
</script>

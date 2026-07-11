<template>
  <div class="bg-white rounded-lg sm:rounded-xl p-3 sm:p-5 border border-slate-200 shadow-sm">
    <div class="flex items-center justify-between mb-2 sm:mb-4">
      <h3
        class="text-xs font-black text-slate-900 uppercase tracking-wider flex items-center gap-1.5 sm:gap-2 truncate"
      >
        <v-icon size="14" sm:size="16" color="amber">mdi-trophy</v-icon>
        <span class="hidden sm:inline">Top Sellers</span><span class="sm:hidden">Top</span>
      </h3>
      <span class="text-xs font-bold text-slate-400 hidden sm:inline">Last 30 Days</span>
    </div>

    <!-- Loading State -->
    <div v-if="loading" class="flex items-center justify-center py-8 sm:py-12">
      <v-progress-circular indeterminate color="slate-400" size="28" sm:size="32" width="3" />
    </div>

    <!-- Empty State -->
    <div
      v-else-if="topSellers.length === 0"
      class="flex flex-col items-center justify-center py-8 sm:py-12 text-center"
    >
      <v-icon size="28" sm:size="32" color="slate-300">mdi-silverware-fork-knife</v-icon>
      <p class="text-xs text-slate-500 mt-2">No sales data yet</p>
    </div>

    <!-- Top Sellers List -->
    <div v-else class="space-y-2 sm:space-y-4">
      <div v-for="(dish, index) in topSellers" :key="dish.id" class="flex items-center gap-2 sm:gap-3">
        <!-- Rank Badge -->
        <div class="relative flex-shrink-0">
          <img
            :src="dish.image || 'https://via.placeholder.com/44'"
            class="w-9 sm:w-11 h-9 sm:h-11 rounded-lg sm:rounded-xl object-cover bg-slate-50 border border-slate-100 shadow-sm"
            alt=""
          />
          <div
            class="absolute -top-1 sm:-top-1.5 -left-1 sm:-left-1.5 w-5 h-5 rounded-full flex items-center justify-center font-black text-xs border-2 border-white shadow-md"
            :class="getRankClass(index)"
          >
            {{ index + 1 }}
          </div>
        </div>

        <!-- Dish Info -->
        <div class="flex-1 min-w-0">
          <div class="text-xs sm:text-sm font-bold text-slate-800 truncate">{{ dish.name }}</div>
          <div class="text-xs text-slate-500 font-medium mt-0.5 flex items-center gap-1">
            <v-icon size="10" sm:size="12">mdi-shopping-outline</v-icon>
            <span class="hidden sm:inline">{{ dish.total_orders }} Orders</span><span class="sm:hidden">{{ dish.total_orders }}</span>
          </div>
          <!-- Progress Bar -->
          <div class="w-full bg-slate-100 h-1 sm:h-1.5 rounded-full mt-1 overflow-hidden">
            <div
              class="h-full rounded-full transition-all duration-500"
              :class="getProgressClass(index)"
              :style="{ width: (dish.total_orders / maxOrders) * 100 + '%' }"
            />
          </div>
        </div>

        <!-- Order Count Badge -->
        <div class="text-right flex-shrink-0">
          <div class="text-xs sm:text-sm font-bold text-slate-900">{{ dish.total_orders }}</div>
          <div class="text-xs text-slate-500 hidden sm:block">orders</div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed } from 'vue'

interface TopSeller {
  id: number
  name: string
  image: string
  total_orders: number
}

const props = defineProps<{
  topSellers: TopSeller[]
  loading?: boolean
}>()

/**
 * Calculate max orders for progress bar scaling
 */
const maxOrders = computed(() => {
  if (props.topSellers.length === 0) return 1
  return Math.max(...props.topSellers.map((d) => d.total_orders))
})

/**
 * Get CSS class for rank badge based on position
 */
function getRankClass(index: number): string {
  switch (index) {
    case 0:
      return 'bg-amber-500 text-white' // Gold
    case 1:
      return 'bg-slate-400 text-white' // Silver
    case 2:
      return 'bg-orange-600 text-white' // Bronze
    default:
      return 'bg-slate-600 text-white'
  }
}

/**
 * Get CSS class for progress bar based on rank
 */
function getProgressClass(index: number): string {
  switch (index) {
    case 0:
      return 'bg-amber-500'
    case 1:
      return 'bg-slate-400'
    case 2:
      return 'bg-orange-600'
    default:
      return 'bg-slate-600'
  }
}
</script>

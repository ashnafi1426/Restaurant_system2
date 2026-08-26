<template>
  <!-- Main Horizontally Responsive Grid Layout matching your visual mockup design -->
  <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-5 gap-4">
    <div
      v-for="card in dynamicStatCards"
      :key="card.key"
      @click="$emit('select', selectedCategory === card.key ? null : card.key)"
      class="bg-white dark:bg-slate-900 rounded-2xl p-4 border transition duration-200 hover:shadow-md cursor-pointer flex flex-col justify-between select-none relative group shadow-xs"
      :class="
        selectedCategory === card.key
          ? 'border-amber-500 ring-2 ring-amber-500/20'
          : 'border-slate-200/80 dark:border-slate-800'
      "
    >
      <!-- Meta Card Label & Character Icon Container -->
      <div class="flex items-center justify-between">
        <span class="text-[10px] font-black tracking-wider text-slate-400 dark:text-slate-500 uppercase">{{
          card.title
        }}</span>
        <div
          class="p-2 rounded-xl bg-amber-500/10 text-amber-600 dark:text-amber-400 group-hover:bg-amber-500/20 transition-colors"
        >
          <component :is="card.iconComponent" class="w-5 h-5" />
        </div>
      </div>

      <!-- Real Live Numeric Tracking Count Block -->
      <div class="mt-4">
        <span class="text-2xl font-black text-slate-900 dark:text-white tracking-tight">
          {{ card.count }}
        </span>
        <span class="text-xs font-semibold text-slate-400 dark:text-slate-500 ml-1">Items</span>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue'
import type { MenuItem } from '@/types/menu'
import { Sun, Utensils, Soup, Wine, Cake } from 'lucide-vue-next'

interface BackendStatistics {
  total_items?: number
  available_items?: number
  unavailable_items?: number
  breakfast_items?: number
  lunch_items?: number
  dinner_items?: number
  drink_items?: number
  dessert_items?: number
}

const props = defineProps<{
  statistics: BackendStatistics
  selectedCategory: string | null
  menuItems?: MenuItem[]
}>()

defineEmits(['select'])

// Visual anchors configuration metadata mapping
const baseStaticMetadata = [
  { title: 'Breakfast', key: 'breakfast', iconComponent: Sun, field: 'breakfast_items' as const },
  { title: 'Lunch', key: 'lunch', iconComponent: Utensils, field: 'lunch_items' as const },
  { title: 'Dinner', key: 'dinner', iconComponent: Soup, field: 'dinner_items' as const },
  { title: 'Drinks', key: 'drinks', iconComponent: Wine, field: 'drink_items' as const },
  { title: 'Dessert', key: 'dessert', iconComponent: Cake, field: 'dessert_items' as const },
]

/**
 * Reactively bind live counts cleanly from backend indices
 */
const dynamicStatCards = computed(() => {
  return baseStaticMetadata.map((meta) => {
    let finalCount = 0

    // 1. Direct validation check against actual backend payload response numbers
    if (props.statistics && typeof props.statistics[meta.field] === 'number') {
      finalCount = props.statistics[meta.field] as number
    }
    // 2. Real-time array filter calculation safety fallback if statistics state is loading/empty
    else if (props.menuItems && props.menuItems.length > 0) {
      finalCount = props.menuItems.filter(
        (item) => item.category?.toLowerCase() === meta.key,
      ).length
    }

    return {
      ...meta,
      count: finalCount,
    }
  })
})
</script>

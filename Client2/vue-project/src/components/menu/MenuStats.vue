<template>
  <!-- Main Horizontally Responsive Grid Layout matching your visual mockup design -->
  <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-5 gap-4">
    <div
      v-for="card in dynamicStatCards"
      :key="card.key"
      @click="$emit('select', selectedCategory === card.key ? null : card.key)"
      class="bg-white rounded-xl p-4 border transition duration-200 hover:shadow-md cursor-pointer flex flex-col justify-between select-none relative group"
      :class="
        selectedCategory === card.key
          ? 'border-teal-600 ring-2 ring-teal-600/20'
          : 'border-slate-200/80'
      "
    >
      <!-- Meta Card Label & Character Icon Container -->
      <div class="flex items-center justify-between">
        <span class="text-[10px] font-black tracking-wider text-slate-400 uppercase">{{
          card.title
        }}</span>
        <span
          class="text-lg p-1.5 rounded-lg bg-slate-50 group-hover:bg-slate-100 transition-colors duration-150"
        >
          {{ card.icon }}
        </span>
      </div>

      <!-- Real Live Numeric Tracking Count Block -->
      <div class="mt-4">
        <span class="text-2xl font-black text-slate-900 tracking-tight">
          {{ card.count }}
        </span>
        <span class="text-xs font-semibold text-slate-400 ml-1">Items</span>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed } from 'vue'
import type { MenuItem } from '@/types/menu'

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
const baseStaticMetadata = ref([
  { title: 'Breakfast', key: 'breakfast', icon: '☀️', field: 'breakfast_items' as const },
  { title: 'Lunch', key: 'lunch', icon: '🍔', field: 'lunch_items' as const },
  { title: 'Dinner', key: 'dinner', icon: '🍲', field: 'dinner_items' as const },
  { title: 'Drinks', key: 'drinks', icon: '🍹', field: 'drink_items' as const },
  { title: 'Dessert', key: 'dessert', icon: '🍦', field: 'dessert_items' as const },
])

/**
 * Reactively bind live counts cleanly from backend indices
 */
const dynamicStatCards = computed(() => {
  return baseStaticMetadata.value.map((meta) => {
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

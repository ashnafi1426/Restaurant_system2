<script setup lang="ts">
// MenuCategoryTabs - Category Filtering Component
// NO MOCK DATA: Categories derived from actual menu items in store
import { useMenuStore } from '@/stores/menuStore'
import { computed } from 'vue'

defineProps<{
  selected: string | null
}>()

const emit = defineEmits<{
  (e: 'select', category: string | null): void
}>()

const store = useMenuStore()

// UI Metadata (icons and labels using pure characters/emojis)
const categoryIcons: Record<string, string> = {
  breakfast: '☀️',
  lunch: '🍔',
  dessert: '🍦',
  drinks: '🍹',
  dinner: '🍲',
}

const categoryLabels: Record<string, string> = {
  breakfast: 'Breakfast',
  lunch: 'Lunch',
  dinner: 'Dinner',
  drinks: 'Drinks',
  dessert: 'Dessert',
}

// BACKEND-DRIVEN: Categories generated from actual menu items
const categories = computed(() => {
  const uniqueCategories = new Set(
    store.menuItems.map((item) => item.category?.toLowerCase()).filter(Boolean),
  )
  return Array.from(uniqueCategories)
    .sort()
    .map((cat) => ({
      value: cat,
      label: categoryLabels[cat] || cat.charAt(0).toUpperCase() + cat.slice(1),
      icon: categoryIcons[cat] || '🍽️',
    }))
})
</script>

<template>
  <div>
    <!-- Header Row Matching Layout -->
    <div class="flex items-center justify-between mb-5">
      <h3 class="text-xs font-black text-slate-400 uppercase tracking-wider">Filter by Category</h3>
      <button
        @click="emit('select', null)"
        class="text-indigo-600 hover:text-indigo-700 font-bold text-xs transition flex items-center gap-1"
      >
        View All
        <span class="text-sm">→</span>
      </button>
    </div>

    <!-- Scrollable/Wrap Flex Pill Row Container -->
    <div class="flex flex-wrap items-center gap-2.5">
      <!-- All Items Tab Pill -->
      <button
        @click="emit('select', null)"
        :class="[
          'px-5 py-2.5 rounded-xl font-bold text-xs tracking-wide transition flex items-center gap-2 select-none',
          selected === null
            ? 'bg-slate-900 text-white shadow-sm'
            : 'bg-white border border-slate-200 text-slate-600 hover:bg-slate-50',
        ]"
      >
        <span>📁</span>
        All Items
      </button>

      <!-- Dynamic Category Tab Pills -->
      <button
        v-for="category in categories"
        :key="category.value"
        @click="emit('select', category.value)"
        :class="[
          'px-5 py-2.5 rounded-xl font-bold text-xs tracking-wide transition flex items-center gap-2 select-none',
          selected === category.value
            ? 'bg-teal-800 text-white shadow-sm'
            : 'bg-white border border-slate-200 text-slate-600 hover:bg-slate-50',
        ]"
      >
        <span class="text-sm leading-none">{{ category.icon }}</span>
        {{ category.label }}
      </button>

      <!-- Action Create Category Pill Button -->
      <button
        class="px-5 py-2.5 rounded-xl font-bold text-xs tracking-wide bg-slate-50 border border-slate-200 border-dashed text-slate-400 hover:border-slate-400 hover:text-slate-600 transition flex items-center gap-2"
      >
        <span>＋</span>
        New Cat
      </button>
    </div>
  </div>
</template>

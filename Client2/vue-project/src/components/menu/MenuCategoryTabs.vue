<script setup lang="ts">
import { useMenuStore } from '@/stores/menuStore'
import { computed } from 'vue'
import {
  Folder,
  Sun,
  Utensils,
  Soup,
  Wine,
  Cake,
  Leaf,
  Layers,
  Sandwich,
  Plus,
  UtensilsCrossed,
  ArrowRight,
} from 'lucide-vue-next'

defineProps<{
  selected: string | null
}>()

const emit = defineEmits<{
  (e: 'select', category: string | null): void
  (e: 'manage-categories'): void
}>()

const store = useMenuStore()

const categoryLabels: Record<string, string> = {
  breakfast: 'Breakfast',
  lunch: 'Lunch',
  dinner: 'Dinner',
  drinks: 'Drinks',
  dessert: 'Dessert',
  appetizers: 'Appetizers',
  beverages: 'Beverages',
  pasta: 'Pasta',
  sandwiches: 'Sandwiches',
  soups: 'Soups',
}

const iconMap: Record<string, any> = {
  breakfast: Sun,
  lunch: Utensils,
  dinner: Soup,
  drinks: Wine,
  beverages: Wine,
  dessert: Cake,
  desserts: Cake,
  appetizers: Leaf,
  pasta: Layers,
  sandwiches: Sandwich,
  soups: Soup,
  newfood: UtensilsCrossed,
}

function getCategoryIcon(cat: string) {
  return iconMap[cat?.toLowerCase()] || Utensils
}

const categories = computed(() => {
  const uniqueCategories = new Set(
    store.menuItems
      .map((item) => {
        const c = item.category
        if (!c) return ''
        return (typeof c === 'object' ? c.name || c.slug : String(c)).toLowerCase()
      })
      .filter(Boolean),
  )
  return Array.from(uniqueCategories)
    .sort()
    .map((cat) => ({
      value: cat,
      label: categoryLabels[cat] || cat.charAt(0).toUpperCase() + cat.slice(1),
      iconComponent: getCategoryIcon(cat),
    }))
})
</script>

<template>
  <div>
    <div class="flex items-center justify-between mb-3 sm:mb-4 md:mb-5 px-1 sm:px-0">
      <h3 class="text-xs font-black text-slate-400 dark:text-slate-500 uppercase tracking-wider">
        Filter by Category
      </h3>
      <button
        @click="emit('select', null)"
        class="text-indigo-600 dark:text-indigo-400 hover:text-indigo-700 font-bold text-xs transition flex items-center gap-1 cursor-pointer"
      >
        <span>View All</span>
        <ArrowRight class="w-3.5 h-3.5" />
      </button>
    </div>

    <div class="flex flex-wrap items-center gap-1.5 sm:gap-2 md:gap-2.5">
      <button
        @click="emit('select', null)"
        :class="[
          'px-3.5 sm:px-5 py-2 sm:py-2.5 min-h-10 rounded-xl font-extrabold text-xs tracking-wide transition flex items-center gap-2 select-none cursor-pointer shadow-xs',
          selected === null
            ? 'bg-amber-600 text-white shadow-amber-600/20'
            : 'bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 text-slate-700 dark:text-slate-300 hover:border-amber-500',
        ]"
      >
        <Folder class="w-4 h-4" />
        <span class="hidden sm:inline">All Items</span><span class="sm:hidden">All</span>
      </button>

      <button
        v-for="category in categories"
        :key="category.value"
        @click="emit('select', category.value)"
        :class="[
          'px-3.5 sm:px-5 py-2 sm:py-2.5 min-h-10 rounded-xl font-extrabold text-xs tracking-wide transition flex items-center gap-2 select-none whitespace-nowrap cursor-pointer shadow-xs',
          selected === category.value
            ? 'bg-amber-600 text-white shadow-amber-600/20'
            : 'bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 text-slate-700 dark:text-slate-300 hover:border-amber-500',
        ]"
      >
        <component :is="category.iconComponent" class="w-4 h-4" />
        <span class="hidden sm:inline">{{ category.label }}</span>
        <span class="sm:hidden capitalize">{{ category.label }}</span>
      </button>

      <button
        @click="emit('manage-categories')"
        class="px-3.5 sm:px-5 py-2 sm:py-2.5 min-h-10 rounded-xl font-extrabold text-xs tracking-wide bg-slate-50 dark:bg-slate-900/50 border border-slate-200 dark:border-slate-800 border-dashed text-slate-500 dark:text-slate-400 hover:border-amber-500 hover:text-amber-600 transition flex items-center gap-2 whitespace-nowrap cursor-pointer"
      >
        <Plus class="w-4 h-4" />
        <span class="hidden sm:inline">New Cat</span><span class="sm:hidden">New</span>
      </button>
    </div>
  </div>
</template>

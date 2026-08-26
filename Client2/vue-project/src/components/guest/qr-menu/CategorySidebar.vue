<template>
  <aside class="category-sidebar bg-white dark:bg-slate-900 flex flex-col h-full font-sans transition-colors p-2 space-y-3">
    <!-- Categories List (100% Dynamic Real Data From Backend) -->
    <nav class="flex-1 space-y-1.5 overflow-y-auto pr-1 max-h-full">
      <button
        v-for="category in displayCategories"
        :key="category.id || category.slug || category.name"
        @click="selectCategory(category)"
        :class="[
          'w-full flex items-center justify-between px-3.5 py-2.5 rounded-xl text-xs font-bold transition-all duration-200 text-left cursor-pointer',
          (selectedCategory === category.id || (category.name === 'All Categories' && selectedCategory === null))
            ? 'bg-[#c29353] text-white shadow-sm font-black'
            : 'bg-transparent text-slate-700 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800'
        ]"
      >
        <!-- Left: Icon & Category Name -->
        <div class="flex items-center gap-3 min-w-0">
          <div class="flex items-center justify-center">
            <component :is="getIconComponent(category.icon || category.id)" class="w-4 h-4" />
          </div>
          <span class="truncate">{{ category.name }}</span>
        </div>

        <!-- Right: Count Badge -->
        <span
          v-if="category.count !== undefined"
          :class="[
            'px-2 py-0.5 rounded-full text-[10px] font-black tracking-wider transition-colors',
            selectedCategory === category.id
              ? 'bg-white/20 text-white'
              : 'bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-400'
          ]"
        >
          {{ category.count }}
        </span>
      </button>
    </nav>
  </aside>
</template>

<script setup lang="ts">
import { ref, watch, computed } from 'vue'
import {
  Clock,
  Utensils,
  Leaf,
  Soup,
  Salad,
  UtensilsCrossed,
  Layers,
  Pizza,
  Sandwich,
  Cake,
  Wine,
  Grid,
  Coffee,
} from 'lucide-vue-next'

interface Category {
  id: string | null
  name: string
  icon?: string
  count?: number
}

interface Props {
  categories?: Category[]
  selectedCategoryId?: string | null
}

const props = withDefaults(defineProps<Props>(), {
  categories: () => [],
  selectedCategoryId: null,
})

const selectedCategory = ref<string | null>(props.selectedCategoryId)

const displayCategories = computed(() => {
  return props.categories || []
})

const getIconComponent = (iconKey?: string | null) => {
  if (!iconKey) return Grid
  const key = String(iconKey).toLowerCase()
  const iconMap: { [key: string]: any } = {
    grid: Grid,
    all: Grid,
    menu: Grid,
    clock: Clock,
    breakfast: Clock,
    soup: Soup,
    soups: Soup,
    leaf: Leaf,
    appetizers: Leaf,
    utensils: Utensils,
    'main-courses': Utensils,
    'main-course': UtensilsCrossed,
    sandwich: Sandwich,
    sandwiches: Sandwich,
    burgers: Sandwich,
    layers: Layers,
    pasta: Layers,
    pizza: Pizza,
    cake: Cake,
    desserts: Cake,
    wine: Wine,
    beverages: Wine,
    drinks: Coffee,
  }
  return iconMap[key] || Grid
}

const emit = defineEmits<{
  'category-selected': [categoryId: string | null]
}>()

watch(
  () => props.selectedCategoryId,
  (newVal) => {
    selectedCategory.value = newVal
  },
)

const selectCategory = (cat: any) => {
  let categoryId: string | null = null
  if (typeof cat === 'object' && cat !== null) {
    if (cat.name === 'All Categories') {
      categoryId = null
    } else {
      categoryId = cat.id ?? cat.slug ?? cat.name
    }
  } else {
    categoryId = cat
  }

  selectedCategory.value = categoryId
  emit('category-selected', categoryId)
}
</script>

<style scoped>
nav::-webkit-scrollbar {
  width: 4px;
}
nav::-webkit-scrollbar-track {
  background: transparent;
}
nav::-webkit-scrollbar-thumb {
  background: #cbd5e1;
  border-radius: 9999px;
}
nav::-webkit-scrollbar-thumb:hover {
  background: #c29353;
}
</style>

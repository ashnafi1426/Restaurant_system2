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
          isCategoryActive(category)
            ? 'bg-[#c29353] text-white shadow-sm font-black'
            : 'bg-transparent text-slate-700 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800'
        ]"
      >
        <!-- Left: Icon & Category Name -->
        <div class="flex items-center gap-2.5 min-w-0 flex-1">
          <div class="flex items-center justify-center flex-shrink-0 text-slate-500 dark:text-slate-400 group-hover:text-slate-700" :class="{ '!text-white': isCategoryActive(category) }">
            <component :is="getCategoryIcon(category)" class="w-4 h-4" />
          </div>
          <span class="truncate font-semibold">{{ languageStore.t(category.name, category.name) }}</span>
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
import { useLanguageStore } from '@/stores/language'
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

const languageStore = useLanguageStore()
const selectedCategory = ref<string | null>(props.selectedCategoryId)

const displayCategories = computed(() => {
  return props.categories || []
})

const getIconComponent = (iconKey?: string | null) => {
  if (!iconKey) return Grid
  const key = String(iconKey).toLowerCase().trim().replace(/_/g, '-')
  const iconMap: { [key: string]: any } = {
    grid: Grid,
    all: Grid,
    menu: Grid,
    clock: Clock,
    breakfast: Clock,
    soup: Soup,
    soups: Soup,
    leaf: Leaf,
    starter: Leaf,
    starters: Leaf,
    appetizers: Leaf,
    appetizer: Leaf,
    utensils: Utensils,
    'utensils-crossed': UtensilsCrossed,
    'main-courses': UtensilsCrossed,
    'main-course': UtensilsCrossed,
    main: UtensilsCrossed,
    sandwich: Sandwich,
    sandwiches: Sandwich,
    burger: Sandwich,
    burgers: Sandwich,
    layers: Layers,
    pasta: Layers,
    pizza: Pizza,
    cake: Cake,
    dessert: Cake,
    desserts: Cake,
    wine: Wine,
    beverage: Wine,
    beverages: Wine,
    drinks: Wine,
    drink: Wine,
    coffee: Coffee,
    tea: Coffee,
    salad: Salad,
    salads: Salad,
  }
  return iconMap[key] || Grid
}

const getCategoryIcon = (category: any) => {
  if (!category) return Grid

  const name = String(category.name || '').toLowerCase().trim()
  const slug = String(category.slug || '').toLowerCase().trim()
  const icon = String(category.icon || '').toLowerCase().trim()
  const id = String(category.id || '').toLowerCase().trim()

  // "All Categories" is always the Grid icon
  if (category.id === null || name === 'all categories' || slug === 'all' || name === 'all') {
    return Grid
  }

  // If icon is directly provided and is not generic grid/menu
  if (icon && icon !== 'grid' && icon !== 'menu') {
    const matched = getIconComponent(icon)
    if (matched !== Grid) return matched
  }

  // Check name, slug, and id keywords
  const text = `${slug} ${name} ${id} ${icon}`

  if (text.includes('break') || text.includes('egg') || text.includes('morn') || text.includes('pancake') || text.includes('toast') || text.includes('clock')) {
    return Clock
  }
  if (text.includes('soup') || text.includes('broth') || text.includes('stew') || text.includes('ramen') || text.includes('chowder')) {
    return Soup
  }
  if (text.includes('appetiz') || text.includes('starter') || text.includes('snack') || text.includes('finger') || text.includes('leaf') || text.includes('bruschetta')) {
    return Leaf
  }
  if (text.includes('salad') || text.includes('green') || text.includes('veg')) {
    return Salad
  }
  if (text.includes('sandw') || text.includes('burger') || text.includes('wrap') || text.includes('sub') || text.includes('panini')) {
    return Sandwich
  }
  if (text.includes('pasta') || text.includes('noodl') || text.includes('spaghetti') || text.includes('layer') || text.includes('lasagna')) {
    return Layers
  }
  if (text.includes('pizza') || text.includes('pie') || text.includes('calzone')) {
    return Pizza
  }
  if (text.includes('dessert') || text.includes('cake') || text.includes('sweet') || text.includes('pastry') || text.includes('ice cream') || text.includes('chocolate') || text.includes('pudding')) {
    return Cake
  }
  if (text.includes('bev') || text.includes('drink') || text.includes('wine') || text.includes('beer') || text.includes('cocktail') || text.includes('bar') || text.includes('juice') || text.includes('smoothie')) {
    return Wine
  }
  if (text.includes('coffee') || text.includes('tea') || text.includes('latte') || text.includes('cappuccino') || text.includes('cafe')) {
    return Coffee
  }
  if (text.includes('main') || text.includes('dinner') || text.includes('lunch') || text.includes('entree') || text.includes('steak') || text.includes('grill') || text.includes('meat') || text.includes('seafood') || text.includes('fish') || text.includes('chicken')) {
    return UtensilsCrossed
  }

  return Utensils
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

function isCategoryActive(cat: any): boolean {
  if (!cat) return false
  const catName = String(cat.name || '').toLowerCase().trim()
  const catSlug = String(cat.slug || '').toLowerCase().trim()
  const catId = cat.id !== undefined && cat.id !== null ? String(cat.id).toLowerCase().trim() : null

  const selected = selectedCategory.value

  if (selected === null || selected === '' || selected === 'all') {
    return cat.id === null || catName === 'all categories' || catSlug === 'all'
  }

  const selStr = String(selected).toLowerCase().trim()
  return (
    (catId !== null && catId === selStr) ||
    (catSlug !== '' && catSlug === selStr) ||
    (catName !== '' && catName === selStr) ||
    (catSlug !== '' && selStr.includes(catSlug)) ||
    (catName !== '' && selStr.includes(catName))
  )
}

const selectCategory = (cat: any) => {
  let categoryId: string | null = null
  if (typeof cat === 'object' && cat !== null) {
    if (cat.name === 'All Categories' || cat.id === null || cat.slug === 'all') {
      categoryId = null
    } else {
      categoryId = cat.slug || cat.id || cat.name
    }
  } else {
    categoryId = (cat === 'All Categories' || cat === 'all') ? null : cat
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

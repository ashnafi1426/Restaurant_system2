<script setup lang="ts">
import { computed, ref, onMounted } from 'vue'
import categoryService from '@/services/categoryService'

interface Category {
  id: string
  name: string
  slug: string
  is_active: boolean
}

interface Props {
  active: string
}

const props = defineProps<Props>()

const emit = defineEmits<{
  (e: 'change', category: string): void
}>()

const categories = ref<Category[]>([])
const loading = ref(false)
const error = ref<string | null>(null)

const categoryLabels: Record<string, string> = {
  all: 'All',
  breakfast: '🥐 Breakfast',
  lunch: '🍲 Lunch',
  dinner: 'Dinner',
  drinks: '🥤 Drinks',
  dessert: '🍰 Dessert',
  appetizer: '🥗 Appetizer',
  appetizers: '🥗 Appetizers',
  snacks: '🍿 Snacks',
  beverage: '☕ Beverage',
  beverages: '☕ Beverages',
  coffee: '☕ Coffee',
  tea: '🫖 Tea',
  juice: '🧃 Juice',
  smoothie: '🥤 Smoothie',
  salad: '🥗 Salad',
  soup: '🍲 Soup',
  pasta: '🍝 Pasta',
  pizza: '🍕 Pizza',
  burger: '🍔 Burger',
  sandwich: '🥪 Sandwich',
  steak: '🥩 Steak',
  chicken: '🍗 Chicken',
  fish: '🐟 Fish',
  seafood: '🦞 Seafood',
  vegetarian: '🥬 Vegetarian',
  vegan: '🌱 Vegan',
  desserts: '🍰 Desserts',
  cake: '🎂 Cake',
  ice: '🍦 Ice Cream',
  chocolate: '🍫 Chocolate',
}

const getLabel = (category: Category | string) => {
  if (!category) return '📌 All'

  const categoryName = typeof category === 'string' ? category : category.name
  const categorySlug = typeof category === 'string' ? category : category.slug

  const normalized = categorySlug.toLowerCase().trim()

  if (categoryLabels[normalized]) {
    return categoryLabels[normalized]
  }

  for (const [key, label] of Object.entries(categoryLabels)) {
    if (normalized.includes(key) || key.includes(normalized)) {
      const emoji = label.split(' ')[0]
      return `${emoji} ${categoryName}`
    }
  }

  return `📌 ${categoryName}`
}

const loadCategories = async () => {
  loading.value = true
  error.value = null
  try {
    const response = await categoryService.getCategories({ is_active: true })

    if (response.data?.data && Array.isArray(response.data.data)) {
      categories.value = response.data.data
    } else {
      error.value = 'No categories found'
    }
  } catch (err: any) {
    console.error('[CategorySlider] Failed to load categories:', err)
    error.value = err.message || 'Failed to load categories'
  } finally {
    loading.value = false
  }
}

const handleCategoryChange = (category: string) => {
  emit('change', category)
}

const isActive = computed(() => (categoryOrSlug: Category | string) => {
  const slug = typeof categoryOrSlug === 'string' ? categoryOrSlug : categoryOrSlug.slug
  return props.active?.toLowerCase() === slug.toLowerCase()
})

onMounted(() => {
  loadCategories()
})
</script>

<template>
  <section class="py-3 sm:py-4 bg-white border-b border-gray-200">
    <div class="overflow-x-auto pb-2 px-3 sm:px-4 md:px-8">
      <div v-if="error" class="flex items-center gap-2 text-red-600 text-xs sm:text-sm px-4 py-2">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path
            stroke-linecap="round"
            stroke-linejoin="round"
            stroke-width="2"
            d="M12 8v4m0 4v.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"
          />
        </svg>
        <span>{{ error }}</span>
      </div>

      <div v-else class="flex gap-2 md:gap-3 min-w-max">
        <button
          @click="handleCategoryChange('all')"
          :disabled="loading"
          :class="[
            'px-3 sm:px-4 md:px-6 py-1.5 sm:py-2 md:py-3 rounded-full font-semibold whitespace-nowrap transition-all duration-300 text-xs sm:text-sm md:text-base',
            'disabled:opacity-50 disabled:cursor-not-allowed',
            isActive('all')
              ? 'bg-amber-600 dark:bg-amber-500 text-white dark:text-slate-950 shadow-lg scale-105 font-bold'
              : 'bg-white dark:bg-slate-800 text-slate-700 dark:text-slate-200 border border-slate-300 dark:border-slate-700 hover:border-amber-500 dark:hover:border-amber-400 hover:shadow-md',
          ]"
          aria-label="Show all menu items"
        >
          {{ getLabel('all') }}
        </button>

        <button
          v-for="category in categories"
          :key="category.id"
          @click="handleCategoryChange(category.slug)"
          :disabled="loading"
          :class="[
            'px-3 sm:px-4 md:px-6 py-1.5 sm:py-2 md:py-3 rounded-full font-semibold whitespace-nowrap transition-all duration-300 text-xs sm:text-sm md:text-base',
            'disabled:opacity-50 disabled:cursor-not-allowed',
            isActive(category)
              ? 'bg-amber-600 dark:bg-amber-500 text-white dark:text-slate-950 shadow-lg scale-105 font-bold'
              : 'bg-white dark:bg-slate-800 text-slate-700 dark:text-slate-200 border border-slate-300 dark:border-slate-700 hover:border-amber-500 dark:hover:border-amber-400 hover:shadow-md',
          ]"
          :aria-label="`Filter by ${category.name}`"
          :aria-pressed="isActive(category)"
        >
          {{ getLabel(category) }}
        </button>

        <div
          v-if="loading"
          class="px-3 sm:px-4 py-1.5 sm:py-2 md:py-3 flex items-center gap-2 text-gray-500"
        >
          <div class="relative w-4 h-4">
            <svg class="absolute inset-0 w-full h-full" viewBox="0 0 100 100">
              <circle cx="50" cy="50" r="40" fill="none" stroke="#0EA5E9" stroke-width="5" opacity="0.3" />
            </svg>
            
            <div class="absolute inset-0 animate-spin" style="animation: spin 1.5s linear infinite;">
              <svg viewBox="0 0 100 100" class="w-full h-full">
                <circle cx="50" cy="50" r="40" fill="none" stroke="#FBBF24" stroke-width="6" stroke-linecap="round" stroke-dasharray="60 240" />
              </svg>
            </div>
          </div>
          <span class="text-xs">Loading...</span>
        </div>
      </div>
    </div>
  </section>
</template>

<style scoped>
@keyframes spin {
  from {
    transform: rotate(0deg);
  }
  to {
    transform: rotate(360deg);
  }
}
</style>

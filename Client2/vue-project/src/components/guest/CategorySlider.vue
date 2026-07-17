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

// State
const categories = ref<Category[]>([])
const loading = ref(false)
const error = ref<string | null>(null)

// Enhanced category labels with emojis - fallback mapping
const categoryLabels: Record<string, string> = {
  all: '🍽️ All',
  breakfast: '🥐 Breakfast',
  lunch: '🍲 Lunch',
  dinner: '🍽️ Dinner',
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

/**
 * Get label for a category with intelligent fallback
 */
const getLabel = (category: Category | string) => {
  if (!category) return '📌 All'
  
  const categoryName = typeof category === 'string' ? category : category.name
  const categorySlug = typeof category === 'string' ? category : category.slug
  
  const normalized = categorySlug.toLowerCase().trim()
  
  // Direct match
  if (categoryLabels[normalized]) {
    return categoryLabels[normalized]
  }
  
  // Check for partial matches (e.g., "beverages" -> "🥤 Beverages")
  for (const [key, label] of Object.entries(categoryLabels)) {
    if (normalized.includes(key) || key.includes(normalized)) {
      const emoji = label.split(' ')[0]
      return `${emoji} ${categoryName}`
    }
  }
  
  // Default format
  return `📌 ${categoryName}`
}
const loadCategories = async () => {
  loading.value = true
  error.value = null
  try {
    const response = await categoryService.getCategories({ is_active: true })
    
    if (response.data?.data && Array.isArray(response.data.data)) {
      categories.value = response.data.data
      console.log(`[CategorySlider] Loaded ${categories.value.length} categories from backend`)
    } else {
      error.value = 'No categories found'
      console.warn('[CategorySlider] No categories in response')
    }
  } catch (err: any) {
    error.value = err.message || 'Failed to load categories'
    console.error('[CategorySlider] Error loading categories:', err)
  } finally {
    loading.value = false
  }
}

/**
 * Handle category change with logging
 */
const handleCategoryChange = (category: string) => {
  console.log(`[CategorySlider] Category changed to: "${category}"`)
  emit('change', category)
}

/**
 * Check if category is active
 */
const isActive = computed(() => (categoryOrSlug: Category | string) => {
  const slug = typeof categoryOrSlug === 'string' ? categoryOrSlug : categoryOrSlug.slug
  return props.active?.toLowerCase() === slug.toLowerCase()
})

/**
 * Load categories on mount
 */
onMounted(() => {
  loadCategories()
})
</script>

<template>
  <section class="py-3 sm:py-4 bg-white border-b border-gray-200">
    <div class="overflow-x-auto pb-2 px-3 sm:px-4 md:px-8">
      <div v-if="error" class="flex items-center gap-2 text-red-600 text-xs sm:text-sm px-4 py-2">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4v.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
        </svg>
        <span>{{ error }}</span>
      </div>

      <div v-else class="flex gap-2 md:gap-3 min-w-max">
        <!-- All Categories Button -->
        <button
          @click="handleCategoryChange('all')"
          :disabled="loading"
          :class="[
            'px-3 sm:px-4 md:px-6 py-1.5 sm:py-2 md:py-3 rounded-full font-semibold whitespace-nowrap transition-all duration-300 text-xs sm:text-sm md:text-base',
            'disabled:opacity-50 disabled:cursor-not-allowed',
            isActive('all')
              ? 'bg-amber-700 text-white shadow-lg scale-105'
              : 'bg-white text-gray-700 border-2 border-gray-300 hover:border-amber-600 hover:shadow-md'
          ]"
          aria-label="Show all menu items"
        >
          {{ getLabel('all') }}
        </button>

        <!-- Dynamic Category Buttons from Backend -->
        <button
          v-for="category in categories"
          :key="category.id"
          @click="handleCategoryChange(category.slug)"
          :disabled="loading"
          :class="[
            'px-3 sm:px-4 md:px-6 py-1.5 sm:py-2 md:py-3 rounded-full font-semibold whitespace-nowrap transition-all duration-300 text-xs sm:text-sm md:text-base',
            'disabled:opacity-50 disabled:cursor-not-allowed',
            isActive(category)
              ? 'bg-amber-700 text-white shadow-lg scale-105'
              : 'bg-white text-gray-700 border-2 border-gray-300 hover:border-amber-600 hover:shadow-md'
          ]"
          :aria-label="`Filter by ${category.name}`"
          :aria-pressed="isActive(category)"
        >
          {{ getLabel(category) }}
        </button>

        <!-- Loading Indicator -->
        <div v-if="loading" class="px-3 sm:px-4 py-1.5 sm:py-2 md:py-3 flex items-center gap-2 text-gray-500">
          <svg class="w-4 h-4 animate-spin" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm0 18c-4.41 0-8-3.59-8-8s3.59-8 8-8 8 3.59 8 8-3.59 8-8 8z" />
          </svg>
          <span class="text-xs">Loading...</span>
        </div>
      </div>
    </div>
  </section>
</template>
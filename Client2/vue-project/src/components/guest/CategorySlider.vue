<script setup lang="ts">
import { computed, ref } from 'vue'

interface Props {
  active: string
  categories?: string[]
  loading?: boolean
}

const props = withDefaults(defineProps<Props>(), {
  categories: () => ['all', 'breakfast', 'lunch', 'dinner', 'drinks', 'dessert'],
  loading: false,
})

const emit = defineEmits<{
  (e: 'change', category: string): void
}>()

// Enhanced category labels with emojis
const categoryLabels: Record<string, string> = {
  all: '🍽️ All',
  breakfast: '🥐 Breakfast',
  lunch: '🍲 Lunch',
  dinner: '🍽️ Dinner',
  drinks: '🥤 Drinks',
  dessert: '🍰 Dessert',
  appetizer: '🥗 Appetizer',
  snacks: '🍿 Snacks',
  beverage: '☕ Beverage',
  beverages: '☕ Beverages',
  'all day': '🌅 All Day',
  'fine dining': '🍷 Fine Dining',
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
const getLabel = (category: string) => {
  if (!category) return '📌 All'
  
  const normalized = category.toLowerCase().trim()
  
  // Direct match
  if (categoryLabels[normalized]) {
    return categoryLabels[normalized]
  }
  
  // Check for partial matches (e.g., "beverages" -> "🥤 Beverages")
  for (const [key, label] of Object.entries(categoryLabels)) {
    if (normalized.includes(key) || key.includes(normalized)) {
      // Extract emoji from label and use original category name
      const emoji = label.split(' ')[0]
      const formatted = category.charAt(0).toUpperCase() + category.slice(1)
      return `${emoji} ${formatted}`
    }
  }
  
  // Default format
  const formatted = category.charAt(0).toUpperCase() + category.slice(1)
  return `📌 ${formatted}`
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
const isActive = computed(() => (category: string) => {
  return props.active?.toLowerCase() === category.toLowerCase()
})
</script>

<template>
  <section class="py-4 bg-white border-b border-gray-200">
    <div class="overflow-x-auto pb-2 px-4 md:px-8">
      <div class="flex gap-2 md:gap-3 min-w-max">
        <!-- All Categories Button -->
        <button
          @click="handleCategoryChange('all')"
          :disabled="loading"
          :class="[
            'px-4 md:px-6 py-2 md:py-3 rounded-full font-semibold whitespace-nowrap transition-all duration-300 text-sm md:text-base',
            'disabled:opacity-50 disabled:cursor-not-allowed',
            isActive('all')
              ? 'bg-amber-700 text-white shadow-lg scale-105'
              : 'bg-white text-gray-700 border-2 border-gray-300 hover:border-amber-600 hover:shadow-md'
          ]"
          aria-label="Show all menu items"
        >
          {{ getLabel('all') }}
        </button>

        <!-- Dynamic Category Buttons -->
        <button
          v-for="category in categories"
          :key="category"
          @click="handleCategoryChange(category)"
          :disabled="loading"
          :class="[
            'px-4 md:px-6 py-2 md:py-3 rounded-full font-semibold whitespace-nowrap transition-all duration-300 text-sm md:text-base',
            'disabled:opacity-50 disabled:cursor-not-allowed',
            isActive(category)
              ? 'bg-amber-700 text-white shadow-lg scale-105'
              : 'bg-white text-gray-700 border-2 border-gray-300 hover:border-amber-600 hover:shadow-md'
          ]"
          :aria-label="`Filter by ${category}`"
          :aria-pressed="isActive(category)"
        >
          {{ getLabel(category) }}
        </button>

        <!-- Loading Indicator -->
        <div v-if="loading" class="px-4 py-2 md:py-3 flex items-center gap-2 text-gray-500">
          <svg class="w-4 h-4 animate-spin" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm0 18c-4.41 0-8-3.59-8-8s3.59-8 8-8 8 3.59 8 8-3.59 8-8 8z" />
          </svg>
          <span class="text-xs">Loading...</span>
        </div>
      </div>
    </div>
  </section>
</template>
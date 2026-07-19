<script setup lang="ts">
import { ref, computed } from 'vue'

interface MenuItem {
  id: string | number
  name: string
  description: string
  price: number
  image?: string | null
  image_url?: string
  category?: string
  is_available?: boolean
  dietary_tags?: string[]
  status?: string
}

const props = defineProps<{
  item: MenuItem
}>()

const emit = defineEmits<{
  addToCart: [item: MenuItem]
}>()

// Track image loading state
const imageError = ref(false)
const imageLoading = ref(false)

// Get the image URL from either image or image_url field
const imageUrl = computed(() => {
  return props.item.image || props.item.image_url || null
})

// Determine availability status
const isAvailable = computed(() => {
  return props.item.is_available !== false && props.item.status !== 'unavailable'
})

// Get emoji based on category
const getCategoryEmoji = (category: string): string => {
  const emojiMap: Record<string, string> = {
    'breakfast': '🥞',
    'lunch': '🥗',
    'dinner': '🍽️',
    'appetizer': '🥒',
    'main': '🍖',
    'dessert': '🍰',
    'drink': '🥤',
    'beverage': '🍹',
    'coffee': '☕',
    'tea': '🫖',
    'smoothie': '🥤',
    'burger': '🍔',
    'pizza': '🍕',
    'salad': '🥗',
    'sandwich': '🥪',
    'soup': '🍲',
    'pasta': '🍝',
    'seafood': '🦐',
    'vegetarian': '🥬',
    'vegan': '🌱',
  }
  
  const lowercaseCategory = (category || '').toLowerCase()
  return emojiMap[lowercaseCategory] || '🍽️'
}

const handleImageError = () => {
  imageError.value = true
  console.warn(`[MenuCard] Failed to load image for "${props.item.name}"`, {
    image_url: imageUrl.value,
    item_id: props.item.id,
  })
}

const handleImageLoad = () => {
  imageLoading.value = false
  imageError.value = false
  console.log(`[MenuCard] Image loaded successfully for "${props.item.name}"`)
}

const handleImageLoadStart = () => {
  imageLoading.value = true
}

const addToCart = () => {
  emit('addToCart', props.item)
}
</script>

<template>
  <div
    class="group bg-white rounded-2xl shadow-md overflow-hidden hover:shadow-xl transition-all duration-300 hover:-translate-y-1"
    :class="{ 'opacity-75': !isAvailable }"
  >
    <!-- Image Section -->
    <div class="relative h-56 sm:h-64 bg-gradient-to-br from-slate-100 to-slate-200 overflow-hidden">
      <img
        v-if="imageUrl && !imageError"
        :src="imageUrl"
        :alt="item.name"
        class="w-full h-full object-cover group-hover:scale-110 transition-transform duration-500"
        @error="handleImageError"
        @load="handleImageLoad"
        @loadstart="handleImageLoadStart"
        loading="lazy"
      />
      <div
        v-else
        class="w-full h-full flex flex-col items-center justify-center bg-gradient-to-br from-amber-50 to-orange-100"
      >
        <span class="text-6xl sm:text-7xl mb-2">{{ getCategoryEmoji(item.category || '') }}</span>
        <span class="text-xs sm:text-sm text-slate-500 text-center px-2 font-medium">
          {{ imageError ? 'Image unavailable' : 'No image' }}
        </span>
      </div>

      <!-- Loading Indicator -->
      <div
        v-if="imageLoading"
        class="absolute inset-0 bg-white/40 backdrop-blur-sm flex items-center justify-center"
      >
        <div class="animate-spin">
          <svg
            class="w-8 h-8 text-amber-600"
            fill="none"
            stroke="currentColor"
            viewBox="0 0 24 24"
          >
            <path
              stroke-linecap="round"
              stroke-linejoin="round"
              stroke-width="2"
              d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"
            />
          </svg>
        </div>
      </div>

      <!-- Category Badge -->
      <div
        class="absolute top-3 left-3 bg-white/90 backdrop-blur-sm px-3 py-1.5 rounded-full text-xs sm:text-sm font-semibold text-amber-700 shadow-md"
      >
        {{ getCategoryEmoji(item.category || '') }} {{ item.category }}
      </div>

      <!-- Availability Badge -->
      <div
        v-if="!isAvailable"
        class="absolute top-3 right-3 bg-red-500/90 backdrop-blur-sm px-3 py-1.5 rounded-full text-xs sm:text-sm font-bold text-white shadow-md"
      >
        Out of Stock
      </div>
      <div
        v-else
        class="absolute top-3 right-3 bg-green-500/90 backdrop-blur-sm px-3 py-1.5 rounded-full text-xs sm:text-sm font-bold text-white shadow-md"
      >
        Available
      </div>

      <!-- Dietary Tags -->
      <div
        v-if="item.dietary_tags && item.dietary_tags.length > 0"
        class="absolute bottom-3 left-3 flex flex-wrap gap-2"
      >
        <span
          v-for="tag in item.dietary_tags.slice(0, 2)"
          :key="tag"
          class="bg-yellow-400/90 backdrop-blur-sm text-slate-900 text-xs px-2 py-0.5 rounded-full font-medium shadow-md"
        >
          {{ tag }}
        </span>
      </div>
    </div>

    <!-- Content Section -->
    <div class="p-4 sm:p-5 flex flex-col h-full">
      <!-- Name -->
      <h3 class="font-bold text-lg sm:text-xl text-slate-900 leading-tight mb-1">{{ item.name }}</h3>

      <!-- Category Label (Mobile Fallback) -->
      <p class="text-xs sm:text-sm text-amber-600 font-semibold uppercase tracking-wide mb-2 sm:hidden">
        {{ item.category || 'Menu Item' }}
      </p>

      <!-- Description -->
      <p class="text-sm sm:text-base text-slate-600 mb-4 line-clamp-2 flex-grow">
        {{ item.description }}
      </p>

      <!-- Price and Action Row -->
      <div class="flex items-center justify-between gap-3 pt-2 border-t border-slate-200">
        <!-- Price -->
        <div class="flex flex-col">
          <span class="text-xs text-slate-500 font-medium">Price</span>
          <span class="text-xl sm:text-2xl font-bold text-amber-600">
            ${{ item.price.toFixed(2) }}
          </span>
        </div>

        <!-- Add to Cart Button -->
        <button
          :disabled="!isAvailable"
          @click="addToCart"
          class="flex-1 px-4 py-2.5 sm:py-3 bg-gradient-to-r from-amber-500 to-amber-600 hover:from-amber-600 hover:to-amber-700 disabled:from-slate-300 disabled:to-slate-400 text-white font-semibold rounded-xl transition-all duration-300 flex items-center justify-center gap-2 shadow-md hover:shadow-lg active:scale-95 disabled:cursor-not-allowed disabled:active:scale-100"
        >
          <svg
            v-if="isAvailable"
            class="w-5 h-5 flex-shrink-0"
            fill="none"
            stroke="currentColor"
            viewBox="0 0 24 24"
          >
            <path
              stroke-linecap="round"
              stroke-linejoin="round"
              stroke-width="2.5"
              d="M12 4v16m8-8H4"
            />
          </svg>
          <svg v-else class="w-5 h-5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
            <path
              fill-rule="evenodd"
              d="M13.586 3.586a2 2 0 112.828 2.828l-.793.793-2.828-2.828.793-.793zM11.379 5.793L3 14.172V17h2.828l8.38-8.379-2.83-2.828z"
              clip-rule="evenodd"
            />
          </svg>
          <span class="text-sm sm:text-base">
            {{ isAvailable ? 'Add to Cart' : 'Unavailable' }}
          </span>
        </button>
      </div>
    </div>
  </div>
</template>

<style scoped>
.line-clamp-2 {
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
}
</style>

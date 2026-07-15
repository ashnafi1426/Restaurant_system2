<script setup lang="ts">
import { ref } from 'vue'

interface MenuItem {
  id: string | number
  name: string
  description: string
  price: number
  image: string | null
}

const props = defineProps<{
  item: MenuItem
}>()

defineEmits<{
  addToCart: [item: MenuItem]
}>()

// Track image loading state
const imageError = ref(false)
const imageLoading = ref(false)

const handleImageError = () => {
  imageError.value = true
  console.warn(`[MenuCard] Failed to load image for "${props.item.name}"`, {
    image_url: props.item.image,
    item_id: props.item.id
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
</script>

<template>
  <div class="bg-white rounded-lg shadow-sm overflow-hidden hover:shadow-md transition group">
    <!-- Image Section -->
    <div class="h-48 bg-gray-200 overflow-hidden relative">
      <img
        v-if="item.image && !imageError"
        :src="item.image"
        :alt="item.name"
        class="w-full h-full object-cover group-hover:scale-105 transition"
        @error="handleImageError"
        @load="handleImageLoad"
        @loadstart="handleImageLoadStart"
      />
      <div v-else class="w-full h-full flex flex-col items-center justify-center bg-gradient-to-br from-gray-100 to-gray-200">
        <span class="text-4xl mb-2">🍽️</span>
        <span class="text-xs text-gray-500 text-center px-2">
          {{ imageError ? 'Image not found' : 'No image available' }}
        </span>
      </div>
      
      <!-- Loading Indicator -->
      <div v-if="imageLoading" class="absolute inset-0 bg-gray-200 opacity-50 flex items-center justify-center">
        <div class="animate-spin text-2xl">⏳</div>
      </div>
    </div>

    <!-- Content Section -->
    <div class="p-4">
      <h3 class="font-semibold text-lg text-gray-900">{{ item.name }}</h3>
      <p class="text-sm text-gray-600 mt-1 line-clamp-2">{{ item.description }}</p>

      <div class="flex items-center justify-between mt-4">
        <span class="text-2xl font-bold text-blue-600">${{ item.price.toFixed(2) }}</span>
        <button
          @click="$emit('addToCart', item)"
          class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition flex items-center gap-2 active:scale-95"
        >
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
          </svg>
          Add
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

<script setup lang="ts">
import { computed } from 'vue'

interface MenuItem {
  id: number
  name: string
  description: string
  price: number
  image?: string | null
  category: string
  badge?: string
}

interface Props {
  items?: MenuItem[]
}

withDefaults(defineProps<Props>(), {
  items: () => [],
})

const emit = defineEmits<{
  (e: 'add-to-cart', item: MenuItem): void
}>()

const featuredItems = computed(() => {
  return (props.items || []).filter(item => item.badge === '20% OFF' || item.badge === 'Chef\'s Special').slice(0, 3)
})

const getImageUrl = (imagePath?: string | null) => {
  if (!imagePath) return '/placeholder-food.jpg'
  if (imagePath.startsWith('http')) return imagePath
  return `http://127.0.0.1:8000/storage/${imagePath}`
}
</script>

<template>
  <div v-if="featuredItems.length > 0" class="mb-12">
    <!-- Section Header -->
    <div class="flex items-center gap-3 mb-6">
      <h2 class="text-2xl font-bold text-gray-900">⭐ Chef's Recommendations</h2>
      <span class="text-sm font-semibold text-teal-600 bg-teal-50 px-3 py-1 rounded-full">
        Special Offers
      </span>
    </div>

    <!-- Featured Grid -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
      <div
        v-for="item in featuredItems"
        :key="item.id"
        class="relative group bg-white rounded-2xl shadow-lg overflow-hidden hover:shadow-2xl transition-all duration-300"
      >
        <!-- Image -->
        <div class="relative h-48 overflow-hidden bg-gray-100">
          <img
            :src="getImageUrl(item.image)"
            :alt="item.name"
            class="w-full h-full object-cover group-hover:scale-110 transition-transform duration-300"
          />

          <!-- Badge -->
          <span class="absolute top-3 right-3 bg-red-500 text-white font-bold px-3 py-1 rounded-full text-sm">
            {{ item.badge }}
          </span>

          <!-- Overlay -->
          <div class="absolute inset-0 bg-black bg-opacity-0 group-hover:bg-opacity-40 transition-all duration-300 flex items-center justify-center opacity-0 group-hover:opacity-100">
            <button
              @click="emit('add-to-cart', item)"
              class="bg-teal-600 text-white px-6 py-2 rounded-full font-semibold hover:bg-teal-700"
            >
              + Add
            </button>
          </div>
        </div>

        <!-- Content -->
        <div class="p-4">
          <h3 class="font-bold text-lg text-gray-900 mb-1">{{ item.name }}</h3>
          <p class="text-sm text-gray-600 mb-3 line-clamp-2">{{ item.description }}</p>

          <!-- Footer -->
          <div class="flex justify-between items-center">
            <span class="text-2xl font-bold text-teal-700">{{ item.price }} ETB</span>
            <button
              @click="emit('add-to-cart', item)"
              class="lg:hidden bg-teal-600 text-white px-3 py-1 rounded-lg font-semibold text-sm hover:bg-teal-700"
            >
              +
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<style scoped>
</style>

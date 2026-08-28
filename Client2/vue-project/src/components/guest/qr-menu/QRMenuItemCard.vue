<script setup lang="ts">
import { ref, onMounted } from 'vue'
import reviewService from '@/services/reviewService'
import type { MenuItem } from '@/types/menu'
import type { ReviewStats } from '@/types/review'
import { ShoppingCart, Star, MessageSquare } from 'lucide-vue-next'

interface Props {
  item: MenuItem
  orderId?: string
  guestName?: string
  guestEmail?: string
}

const props = withDefaults(defineProps<Props>(), {
  orderId: '',
  guestName: 'Guest',
  guestEmail: '',
})

const emit = defineEmits<{
  (e: 'add-to-cart', quantity: number): void
  (e: 'write-review'): void
}>()

// State
const quantity = ref(1)
const reviewStats = ref<ReviewStats | null>(null)
const loadingReviews = ref(false)

onMounted(async () => {
  await loadReviewStats()
  
  // Listen for review updates
  window.addEventListener('review-stats-updated', () => {
    loadReviewStats()
  })
})

const loadReviewStats = async () => {
  loadingReviews.value = true
  try {
    const stats = await reviewService.getMenuItemStats(props.item.id)
    console.log('Stats loaded for', props.item.id, ':', stats)
    reviewStats.value = stats
  } catch (error) {
    console.error('Failed to load review stats for', props.item.id, ':', error)
    // Set default empty stats
    reviewStats.value = {
      menu_item_id: props.item.id,
      total_reviews: 0,
      average_rating: 0,
      rating_distribution: { 1: 0, 2: 0, 3: 0, 4: 0, 5: 0 }
    }
  } finally {
    loadingReviews.value = false
  }
}

const handleAddToCart = () => {
  if (quantity.value > 0) {
    emit('add-to-cart', quantity.value)
    quantity.value = 1
  }
}

const reloadReviewStats = async () => {
  await loadReviewStats()
}

const incrementQuantity = () => {
  quantity.value++
}

const decrementQuantity = () => {
  if (quantity.value > 1) {
    quantity.value--
  }
}

const renderStars = (rating: number | null): string => {
  if (!rating) return '☆☆☆☆☆'
  const filled = Math.round(rating)
  const empty = 5 - filled
  return '★'.repeat(filled) + '☆'.repeat(empty)
}
</script>

<template>
  <div class="bg-white rounded-xl border border-slate-200 overflow-hidden shadow-sm hover:shadow-lg transition-all">
    <!-- Image -->
    <div class="relative h-48 bg-gradient-to-br from-slate-100 to-slate-200 overflow-hidden">
      <img
        v-if="item.image_url || item.image"
        :src="item.image_url || item.image"
        :alt="item.name"
        class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300"
      />
      <div v-else class="absolute inset-0 flex items-center justify-center text-slate-300">
        <svg class="w-16 h-16" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
        </svg>
      </div>

      <!-- Availability Badge -->
      <div class="absolute top-3 right-3">
        <div
          :class="[
            'inline-flex items-center gap-1 px-3 py-1.5 rounded-full text-xs font-bold',
            item.is_available ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700',
          ]"
        >
          <span class="w-2 h-2 rounded-full" :class="item.is_available ? 'bg-green-600' : 'bg-red-600'" />
          {{ item.is_available ? 'Available' : 'Unavailable' }}
        </div>
      </div>
    </div>

    <!-- Content -->
    <div class="p-4 space-y-3">
      <!-- Category -->
      <div>
        <span class="inline-block px-2.5 py-0.5 bg-purple-100 text-purple-700 text-xs font-bold rounded-lg uppercase tracking-wide">
          {{ item.category }}
        </span>
      </div>

      <!-- Title -->
      <h3 class="text-lg font-bold text-slate-900 line-clamp-2">{{ item.name }}</h3>

      <!-- Description -->
      <p class="text-sm text-slate-600 line-clamp-2">{{ item.description }}</p>

      <!-- Review Rating -->
      <div v-if="reviewStats && reviewStats.total_reviews > 0" class="bg-gradient-to-r from-yellow-50 to-amber-50 rounded-lg p-3 border border-yellow-200">
        <div class="flex items-center justify-between">
          <div class="flex items-center gap-2">
            <div class="flex gap-0.5">
              <Star 
                v-for="i in 5" 
                :key="i" 
                :size="16" 
                :class="i <= Math.round(reviewStats.average_rating) 
                  ? 'fill-yellow-400 text-yellow-400' 
                  : 'text-gray-300'" 
              />
            </div>
            <div>
              <span class="text-sm font-bold text-slate-900">{{ reviewStats.average_rating?.toFixed(1) || '0.0' }}</span>
              <span class="text-xs text-slate-600 ml-1">({{ reviewStats.total_reviews }} <span v-if="reviewStats.total_reviews === 1">review</span><span v-else>reviews</span>)</span>
            </div>
          </div>
        </div>
      </div>

      <!-- No Reviews Yet -->
      <div v-else class="bg-slate-50 rounded-lg p-3 border border-slate-200">
        <div class="flex items-center gap-2">
          <MessageSquare :size="16" class="text-slate-400" />
          <span class="text-xs text-slate-600 font-medium">No reviews yet</span>
        </div>
      </div>

      <!-- Price -->
      <div class="pt-2 border-t border-slate-200">
        <p class="text-2xl font-black text-purple-600 mb-3">${{ item.price.toFixed(2) }}</p>

        <!-- Action Buttons -->
        <div class="space-y-2">
          <!-- Add to Cart -->
          <div v-if="item.is_available" class="flex items-center gap-2">
            <div class="flex items-center gap-1 bg-slate-100 rounded-lg p-1.5 flex-shrink-0">
              <button
                @click="decrementQuantity"
                class="w-6 h-6 flex items-center justify-center hover:bg-slate-200 rounded transition-colors"
              >
                <span class="text-slate-700 font-bold">−</span>
              </button>
              <span class="w-8 text-center font-semibold text-slate-800">{{ quantity }}</span>
              <button
                @click="incrementQuantity"
                class="w-6 h-6 flex items-center justify-center hover:bg-slate-200 rounded transition-colors"
              >
                <span class="text-slate-700 font-bold">+</span>
              </button>
            </div>
            <button
              @click="handleAddToCart"
              class="flex-1 flex items-center justify-center gap-2 bg-gradient-to-r from-amber-500 to-amber-600 text-white font-bold py-2.5 rounded-lg hover:shadow-lg transition-all"
            >
              <ShoppingCart size="18" />
              Add to Cart
            </button>
          </div>

          <!-- Write Review Button (Always Available) -->
          <button
            @click="$emit('write-review')"
            :disabled="!orderId"
            :title="orderId ? 'Write a review' : 'Complete your order first'"
            class="w-full flex items-center justify-center gap-2 border-2 border-slate-200 text-slate-700 font-semibold py-2.5 rounded-lg hover:bg-slate-50 disabled:opacity-50 disabled:cursor-not-allowed transition-all"
          >
            <MessageSquare size="18" />
            Write Review
          </button>

          <!-- Unavailable Message -->
          <div v-if="!item.is_available" class="text-center py-3 bg-red-50 rounded-lg">
            <p class="text-sm text-red-600 font-medium">Currently Unavailable</p>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<style scoped>
/* Smooth transitions */
button:disabled {
  cursor: not-allowed;
}
</style>

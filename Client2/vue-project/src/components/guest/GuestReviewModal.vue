<script setup lang="ts">
import { ref, computed, watch } from 'vue'
import reviewService from '@/services/reviewService'
import { useAuthStore } from '@/stores/auth'
import type { MenuItem } from '@/types/menu'
import { Star } from 'lucide-vue-next'

const authStore = useAuthStore()

interface Props {
  isOpen: boolean
  menuItem: MenuItem | null
  guestName?: string
  guestEmail?: string
  orderId?: string
}

interface Emits {
  (e: 'close'): void
  (e: 'success', message: string): void
  (e: 'error', message: string): void
}

const props = withDefaults(defineProps<Props>(), {
  guestName: 'Guest',
  guestEmail: '',
  orderId: '',
})

const emit = defineEmits<Emits>()

const customName = ref('')
const customEmail = ref('')
const rating = ref(5)
const reviewText = ref('')
const isSubmitting = ref(false)
const hoverRating = ref(0)

const isFormValid = computed(() => {
  return rating.value >= 1 && rating.value <= 5 && reviewText.value.length <= 500
})

const errorMessage = computed(() => {
  if (reviewText.value.length > 500) return 'Review must not exceed 500 characters'
  return null
})

watch(
  () => props.isOpen,
  (newVal) => {
    if (!newVal) {
      resetForm()
    } else {
      customName.value = props.guestName && props.guestName !== 'Guest' ? props.guestName : (authStore.user?.name || '')
      customEmail.value = props.guestEmail || (authStore.user?.email || '')
    }
  }
)

const resetForm = () => {
  rating.value = 5
  reviewText.value = ''
  hoverRating.value = 0
  isSubmitting.value = false
}

const handleSubmit = async () => {
  if (!props.menuItem) {
    emit('error', 'Menu item information missing')
    return
  }

  if (!isFormValid.value) {
    emit('error', errorMessage.value || 'Invalid form')
    return
  }

  isSubmitting.value = true

  try {
    const email = customEmail.value.trim() || props.guestEmail || authStore.user?.email || ''
    const name = customName.value.trim() || props.guestName || authStore.user?.name || 'Guest'
    const reviewPayload: any = {
      menu_item_id: String(props.menuItem.id),
      rating: rating.value,
      review_text: reviewText.value.trim() || null,
      guest_name: name,
    }

    if (email) {
      reviewPayload.guest_email = email
    }

    if (props.orderId && isValidUUID(props.orderId)) {
      reviewPayload.order_id = props.orderId
    }

    const response = await reviewService.createReview(reviewPayload)

    if (response.success || response.data || response) {
      emit('success', 'Review submitted and published successfully! Thank you for your feedback.')
      resetForm()
      emit('close')
    } else {
      emit('error', response.message || 'Failed to submit review')
    }
  } catch (error: any) {
    console.error('[GuestReviewModal] Error submitting review:', error)
    const errorMsg = error.response?.data?.message || error.response?.data?.errors?.review_text?.[0] || error.message || 'Failed to submit review'
    emit('error', errorMsg)
  } finally {
    isSubmitting.value = false
  }
}

const isValidUUID = (uuid: string): boolean => {
  const uuidRegex = /^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i
  return uuidRegex.test(uuid)
}

const renderStars = (count: number): string => {
  return '★'.repeat(count) + '☆'.repeat(5 - count)
}
</script>

<template>
  <Teleport to="body">
    <Transition name="fade">
      <div
        v-if="isOpen"
        class="fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4"
        @click.self="$emit('close')"
      >
        <div
          class="bg-white rounded-2xl shadow-2xl max-w-md w-full max-h-[90vh] overflow-y-auto"
          @click.stop
        >
          <div class="bg-gradient-to-r from-amber-500 to-amber-600 px-6 py-5 text-white sticky top-0 z-10">
            <div class="flex items-start justify-between gap-4">
              <div>
                <h2 class="text-2xl font-bold">Write a Review</h2>
                <p class="text-amber-100 text-sm mt-1">Share your dining experience</p>
              </div>
              <button
                @click="$emit('close')"
                class="text-white hover:bg-white/20 p-2 rounded-lg transition-colors flex-shrink-0"
              >
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
              </button>
            </div>
          </div>

          <div class="p-6 space-y-5">
            <div v-if="menuItem" class="bg-amber-50 rounded-lg p-4 border border-amber-200">
              <div class="flex gap-3">
                <img
                  v-if="menuItem.image_url"
                  :src="menuItem.image_url"
                  :alt="menuItem.name"
                  class="w-16 h-16 rounded-lg object-cover flex-shrink-0"
                />
                <div class="flex-1 min-w-0">
                  <h3 class="font-bold text-gray-800 truncate">{{ menuItem.name }}</h3>
                  <p class="text-sm text-gray-600 line-clamp-2 mt-1">{{ menuItem.description }}</p>
                  <p class="text-lg font-bold text-amber-600 mt-2">${{ ((menuItem.total_price !== undefined && menuItem.total_price !== null) ? Number(menuItem.total_price) : Number(menuItem.price)).toFixed(2) }}</p>
                </div>
              </div>
            </div>

            <div class="space-y-2">
              <label class="block text-sm font-bold text-gray-800">How would you rate this item?</label>
              <div class="flex gap-2 justify-center">
                <button
                  v-for="star in 5"
                  :key="star"
                  @click="rating = star"
                  @mouseenter="hoverRating = star"
                  @mouseleave="hoverRating = 0"
                  class="transition-transform hover:scale-110 focus:outline-none"
                  :title="`${star} star${star !== 1 ? 's' : ''}`"
                >
                  <Star
                    :size="40"
                    :class="[
                      'transition-colors',
                      star <= (hoverRating || rating)
                        ? 'fill-amber-400 text-amber-400'
                        : 'text-gray-300',
                    ]"
                  />
                </button>
              </div>
              <p class="text-center text-sm font-semibold text-amber-600">
                {{ renderStars(rating) }} ({{ rating }}/5)
              </p>
            </div>

            <div class="space-y-2">
              <label class="block text-sm font-bold text-gray-800">
                Your Review
                <span class="text-gray-500 font-normal">(optional)</span>
              </label>
              <textarea
                v-model="reviewText"
                placeholder="Tell us about your experience with this dish... Was it delicious? Any suggestions?"
                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-amber-500 focus:border-transparent resize-none"
                rows="4"
              />
              <div class="flex items-center justify-between">
                <p v-if="errorMessage" class="text-sm text-red-600 font-medium">
                   {{ errorMessage }}
                </p>
                <p v-else class="text-xs text-gray-500">
                  {{ reviewText.length }} / 500 characters
                </p>
              </div>
            </div>

            <!-- Optional Name & Email -->
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 pt-1">
              <div>
                <label class="block text-xs font-semibold text-gray-700 mb-1">Your Name (optional)</label>
                <input
                  v-model="customName"
                  type="text"
                  placeholder="e.g. John Doe"
                  class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-amber-500 focus:border-transparent"
                />
              </div>
              <div>
                <label class="block text-xs font-semibold text-gray-700 mb-1">Email (optional)</label>
                <input
                  v-model="customEmail"
                  type="email"
                  placeholder="e.g. john@example.com"
                  class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-amber-500 focus:border-transparent"
                />
              </div>
            </div>

            <div class="bg-emerald-50 rounded-lg p-3 border border-emerald-200 flex items-center gap-2">
              <span class="text-emerald-600 font-bold text-base">✓</span>
              <p class="text-xs text-emerald-800 font-medium">
                Your rating and review will be published immediately for other guests to see.
              </p>
            </div>
          </div>

          <div class="bg-gray-50 px-6 py-4 border-t border-gray-200 sticky bottom-0 flex gap-3">
            <button
              @click="$emit('close')"
              :disabled="isSubmitting"
              class="flex-1 px-4 py-3 border-2 border-gray-300 rounded-lg font-semibold text-gray-700 hover:bg-gray-100 disabled:opacity-50 transition-colors"
            >
              Cancel
            </button>
            <button
              @click="handleSubmit"
              :disabled="!isFormValid || isSubmitting"
              class="flex-1 px-4 py-3 bg-gradient-to-r from-amber-500 to-amber-600 text-white rounded-lg font-semibold hover:shadow-lg disabled:opacity-50 disabled:cursor-not-allowed flex items-center justify-center gap-2 transition-shadow"
            >
              <span v-if="isSubmitting" class="inline-flex items-center gap-1">
                <svg class="w-4 h-4 animate-spin" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 2v20m0-20a9.978 9.978 0 00-9 18m18 0a9.978 9.978 0 00-9-18" />
                </svg>
                Submitting...
              </span>
              <span v-else class="inline-flex items-center gap-1">
                ⭐ Submit Review
              </span>
            </button>
          </div>
        </div>
      </div>
    </Transition>
  </Teleport>
</template>

<style scoped>
.fade-enter-active,
.fade-leave-active {
  transition: opacity 0.2s ease;
}

.fade-enter-from,
.fade-leave-to {
  opacity: 0;
}
</style>

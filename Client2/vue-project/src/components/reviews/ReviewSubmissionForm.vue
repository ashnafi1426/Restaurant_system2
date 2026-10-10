<template>
  <div class="review-submission-form">
    <div class="form-header mb-4">
      <h3 class="text-xl font-bold">
        {{ languageStore.t('submit_your_review', 'Submit Your Review') }}
      </h3>
      <p class="text-gray-600 text-sm mt-1">
        {{ languageStore.t('share_experience_desc', 'Share your experience with other guests') }}
      </p>
    </div>

    <form @submit.prevent="submitReview" class="space-y-4">
      <div v-if="menuItem" class="bg-gray-50 p-4 rounded-lg mb-4">
        <div class="flex items-center gap-3">
          <img
            v-if="menuItem.image"
            :src="menuItem.image"
            :alt="menuItem.name"
            class="w-16 h-16 object-cover rounded"
          />
          <div>
            <h4 class="font-semibold">{{ menuItem.name }}</h4>
            <p class="text-sm text-gray-600">{{ menuItem.description }}</p>
            <p class="text-lg font-bold text-green-600 mt-1">{{ formatPrice(menuItem.price) }}</p>
          </div>
        </div>
      </div>

      <div class="form-group">
        <label class="block text-sm font-semibold mb-2"
          >{{ languageStore.t('rating', 'Rating') }} *</label
        >
        <div class="flex gap-2">
          <button
            v-for="star in 5"
            :key="star"
            type="button"
            @click="form.rating = star"
            class="text-3xl transition-colors cursor-pointer"
            :class="star <= form.rating ? 'text-yellow-400' : 'text-gray-300'"
          >
            ★
          </button>
        </div>
        <span v-if="form.rating" class="text-sm text-gray-600 ml-2"
          >{{ form.rating }} {{ languageStore.t('out_of_5', 'out of 5') }}</span
        >
      </div>

      <div class="form-group">
        <label for="reviewText" class="block text-sm font-semibold mb-2">{{
          languageStore.t('your_review', 'Your Review')
        }}</label>
        <textarea
          id="reviewText"
          v-model="form.review_text"
          :placeholder="
            languageStore.t(
              'review_placeholder',
              'Share your thoughts about this menu item... (optional)',
            )
          "
          rows="4"
          maxlength="1000"
          class="w-full p-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
        />
        <div class="text-xs text-gray-500 mt-1">
          {{ form.review_text?.length || 0 }} / 1000
          {{ languageStore.t('characters', 'characters') }}
        </div>
      </div>

      <div class="flex gap-2 pt-4">
        <button
          type="submit"
          :disabled="!form.rating || loading"
          class="flex-1 bg-blue-600 hover:bg-blue-700 disabled:bg-gray-400 text-white font-semibold py-2 px-4 rounded-lg transition-colors cursor-pointer"
        >
          <span v-if="!loading">{{ languageStore.t('submit_review', 'Submit Review') }}</span>
          <span v-else>{{ languageStore.t('submitting', 'Submitting...') }}</span>
        </button>
        <button
          type="button"
          @click="$emit('cancel')"
          class="px-4 py-2 border border-gray-300 hover:bg-gray-50 font-semibold rounded-lg transition-colors cursor-pointer"
        >
          {{ languageStore.t('cancel', 'Cancel') }}
        </button>
      </div>

      <div
        v-if="errorMessage"
        class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg"
      >
        {{ errorMessage }}
      </div>
    </form>
  </div>
</template>

<script setup lang="ts">
import { ref, watch } from 'vue'
import reviewService from '@/services/reviewService'
import type { MenuItem } from '@/types/menu'
import type { CreateReviewRequest } from '@/types/review'
import { useLanguageStore } from '@/stores/language'

interface Props {
  menuItem?: MenuItem
  guestId?: string
  orderId?: string
  menuItemId: string
}

interface Emits {
  (e: 'success', data: any): void
  (e: 'cancel'): void
  (e: 'error', message: string): void
}

const props = defineProps<Props>()
const emit = defineEmits<Emits>()
const languageStore = useLanguageStore()

const form = ref<any>({
  guest_id: props.guestId || '',
  order_id: props.orderId || '',
  menu_item_id: props.menuItemId,
  rating: 0,
  review_text: '',
})

const loading = ref(false)
const errorMessage = ref('')

const formatPrice = (price: number) => {
  return `${price.toFixed(2)}`
}

const submitReview = async () => {
  if (!form.value.rating) {
    errorMessage.value = 'Please select a rating'
    return
  }

  loading.value = true
  errorMessage.value = ''

  try {
    const result = await reviewService.createReview(form.value)
    emit('success', result)
  } catch (error: any) {
    console.error('[ReviewSubmissionForm] Failed to submit review:', error)
    errorMessage.value = error.response?.data?.message || 'Failed to submit review'
    emit('error', errorMessage.value)
  } finally {
    loading.value = false
  }
}

watch(
  () => props.menuItem,
  (newVal) => {},
  { deep: true },
)
</script>

<style scoped>
.review-submission-form {
  background: white;
  border-radius: 8px;
  padding: 20px;
}

.form-group {
  margin-bottom: 16px;
}

textarea:focus {
  box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
}
</style>

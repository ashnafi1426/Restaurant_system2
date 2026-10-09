<script setup lang="ts">
import { ref } from 'vue'
import type { MenuItem } from '@/types/menu'
import { ShoppingCart, MessageSquare, Star } from 'lucide-vue-next'
import { useLanguageStore } from '@/stores/language'

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

const languageStore = useLanguageStore()

const emit = defineEmits<{
  (e: 'add-to-cart', quantity: number): void
  (e: 'write-review'): void
  (e: 'view-reviews'): void
}>()

const quantity = ref(1)

// REMOVED: All review loading logic to improve performance
// Reviews are not critical for menu browsing and were causing 12+ second load times

const handleAddToCart = () => {
  if (quantity.value > 0) {
    emit('add-to-cart', quantity.value)
    quantity.value = 1
  }
}

const incrementQuantity = () => {
  quantity.value++
}

const decrementQuantity = () => {
  if (quantity.value > 1) {
    quantity.value--
  }
}
</script>

<template>
  <div
    class="group bg-white dark:bg-slate-900 rounded-2xl border border-slate-200/80 dark:border-slate-800/80 overflow-hidden shadow-xs hover:shadow-xl transition-all duration-300 flex flex-col justify-between"
  >
    <!-- Image Header -->
    <div class="relative h-44 sm:h-48 w-full bg-slate-100 dark:bg-slate-800 overflow-hidden">
      <img
        v-if="item.image_url || item.image"
        :src="item.image_url || item.image"
        :alt="item.name"
        class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500"
        loading="lazy"
      />
      <div v-else class="absolute inset-0 flex items-center justify-center text-slate-300 dark:text-slate-600">
        <svg class="w-12 h-12" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
        </svg>
      </div>

      <!-- Subtle bottom gradient -->
      <div class="absolute inset-0 bg-gradient-to-t from-black/40 via-transparent to-transparent opacity-60 pointer-events-none"></div>

      <!-- Category badge -->
      <div v-if="item.category" class="absolute top-2.5 left-2.5 z-10">
        <span
          class="inline-block px-2.5 py-1 bg-slate-950/75 backdrop-blur-md text-[#c29353] dark:text-amber-400 text-[10px] sm:text-[11px] font-bold uppercase tracking-wider rounded-lg border border-amber-500/20 shadow-sm"
        >
          {{ languageStore.t(item.category, item.category) }}
        </span>
      </div>

      <!-- Availability status -->
      <div class="absolute top-2.5 right-2.5 z-10">
        <div
          :class="[
            'inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[10px] sm:text-xs font-bold shadow-sm backdrop-blur-md',
            item.is_available
              ? 'bg-emerald-600/90 text-white'
              : 'bg-rose-600/90 text-white',
          ]"
        >
          <span
            class="w-1.5 h-1.5 rounded-full"
            :class="item.is_available ? 'bg-emerald-200' : 'bg-rose-200'"
          />
          {{ item.is_available ? languageStore.t('available', 'Available') : languageStore.t('unavailable', 'Unavailable') }}
        </div>
      </div>
    </div>

    <!-- Content & Details -->
    <div class="p-3.5 sm:p-4 flex-1 flex flex-col justify-between gap-3">
      <!-- Title & Description -->
      <div class="space-y-1">
        <h3
          class="text-base sm:text-lg font-bold text-slate-900 dark:text-white line-clamp-1 group-hover:text-[#c29353] transition-colors"
          :title="item.name"
        >
          {{ item.name }}
        </h3>
        <p class="text-xs text-slate-500 dark:text-slate-400 line-clamp-2 leading-relaxed">
          {{ item.description }}
        </p>
      </div>

      <!-- Rating & Review Display -->
      <div class="flex items-center justify-between text-xs py-0.5">
        <button
          v-if="item.review_count && item.review_count > 0"
          type="button"
          @click.stop="$emit('view-reviews')"
          class="inline-flex items-center gap-1.5 px-2 py-0.5 rounded-lg bg-amber-50/90 dark:bg-amber-950/40 text-amber-700 dark:text-amber-400 hover:bg-amber-100 transition cursor-pointer"
          :title="`${item.review_count} reviews. Click to view reviews`"
        >
          <Star :size="13" class="fill-amber-400 text-amber-400 shrink-0" />
          <span class="font-bold text-xs">{{ Number(item.average_rating || item.rating || 0).toFixed(1) }}</span>
          <span class="text-[11px] text-amber-600/90 dark:text-amber-400/80 underline font-medium">({{ item.review_count }} {{ item.review_count === 1 ? 'review' : 'reviews' }})</span>
        </button>
        <button
          v-else
          type="button"
          @click.stop="$emit('write-review')"
          class="inline-flex items-center gap-1 text-[11px] text-slate-400 hover:text-amber-600 transition cursor-pointer"
          title="No reviews yet. Be the first to rate!"
        >
          <Star :size="12" class="text-slate-300 dark:text-slate-600" />
          <span>{{ languageStore.t('no_reviews_yet', 'No reviews yet') }}</span>
          <span class="text-amber-600 dark:text-amber-400 font-semibold">• {{ languageStore.t('rate_now', 'Rate now') }}</span>
        </button>
      </div>

      <!-- Price & Actions footer -->
      <div class="pt-2.5 border-t border-slate-100 dark:border-slate-800 flex flex-col gap-2.5">
        <!-- Price Row -->
        <div class="flex items-baseline justify-between">
          <span class="text-[10px] sm:text-xs font-bold uppercase tracking-wider text-slate-400 dark:text-slate-500">{{ languageStore.t('price', 'Price') }}</span>
          <div class="text-right">
            <span class="text-lg sm:text-xl font-black text-slate-900 dark:text-white tracking-tight">
              ${{ ((item.total_price !== undefined && item.total_price !== null) ? Number(item.total_price) : Number(item.price || 0)).toFixed(2) }}
            </span>
            <div v-if="item.tax_rate && Number(item.tax_rate.rate) > 0" class="text-[10px] text-slate-400 font-medium">
              <span v-if="item.tax_included" class="text-emerald-600 dark:text-emerald-400 font-semibold">
                Incl. {{ item.tax_rate.rate }}% {{ item.tax_rate.name || 'VAT' }}
              </span>
              <span v-else>
                ${{ Number(item.base_price || item.price).toFixed(2) }} + {{ item.tax_rate.rate }}% tax
              </span>
            </div>
          </div>
        </div>

        <!-- Order Controls -->
        <div v-if="item.is_available" class="flex items-center gap-2">
          <!-- Stepper -->
          <div
            class="flex items-center bg-slate-100 dark:bg-slate-800 rounded-xl p-1 shrink-0 border border-slate-200/60 dark:border-slate-700/60"
          >
            <button
              @click.stop="decrementQuantity"
              class="w-7 h-7 flex items-center justify-center rounded-lg text-slate-700 dark:text-slate-200 hover:bg-white dark:hover:bg-slate-700 transition active:scale-95 text-xs font-bold"
              aria-label="Decrease quantity"
            >
              −
            </button>
            <span class="w-6 sm:w-7 text-center font-bold text-xs sm:text-sm text-slate-800 dark:text-slate-100 select-none">
              {{ quantity }}
            </span>
            <button
              @click.stop="incrementQuantity"
              class="w-7 h-7 flex items-center justify-center rounded-lg text-slate-700 dark:text-slate-200 hover:bg-white dark:hover:bg-slate-700 transition active:scale-95 text-xs font-bold"
              aria-label="Increase quantity"
            >
              +
            </button>
          </div>

          <!-- Add to Cart Button -->
          <button
            @click.stop="handleAddToCart"
            class="flex-1 min-w-0 h-9 sm:h-10 flex items-center justify-center gap-1.5 bg-gradient-to-r from-[#c29353] to-[#a8793b] hover:from-[#b08244] hover:to-[#966b32] text-white font-bold py-2 px-3 rounded-xl shadow-xs hover:shadow-md transition-all active:scale-[0.98] text-xs sm:text-sm whitespace-nowrap"
          >
            <ShoppingCart :size="15" class="shrink-0" />
            <span class="truncate">{{ languageStore.t('add_to_cart', 'Add to Cart') }}</span>
          </button>
        </div>

        <!-- Review Action Row -->
        <div class="flex items-center gap-2">
          <button
            v-if="item.review_count && item.review_count > 0"
            @click.stop="$emit('view-reviews')"
            class="flex-1 h-8 sm:h-9 flex items-center justify-center gap-1 text-xs font-semibold rounded-xl border border-amber-300 dark:border-amber-700/60 bg-amber-50/50 dark:bg-amber-950/20 text-amber-800 dark:text-amber-300 hover:bg-amber-100 transition-all cursor-pointer"
            :title="languageStore.t('view_reviews', 'View Reviews')"
          >
            <Star :size="12" class="fill-amber-400 text-amber-400 shrink-0" />
            <span>{{ languageStore.t('reviews', 'Reviews') }} ({{ item.review_count }})</span>
          </button>

          <!-- Write Review Button -->
          <button
            @click.stop="$emit('write-review')"
            :title="languageStore.t('write_review', 'Write Review')"
            :class="[
              item.review_count && item.review_count > 0 ? 'flex-1' : 'w-full',
              'h-8 sm:h-9 flex items-center justify-center gap-1.5 text-xs font-semibold rounded-xl border border-slate-200 dark:border-slate-700/80 text-slate-600 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-800 hover:text-slate-900 dark:hover:text-white transition-all cursor-pointer'
            ]"
          >
            <MessageSquare :size="13" class="shrink-0" />
            <span>{{ languageStore.t('write_review', 'Write Review') }}</span>
          </button>
        </div>

        <!-- Unavailable Notice -->
        <div
          v-if="!item.is_available"
          class="text-center py-2 bg-rose-50 dark:bg-rose-950/30 border border-rose-200 dark:border-rose-900/50 rounded-xl"
        >
          <p class="text-xs font-bold text-rose-600 dark:text-rose-400">{{ languageStore.t('currently_unavailable', 'Currently Unavailable') }}</p>
        </div>
      </div>
    </div>
  </div>
</template>

<style scoped>
button:disabled {
  cursor: not-allowed;
}
</style>

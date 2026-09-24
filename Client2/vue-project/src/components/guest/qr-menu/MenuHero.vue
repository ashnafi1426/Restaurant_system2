<template>
  <div class="relative h-48 sm:h-60 md:h-64 lg:h-72 rounded-2xl overflow-hidden shadow-md bg-slate-950 border border-slate-800/80 font-sans">
    <!-- Hero Photo (Vibrant & Crisp Gourmet Salmon Dish) -->
    <div class="absolute inset-0 z-0">
      <img
        src="https://images.unsplash.com/photo-1519708227418-c8fd9a32b7a2?w=1200&h=800&fit=crop"
        alt="Good Food Great Moments"
        class="w-full h-full object-cover object-right opacity-100 brightness-105 contrast-105 transition-transform duration-500 hover:scale-105"
      />
      <!-- Soft Gradient Overlay - keeps text readable without darkening food -->
      <div class="absolute inset-0 bg-gradient-to-r from-slate-950/95 via-slate-950/70 to-transparent"></div>
    </div>

    <!-- Content Container -->
    <div class="relative z-10 h-full flex items-center px-6 sm:px-8 md:px-10">
      <div class="max-w-md space-y-2 sm:space-y-3 text-white">
        <!-- Main Heading with Gold Accent -->
        <h1 class="text-2xl sm:text-3xl md:text-4xl font-serif font-bold tracking-tight leading-tight">
          <span class="inline block text-white">{{ languageStore.t('good_food', 'Good Food, ') }}</span>
          <span class="inline block text-[#c29353] drop-shadow-md">{{ languageStore.t('great_moments', 'Great Moments') }}</span>
        </h1>

        <!-- Subtitle Text -->
        <p class="text-xs sm:text-sm text-slate-200 font-medium leading-relaxed max-w-sm">
          {{ languageStore.t('fresh_ingredients_desc', 'Fresh ingredients, expertly prepared. Delivered directly to your room.') }}
        </p>

        <!-- CTA Button -->
        <div class="pt-2">
          <button
            @click="handleSpecials"
            class="px-5 py-2.5 sm:px-6 sm:py-3 rounded-full bg-[#c29353] hover:bg-[#b08244] text-white font-black text-xs sm:text-sm shadow-lg hover:shadow-xl transition cursor-pointer inline-flex items-center gap-2 transform hover:-translate-y-0.5"
          >
            <span>{{ languageStore.t('view_specials', 'View Specials') }}</span>
            <ArrowRight class="w-4 h-4" />
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ArrowRight } from 'lucide-vue-next'
import { useLanguageStore } from '@/stores/language'

const languageStore = useLanguageStore()

const emit = defineEmits<{
  'view-specials': []
}>()

function handleSpecials() {
  emit('view-specials')
}
</script>

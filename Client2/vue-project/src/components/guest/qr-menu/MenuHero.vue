<template>
  <div
    class="relative h-52 sm:h-60 md:h-64 lg:h-72 rounded-2xl overflow-hidden shadow-md bg-slate-950 border border-slate-800/80 font-sans group select-none"
    @touchstart="handleTouchStart"
    @touchend="handleTouchEnd"
  >
    <!-- Slides Background & Image Container -->
    <div
      v-for="(slide, index) in slides"
      :key="slide.id"
      class="absolute inset-0 transition-opacity duration-500 ease-in-out"
      :class="index === currentSlide ? 'opacity-100 z-10 pointer-events-auto' : 'opacity-0 z-0 pointer-events-none'"
    >
      <img
        :src="slide.imageUrl"
        :alt="slide.titleLine1 + ' ' + slide.titleLine2"
        class="w-full h-full object-cover object-right opacity-100 brightness-105 contrast-105 transition-transform duration-1000 ease-out"
        :class="index === currentSlide ? 'scale-105' : 'scale-100'"
        loading="eager"
      />

      <!-- Soft Gradient Overlay - keeps typography readable while maintaining rich food colors -->
      <div class="absolute inset-0 bg-gradient-to-r from-slate-950/95 via-slate-950/75 to-transparent"></div>
    </div>

    <!-- Active Slide Content Overlay -->
    <div class="relative z-20 h-full flex items-center px-6 sm:px-8 md:px-10">
      <Transition name="slide-fade" mode="out-in">
        <div :key="currentSlide" class="max-w-md space-y-2 sm:space-y-2.5 text-white">
          <!-- Tag / Badge -->
          <div v-if="activeSlide.badge" class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full bg-white/10 backdrop-blur-md border border-white/15 text-[11px] font-bold uppercase tracking-wider text-amber-300">
            <span class="w-1.5 h-1.5 rounded-full bg-amber-400 animate-pulse"></span>
            <span>{{ activeSlide.badge }}</span>
          </div>

          <!-- Main Heading with Gold Accent matching screenshot -->
          <h1 class="text-2xl sm:text-3xl md:text-4xl font-serif font-bold tracking-tight leading-tight">
            <span class="block text-white">{{ activeSlide.titleLine1 }}</span>
            <span class="block text-[#c29353] drop-shadow-md">{{ activeSlide.titleLine2 }}</span>
          </h1>

          <!-- Subtitle Text -->
          <p class="text-xs sm:text-sm text-slate-200 font-medium leading-relaxed max-w-sm line-clamp-2">
            {{ activeSlide.subtitle }}
          </p>

          <!-- CTA Button -->
          <div class="pt-1.5 sm:pt-2">
            <button
              @click="handleSpecials(activeSlide.category)"
              class="px-5 py-2.5 sm:px-6 sm:py-3 rounded-full bg-[#c29353] hover:bg-[#b08244] text-white font-bold text-xs sm:text-sm shadow-lg hover:shadow-xl transition-all cursor-pointer inline-flex items-center gap-2 transform hover:-translate-y-0.5 active:translate-y-0"
            >
              <span>{{ activeSlide.ctaText || languageStore.t('view_specials', 'View Specials') }}</span>
              <ChevronRight class="w-4 h-4" />
            </button>
          </div>
        </div>
      </Transition>
    </div>

    <!-- Arrow Navigation (Visible on hover and on touch devices) -->
    <button
      @click="prevSlide"
      class="absolute left-3 top-1/2 -translate-y-1/2 z-30 w-8 h-8 sm:w-9 sm:h-9 rounded-full bg-slate-900/60 hover:bg-slate-900/90 text-white/80 hover:text-white backdrop-blur-md border border-white/10 flex items-center justify-center opacity-0 group-hover:opacity-100 transition-all duration-200 cursor-pointer shadow-md"
      title="Previous slide"
      aria-label="Previous slide"
    >
      <ChevronLeft class="w-5 h-5" />
    </button>

    <button
      @click="nextSlide"
      class="absolute right-3 top-1/2 -translate-y-1/2 z-30 w-8 h-8 sm:w-9 sm:h-9 rounded-full bg-slate-900/60 hover:bg-slate-900/90 text-white/80 hover:text-white backdrop-blur-md border border-white/10 flex items-center justify-center opacity-0 group-hover:opacity-100 transition-all duration-200 cursor-pointer shadow-md"
      title="Next slide"
      aria-label="Next slide"
    >
      <ChevronRight class="w-5 h-5" />
    </button>

    <!-- Bottom Indicator Pagination Dots -->
    <div class="absolute bottom-3.5 right-6 sm:right-8 z-30 flex items-center gap-1.5">
      <button
        v-for="(slide, index) in slides"
        :key="slide.id"
        @click="goToSlide(index)"
        class="h-1.5 rounded-full transition-all duration-300 cursor-pointer focus:outline-none"
        :class="index === currentSlide ? 'w-6 bg-[#c29353]' : 'w-1.5 bg-white/40 hover:bg-white/70'"
        :title="`Go to slide ${index + 1}`"
        :aria-label="`Slide ${index + 1}`"
      />
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted, onUnmounted } from 'vue'
import { ChevronRight, ChevronLeft } from 'lucide-vue-next'
import { useLanguageStore } from '@/stores/language'

interface HeroSlide {
  id: number
  badge: string
  titleLine1: string
  titleLine2: string
  subtitle: string
  ctaText: string
  category?: string
  imageUrl: string
}

interface Props {
  intervalMs?: number
}

const props = withDefaults(defineProps<Props>(), {
  // Navigates every second (1000ms) as requested
  intervalMs: 1000
})

const emit = defineEmits<{
  (e: 'view-specials', category?: string): void
}>()

const languageStore = useLanguageStore()
const currentSlide = ref(0)
let timer: ReturnType<typeof setInterval> | null = null

// Touch coordinates for mobile swiping
let touchStartX = 0
let touchEndX = 0

// 5+ Curated Distinct Gourmet Food Images from unsplash.com
const slides = computed<HeroSlide[]>(() => [
  {
    id: 1,
    badge: "Chef's Signature",
    titleLine1: languageStore.t('good_food', 'Good Food,'),
    titleLine2: languageStore.t('great_moments', 'Great Moments'),
    subtitle: languageStore.t('fresh_ingredients_desc', 'Fresh ingredients, expertly prepared. Delivered directly to your room.'),
    ctaText: languageStore.t('view_specials', 'View Specials'),
    category: 'Specials',
    imageUrl: 'https://images.unsplash.com/photo-1519708227418-c8fd9a32b7a2?w=1600&auto=format&fit=crop&q=80'
  },
  {
    id: 2,
    badge: 'Flame-Grilled',
    titleLine1: 'Prime Cuts,',
    titleLine2: 'Artisan Steaks',
    subtitle: 'Tender aged steaks grilled to perfection with signature herb butter and rich jus.',
    ctaText: 'Explore Steaks',
    category: 'Main Courses',
    imageUrl: 'https://images.unsplash.com/photo-1544025162-d76694265947?w=1600&auto=format&fit=crop&q=80'
  },
  {
    id: 3,
    badge: 'Wood-Fired',
    titleLine1: 'Authentic Crust,',
    titleLine2: 'Italian Pizza',
    subtitle: 'Crispy stone-baked dough, San Marzano sauce, and melted fresh mozzarella.',
    ctaText: 'Taste Pizza',
    category: 'Pizza',
    imageUrl: 'https://images.unsplash.com/photo-1513104890138-7c749659a591?w=1600&auto=format&fit=crop&q=80'
  },
  {
    id: 4,
    badge: 'Guest Favorite',
    titleLine1: 'Handcrafted,',
    titleLine2: 'Gourmet Burgers',
    subtitle: 'Toasted brioche, melted cheddar, and savory flame-grilled patties.',
    ctaText: 'Discover Burgers',
    category: 'Burgers',
    imageUrl: 'https://images.unsplash.com/photo-1568901346375-23c9450c58cd?w=1600&auto=format&fit=crop&q=80'
  },
  {
    id: 5,
    badge: 'Ocean Fresh',
    titleLine1: 'Hand-Rolled,',
    titleLine2: 'Sushi & Sashimi',
    subtitle: 'Fresh Pacific salmon, premium tuna rolls, and handcrafted coastal delicacies.',
    ctaText: 'Taste Seafood',
    category: 'Seafood',
    imageUrl: 'https://images.unsplash.com/photo-1579871494447-9811cf80d66c?w=1600&auto=format&fit=crop&q=80'
  },
  {
    id: 6,
    badge: 'Sweet Finale',
    titleLine1: 'Warm Molten,',
    titleLine2: 'Decadent Desserts',
    subtitle: 'Rich chocolate lava cakes, artisan pastries, and delicate berry garnishes.',
    ctaText: 'Browse Sweets',
    category: 'Desserts',
    imageUrl: 'https://images.unsplash.com/photo-1606313564200-e75d5e30476c?w=1600&auto=format&fit=crop&q=80'
  }
])

const activeSlide = computed(() => slides.value[currentSlide.value] || slides.value[0])

const nextSlide = () => {
  currentSlide.value = (currentSlide.value + 1) % slides.value.length
}

const prevSlide = () => {
  currentSlide.value = (currentSlide.value - 1 + slides.value.length) % slides.value.length
}

const goToSlide = (index: number) => {
  currentSlide.value = index
}

const startAutoplay = () => {
  stopAutoplay()
  timer = setInterval(() => {
    nextSlide()
  }, props.intervalMs)
}

const stopAutoplay = () => {
  if (timer) {
    clearInterval(timer)
    timer = null
  }
}

// Touch swipe support
const handleTouchStart = (e: TouchEvent) => {
  touchStartX = e.changedTouches[0].screenX
}

const handleTouchEnd = (e: TouchEvent) => {
  touchEndX = e.changedTouches[0].screenX
  if (touchStartX - touchEndX > 50) {
    nextSlide()
  } else if (touchEndX - touchStartX > 50) {
    prevSlide()
  }
}

const handleSpecials = (category?: string) => {
  emit('view-specials', category)
}

onMounted(() => {
  // Preload all unsplash images into browser cache so navigation every second is instant & smooth
  slides.value.forEach(slide => {
    const img = new Image()
    img.src = slide.imageUrl
  })
  startAutoplay()
})

onUnmounted(() => {
  stopAutoplay()
})
</script>

<style scoped>
.slide-fade-enter-active,
.slide-fade-leave-active {
  transition: all 0.3s ease;
}

.slide-fade-enter-from {
  opacity: 0;
  transform: translateY(4px);
}

.slide-fade-leave-to {
  opacity: 0;
  transform: translateY(-4px);
}
</style>

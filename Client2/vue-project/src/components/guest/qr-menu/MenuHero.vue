<template>
  <div class="premium-hero-banner relative h-72 sm:h-80 md:h-96 rounded-2xl sm:rounded-3xl overflow-hidden shadow-2xl">
    
    <!-- Background Image Layer -->
    <div class="absolute inset-0 bg-slate-800">
      <img
        :src="heroImage"
        :alt="heading || 'Restaurant Menu Hero'"
        class="w-full h-full object-cover"
        loading="eager"
        @error="handleImageError"
      />
      <!-- Fallback gradient if image fails -->
      <div 
        v-if="imageError" 
        class="absolute inset-0 bg-gradient-to-r from-amber-900 via-amber-800 to-amber-700"
      ></div>
    </div>

    <!-- Dark Overlay with Gradient -->
    <div class="absolute inset-0 bg-gradient-to-r from-black/80 via-black/60 to-black/30"></div>

    <!-- Additional dark accent on top -->
    <div class="absolute top-0 left-0 right-0 h-20 sm:h-32 bg-gradient-to-b from-black/60 to-transparent"></div>

    <!-- Content Container -->
    <div class="absolute inset-0 flex items-center">
      <div class="container mx-auto px-4 sm:px-6 md:px-8 lg:px-12 h-full flex items-center">
        
        <!-- LEFT SECTION: Content -->
        <div class="w-full md:w-3/5 lg:w-1/2 pr-0 md:pr-6 lg:pr-8 flex flex-col justify-center z-10">
          
          <!-- Small Subtitle/Tag -->
          <div class="flex items-center gap-2 mb-3 sm:mb-4 md:mb-6">
            <div class="w-8 sm:w-10 md:w-12 h-1 bg-gradient-to-r from-amber-400 to-amber-500 rounded-full"></div>
            <span class="text-[10px] sm:text-xs md:text-sm font-semibold text-amber-300 tracking-widest uppercase">
              {{ subHeading || 'LUXURY DINING' }}
            </span>
          </div>

          <!-- Main Heading with Gold Highlights -->
          <h1 class="text-2xl sm:text-3xl md:text-4xl lg:text-5xl xl:text-6xl font-serif font-bold text-white mb-2 sm:mb-3 md:mb-4 lg:mb-6 leading-tight">
            <template v-if="heading.includes('Great Moments')">
              <span class="block">Good Food,</span>
              <span class="block text-amber-400 drop-shadow-lg">Great Moments</span>
            </template>
            <template v-else>
              {{ heading }}
            </template>
          </h1>

          <!-- Description -->
          <p class="text-xs sm:text-sm md:text-base text-gray-200 font-light mb-4 sm:mb-5 md:mb-6 lg:mb-8 max-w-md leading-relaxed">
            {{ description }}
          </p>

          <!-- CTA Buttons -->
          <div class="flex flex-wrap gap-2 sm:gap-3 md:gap-4">
            
            <!-- Primary CTA -->
            <button
              @click="handleExplore"
              class="premium-cta-button group relative inline-flex items-center gap-2 sm:gap-3 px-5 sm:px-6 md:px-7 lg:px-8 py-2.5 sm:py-3 md:py-3.5 lg:py-4 bg-gradient-to-r from-amber-400 to-amber-600 hover:from-amber-500 hover:to-amber-700 text-gray-900 font-bold rounded-full shadow-2xl hover:shadow-amber-500/50 transition-all duration-300 transform hover:scale-105 border-2 border-amber-300 hover:border-amber-200 text-sm sm:text-base"
              aria-label="Explore our menu"
            >
              <!-- Glow effect -->
              <span class="absolute inset-0 rounded-full bg-amber-400 opacity-0 group-hover:opacity-20 blur-xl transition-opacity duration-300"></span>
              
              <!-- Content -->
              <span class="relative">{{ ctaText || 'Explore Menu' }}</span>
              <svg class="relative w-4 h-4 sm:w-5 sm:h-5 group-hover:translate-x-1 transition-transform duration-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M13 7l5 5m0 0l-5 5m5-5H6"></path>
              </svg>
            </button>

            <!-- Secondary Button -->
            <button
              @click="handleSpecials"
              class="inline-flex items-center gap-1.5 sm:gap-2 px-4 sm:px-5 md:px-6 py-2.5 sm:py-3 md:py-3.5 lg:py-4 border-2 border-amber-400 text-amber-300 font-semibold rounded-full hover:bg-white/10 hover:border-amber-300 transition-all duration-300 backdrop-blur-sm text-sm sm:text-base"
              aria-label="View specials"
            >
              <svg class="w-4 h-4 sm:w-5 sm:h-5" fill="currentColor" viewBox="0 0 24 24">
                <path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"></path>
              </svg>
              <span class="hidden xs:inline">{{ ctaSubText || 'View Specials' }}</span>
              <span class="xs:hidden">Specials</span>
            </button>

          </div>

          <!-- Features Row -->
          <div class="grid grid-cols-3 gap-1.5 sm:gap-2 md:gap-3 lg:gap-4 mt-4 sm:mt-5 md:mt-6 lg:mt-8 max-w-sm sm:max-w-md">
            
            <div class="flex items-center gap-1 sm:gap-1.5 md:gap-2">
              <svg class="w-3.5 h-3.5 sm:w-4 sm:h-4 md:w-5 md:h-5 text-amber-400 flex-shrink-0" fill="currentColor" viewBox="0 0 24 24">
                <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-2 15l-5-5 1.41-1.41L10 14.17l7.59-7.59L19 8l-9 9z"></path>
              </svg>
              <span class="text-[10px] xs:text-xs sm:text-sm text-gray-300 font-medium truncate">Premium</span>
            </div>

            <div class="flex items-center gap-1 sm:gap-1.5 md:gap-2">
              <svg class="w-3.5 h-3.5 sm:w-4 sm:h-4 md:w-5 md:h-5 text-amber-400 flex-shrink-0" fill="currentColor" viewBox="0 0 24 24">
                <path d="M19 3H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zm0 16H5V5h14v14zM7 10h2v7H7zm4-3h2v10h-2zm4-3h2v13h-2z"></path>
              </svg>
              <span class="text-[10px] xs:text-xs sm:text-sm text-gray-300 font-medium truncate">Fresh</span>
            </div>

            <div class="flex items-center gap-1 sm:gap-1.5 md:gap-2">
              <svg class="w-3.5 h-3.5 sm:w-4 sm:h-4 md:w-5 md:h-5 text-amber-400 flex-shrink-0" fill="currentColor" viewBox="0 0 24 24">
                <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-2 15l-5-5 1.41-1.41L10 14.17l7.59-7.59L19 8l-9 9z"></path>
              </svg>
              <span class="text-[10px] xs:text-xs sm:text-sm text-gray-300 font-medium truncate">24/7</span>
            </div>

          </div>
        </div>

        <!-- RIGHT SECTION: Decorative (hidden on mobile) -->
        <div class="hidden md:flex w-2/5 lg:w-1/2 h-full items-center justify-end relative">
          <!-- Decorative light elements -->
          <div class="absolute top-10 right-10 w-32 h-32 bg-amber-400/10 rounded-full blur-3xl"></div>
          <div class="absolute bottom-10 right-20 w-24 h-24 bg-amber-500/5 rounded-full blur-2xl"></div>
          <div class="absolute inset-0 bg-gradient-to-l from-transparent via-transparent to-black/30"></div>
        </div>

      </div>
    </div>

    <!-- Bottom Decorative Element -->
    <div class="absolute bottom-0 left-0 right-0 h-0.5 sm:h-1 bg-gradient-to-r from-amber-400 via-amber-500 to-transparent"></div>

    <!-- Corner accents -->
    <div class="absolute top-0 right-0 w-32 h-32 sm:w-40 sm:h-40 bg-gradient-to-br from-amber-500/20 to-transparent blur-3xl"></div>
    <div class="absolute bottom-0 left-0 w-24 h-24 sm:w-32 sm:h-32 bg-gradient-to-tr from-amber-400/10 to-transparent blur-2xl"></div>

  </div>
</template>

<script setup lang="ts">
import { ref } from 'vue'

interface Props {
  heroImage?: string
  heading?: string
  subHeading?: string
  description?: string
  ctaText?: string
  ctaSubText?: string
}

const props = withDefaults(defineProps<Props>(), {
  heroImage: '/images/gallery/fine-dining.jpg',
  heading: 'Good Food, Great Moments',
  subHeading: 'LUXURY DINING',
  description: 'Indulge in our meticulously curated menu, crafted by award-winning chefs using only the finest, freshest ingredients. Experience culinary excellence delivered directly to the comfort of your room.',
  ctaText: 'Explore Menu',
  ctaSubText: 'View Specials'
})

const emit = defineEmits<{
  'explore': []
  'view-specials': []
  'image-error': [error: Event]
}>()

const imageError = ref(false)

const handleExplore = () => {
  emit('explore')
  // Scroll to menu section
  setTimeout(() => {
    const menuSection = document.getElementById('menu-grid')
    if (menuSection) {
      menuSection.scrollIntoView({ behavior: 'smooth', block: 'start' })
    }
  }, 100)
}

const handleSpecials = () => {
  emit('view-specials')
}

const handleImageError = (event: Event) => {
  imageError.value = true
  emit('image-error', event)
}
</script>

<style scoped>
.premium-hero-banner {
  position: relative;
  isolation: isolate;
}

/* Premium CTA Button Glow Effect */
.premium-cta-button {
  position: relative;
  overflow: hidden;
}

.premium-cta-button::before {
  content: '';
  position: absolute;
  inset: 0;
  background: linear-gradient(45deg, transparent 30%, rgba(255, 255, 255, 0.15) 50%, transparent 70%);
  transform: translateX(-100%);
  animation: shimmer 3s infinite;
}

@keyframes shimmer {
  0% {
    transform: translateX(-100%);
  }
  100% {
    transform: translateX(100%);
  }
}

.premium-cta-button:hover::before {
  animation: shimmer 1.5s infinite;
}

/* Smooth transitions */
@media (prefers-reduced-motion: reduce) {
  .premium-hero-banner,
  .premium-cta-button {
    transition: none;
    animation: none;
  }
  
  .premium-cta-button::before {
    animation: none;
  }
}

/* Luxury typography */
h1 {
  font-family: 'Georgia', 'Garamond', 'Times New Roman', serif;
  letter-spacing: 0.5px;
  text-shadow: 0 2px 8px rgba(0, 0, 0, 0.4);
}

/* Ensure text stays readable */
.premium-hero-banner p {
  text-shadow: 0 2px 8px rgba(0, 0, 0, 0.3);
}

/* Button hover effects */
button:focus-visible {
  outline: 2px solid #fbbf24;
  outline-offset: 2px;
}

/* Drop shadow for heading */
.drop-shadow-lg {
  filter: drop-shadow(0 4px 12px rgba(0, 0, 0, 0.5));
}

/* Image loading */
img {
  transition: opacity 0.3s ease;
}

img:not([src]) {
  opacity: 0;
}

img[src] {
  opacity: 1;
}

/* Extra small screens (below 400px) */
@media (max-width: 400px) {
  .premium-hero-banner {
    height: 16rem;
    border-radius: 1rem;
  }
  
  h1 {
    font-size: 1.5rem;
  }
}

/* Small screens (400-640px) */
@media (min-width: 400px) and (max-width: 640px) {
  .premium-hero-banner {
    height: 17rem;
  }
  
  h1 {
    font-size: 1.75rem;
  }
}

/* Tablets (640-768px) */
@media (min-width: 640px) and (max-width: 768px) {
  .premium-hero-banner {
    height: 18rem;
  }
  
  h1 {
    font-size: 2rem;
  }
}

/* Desktop (768px+) */
@media (min-width: 768px) {
  .premium-hero-banner {
    height: 22rem;
  }
  
  h1 {
    font-size: 2.5rem;
  }
}

/* Large Desktop (1024px+) */
@media (min-width: 1024px) {
  .premium-hero-banner {
    height: 24rem;
  }
  
  h1 {
    font-size: 3rem;
  }
}

/* XL Desktop (1280px+) */
@media (min-width: 1280px) {
  .premium-hero-banner {
    height: 26rem;
  }
  
  h1 {
    font-size: 3.5rem;
  }
}

/* Image overlay gradient for depth */
.premium-hero-banner::after {
  content: '';
  position: absolute;
  inset: 0;
  background: radial-gradient(ellipse at bottom right, transparent 0%, rgba(0, 0, 0, 0.2) 100%);
  pointer-events: none;
}

/* Hide scrollbar for category pills if needed */
.hide-scrollbar::-webkit-scrollbar {
  display: none;
}

.hide-scrollbar {
  -ms-overflow-style: none;
  scrollbar-width: none;
}

/* Touch device optimizations */
@media (hover: none) {
  .premium-cta-button:hover {
    transform: none;
  }
  
  .premium-cta-button:hover::before {
    animation: shimmer 3s infinite;
  }
}

/* High contrast mode support */
@media (prefers-contrast: high) {
  .premium-hero-banner {
    background: #000;
  }
  
  .premium-hero-banner .text-amber-400 {
    color: #fbbf24;
  }
  
  .premium-hero-banner .border-amber-400 {
    border-color: #fbbf24;
  }
}
</style>
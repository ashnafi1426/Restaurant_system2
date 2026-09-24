<script setup lang="ts">
import { ref, onMounted, onUnmounted, computed, watch } from 'vue'
import { RouterLink, useRoute } from 'vue-router'
import { useThemeStore } from '@/stores/theme'
import { useGuestHotelStore } from '@/stores/guestHotelStore'
import { useLanguageStore } from '@/stores/language'
import HotelSelectorModal from '@/components/guest/HotelSelectorModal.vue'
import LanguageSelector from '@/components/common/LanguageSelector.vue'
import { Sun, Moon, QrCode, Maximize, Minimize, Menu as MenuIcon, X as CloseIcon, Building2, ChevronDown } from 'lucide-vue-next'

const route = useRoute()
const theme = useThemeStore()
const guestHotelStore = useGuestHotelStore()
const languageStore = useLanguageStore()

const mobileMenu = ref(false)
const scrolled = ref(false)
const isFullscreen = ref(false)
const isHotelModalOpen = ref(false)
const imageLoadFailed = ref(false)

// Reset imageLoadFailed whenever selected hotel or logo changes
watch(
  () => [guestHotelStore.hotelId, guestHotelStore.currentHotel?.logo],
  () => {
    imageLoadFailed.value = false
  },
  { immediate: true }
)

const hotelLogoUrl = computed(() => {
  if (imageLoadFailed.value) return null
  const logo = guestHotelStore.currentHotel?.logo
  if (logo && typeof logo === 'string' && logo.trim()) {
    const trimmed = logo.trim()
    if (
      trimmed.startsWith('http://') ||
      trimmed.startsWith('https://') ||
      trimmed.startsWith('data:') ||
      trimmed.startsWith('/images/')
    ) {
      return trimmed
    }
    if (trimmed.startsWith('/storage/')) {
      return `http://127.0.0.1:8000${trimmed}`
    }
    if (trimmed.startsWith('storage/') || trimmed.startsWith('hotels/')) {
      return `http://127.0.0.1:8000/storage/${trimmed.replace(/^storage\//, '')}`
    }
    if (trimmed.startsWith('images/')) {
      return `/${trimmed}`
    }
    return `http://127.0.0.1:8000/storage/${trimmed}`
  }

  // If no custom logo in backend, resolve branded default logos by hotel name
  const name = (guestHotelStore.hotelName || '').toLowerCase()
  if (name.includes('qelem') || name.includes('meda')) {
    return '/images/qelem meda logo.png'
  }
  return '/images/Hotel logo.png'
})

function openHotelModal() {
  isHotelModalOpen.value = true
  guestHotelStore.openHotelSelector()
}

function handleHotelSelected() {
  isHotelModalOpen.value = false
  imageLoadFailed.value = false
}

const menus = computed(() => [
  { title: languageStore.t('home', 'Home'), route: '/' },
  { title: languageStore.t('rooms', 'Rooms'), route: '/rooms' },
  { title: languageStore.t('gallery', 'Gallery'), route: '/gallery' },
  { title: languageStore.t('about', 'About'), route: '/about' },
  { title: languageStore.t('contact', 'Contact'), route: '/contact' },
])

function toggleMenu() {
  mobileMenu.value = !mobileMenu.value
}

function closeMenu() {
  mobileMenu.value = false
}

function handleScroll() {
  scrolled.value = window.scrollY > 40
}

function handleThemeToggle() {
  theme.toggleTheme()
}

function toggleFullscreen() {
  if (!document.fullscreenElement) {
    document.documentElement.requestFullscreen().catch((err) => {
      console.error('[guestNavbar] Request fullscreen error:', err)
    })
    isFullscreen.value = true
  } else {
    if (document.exitFullscreen) {
      document.exitFullscreen().catch((err) => {
        console.error('[guestNavbar] Exit fullscreen error:', err)
      })
      isFullscreen.value = false
    }
  }
}

onMounted(() => {
  theme.initializeTheme()
  window.addEventListener('scroll', handleScroll)
  if (!guestHotelStore.hasSelectedHotel) {
    isHotelModalOpen.value = true
  }
})

onUnmounted(() => {
  window.removeEventListener('scroll', handleScroll)
})
</script>

<template>
  <header
    class="fixed inset-x-0 top-0 z-50 transition-all duration-300 backdrop-blur-md"
    :class="[
      scrolled
        ? 'bg-white/95 dark:bg-slate-900/95 shadow-md border-b border-slate-200/80 dark:border-slate-800/80'
        : 'bg-slate-50/80 dark:bg-slate-950/80 border-b border-slate-200/40 dark:border-slate-800/40'
    ]"
  >
    <div class="mx-auto flex h-20 w-full max-w-[1550px] items-center justify-between px-4 sm:px-6 lg:px-8 xl:px-10">
      <!-- Logo + Hotel Switcher Button -->
      <div class="flex items-center gap-3 xl:gap-4 shrink-0">
        <RouterLink to="/" class="flex items-center gap-3 group shrink-0">
          <div 
            class="flex h-11 w-11 items-center justify-center rounded-2xl overflow-hidden shadow-xs border border-amber-500/30 bg-amber-500/10 shrink-0 transition-transform duration-300 group-hover:scale-105"
          >
            <img 
              v-if="hotelLogoUrl && !imageLoadFailed" 
              :src="hotelLogoUrl" 
              :alt="guestHotelStore.hotelName" 
              class="w-full h-full object-cover"
              @error="imageLoadFailed = true" 
            />
            <span 
              v-else 
              class="text-amber-600 dark:text-amber-400 font-black text-xl"
            >
              {{ (guestHotelStore.hotelName || 'H').charAt(0).toUpperCase() }}
            </span>
          </div>

          <div class="flex flex-col min-w-0">
            <h2 class="text-base sm:text-lg font-black tracking-tight text-slate-900 dark:text-white transition-colors group-hover:text-amber-500 whitespace-nowrap leading-tight">
              {{ guestHotelStore.hotelName }}
            </h2>
            <p class="text-[9px] uppercase tracking-[2.5px] font-black text-amber-600 dark:text-amber-400 whitespace-nowrap">
              {{ guestHotelStore.currentHotel?.city || 'Addis Ababa' }}
            </p>
          </div>
        </RouterLink>

        <!-- Switch Hotel Button (Desktop & Tablet) -->
        <button
          @click.stop="openHotelModal"
          type="button"
          class="hidden md:inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl text-xs font-bold bg-amber-500/10 hover:bg-amber-500 text-amber-600 dark:text-amber-400 hover:text-slate-950 border border-amber-500/30 transition-all cursor-pointer shadow-xs whitespace-nowrap shrink-0"
          title="Switch Hotel"
        >
          <Building2 class="w-3.5 h-3.5 shrink-0" />
          <span class="whitespace-nowrap">{{ languageStore.t('switch_hotel', 'Switch Hotel') }}</span>
          <ChevronDown class="w-3.5 h-3.5 opacity-70 shrink-0" />
        </button>
      </div>

      <!-- Desktop Navigation -->
      <nav class="hidden lg:flex items-center gap-5 xl:gap-7 mx-2 shrink-0">
        <RouterLink
          v-for="menu in menus"
          :key="menu.route"
          :to="menu.route"
          class="relative text-xs uppercase tracking-widest font-extrabold transition-colors py-2 whitespace-nowrap"
          :class="[
            route.path === menu.route
              ? 'text-amber-600 dark:text-amber-400'
              : 'text-slate-700 dark:text-slate-300 hover:text-amber-600 dark:hover:text-amber-400'
          ]"
        >
          {{ menu.title }}
          <span
            v-if="route.path === menu.route"
            class="absolute bottom-0 left-0 right-0 h-[2px] bg-amber-500 rounded-full"
          />
        </RouterLink>
      </nav>

      <!-- Action Control Buttons & Book Now -->
      <div class="hidden lg:flex items-center gap-2.5 shrink-0">
        <!-- Digital QR Menu Icon Button -->
        <RouterLink
          to="/menu"
          class="flex h-10 w-10 items-center justify-center rounded-xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 text-amber-500 hover:border-amber-500 hover:scale-105 transition-all cursor-pointer shadow-xs shrink-0"
          title="Digital QR Room Service & Restaurant Menu"
        >
          <QrCode class="w-4.5 h-4.5" />
        </RouterLink>

        <!-- Fullscreen Toggle Button -->
        <button
          @click="toggleFullscreen"
          type="button"
          class="flex h-10 w-10 items-center justify-center rounded-xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 text-slate-700 dark:text-slate-200 hover:border-amber-500 hover:text-amber-500 transition-all cursor-pointer shadow-xs shrink-0"
          :title="isFullscreen ? 'Exit Fullscreen' : 'Fullscreen View'"
        >
          <Maximize v-if="!isFullscreen" class="w-4.5 h-4.5" />
          <Minimize v-else class="w-4.5 h-4.5 text-amber-500" />
        </button>

        <!-- Interactive Theme Toggle Button (Icon Button) -->
        <button
          @click="handleThemeToggle"
          type="button"
          class="flex h-10 w-10 items-center justify-center rounded-xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 text-slate-700 dark:text-slate-200 hover:border-amber-500 transition-all cursor-pointer shadow-xs shrink-0"
          :title="theme.isDark ? languageStore.t('light_mode', 'Light Mode') : languageStore.t('dark_mode', 'Dark Mode')"
        >
          <Sun v-if="theme.isDark" class="w-4.5 h-4.5 text-amber-400" />
          <Moon v-else class="w-4.5 h-4.5 text-slate-700" />
        </button>

        <!-- Language Switcher Dropdown -->
        <LanguageSelector variant="pill" />

        <!-- Book Now Button -->
        <RouterLink
          to="/rooms"
          class="h-10 px-5 flex items-center justify-center rounded-xl bg-gradient-to-r from-amber-500 to-amber-600 hover:from-amber-400 hover:to-amber-500 text-slate-950 font-black text-xs uppercase tracking-wider shadow-md shadow-amber-500/20 hover:shadow-lg transition-all whitespace-nowrap shrink-0 ml-1"
        >
          {{ languageStore.t('book_now', 'Book Now') }}
        </RouterLink>
      </div>

      <!-- Mobile Controls -->
      <div class="flex items-center gap-1.5 sm:gap-2 lg:hidden">
        <!-- Language Switcher (Mobile Compact) -->
        <LanguageSelector variant="compact" />

        <!-- Mobile Switch Hotel Shortcut -->
        <button
          @click.stop="openHotelModal"
          type="button"
          class="flex h-10 w-10 items-center justify-center rounded-xl border border-amber-500/40 bg-amber-500/10 text-amber-600 dark:text-amber-400 cursor-pointer"
          title="Switch Hotel"
        >
          <Building2 class="w-4.5 h-4.5" />
        </button>

        <!-- QR Menu Shortcut -->
        <RouterLink
          to="/menu"
          class="flex h-10 w-10 items-center justify-center rounded-xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 text-amber-500"
          title="Digital QR Menu"
        >
          <QrCode class="w-4.5 h-4.5" />
        </RouterLink>

        <!-- Theme Toggle Button -->
        <button
          @click="handleThemeToggle"
          type="button"
          class="flex h-10 w-10 items-center justify-center rounded-xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 text-slate-700 dark:text-slate-200"
        >
          <Sun v-if="theme.isDark" class="w-4.5 h-4.5 text-amber-400" />
          <Moon v-else class="w-4.5 h-4.5 text-slate-700" />
        </button>

        <button @click="toggleMenu" class="h-10 w-10 flex items-center justify-center rounded-xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 text-slate-900 dark:text-white">
          <MenuIcon v-if="!mobileMenu" class="w-5 h-5" />
          <CloseIcon v-else class="w-5 h-5" />
        </button>
      </div>
    </div>

    <!-- Mobile Dropdown Menu -->
    <div v-if="mobileMenu" class="lg:hidden bg-white dark:bg-slate-900 border-b border-slate-200 dark:border-slate-800 px-6 py-4 space-y-3">
      <!-- Switch Hotel Option -->
      <button
        @click.stop="openHotelModal(); closeMenu()"
        type="button"
        class="w-full text-left p-3 rounded-2xl bg-amber-50/80 dark:bg-amber-950/30 border border-amber-500/30 text-xs font-bold text-amber-700 dark:text-amber-400 flex items-center justify-between transition cursor-pointer"
      >
        <span class="flex items-center gap-2 truncate pr-2">
          <Building2 class="w-4 h-4 flex-shrink-0" />
          <span class="truncate">{{ guestHotelStore.hotelName }}</span>
        </span>
        <span class="flex-shrink-0 text-[10px] uppercase tracking-wider font-extrabold px-2.5 py-1 rounded-lg bg-amber-500 text-slate-950">
          Switch
        </span>
      </button>

      <RouterLink
        v-for="menu in menus"
        :key="menu.route"
        :to="menu.route"
        @click="closeMenu"
        class="block text-xs font-black uppercase tracking-wider text-slate-700 dark:text-slate-300 hover:text-amber-500 py-1.5"
      >
        {{ menu.title }}
      </RouterLink>
      <RouterLink
        to="/menu"
        @click="closeMenu"
        class="block text-xs font-black uppercase tracking-wider text-amber-600 dark:text-amber-400 py-1.5 flex items-center gap-2"
      >
        <QrCode class="w-4 h-4" />
        <span>{{ languageStore.t('digital_menu', 'Digital QR Menu & Food Order') }}</span>
      </RouterLink>
      <RouterLink
        to="/rooms"
        @click="closeMenu"
        class="block w-full text-center py-3 rounded-2xl bg-amber-500 text-slate-950 font-black text-xs uppercase tracking-wider mt-2"
      >
        {{ languageStore.t('book_now', 'Book Now') }}
      </RouterLink>
    </div>

    <!-- Hotel Selector Modal rendered with explicit local state -->
    <HotelSelectorModal 
      :open="isHotelModalOpen" 
      @close="isHotelModalOpen = false" 
      @select="handleHotelSelected" 
    />
  </header>
</template>

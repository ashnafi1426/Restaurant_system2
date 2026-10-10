<script setup lang="ts">
import { ref, onMounted, onUnmounted, computed } from 'vue'
import { RouterLink, useRoute } from 'vue-router'
import { useThemeStore } from '@/stores/theme'
import { useGuestHotelStore } from '@/stores/guestHotelStore'
import { useLanguageStore } from '@/stores/language'
import HotelSelectorModal from '@/components/guest/HotelSelectorModal.vue'
import LanguageSelector from '@/components/common/LanguageSelector.vue'
import {
  Sun,
  Moon,
  Menu as MenuIcon,
  X as CloseIcon,
  Building2,
  ChevronDown,
} from 'lucide-vue-next'

const route = useRoute()
const theme = useThemeStore()
const guestHotelStore = useGuestHotelStore()
const languageStore = useLanguageStore()

const mobileMenu = ref(false)
const scrolled = ref(false)
const isHotelModalOpen = ref(false)

function openHotelModal() {
  isHotelModalOpen.value = true
  guestHotelStore.openHotelSelector()
}

function handleHotelSelected() {
  isHotelModalOpen.value = false
}

function isItemActive(itemRoute: string) {
  if (itemRoute === '/') {
    return route.path === '/' || route.name === 'home'
  }
  return route.path === itemRoute || route.path.startsWith(`${itemRoute}/`)
}

// Title Case navigation labels
const menus = computed(() => [
  { title: languageStore.t('home', 'Home'), route: '/' },
  { title: languageStore.t('rooms', 'Rooms'), route: '/rooms' },
  { title: languageStore.t('gallery', 'Gallery'), route: '/gallery' },
  { title: languageStore.t('about', 'About Us'), route: '/about' },
  { title: languageStore.t('contact', 'Contact Us'), route: '/contact' },
])

function toggleMenu() {
  mobileMenu.value = !mobileMenu.value
}

function closeMenu() {
  mobileMenu.value = false
}

function handleScroll() {
  scrolled.value = window.scrollY > 20
}

function handleThemeToggle() {
  theme.toggleTheme()
}

onMounted(() => {
  theme.initializeTheme()
  window.addEventListener('scroll', handleScroll, { passive: true })
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
    class="fixed inset-x-0 top-0 z-50 transition-colors duration-200 bg-white/95 dark:bg-[#0B1B35]/95 backdrop-blur-md border-b border-slate-200/80 dark:border-slate-800 shadow-xs"
  >
    <!-- Container -->
    <div
      class="mx-auto flex h-20 w-full max-w-[1600px] items-center justify-between px-4 sm:px-6 lg:px-8 xl:px-10"
    >
      <!-- ============================================================== -->
      <!-- LEFT SECTION: Brand Identity + Clean Nav Links                 -->
      <!-- ============================================================== -->
      <div class="flex items-center gap-8 lg:gap-10 min-w-0">
        <!-- Brand Link -->
        <RouterLink
          to="/"
          class="flex items-center gap-3 shrink-0 select-none focus:outline-none focus-visible:ring-2 focus-visible:ring-[#E9A11A] rounded-lg group"
        >
          <!-- Logo (White rounded badge so it stays crisp in both Light & Dark mode) -->
          <div
            class="relative w-11 h-11 flex items-center justify-center rounded-xl bg-white p-1 shadow-xs border border-slate-200/60 dark:border-slate-700/60 shrink-0 transition-transform duration-300 group-hover:scale-105"
          >
            <img
              src="/images/Hotel logo.png"
              alt="Hotel Logo"
              class="w-full h-full object-contain select-none"
            />
          </div>

          <!-- Brand Typography -->
          <div class="flex flex-col min-w-0">
            <span
              class="text-[16px] sm:text-[17px] font-bold tracking-tight text-[#0B1B35] dark:text-white leading-tight uppercase truncate"
            >
              {{ guestHotelStore.currentHotel?.name || 'SHERATON' }}
            </span>
            <span
              class="text-[10px] font-semibold text-[#E9A11A] tracking-wider uppercase mt-0.5 leading-none"
            >
              {{
                (guestHotelStore.currentHotel?.city || 'ADDIS ABABA')
                  .toUpperCase()
                  .replace('ADDISS', 'ADDIS')
              }}
            </span>
          </div>
        </RouterLink>

        <!-- Clean Navigation Links with Thin Elegant Underline Indicator -->
        <nav class="hidden lg:flex items-center gap-6 xl:gap-8" aria-label="Main Navigation">
          <RouterLink
            v-for="menu in menus"
            :key="menu.route"
            :to="menu.route"
            class="relative py-1.5 px-1 text-[14px] font-medium transition-colors duration-200 whitespace-nowrap select-none group focus:outline-none focus-visible:ring-2 focus-visible:ring-[#E9A11A] rounded"
            :class="[
              isItemActive(menu.route)
                ? 'text-[#0B1B35] dark:text-white font-semibold'
                : 'text-slate-600 hover:text-[#0B1B35] dark:text-slate-300 dark:hover:text-white',
            ]"
          >
            <span>{{ menu.title }}</span>

            <!-- Subtle Gold Active Underline -->
            <span
              v-if="isItemActive(menu.route)"
              class="absolute -bottom-1 inset-x-0 h-[2px] bg-[#E9A11A] rounded-full transition-all"
            />
            <span
              v-else
              class="absolute -bottom-1 left-1/2 right-1/2 h-[2px] bg-[#E9A11A]/40 rounded-full transition-all duration-200 group-hover:inset-x-0 opacity-0 group-hover:opacity-100"
            />
          </RouterLink>
        </nav>
      </div>

      <!-- ============================================================== -->
      <!-- RIGHT ACTIONS: [Language] -> [Theme] -> [Switch] -> [Book Now] -->
      <!-- ============================================================== -->
      <div class="hidden lg:flex items-center gap-2.5 xl:gap-3 shrink-0">
        <!-- 1. Language Selector -->
        <LanguageSelector variant="header" />

        <!-- 2. Theme Toggle (Pill / Circular border) -->
        <button
          @click="handleThemeToggle"
          type="button"
          class="w-10 h-10 flex items-center justify-center rounded-full border border-slate-200 dark:border-slate-700 bg-transparent hover:bg-slate-100 dark:hover:bg-slate-800 text-slate-700 dark:text-slate-200 hover:border-[#E9A11A] dark:hover:border-[#E9A11A] transition-colors duration-200 cursor-pointer focus:outline-none focus-visible:ring-2 focus-visible:ring-[#E9A11A]"
          :title="
            theme.isDark
              ? languageStore.t('light_mode', 'Light Mode')
              : languageStore.t('dark_mode', 'Dark Mode')
          "
          :aria-label="theme.isDark ? 'Switch to light mode' : 'Switch to dark mode'"
        >
          <Sun v-if="theme.isDark" class="w-4.5 h-4.5 text-[#E9A11A]" />
          <Moon v-else class="w-4.5 h-4.5 text-slate-700 dark:text-slate-300" />
        </button>

        <!-- 3. Switch Hotel (Secondary ghost pill) -->
        <button
          @click.stop="openHotelModal"
          type="button"
          class="inline-flex items-center gap-2 h-10 px-4 rounded-full border border-slate-200 dark:border-slate-700 bg-transparent hover:bg-slate-100 dark:hover:bg-slate-800 text-slate-700 dark:text-slate-200 hover:text-[#0B1B35] dark:hover:text-white hover:border-[#E9A11A] dark:hover:border-[#E9A11A] transition-colors duration-200 cursor-pointer group focus:outline-none focus-visible:ring-2 focus-visible:ring-[#E9A11A]"
          title="Switch Hotel / Property"
        >
          <Building2
            class="w-4 h-4 shrink-0 text-slate-500 dark:text-slate-400 group-hover:text-[#E9A11A] transition-colors"
          />
          <span class="text-[13px] font-medium whitespace-nowrap">{{
            languageStore.t('switch_hotel', 'Switch Hotel')
          }}</span>
          <ChevronDown
            class="w-3.5 h-3.5 shrink-0 text-slate-400 transition-transform duration-200 group-hover:translate-y-0.5"
          />
        </button>

        <!-- 4. Primary CTA: Book Now -->
        <RouterLink
          to="/rooms"
          class="h-10 px-6 flex items-center justify-center rounded-full bg-[#E9A11A] hover:bg-[#d69213] text-white font-medium text-[13px] tracking-wide shadow-md shadow-amber-500/25 hover:shadow-lg hover:shadow-amber-500/35 transition-all duration-200 whitespace-nowrap active:scale-[0.98] focus:outline-none focus-visible:ring-2 focus-visible:ring-[#E9A11A]"
        >
          {{ languageStore.t('book_now', 'Book Now') }}
        </RouterLink>
      </div>

      <!-- ============================================================== -->
      <!-- MOBILE TRIGGER CONTROLS                                        -->
      <!-- ============================================================== -->
      <div class="flex items-center gap-2 lg:hidden">
        <LanguageSelector variant="header" />

        <button
          @click="handleThemeToggle"
          type="button"
          class="flex h-10 w-10 items-center justify-center rounded-full border border-slate-200 dark:border-slate-700 bg-transparent text-slate-700 dark:text-slate-200 hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors"
          :aria-label="theme.isDark ? 'Switch to light mode' : 'Switch to dark mode'"
        >
          <Sun v-if="theme.isDark" class="w-4 h-4 text-[#E9A11A]" />
          <Moon v-else class="w-4 h-4" />
        </button>

        <button
          @click.stop="openHotelModal"
          type="button"
          class="flex h-10 w-10 items-center justify-center rounded-full border border-slate-200 dark:border-slate-700 bg-transparent text-slate-700 dark:text-slate-200 hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors"
          title="Switch Hotel"
          aria-label="Switch Hotel"
        >
          <Building2 class="w-4 h-4 text-slate-500 dark:text-slate-400" />
        </button>

        <button
          @click="toggleMenu"
          type="button"
          class="h-10 w-10 flex items-center justify-center rounded-full border border-slate-200 dark:border-slate-700 bg-transparent text-[#0B1B35] dark:text-white transition-colors hover:bg-slate-100 dark:hover:bg-slate-800 focus:outline-none focus-visible:ring-2 focus-visible:ring-[#E9A11A]"
          :aria-expanded="mobileMenu"
          aria-label="Toggle navigation"
        >
          <MenuIcon v-if="!mobileMenu" class="w-5 h-5" />
          <CloseIcon v-else class="w-5 h-5 text-[#E9A11A]" />
        </button>
      </div>
    </div>

    <!-- ============================================================== -->
    <!-- MOBILE DRAWER                                                  -->
    <!-- ============================================================== -->
    <Transition
      enter-active-class="transition duration-150 ease-out"
      enter-from-class="opacity-0 -translate-y-2"
      enter-to-class="opacity-100 translate-y-0"
      leave-active-class="transition duration-100 ease-in"
      leave-from-class="opacity-100 translate-y-0"
      leave-to-class="opacity-0 -translate-y-2"
    >
      <div
        v-if="mobileMenu"
        class="lg:hidden bg-white dark:bg-[#0B1B35] border-b border-slate-200 dark:border-slate-800 px-4 py-4 space-y-3 shadow-lg"
      >
        <!-- Switch Hotel Card -->
        <button
          @click.stop="
            openHotelModal()
            closeMenu()
          "
          type="button"
          class="w-full flex items-center justify-between p-3.5 rounded-xl border border-slate-200 dark:border-slate-700 text-xs font-medium text-slate-700 dark:text-slate-200 bg-slate-50 dark:bg-slate-800/60 hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors"
        >
          <span class="flex items-center gap-2.5 truncate">
            <Building2 class="w-4 h-4 text-[#E9A11A]" />
            <span class="truncate">{{ guestHotelStore.currentHotel?.name || 'SHERATON' }}</span>
          </span>
          <span class="text-[11px] font-semibold text-[#E9A11A]">Switch</span>
        </button>

        <!-- Navigation Links -->
        <div class="flex flex-col space-y-1">
          <RouterLink
            v-for="menu in menus"
            :key="menu.route"
            :to="menu.route"
            @click="closeMenu"
            class="px-3.5 py-2.5 rounded-lg text-sm transition-colors"
            :class="[
              isItemActive(menu.route)
                ? 'font-semibold text-[#0B1B35] dark:text-white bg-slate-100 dark:bg-slate-800'
                : 'text-slate-600 dark:text-slate-300 hover:text-[#0B1B35] dark:hover:text-white',
            ]"
          >
            {{ menu.title }}
          </RouterLink>
        </div>

        <!-- Book Now Mobile CTA -->
        <RouterLink
          to="/rooms"
          @click="closeMenu"
          class="block w-full text-center py-3 rounded-full bg-[#E9A11A] hover:bg-[#d69213] text-white text-xs font-semibold shadow-md shadow-amber-500/25 transition-all"
        >
          {{ languageStore.t('book_now', 'Book Now') }}
        </RouterLink>
      </div>
    </Transition>

    <HotelSelectorModal
      :open="isHotelModalOpen"
      @close="isHotelModalOpen = false"
      @select="handleHotelSelected"
    />
  </header>
</template>

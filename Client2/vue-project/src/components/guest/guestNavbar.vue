<script setup lang="ts">
import { ref, onMounted, onUnmounted } from 'vue'
import { RouterLink, useRoute } from 'vue-router'
import { useThemeStore } from '../../stores/theme'
import { Sun, Moon, QrCode, Maximize, Minimize, Menu as MenuIcon, X as CloseIcon } from 'lucide-vue-next'

const route = useRoute()
const theme = useThemeStore()

const mobileMenu = ref(false)
const scrolled = ref(false)
const isFullscreen = ref(false)

const menus = [
  { title: 'Home', route: '/' },
  { title: 'Rooms', route: '/rooms' },
  { title: 'Gallery', route: '/gallery' },
  { title: 'About', route: '/about' },
  { title: 'Contact', route: '/contact' },
]

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
    document.documentElement.requestFullscreen().catch(() => {})
    isFullscreen.value = true
  } else {
    if (document.exitFullscreen) {
      document.exitFullscreen().catch(() => {})
      isFullscreen.value = false
    }
  }
}

onMounted(() => {
  theme.initializeTheme()
  window.addEventListener('scroll', handleScroll)
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
    <div class="mx-auto flex h-20 max-w-7xl items-center justify-between px-4 sm:px-6 lg:px-10">
      <!-- Logo -->
      <RouterLink to="/" class="flex items-center gap-3">
        <div class="flex h-11 w-11 items-center justify-center rounded-2xl border border-amber-500/40 bg-amber-500/10 text-amber-600 dark:text-amber-400 font-black text-xl shadow-xs">
          H
        </div>

        <div>
          <h2 class="text-lg font-black tracking-wide text-slate-900 dark:text-white transition-colors">
            Grand Horizon
          </h2>
          <p class="text-[9px] uppercase tracking-[3px] font-extrabold text-amber-600 dark:text-amber-400">
            Luxury Hotel
          </p>
        </div>
      </RouterLink>

      <!-- Desktop Navigation -->
      <nav class="hidden items-center gap-8 lg:flex">
        <RouterLink
          v-for="menu in menus"
          :key="menu.route"
          :to="menu.route"
          class="text-xs uppercase tracking-wider font-extrabold transition-colors"
          :class="[
            route.path === menu.route
              ? 'text-amber-600 dark:text-amber-400 font-black'
              : 'text-slate-700 dark:text-slate-300 hover:text-amber-600 dark:hover:text-amber-400'
          ]"
        >
          {{ menu.title }}
        </RouterLink>
      </nav>

      <!-- Action Control Buttons & Book Now -->
      <div class="hidden items-center gap-3 lg:flex">
        <!-- Digital QR Menu Icon Button -->
        <RouterLink
          to="/menu"
          class="flex h-10 w-10 items-center justify-center rounded-2xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-900 text-slate-700 dark:text-slate-200 hover:border-amber-500 hover:text-amber-500 transition cursor-pointer shadow-xs"
          title="Digital QR Room Service & Restaurant Menu"
        >
          <QrCode class="w-5 h-5 text-amber-500" />
        </RouterLink>

        <!-- Fullscreen Toggle Button (From Screenshot) -->
        <button
          @click="toggleFullscreen"
          type="button"
          class="flex h-10 w-10 items-center justify-center rounded-2xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-900 text-slate-700 dark:text-slate-200 hover:border-amber-500 hover:text-amber-500 transition cursor-pointer shadow-xs"
          :title="isFullscreen ? 'Exit Fullscreen' : 'Fullscreen View'"
        >
          <Maximize v-if="!isFullscreen" class="w-5 h-5" />
          <Minimize v-else class="w-5 h-5 text-amber-500" />
        </button>

        <!-- Interactive Theme Toggle Button -->
        <button
          @click="handleThemeToggle"
          type="button"
          class="flex items-center gap-2 px-3.5 py-2 rounded-2xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-900 text-slate-800 dark:text-slate-200 hover:border-amber-500 transition cursor-pointer shadow-xs font-black text-xs"
          :title="theme.isDark ? 'Switch to Light Mode' : 'Switch to Dark Mode'"
        >
          <template v-if="theme.isDark">
            <Sun class="w-4 h-4 text-amber-400" />
            <span>Light Mode</span>
          </template>

          <template v-else>
            <Moon class="w-4 h-4 text-slate-700" />
            <span>Dark Mode</span>
          </template>
        </button>

        <!-- Book Now Button -->
        <RouterLink
          to="/rooms"
          class="px-5 py-2.5 rounded-2xl bg-amber-500 hover:bg-amber-600 text-slate-950 font-black text-xs uppercase tracking-wider shadow-md shadow-amber-500/20 transition"
        >
          Book Now
        </RouterLink>
      </div>

      <!-- Mobile Controls -->
      <div class="flex items-center gap-2 lg:hidden">
        <!-- QR Menu Shortcut -->
        <RouterLink
          to="/menu"
          class="flex h-10 w-10 items-center justify-center rounded-2xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-900 text-amber-500"
          title="Digital QR Menu"
        >
          <QrCode class="w-5 h-5" />
        </RouterLink>

        <!-- Theme Toggle Button -->
        <button
          @click="handleThemeToggle"
          type="button"
          class="flex items-center gap-1.5 px-3 py-2 rounded-2xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-900 text-slate-800 dark:text-slate-200 text-xs font-bold"
        >
          <Sun v-if="theme.isDark" class="w-4 h-4 text-amber-400" />
          <Moon v-else class="w-4 h-4 text-slate-700" />
        </button>

        <button @click="toggleMenu" class="p-2 rounded-2xl text-slate-900 dark:text-white">
          <MenuIcon v-if="!mobileMenu" class="w-7 h-7" />
          <CloseIcon v-else class="w-7 h-7" />
        </button>
      </div>
    </div>

    <!-- Mobile Dropdown Menu -->
    <div v-if="mobileMenu" class="lg:hidden bg-white dark:bg-slate-900 border-b border-slate-200 dark:border-slate-800 px-6 py-4 space-y-3">
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
        <span>Digital QR Menu & Food Order</span>
      </RouterLink>
      <RouterLink
        to="/rooms"
        @click="closeMenu"
        class="block w-full text-center py-3 rounded-2xl bg-amber-500 text-slate-950 font-black text-xs uppercase tracking-wider mt-2"
      >
        Book Now
      </RouterLink>
    </div>
  </header>
</template>

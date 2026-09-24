<script setup lang="ts">
import { ref, onUnmounted } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '../../stores/auth'
import { useHotelStore } from '../../stores/hotelStore'
import { useThemeStore } from '../../stores/theme'
import { useSidebarStore } from '../../stores/sidebarStore'
import { useLanguageStore } from '@/stores/language'
import LanguageSelector from '@/components/common/LanguageSelector.vue'
import NotificationCenter from '@/components/reception/NotificationCenter.vue'
import { Sun, Moon, PanelLeft, Maximize, Minimize, Building2, ChevronDown, Check, Search } from 'lucide-vue-next'

const auth = useAuthStore()
const hotelStore = useHotelStore()
const router = useRouter()
const themeStore = useThemeStore()
const sidebarStore = useSidebarStore()
const languageStore = useLanguageStore()
const profileOpen = ref(false)
const isFullscreen = ref(false)
const hotelDropdownOpen = ref(false)

const handleSwitchHotel = async (hotelId: string) => {
  hotelDropdownOpen.value = false
  const success = await hotelStore.switchHotel(hotelId)
  if (success) {
    const newRole = String(hotelStore.currentHotel?.role || auth.currentRole || 'admin').toLowerCase().trim()

    let targetRoute = '/admin'
    if (auth.isPlatformAdmin || newRole === 'admin') {
      targetRoute = '/admin'
    } else if (newRole === 'manager') {
      targetRoute = '/manager'
    } else if (newRole === 'receptionist') {
      targetRoute = '/receptionist'
    } else if (newRole === 'cashier') {
      targetRoute = '/cashier'
    } else if (newRole === 'chef' || newRole === 'kitchen') {
      targetRoute = '/chef'
    } else if (newRole === 'waiter') {
      targetRoute = '/waiter'
    } else {
      targetRoute = `/${newRole}`
    }

    const currentPath = router.currentRoute.value.path
    const canStayOnCurrent = auth.isPlatformAdmin || 
      (newRole === 'admin') || 
      (newRole === 'manager' && (currentPath.startsWith('/manager') || currentPath.startsWith('/reviews'))) ||
      (newRole === 'receptionist' && (currentPath.startsWith('/receptionist') || currentPath === '/reports' || currentPath.startsWith('/reservations') || currentPath.startsWith('/guests'))) ||
      (newRole === 'cashier' && currentPath.startsWith('/cashier')) ||
      (newRole === 'chef' && currentPath.startsWith('/chef')) ||
      (newRole === 'kitchen' && (currentPath.startsWith('/kitchen') || currentPath.startsWith('/chef')))

    if (canStayOnCurrent) {
      await router.replace({ path: currentPath, query: { ...router.currentRoute.value.query, _t: Date.now().toString() } })
      return
    }

    if (router.currentRoute.value.path !== targetRoute) {
      await router.push(targetRoute)
    } else {
      await router.replace({ path: targetRoute, query: { _t: Date.now().toString() } })
    }
  }
}

const toggleProfile = () => {
  profileOpen.value = !profileOpen.value
}

const handleThemeToggle = () => {
  themeStore.toggleTheme()
}

const toggleFullscreen = async () => {
  try {
    if (!document.fullscreenElement) {
      await document.documentElement.requestFullscreen()
      isFullscreen.value = true
    } else {
      if (document.exitFullscreen) {
        await document.exitFullscreen()
        isFullscreen.value = false
      }
    }
  } catch (error) {
    console.error('[Navbar] Fullscreen toggle error:', error)
  }
}

const handleFullscreenChange = () => {
  isFullscreen.value = !!document.fullscreenElement
}

if (typeof document !== 'undefined') {
  document.addEventListener('fullscreenchange', handleFullscreenChange)
}
onUnmounted(() => {
  if (typeof document !== 'undefined') {
    document.removeEventListener('fullscreenchange', handleFullscreenChange)
  }
})

const logout = async () => {
  profileOpen.value = false
  await auth.logout()
  router.push('/login')
}

const handleHamburgerClick = () => {
  const screenWidth = window.innerWidth
  
  if (screenWidth >= 1024) {
    sidebarStore.toggleCollapse()
  } else {
    sidebarStore.toggleMobile()
  }
}
</script>
<template>
  <header
    class="sticky top-0 z-40 h-16 w-full bg-white dark:bg-slate-900 border-b border-slate-200 dark:border-slate-800 px-3 sm:px-6 flex items-center justify-between gap-2 sm:gap-3 transition-colors"
  >
    <!-- Left Section: Toggle, Title, Hotel Switcher -->
    <div class="flex items-center gap-2 sm:gap-3.5 min-w-0 shrink">
      <button
        @click="handleHamburgerClick"
        class="flex lg:hidden items-center justify-center w-9 h-9 sm:w-10 sm:h-10 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 hover:bg-slate-50 dark:hover:bg-slate-800 transition-colors shrink-0 cursor-pointer"
        title="Toggle Sidebar"
      >
        <PanelLeft class="w-4.5 h-4.5 text-slate-600 dark:text-slate-300" :stroke-width="2" />
      </button>

      <div class="min-w-0 shrink">
        <h1 class="text-sm sm:text-lg lg:text-xl font-bold text-slate-800 dark:text-slate-100 truncate whitespace-nowrap leading-tight">
          {{ languageStore.t('dashboard', 'Dashboard') }}
        </h1>

        <p class="text-[11px] text-slate-400 dark:text-slate-500 whitespace-nowrap hidden 2xl:block leading-tight">
          {{ languageStore.t('hotel_management_system', 'Hotel Management System') }}
        </p>
      </div>

      <div v-if="hotelStore.currentHotel" class="relative hidden sm:block shrink-0">
        <button
          v-if="hotelStore.hasMultipleHotels"
          @click="hotelDropdownOpen = !hotelDropdownOpen"
          class="flex items-center gap-1.5 px-2.5 py-1.5 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 hover:bg-slate-100 dark:hover:bg-slate-700 text-xs font-semibold text-slate-700 dark:text-slate-200 transition-colors shadow-xs whitespace-nowrap"
        >
          <Building2 class="w-3.5 h-3.5 text-emerald-600 dark:text-emerald-400 shrink-0" />
          <span class="max-w-[120px] lg:max-w-[180px] truncate">{{ hotelStore.hotelName }}</span>
          <ChevronDown class="w-3.5 h-3.5 text-slate-400 shrink-0" />
        </button>
        <div
          v-else
          class="flex items-center gap-1.5 px-2.5 py-1.5 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-xs font-semibold text-slate-700 dark:text-slate-200 shadow-xs whitespace-nowrap"
        >
          <Building2 class="w-3.5 h-3.5 text-emerald-600 dark:text-emerald-400 shrink-0" />
          <span class="max-w-[120px] lg:max-w-[180px] truncate">{{ hotelStore.hotelName }}</span>
        </div>

        <div
          v-if="hotelDropdownOpen && hotelStore.hasMultipleHotels"
          class="absolute left-0 mt-2 w-64 bg-white dark:bg-slate-800 rounded-xl shadow-xl border border-slate-200 dark:border-slate-700 py-2 z-50 animate-in fade-in slide-in-from-top-2 duration-150"
        >
          <div class="px-3 py-1.5 text-xs font-semibold text-slate-400 dark:text-slate-500 uppercase tracking-wider">
            {{ languageStore.t('switch_hotel', 'Switch Hotel') }}
          </div>
          <button
            v-for="h in hotelStore.availableHotels"
            :key="h.id"
            @click="handleSwitchHotel(h.id)"
            class="w-full px-3 py-2 text-left flex items-center justify-between hover:bg-slate-50 dark:hover:bg-slate-700/50 text-xs sm:text-sm transition-colors"
            :class="h.id === hotelStore.hotelId ? 'text-emerald-600 dark:text-emerald-400 font-semibold bg-emerald-50/50 dark:bg-emerald-950/20' : 'text-slate-700 dark:text-slate-300'"
          >
            <span class="truncate">{{ h.name }}</span>
            <Check v-if="h.id === hotelStore.hotelId" class="w-4 h-4 text-emerald-600 dark:text-emerald-400 flex-shrink-0" />
          </button>
        </div>
      </div>
    </div>

    <!-- Right Section: Search, Notifications, Language, Controls, Profile -->
    <div class="flex items-center gap-1.5 sm:gap-2 shrink-0 ml-auto">
      <!-- Responsive Search Input -->
      <div class="relative hidden md:block">
        <Search class="absolute left-3 top-1/2 -translate-y-1/2 w-3.5 h-3.5 text-slate-400 dark:text-slate-500 pointer-events-none" />

        <input
          type="text"
          :placeholder="languageStore.t('search', 'Search...')"
          class="w-32 sm:w-40 md:w-44 lg:w-52 h-10 rounded-xl border border-slate-200 dark:border-slate-700 py-1.5 pl-8 pr-3 text-xs outline-none transition focus:border-amber-500 dark:focus:border-amber-500 bg-slate-50/80 dark:bg-slate-800 text-slate-900 dark:text-slate-100 placeholder-slate-400"
        />
      </div>

      <NotificationCenter />

      <!-- Language Selector -->
      <LanguageSelector variant="compact" />

      <!-- Fullscreen Button -->
      <button
        @click="toggleFullscreen"
        class="hidden sm:flex h-10 w-10 items-center justify-center rounded-xl border border-slate-200 dark:border-slate-700 hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors shrink-0 cursor-pointer shadow-xs bg-white dark:bg-slate-900"
        :title="isFullscreen ? 'Exit fullscreen (ESC)' : 'Enter fullscreen'"
      >
        <Maximize
          v-if="!isFullscreen"
          class="w-4.5 h-4.5 text-slate-600 dark:text-slate-400 transition-transform duration-300"
        />
        <Minimize
          v-else
          class="w-4.5 h-4.5 text-amber-500 transition-transform duration-300"
        />
      </button>

      <!-- Theme Toggle Button -->
      <button
        @click="handleThemeToggle"
        class="flex h-10 w-10 items-center justify-center rounded-xl border border-slate-200 dark:border-slate-700 hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors shrink-0 cursor-pointer shadow-xs bg-white dark:bg-slate-900"
        :title="themeStore.isDark ? 'Switch to light mode' : 'Switch to dark mode'"
      >
        <Sun
          v-if="!themeStore.isDark"
          class="w-4.5 h-4.5 text-slate-600 transition-transform duration-300"
        />
        <Moon
          v-else
          class="w-4.5 h-4.5 text-yellow-400 transition-transform duration-300"
        />
      </button>

      <!-- User Profile Button (Guaranteed Visible with shrink-0) -->
      <div class="relative shrink-0">
        <button
          @click="toggleProfile"
          class="flex items-center gap-2 h-10 rounded-xl border border-slate-200 dark:border-slate-700 px-2 sm:px-2.5 transition hover:bg-slate-100 dark:hover:bg-slate-800 shrink-0 cursor-pointer shadow-xs bg-white dark:bg-slate-900"
          title="User profile"
        >
          <div
            class="flex h-7 w-7 items-center justify-center rounded-lg bg-amber-500/10 text-amber-600 dark:text-amber-400 text-xs font-black shrink-0 border border-amber-500/25"
          >
            {{ auth.user?.name?.charAt(0).toUpperCase() || 'A' }}
          </div>

          <div class="hidden xl:block text-left min-w-0 max-w-[90px]">
            <h3 class="text-xs font-bold text-slate-800 dark:text-slate-100 truncate leading-tight">
              {{ auth.user?.name || 'Admin' }}
            </h3>

            <p class="text-[10px] text-slate-400 dark:text-slate-500 truncate leading-tight capitalize">
              {{ languageStore.t(auth.currentRole || 'Administrator', auth.currentRole || 'Administrator') }}
            </p>
          </div>

          <ChevronDown class="w-3 h-3 text-slate-400 shrink-0" />
        </button>

        <div
          v-if="profileOpen"
          class="absolute right-0 mt-2 sm:mt-3 w-56 sm:w-60 md:w-64 overflow-hidden rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 shadow-xl"
        >
          <div class="border-b border-slate-200 dark:border-slate-700 p-3 sm:p-4 md:p-5">
            <div class="flex items-center gap-2 sm:gap-3">
              <div
                class="flex h-10 w-10 sm:h-12 sm:w-12 items-center justify-center rounded-full bg-slate-200 dark:bg-slate-700 font-semibold text-slate-700 dark:text-slate-200 text-sm sm:text-base flex-shrink-0"
              >
                {{ auth.user?.name?.charAt(0).toUpperCase() }}
              </div>

              <div class="min-w-0">
                <h4 class="font-semibold text-slate-800 dark:text-slate-100 text-xs sm:text-sm truncate">
                  {{ auth.user?.name }}
                </h4>

                <p class="text-xs sm:text-sm text-slate-500 dark:text-slate-400 truncate">
                  {{ auth.user?.email }}
                </p>
              </div>
            </div>
          </div>
          <button
            class="flex w-full items-center gap-2 sm:gap-3 px-3 sm:px-4 md:px-5 py-2 sm:py-3 text-left text-xs sm:text-sm text-slate-700 dark:text-slate-300 transition hover:bg-slate-100 dark:hover:bg-slate-800"
          >
            {{ languageStore.t('my_profile', 'Profile') }}
          </button>

          <button
            class="flex w-full items-center gap-2 sm:gap-3 px-3 sm:px-4 md:px-5 py-2 sm:py-3 text-left text-xs sm:text-sm text-slate-700 dark:text-slate-300 transition hover:bg-slate-100 dark:hover:bg-slate-800"
          >
            {{ languageStore.t('settings', 'Settings') }}
          </button>

          <div class="border-t border-slate-200 dark:border-slate-700"></div>

          <button
            @click="logout"
            class="flex w-full items-center gap-2 sm:gap-3 px-3 sm:px-4 md:px-5 py-2 sm:py-3 text-left text-xs sm:text-sm text-slate-700 dark:text-slate-300 transition hover:bg-slate-100 dark:hover:bg-slate-800"
          >
            {{ languageStore.t('logout', 'Logout') }}
          </button>
        </div>
      </div>
    </div>
  </header>
</template>

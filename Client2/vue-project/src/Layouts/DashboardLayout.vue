<script setup lang="ts">
import { ref, onMounted, onUnmounted } from 'vue'
import Sidebar from '../components/dashboard/Sidebar.vue'
import Navbar from '../components/dashboard/Navbar.vue'
import { useRouter } from 'vue-router'
import { useThemeStore } from '../stores/theme'
import { useSidebarStore } from '../stores/sidebarStore'
import { useHotelStore } from '../stores/hotelStore'
import { useAuthStore } from '../stores/auth'
import { useLanguageStore } from '../stores/language'
import { platformService } from '../services/platformService'
import { ShieldAlert, LogOut, X } from 'lucide-vue-next'

const themeStore = useThemeStore()
const sidebarStore = useSidebarStore()
const hotelStore = useHotelStore()
const authStore = useAuthStore()
const languageStore = useLanguageStore()
const router = useRouter()

onMounted(() => {
  themeStore.initTheme()
})
const exitPlatformView = async () => {
  try {
    await platformService.exitHotelViewMode()
  } catch (e) {
    console.error('[DashboardLayout] Error exiting hotel view mode:', e)
  }
  hotelStore.exitPlatformViewMode()
  router.push('/admin/hotels')
}

const closeMobileSidebar = () => {
  if (window.innerWidth < 1024) {
    sidebarStore.closeMobile()
  }
}
</script>

<template>
  <div
    class="h-screen flex bg-white dark:bg-slate-950 overflow-hidden transition-colors duration-300"
  >
    <!-- Mobile Backdrop -->
    <Transition
      enter-active-class="transition-opacity duration-300 ease-out"
      enter-from-class="opacity-0"
      enter-to-class="opacity-100"
      leave-active-class="transition-opacity duration-200 ease-in"
      leave-from-class="opacity-100"
      leave-to-class="opacity-0"
    >
      <div
        v-if="sidebarStore.isMobileOpen"
        @click="sidebarStore.closeMobile()"
        class="fixed inset-0 bg-slate-950/60 backdrop-blur-xs z-40 lg:hidden"
        role="presentation"
      ></div>
    </Transition>

    <!-- Sidebar Wrapper -->
    <div
      :class="[
        'h-screen flex flex-col flex-shrink-0 transition-all duration-300 ease-in-out',
        'fixed inset-y-0 left-0 z-50 w-72 max-w-[85vw] shadow-2xl lg:shadow-none',
        'lg:static lg:sticky lg:top-0 lg:left-0 lg:z-30',
        sidebarStore.isCollapsed ? 'lg:w-20' : 'lg:w-72',
        sidebarStore.isMobileOpen ? 'translate-x-0' : '-translate-x-full lg:translate-x-0',
      ]"
    >
      <Sidebar :key="hotelStore.hotelId || authStore.currentHotel?.id || 'sidebar-main'" @navigate="closeMobileSidebar" />
    </div>
    <div class="flex-1 flex flex-col min-w-0 overflow-hidden">
      <Navbar />

      <div
        v-if="hotelStore.isViewingAsPlatformAdmin"
        class="bg-amber-500 text-slate-950 font-black px-4 sm:px-6 py-2 flex items-center justify-between shadow-md text-xs tracking-wide flex-shrink-0 z-20 animate-in fade-in duration-200"
      >
        <div class="flex items-center gap-2">
          <ShieldAlert class="w-4 h-4 text-slate-950 flex-shrink-0" />
          <span>⚠ PLATFORM ADMIN VIEW &mdash; Currently viewing: <strong class="underline">{{ hotelStore.hotelName }}</strong></span>
          <span class="hidden md:inline text-[11px] font-semibold opacity-90">(Audit logging active for all operations)</span>
        </div>
        <button
          @click="exitPlatformView"
          class="px-3 py-1 bg-slate-950 hover:bg-slate-900 text-white rounded-lg transition font-bold text-xs flex items-center gap-1.5 cursor-pointer shadow-xs"
        >
          <LogOut class="w-3.5 h-3.5" />
          <span>{{ languageStore.t('Exit Hotel View', 'Exit Hotel View') }}</span>
        </button>
      </div>

      <main class="flex-1 overflow-y-auto bg-slate-50/50 dark:bg-slate-950 transition-colors duration-300">
        <div :key="hotelStore.hotelId" class="w-full px-4 sm:px-6 lg:px-8 py-6">
          <div>
            <slot name="header"></slot>
          </div>

          <slot></slot>
        </div>
      </main>

      <footer class="border-t border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900/80 backdrop-blur-sm px-4 sm:px-6 lg:px-8 py-3 transition-colors flex-shrink-0">
        <div
          class="w-full flex flex-col sm:flex-row items-center justify-between gap-2 text-xs text-slate-500 dark:text-slate-400"
        >
          <div class="flex flex-col sm:flex-row items-center gap-2 sm:gap-4 text-center sm:text-left">
            <span class="whitespace-nowrap">&copy; 2024 {{ languageStore.t('hotel_management_system', 'Hotel Management System') }}</span>
            <span class="hidden sm:inline text-slate-300 dark:text-slate-600">•</span>
            <span class="flex items-center justify-center gap-1">
              <span class="relative flex h-2 w-2">
                <span
                  class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 dark:bg-emerald-500 opacity-75"
                ></span>
                <span class="relative inline-flex rounded-full h-2 w-2 bg-emerald-500 dark:bg-emerald-400"></span>
              </span>
              <span class="whitespace-nowrap">{{ languageStore.t('all_systems_operational', 'All systems operational') }}</span>
            </span>
          </div>
          <div class="flex items-center gap-3 md:gap-4 font-bold">
            <router-link to="/contact" class="hover:text-slate-700 dark:hover:text-slate-200 transition-colors whitespace-nowrap">{{ languageStore.t('privacy', 'Privacy') }}</router-link>
            <router-link to="/contact" class="hover:text-slate-700 dark:hover:text-slate-200 transition-colors whitespace-nowrap">{{ languageStore.t('terms', 'Terms') }}</router-link>
            <router-link to="/contact" class="hover:text-slate-700 dark:hover:text-slate-200 transition-colors whitespace-nowrap hidden sm:inline">{{ languageStore.t('support', 'Support') }}</router-link>
            <span class="text-slate-300 dark:text-slate-600 hidden sm:inline">|</span>
            <span class="text-slate-400 dark:text-slate-500 whitespace-nowrap">v2.0.0</span>
          </div>
        </div>
      </footer>
    </div>
  </div>
</template>

<style scoped>
main {
  scrollbar-gutter: stable;
}

main::-webkit-scrollbar {
  width: 6px;
  height: 6px;
}

main::-webkit-scrollbar-track {
  background: transparent;
}

main::-webkit-scrollbar-thumb {
  background: #cbd5e1;
  border-radius: 3px;
}

main::-webkit-scrollbar-thumb:hover {
  background: #94a3b8;
}

.dark main::-webkit-scrollbar-thumb {
  background: #475569;
}

.dark main::-webkit-scrollbar-thumb:hover {
  background: #64748b;
}

main > div {
  animation: fadeInUp 0.4s ease-out forwards;
}

@keyframes fadeInUp {
  from {
    opacity: 0;
    transform: translateY(10px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}

button:focus-visible {
  outline: 2px solid #3b82f6;
  outline-offset: 2px;
}
</style>

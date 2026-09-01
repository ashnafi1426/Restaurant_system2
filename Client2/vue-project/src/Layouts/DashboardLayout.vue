<script setup lang="ts">
import { ref, onMounted } from 'vue'
import Sidebar from '../components/dashboard/Sidebar.vue'
import Navbar from '../components/dashboard/Navbar.vue'
import { useRouter } from 'vue-router'
import { useThemeStore } from '../stores/theme'
import { useSidebarStore } from '../stores/sidebarStore'
import { useHotelStore } from '../stores/hotelStore'
import { useAuthStore } from '../stores/auth'
import { platformService } from '../services/platformService'
import { ShieldAlert, LogOut, KeyRound, X } from 'lucide-vue-next'

const themeStore = useThemeStore()
const sidebarStore = useSidebarStore()
const hotelStore = useHotelStore()
const authStore = useAuthStore()
const router = useRouter()

const showPasswordNotice = ref(true)

onMounted(() => {
  themeStore.initTheme()
})

const exitPlatformView = async () => {
  try {
    await platformService.exitHotelViewMode()
  } catch (e) {
    // ignore
  }
  hotelStore.exitPlatformViewMode()
  router.push('/admin/hotels')
}

// Close sidebar when navigating (only on mobile)
const closeMobileSidebar = () => {
  // Only close on mobile screens (less than lg breakpoint: 1024px)
  if (window.innerWidth < 1024) {
    sidebarStore.closeMobile()
  }
}
</script>

<template>
  <div
    class="h-screen flex bg-white dark:bg-slate-950 overflow-hidden transition-colors duration-300"
  >
    <!-- ============ MOBILE OVERLAY ============ -->
    <!-- Only show on mobile when sidebar is open -->
    <div
      v-if="sidebarStore.isMobileOpen"
      @click="sidebarStore.closeMobile()"
      class="fixed inset-0 bg-black/50 z-30 lg:hidden"
      role="presentation"
    ></div>

    <!-- ============ SIDEBAR ============ -->
    <!-- Desktop: collapsible width | Mobile: fixed overlay -->
    <div
      :class="[
        'h-screen bg-slate-900 dark:bg-slate-950 flex flex-col flex-shrink-0 shadow-sm dark:shadow-black transition-all duration-300 ease-in-out',
        // Desktop behavior (lg and up) - dynamic width based on collapse state
        'lg:static lg:sticky lg:top-0 lg:left-0',
        sidebarStore.isCollapsed ? 'lg:w-20' : 'lg:w-72',
        // Mobile behavior (below lg) - fixed width with transform
        'fixed inset-y-0 left-0 z-40 w-64',
        sidebarStore.isMobileOpen ? 'translate-x-0' : '-translate-x-full lg:translate-x-0',
      ]"
    >
      <Sidebar @navigate="closeMobileSidebar" />
    </div>
    <div class="flex-1 flex flex-col min-w-0 overflow-hidden">
      <Navbar />

      <!-- ===== PLATFORM ADMIN VIEW WARNING BANNER ===== -->
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
          <span>Exit Hotel View</span>
        </button>
      </div>

      <!-- ===== NON-BLOCKING SYSTEM PASSWORD NOTICE BANNER ===== -->
      <div
        v-if="authStore.mustChangePassword && showPasswordNotice"
        class="bg-indigo-500/10 border-b border-indigo-500/20 px-4 sm:px-6 py-2.5 flex items-center justify-between text-indigo-700 dark:text-indigo-300 text-xs flex-shrink-0 z-10 animate-in fade-in duration-200"
      >
        <div class="flex items-center gap-2">
          <KeyRound class="w-4 h-4 text-indigo-500 flex-shrink-0" />
          <span>
            You logged in with a system-generated password. You can set your permanent password anytime on your
            <router-link to="/admin/profile?tab=security" class="font-bold underline hover:text-indigo-900 dark:hover:text-indigo-100">
              Profile page
            </router-link>.
          </span>
        </div>
        <div class="flex items-center gap-2.5">
          <router-link
            to="/admin/profile?tab=security"
            class="px-2.5 py-1 rounded-lg bg-indigo-600 hover:bg-indigo-500 text-white font-bold text-[11px] transition shadow-xs"
          >
            Go to Profile
          </router-link>
          <button
            @click="showPasswordNotice = false"
            class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 cursor-pointer p-0.5"
            title="Dismiss notice"
          >
            <X class="w-3.5 h-3.5" />
          </button>
        </div>
      </div>

      <!-- ===== MAIN CONTENT ===== -->
      <main class="flex-1 overflow-y-auto bg-slate-50/50 dark:bg-slate-950 transition-colors duration-300">
        <!-- Content Container -->
        <div class="w-full px-4 sm:px-6 lg:px-8 py-6">
          <!-- Page Header (Optional) -->
          <div>
            <slot name="header"></slot>
          </div>

          <!-- Main Slot Content -->
          <slot></slot>
        </div>
      </main>

      <!-- ===== FOOTER ===== -->
      <footer class="border-t border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900/80 backdrop-blur-sm px-4 sm:px-6 lg:px-8 py-3 transition-colors flex-shrink-0">
        <div
          class="w-full flex flex-col sm:flex-row items-center justify-between gap-2 text-xs text-slate-500 dark:text-slate-400"
        >
          <div class="flex flex-col sm:flex-row items-center gap-2 sm:gap-4 text-center sm:text-left">
            <span class="whitespace-nowrap">&copy; 2024 Hotel Management System</span>
            <span class="hidden sm:inline text-slate-300 dark:text-slate-600">•</span>
            <span class="flex items-center justify-center gap-1">
              <span class="relative flex h-2 w-2">
                <span
                  class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 dark:bg-emerald-500 opacity-75"
                ></span>
                <span class="relative inline-flex rounded-full h-2 w-2 bg-emerald-500 dark:bg-emerald-400"></span>
              </span>
              <span class="whitespace-nowrap">All systems operational</span>
            </span>
          </div>
          <div class="flex items-center gap-3 md:gap-4 font-bold">
            <router-link to="/contact" class="hover:text-slate-700 dark:hover:text-slate-200 transition-colors whitespace-nowrap">Privacy</router-link>
            <router-link to="/contact" class="hover:text-slate-700 dark:hover:text-slate-200 transition-colors whitespace-nowrap">Terms</router-link>
            <router-link to="/contact" class="hover:text-slate-700 dark:hover:text-slate-200 transition-colors whitespace-nowrap hidden sm:inline">Support</router-link>
            <span class="text-slate-300 dark:text-slate-600 hidden sm:inline">|</span>
            <span class="text-slate-400 dark:text-slate-500 whitespace-nowrap">v2.0.0</span>
          </div>
        </div>
      </footer>
    </div>
  </div>
</template>

<style scoped>
/* Force scrollbar to always be visible to prevent layout shift */
main {
  scrollbar-gutter: stable;
}

/* Smooth scrollbar */
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

/* Dark mode scrollbar */
.dark main::-webkit-scrollbar-thumb {
  background: #475569;
}

.dark main::-webkit-scrollbar-thumb:hover {
  background: #64748b;
}

/* Fade in content animation */
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

/* Button focus styles */
button:focus-visible {
  outline: 2px solid #3b82f6;
  outline-offset: 2px;
}
</style>

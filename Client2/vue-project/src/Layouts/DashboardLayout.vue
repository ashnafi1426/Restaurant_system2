<script setup lang="ts">
import { onMounted } from 'vue'
import Sidebar from '../components/dashboard/Sidebar.vue'
import Navbar from '../components/dashboard/Navbar.vue'
import { useThemeStore } from '../stores/theme'
import { useSidebarStore } from '../stores/sidebarStore'

const themeStore = useThemeStore()
const sidebarStore = useSidebarStore()
onMounted(() => {
  themeStore.initTheme()
})

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

    <!-- ============ MAIN CONTENT AREA ============ -->
    <div class="flex-1 flex flex-col min-w-0 overflow-hidden">
      <!-- ===== NAVBAR ===== -->
      <Navbar />

      <!-- ===== MAIN CONTENT ===== -->
      <main class="flex-1 overflow-y-scroll p-4 md:p-6 lg:p-8 bg-white dark:bg-slate-950 transition-colors duration-300">
        <!-- Content Container -->
        <div class="max-w-7xl mx-auto">
          <!-- Page Header (Optional) -->
          <div class="mb-6">
            <slot name="header"></slot>
          </div>

          <!-- Main Slot Content -->
          <slot></slot>
        </div>
      </main>

      <!-- ===== FOOTER ===== -->
      <footer class="border-t border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900/80 backdrop-blur-sm px-6 py-3 transition-colors flex-shrink-0">
        <div
          class="max-w-7xl mx-auto flex flex-col sm:flex-row items-center justify-between gap-2 text-xs text-slate-500 dark:text-slate-400"
        >
          <div class="flex items-center gap-4">
            <span>&copy; 2024 Hotel Management System</span>
            <span class="hidden sm:inline text-slate-300 dark:text-slate-600">•</span>
            <span class="flex items-center gap-1">
              <span class="relative flex h-2 w-2">
                <span
                  class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 dark:bg-emerald-500 opacity-75"
                ></span>
                <span class="relative inline-flex rounded-full h-2 w-2 bg-emerald-500 dark:bg-emerald-400"></span>
              </span>
              All systems operational
            </span>
          </div>
          <div class="flex items-center gap-4">
            <a href="#" class="hover:text-slate-700 dark:hover:text-slate-200 hover:underline transition-colors">Privacy</a>
            <a href="#" class="hover:text-slate-700 dark:hover:text-slate-200 hover:underline transition-colors">Terms</a>
            <a href="#" class="hover:text-slate-slate-700 dark:hover:text-slate-200 hover:underline transition-colors">Support</a>
            <span class="text-slate-300 dark:text-slate-600">|</span>
            <span class="text-slate-400 dark:text-slate-500">v2.0.0</span>
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

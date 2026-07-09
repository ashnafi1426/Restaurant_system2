<script setup lang="ts">
import Sidebar from '../components/dashboard/Sidebar.vue'
import Navbar from '../components/dashboard/Navbar.vue'
</script>

<template>
  <div
    class="h-screen flex bg-gradient-to-br from-slate-50 via-white to-blue-50/30 overflow-hidden"
  >
    <!-- Sidebar -->
    <Sidebar />

    <!-- Main Content Area -->
    <div class="flex-1 flex flex-col min-w-0">
      <!-- Navbar -->
      <Navbar />

      <!-- Main Content -->
      <main class="flex-1 overflow-y-auto p-4 md:p-6 lg:p-8">
        <!-- Content Container -->
        <div class="max-w-7xl mx-auto">
          <!-- Page Header (Optional - can be overridden by slot) -->
          <div class="mb-6">
            <slot name="header"> </slot>
          </div>

          <!-- Main Slot Content -->
          <slot />
        </div>
      </main>

      <!-- Footer -->
      <footer class="border-t border-slate-200/60 bg-white/50 backdrop-blur-sm px-6 py-3">
        <div
          class="max-w-7xl mx-auto flex flex-col sm:flex-row items-center justify-between gap-2 text-xs text-slate-500"
        >
          <div class="flex items-center gap-4">
            <span>&copy; 2024 Hotel Management System</span>
            <span class="hidden sm:inline">•</span>
            <span class="flex items-center gap-1">
              <span class="relative flex h-2 w-2">
                <span
                  class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"
                ></span>
                <span class="relative inline-flex rounded-full h-2 w-2 bg-emerald-500"></span>
              </span>
              All systems operational
            </span>
          </div>
          <div class="flex items-center gap-4">
            <a href="#" class="hover:text-slate-700 hover:underline transition-colors">Privacy</a>
            <a href="#" class="hover:text-slate-700 hover:underline transition-colors">Terms</a>
            <a href="#" class="hover:text-slate-700 hover:underline transition-colors">Support</a>
            <span class="text-slate-300">|</span>
            <span class="text-slate-400">v2.0.0</span>
          </div>
        </div>
      </footer>
    </div>
  </div>
</template>

<style scoped>
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

/* Optional: Add subtle animation for content */
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

/* Better focus styles */
button:focus-visible {
  outline: 2px solid #3b82f6;
  outline-offset: 2px;
}

/* Smooth transitions */
* {
  transition: all 0.2s ease;
}
</style>

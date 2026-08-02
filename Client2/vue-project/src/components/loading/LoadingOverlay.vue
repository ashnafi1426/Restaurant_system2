<template>
  <Teleport to="body">
    <Transition name="fade">
      <div
        v-if="isVisible"
        class="fixed inset-0 bg-black/50 backdrop-blur-sm flex items-center justify-center z-50"
      >
        <div class="bg-white dark:bg-slate-900 rounded-2xl p-8 shadow-2xl flex flex-col items-center gap-4">
          <!-- Animated loader - UNIFIED CYAN + YELLOW -->
          <div class="relative w-16 h-16">
            <!-- Static background - BRIGHT CYAN -->
            <svg class="absolute inset-0 w-full h-full" viewBox="0 0 100 100">
              <circle cx="50" cy="50" r="40" fill="none" stroke="#0EA5E9" stroke-width="5" opacity="0.3" />
            </svg>
            
            <!-- Animated spinner - BRIGHT YELLOW -->
            <div class="absolute inset-0 animate-spin" style="animation: spin 1.5s linear infinite;">
              <svg viewBox="0 0 100 100" class="w-full h-full">
                <circle cx="50" cy="50" r="40" fill="none" stroke="#FBBF24" stroke-width="6" stroke-linecap="round" stroke-dasharray="60 240" />
              </svg>
            </div>
          </div>

          <!-- Loading text -->
          <div class="text-center">
            <p class="text-slate-900 dark:text-slate-100 font-semibold">{{ title }}</p>
            <p class="text-slate-500 dark:text-slate-400 text-sm mt-1">{{ message }}</p>
          </div>

          <!-- Progress bar (optional) -->
          <div v-if="showProgress" class="w-32 h-1 bg-slate-200 dark:bg-slate-700 rounded-full overflow-hidden mt-2">
            <div
              class="h-full bg-gradient-to-r from-cyan-500 to-yellow-400 rounded-full"
              :style="{ width: progress + '%' }"
            ></div>
          </div>
        </div>
      </div>
    </Transition>
  </Teleport>
</template>

<script setup lang="ts">
import { computed } from 'vue'

interface Props {
  isVisible: boolean
  title?: string
  message?: string
  showProgress?: boolean
  progress?: number
}

withDefaults(defineProps<Props>(), {
  title: 'Loading...',
  message: 'Please wait',
  showProgress: false,
  progress: 0,
})
</script>

<style scoped>
@keyframes spin {
  from {
    transform: rotate(0deg);
  }
  to {
    transform: rotate(360deg);
  }
}

.fade-enter-active,
.fade-leave-active {
  transition: opacity 0.3s ease;
}

.fade-enter-from,
.fade-leave-to {
  opacity: 0;
}
</style>

<template>
  <div class="fixed inset-0 bg-black/50 dark:bg-black/70 flex items-center justify-center z-50">
    <div class="bg-white dark:bg-slate-900 rounded-lg shadow-xl p-8">
      <div class="flex flex-col items-center gap-4">
        <!-- Spinner -->
        <div class="relative w-12 h-12">
          <!-- Static background -->
          <svg class="absolute inset-0 w-full h-full" viewBox="0 0 100 100">
            <circle cx="50" cy="50" r="45" fill="none" stroke="#0EA5E9" stroke-width="6" opacity="0.3" />
          </svg>
          
          <!-- Animated arc -->
          <div class="absolute inset-0 animate-spin" style="animation: spin 1.5s linear infinite;">
            <svg viewBox="0 0 100 100" class="w-full h-full">
              <circle cx="50" cy="50" r="45" fill="none" stroke="#FBBF24" stroke-width="8" stroke-linecap="round" stroke-dasharray="70 280" />
            </svg>
          </div>
        </div>

        <!-- Text -->
        <div class="text-center">
          <p class="text-slate-700 dark:text-yellow-300 font-semibold text-sm">{{ text }}</p>
          <p class="text-xs text-slate-500 dark:text-cyan-300 mt-1">{{ subtext }}</p>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
defineProps({
  text: {
    type: String,
    default: 'Loading...',
  },
  subtext: {
    type: String,
    default: 'Please wait',
  },
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

.animate-spin {
  animation: spin 1.5s linear infinite;
}
</style>

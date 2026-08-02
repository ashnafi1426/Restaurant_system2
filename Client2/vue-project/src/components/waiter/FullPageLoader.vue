<template>
  <div class="w-full h-full flex items-center justify-center py-16">
    <div class="flex flex-col items-center gap-4">
      <!-- Spinner - COMPACT -->
      <div class="relative w-12 h-12">
        <!-- Static background circle -->
        <svg class="absolute inset-0 w-full h-full" viewBox="0 0 100 100">
          <!-- OUTER RING - BRIGHT CYAN/BLUE -->
          <circle cx="50" cy="50" r="45" fill="none" stroke="#0EA5E9" stroke-width="6" opacity="0.3" />
        </svg>
        
        <!-- Animated spinner - BRIGHT YELLOW -->
        <div class="absolute inset-0 animate-spin" style="animation: spin 1.5s linear infinite;">
          <svg viewBox="0 0 100 100" class="w-full h-full">
            <!-- ARC - BRIGHT YELLOW -->
            <circle cx="50" cy="50" r="45" fill="none" stroke="#FBBF24" stroke-width="8" stroke-linecap="round" stroke-dasharray="70 280" />
          </svg>
        </div>
      </div>

      <!-- Loading Text -->
      <div class="text-center">
        <p class="text-slate-700 dark:text-yellow-300 font-semibold text-sm">{{ loadingText }}</p>
        <p class="text-xs text-slate-500 dark:text-cyan-300 mt-1">Please wait...</p>
      </div>

      <!-- Optional Sub-text -->
      <div v-if="showSubtext" class="text-xs text-slate-500 dark:text-cyan-300 max-w-xs text-center">
        <p>{{ subtext }}</p>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
defineProps({
  loadingText: {
    type: String,
    default: 'Loading data...',
  },
  subtext: {
    type: String,
    default: 'This may take a few moments',
  },
  showSubtext: {
    type: Boolean,
    default: true,
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

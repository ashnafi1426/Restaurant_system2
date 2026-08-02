<template>
  <div class="flex items-center justify-center p-4">
    <div class="relative w-10 h-10">
      <!-- Static background - BRIGHT CYAN -->
      <svg class="absolute inset-0 w-full h-full" viewBox="0 0 100 100">
        <circle cx="50" cy="50" r="40" fill="none" stroke="#06B6D4" stroke-width="5" opacity="0.3" />
      </svg>
      
      <!-- Animated spinner - BRIGHT YELLOW -->
      <div class="absolute inset-0 animate-spin" style="animation: spin 1.5s linear infinite;">
        <svg viewBox="0 0 100 100" class="w-full h-full">
          <circle cx="50" cy="50" r="40" fill="none" stroke="#FBBF24" stroke-width="6" stroke-linecap="round" stroke-dasharray="60 240" />
        </svg>
      </div>
    </div>
  </div>
</template>

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

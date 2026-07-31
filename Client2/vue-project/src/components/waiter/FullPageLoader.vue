<template>
  <div class="w-full h-full flex items-center justify-center py-16">
    <div class="flex flex-col items-center gap-6">
      <!-- Spinner -->
      <div class="relative w-16 h-16">
        <div class="absolute inset-0 rounded-full border-4 border-slate-200"></div>
        <div class="absolute inset-0 rounded-full border-4 border-transparent border-t-blue-500 border-r-blue-500 animate-spin"></div>
      </div>

      <!-- Loading Text -->
      <div class="text-center">
        <p class="text-slate-600 font-medium">{{ loadingText }}</p>
        <p class="text-sm text-slate-500 mt-1">Please wait...</p>
      </div>

      <!-- Optional Sub-text -->
      <div v-if="showSubtext" class="text-xs text-slate-400 max-w-xs text-center">
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
  animation: spin 1s linear infinite;
}
</style>

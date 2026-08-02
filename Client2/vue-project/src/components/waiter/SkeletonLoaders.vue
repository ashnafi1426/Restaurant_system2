<template>
  <!-- Dashboard Stats Card Skeleton -->
  <div v-if="type === 'stat-card'" class="bg-white dark:bg-slate-800 rounded-2xl border border-slate-200 dark:border-slate-700 shadow-sm p-6 animate-pulse">
    <div class="flex items-center justify-between">
      <div class="flex-1">
        <div class="h-4 bg-slate-200 dark:bg-slate-700 rounded w-24 mb-3"></div>
        <div class="h-8 bg-slate-200 dark:bg-slate-700 rounded w-32"></div>
      </div>
      <div class="w-12 h-12 bg-slate-200 dark:bg-slate-700 rounded-lg flex-shrink-0"></div>
    </div>
  </div>

  <!-- Table Row Skeleton -->
  <div v-else-if="type === 'table-row'" class="bg-white dark:bg-slate-800 border-b border-slate-200 dark:border-slate-700 p-4 animate-pulse">
    <div class="grid grid-cols-5 gap-4">
      <div class="h-4 bg-slate-200 dark:bg-slate-700 rounded"></div>
      <div class="h-4 bg-slate-200 dark:bg-slate-700 rounded"></div>
      <div class="h-4 bg-slate-200 dark:bg-slate-700 rounded"></div>
      <div class="h-4 bg-slate-200 dark:bg-slate-700 rounded"></div>
      <div class="h-4 bg-slate-200 dark:bg-slate-700 rounded w-12"></div>
    </div>
  </div>

  <!-- Order Card Skeleton -->
  <div v-else-if="type === 'order-card'" class="bg-white dark:bg-slate-800 rounded-3xl border border-slate-200 dark:border-slate-700 shadow-sm p-6 animate-pulse">
    <div class="flex items-center justify-between mb-4">
      <div class="flex-1">
        <div class="h-6 bg-slate-200 dark:bg-slate-700 rounded w-32 mb-2"></div>
        <div class="h-4 bg-slate-200 dark:bg-slate-700 rounded w-24"></div>
      </div>
      <div class="h-6 bg-slate-200 dark:bg-slate-700 rounded w-20"></div>
    </div>
    <div class="grid grid-cols-2 gap-4 mb-6">
      <div>
        <div class="h-3 bg-slate-200 dark:bg-slate-700 rounded w-16 mb-2"></div>
        <div class="h-4 bg-slate-200 dark:bg-slate-700 rounded w-24"></div>
      </div>
      <div>
        <div class="h-3 bg-slate-200 dark:bg-slate-700 rounded w-16 mb-2"></div>
        <div class="h-4 bg-slate-200 dark:bg-slate-700 rounded w-24"></div>
      </div>
    </div>
    <div class="flex gap-3">
      <div class="flex-1 h-10 bg-slate-200 dark:bg-slate-700 rounded-lg"></div>
      <div class="flex-1 h-10 bg-slate-200 dark:bg-slate-700 rounded-lg"></div>
    </div>
  </div>

  <!-- Full Page Skeleton -->
  <div v-else-if="type === 'full-page'" class="space-y-6">
    <!-- Header -->
    <div class="flex items-center justify-between animate-pulse">
      <div>
        <div class="h-8 bg-slate-200 dark:bg-slate-700 rounded w-48 mb-2"></div>
        <div class="h-4 bg-slate-200 dark:bg-slate-700 rounded w-64"></div>
      </div>
      <div class="h-10 bg-slate-200 dark:bg-slate-700 rounded w-28"></div>
    </div>

    <!-- 4 Stats Cards -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
      <div v-for="i in 4" :key="i" class="bg-white dark:bg-slate-800 rounded-2xl border border-slate-200 dark:border-slate-700 shadow-sm p-6">
        <div class="flex items-center justify-between">
          <div class="flex-1">
            <div class="h-4 bg-slate-200 dark:bg-slate-700 rounded w-24 mb-3"></div>
            <div class="h-8 bg-slate-200 dark:bg-slate-700 rounded w-32"></div>
          </div>
          <div class="w-12 h-12 bg-slate-200 dark:bg-slate-700 rounded-lg flex-shrink-0"></div>
        </div>
      </div>
    </div>

    <!-- Content Section -->
    <div class="bg-white dark:bg-slate-800 rounded-3xl border border-slate-200 dark:border-slate-700 shadow-sm p-6">
      <div class="h-6 bg-slate-200 dark:bg-slate-700 rounded w-40 mb-6"></div>
      <div class="space-y-4">
        <div v-for="i in 3" :key="i" class="border-b border-slate-200 dark:border-slate-700 pb-4">
          <div class="h-4 bg-slate-200 dark:bg-slate-700 rounded w-3/4 mb-3"></div>
          <div class="grid grid-cols-2 gap-4">
            <div class="h-4 bg-slate-200 dark:bg-slate-700 rounded"></div>
            <div class="h-4 bg-slate-200 dark:bg-slate-700 rounded"></div>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- Minimal Spinner with Text -->
  <div v-else-if="type === 'spinner-with-text'" class="flex flex-col items-center justify-center py-12">
    <div class="relative w-14 h-14 mb-3">
      <!-- Static background - BRIGHT CYAN -->
      <svg class="absolute inset-0 w-full h-full" viewBox="0 0 100 100">
        <circle cx="50" cy="50" r="42" fill="none" stroke="#06B6D4" stroke-width="5" opacity="0.3" />
      </svg>
      
      <!-- Animated spinner - BRIGHT YELLOW -->
      <div class="absolute inset-0 animate-spin" style="animation: spin 1.5s linear infinite;">
        <svg viewBox="0 0 100 100" class="w-full h-full">
          <circle cx="50" cy="50" r="42" fill="none" stroke="#FBBF24" stroke-width="6" stroke-linecap="round" stroke-dasharray="65 260" />
        </svg>
      </div>
    </div>
    <p class="text-slate-700 dark:text-yellow-300 font-semibold text-sm">{{ loadingText }}</p>
  </div>

  <!-- List Items Skeleton -->
  <div v-else-if="type === 'list-items'" class="space-y-3">
    <div v-for="i in itemCount" :key="i" class="bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700 p-4 animate-pulse">
      <div class="flex items-center justify-between">
        <div class="flex-1">
          <div class="h-5 bg-slate-200 dark:bg-slate-700 rounded w-2/3 mb-2"></div>
          <div class="h-4 bg-slate-200 dark:bg-slate-700 rounded w-1/2"></div>
        </div>
        <div class="w-10 h-10 bg-slate-200 dark:bg-slate-700 rounded-lg flex-shrink-0"></div>
      </div>
    </div>
  </div>

  <!-- Chart Skeleton -->
  <div v-else-if="type === 'chart'" class="bg-white dark:bg-slate-800 rounded-2xl border border-slate-200 dark:border-slate-700 shadow-sm p-6">
    <div class="h-6 bg-slate-200 dark:bg-slate-700 rounded w-32 mb-6"></div>
    <div class="space-y-4">
      <div v-for="i in 5" :key="i" class="flex items-center gap-4">
        <div class="h-4 bg-slate-200 dark:bg-slate-700 rounded w-12"></div>
        <div class="h-6 bg-gradient-to-r from-slate-200 dark:from-slate-700 to-slate-100 dark:to-slate-600 rounded flex-1" :style="{ width: Math.random() * 80 + 20 + '%' }"></div>
      </div>
    </div>
  </div>

  <!-- Grid Items Skeleton -->
  <div v-else-if="type === 'grid-items'" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
    <div v-for="i in itemCount" :key="i" class="bg-white dark:bg-slate-800 rounded-2xl border border-slate-200 dark:border-slate-700 shadow-sm p-6 animate-pulse">
      <div class="h-6 bg-slate-200 dark:bg-slate-700 rounded w-3/4 mb-4"></div>
      <div class="space-y-3">
        <div class="h-4 bg-slate-200 dark:bg-slate-700 rounded w-full"></div>
        <div class="h-4 bg-slate-200 dark:bg-slate-700 rounded w-5/6"></div>
        <div class="h-4 bg-slate-200 dark:bg-slate-700 rounded w-4/5"></div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
defineProps({
  type: {
    type: String,
    default: 'stat-card',
    validator: (value: string) => [
      'stat-card',
      'table-row',
      'order-card',
      'full-page',
      'spinner-with-text',
      'list-items',
      'chart',
      'grid-items',
    ].includes(value),
  },
  itemCount: {
    type: Number,
    default: 3,
  },
  loadingText: {
    type: String,
    default: 'Loading...',
  },
})
</script>

<style scoped>
@keyframes shimmer {
  0% {
    background-position: -1000px 0;
  }
  100% {
    background-position: 1000px 0;
  }
}

@keyframes spin {
  from {
    transform: rotate(0deg);
  }
  to {
    transform: rotate(360deg);
  }
}

.animate-pulse {
  animation: pulse 2s cubic-bezier(0.4, 0, 0.6, 1) infinite;
}

.animate-spin {
  animation: spin 1.5s linear infinite;
}

@keyframes pulse {
  0%,
  100% {
    opacity: 1;
  }
  50% {
    opacity: 0.5;
  }
}
</style>

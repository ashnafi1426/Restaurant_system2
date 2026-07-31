<template>
  <div class="space-y-4">
    <!-- Stats Cards Skeleton -->
    <div v-if="type === 'stats'" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
      <div v-for="i in 4" :key="i" class="bg-white rounded-lg p-6 shadow-sm animate-pulse">
        <div class="flex justify-between items-start">
          <div class="flex-1">
            <div class="h-4 bg-slate-200 rounded w-24 mb-2"></div>
            <div class="h-8 bg-slate-300 rounded w-16"></div>
          </div>
          <div class="w-12 h-12 bg-slate-200 rounded-lg"></div>
        </div>
      </div>
    </div>

    <!-- List Items Skeleton -->
    <div v-if="type === 'list'" class="space-y-3">
      <div v-for="i in (count || 5)" :key="i" class="bg-white rounded-lg p-4 shadow-sm animate-pulse">
        <div class="flex justify-between items-center">
          <div class="flex-1">
            <div class="h-4 bg-slate-200 rounded w-32 mb-2"></div>
            <div class="h-3 bg-slate-100 rounded w-48"></div>
          </div>
          <div class="w-20 h-6 bg-slate-200 rounded"></div>
        </div>
      </div>
    </div>

    <!-- Table Skeleton -->
    <div v-if="type === 'table'" class="bg-white rounded-lg p-4 shadow-sm">
      <div class="space-y-3">
        <div v-for="i in (count || 6)" :key="i" class="animate-pulse">
          <div class="flex gap-4">
            <div class="h-12 bg-slate-200 rounded flex-1"></div>
            <div class="h-12 bg-slate-200 rounded flex-1"></div>
            <div class="h-12 bg-slate-200 rounded flex-1"></div>
            <div class="h-12 bg-slate-200 rounded w-20"></div>
          </div>
        </div>
      </div>
    </div>

    <!-- Card Grid Skeleton -->
    <div v-if="type === 'cards'" class="grid grid-cols-1 md:grid-cols-2 gap-4">
      <div v-for="i in (count || 4)" :key="i" class="bg-white rounded-lg p-6 shadow-sm animate-pulse">
        <div class="h-4 bg-slate-200 rounded w-32 mb-4"></div>
        <div class="space-y-2">
          <div class="h-3 bg-slate-100 rounded"></div>
          <div class="h-3 bg-slate-100 rounded w-5/6"></div>
        </div>
      </div>
    </div>

    <!-- Full Page Skeleton -->
    <div v-if="type === 'page'" class="space-y-6">
      <!-- Header -->
      <div class="animate-pulse">
        <div class="h-8 bg-slate-300 rounded w-48 mb-2"></div>
        <div class="h-4 bg-slate-200 rounded w-64"></div>
      </div>

      <!-- Stats Cards -->
      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
        <div v-for="i in 4" :key="i" class="bg-white rounded-lg p-6 shadow-sm animate-pulse">
          <div class="h-4 bg-slate-200 rounded w-24 mb-2"></div>
          <div class="h-8 bg-slate-300 rounded w-16"></div>
        </div>
      </div>

      <!-- Content -->
      <div class="bg-white rounded-lg p-6 shadow-sm animate-pulse space-y-4">
        <div class="h-6 bg-slate-300 rounded w-40 mb-4"></div>
        <div v-for="i in 5" :key="i" class="flex gap-4">
          <div class="h-12 bg-slate-200 rounded flex-1"></div>
          <div class="h-12 bg-slate-200 rounded flex-1"></div>
          <div class="h-12 bg-slate-200 rounded w-20"></div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
defineProps({
  type: {
    type: String,
    default: 'list',
    validator: (v: string) => ['stats', 'list', 'table', 'cards', 'page'].includes(v),
  },
  count: {
    type: Number,
    default: null,
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

.animate-pulse {
  animation: pulse 2s cubic-bezier(0.4, 0, 0.6, 1) infinite;
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

<template>
  <div class="text-center py-16">
    <svg class="mx-auto h-12 w-12 text-slate-400 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12a9 9 0 1018 0 9 9 0 00-18 0z" />
      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 9a.75.75 0 1101.5 0A.75.75 0 009 9m6 0a.75.75 0 1101.5 0A.75.75 0 0015 9m-6 6a3 3 0 016 0" />
    </svg>
    <h3 class="text-lg font-medium text-slate-900 mb-2">No rooms found</h3>
    <p class="text-slate-600 mb-6">Try adjusting your search or filter criteria</p>
    <button
      @click="$emit('clear-filters')"
      class="px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition"
    >
      Clear Filters
    </button>
  </div>
</template>

<script setup lang="ts">
defineEmits<{
  'clear-filters': []
}>()
</script>

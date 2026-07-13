<template>
  <div class="flex flex-col items-center justify-center py-16 sm:py-20 md:py-24 px-4">
    <!-- Empty State Icon -->
    <div class="text-6xl sm:text-7xl mb-6">🏨</div>

    <!-- Heading -->
    <h3 class="text-2xl sm:text-3xl font-bold text-slate-900 mb-3 text-center">No Rooms Found</h3>

    <!-- Description -->
    <p class="text-slate-600 text-center max-w-md mb-8 text-sm sm:text-base">
      Sorry! We couldn't find any rooms matching your search criteria. Try adjusting your filters or
      search terms to find the perfect room for you.
    </p>

    <!-- Suggestions -->
    <div class="bg-amber-50 border-l-4 border-amber-500 p-4 sm:p-6 mb-8 rounded max-w-md w-full">
      <p class="text-sm sm:text-base font-semibold text-amber-900 mb-3">Try these suggestions:</p>
      <ul class="space-y-2 text-sm text-amber-800">
        <li class="flex items-start">
          <span class="mr-2">•</span>
          <span>Clear all filters and try again</span>
        </li>
        <li class="flex items-start">
          <span class="mr-2">•</span>
          <span>Search for a different room type</span>
        </li>
        <li class="flex items-start">
          <span class="mr-2">•</span>
          <span>Check back later for availability</span>
        </li>
      </ul>
    </div>

    <!-- CTA Buttons -->
    <div class="flex flex-col sm:flex-row gap-3 sm:gap-4">
      <button
        @click="$emit('clear-filters')"
        class="px-6 py-2.5 sm:py-3 bg-slate-100 hover:bg-slate-200 text-slate-900 font-semibold rounded-lg transition-all text-sm sm:text-base"
      >
        Clear Filters
      </button>

      <a
        href="/contact"
        class="px-6 py-2.5 sm:py-3 bg-amber-500 hover:bg-amber-600 text-white font-semibold rounded-lg transition-all text-center text-sm sm:text-base"
      >
        Contact Support
      </a>
    </div>
  </div>
</template>

<script setup lang="ts">
defineEmits<{
  'clear-filters': []
}>()
</script>

<style scoped>
/* Responsive empty state */
</style>

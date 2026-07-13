<script setup lang="ts">
const search = defineModel<string>()
</script>

<template>
  <div class="mb-4 sm:mb-6">
    <input
      v-model="search"
      placeholder="Search room types..."
      class="w-full px-3 sm:px-4 py-2 sm:py-2.5 text-xs sm:text-sm border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition duration-150 bg-white shadow-sm"
    />
  </div>
</template>

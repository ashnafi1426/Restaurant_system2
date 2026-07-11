<template>
  <div class="flex flex-col sm:flex-row flex-wrap items-stretch sm:items-center gap-2 sm:gap-2.5 md:gap-3 w-full px-4 sm:px-0">
    <!-- Search Bar with Icon -->
    <div class="relative flex-1 min-w-[200px] sm:min-w-[260px]">
      <v-icon
        class="absolute inset-y-0 left-0 pl-2 sm:pl-3 flex items-center pointer-events-none text-slate-400"
        size="16"
        sm:size="18"
      >
        mdi-magnify
      </v-icon>
      <input
        :value="search"
        @input="$emit('update:search', ($event.target as HTMLInputElement).value)"
        type="text"
        placeholder="Search dish..."
        class="w-full pl-8 sm:pl-10 pr-3 sm:pr-4 py-2 sm:py-2.5 border border-slate-200 rounded-lg text-xs sm:text-sm focus:outline-none focus:border-purple-600 focus:ring-1 focus:ring-purple-600 transition-colors"
      />
    </div>

    <!-- Category Filter -->
    <div class="flex items-center gap-1.5 sm:gap-2 flex-1 sm:flex-initial min-w-[150px] sm:min-w-auto">
      <v-icon size="16" sm:size="18" class="text-slate-500 flex-shrink-0 hidden sm:block">mdi-tag</v-icon>
      <select
        :value="category"
        @change="$emit('update:category', ($event.target as HTMLSelectElement).value)"
        class="flex-1 px-2 sm:px-3 py-2 sm:py-2.5 border border-slate-200 rounded-lg text-xs sm:text-sm bg-white text-slate-600 focus:outline-none focus:border-purple-600 focus:ring-1 focus:ring-purple-600 transition-colors cursor-pointer"
      >
        <option value="">All Categories</option>
        <option v-for="cat in categories" :key="cat.id" :value="cat.id">{{ cat.name }}</option>
      </select>
    </div>

    <!-- Status Filter -->
    <div class="flex items-center gap-1.5 sm:gap-2 flex-1 sm:flex-initial min-w-[150px] sm:min-w-auto">
      <v-icon size="16" sm:size="18" class="text-slate-500 flex-shrink-0 hidden sm:block">mdi-check-circle</v-icon>
      <select
        :value="status"
        @change="$emit('update:status', ($event.target as HTMLSelectElement).value)"
        class="flex-1 px-2 sm:px-3 py-2 sm:py-2.5 border border-slate-200 rounded-lg text-xs sm:text-sm bg-white text-slate-600 focus:outline-none focus:border-purple-600 focus:ring-1 focus:ring-purple-600 transition-colors cursor-pointer"
      >
        <option value="">All Status</option>
        <option value="available">Available</option>
        <option value="unavailable">Out of Stock</option>
      </select>
    </div>

    <!-- Refresh Button -->
    <button
      @click="$emit('refresh')"
      class="px-2 sm:px-3 py-2 sm:py-2.5 border border-slate-200 rounded-lg hover:bg-slate-50 text-slate-500 hover:text-slate-700 transition-colors min-h-10 w-10 sm:w-auto flex items-center justify-center"
      title="Refresh filters"
    >
      <v-icon size="16" sm:size="18">mdi-refresh</v-icon>
    </button>

    <!-- Clear Filters Button -->
    <button
      v-if="search || category || status"
      @click="$emit('clear')"
      class="px-2 sm:px-3 py-2 sm:py-2.5 border border-slate-200 rounded-lg hover:bg-slate-50 text-slate-500 hover:text-slate-700 transition-colors min-h-10 w-10 sm:w-auto flex items-center justify-center"
      title="Clear all filters"
    >
      <v-icon size="16" sm:size="18">mdi-close-circle</v-icon>
    </button>
  </div>
</template>

<script setup lang="ts">
defineProps<{
  search: string
  category: string
  status: string
  categories: any[]
}>()

defineEmits(['update:search', 'update:category', 'update:status', 'refresh', 'clear'])
</script>

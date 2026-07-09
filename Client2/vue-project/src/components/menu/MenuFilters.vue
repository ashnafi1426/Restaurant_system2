<template>
  <div class="flex flex-wrap items-center gap-3 w-full">
    <!-- Search Bar with Icon -->
    <div class="relative flex-1 min-w-[260px]">
      <v-icon
        class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-slate-400"
        size="18"
      >
        mdi-magnify
      </v-icon>
      <input
        :value="search"
        @input="$emit('update:search', ($event.target as HTMLInputElement).value)"
        type="text"
        placeholder="Search dish name, description..."
        class="w-full pl-10 pr-4 py-2 border border-slate-200 rounded-lg text-sm focus:outline-none focus:border-purple-600 focus:ring-1 focus:ring-purple-600 transition-colors"
      />
    </div>

    <!-- Category Filter -->
    <div class="flex items-center gap-2">
      <v-icon size="18" class="text-slate-500">mdi-tag</v-icon>
      <select
        :value="category"
        @change="$emit('update:category', ($event.target as HTMLSelectElement).value)"
        class="px-3 py-2 border border-slate-200 rounded-lg text-sm bg-white text-slate-600 focus:outline-none focus:border-purple-600 focus:ring-1 focus:ring-purple-600 transition-colors cursor-pointer"
      >
        <option value="">All Categories</option>
        <option v-for="cat in categories" :key="cat.id" :value="cat.id">{{ cat.name }}</option>
      </select>
    </div>

    <!-- Status Filter -->
    <div class="flex items-center gap-2">
      <v-icon size="18" class="text-slate-500">mdi-check-circle</v-icon>
      <select
        :value="status"
        @change="$emit('update:status', ($event.target as HTMLSelectElement).value)"
        class="px-3 py-2 border border-slate-200 rounded-lg text-sm bg-white text-slate-600 focus:outline-none focus:border-purple-600 focus:ring-1 focus:ring-purple-600 transition-colors cursor-pointer"
      >
        <option value="">All Status</option>
        <option value="available">Available</option>
        <option value="unavailable">Out of Stock</option>
      </select>
    </div>

    <!-- Refresh Button -->
    <button
      @click="$emit('refresh')"
      class="p-2 border border-slate-200 rounded-lg hover:bg-slate-50 text-slate-500 hover:text-slate-700 transition-colors"
      title="Refresh filters"
    >
      <v-icon size="18">mdi-refresh</v-icon>
    </button>

    <!-- Clear Filters Button -->
    <button
      v-if="search || category || status"
      @click="$emit('clear')"
      class="p-2 border border-slate-200 rounded-lg hover:bg-slate-50 text-slate-500 hover:text-slate-700 transition-colors"
      title="Clear all filters"
    >
      <v-icon size="18">mdi-close-circle</v-icon>
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

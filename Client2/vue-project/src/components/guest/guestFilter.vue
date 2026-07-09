<script setup lang="ts">
import { reactive, watch } from 'vue'
import type { GuestFilter } from '../../types/guest'

interface Props {
  filters: GuestFilter
}

const props = defineProps<Props>()

const emit = defineEmits<{
  (e: 'update:filters', value: GuestFilter): void
  (e: 'search'): void
  (e: 'reset'): void
  (e: 'create'): void
}>()

const localFilters = reactive<GuestFilter>({
  search: '',
  nationality: '',
  page: 1,
  per_page: 10,
})

watch(
  () => props.filters,
  (value) => {
    Object.assign(localFilters, value)
  },
  {
    immediate: true,
    deep: true,
  },
)

watch(
  localFilters,
  () => {
    emit('update:filters', { ...localFilters })
  },
  {
    deep: true,
  },
)

const resetFilters = () => {
  localFilters.search = ''
  localFilters.nationality = ''
  localFilters.page = 1
  localFilters.per_page = 10

  emit('reset')
}
</script>

<template>
  <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-6">
    <!-- Header -->
    <div class="flex items-center justify-between mb-6">
      <div>
        <h3 class="text-lg font-semibold text-slate-800">Search & Filter</h3>
        <p class="text-sm text-slate-500 mt-1">Find guests quickly using filters below</p>
      </div>
      <span class="material-symbols-rounded text-slate-400">filter_list</span>
    </div>

    <!-- Filters -->
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-4">
      <!-- Search -->
      <div class="lg:col-span-5">
        <label class="block text-sm font-medium text-slate-700 mb-2">
          <span class="flex items-center gap-1">
            <span class="material-symbols-rounded text-sm">search</span>
            Search Guest
          </span>
        </label>
        <input
          v-model="localFilters.search"
          type="text"
          placeholder="Search by name, email, or phone..."
          class="w-full rounded-lg border border-slate-300 px-4 py-2.5 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition"
          @keyup.enter="emit('search')"
        />
      </div>

      <!-- Nationality -->
      <div class="lg:col-span-3">
        <label class="block text-sm font-medium text-slate-700 mb-2">
          <span class="flex items-center gap-1">
            <span class="material-symbols-rounded text-sm">public</span>
            Nationality
          </span>
        </label>
        <input
          v-model="localFilters.nationality"
          type="text"
          placeholder="e.g. USA, UK..."
          class="w-full rounded-lg border border-slate-300 px-4 py-2.5 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition"
        />
      </div>

      <!-- Per Page -->
      <div class="lg:col-span-2">
        <label class="block text-sm font-medium text-slate-700 mb-2">
          <span class="flex items-center gap-1">
            <span class="material-symbols-rounded text-sm">view_list</span>
            Per Page
          </span>
        </label>
        <select
          v-model.number="localFilters.per_page"
          class="w-full rounded-lg border border-slate-300 px-4 py-2.5 focus:ring-2 focus:ring-blue-500 transition cursor-pointer"
        >
          <option :value="5">5</option>
          <option :value="10">10</option>
          <option :value="25">25</option>
          <option :value="50">50</option>
          <option :value="100">100</option>
        </select>
      </div>

      <!-- Action Buttons -->
      <div class="lg:col-span-2 flex items-end gap-2">
        <button
          @click="emit('search')"
          class="flex-1 flex items-center justify-center gap-2 bg-blue-600 hover:bg-blue-700 text-white px-4 py-2.5 rounded-lg transition shadow-sm font-medium"
          title="Search Guests"
        >
          <span class="material-symbols-rounded text-sm">search</span>
          <span class="hidden sm:inline">Search</span>
        </button>

        <button
          @click="resetFilters"
          class="flex items-center justify-center gap-1 bg-slate-100 hover:bg-slate-200 text-slate-700 px-4 py-2.5 rounded-lg transition"
          title="Reset Filters"
        >
          <span class="material-symbols-rounded text-sm">restart_alt</span>
        </button>
      </div>
    </div>

    <!-- Active Filters Info -->
    <div
      v-if="localFilters.search || localFilters.nationality"
      class="mt-4 pt-4 border-t border-slate-200"
    >
      <div class="flex items-center gap-2 flex-wrap">
        <span class="text-sm text-slate-600 font-medium">Active Filters:</span>

        <span
          v-if="localFilters.search"
          class="inline-flex items-center gap-1 bg-blue-100 text-blue-700 px-3 py-1 rounded-full text-sm"
        >
          <span class="material-symbols-rounded text-xs">search</span>
          {{ localFilters.search }}
        </span>

        <span
          v-if="localFilters.nationality"
          class="inline-flex items-center gap-1 bg-green-100 text-green-700 px-3 py-1 rounded-full text-sm"
        >
          <span class="material-symbols-rounded text-xs">public</span>
          {{ localFilters.nationality }}
        </span>

        <button @click="resetFilters" class="text-xs text-slate-500 hover:text-slate-700 underline">
          Clear all
        </button>
      </div>
    </div>
  </div>
</template>

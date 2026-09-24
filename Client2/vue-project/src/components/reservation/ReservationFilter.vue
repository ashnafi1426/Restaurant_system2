<script setup lang="ts">
import { reactive, watch } from 'vue'
import type { ReservationFilter } from '@/types/reservation'
import { Search, X, RotateCcw } from 'lucide-vue-next'

interface Props {
  filters: ReservationFilter
}

const props = defineProps<Props>()

const emit = defineEmits<{
  (e: 'update:filters', value: ReservationFilter): void
  (e: 'search'): void
  (e: 'reset'): void
}>()

const localFilters = reactive({
  ...props.filters,
})

let debounceTimer: ReturnType<typeof setTimeout> | null = null

watch(
  () => props.filters,
  (value) => {
    Object.assign(localFilters, value)
  },
  { deep: true }
)

const handleSearchInput = () => {
  emit('update:filters', { ...localFilters, page: 1 })
  if (debounceTimer) clearTimeout(debounceTimer)
  debounceTimer = setTimeout(() => {
    emit('search')
  }, 250)
}

const clearSearch = () => {
  localFilters.search = ''
  handleSearchInput()
}

const reset = () => {
  localFilters.search = ''
  localFilters.status = ''
  localFilters.guest_id = ''
  localFilters.room_id = ''
  localFilters.check_in_date = ''
  localFilters.check_out_date = ''
  localFilters.page = 1
  emit('update:filters', { ...localFilters })
  emit('reset')
}
</script>

<template>
  <div class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-800 p-4 shadow-xs">
    <div class="relative flex items-center w-full">
      <div class="absolute left-4 text-purple-600 dark:text-purple-400 pointer-events-none">
        <Search class="w-5 h-5" />
      </div>

      <input
        v-model="localFilters.search"
        @input="handleSearchInput"
        @keyup.enter="emit('search')"
        type="text"
        placeholder="Search everything... (Booking reference, guest name, email, phone, room number, or status)"
        class="w-full pl-12 pr-28 py-3.5 rounded-xl border border-slate-200 dark:border-slate-800 bg-slate-50/60 dark:bg-slate-950 text-sm font-semibold text-slate-900 dark:text-white placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-purple-500/50 focus:bg-white dark:focus:bg-slate-900 transition shadow-xs"
      />

      <div class="absolute right-3 flex items-center gap-1.5">
        <button
          v-if="localFilters.search"
          @click="clearSearch"
          class="p-1.5 text-slate-400 hover:text-slate-700 dark:hover:text-slate-200 hover:bg-slate-200/50 dark:hover:bg-slate-800 rounded-lg transition"
          title="Clear Search"
        >
          <X class="w-4 h-4" />
        </button>

        <button
          @click="reset"
          v-if="localFilters.search || localFilters.status || localFilters.check_in_date"
          class="inline-flex items-center gap-1 px-3 py-1.5 text-xs font-bold text-slate-600 dark:text-slate-300 bg-slate-200/60 dark:bg-slate-800 hover:bg-slate-300 dark:hover:bg-slate-700 rounded-lg transition cursor-pointer"
          title="Reset Search"
        >
          <RotateCcw class="w-3.5 h-3.5" />
          <span>Reset</span>
        </button>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue'
import { ChevronLeft, ChevronRight, UserCheck } from 'lucide-vue-next'

interface Pagination {
  current_page: number
  last_page: number
  per_page: number
  total: number
  from?: number
  to?: number
}

interface Props {
  pagination: Pagination
  roleName?: string
  perPageOptions?: number[]
}

const props = withDefaults(defineProps<Props>(), {
  roleName: '',
  perPageOptions: () => [5, 10, 20, 50],
})

const emit = defineEmits<{
  (e: 'page-change', page: number): void
  (e: 'per-page-change', perPage: number): void
}>()

const fromItem = computed(() => {
  if (!props.pagination.total || props.pagination.total === 0) return 0
  if (props.pagination.from) return props.pagination.from
  return (props.pagination.current_page - 1) * props.pagination.per_page + 1
})

const toItem = computed(() => {
  if (!props.pagination.total || props.pagination.total === 0) return 0
  if (props.pagination.to) return props.pagination.to
  return Math.min(props.pagination.current_page * props.pagination.per_page, props.pagination.total)
})

const visiblePages = computed(() => {
  const pages: number[] = []
  const total = props.pagination.last_page || 1
  const current = props.pagination.current_page || 1

  let start = Math.max(1, current - 2)
  let end = Math.min(total, current + 2)

  if (current <= 3) {
    end = Math.min(total, 5)
  }
  if (current >= total - 2) {
    start = Math.max(1, total - 4)
  }

  for (let i = start; i <= end; i++) {
    pages.push(i)
  }

  return pages
})

const goToPage = (page: number) => {
  if (page < 1 || page > (props.pagination.last_page || 1) || page === props.pagination.current_page) return
  emit('page-change', page)
}

const handlePerPageChange = (event: Event) => {
  const val = Number((event.target as HTMLSelectElement).value)
  emit('per-page-change', val)
}
</script>

<template>
  <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-4 shadow-xs flex flex-col sm:flex-row items-center justify-between gap-4">
    <!-- Left: Status Info + User Role Badge -->
    <div class="flex items-center gap-3 flex-wrap">
      <div v-if="roleName" class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-xl text-xs font-bold bg-purple-500/10 text-purple-600 dark:text-purple-400 border border-purple-500/20">
        <UserCheck class="w-3.5 h-3.5" />
        <span>{{ roleName }}</span>
      </div>

      <p class="text-xs font-semibold text-slate-600 dark:text-slate-400">
        Showing <span class="font-extrabold text-slate-900 dark:text-white">{{ fromItem }}</span> to
        <span class="font-extrabold text-slate-900 dark:text-white">{{ toItem }}</span> of
        <span class="font-extrabold text-slate-900 dark:text-white">{{ pagination.total || 0 }}</span> entries
      </p>

      <!-- Per Page Selector -->
      <div class="flex items-center gap-1.5 ml-1">
        <span class="text-xs font-medium text-slate-500 dark:text-slate-400">Per page:</span>
        <select
          :value="pagination.per_page"
          @change="handlePerPageChange"
          class="rounded-lg border border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-950 px-2 py-1 text-xs font-bold text-slate-800 dark:text-slate-200 focus:outline-none focus:ring-2 focus:ring-purple-500/50 cursor-pointer"
        >
          <option v-for="opt in perPageOptions" :key="opt" :value="opt">
            {{ opt }}
          </option>
        </select>
      </div>
    </div>

    <!-- Right: Page Controls -->
    <div class="flex items-center gap-1.5">
      <!-- Previous Button -->
      <button
        @click="goToPage(pagination.current_page - 1)"
        :disabled="pagination.current_page <= 1"
        class="inline-flex items-center gap-1 px-3 py-1.5 rounded-xl text-xs font-bold border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 text-slate-700 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800 disabled:opacity-40 disabled:cursor-not-allowed transition cursor-pointer"
      >
        <ChevronLeft class="w-3.5 h-3.5" />
        <span>Prev</span>
      </button>

      <!-- Page Numbers -->
      <button
        v-for="page in visiblePages"
        :key="page"
        @click="goToPage(page)"
        :class="[
          'w-8 h-8 rounded-xl text-xs font-bold transition cursor-pointer flex items-center justify-center',
          pagination.current_page === page
            ? 'bg-purple-600 text-white shadow-xs'
            : 'bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 hover:bg-slate-200 dark:hover:bg-slate-700'
        ]"
      >
        {{ page }}
      </button>

      <!-- Next Button -->
      <button
        @click="goToPage(pagination.current_page + 1)"
        :disabled="pagination.current_page >= pagination.last_page"
        class="inline-flex items-center gap-1 px-3 py-1.5 rounded-xl text-xs font-bold border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 text-slate-700 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800 disabled:opacity-40 disabled:cursor-not-allowed transition cursor-pointer"
      >
        <span>Next</span>
        <ChevronRight class="w-3.5 h-3.5" />
      </button>
    </div>
  </div>
</template>

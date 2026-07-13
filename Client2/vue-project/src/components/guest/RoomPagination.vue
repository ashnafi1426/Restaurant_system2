<script setup lang="ts">
interface PaginationMeta {
  current_page: number
  last_page: number
  per_page: number
  total: number
  from: number
  to: number
}

const props = defineProps<{
  meta: PaginationMeta
}>()

const emit = defineEmits<{
  'change-page': [page: number]
}>()

function goToPage(page: number) {
  if (page >= 1 && page <= props.meta.last_page) {
    emit('change-page', page)
  }
}

function getPageNumbers() {
  const pages: (number | string)[] = []
  const { current_page, last_page } = props.meta

  // First page
  pages.push(1)

  // Previous pages
  if (current_page > 3) {
    pages.push('...')
  }

  // Current and adjacent pages
  for (let i = Math.max(2, current_page - 1); i <= Math.min(last_page - 1, current_page + 1); i++) {
    if (!pages.includes(i)) {
      pages.push(i)
    }
  }

  // Last page
  if (last_page > 1) {
    pages.push(last_page)
  }

  return pages
}
</script>

<template>
  <div
    v-if="props.meta.last_page > 1"
    class="flex flex-col sm:flex-row items-center justify-between gap-4 sm:gap-6"
  >
    <!-- Info Text -->
    <div class="text-sm text-slate-600 order-2 sm:order-1">
      Showing <span class="font-semibold">{{ props.meta.from }}</span> to
      <span class="font-semibold">{{ props.meta.to }}</span> of
      <span class="font-semibold">{{ props.meta.total }}</span> rooms
    </div>

    <!-- Pagination Controls -->
    <div class="flex items-center gap-2 order-1 sm:order-2 flex-wrap justify-center sm:justify-end">
      <!-- Previous Button -->
      <button
        @click="goToPage(props.meta.current_page - 1)"
        :disabled="props.meta.current_page === 1"
        class="px-3 sm:px-4 py-2 text-sm font-medium rounded-lg border border-slate-300 text-slate-700 hover:bg-slate-100 disabled:opacity-50 disabled:cursor-not-allowed transition-all"
      >
        ← Prev
      </button>

      <!-- Page Numbers -->
      <div class="flex items-center gap-1 sm:gap-2">
        <button
          v-for="(page, idx) in getPageNumbers()"
          :key="idx"
          @click="page !== '...' && goToPage(page as number)"
          :disabled="page === '...' || page === props.meta.current_page"
          :class="[
            'px-2.5 sm:px-3.5 py-2 text-sm font-medium rounded-lg transition-all',
            page === props.meta.current_page
              ? 'bg-amber-500 text-white border border-amber-500'
              : page === '...'
                ? 'text-slate-500 cursor-default'
                : 'border border-slate-300 text-slate-700 hover:bg-slate-100',
          ]"
        >
          {{ page }}
        </button>
      </div>

      <!-- Next Button -->
      <button
        @click="goToPage(props.meta.current_page + 1)"
        :disabled="props.meta.current_page === props.meta.last_page"
        class="px-3 sm:px-4 py-2 text-sm font-medium rounded-lg border border-slate-300 text-slate-700 hover:bg-slate-100 disabled:opacity-50 disabled:cursor-not-allowed transition-all"
      >
        Next →
      </button>
    </div>
  </div>
</template>

<style scoped>
/* Responsive pagination */
</style>

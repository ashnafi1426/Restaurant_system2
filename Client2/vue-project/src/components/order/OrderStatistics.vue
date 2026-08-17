<script setup lang="ts">
import type { OrderStatistics } from '@/types/order'

/*
|--------------------------------------------------------------------------
| Props
|--------------------------------------------------------------------------
*/

defineProps<{
  statistics: OrderStatistics
  loading?: boolean
}>()

/*
|--------------------------------------------------------------------------
| Helpers
|--------------------------------------------------------------------------
*/

function formatCurrency(value: number): string {
  return new Intl.NumberFormat('en-US', {
    style: 'currency',
    currency: 'USD',
  }).format(value)
}
</script>

<template>
  <div class="grid grid-cols-2 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-3 xl:grid-cols-6 gap-3 sm:gap-4">
    <!-- ======================================================= -->
    <!-- Total Orders -->
    <!-- ======================================================= -->
    <div
      class="group relative overflow-hidden rounded-2xl border border-slate-200/80 dark:border-slate-800 bg-gradient-to-br from-white via-slate-50/50 to-indigo-50/30 dark:from-slate-900 dark:to-indigo-950/20 p-4 sm:p-5 shadow-sm transition-all duration-300 hover:-translate-y-1 hover:shadow-md hover:border-indigo-200 dark:hover:border-indigo-800"
    >
      <div class="flex items-center justify-between">
        <span class="text-xs sm:text-sm font-semibold tracking-wide text-slate-500 dark:text-slate-400">Total Orders</span>
        <div class="flex h-9 w-9 items-center justify-center rounded-xl bg-indigo-50 text-indigo-600 dark:bg-indigo-900/40 dark:text-indigo-400 transition-transform group-hover:scale-110">
          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z" />
          </svg>
        </div>
      </div>

      <div v-if="loading" class="mt-3 h-8 animate-pulse rounded-lg bg-slate-200 dark:bg-slate-800" />
      <div v-else class="mt-2 flex items-baseline justify-between">
        <h2 class="text-2xl sm:text-3xl font-extrabold text-slate-900 dark:text-white tracking-tight">
          {{ statistics?.total_orders ?? 0 }}
        </h2>
        <span class="text-[11px] font-medium px-2 py-0.5 rounded-full bg-indigo-100/80 text-indigo-700 dark:bg-indigo-900/60 dark:text-indigo-300">
          All Time
        </span>
      </div>
    </div>

    <!-- ======================================================= -->
    <!-- Pending -->
    <!-- ======================================================= -->
    <div
      class="group relative overflow-hidden rounded-2xl border border-amber-200/80 dark:border-amber-900/40 bg-gradient-to-br from-amber-50/50 via-white to-amber-100/30 dark:from-slate-900 dark:to-amber-950/20 p-4 sm:p-5 shadow-sm transition-all duration-300 hover:-translate-y-1 hover:shadow-md hover:border-amber-300"
    >
      <div class="flex items-center justify-between">
        <span class="text-xs sm:text-sm font-semibold tracking-wide text-amber-700 dark:text-amber-400">Pending</span>
        <div class="flex h-9 w-9 items-center justify-center rounded-xl bg-amber-100 text-amber-600 dark:bg-amber-900/40 dark:text-amber-400 transition-transform group-hover:scale-110">
          <svg class="w-5 h-5 animate-spin-slow" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
          </svg>
        </div>
      </div>

      <div v-if="loading" class="mt-3 h-8 animate-pulse rounded-lg bg-amber-200/60 dark:bg-amber-950/40" />
      <div v-else class="mt-2 flex items-baseline justify-between">
        <h2 class="text-2xl sm:text-3xl font-extrabold text-amber-700 dark:text-amber-300 tracking-tight">
          {{ statistics?.pending_orders ?? 0 }}
        </h2>
        <span class="inline-flex items-center gap-1 text-[11px] font-medium px-2 py-0.5 rounded-full bg-amber-100 text-amber-800 dark:bg-amber-900/60 dark:text-amber-300">
          <span class="h-1.5 w-1.5 rounded-full bg-amber-500 animate-ping"></span>
          Queue
        </span>
      </div>
    </div>

    <!-- ======================================================= -->
    <!-- Preparing -->
    <!-- ======================================================= -->
    <div
      class="group relative overflow-hidden rounded-2xl border border-sky-200/80 dark:border-sky-900/40 bg-gradient-to-br from-sky-50/50 via-white to-sky-100/30 dark:from-slate-900 dark:to-sky-950/20 p-4 sm:p-5 shadow-sm transition-all duration-300 hover:-translate-y-1 hover:shadow-md hover:border-sky-300"
    >
      <div class="flex items-center justify-between">
        <span class="text-xs sm:text-sm font-semibold tracking-wide text-sky-700 dark:text-sky-400">Preparing</span>
        <div class="flex h-9 w-9 items-center justify-center rounded-xl bg-sky-100 text-sky-600 dark:bg-sky-900/40 dark:text-sky-400 transition-transform group-hover:scale-110">
          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 18.657A8 8 0 016.343 7.343S7 9 9 10c0-2 .5-5 2.986-7C14 5 16.09 5.777 17.656 7.343A7.975 7.975 0 0120 13a7.975 7.975 0 01-2.343 5.657z" />
          </svg>
        </div>
      </div>

      <div v-if="loading" class="mt-3 h-8 animate-pulse rounded-lg bg-sky-200/60 dark:bg-sky-950/40" />
      <div v-else class="mt-2 flex items-baseline justify-between">
        <h2 class="text-2xl sm:text-3xl font-extrabold text-sky-700 dark:text-sky-300 tracking-tight">
          {{ statistics?.preparing_orders ?? 0 }}
        </h2>
        <span class="text-[11px] font-medium px-2 py-0.5 rounded-full bg-sky-100 text-sky-800 dark:bg-sky-900/60 dark:text-sky-300">
          In Kitchen
        </span>
      </div>
    </div>

    <!-- ======================================================= -->
    <!-- Ready -->
    <!-- ======================================================= -->
    <div
      class="group relative overflow-hidden rounded-2xl border border-emerald-200/80 dark:border-emerald-900/40 bg-gradient-to-br from-emerald-50/50 via-white to-emerald-100/30 dark:from-slate-900 dark:to-emerald-950/20 p-4 sm:p-5 shadow-sm transition-all duration-300 hover:-translate-y-1 hover:shadow-md hover:border-emerald-300"
    >
      <div class="flex items-center justify-between">
        <span class="text-xs sm:text-sm font-semibold tracking-wide text-emerald-700 dark:text-emerald-400">Ready</span>
        <div class="flex h-9 w-9 items-center justify-center rounded-xl bg-emerald-100 text-emerald-600 dark:bg-emerald-900/40 dark:text-emerald-400 transition-transform group-hover:scale-110">
          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
          </svg>
        </div>
      </div>

      <div v-if="loading" class="mt-3 h-8 animate-pulse rounded-lg bg-emerald-200/60 dark:bg-emerald-950/40" />
      <div v-else class="mt-2 flex items-baseline justify-between">
        <h2 class="text-2xl sm:text-3xl font-extrabold text-emerald-700 dark:text-emerald-300 tracking-tight">
          {{ statistics?.ready_orders ?? 0 }}
        </h2>
        <span class="text-[11px] font-medium px-2 py-0.5 rounded-full bg-emerald-100 text-emerald-800 dark:bg-emerald-900/60 dark:text-emerald-300">
          Pickup Ready
        </span>
      </div>
    </div>

    <!-- ======================================================= -->
    <!-- Served -->
    <!-- ======================================================= -->
    <div
      class="group relative overflow-hidden rounded-2xl border border-purple-200/80 dark:border-purple-900/40 bg-gradient-to-br from-purple-50/50 via-white to-purple-100/30 dark:from-slate-900 dark:to-purple-950/20 p-4 sm:p-5 shadow-sm transition-all duration-300 hover:-translate-y-1 hover:shadow-md hover:border-purple-300"
    >
      <div class="flex items-center justify-between">
        <span class="text-xs sm:text-sm font-semibold tracking-wide text-purple-700 dark:text-purple-400">Served</span>
        <div class="flex h-9 w-9 items-center justify-center rounded-xl bg-purple-100 text-purple-600 dark:bg-purple-900/40 dark:text-purple-400 transition-transform group-hover:scale-110">
          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
          </svg>
        </div>
      </div>

      <div v-if="loading" class="mt-3 h-8 animate-pulse rounded-lg bg-purple-200/60 dark:bg-purple-950/40" />
      <div v-else class="mt-2 flex items-baseline justify-between">
        <h2 class="text-2xl sm:text-3xl font-extrabold text-purple-700 dark:text-purple-300 tracking-tight">
          {{ statistics?.served_orders ?? 0 }}
        </h2>
        <span class="text-[11px] font-medium px-2 py-0.5 rounded-full bg-purple-100 text-purple-800 dark:bg-purple-900/60 dark:text-purple-300">
          Completed
        </span>
      </div>
    </div>

    <!-- ======================================================= -->
    <!-- Revenue -->
    <!-- ======================================================= -->
    <div
      class="group relative overflow-hidden rounded-2xl border border-teal-200/80 dark:border-teal-900/40 bg-gradient-to-br from-teal-50/50 via-white to-teal-100/30 dark:from-slate-900 dark:to-teal-950/20 p-4 sm:p-5 shadow-sm transition-all duration-300 hover:-translate-y-1 hover:shadow-md hover:border-teal-300"
    >
      <div class="flex items-center justify-between">
        <span class="text-xs sm:text-sm font-semibold tracking-wide text-teal-700 dark:text-teal-400">Revenue</span>
        <div class="flex h-9 w-9 items-center justify-center rounded-xl bg-teal-100 text-teal-600 dark:bg-teal-900/40 dark:text-teal-400 transition-transform group-hover:scale-110">
          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
          </svg>
        </div>
      </div>

      <div v-if="loading" class="mt-3 h-8 animate-pulse rounded-lg bg-teal-200/60 dark:bg-teal-950/40" />
      <div v-else class="mt-2 flex items-baseline justify-between">
        <h2 class="text-xl sm:text-2xl font-extrabold text-teal-700 dark:text-teal-300 tracking-tight">
          {{ formatCurrency(statistics?.total_revenue ?? 0) }}
        </h2>
        <span class="text-[11px] font-medium px-2 py-0.5 rounded-full bg-teal-100 text-teal-800 dark:bg-teal-900/60 dark:text-teal-300">
          Total
        </span>
      </div>
    </div>
  </div>
</template>

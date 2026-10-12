<script setup lang="ts">
import { useHotelStore } from '@/stores/hotelStore'
import { Building2 } from 'lucide-vue-next'

const hotelStore = useHotelStore()
</script>

<template>
  <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between pb-1">
    <div>
      <div class="flex flex-wrap items-center gap-2">
        <h1
          class="text-xl sm:text-2xl md:text-3xl font-extrabold text-slate-900 dark:text-white tracking-tight"
        >
          Order Management
        </h1>
        <span
          v-if="hotelStore.hotelName"
          class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-xs font-bold bg-blue-100 dark:bg-blue-900/30 text-blue-800 dark:text-blue-300 border border-blue-200 dark:border-blue-700/50 flex-shrink-0"
        >
          <Building2 class="w-3 h-3" />
          <span class="truncate">{{ hotelStore.hotelName }}</span>
        </span>
      </div>
      <p class="mt-0.5 sm:mt-1 text-xs sm:text-sm text-slate-500 dark:text-slate-400">
        Manage, track, and process all restaurant orders
      </p>
    </div>
  </div>
</template>

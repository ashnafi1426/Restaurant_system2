<script setup lang="ts">
import { computed } from 'vue'

import { TrendingUp, BedDouble, Utensils, Sparkles, Shirt } from 'lucide-vue-next'

import { useManagerStore } from '@/stores/managerStore'

const manager = useManagerStore()

/*
|--------------------------------------------------------------------------
| Revenue Data
|--------------------------------------------------------------------------
*/

const revenue = computed(() => {
  return {
    today: manager.revenue.today ?? 0,

    week: manager.revenue.week ?? 0,

    month: manager.revenue.month ?? 0,
  }
})

/*
|--------------------------------------------------------------------------
| Revenue Departments
|--------------------------------------------------------------------------
*/

const departments = computed(() => {
  return [
    {
      name: 'Room Revenue',
      value: manager.revenue.rooms ?? 0,
      icon: BedDouble,
    },

    {
      name: 'Restaurant',
      value: manager.revenue.restaurant ?? 0,
      icon: Utensils,
    },

    {
      name: 'Room Service',
      value: manager.revenue.roomService ?? 0,
      icon: Sparkles,
    },

    {
      name: 'Laundry',
      value: manager.revenue.laundry ?? 0,
      icon: Shirt,
    },
  ]
})

/*
|--------------------------------------------------------------------------
| Total Revenue
|--------------------------------------------------------------------------
*/

const totalRevenue = computed(() => {
  return departments.value.reduce(
    (total, item) => total + item.value,

    0,
  )
})

/*
|--------------------------------------------------------------------------
| Percentage
|--------------------------------------------------------------------------
*/

const percentage = (value: number) => {
  if (!totalRevenue.value) return 0

  return Math.round((value / totalRevenue.value) * 100)
}

/*
|--------------------------------------------------------------------------
| Currency
|--------------------------------------------------------------------------
*/

const money = (value: number) => {
  return new Intl.NumberFormat('en-US', {
    style: 'currency',
    currency: 'ETB',
    maximumFractionDigits: 0,
  }).format(value)
}
</script>

<template>
  <section class="bg-white rounded-3xl border border-slate-200 shadow-sm p-6">
    <!-- Header -->

    <div class="flex items-center justify-between mb-8">
      <div>
        <h2 class="text-xl font-bold text-slate-900">Revenue Overview</h2>

        <p class="text-sm text-slate-500 mt-1">Hotel financial performance</p>
      </div>

      <div class="flex items-center gap-2 text-emerald-600 text-sm font-semibold">
        <TrendingUp class="w-5 h-5" />

        Growing
      </div>
    </div>

    <!-- Revenue Cards -->

    <div class="grid grid-cols-1 md:grid-cols-3 gap-5 mb-10">
      <div class="rounded-2xl bg-slate-50 p-5">
        <p class="text-sm text-slate-500">Today</p>

        <h3 class="mt-2 text-2xl font-bold">
          {{ money(revenue.today) }}
        </h3>
      </div>

      <div class="rounded-2xl bg-slate-50 p-5">
        <p class="text-sm text-slate-500">This Week</p>

        <h3 class="mt-2 text-2xl font-bold">
          {{ money(revenue.week) }}
        </h3>
      </div>

      <div class="rounded-2xl bg-slate-50 p-5">
        <p class="text-sm text-slate-500">This Month</p>

        <h3 class="mt-2 text-2xl font-bold">
          {{ money(revenue.month) }}
        </h3>
      </div>
    </div>

    <!-- Departments -->

    <h3 class="font-bold text-slate-900 mb-5">Revenue Sources</h3>

    <div class="space-y-5">
      <div v-for="item in departments" :key="item.name">
        <div class="flex justify-between items-center mb-2">
          <div class="flex items-center gap-3">
            <div class="w-10 h-10 rounded-xl bg-blue-50 flex items-center justify-center">
              <component :is="item.icon" class="w-5 h-5 text-blue-600" />
            </div>

            <span class="font-medium">
              {{ item.name }}
            </span>
          </div>

          <div class="text-sm font-bold">{{ percentage(item.value) }}%</div>
        </div>

        <!-- Progress Bar -->

        <div class="h-3 rounded-full bg-slate-100 overflow-hidden">
          <div
            class="h-full bg-blue-600 rounded-full transition-all"
            :style="{
              width: percentage(item.value) + '%',
            }"
          ></div>
        </div>

        <div class="text-sm text-slate-500 mt-1">
          {{ money(item.value) }}
        </div>
      </div>
    </div>
  </section>
</template>

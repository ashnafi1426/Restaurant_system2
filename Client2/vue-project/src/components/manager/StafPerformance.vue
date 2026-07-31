<script setup lang="ts">
import { computed } from 'vue'

import { Users, ChefHat, ConciergeBell, BedDouble, TrendingUp } from 'lucide-vue-next'

import { useManagerStore } from '@/stores/managerStore'

const manager = useManagerStore()

/*
|--------------------------------------------------------------------------
| Staff Summary
|--------------------------------------------------------------------------
*/

const summary = computed(() => {
  return {
    total: manager.staff.total ?? 0,

    active: manager.staff.active ?? 0,

    completed: manager.staff.completedTasks ?? 0,
  }
})

/*
|--------------------------------------------------------------------------
| Departments
|--------------------------------------------------------------------------
*/

const departments = computed(() => {
  return [
    {
      name: 'Reception',
      performance: manager.staff.reception ?? 0,
      icon: ConciergeBell,
    },

    {
      name: 'Kitchen',
      performance: manager.staff.kitchen ?? 0,
      icon: ChefHat,
    },

    {
      name: 'Restaurant / Waiter',
      performance: manager.staff.waiter ?? 0,
      icon: Users,
    },

    {
      name: 'Housekeeping',
      performance: manager.staff.housekeeping ?? 0,
      icon: BedDouble,
    },
  ]
})
</script>

<template>
  <section class="bg-white rounded-3xl border border-slate-200 shadow-sm p-6">
    <!-- Header -->

    <div class="flex justify-between items-center mb-8">
      <div>
        <h2 class="text-xl font-bold text-slate-900">Staff Performance</h2>

        <p class="text-sm text-slate-500">Employee productivity overview</p>
      </div>

      <div class="flex items-center gap-2 text-emerald-600 font-semibold">
        <TrendingUp class="w-5 h-5" />

        Good
      </div>
    </div>

    <!-- Summary Cards -->

    <div class="grid grid-cols-1 md:grid-cols-3 gap-5 mb-10">
      <div class="bg-slate-50 rounded-2xl p-5">
        <p class="text-sm text-slate-500">Total Employees</p>

        <h3 class="text-3xl font-bold mt-2">
          {{ summary.total }}
        </h3>
      </div>

      <div class="bg-green-50 rounded-2xl p-5">
        <p class="text-sm text-slate-500">Active Today</p>

        <h3 class="text-3xl font-bold mt-2">
          {{ summary.active }}
        </h3>
      </div>

      <div class="bg-blue-50 rounded-2xl p-5">
        <p class="text-sm text-slate-500">Completed Tasks</p>

        <h3 class="text-3xl font-bold mt-2">
          {{ summary.completed }}
        </h3>
      </div>
    </div>

    <!-- Department Performance -->

    <h3 class="font-bold mb-5">Department Performance</h3>

    <div class="space-y-6">
      <div v-for="department in departments" :key="department.name">
        <div class="flex justify-between items-center mb-2">
          <div class="flex items-center gap-3">
            <div class="w-10 h-10 rounded-xl bg-blue-50 flex items-center justify-center">
              <component :is="department.icon" class="w-5 h-5 text-blue-600" />
            </div>

            <span class="font-semibold">
              {{ department.name }}
            </span>
          </div>

          <span class="font-bold"> {{ department.performance }}% </span>
        </div>

        <div class="h-3 bg-slate-100 rounded-full overflow-hidden">
          <div
            class="h-full bg-blue-600 rounded-full transition-all"
            :style="{
              width: department.performance + '%',
            }"
          ></div>
        </div>
      </div>
    </div>
  </section>
</template>

<script setup lang="ts">
import { computed } from 'vue'

import { BedDouble, DoorOpen, LogIn, LogOut, TrendingUp } from 'lucide-vue-next'

import { useManagerStore } from '@/stores/managerStore'

const manager = useManagerStore()

const occupancy = computed(() => {
  return {
    total: manager.occupancyData.totalRooms ?? 0,

    occupied: manager.occupancyData.occupiedRooms ?? 0,

    available: manager.occupancyData.availableRooms ?? 0,

    checkIns: manager.occupancyData.todayCheckIns ?? 0,

    checkOuts: manager.occupancyData.todayCheckOuts ?? 0,
  }
})

/*
|--------------------------------------------------------------------------
| Calculate Percentage
|--------------------------------------------------------------------------
*/

const occupancyPercentage = computed(() => {
  if (!occupancy.value.total) return 0

  return Math.round((occupancy.value.occupied / occupancy.value.total) * 100)
})

/*
|--------------------------------------------------------------------------
| Color Status
|--------------------------------------------------------------------------
*/

const occupancyStatus = computed(() => {
  const value = occupancyPercentage.value

  if (value >= 90) return 'Very High'

  if (value >= 70) return 'Healthy'

  if (value >= 40) return 'Normal'

  return 'Low'
})
</script>

<template>
  <section class="bg-white rounded-3xl border border-slate-200 shadow-sm p-6">
    <!-- Header -->

    <div class="flex justify-between items-center mb-8">
      <div>
        <h2 class="text-xl font-bold text-slate-900">Occupancy Analytics</h2>

        <p class="text-sm text-slate-500 mt-1">Current hotel room performance</p>
      </div>

      <div class="flex items-center gap-2 text-emerald-600 font-semibold">
        <TrendingUp class="w-5 h-5" />

        {{ occupancyStatus }}
      </div>
    </div>

    <!-- Main Occupancy -->

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
      <!-- Percentage Card -->

      <div class="lg:col-span-2 bg-slate-50 rounded-3xl p-6">
        <div class="flex justify-between items-center">
          <h3 class="font-semibold">Current Occupancy</h3>

          <span class="text-3xl font-bold text-blue-600"> {{ occupancyPercentage }}% </span>
        </div>

        <!-- Progress -->

        <div class="mt-6 h-5 bg-slate-200 rounded-full overflow-hidden">
          <div
            class="h-full bg-blue-600 rounded-full transition-all duration-700"
            :style="{
              width: occupancyPercentage + '%',
            }"
          ></div>
        </div>
      </div>

      <!-- Room Numbers -->

      <div class="space-y-4">
        <div class="flex items-center justify-between bg-blue-50 rounded-2xl p-4">
          <div class="flex items-center gap-3">
            <BedDouble class="text-blue-600" />

            <span> Total Rooms </span>
          </div>

          <strong>
            {{ occupancy.total }}
          </strong>
        </div>

        <div class="flex items-center justify-between bg-emerald-50 rounded-2xl p-4">
          <div class="flex items-center gap-3">
            <BedDouble class="text-emerald-600" />

            <span> Occupied </span>
          </div>

          <strong>
            {{ occupancy.occupied }}
          </strong>
        </div>

        <div class="flex items-center justify-between bg-amber-50 rounded-2xl p-4">
          <div class="flex items-center gap-3">
            <DoorOpen class="text-amber-600" />

            <span> Available </span>
          </div>

          <strong>
            {{ occupancy.available }}
          </strong>
        </div>
      </div>
    </div>

    <!-- Daily Movement -->

    <div class="mt-8 grid grid-cols-1 md:grid-cols-2 gap-5">
      <div class="border rounded-2xl p-5 flex items-center justify-between">
        <div class="flex items-center gap-3">
          <div class="w-12 h-12 rounded-xl bg-green-100 flex items-center justify-center">
            <LogIn class="text-green-600" />
          </div>

          <div>
            <p class="text-sm text-slate-500">Today's Check-ins</p>

            <h3 class="text-2xl font-bold">
              {{ occupancy.checkIns }}
            </h3>
          </div>
        </div>
      </div>

      <div class="border rounded-2xl p-5 flex items-center justify-between">
        <div class="flex items-center gap-3">
          <div class="w-12 h-12 rounded-xl bg-red-100 flex items-center justify-center">
            <LogOut class="text-red-600" />
          </div>

          <div>
            <p class="text-sm text-slate-500">Today's Check-outs</p>

            <h3 class="text-2xl font-bold">
              {{ occupancy.checkOuts }}
            </h3>
          </div>
        </div>
      </div>
    </div>
  </section>
</template>

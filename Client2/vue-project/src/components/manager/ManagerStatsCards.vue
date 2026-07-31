<script setup lang="ts">
import { computed } from 'vue'

import {
  BedDouble,
  Users,
  DoorOpen,
  CircleDollarSign,
  ClipboardList,
  UserRoundCheck,
  LogIn,
  LogOut,
  Calendar,
} from 'lucide-vue-next'

import { useManagerStore } from '@/stores/managerStore'

const manager = useManagerStore()

/*
|--------------------------------------------------------------------------
| Dashboard Statistics
|--------------------------------------------------------------------------
*/

const stats = computed(() => {
  const stats = manager.safeStatistics || {}
  
  return [
    {
      title: 'Total Reservations',
      value: stats.totalReservations ?? 0,
      icon: Calendar,
      color: 'blue',
      description: 'All reservations',
    },

    {
      title: "Today's Check-ins",
      value: stats.todayCheckIns ?? 0,
      icon: LogIn,
      color: 'emerald',
      description: 'Guests checked in',
    },

    {
      title: "Today's Check-outs",
      value: stats.todayCheckOuts ?? 0,
      icon: LogOut,
      color: 'amber',
      description: 'Guests checked out',
    },

    {
      title: 'Available Rooms',
      value: stats.availableRooms ?? 0,
      icon: DoorOpen,
      color: 'purple',
      description: 'Ready for booking',
    },

    {
      title: 'Occupied Rooms',
      value: stats.occupiedRooms ?? 0,
      icon: Users,
      color: 'indigo',
      description: 'Currently occupied',
    },

    {
      title: "Today's Revenue",
      value: formatMoney(stats.todayRevenue ?? 0),
      icon: CircleDollarSign,
      color: 'rose',
      description: "Today's income",
    },
  ]
})

/*
|--------------------------------------------------------------------------
| Format Currency
|--------------------------------------------------------------------------
*/

function formatMoney(value: number) {
  return new Intl.NumberFormat('en-US', {
    style: 'currency',
    currency: 'ETB',
    maximumFractionDigits: 0,
  }).format(value)
}
</script>

<template>
  <section>
    <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-3 gap-6">
      <div
        v-for="item in stats"
        :key="item.title"
        class="bg-white rounded-3xl border border-slate-200 p-6 shadow-sm hover:shadow-lg transition-all duration-300 group"
      >
        <div class="flex items-center justify-between">
          <!-- ICON -->

          <div
            :class="[
              'w-14 h-14 rounded-2xl flex items-center justify-center',

              item.color === 'blue' ? 'bg-blue-100 text-blue-600' : '',

              item.color === 'emerald' ? 'bg-emerald-100 text-emerald-600' : '',

              item.color === 'amber' ? 'bg-amber-100 text-amber-600' : '',

              item.color === 'purple' ? 'bg-purple-100 text-purple-600' : '',

              item.color === 'indigo' ? 'bg-indigo-100 text-indigo-600' : '',

              item.color === 'red' ? 'bg-red-100 text-red-600' : '',
            ]"
          >
            <component :is="item.icon" class="w-7 h-7" />
          </div>

          <!-- STATUS DOT -->

          <div class="w-3 h-3 rounded-full bg-emerald-500 animate-pulse"></div>
        </div>

        <!-- CONTENT -->

        <div class="mt-6">
          <p class="text-sm font-medium text-slate-500">
            {{ item.title }}
          </p>

          <h2 class="mt-2 text-3xl font-bold text-slate-900">
            {{ item.value }}
          </h2>

          <p class="mt-2 text-sm text-slate-400">
            {{ item.description }}
          </p>
        </div>
      </div>
    </div>
  </section>
</template>

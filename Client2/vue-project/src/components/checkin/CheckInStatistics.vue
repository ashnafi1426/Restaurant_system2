<script setup lang="ts">
import { computed } from 'vue'
import type { CheckInStatistics } from '@/types/checkIn'

const props = defineProps<{
  statistics: CheckInStatistics
}>()

const cards = computed(() => [
  {
    title: 'Total Check Ins',
    value: props.statistics.total_check_ins,
    icon: 'mdi-account-group',
    iconBg: 'bg-blue-100',
    iconColor: 'text-blue-600',
    border: 'border-blue-500',
    description: 'All guest check-ins',
  },
  {
    title: 'Today',
    value: props.statistics.today_check_ins,
    icon: 'mdi-calendar-today',
    iconBg: 'bg-emerald-100',
    iconColor: 'text-emerald-600',
    border: 'border-emerald-500',
    description: 'Checked in today',
  },
  {
    title: 'Staying Guests',
    value: props.statistics.active_guests,
    icon: 'mdi-bed',
    iconBg: 'bg-orange-100',
    iconColor: 'text-orange-600',
    border: 'border-orange-500',
    description: 'Currently staying',
  },
  {
    title: 'Expected Checkout',
    value: props.statistics.expected_today,
    icon: 'mdi-door-open',
    iconBg: 'bg-red-100',
    iconColor: 'text-red-600',
    border: 'border-red-500',
    description: 'Leaving today',
  },
])
</script>

<template>
  <div class="grid grid-cols-1 gap-6 md:grid-cols-2 xl:grid-cols-4 mt-8">
    <div
      v-for="card in cards"
      :key="card.title"
      class="group relative overflow-hidden rounded-2xl border-l-4 bg-white p-6 shadow-sm transition-all duration-300 hover:-translate-y-1 hover:shadow-xl"
      :class="card.border"
    >
      <!-- Decorative Circle -->
      <div
        class="absolute -right-8 -top-8 h-28 w-28 rounded-full bg-slate-100 opacity-40 transition-all duration-300 group-hover:scale-125"
      ></div>

      <div class="relative flex items-center justify-between">
        <div>
          <p class="text-sm font-semibold uppercase tracking-wide text-slate-500">
            {{ card.title }}
          </p>

          <h2 class="mt-3 text-4xl font-bold text-slate-800">
            {{ card.value }}
          </h2>

          <p class="mt-2 text-sm text-slate-500">
            {{ card.description }}
          </p>
        </div>

        <div class="flex h-16 w-16 items-center justify-center rounded-2xl" :class="card.iconBg">
          <v-icon size="34" :class="card.iconColor">
            {{ card.icon }}
          </v-icon>
        </div>
      </div>
    </div>
  </div>
</template>

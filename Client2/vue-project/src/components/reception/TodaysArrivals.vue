<script setup lang="ts">
import type { ReservationInfo } from '@/types/reception'

interface Props {
  arrivals: ReservationInfo[]
}

defineProps<Props>()

const getInitials = (firstName: string, lastName: string) => {
  return `${firstName.charAt(0)}${lastName.charAt(0)}`.toUpperCase()
}

const formatTime = (date: string) => {
  return new Date(date).toLocaleTimeString('en-US', {
    hour: '2-digit',
    minute: '2-digit',
    hour12: true,
  })
}
</script>

<template>
  <div class="bg-blue-50 dark:bg-slate-800 rounded-lg border border-blue-200 dark:border-slate-700 p-5 sm:p-6 shadow-sm">
    <!-- Header -->
    <div
      class="flex items-center justify-between mb-4 pb-4 border-b border-blue-200 dark:border-slate-700"
    >
      <h3 class="text-lg sm:text-xl font-bold text-gray-900 dark:text-white">
        Today's Arrivals
      </h3>
      <span class="text-sm sm:text-base font-bold text-teal-600 dark:text-teal-400"
        >{{ arrivals.length }} Total</span
      >
    </div>

    <!-- Arrivals List -->
    <div v-if="arrivals.length > 0" class="space-y-4">
      <div
        v-for="arrival in arrivals.slice(0, 3)"
        :key="arrival.id"
        class="flex items-start gap-4 pb-4 border-l-4 border-teal-600 pl-4 last:pb-0 last:border-b-0"
      >
        <!-- Avatar -->
        <div class="flex-shrink-0">
          <div
            class="w-12 h-12 sm:w-14 sm:h-14 rounded-full bg-blue-100 dark:bg-blue-900/30 flex items-center justify-center"
          >
            <span class="text-sm sm:text-base font-bold text-blue-700 dark:text-blue-300">
              {{ getInitials(arrival.guest?.first_name || 'U', arrival.guest?.last_name || 'N') }}
            </span>
          </div>
        </div>

        <!-- Guest & Room Info -->
        <div class="flex-1 min-w-0">
          <p class="text-sm sm:text-base font-bold text-gray-900 dark:text-white truncate">
            {{ arrival.guest?.first_name }} {{ arrival.guest?.last_name }}
          </p>
          <p class="text-sm text-gray-600 dark:text-slate-400 mt-1 truncate">
            Room {{ arrival.room?.room_number }} · {{ arrival.room?.room_type?.name }}
          </p>
        </div>

        <!-- Check-in Time -->
        <div class="flex-shrink-0 text-right">
          <p class="text-sm sm:text-base font-bold text-gray-900 dark:text-white whitespace-nowrap">
            {{ formatTime(arrival.check_in_date) }}
          </p>
        </div>
      </div>
    </div>

    <!-- Empty State -->
    <div v-else class="text-center py-8">
      <p class="text-sm text-gray-500 dark:text-slate-400">No arrivals today</p>
    </div>

    <!-- View All Link -->
    <div
      v-if="arrivals.length > 3"
      class="mt-4 pt-4 border-t border-gray-200 dark:border-slate-700"
    >
      <button
        class="w-full py-2.5 text-sm sm:text-base font-bold text-teal-600 dark:text-teal-400 hover:text-teal-700 dark:hover:text-teal-300 transition"
      >
        View All Arrivals
      </button>
    </div>
  </div>
</template>

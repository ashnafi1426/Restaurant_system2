<script setup lang="ts">
import type { CheckInInfo } from '@/types/reception'

interface Props {
  departures: CheckInInfo[]
}

defineProps<Props>()

const getInitials = (firstName: string, lastName: string) => {
  return `${firstName.charAt(0)}${lastName.charAt(0)}`.toUpperCase()
}

const getStatusDisplay = (checkout_at: string | null) => {
  if (checkout_at) {
    return { label: 'Checked Out', color: 'text-green-600' }
  }
  return { label: 'Pending', color: 'text-orange-600' }
}
</script>

<template>
  <div class="bg-blue-50 dark:bg-slate-800/50 rounded-lg border border-blue-200 dark:border-slate-700 p-5 sm:p-6 shadow-sm">
    <!-- Header -->
    <div
      class="flex items-center justify-between mb-4 pb-4 border-b border-blue-200 dark:border-slate-700"
    >
      <h3 class="text-lg sm:text-xl font-bold text-gray-900 dark:text-white">
        Today's Departures
      </h3>
      <span class="text-sm sm:text-base font-bold text-red-600 dark:text-red-400"
        >{{ departures.length }} Total</span
      >
    </div>

    <!-- Departures List -->
    <div v-if="departures.length > 0" class="space-y-4">
      <div
        v-for="departure in departures.slice(0, 3)"
        :key="departure.id"
        class="flex items-start gap-4 pb-4 border-b border-gray-100 dark:border-slate-700 last:pb-0 last:border-b-0"
      >
        <!-- Avatar -->
        <div class="flex-shrink-0">
          <div
            class="w-12 h-12 sm:w-14 sm:h-14 rounded-full bg-purple-100 dark:bg-purple-900/40 flex items-center justify-center"
          >
            <span class="text-sm sm:text-base font-bold text-purple-700 dark:text-purple-300">
              {{
                getInitials(departure.guest?.first_name || 'U', departure.guest?.last_name || 'N')
              }}
            </span>
          </div>
        </div>

        <!-- Guest & Room Info -->
        <div class="flex-1 min-w-0">
          <p class="text-sm sm:text-base font-bold text-gray-900 dark:text-white truncate">
            {{ departure.guest?.first_name }} {{ departure.guest?.last_name }}
          </p>
          <p class="text-sm text-gray-600 dark:text-slate-400 mt-1 truncate">
            Room {{ departure.room?.room_number }} · {{ departure.room?.room_type?.name }}
          </p>
        </div>

        <!-- Status Badge -->
        <div class="flex-shrink-0 text-right">
          <span
            :class="`text-sm font-bold px-3 py-1 rounded-full ${getStatusDisplay(departure.checked_out_at).color}`"
          >
            {{ getStatusDisplay(departure.checked_out_at).label }}
          </span>
        </div>
      </div>
    </div>

    <!-- Empty State -->
    <div v-else class="text-center py-8">
      <p class="text-sm text-gray-500 dark:text-slate-400">No departures today</p>
    </div>

    <!-- View All Link -->
    <div
      v-if="departures.length > 3"
      class="mt-4 pt-4 border-t border-gray-200 dark:border-slate-700"
    >
      <button
        class="w-full py-2.5 text-sm sm:text-base font-bold text-teal-600 dark:text-teal-400 hover:text-teal-700 dark:hover:text-teal-300 transition"
      >
        View All Departures
      </button>
    </div>
  </div>
</template>

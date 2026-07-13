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
  <div class="bg-blue-50 rounded-lg border border-blue-200 p-4 sm:p-5 md:p-6 lg:p-8 shadow-sm">
    <!-- Header -->
    <div
      class="flex items-center justify-between mb-3 sm:mb-4 md:mb-5 pb-3 sm:pb-4 md:pb-5 border-b border-blue-200"
    >
      <h3 class="text-base sm:text-lg md:text-xl lg:text-2xl font-bold text-gray-900">
        Today's Arrivals
      </h3>
      <span class="text-xs sm:text-sm md:text-base font-bold text-teal-600"
        >{{ arrivals.length }} Total</span
      >
    </div>

    <!-- Arrivals List -->
    <div v-if="arrivals.length > 0" class="space-y-2 sm:space-y-3 md:space-y-4">
      <div
        v-for="arrival in arrivals.slice(0, 3)"
        :key="arrival.id"
        class="flex items-start gap-2 sm:gap-3 md:gap-4 pb-3 sm:pb-4 md:pb-5 border-l-4 border-teal-600 pl-2 sm:pl-3 md:pl-4 last:pb-0 last:border-b-0"
      >
        <!-- Avatar -->
        <div class="flex-shrink-0">
          <div
            class="w-10 h-10 sm:w-12 sm:h-12 md:w-14 md:h-14 rounded-full bg-blue-100 flex items-center justify-center flex-shrink-0"
          >
            <span class="text-xs sm:text-sm md:text-base font-bold text-blue-700">
              {{ getInitials(arrival.guest?.first_name || 'U', arrival.guest?.last_name || 'N') }}
            </span>
          </div>
        </div>

        <!-- Guest & Room Info -->
        <div class="flex-1 min-w-0">
          <p class="text-xs sm:text-sm md:text-base font-bold text-gray-900 truncate">
            {{ arrival.guest?.first_name }} {{ arrival.guest?.last_name }}
          </p>
          <p class="text-xs sm:text-sm md:text-base text-gray-600 mt-1 truncate">
            Room {{ arrival.room?.room_number }} · {{ arrival.room?.room_type?.name }}
          </p>
        </div>

        <!-- Check-in Time -->
        <div class="flex-shrink-0 text-right">
          <p class="text-xs sm:text-sm md:text-base font-bold text-gray-900 whitespace-nowrap">
            {{ formatTime(arrival.check_in_date) }}
          </p>
        </div>
      </div>
    </div>

    <!-- Empty State -->
    <div v-else class="text-center py-6 sm:py-8 md:py-10">
      <p class="text-xs sm:text-sm md:text-base text-gray-500">No arrivals today</p>
    </div>

    <!-- View All Link -->
    <div
      v-if="arrivals.length > 3"
      class="mt-3 sm:mt-4 md:mt-5 pt-3 sm:pt-4 md:pt-5 border-t border-gray-200"
    >
      <button
        class="w-full py-2 sm:py-2.5 md:py-3 min-h-10 text-xs sm:text-sm md:text-base font-bold text-teal-600 hover:text-teal-700 transition"
      >
        View All Arrivals
      </button>
    </div>
  </div>
</template>

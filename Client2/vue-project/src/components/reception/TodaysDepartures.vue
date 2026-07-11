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
  <div class="bg-blue-50 rounded-lg border border-blue-200 p-4 sm:p-5 md:p-6 lg:p-8 shadow-sm">
    <!-- Header -->
    <div class="flex items-center justify-between mb-3 sm:mb-4 md:mb-5 pb-3 sm:pb-4 md:pb-5 border-b border-blue-200">
      <h3 class="text-base sm:text-lg md:text-xl lg:text-2xl font-bold text-gray-900">Today's Departures</h3>
      <span class="text-xs sm:text-sm md:text-base font-bold text-red-600">{{ departures.length }} Total</span>
    </div>

    <!-- Departures List -->
    <div v-if="departures.length > 0" class="space-y-2 sm:space-y-3 md:space-y-4">
      <div
        v-for="departure in departures.slice(0, 3)"
        :key="departure.id"
        class="flex items-start gap-2 sm:gap-3 md:gap-4 pb-3 sm:pb-4 md:pb-5 border-b border-gray-100 last:pb-0 last:border-b-0"
      >
        <!-- Avatar -->
        <div class="flex-shrink-0">
          <div
            class="w-10 h-10 sm:w-12 sm:h-12 md:w-14 md:h-14 rounded-full bg-purple-100 flex items-center justify-center flex-shrink-0"
          >
            <span class="text-xs sm:text-sm md:text-base font-bold text-purple-700">
              {{
                getInitials(departure.guest?.first_name || 'U', departure.guest?.last_name || 'N')
              }}
            </span>
          </div>
        </div>

        <!-- Guest & Room Info -->
        <div class="flex-1 min-w-0">
          <p class="text-xs sm:text-sm md:text-base font-bold text-gray-900 truncate">
            {{ departure.guest?.first_name }} {{ departure.guest?.last_name }}
          </p>
          <p class="text-xs sm:text-sm md:text-base text-gray-600 mt-1 truncate">
            Room {{ departure.room?.room_number }} · {{ departure.room?.room_type?.name }}
          </p>
        </div>

        <!-- Status Badge -->
        <div class="flex-shrink-0 text-right">
          <span
            :class="`text-xs sm:text-sm md:text-base font-bold px-2 sm:px-3 md:px-3 py-1 rounded-full ${getStatusDisplay(departure.checked_out_at).color}`"
          >
            {{ getStatusDisplay(departure.checked_out_at).label }}
          </span>
        </div>
      </div>
    </div>

    <!-- Empty State -->
    <div v-else class="text-center py-6 sm:py-8 md:py-10">
      <p class="text-xs sm:text-sm md:text-base text-gray-500">No departures today</p>
    </div>

    <!-- View All Link -->
    <div v-if="departures.length > 3" class="mt-3 sm:mt-4 md:mt-5 pt-3 sm:pt-4 md:pt-5 border-t border-gray-200">
      <button class="w-full py-2 sm:py-2.5 md:py-3 min-h-10 text-xs sm:text-sm md:text-base font-bold text-teal-600 hover:text-teal-700 transition">
        View All Departures
      </button>
    </div>
  </div>
</template>

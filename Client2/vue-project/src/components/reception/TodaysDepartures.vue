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
  <div class="bg-blue-50 rounded-lg border border-blue-200 p-6 shadow-sm">
    <!-- Header -->
    <div class="flex items-center justify-between mb-5 pb-4 border-b border-blue-200">
      <h3 class="text-lg font-bold text-gray-900">Today's Departures</h3>
      <span class="text-sm font-bold text-red-600">{{ departures.length }} Total</span>
    </div>

    <!-- Departures List -->
    <div v-if="departures.length > 0" class="space-y-4">
      <div
        v-for="departure in departures.slice(0, 3)"
        :key="departure.id"
        class="flex items-start gap-4 pb-4 border-b border-gray-100 last:pb-0 last:border-b-0"
      >
        <!-- Avatar -->
        <div class="flex-shrink-0">
          <div
            class="w-12 h-12 rounded-full bg-purple-100 flex items-center justify-center flex-shrink-0"
          >
            <span class="text-sm font-bold text-purple-700">
              {{
                getInitials(departure.guest?.first_name || 'U', departure.guest?.last_name || 'N')
              }}
            </span>
          </div>
        </div>

        <!-- Guest & Room Info -->
        <div class="flex-1 min-w-0">
          <p class="text-sm font-bold text-gray-900">
            {{ departure.guest?.first_name }} {{ departure.guest?.last_name }}
          </p>
          <p class="text-xs text-gray-600 mt-1">
            Room {{ departure.room?.room_number }} · {{ departure.room?.room_type?.name }}
          </p>
        </div>

        <!-- Status Badge -->
        <div class="flex-shrink-0 text-right">
          <span
            :class="`text-xs font-bold px-3 py-1 rounded-full ${getStatusDisplay(departure.checked_out_at).color}`"
          >
            {{ getStatusDisplay(departure.checked_out_at).label }}
          </span>
        </div>
      </div>
    </div>

    <!-- Empty State -->
    <div v-else class="text-center py-8">
      <p class="text-sm text-gray-500">No departures today</p>
    </div>

    <!-- View All Link -->
    <div v-if="departures.length > 3" class="mt-4 pt-4 border-t border-gray-200">
      <button class="w-full py-2 text-sm font-bold text-teal-600 hover:text-teal-700 transition">
        View All Departures
      </button>
    </div>
  </div>
</template>

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
  <div class="bg-blue-50 rounded-lg border border-blue-200 p-6 shadow-sm">
    <!-- Header -->
    <div class="flex items-center justify-between mb-5 pb-4 border-b border-blue-200">
      <h3 class="text-lg font-bold text-gray-900">Today's Arrivals</h3>
      <span class="text-sm font-bold text-teal-600">{{ arrivals.length }} Total</span>
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
            class="w-12 h-12 rounded-full bg-blue-100 flex items-center justify-center flex-shrink-0"
          >
            <span class="text-sm font-bold text-blue-700">
              {{ getInitials(arrival.guest?.first_name || 'U', arrival.guest?.last_name || 'N') }}
            </span>
          </div>
        </div>

        <!-- Guest & Room Info -->
        <div class="flex-1 min-w-0">
          <p class="text-sm font-bold text-gray-900">
            {{ arrival.guest?.first_name }} {{ arrival.guest?.last_name }}
          </p>
          <p class="text-xs text-gray-600 mt-1">
            Room {{ arrival.room?.room_number }} · {{ arrival.room?.room_type?.name }}
          </p>
        </div>

        <!-- Check-in Time -->
        <div class="flex-shrink-0 text-right">
          <p class="text-sm font-bold text-gray-900">
            {{ formatTime(arrival.check_in_date) }}
          </p>
        </div>
      </div>
    </div>

    <!-- Empty State -->
    <div v-else class="text-center py-8">
      <p class="text-sm text-gray-500">No arrivals today</p>
    </div>

    <!-- View All Link -->
    <div v-if="arrivals.length > 3" class="mt-4 pt-4 border-t border-gray-200">
      <button class="w-full py-2 text-sm font-bold text-teal-600 hover:text-teal-700 transition">
        View All Arrivals
      </button>
    </div>
  </div>
</template>

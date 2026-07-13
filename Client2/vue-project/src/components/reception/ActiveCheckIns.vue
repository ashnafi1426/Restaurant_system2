<script setup lang="ts">
import type { CheckInInfo } from '@/types/reception'

interface Props {
  checkIns: CheckInInfo[]
}

defineProps<Props>()

const getInitials = (firstName: string, lastName: string) => {
  return `${firstName.charAt(0)}${lastName.charAt(0)}`.toUpperCase()
}

const calculateDaysRemaining = (checkOutDate: string) => {
  const now = new Date()
  const checkout = new Date(checkOutDate)
  const daysRemaining = Math.ceil((checkout.getTime() - now.getTime()) / (1000 * 60 * 60 * 24))
  return daysRemaining
}

const getDaysRemainingColor = (days: number) => {
  if (days < 0) return 'text-red-600 font-bold'
  if (days <= 1) return 'text-orange-600 font-bold'
  if (days <= 3) return 'text-yellow-600 font-bold'
  return 'text-green-600 font-bold'
}

const formatDate = (date: string) => {
  return new Date(date).toLocaleDateString('en-US', {
    month: 'short',
    day: 'numeric',
  })
}
</script>

<template>
  <div class="bg-white rounded-lg border border-gray-200 p-4 sm:p-5 md:p-6 lg:p-8 shadow-sm">
    <!-- Header -->
    <div class="flex items-center justify-between mb-3 sm:mb-4 md:mb-5">
      <h3 class="text-base sm:text-lg md:text-xl font-semibold text-gray-900">Active Guests</h3>
      <span class="text-xs sm:text-sm md:text-base text-gray-600 font-medium"
        >{{ checkIns.length }} Active</span
      >
    </div>

    <!-- Check-ins List -->
    <div v-if="checkIns.length > 0" class="space-y-2 sm:space-y-2.5 md:space-y-3">
      <div
        v-for="checkIn in checkIns.slice(0, 5)"
        :key="checkIn.id"
        class="flex items-center gap-2 sm:gap-3 md:gap-4 p-2 sm:p-3 md:p-4 bg-gray-50 rounded-lg hover:bg-gray-100 transition"
      >
        <!-- Avatar -->
        <div class="flex-shrink-0">
          <div
            class="w-9 h-9 sm:w-10 sm:h-10 md:w-12 md:h-12 rounded-full bg-teal-100 flex items-center justify-center"
          >
            <span class="text-xs sm:text-sm md:text-base font-bold text-teal-700">
              {{ getInitials(checkIn.guest?.first_name || 'U', checkIn.guest?.last_name || 'N') }}
            </span>
          </div>
        </div>

        <!-- Guest & Room Info -->
        <div class="flex-1 min-w-0">
          <p class="text-xs sm:text-sm md:text-base font-semibold text-gray-900 truncate">
            {{ checkIn.guest?.first_name }} {{ checkIn.guest?.last_name }}
          </p>
          <p class="text-xs sm:text-sm md:text-base text-gray-500 truncate">
            Room {{ checkIn.room?.room_number }} · {{ checkIn.room?.room_type?.name }}
          </p>
        </div>

        <!-- Days Remaining -->
        <div class="flex-shrink-0 text-right">
          <p
            :class="`text-xs sm:text-sm md:text-base ${getDaysRemainingColor(calculateDaysRemaining(checkIn.expected_check_out_at))}`"
          >
            {{ calculateDaysRemaining(checkIn.expected_check_out_at) }} days left
          </p>
          <p class="text-xs text-gray-500">
            {{ formatDate(checkIn.expected_check_out_at) }}
          </p>
        </div>
      </div>
    </div>

    <!-- Empty State -->
    <div v-else class="text-center py-6 sm:py-8 md:py-10">
      <p class="text-xs sm:text-sm md:text-base text-gray-500">No active guests</p>
    </div>

    <!-- View All Link -->
    <div
      v-if="checkIns.length > 5"
      class="mt-3 sm:mt-4 md:mt-5 pt-3 sm:pt-4 md:pt-5 border-t border-gray-100"
    >
      <button
        class="w-full py-2 sm:py-2.5 md:py-3 min-h-10 text-xs sm:text-sm md:text-base font-medium text-teal-600 hover:text-teal-700 transition"
      >
        View All Guests
      </button>
    </div>
  </div>
</template>

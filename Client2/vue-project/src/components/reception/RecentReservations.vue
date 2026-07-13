<script setup lang="ts">
import { ref, computed } from 'vue'
import type { ReservationInfo } from '@/types/reception'

interface Props {
  reservations: ReservationInfo[]
}

const props = defineProps<Props>()

const currentPage = ref(1)
const itemsPerPage = 5

const paginatedReservations = computed(() => {
  const start = (currentPage.value - 1) * itemsPerPage
  const end = start + itemsPerPage
  return props.reservations.slice(start, end)
})

const totalPages = computed(() => {
  return Math.ceil(props.reservations.length / itemsPerPage)
})

const getStatusColor = (status: string) => {
  const colors: Record<string, { bg: string; text: string }> = {
    pending: { bg: 'bg-yellow-100', text: 'text-yellow-700' },
    confirmed: { bg: 'bg-blue-100', text: 'text-blue-700' },
    checked_in: { bg: 'bg-green-100', text: 'text-green-700' },
    checked_out: { bg: 'bg-gray-100', text: 'text-gray-700' },
    cancelled: { bg: 'bg-red-100', text: 'text-red-700' },
  }
  return colors[status] || { bg: 'bg-gray-100', text: 'text-gray-700' }
}

const getStatusLabel = (status: string) => {
  const labels: Record<string, string> = {
    pending: 'Pending',
    confirmed: 'Confirmed',
    checked_in: 'Checked In',
    checked_out: 'Checked Out',
    cancelled: 'Cancelled',
  }
  return labels[status] || status
}

const formatDate = (date: string) => {
  const d = new Date(date)
  return d.toLocaleDateString('en-US', { month: 'short', day: 'numeric' })
}

const getInitials = (firstName: string, lastName: string) => {
  return `${firstName.charAt(0)}${lastName.charAt(0)}`.toUpperCase()
}

const goToPage = (page: number) => {
  if (page >= 1 && page <= totalPages.value) {
    currentPage.value = page
  }
}
</script>

<template>
  <div class="bg-blue-50 rounded-lg border border-blue-200 p-4 sm:p-5 md:p-6 lg:p-8 shadow-sm">
    <!-- Header -->
    <div
      class="flex items-center justify-between mb-3 sm:mb-4 md:mb-5 flex-col sm:flex-row gap-2 sm:gap-0"
    >
      <h3 class="text-base sm:text-lg md:text-xl lg:text-2xl font-bold text-gray-900">
        Recent Guest Requests
      </h3>
      <div class="text-xs sm:text-sm md:text-base text-gray-600 font-medium">
        Showing {{ (currentPage - 1) * itemsPerPage + 1 }}-{{
          Math.min(currentPage * itemsPerPage, reservations.length)
        }}
        of {{ reservations.length }}
      </div>
    </div>

    <!-- Table -->
    <div v-if="reservations.length > 0" class="overflow-x-auto -mx-4 sm:mx-0 rounded-lg">
      <table class="w-full text-xs sm:text-sm md:text-base">
        <thead>
          <tr class="border-b-2 border-gray-300 bg-gray-50">
            <th
              class="text-left py-2 sm:py-2.5 md:py-3 px-3 sm:px-4 md:px-5 font-bold text-gray-700 text-xs md:text-sm"
            >
              BOOKING REF
            </th>
            <th
              class="text-left py-2 sm:py-2.5 md:py-3 px-3 sm:px-4 md:px-5 font-bold text-gray-700 text-xs md:text-sm"
            >
              GUEST
            </th>
            <th
              class="hidden sm:table-cell text-left py-2 sm:py-2.5 md:py-3 px-3 sm:px-4 md:px-5 font-bold text-gray-700 text-xs md:text-sm"
            >
              ROOM
            </th>
            <th
              class="hidden md:table-cell text-left py-2 sm:py-2.5 md:py-3 px-3 sm:px-4 md:px-5 font-bold text-gray-700 text-xs md:text-sm"
            >
              CHECK-IN
            </th>
            <th
              class="hidden lg:table-cell text-left py-2 sm:py-2.5 md:py-3 px-3 sm:px-4 md:px-5 font-bold text-gray-700 text-xs md:text-sm"
            >
              CHECK-OUT
            </th>
            <th
              class="hidden lg:table-cell text-center py-2 sm:py-2.5 md:py-3 px-3 sm:px-4 md:px-5 font-bold text-gray-700 text-xs md:text-sm"
            >
              NIGHTS
            </th>
            <th
              class="text-left py-2 sm:py-2.5 md:py-3 px-3 sm:px-4 md:px-5 font-bold text-gray-700 text-xs md:text-sm"
            >
              STATUS
            </th>
            <th
              class="text-center py-2 sm:py-2.5 md:py-3 px-3 sm:px-4 md:px-5 font-bold text-gray-700 text-xs md:text-sm"
            >
              ACTION
            </th>
          </tr>
        </thead>
        <tbody>
          <tr
            v-for="reservation in paginatedReservations"
            :key="reservation.id"
            class="border-b border-gray-200 hover:bg-blue-100 transition py-1 text-xs sm:text-sm md:text-base"
          >
            <!-- Booking Reference -->
            <td class="py-2 sm:py-2.5 md:py-3 px-3 sm:px-4 md:px-5">
              <p class="text-xs sm:text-sm md:text-base font-bold text-blue-600">
                {{ reservation.booking_reference }}
              </p>
            </td>

            <!-- Guest (Compact) -->
            <td class="py-2 sm:py-2.5 md:py-3 px-3 sm:px-4 md:px-5">
              <div class="flex items-center gap-2">
                <div
                  class="w-6 h-6 sm:w-7 sm:h-7 md:w-8 md:h-8 rounded-full bg-purple-400 flex items-center justify-center flex-shrink-0 text-xs font-bold text-white"
                >
                  {{
                    getInitials(
                      reservation.guest?.first_name || 'U',
                      reservation.guest?.last_name || 'N',
                    )
                  }}
                </div>
                <span class="text-xs sm:text-sm md:text-base text-gray-700 truncate">{{
                  reservation.guest?.first_name || 'N/A'
                }}</span>
              </div>
            </td>

            <!-- Room -->
            <td class="hidden sm:table-cell py-2 sm:py-2.5 md:py-3 px-3 sm:px-4 md:px-5">
              <p class="text-xs sm:text-sm md:text-base font-semibold text-gray-900">
                {{ reservation.room?.room_number }}
              </p>
            </td>

            <!-- Check-in Date -->
            <td class="hidden md:table-cell py-2 sm:py-2.5 md:py-3 px-3 sm:px-4 md:px-5">
              <p class="text-xs sm:text-sm md:text-base text-gray-700">
                {{ formatDate(reservation.check_in_date) }}
              </p>
            </td>

            <!-- Check-out Date -->
            <td class="hidden lg:table-cell py-2 sm:py-2.5 md:py-3 px-3 sm:px-4 md:px-5">
              <p class="text-xs sm:text-sm md:text-base text-gray-700">
                {{ formatDate(reservation.check_out_date) }}
              </p>
            </td>

            <!-- Nights -->
            <td
              class="hidden lg:table-cell py-2 sm:py-2.5 md:py-3 px-3 sm:px-4 md:px-5 text-center"
            >
              <span class="text-xs sm:text-sm md:text-base font-bold text-gray-900">{{
                reservation.total_nights
              }}</span>
            </td>

            <!-- Status Badge -->
            <td class="py-2 sm:py-2.5 md:py-3 px-3 sm:px-4 md:px-5">
              <span
                :class="`inline-block text-xs sm:text-sm md:text-base font-bold px-2 sm:px-2.5 md:px-3 py-1 md:py-1.5 rounded-full min-h-10 flex items-center justify-center ${getStatusColor(reservation.status).bg} ${getStatusColor(reservation.status).text}`"
              >
                {{ getStatusLabel(reservation.status) }}
              </span>
            </td>

            <!-- Action Button -->
            <td class="py-2 sm:py-2.5 md:py-3 px-3 sm:px-4 md:px-5 text-center">
              <button
                class="text-gray-500 hover:text-gray-700 transition p-1 md:p-2 min-h-10 flex items-center justify-center w-full"
              >
                <svg
                  class="w-4 h-4 sm:w-5 sm:h-5 md:w-6 md:h-6"
                  fill="currentColor"
                  viewBox="0 0 24 24"
                >
                  <path
                    d="M6 10c-1.1 0-2 .9-2 2s.9 2 2 2 2-.9 2-2-.9-2-2-2zm12 0c-1.1 0-2 .9-2 2s.9 2 2 2 2-.9 2-2-.9-2-2-2zm-6 0c-1.1 0-2 .9-2 2s.9 2 2 2 2-.9 2-2-.9-2-2-2z"
                  />
                </svg>
              </button>
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <!-- Empty State -->
    <div v-else class="text-center py-6 sm:py-8 md:py-10">
      <p class="text-xs sm:text-sm md:text-base text-gray-500">No requests</p>
    </div>

    <!-- Pagination Footer -->
    <div
      v-if="reservations.length > 0"
      class="flex items-center justify-between mt-3 sm:mt-4 md:mt-5 pt-3 sm:pt-4 md:pt-5 border-t border-blue-200 flex-col sm:flex-row gap-2 sm:gap-0"
    >
      <div class="text-xs sm:text-sm md:text-base text-gray-600">
        Page {{ currentPage }} of {{ totalPages }}
      </div>
      <div class="flex gap-1 flex-wrap justify-center">
        <button
          @click="goToPage(currentPage - 1)"
          :disabled="currentPage === 1"
          class="px-2 sm:px-2.5 md:px-3 py-1 md:py-1.5 min-h-10 text-xs sm:text-sm md:text-base font-bold text-gray-700 bg-gray-200 rounded hover:bg-gray-300 disabled:opacity-50 disabled:cursor-not-allowed transition"
        >
          ← Prev
        </button>
        <button
          v-for="page in totalPages"
          :key="page"
          @click="goToPage(page)"
          :class="`px-2 sm:px-2.5 md:px-3 py-1 md:py-1.5 min-h-10 text-xs sm:text-sm md:text-base font-bold rounded transition ${
            currentPage === page
              ? 'bg-teal-600 text-white'
              : 'bg-gray-200 text-gray-700 hover:bg-gray-300'
          }`"
        >
          {{ page }}
        </button>
        <button
          @click="goToPage(currentPage + 1)"
          :disabled="currentPage === totalPages"
          class="px-2 sm:px-2.5 md:px-3 py-1 md:py-1.5 min-h-10 text-xs sm:text-sm md:text-base font-bold text-gray-700 bg-gray-200 rounded hover:bg-gray-300 disabled:opacity-50 disabled:cursor-not-allowed transition"
        >
          Next →
        </button>
      </div>
    </div>
  </div>
</template>

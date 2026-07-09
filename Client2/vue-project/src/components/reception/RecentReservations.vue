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
  <div class="bg-blue-50 rounded-lg border border-blue-200 p-6 shadow-sm">
    <!-- Header -->
    <div class="flex items-center justify-between mb-4">
      <h3 class="text-lg font-bold text-gray-900">Recent Guest Requests</h3>
      <div class="text-xs text-gray-600 font-medium">
        Showing {{ (currentPage - 1) * itemsPerPage + 1 }}-{{
          Math.min(currentPage * itemsPerPage, reservations.length)
        }}
        of {{ reservations.length }}
      </div>
    </div>

    <!-- Table -->
    <div v-if="reservations.length > 0" class="overflow-x-auto">
      <table class="w-full text-sm">
        <thead>
          <tr class="border-b-2 border-gray-300 bg-gray-50">
            <th class="text-left py-2 px-3 font-bold text-gray-700 text-xs">BOOKING REF</th>
            <th class="text-left py-2 px-3 font-bold text-gray-700 text-xs">GUEST</th>
            <th class="text-left py-2 px-3 font-bold text-gray-700 text-xs">ROOM</th>
            <th class="text-left py-2 px-3 font-bold text-gray-700 text-xs">CHECK-IN</th>
            <th class="text-left py-2 px-3 font-bold text-gray-700 text-xs">CHECK-OUT</th>
            <th class="text-center py-2 px-3 font-bold text-gray-700 text-xs">NIGHTS</th>
            <th class="text-left py-2 px-3 font-bold text-gray-700 text-xs">STATUS</th>
            <th class="text-center py-2 px-3 font-bold text-gray-700 text-xs">ACTION</th>
          </tr>
        </thead>
        <tbody>
          <tr
            v-for="reservation in paginatedReservations"
            :key="reservation.id"
            class="border-b border-gray-200 hover:bg-blue-100 transition py-1"
          >
            <!-- Booking Reference -->
            <td class="py-2 px-3">
              <p class="text-xs font-bold text-blue-600">{{ reservation.booking_reference }}</p>
            </td>

            <!-- Guest (Compact) -->
            <td class="py-2 px-3">
              <div class="flex items-center gap-2">
                <div
                  class="w-7 h-7 rounded-full bg-purple-400 flex items-center justify-center flex-shrink-0"
                >
                  <span class="text-xs font-bold text-white">
                    {{
                      getInitials(
                        reservation.guest?.first_name || 'U',
                        reservation.guest?.last_name || 'N',
                      )
                    }}
                  </span>
                </div>
                <span class="text-xs text-gray-700">{{
                  reservation.guest?.first_name || 'N/A'
                }}</span>
              </div>
            </td>

            <!-- Room -->
            <td class="py-2 px-3">
              <p class="text-xs font-semibold text-gray-900">{{ reservation.room?.room_number }}</p>
            </td>

            <!-- Check-in Date -->
            <td class="py-2 px-3">
              <p class="text-xs text-gray-700">{{ formatDate(reservation.check_in_date) }}</p>
            </td>

            <!-- Check-out Date -->
            <td class="py-2 px-3">
              <p class="text-xs text-gray-700">{{ formatDate(reservation.check_out_date) }}</p>
            </td>

            <!-- Nights -->
            <td class="py-2 px-3 text-center">
              <span class="text-xs font-bold text-gray-900">{{ reservation.total_nights }}</span>
            </td>

            <!-- Status Badge -->
            <td class="py-2 px-3">
              <span
                :class="`inline-block text-xs font-bold px-2 py-1 rounded-full ${getStatusColor(reservation.status).bg} ${getStatusColor(reservation.status).text}`"
              >
                {{ getStatusLabel(reservation.status) }}
              </span>
            </td>

            <!-- Action Button -->
            <td class="py-2 px-3 text-center">
              <button class="text-gray-500 hover:text-gray-700 transition">
                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24">
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
    <div v-else class="text-center py-8">
      <p class="text-sm text-gray-500">No requests</p>
    </div>

    <!-- Pagination Footer -->
    <div
      v-if="reservations.length > 0"
      class="flex items-center justify-between mt-4 pt-4 border-t border-blue-200"
    >
      <div class="text-xs text-gray-600">Page {{ currentPage }} of {{ totalPages }}</div>
      <div class="flex gap-1">
        <button
          @click="goToPage(currentPage - 1)"
          :disabled="currentPage === 1"
          class="px-2 py-1 text-xs font-bold text-gray-700 bg-gray-200 rounded hover:bg-gray-300 disabled:opacity-50 disabled:cursor-not-allowed transition"
        >
          ← Prev
        </button>
        <button
          v-for="page in totalPages"
          :key="page"
          @click="goToPage(page)"
          :class="`px-2 py-1 text-xs font-bold rounded transition ${
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
          class="px-2 py-1 text-xs font-bold text-gray-700 bg-gray-200 rounded hover:bg-gray-300 disabled:opacity-50 disabled:cursor-not-allowed transition"
        >
          Next →
        </button>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import type { DashboardReservation } from '../../types/dashboard'

interface Props {
  reservations?: DashboardReservation[]
}

withDefaults(defineProps<Props>(), {
  reservations: () => [],
})

const getStatusColor = (status: string) => {
  const colors: Record<string, string> = {
    Pending: 'bg-yellow-100 text-yellow-800',
    Confirmed: 'bg-green-100 text-green-800',
    Checked_in: 'bg-blue-100 text-blue-800',
    Checked_out: 'bg-gray-100 text-gray-800',
    Cancelled: 'bg-red-100 text-red-800',
  }
  return colors[status] || 'bg-gray-100 text-gray-800'
}

const formatDate = (date: string) => {
  return new Date(date).toLocaleDateString('en-US', {
    month: 'short',
    day: 'numeric',
    year: 'numeric',
  })
}

const formatCurrency = (amount: number) => {
  return new Intl.NumberFormat('en-US', {
    style: 'currency',
    currency: 'USD',
    minimumFractionDigits: 0,
  }).format(amount)
}

const getInitialColor = (initials: string) => {
  const colors = [
    'bg-teal-100 text-teal-700',
    'bg-blue-100 text-blue-700',
    'bg-purple-100 text-purple-700',
    'bg-pink-100 text-pink-700',
  ]
  return colors[initials.charCodeAt(0) % colors.length]
}
</script>

<template>
  <div
    class="bg-white rounded-lg border border-gray-200 px-4 sm:px-6 md:px-8 lg:px-10 py-4 sm:py-5 md:py-6 lg:py-8"
  >
    <div
      class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-2 sm:gap-3 mb-3 sm:mb-4 md:mb-6"
    >
      <div>
        <h3 class="text-base sm:text-lg md:text-xl lg:text-2xl font-semibold text-gray-900">
          Recent Reservations
        </h3>
      </div>
      <router-link
        to="/admin/reservations"
        class="text-teal-600 hover:text-teal-700 text-xs sm:text-sm font-medium whitespace-nowrap"
      >
        View All
      </router-link>
    </div>

    <div v-if="reservations.length === 0" class="text-center py-8 sm:py-10 md:py-12">
      <p class="text-gray-500 text-xs sm:text-sm md:text-base">No reservations yet</p>
    </div>

    <div v-else class="overflow-x-auto -mx-4 sm:-mx-6 md:-mx-8 lg:-mx-10">
      <table class="w-full">
        <thead>
          <tr class="border-b border-gray-200">
            <th
              class="text-left py-2 sm:py-3 md:py-4 px-4 sm:px-6 md:px-8 lg:px-10 text-xs font-semibold text-gray-600 uppercase"
            >
              Guest
            </th>
            <th
              class="text-left py-2 sm:py-3 md:py-4 px-4 sm:px-6 md:px-8 lg:px-10 text-xs font-semibold text-gray-600 uppercase hidden sm:table-cell"
            >
              Room Type
            </th>
            <th
              class="text-left py-2 sm:py-3 md:py-4 px-4 sm:px-6 md:px-8 lg:px-10 text-xs font-semibold text-gray-600 uppercase hidden md:table-cell"
            >
              Check In
            </th>
            <th
              class="text-left py-2 sm:py-3 md:py-4 px-4 sm:px-6 md:px-8 lg:px-10 text-xs font-semibold text-gray-600 uppercase"
            >
              Status
            </th>
            <th
              class="text-right py-2 sm:py-3 md:py-4 px-4 sm:px-6 md:px-8 lg:px-10 text-xs font-semibold text-gray-600 uppercase"
            >
              Amount
            </th>
          </tr>
        </thead>
        <tbody class="divide-y divide-gray-100">
          <tr
            v-for="reservation in reservations"
            :key="reservation.id"
            class="hover:bg-gray-50 transition-colors"
          >
            <td class="py-3 sm:py-4 md:py-5 px-4 sm:px-6 md:px-8 lg:px-10">
              <div class="flex items-center gap-2 sm:gap-3">
                <div
                  :class="[
                    getInitialColor(reservation.guest.initials),
                    'w-8 sm:w-9 md:w-10 h-8 sm:h-9 md:h-10 rounded-full flex items-center justify-center flex-shrink-0',
                  ]"
                >
                  <span class="text-xs sm:text-xs md:text-sm font-semibold">{{
                    reservation.guest.initials
                  }}</span>
                </div>
                <div class="min-w-0">
                  <p class="text-xs sm:text-sm font-medium text-gray-900 truncate">
                    {{ reservation.guest.name }}
                  </p>
                  <p class="text-xs text-gray-500 truncate">{{ reservation.booking_reference }}</p>
                </div>
              </div>
            </td>
            <td class="py-3 sm:py-4 md:py-5 px-4 sm:px-6 md:px-8 lg:px-10 hidden sm:table-cell">
              <span class="text-xs sm:text-sm text-gray-700 truncate">{{
                reservation.room_type
              }}</span>
            </td>
            <td class="py-3 sm:py-4 md:py-5 px-4 sm:px-6 md:px-8 lg:px-10 hidden md:table-cell">
              <span class="text-xs sm:text-sm text-gray-700">{{
                formatDate(reservation.check_in_date)
              }}</span>
            </td>
            <td class="py-3 sm:py-4 md:py-5 px-4 sm:px-6 md:px-8 lg:px-10">
              <span
                :class="getStatusColor(reservation.status)"
                class="text-xs font-semibold px-2 sm:px-2.5 py-1 rounded-full inline-block"
              >
                {{ reservation.status }}
              </span>
            </td>
            <td class="py-3 sm:py-4 md:py-5 px-4 sm:px-6 md:px-8 lg:px-10 text-right">
              <span class="text-xs sm:text-sm font-semibold text-gray-900">{{
                formatCurrency(reservation.total_price)
              }}</span>
            </td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>
</template>

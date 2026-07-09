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
  <div class="bg-white rounded-lg border border-gray-200 p-6">
    <div class="flex justify-between items-center mb-6">
      <div>
        <h3 class="text-lg font-semibold text-gray-900">Recent Reservations</h3>
      </div>
      <router-link
        to="/admin/reservations"
        class="text-teal-600 hover:text-teal-700 text-sm font-medium"
      >
        View All
      </router-link>
    </div>

    <div v-if="reservations.length === 0" class="text-center py-12">
      <p class="text-gray-500 text-sm">No reservations yet</p>
    </div>

    <div v-else class="overflow-x-auto">
      <table class="w-full">
        <thead>
          <tr class="border-b border-gray-200">
            <th class="text-left py-3 px-4 text-xs font-semibold text-gray-600 uppercase">Guest</th>
            <th class="text-left py-3 px-4 text-xs font-semibold text-gray-600 uppercase">
              Room Type
            </th>
            <th class="text-left py-3 px-4 text-xs font-semibold text-gray-600 uppercase">
              Check In
            </th>
            <th class="text-left py-3 px-4 text-xs font-semibold text-gray-600 uppercase">
              Status
            </th>
            <th class="text-right py-3 px-4 text-xs font-semibold text-gray-600 uppercase">
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
            <td class="py-4 px-4">
              <div class="flex items-center gap-3">
                <div
                  :class="[
                    getInitialColor(reservation.guest.initials),
                    'w-10 h-10 rounded-full flex items-center justify-center flex-shrink-0',
                  ]"
                >
                  <span class="text-xs font-semibold">{{ reservation.guest.initials }}</span>
                </div>
                <div>
                  <p class="text-sm font-medium text-gray-900">{{ reservation.guest.name }}</p>
                  <p class="text-xs text-gray-500">{{ reservation.booking_reference }}</p>
                </div>
              </div>
            </td>
            <td class="py-4 px-4">
              <span class="text-sm text-gray-700">{{ reservation.room_type }}</span>
            </td>
            <td class="py-4 px-4">
              <span class="text-sm text-gray-700">{{ formatDate(reservation.check_in_date) }}</span>
            </td>
            <td class="py-4 px-4">
              <span
                :class="getStatusColor(reservation.status)"
                class="text-xs font-semibold px-2.5 py-1 rounded-full"
              >
                {{ reservation.status }}
              </span>
            </td>
            <td class="py-4 px-4 text-right">
              <span class="text-sm font-semibold text-gray-900">{{
                formatCurrency(reservation.total_price)
              }}</span>
            </td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>
</template>

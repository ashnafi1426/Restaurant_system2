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
    Pending: 'bg-yellow-100 text-yellow-800 border-yellow-200',
    Confirmed: 'bg-emerald-100 text-emerald-800 border-emerald-200',
    Checked_in: 'bg-blue-100 text-blue-800 border-blue-200',
    Checked_out: 'bg-gray-100 text-gray-800 border-gray-200',
    Cancelled: 'bg-red-100 text-red-800 border-red-200',
  }
  return colors[status] || 'bg-gray-100 text-gray-800 border-gray-200'
}

const getStatusIcon = (status: string) => {
  const icons: Record<string, string> = {
    Pending: 'clock',
    Confirmed: 'check',
    Checked_in: 'login',
    Checked_out: 'logout',
    Cancelled: 'x',
  }
  return icons[status] || 'circle'
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
    class="bg-white rounded-2xl shadow-sm border border-gray-100 p-4 sm:p-6 lg:p-8 hover:shadow-md transition-shadow duration-300"
  >
    <!-- Header Section -->
    <div
      class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-3 sm:gap-4 mb-6 sm:mb-8 pb-4 sm:pb-5 border-b border-gray-100"
    >
      <div>
        <h3 class="text-xl sm:text-2xl font-bold text-slate-900">
          Recent Reservations
        </h3>
        <p class="text-sm text-slate-600 mt-1.5">Latest bookings and status overview</p>
      </div>
      <router-link
        to="/admin/reservations"
        class="px-4 sm:px-5 py-2 sm:py-2.5 bg-gradient-to-r from-teal-600 to-teal-700 hover:from-teal-700 hover:to-teal-800 text-white text-xs sm:text-sm font-semibold rounded-lg transition-all shadow-sm hover:shadow-md whitespace-nowrap group"
      >
        <span class="flex items-center gap-2">
          View All
          <svg class="w-4 h-4 group-hover:translate-x-1 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
          </svg>
        </span>
      </router-link>
    </div>

    <!-- Empty State -->
    <div v-if="reservations.length === 0" class="text-center py-16">
      <div class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-slate-100 mb-4">
        <svg class="w-8 h-8 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
        </svg>
      </div>
      <p class="text-slate-600 font-semibold">No reservations yet</p>
      <p class="text-sm text-slate-500 mt-1">Bookings will appear here once created</p>
    </div>

    <!-- Table -->
    <div v-else class="overflow-x-auto -mx-4 sm:-mx-6 lg:-mx-8">
      <table class="w-full">
        <!-- Table Header -->
        <thead>
          <tr class="border-b border-gray-200 bg-slate-50">
            <th
              class="text-left py-4 px-4 sm:px-6 lg:px-8 text-xs font-bold text-slate-700 uppercase tracking-widest"
            >
              Guest
            </th>
            <th
              class="text-left py-4 px-4 sm:px-6 lg:px-8 text-xs font-bold text-slate-700 uppercase tracking-widest hidden sm:table-cell"
            >
              Room Type
            </th>
            <th
              class="text-left py-4 px-4 sm:px-6 lg:px-8 text-xs font-bold text-slate-700 uppercase tracking-widest hidden md:table-cell"
            >
              Check In
            </th>
            <th
              class="text-left py-4 px-4 sm:px-6 lg:px-8 text-xs font-bold text-slate-700 uppercase tracking-widest"
            >
              Status
            </th>
            <th
              class="text-right py-4 px-4 sm:px-6 lg:px-8 text-xs font-bold text-slate-700 uppercase tracking-widest"
            >
              Total
            </th>
          </tr>
        </thead>
        <!-- Table Body -->
        <tbody class="divide-y divide-gray-100">
          <tr
            v-for="reservation in reservations"
            :key="reservation.id"
            class="hover:bg-slate-50/50 transition-colors duration-200 group"
          >
            <!-- Guest Column -->
            <td class="py-4 px-4 sm:px-6 lg:px-8">
              <div class="flex items-center gap-3">
                <div
                  :class="[
                    getInitialColor(reservation.guest.initials),
                    'w-9 h-9 rounded-full flex items-center justify-center flex-shrink-0 font-semibold text-sm shadow-sm group-hover:scale-110 transition-transform',
                  ]"
                >
                  {{ reservation.guest.initials }}
                </div>
                <div class="min-w-0">
                  <p class="text-sm font-semibold text-slate-900 truncate">
                    {{ reservation.guest.name }}
                  </p>
                  <p class="text-xs text-slate-500 truncate font-medium mt-0.5">{{ reservation.booking_reference }}</p>
                </div>
              </div>
            </td>
            <!-- Room Type Column -->
            <td class="py-4 px-4 sm:px-6 lg:px-8 hidden sm:table-cell">
              <span class="text-sm text-slate-700 font-medium">{{
                reservation.room_type
              }}</span>
            </td>
            <!-- Check In Column -->
            <td class="py-4 px-4 sm:px-6 lg:px-8 hidden md:table-cell">
              <div>
                <span class="text-sm text-slate-700 font-medium block">{{
                  formatDate(reservation.check_in_date)
                }}</span>
                <span class="text-xs text-slate-500 mt-0.5 block">Check-in</span>
              </div>
            </td>
            <!-- Status Column -->
            <td class="py-4 px-4 sm:px-6 lg:px-8">
              <span
                :class="[getStatusColor(reservation.status), 'text-xs font-bold px-2.5 py-1.5 rounded-full inline-flex items-center gap-1.5 border']"
              >
                <span class="w-1.5 h-1.5 rounded-full bg-current"></span>
                {{ reservation.status }}
              </span>
            </td>
            <!-- Amount Column -->
            <td class="py-4 px-4 sm:px-6 lg:px-8 text-right">
              <span class="text-sm font-bold text-slate-900">{{
                formatCurrency(reservation.total_price)
              }}</span>
            </td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed } from 'vue'
import { useRouter } from 'vue-router'
import type { ReservationInfo } from '@/types/reception'
import { Calendar, User, BedDouble, Clock, ChevronLeft, ChevronRight, Eye } from 'lucide-vue-next'

interface Props {
  reservations: ReservationInfo[]
}

const props = defineProps<Props>()
const router = useRouter()

const currentPage = ref(1)
const itemsPerPage = 5

const paginatedReservations = computed(() => {
  const start = (currentPage.value - 1) * itemsPerPage
  const end = start + itemsPerPage
  return props.reservations.slice(start, end)
})

const totalPages = computed(() => {
  return Math.ceil(props.reservations.length / itemsPerPage) || 1
})

const getStatusBadge = (status: string) => {
  const map: Record<string, { bg: string; text: string; dot: string; label: string }> = {
    pending: {
      bg: 'bg-amber-500/10 dark:bg-amber-500/20',
      text: 'text-amber-700 dark:text-amber-400',
      dot: 'bg-amber-500',
      label: 'Pending'
    },
    confirmed: {
      bg: 'bg-blue-500/10 dark:bg-blue-500/20',
      text: 'text-blue-700 dark:text-blue-400',
      dot: 'bg-blue-500',
      label: 'Confirmed'
    },
    checked_in: {
      bg: 'bg-emerald-500/10 dark:bg-emerald-500/20',
      text: 'text-emerald-700 dark:text-emerald-400',
      dot: 'bg-emerald-500',
      label: 'Checked In'
    },
    checked_out: {
      bg: 'bg-slate-500/10 dark:bg-slate-500/20',
      text: 'text-slate-700 dark:text-slate-400',
      dot: 'bg-slate-500',
      label: 'Checked Out'
    },
    cancelled: {
      bg: 'bg-rose-500/10 dark:bg-rose-500/20',
      text: 'text-rose-700 dark:text-rose-400',
      dot: 'bg-rose-500',
      label: 'Cancelled'
    },
  }
  return map[status] || {
    bg: 'bg-slate-100 dark:bg-slate-800',
    text: 'text-slate-700 dark:text-slate-300',
    dot: 'bg-slate-400',
    label: status
  }
}

const formatDate = (date: string) => {
  if (!date) return 'N/A'
  const d = new Date(date)
  return d.toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' })
}

const getInitials = (firstName: string, lastName: string) => {
  const f = firstName ? firstName.charAt(0) : 'G'
  const l = lastName ? lastName.charAt(0) : ''
  return `${f}${l}`.toUpperCase()
}

const goToPage = (page: number) => {
  if (page >= 1 && page <= totalPages.value) {
    currentPage.value = page
  }
}

const viewReservationDetails = (id: string) => {
  router.push('/reservations')
}
</script>

<template>
  <div class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-800 shadow-xs overflow-hidden">
    <div class="px-5 py-4 border-b border-slate-200 dark:border-slate-800 flex items-center justify-between gap-4">
      <div class="flex items-center gap-2.5">
        <div class="w-8 h-8 rounded-xl bg-blue-500/10 dark:bg-blue-500/20 text-blue-600 dark:text-blue-400 flex items-center justify-center font-bold text-sm">
          <Calendar class="w-4 h-4" />
        </div>
        <div>
          <h3 class="text-base font-bold text-slate-900 dark:text-white">Recent Guest Reservations</h3>
          <p class="text-xs text-slate-500 dark:text-slate-400">Live booking activity and requests</p>
        </div>
      </div>

      <div class="text-xs font-semibold text-slate-500 dark:text-slate-400 bg-slate-100 dark:bg-slate-800 px-3 py-1.5 rounded-xl">
        {{ reservations.length }} total
      </div>
    </div>

    <div v-if="reservations.length > 0" class="overflow-x-auto">
      <table class="w-full text-left border-collapse">
        <thead>
          <tr class="bg-slate-50/80 dark:bg-slate-800/50 border-b border-slate-200 dark:border-slate-800 text-[11px] font-black uppercase text-slate-500 dark:text-slate-400 tracking-wider">
            <th class="py-3 px-4">Booking Ref</th>
            <th class="py-3 px-4">Guest</th>
            <th class="py-3 px-4">Room</th>
            <th class="py-3 px-4">Check-In</th>
            <th class="py-3 px-4">Check-Out</th>
            <th class="py-3 px-4 text-center">Nights</th>
            <th class="py-3 px-4 text-center">Status</th>
            <th class="py-3 px-4 text-right">Action</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-slate-100 dark:divide-slate-800/60 text-xs">
          <tr
            v-for="res in paginatedReservations"
            :key="res.id"
            class="hover:bg-slate-50/60 dark:hover:bg-slate-800/40 transition-colors"
          >
            <td class="py-3 px-4 font-mono font-bold text-blue-600 dark:text-blue-400 whitespace-nowrap">
              {{ res.booking_reference }}
            </td>

            <td class="py-3 px-4 whitespace-nowrap">
              <div class="flex items-center gap-2.5">
                <div class="w-7 h-7 rounded-full bg-purple-500/10 text-purple-600 dark:bg-purple-500/20 dark:text-purple-300 flex items-center justify-center text-xs font-bold flex-shrink-0">
                  {{ getInitials(res.guest?.first_name || '', res.guest?.last_name || '') }}
                </div>
                <span class="font-semibold text-slate-900 dark:text-white truncate max-w-[120px]">
                  {{ res.guest?.first_name }} {{ res.guest?.last_name }}
                </span>
              </div>
            </td>

            <td class="py-3 px-4 whitespace-nowrap">
              <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg bg-slate-100 dark:bg-slate-800 font-bold text-slate-800 dark:text-slate-200">
                <BedDouble class="w-3.5 h-3.5 text-slate-400" />
                Room {{ res.room?.room_number || 'N/A' }}
              </span>
            </td>

            <td class="py-3 px-4 whitespace-nowrap font-medium text-slate-600 dark:text-slate-300">
              {{ formatDate(res.check_in_date) }}
            </td>

            <td class="py-3 px-4 whitespace-nowrap font-medium text-slate-600 dark:text-slate-300">
              {{ formatDate(res.check_out_date) }}
            </td>

            <td class="py-3 px-4 whitespace-nowrap text-center font-bold text-slate-900 dark:text-white">
              {{ res.total_nights || 1 }}
            </td>

            <td class="py-3 px-4 whitespace-nowrap text-center">
              <span
                class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-bold"
                :class="[getStatusBadge(res.status).bg, getStatusBadge(res.status).text]"
              >
                <span class="w-1.5 h-1.5 rounded-full" :class="getStatusBadge(res.status).dot" />
                {{ getStatusBadge(res.status).label }}
              </span>
            </td>

            <td class="py-3 px-4 whitespace-nowrap text-right">
              <button
                @click="viewReservationDetails(res.id)"
                class="inline-flex items-center gap-1 px-2.5 py-1.5 text-xs font-semibold text-blue-600 dark:text-blue-400 hover:bg-blue-50 dark:hover:bg-blue-900/30 rounded-lg transition"
                title="View Reservation"
              >
                <Eye class="w-3.5 h-3.5" />
                <span>View</span>
              </button>
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <div v-else class="text-center py-12 px-4">
      <Clock class="w-8 h-8 text-slate-300 dark:text-slate-600 mx-auto mb-2" />
      <p class="text-sm font-semibold text-slate-600 dark:text-slate-400">No recent reservations found</p>
    </div>

    <div
      v-if="reservations.length > 0"
      class="px-5 py-3 border-t border-slate-200 dark:border-slate-800 bg-slate-50/50 dark:bg-slate-900 flex items-center justify-between text-xs"
    >
      <span class="text-slate-500 dark:text-slate-400">
        Page <strong>{{ currentPage }}</strong> of <strong>{{ totalPages }}</strong>
      </span>

      <div class="flex items-center gap-1">
        <button
          @click="goToPage(currentPage - 1)"
          :disabled="currentPage === 1"
          class="p-1.5 rounded-lg border border-slate-200 dark:border-slate-800 hover:bg-white dark:hover:bg-slate-800 disabled:opacity-40 disabled:cursor-not-allowed transition"
        >
          <ChevronLeft class="w-4 h-4" />
        </button>
        <button
          @click="goToPage(currentPage + 1)"
          :disabled="currentPage === totalPages"
          class="p-1.5 rounded-lg border border-slate-200 dark:border-slate-800 hover:bg-white dark:hover:bg-slate-800 disabled:opacity-40 disabled:cursor-not-allowed transition"
        >
          <ChevronRight class="w-4 h-4" />
        </button>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue'

interface Props {
  reservations?: any[]
}

const props = withDefaults(defineProps<Props>(), {
  reservations: () => [],
})

const defaultReservations = [
  { id: 1, guest_name: 'Abebe Bikila', room_type: 'Deluxe Suite', check_in: '2026-08-23', status: 'Confirmed', total: 4500 },
  { id: 2, guest_name: 'Tigist Assefa', room_type: 'Executive Room', check_in: '2026-08-24', status: 'Pending', total: 3200 },
  { id: 3, guest_name: 'Haile Gebrselassie', room_type: 'Presidential Suite', check_in: '2026-08-22', status: 'Checked_in', total: 12000 },
  { id: 4, guest_name: 'Derartu Tulu', room_type: 'Standard King', check_in: '2026-08-20', status: 'Checked_out', total: 2800 },
]

const displayReservations = computed(() => {
  if (props.reservations && props.reservations.length > 0) {
    return props.reservations.map((res: any) => {
      let gName = res.guest_name || res.guest?.name
      if (!gName || gName === ' ') {
        gName = res.guest?.email || 'Guest #' + String(res.id || '').substring(0, 6)
      }
      const roomType = res.room_type || res.room?.roomType?.name || res.room?.name || 'Standard'
      const checkIn = res.check_in || res.check_in_date || res.created_at || '-'
      const price = Number(res.total ?? res.total_price ?? res.total_amount ?? 2500)

      return {
        id: res.id,
        guest_name: gName,
        room_type: roomType,
        check_in: checkIn,
        status: res.status || 'Confirmed',
        total: price > 0 ? price : 2500,
      }
    })
  }
  return defaultReservations
})

const formatDate = (dateStr: string) => {
  if (!dateStr || dateStr === '-') return '-'
  try {
    return new Date(dateStr).toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' })
  } catch {
    return dateStr
  }
}

const formatCurrency = (val: number) => {
  return `${(val || 0).toLocaleString()} ETB`
}
</script>

<template>
  <div class="bg-white dark:bg-slate-900 rounded-3xl border border-slate-200 dark:border-slate-800 p-5 shadow-xs space-y-4">
    <!-- Header Section -->
    <div class="flex items-center justify-between pb-3 border-b border-slate-100 dark:border-slate-800">
      <div>
        <h3 class="text-base font-black text-slate-900 dark:text-white">Recent Reservations</h3>
        <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">Latest bookings and status overview</p>
      </div>
      <router-link
        to="/reservations"
        class="px-3.5 py-1.5 bg-blue-600 hover:bg-blue-700 text-white text-xs font-black rounded-xl transition cursor-pointer shadow-xs"
      >
        View All
      </router-link>
    </div>

    <!-- Table -->
    <div class="overflow-x-auto w-full">
      <table class="w-full text-left border-collapse">
        <thead>
          <tr class="bg-slate-50 dark:bg-slate-950 border-b border-slate-200 dark:border-slate-800 text-[10px] font-black uppercase text-slate-500 dark:text-slate-400 tracking-wider">
            <th class="px-3.5 py-3 whitespace-nowrap">Guest</th>
            <th class="px-3.5 py-3 whitespace-nowrap">Room Type</th>
            <th class="px-3.5 py-3 whitespace-nowrap">Check In</th>
            <th class="px-3.5 py-3 whitespace-nowrap">Status</th>
            <th class="px-3.5 py-3 text-right whitespace-nowrap pr-4">Total</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-slate-100 dark:divide-slate-800/60 text-xs">
          <tr v-for="res in displayReservations" :key="res.id" class="hover:bg-slate-50/70 dark:hover:bg-slate-800/40 transition">
            <td class="px-3.5 py-3 font-extrabold text-slate-900 dark:text-white whitespace-nowrap">
              {{ res.guest_name }}
            </td>
            <td class="px-3.5 py-3 font-medium text-slate-600 dark:text-slate-400 whitespace-nowrap">
              {{ res.room_type }}
            </td>
            <td class="px-3.5 py-3 font-medium text-slate-500 dark:text-slate-400 whitespace-nowrap">
              {{ formatDate(res.check_in) }}
            </td>
            <td class="px-3.5 py-3 whitespace-nowrap">
              <span
                :class="[
                  'px-2.5 py-0.5 rounded-full text-[10px] font-black uppercase tracking-wider border',
                  (res.status === 'Confirmed' || res.status === 'confirmed') && 'bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 border-emerald-500/20',
                  (res.status === 'Pending' || res.status === 'pending') && 'bg-amber-500/10 text-amber-600 dark:text-amber-400 border-amber-500/20',
                  (res.status === 'Checked_in' || res.status === 'checked_in') && 'bg-blue-500/10 text-blue-600 dark:text-blue-400 border-blue-500/20',
                  (res.status === 'Checked_out' || res.status === 'checked_out') && 'bg-slate-500/10 text-slate-600 dark:text-slate-400 border-slate-500/20',
                  (res.status === 'Cancelled' || res.status === 'cancelled') && 'bg-rose-500/10 text-rose-600 dark:text-rose-400 border-rose-500/20'
                ]"
              >
                {{ res.status }}
              </span>
            </td>
            <td class="px-3.5 py-3 text-right font-black text-slate-900 dark:text-white whitespace-nowrap pr-4">
              {{ formatCurrency(res.total) }}
            </td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>
</template>

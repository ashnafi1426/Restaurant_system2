<script setup lang="ts">
import { useRouter } from 'vue-router'
import type { ReservationInfo } from '@/types/reception'
import { LogIn, ArrowRight, UserCheck } from 'lucide-vue-next'

interface Props {
  arrivals: ReservationInfo[]
}

defineProps<Props>()
const router = useRouter()

const getInitials = (firstName: string, lastName: string) => {
  const f = firstName ? firstName.charAt(0) : 'G'
  const l = lastName ? lastName.charAt(0) : ''
  return `${f}${l}`.toUpperCase()
}

const formatTime = (date: string) => {
  if (!date) return '12:00 PM'
  return new Date(date).toLocaleTimeString('en-US', {
    hour: '2-digit',
    minute: '2-digit',
    hour12: true,
  })
}

const goToCheckIn = () => {
  router.push('/check-in')
}
</script>

<template>
  <div class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-800 p-5 shadow-xs">
    <div class="flex items-center justify-between gap-4 pb-4 border-b border-slate-200 dark:border-slate-800">
      <div class="flex items-center gap-2.5">
        <div class="w-8 h-8 rounded-xl bg-emerald-500/10 dark:bg-emerald-500/20 text-emerald-600 dark:text-emerald-400 flex items-center justify-center font-bold text-sm">
          <LogIn class="w-4 h-4" />
        </div>
        <div>
          <h3 class="text-base font-bold text-slate-900 dark:text-white">Today's Expected Arrivals</h3>
          <p class="text-xs text-slate-500 dark:text-slate-400">Scheduled guest check-ins</p>
        </div>
      </div>
      <span class="px-2.5 py-1 text-xs font-bold bg-emerald-500/10 text-emerald-700 dark:text-emerald-400 rounded-full">
        {{ arrivals.length }} Total
      </span>
    </div>

    <div v-if="arrivals.length > 0" class="divide-y divide-slate-100 dark:divide-slate-800/60 my-2">
      <div
        v-for="arrival in arrivals.slice(0, 4)"
        :key="arrival.id"
        class="py-3 flex items-center justify-between gap-3 hover:bg-slate-50/50 dark:hover:bg-slate-800/30 px-2 rounded-xl transition"
      >
        <div class="flex items-center gap-3 min-w-0">
          <div class="w-9 h-9 rounded-full bg-emerald-500/10 text-emerald-600 dark:bg-emerald-500/20 dark:text-emerald-300 font-extrabold flex items-center justify-center text-xs flex-shrink-0">
            {{ getInitials(arrival.guest?.first_name || '', arrival.guest?.last_name || '') }}
          </div>
          <div class="min-w-0">
            <p class="text-xs font-bold text-slate-900 dark:text-white truncate">
              {{ arrival.guest?.first_name }} {{ arrival.guest?.last_name }}
            </p>
            <p class="text-[11px] font-medium text-slate-500 dark:text-slate-400 truncate">
              Room {{ arrival.room?.room_number || 'TBD' }} · {{ arrival.booking_reference }}
            </p>
          </div>
        </div>

        <div class="flex items-center gap-2 flex-shrink-0">
          <span class="text-xs font-semibold text-slate-500 dark:text-slate-400">
            {{ formatTime(arrival.check_in_date) }}
          </span>
          <button
            @click="goToCheckIn"
            class="px-2.5 py-1 text-xs font-bold text-emerald-600 dark:text-emerald-400 bg-emerald-500/10 hover:bg-emerald-500/20 rounded-lg transition"
          >
            Check In
          </button>
        </div>
      </div>
    </div>

    <div v-else class="text-center py-8">
      <UserCheck class="w-7 h-7 text-slate-300 dark:text-slate-600 mx-auto mb-2" />
      <p class="text-xs font-semibold text-slate-500 dark:text-slate-400">No expected arrivals for today</p>
    </div>
  </div>
</template>

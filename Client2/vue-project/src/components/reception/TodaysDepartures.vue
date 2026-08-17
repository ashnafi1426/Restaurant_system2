<script setup lang="ts">
import { useRouter } from 'vue-router'
import type { CheckInInfo } from '@/types/reception'
import { LogOut, CheckCircle, Clock } from 'lucide-vue-next'

interface Props {
  departures: CheckInInfo[]
}

defineProps<Props>()
const router = useRouter()

const getInitials = (firstName: string, lastName: string) => {
  const f = firstName ? firstName.charAt(0) : 'G'
  const l = lastName ? lastName.charAt(0) : ''
  return `${f}${l}`.toUpperCase()
}

const goToCheckOut = () => {
  router.push('/check-out')
}
</script>

<template>
  <div class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-800 p-5 shadow-xs">
    <!-- Header -->
    <div class="flex items-center justify-between gap-4 pb-4 border-b border-slate-200 dark:border-slate-800">
      <div class="flex items-center gap-2.5">
        <div class="w-8 h-8 rounded-xl bg-rose-500/10 dark:bg-rose-500/20 text-rose-600 dark:text-rose-400 flex items-center justify-center font-bold text-sm">
          <LogOut class="w-4 h-4" />
        </div>
        <div>
          <h3 class="text-base font-bold text-slate-900 dark:text-white">Today's Expected Departures</h3>
          <p class="text-xs text-slate-500 dark:text-slate-400">Scheduled guest check-outs</p>
        </div>
      </div>
      <span class="px-2.5 py-1 text-xs font-bold bg-rose-500/10 text-rose-700 dark:text-rose-400 rounded-full">
        {{ departures.length }} Total
      </span>
    </div>

    <!-- Departures List -->
    <div v-if="departures.length > 0" class="divide-y divide-slate-100 dark:divide-slate-800/60 my-2">
      <div
        v-for="dep in departures.slice(0, 4)"
        :key="dep.id"
        class="py-3 flex items-center justify-between gap-3 hover:bg-slate-50/50 dark:hover:bg-slate-800/30 px-2 rounded-xl transition"
      >
        <div class="flex items-center gap-3 min-w-0">
          <div class="w-9 h-9 rounded-full bg-rose-500/10 text-rose-600 dark:bg-rose-500/20 dark:text-rose-300 font-extrabold flex items-center justify-center text-xs flex-shrink-0">
            {{ getInitials(dep.guest?.first_name || '', dep.guest?.last_name || '') }}
          </div>
          <div class="min-w-0">
            <p class="text-xs font-bold text-slate-900 dark:text-white truncate">
              {{ dep.guest?.first_name }} {{ dep.guest?.last_name }}
            </p>
            <p class="text-[11px] font-medium text-slate-500 dark:text-slate-400 truncate">
              Room {{ dep.room?.room_number || 'N/A' }}
            </p>
          </div>
        </div>

        <div class="flex items-center gap-2 flex-shrink-0">
          <span
            v-if="dep.checked_out_at"
            class="px-2.5 py-1 text-xs font-bold text-emerald-700 dark:text-emerald-400 bg-emerald-500/10 rounded-full inline-flex items-center gap-1"
          >
            <CheckCircle class="w-3 h-3" /> Checked Out
          </span>
          <button
            v-else
            @click="goToCheckOut"
            class="px-2.5 py-1 text-xs font-bold text-rose-600 dark:text-rose-400 bg-rose-500/10 hover:bg-rose-500/20 rounded-lg transition"
          >
            Check Out
          </button>
        </div>
      </div>
    </div>

    <!-- Empty State -->
    <div v-else class="text-center py-8">
      <Clock class="w-7 h-7 text-slate-300 dark:text-slate-600 mx-auto mb-2" />
      <p class="text-xs font-semibold text-slate-500 dark:text-slate-400">No scheduled departures for today</p>
    </div>
  </div>
</template>

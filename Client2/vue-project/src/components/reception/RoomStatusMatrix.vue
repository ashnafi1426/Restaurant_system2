<script setup lang="ts">
import { ref, computed } from 'vue'
import type { RoomInMatrix } from '@/types/reception'
import { Grid, Sparkles, SlidersHorizontal } from 'lucide-vue-next'

interface Props {
  rooms: RoomInMatrix[]
}

const props = defineProps<Props>()
const activeFilter = ref<string>('all')

const counts = computed(() => {
  const map = { available: 0, occupied: 0, reserved: 0, cleaning: 0, maintenance: 0 }
  props.rooms.forEach(r => {
    const s = String(r.status || 'available').toLowerCase()
    if (s in map) map[s as keyof typeof map]++
  })
  return map
})

const filteredRooms = computed(() => {
  if (activeFilter.value === 'all') return props.rooms
  return props.rooms.filter(r => String(r.status || '').toLowerCase() === activeFilter.value)
})

const getRoomBadgeClass = (status: string) => {
  const s = String(status || '').toLowerCase()
  const colors: Record<string, { border: string; bgHover: string; badge: string; text: string }> = {
    available: {
      border: 'border-2 border-emerald-500 dark:border-emerald-500',
      bgHover: 'hover:bg-emerald-50/60 dark:hover:bg-emerald-950/30',
      badge: 'bg-emerald-500 text-white',
      text: 'text-emerald-700 dark:text-emerald-400'
    },
    occupied: {
      border: 'border-2 border-slate-400 dark:border-slate-600',
      bgHover: 'hover:bg-slate-100/60 dark:hover:bg-slate-800/50',
      badge: 'bg-slate-700 text-white dark:bg-slate-600',
      text: 'text-slate-700 dark:text-slate-300'
    },
    reserved: {
      border: 'border-2 border-indigo-500 dark:border-indigo-500',
      bgHover: 'hover:bg-indigo-50/60 dark:hover:bg-indigo-950/30',
      badge: 'bg-indigo-600 text-white',
      text: 'text-indigo-700 dark:text-indigo-400'
    },
    cleaning: {
      border: 'border-2 border-amber-500 dark:border-amber-500',
      bgHover: 'hover:bg-amber-50/60 dark:hover:bg-amber-950/30',
      badge: 'bg-amber-500 text-white',
      text: 'text-amber-700 dark:text-amber-400'
    },
    maintenance: {
      border: 'border-2 border-rose-500 dark:border-rose-500',
      bgHover: 'hover:bg-rose-50/60 dark:hover:bg-rose-950/30',
      badge: 'bg-rose-600 text-white',
      text: 'text-rose-700 dark:text-rose-400'
    },
  }
  return colors[s] || {
    border: 'border-2 border-slate-300',
    bgHover: 'hover:bg-slate-50',
    badge: 'bg-slate-500 text-white',
    text: 'text-slate-800'
  }
}
</script>

<template>
  <div class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-800 p-5 shadow-xs">
    <div class="flex items-center justify-between gap-4 pb-4 border-b border-slate-200 dark:border-slate-800 flex-wrap">
      <div class="flex items-center gap-2.5">
        <div class="w-8 h-8 rounded-xl bg-teal-500/10 dark:bg-teal-500/20 text-teal-600 dark:text-teal-400 flex items-center justify-center font-bold text-sm">
          <Grid class="w-4 h-4" />
        </div>
        <div>
          <h3 class="text-base font-bold text-slate-900 dark:text-white">Room Status Matrix</h3>
          <p class="text-xs text-slate-500 dark:text-slate-400">Real-time room occupancy grid</p>
        </div>
      </div>

      <div class="flex items-center gap-1.5 flex-wrap">
        <button
          @click="activeFilter = 'all'"
          :class="[
            'px-2.5 py-1 text-xs font-bold rounded-lg transition-colors cursor-pointer',
            activeFilter === 'all'
              ? 'bg-slate-900 dark:bg-white text-white dark:text-slate-900'
              : 'bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300 hover:bg-slate-200 dark:hover:bg-slate-700'
          ]"
        >
          All ({{ rooms.length }})
        </button>
        <button
          @click="activeFilter = 'available'"
          :class="[
            'px-2.5 py-1 text-xs font-bold rounded-lg transition-colors cursor-pointer',
            activeFilter === 'available'
              ? 'bg-emerald-600 text-white'
              : 'bg-emerald-500/10 text-emerald-700 dark:text-emerald-400 hover:bg-emerald-500/20'
          ]"
        >
          Available ({{ counts.available }})
        </button>
        <button
          @click="activeFilter = 'occupied'"
          :class="[
            'px-2.5 py-1 text-xs font-bold rounded-lg transition-colors cursor-pointer',
            activeFilter === 'occupied'
              ? 'bg-slate-700 text-white'
              : 'bg-slate-500/10 text-slate-700 dark:text-slate-300 hover:bg-slate-500/20'
          ]"
        >
          Occupied ({{ counts.occupied }})
        </button>
      </div>
    </div>

    <div
      v-if="filteredRooms.length > 0"
      class="grid grid-cols-3 sm:grid-cols-4 md:grid-cols-5 xl:grid-cols-6 gap-3 my-4"
    >
      <div
        v-for="room in filteredRooms"
        :key="room.id"
        :class="[
          'bg-white dark:bg-slate-800/90 rounded-2xl p-3 flex flex-col items-center justify-between transition-all duration-200 shadow-xs hover:scale-105 cursor-pointer min-h-[64px]',
          getRoomBadgeClass(room.status).border,
          getRoomBadgeClass(room.status).bgHover,
        ]"
      >
        <span class="text-base sm:text-lg font-black text-slate-900 dark:text-white tracking-tight leading-none mb-1">
          {{ room.room_number }}
        </span>
        <span
          :class="[
            'text-[9px] uppercase font-black tracking-wider leading-none px-2 py-0.5 rounded-full truncate max-w-full font-mono',
            getRoomBadgeClass(room.status).badge
          ]"
        >
          {{ room.status }}
        </span>
      </div>
    </div>

    <div v-else class="text-center py-8">
      <p class="text-xs font-semibold text-slate-500 dark:text-slate-400">No rooms match filter: {{ activeFilter }}</p>
    </div>
  </div>
</template>

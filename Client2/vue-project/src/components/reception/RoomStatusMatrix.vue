<script setup lang="ts">
import type { RoomInMatrix } from '@/types/reception'

interface Props {
  rooms: RoomInMatrix[]
}

const props = defineProps<Props>()

const getRoomStatusColor = (status: string) => {
  const colors: Record<string, string> = {
    available: 'bg-teal-600 hover:bg-teal-700 text-white',
    occupied: 'bg-slate-600 hover:bg-slate-700 text-white',
    reserved: 'bg-red-600 hover:bg-red-700 text-white',
    cleaning: 'bg-yellow-600 hover:bg-yellow-700 text-white',
    maintenance: 'bg-red-700 hover:bg-red-800 text-white',
  }
  return colors[status] || 'bg-gray-400'
}

const statusLabels: Record<string, string> = {
  available: 'Available',
  occupied: 'Occupied',
  reserved: 'Reserved',
  cleaning: 'Cleaning',
  maintenance: 'Maintenance',
}
</script>

<template>
  <div class="bg-blue-50 dark:bg-slate-800 rounded-lg border border-blue-200 dark:border-slate-700 p-5 sm:p-6 shadow-sm">
    <!-- Header -->
    <div
      class="flex items-center justify-between mb-4 pb-4 border-b border-blue-200 dark:border-slate-700 flex-col sm:flex-row gap-3 sm:gap-0"
    >
      <h3 class="text-lg sm:text-xl font-bold text-gray-900 dark:text-white">
        Room Status Matrix
      </h3>
      <div class="flex gap-3 items-center flex-wrap justify-center sm:justify-end">
        <!-- Legend -->
        <div class="flex items-center gap-2">
          <div class="w-3 h-3 rounded-full bg-teal-600"></div>
          <span class="text-sm text-gray-600 dark:text-slate-400 font-medium">Available</span>
        </div>
        <div class="flex items-center gap-2">
          <div class="w-3 h-3 rounded-full bg-slate-600"></div>
          <span class="text-sm text-gray-600 dark:text-slate-400 font-medium">Occupied</span>
        </div>
        <div class="flex items-center gap-2">
          <div class="w-3 h-3 rounded-full bg-red-600"></div>
          <span class="text-sm text-gray-600 dark:text-slate-400 font-medium">Dirty</span>
        </div>
        <button
          class="text-sm px-4 py-1.5 text-teal-600 dark:text-teal-400 hover:text-teal-700 dark:hover:text-teal-300 font-bold rounded transition"
        >
          Filters
        </button>
      </div>
    </div>

    <!-- Room Grid -->
    <div
      v-if="props.rooms.length > 0"
      class="grid gap-3 mb-4"
      style="grid-template-columns: repeat(auto-fill, minmax(56px, 1fr))"
    >
      <button
        v-for="room in props.rooms"
        :key="room.id"
        :class="`${getRoomStatusColor(room.status)} text-sm font-bold py-3 px-2 rounded transition duration-200 cursor-pointer`"
        :title="`Room ${room.room_number} - ${statusLabels[room.status]}`"
      >
        {{ room.room_number }}
      </button>
    </div>

    <!-- Empty State -->
    <div v-else class="text-center py-8">
      <p class="text-sm text-gray-500 dark:text-slate-400">No rooms available</p>
    </div>

    <!-- Footer -->
    <div
      class="flex justify-between items-center text-sm pt-4 border-t border-gray-200 dark:border-slate-700 flex-col sm:flex-row gap-2 sm:gap-0"
    >
      <p class="text-gray-500 dark:text-slate-400 font-medium">Last updated: 2 mins ago</p>
      <button class="text-teal-600 dark:text-teal-400 hover:text-teal-700 dark:hover:text-teal-300 font-bold">Open Map</button>
    </div>
  </div>
</template>

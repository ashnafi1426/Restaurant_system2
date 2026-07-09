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
  <div class="bg-blue-50 rounded-lg border border-blue-200 p-6 shadow-sm">
    <!-- Header -->
    <div class="flex items-center justify-between mb-5 pb-4 border-b border-blue-200">
      <h3 class="text-lg font-bold text-gray-900">Room Status Matrix</h3>
      <div class="flex gap-3 items-center">
        <!-- Legend -->
        <div class="flex items-center gap-2">
          <div class="w-3 h-3 rounded-full bg-teal-600"></div>
          <span class="text-xs text-gray-600 font-medium">Available</span>
        </div>
        <div class="flex items-center gap-2">
          <div class="w-3 h-3 rounded-full bg-slate-600"></div>
          <span class="text-xs text-gray-600 font-medium">Occupied</span>
        </div>
        <div class="flex items-center gap-2">
          <div class="w-3 h-3 rounded-full bg-red-600"></div>
          <span class="text-xs text-gray-600 font-medium">Dirty</span>
        </div>
        <div class="flex items-center gap-2">
          <div class="w-3 h-3 rounded-full bg-red-700"></div>
          <span class="text-xs text-gray-600 font-medium">Maintenance</span>
        </div>
        <button
          class="text-xs px-3 py-1 text-teal-600 hover:text-teal-700 font-bold rounded transition"
        >
          All Floors
        </button>
      </div>
    </div>

    <!-- Room Grid -->
    <div
      v-if="props.rooms.length > 0"
      class="grid gap-2 mb-4"
      style="grid-template-columns: repeat(auto-fill, minmax(54px, 1fr))"
    >
      <button
        v-for="room in props.rooms"
        :key="room.id"
        :class="`${getRoomStatusColor(room.status)} text-xs font-bold py-3 px-2 rounded transition duration-200 cursor-pointer`"
        :title="`Room ${room.room_number} - ${statusLabels[room.status]}`"
      >
        {{ room.room_number }}
      </button>
    </div>

    <!-- Empty State -->
    <div v-else class="text-center py-8">
      <p class="text-sm text-gray-500">No rooms available</p>
    </div>

    <!-- Footer -->
    <div class="flex justify-between items-center text-xs pt-4 border-t border-gray-200">
      <p class="text-gray-500 font-medium">Last updated: 2 mins ago</p>
      <button class="text-teal-600 hover:text-teal-700 font-bold">Open Full Interactive Map</button>
    </div>
  </div>
</template>

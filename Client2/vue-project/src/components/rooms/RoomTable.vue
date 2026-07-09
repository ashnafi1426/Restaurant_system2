<script setup lang="ts">
import { ref, onMounted, onBeforeUnmount } from 'vue'
import RoomStatusBadge from './RoomStatusBadge.vue'
import type { Room } from '../../types/room'

withDefaults(
  defineProps<{
    rooms?: Room[]
    loading?: boolean
  }>(),
  {
    rooms: () => [],
    loading: false,
  },
)

const emit = defineEmits(['view', 'edit', 'delete'])

const openMenu = ref<string | null>(null)

const toggleMenu = (id: string, event: MouseEvent) => {
  console.log('Toggle menu for room:', id)
  event.stopPropagation()
  openMenu.value = openMenu.value === id ? null : id
}

const closeMenu = () => {
  openMenu.value = null
}

const handleView = (room: any) => {
  console.log('View room:', room, 'ID:', room.id)
  emit('view', room)
  closeMenu()
}

const handleEdit = (room: any) => {
  console.log('✏️ Edit room:', room, 'ID:', room.id)
  emit('edit', room)
  closeMenu()
}

const handleDelete = (room: any) => {
  console.log('Delete room:', room, 'ID:', room.id)
  emit('delete', room)
  closeMenu()
}

const handleClickOutside = () => {
  closeMenu()
}

onMounted(() => {
  window.addEventListener('click', handleClickOutside)
})

onBeforeUnmount(() => {
  window.removeEventListener('click', handleClickOutside)
})
</script>

<template>
  <div class="rounded-xl border border-slate-200 bg-white shadow overflow-visible">
    <div class="overflow-x-auto">
      <table class="min-w-full">
        <thead class="bg-slate-100 sticky top-0 z-10">
          <tr>
            <th class="px-6 py-4 text-left">Room</th>
            <th class="px-6 py-4 text-left">Type</th>
            <th class="px-6 py-4 text-center">Floor</th>
            <th class="px-6 py-4 text-center">Capacity</th>
            <th class="px-6 py-4 text-right">Price</th>
            <th class="px-6 py-4 text-center">Status</th>
            <th class="px-6 py-4 text-center">Active</th>
            <th class="px-6 py-4 text-center">Action</th>
          </tr>
        </thead>

        <tbody>
          <tr v-for="room in rooms" :key="room.id" class="border-b hover:bg-slate-50">
            <td class="px-6 py-4 font-semibold">
              {{ room.room_number }}
            </td>
            <td class="px-6 py-4">
              {{ room.room_type?.name }}
            </td>

            <td class="px-6 py-4 text-center">
              {{ room.floor }}
            </td>

            <td class="px-6 py-4 text-center">
              {{ room.room_type?.capacity }}
            </td>

            <td class="px-6 py-4 text-right">${{ room.room_type?.base_price_per_night }}</td>

            <td class="px-6 py-4 text-center">
              <RoomStatusBadge :status="room.status" />
            </td>
            <td class="px-6 py-4 text-center">
              <span
                v-if="room.is_active"
                class="rounded-full bg-green-100 px-3 py-1 text-xs font-semibold text-green-700"
              >
                Active
              </span>
              <span
                v-else
                class="rounded-full bg-red-100 px-3 py-1 text-xs font-semibold text-red-700"
              >
                Inactive
              </span>
            </td>

            <td class="relative px-6 py-4 text-center overflow-visible">
              <button
                @click.stop="toggleMenu(room.id, $event)"
                class="rounded-lg p-2 hover:bg-slate-100"
              >
                <svg
                  xmlns="http://www.w3.org/2000/svg"
                  class="h-5 w-5"
                  fill="none"
                  viewBox="0 0 24 24"
                  stroke="currentColor"
                >
                  <path
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    stroke-width="2"
                    d="M12 5h.01M12 12h.01M12 19h.01"
                  />
                </svg>
              </button>

              <transition
                enter-active-class="transition duration-150"
                leave-active-class="transition duration-100"
                enter-from-class="opacity-0 scale-95"
                enter-to-class="opacity-100 scale-100"
                leave-from-class="opacity-100 scale-100"
                leave-to-class="opacity-0 scale-95"
              >
                <div
                  v-if="openMenu === room.id"
                  @click.stop
                  class="absolute right-0 mt-2 w-52 rounded-xl border bg-white shadow-2xl z-50"
                >
                  <button
                    @click="handleView(room)"
                    class="flex w-full items-center gap-3 px-4 py-3 hover:bg-slate-50"
                  >
                    👁
                    <span>View</span>
                  </button>

                  <button
                    @click="handleEdit(room)"
                    class="flex w-full items-center gap-3 px-4 py-3 hover:bg-slate-50"
                  >
                    ✏️
                    <span>Edit</span>
                  </button>

                  <hr />

                  <button
                    @click="handleDelete(room)"
                    class="flex w-full items-center gap-3 px-4 py-3 text-red-600 hover:bg-red-50"
                  >
                    🗑
                    <span>Delete</span>
                  </button>
                </div>
              </transition>
            </td>
          </tr>

          <tr v-if="rooms.length === 0">
            <td colspan="8" class="py-12 text-center text-slate-500">No rooms found</td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>
</template>

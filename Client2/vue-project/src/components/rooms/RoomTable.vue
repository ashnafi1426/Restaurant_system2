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
  <div class="rounded-lg sm:rounded-xl border border-slate-200 bg-white shadow overflow-hidden">
    <!-- Desktop Table View (md and up) -->
    <div class="hidden md:block overflow-x-auto">
      <table class="w-full">
        <thead class="bg-slate-50/80 border-b border-slate-200 sticky top-0 z-10">
          <tr>
            <th
              class="px-3 sm:px-4 md:px-6 py-2 sm:py-3 md:py-3.5 text-left text-xs font-semibold uppercase tracking-wider text-slate-600"
            >
              Room
            </th>
            <th
              class="hidden sm:table-cell px-3 sm:px-4 md:px-6 py-2 sm:py-3 md:py-3.5 text-left text-xs font-semibold uppercase tracking-wider text-slate-600"
            >
              Type
            </th>

            <th
              class="hidden md:table-cell px-3 sm:px-4 md:px-6 py-2 sm:py-3 md:py-3.5 text-center text-xs font-semibold uppercase tracking-wider text-slate-600"
            >
              Floor
            </th>

            <th
              class="hidden lg:table-cell px-3 sm:px-4 md:px-6 py-2 sm:py-3 md:py-3.5 text-center text-xs font-semibold uppercase tracking-wider text-slate-600"
            >
              Capacity
            </th>

            <th
              class="hidden lg:table-cell px-3 sm:px-4 md:px-6 py-2 sm:py-3 md:py-3.5 text-right text-xs font-semibold uppercase tracking-wider text-slate-600"
            >
              Price
            </th>

            <th
              class="px-3 sm:px-4 md:px-6 py-2 sm:py-3 md:py-3.5 text-center text-xs font-semibold uppercase tracking-wider text-slate-600"
            >
              Status
            </th>
            <th
              class="hidden sm:table-cell px-3 sm:px-4 md:px-6 py-2 sm:py-3 md:py-3.5 text-center text-xs font-semibold uppercase tracking-wider text-slate-600"
            >
              Active
            </th>
            <th
              class="px-3 sm:px-4 md:px-6 py-2 sm:py-3 md:py-3.5 text-center text-xs font-semibold uppercase tracking-wider text-slate-600"
            >
              Action
            </th>
          </tr>
        </thead>

        <tbody class="divide-y divide-slate-200">
          <tr
            v-for="room in rooms"
            :key="room.id"
            class="hover:bg-slate-50/60 transition duration-150"
          >
            <td
              class="px-3 sm:px-4 md:px-6 py-2 sm:py-3 md:py-4 font-semibold text-xs sm:text-sm md:text-base text-slate-900"
            >
              {{ room.room_number }}
            </td>
            <td
              class="hidden sm:table-cell px-3 sm:px-4 md:px-6 py-2 sm:py-3 md:py-4 text-xs sm:text-sm text-slate-600"
            >
              {{ room.room_type?.name }}
            </td>

            <td
              class="hidden md:table-cell px-3 sm:px-4 md:px-6 py-2 sm:py-3 md:py-4 text-center text-xs sm:text-sm text-slate-600"
            >
              {{ room.floor }}
            </td>

            <td
              class="hidden lg:table-cell px-3 sm:px-4 md:px-6 py-2 sm:py-3 md:py-4 text-center text-xs sm:text-sm text-slate-600"
            >
              {{ room.room_type?.capacity }}
            </td>

            <td
              class="hidden lg:table-cell px-3 sm:px-4 md:px-6 py-2 sm:py-3 md:py-4 text-right text-xs sm:text-sm font-semibold text-slate-900"
            >
              ₹{{ parseFloat(room.room_type?.base_price_per_night || 0).toLocaleString('en-IN') }}
            </td>

            <td class="px-3 sm:px-4 md:px-6 py-2 sm:py-3 md:py-4 text-center">
              <RoomStatusBadge :status="room.status" />
            </td>
            <td class="hidden sm:table-cell px-3 sm:px-4 md:px-6 py-2 sm:py-3 md:py-4 text-center">
              <span
                v-if="room.is_active"
                class="inline-flex items-center px-2 sm:px-3 py-1 sm:py-1.5 rounded-full text-xs sm:text-xs font-medium bg-green-50 text-green-700 border border-green-200"
              >
                <span
                  class="h-1.5 w-1.5 sm:h-2 sm:w-2 rounded-full bg-green-500 mr-1 sm:mr-1.5"
                ></span>
                Active
              </span>
              <span
                v-else
                class="inline-flex items-center px-2 sm:px-3 py-1 sm:py-1.5 rounded-full text-xs sm:text-xs font-medium bg-red-50 text-red-700 border border-red-200"
              >
                <span
                  class="h-1.5 w-1.5 sm:h-2 sm:w-2 rounded-full bg-red-500 mr-1 sm:mr-1.5"
                ></span>
                Inactive
              </span>
            </td>

            <td
              class="px-3 sm:px-4 md:px-6 py-2 sm:py-3 md:py-4 text-center relative overflow-visible"
            >
              <div class="action-menu inline-block relative">
                <button
                  @click.stop="toggleMenu(room.id, $event)"
                  class="w-8 h-8 sm:w-9 sm:h-9 flex items-center justify-center rounded-lg hover:bg-slate-100 transition duration-150 text-slate-600 hover:text-slate-900"
                  :aria-label="`Options for room ${room.room_number}`"
                >
                  ⋮
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
                    class="absolute right-0 mt-2 w-36 sm:w-40 md:w-44 bg-white border border-slate-200 rounded-lg shadow-lg z-50"
                  >
                    <button
                      @click="handleView(room)"
                      class="w-full text-left px-3 sm:px-4 py-2 sm:py-2.5 text-xs sm:text-sm hover:bg-slate-50 text-slate-700 hover:text-slate-900 transition duration-150 flex items-center gap-2"
                    >
                      👁️ <span>View</span>
                    </button>

                    <button
                      @click="handleEdit(room)"
                      class="w-full text-left px-3 sm:px-4 py-2 sm:py-2.5 text-xs sm:text-sm hover:bg-slate-50 text-slate-700 hover:text-slate-900 transition duration-150 flex items-center gap-2 border-t border-slate-100"
                    >
                      ✏️ <span>Edit</span>
                    </button>

                    <button
                      @click="handleDelete(room)"
                      class="w-full text-left px-3 sm:px-4 py-2 sm:py-2.5 text-xs sm:text-sm text-red-600 hover:bg-red-50 hover:text-red-700 transition duration-150 flex items-center gap-2 border-t border-slate-100"
                    >
                      🗑️ <span>Delete</span>
                    </button>
                  </div>
                </transition>
              </div>
            </td>
          </tr>

          <tr v-if="rooms.length === 0">
            <td colspan="8" class="px-4 py-12 text-center text-slate-500 text-sm">
              No rooms found
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <!-- Mobile Card View (below md) -->
    <div class="md:hidden">
      <div v-if="rooms.length === 0" class="px-4 py-8 text-center text-slate-500 text-sm">
        No rooms found
      </div>

      <div
        v-for="room in rooms"
        :key="room.id"
        class="border-b border-slate-200 last:border-b-0 p-4 sm:p-5 hover:bg-slate-50/50 transition duration-150"
      >
        <!-- Room Number and Status -->
        <div class="flex items-start justify-between gap-2 mb-3">
          <div class="flex-1 min-w-0">
            <h3 class="font-semibold text-sm text-slate-900">Room {{ room.room_number }}</h3>
            <p class="text-xs text-slate-500 mt-1">{{ room.room_type?.name }}</p>
          </div>
          <div class="flex flex-col gap-1 items-end flex-shrink-0 ml-2">
            <RoomStatusBadge :status="room.status" />
            <span
              v-if="room.is_active"
              class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-green-50 text-green-700 border border-green-200"
            >
              <span class="h-1.5 w-1.5 rounded-full bg-green-500 mr-1"></span>
              Active
            </span>
            <span
              v-else
              class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-red-50 text-red-700 border border-red-200"
            >
              <span class="h-1.5 w-1.5 rounded-full bg-red-500 mr-1"></span>
              Inactive
            </span>
          </div>
        </div>

        <!-- Additional Info Grid -->
        <div class="grid grid-cols-2 gap-2 mb-3 text-xs">
          <div class="bg-slate-50 p-2 rounded-lg">
            <p class="text-slate-500 mb-0.5">Floor</p>
            <p class="font-semibold text-slate-900">{{ room.floor }}</p>
          </div>
          <div class="bg-slate-50 p-2 rounded-lg">
            <p class="text-slate-500 mb-0.5">Capacity</p>
            <p class="font-semibold text-slate-900">{{ room.room_type?.capacity }} Guests</p>
          </div>
          <div class="bg-slate-50 p-2 rounded-lg col-span-2">
            <p class="text-slate-500 mb-0.5">Price/Night</p>
            <p class="font-semibold text-slate-900">
              ₹{{ parseFloat(room.room_type?.base_price_per_night || 0).toLocaleString('en-IN') }}
            </p>
          </div>
        </div>

        <!-- Actions -->
        <div class="flex gap-2 pt-3 border-t border-slate-100">
          <button
            @click="handleView(room)"
            class="flex-1 px-3 py-2 text-xs font-medium text-blue-600 bg-blue-50 hover:bg-blue-100 rounded-lg transition duration-150"
          >
            👁️ View
          </button>
          <button
            @click="handleEdit(room)"
            class="flex-1 px-3 py-2 text-xs font-medium text-amber-600 bg-amber-50 hover:bg-amber-100 rounded-lg transition duration-150"
          >
            ✏️ Edit
          </button>
          <button
            @click="handleDelete(room)"
            class="flex-1 px-3 py-2 text-xs font-medium text-red-600 bg-red-50 hover:bg-red-100 rounded-lg transition duration-150"
          >
            🗑️ Delete
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

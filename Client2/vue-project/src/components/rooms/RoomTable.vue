<script setup lang="ts">
import { ref, computed, watch, onMounted, onBeforeUnmount } from 'vue'
import RoomStatusBadge from './RoomStatusBadge.vue'
import type { Room } from '../../types/room'
import {
  BedDouble,
  MoreVertical,
  Eye,
  Edit,
  Trash2,
  Loader2,
  ChevronLeft,
  ChevronRight,
  CheckCircle2,
  XCircle
} from 'lucide-vue-next'

const props = withDefaults(
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
const currentPage = ref(1)
const perPage = ref(10)

const total = computed(() => props.rooms?.length || 0)
const lastPage = computed(() => Math.ceil(total.value / perPage.value) || 1)

const paginatedRooms = computed(() => {
  const start = (currentPage.value - 1) * perPage.value
  const end = start + perPage.value
  return (props.rooms || []).slice(start, end)
})

const showingFrom = computed(() => {
  if (total.value === 0) return 0
  return (currentPage.value - 1) * perPage.value + 1
})

const showingTo = computed(() => {
  return Math.min(currentPage.value * perPage.value, total.value)
})

const paginationPages = computed(() => {
  const pages: number[] = []
  const max = lastPage.value
  const cur = currentPage.value

  for (let i = Math.max(1, cur - 2); i <= Math.min(max, cur + 2); i++) {
    pages.push(i)
  }
  return pages
})

watch(() => props.rooms, () => {
  currentPage.value = 1
})

const changePerPage = (event: Event) => {
  const target = event.target as HTMLSelectElement
  perPage.value = Number(target.value)
  currentPage.value = 1
}

const goToPage = (p: number) => {
  if (p >= 1 && p <= lastPage.value) {
    currentPage.value = p
  }
}

const prevPage = () => {
  if (currentPage.value > 1) {
    currentPage.value--
  }
}

const nextPage = () => {
  if (currentPage.value < lastPage.value) {
    currentPage.value++
  }
}

const toggleMenu = (id: string, event: MouseEvent) => {
  event.stopPropagation()
  openMenu.value = openMenu.value === id ? null : id
}

const closeMenu = () => {
  openMenu.value = null
}

const handleView = (room: any) => {
  emit('view', room)
  closeMenu()
}

const handleEdit = (room: any) => {
  emit('edit', room)
  closeMenu()
}

const handleDelete = (room: any) => {
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
  <div class="rounded-2xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 shadow-xs overflow-hidden font-sans w-full">
    <!-- Desktop & Tablet Table View -->
    <div class="hidden md:block overflow-x-auto w-full">
      <table class="w-full text-left border-collapse">
        <thead class="bg-slate-50/90 dark:bg-slate-950 border-b border-slate-200 dark:border-slate-800">
          <tr class="text-[10px] font-black text-slate-500 dark:text-slate-400 uppercase tracking-wider select-none">
            <th class="px-4 py-3 whitespace-nowrap">Room</th>
            <th class="px-4 py-3 whitespace-nowrap">Type</th>
            <th class="px-4 py-3 text-center whitespace-nowrap">Floor</th>
            <th class="px-4 py-3 text-center whitespace-nowrap">Capacity</th>
            <th class="px-4 py-3 text-right whitespace-nowrap">Price</th>
            <th class="px-4 py-3 text-center whitespace-nowrap">Status</th>
            <th class="px-4 py-3 text-center whitespace-nowrap">Active</th>
            <th class="px-4 py-3 text-right whitespace-nowrap pr-6">Action</th>
          </tr>
        </thead>

        <tbody class="divide-y divide-slate-100 dark:divide-slate-800/60 text-xs">
          <!-- Loading State -->
          <tr v-if="loading">
            <td colspan="8" class="px-4 py-12 text-center text-slate-500 dark:text-slate-400">
              <div class="flex items-center justify-center gap-2">
                <Loader2 class="w-6 h-6 text-blue-600 dark:text-blue-400 animate-spin" />
                <span class="font-bold text-xs">Loading rooms...</span>
              </div>
            </td>
          </tr>

          <!-- Rooms Data Rows -->
          <tr
            v-else
            v-for="room in paginatedRooms"
            :key="room.id"
            class="hover:bg-slate-50/70 dark:hover:bg-slate-800/40 transition-colors duration-150 group"
          >
            <!-- Room Number -->
            <td class="px-4 py-3 whitespace-nowrap font-black text-slate-900 dark:text-white text-xs sm:text-sm">
              Room {{ room.room_number }}
            </td>

            <!-- Type -->
            <td class="px-4 py-3 whitespace-nowrap font-bold text-slate-700 dark:text-slate-300">
              {{ room.room_type?.name || 'Standard' }}
            </td>

            <!-- Floor -->
            <td class="px-4 py-3 text-center whitespace-nowrap font-bold text-slate-700 dark:text-slate-300">
              {{ room.floor }}
            </td>

            <!-- Capacity -->
            <td class="px-4 py-3 text-center whitespace-nowrap font-bold text-slate-700 dark:text-slate-300">
              {{ room.room_type?.capacity || 1 }}
            </td>

            <!-- Price -->
            <td class="px-4 py-3 text-right whitespace-nowrap font-extrabold text-slate-900 dark:text-white">
              ₹{{ parseFloat(room.room_type?.base_price_per_night || 0).toLocaleString('en-IN') }}
            </td>

            <!-- Status -->
            <td class="px-4 py-3 text-center whitespace-nowrap">
              <RoomStatusBadge :status="room.status" />
            </td>

            <!-- Active -->
            <td class="px-4 py-3 text-center whitespace-nowrap">
              <span
                v-if="room.is_active"
                class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-[10px] font-extrabold bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 border border-emerald-500/20"
              >
                <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                <span>Active</span>
              </span>
              <span
                v-else
                class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-[10px] font-extrabold bg-rose-500/10 text-rose-600 dark:text-rose-400 border border-rose-500/20"
              >
                <span class="w-1.5 h-1.5 rounded-full bg-rose-500"></span>
                <span>Inactive</span>
              </span>
            </td>

            <!-- Action -->
            <td class="px-4 py-3 text-right whitespace-nowrap pr-6 relative">
              <div class="action-menu inline-block relative">
                <button
                  @click.stop="toggleMenu(room.id, $event)"
                  class="p-1.5 rounded-xl text-slate-400 hover:text-slate-700 dark:hover:text-white hover:bg-slate-100 dark:hover:bg-slate-800 transition cursor-pointer"
                  :class="{ 'bg-slate-100 dark:bg-slate-800 text-slate-900 dark:text-white': openMenu === room.id }"
                  title="Actions"
                >
                  <MoreVertical class="w-4 h-4" />
                </button>

                <!-- Dropdown Popup Card -->
                <transition
                  enter-active-class="transition duration-100 ease-out"
                  leave-active-class="transition duration-75 ease-in"
                  enter-from-class="opacity-0 scale-95 -translate-y-2"
                  enter-to-class="opacity-100 scale-100 translate-y-0"
                  leave-from-class="opacity-100 scale-100 translate-y-0"
                  leave-to-class="opacity-0 scale-95 -translate-y-2"
                >
                  <div
                    v-if="openMenu === room.id"
                    @click.stop
                    class="absolute right-0 top-8 z-50 w-40 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl shadow-2xl p-1.5 space-y-1 text-left"
                  >
                    <button
                      @click="handleView(room)"
                      class="flex w-full items-center gap-2 px-3 py-2 rounded-xl text-xs font-bold text-slate-700 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800 transition cursor-pointer"
                    >
                      <Eye class="w-3.5 h-3.5 text-blue-500" />
                      <span>View Details</span>
                    </button>

                    <button
                      @click="handleEdit(room)"
                      class="flex w-full items-center gap-2 px-3 py-2 rounded-xl text-xs font-bold text-amber-600 dark:text-amber-400 hover:bg-amber-50 dark:hover:bg-amber-500/10 transition cursor-pointer"
                    >
                      <Edit class="w-3.5 h-3.5 text-amber-500" />
                      <span>Edit Room</span>
                    </button>

                    <div class="border-t border-slate-100 dark:border-slate-800 my-1"></div>

                    <button
                      @click="handleDelete(room)"
                      class="flex w-full items-center gap-2 px-3 py-2 rounded-xl text-xs font-bold text-rose-600 dark:text-rose-400 hover:bg-rose-50 dark:hover:bg-rose-500/10 transition cursor-pointer"
                    >
                      <Trash2 class="w-3.5 h-3.5 text-rose-500" />
                      <span>Delete</span>
                    </button>
                  </div>
                </transition>
              </div>
            </td>
          </tr>

          <!-- Empty State -->
          <tr v-if="!loading && rooms.length === 0">
            <td colspan="8" class="px-4 py-12 text-center text-slate-500 dark:text-slate-400 text-xs font-bold">
              No rooms found matching your search.
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <!-- Mobile Card View (md and smaller) -->
    <div class="md:hidden divide-y divide-slate-100 dark:divide-slate-800">
      <div v-if="!loading && rooms.length === 0" class="px-4 py-8 text-center text-slate-500 dark:text-slate-400 text-xs font-bold">
        No rooms found
      </div>

      <div
        v-for="room in paginatedRooms"
        :key="room.id"
        class="p-4 space-y-3 hover:bg-slate-50/50 dark:hover:bg-slate-800/40 transition duration-150"
      >
        <div class="flex items-start justify-between gap-2">
          <div>
            <h3 class="font-extrabold text-sm text-slate-900 dark:text-white">Room {{ room.room_number }}</h3>
            <p class="text-xs text-slate-500 dark:text-slate-400 font-semibold mt-0.5">{{ room.room_type?.name }}</p>
          </div>
          <div class="flex flex-col gap-1 items-end">
            <RoomStatusBadge :status="room.status" />
            <span
              v-if="room.is_active"
              class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 border border-emerald-500/20"
            >
              <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
              Active
            </span>
            <span
              v-else
              class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-bold bg-rose-500/10 text-rose-600 dark:text-rose-400 border border-rose-500/20"
            >
              <span class="w-1.5 h-1.5 rounded-full bg-rose-500"></span>
              Inactive
            </span>
          </div>
        </div>

        <div class="grid grid-cols-2 gap-2 text-xs bg-slate-50 dark:bg-slate-950 p-2.5 rounded-xl border border-slate-200 dark:border-slate-800">
          <div>
            <span class="text-[10px] text-slate-400 block font-bold uppercase">Floor</span>
            <span class="font-bold text-slate-900 dark:text-white">{{ room.floor }}</span>
          </div>
          <div>
            <span class="text-[10px] text-slate-400 block font-bold uppercase">Capacity</span>
            <span class="font-bold text-slate-900 dark:text-white">{{ room.room_type?.capacity }} Guests</span>
          </div>
          <div class="col-span-2 pt-1 border-t border-slate-200 dark:border-slate-800 flex justify-between items-center">
            <span class="text-[10px] text-slate-400 font-bold uppercase">Price/Night</span>
            <span class="font-black text-sm text-slate-900 dark:text-white">
              ₹{{ parseFloat(room.room_type?.base_price_per_night || 0).toLocaleString('en-IN') }}
            </span>
          </div>
        </div>
      </div>
    </div>

    <!-- Pagination Bar with 5, 10, 20, 50 Per Page Options -->
    <div
      v-if="rooms.length > 0"
      class="flex flex-col sm:flex-row items-center justify-between gap-4 border-t border-slate-200 dark:border-slate-800 bg-slate-50/80 dark:bg-slate-950 p-4 text-xs font-sans"
    >
      <!-- Left Side: Per Page Selector & Showing Count -->
      <div class="flex flex-wrap items-center gap-4 text-slate-600 dark:text-slate-400">
        <div class="flex items-center gap-2">
          <span class="font-bold text-slate-700 dark:text-slate-300">Items per page:</span>
          <select
            :value="perPage"
            @change="changePerPage"
            class="px-2.5 py-1.5 rounded-xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 text-slate-900 dark:text-white font-black focus:outline-none focus:border-blue-500 cursor-pointer shadow-2xs"
          >
            <option :value="5">5</option>
            <option :value="10">10</option>
            <option :value="20">20</option>
            <option :value="50">50</option>
          </select>
        </div>

        <div class="text-xs font-medium">
          Showing <span class="font-extrabold text-slate-900 dark:text-white">{{ showingFrom }}</span> to
          <span class="font-extrabold text-slate-900 dark:text-white">{{ showingTo }}</span> of
          <span class="font-extrabold text-slate-900 dark:text-white">{{ total }}</span> rooms
        </div>
      </div>

      <!-- Right Side: Page Controls -->
      <div class="flex items-center gap-1.5">
        <button
          @click="prevPage"
          :disabled="currentPage <= 1"
          class="p-2 rounded-xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 text-slate-700 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800 disabled:opacity-40 disabled:cursor-not-allowed transition cursor-pointer flex items-center gap-1 font-bold"
          title="Previous Page"
        >
          <ChevronLeft class="w-4 h-4" />
          <span class="hidden sm:inline">Prev</span>
        </button>

        <div class="flex items-center gap-1">
          <button
            v-for="p in paginationPages"
            :key="p"
            @click="goToPage(p)"
            :class="[
              'w-8 h-8 rounded-xl font-black text-xs transition cursor-pointer flex items-center justify-center border',
              currentPage === p
                ? 'bg-blue-600 border-blue-600 text-white shadow-md shadow-blue-600/20'
                : 'bg-white dark:bg-slate-900 border-slate-200 dark:border-slate-800 text-slate-700 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800'
            ]"
          >
            {{ p }}
          </button>
        </div>

        <button
          @click="nextPage"
          :disabled="currentPage >= lastPage"
          class="p-2 rounded-xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 text-slate-700 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800 disabled:opacity-40 disabled:cursor-not-allowed transition cursor-pointer flex items-center gap-1 font-bold"
          title="Next Page"
        >
          <span class="hidden sm:inline">Next</span>
          <ChevronRight class="w-4 h-4" />
        </button>
      </div>
    </div>
  </div>
</template>

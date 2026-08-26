<script setup lang="ts">
import { ref, computed, watch, onMounted, onBeforeUnmount } from 'vue'
import type { RoomType } from '../../types/roomType'
import {
  MoreVertical,
  Eye,
  Edit,
  Trash2,
  ChevronLeft,
  ChevronRight,
  Users
} from 'lucide-vue-next'

const props = defineProps<{
  roomTypes: RoomType[]
}>()

const emit = defineEmits(['view', 'edit', 'delete'])

const openMenuId = ref<number | null>(null)
const currentPage = ref(1)
const perPage = ref(10)

const total = computed(() => props.roomTypes?.length || 0)
const lastPage = computed(() => Math.ceil(total.value / perPage.value) || 1)

const paginatedRoomTypes = computed(() => {
  const start = (currentPage.value - 1) * perPage.value
  const end = start + perPage.value
  return (props.roomTypes || []).slice(start, end)
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

watch(() => props.roomTypes, () => {
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

const toggleMenu = (id: number, e: MouseEvent) => {
  e.stopPropagation()
  openMenuId.value = openMenuId.value === id ? null : id
}

const closeMenu = () => {
  openMenuId.value = null
}

const handleView = (rt: RoomType) => {
  emit('view', rt)
  closeMenu()
}

const handleEdit = (rt: RoomType) => {
  emit('edit', rt)
  closeMenu()
}

const handleDelete = (rt: RoomType) => {
  emit('delete', rt)
  closeMenu()
}

const handleClickOutside = (e: MouseEvent) => {
  const target = e.target as HTMLElement
  if (openMenuId.value !== null && !target.closest('[data-menu-container]')) {
    closeMenu()
  }
}

onMounted(() => {
  document.addEventListener('click', handleClickOutside)
})

onBeforeUnmount(() => {
  document.removeEventListener('click', handleClickOutside)
})
</script>

<template>
  <div class="rounded-2xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 shadow-xs overflow-hidden font-sans w-full">
    <!-- Desktop Table View -->
    <div class="hidden md:block overflow-x-auto w-full">
      <table class="w-full text-left border-collapse">
        <thead class="bg-slate-50/90 dark:bg-slate-950 border-b border-slate-200 dark:border-slate-800">
          <tr class="text-[10px] font-black text-slate-500 dark:text-slate-400 uppercase tracking-wider select-none">
            <th class="py-3 px-6">Type Name</th>
            <th class="py-3 px-6">Price/Night</th>
            <th class="py-3 px-6 text-center">Capacity</th>
            <th class="py-3 px-6 text-center">Status</th>
            <th class="py-3 px-6 text-right pr-6">Actions</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-slate-100 dark:divide-slate-800/60 text-xs">
          <tr
            v-for="rt in paginatedRoomTypes"
            :key="rt.id"
            class="hover:bg-slate-50/70 dark:hover:bg-slate-800/40 transition-colors duration-150 group"
          >
            <!-- Type Name -->
            <td class="py-3.5 px-6 whitespace-nowrap">
              <div class="font-extrabold text-slate-900 dark:text-white text-xs sm:text-sm">
                {{ rt.name }}
              </div>
              <div class="text-[10px] text-slate-400 dark:text-slate-500 font-mono">
                ID: #{{ rt.id }}
              </div>
            </td>

            <!-- Price per Night -->
            <td class="py-3.5 px-6 whitespace-nowrap font-extrabold text-slate-900 dark:text-white text-xs sm:text-sm font-mono">
              ₹{{ parseFloat(rt.base_price_per_night || 0).toLocaleString('en-IN') }}
            </td>

            <!-- Capacity -->
            <td class="py-3.5 px-6 text-center whitespace-nowrap">
              <span class="inline-flex items-center gap-1 bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 px-2.5 py-1 rounded-xl text-xs font-black border border-slate-200 dark:border-slate-700">
                <Users class="w-3.5 h-3.5 text-slate-400" />
                {{ rt.capacity }} Guests
              </span>
            </td>

            <!-- Status -->
            <td class="py-3.5 px-6 text-center whitespace-nowrap">
              <span
                v-if="rt.is_active"
                class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-[10px] font-extrabold bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 border border-emerald-500/20 select-none"
              >
                <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse"></span>
                <span>Active</span>
              </span>
              <span
                v-else
                class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-[10px] font-extrabold bg-rose-500/10 text-rose-600 dark:text-rose-400 border border-rose-500/20 select-none"
              >
                <span class="w-1.5 h-1.5 rounded-full bg-rose-500"></span>
                <span>Inactive</span>
              </span>
            </td>

            <!-- Actions Menu -->
            <td class="py-3.5 px-6 text-right whitespace-nowrap pr-6 relative" data-menu-container>
              <div class="relative inline-block text-left">
                <button
                  @click="toggleMenu(rt.id, $event)"
                  class="p-1.5 rounded-xl text-slate-400 hover:text-slate-700 dark:hover:text-white hover:bg-slate-100 dark:hover:bg-slate-800 transition cursor-pointer"
                  :class="{ 'bg-slate-100 dark:bg-slate-800 text-slate-900 dark:text-white': openMenuId === rt.id }"
                  title="Actions"
                >
                  <MoreVertical class="w-4 h-4" />
                </button>

                <!-- Dropdown Card Popup -->
                <transition
                  enter-active-class="transition duration-100 ease-out"
                  leave-active-class="transition duration-75 ease-in"
                  enter-from-class="opacity-0 scale-95 -translate-y-2"
                  enter-to-class="opacity-100 scale-100 translate-y-0"
                  leave-from-class="opacity-100 scale-100 translate-y-0"
                  leave-to-class="opacity-0 scale-95 -translate-y-2"
                >
                  <div
                    v-if="openMenuId === rt.id"
                    class="absolute right-0 top-8 z-50 w-40 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl shadow-2xl p-1.5 space-y-1 text-left"
                    @click.stop
                  >
                    <button
                      @click="handleView(rt)"
                      class="flex w-full items-center gap-2 px-3 py-2 rounded-xl text-xs font-bold text-slate-700 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800 transition cursor-pointer"
                    >
                      <Eye class="w-3.5 h-3.5 text-blue-500" />
                      <span>View Details</span>
                    </button>

                    <button
                      @click="handleEdit(rt)"
                      class="flex w-full items-center gap-2 px-3 py-2 rounded-xl text-xs font-bold text-amber-600 dark:text-amber-400 hover:bg-amber-50 dark:hover:bg-amber-500/10 transition cursor-pointer"
                    >
                      <Edit class="w-3.5 h-3.5 text-amber-500" />
                      <span>Edit Type</span>
                    </button>

                    <div class="border-t border-slate-100 dark:border-slate-800 my-1"></div>

                    <button
                      @click="handleDelete(rt)"
                      class="flex w-full items-center gap-2 px-3 py-2 rounded-xl text-xs font-bold text-rose-600 dark:text-rose-400 hover:bg-rose-50 dark:hover:bg-rose-500/10 transition cursor-pointer"
                    >
                      <Trash2 class="w-3.5 h-3.5 text-rose-500" />
                      <span>Delete Type</span>
                    </button>
                  </div>
                </transition>
              </div>
            </td>
          </tr>

          <!-- Empty State -->
          <tr v-if="roomTypes.length === 0">
            <td colspan="5" class="px-6 py-12 text-center text-slate-500 dark:text-slate-400 text-xs font-bold">
              No room types found.
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <!-- Mobile Card View -->
    <div class="md:hidden divide-y divide-slate-100 dark:divide-slate-800">
      <div v-if="roomTypes.length === 0" class="px-4 py-8 text-center text-slate-500 dark:text-slate-400 text-xs font-bold">
        No room types found
      </div>

      <div
        v-for="rt in paginatedRoomTypes"
        :key="rt.id"
        class="p-4 space-y-3 hover:bg-slate-50/50 dark:hover:bg-slate-800/40 transition duration-150"
      >
        <div class="flex items-start justify-between gap-2">
          <div>
            <h3 class="font-extrabold text-sm text-slate-900 dark:text-white">{{ rt.name }}</h3>
            <p class="text-xs text-slate-400 dark:text-slate-500 font-mono mt-0.5">ID: #{{ rt.id }}</p>
          </div>
          <span
            v-if="rt.is_active"
            class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 border border-emerald-500/20"
          >
            <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
            Active
          </span>
          <span
            v-else
            class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-rose-500/10 text-rose-600 dark:text-rose-400 border border-rose-500/20"
          >
            <span class="w-1.5 h-1.5 rounded-full bg-rose-500"></span>
            Inactive
          </span>
        </div>

        <div class="grid grid-cols-2 gap-2 text-xs bg-slate-50 dark:bg-slate-950 p-2.5 rounded-xl border border-slate-200 dark:border-slate-800">
          <div>
            <span class="text-[10px] text-slate-400 block font-bold uppercase">Price/Night</span>
            <span class="font-mono font-black text-slate-900 dark:text-white">
              ₹{{ parseFloat(rt.base_price_per_night || 0).toLocaleString('en-IN') }}
            </span>
          </div>
          <div>
            <span class="text-[10px] text-slate-400 block font-bold uppercase">Capacity</span>
            <span class="font-bold text-slate-900 dark:text-white">{{ rt.capacity }} Guests</span>
          </div>
        </div>
      </div>
    </div>

    <!-- Pagination Bar with 5, 10, 20, 50 Options -->
    <div
      v-if="roomTypes.length > 0"
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
          <span class="font-extrabold text-slate-900 dark:text-white">{{ total }}</span> room types
        </div>
      </div>

      <!-- Right Side: Page Navigation Buttons -->
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

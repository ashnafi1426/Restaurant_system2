<script setup lang="ts">
import { ref, computed, onMounted, onBeforeUnmount, watch } from 'vue'
import RoomStatusBadge from './RoomStatusBadge.vue'
import { useLanguageStore } from '@/stores/language'
import type { Room } from '../../types/room'

const languageStore = useLanguageStore()
import {
  Search,
  Filter,
  X,
  RefreshCw,
  Maximize2,
  Minimize2,
  Plus,
  RotateCcw,
  BedDouble,
  MoreVertical,
  Eye,
  Edit,
  Trash2,
  ChevronLeft,
  ChevronRight,
  Users,
  Loader2,
} from 'lucide-vue-next'

interface PaginationMeta {
  current_page: number
  last_page: number
  per_page: number
  total: number
  from: number
  to: number
}

const props = withDefaults(
  defineProps<{
    rooms?: Room[]
    loading?: boolean
    pagination?: PaginationMeta | null
  }>(),
  {
    rooms: () => [],
    loading: false,
    pagination: null,
  },
)

const emit = defineEmits<{
  (e: 'view', room: Room): void
  (e: 'edit', room: Room): void
  (e: 'delete', room: Room): void
  (e: 'refresh'): void
  (e: 'create'): void
  (e: 'filter-change', filters: Record<string, any>): void
  (e: 'page-change', page: number): void
  (e: 'per-page-change', perPage: number): void
}>()

const openMenu = ref<string | null>(null)
const isFilterOpen = ref(false)
const isFullscreen = ref(false)

// Filter state - now sent to parent/API instead of filtering client-side
const search = ref('')
const statusFilter = ref('')
const floorFilter = ref('')
const activeFilter = ref('')

// Pagination now comes from API response
const currentPage = computed(() => props.pagination?.current_page || 1)
const lastPage = computed(() => props.pagination?.last_page || 1)
const perPage = ref(25)
const total = computed(() => props.pagination?.total || 0)

// Rooms displayed are directly from props (already filtered/paginated by API)
const displayedRooms = computed(() => props.rooms || [])

const showingFrom = computed(() => props.pagination?.from || 0)
const showingTo = computed(() => props.pagination?.to || 0)

const paginationPages = computed(() => {
  const pages: number[] = []
  const max = lastPage.value
  const cur = currentPage.value

  for (let i = Math.max(1, cur - 2); i <= Math.min(max, cur + 2); i++) {
    pages.push(i)
  }
  return pages
})

// Watch filters and emit changes to parent (which will call API)
watch([search, statusFilter, floorFilter, activeFilter], () => {
  emitFilterChange()
})

const emitFilterChange = () => {
  const filters: Record<string, any> = {}
  
  if (search.value.trim()) {
    filters.search = search.value.trim()
  }
  if (statusFilter.value) {
    filters.status = statusFilter.value
  }
  if (floorFilter.value) {
    filters.floor = floorFilter.value
  }
  if (activeFilter.value) {
    filters.is_active = activeFilter.value === 'active' ? '1' : '0'
  }
  
  filters.page = 1 // Reset to page 1 on filter change
  filters.per_page = perPage.value
  
  emit('filter-change', filters)
}

const changePerPage = (event: Event) => {
  const target = event.target as HTMLSelectElement
  perPage.value = Number(target.value)
  emit('per-page-change', perPage.value)
  
  // Re-emit filters with new per_page
  emitFilterChange()
}

const goToPage = (p: number) => {
  if (p >= 1 && p <= lastPage.value && p !== currentPage.value) {
    emit('page-change', p)
  }
}

const prevPage = () => {
  if (currentPage.value > 1) {
    goToPage(currentPage.value - 1)
  }
}

const nextPage = () => {
  if (currentPage.value < lastPage.value) {
    goToPage(currentPage.value + 1)
  }
}

const resetFilters = () => {
  search.value = ''
  statusFilter.value = ''
  floorFilter.value = ''
  activeFilter.value = ''
  perPage.value = 25
  
  // Emit empty filters to parent
  emit('filter-change', { page: 1, per_page: 25 })
}

const toggleFilter = () => {
  isFilterOpen.value = !isFilterOpen.value
}

const toggleFullscreen = () => {
  isFullscreen.value = !isFullscreen.value
}

const toggleMenu = (id: string, event: MouseEvent) => {
  event.stopPropagation()
  openMenu.value = openMenu.value === id ? null : id
}

const closeMenu = () => {
  openMenu.value = null
}

const handleView = (room: Room) => {
  emit('view', room)
  closeMenu()
}

const handleEdit = (room: Room) => {
  emit('edit', room)
  closeMenu()
}

const handleDelete = (room: Room) => {
  emit('delete', room)
  closeMenu()
}

const handleClickOutside = () => {
  closeMenu()
}

onMounted(() => {
  document.addEventListener('click', handleClickOutside)
})

onBeforeUnmount(() => {
  document.removeEventListener('click', handleClickOutside)
})
</script>

<template>
  <div
    class="space-y-3 font-sans w-full"
    :class="{ 'fixed inset-0 z-50 p-6 overflow-y-auto bg-white dark:bg-slate-950': isFullscreen }"
  >
    <div
      class="flex flex-wrap items-center justify-between gap-3 rounded-2xl border border-slate-200 dark:border-slate-800/80 bg-white dark:bg-[#0b1527] p-3 sm:p-4 shadow-xs transition-all"
    >
      <div class="flex flex-1 items-center gap-2.5 min-w-[280px] max-w-2xl">
        <div class="relative flex-1">
          <Search class="w-4 h-4 absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400 dark:text-slate-500" />
          <input
            v-model="search"
            type="text"
            :placeholder="languageStore.t('search_rooms_placeholder', 'Search rooms by number, room type, floor...')"
            class="w-full rounded-xl border border-slate-200 dark:border-[#1e3455] bg-slate-50/80 dark:bg-[#13233c] text-slate-900 dark:text-white placeholder-slate-400 dark:placeholder-slate-500 pl-10 pr-4 py-2.5 text-xs sm:text-sm focus:ring-2 focus:ring-blue-500/40 focus:border-blue-500 dark:focus:border-blue-400 transition outline-none"
          />
        </div>

        <button
          type="button"
          @click="toggleFilter"
          class="inline-flex items-center gap-2 rounded-xl px-4 py-2.5 text-xs sm:text-sm font-semibold transition cursor-pointer flex-shrink-0"
          :class="[
            isFilterOpen
              ? 'bg-blue-600/10 dark:bg-blue-900/30 text-blue-600 dark:text-blue-400 border border-blue-500/40'
              : 'border border-slate-200 dark:border-[#1e3455] bg-white dark:bg-[#13233c]/80 text-slate-700 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-[#1c3356]'
          ]"
        >
          <component :is="isFilterOpen ? X : Filter" class="w-4 h-4" />
          <span>{{ isFilterOpen ? languageStore.t('hide_filter', 'Hide Filter') : languageStore.t('filter', 'Filter') }}</span>
        </button>
      </div>

      <div class="flex items-center gap-2 sm:gap-2.5">
        <button
          type="button"
          @click="emit('refresh')"
          :disabled="loading"
          :title="languageStore.t('refresh', 'Refresh')"
          class="inline-flex items-center justify-center h-9 w-9 sm:h-10 sm:w-10 rounded-xl border border-slate-200 dark:border-[#1e3455] bg-white dark:bg-[#13233c]/80 text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-[#1c3356] hover:text-slate-900 dark:hover:text-white transition disabled:opacity-50 cursor-pointer"
        >
          <RefreshCw class="w-4 h-4" :class="{ 'animate-spin': loading }" />
        </button>

        <button
          type="button"
          @click="toggleFullscreen"
          :title="languageStore.t('fullscreen', 'Toggle Fullscreen')"
          class="inline-flex items-center justify-center h-9 w-9 sm:h-10 sm:w-10 rounded-xl border border-slate-200 dark:border-[#1e3455] bg-white dark:bg-[#13233c]/80 text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-[#1c3356] hover:text-slate-900 dark:hover:text-white transition cursor-pointer"
        >
          <component :is="isFullscreen ? Minimize2 : Maximize2" class="w-4 h-4" />
        </button>

        <button
          type="button"
          @click="emit('create')"
          class="inline-flex items-center gap-1.5 rounded-xl bg-blue-600 hover:bg-blue-700 dark:bg-[#0066FF] dark:hover:bg-[#0055DD] px-3.5 sm:px-4 py-2.5 text-xs sm:text-sm font-bold text-white shadow-sm shadow-blue-600/30 transition active:scale-98 cursor-pointer flex-shrink-0"
        >
          <Plus class="w-4 h-4" />
          <span>{{ languageStore.t('add_room', 'Add Room') }}</span>
        </button>
      </div>
    </div>

    <Transition
      enter-active-class="transition duration-200 ease-out"
      enter-from-class="transform -translate-y-2 opacity-0 scale-98"
      enter-to-class="transform translate-y-0 opacity-100 scale-100"
      leave-active-class="transition duration-150 ease-in"
      leave-from-class="transform translate-y-0 opacity-100 scale-100"
      leave-to-class="transform -translate-y-2 opacity-0 scale-98"
    >
      <div
        v-if="isFilterOpen"
        class="rounded-2xl border border-slate-200 dark:border-slate-800/80 bg-white dark:bg-[#0b1527] p-4 sm:p-5 shadow-sm space-y-4"
      >
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3.5 sm:gap-4">
          <div>
            <label class="mb-1.5 block text-xs font-semibold text-slate-600 dark:text-slate-300">
              {{ languageStore.t('room_status', 'Room Status') }}
            </label>
            <select
              v-model="statusFilter"
              class="w-full rounded-xl border border-slate-200 dark:border-[#1e3455] bg-slate-50/80 dark:bg-[#13233c] text-slate-900 dark:text-white px-3.5 py-2 text-xs sm:text-sm focus:ring-2 focus:ring-blue-500/40 focus:border-blue-500 dark:focus:border-blue-400 transition cursor-pointer font-medium outline-none"
            >
              <option value="">{{ languageStore.t('all_statuses', 'All Statuses') }}</option>
              <option value="available">{{ languageStore.t('available', 'Available') }}</option>
              <option value="occupied">{{ languageStore.t('occupied', 'Occupied') }}</option>
              <option value="reserved">{{ languageStore.t('reserved', 'Reserved') }}</option>
              <option value="maintenance">{{ languageStore.t('maintenance', 'Maintenance') }}</option>
              <option value="cleaning">{{ languageStore.t('cleaning', 'Cleaning') }}</option>
            </select>
          </div>

          <div>
            <label class="mb-1.5 block text-xs font-semibold text-slate-600 dark:text-slate-300">
              {{ languageStore.t('floor', 'Floor') }}
            </label>
            <select
              v-model="floorFilter"
              class="w-full rounded-xl border border-slate-200 dark:border-[#1e3455] bg-slate-50/80 dark:bg-[#13233c] text-slate-900 dark:text-white px-3.5 py-2 text-xs sm:text-sm focus:ring-2 focus:ring-blue-500/40 focus:border-blue-500 dark:focus:border-blue-400 transition cursor-pointer font-medium outline-none"
            >
              <option value="">{{ languageStore.t('all_floors', 'All Floors') }}</option>
              <option value="1">{{ languageStore.t('floor', 'Floor') }} 1</option>
              <option value="2">{{ languageStore.t('floor', 'Floor') }} 2</option>
              <option value="3">{{ languageStore.t('floor', 'Floor') }} 3</option>
              <option value="4">{{ languageStore.t('floor', 'Floor') }} 4</option>
              <option value="5">{{ languageStore.t('floor', 'Floor') }} 5</option>
            </select>
          </div>

          <div>
            <label class="mb-1.5 block text-xs font-semibold text-slate-600 dark:text-slate-300">
              {{ languageStore.t('activation', 'Activation') }}
            </label>
            <select
              v-model="activeFilter"
              class="w-full rounded-xl border border-slate-200 dark:border-[#1e3455] bg-slate-50/80 dark:bg-[#13233c] text-slate-900 dark:text-white px-3.5 py-2 text-xs sm:text-sm focus:ring-2 focus:ring-blue-500/40 focus:border-blue-500 dark:focus:border-blue-400 transition cursor-pointer font-medium outline-none"
            >
              <option value="">{{ languageStore.t('all_activation_states', 'All Activation States') }}</option>
              <option value="active">{{ languageStore.t('active', 'Active') }}</option>
              <option value="inactive">{{ languageStore.t('inactive', 'Inactive') }}</option>
            </select>
          </div>

          <div class="flex items-end">
            <button
              type="button"
              @click="resetFilters"
              class="w-full inline-flex items-center justify-center gap-1.5 rounded-xl border border-slate-200 dark:border-[#1e3455] bg-slate-100/70 dark:bg-[#13233c] px-3.5 py-2 text-xs font-semibold text-slate-700 dark:text-slate-300 hover:bg-slate-200 dark:hover:bg-[#1c3356] transition cursor-pointer"
            >
              <RotateCcw class="w-3.5 h-3.5" />
              <span>{{ languageStore.t('reset_filters', 'Reset Filters') }}</span>
            </button>
          </div>
        </div>
      </div>
    </Transition>

    <div class="rounded-2xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 shadow-xs overflow-hidden font-sans w-full">
      <div class="hidden md:block overflow-x-auto w-full">
        <table class="w-full text-left border-collapse">
          <thead class="bg-slate-50/90 dark:bg-[#0c182c] border-b border-slate-200 dark:border-[#1e3455]">
            <tr class="text-[11px] font-bold text-slate-500 dark:text-slate-400 select-none">
              <th class="px-3 py-3 pl-5 whitespace-nowrap">{{ languageStore.t('room', 'Room') }}</th>
              <th class="px-3 py-3 whitespace-nowrap">{{ languageStore.t('type', 'Type') }}</th>
              <th class="px-3 py-3 text-center whitespace-nowrap">{{ languageStore.t('floor', 'Floor') }}</th>
              <th class="px-3 py-3 text-center whitespace-nowrap">{{ languageStore.t('capacity', 'Capacity') }}</th>
              <th class="px-3 py-3 text-right whitespace-nowrap">{{ languageStore.t('price_night', 'Price/Night') }}</th>
              <th class="px-3 py-3 text-center whitespace-nowrap">{{ languageStore.t('status', 'Status') }}</th>
              <th class="px-3 py-3 text-right pr-5 whitespace-nowrap">{{ languageStore.t('actions', 'Actions') }}</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-100 dark:divide-[#1e3455]/60 text-xs">
            <tr v-if="loading">
              <td colspan="7" class="px-6 py-20 text-center">
                <div class="flex flex-col items-center justify-center gap-3">
                  <Loader2 class="w-8 h-8 text-blue-600 dark:text-blue-400 animate-spin" />
                  <div class="space-y-0.5">
                    <p class="text-xs sm:text-sm font-extrabold text-slate-800 dark:text-slate-200">{{ languageStore.t('loading_rooms', 'Loading Rooms...') }}</p>
                    <p class="text-[11px] text-slate-500 dark:text-slate-400">{{ languageStore.t('fetching_rooms_sub', 'Fetching room availability and occupancy') }}</p>
                  </div>
                </div>
              </td>
            </tr>

            <template v-else>
              <tr
                v-for="room in displayedRooms"
                :key="room.id"
                class="hover:bg-slate-50/80 dark:hover:bg-[#13233c]/60 transition-colors duration-150 group"
              >
                <td class="px-3 py-3 pl-5 whitespace-nowrap">
                  <div class="flex items-center gap-2">
                    <div class="w-7 h-7 rounded-lg bg-blue-50 dark:bg-blue-500/10 text-blue-600 dark:text-blue-400 flex items-center justify-center font-bold text-xs">
                      <BedDouble class="w-3.5 h-3.5" />
                    </div>
                    <div>
                      <div class="font-extrabold text-slate-900 dark:text-white text-xs sm:text-sm">
                        {{ languageStore.t('room', 'Room') }} {{ room.room_number }}
                      </div>
                    </div>
                  </div>
                </td>

                <td class="px-3 py-3 whitespace-nowrap">
                  <span class="font-bold text-slate-700 dark:text-slate-300">
                    {{ room.room_type?.name || 'Standard' }}
                  </span>
                </td>

                <td class="px-3 py-3 text-center whitespace-nowrap">
                  <span class="font-semibold text-slate-600 dark:text-slate-400">
                    {{ languageStore.t('floor', 'Floor') }} {{ room.floor || 1 }}
                  </span>
                </td>

                <td class="px-3 py-3 text-center whitespace-nowrap">
                  <span class="inline-flex items-center gap-1 bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 px-2 py-0.5 rounded-lg text-xs font-bold border border-slate-200 dark:border-slate-700">
                    <Users class="w-3.5 h-3.5 text-slate-400" />
                    {{ room.room_type?.capacity || (room as any).capacity || 2 }}
                  </span>
                </td>

                <td class="px-3 py-3 text-right whitespace-nowrap font-extrabold text-slate-900 dark:text-white font-mono">
                  ${{ parseFloat(String(room.room_type?.base_price_per_night || (room as any).price_per_night || 0)).toFixed(2) }}
                </td>

                <td class="px-3 py-3 text-center whitespace-nowrap">
                  <RoomStatusBadge :status="room.status" />
                </td>

                <td class="px-3 py-3 text-right whitespace-nowrap pr-5 relative" @click.stop>
                  <div class="relative inline-block text-left">
                    <button
                      @click="toggleMenu(String(room.id), $event)"
                      class="p-1.5 rounded-xl text-slate-400 hover:text-slate-700 dark:hover:text-white hover:bg-slate-100 dark:hover:bg-slate-800 transition cursor-pointer"
                      :class="{ 'bg-slate-100 dark:bg-slate-800 text-slate-900 dark:text-white': openMenu === String(room.id) }"
                      :title="languageStore.t('actions', 'Actions')"
                    >
                      <MoreVertical class="w-4 h-4" />
                    </button>

                    <transition
                      enter-active-class="transition duration-100 ease-out"
                      leave-active-class="transition duration-75 ease-in"
                      enter-from-class="opacity-0 scale-95 -translate-y-2"
                      enter-to-class="opacity-100 scale-100 translate-y-0"
                      leave-from-class="opacity-100 scale-100 translate-y-0"
                      leave-to-class="opacity-0 scale-95 -translate-y-2"
                    >
                      <div
                        v-if="openMenu === String(room.id)"
                        class="absolute right-0 top-8 z-50 w-36 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl shadow-2xl p-1.5 space-y-1 text-left"
                      >
                        <button
                          @click="handleView(room)"
                          class="flex w-full items-center gap-2 px-3 py-1.5 rounded-xl text-xs font-bold text-slate-700 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800 transition cursor-pointer"
                        >
                          <Eye class="w-3.5 h-3.5 text-blue-500" />
                          <span>{{ languageStore.t('view', 'View') }}</span>
                        </button>

                        <button
                          @click="handleEdit(room)"
                          class="flex w-full items-center gap-2 px-3 py-1.5 rounded-xl text-xs font-bold text-amber-600 dark:text-amber-400 hover:bg-amber-50 dark:hover:bg-amber-500/10 transition cursor-pointer"
                        >
                          <Edit class="w-3.5 h-3.5 text-amber-500" />
                          <span>{{ languageStore.t('edit', 'Edit') }}</span>
                        </button>

                        <div class="border-t border-slate-100 dark:border-slate-800 my-0.5"></div>

                        <button
                          @click="handleDelete(room)"
                          class="flex w-full items-center gap-2 px-3 py-1.5 rounded-xl text-xs font-bold text-rose-600 dark:text-rose-400 hover:bg-rose-50 dark:hover:bg-rose-500/10 transition cursor-pointer"
                        >
                          <Trash2 class="w-3.5 h-3.5 text-rose-500" />
                          <span>{{ languageStore.t('delete', 'Delete') }}</span>
                        </button>
                      </div>
                    </transition>
                  </div>
                </td>
              </tr>

              <tr v-if="displayedRooms.length === 0">
                <td colspan="7" class="px-6 py-12 text-center text-slate-500 dark:text-slate-400 text-xs font-bold">
                  {{ languageStore.t('no_rooms_match', 'No rooms match your current search or filter criteria.') }}
                </td>
              </tr>
            </template>
          </tbody>
        </table>
      </div>

      <div class="md:hidden divide-y divide-slate-100 dark:divide-slate-800">
        <div v-if="loading" class="py-16 text-center flex flex-col items-center justify-center gap-3">
          <Loader2 class="w-8 h-8 text-blue-600 dark:text-blue-400 animate-spin" />
          <span class="text-xs font-extrabold text-slate-700 dark:text-slate-300">{{ languageStore.t('loading_rooms', 'Loading rooms...') }}</span>
        </div>
        <template v-else>
          <div
            v-for="room in displayedRooms"
            :key="room.id"
            class="p-4 space-y-3 hover:bg-slate-50/60 dark:hover:bg-slate-800/40 transition"
          >
            <div class="flex items-center justify-between">
              <div class="flex items-center gap-2">
                <BedDouble class="w-4 h-4 text-blue-600 dark:text-blue-400" />
                <span class="font-bold text-slate-900 dark:text-white text-sm">
                  {{ languageStore.t('room', 'Room') }} {{ room.room_number }}
                </span>
              </div>
              <RoomStatusBadge :status="room.status" />
            </div>

            <div class="grid grid-cols-2 gap-2 text-xs text-slate-600 dark:text-slate-400">
              <div>{{ languageStore.t('type', 'Type') }}: {{ room.room_type?.name || 'Standard' }}</div>
              <div>{{ languageStore.t('floor', 'Floor') }}: {{ room.floor || 1 }}</div>
            </div>

            <div class="flex gap-2 pt-2 border-t border-slate-100 dark:border-slate-800">
              <button
                @click="handleView(room)"
                class="flex-1 py-1.5 text-xs font-bold text-slate-700 dark:text-slate-300 bg-slate-100 dark:bg-slate-800 rounded-lg cursor-pointer"
              >
                {{ languageStore.t('view', 'View') }}
              </button>
              <button
                @click="handleEdit(room)"
                class="flex-1 py-1.5 text-xs font-bold text-amber-600 dark:text-amber-400 bg-amber-50 dark:bg-amber-950/40 rounded-lg cursor-pointer"
              >
                {{ languageStore.t('edit', 'Edit') }}
              </button>
              <button
                @click="handleDelete(room)"
                class="flex-1 py-1.5 text-xs font-bold text-rose-600 dark:text-rose-400 bg-rose-50 dark:bg-rose-950/40 rounded-lg cursor-pointer"
              >
                {{ languageStore.t('delete', 'Delete') }}
              </button>
            </div>
          </div>
          <div v-if="displayedRooms.length === 0" class="p-8 text-center text-slate-500 text-xs font-bold">
            {{ languageStore.t('no_rooms_match', 'No rooms match your current search or filter criteria.') }}
          </div>
        </template>
      </div>

      <div
        v-if="total > 0"
        class="border-t border-slate-200 dark:border-slate-800/80 px-4 sm:px-6 py-3 sm:py-4 bg-slate-50/50 dark:bg-[#0c182c] flex flex-col sm:flex-row items-center justify-between gap-3 text-xs"
      >
        <div class="text-slate-500 dark:text-slate-400 font-medium">
          {{ languageStore.t('showing', 'Showing') }} <span class="font-bold text-slate-900 dark:text-white">{{ showingFrom }}</span> {{ languageStore.t('to', 'to') }}
          <span class="font-bold text-slate-900 dark:text-white">{{ showingTo }}</span> {{ languageStore.t('of', 'of') }}
          <span class="font-bold text-slate-900 dark:text-white">{{ total }}</span> {{ languageStore.t('rooms', 'rooms') }}
        </div>

        <div class="flex items-center gap-2 sm:gap-3">
          <div class="flex items-center gap-1.5">
            <span class="text-slate-500 dark:text-slate-400 font-medium">{{ languageStore.t('per_page', 'Per page') }}:</span>
            <select
              :value="perPage"
              @change="changePerPage"
              class="rounded-lg border border-slate-200 dark:border-slate-700 bg-white dark:bg-[#13233c] text-slate-900 dark:text-white px-2 py-1 text-xs outline-none"
            >
              <option :value="10">10</option>
              <option :value="25">25</option>
              <option :value="50">50</option>
              <option :value="100">100</option>
            </select>
          </div>

          <div class="flex items-center gap-1">
            <button
              @click="prevPage"
              :disabled="currentPage === 1"
              class="p-1 rounded-lg border border-slate-200 dark:border-slate-700 text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800 disabled:opacity-40 transition cursor-pointer"
            >
              <ChevronLeft class="w-4 h-4" />
            </button>

            <button
              v-for="page in paginationPages"
              :key="page"
              @click="goToPage(page)"
              class="px-2.5 py-1 rounded-lg text-xs font-bold transition cursor-pointer"
              :class="[
                currentPage === page
                  ? 'bg-blue-600 text-white'
                  : 'text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800'
              ]"
            >
              {{ page }}
            </button>

            <button
              @click="nextPage"
              :disabled="currentPage === lastPage"
              class="p-1 rounded-lg border border-slate-200 dark:border-slate-700 text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800 disabled:opacity-40 transition cursor-pointer"
            >
              <ChevronRight class="w-4 h-4" />
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

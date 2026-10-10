<script setup lang="ts">
import { ref, computed, watch, onMounted, onBeforeUnmount } from 'vue'
import type { RoomType } from '../../types/roomType'
import { useLanguageStore } from '@/stores/language'
import {
  Search,
  Filter,
  X,
  RefreshCw,
  Maximize2,
  Minimize2,
  Plus,
  RotateCcw,
  MoreVertical,
  Eye,
  Edit,
  Trash2,
  ChevronLeft,
  ChevronRight,
  Users,
  Loader2,
} from 'lucide-vue-next'

const props = defineProps<{
  roomTypes: RoomType[]
  loading?: boolean
}>()

const emit = defineEmits<{
  (e: 'view', rt: RoomType): void
  (e: 'edit', rt: RoomType): void
  (e: 'delete', rt: RoomType): void
  (e: 'refresh'): void
  (e: 'create'): void
}>()

const languageStore = useLanguageStore()
const openMenuId = ref<string | number | null>(null)
const isFilterOpen = ref(false)
const isFullscreen = ref(false)

const search = ref('')
const statusFilter = ref('')
const capacityFilter = ref('')
const priceMin = ref('')
const priceMax = ref('')

const currentPage = ref(1)
const perPage = ref(10)

const filteredList = computed(() => {
  let list = props.roomTypes || []

  if (search.value.trim()) {
    const q = search.value.toLowerCase().trim()
    list = list.filter(
      (rt) =>
        (rt.name || '').toLowerCase().includes(q) ||
        (rt.description || '').toLowerCase().includes(q) ||
        String(rt.id).includes(q),
    )
  }

  if (statusFilter.value !== '') {
    const isActive = statusFilter.value === 'active'
    list = list.filter((rt) => rt.is_active === isActive)
  }

  if (capacityFilter.value !== '') {
    const cap = Number(capacityFilter.value)
    if (cap === 4) {
      list = list.filter((rt) => Number(rt.capacity || 0) >= 4)
    } else {
      list = list.filter((rt) => Number(rt.capacity || 0) === cap)
    }
  }

  if (priceMin.value !== '') {
    const min = parseFloat(priceMin.value)
    if (!isNaN(min)) {
      list = list.filter((rt) => parseFloat(String(rt.base_price_per_night || 0)) >= min)
    }
  }

  if (priceMax.value !== '') {
    const max = parseFloat(priceMax.value)
    if (!isNaN(max)) {
      list = list.filter((rt) => parseFloat(String(rt.base_price_per_night || 0)) <= max)
    }
  }

  return list
})

const total = computed(() => filteredList.value.length)
const lastPage = computed(() => Math.ceil(total.value / perPage.value) || 1)

const paginatedRoomTypes = computed(() => {
  const start = (currentPage.value - 1) * perPage.value
  const end = start + perPage.value
  return filteredList.value.slice(start, end)
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

watch([search, statusFilter, capacityFilter, priceMin, priceMax], () => {
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

const resetFilters = () => {
  search.value = ''
  statusFilter.value = ''
  capacityFilter.value = ''
  priceMin.value = ''
  priceMax.value = ''
  currentPage.value = 1
}

const toggleFilter = () => {
  isFilterOpen.value = !isFilterOpen.value
}

const toggleFullscreen = () => {
  isFullscreen.value = !isFullscreen.value
}

const toggleMenu = (id: string | number, e: MouseEvent) => {
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
  <div
    class="space-y-3 font-sans w-full"
    :class="{ 'fixed inset-0 z-50 p-6 overflow-y-auto bg-white dark:bg-slate-950': isFullscreen }"
  >
    <div
      class="flex flex-wrap items-center justify-between gap-3 rounded-2xl border border-slate-200 dark:border-slate-800/80 bg-white dark:bg-[#0b1527] p-3 sm:p-4 shadow-xs transition-all"
    >
      <div class="flex flex-1 items-center gap-2.5 min-w-[280px] max-w-2xl">
        <div class="relative flex-1">
          <Search
            class="w-4 h-4 absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400 dark:text-slate-500"
          />
          <input
            v-model="search"
            type="text"
            :placeholder="
              languageStore.t(
                'search_room_types',
                'Search room types by name, ID, or description...',
              )
            "
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
              : 'border border-slate-200 dark:border-[#1e3455] bg-white dark:bg-[#13233c]/80 text-slate-700 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-[#1c3356]',
          ]"
        >
          <component :is="isFilterOpen ? X : Filter" class="w-4 h-4" />
          <span>{{
            isFilterOpen
              ? languageStore.t('hide_filters', 'Hide Filter')
              : languageStore.t('filter', 'Filter')
          }}</span>
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
          :title="languageStore.t('toggle_fullscreen', 'Toggle Fullscreen')"
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
          <span>{{ languageStore.t('add_room_type', 'Add Room Type') }}</span>
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
              {{ languageStore.t('status', 'Status') }}
            </label>
            <select
              v-model="statusFilter"
              class="w-full rounded-xl border border-slate-200 dark:border-[#1e3455] bg-slate-50/80 dark:bg-[#13233c] text-slate-900 dark:text-white px-3.5 py-2 text-xs sm:text-sm focus:ring-2 focus:ring-blue-500/40 focus:border-blue-500 dark:focus:border-blue-400 transition cursor-pointer font-medium outline-none"
            >
              <option value="">{{ languageStore.t('all_statuses', 'All Statuses') }}</option>
              <option value="active">{{ languageStore.t('active', 'Active') }}</option>
              <option value="inactive">{{ languageStore.t('inactive', 'Inactive') }}</option>
            </select>
          </div>

          <div>
            <label class="mb-1.5 block text-xs font-semibold text-slate-600 dark:text-slate-300">
              {{ languageStore.t('capacity', 'Capacity') }}
            </label>
            <select
              v-model="capacityFilter"
              class="w-full rounded-xl border border-slate-200 dark:border-[#1e3455] bg-slate-50/80 dark:bg-[#13233c] text-slate-900 dark:text-white px-3.5 py-2 text-xs sm:text-sm focus:ring-2 focus:ring-blue-500/40 focus:border-blue-500 dark:focus:border-blue-400 transition cursor-pointer font-medium outline-none"
            >
              <option value="">{{ languageStore.t('all_capacities', 'All Capacities') }}</option>
              <option value="1">1 {{ languageStore.t('guest', 'Guest') }}</option>
              <option value="2">2 {{ languageStore.t('guests', 'Guests') }}</option>
              <option value="3">3 {{ languageStore.t('guests', 'Guests') }}</option>
              <option value="4">4+ {{ languageStore.t('guests', 'Guests') }}</option>
            </select>
          </div>

          <div>
            <label class="mb-1.5 block text-xs font-semibold text-slate-600 dark:text-slate-300">
              {{ languageStore.t('min_price_night', 'Min Price / Night') }}
            </label>
            <input
              v-model="priceMin"
              type="number"
              placeholder="e.g. 50"
              class="w-full rounded-xl border border-slate-200 dark:border-[#1e3455] bg-slate-50/80 dark:bg-[#13233c] text-slate-900 dark:text-white px-3.5 py-2 text-xs sm:text-sm focus:ring-2 focus:ring-blue-500/40 focus:border-blue-500 dark:focus:border-blue-400 transition outline-none"
            />
          </div>

          <div>
            <label class="mb-1.5 block text-xs font-semibold text-slate-600 dark:text-slate-300">
              {{ languageStore.t('max_price_night', 'Max Price / Night') }}
            </label>
            <input
              v-model="priceMax"
              type="number"
              placeholder="e.g. 500"
              class="w-full rounded-xl border border-slate-200 dark:border-[#1e3455] bg-slate-50/80 dark:bg-[#13233c] text-slate-900 dark:text-white px-3.5 py-2 text-xs sm:text-sm focus:ring-2 focus:ring-blue-500/40 focus:border-blue-500 dark:focus:border-blue-400 transition outline-none"
            />
          </div>
        </div>

        <div
          class="flex items-center justify-between pt-1 border-t border-slate-100 dark:border-slate-800/60"
        >
          <span class="text-[11px] text-slate-500 dark:text-slate-400 font-medium">
            {{ languageStore.t('found', 'Found') }} {{ total }}
            {{ languageStore.t('matching_room_types', 'matching room type(s)') }}
          </span>
          <button
            type="button"
            @click="resetFilters"
            class="inline-flex items-center gap-1.5 rounded-lg border border-slate-200 dark:border-[#1e3455] bg-slate-100/70 dark:bg-[#13233c] px-3.5 py-1.5 text-xs font-semibold text-slate-700 dark:text-slate-300 hover:bg-slate-200 dark:hover:bg-[#1c3356] transition cursor-pointer"
          >
            <RotateCcw class="w-3.5 h-3.5" />
            <span>{{ languageStore.t('reset_filters', 'Reset Filters') }}</span>
          </button>
        </div>
      </div>
    </Transition>

    <div
      class="rounded-2xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 shadow-xs overflow-hidden font-sans w-full"
    >
      <div class="hidden md:block overflow-x-auto w-full">
        <table class="w-full text-left border-collapse">
          <thead
            class="bg-slate-50/90 dark:bg-[#0c182c] border-b border-slate-200 dark:border-[#1e3455]"
          >
            <tr class="text-[11px] font-bold text-slate-500 dark:text-slate-400 select-none">
              <th class="py-3 px-4 pl-5 whitespace-nowrap">
                {{ languageStore.t('type_name', 'Type Name') }}
              </th>
              <th class="py-3 px-4 whitespace-nowrap">
                {{ languageStore.t('price_night', 'Price/Night') }}
              </th>
              <th class="py-3 px-4 text-center whitespace-nowrap">
                {{ languageStore.t('capacity', 'Capacity') }}
              </th>
              <th class="py-3 px-4 text-center whitespace-nowrap">
                {{ languageStore.t('status', 'Status') }}
              </th>
              <th class="py-3 px-4 text-right pr-5 whitespace-nowrap">
                {{ languageStore.t('actions', 'Actions') }}
              </th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-100 dark:divide-[#1e3455]/60 text-xs">
            <tr v-if="loading">
              <td colspan="5" class="px-6 py-20 text-center">
                <div class="flex flex-col items-center justify-center gap-3">
                  <Loader2 class="w-8 h-8 text-blue-600 dark:text-blue-400 animate-spin" />
                  <div class="space-y-0.5">
                    <p class="text-xs sm:text-sm font-extrabold text-slate-800 dark:text-slate-200">
                      {{ languageStore.t('loading_room_types', 'Loading Room Types...') }}
                    </p>
                    <p class="text-[11px] text-slate-500 dark:text-slate-400">
                      {{
                        languageStore.t(
                          'fetching_room_types_sub',
                          'Fetching pricing tiers and room capacities',
                        )
                      }}
                    </p>
                  </div>
                </div>
              </td>
            </tr>

            <template v-else>
              <tr
                v-for="rt in paginatedRoomTypes"
                :key="rt.id"
                class="hover:bg-slate-50/80 dark:hover:bg-[#13233c]/60 transition-colors duration-150 group"
              >
                <td class="py-3 px-4 pl-5 whitespace-nowrap">
                  <div class="font-extrabold text-slate-900 dark:text-white text-xs sm:text-sm">
                    {{ rt.name }}
                  </div>
                  <div class="text-[10px] text-slate-400 dark:text-slate-500 font-mono">
                    ID: #{{ rt.id }}
                  </div>
                </td>

                <td
                  class="py-3 px-4 whitespace-nowrap font-extrabold text-slate-900 dark:text-white text-xs sm:text-sm font-mono"
                >
                  ${{ parseFloat(String(rt.base_price_per_night || 0)).toFixed(2) }}
                </td>

                <td class="py-3 px-4 text-center whitespace-nowrap">
                  <span
                    class="inline-flex items-center gap-1 bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 px-2.5 py-1 rounded-xl text-xs font-bold border border-slate-200 dark:border-slate-700"
                  >
                    <Users class="w-3.5 h-3.5 text-slate-400" />
                    {{ rt.capacity }} {{ languageStore.t('guests', 'Guests') }}
                  </span>
                </td>

                <td class="py-3 px-4 text-center whitespace-nowrap">
                  <span
                    v-if="rt.is_active"
                    class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-[10px] font-extrabold bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 border border-emerald-500/20 select-none"
                  >
                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse"></span>
                    <span>{{ languageStore.t('active', 'Active') }}</span>
                  </span>
                  <span
                    v-else
                    class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-[10px] font-extrabold bg-rose-500/10 text-rose-600 dark:text-rose-400 border border-rose-500/20 select-none"
                  >
                    <span class="w-1.5 h-1.5 rounded-full bg-rose-500"></span>
                    <span>{{ languageStore.t('inactive', 'Inactive') }}</span>
                  </span>
                </td>

                <td
                  class="py-3 px-4 text-right whitespace-nowrap pr-5 relative"
                  data-menu-container
                >
                  <div class="relative inline-block text-left">
                    <button
                      @click="toggleMenu(rt.id!, $event)"
                      class="p-1.5 rounded-xl text-slate-400 hover:text-slate-700 dark:hover:text-white hover:bg-slate-100 dark:hover:bg-slate-800 transition cursor-pointer"
                      :class="{
                        'bg-slate-100 dark:bg-slate-800 text-slate-900 dark:text-white':
                          openMenuId === rt.id,
                      }"
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
                        v-if="openMenuId === rt.id"
                        class="absolute right-0 top-8 z-50 w-40 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl shadow-2xl p-1.5 space-y-1 text-left"
                        @click.stop
                      >
                        <button
                          @click="handleView(rt)"
                          class="flex w-full items-center gap-2 px-3 py-2 rounded-xl text-xs font-bold text-slate-700 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800 transition cursor-pointer"
                        >
                          <Eye class="w-3.5 h-3.5 text-blue-500" />
                          <span>{{ languageStore.t('view_details', 'View Details') }}</span>
                        </button>

                        <button
                          @click="handleEdit(rt)"
                          class="flex w-full items-center gap-2 px-3 py-2 rounded-xl text-xs font-bold text-amber-600 dark:text-amber-400 hover:bg-amber-50 dark:hover:bg-amber-500/10 transition cursor-pointer"
                        >
                          <Edit class="w-3.5 h-3.5 text-amber-500" />
                          <span>{{ languageStore.t('edit_type', 'Edit Type') }}</span>
                        </button>

                        <div class="border-t border-slate-100 dark:border-slate-800 my-1"></div>

                        <button
                          @click="handleDelete(rt)"
                          class="flex w-full items-center gap-2 px-3 py-2 rounded-xl text-xs font-bold text-rose-600 dark:text-rose-400 hover:bg-rose-50 dark:hover:bg-rose-500/10 transition cursor-pointer"
                        >
                          <Trash2 class="w-3.5 h-3.5 text-rose-500" />
                          <span>{{ languageStore.t('delete_type', 'Delete Type') }}</span>
                        </button>
                      </div>
                    </transition>
                  </div>
                </td>
              </tr>

              <tr v-if="paginatedRoomTypes.length === 0">
                <td
                  colspan="5"
                  class="px-6 py-12 text-center text-slate-500 dark:text-slate-400 text-xs font-bold"
                >
                  {{
                    languageStore.t(
                      'no_room_types_found',
                      'No room types match your current search or filter criteria.',
                    )
                  }}
                </td>
              </tr>
            </template>
          </tbody>
        </table>
      </div>

      <div class="md:hidden divide-y divide-slate-100 dark:divide-slate-800">
        <div
          v-if="loading"
          class="py-16 text-center flex flex-col items-center justify-center gap-3"
        >
          <Loader2 class="w-8 h-8 text-blue-600 dark:text-blue-400 animate-spin" />
          <span class="text-xs font-extrabold text-slate-700 dark:text-slate-300">
            {{ languageStore.t('loading_room_types', 'Loading Room Types...') }}
          </span>
        </div>
        <template v-else>
          <div
            v-for="rt in paginatedRoomTypes"
            :key="rt.id"
            class="p-4 space-y-3 hover:bg-slate-50/60 dark:hover:bg-slate-800/40 transition"
          >
            <div class="flex items-center justify-between">
              <span class="font-bold text-slate-900 dark:text-white text-sm">
                {{ rt.name }}
              </span>
              <span
                v-if="rt.is_active"
                class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-500/10 text-emerald-600 border border-emerald-500/20"
              >
                {{ languageStore.t('active', 'Active') }}
              </span>
              <span
                v-else
                class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-rose-500/10 text-rose-600 border border-rose-500/20"
              >
                {{ languageStore.t('inactive', 'Inactive') }}
              </span>
            </div>

            <div
              class="flex items-center justify-between text-xs text-slate-600 dark:text-slate-400"
            >
              <span
                >{{ languageStore.t('capacity', 'Capacity') }}: {{ rt.capacity }}
                {{ languageStore.t('guests', 'Guests') }}</span
              >
              <span class="font-extrabold text-slate-900 dark:text-white"
                >${{ parseFloat(String(rt.base_price_per_night || 0)).toFixed(2) }}</span
              >
            </div>

            <div class="flex gap-2 pt-2 border-t border-slate-100 dark:border-slate-800">
              <button
                @click="handleView(rt)"
                class="flex-1 py-1.5 text-xs font-bold text-slate-700 dark:text-slate-300 bg-slate-100 dark:bg-slate-800 rounded-lg cursor-pointer"
              >
                {{ languageStore.t('view', 'View') }}
              </button>
              <button
                @click="handleEdit(rt)"
                class="flex-1 py-1.5 text-xs font-bold text-amber-600 dark:text-amber-400 bg-amber-50 dark:bg-amber-950/40 rounded-lg cursor-pointer"
              >
                {{ languageStore.t('edit', 'Edit') }}
              </button>
              <button
                @click="handleDelete(rt)"
                class="flex-1 py-1.5 text-xs font-bold text-rose-600 dark:text-rose-400 bg-rose-50 dark:bg-rose-950/40 rounded-lg cursor-pointer"
              >
                {{ languageStore.t('delete', 'Delete') }}
              </button>
            </div>
          </div>
          <div
            v-if="paginatedRoomTypes.length === 0"
            class="p-8 text-center text-slate-500 text-xs font-bold"
          >
            {{
              languageStore.t(
                'no_room_types_found',
                'No room types match your current search or filter criteria.',
              )
            }}
          </div>
        </template>
      </div>

      <div
        v-if="total > 0"
        class="border-t border-slate-200 dark:border-slate-800/80 px-4 sm:px-6 py-3 sm:py-4 bg-slate-50/50 dark:bg-[#0c182c] flex flex-col sm:flex-row items-center justify-between gap-3 text-xs"
      >
        <div class="text-slate-500 dark:text-slate-400 font-medium">
          {{ languageStore.t('showing', 'Showing') }}
          <span class="font-bold text-slate-900 dark:text-white">{{ showingFrom }}</span>
          {{ languageStore.t('to', 'to') }}
          <span class="font-bold text-slate-900 dark:text-white">{{ showingTo }}</span>
          {{ languageStore.t('of', 'of') }}
          <span class="font-bold text-slate-900 dark:text-white">{{ total }}</span>
          {{ languageStore.t('room_types', 'room types') }}
        </div>

        <div class="flex items-center gap-2 sm:gap-3">
          <div class="flex items-center gap-1.5">
            <span class="text-slate-500 dark:text-slate-400 font-medium"
              >{{ languageStore.t('per_page', 'Per page') }}:</span
            >
            <select
              :value="perPage"
              @change="changePerPage"
              class="rounded-lg border border-slate-200 dark:border-slate-700 bg-white dark:bg-[#13233c] text-slate-900 dark:text-white px-2 py-1 text-xs outline-none"
            >
              <option :value="10">10</option>
              <option :value="20">20</option>
              <option :value="50">50</option>
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
                  : 'text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800',
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

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

// Add isMobile reactive ref for responsive pagination
const isMobile = ref(false)

const updateMobileStatus = () => {
  isMobile.value = window.innerWidth < 768
}

onMounted(() => {
  updateMobileStatus()
  window.addEventListener('resize', updateMobileStatus)
})

onBeforeUnmount(() => {
  window.removeEventListener('resize', updateMobileStatus)
})

const paginationPages = computed(() => {
  const pages: number[] = []
  const max = lastPage.value
  const cur = currentPage.value
  
  // Use ±1 on mobile, ±2 on desktop
  const range = isMobile.value ? 1 : 2

  for (let i = Math.max(1, cur - range); i <= Math.min(max, cur + range); i++) {
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
    class="space-y-4 font-sans w-full max-w-full"
    :class="{ 'fixed inset-0 z-50 p-6 overflow-y-auto bg-white dark:bg-slate-950': isFullscreen }"
  >
    <!-- Mobile-First Responsive Toolbar -->
    <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-700 rounded-xl shadow-sm">
      <!-- Mobile Layout: Full width stacked -->
      <div class="block sm:hidden p-4 space-y-3">
        <!-- Search Row -->
        <div class="relative">
          <Search class="w-5 h-5 absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400 dark:text-slate-500" />
          <input
            v-model="search"
            type="text"
            :placeholder="languageStore.t('search_room_types', 'Search room types...')"
            class="w-full h-12 pl-11 pr-4 border border-slate-300 dark:border-slate-600 rounded-lg bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-white placeholder-slate-500 dark:placeholder-slate-400 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors"
          />
        </div>
        
        <!-- Action Buttons Row -->
        <div class="grid grid-cols-4 gap-2">
          <button
            type="button"
            @click="toggleFilter"
            class="h-12 flex items-center justify-center gap-2 rounded-lg font-medium text-sm transition-colors"
            :class="[
              isFilterOpen
                ? 'bg-blue-100 dark:bg-blue-900/50 text-blue-700 dark:text-blue-300 border-2 border-blue-300 dark:border-blue-600'
                : 'bg-slate-100 dark:bg-slate-700 text-slate-700 dark:text-slate-300 border-2 border-slate-300 dark:border-slate-600 hover:bg-slate-200 dark:hover:bg-slate-600'
            ]"
          >
            <component :is="isFilterOpen ? X : Filter" class="w-4 h-4" />
            <span class="text-xs">Filter</span>
          </button>
          
          <button
            type="button"
            @click="emit('refresh')"
            :disabled="loading"
            class="h-12 flex items-center justify-center gap-2 bg-slate-100 dark:bg-slate-700 hover:bg-slate-200 dark:hover:bg-slate-600 text-slate-700 dark:text-slate-300 border-2 border-slate-300 dark:border-slate-600 rounded-lg font-medium text-sm transition-colors disabled:opacity-50"
          >
            <RefreshCw class="w-4 h-4" :class="{ 'animate-spin': loading }" />
            <span class="text-xs">Sync</span>
          </button>
          
          <button
            type="button"
            @click="toggleFullscreen"
            class="h-12 flex items-center justify-center gap-2 bg-slate-100 dark:bg-slate-700 hover:bg-slate-200 dark:hover:bg-slate-600 text-slate-700 dark:text-slate-300 border-2 border-slate-300 dark:border-slate-600 rounded-lg font-medium text-sm transition-colors"
          >
            <component :is="isFullscreen ? Minimize2 : Maximize2" class="w-4 h-4" />
            <span class="text-xs">Full</span>
          </button>
          
          <button
            type="button"
            @click="emit('create')"
            class="h-12 flex items-center justify-center gap-2 bg-blue-600 hover:bg-blue-700 text-white border-2 border-blue-600 hover:border-blue-700 rounded-lg font-bold text-sm transition-colors shadow-sm"
          >
            <Plus class="w-4 h-4" />
            <span class="text-xs">Add</span>
          </button>
        </div>
      </div>

      <!-- Desktop Layout: Horizontal -->
      <div class="hidden sm:flex items-center justify-between p-4 gap-4">
        <div class="flex items-center gap-3 flex-1 max-w-lg">
          <div class="relative flex-1">
            <Search class="w-5 h-5 absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400 dark:text-slate-500" />
            <input
              v-model="search"
              type="text"
              :placeholder="languageStore.t('search_room_types', 'Search room types by name, ID, or description...')"
              class="w-full h-11 pl-11 pr-4 border border-slate-300 dark:border-slate-600 rounded-lg bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-white placeholder-slate-500 dark:placeholder-slate-400 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors"
            />
          </div>
          
          <button
            type="button"
            @click="toggleFilter"
            class="h-11 px-4 flex items-center gap-2 rounded-lg font-medium text-sm transition-colors"
            :class="[
              isFilterOpen
                ? 'bg-blue-100 dark:bg-blue-900/50 text-blue-700 dark:text-blue-300 border-2 border-blue-300 dark:border-blue-600'
                : 'bg-slate-100 dark:bg-slate-700 text-slate-700 dark:text-slate-300 border-2 border-slate-300 dark:border-slate-600 hover:bg-slate-200 dark:hover:bg-slate-600'
            ]"
          >
            <component :is="isFilterOpen ? X : Filter" class="w-4 h-4" />
            <span>{{ isFilterOpen ? languageStore.t('hide_filters', 'Hide Filters') : languageStore.t('filter', 'Filter') }}</span>
          </button>
        </div>
        
        <div class="flex items-center gap-2">
          <button
            type="button"
            @click="emit('refresh')"
            :disabled="loading"
            :title="languageStore.t('refresh', 'Refresh')"
            class="h-11 w-11 flex items-center justify-center bg-slate-100 dark:bg-slate-700 hover:bg-slate-200 dark:hover:bg-slate-600 text-slate-700 dark:text-slate-300 border-2 border-slate-300 dark:border-slate-600 rounded-lg transition-colors disabled:opacity-50"
          >
            <RefreshCw class="w-4 h-4" :class="{ 'animate-spin': loading }" />
          </button>
          
          <button
            type="button"
            @click="toggleFullscreen"
            :title="languageStore.t('toggle_fullscreen', 'Toggle Fullscreen')"
            class="h-11 w-11 flex items-center justify-center bg-slate-100 dark:bg-slate-700 hover:bg-slate-200 dark:hover:bg-slate-600 text-slate-700 dark:text-slate-300 border-2 border-slate-300 dark:border-slate-600 rounded-lg transition-colors"
          >
            <component :is="isFullscreen ? Minimize2 : Maximize2" class="w-4 h-4" />
          </button>
          
          <button
            type="button"
            @click="emit('create')"
            class="h-11 px-6 flex items-center gap-2 bg-blue-600 hover:bg-blue-700 text-white border-2 border-blue-600 hover:border-blue-700 rounded-lg font-bold text-sm transition-colors shadow-sm"
          >
            <Plus class="w-4 h-4" />
            <span>{{ languageStore.t('add_room_type', 'Add Room Type') }}</span>
          </button>
        </div>
      </div>
    </div>

    <!-- Mobile-Optimized Filter Panel -->
    <Transition
      enter-active-class="transition duration-200 ease-out"
      enter-from-class="transform -translate-y-2 opacity-0 scale-95"
      enter-to-class="transform translate-y-0 opacity-100 scale-100"
      leave-active-class="transition duration-150 ease-in"
      leave-from-class="transform translate-y-0 opacity-100 scale-100"
      leave-to-class="transform -translate-y-2 opacity-0 scale-95"
    >
      <div v-if="isFilterOpen" class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-700 rounded-xl shadow-sm p-4">
        <!-- Mobile: Stacked Layout -->
        <div class="block sm:hidden space-y-4">
          <div>
            <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">
              {{ languageStore.t('status', 'Status') }}
            </label>
            <select
              v-model="statusFilter"
              class="w-full h-12 px-4 border border-slate-300 dark:border-slate-600 rounded-lg bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-white text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
            >
              <option value="">{{ languageStore.t('all_statuses', 'All Statuses') }}</option>
              <option value="active">{{ languageStore.t('active', 'Active') }}</option>
              <option value="inactive">{{ languageStore.t('inactive', 'Inactive') }}</option>
            </select>
          </div>

          <div>
            <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">
              {{ languageStore.t('capacity', 'Capacity') }}
            </label>
            <select
              v-model="capacityFilter"
              class="w-full h-12 px-4 border border-slate-300 dark:border-slate-600 rounded-lg bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-white text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
            >
              <option value="">{{ languageStore.t('all_capacities', 'All Capacities') }}</option>
              <option value="1">1 {{ languageStore.t('guest', 'Guest') }}</option>
              <option value="2">2 {{ languageStore.t('guests', 'Guests') }}</option>
              <option value="3">3 {{ languageStore.t('guests', 'Guests') }}</option>
              <option value="4">4+ {{ languageStore.t('guests', 'Guests') }}</option>
            </select>
          </div>

          <div class="grid grid-cols-2 gap-3">
            <div>
              <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">
                {{ languageStore.t('min_price_night', 'Min Price') }}
              </label>
              <input
                v-model="priceMin"
                type="number"
                placeholder="e.g. 50"
                class="w-full h-12 px-4 border border-slate-300 dark:border-slate-600 rounded-lg bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-white text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
              />
            </div>
            <div>
              <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">
                {{ languageStore.t('max_price_night', 'Max Price') }}
              </label>
              <input
                v-model="priceMax"
                type="number"
                placeholder="e.g. 500"
                class="w-full h-12 px-4 border border-slate-300 dark:border-slate-600 rounded-lg bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-white text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
              />
            </div>
          </div>

          <div class="flex flex-col sm:flex-row items-stretch sm:items-center justify-between gap-3 pt-3 border-t border-slate-200 dark:border-slate-700">
            <p class="text-sm text-slate-600 dark:text-slate-400 text-center sm:text-left">
              {{ languageStore.t('found', 'Found') }} {{ total }} {{ languageStore.t('matching_room_types', 'matching room type(s)') }}
            </p>
            <button
              type="button"
              @click="resetFilters"
              class="h-12 px-6 flex items-center justify-center gap-2 bg-slate-100 dark:bg-slate-700 hover:bg-slate-200 dark:hover:bg-slate-600 text-slate-700 dark:text-slate-300 border-2 border-slate-300 dark:border-slate-600 rounded-lg font-medium text-sm transition-colors"
            >
              <RotateCcw class="w-4 h-4" />
              <span>{{ languageStore.t('reset_filters', 'Reset Filters') }}</span>
            </button>
          </div>
        </div>

        <!-- Desktop: Grid Layout -->
        <div class="hidden sm:block">
          <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-4">
            <div>
              <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">
                {{ languageStore.t('status', 'Status') }}
              </label>
              <select
                v-model="statusFilter"
                class="w-full h-11 px-4 border border-slate-300 dark:border-slate-600 rounded-lg bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-white text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
              >
                <option value="">{{ languageStore.t('all_statuses', 'All Statuses') }}</option>
                <option value="active">{{ languageStore.t('active', 'Active') }}</option>
                <option value="inactive">{{ languageStore.t('inactive', 'Inactive') }}</option>
              </select>
            </div>

            <div>
              <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">
                {{ languageStore.t('capacity', 'Capacity') }}
              </label>
              <select
                v-model="capacityFilter"
                class="w-full h-11 px-4 border border-slate-300 dark:border-slate-600 rounded-lg bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-white text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
              >
                <option value="">{{ languageStore.t('all_capacities', 'All Capacities') }}</option>
                <option value="1">1 {{ languageStore.t('guest', 'Guest') }}</option>
                <option value="2">2 {{ languageStore.t('guests', 'Guests') }}</option>
                <option value="3">3 {{ languageStore.t('guests', 'Guests') }}</option>
                <option value="4">4+ {{ languageStore.t('guests', 'Guests') }}</option>
              </select>
            </div>

            <div>
              <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">
                {{ languageStore.t('min_price_night', 'Min Price / Night') }}
              </label>
              <input
                v-model="priceMin"
                type="number"
                placeholder="e.g. 50"
                class="w-full h-11 px-4 border border-slate-300 dark:border-slate-600 rounded-lg bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-white text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
              />
            </div>

            <div>
              <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">
                {{ languageStore.t('max_price_night', 'Max Price / Night') }}
              </label>
              <input
                v-model="priceMax"
                type="number"
                placeholder="e.g. 500"
                class="w-full h-11 px-4 border border-slate-300 dark:border-slate-600 rounded-lg bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-white text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
              />
            </div>
          </div>

          <div class="flex items-center justify-between pt-4 border-t border-slate-200 dark:border-slate-700">
            <span class="text-sm text-slate-600 dark:text-slate-400">
              {{ languageStore.t('found', 'Found') }} {{ total }} {{ languageStore.t('matching_room_types', 'matching room type(s)') }}
            </span>
            <button
              type="button"
              @click="resetFilters"
              class="h-11 px-6 flex items-center gap-2 bg-slate-100 dark:bg-slate-700 hover:bg-slate-200 dark:hover:bg-slate-600 text-slate-700 dark:text-slate-300 border-2 border-slate-300 dark:border-slate-600 rounded-lg font-medium text-sm transition-colors"
            >
              <RotateCcw class="w-4 h-4" />
              <span>{{ languageStore.t('reset_filters', 'Reset Filters') }}</span>
            </button>
          </div>
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

      <!-- Mobile Cards - Completely Redesigned -->
      <div class="block md:hidden">
        <div v-if="loading" class="py-20 text-center">
          <div class="flex flex-col items-center justify-center gap-4">
            <Loader2 class="w-10 h-10 text-blue-600 dark:text-blue-400 animate-spin" />
            <div class="space-y-1">
              <p class="text-sm font-bold text-slate-800 dark:text-slate-200">
                {{ languageStore.t('loading_room_types', 'Loading Room Types...') }}
              </p>
              <p class="text-xs text-slate-500 dark:text-slate-400">
                {{ languageStore.t('fetching_room_types_sub', 'Fetching pricing tiers and room capacities') }}
              </p>
            </div>
          </div>
        </div>

        <div v-else-if="paginatedRoomTypes.length === 0" class="py-16 text-center">
          <div class="space-y-2">
            <p class="text-sm font-bold text-slate-600 dark:text-slate-400">
              {{ languageStore.t('no_room_types_found', 'No room types match your current search or filter criteria.') }}
            </p>
          </div>
        </div>

        <div v-else class="space-y-3 overflow-x-hidden">
          <div
            v-for="rt in paginatedRoomTypes"
            :key="rt.id"
            class="bg-white dark:bg-slate-800/50 border border-slate-200 dark:border-slate-700/50 rounded-xl p-4 space-y-4 shadow-sm hover:shadow-md hover:bg-slate-50 dark:hover:bg-slate-800/70 transition-all duration-200 border-l-4"
            :class="[
              rt.is_active 
                ? 'border-l-emerald-500 hover:border-l-emerald-600' 
                : 'border-l-red-500 hover:border-l-red-600'
            ]"
          >
            <!-- Card Header: Name + Status -->
            <div class="flex items-start justify-between gap-3">
              <div class="flex-1 min-w-0">
                <h3 class="text-lg font-bold text-slate-900 dark:text-white truncate mb-1">
                  {{ rt.name }}
                </h3>
                <p class="text-xs text-slate-500 dark:text-slate-400 font-mono">
                  ID: {{ (rt.id || '').toString().substring(0, 8) }}...
                </p>
              </div>
              <div class="flex-shrink-0">
                <span
                  v-if="rt.is_active"
                  class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full text-xs font-bold bg-emerald-50 dark:bg-emerald-900/30 text-emerald-700 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-700/50"
                >
                  <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
                  {{ languageStore.t('active', 'Active') }}
                </span>
                <span
                  v-else
                  class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full text-xs font-bold bg-red-50 dark:bg-red-900/30 text-red-700 dark:text-red-300 border border-red-200 dark:border-red-700/50"
                >
                  <span class="w-2 h-2 rounded-full bg-red-500"></span>
                  {{ languageStore.t('inactive', 'Inactive') }}
                </span>
              </div>
            </div>

            <!-- Card Details Grid -->
            <div class="grid grid-cols-2 gap-4 py-2">
              <div class="space-y-1">
                <div class="flex items-center gap-2">
                  <Users class="w-4 h-4 text-slate-400 dark:text-slate-500" />
                  <span class="text-xs font-medium text-slate-600 dark:text-slate-400 uppercase tracking-wide">
                    {{ languageStore.t('capacity', 'Capacity') }}
                  </span>
                </div>
                <p class="text-sm font-bold text-slate-900 dark:text-white">
                  {{ rt.capacity }} {{ languageStore.t('guests', 'Guests') }}
                </p>
              </div>
              <div class="space-y-1 text-right">
                <div class="flex items-center justify-end gap-2">
                  <span class="text-xs font-medium text-slate-600 dark:text-slate-400 uppercase tracking-wide">
                    {{ languageStore.t('price_night', 'Price/Night') }}
                  </span>
                </div>
                <p class="text-xl font-bold text-slate-900 dark:text-white">
                  ${{ parseFloat(String(rt.base_price_per_night || 0)).toFixed(2) }}
                </p>
              </div>
            </div>

            <!-- Description (if available) -->
            <div v-if="rt.description" class="pt-2 border-t border-slate-100 dark:border-slate-700">
              <p class="text-sm text-slate-600 dark:text-slate-400 leading-relaxed line-clamp-2">
                {{ rt.description }}
              </p>
            </div>

            <!-- Action Buttons -->
            <div class="grid grid-cols-3 gap-2 pt-3 border-t border-slate-100 dark:border-slate-700">
              <button
                @click="handleView(rt)"
                class="min-h-[48px] py-3 px-4 bg-blue-50 dark:bg-blue-900/30 hover:bg-blue-100 dark:hover:bg-blue-900/50 border border-blue-200 dark:border-blue-700/50 text-blue-700 dark:text-blue-300 rounded-lg text-xs font-bold flex items-center justify-center gap-2 transition-colors"
              >
                <Eye class="w-4 h-4" />
                <span>{{ languageStore.t('view', 'View') }}</span>
              </button>
              <button
                @click="handleEdit(rt)"
                class="min-h-[48px] py-3 px-4 bg-amber-50 dark:bg-amber-900/30 hover:bg-amber-100 dark:hover:bg-amber-900/50 border border-amber-200 dark:border-amber-700/50 text-amber-700 dark:text-amber-300 rounded-lg text-xs font-bold flex items-center justify-center gap-2 transition-colors"
              >
                <Edit class="w-4 h-4" />
                <span>{{ languageStore.t('edit', 'Edit') }}</span>
              </button>
              <button
                @click="handleDelete(rt)"
                class="min-h-[48px] py-3 px-4 bg-red-50 dark:bg-red-900/30 hover:bg-red-100 dark:hover:bg-red-900/50 border border-red-200 dark:border-red-700/50 text-red-700 dark:text-red-300 rounded-lg text-xs font-bold flex items-center justify-center gap-2 transition-colors"
              >
                <Trash2 class="w-4 h-4" />
                <span>{{ languageStore.t('delete', 'Delete') }}</span>
              </button>
            </div>
          </div>
        </div>
      </div>

      <!-- Mobile-First Responsive Pagination -->
      <div
        v-if="total > 0"
        class="border-t border-slate-200 dark:border-slate-700 bg-slate-50/80 dark:bg-slate-800/50"
      >
        <!-- Mobile Layout -->
        <div class="block sm:hidden px-4 py-4 space-y-4">
          <div class="text-center">
            <p class="text-sm text-slate-600 dark:text-slate-400">
              {{ languageStore.t('showing', 'Showing') }}
              <span class="font-bold text-slate-900 dark:text-white">{{ showingFrom }}-{{ showingTo }}</span>
              {{ languageStore.t('of', 'of') }}
              <span class="font-bold text-slate-900 dark:text-white">{{ total }}</span>
              {{ languageStore.t('room_types', 'room types') }}
            </p>
          </div>
          
          <div class="flex items-center justify-center gap-2">
            <button
              @click="prevPage"
              :disabled="currentPage === 1"
              class="h-12 w-12 flex items-center justify-center bg-white dark:bg-slate-700 border-2 border-slate-300 dark:border-slate-600 rounded-lg text-slate-700 dark:text-slate-300 disabled:opacity-50 disabled:cursor-not-allowed hover:bg-slate-50 dark:hover:bg-slate-600 transition-colors"
            >
              <ChevronLeft class="w-5 h-5" />
            </button>
            
            <div class="flex items-center px-4 py-3 bg-white dark:bg-slate-700 border-2 border-slate-300 dark:border-slate-600 rounded-lg">
              <span class="text-sm font-medium text-slate-700 dark:text-slate-300">
                {{ languageStore.t('page', 'Page') }} {{ currentPage }} {{ languageStore.t('of', 'of') }} {{ lastPage }}
              </span>
            </div>
            
            <button
              @click="nextPage"
              :disabled="currentPage === lastPage"
              class="h-12 w-12 flex items-center justify-center bg-white dark:bg-slate-700 border-2 border-slate-300 dark:border-slate-600 rounded-lg text-slate-700 dark:text-slate-300 disabled:opacity-50 disabled:cursor-not-allowed hover:bg-slate-50 dark:hover:bg-slate-600 transition-colors"
            >
              <ChevronRight class="w-5 h-5" />
            </button>
          </div>
        </div>

        <!-- Desktop Layout -->
        <div class="hidden sm:flex items-center justify-between px-6 py-4">
          <div class="flex items-center gap-6">
            <p class="text-sm text-slate-600 dark:text-slate-400">
              {{ languageStore.t('showing', 'Showing') }}
              <span class="font-bold text-slate-900 dark:text-white">{{ showingFrom }}</span>
              {{ languageStore.t('to', 'to') }}
              <span class="font-bold text-slate-900 dark:text-white">{{ showingTo }}</span>
              {{ languageStore.t('of', 'of') }}
              <span class="font-bold text-slate-900 dark:text-white">{{ total }}</span>
              {{ languageStore.t('room_types', 'room types') }}
            </p>
            
            <div class="flex items-center gap-2">
              <span class="text-sm text-slate-600 dark:text-slate-400">{{ languageStore.t('per_page', 'Per page') }}:</span>
              <select
                :value="perPage"
                @change="changePerPage"
                class="h-9 px-3 border border-slate-300 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 text-slate-900 dark:text-white text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
              >
                <option :value="10">10</option>
                <option :value="20">20</option>
                <option :value="50">50</option>
              </select>
            </div>
          </div>

          <div class="flex items-center gap-1">
            <button
              @click="prevPage"
              :disabled="currentPage === 1"
              class="h-10 w-10 flex items-center justify-center border-2 border-slate-300 dark:border-slate-600 rounded-lg text-slate-700 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-600 disabled:opacity-50 disabled:cursor-not-allowed transition-colors"
            >
              <ChevronLeft class="w-4 h-4" />
            </button>

            <button
              v-for="page in paginationPages"
              :key="page"
              @click="goToPage(page)"
              class="h-10 min-w-[40px] px-3 flex items-center justify-center rounded-lg text-sm font-medium transition-colors"
              :class="[
                currentPage === page
                  ? 'bg-blue-600 text-white border-2 border-blue-600'
                  : 'bg-white dark:bg-slate-700 text-slate-700 dark:text-slate-300 border-2 border-slate-300 dark:border-slate-600 hover:bg-slate-100 dark:hover:bg-slate-600'
              ]"
            >
              {{ page }}
            </button>

            <button
              @click="nextPage"
              :disabled="currentPage === lastPage"
              class="h-10 w-10 flex items-center justify-center border-2 border-slate-300 dark:border-slate-600 rounded-lg text-slate-700 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-600 disabled:opacity-50 disabled:cursor-not-allowed transition-colors"
            >
              <ChevronRight class="w-4 h-4" />
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<style scoped>
.line-clamp-2 {
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
}

/* Ensure mobile responsiveness at very small screens */
@media (max-width: 320px) {
  .grid-cols-4 {
    grid-template-columns: repeat(2, minmax(0, 1fr));
  }
  
  .grid-cols-3 {
    grid-template-columns: repeat(1, minmax(0, 1fr));
  }
}

/* Improve touch targets on mobile */
@media (max-width: 640px) {
  button {
    min-height: 48px;
  }
  
  input, select {
    min-height: 48px;
  }
}
</style>

<script setup lang="ts">
import { ref, computed, onMounted, onUnmounted } from 'vue'
import { useRestaurantTableStore } from '@/stores/restaurantTableStore'
import { useRestaurantSectionStore } from '@/stores/restaurantSectionStore'
import { useLanguageStore } from '@/stores/language'
import { storeToRefs } from 'pinia'
import DashboardLayout from '@/Layouts/DashboardLayout.vue'
import RestaurantTableFormModal from '@/components/manager/RestaurantTableFormModal.vue'
import RestaurantSectionModal from '@/components/manager/RestaurantSectionModal.vue'
import type { RestaurantTable } from '@/types/restaurantTable'
import {
  UtensilsCrossed,
  CheckCircle2,
  Users,
  RefreshCw,
  AlertCircle,
  Search,
  Filter,
  X,
  Maximize2,
  Minimize2,
  RotateCcw,
  Plus,
  QrCode,
  Edit,
  Trash2,
  ChevronLeft,
  ChevronRight,
  Download,
  Layers,
} from 'lucide-vue-next'

const tableStore = useRestaurantTableStore()
const sectionStore = useRestaurantSectionStore()
const languageStore = useLanguageStore()
const { tables, statistics, pagination, loading } = storeToRefs(tableStore)

const isFilterOpen = ref(false)
const isFullscreen = ref(false)

const searchQuery = ref('')
const statusFilter = ref('')
const activeFilter = ref<boolean | null>(null)
const sectionFilter = ref('')
const localPerPage = ref(10)

const showFormModal = ref(false)
const showSectionModal = ref(false)
const showQRModal = ref(false)
const showDeleteModal = ref(false)
const selectedTable = ref<RestaurantTable | null>(null)
let searchTimeout: ReturnType<typeof setTimeout> | null = null

onUnmounted(() => {
  if (searchTimeout) clearTimeout(searchTimeout)
})

const getStatusBadgeClass = (status: string) => {
  const classes: Record<string, string> = {
    available: 'bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 border-emerald-500/20',
    occupied: 'bg-rose-500/10 text-rose-600 dark:text-rose-400 border-rose-500/20',
    reserved: 'bg-amber-500/10 text-amber-600 dark:text-amber-400 border-amber-500/20',
    cleaning: 'bg-sky-500/10 text-sky-600 dark:text-sky-400 border-sky-500/20',
    out_of_service: 'bg-slate-500/10 text-slate-600 dark:text-slate-400 border-slate-500/20',
  }
  return classes[status] || 'bg-slate-500/10 text-slate-600 dark:text-slate-400 border-slate-500/20'
}

const handleSearchChange = () => {
  if (searchTimeout) clearTimeout(searchTimeout)
  searchTimeout = setTimeout(() => {
    tableStore.setFilters({ search: searchQuery.value, page: 1 })
    tableStore.fetchTables()
  }, 400)
}

const handleFilterChange = () => {
  tableStore.setFilters({
    status: statusFilter.value as any,
    is_active: activeFilter.value,
    section_id: sectionFilter.value || undefined,
    page: 1,
  })
  tableStore.fetchTables()
}

const resetFilters = () => {
  searchQuery.value = ''
  statusFilter.value = ''
  activeFilter.value = null
  sectionFilter.value = ''
  tableStore.setFilters({ search: '', status: undefined, is_active: undefined, section_id: undefined, page: 1 })
  tableStore.fetchTables()
}

const handleSectionUpdated = async () => {
  await Promise.all([
    sectionStore.fetchSections(),
    tableStore.fetchTables(),
    tableStore.fetchStatistics(),
  ])
}

const refreshData = async () => {
  await Promise.all([
    sectionStore.fetchSections(),
    tableStore.fetchTables(),
    tableStore.fetchStatistics(),
  ])
}

const toggleFilter = () => {
  isFilterOpen.value = !isFilterOpen.value
}

const toggleFullscreen = () => {
  isFullscreen.value = !isFullscreen.value
}

const changePerPage = (event: Event) => {
  const target = event.target as HTMLSelectElement
  localPerPage.value = Number(target.value)
  tableStore.setFilters({ per_page: localPerPage.value, page: 1 })
  tableStore.fetchTables()
}

const openCreateModal = () => {
  selectedTable.value = null
  showFormModal.value = true
}

const editTable = (table: RestaurantTable) => {
  selectedTable.value = table
  showFormModal.value = true
}

const closeFormModal = () => {
  showFormModal.value = false
  selectedTable.value = null
}

const handleFormSuccess = () => {
  closeFormModal()
  tableStore.fetchTables()
  tableStore.fetchStatistics()
}

const viewQRCode = (table: RestaurantTable) => {
  selectedTable.value = table
  showQRModal.value = true
}

const closeQRModal = () => {
  showQRModal.value = false
  selectedTable.value = null
}

const downloadQR = async (table: RestaurantTable) => {
  await tableStore.downloadQRCode(table)
}

const regenerateQRCode = async (tableId: string) => {
  if (confirm('Are you sure you want to regenerate the QR code? The old QR code will no longer work.')) {
    try {
      await tableStore.regenerateQR(tableId)
      const updatedTable = await tableStore.fetchTableById(tableId)
      selectedTable.value = (updatedTable as any)?.data ?? updatedTable
      alert('QR code regenerated successfully!')
    } catch (err: any) {
      console.error('[RestaurantTables] Error regenerating QR code:', err)
      alert('Failed to regenerate QR code')
    }
  }
}

const confirmDelete = (table: RestaurantTable) => {
  selectedTable.value = table
  showDeleteModal.value = true
}

const closeDeleteModal = () => {
  showDeleteModal.value = false
  selectedTable.value = null
}

const handleDelete = async () => {
  if (!selectedTable.value) return
  try {
    await tableStore.deleteTable(selectedTable.value.id)
    closeDeleteModal()
    tableStore.fetchTables()
    tableStore.fetchStatistics()
  } catch (err: any) {
    console.error('[RestaurantTables] Error deleting table:', err)
    alert('Failed to delete table')
  }
}

const goToPage = (page: number) => {
  tableStore.setFilters({ page })
  tableStore.fetchTables()
}

const paginationPages = computed(() => {
  if (!pagination.value || !pagination.value.current_page || !pagination.value.last_page) return []
  const current = pagination.value.current_page
  const last = pagination.value.last_page
  const pages: number[] = []

  pages.push(1)
  for (let i = Math.max(2, current - 2); i <= Math.min(last - 1, current + 2); i++) {
    pages.push(i)
  }
  if (last > 1 && !pages.includes(last)) pages.push(last)

  return [...new Set(pages)].sort((a, b) => a - b)
})

onMounted(() => {
  tableStore.fetchTables()
  tableStore.fetchStatistics()
  sectionStore.fetchSections()
})
</script>

<template>
  <DashboardLayout>
    <div
      class="space-y-6 bg-slate-50 dark:bg-slate-950 min-h-screen p-4 sm:p-6 max-w-full overflow-hidden font-sans"
      :class="{ 'fixed inset-0 z-50 p-6 overflow-y-auto bg-white dark:bg-slate-950': isFullscreen }"
    >
      <!-- Page Header -->
      <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 p-5 sm:p-6 rounded-2xl shadow-xs">
        <div class="flex items-center gap-3">
          <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-amber-500 to-amber-600 flex items-center justify-center shadow-md flex-shrink-0 text-slate-950">
            <UtensilsCrossed class="w-5 h-5 stroke-[2.2]" />
          </div>
          <div>
            <h1 class="text-xl sm:text-2xl font-black text-slate-900 dark:text-white">
              {{ languageStore.t('restaurant_tables', 'Restaurant Tables') }}
            </h1>
            <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">
              {{ languageStore.t('manage_dining_tables_desc', 'Manage dining tables, seating capacity, QR codes, and occupancy status.') }}
            </p>
          </div>
        </div>
      </div>

      <!-- Statistics Cards Grid -->
      <div v-if="statistics" class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-3 sm:gap-4">
        <!-- Total -->
        <div class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-800 p-4 shadow-xs flex items-center justify-between">
          <div>
            <p class="text-[10px] font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider">
              {{ languageStore.t('total', 'Total') }}
            </p>
            <h2 class="text-2xl font-black text-slate-900 dark:text-white mt-1">{{ statistics.total }}</h2>
          </div>
          <div class="p-2.5 rounded-xl bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300">
            <UtensilsCrossed class="w-5 h-5" />
          </div>
        </div>

        <!-- Available -->
        <div class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-800 p-4 shadow-xs flex items-center justify-between">
          <div>
            <p class="text-[10px] font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider">
              {{ languageStore.t('Available', 'Available') }}
            </p>
            <h2 class="text-2xl font-black text-emerald-600 dark:text-emerald-400 mt-1">{{ statistics.available }}</h2>
          </div>
          <div class="p-2.5 rounded-xl bg-emerald-500/10 text-emerald-600 dark:text-emerald-400">
            <Sparkles class="w-5 h-5" />
          </div>
        </div>

        <!-- Occupied -->
        <div class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-800 p-4 shadow-xs flex items-center justify-between">
          <div>
            <p class="text-[10px] font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider">
              {{ languageStore.t('Occupied', 'Occupied') }}
            </p>
            <h2 class="text-2xl font-black text-rose-600 dark:text-rose-400 mt-1">{{ statistics.occupied }}</h2>
          </div>
          <div class="p-2.5 rounded-xl bg-rose-500/10 text-rose-600 dark:text-rose-400">
            <Users class="w-5 h-5" />
          </div>
        </div>

        <!-- Reserved -->
        <div class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-800 p-4 shadow-xs flex items-center justify-between">
          <div>
            <p class="text-[10px] font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider">
              {{ languageStore.t('Reserved', 'Reserved') }}
            </p>
            <h2 class="text-2xl font-black text-amber-600 dark:text-amber-400 mt-1">{{ statistics.reserved || 0 }}</h2>
          </div>
          <div class="p-2.5 rounded-xl bg-amber-500/10 text-amber-600 dark:text-amber-400">
            <CheckCircle2 class="w-5 h-5" />
          </div>
        </div>

        <!-- Cleaning -->
        <div class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-800 p-4 shadow-xs flex items-center justify-between">
          <div>
            <p class="text-[10px] font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider">
              {{ languageStore.t('Cleaning', 'Cleaning') }}
            </p>
            <h2 class="text-2xl font-black text-sky-600 dark:text-sky-400 mt-1">{{ statistics.cleaning || 0 }}</h2>
          </div>
          <div class="p-2.5 rounded-xl bg-sky-500/10 text-sky-600 dark:text-sky-400">
            <RefreshCw class="w-5 h-5" />
          </div>
        </div>

        <!-- Out of Service -->
        <div class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-800 p-4 shadow-xs flex items-center justify-between">
          <div>
            <p class="text-[10px] font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider">
              {{ languageStore.t('Maintenance', 'Service Off') }}
            </p>
            <h2 class="text-2xl font-black text-slate-500 dark:text-slate-400 mt-1">{{ statistics.out_of_service || 0 }}</h2>
          </div>
          <div class="p-2.5 rounded-xl bg-slate-100 dark:bg-slate-800 text-slate-500">
            <AlertCircle class="w-5 h-5" />
          </div>
        </div>
      </div>

      <!-- Top Bar Toolbar -->
      <div
        class="flex flex-wrap items-center justify-between gap-3 rounded-2xl border border-slate-200 dark:border-slate-800/80 bg-white dark:bg-[#0b1527] p-3 sm:p-4 shadow-xs transition-all"
      >
        <!-- Left: Search & Filter Toggle -->
        <div class="flex flex-1 items-center gap-2.5 min-w-[280px] max-w-2xl">
          <!-- Search Input -->
          <div class="relative flex-1">
            <Search class="w-4 h-4 absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400 dark:text-slate-500" />
            <input
              v-model="searchQuery"
              @input="handleSearchChange"
              type="text"
              :placeholder="languageStore.t('search_tables_placeholder', 'Search by table number, name, or location...')"
              class="w-full rounded-xl border border-slate-200 dark:border-[#1e3455] bg-slate-50/80 dark:bg-[#13233c] text-slate-900 dark:text-white placeholder-slate-400 dark:placeholder-slate-500 pl-10 pr-4 py-2.5 text-xs sm:text-sm focus:ring-2 focus:ring-blue-500/40 focus:border-blue-500 dark:focus:border-blue-400 transition outline-none"
            />
          </div>

          <!-- Filter Toggle Button -->
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

        <!-- Right: Action Buttons -->
        <div class="flex items-center gap-2 sm:gap-2.5">
          <!-- Refresh Button -->
          <button
            type="button"
            @click="refreshData"
            :disabled="loading"
            :title="languageStore.t('refresh', 'Refresh')"
            class="inline-flex items-center justify-center h-9 w-9 sm:h-10 sm:w-10 rounded-xl border border-slate-200 dark:border-[#1e3455] bg-white dark:bg-[#13233c]/80 text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-[#1c3356] hover:text-slate-900 dark:hover:text-white transition disabled:opacity-50 cursor-pointer"
          >
            <RefreshCw class="w-4 h-4" :class="{ 'animate-spin': loading }" />
          </button>

          <!-- Fullscreen Toggle -->
          <button
            type="button"
            @click="toggleFullscreen"
            :title="languageStore.t('fullscreen', 'Toggle Fullscreen')"
            class="inline-flex items-center justify-center h-9 w-9 sm:h-10 sm:w-10 rounded-xl border border-slate-200 dark:border-[#1e3455] bg-white dark:bg-[#13233c]/80 text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-[#1c3356] hover:text-slate-900 dark:hover:text-white transition cursor-pointer"
          >
            <component :is="isFullscreen ? Minimize2 : Maximize2" class="w-4 h-4" />
          </button>

          <!-- Manage Sections Button -->
          <button
            type="button"
            @click="showSectionModal = true"
            class="inline-flex items-center gap-1.5 rounded-xl border border-slate-200 dark:border-[#1e3455] bg-white dark:bg-[#13233c]/80 text-slate-700 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-[#1c3356] hover:text-slate-900 dark:hover:text-white px-3.5 py-2.5 text-xs sm:text-sm font-semibold transition cursor-pointer flex-shrink-0"
          >
            <Layers class="w-4 h-4 text-blue-600 dark:text-blue-400" />
            <span>Manage Sections</span>
          </button>

          <!-- Create Table Primary Button -->
          <button
            type="button"
            @click="openCreateModal"
            class="inline-flex items-center gap-1.5 rounded-xl bg-blue-600 hover:bg-blue-700 text-white px-3.5 sm:px-4 py-2.5 text-xs sm:text-sm font-bold shadow-md shadow-blue-600/25 transition active:scale-98 cursor-pointer flex-shrink-0"
          >
            <Plus class="w-4 h-4 text-white" />
            <span class="text-white">{{ languageStore.t('add_table', 'Add Table') }}</span>
          </button>
        </div>
      </div>

      <!-- Expandable Filter Panel -->
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
          <div class="grid grid-cols-1 sm:grid-cols-4 gap-3.5 sm:gap-4">
            <!-- Section Filter -->
            <div>
              <label class="mb-1.5 block text-xs font-semibold text-slate-600 dark:text-slate-300">
                Restaurant Section
              </label>
              <select
                v-model="sectionFilter"
                @change="handleFilterChange"
                class="w-full rounded-xl border border-slate-200 dark:border-[#1e3455] bg-slate-50/80 dark:bg-[#13233c] text-slate-900 dark:text-white px-3.5 py-2 text-xs sm:text-sm focus:ring-2 focus:ring-blue-500/40 focus:border-blue-500 dark:focus:border-blue-400 transition cursor-pointer font-medium outline-none"
              >
                <option value="">All Sections</option>
                <option
                  v-for="sec in sectionStore.sections"
                  :key="sec.id"
                  :value="sec.id"
                >
                  {{ sec.name }}
                </option>
              </select>
            </div>

            <!-- Status Filter -->
            <div>
              <label class="mb-1.5 block text-xs font-semibold text-slate-600 dark:text-slate-300">
                {{ languageStore.t('Status', 'Table Status') }}
              </label>
              <select
                v-model="statusFilter"
                @change="handleFilterChange"
                class="w-full rounded-xl border border-slate-200 dark:border-[#1e3455] bg-slate-50/80 dark:bg-[#13233c] text-slate-900 dark:text-white px-3.5 py-2 text-xs sm:text-sm focus:ring-2 focus:ring-blue-500/40 focus:border-blue-500 dark:focus:border-blue-400 transition cursor-pointer font-medium outline-none"
              >
                <option value="">{{ languageStore.t('all_statuses', 'All Statuses') }}</option>
                <option value="available">{{ languageStore.t('Available', 'Available') }}</option>
                <option value="occupied">{{ languageStore.t('Occupied', 'Occupied') }}</option>
                <option value="reserved">{{ languageStore.t('Reserved', 'Reserved') }}</option>
                <option value="cleaning">{{ languageStore.t('Cleaning', 'Cleaning') }}</option>
                <option value="out_of_service">{{ languageStore.t('Maintenance', 'Out of Service') }}</option>
              </select>
            </div>

            <!-- Active Filter -->
            <div>
              <label class="mb-1.5 block text-xs font-semibold text-slate-600 dark:text-slate-300">
                {{ languageStore.t('activation', 'Activation State') }}
              </label>
              <select
                v-model="activeFilter"
                @change="handleFilterChange"
                class="w-full rounded-xl border border-slate-200 dark:border-[#1e3455] bg-slate-50/80 dark:bg-[#13233c] text-slate-900 dark:text-white px-3.5 py-2 text-xs sm:text-sm focus:ring-2 focus:ring-blue-500/40 focus:border-blue-500 dark:focus:border-blue-400 transition cursor-pointer font-medium outline-none"
              >
                <option :value="null">{{ languageStore.t('All', 'All Tables') }}</option>
                <option :value="true">{{ languageStore.t('Active', 'Active Only') }}</option>
                <option :value="false">{{ languageStore.t('Inactive', 'Inactive') }}</option>
              </select>
            </div>

            <!-- Reset Filters -->
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

      <!-- Tables Table Container -->
      <div class="rounded-2xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 shadow-xs overflow-hidden font-sans w-full">
        <!-- Desktop Table View -->
        <div class="hidden md:block overflow-x-auto w-full">
          <table class="w-full text-left border-collapse">
            <thead class="bg-slate-50/90 dark:bg-[#0c182c] border-b border-slate-200 dark:border-[#1e3455]">
              <tr class="text-[11px] font-bold text-slate-500 dark:text-slate-400 select-none">
                <th class="py-3 px-4 pl-5 whitespace-nowrap">{{ languageStore.t('table_number', 'Table #') }}</th>
                <th class="py-3 px-4 whitespace-nowrap">{{ languageStore.t('table', 'Table Name') }}</th>
                <th class="py-3 px-4 text-center whitespace-nowrap">{{ languageStore.t('Capacity', 'Capacity') }}</th>
                <th class="py-3 px-4 whitespace-nowrap">Section</th>
                <th class="py-3 px-4 text-center whitespace-nowrap">{{ languageStore.t('Status', 'Status') }}</th>
                <th class="py-3 px-4 text-center whitespace-nowrap">{{ languageStore.t('Active', 'Active') }}</th>
                <th class="py-3 px-4 text-center whitespace-nowrap">QR</th>
                <th class="py-3 px-4 text-right pr-5 whitespace-nowrap">{{ languageStore.t('Actions', 'Actions') }}</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-100 dark:divide-[#1e3455]/60 text-xs">
              <!-- Loading Spinner State -->
              <tr v-if="loading">
                <td colspan="8" class="px-6 py-20 text-center">
                  <div class="flex flex-col items-center justify-center gap-3">
                    <Loader2 class="w-8 h-8 text-blue-600 dark:text-blue-400 animate-spin" />
                    <span class="text-xs font-bold text-slate-600 dark:text-slate-400">{{ languageStore.t('Loading...', 'Loading restaurant tables...') }}</span>
                  </div>
                </td>
              </tr>

              <!-- Data Rows -->
              <tr
                v-for="table in (loading ? [] : tables)"
                :key="table.id"
                class="hover:bg-slate-50/80 dark:hover:bg-[#13233c]/60 transition-colors duration-150 group"
              >
                <!-- Table Number -->
                <td class="py-3 px-4 pl-5 whitespace-nowrap font-mono font-black text-slate-900 dark:text-white text-xs sm:text-sm">
                  {{ table.table_number }}
                </td>

                <!-- Table Name -->
                <td class="py-3 px-4 whitespace-nowrap font-medium text-slate-900 dark:text-white">
                  {{ table.table_name || `Table ${table.table_number}` }}
                </td>

                <!-- Capacity -->
                <td class="py-3 px-4 text-center whitespace-nowrap">
                  <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-lg bg-slate-100 dark:bg-slate-800 font-bold text-xs text-slate-700 dark:text-slate-300">
                    <Users class="w-3 h-3 text-slate-400" />
                    {{ table.capacity }} {{ languageStore.t('capacity', 'Seats') }}
                  </span>
                </td>

                <!-- Section -->
                <td class="py-3 px-4 whitespace-nowrap text-slate-700 dark:text-slate-300 font-medium">
                  <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-lg bg-blue-50 dark:bg-blue-950/40 text-blue-700 dark:text-blue-300 font-semibold text-xs border border-blue-100 dark:border-blue-900/40">
                    <Layers class="w-3 h-3 text-blue-500" />
                    {{ table.section_name || table.section || table.location || 'Main Dining' }}
                  </span>
                </td>

                <!-- Status -->
                <td class="py-3 px-4 text-center whitespace-nowrap">
                  <span
                    class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[10px] font-extrabold border uppercase tracking-wider"
                    :class="getStatusBadgeClass(table.status)"
                  >
                    {{ languageStore.t(table.status || 'available', (table.status || 'available').replace('_', ' ')) }}
                  </span>
                </td>

                <!-- Active -->
                <td class="py-3 px-4 text-center whitespace-nowrap">
                  <span
                    class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold"
                    :class="table.is_active ? 'bg-emerald-500/10 text-emerald-600' : 'bg-slate-100 text-slate-500'"
                  >
                    {{ table.is_active ? languageStore.t('Active', 'Active') : languageStore.t('Inactive', 'Inactive') }}
                  </span>
                </td>

                <!-- QR Code -->
                <td class="py-3 px-4 text-center whitespace-nowrap">
                  <button
                    @click="viewQRCode(table)"
                    class="p-1.5 text-slate-600 dark:text-slate-300 hover:text-blue-600 dark:hover:text-blue-400 hover:bg-blue-50 dark:hover:bg-blue-950/40 rounded-lg transition cursor-pointer"
                    :title="languageStore.t('view_qr_code', 'View QR Code')"
                  >
                    <QrCode class="w-4 h-4" />
                  </button>
                </td>

                <!-- Actions -->
                <td class="py-3 px-4 text-right pr-5 whitespace-nowrap">
                  <div class="flex items-center justify-end gap-1">
                    <button
                      @click="editTable(table)"
                      class="p-1.5 text-slate-500 hover:text-blue-600 hover:bg-slate-100 dark:hover:bg-slate-800 rounded-lg transition cursor-pointer"
                      :title="languageStore.t('Edit', 'Edit Table')"
                    >
                      <Edit class="w-3.5 h-3.5" />
                    </button>
                    <button
                      @click="confirmDelete(table)"
                      class="p-1.5 text-slate-500 hover:text-rose-600 hover:bg-rose-50 dark:hover:bg-rose-950/40 rounded-lg transition cursor-pointer"
                      :title="languageStore.t('Delete', 'Delete Table')"
                    >
                      <Trash2 class="w-3.5 h-3.5" />
                    </button>
                  </div>
                </td>
              </tr>

              <!-- Empty State -->
              <tr v-if="!loading && tables.length === 0">
                <td colspan="8" class="px-6 py-12 text-center text-slate-500 dark:text-slate-400 text-xs font-bold">
                  {{ languageStore.t('no_items_found', 'No restaurant tables found.') }}
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <!-- Mobile Card View -->
        <div class="md:hidden divide-y divide-slate-100 dark:divide-slate-800">
          <div v-if="loading" class="py-16 text-center flex flex-col items-center justify-center gap-3">
            <Loader2 class="w-8 h-8 text-blue-600 dark:text-blue-400 animate-spin" />
            <span class="text-xs font-bold text-slate-600 dark:text-slate-400">{{ languageStore.t('Loading...', 'Loading restaurant tables...') }}</span>
          </div>
          <div v-else-if="tables.length === 0" class="py-12 text-center text-slate-500 text-xs font-bold">
            {{ languageStore.t('no_items_found', 'No restaurant tables found.') }}
          </div>
          <div v-else>
            <div
              v-for="table in tables"
              :key="table.id"
              class="p-4 space-y-3 hover:bg-slate-50/60 dark:hover:bg-slate-800/40 transition border-b border-slate-100 dark:border-slate-800/60 last:border-b-0"
            >
              <div class="flex items-center justify-between">
                <span class="font-bold text-slate-900 dark:text-white text-sm">
                  {{ languageStore.t('table', 'Table') }} {{ table.table_number }}
                </span>
                <span
                  class="px-2 py-0.5 rounded-full text-[10px] font-bold border uppercase"
                  :class="getStatusBadgeClass(table.status)"
                >
                  {{ languageStore.t(table.status, table.status) }}
                </span>
              </div>
              <div class="flex items-center justify-between text-xs text-slate-600 dark:text-slate-400">
                <div class="flex items-center gap-1.5">
                  <span>{{ table.capacity }} {{ languageStore.t('capacity', 'Seats') }}</span>
                  <span class="text-slate-300">•</span>
                  <span class="font-semibold text-blue-600 dark:text-blue-400">{{ table.section_name || table.section || table.location || 'Main Dining' }}</span>
                </div>
                <div class="flex gap-2">
                  <button @click="viewQRCode(table)" class="text-blue-600 font-bold cursor-pointer">QR</button>
                  <button @click="editTable(table)" class="text-amber-600 font-bold cursor-pointer">{{ languageStore.t('Edit', 'Edit') }}</button>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Pagination Footer -->
        <div
          v-if="pagination && pagination.total > 0"
          class="border-t border-slate-200 dark:border-slate-800/80 px-4 sm:px-6 py-3 sm:py-4 bg-slate-50/50 dark:bg-[#0c182c] flex flex-col sm:flex-row items-center justify-between gap-3 text-xs"
        >
          <div class="text-slate-500 dark:text-slate-400 font-medium">
            {{ languageStore.t('Showing', 'Showing') }} <span class="font-bold text-slate-900 dark:text-white">{{ pagination.from || 1 }}</span> {{ languageStore.t('to', 'to') }}
            <span class="font-bold text-slate-900 dark:text-white">{{ pagination.to || tables.length }}</span> {{ languageStore.t('of', 'of') }}
            <span class="font-bold text-slate-900 dark:text-white">{{ pagination.total }}</span> {{ languageStore.t('restaurant_tables', 'tables') }}
          </div>

          <div class="flex items-center gap-2 sm:gap-3">
            <div class="flex items-center gap-1.5">
              <span class="text-slate-500 dark:text-slate-400 font-medium">{{ languageStore.t('per_page', 'Per page:') }}</span>
              <select
                :value="localPerPage"
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
                @click="goToPage((pagination.current_page || 1) - 1)"
                :disabled="(pagination.current_page || 1) <= 1"
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
                  pagination.current_page === page
                    ? 'bg-blue-600 text-white'
                    : 'text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800'
                ]"
              >
                {{ page }}
              </button>

              <button
                @click="goToPage((pagination.current_page || 1) + 1)"
                :disabled="(pagination.current_page || 1) >= (pagination.last_page || 1)"
                class="p-1 rounded-lg border border-slate-200 dark:border-slate-700 text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800 disabled:opacity-40 transition cursor-pointer"
              >
                <ChevronRight class="w-4 h-4" />
              </button>
            </div>
          </div>
        </div>
      </div>

      <!-- Create/Edit Table Modal -->
      <RestaurantTableFormModal
        v-if="showFormModal"
        :table="selectedTable"
        @close="closeFormModal"
        @success="handleFormSuccess"
      />

      <!-- Section Management Modal -->
      <RestaurantSectionModal
        v-if="showSectionModal"
        @close="showSectionModal = false"
        @updated="handleSectionUpdated"
      />

      <!-- QR Code Modal -->
      <Teleport to="body">
        <Transition name="fade">
          <div
            v-if="showQRModal && selectedTable"
            class="fixed inset-0 bg-black/60 backdrop-blur-xs z-50 flex items-center justify-center p-4"
            @click.self="closeQRModal"
          >
            <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-3xl shadow-2xl max-w-md w-full p-6 space-y-4">
              <div class="flex items-center justify-between">
                <h3 class="text-base font-black text-slate-900 dark:text-white">QR Code - {{ languageStore.t('table', 'Table') }} {{ selectedTable.table_number }}</h3>
                <button
                  @click="closeQRModal"
                  class="p-1.5 rounded-xl text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 hover:bg-slate-100 dark:hover:bg-slate-800 transition cursor-pointer"
                >
                  <X class="w-5 h-5" />
                </button>
              </div>

              <div v-if="selectedTable.qr_code_url" class="space-y-4">
                <img
                  :src="selectedTable.qr_code_url"
                  :alt="`QR Code for ${selectedTable.table_number}`"
                  class="w-full rounded-2xl border border-slate-200 dark:border-slate-800 bg-white p-2"
                />
                <div class="text-center text-xs text-slate-500 dark:text-slate-400">
                  <p>Token: <code class="bg-slate-100 dark:bg-slate-800 text-slate-900 dark:text-white px-2 py-0.5 rounded-lg font-mono font-bold">{{ selectedTable.qr_token }}</code></p>
                  <p v-if="selectedTable.table_name" class="mt-1 font-bold text-slate-900 dark:text-white">{{ selectedTable.table_name }}</p>
                  <p class="mt-0.5 text-[11px] text-blue-600 dark:text-blue-400 font-semibold">Section: {{ selectedTable.section_name || selectedTable.section || 'Main Dining' }}</p>
                </div>
                <div class="flex gap-3">
                  <button
                    @click="downloadQR(selectedTable)"
                    class="flex-1 px-4 py-2 bg-blue-600 text-white rounded-xl font-bold text-xs hover:bg-blue-700 transition cursor-pointer flex items-center justify-center gap-2"
                  >
                    <Download class="w-4 h-4" />
                    <span>{{ languageStore.t('download', 'Download') }}</span>
                  </button>
                  <button
                    @click="regenerateQRCode(selectedTable.id)"
                    class="flex-1 px-4 py-2 bg-amber-500 text-slate-950 rounded-xl font-bold text-xs hover:bg-amber-600 transition cursor-pointer flex items-center justify-center gap-2"
                  >
                    <RefreshCw class="w-4 h-4" />
                    <span>{{ languageStore.t('regenerate', 'Regenerate') }}</span>
                  </button>
                </div>
              </div>
              <div v-else class="text-center text-slate-500 text-xs py-8 font-bold">
                {{ languageStore.t('no_qr_available', 'No QR code available') }}
              </div>
            </div>
          </div>
        </Transition>
      </Teleport>

      <!-- Delete Confirmation Modal -->
      <Teleport to="body">
        <Transition name="fade">
          <div
            v-if="showDeleteModal"
            class="fixed inset-0 bg-black/60 backdrop-blur-xs z-50 flex items-center justify-center p-4"
            @click.self="closeDeleteModal"
          >
            <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-3xl shadow-2xl max-w-md w-full p-6 space-y-4">
              <div class="text-center space-y-3">
                <div class="w-12 h-12 bg-rose-500/10 border border-rose-500/20 text-rose-500 rounded-2xl flex items-center justify-center mx-auto">
                  <AlertCircle class="w-6 h-6" />
                </div>
                <h3 class="text-base font-black text-slate-900 dark:text-white">Delete Table {{ selectedTable?.table_number }}?</h3>
                <p class="text-xs text-slate-500 dark:text-slate-400 font-medium">
                  Are you sure you want to delete this table? Its QR code will become inactive and will no longer resolve orders.
                </p>
              </div>
              <div class="flex gap-3 pt-2">
                <button
                  @click="closeDeleteModal"
                  class="flex-1 px-4 py-2 border border-slate-200 dark:border-slate-800 rounded-xl font-bold text-xs text-slate-700 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800 transition cursor-pointer"
                >
                  {{ languageStore.t('Cancel', 'Cancel') }}
                </button>
                <button
                  @click="handleDelete"
                  class="flex-1 px-4 py-2 bg-rose-600 text-white rounded-xl font-bold text-xs hover:bg-rose-700 transition cursor-pointer"
                >
                  {{ languageStore.t('Delete', 'Delete') }}
                </button>
              </div>
            </div>
          </div>
        </Transition>
      </Teleport>
    </div>
  </DashboardLayout>
</template>

<style scoped>
.fade-enter-active,
.fade-leave-active {
  transition: opacity 0.2s ease;
}

.fade-enter-from,
.fade-leave-to {
  opacity: 0;
}
</style>

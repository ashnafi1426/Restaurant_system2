<script setup lang="ts">
import { ref, onMounted, onUnmounted, computed, watch } from 'vue'
import { useTableAssignmentStore } from '@/stores/manager/tableAssignmentStore'
import { useHotelStore } from '@/stores/hotelStore'
import { useLanguageStore } from '@/stores/language'
import AssignWaiterToTableModal from '@/components/manager/AssignWaiterToTableModal.vue'
import EditTableAssignmentModal from '@/components/manager/EditTableAssignmentModal.vue'
import DashboardLayout from '@/Layouts/DashboardLayout.vue'
import {
  Plus,
  RefreshCw,
  Filter,
  X,
  Maximize2,
  Minimize2,
  RotateCcw,
  Users,
  MapPin,
  Clock,
  Trash2,
  Edit3,
  CheckCircle2,
  AlertCircle,
  Search,
  ChevronLeft,
  ChevronRight,
  MoreVertical,
} from 'lucide-vue-next'

const tableAssignmentStore = useTableAssignmentStore()
const hotelStore = useHotelStore()
const languageStore = useLanguageStore()

const showAssignModal = ref(false)
const showEditModal = ref(false)
const selectedEditAssignment = ref<any>(null)

const isFilterOpen = ref(false)
const isFullscreen = ref(false)

const filterDate = ref(new Date().toISOString().split('T')[0])
const filterStatus = ref('')
const searchQuery = ref('')

const selectedAssignment = ref<any>(null)
const showDeleteConfirm = ref(false)
const toastMessage = ref<string | null>(null)
let toastTimer: ReturnType<typeof setTimeout> | null = null
const activeMenuId = ref<string | null>(null)

const toggleMenu = (id: string) => {
  activeMenuId.value = activeMenuId.value === id ? null : id
}

const closeMenu = () => {
  activeMenuId.value = null
}

// Pagination State
const currentPage = ref(1)
const perPage = ref(10)

const formatTime = (timeString?: string): string => {
  if (!timeString) return '—'
  try {
    if (timeString.includes('T') || timeString.includes('-')) {
      const date = new Date(timeString)
      return date.toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit' })
    }
    const timeParts = timeString.split(':')
    if (timeParts.length >= 2) {
      let hours = parseInt(timeParts[0])
      const minutes = timeParts[1]
      const ampm = hours >= 12 ? 'PM' : 'AM'
      hours = hours % 12 || 12
      return `${hours}:${minutes} ${ampm}`
    }
    return timeString
  } catch (err) {
    console.error('[TableAssignments] Error formatting time:', err)
    return timeString
  }
}

const getWaiterName = (waiter: any): string => {
  if (!waiter) return 'Unknown Staff'
  if (waiter.user?.name) return waiter.user.name
  if (waiter.name) return waiter.name
  if (waiter.user?.first_name) return `${waiter.user.first_name} ${waiter.user.last_name || ''}`.trim()
  if (waiter.user?.email) return waiter.user.email.split('@')[0]
  if (waiter.employee_number) return `Waiter #${waiter.employee_number}`
  return 'Service Waiter'
}

const getTableNumber = (assignment: any): string => {
  if (assignment.table?.table_number) return String(assignment.table.table_number)
  if (assignment.restaurant_table?.table_number) return String(assignment.restaurant_table.table_number)
  if (assignment.table_number) return String(assignment.table_number)
  if (assignment.table?.table_name) return String(assignment.table.table_name)
  if (assignment.table_id) return `#${String(assignment.table_id).slice(-4)}`
  return '1'
}

const getTableSection = (assignment: any): string => {
  return (
    assignment.table?.section ||
    assignment.restaurant_table?.section ||
    assignment.table?.location ||
    assignment.restaurant_table?.location ||
    'Main Dining'
  )
}

const getTableCapacity = (assignment: any): number | null => {
  return assignment.table?.capacity || assignment.restaurant_table?.capacity || null
}

const filteredAssignments = computed(() => {
  const list = tableAssignmentStore.assignments || []
  const q = searchQuery.value.trim().toLowerCase()
  const statusFilter = filterStatus.value.toLowerCase()

  if (!q && !statusFilter) return list

  return list.filter((a: any) => {
    if (statusFilter && (a.status || 'active').toLowerCase() !== statusFilter) {
      return false
    }
    if (q) {
      const waiterName = getWaiterName(a.waiter).toLowerCase()
      const tableNum = getTableNumber(a).toLowerCase()
      const section = getTableSection(a).toLowerCase()
      const status = (a.status || 'active').toLowerCase()
      if (!waiterName.includes(q) && !tableNum.includes(q) && !section.includes(q) && !status.includes(q)) {
        return false
      }
    }
    return true
  })
})

const totalRecords = computed(() => filteredAssignments.value.length)
const lastPage = computed(() => Math.ceil(totalRecords.value / perPage.value) || 1)

const paginatedAssignments = computed(() => {
  const start = (currentPage.value - 1) * perPage.value
  return filteredAssignments.value.slice(start, start + perPage.value)
})

const showingFrom = computed(() => {
  if (totalRecords.value === 0) return 0
  return (currentPage.value - 1) * perPage.value + 1
})

const showingTo = computed(() => {
  return Math.min(currentPage.value * perPage.value, totalRecords.value)
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

const loadData = async (dateVal?: string) => {
  const d = dateVal || filterDate.value
  try {
    await tableAssignmentStore.loadAssignments({ date: d })
  } catch (e) {
    console.warn('[TableAssignments] Failed to load assignments:', e)
  }
  try {
    await tableAssignmentStore.loadStats(d)
  } catch (e) {
    console.warn('[TableAssignments] Failed to load stats:', e)
  }
}

onMounted(() => {
  loadData()
  document.addEventListener('click', closeMenu)
})

onUnmounted(() => {
  document.removeEventListener('click', closeMenu)
  if (toastTimer) clearTimeout(toastTimer)
})

watch(() => hotelStore.hotelId, async () => {
  currentPage.value = 1
  await loadData()
})

watch([searchQuery, filterStatus], () => {
  currentPage.value = 1
})

watch(filterDate, async (newDate) => {
  currentPage.value = 1
  await loadData(newDate)
})

watch(perPage, () => {
  currentPage.value = 1
})

const handleRefresh = async () => {
  await loadData()
}

const handleResetFilters = () => {
  searchQuery.value = ''
  filterStatus.value = ''
  filterDate.value = new Date().toISOString().split('T')[0]
  currentPage.value = 1
}

const toggleFilter = () => {
  isFilterOpen.value = !isFilterOpen.value
}

const toggleFullscreen = () => {
  isFullscreen.value = !isFullscreen.value
}

const changePerPage = (event: Event) => {
  const target = event.target as HTMLSelectElement
  perPage.value = Number(target.value)
}

const goToPage = (page: number) => {
  if (page >= 1 && page <= lastPage.value) {
    currentPage.value = page
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

const showToast = (msg: string) => {
  toastMessage.value = msg
  if (toastTimer) clearTimeout(toastTimer)
  toastTimer = setTimeout(() => {
    toastMessage.value = null
  }, 3000)
}

const handleEdit = (assignment: any) => {
  selectedEditAssignment.value = assignment
  showEditModal.value = true
}

const handleEditSuccess = async () => {
  showEditModal.value = false
  selectedEditAssignment.value = null
  await handleRefresh()
  showToast('Table assignment updated successfully')
}

const promptDeleteAssignment = (assignment: any) => {
  selectedAssignment.value = assignment
  showDeleteConfirm.value = true
}

const confirmDelete = async () => {
  if (!selectedAssignment.value) return
  const id = selectedAssignment.value.id
  try {
    await tableAssignmentStore.removeAssignment(id)
    showDeleteConfirm.value = false
    selectedAssignment.value = null
    await handleRefresh()
    showToast('Assignment removed successfully')
  } catch (err: any) {
    console.error('[TableAssignments] Error deleting assignment:', err)
    showToast('Failed to delete assignment')
  }
}

const handleAssignSuccess = async () => {
  showAssignModal.value = false
  await handleRefresh()
  showToast('Waiter assigned to table successfully')
}
</script>

<template>
  <DashboardLayout>
    <div
      class="space-y-6 bg-slate-50 dark:bg-slate-950 min-h-screen p-4 sm:p-6 max-w-full overflow-hidden font-sans"
      :class="{ 'fixed inset-0 z-50 p-6 overflow-y-auto bg-white dark:bg-slate-950': isFullscreen }"
    >
      <!-- Toast Notification -->
      <div v-if="toastMessage" class="fixed bottom-6 right-6 z-50 bg-slate-900 dark:bg-white text-white dark:text-slate-900 px-5 py-3 rounded-2xl shadow-2xl flex items-center gap-3 border border-slate-800 text-xs font-bold">
        <CheckCircle2 class="w-4 h-4 text-emerald-500" />
        <span>{{ toastMessage }}</span>
      </div>

      <!-- Header Section -->
      <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 p-5 sm:p-6 rounded-2xl shadow-xs">
        <div class="flex items-center gap-3">
          <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-indigo-600 to-indigo-700 flex items-center justify-center shadow-md flex-shrink-0 text-white">
            <Users class="w-5 h-5 stroke-[2.2]" />
          </div>
          <div>
            <h1 class="text-xl sm:text-2xl font-black text-slate-900 dark:text-white">{{ languageStore.t('table_assignments', 'Table Assignments') }}</h1>
            <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">{{ languageStore.t('table_assignments_desc', 'Assign waiters to dining tables, designate shifts, and monitor priority coverage.') }}</p>
          </div>
        </div>
      </div>

      <!-- Statistics Cards -->
      <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
        <!-- Total Assignments -->
        <div class="bg-white dark:bg-slate-900 rounded-2xl p-5 shadow-xs border border-slate-200 dark:border-slate-800 flex items-center justify-between">
          <div>
            <p class="text-[10px] font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider">{{ languageStore.t('total_assignments', 'Total Assignments') }}</p>
            <h3 class="text-2xl font-black text-slate-900 dark:text-white mt-1">{{ tableAssignmentStore.stats.total_assignments }}</h3>
          </div>
          <div class="p-3 rounded-2xl bg-blue-500/10 text-blue-600 dark:text-blue-400">
            <Users class="w-5 h-5" />
          </div>
        </div>

        <!-- Tables Covered -->
        <div class="bg-white dark:bg-slate-900 rounded-2xl p-5 shadow-xs border border-slate-200 dark:border-slate-800 flex items-center justify-between">
          <div>
            <p class="text-[10px] font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider">{{ languageStore.t('tables_covered', 'Tables Covered') }}</p>
            <h3 class="text-2xl font-black text-purple-600 dark:text-purple-400 mt-1">{{ tableAssignmentStore.stats.total_tables }}</h3>
          </div>
          <div class="p-3 rounded-2xl bg-purple-500/10 text-purple-600 dark:text-purple-400">
            <MapPin class="w-5 h-5" />
          </div>
        </div>

        <!-- Waiters Assigned -->
        <div class="bg-white dark:bg-slate-900 rounded-2xl p-5 shadow-xs border border-slate-200 dark:border-slate-800 flex items-center justify-between">
          <div>
            <p class="text-[10px] font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider">{{ languageStore.t('waiters_on_duty', 'Waiters on Duty') }}</p>
            <h3 class="text-2xl font-black text-emerald-600 dark:text-emerald-400 mt-1">{{ tableAssignmentStore.stats.total_waiters }}</h3>
          </div>
          <div class="p-3 rounded-2xl bg-emerald-500/10 text-emerald-600 dark:text-emerald-400">
            <Users class="w-5 h-5" />
          </div>
        </div>

        <!-- Active Assignments -->
        <div class="bg-white dark:bg-slate-900 rounded-2xl p-5 shadow-xs border border-slate-200 dark:border-slate-800 flex items-center justify-between">
          <div>
            <p class="text-[10px] font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider">{{ languageStore.t('active_assignments', 'Active Assignments') }}</p>
            <h3 class="text-2xl font-black text-amber-600 dark:text-amber-400 mt-1">{{ tableAssignmentStore.stats.active_assignments ?? tableAssignmentStore.stats.primary_assignments }}</h3>
          </div>
          <div class="p-3 rounded-2xl bg-amber-500/10 text-amber-600 dark:text-amber-400">
            <CheckCircle2 class="w-5 h-5" />
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
              type="text"
              :placeholder="languageStore.t('search_assignments_placeholder', 'Search assignments by waiter, table, section...')"
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
            @click="handleRefresh"
            :disabled="tableAssignmentStore.loading"
            :title="languageStore.t('refresh', 'Refresh')"
            class="inline-flex items-center justify-center h-9 w-9 sm:h-10 sm:w-10 rounded-xl border border-slate-200 dark:border-[#1e3455] bg-white dark:bg-[#13233c]/80 text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-[#1c3356] hover:text-slate-900 dark:hover:text-white transition disabled:opacity-50 cursor-pointer"
          >
            <RefreshCw class="w-4 h-4" :class="{ 'animate-spin': tableAssignmentStore.loading }" />
          </button>

          <!-- Fullscreen Toggle -->
          <button
            type="button"
            @click="toggleFullscreen"
            :title="languageStore.t('toggle_fullscreen', 'Toggle Fullscreen')"
            class="inline-flex items-center justify-center h-9 w-9 sm:h-10 sm:w-10 rounded-xl border border-slate-200 dark:border-[#1e3455] bg-white dark:bg-[#13233c]/80 text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-[#1c3356] hover:text-slate-900 dark:hover:text-white transition cursor-pointer"
          >
            <component :is="isFullscreen ? Minimize2 : Maximize2" class="w-4 h-4" />
          </button>

          <!-- Assign Primary Button -->
          <button
            type="button"
            @click="showAssignModal = true"
            class="inline-flex items-center gap-1.5 rounded-xl bg-blue-600 hover:bg-blue-700 text-white px-3.5 sm:px-4 py-2.5 text-xs sm:text-sm font-bold shadow-md shadow-blue-600/25 transition active:scale-98 cursor-pointer flex-shrink-0"
          >
            <Plus class="w-4 h-4 text-white" />
            <span class="text-white">{{ languageStore.t('assign_table', 'Assign Table') }}</span>
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
          <div class="grid grid-cols-1 sm:grid-cols-3 gap-3.5 sm:gap-4">
            <!-- Date Filter -->
            <div>
              <label class="mb-1.5 block text-xs font-semibold text-slate-600 dark:text-slate-300">
                {{ languageStore.t('assignment_date', 'Assignment Date') }}
              </label>
              <input
                v-model="filterDate"
                type="date"
                class="w-full rounded-xl border border-slate-200 dark:border-[#1e3455] bg-slate-50/80 dark:bg-[#13233c] text-slate-900 dark:text-white px-3.5 py-2 text-xs sm:text-sm focus:ring-2 focus:ring-blue-500/40 focus:border-blue-500 dark:focus:border-blue-400 transition outline-none"
              />
            </div>

            <!-- Status Filter -->
            <div>
              <label class="mb-1.5 block text-xs font-semibold text-slate-600 dark:text-slate-300">
                {{ languageStore.t('status', 'Status') }}
              </label>
              <select
                v-model="filterStatus"
                class="w-full rounded-xl border border-slate-200 dark:border-[#1e3455] bg-slate-50/80 dark:bg-[#13233c] text-slate-900 dark:text-white px-3.5 py-2 text-xs sm:text-sm focus:ring-2 focus:ring-blue-500/40 focus:border-blue-500 dark:focus:border-blue-400 transition cursor-pointer font-medium outline-none"
              >
                <option value="">{{ languageStore.t('all_statuses', 'All Statuses') }}</option>
                <option value="active">{{ languageStore.t('active', 'Active') }}</option>
                <option value="inactive">{{ languageStore.t('inactive', 'Inactive') }}</option>
              </select>
            </div>

            <!-- Reset Filters -->
            <div class="flex items-end">
              <button
                type="button"
                @click="handleResetFilters"
                class="w-full inline-flex items-center justify-center gap-1.5 rounded-xl border border-slate-200 dark:border-[#1e3455] bg-slate-100/70 dark:bg-[#13233c] px-3.5 py-2 text-xs font-semibold text-slate-700 dark:text-slate-300 hover:bg-slate-200 dark:hover:bg-[#1c3356] transition cursor-pointer"
              >
                <RotateCcw class="w-3.5 h-3.5" />
                <span>{{ languageStore.t('reset_filters', 'Reset Filters') }}</span>
              </button>
            </div>
          </div>
        </div>
      </Transition>

      <!-- Assignments Table Container -->
      <div class="rounded-2xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 shadow-xs overflow-hidden font-sans w-full">
        <!-- Desktop Table View -->
        <div class="hidden md:block overflow-x-auto w-full">
          <table class="w-full text-left border-collapse">
            <thead class="bg-slate-50/90 dark:bg-[#0c182c] border-b border-slate-200 dark:border-[#1e3455]">
              <tr class="text-[11px] font-bold text-slate-500 dark:text-slate-400 select-none">
                <th class="py-3 px-4 pl-5 whitespace-nowrap">{{ languageStore.t('table', 'Table') }}</th>
                <th class="py-3 px-4 whitespace-nowrap">{{ languageStore.t('section', 'Section / Location') }}</th>
                <th class="py-3 px-4 whitespace-nowrap">{{ languageStore.t('assigned_waiter', 'Assigned Waiter') }}</th>
                <th class="py-3 px-4 text-center whitespace-nowrap">{{ languageStore.t('status', 'Status') }}</th>
                <th class="py-3 px-4 text-right pr-5 whitespace-nowrap">{{ languageStore.t('actions', 'Actions') }}</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-100 dark:divide-[#1e3455]/60 text-xs">
              <!-- Loading Spinner State -->
              <tr v-if="tableAssignmentStore.loading">
                <td colspan="5" class="px-6 py-20 text-center">
                  <div class="flex flex-col items-center justify-center gap-3">
                    <Loader2 class="w-8 h-8 text-blue-600 dark:text-blue-400 animate-spin" />
                    <span class="text-xs font-bold text-slate-600 dark:text-slate-400">{{ languageStore.t('loading_table_assignments', 'Loading table assignments...') }}</span>
                  </div>
                </td>
              </tr>

              <!-- Data Rows -->
              <template v-else>
                <tr
                  v-for="(assignment, index) in paginatedAssignments"
                  :key="assignment.id"
                  class="hover:bg-slate-50/80 dark:hover:bg-[#13233c]/60 transition-colors duration-150 group"
                >
                <!-- Table -->
                <td class="py-3 px-4 pl-5 whitespace-nowrap">
                  <div class="flex items-center gap-2">
                    <span class="font-extrabold text-slate-900 dark:text-white font-mono text-xs sm:text-sm">
                      {{ languageStore.t('table', 'Table') }} {{ getTableNumber(assignment) }}
                    </span>
                    <span v-if="getTableCapacity(assignment)" class="text-[10px] text-slate-400 font-bold">
                      ({{ getTableCapacity(assignment) }} {{ languageStore.t('seats', 'seats') }})
                    </span>
                  </div>
                </td>

                <!-- Section / Location -->
                <td class="py-3 px-4 whitespace-nowrap">
                  <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-xl text-xs font-bold bg-blue-500/10 text-blue-600 dark:text-blue-400 border border-blue-500/20">
                    <MapPin class="w-3 h-3 text-blue-500" />
                    {{ getTableSection(assignment) }}
                  </span>
                </td>

                <!-- Assigned Waiter -->
                <td class="py-3 px-4 whitespace-nowrap">
                  <div class="flex items-center gap-2.5">
                    <div class="w-7 h-7 rounded-full bg-blue-500/10 text-blue-600 dark:text-blue-400 font-bold flex items-center justify-center text-xs border border-blue-500/20">
                      {{ getWaiterName(assignment.waiter).charAt(0).toUpperCase() }}
                    </div>
                    <span class="font-bold text-slate-900 dark:text-white">{{ getWaiterName(assignment.waiter) }}</span>
                  </div>
                </td>

                <!-- Status -->
                <td class="py-3 px-4 text-center whitespace-nowrap">
                  <span
                    class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[10px] font-bold"
                    :class="assignment.status === 'active' ? 'bg-emerald-500/10 text-emerald-600 border border-emerald-500/20' : 'bg-slate-100 text-slate-500 border border-slate-200'"
                  >
                    <span class="w-1.5 h-1.5 rounded-full" :class="assignment.status === 'active' ? 'bg-emerald-500' : 'bg-slate-400'"></span>
                    {{ languageStore.t(assignment.status || 'active', (assignment.status || 'active').replace('_', ' ')) }}
                  </span>
                </td>

                <!-- Actions -->
                <td class="py-3 px-4 text-right pr-5 whitespace-nowrap">
                  <div class="relative inline-block text-left">
                    <button
                      type="button"
                      @click.stop="toggleMenu(assignment.id)"
                      class="inline-flex items-center justify-center w-8 h-8 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-[#13233c] text-slate-600 dark:text-slate-300 hover:text-blue-600 dark:hover:text-blue-400 hover:border-blue-300 dark:hover:border-blue-500/50 hover:bg-blue-50/50 dark:hover:bg-blue-950/30 shadow-xs transition cursor-pointer"
                      :class="{ 'bg-blue-50 dark:bg-blue-950/40 text-blue-600 dark:text-blue-400 border-blue-400 dark:border-blue-500 ring-2 ring-blue-500/20': activeMenuId === assignment.id }"
                      :title="languageStore.t('actions', 'Actions')"
                    >
                      <MoreVertical class="w-4 h-4 stroke-[2.2]" />
                    </button>

                    <!-- Dropdown Menu -->
                    <Transition
                      enter-active-class="transition duration-100 ease-out"
                      enter-from-class="transform scale-95 opacity-0"
                      enter-to-class="transform scale-100 opacity-100"
                      leave-active-class="transition duration-75 ease-in"
                      leave-from-class="transform scale-100 opacity-100"
                      leave-to-class="transform scale-95 opacity-0"
                    >
                      <div
                        v-if="activeMenuId === assignment.id"
                        class="absolute right-0 z-50 w-44 rounded-2xl bg-white dark:bg-[#0f1d32] border border-slate-200 dark:border-slate-800 shadow-xl shadow-slate-900/15 dark:shadow-slate-950/60 py-1.5 focus:outline-none"
                        :class="[
                          index >= paginatedAssignments.length - 2 && paginatedAssignments.length > 2
                            ? 'bottom-full mb-1 origin-bottom-right'
                            : 'top-full mt-1 origin-top-right'
                        ]"
                        @click.stop
                      >
                        <button
                          @click="handleEdit(assignment); closeMenu()"
                          class="w-full flex items-center gap-2.5 px-3.5 py-2 text-xs font-semibold text-slate-700 dark:text-slate-200 hover:bg-slate-100 dark:hover:bg-[#152744] hover:text-blue-600 dark:hover:text-blue-400 transition cursor-pointer"
                        >
                          <Edit3 class="w-3.5 h-3.5 text-blue-500" />
                          <span>{{ languageStore.t('edit_assignment', 'Edit Assignment') }}</span>
                        </button>

                        <div class="my-1 border-t border-slate-100 dark:border-slate-800"></div>

                        <button
                          @click="promptDeleteAssignment(assignment); closeMenu()"
                          class="w-full flex items-center gap-2.5 px-3.5 py-2 text-xs font-semibold text-rose-600 dark:text-rose-400 hover:bg-rose-50 dark:hover:bg-rose-950/30 transition cursor-pointer"
                        >
                          <Trash2 class="w-3.5 h-3.5" />
                          <span>{{ languageStore.t('delete_assignment', 'Delete Assignment') }}</span>
                        </button>
                      </div>
                    </Transition>
                  </div>
                </td>
              </tr>

              <!-- Empty State -->
              <tr v-if="paginatedAssignments.length === 0">
                <td colspan="6" class="px-6 py-12 text-center text-slate-500 dark:text-slate-400 text-xs font-bold">
                  {{ languageStore.t('no_table_assignments', 'No table assignments found for this filter.') }}
                </td>
              </tr>
            </template>
          </tbody>
        </table>
      </div>

      <!-- Mobile View -->
      <div class="md:hidden divide-y divide-slate-100 dark:divide-slate-800">
        <div v-if="tableAssignmentStore.loading" class="py-16 text-center flex flex-col items-center justify-center gap-3">
          <Loader2 class="w-8 h-8 text-blue-600 dark:text-blue-400 animate-spin" />
          <span class="text-xs font-bold text-slate-600 dark:text-slate-400">{{ languageStore.t('loading_table_assignments', 'Loading table assignments...') }}</span>
        </div>
        <template v-else>
          <div
            v-for="assignment in paginatedAssignments"
            :key="assignment.id"
            class="p-4 space-y-3 hover:bg-slate-50/60 dark:hover:bg-slate-800/40 transition"
          >
            <div class="flex items-center justify-between">
              <div class="flex items-center gap-2">
                <span class="font-bold text-slate-900 dark:text-white text-sm">
                  {{ languageStore.t('table', 'Table') }} {{ getTableNumber(assignment) }}
                </span>
                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-lg text-[10px] font-bold bg-blue-500/10 text-blue-600 dark:text-blue-400 border border-blue-500/20">
                  <MapPin class="w-3 h-3 text-blue-500" />
                  {{ getTableSection(assignment) }}
                </span>
              </div>
              <span
                class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-bold"
                :class="assignment.status === 'active' ? 'bg-emerald-500/10 text-emerald-600 border border-emerald-500/20' : 'bg-slate-100 text-slate-500 border border-slate-200'"
              >
                <span class="w-1.5 h-1.5 rounded-full" :class="assignment.status === 'active' ? 'bg-emerald-500' : 'bg-slate-400'"></span>
                {{ languageStore.t(assignment.status || 'active', (assignment.status || 'active').replace('_', ' ')) }}
              </span>
            </div>
            <div class="flex items-center justify-between text-xs text-slate-600 dark:text-slate-400">
              <div class="flex items-center gap-2">
                <div class="w-6 h-6 rounded-full bg-blue-500/10 text-blue-600 dark:text-blue-400 font-bold flex items-center justify-center text-[10px]">
                  {{ getWaiterName(assignment.waiter).charAt(0).toUpperCase() }}
                </div>
                <span>{{ getWaiterName(assignment.waiter) }}</span>
              </div>
              <div class="flex gap-2.5">
                <button @click="handleEdit(assignment)" class="text-blue-600 dark:text-blue-400 font-bold cursor-pointer">{{ languageStore.t('edit', 'Edit') }}</button>
                <button @click="promptDeleteAssignment(assignment)" class="text-rose-600 dark:text-rose-400 font-bold cursor-pointer">{{ languageStore.t('delete', 'Delete') }}</button>
              </div>
            </div>
          </div>
          <div v-if="paginatedAssignments.length === 0" class="p-8 text-center text-slate-500 text-xs font-bold">
            {{ languageStore.t('no_table_assignments', 'No table assignments found for this filter.') }}
          </div>
        </template>
      </div>

        <!-- Pagination Footer -->
        <div
          v-if="totalRecords > 0"
          class="border-t border-slate-200 dark:border-slate-800/80 px-4 sm:px-6 py-3 sm:py-4 bg-slate-50/50 dark:bg-[#0c182c] flex flex-col sm:flex-row items-center justify-between gap-3 text-xs"
        >
          <div class="text-slate-500 dark:text-slate-400 font-medium">
            {{ languageStore.t('showing', 'Showing') }} <span class="font-bold text-slate-900 dark:text-white">{{ showingFrom }}</span> {{ languageStore.t('to', 'to') }}
            <span class="font-bold text-slate-900 dark:text-white">{{ showingTo }}</span> {{ languageStore.t('of', 'of') }}
            <span class="font-bold text-slate-900 dark:text-white">{{ totalRecords }}</span> {{ languageStore.t('assignments', 'assignments') }}
          </div>

          <div class="flex items-center gap-2 sm:gap-3">
            <div class="flex items-center gap-1.5">
              <span class="text-slate-500 dark:text-slate-400 font-medium">{{ languageStore.t('per_page', 'Per page:') }}</span>
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

      <!-- Modals -->
      <AssignWaiterToTableModal
        v-if="showAssignModal"
        @close="showAssignModal = false"
        @success="handleAssignSuccess"
      />

      <EditTableAssignmentModal
        v-if="showEditModal && selectedEditAssignment"
        :assignment="selectedEditAssignment"
        @close="showEditModal = false"
        @success="handleEditSuccess"
      />

      <!-- Delete Confirmation Modal -->
      <Teleport to="body">
        <div
          v-if="showDeleteConfirm"
          class="fixed inset-0 z-50 bg-black/60 backdrop-blur-xs flex items-center justify-center p-4"
          @click.self="showDeleteConfirm = false"
        >
          <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-3xl p-6 max-w-sm w-full space-y-4 shadow-2xl">
            <div class="text-center space-y-2">
              <div class="w-12 h-12 bg-rose-500/10 text-rose-500 rounded-2xl flex items-center justify-center mx-auto">
                <AlertCircle class="w-6 h-6" />
              </div>
              <h3 class="text-base font-black text-slate-900 dark:text-white">{{ languageStore.t('delete_assignment_q', 'Delete Assignment?') }}</h3>
              <p class="text-xs text-slate-500 dark:text-slate-400">
                {{ languageStore.t('delete_assignment_confirm', 'Are you sure you want to unassign this waiter from the table?') }}
              </p>
            </div>
            <div class="flex gap-3">
              <button
                @click="showDeleteConfirm = false"
                class="flex-1 py-2 border border-slate-200 dark:border-slate-800 rounded-xl text-xs font-bold text-slate-700 dark:text-slate-300 cursor-pointer"
              >
                {{ languageStore.t('cancel', 'Cancel') }}
              </button>
              <button
                @click="confirmDelete"
                class="flex-1 py-2 bg-rose-600 hover:bg-rose-700 text-white rounded-xl text-xs font-bold cursor-pointer"
              >
                {{ languageStore.t('delete', 'Delete') }}
              </button>
            </div>
          </div>
        </div>
      </Teleport>
    </div>
  </DashboardLayout>
</template>

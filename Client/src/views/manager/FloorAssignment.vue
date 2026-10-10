<script setup lang="ts">
import { onMounted, ref, computed, watch } from 'vue'
import DashboardLayout from '../../Layouts/DashboardLayout.vue'
import AddStaffToFloorModal from '@/components/manager/AddStaffToFloorModal.vue'
import FloorDeepDetailsModal from '@/components/manager/FloorDeepDetailsModal.vue'
import AddEditFloorModal from '@/components/manager/AddEditFloorModal.vue'
import { useFloorAssignmentStore } from '@/stores/manager/floorAssignmentStore'
import { useHotelStore } from '@/stores/hotelStore'
import { useLanguageStore } from '@/stores/language'
import floorManagementService, { type Floor } from '@/services/manager/floorManagementService'
import {
  Save,
  Plus,
  Users,
  CheckCircle2,
  AlertCircle,
  ChevronLeft,
  ChevronRight,
  UserPlus,
  Trash2,
  Search,
  Filter,
  X,
  Maximize2,
  Minimize2,
  RotateCcw,
  RefreshCw,
  Building2,
  Eye,
  Edit2,
  Hotel,
  BedDouble,
  Layers,
  LayoutGrid,
  LayoutList,
  Clock,
  Sparkles,
  ToggleLeft,
  ToggleRight,
  Loader2,
  Check,
} from 'lucide-vue-next'

const assignmentStore = useFloorAssignmentStore()
const hotelStore = useHotelStore()
const languageStore = useLanguageStore()

const isLoading = ref(false)
const isSaving = ref(false)
const hasChanges = ref(false)
const allFloors = ref<Floor[]>([])

const viewMode = ref<'grid' | 'table'>('grid')

const isFilterOpen = ref(false)
const isFullscreen = ref(false)
const searchQuery = ref('')
const selectedStatus = ref<'all' | 'active' | 'inactive'>('all')
const selectedStaffing = ref<'all' | 'staffed' | 'unstaffed'>('all')

const showAddStaffModal = ref(false)
const selectedFloorForModal = ref<{ id: string; name: string; hotel_id?: string } | null>(null)

const showDeepDetailsModal = ref(false)
const inspectingFloor = ref<Floor | null>(null)

const showAddEditFloorModal = ref(false)
const editingFloor = ref<Floor | null>(null)

const actionMessage = ref<{ type: 'success' | 'error'; text: string } | null>(null)

const showFeedback = (text: string, type: 'success' | 'error' = 'success') => {
  actionMessage.value = { type, text }
  setTimeout(() => {
    actionMessage.value = null
  }, 4000)
}

const currentPage = ref(1)
const perPage = ref(12)

const getWaitersForFloor = (floorId: string) => {
  const storeWaiters = assignmentStore.groupedByFloor[floorId] || []
  if (storeWaiters.length > 0) return storeWaiters

  const floorObj = allFloors.value.find((f) => String(f.id) === String(floorId))
  if (floorObj?.waiter_assignments && floorObj.waiter_assignments.length > 0) {
    return floorObj.waiter_assignments
  }
  return []
}

const filteredFloors = computed(() => {
  const list = Array.isArray(allFloors.value) ? allFloors.value : []
  const status = selectedStatus.value
  const staffing = selectedStaffing.value
  const q = searchQuery.value.trim().toLowerCase()

  return list.filter((f) => {
    if (status !== 'all' && Boolean(f.is_active) !== (status === 'active')) return false

    const staffList = getWaitersForFloor(f.id)
    if (staffing !== 'all') {
      if (staffing === 'staffed' && staffList.length === 0) return false
      if (staffing === 'unstaffed' && staffList.length > 0) return false
    }

    if (q) {
      const name = (f.name || '').toLowerCase()
      const floorNum = String(f.floor_number || '').toLowerCase()
      const waiters = staffList
        .map((a: any) =>
          (a.waiter?.user?.name || a.waiter_name || a.waiter?.name || '').toLowerCase(),
        )
        .join(' ')
      if (!name.includes(q) && !floorNum.includes(q) && !waiters.includes(q)) return false
    }

    return true
  })
})

const totalFloorsCount = computed(() => filteredFloors.value.length)
const lastPage = computed(() => Math.ceil(totalFloorsCount.value / perPage.value) || 1)

const paginatedFloors = computed(() => {
  const start = (currentPage.value - 1) * perPage.value
  return filteredFloors.value.slice(start, start + perPage.value)
})

const showingFrom = computed(() => {
  if (totalFloorsCount.value === 0) return 0
  return (currentPage.value - 1) * perPage.value + 1
})

const showingTo = computed(() => {
  return Math.min(currentPage.value * perPage.value, totalFloorsCount.value)
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

watch(perPage, () => {
  currentPage.value = 1
})

const goToPage = (p: number) => {
  if (p >= 1 && p <= lastPage.value) {
    currentPage.value = p
  }
}

const prevPage = () => {
  if (currentPage.value > 1) currentPage.value--
}

const nextPage = () => {
  if (currentPage.value < lastPage.value) currentPage.value++
}

const resetFilters = () => {
  searchQuery.value = ''
  selectedStatus.value = 'all'
  selectedStaffing.value = 'all'
  currentPage.value = 1
}

const toggleFilter = () => {
  isFilterOpen.value = !isFilterOpen.value
}

const toggleFullscreen = () => {
  isFullscreen.value = !isFullscreen.value
}

const overallStats = computed(() => {
  const total = allFloors.value.length
  const activeCount = allFloors.value.filter((f) => f.is_active).length
  const assignedWaitersCount = assignmentStore.stats?.total_assignments || 0
  const availableWaitersCount = assignmentStore.stats?.total_waiters || 0
  const totalRooms = allFloors.value.reduce(
    (acc, f) => acc + (f.room_count || f.total_rooms || 0),
    0,
  )

  const staffedFloors = allFloors.value.filter((f) => getWaitersForFloor(f.id).length > 0).length
  const coverageRate = total > 0 ? Math.round((staffedFloors / total) * 100) : 0

  return {
    totalFloors: total,
    activeFloors: activeCount,
    inactiveFloors: total - activeCount,
    totalRooms,
    assignedWaiters: assignedWaitersCount,
    availableWaiters: availableWaitersCount,
    coverageRate,
  }
})

const nextFloorNumber = computed(() => {
  if (allFloors.value.length === 0) return 1
  const numbers = allFloors.value.map((f) => Number(f.floor_number) || 0)
  return Math.max(...numbers, 0) + 1
})

const loadData = async () => {
  isLoading.value = true
  try {
    const floorsRes = await floorManagementService.getFloors({ per_page: 200 })
    if (floorsRes && Array.isArray(floorsRes.data)) {
      allFloors.value = floorsRes.data
    } else if (Array.isArray(floorsRes)) {
      allFloors.value = floorsRes
    } else {
      allFloors.value = []
    }
    await Promise.all([assignmentStore.fetchTodayAssignments(), assignmentStore.fetchStats()])
  } catch (err: any) {
    console.error('Failed to load floor management data:', err)
  } finally {
    isLoading.value = false
  }
}

const openDeepInspection = (floor: Floor) => {
  inspectingFloor.value = floor
  showDeepDetailsModal.value = true
}

const openAddStaff = (floor: Floor) => {
  selectedFloorForModal.value = {
    id: floor.id,
    name: floor.name || `Floor ${floor.floor_number}`,
    hotel_id: floor.hotel_id,
  }
  showAddStaffModal.value = true
}

const handleStaffAssigned = async () => {
  showAddStaffModal.value = false
  showFeedback(languageStore.t('staff_assigned_success', 'Staff assigned to floor successfully!'))
  await loadData()
}

// Floor Add & Edit handlers
const openCreateFloor = () => {
  editingFloor.value = null
  showAddEditFloorModal.value = true
}

const openEditFloor = (floor: Floor) => {
  editingFloor.value = floor
  showAddEditFloorModal.value = true
}

const handleFloorSaved = async (saved: Floor) => {
  showAddEditFloorModal.value = false
  showFeedback(
    editingFloor.value
      ? languageStore.t('floor_updated_success', 'Floor level updated successfully!')
      : languageStore.t('floor_created_success', 'New floor created successfully!'),
  )
  await loadData()
}

// Status Toggle
const toggleFloorActive = async (floor: Floor) => {
  try {
    if (floor.is_active) {
      await floorManagementService.deactivateFloor(floor.id)
      showFeedback(`Floor #${floor.floor_number} deactivated`)
    } else {
      await floorManagementService.activateFloor(floor.id)
      showFeedback(`Floor #${floor.floor_number} activated`)
    }
    await loadData()
  } catch (err: any) {
    showFeedback(err?.response?.data?.message || 'Failed to toggle floor status', 'error')
  }
}

// Delete Floor
const handleDeleteFloor = async (floor: Floor) => {
  const confirmed = confirm(
    languageStore.t(
      'confirm_delete_floor',
      `Are you sure you want to delete ${floor.name || 'Floor #' + floor.floor_number}? All room assignments must be unlinked first.`,
    ),
  )
  if (!confirmed) return

  try {
    await floorManagementService.deleteFloor(floor.id)
    showFeedback(languageStore.t('floor_deleted_success', 'Floor deleted successfully'))
    if (inspectingFloor.value?.id === floor.id) {
      showDeepDetailsModal.value = false
    }
    await loadData()
  } catch (err: any) {
    showFeedback(err?.response?.data?.message || err?.message || 'Cannot delete floor', 'error')
  }
}

// Save Assignments Bulk
const saveAssignments = async () => {
  if (!hasChanges.value) return
  isSaving.value = true
  try {
    const assignmentsToSave = assignmentStore.assignments.map((a: any) => ({
      waiter_id: a.waiter?.id || a.waiter_id,
      floor_id: a.floor?.id || a.floor_id,
      shift_id: a.shift?.id || a.shift_id,
      assignment_date: a.assignment_date || new Date().toISOString().split('T')[0],
      priority: a.priority || 'primary',
    }))

    if (assignmentsToSave.length === 0) return

    await assignmentStore.saveAssignments(assignmentsToSave)
    hasChanges.value = false
    showFeedback(languageStore.t('assignments_saved', 'All floor assignments saved successfully!'))
    await loadData()
  } catch (err: any) {
    showFeedback('Failed to save assignments', 'error')
  } finally {
    isSaving.value = false
  }
}

const refreshData = async () => {
  await loadData()
  showFeedback(languageStore.t('data_refreshed', 'Floor data refreshed'))
}

onMounted(loadData)
watch(() => hotelStore.hotelId, loadData)
</script>

<template>
  <DashboardLayout>
    <div
      class="space-y-6 bg-slate-50 dark:bg-slate-950 min-h-screen p-4 sm:p-6 max-w-full overflow-hidden font-sans"
      :class="{ 'fixed inset-0 z-50 p-6 overflow-y-auto bg-white dark:bg-slate-950': isFullscreen }"
    >
      <Transition
        enter-active-class="transition duration-300 ease-out"
        enter-from-class="transform -translate-y-4 opacity-0"
        enter-to-class="transform translate-y-0 opacity-100"
        leave-active-class="transition duration-200 ease-in"
        leave-from-class="transform translate-y-0 opacity-100"
        leave-to-class="transform -translate-y-4 opacity-0"
      >
        <div
          v-if="actionMessage"
          class="fixed top-6 right-6 z-50 px-4 py-3 rounded-2xl shadow-xl flex items-center gap-3 border text-xs sm:text-sm font-bold backdrop-blur-md"
          :class="
            actionMessage.type === 'success'
              ? 'bg-emerald-500/90 text-white border-emerald-400'
              : 'bg-rose-500/90 text-white border-rose-400'
          "
        >
          <CheckCircle2 v-if="actionMessage.type === 'success'" class="w-5 h-5 flex-shrink-0" />
          <AlertCircle v-else class="w-5 h-5 flex-shrink-0" />
          <span>{{ actionMessage.text }}</span>
        </div>
      </Transition>

      <div
        class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 p-5 sm:p-6 rounded-3xl shadow-xs flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4"
      >
        <div class="flex items-center gap-3.5">
          <div
            class="w-12 h-12 rounded-2xl bg-gradient-to-br from-blue-600 to-indigo-700 flex items-center justify-center shadow-md shadow-blue-500/20 text-white flex-shrink-0"
          >
            <Hotel class="w-6 h-6 stroke-[2.2]" />
          </div>
          <div>
            <div class="flex items-center gap-2.5 flex-wrap">
              <h1 class="text-xl sm:text-2xl font-black text-slate-900 dark:text-white">
                {{
                  languageStore.t('floor_management_title', 'Floor Management & Staff Allocation')
                }}
              </h1>
              <span
                v-if="hotelStore.hotelName"
                class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold bg-blue-100 dark:bg-blue-900/30 text-blue-800 dark:text-blue-300 border border-blue-200 dark:border-blue-700/50"
              >
                <Building2 class="w-3.5 h-3.5" />
                {{ hotelStore.hotelName }}
              </span>
            </div>
            <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">
              {{
                languageStore.t(
                  'floor_management_desc',
                  'Create and configure building levels, inspect rooms, and allocate waiters to coverage zones.',
                )
              }}
            </p>
          </div>
        </div>

        <div class="flex flex-wrap items-center gap-2.5">
          <button
            @click="openCreateFloor"
            class="px-4 py-2.5 rounded-2xl bg-blue-600 hover:bg-blue-700 text-white text-xs sm:text-sm font-black shadow-md shadow-blue-600/25 transition flex items-center gap-2 cursor-pointer hover:shadow-lg active:scale-98"
          >
            <Plus class="w-4 h-4 stroke-[3]" />
            <span>{{ languageStore.t('add_floor', 'Add Floor') }}</span>
          </button>

          <button
            v-if="hasChanges"
            @click="saveAssignments"
            :disabled="isSaving"
            class="px-4 py-2.5 rounded-2xl bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-xs sm:text-sm shadow-md shadow-emerald-600/20 transition flex items-center gap-1.5 cursor-pointer disabled:opacity-40"
          >
            <Save class="w-4 h-4" />
            <span>{{
              isSaving
                ? languageStore.t('saving', 'Saving...')
                : languageStore.t('save_assignments', 'Save Assignments')
            }}</span>
          </button>
        </div>
      </div>

      <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
        <div
          class="bg-white dark:bg-slate-900 rounded-3xl p-5 shadow-xs border border-slate-200 dark:border-slate-800 flex items-center justify-between"
        >
          <div>
            <p
              class="text-[11px] font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider"
            >
              {{ languageStore.t('total_floors', 'Total Floors') }}
            </p>
            <div class="flex items-baseline gap-2 mt-1">
              <h3 class="text-2xl sm:text-3xl font-black text-slate-900 dark:text-white">
                {{ overallStats.totalFloors }}
              </h3>
              <span class="text-xs font-bold text-emerald-600 dark:text-emerald-400">
                {{ overallStats.activeFloors }} {{ languageStore.t('active', 'active') }}
              </span>
            </div>
          </div>
          <div class="p-3.5 rounded-2xl bg-blue-500/10 text-blue-600 dark:text-blue-400">
            <Layers class="w-6 h-6" />
          </div>
        </div>

        <div
          class="bg-white dark:bg-slate-900 rounded-3xl p-5 shadow-xs border border-slate-200 dark:border-slate-800 flex items-center justify-between"
        >
          <div>
            <p
              class="text-[11px] font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider"
            >
              {{ languageStore.t('rooms_managed', 'Rooms Managed') }}
            </p>
            <div class="flex items-baseline gap-2 mt-1">
              <h3 class="text-2xl sm:text-3xl font-black text-slate-900 dark:text-white">
                {{ overallStats.totalRooms }}
              </h3>
              <span class="text-xs font-medium text-slate-400">
                {{ languageStore.t('across_floors', 'across floors') }}
              </span>
            </div>
          </div>
          <div class="p-3.5 rounded-2xl bg-indigo-500/10 text-indigo-600 dark:text-indigo-400">
            <BedDouble class="w-6 h-6" />
          </div>
        </div>

        <div
          class="bg-white dark:bg-slate-900 rounded-3xl p-5 shadow-xs border border-slate-200 dark:border-slate-800 flex items-center justify-between"
        >
          <div>
            <p
              class="text-[11px] font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider"
            >
              {{ languageStore.t('assigned_waiters', 'Assigned Waiters') }}
            </p>
            <div class="flex items-baseline gap-2 mt-1">
              <h3 class="text-2xl sm:text-3xl font-black text-emerald-600 dark:text-emerald-400">
                {{ overallStats.assignedWaiters }}
              </h3>
              <span class="text-xs font-bold text-slate-500 dark:text-slate-400">
                / {{ overallStats.availableWaiters }}
                {{ languageStore.t('available', 'available') }}
              </span>
            </div>
          </div>
          <div class="p-3.5 rounded-2xl bg-emerald-500/10 text-emerald-600 dark:text-emerald-400">
            <Users class="w-6 h-6" />
          </div>
        </div>

        <div
          class="bg-white dark:bg-slate-900 rounded-3xl p-5 shadow-xs border border-slate-200 dark:border-slate-800 flex items-center justify-between"
        >
          <div>
            <p
              class="text-[11px] font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider"
            >
              {{ languageStore.t('floor_coverage_health', 'Coverage Health') }}
            </p>
            <div class="flex items-baseline gap-2 mt-1">
              <h3 class="text-2xl sm:text-3xl font-black text-purple-600 dark:text-purple-400">
                {{ overallStats.coverageRate }}%
              </h3>
              <span class="text-xs font-bold text-slate-500 dark:text-slate-400">
                {{ languageStore.t('staffed', 'staffed') }}
              </span>
            </div>
          </div>
          <div class="p-3.5 rounded-2xl bg-purple-500/10 text-purple-600 dark:text-purple-400">
            <Sparkles class="w-6 h-6" />
          </div>
        </div>
      </div>

      <div
        class="flex flex-wrap items-center justify-between gap-3 rounded-2xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 p-3 sm:p-4 shadow-xs"
      >
        <div class="flex flex-1 items-center gap-2.5 min-w-[280px] max-w-2xl">
          <div class="relative flex-1">
            <Search
              class="w-4 h-4 absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400 dark:text-slate-500"
            />
            <input
              v-model="searchQuery"
              type="text"
              :placeholder="
                languageStore.t(
                  'search_floors_placeholder',
                  'Search by floor name, level #, or assigned staff...',
                )
              "
              class="w-full rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800/80 text-slate-900 dark:text-white placeholder-slate-400 pl-10 pr-4 py-2.5 text-xs sm:text-sm font-medium outline-none focus:ring-2 focus:ring-blue-500/40 transition"
            />
          </div>

          <button
            type="button"
            @click="toggleFilter"
            class="inline-flex items-center gap-2 rounded-xl px-4 py-2.5 text-xs sm:text-sm font-bold transition cursor-pointer flex-shrink-0 border"
            :class="
              isFilterOpen
                ? 'bg-blue-600/10 text-blue-600 dark:text-blue-400 border-blue-500/40'
                : 'bg-slate-50 dark:bg-slate-800 text-slate-700 dark:text-slate-300 border-slate-200 dark:border-slate-700 hover:bg-slate-100'
            "
          >
            <component :is="isFilterOpen ? X : Filter" class="w-4 h-4" />
            <span>{{
              isFilterOpen
                ? languageStore.t('hide_filters', 'Hide Filters')
                : languageStore.t('filter', 'Filter')
            }}</span>
          </button>
        </div>

        <div class="flex items-center gap-2">
          <div
            class="flex items-center p-1 rounded-xl bg-slate-100 dark:bg-slate-800 border border-slate-200 dark:border-slate-700"
          >
            <button
              @click="viewMode = 'grid'"
              :class="
                viewMode === 'grid'
                  ? 'bg-white dark:bg-slate-900 text-blue-600 dark:text-blue-400 shadow-xs'
                  : 'text-slate-500 dark:text-slate-400 hover:text-slate-700'
              "
              class="p-2 rounded-lg text-xs font-bold transition flex items-center gap-1.5 cursor-pointer"
              :title="languageStore.t('grid_view', 'Grid Cards View')"
            >
              <LayoutGrid class="w-4 h-4" />
            </button>
            <button
              @click="viewMode = 'table'"
              :class="
                viewMode === 'table'
                  ? 'bg-white dark:bg-slate-900 text-blue-600 dark:text-blue-400 shadow-xs'
                  : 'text-slate-500 dark:text-slate-400 hover:text-slate-700'
              "
              class="p-2 rounded-lg text-xs font-bold transition flex items-center gap-1.5 cursor-pointer"
              :title="languageStore.t('table_view', 'Table List View')"
            >
              <LayoutList class="w-4 h-4" />
            </button>
          </div>

          <button
            type="button"
            @click="refreshData"
            :disabled="isLoading"
            class="p-2.5 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-600 dark:text-slate-300 hover:bg-slate-100 transition cursor-pointer"
            :title="languageStore.t('refresh', 'Refresh Floors')"
          >
            <RefreshCw class="w-4 h-4" :class="{ 'animate-spin': isLoading }" />
          </button>

          <button
            type="button"
            @click="toggleFullscreen"
            class="p-2.5 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-600 dark:text-slate-300 hover:bg-slate-100 transition cursor-pointer"
            :title="languageStore.t('fullscreen', 'Toggle Fullscreen')"
          >
            <component :is="isFullscreen ? Minimize2 : Maximize2" class="w-4 h-4" />
          </button>
        </div>
      </div>

      <Transition
        enter-active-class="transition duration-200 ease-out"
        enter-from-class="transform -translate-y-2 opacity-0"
        enter-to-class="transform translate-y-0 opacity-100"
        leave-active-class="transition duration-150 ease-in"
        leave-from-class="transform translate-y-0 opacity-100"
        leave-to-class="transform -translate-y-2 opacity-0"
      >
        <div
          v-if="isFilterOpen"
          class="rounded-2xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 p-4 sm:p-5 shadow-xs space-y-4"
        >
          <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
            <div>
              <label class="mb-1.5 block text-xs font-bold text-slate-700 dark:text-slate-300">
                {{ languageStore.t('floor_status', 'Floor Status') }}
              </label>
              <select
                v-model="selectedStatus"
                class="w-full rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-white px-3.5 py-2 text-xs font-bold outline-none cursor-pointer"
              >
                <option value="all">{{ languageStore.t('all_floors', 'All Floors') }}</option>
                <option value="active">
                  {{ languageStore.t('active_only', 'Active Floors Only') }}
                </option>
                <option value="inactive">
                  {{ languageStore.t('inactive_only', 'Inactive Floors Only') }}
                </option>
              </select>
            </div>

            <div>
              <label class="mb-1.5 block text-xs font-bold text-slate-700 dark:text-slate-300">
                {{ languageStore.t('staff_coverage', 'Staff Coverage') }}
              </label>
              <select
                v-model="selectedStaffing"
                class="w-full rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-white px-3.5 py-2 text-xs font-bold outline-none cursor-pointer"
              >
                <option value="all">
                  {{ languageStore.t('all_coverage', 'All Coverage Levels') }}
                </option>
                <option value="staffed">
                  {{ languageStore.t('has_assigned_staff', 'Has Staff Assigned') }}
                </option>
                <option value="unstaffed">
                  {{ languageStore.t('unstaffed_floors', 'Unstaffed / Needs Staff') }}
                </option>
              </select>
            </div>

            <div class="flex items-end">
              <button
                type="button"
                @click="resetFilters"
                class="w-full inline-flex items-center justify-center gap-1.5 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-100 dark:bg-slate-800 px-3.5 py-2 text-xs font-bold text-slate-700 dark:text-slate-300 hover:bg-slate-200 cursor-pointer transition"
              >
                <RotateCcw class="w-3.5 h-3.5" />
                <span>{{ languageStore.t('reset_filters', 'Reset Filters') }}</span>
              </button>
            </div>
          </div>
        </div>
      </Transition>

      <div
        v-if="isLoading"
        class="p-16 text-center bg-white dark:bg-slate-900 rounded-3xl border border-slate-200 dark:border-slate-800"
      >
        <Loader2 class="w-10 h-10 text-blue-600 dark:text-blue-400 animate-spin mx-auto mb-3" />
        <h4 class="text-sm font-bold text-slate-800 dark:text-slate-200">
          {{ languageStore.t('loading_floors', 'Loading floors & staff allocations...') }}
        </h4>
      </div>

      <div
        v-else-if="paginatedFloors.length === 0"
        class="p-16 text-center bg-white dark:bg-slate-900 rounded-3xl border border-slate-200 dark:border-slate-800"
      >
        <Hotel class="w-12 h-12 text-slate-400 mx-auto mb-3 opacity-50" />
        <h3 class="text-base font-bold text-slate-800 dark:text-slate-200">
          {{ languageStore.t('no_floors_found', 'No floors match your criteria') }}
        </h3>
        <p class="text-xs text-slate-500 dark:text-slate-400 mt-1 mb-4">
          {{
            languageStore.t(
              'no_floors_desc',
              'Try clearing your search query or create a new floor level.',
            )
          }}
        </p>
        <button
          @click="openCreateFloor"
          class="px-4 py-2.5 rounded-2xl bg-blue-600 hover:bg-blue-700 text-white font-bold text-xs shadow-md transition inline-flex items-center gap-2 cursor-pointer"
        >
          <Plus class="w-4 h-4" />
          <span>{{ languageStore.t('add_new_floor', 'Add New Floor') }}</span>
        </button>
      </div>

      <div
        v-else-if="viewMode === 'grid'"
        class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-5"
      >
        <div
          v-for="floor in paginatedFloors"
          :key="floor.id"
          class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-3xl p-5 sm:p-6 shadow-xs hover:shadow-md transition-all flex flex-col justify-between group relative overflow-hidden"
        >
          <div>
            <div class="flex items-start justify-between gap-3 mb-3.5">
              <div class="flex items-center gap-3">
                <div
                  class="w-11 h-11 rounded-2xl bg-gradient-to-br from-blue-600 to-indigo-600 text-white font-black text-sm flex items-center justify-center shadow-md shadow-blue-500/20 flex-shrink-0"
                >
                  L{{ floor.floor_number }}
                </div>
                <div>
                  <h3
                    class="text-base font-black text-slate-900 dark:text-white capitalize group-hover:text-blue-600 dark:group-hover:text-blue-400 transition-colors"
                  >
                    {{ floor.name }}
                  </h3>
                  <div class="flex items-center gap-2 mt-0.5">
                    <span class="text-[11px] font-bold text-slate-400 dark:text-slate-500">
                      {{ languageStore.t('floor', 'Floor') }} #{{ floor.floor_number }}
                    </span>
                    <span class="text-slate-300 dark:text-slate-700">•</span>
                    <span
                      class="text-[11px] font-bold text-slate-600 dark:text-slate-400 flex items-center gap-1"
                    >
                      <BedDouble class="w-3.5 h-3.5 text-slate-400" />
                      {{ floor.room_count || floor.total_rooms || 0 }}
                      {{ languageStore.t('rooms', 'rooms') }}
                    </span>
                  </div>
                </div>
              </div>

              <button
                @click="toggleFloorActive(floor)"
                :title="languageStore.t('toggle_status', 'Click to toggle active status')"
                class="px-2.5 py-1 rounded-full text-[10px] font-black uppercase tracking-wider border cursor-pointer transition"
                :class="
                  floor.is_active
                    ? 'bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 border-emerald-500/30 hover:bg-emerald-500/20'
                    : 'bg-slate-500/10 text-slate-600 dark:text-slate-400 border-slate-500/30 hover:bg-slate-500/20'
                "
              >
                {{
                  floor.is_active
                    ? languageStore.t('active', 'Active')
                    : languageStore.t('inactive', 'Inactive')
                }}
              </button>
            </div>

            <p class="text-xs text-slate-500 dark:text-slate-400 line-clamp-2 min-h-[32px] mb-4">
              {{
                floor.description ||
                languageStore.t(
                  'no_desc_provided',
                  'Standard operational floor level with guest rooms and service stations.',
                )
              }}
            </p>

            <div
              class="p-3.5 rounded-2xl bg-slate-50 dark:bg-slate-800/60 border border-slate-100 dark:border-slate-800 mb-4"
            >
              <div class="flex items-center justify-between mb-2">
                <span
                  class="text-[11px] font-bold text-slate-700 dark:text-slate-300 flex items-center gap-1.5"
                >
                  <Users class="w-3.5 h-3.5 text-blue-500" />
                  {{ languageStore.t('assigned_waiters', 'Assigned Waiters') }}
                  <span
                    class="px-1.5 py-0.2 rounded-full text-[10px] font-black bg-blue-100 dark:bg-blue-900/40 text-blue-700 dark:text-blue-300"
                  >
                    {{ getWaitersForFloor(floor.id).length }}
                  </span>
                </span>

                <button
                  @click="openAddStaff(floor)"
                  class="text-[11px] font-bold text-blue-600 dark:text-blue-400 hover:underline flex items-center gap-1 cursor-pointer"
                >
                  <Plus class="w-3 h-3" />
                  <span>{{ languageStore.t('assign', 'Assign') }}</span>
                </button>
              </div>

              <div v-if="getWaitersForFloor(floor.id).length > 0" class="flex flex-wrap gap-1.5">
                <div
                  v-for="(w, idx) in getWaitersForFloor(floor.id).slice(0, 4)"
                  :key="idx"
                  class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-700 text-[11px] font-bold text-slate-800 dark:text-slate-200 shadow-2xs"
                >
                  <div
                    class="w-4 h-4 rounded-full bg-blue-600 text-white text-[9px] font-black flex items-center justify-center"
                  >
                    {{
                      (w.waiter?.user?.name ||
                        w.waiter_name ||
                        w.waiter?.name ||
                        'W')[0].toUpperCase()
                    }}
                  </div>
                  <span class="max-w-[100px] truncate capitalize">
                    {{ w.waiter?.user?.name || w.waiter_name || w.waiter?.name || 'Waiter' }}
                  </span>
                  <span
                    v-if="w.priority === 'primary'"
                    class="w-1.5 h-1.5 rounded-full bg-blue-500"
                    title="Primary Waiter"
                  ></span>
                </div>

                <span
                  v-if="getWaitersForFloor(floor.id).length > 4"
                  class="inline-flex items-center px-2 py-1 rounded-xl bg-slate-200 dark:bg-slate-700 text-[10px] font-black text-slate-600 dark:text-slate-300"
                >
                  +{{ getWaitersForFloor(floor.id).length - 4 }} more
                </span>
              </div>

              <div
                v-else
                class="text-xs text-amber-600 dark:text-amber-400 flex items-center gap-1.5 font-medium py-0.5"
              >
                <AlertCircle class="w-3.5 h-3.5 flex-shrink-0" />
                <span>{{
                  languageStore.t('unstaffed_hint', 'No waiters currently assigned to this floor')
                }}</span>
              </div>
            </div>
          </div>

          <div
            class="pt-3 border-t border-slate-100 dark:border-slate-800 flex items-center justify-between gap-2"
          >
            <button
              @click="openDeepInspection(floor)"
              class="flex-1 py-2 px-3 rounded-xl bg-blue-50 dark:bg-blue-950/40 text-blue-600 dark:text-blue-400 hover:bg-blue-100 dark:hover:bg-blue-900/40 font-bold text-xs transition flex items-center justify-center gap-1.5 cursor-pointer border border-blue-200 dark:border-blue-800/60"
            >
              <Eye class="w-3.5 h-3.5 stroke-[2.2]" />
              <span>{{ languageStore.t('inspect_floor', 'Deep Inspection') }}</span>
            </button>

            <button
              @click="openEditFloor(floor)"
              class="p-2 rounded-xl text-slate-500 hover:text-slate-900 dark:hover:text-white hover:bg-slate-100 dark:hover:bg-slate-800 transition cursor-pointer border border-transparent hover:border-slate-200 dark:hover:border-slate-700"
              :title="languageStore.t('edit_floor', 'Edit Floor')"
            >
              <Edit2 class="w-4 h-4" />
            </button>

            <button
              @click="handleDeleteFloor(floor)"
              class="p-2 rounded-xl text-slate-400 hover:text-rose-600 hover:bg-rose-50 dark:hover:bg-rose-950/30 transition cursor-pointer"
              :title="languageStore.t('delete_floor', 'Delete Floor')"
            >
              <Trash2 class="w-4 h-4" />
            </button>
          </div>
        </div>
      </div>

      <div
        v-else
        class="rounded-3xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 shadow-xs overflow-hidden w-full"
      >
        <div class="overflow-x-auto w-full">
          <table class="w-full text-left border-collapse">
            <thead
              class="bg-slate-50/90 dark:bg-slate-950/60 border-b border-slate-200 dark:border-slate-800"
            >
              <tr class="text-[11px] font-bold text-slate-500 dark:text-slate-400 select-none">
                <th class="py-3.5 px-4 pl-6 whitespace-nowrap">
                  {{ languageStore.t('floor_level', 'Floor Level') }}
                </th>
                <th class="py-3.5 px-4 whitespace-nowrap">
                  {{ languageStore.t('rooms', 'Rooms') }}
                </th>
                <th class="py-3.5 px-4 text-center whitespace-nowrap">
                  {{ languageStore.t('status', 'Status') }}
                </th>
                <th class="py-3.5 px-4 whitespace-nowrap">
                  {{ languageStore.t('assigned_waiters', 'Assigned Waiters') }}
                </th>
                <th class="py-3.5 px-4 text-right pr-6 whitespace-nowrap">
                  {{ languageStore.t('actions', 'Actions') }}
                </th>
              </tr>
            </thead>

            <tbody class="divide-y divide-slate-100 dark:divide-slate-800/80 text-xs">
              <tr
                v-for="floor in paginatedFloors"
                :key="floor.id"
                class="hover:bg-slate-50/80 dark:hover:bg-slate-800/50 transition-colors group"
              >
                <td class="py-3.5 px-4 pl-6 whitespace-nowrap">
                  <div class="flex items-center gap-3">
                    <div
                      class="w-9 h-9 rounded-xl bg-gradient-to-br from-blue-600 to-indigo-600 text-white font-black text-xs flex items-center justify-center flex-shrink-0 shadow-xs"
                    >
                      L{{ floor.floor_number }}
                    </div>
                    <div>
                      <div class="font-black text-slate-900 dark:text-white capitalize">
                        {{ floor.name }}
                      </div>
                      <div class="text-[10px] text-slate-400">
                        {{ languageStore.t('floor', 'Floor') }} #{{ floor.floor_number }}
                      </div>
                    </div>
                  </div>
                </td>

                <td class="py-3.5 px-4 whitespace-nowrap">
                  <div
                    class="flex items-center gap-1.5 font-bold text-slate-700 dark:text-slate-300"
                  >
                    <BedDouble class="w-4 h-4 text-slate-400" />
                    <span
                      >{{ floor.room_count || floor.total_rooms || 0 }}
                      {{ languageStore.t('rooms', 'rooms') }}</span
                    >
                  </div>
                </td>

                <td class="py-3.5 px-4 text-center whitespace-nowrap">
                  <button
                    @click="toggleFloorActive(floor)"
                    class="px-2.5 py-0.5 rounded-full text-[10px] font-black uppercase tracking-wider border cursor-pointer transition"
                    :class="
                      floor.is_active
                        ? 'bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 border-emerald-500/30'
                        : 'bg-slate-500/10 text-slate-600 dark:text-slate-400 border-slate-500/30'
                    "
                  >
                    {{
                      floor.is_active
                        ? languageStore.t('active', 'Active')
                        : languageStore.t('inactive', 'Inactive')
                    }}
                  </button>
                </td>

                <td class="py-3.5 px-4">
                  <div
                    v-if="getWaitersForFloor(floor.id).length > 0"
                    class="flex flex-wrap items-center gap-1.5"
                  >
                    <div
                      v-for="(w, idx) in getWaitersForFloor(floor.id).slice(0, 3)"
                      :key="idx"
                      class="inline-flex items-center gap-1 px-2 py-0.5 rounded-lg bg-slate-100 dark:bg-slate-800 text-[11px] font-bold text-slate-700 dark:text-slate-300 border border-slate-200 dark:border-slate-700"
                    >
                      <div
                        class="w-3.5 h-3.5 rounded-full bg-blue-600 text-white text-[8px] font-bold flex items-center justify-center"
                      >
                        {{
                          (w.waiter?.user?.name ||
                            w.waiter_name ||
                            w.waiter?.name ||
                            'W')[0].toUpperCase()
                        }}
                      </div>
                      <span class="truncate max-w-[90px] capitalize">
                        {{ w.waiter?.user?.name || w.waiter_name || w.waiter?.name || 'Waiter' }}
                      </span>
                    </div>

                    <span
                      v-if="getWaitersForFloor(floor.id).length > 3"
                      class="text-[10px] font-bold text-slate-400"
                    >
                      +{{ getWaitersForFloor(floor.id).length - 3 }}
                    </span>

                    <button
                      @click="openAddStaff(floor)"
                      class="p-1 rounded-lg hover:bg-slate-100 dark:hover:bg-slate-800 text-blue-600 dark:text-blue-400 cursor-pointer"
                      title="Assign more staff"
                    >
                      <Plus class="w-3.5 h-3.5" />
                    </button>
                  </div>

                  <button
                    v-else
                    @click="openAddStaff(floor)"
                    class="text-xs font-bold text-blue-600 dark:text-blue-400 hover:underline flex items-center gap-1 cursor-pointer"
                  >
                    <UserPlus class="w-3.5 h-3.5" />
                    <span>{{ languageStore.t('assign_waiter', 'Assign Waiter') }}</span>
                  </button>
                </td>

                <td class="py-3.5 px-4 text-right pr-6 whitespace-nowrap">
                  <div class="flex items-center justify-end gap-1.5">
                    <button
                      @click="openDeepInspection(floor)"
                      class="px-2.5 py-1.5 rounded-xl bg-blue-50 dark:bg-blue-950/40 text-blue-600 dark:text-blue-400 hover:bg-blue-100 font-bold text-xs transition flex items-center gap-1 cursor-pointer border border-blue-200 dark:border-blue-800/60"
                      :title="languageStore.t('inspect_floor', 'Deep Inspection')"
                    >
                      <Eye class="w-3.5 h-3.5" />
                      <span>{{ languageStore.t('inspect', 'Inspect') }}</span>
                    </button>

                    <button
                      @click="openEditFloor(floor)"
                      class="p-1.5 rounded-xl text-slate-400 hover:text-slate-800 dark:hover:text-white hover:bg-slate-100 dark:hover:bg-slate-800 transition cursor-pointer"
                      :title="languageStore.t('edit_floor', 'Edit Floor')"
                    >
                      <Edit2 class="w-4 h-4" />
                    </button>

                    <button
                      @click="handleDeleteFloor(floor)"
                      class="p-1.5 rounded-xl text-slate-400 hover:text-rose-600 hover:bg-rose-50 dark:hover:bg-rose-950/30 transition cursor-pointer"
                      :title="languageStore.t('delete_floor', 'Delete Floor')"
                    >
                      <Trash2 class="w-4 h-4" />
                    </button>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

      <div
        v-if="totalFloorsCount > 0"
        class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 p-4 rounded-2xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 text-xs font-bold text-slate-500 dark:text-slate-400"
      >
        <div>
          {{ languageStore.t('showing', 'Showing') }}
          <span class="text-slate-900 dark:text-white">{{ showingFrom }}</span> -
          <span class="text-slate-900 dark:text-white">{{ showingTo }}</span>
          {{ languageStore.t('of', 'of') }}
          <span class="text-slate-900 dark:text-white">{{ totalFloorsCount }}</span>
          {{ languageStore.t('floors', 'floors') }}
        </div>

        <div class="flex items-center gap-1.5">
          <button
            @click="prevPage"
            :disabled="currentPage === 1"
            class="p-2 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-600 dark:text-slate-300 disabled:opacity-30 disabled:cursor-not-allowed hover:bg-slate-100 cursor-pointer"
          >
            <ChevronLeft class="w-4 h-4" />
          </button>

          <button
            v-for="p in paginationPages"
            :key="p"
            @click="goToPage(p)"
            class="w-8 h-8 rounded-xl font-black text-xs transition cursor-pointer"
            :class="
              p === currentPage
                ? 'bg-blue-600 text-white shadow-xs'
                : 'border border-slate-200 dark:border-slate-700 text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800'
            "
          >
            {{ p }}
          </button>

          <button
            @click="nextPage"
            :disabled="currentPage === lastPage"
            class="p-2 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-600 dark:text-slate-300 disabled:opacity-30 disabled:cursor-not-allowed hover:bg-slate-100 cursor-pointer"
          >
            <ChevronRight class="w-4 h-4" />
          </button>
        </div>
      </div>
    </div>

    <FloorDeepDetailsModal
      :is-open="showDeepDetailsModal"
      :floor="inspectingFloor"
      :floors-list="allFloors"
      @close="showDeepDetailsModal = false"
      @assign-staff="openAddStaff"
      @edit-floor="openEditFloor"
      @refresh="loadData"
    />

    <AddEditFloorModal
      :is-open="showAddEditFloorModal"
      :floor="editingFloor"
      :suggested-floor-number="nextFloorNumber"
      @close="showAddEditFloorModal = false"
      @saved="handleFloorSaved"
    />

    <AddStaffToFloorModal
      v-if="showAddStaffModal && selectedFloorForModal"
      :is-open="showAddStaffModal"
      :floor-id="selectedFloorForModal.id"
      :floor-name="selectedFloorForModal.name"
      :hotel-id="selectedFloorForModal.hotel_id"
      :floors="allFloors"
      @close="showAddStaffModal = false"
      @assigned="handleStaffAssigned"
      @success="handleStaffAssigned"
    />
  </DashboardLayout>
</template>

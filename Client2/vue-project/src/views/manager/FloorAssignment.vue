<script setup lang="ts">
import { onMounted, ref, computed } from 'vue'
import { useRouter } from 'vue-router'
import DashboardLayout from '../../Layouts/DashboardLayout.vue'
import AddStaffToFloorModal from '@/components/manager/AddStaffToFloorModal.vue'
import { useFloorAssignmentStore } from '@/stores/manager/floorAssignmentStore'
import floorManagementService from '@/services/manager/floorManagementService'
import {
  Hotel,
  Save,
  Clock,
  Plus,
  Users,
  CheckCircle2,
  AlertCircle,
  TrendingUp,
  Loader2,
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
} from 'lucide-vue-next'

const router = useRouter()
const assignmentStore = useFloorAssignmentStore()

const isLoading = ref(false)
const isSaving = ref(false)
const hasChanges = ref(false)
const allFloors = ref<any[]>([])

const isFilterOpen = ref(false)
const isFullscreen = ref(false)

const searchQuery = ref('')
const selectedStatus = ref('all')
const selectedStaffing = ref('all')

// Modal state
const showAddStaffModal = ref(false)
const selectedFloorForModal = ref<{ id: string; name: string } | null>(null)

// Pagination State
const currentPage = ref(1)
const perPage = ref(10)

const filteredFloors = computed(() => {
  let list = Array.isArray(allFloors.value) ? allFloors.value : []

  if (selectedStatus.value !== 'all') {
    const isActive = selectedStatus.value === 'active'
    list = list.filter((f) => Boolean(f.is_active) === isActive)
  }

  if (selectedStaffing.value !== 'all') {
    const grouped = assignmentStore.groupedByFloor || {}
    if (selectedStaffing.value === 'staffed') {
      list = list.filter((f) => (grouped[f.id] || []).length > 0)
    } else if (selectedStaffing.value === 'unstaffed') {
      list = list.filter((f) => (grouped[f.id] || []).length === 0)
    }
  }

  if (searchQuery.value.trim()) {
    const q = searchQuery.value.toLowerCase().trim()
    const grouped = assignmentStore.groupedByFloor || {}
    list = list.filter((f) => {
      const name = (f.name || '').toLowerCase()
      const floorNum = String(f.floor_number || '').toLowerCase()
      const waiters = (grouped[f.id] || [])
        .map((a: any) => (a.waiter?.user?.name || a.waiter?.name || '').toLowerCase())
        .join(' ')
      return name.includes(q) || floorNum.includes(q) || waiters.includes(q)
    })
  }

  return list
})

const totalFloors = computed(() => (Array.isArray(filteredFloors.value) ? filteredFloors.value.length : 0))
const lastPage = computed(() => Math.ceil(totalFloors.value / perPage.value) || 1)

const paginatedFloors = computed(() => {
  if (!Array.isArray(filteredFloors.value)) return []
  const start = (currentPage.value - 1) * perPage.value
  const end = start + perPage.value
  return filteredFloors.value.slice(start, end)
})

const showingFrom = computed(() => {
  if (totalFloors.value === 0) return 0
  return (currentPage.value - 1) * perPage.value + 1
})

const showingTo = computed(() => {
  return Math.min(currentPage.value * perPage.value, totalFloors.value)
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

// Computed stats
const stats = computed(() => ({
  total_waiters: assignmentStore.stats?.total_waiters || 0,
  assigned: assignmentStore.stats?.total_assignments || 0,
  on_break: 0,
  open_slots: Math.max(0, (assignmentStore.stats?.total_waiters || 0) - (assignmentStore.stats?.total_assignments || 0)),
}))

const loadData = async () => {
  isLoading.value = true
  try {
    const floorsRes = await floorManagementService.getFloors()
    if (floorsRes && Array.isArray(floorsRes.data)) {
      allFloors.value = floorsRes.data
    } else if (Array.isArray(floorsRes)) {
      allFloors.value = floorsRes
    } else {
      allFloors.value = []
    }
    await assignmentStore.fetchTodayAssignments()
    await assignmentStore.fetchStats()
  } catch (err: any) {
    console.error('Failed to load initial data:', err)
  } finally {
    isLoading.value = false
  }
}

const openAddStaff = (floor: any) => {
  selectedFloorForModal.value = {
    id: floor.id,
    name: floor.name || `Floor ${floor.floor_number}`,
  }
  showAddStaffModal.value = true
}

const handleStaffAdded = async () => {
  showAddStaffModal.value = false
  hasChanges.value = true
  await assignmentStore.fetchAssignments()
  await assignmentStore.fetchStats()
}

const removeAssignment = (assignmentId: string) => {
  assignmentStore.removeAssignment(assignmentId)
  hasChanges.value = true
}

const refreshData = async () => {
  isLoading.value = true
  try {
    await loadData()
  } finally {
    isLoading.value = false
  }
}

const saveAssignments = async () => {
  if (!hasChanges.value) return

  isSaving.value = true
  try {
    const assignmentsToSave = assignmentStore.assignments.map((a: any) => ({
      waiter_id: a.waiter.id,
      floor_id: a.floor.id,
      shift_id: a.shift.id,
      assignment_date: a.assignment_date,
      priority: a.priority,
    }))

    if (assignmentsToSave.length === 0) {
      assignmentStore.error = 'No assignments to save'
      return
    }

    await assignmentStore.saveAssignments(assignmentsToSave)
    hasChanges.value = false
  } catch (err: any) {
    console.error('Failed to save assignments:', err)
  } finally {
    isSaving.value = false
  }
}

onMounted(() => {
  loadData()
})
</script>

<template>
  <DashboardLayout>
    <div
      class="space-y-6 bg-slate-50 dark:bg-slate-950 min-h-screen p-4 sm:p-6 max-w-full overflow-hidden font-sans"
      :class="{ 'fixed inset-0 z-50 p-6 overflow-y-auto bg-white dark:bg-slate-950': isFullscreen }"
    >
      <!-- Header Banner -->
      <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 p-5 sm:p-6 rounded-2xl shadow-xs flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div class="flex items-center gap-3">
          <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-blue-600 to-blue-700 flex items-center justify-center shadow-md flex-shrink-0 text-white">
            <Hotel class="w-5 h-5 stroke-[2.2]" />
          </div>
          <div>
            <h1 class="text-xl sm:text-2xl font-black text-slate-900 dark:text-white">Floor Staff Assignments</h1>
            <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">Allocate service staff and floor coverage across building zones.</p>
          </div>
        </div>

        <div class="flex flex-wrap items-center gap-2.5">
          <router-link
            to="/manager/add-floor"
            class="px-3.5 py-2.5 rounded-xl bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 dark:hover:bg-slate-700 text-blue-600 dark:text-blue-400 text-xs font-bold transition flex items-center gap-1.5 cursor-pointer border border-slate-200 dark:border-slate-700"
          >
            <Plus class="w-3.5 h-3.5 stroke-[3]" />
            <span>Add Floor</span>
          </router-link>

          <button
            @click="saveAssignments"
            :disabled="isSaving || !hasChanges"
            class="px-4 py-2.5 rounded-xl bg-blue-600 hover:bg-blue-700 text-white font-bold text-xs sm:text-sm shadow-md shadow-blue-600/20 transition flex items-center gap-1.5 cursor-pointer disabled:opacity-40 disabled:cursor-not-allowed"
          >
            <Save class="w-4 h-4" />
            <span>{{ isSaving ? 'Saving...' : 'Save Assignments' }}</span>
          </button>
        </div>
      </div>

      <!-- Stats Grid -->
      <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
        <div class="bg-white dark:bg-slate-900 rounded-2xl p-5 shadow-xs border border-slate-200 dark:border-slate-800 flex items-center justify-between">
          <div>
            <p class="text-[10px] font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Total Floors</p>
            <h3 class="text-2xl font-black text-slate-900 dark:text-white mt-1">{{ allFloors.length }}</h3>
          </div>
          <div class="p-3 rounded-2xl bg-blue-500/10 text-blue-600 dark:text-blue-400">
            <Hotel class="w-5 h-5" />
          </div>
        </div>

        <div class="bg-white dark:bg-slate-900 rounded-2xl p-5 shadow-xs border border-slate-200 dark:border-slate-800 flex items-center justify-between">
          <div>
            <p class="text-[10px] font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Staff Assigned</p>
            <h3 class="text-2xl font-black text-emerald-600 dark:text-emerald-400 mt-1">{{ stats.assigned }}</h3>
          </div>
          <div class="p-3 rounded-2xl bg-emerald-500/10 text-emerald-600 dark:text-emerald-400">
            <CheckCircle2 class="w-5 h-5" />
          </div>
        </div>

        <div class="bg-white dark:bg-slate-900 rounded-2xl p-5 shadow-xs border border-slate-200 dark:border-slate-800 flex items-center justify-between">
          <div>
            <p class="text-[10px] font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Available Waiters</p>
            <h3 class="text-2xl font-black text-slate-900 dark:text-white mt-1">{{ stats.total_waiters }}</h3>
          </div>
          <div class="p-3 rounded-2xl bg-purple-500/10 text-purple-600 dark:text-purple-400">
            <Users class="w-5 h-5" />
          </div>
        </div>

        <div class="bg-white dark:bg-slate-900 rounded-2xl p-5 shadow-xs border border-slate-200 dark:border-slate-800 flex items-center justify-between">
          <div>
            <p class="text-[10px] font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Open Slots</p>
            <h3 class="text-2xl font-black text-amber-600 dark:text-amber-400 mt-1">{{ stats.open_slots }}</h3>
          </div>
          <div class="p-3 rounded-2xl bg-amber-500/10 text-amber-600 dark:text-amber-400">
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
              type="text"
              placeholder="Search floors by name, number, or assigned staff..."
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
            <span>{{ isFilterOpen ? 'Hide Filter' : 'Filter' }}</span>
          </button>
        </div>

        <!-- Right: Action Buttons -->
        <div class="flex items-center gap-2 sm:gap-2.5">
          <!-- Refresh Button -->
          <button
            type="button"
            @click="refreshData"
            :disabled="isLoading"
            title="Refresh"
            class="inline-flex items-center justify-center h-9 w-9 sm:h-10 sm:w-10 rounded-xl border border-slate-200 dark:border-[#1e3455] bg-white dark:bg-[#13233c]/80 text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-[#1c3356] hover:text-slate-900 dark:hover:text-white transition disabled:opacity-50 cursor-pointer"
          >
            <RefreshCw class="w-4 h-4" :class="{ 'animate-spin': isLoading }" />
          </button>

          <!-- Fullscreen Toggle -->
          <button
            type="button"
            @click="toggleFullscreen"
            title="Toggle Fullscreen"
            class="inline-flex items-center justify-center h-9 w-9 sm:h-10 sm:w-10 rounded-xl border border-slate-200 dark:border-[#1e3455] bg-white dark:bg-[#13233c]/80 text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-[#1c3356] hover:text-slate-900 dark:hover:text-white transition cursor-pointer"
          >
            <component :is="isFullscreen ? Minimize2 : Maximize2" class="w-4 h-4" />
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
            <!-- Status Filter -->
            <div>
              <label class="mb-1.5 block text-xs font-semibold text-slate-600 dark:text-slate-300">
                Floor Status
              </label>
              <select
                v-model="selectedStatus"
                class="w-full rounded-xl border border-slate-200 dark:border-[#1e3455] bg-slate-50/80 dark:bg-[#13233c] text-slate-900 dark:text-white px-3.5 py-2 text-xs sm:text-sm focus:ring-2 focus:ring-blue-500/40 focus:border-blue-500 dark:focus:border-blue-400 transition cursor-pointer font-medium outline-none"
              >
                <option value="all">All Floors</option>
                <option value="active">Active Floors</option>
                <option value="inactive">Inactive Floors</option>
              </select>
            </div>

            <!-- Staffing Filter -->
            <div>
              <label class="mb-1.5 block text-xs font-semibold text-slate-600 dark:text-slate-300">
                Staff Coverage
              </label>
              <select
                v-model="selectedStaffing"
                class="w-full rounded-xl border border-slate-200 dark:border-[#1e3455] bg-slate-50/80 dark:bg-[#13233c] text-slate-900 dark:text-white px-3.5 py-2 text-xs sm:text-sm focus:ring-2 focus:ring-blue-500/40 focus:border-blue-500 dark:focus:border-blue-400 transition cursor-pointer font-medium outline-none"
              >
                <option value="all">All Staffing Levels</option>
                <option value="staffed">Staff Assigned</option>
                <option value="unstaffed">Unstaffed Floors</option>
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
                <span>Reset Filters</span>
              </button>
            </div>
          </div>
        </div>
      </Transition>

      <!-- Floors Data Table -->
      <div class="rounded-2xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 shadow-xs overflow-hidden font-sans w-full">
        <div class="overflow-x-auto w-full">
          <table class="w-full text-left border-collapse">
            <thead class="bg-slate-50/90 dark:bg-[#0c182c] border-b border-slate-200 dark:border-[#1e3455]">
              <tr class="text-[11px] font-bold text-slate-500 dark:text-slate-400 select-none">
                <th class="py-3 px-4 pl-5 whitespace-nowrap">Floor Zone</th>
                <th class="py-3 px-4 text-center whitespace-nowrap">Status</th>
                <th class="py-3 px-4 whitespace-nowrap">Assigned Waiters & Staff</th>
                <th class="py-3 px-4 text-right pr-5 whitespace-nowrap">Actions</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-100 dark:divide-[#1e3455]/60 text-xs">
              <tr v-if="paginatedFloors.length === 0">
                <td colspan="4" class="px-6 py-12 text-center text-slate-500 dark:text-slate-400 text-xs font-bold">
                  No floors found matching your criteria.
                </td>
              </tr>

              <tr
                v-for="floor in paginatedFloors"
                :key="floor.id"
                class="hover:bg-slate-50/80 dark:hover:bg-[#13233c]/60 transition-colors duration-150 group"
              >
                <!-- Floor Zone -->
                <td class="py-3 px-4 pl-5 whitespace-nowrap">
                  <div class="flex items-center gap-3">
                    <div class="w-8 h-8 rounded-xl bg-blue-500/10 text-blue-600 dark:text-blue-400 border border-blue-500/20 font-black text-xs flex items-center justify-center flex-shrink-0">
                      <Hotel class="w-4 h-4" />
                    </div>
                    <div>
                      <div class="font-extrabold text-slate-900 dark:text-white text-xs sm:text-sm capitalize">
                        {{ floor.name }}
                      </div>
                      <div class="text-[10px] text-blue-600 dark:text-blue-400 font-black uppercase tracking-wider">
                        Floor #{{ floor.floor_number }}
                      </div>
                    </div>
                  </div>
                </td>

                <!-- Status -->
                <td class="py-3 px-4 text-center whitespace-nowrap">
                  <span
                    class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[10px] font-extrabold border uppercase tracking-wider"
                    :class="[
                      floor.is_active
                        ? 'bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 border-emerald-500/20'
                        : 'bg-rose-500/10 text-rose-600 dark:text-rose-400 border-rose-500/20'
                    ]"
                  >
                    {{ floor.is_active ? 'Active' : 'Inactive' }}
                  </span>
                </td>

                <!-- Assigned Staff List -->
                <td class="py-3 px-4">
                  <div v-if="assignmentStore.groupedByFloor[floor.id]?.length" class="flex flex-wrap items-center gap-1.5">
                    <div
                      v-for="assignment in assignmentStore.groupedByFloor[floor.id]"
                      :key="assignment.id"
                      class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-xl bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 text-xs"
                    >
                      <div class="w-5 h-5 rounded-full bg-amber-500/10 text-amber-600 font-bold text-[10px] flex items-center justify-center flex-shrink-0">
                        {{ (assignment.waiter?.user?.name || assignment.waiter?.name || 'W')?.[0]?.toUpperCase() }}
                      </div>
                      <span class="font-bold text-slate-900 dark:text-white">
                        {{ assignment.waiter?.user?.name || assignment.waiter?.name || 'Waiter' }}
                      </span>
                      <button
                        @click="removeAssignment(assignment.id)"
                        class="text-slate-400 hover:text-rose-500 p-0.5 transition"
                        title="Remove Waiter from Floor"
                      >
                        <Trash2 class="w-3 h-3" />
                      </button>
                    </div>
                  </div>
                  <span v-else class="text-slate-400 italic text-xs">No staff currently assigned</span>
                </td>

                <!-- Actions -->
                <td class="py-3 px-4 text-right pr-5 whitespace-nowrap">
                  <button
                    @click="openAddStaff(floor)"
                    class="inline-flex items-center gap-1 px-3 py-1.5 bg-blue-600 hover:bg-blue-700 text-white rounded-xl font-bold text-xs shadow-xs transition cursor-pointer"
                  >
                    <UserPlus class="w-3.5 h-3.5" />
                    <span>Assign Staff</span>
                  </button>
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <!-- Pagination Footer -->
        <div
          v-if="totalFloors > 0"
          class="border-t border-slate-200 dark:border-slate-800/80 px-4 sm:px-6 py-3 sm:py-4 bg-slate-50/50 dark:bg-[#0c182c] flex flex-col sm:flex-row items-center justify-between gap-3 text-xs"
        >
          <div class="text-slate-500 dark:text-slate-400 font-medium">
            Showing <span class="font-bold text-slate-900 dark:text-white">{{ showingFrom }}</span> to
            <span class="font-bold text-slate-900 dark:text-white">{{ showingTo }}</span> of
            <span class="font-bold text-slate-900 dark:text-white">{{ totalFloors }}</span> floors
          </div>

          <div class="flex items-center gap-2 sm:gap-3">
            <div class="flex items-center gap-1.5">
              <span class="text-slate-500 dark:text-slate-400 font-medium">Per page:</span>
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

      <!-- Add Staff Modal -->
      <AddStaffToFloorModal
        v-if="showAddStaffModal && selectedFloorForModal"
        :floor-id="selectedFloorForModal.id"
        :floor-name="selectedFloorForModal.name"
        @close="showAddStaffModal = false"
        @success="handleStaffAdded"
      />
    </div>
  </DashboardLayout>
</template>

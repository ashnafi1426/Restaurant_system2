<script setup lang="ts">
import { ref, onMounted, onUnmounted, computed, watch } from 'vue'
import { useTableAssignmentStore } from '@/stores/manager/tableAssignmentStore'
import AssignWaiterToTableModal from '@/components/manager/AssignWaiterToTableModal.vue'
import EditTableAssignmentModal from '@/components/manager/EditTableAssignmentModal.vue'
import DashboardLayout from '@/Layouts/DashboardLayout.vue'
import { 
  Plus, 
  RefreshCw, 
  Filter, 
  Users, 
  MapPin, 
  Award,
  Clock,
  Trash2,
  Edit3,
  MoreVertical,
  Loader2,
  Calendar,
  TrendingUp,
  CheckCircle2,
  AlertCircle,
  Search,
  Check,
  X,
  ChevronLeft,
  ChevronRight
} from 'lucide-vue-next'

const tableAssignmentStore = useTableAssignmentStore()

const showAssignModal = ref(false)
const showEditModal = ref(false)
const selectedEditAssignment = ref<any>(null)
const activeMenuId = ref<string | null>(null)

const filterDate = ref(new Date().toISOString().split('T')[0])
const filterShift = ref('')
const filterPriority = ref('')
const searchQuery = ref('')

const selectedAssignment = ref<any>(null)
const showDeleteConfirm = ref(false)
const toastMessage = ref<string | null>(null)

// Pagination State
const currentPage = ref(1)
const perPage = ref(10)

const totalRecords = computed(() => filteredAssignments.value.length)
const lastPage = computed(() => Math.ceil(totalRecords.value / perPage.value) || 1)

const paginatedAssignments = computed(() => {
  const start = (currentPage.value - 1) * perPage.value
  const end = start + perPage.value
  return filteredAssignments.value.slice(start, end)
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

onMounted(async () => {
  await tableAssignmentStore.loadAssignments({ date: filterDate.value })
  await tableAssignmentStore.loadStats(filterDate.value)
})

watch([searchQuery, filterShift, filterPriority], () => {
  currentPage.value = 1
})

watch(filterDate, async (newDate) => {
  currentPage.value = 1
  await tableAssignmentStore.loadAssignments({ date: newDate })
  await tableAssignmentStore.loadStats(newDate)
})

const handleRefresh = async () => {
  await tableAssignmentStore.loadAssignments({ date: filterDate.value })
  await tableAssignmentStore.loadStats(filterDate.value)
}

const handleFilter = async () => {
  currentPage.value = 1
  await tableAssignmentStore.loadAssignments({
    date: filterDate.value,
    shift_id: filterShift.value || undefined,
    priority: filterPriority.value || undefined,
  })
  await tableAssignmentStore.loadStats(filterDate.value)
}

const handleResetFilters = async () => {
  searchQuery.value = ''
  filterShift.value = ''
  filterPriority.value = ''
  filterDate.value = new Date().toISOString().split('T')[0]
  currentPage.value = 1
  await tableAssignmentStore.loadAssignments({ date: filterDate.value })
  await tableAssignmentStore.loadStats(filterDate.value)
}

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

// Client-side search filtering
const filteredAssignments = computed(() => {
  const list = tableAssignmentStore.assignments || []
  if (!searchQuery.value.trim()) return list

  const query = searchQuery.value.toLowerCase().trim()
  return list.filter((a: any) => {
    const waiterName = getWaiterName(a.waiter).toLowerCase()
    const tableName = (a.table?.table_name || '').toLowerCase()
    const tableNumber = String(a.table?.table_number || '').toLowerCase()
    const shiftName = (a.shift?.name || '').toLowerCase()
    const priority = (a.priority || '').toLowerCase()
    const status = (a.status || '').toLowerCase()

    return (
      waiterName.includes(query) ||
      tableName.includes(query) ||
      tableNumber.includes(query) ||
      shiftName.includes(query) ||
      priority.includes(query) ||
      status.includes(query)
    )
  })
})

const getPriorityColor = (priority: string) => {
  switch ((priority || '').toLowerCase()) {
    case 'primary':
      return 'bg-blue-500/10 text-blue-600 dark:text-blue-400 border-blue-500/20 font-black'
    case 'secondary':
      return 'bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 border-emerald-500/20 font-black'
    case 'backup':
      return 'bg-amber-500/10 text-amber-600 dark:text-amber-400 border-amber-500/20 font-black'
    default:
      return 'bg-slate-500/10 text-slate-600 dark:text-slate-400 border-slate-500/20 font-black'
  }
}

const getStatusColor = (status: string) => {
  switch ((status || '').toLowerCase()) {
    case 'active':
      return 'bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 border-emerald-500/20 font-black'
    case 'inactive':
      return 'bg-slate-500/10 text-slate-600 dark:text-slate-400 border-slate-500/20 font-black'
    case 'completed':
      return 'bg-purple-500/10 text-purple-600 dark:text-purple-400 border-purple-500/20 font-black'
    default:
      return 'bg-slate-500/10 text-slate-600 dark:text-slate-400 border-slate-500/20 font-black'
  }
}

const formatTime = (timeString: string): string => {
  if (!timeString) return ''
  
  try {
    if (timeString.includes('T')) {
      const date = new Date(timeString)
      return date.toLocaleTimeString('en-US', { 
        hour: '2-digit', 
        minute: '2-digit',
        hour12: true 
      })
    }
    
    const timeParts = timeString.split(':')
    if (timeParts.length >= 2) {
      let hours = parseInt(timeParts[0])
      const minutes = timeParts[1]
      const ampm = hours >= 12 ? 'PM' : 'AM'
      
      hours = hours % 12
      hours = hours ? hours : 12
      
      return `${hours}:${minutes} ${ampm}`
    }
    
    return timeString
  } catch (err) {
    return timeString
  }
}

const getWaiterName = (waiter: any): string => {
  if (!waiter) return 'Unknown Waiter'
  if (waiter.user?.name) return waiter.user.name
  if (waiter.name) return waiter.name
  if (waiter.user?.email) return waiter.user.email.split('@')[0]
  if (waiter.employee_number) return `Waiter #${waiter.employee_number}`
  if (waiter.id) return `Waiter #${waiter.id}`
  return 'Unknown Waiter'
}

const getWaiterInitial = (waiter: any): string => {
  const name = getWaiterName(waiter)
  if (name === 'Unknown Waiter') return 'W'
  return name.charAt(0).toUpperCase()
}
</script>

<template>
  <DashboardLayout>
    <div class="min-h-screen bg-gradient-to-br from-slate-50 via-blue-50/20 to-slate-100/80">
      <div class="max-w-[1440px] mx-auto px-4 sm:px-6 lg:px-8 py-8">

        <!-- Toast Notification -->
        <Teleport to="body">
          <transition name="fade">
            <div v-if="toastMessage" class="fixed bottom-6 right-6 z-[10000] bg-slate-900 text-white px-5 py-3 rounded-xl shadow-2xl flex items-center gap-3 border border-slate-700">
              <CheckCircle2 class="w-5 h-5 text-emerald-400" />
              <span class="text-sm font-medium">{{ toastMessage }}</span>
            </div>
          </transition>
        </Teleport>

        <!-- Header Section -->
        <div class="mb-8">
          <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
            <div class="flex items-center gap-4">
              <div class="w-14 h-14 bg-gradient-to-br from-blue-600 via-indigo-600 to-purple-600 rounded-2xl flex items-center justify-center shadow-lg shadow-blue-500/20">
                <Users class="w-7 h-7 text-white" />
              </div>
              <div>
                <h1 class="text-3xl md:text-4xl font-extrabold text-slate-900 tracking-tight">Table Assignments</h1>
                <p class="text-slate-600 mt-1 text-sm md:text-base font-medium">Manage waiter-table assignments for optimal restaurant floor operations</p>
              </div>
            </div>
            <button
              type="button"
              @click="showAssignModal = true"
              class="inline-flex items-center justify-center gap-2.5 px-6 py-3.5 bg-gradient-to-r from-blue-600 via-indigo-600 to-blue-700 hover:from-blue-700 hover:to-indigo-800 text-white font-semibold rounded-xl shadow-lg shadow-blue-500/25 hover:shadow-xl hover:shadow-blue-500/35 transition-all duration-200 transform hover:-translate-y-0.5 active:translate-y-0"
            >
              <Plus class="w-5 h-5" />
              <span>Assign Waiter to Table</span>
            </button>
          </div>
        </div>

        <!-- Statistics Cards -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 sm:gap-6 mb-6">
          <!-- Total Assignments Card -->
          <div class="bg-white dark:bg-slate-900 rounded-2xl p-5 shadow-xs hover:shadow-md transition-all duration-300 border border-slate-200 dark:border-slate-800 group">
            <div class="flex items-start justify-between mb-4">
              <div class="flex-1">
                <p class="text-xs font-bold text-slate-400 dark:text-slate-500 uppercase tracking-wider mb-1">Total Assignments</p>
                <p class="text-3xl font-black text-slate-900 dark:text-white mb-1 tracking-tight">{{ tableAssignmentStore.stats.total_assignments }}</p>
                <div class="flex items-center gap-1 text-emerald-600 dark:text-emerald-400 text-xs font-bold">
                  <TrendingUp class="w-3.5 h-3.5" />
                  <span>Active Today</span>
                </div>
              </div>
              <div class="w-12 h-12 bg-blue-500/10 text-blue-600 dark:text-blue-400 rounded-xl flex items-center justify-center group-hover:scale-105 transition-transform">
                <Users class="w-6 h-6" />
              </div>
            </div>
            <div class="h-1 bg-gradient-to-r from-blue-500 to-indigo-600 rounded-full"></div>
          </div>

          <!-- Tables Covered Card -->
          <div class="bg-white dark:bg-slate-900 rounded-2xl p-5 shadow-xs hover:shadow-md transition-all duration-300 border border-slate-200 dark:border-slate-800 group">
            <div class="flex items-start justify-between mb-4">
              <div class="flex-1">
                <p class="text-xs font-bold text-slate-400 dark:text-slate-500 uppercase tracking-wider mb-1">Tables Covered</p>
                <p class="text-3xl font-black text-slate-900 dark:text-white mb-1 tracking-tight">{{ tableAssignmentStore.stats.total_tables }}</p>
                <div class="flex items-center gap-1 text-purple-600 dark:text-purple-400 text-xs font-bold">
                  <CheckCircle2 class="w-3.5 h-3.5" />
                  <span>Assigned</span>
                </div>
              </div>
              <div class="w-12 h-12 bg-purple-500/10 text-purple-600 dark:text-purple-400 rounded-xl flex items-center justify-center group-hover:scale-105 transition-transform">
                <MapPin class="w-6 h-6" />
              </div>
            </div>
            <div class="h-1 bg-gradient-to-r from-purple-500 to-purple-600 rounded-full"></div>
          </div>

          <!-- Waiters Assigned Card -->
          <div class="bg-white dark:bg-slate-900 rounded-2xl p-5 shadow-xs hover:shadow-md transition-all duration-300 border border-slate-200 dark:border-slate-800 group">
            <div class="flex items-start justify-between mb-4">
              <div class="flex-1">
                <p class="text-xs font-bold text-slate-400 dark:text-slate-500 uppercase tracking-wider mb-1">Waiters Assigned</p>
                <p class="text-3xl font-black text-slate-900 dark:text-white mb-1 tracking-tight">{{ tableAssignmentStore.stats.total_waiters }}</p>
                <div class="flex items-center gap-1 text-emerald-600 dark:text-emerald-400 text-xs font-bold">
                  <Users class="w-3.5 h-3.5" />
                  <span>On Duty</span>
                </div>
              </div>
              <div class="w-12 h-12 bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 rounded-xl flex items-center justify-center group-hover:scale-105 transition-transform">
                <Users class="w-6 h-6" />
              </div>
            </div>
            <div class="h-1 bg-gradient-to-r from-emerald-500 to-emerald-600 rounded-full"></div>
          </div>

          <!-- Primary Assignments Card -->
          <div class="bg-white dark:bg-slate-900 rounded-2xl p-5 shadow-xs hover:shadow-md transition-all duration-300 border border-slate-200 dark:border-slate-800 group">
            <div class="flex items-start justify-between mb-4">
              <div class="flex-1">
                <p class="text-xs font-bold text-slate-400 dark:text-slate-500 uppercase tracking-wider mb-1">Primary Assignments</p>
                <p class="text-3xl font-black text-slate-900 dark:text-white mb-1 tracking-tight">{{ tableAssignmentStore.stats.primary_assignments }}</p>
                <div class="flex items-center gap-1 text-amber-600 dark:text-amber-400 text-xs font-bold">
                  <Award class="w-3.5 h-3.5" />
                  <span>High Priority</span>
                </div>
              </div>
              <div class="w-12 h-12 bg-amber-500/10 text-amber-600 dark:text-amber-400 rounded-xl flex items-center justify-center group-hover:scale-105 transition-transform">
                <Award class="w-6 h-6" />
              </div>
            </div>
            <div class="h-1 bg-gradient-to-r from-amber-500 to-amber-600 rounded-full"></div>
          </div>
        </div>

        <!-- Filters Bar Section -->
        <div class="bg-white dark:bg-slate-900 rounded-2xl p-4 shadow-xs border border-slate-200 dark:border-slate-800 mb-6">
          <div class="flex flex-col lg:flex-row items-stretch lg:items-center justify-between gap-4">
            
            <!-- Search Input -->
            <div class="relative flex-1 min-w-[240px]">
              <div class="absolute inset-y-0 left-3.5 flex items-center pointer-events-none text-slate-400">
                <Search class="w-4 h-4" />
              </div>
              <input
                type="text"
                v-model="searchQuery"
                placeholder="Search by waiter, table, priority..."
                class="w-full pl-10 pr-4 py-2 bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-xl text-xs font-medium text-slate-900 dark:text-white focus:outline-none focus:border-blue-500 transition placeholder:text-slate-400"
              />
            </div>

            <div class="flex flex-wrap items-center gap-3">
              <!-- Priority Filter -->
              <select
                v-model="filterPriority"
                @change="handleFilter"
                class="px-3 py-2 bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-xl text-xs font-bold text-slate-900 dark:text-white focus:outline-none focus:border-blue-500 cursor-pointer"
              >
                <option value="">All Priorities</option>
                <option value="primary">Primary</option>
                <option value="secondary">Secondary</option>
                <option value="backup">Backup</option>
              </select>

              <!-- Date Filter -->
              <div class="relative min-w-[140px]">
                <input
                  type="date"
                  v-model="filterDate"
                  @change="handleFilter"
                  class="w-full px-3 py-2 bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-xl text-xs font-bold text-slate-900 dark:text-white focus:outline-none focus:border-blue-500 transition"
                />
              </div>

              <!-- Filter Action Buttons -->
              <button
                type="button"
                @click="handleFilter"
                class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white font-bold rounded-xl text-xs transition shadow-sm cursor-pointer"
              >
                Apply
              </button>

              <button
                type="button"
                @click="handleResetFilters"
                class="px-3.5 py-2 border border-slate-200 dark:border-slate-800 hover:bg-slate-100 dark:hover:bg-slate-800 text-slate-700 dark:text-slate-300 font-bold rounded-xl text-xs transition cursor-pointer"
                title="Reset Filters"
              >
                Reset
              </button>

              <button
                type="button"
                @click="handleRefresh"
                :disabled="tableAssignmentStore.loading"
                class="p-2 border border-slate-200 dark:border-slate-800 hover:bg-slate-100 dark:hover:bg-slate-800 text-slate-700 dark:text-slate-300 rounded-xl transition disabled:opacity-50 cursor-pointer"
                title="Refresh Table"
              >
                <RefreshCw class="w-4 h-4 text-slate-600 dark:text-slate-400" :class="{ 'animate-spin': tableAssignmentStore.loading }" />
              </button>
            </div>
          </div>
        </div>

        <!-- Assignments Table Card -->
        <div class="bg-white dark:bg-slate-900 rounded-2xl shadow-xs border border-slate-200 dark:border-slate-800 overflow-hidden">
          
          <!-- Table Header Banner -->
          <div class="bg-slate-900 dark:bg-slate-950 px-6 py-4 flex items-center justify-between border-b border-slate-800">
            <div class="flex items-center gap-2.5">
              <MapPin class="w-5 h-5 text-blue-400" />
              <h3 class="text-base font-extrabold text-white tracking-wide">Current Table Assignments</h3>
            </div>
            <span v-if="filteredAssignments.length" class="px-3 py-1 bg-slate-800 text-slate-300 text-xs font-bold rounded-full border border-slate-700">
              {{ filteredAssignments.length }} {{ filteredAssignments.length === 1 ? 'Record' : 'Records' }}
            </span>
          </div>

          <!-- Column Headers -->
          <div v-if="filteredAssignments.length > 0 && !tableAssignmentStore.loading" class="bg-slate-50 dark:bg-slate-950/80 border-b border-slate-200 dark:border-slate-800 px-6 py-3.5 hidden md:block">
            <div class="grid grid-cols-12 gap-4 items-center text-[10px] font-black text-slate-500 dark:text-slate-400 uppercase tracking-wider">
              <div class="col-span-2">Table</div>
              <div class="col-span-3">Assigned Waiter</div>
              <div class="col-span-3">Shift & Schedule</div>
              <div class="col-span-2">Priority</div>
              <div class="col-span-1">Status</div>
              <div class="col-span-1 text-right">Actions</div>
            </div>
          </div>

          <!-- Loading State -->
          <div v-if="tableAssignmentStore.loading" class="px-6 py-16">
            <div class="flex flex-col items-center justify-center gap-3">
              <Loader2 class="w-10 h-10 text-blue-600 animate-spin" />
              <p class="text-slate-800 font-semibold text-base">Loading table assignments...</p>
              <p class="text-slate-400 text-xs">Fetching current schedule and waiters</p>
            </div>
          </div>

          <!-- Empty State -->
          <div v-else-if="filteredAssignments.length === 0" class="px-6 py-16">
            <div class="flex flex-col items-center justify-center text-center max-w-md mx-auto">
              <div class="w-16 h-16 bg-slate-100 rounded-full flex items-center justify-center mb-4">
                <AlertCircle class="w-8 h-8 text-slate-400" />
              </div>
              <h4 class="text-slate-900 font-bold text-lg mb-1">No table assignments found</h4>
              <p class="text-slate-500 text-sm mb-6">No waiter assignments match your current filters or date range.</p>
              <button
                type="button"
                @click="showAssignModal = true"
                class="inline-flex items-center gap-2 px-5 py-2.5 bg-blue-600 hover:bg-blue-700 text-white font-semibold rounded-xl shadow transition"
              >
                <Plus class="w-4 h-4" />
                Assign Waiter Now
              </button>
            </div>
          </div>

          <!-- Assignments Rows -->
          <div v-else class="divide-y divide-slate-100 dark:divide-slate-800/60">
            <div
              v-for="assignment in paginatedAssignments"
              :key="assignment.id"
              class="px-6 py-4.5 hover:bg-slate-50/80 dark:hover:bg-slate-800/40 transition duration-150 relative"
            >
              <div class="grid grid-cols-1 md:grid-cols-12 gap-4 items-center">
                
                <!-- Table Column -->
                <div class="md:col-span-2 flex items-center gap-3">
                  <div class="w-11 h-11 bg-gradient-to-br from-indigo-500 to-purple-600 rounded-xl flex items-center justify-center text-white font-black text-base shadow-sm shrink-0">
                    {{ assignment.table?.table_number || 'T' }}
                  </div>
                  <div>
                    <p class="font-bold text-slate-900 dark:text-white text-sm">{{ assignment.table?.table_name || 'Table ' + assignment.table?.table_number }}</p>
                    <p class="text-xs text-slate-500 dark:text-slate-400 font-medium">{{ assignment.table?.section || assignment.table?.location || 'Main Floor' }}</p>
                  </div>
                </div>

                <!-- Waiter Column -->
                <div class="md:col-span-3 flex items-center gap-3">
                  <div class="w-9 h-9 bg-blue-600 text-white rounded-full flex items-center justify-center font-black text-xs shadow-sm shrink-0">
                    {{ getWaiterInitial(assignment.waiter) }}
                  </div>
                  <div class="overflow-hidden">
                    <p class="font-extrabold text-slate-900 dark:text-white text-sm truncate">{{ getWaiterName(assignment.waiter) }}</p>
                    <p class="text-xs text-slate-500 dark:text-slate-400 capitalize truncate font-medium">{{ assignment.waiter?.experience_level || 'Junior Waiter' }}</p>
                  </div>
                </div>

                <!-- Shift Column -->
                <div class="md:col-span-3">
                  <div class="flex items-center gap-2 text-slate-700 dark:text-slate-300">
                    <Clock class="w-4 h-4 text-slate-400 shrink-0" />
                    <div>
                      <p class="font-bold text-xs text-slate-900 dark:text-white">{{ assignment.shift?.name || 'Shift' }}</p>
                      <p class="text-[11px] text-slate-500 dark:text-slate-400 font-medium">{{ formatTime(assignment.shift?.start_time) }} - {{ formatTime(assignment.shift?.end_time) }}</p>
                    </div>
                  </div>
                </div>

                <!-- Priority Column -->
                <div class="md:col-span-2">
                  <span :class="[
                    'inline-flex items-center gap-1.5 px-3 py-1 text-xs font-black rounded-xl border uppercase tracking-wider',
                    getPriorityColor(assignment.priority)
                  ]">
                    <Award class="w-3.5 h-3.5" />
                    {{ assignment.priority }}
                  </span>
                </div>

                <!-- Status Column -->
                <div class="md:col-span-1">
                  <span :class="[
                    'inline-flex items-center gap-1.5 px-2.5 py-1 text-xs font-black rounded-xl border capitalize',
                    getStatusColor(assignment.status)
                  ]">
                    <span class="w-2 h-2 rounded-full" :class="assignment.status === 'active' ? 'bg-emerald-500 animate-pulse' : 'bg-slate-400'"></span>
                    {{ assignment.status }}
                  </span>
                </div>

                <!-- Actions Column: Three Dots Button & Context Menu -->
                <div class="md:col-span-1 flex items-center justify-end relative">
                  <button
                    type="button"
                    @click.stop="toggleMenu(assignment.id)"
                    class="p-2 text-slate-500 hover:text-slate-900 dark:hover:text-white hover:bg-slate-200/60 dark:hover:bg-slate-800 rounded-xl transition focus:outline-none border border-slate-200/80 dark:border-slate-700 cursor-pointer"
                    title="Actions"
                  >
                    <MoreVertical class="w-5 h-5" />
                  </button>

                  <!-- Dropdown Menu -->
                  <div
                    v-if="activeMenuId === assignment.id"
                    class="absolute right-0 top-11 z-30 w-48 bg-white dark:bg-slate-900 rounded-xl shadow-2xl border border-slate-200 dark:border-slate-800 py-1.5 animate-scale-in"
                    @click.stop
                  >
                    <button
                      type="button"
                      @click="openEditModal(assignment)"
                      class="w-full px-4 py-2.5 text-left text-xs font-bold text-slate-700 dark:text-slate-200 hover:bg-blue-50 dark:hover:bg-blue-600/20 hover:text-blue-600 dark:hover:text-blue-400 flex items-center gap-2.5 transition cursor-pointer"
                    >
                      <Edit3 class="w-4 h-4 text-blue-600" />
                      <span>Edit Assignment</span>
                    </button>

                    <div class="my-1 border-t border-slate-100 dark:border-slate-800"></div>

                    <button
                      type="button"
                      @click="confirmDelete(assignment)"
                      class="w-full px-4 py-2.5 text-left text-xs font-bold text-rose-600 hover:bg-rose-50 dark:hover:bg-rose-600/20 flex items-center gap-2.5 transition cursor-pointer"
                    >
                      <Trash2 class="w-4 h-4 text-rose-600" />
                      <span>Delete Assignment</span>
                    </button>
                  </div>
                </div>

              </div>
            </div>

            <!-- Pagination Bar with 5, 10, 20, 50 Options -->
            <div
              v-if="filteredAssignments.length > 0"
              class="flex flex-col sm:flex-row items-center justify-between gap-4 border-t border-slate-200 dark:border-slate-800 bg-slate-50/80 dark:bg-slate-950 p-4 text-xs font-sans"
            >
              <!-- Left Side: Per Page Selector & Showing Count -->
              <div class="flex flex-wrap items-center gap-4 text-slate-600 dark:text-slate-400">
                <div class="flex items-center gap-2">
                  <span class="font-bold text-slate-700 dark:text-slate-300">Items per page:</span>
                  <select
                    :value="perPage"
                    @change="changePerPage"
                    class="px-2.5 py-1.5 rounded-xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 text-slate-900 dark:text-white font-black focus:outline-none focus:border-amber-500 cursor-pointer shadow-2xs"
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
                  <span class="font-extrabold text-slate-900 dark:text-white">{{ totalRecords }}</span> assignments
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
                        ? 'bg-amber-500 border-amber-500 text-slate-950 font-black shadow-md shadow-amber-500/20'
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

        </div>

        <!-- Modals -->
        <AssignWaiterToTableModal
          :is-open="showAssignModal"
          @close="showAssignModal = false"
          @assigned="handleAssigned"
        />

        <EditTableAssignmentModal
          :is-open="showEditModal"
          :assignment="selectedEditAssignment"
          @close="showEditModal = false"
          @updated="handleUpdated"
        />

        <!-- Delete Confirmation Modal -->
        <Teleport to="body">
          <transition name="modal">
            <div v-if="showDeleteConfirm" class="fixed inset-0 z-[9999] flex items-center justify-center p-4">
              <div class="absolute inset-0 bg-slate-900/60 backdrop-blur-sm" @click="showDeleteConfirm = false"></div>
              
              <div class="relative bg-white rounded-2xl shadow-2xl max-w-md w-full p-6 animate-scale-in border border-slate-100">
                <div class="w-12 h-12 bg-red-100 rounded-2xl flex items-center justify-center mx-auto mb-4 text-red-600">
                  <AlertCircle class="w-6 h-6" />
                </div>
                
                <h3 class="text-xl font-bold text-slate-900 text-center mb-2">Delete Assignment?</h3>
                <p class="text-slate-600 text-center text-sm mb-6">Are you sure you want to delete this table assignment? This action will unassign the waiter from the table.</p>
                
                <div class="flex gap-3">
                  <button
                    type="button"
                    @click="showDeleteConfirm = false"
                    class="flex-1 px-4 py-2.5 border-2 border-slate-200 rounded-xl text-slate-700 text-sm font-semibold hover:bg-slate-50 transition"
                  >
                    Cancel
                  </button>
                  <button
                    type="button"
                    @click="handleDelete"
                    class="flex-1 px-4 py-2.5 bg-red-600 hover:bg-red-700 text-white text-sm font-semibold rounded-xl transition shadow"
                  >
                    Delete Assignment
                  </button>
                </div>
              </div>
            </div>
          </transition>
        </Teleport>

      </div>
    </div>
  </DashboardLayout>
</template>

<style scoped>
.modal-enter-active, .modal-leave-active {
  transition: opacity 0.25s ease;
}

.modal-enter-from, .modal-leave-to {
  opacity: 0;
}

.fade-enter-active, .fade-leave-active {
  transition: opacity 0.3s ease;
}

.fade-enter-from, .fade-leave-to {
  opacity: 0;
}

.animate-scale-in {
  animation: scale-in 0.2s cubic-bezier(0.16, 1, 0.3, 1);
}

@keyframes scale-in {
  from {
    transform: scale(0.95);
    opacity: 0;
  }
  to {
    transform: scale(1);
    opacity: 1;
  }
}
</style>

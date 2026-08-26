<script setup lang="ts">
import { onMounted, ref, computed, watch } from 'vue'
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
  Trash2
} from 'lucide-vue-next'

const router = useRouter()
const assignmentStore = useFloorAssignmentStore()

const isLoading = ref(false)
const isSaving = ref(false)
const hasChanges = ref(false)
const allFloors = ref<any[]>([])

// Modal state
const showAddStaffModal = ref(false)
const selectedFloorForModal = ref<{ id: string; name: string } | null>(null)

// Pagination State
const currentPage = ref(1)
const perPage = ref(10)

const totalFloors = computed(() => allFloors.value.length)
const lastPage = computed(() => Math.ceil(totalFloors.value / perPage.value) || 1)

const paginatedFloors = computed(() => {
  const start = (currentPage.value - 1) * perPage.value
  const end = start + perPage.value
  return allFloors.value.slice(start, end)
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

// Computed stats
const stats = computed(() => ({
  total_waiters: assignmentStore.stats?.total_waiters || 0,
  assigned: assignmentStore.stats?.total_assignments || 0,
  on_break: 0,
  open_slots: Math.max(0, (assignmentStore.stats?.total_waiters || 0) - (assignmentStore.stats?.total_assignments || 0))
}))

// Load data on mount
const loadData = async () => {
  isLoading.value = true
  try {
    try {
      const floorsResponse = await floorManagementService.getFloors({ is_active: true })
      allFloors.value = Array.isArray(floorsResponse.data) ? floorsResponse.data : floorsResponse
    } catch (err) {
      console.error('[FloorAssignment] Failed to load floors:', err)
      allFloors.value = []
    }

    try {
      await assignmentStore.fetchTodayAssignments()
    } catch (err) {
      console.warn('[FloorAssignment] Failed to load today assignments:', err)
    }

    try {
      await assignmentStore.fetchStats()
    } catch (err) {
      console.warn('[FloorAssignment] Failed to load stats:', err)
    }
  } catch (err) {
    console.error('[FloorAssignment] Failed to load data:', err)
  } finally {
    isLoading.value = false
  }
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
      priority: a.priority
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
    <div class="space-y-6 bg-slate-50 dark:bg-slate-950 min-h-screen p-4 sm:p-6 max-w-full overflow-hidden font-sans">
      <!-- Header Banner -->
      <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 p-6 rounded-3xl shadow-xs flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
          <h1 class="text-2xl font-black text-slate-900 dark:text-white">Floor Staff Assignments</h1>
          <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">Allocate primary and support staff across all floor zones and dining areas.</p>
        </div>

        <div class="flex flex-wrap items-center gap-2.5">
          <button
            @click="refreshData"
            class="px-3.5 py-2 rounded-xl bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 dark:hover:bg-slate-700 text-slate-800 dark:text-slate-200 text-xs font-bold transition flex items-center gap-1.5 cursor-pointer border border-slate-200 dark:border-slate-700"
          >
            <Clock class="w-3.5 h-3.5" />
            <span>History</span>
          </button>

          <router-link
            to="/manager/add-floor"
            class="px-3.5 py-2 rounded-xl bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 dark:hover:bg-slate-700 text-blue-600 dark:text-blue-400 text-xs font-bold transition flex items-center gap-1.5 cursor-pointer border border-slate-200 dark:border-slate-700"
          >
            <Plus class="w-3.5 h-3.5 stroke-[3]" />
            <span>Add Floor</span>
          </router-link>

          <button
            @click="saveAssignments"
            :disabled="isSaving || !hasChanges"
            class="px-4 py-2 rounded-xl bg-blue-600 hover:bg-blue-700 text-white font-extrabold text-xs shadow-md shadow-blue-600/20 transition flex items-center gap-1.5 cursor-pointer disabled:opacity-40 disabled:cursor-not-allowed"
          >
            <Save class="w-3.5 h-3.5" />
            <span>{{ isSaving ? 'Saving...' : 'Save Assignments' }}</span>
          </button>
        </div>
      </div>

      <!-- Error Alert -->
      <div v-if="assignmentStore.error" class="p-4 rounded-2xl bg-rose-500/10 border border-rose-500/30 text-rose-700 dark:text-rose-300 text-xs font-bold flex items-center justify-between">
        <div class="flex items-center gap-2.5">
          <AlertCircle class="w-4 h-4 text-rose-500 flex-shrink-0" />
          <span>{{ assignmentStore.error }}</span>
        </div>
        <button @click="assignmentStore.clearError" class="text-rose-500 hover:text-rose-700 cursor-pointer">×</button>
      </div>

      <!-- Success Alert -->
      <div v-if="assignmentStore.successMessage" class="p-4 rounded-2xl bg-emerald-500/10 border border-emerald-500/30 text-emerald-700 dark:text-emerald-300 text-xs font-bold flex items-center gap-2.5">
        <CheckCircle2 class="w-4 h-4 text-emerald-500 flex-shrink-0" />
        <span>{{ assignmentStore.successMessage }}</span>
      </div>

      <!-- Loading State -->
      <div v-if="isLoading" class="bg-white dark:bg-slate-900 rounded-3xl border border-slate-200 dark:border-slate-800 p-16 text-center space-y-3">
        <Loader2 class="w-8 h-8 text-amber-500 animate-spin mx-auto" />
        <p class="text-xs font-bold text-slate-500 dark:text-slate-400">Loading floor assignments table...</p>
      </div>

      <!-- Floors Data Table -->
      <div v-else class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-3xl overflow-hidden shadow-xs">
        <div class="overflow-x-auto w-full">
          <table class="w-full text-left border-collapse">
            <thead>
              <tr class="bg-slate-50 dark:bg-slate-950/80 border-b border-slate-200 dark:border-slate-800 text-[10px] font-black uppercase text-slate-500 dark:text-slate-400 tracking-wider">
                <th class="px-4 py-3 whitespace-nowrap">Floor Zone</th>
                <th class="px-4 py-3 text-center whitespace-nowrap">Status</th>
                <th class="px-4 py-3 whitespace-nowrap">Assigned Waiters & Staff</th>
                <th class="px-4 py-3 text-right whitespace-nowrap pr-6">Actions</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-100 dark:divide-slate-800/60 text-xs">
              <tr v-if="allFloors.length === 0">
                <td colspan="4" class="p-12 text-center text-slate-500 dark:text-slate-400 text-xs font-bold">
                  No floors found. Click "Add Floor" to create your first floor zone.
                </td>
              </tr>

              <tr
                v-for="floor in paginatedFloors"
                :key="floor.id"
                class="hover:bg-slate-50/70 dark:hover:bg-slate-800/40 transition"
              >
                <!-- Floor Zone -->
                <td class="px-4 py-3.5 whitespace-nowrap">
                  <div class="flex items-center gap-3">
                    <div class="w-9 h-9 rounded-2xl bg-blue-500/10 text-blue-600 dark:text-blue-400 border border-blue-500/20 font-black text-xs flex items-center justify-center flex-shrink-0">
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
                <td class="px-4 py-3.5 text-center whitespace-nowrap">
                  <span
                    :class="[
                      'inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-[10px] font-black uppercase tracking-wider border',
                      floor.is_active
                        ? 'bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 border-emerald-500/20'
                        : 'bg-rose-500/10 text-rose-600 dark:text-rose-400 border-rose-500/20'
                    ]"
                  >
                    <span class="w-1.5 h-1.5 rounded-full" :class="floor.is_active ? 'bg-emerald-500' : 'bg-rose-500'"></span>
                    {{ floor.is_active ? 'Active' : 'Inactive' }}
                  </span>
                </td>

                <!-- Assigned Staff List -->
                <td class="px-4 py-3.5">
                  <div v-if="assignmentStore.groupedByFloor[floor.id]?.length" class="flex flex-wrap items-center gap-2">
                    <div
                      v-for="assignment in assignmentStore.groupedByFloor[floor.id]"
                      :key="assignment.id"
                      class="inline-flex items-center gap-2 px-3 py-1.5 rounded-xl bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800"
                    >
                      <div class="w-6 h-6 rounded-full bg-amber-500/10 text-amber-600 dark:bg-amber-500/20 dark:text-amber-400 border border-amber-500/20 font-black text-[10px] flex items-center justify-center flex-shrink-0">
                        {{ (assignment.waiter?.user?.name || assignment.waiter?.name || 'W')?.[0]?.toUpperCase() }}
                      </div>
                      <div class="text-xs">
                        <span class="font-extrabold text-slate-900 dark:text-white mr-1">
                          {{ assignment.waiter?.user?.name || assignment.waiter?.name || 'Unassigned' }}
                        </span>
                        <span class="text-[10px] text-slate-500 dark:text-slate-400 font-medium">
                          ({{ assignment.shift?.name || 'General' }})
                        </span>
                      </div>
                      <span
                        :class="[
                          'px-1.5 py-0.2 rounded text-[9px] font-black uppercase border ml-1',
                          assignment.priority === 'primary' && 'bg-blue-500/10 text-blue-600 dark:text-blue-400 border-blue-500/20',
                          assignment.priority === 'secondary' && 'bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 border-emerald-500/20',
                          assignment.priority === 'backup' && 'bg-amber-500/10 text-amber-600 dark:text-amber-400 border-amber-500/20'
                        ]"
                      >
                        {{ assignment.priority }}
                      </span>
                    </div>
                  </div>
                  <span v-else class="inline-flex items-center gap-1.5 px-3 py-1 rounded-xl bg-amber-500/10 text-amber-700 dark:text-amber-300 border border-amber-500/20 font-bold text-xs">
                    No staff assigned yet
                  </span>
                </td>

                <!-- Actions -->
                <td class="px-4 py-3.5 text-right whitespace-nowrap pr-6">
                  <button
                    @click="() => {
                      selectedFloorForModal = { id: floor.id, name: floor.name }
                      showAddStaffModal = true
                    }"
                    class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl bg-blue-600 hover:bg-blue-700 text-white font-bold text-xs shadow-md shadow-blue-600/20 transition cursor-pointer"
                  >
                    <UserPlus class="w-3.5 h-3.5" />
                    <span>+ Add Staff</span>
                  </button>
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <!-- Pagination Bar with 5, 10, 20, 50 Options -->
        <div
          v-if="allFloors.length > 0"
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
              <span class="font-extrabold text-slate-900 dark:text-white">{{ totalFloors }}</span> floors
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

      <!-- Bottom Stats Summary Bar -->
      <div v-if="allFloors.length > 0" class="grid grid-cols-2 md:grid-cols-4 gap-4">
        <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-3xl p-4 flex items-center gap-3.5 shadow-xs">
          <div class="w-10 h-10 rounded-2xl bg-blue-500/10 text-blue-600 dark:text-blue-400 flex items-center justify-center font-bold">
            <Users class="w-5 h-5" />
          </div>
          <div>
            <p class="text-[10px] font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Total Waiters</p>
            <p class="text-xl font-black text-slate-900 dark:text-white">{{ stats.total_waiters }}</p>
          </div>
        </div>

        <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-3xl p-4 flex items-center gap-3.5 shadow-xs">
          <div class="w-10 h-10 rounded-2xl bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 flex items-center justify-center font-bold">
            <CheckCircle2 class="w-5 h-5" />
          </div>
          <div>
            <p class="text-[10px] font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Assigned</p>
            <p class="text-xl font-black text-emerald-600 dark:text-emerald-400">{{ stats.assigned }}</p>
          </div>
        </div>

        <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-3xl p-4 flex items-center gap-3.5 shadow-xs">
          <div class="w-10 h-10 rounded-2xl bg-blue-500/10 text-blue-600 dark:text-blue-400 flex items-center justify-center font-bold">
            <Clock class="w-5 h-5" />
          </div>
          <div>
            <p class="text-[10px] font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider">On Break</p>
            <p class="text-xl font-black text-blue-600 dark:text-blue-400">{{ stats.on_break }}</p>
          </div>
        </div>

        <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-3xl p-4 flex items-center gap-3.5 shadow-xs">
          <div class="w-10 h-10 rounded-2xl bg-rose-500/10 text-rose-600 dark:text-rose-400 flex items-center justify-center font-bold">
            <AlertCircle class="w-5 h-5" />
          </div>
          <div>
            <p class="text-[10px] font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Open Slots</p>
            <p class="text-xl font-black text-rose-600 dark:text-rose-400">{{ stats.open_slots }}</p>
          </div>
        </div>
      </div>
    </div>

    <!-- Add Staff Modal -->
    <AddStaffToFloorModal
      :is-open="showAddStaffModal"
      :floor-id="selectedFloorForModal?.id || ''"
      :floor-name="selectedFloorForModal?.name || ''"
      @close="showAddStaffModal = false"
      @assigned="hasChanges = true"
    />
  </DashboardLayout>
</template>

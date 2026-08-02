<script setup lang="ts">
import { onMounted, ref, computed } from 'vue'
import { useRouter } from 'vue-router'
import DashboardLayout from '../../Layouts/DashboardLayout.vue'
import AddStaffToFloorModal from '@/components/manager/AddStaffToFloorModal.vue'
import { useFloorAssignmentStore } from '@/stores/manager/floorAssignmentStore'
import floorManagementService from '@/services/manager/floorManagementService'
import floorAssignmentService from '@/services/manager/floorAssignmentService'
import { Hotel, Save, Clock, Plus } from 'lucide-vue-next'

const router = useRouter()
const assignmentStore = useFloorAssignmentStore()

const isLoading = ref(false)
const isSaving = ref(false)
const hasChanges = ref(false)
const allFloors = ref<any[]>([])

// Modal state
const showAddStaffModal = ref(false)
const selectedFloorForModal = ref<{ id: string; name: string } | null>(null)

const todayDate = computed(() => {
  return new Date().toLocaleDateString('en-US', { weekday: 'long', month: 'short', day: 'numeric' })
})

// Computed stats
const stats = computed(() => ({
  total_waiters: assignmentStore.stats?.total_waiters || 0,
  assigned: assignmentStore.stats?.total_assignments || 0,
  on_break: 0,
  open_slots: (assignmentStore.stats?.total_waiters || 0) - (assignmentStore.stats?.total_assignments || 0)
}))

// Load data on mount
const loadData = async () => {
  isLoading.value = true
  try {
    // First load all floors from floor management API
    try {
      const floorsResponse = await floorManagementService.getFloors({ is_active: true })
      allFloors.value = Array.isArray(floorsResponse.data) ? floorsResponse.data : floorsResponse
      console.log('[FloorAssignment] Loaded', allFloors.value.length, 'floors')
    } catch (err) {
      console.error('[FloorAssignment] Failed to load floors:', err)
      allFloors.value = []
    }

    // Then ALWAYS load today's assignments from database
    try {
      console.log('[FloorAssignment] Fetching today assignments from API...')
      await assignmentStore.fetchTodayAssignments()
      console.log('[FloorAssignment] Assignments loaded:', assignmentStore.assignments.length)
    } catch (err) {
      console.warn('[FloorAssignment] Failed to load today assignments:', err)
      // This is OK - we show all floors even if no assignments exist yet
    }

    // Try to fetch stats
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
  if (!hasChanges.value) {
    return
  }

  isSaving.value = true
  try {
    // Gather all assignments from the store
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
    <div class="min-h-screen bg-gradient-to-br from-slate-50 via-white to-blue-50/30 px-6 py-6">
      <!-- HEADER -->
      <div class="flex items-center justify-between mb-8">
        <div>
          <h1 class="text-4xl font-bold text-slate-900">Assign Staff to Floors</h1>
          <p class="text-slate-600 mt-2">Optimize hospitality coverage by allocating primary and support staff across all zones.</p>
        </div>
      </div>

      <!-- ERROR ALERT -->
      <div v-if="assignmentStore.error" class="mb-8 bg-red-50 border border-red-200 rounded-xl p-4 flex items-start gap-3">
        <span class="text-2xl">✕</span>
        <div class="flex-1">
          <p class="text-sm font-medium text-red-900">{{ assignmentStore.error }}</p>
        </div>
        <button @click="assignmentStore.clearError" class="text-red-600 hover:text-red-700">×</button>
      </div>

      <!-- SUCCESS ALERT -->
      <div v-if="assignmentStore.successMessage" class="mb-8 bg-emerald-50 border border-emerald-200 rounded-xl p-4 flex items-start gap-3">
        <span class="text-2xl">✓</span>
        <div class="flex-1">
          <p class="text-sm font-medium text-emerald-900">{{ assignmentStore.successMessage }}</p>
        </div>
      </div>

      <!-- ACTION BUTTONS -->
      <div class="flex items-center gap-3 mb-8">
        <button @click="refreshData" class="flex items-center gap-2 px-4 py-2 bg-white border border-slate-300 rounded-lg text-slate-700 hover:bg-slate-50 font-medium transition">
          <Clock class="w-4 h-4" />
          Recent History
        </button>
        <router-link to="/manager/add-floor" class="flex items-center gap-2 px-6 py-2 bg-white border border-slate-300 rounded-lg text-slate-700 hover:bg-slate-50 font-medium transition">
          <Plus class="w-4 h-4" />
          Add New Floor
        </router-link>
        <button @click="saveAssignments" :disabled="isSaving || !hasChanges" class="flex items-center gap-2 px-6 py-2 bg-gradient-to-r from-blue-600 to-blue-700 hover:from-blue-700 hover:to-blue-800 text-white font-semibold rounded-lg transition shadow-lg shadow-blue-500/30 ml-auto disabled:opacity-50">
          <Save class="w-4 h-4" />
          {{ isSaving ? 'Saving...' : 'Save Assignments' }}
        </button>
      </div>

      <!-- LOADING STATE -->
      <div v-if="isLoading" class="flex justify-center items-center py-32">
        <div class="text-center">
          <div class="relative w-12 h-12 mx-auto mb-4">
            <!-- Static background - BRIGHT CYAN -->
            <svg class="absolute inset-0 w-full h-full" viewBox="0 0 100 100">
              <circle cx="50" cy="50" r="45" fill="none" stroke="#0EA5E9" stroke-width="6" opacity="0.3" />
            </svg>
            
            <!-- Animated spinner - BRIGHT YELLOW -->
            <div class="absolute inset-0 animate-spin" style="animation: spin 1.5s linear infinite;">
              <svg viewBox="0 0 100 100" class="w-full h-full">
                <circle cx="50" cy="50" r="45" fill="none" stroke="#FBBF24" stroke-width="8" stroke-linecap="round" stroke-dasharray="70 280" />
              </svg>
            </div>
          </div>
          <p class="text-slate-700 dark:text-yellow-300 font-semibold text-sm">Loading assignments...</p>
        </div>
      </div>

      <!-- FLOORS GRID -->
      <div v-if="!isLoading" class="space-y-8">
        <!-- No Floors State -->
        <div v-if="allFloors.length === 0" class="bg-white rounded-xl border border-slate-200 p-12 text-center">
          <p class="text-slate-600 text-lg mb-4">No floors found</p>
          <p class="text-slate-500 mb-6">Start by adding new floors to your hospitality suite</p>
          <router-link to="/manager/add-floor" class="inline-flex items-center gap-2 px-6 py-3 bg-blue-600 hover:bg-blue-700 text-white font-semibold rounded-lg">
            <Plus class="w-4 h-4" />
            Create First Floor
          </router-link>
        </div>

        <!-- Floor Cards (3-column grid) -->
        <div v-else class="grid grid-cols-1 lg:grid-cols-3 gap-6">
          <div
            v-for="floor in allFloors"
            :key="floor.id"
            class="bg-white rounded-xl border border-slate-200 shadow-sm p-6 hover:shadow-md transition"
          >
            <!-- Floor Header -->
            <div class="flex items-center justify-between mb-6 pb-4 border-b border-slate-200">
              <div class="flex items-center gap-3">
                <div class="w-10 h-10 bg-blue-100 rounded-lg flex items-center justify-center">
                  <Hotel class="w-6 h-6 text-blue-600" />
                </div>
                <div>
                  <h3 class="font-bold text-lg text-slate-900">{{ floor.name }}</h3>
                  <p class="text-xs text-blue-600 font-semibold uppercase">Floor #{{ floor.floor_number }}</p>
                </div>
              </div>
              <span class="px-2 py-1 bg-blue-100 text-blue-700 text-xs font-bold rounded-full">{{ floor.is_active ? 'ACTIVE' : 'INACTIVE' }}</span>
            </div>

            <!-- Assignments List -->
            <div class="space-y-3">
              <!-- Show assignments if they exist for this floor -->
              <template v-if="assignmentStore.groupedByFloor[floor.id]?.length">
                <div
                  v-for="assignment in assignmentStore.groupedByFloor[floor.id]"
                  :key="assignment.id"
                  class="p-4 bg-gradient-to-br from-blue-50 to-indigo-50 rounded-lg border-2 border-blue-200 hover:shadow-md transition"
                >
                  <div class="flex items-start justify-between mb-3">
                    <div class="flex items-center gap-3 flex-1">
                      <div class="w-10 h-10 rounded-full bg-gradient-to-br from-blue-500 to-indigo-600 flex items-center justify-center text-white font-bold text-sm flex-shrink-0">
                        {{ (assignment.waiter.user?.name || 'U')?.charAt(0).toUpperCase() }}
                      </div>
                      <div>
                        <p class="font-bold text-slate-900">{{ assignment.waiter.user?.name || 'Unassigned' }}</p>
                        <p class="text-xs text-slate-600">{{ assignment.shift?.name || 'N/A' }} Shift</p>
                      </div>
                    </div>
                    <span
                      :class="[
                        'px-3 py-1 text-xs font-bold rounded-full whitespace-nowrap flex-shrink-0',
                        assignment.priority === 'primary' && 'bg-blue-600 text-white',
                        assignment.priority === 'secondary' && 'bg-emerald-600 text-white',
                        assignment.priority === 'backup' && 'bg-amber-600 text-white'
                      ]"
                    >
                      {{ assignment.priority.toUpperCase() }}
                    </span>
                  </div>
                  <div class="grid grid-cols-2 gap-2 text-xs">
                    <div class="bg-white rounded px-2 py-1">
                      <p class="text-slate-500">Employment</p>
                      <p class="font-semibold text-slate-900">{{ assignment.waiter.employment_type?.replace('_', ' ') || 'N/A' }}</p>
                    </div>
                    <div class="bg-white rounded px-2 py-1">
                      <p class="text-slate-500">Status</p>
                      <p :class="['font-semibold', assignment.waiter.status === 'active' ? 'text-emerald-600' : 'text-slate-600']">
                        {{ assignment.waiter.status || 'N/A' }}
                      </p>
                    </div>
                  </div>
                </div>
              </template>

              <!-- No assignments message -->
              <div v-else class="p-4 bg-yellow-50 rounded-lg border-2 border-yellow-200 text-center">
                <p class="text-sm font-bold text-yellow-800">No staff assigned yet</p>
                <p class="text-xs text-yellow-700 mt-1">Click "Add Staff" to assign waiters to this floor</p>
              </div>

              <!-- Add Staff Button -->
              <button
                @click="() => {
                  selectedFloorForModal = { id: floor.id, name: floor.name }
                  showAddStaffModal = true
                }"
                class="w-full mt-3 px-4 py-3 border-2 border-dashed border-blue-300 text-blue-600 hover:border-blue-500 hover:bg-blue-50 rounded-lg text-sm font-bold transition uppercase tracking-wide"
              >
                + Add Staff
              </button>
            </div>
          </div>
        </div>

        <!-- BOTTOM STATS -->
        <div v-if="allFloors.length > 0" class="grid grid-cols-1 md:grid-cols-4 gap-4 mt-8 pt-8 border-t border-slate-200">
          <div class="flex items-center gap-4">
            <div class="w-12 h-12 bg-blue-100 rounded-lg flex items-center justify-center">
              <span class="text-xl">👥</span>
            </div>
            <div>
              <p class="text-xs text-slate-600 font-semibold">Total Waiters</p>
              <p class="text-2xl font-bold text-slate-900">{{ stats.total_waiters }}</p>
            </div>
          </div>

          <div class="flex items-center gap-4">
            <div class="w-12 h-12 bg-emerald-100 rounded-lg flex items-center justify-center">
              <span class="text-xl">✓</span>
            </div>
            <div>
              <p class="text-xs text-slate-600 font-semibold">Assigned</p>
              <p class="text-2xl font-bold text-emerald-600">{{ stats.assigned }}</p>
            </div>
          </div>

          <div class="flex items-center gap-4">
            <div class="w-12 h-12 bg-blue-100 rounded-lg flex items-center justify-center">
              <span class="text-xl">⏸</span>
            </div>
            <div>
              <p class="text-xs text-slate-600 font-semibold">On Break</p>
              <p class="text-2xl font-bold text-blue-600">{{ stats.on_break }}</p>
            </div>
          </div>

          <div class="flex items-center gap-4">
            <div class="w-12 h-12 bg-red-100 rounded-lg flex items-center justify-center">
              <span class="text-xl">!</span>
            </div>
            <div>
              <p class="text-xs text-slate-600 font-semibold">Open Slots</p>
              <p class="text-2xl font-bold text-red-600">{{ stats.open_slots }}</p>
            </div>
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

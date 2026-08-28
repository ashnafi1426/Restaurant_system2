<script setup lang="ts">
import { ref, onMounted, computed, watch } from 'vue'
import {
  X,
  Loader2,
  CheckCircle2,
  User,
  Clock,
  Award,
  Users,
  UtensilsCrossed,
  AlertCircle,
  Sparkles,
} from 'lucide-vue-next'
import api from '@/api/auth'
import { useTableAssignmentStore } from '@/stores/manager/tableAssignmentStore'

interface Props {
  isOpen?: boolean
}

interface Emits {
  (e: 'close'): void
  (e: 'assigned'): void
  (e: 'success'): void
}

const props = withDefaults(defineProps<Props>(), {
  isOpen: true,
})

const emit = defineEmits<Emits>()

const tableAssignmentStore = useTableAssignmentStore()

const tables = ref<any[]>([])
const waiters = ref<any[]>([])
const shifts = ref<any[]>([])
const selectedTable = ref<string>('')
const selectedWaiter = ref<number | string>('')
const selectedShift = ref<string>('')
const selectedPriority = ref<'primary' | 'secondary' | 'backup'>('primary')
const assignmentDate = ref(new Date().toISOString().split('T')[0])

const isLoading = ref(false)
const isSubmitting = ref(false)
const error = ref<string | null>(null)
const successMessage = ref<string | null>(null)

const priorities = [
  { value: 'primary', label: 'Primary', desc: 'Main responsible waiter' },
  { value: 'secondary', label: 'Secondary', desc: 'Backup support waiter' },
  { value: 'backup', label: 'Backup', desc: 'On-demand coverage' },
]

const isFormValid = computed(() => {
  return selectedTable.value && selectedWaiter.value && selectedShift.value
})

const selectedTableData = computed(() => {
  return tables.value.find((t) => t.id === selectedTable.value) || null
})

const selectedWaiterData = computed(() => {
  return (
    waiters.value.find(
      (w) => w.id === Number(selectedWaiter.value) || String(w.id) === String(selectedWaiter.value)
    ) || null
  )
})

// Format time from ISO string or HH:MM:SS to readable format
const formatTime = (timeString?: string): string => {
  if (!timeString) return ''
  try {
    if (timeString.includes('T')) {
      const date = new Date(timeString)
      return date.toLocaleTimeString('en-US', {
        hour: '2-digit',
        minute: '2-digit',
        hour12: true,
      })
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
  } catch {
    return timeString || ''
  }
}

// Load Tables from Backend
const loadTables = async () => {
  try {
    const response = await api.get('/manager/restaurant-tables', { params: { per_page: 100 } })
    let list: any[] = []
    if (Array.isArray(response.data?.data)) {
      list = response.data.data
    } else if (Array.isArray(response.data?.data?.data)) {
      list = response.data.data.data
    } else if (Array.isArray(response.data)) {
      list = response.data
    }
    tables.value = list.filter((t) => t.is_active !== false)
  } catch (err: any) {
    console.error('[AssignModal] Error loading tables:', err)
  }
}

// Load Waiters from Backend
const loadWaiters = async () => {
  try {
    const response = await api.get('/manager/waiters')
    let list: any[] = []
    if (Array.isArray(response.data?.data)) {
      list = response.data.data
    } else if (Array.isArray(response.data)) {
      list = response.data
    }
    waiters.value = list
  } catch (err: any) {
    console.error('[AssignModal] Error loading waiters:', err)
  }
}

// Load Shifts from Backend
const loadShifts = async () => {
  try {
    const response = await api.get('/manager/shifts')
    let list: any[] = []
    if (Array.isArray(response.data?.data)) {
      list = response.data.data
    } else if (Array.isArray(response.data)) {
      list = response.data
    }
    if (list.length > 0) {
      shifts.value = list
      if (!selectedShift.value) {
        selectedShift.value = list[0].id
      }
    } else {
      // Fallback default shifts
      shifts.value = [
        { id: 'morning-shift', name: 'Morning', start_time: '06:00', end_time: '14:00' },
        { id: 'afternoon-shift', name: 'Afternoon', start_time: '14:00', end_time: '22:00' },
        { id: 'evening-shift', name: 'Evening', start_time: '17:00', end_time: '23:00' },
        { id: 'night-shift', name: 'Night', start_time: '22:00', end_time: '06:00' },
      ]
      selectedShift.value = shifts.value[0].id
    }
  } catch (err: any) {
    console.warn('[AssignModal] Could not fetch shifts, using default shifts:', err)
    shifts.value = [
      { id: 'morning-shift', name: 'Morning', start_time: '06:00', end_time: '14:00' },
      { id: 'afternoon-shift', name: 'Afternoon', start_time: '14:00', end_time: '22:00' },
      { id: 'evening-shift', name: 'Evening', start_time: '17:00', end_time: '23:00' },
      { id: 'night-shift', name: 'Night', start_time: '22:00', end_time: '06:00' },
    ]
    selectedShift.value = shifts.value[0].id
  }
}

const loadInitialData = async () => {
  isLoading.value = true
  error.value = null
  try {
    await Promise.all([loadTables(), loadWaiters(), loadShifts()])
  } finally {
    isLoading.value = false
  }
}

const handleSubmit = async () => {
  if (!isFormValid.value) {
    error.value = 'Please select a table, waiter, and shift.'
    return
  }

  isSubmitting.value = true
  error.value = null
  successMessage.value = null

  try {
    const assignmentPayload = [
      {
        table_id: selectedTable.value,
        waiter_id: Number(selectedWaiter.value),
        shift_id: selectedShift.value,
        assignment_date: assignmentDate.value,
        priority: selectedPriority.value,
      },
    ]

    if (tableAssignmentStore.assignWaitersToTables) {
      await tableAssignmentStore.assignWaitersToTables(assignmentPayload)
    } else if (tableAssignmentStore.assignWaiters) {
      await tableAssignmentStore.assignWaiters(assignmentPayload)
    }
    successMessage.value = 'Waiter successfully assigned to table!'

    emit('assigned')
    emit('success')

    setTimeout(() => {
      handleClose()
    }, 1000)
  } catch (err: any) {
    error.value =
      err.response?.data?.message ||
      err.response?.data?.error ||
      err.message ||
      'Failed to assign waiter to table. Please verify selection.'
  } finally {
    isSubmitting.value = false
  }
}

const handleClose = () => {
  selectedTable.value = ''
  selectedWaiter.value = ''
  error.value = null
  successMessage.value = null
  emit('close')
}

onMounted(() => {
  loadInitialData()
})
</script>

<template>
  <Teleport to="body">
    <div
      v-if="isOpen"
      class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/60 backdrop-blur-xs font-sans"
      @click.self="handleClose"
    >
      <div
        class="relative bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-3xl shadow-2xl max-w-xl w-full max-h-[90vh] overflow-hidden flex flex-col transition-all"
      >
        <!-- Modal Header -->
        <div class="flex items-center justify-between px-6 py-5 border-b border-slate-200 dark:border-slate-800 flex-shrink-0">
          <div class="flex items-center gap-3">
            <div class="w-10 h-10 rounded-2xl bg-blue-500/10 text-blue-600 dark:text-blue-400 flex items-center justify-center border border-blue-500/20">
              <UtensilsCrossed class="w-5 h-5" />
            </div>
            <div>
              <h2 class="text-lg font-black text-slate-900 dark:text-white">Assign Table to Waiter</h2>
              <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">Designate waitstaff coverage for dining tables</p>
            </div>
          </div>

          <button
            type="button"
            @click="handleClose"
            class="p-2 text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 hover:bg-slate-100 dark:hover:bg-slate-800 rounded-xl transition cursor-pointer"
          >
            <X class="w-5 h-5" />
          </button>
        </div>

        <!-- Modal Body / Content -->
        <div class="p-6 overflow-y-auto space-y-5 flex-1">
          <!-- Loading State -->
          <div v-if="isLoading" class="py-12 text-center space-y-3">
            <Loader2 class="w-8 h-8 text-blue-600 animate-spin mx-auto" />
            <p class="text-xs font-bold text-slate-500">Loading tables & staff roster...</p>
          </div>

          <template v-else>
            <!-- Error Alert -->
            <div
              v-if="error"
              class="p-3.5 bg-rose-500/10 border border-rose-500/20 text-rose-600 dark:text-rose-400 rounded-2xl text-xs font-bold flex items-center gap-2.5"
            >
              <AlertCircle class="w-4 h-4 flex-shrink-0" />
              <span>{{ error }}</span>
            </div>

            <!-- Success Alert -->
            <div
              v-if="successMessage"
              class="p-3.5 bg-emerald-500/10 border border-emerald-500/20 text-emerald-600 dark:text-emerald-400 rounded-2xl text-xs font-bold flex items-center gap-2.5"
            >
              <CheckCircle2 class="w-4 h-4 flex-shrink-0" />
              <span>{{ successMessage }}</span>
            </div>

            <!-- Select Table -->
            <div>
              <label class="block text-xs font-black text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-2">
                Select Restaurant Table *
              </label>
              <select
                v-model="selectedTable"
                class="w-full rounded-xl border border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-950 text-slate-900 dark:text-white px-3.5 py-2.5 text-xs sm:text-sm font-medium outline-none focus:ring-2 focus:ring-blue-500/40 focus:border-blue-500 transition cursor-pointer"
              >
                <option value="" disabled>Choose a table...</option>
                <option v-for="table in tables" :key="table.id" :value="table.id">
                  Table {{ table.table_number }} {{ table.table_name ? `(${table.table_name})` : '' }} — {{ table.capacity }} Seats
                </option>
              </select>
            </div>

            <!-- Select Waiter -->
            <div>
              <label class="block text-xs font-black text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-2">
                Assigned Waitstaff *
              </label>
              <select
                v-model="selectedWaiter"
                class="w-full rounded-xl border border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-950 text-slate-900 dark:text-white px-3.5 py-2.5 text-xs sm:text-sm font-medium outline-none focus:ring-2 focus:ring-blue-500/40 focus:border-blue-500 transition cursor-pointer"
              >
                <option value="" disabled>Choose a waiter...</option>
                <option v-for="waiter in waiters" :key="waiter.id" :value="waiter.id">
                  {{ waiter.name || waiter.user?.name || waiter.user?.first_name || `Waiter #${waiter.id}` }} {{ waiter.section ? `(${waiter.section})` : '' }}
                </option>
              </select>
            </div>

            <!-- Grid: Shift & Date -->
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
              <!-- Shift -->
              <div>
                <label class="block text-xs font-black text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-2">
                  Shift Schedule *
                </label>
                <select
                  v-model="selectedShift"
                  class="w-full rounded-xl border border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-950 text-slate-900 dark:text-white px-3.5 py-2.5 text-xs sm:text-sm font-medium outline-none focus:ring-2 focus:ring-blue-500/40 focus:border-blue-500 transition cursor-pointer"
                >
                  <option v-for="shift in shifts" :key="shift.id" :value="shift.id">
                    {{ shift.name }} ({{ formatTime(shift.start_time) }} - {{ formatTime(shift.end_time) }})
                  </option>
                </select>
              </div>

              <!-- Date -->
              <div>
                <label class="block text-xs font-black text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-2">
                  Assignment Date *
                </label>
                <input
                  v-model="assignmentDate"
                  type="date"
                  class="w-full rounded-xl border border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-950 text-slate-900 dark:text-white px-3.5 py-2.5 text-xs sm:text-sm font-medium outline-none focus:ring-2 focus:ring-blue-500/40 focus:border-blue-500 transition"
                />
              </div>
            </div>

            <!-- Priority -->
            <div>
              <label class="block text-xs font-black text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-2">
                Coverage Priority
              </label>
              <div class="grid grid-cols-3 gap-2.5">
                <button
                  v-for="p in priorities"
                  :key="p.value"
                  type="button"
                  @click="selectedPriority = p.value as any"
                  class="p-3 rounded-2xl border text-center transition cursor-pointer"
                  :class="[
                    selectedPriority === p.value
                      ? 'border-blue-600 bg-blue-500/10 text-blue-600 dark:text-blue-400 font-black'
                      : 'border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-950 text-slate-600 dark:text-slate-400 font-semibold'
                  ]"
                >
                  <div class="text-xs capitalize font-bold">{{ p.label }}</div>
                  <div class="text-[10px] text-slate-400 dark:text-slate-500 mt-0.5">{{ p.desc }}</div>
                </button>
              </div>
            </div>
          </template>
        </div>

        <!-- Modal Footer -->
        <div class="flex items-center justify-end gap-3 px-6 py-4 border-t border-slate-200 dark:border-slate-800 bg-slate-50/50 dark:bg-slate-950/50 flex-shrink-0">
          <button
            type="button"
            @click="handleClose"
            class="px-4 py-2.5 border border-slate-200 dark:border-slate-800 text-slate-700 dark:text-slate-300 rounded-xl font-bold text-xs hover:bg-slate-100 dark:hover:bg-slate-800 transition cursor-pointer"
          >
            Cancel
          </button>
          <button
            type="button"
            @click="handleSubmit"
            :disabled="!isFormValid || isSubmitting"
            class="px-5 py-2.5 bg-blue-600 hover:bg-blue-700 dark:bg-[#0066FF] dark:hover:bg-[#0055DD] text-white rounded-xl font-bold text-xs shadow-md shadow-blue-600/20 transition cursor-pointer flex items-center gap-2 disabled:opacity-40 disabled:cursor-not-allowed"
          >
            <Loader2 v-if="isSubmitting" class="w-4 h-4 animate-spin" />
            <span>{{ isSubmitting ? 'Assigning...' : 'Confirm Assignment' }}</span>
          </button>
        </div>
      </div>
    </div>
  </Teleport>
</template>

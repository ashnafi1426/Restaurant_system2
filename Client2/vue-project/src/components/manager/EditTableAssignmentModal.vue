<script setup lang="ts">
import { ref, watch, computed } from 'vue'
import { X, Loader2, CheckCircle2, User, Clock, Award, MapPin, Users, Activity, Edit3 } from 'lucide-vue-next'
import api from '@/api/auth'
import { useTableAssignmentStore } from '@/stores/manager/tableAssignmentStore'

interface Props {
  isOpen: boolean
  assignment: any
}

interface Emits {
  (e: 'close'): void
  (e: 'updated'): void
}

const props = defineProps<Props>()
const emit = defineEmits<Emits>()

const tableAssignmentStore = useTableAssignmentStore()

const tables = ref<any[]>([])
const waiters = ref<any[]>([])
const shifts = ref<any[]>([])

const selectedTable = ref<string>('')
const selectedWaiter = ref<number | string>('')
const selectedShift = ref<string>('')
const selectedPriority = ref<'primary' | 'secondary' | 'backup'>('primary')
const selectedStatus = ref<'active' | 'inactive' | 'completed'>('active')

const isLoading = ref(false)
const isSubmitting = ref(false)
const error = ref<string | null>(null)
const successMessage = ref<string | null>(null)

const priorities = ['primary', 'secondary', 'backup'] as const
const statuses = [
  { value: 'active', label: 'Active', color: 'emerald' },
  { value: 'inactive', label: 'Inactive', color: 'slate' },
  { value: 'completed', label: 'Completed', color: 'purple' },
] as const

const isFormValid = computed(() => {
  return selectedTable.value && selectedWaiter.value && selectedShift.value
})

const selectedTableData = computed(() => {
  return tables.value.find(t => t.id === selectedTable.value) || null
})

const selectedWaiterData = computed(() => {
  return waiters.value.find(w => w.id === Number(selectedWaiter.value) || String(w.id) === String(selectedWaiter.value)) || null
})

// Load Tables from Backend
const loadTables = async () => {
  try {
    const response = await api.get('/manager/restaurant-tables')
    let tableData = []
    if (response.data?.data) {
      if (response.data.data.data && Array.isArray(response.data.data.data)) {
        tableData = response.data.data.data
      } else if (Array.isArray(response.data.data)) {
        tableData = response.data.data
      } else if (response.data.data.items && Array.isArray(response.data.data.items)) {
        tableData = response.data.data.items
      }
    } else if (Array.isArray(response.data)) {
      tableData = response.data
    }
    tables.value = tableData
  } catch (err: any) {
    console.error('Failed to load tables:', err)
  }
}

// Load Waiters from Backend
const loadWaiters = async () => {
  try {
    const response = await api.get('/manager/waiters')
    const data = response.data.data || response.data
    if (Array.isArray(data)) {
      waiters.value = data
    }
  } catch (err: any) {
    console.error('Failed to load waiters:', err)
  }
}

// Load Shifts from Backend
const loadShifts = async () => {
  try {
    const response = await api.get('/manager/shifts', { params: { status: 'active' } })
    const data = response.data.data || response.data
    if (Array.isArray(data)) {
      shifts.value = data
    }
  } catch (err: any) {
    console.error('Failed to load shifts:', err)
  }
}

const populateForm = () => {
  if (!props.assignment) return
  selectedTable.value = props.assignment.table_id || props.assignment.table?.id || ''
  selectedWaiter.value = props.assignment.waiter_id || props.assignment.waiter?.id || ''
  selectedShift.value = props.assignment.shift_id || props.assignment.shift?.id || ''
  selectedPriority.value = props.assignment.priority || 'primary'
  selectedStatus.value = props.assignment.status || 'active'
}

watch(
  () => props.isOpen,
  async (newVal) => {
    if (newVal) {
      error.value = null
      successMessage.value = null
      isLoading.value = true
      await Promise.all([loadTables(), loadWaiters(), loadShifts()])
      populateForm()
      isLoading.value = false
    }
  },
  { immediate: true }
)

watch(
  () => props.assignment,
  () => {
    if (props.isOpen) {
      populateForm()
    }
  }
)

const handleUpdate = async () => {
  if (!props.assignment || !isFormValid.value) return

  isSubmitting.value = true
  error.value = null

  try {
    const payload = {
      waiter_id: Number(selectedWaiter.value),
      table_id: selectedTable.value,
      shift_id: selectedShift.value,
      priority: selectedPriority.value,
      status: selectedStatus.value,
    }

    await tableAssignmentStore.updateAssignment(props.assignment.id, payload)
    
    successMessage.value = 'Table assignment updated successfully!'
    emit('updated')

    setTimeout(() => {
      handleClose()
    }, 1000)
  } catch (err: any) {
    error.value = err.response?.data?.message || err.message || 'Failed to update table assignment'
  } finally {
    isSubmitting.value = false
  }
}

const handleClose = () => {
  error.value = null
  successMessage.value = null
  emit('close')
}

const formatTime = (timeString: string): string => {
  if (!timeString) return ''
  try {
    if (timeString.includes('T')) {
      return new Date(timeString).toLocaleTimeString('en-US', {
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
    return timeString
  }
}
</script>

<template>
  <Teleport to="body">
    <transition name="modal">
      <div v-if="isOpen" class="fixed inset-0 z-[9999] flex items-center justify-center p-4">
        <!-- Backdrop -->
        <div 
          class="absolute inset-0 bg-slate-900/60 backdrop-blur-sm transition-opacity"
          @click="handleClose"
        ></div>

        <!-- Modal Content -->
        <div class="relative bg-white rounded-2xl shadow-2xl max-w-2xl w-full max-h-[90vh] overflow-y-auto border border-slate-100 animate-scale-in">
          <!-- Header -->
          <div class="flex items-center justify-between p-6 border-b border-slate-100 sticky top-0 bg-white/95 backdrop-blur-md rounded-t-2xl z-10">
            <div class="flex items-center gap-3">
              <div class="w-10 h-10 bg-blue-50 text-blue-600 rounded-xl flex items-center justify-center font-semibold">
                <Edit3 class="w-5 h-5" />
              </div>
              <div>
                <h2 class="text-xl font-bold text-slate-900">Edit Table Assignment</h2>
                <p class="text-xs text-slate-500 mt-0.5">Update waiter, table, shift, priority, or status</p>
              </div>
            </div>
            <button
              type="button"
              @click="handleClose"
              class="text-slate-400 hover:text-slate-600 transition p-1.5 hover:bg-slate-100 rounded-lg"
            >
              <X class="w-5 h-5" />
            </button>
          </div>

          <!-- Body -->
          <div class="p-6 space-y-5">
            <!-- Success Alert -->
            <transition name="fade">
              <div v-if="successMessage" class="p-4 bg-emerald-50 border border-emerald-200 rounded-xl flex items-center gap-3">
                <CheckCircle2 class="w-5 h-5 text-emerald-600 flex-shrink-0" />
                <p class="text-sm font-medium text-emerald-800">{{ successMessage }}</p>
              </div>
            </transition>

            <!-- Error Alert -->
            <div v-if="error" class="p-4 bg-red-50 border border-red-200 rounded-xl flex items-start gap-3">
              <span class="text-lg">⚠️</span>
              <p class="text-sm text-red-700 font-medium">{{ error }}</p>
            </div>

            <!-- Loading State -->
            <div v-if="isLoading" class="flex justify-center py-12">
              <div class="text-center">
                <Loader2 class="w-8 h-8 text-blue-600 animate-spin mx-auto mb-2" />
                <p class="text-xs text-slate-500 font-medium">Loading assignment details...</p>
              </div>
            </div>

            <!-- Form -->
            <div v-else class="space-y-5">
              <!-- Select Table -->
              <div>
                <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-2">
                  <span class="flex items-center gap-1.5">
                    <MapPin class="w-3.5 h-3.5 text-blue-600" />
                    Restaurant Table
                  </span>
                </label>
                <select
                  v-model="selectedTable"
                  class="w-full px-4 py-2.5 bg-slate-50 border-2 border-slate-200 rounded-xl focus:bg-white focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-sm font-semibold text-slate-800 transition"
                >
                  <option value="" disabled>-- Select Table --</option>
                  <option v-for="t in tables" :key="t.id" :value="t.id">
                    Table {{ t.table_number }} {{ t.table_name ? `(${t.table_name})` : '' }} - {{ t.location || 'Main Floor' }}
                  </option>
                </select>
              </div>

              <!-- Selected Table Card -->
              <div v-if="selectedTableData" class="p-4 bg-gradient-to-r from-purple-50 to-indigo-50 border border-purple-100 rounded-xl flex items-center justify-between">
                <div class="flex items-center gap-3">
                  <div class="w-10 h-10 bg-purple-600 text-white rounded-lg flex items-center justify-center font-bold text-sm shadow">
                    {{ selectedTableData.table_number }}
                  </div>
                  <div>
                    <p class="font-bold text-slate-900 text-sm">{{ selectedTableData.table_name || 'Table ' + selectedTableData.table_number }}</p>
                    <p class="text-xs text-slate-500">Capacity: {{ selectedTableData.capacity || '4' }} Seats • Section: {{ selectedTableData.section || 'General' }}</p>
                  </div>
                </div>
                <span class="px-2.5 py-1 bg-purple-100 text-purple-700 text-xs font-bold rounded-lg uppercase">Table Selected</span>
              </div>

              <!-- Select Waiter -->
              <div>
                <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-2">
                  <span class="flex items-center gap-1.5">
                    <User class="w-3.5 h-3.5 text-blue-600" />
                    Assigned Waiter
                  </span>
                </label>
                <select
                  v-model="selectedWaiter"
                  class="w-full px-4 py-2.5 bg-slate-50 border-2 border-slate-200 rounded-xl focus:bg-white focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-sm font-semibold text-slate-800 transition"
                >
                  <option value="" disabled>-- Select Waiter --</option>
                  <option v-for="w in waiters" :key="w.id" :value="w.id">
                    {{ w.user?.name || `Waiter #${w.id}` }} ({{ w.experience_level || 'Junior' }})
                  </option>
                </select>
              </div>

              <!-- Select Shift -->
              <div>
                <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-2">
                  <span class="flex items-center gap-1.5">
                    <Clock class="w-3.5 h-3.5 text-blue-600" />
                    Shift
                  </span>
                </label>
                <select
                  v-model="selectedShift"
                  class="w-full px-4 py-2.5 bg-slate-50 border-2 border-slate-200 rounded-xl focus:bg-white focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-sm font-semibold text-slate-800 transition"
                >
                  <option value="" disabled>-- Select Shift --</option>
                  <option v-for="s in shifts" :key="s.id" :value="s.id">
                    {{ s.name }} ({{ formatTime(s.start_time) }} - {{ formatTime(s.end_time) }})
                  </option>
                </select>
              </div>

              <!-- Priority & Status Grid -->
              <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <!-- Priority Level -->
                <div>
                  <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-2">
                    <span class="flex items-center gap-1.5">
                      <Award class="w-3.5 h-3.5 text-blue-600" />
                      Priority Level
                    </span>
                  </label>
                  <div class="grid grid-cols-3 gap-2">
                    <button
                      v-for="p in priorities"
                      :key="p"
                      type="button"
                      @click="selectedPriority = p"
                      :class="[
                        'py-2 px-1 text-xs font-bold rounded-xl border text-center transition capitalize',
                        selectedPriority === p
                          ? p === 'primary'
                            ? 'bg-blue-600 text-white border-blue-600 shadow-md'
                            : p === 'secondary'
                            ? 'bg-emerald-600 text-white border-emerald-600 shadow-md'
                            : 'bg-amber-600 text-white border-amber-600 shadow-md'
                          : 'bg-slate-50 text-slate-600 border-slate-200 hover:bg-slate-100'
                      ]"
                    >
                      {{ p }}
                    </button>
                  </div>
                </div>

                <!-- Assignment Status -->
                <div>
                  <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-2">
                    <span class="flex items-center gap-1.5">
                      <Activity class="w-3.5 h-3.5 text-blue-600" />
                      Status
                    </span>
                  </label>
                  <div class="grid grid-cols-3 gap-2">
                    <button
                      v-for="st in statuses"
                      :key="st.value"
                      type="button"
                      @click="selectedStatus = st.value"
                      :class="[
                        'py-2 px-1 text-xs font-bold rounded-xl border text-center transition capitalize',
                        selectedStatus === st.value
                          ? st.value === 'active'
                            ? 'bg-emerald-600 text-white border-emerald-600 shadow-md'
                            : st.value === 'completed'
                            ? 'bg-purple-600 text-white border-purple-600 shadow-md'
                            : 'bg-slate-700 text-white border-slate-700 shadow-md'
                          : 'bg-slate-50 text-slate-600 border-slate-200 hover:bg-slate-100'
                      ]"
                    >
                      {{ st.label }}
                    </button>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- Footer -->
          <div class="flex items-center justify-end gap-3 p-6 border-t border-slate-100 bg-slate-50/80 rounded-b-2xl">
            <button
              type="button"
              @click="handleClose"
              class="px-5 py-2.5 border-2 border-slate-200 rounded-xl text-slate-700 text-sm font-semibold hover:bg-white transition"
            >
              Cancel
            </button>
            <button
              type="button"
              @click="handleUpdate"
              :disabled="!isFormValid || isSubmitting"
              class="px-6 py-2.5 bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-700 hover:to-indigo-700 text-white text-sm font-semibold rounded-xl transition flex items-center gap-2 shadow-md hover:shadow-lg disabled:opacity-50"
            >
              <Loader2 v-if="isSubmitting" class="w-4 h-4 animate-spin" />
              <CheckCircle2 v-else class="w-4 h-4" />
              <span>{{ isSubmitting ? 'Saving Changes...' : 'Save Changes' }}</span>
            </button>
          </div>
        </div>
      </div>
    </transition>
  </Teleport>
</template>

<style scoped>
.modal-enter-active, .modal-leave-active {
  transition: opacity 0.25s ease;
}
.modal-enter-from, .modal-leave-to {
  opacity: 0;
}
.animate-scale-in {
  animation: scale-in 0.25s cubic-bezier(0.16, 1, 0.3, 1);
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

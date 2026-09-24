<script setup lang="ts">
import { ref, onMounted, computed, watch } from 'vue'
import { X, Loader2, CheckCircle2, User, Clock, Award, Shield, Mail, Phone, AlertCircle } from 'lucide-vue-next'
import api from '@/api/auth'

interface Props {
  isOpen?: boolean
  floorId: string
  floorName: string
}

interface Emits {
  (e: 'close'): void
  (e: 'assigned'): void
  (e: 'success'): void
}

const props = defineProps<Props>()
const emit = defineEmits<Emits>()

const waiters = ref<any[]>([])
const shifts = ref<any[]>([])
const selectedWaiter = ref<number | string>('')
const selectedShift = ref<string>('')
const selectedPriority = ref<'primary' | 'secondary' | 'backup'>('primary')
const isLoading = ref(false)
const isSubmitting = ref(false)
const error = ref<string | null>(null)
const successMessage = ref<string | null>(null)

const priorities: Array<{ value: 'primary' | 'secondary' | 'backup'; label: string; desc: string }> = [
  { value: 'primary', label: 'PRIMARY', desc: 'Main Lead' },
  { value: 'secondary', label: 'SECONDARY', desc: 'Floor Support' },
  { value: 'backup', label: 'BACKUP', desc: 'On-call Backup' },
]

const isFormValid = computed(() => {
  return selectedWaiter.value && selectedShift.value
})

const selectedWaiterData = computed(() => {
  return waiters.value.find(w => w.id === Number(selectedWaiter.value) || String(w.id) === String(selectedWaiter.value)) || null
})

const loadWaiters = async () => {
  try {
    const response = await api.get('/manager/waiters')
    const data = response.data.data || response.data
    waiters.value = Array.isArray(data) ? data : []
  } catch (err: any) {
    console.error('[AddStaffToFloorModal] Error loading waiters:', err)
    error.value = 'Failed to load waiters: ' + (err.response?.data?.message || err.message)
    waiters.value = []
  }
}

const loadShifts = async () => {
  try {
    const response = await api.get('/manager/shifts', {
      params: { status: 'active' }
    })
    const data = response.data.data || response.data
    shifts.value = Array.isArray(data) ? data : []
  } catch (err: any) {
    console.error('[AddStaffToFloorModal] Error loading shifts:', err)
    shifts.value = []
  }
}

const formatTime = (timeString: string): string => {
  if (!timeString) return ''
  try {
    if (timeString.includes('T')) {
      const date = new Date(timeString)
      return date.toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit', hour12: true })
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
  } catch (err: any) {
    console.error('[AddStaffToFloorModal] Error formatting time:', err)
    return timeString
  }
}

const handleAssign = async () => {
  if (!isFormValid.value) {
    error.value = 'Please select a waiter and shift'
    return
  }

  isSubmitting.value = true
  error.value = null

  try {
    const waiterObj = selectedWaiterData.value
    const shiftObj = shifts.value.find(s => s.id === selectedShift.value)

    if (!waiterObj) {
      error.value = 'Selected waiter was not found'
      isSubmitting.value = false
      return
    }
    if (!shiftObj) {
      error.value = 'Selected shift was not found'
      isSubmitting.value = false
      return
    }

    const waiter_id = Number(waiterObj.id)
    const floorId = String(props.floorId).trim()
    const shiftId = String(selectedShift.value).trim()

    const assignmentData = {
      assignments: [{
        waiter_id: waiter_id,
        floor_id: floorId,
        shift_id: shiftId,
        assignment_date: new Date().toISOString().split('T')[0],
        priority: selectedPriority.value,
      }]
    }

    const response = await api.post('/manager/floors/assignments', assignmentData)

    if (response.data?.errors && response.data.errors.length > 0) {
      error.value = response.data.errors.map((e: any) => e.error || JSON.stringify(e)).join('\n')
      isSubmitting.value = false
      return
    }

    successMessage.value = `${waiterObj.user?.name || 'Staff member'} assigned to ${props.floorName} successfully!`

    selectedWaiter.value = ''
    selectedShift.value = ''
    selectedPriority.value = 'primary'

    emit('assigned')
    emit('success')

    setTimeout(() => {
      handleClose()
    }, 900)
  } catch (err: any) {
    console.error('[AddStaffToFloorModal] Error assigning staff:', err)
    const errorData = err.response?.data
    if (errorData?.errors && Array.isArray(errorData.errors)) {
      error.value = errorData.errors.map((e: any) => `Assignment Error: ${e.error}`).join('\n')
    } else if (errorData?.message) {
      error.value = errorData.message
    } else if (errorData?.error) {
      error.value = errorData.error
    } else {
      error.value = err.message || 'Failed to assign staff'
    }
  } finally {
    isSubmitting.value = false
  }
}

const handleClose = () => {
  selectedWaiter.value = ''
  selectedShift.value = ''
  selectedPriority.value = 'primary'
  error.value = null
  successMessage.value = null
  emit('close')
}

onMounted(() => {
  isLoading.value = true
  Promise.all([loadWaiters(), loadShifts()]).finally(() => {
    isLoading.value = false
  })
})

watch(() => props.isOpen, (newVal) => {
  if (newVal !== false && (waiters.value.length === 0 || shifts.value.length === 0)) {
    isLoading.value = true
    Promise.all([loadWaiters(), loadShifts()]).finally(() => {
      isLoading.value = false
    })
  }
})
</script>

<template>
  <div
    v-if="isOpen !== false"
    class="fixed inset-0 bg-slate-900/60 backdrop-blur-xs flex items-center justify-center z-50 p-3 sm:p-6 overflow-hidden animate-in fade-in duration-200"
    @click.self="handleClose"
  >
    <div
      class="bg-white dark:bg-slate-900 rounded-2xl sm:rounded-3xl shadow-2xl max-w-xl w-full max-h-[88vh] flex flex-col border border-slate-200 dark:border-slate-800 overflow-hidden font-sans"
    >
      <div class="p-5 sm:p-6 border-b border-slate-100 dark:border-slate-800 flex items-center justify-between flex-shrink-0 bg-white dark:bg-slate-900">
        <div class="flex items-center gap-3">
          <div class="w-10 h-10 rounded-2xl bg-blue-500/10 text-blue-600 dark:text-blue-400 border border-blue-500/20 flex items-center justify-center font-bold flex-shrink-0">
            <User class="w-5 h-5" />
          </div>
          <div>
            <h2 class="text-base sm:text-lg font-black text-slate-900 dark:text-white">
              Assign Staff to {{ floorName }}
            </h2>
            <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">
              Select and assign waiter staff to manage floor operations
            </p>
          </div>
        </div>
        <button
          type="button"
          @click="handleClose"
          class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 p-1.5 hover:bg-slate-100 dark:hover:bg-slate-800 rounded-xl transition cursor-pointer"
          title="Close Modal"
        >
          <X class="w-5 h-5" />
        </button>
      </div>

      <div class="p-5 sm:p-6 space-y-5 overflow-y-auto flex-1">
        <div
          v-if="successMessage"
          class="p-4 bg-emerald-500/10 border border-emerald-500/20 text-emerald-700 dark:text-emerald-300 rounded-2xl flex items-center gap-3 text-xs font-bold"
        >
          <CheckCircle2 class="w-4 h-4 text-emerald-500 flex-shrink-0" />
          <p>{{ successMessage }}</p>
        </div>

        <div
          v-if="error"
          class="p-4 bg-rose-500/10 border border-rose-500/20 text-rose-700 dark:text-rose-300 rounded-2xl flex items-start gap-2.5 text-xs font-bold"
        >
          <AlertCircle class="w-4 h-4 text-rose-500 flex-shrink-0 mt-0.5" />
          <p class="whitespace-pre-line">{{ error }}</p>
        </div>

        <div v-if="isLoading" class="flex flex-col items-center justify-center py-12 gap-3">
          <Loader2 class="w-8 h-8 text-blue-600 dark:text-blue-400 animate-spin" />
          <p class="text-xs font-bold text-slate-500 dark:text-slate-400">Loading waiters & shifts...</p>
        </div>

        <div v-else class="space-y-5">
          <div>
            <label class="block text-[11px] font-extrabold uppercase text-slate-500 dark:text-slate-400 mb-2">
              <span class="flex items-center gap-1.5">
                <User class="w-3.5 h-3.5 text-blue-500" />
                Select Waiter <span class="text-rose-500">*</span>
              </span>
            </label>
            <select
              v-model="selectedWaiter"
              :disabled="waiters.length === 0"
              class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-950 text-slate-900 dark:text-white text-xs sm:text-sm font-bold focus:outline-none focus:border-blue-500 dark:focus:border-blue-400 transition cursor-pointer disabled:opacity-50"
            >
              <option value="">
                {{ waiters.length === 0 ? 'No waiters available' : '-- Choose a waiter --' }}
              </option>
              <option
                v-for="waiter in waiters"
                :key="waiter.id"
                :value="waiter.id"
              >
                {{ waiter.user?.name || waiter.name || `Waiter #${waiter.id}` }} ({{ (waiter.status || 'active').replace('_', ' ') }})
              </option>
            </select>
          </div>

          <div
            v-if="selectedWaiterData"
            class="p-4 rounded-2xl bg-blue-500/5 dark:bg-blue-950/20 border border-blue-500/20 space-y-3"
          >
            <div class="flex items-center justify-between">
              <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-full bg-blue-600 text-white font-black text-sm flex items-center justify-center shadow-md">
                  {{ (selectedWaiterData.user?.name || selectedWaiterData.name || 'W').charAt(0).toUpperCase() }}
                </div>
                <div>
                  <h4 class="font-extrabold text-sm text-slate-900 dark:text-white">
                    {{ selectedWaiterData.user?.name || selectedWaiterData.name }}
                  </h4>
                  <div class="flex items-center gap-2 text-[10px] text-slate-500 dark:text-slate-400 font-medium mt-0.5">
                    <span v-if="selectedWaiterData.user?.email || selectedWaiterData.email">{{ selectedWaiterData.user?.email || selectedWaiterData.email }}</span>
                    <span v-if="selectedWaiterData.phone">• {{ selectedWaiterData.phone }}</span>
                  </div>
                </div>
              </div>
              <span class="px-2.5 py-0.5 rounded-full text-[10px] font-black uppercase tracking-wider bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 border border-emerald-500/20">
                {{ selectedWaiterData.status || 'Active' }}
              </span>
            </div>

            <div class="grid grid-cols-2 gap-2 pt-1 border-t border-blue-500/10 text-[11px]">
              <div class="text-slate-600 dark:text-slate-400">
                Section: <span class="font-bold text-slate-900 dark:text-white">{{ selectedWaiterData.section || 'General' }}</span>
              </div>
              <div class="text-slate-600 dark:text-slate-400">
                Type: <span class="font-bold text-slate-900 dark:text-white capitalize">{{ (selectedWaiterData.employment_type || 'Full Time').replace('_', ' ') }}</span>
              </div>
            </div>
          </div>

          <div>
            <label class="block text-[11px] font-extrabold uppercase text-slate-500 dark:text-slate-400 mb-2">
              <span class="flex items-center gap-1.5">
                <Clock class="w-3.5 h-3.5 text-blue-500" />
                Shift Schedule <span class="text-rose-500">*</span>
              </span>
            </label>
            <select
              v-model="selectedShift"
              :disabled="shifts.length === 0"
              class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-950 text-slate-900 dark:text-white text-xs sm:text-sm font-bold focus:outline-none focus:border-blue-500 dark:focus:border-blue-400 transition cursor-pointer disabled:opacity-50"
            >
              <option value="">
                {{ shifts.length === 0 ? 'No shifts available' : '-- Choose a shift --' }}
              </option>
              <option
                v-for="shift in shifts"
                :key="shift.id"
                :value="shift.id"
              >
                {{ shift.name }} ({{ formatTime(shift.start_time) }} - {{ formatTime(shift.end_time) }})
              </option>
            </select>
          </div>

          <div>
            <label class="block text-[11px] font-extrabold uppercase text-slate-500 dark:text-slate-400 mb-2">
              <span class="flex items-center gap-1.5">
                <Award class="w-3.5 h-3.5 text-blue-500" />
                Priority Role
              </span>
            </label>
            <div class="grid grid-cols-3 gap-2 sm:gap-3">
              <label
                v-for="p in priorities"
                :key="p.value"
                class="p-3 rounded-xl border-2 transition cursor-pointer text-center select-none"
                :class="[
                  selectedPriority === p.value
                    ? 'border-blue-600 dark:border-blue-500 bg-blue-500/10 text-blue-600 dark:text-blue-400'
                    : 'border-slate-200 dark:border-slate-800 bg-slate-50/50 dark:bg-slate-950 text-slate-600 dark:text-slate-400 hover:border-slate-300 dark:hover:border-slate-700'
                ]"
              >
                <input
                  type="radio"
                  v-model="selectedPriority"
                  :value="p.value"
                  class="sr-only"
                />
                <div class="font-extrabold text-[11px] uppercase tracking-wider">{{ p.label }}</div>
                <div class="text-[10px] text-slate-400 font-medium mt-0.5">{{ p.desc }}</div>
              </label>
            </div>
          </div>
        </div>
      </div>

      <div class="p-4 sm:p-5 border-t border-slate-100 dark:border-slate-800 bg-slate-50/80 dark:bg-slate-950 flex-shrink-0 flex items-center justify-end gap-3">
        <button
          type="button"
          @click="handleClose"
          class="px-4 py-2.5 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 text-slate-700 dark:text-slate-300 font-bold text-xs hover:bg-slate-100 dark:hover:bg-slate-800 transition cursor-pointer"
        >
          Cancel
        </button>
        <button
          type="button"
          @click="handleAssign"
          :disabled="!isFormValid || isSubmitting"
          class="px-5 py-2.5 rounded-xl bg-blue-600 hover:bg-blue-700 text-white font-black text-xs shadow-md shadow-blue-600/20 transition flex items-center justify-center gap-2 cursor-pointer disabled:opacity-50"
        >
          <Loader2 v-if="isSubmitting" class="w-4 h-4 animate-spin" />
          <CheckCircle2 v-else class="w-4 h-4" />
          <span>{{ isSubmitting ? 'Assigning...' : 'Assign Staff' }}</span>
        </button>
      </div>
    </div>
  </div>
</template>

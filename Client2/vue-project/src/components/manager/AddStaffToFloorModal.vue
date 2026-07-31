<script setup lang="ts">
import { ref, onMounted, computed, watch } from 'vue'
import { X, Loader2, CheckCircle2, User, Clock, Award } from 'lucide-vue-next'
import api from '@/api/auth'

interface Props {
  isOpen: boolean
  floorId: string
  floorName: string
}

interface Emits {
  (e: 'close'): void
  (e: 'assigned'): void
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

const priorities = ['primary', 'secondary', 'backup']

const isFormValid = computed(() => {
  return selectedWaiter.value && selectedShift.value
})

const selectedWaiterData = computed(() => {
  // Find waiter by numeric ID
  return waiters.value.find(w => w.id === Number(selectedWaiter.value) || String(w.id) === String(selectedWaiter.value)) || null
})

// PART 1: Load Waiters from Backend
const loadWaiters = async () => {
  try {
    console.log('[Modal] PART 1: Loading waiters...')
    const response = await api.get('/manager/waiters')
    console.log('[Modal] PART 1: API response:', response.data)
    
    const data = response.data.data || response.data
    
    if (Array.isArray(data)) {
      waiters.value = data
      console.log('[Modal] PART 1:  Loaded', data.length, 'waiters')
      console.log('[Modal] PART 1: Sample waiter:', data[0])
    } else {
      waiters.value = []
      console.warn('[Modal] PART 1: ❌ Data not array:', data)
    }
  } catch (err: any) {
    error.value = 'Failed to load waiters: ' + err.message
    console.error('[Modal] PART 1: ❌ Error:', err)
    waiters.value = []
  }
}

// PART 2: Load Shifts from Backend
const loadShifts = async () => {
  try {
    console.log('[Modal] PART 2: Loading shifts...')
    const response = await api.get('/manager/shifts', {
      params: { status: 'active' }
    })
    console.log('[Modal] PART 2: API response:', response.data)
    
    const data = response.data.data || response.data
    
    if (Array.isArray(data)) {
      shifts.value = data
      console.log('[Modal] PART 2:  Loaded', data.length, 'shifts')
      console.log('[Modal] PART 2: Sample shift:', data[0])
    } else {
      shifts.value = []
      console.warn('[Modal] PART 2: ❌ Data not array:', data)
    }
  } catch (err: any) {
    console.warn('[Modal] PART 2: Warning:', err.message)
    shifts.value = []
  }
}

// PART 3: Send Assignment to Backend
const handleAssign = async () => {
  if (!isFormValid.value) {
    error.value = 'Please select a waiter and shift'
    return
  }

  isSubmitting.value = true
  error.value = null

  try {
    const waiterObj = waiters.value.find(w => w.id === Number(selectedWaiter.value) || String(w.id) === String(selectedWaiter.value))
    const shiftObj = shifts.value.find(s => s.id === selectedShift.value)
    
    if (!waiterObj) {
      error.value = `Waiter not found (looking for ID: ${selectedWaiter.value})`
      console.error('[Modal] PART 3: ❌ Waiter lookup failed', {
        selectedId: selectedWaiter.value,
        availableWaiters: waiters.value.map(w => ({ id: w.id, idType: typeof w.id, name: w.user?.name }))
      })
      isSubmitting.value = false
      return
    }
    if (!shiftObj) {
      error.value = 'Shift not found'
      console.error('[Modal] PART 3: ❌ Shift lookup failed', {
        selectedId: selectedShift.value,
        availableShifts: shifts.value.map(s => ({ id: s.id, idType: typeof s.id, name: s.name }))
      })
      isSubmitting.value = false
      return
    }
    
    console.log('[Modal] PART 3: Building assignment data...')
    console.log('[Modal] PART 3: Waiter ID:', waiterObj.id, 'Type:', typeof waiterObj.id)
    console.log('[Modal] PART 3: Waiter Object:', waiterObj)
    console.log('[Modal] PART 3: Shift ID:', selectedShift.value, 'Type:', typeof selectedShift.value)
    console.log('[Modal] PART 3: Shift Object:', shiftObj)
    
    //  ENSURE WAITER_ID IS A NUMBER (NOT STRING)
    const waiter_id = Number(waiterObj.id)
    
    if (!Number.isInteger(waiter_id) || waiter_id <= 0) {
      error.value = `Invalid waiter ID format: ${waiter_id} (must be positive integer)`
      console.error('[Modal] PART 3: ❌ Invalid waiter_id type', { waiter_id, type: typeof waiter_id, isInteger: Number.isInteger(waiter_id) })
      isSubmitting.value = false
      return
    }
    
    //  VERIFY FLOOR_ID AND SHIFT_ID ARE UUIDS
    const floorId = String(props.floorId).trim()
    const shiftId = String(selectedShift.value).trim()
    
    if (!floorId || !shiftId) {
      error.value = 'Floor ID or Shift ID is missing'
      console.error('[Modal] PART 3: ❌ Missing IDs', { floorId, shiftId })
      isSubmitting.value = false
      return
    }
    
    const assignmentData = {
      assignments: [{
        waiter_id: waiter_id,  //  NUMBER
        floor_id: floorId,      //  UUID STRING
        shift_id: shiftId,      //  UUID STRING
        assignment_date: new Date().toISOString().split('T')[0],
        priority: selectedPriority.value,
      }]
    }
    
    console.log('[Modal] PART 3:  Assignment payload:', JSON.stringify(assignmentData, null, 2))
    console.log('[Modal] PART 3: Payload validation:', {
      waiter_id_is_number: typeof assignmentData.assignments[0].waiter_id === 'number',
      floor_id_is_string: typeof assignmentData.assignments[0].floor_id === 'string',
      shift_id_is_string: typeof assignmentData.assignments[0].shift_id === 'string',
      date_is_string: typeof assignmentData.assignments[0].assignment_date === 'string',
      priority_valid: ['primary', 'secondary', 'backup'].includes(assignmentData.assignments[0].priority)
    })
    console.log('[Modal] PART 3: Sending to POST /manager/floors/assignments')
    
    // PART 4: Send to Backend
    const response = await api.post('/manager/floors/assignments', assignmentData)
    console.log('[Modal] PART 3:  API Response:', response.data)
    
    // Check if there were errors in the response
    if (response.data.errors && response.data.errors.length > 0) {
      const errorDetails = response.data.errors
        .map((e: any) => `Error: ${e.error || JSON.stringify(e)}`)
        .join('\n')
      error.value = errorDetails
      console.error('[Modal] PART 3: Response has errors:', response.data.errors)
      isSubmitting.value = false
      return
    }
    
    if (response.status === 201 || response.status === 200) {
      successMessage.value = `${waiterObj.user?.name || 'Staff'} assigned successfully!`
      console.log('[Modal] PART 3:  Success! Record saved to database')
      
      // Clear form
      selectedWaiter.value = ''
      selectedShift.value = ''
      selectedPriority.value = 'primary'
      
      emit('assigned')
      
      // Clear success message
      setTimeout(() => {
        successMessage.value = null
      }, 3000)
    }
  } catch (err: any) {
    console.error('[Modal] PART 3: ❌ API Error:', err)
    
    // Check for errors in response
    const errorData = err.response?.data
    
    // Display detailed error message
    if (errorData?.errors && Array.isArray(errorData.errors)) {
      const errorDetails = errorData.errors
        .map((e: any) => `Assignment Error: ${e.error}`)
        .join('\n')
      error.value = errorDetails
    } else if (errorData?.error) {
      error.value = errorData.error
    } else if (err.response?.data?.errors) {
      const errors = err.response.data.errors
      const errorMessages = Object.entries(errors)
        .map(([key, msgs]: [string, any]) => {
          if (Array.isArray(msgs)) {
            return `${key}: ${msgs.join(', ')}`
          }
          return `${key}: ${String(msgs)}`
        })
        .join('\n')
      error.value = errorMessages
    } else if (err.response?.data?.message) {
      error.value = err.response.data.message
    } else {
      error.value = err.message || 'Failed to assign staff'
    }
    
    console.error('[Modal] PART 3: Error details:', {
      status: err.response?.status,
      message: error.value,
      fullError: err.response?.data
    })
  } finally {
    isSubmitting.value = false
  }
}

const handleClose = () => {
  selectedWaiter.value = ''
  selectedShift.value = ''
  selectedPriority.value = 'primary'
  error.value = null
  emit('close')
}

// PART 0: Initialize - Load data on mount
onMounted(() => {
  console.log('[Modal] PART 0: Modal mounted, isOpen=', props.isOpen)
  if (props.isOpen) {
    isLoading.value = true
    console.log('[Modal] PART 0: Starting data load...')
    Promise.all([loadWaiters(), loadShifts()]).then(() => {
      isLoading.value = false
      console.log('[Modal] PART 0:  All data loaded')
    }).catch(err => {
      console.error('[Modal] PART 0: ❌ Error:', err)
      isLoading.value = false
    })
  }
})

// Watch for modal open/close
watch(() => props.isOpen, (newVal) => {
  console.log('[Modal] Watch: isOpen changed to', newVal)
  if (newVal && (waiters.value.length === 0 || shifts.value.length === 0)) {
    isLoading.value = true
    console.log('[Modal] Watch: Reloading data...')
    Promise.all([loadWaiters(), loadShifts()]).then(() => {
      isLoading.value = false
    }).catch(err => {
      console.error('[Modal] Watch: Error:', err)
      isLoading.value = false
    })
  }
})
</script>

<template>
  <div v-if="isOpen" class="fixed inset-0 bg-black/50 flex items-center justify-center z-50">
    <div class="bg-white rounded-xl shadow-2xl max-w-2xl w-full mx-4 max-h-[90vh] overflow-y-auto">
      <!-- Header -->
      <div class="flex items-center justify-between p-6 border-b border-slate-200 sticky top-0 bg-white rounded-t-xl">
        <div>
          <h2 class="text-2xl font-bold text-slate-900">Assign Staff to {{ floorName }}</h2>
          <p class="text-sm text-slate-500 mt-1">Select and assign waiters to manage floor operations</p>
        </div>
        <button
          @click="handleClose"
          class="text-slate-400 hover:text-slate-600 transition p-1 hover:bg-slate-100 rounded-lg"
        >
          <X class="w-6 h-6" />
        </button>
      </div>

      <!-- Content -->
      <div class="p-6 space-y-6">
        <!-- Success Alert -->
        <transition name="fade">
          <div v-if="successMessage" class="p-4 bg-emerald-50 border border-emerald-300 rounded-lg flex items-center gap-3">
            <CheckCircle2 class="w-5 h-5 text-emerald-600 flex-shrink-0" />
            <p class="text-sm font-medium text-emerald-800">{{ successMessage }}</p>
          </div>
        </transition>

        <!-- Error Alert -->
        <div v-if="error" class="p-4 bg-red-50 border border-red-200 rounded-lg flex items-start gap-3">
          <span class="text-lg">⚠️</span>
          <p class="text-sm text-red-700">{{ error }}</p>
        </div>

        <!-- Loading State -->
        <div v-if="isLoading" class="flex justify-center py-12">
          <div class="text-center">
            <div class="animate-spin mb-4">
              <Loader2 class="w-8 h-8 text-blue-600 mx-auto" />
            </div>
            <p class="text-slate-600 font-medium">Loading waiters...</p>
          </div>
        </div>

        <!-- Main Form -->
        <div v-else class="space-y-6">
          <!-- Waiter Select -->
          <div>
            <label class="block text-sm font-semibold text-slate-700 mb-3">
              <div class="flex items-center gap-2">
                <User class="w-4 h-4 text-blue-600" />
                Select Waiter
                <span class="text-red-500">*</span>
              </div>
            </label>
            <select
              v-model="selectedWaiter"
              :disabled="waiters.length === 0"
              class="w-full px-4 py-3 border-2 border-slate-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-slate-900 font-medium transition disabled:opacity-50 disabled:cursor-not-allowed"
            >
              <option value="">
                {{ waiters.length === 0 ? 'No waiters available' : '-- Choose a waiter --' }}
              </option>
              <option
                v-for="waiter in waiters"
                :key="waiter.id"
                :value="waiter.id"
              >
                {{ waiter.user?.name || `Waiter #${waiter.id}` }} - {{ waiter.employment_type?.replace('_', ' ') }}
              </option>
            </select>
          </div>

          <!-- Selected Waiter Card -->
          <transition name="slide-up">
            <div v-if="selectedWaiterData" class="bg-gradient-to-br from-blue-50 to-indigo-50 rounded-xl border-2 border-blue-200 p-6">
              <div class="flex items-start justify-between mb-4">
                <div class="flex items-center gap-4">
                  <div class="w-12 h-12 rounded-full bg-gradient-to-br from-blue-500 to-indigo-600 flex items-center justify-center text-white font-bold text-lg">
                    {{ selectedWaiterData.user?.name?.charAt(0).toUpperCase() }}
                  </div>
                  <div>
                    <h3 class="font-bold text-lg text-slate-900">{{ selectedWaiterData.user?.name }}</h3>
                    <p class="text-sm text-slate-600">{{ selectedWaiterData.employment_type?.replace(/_/g, ' ').toUpperCase() }}</p>
                  </div>
                </div>
                <span class="px-3 py-1 bg-blue-600 text-white text-xs font-bold rounded-full">SELECTED</span>
              </div>

              <!-- Waiter Details Grid -->
              <div class="grid grid-cols-3 gap-3">
                <div class="bg-white rounded-lg p-3 text-center">
                  <p class="text-xs text-slate-500 mb-1">Status</p>
                  <p class="font-semibold text-slate-900 capitalize">{{ selectedWaiterData.status || 'N/A' }}</p>
                </div>
                <div class="bg-white rounded-lg p-3 text-center">
                  <p class="text-xs text-slate-500 mb-1">Experience</p>
                  <p class="font-semibold text-slate-900 capitalize">{{ selectedWaiterData.experience_level || 'N/A' }}</p>
                </div>
                <div class="bg-white rounded-lg p-3 text-center">
                  <p class="text-xs text-slate-500 mb-1">Section</p>
                  <p class="font-semibold text-slate-900">{{ selectedWaiterData.section || 'N/A' }}</p>
                </div>
              </div>
            </div>
          </transition>

          <!-- Shift Select -->
          <div>
            <label class="block text-sm font-semibold text-slate-700 mb-3">
              <div class="flex items-center gap-2">
                <Clock class="w-4 h-4 text-blue-600" />
                Shift
                <span class="text-red-500">*</span>
              </div>
            </label>
            <select
              v-model="selectedShift"
              :disabled="shifts.length === 0"
              class="w-full px-4 py-3 border-2 border-slate-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-slate-900 font-medium transition disabled:opacity-50 disabled:cursor-not-allowed"
            >
              <option value="">
                {{ shifts.length === 0 ? 'No shifts available' : '-- Choose a shift --' }}
              </option>
              <option
                v-for="shift in shifts"
                :key="shift.id"
                :value="shift.id"
              >
                {{ shift.name }} ({{ shift.start_time }} - {{ shift.end_time }})
              </option>
            </select>
          </div>

          <!-- Priority Select -->
          <div>
            <label class="block text-sm font-semibold text-slate-700 mb-3">
              <div class="flex items-center gap-2">
                <Award class="w-4 h-4 text-blue-600" />
                Priority Level
              </div>
            </label>
            <div class="grid grid-cols-3 gap-3">
              <label
                v-for="priority in priorities"
                :key="priority"
                class="relative"
              >
                <input
                  type="radio"
                  :value="priority"
                  v-model="selectedPriority"
                  class="sr-only"
                />
                <div :class="[
                  'p-3 rounded-lg border-2 cursor-pointer transition text-center',
                  selectedPriority === priority
                    ? priority === 'primary' 
                      ? 'border-blue-600 bg-blue-50'
                      : priority === 'secondary'
                      ? 'border-emerald-600 bg-emerald-50'
                      : 'border-amber-600 bg-amber-50'
                    : 'border-slate-200 bg-white hover:border-slate-300'
                ]">
                  <p class="text-xs font-bold text-slate-600 uppercase mb-1">
                    <span :class="{
                      'text-blue-600': priority === 'primary' && selectedPriority === priority,
                      'text-emerald-600': priority === 'secondary' && selectedPriority === priority,
                      'text-amber-600': priority === 'backup' && selectedPriority === priority,
                    }">
                      {{ priority }}
                    </span>
                  </p>
                  <p class="text-xs text-slate-500">
                    {{ priority === 'primary' ? 'Main' : priority === 'secondary' ? 'Support' : 'Backup' }}
                  </p>
                </div>
              </label>
            </div>
          </div>
        </div>
      </div>

      <!-- Footer -->
      <div class="flex gap-3 p-6 border-t border-slate-200 bg-slate-50 rounded-b-xl sticky bottom-0">
        <button
          @click="handleClose"
          class="flex-1 px-4 py-3 border-2 border-slate-300 rounded-lg text-slate-700 font-semibold hover:bg-slate-100 transition"
        >
          Cancel
        </button>
        <button
          @click="handleAssign"
          :disabled="!isFormValid || isSubmitting"
          class="flex-1 px-4 py-3 bg-gradient-to-r from-blue-600 to-blue-700 hover:from-blue-700 hover:to-blue-800 disabled:opacity-50 text-white font-semibold rounded-lg transition flex items-center justify-center gap-2 shadow-lg"
        >
          <Loader2 v-if="isSubmitting" class="w-5 h-5 animate-spin" />
          <CheckCircle2 v-else class="w-5 h-5" />
          {{ isSubmitting ? 'Assigning...' : 'Assign Staff' }}
        </button>
      </div>
    </div>
  </div>
</template>

<style scoped>
.fade-enter-active, .fade-leave-active {
  transition: opacity 0.3s ease;
}

.fade-enter-from, .fade-leave-to {
  opacity: 0;
}

.slide-up-enter-active, .slide-up-leave-active {
  transition: all 0.3s ease;
}

.slide-up-enter-from, .slide-up-leave-to {
  opacity: 0;
  transform: translateY(10px);
}
</style>

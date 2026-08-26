<script setup lang="ts">
import { ref, onMounted, computed, watch } from 'vue'
import { X, Loader2, CheckCircle2, User, Clock, Award, MapPin, Users, RefreshCw } from 'lucide-vue-next'
import api from '@/api/auth'
import { useTableAssignmentStore } from '@/stores/manager/tableAssignmentStore'
import restaurantTableService from '@/services/manager/restaurantTableService'

interface Props {
  isOpen: boolean
}

interface Emits {
  (e: 'close'): void
  (e: 'assigned'): void
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
const isLoading = ref(false)
const isSubmitting = ref(false)
const error = ref<string | null>(null)
const successMessage = ref<string | null>(null)

const priorities = ['primary', 'secondary', 'backup']

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
    console.log('🟡 [TableModal] Loading restaurant tables...')
    const response = await api.get('/manager/restaurant-tables')
    console.log('🟡 [TableModal] API response:', response.data)
    
    // Handle paginated response structure
    let tableData = []
    
    if (response.data?.data) {
      // Check if it's paginated (has .data property within data)
      if (response.data.data.data && Array.isArray(response.data.data.data)) {
        tableData = response.data.data.data
        console.log('🟡 [TableModal] Found paginated data structure')
      }
      // Check if data is directly an array
      else if (Array.isArray(response.data.data)) {
        tableData = response.data.data
        console.log('🟡 [TableModal] Found direct array structure')
      }
      // Check if data has items property (pagination)
      else if (response.data.data.items && Array.isArray(response.data.data.items)) {
        tableData = response.data.data.items
        console.log('🟡 [TableModal] Found items array structure')
      }
    } else if (Array.isArray(response.data)) {
      tableData = response.data
      console.log('🟡 [TableModal] Found root-level array')
    }
    
    tables.value = tableData
    console.log('🟡 [TableModal] ✅ Loaded', tableData.length, 'tables:', tableData.slice(0, 3))
    
    if (tableData.length === 0) {
      console.warn('🟡 [TableModal] ⚠️ No tables found in response')
    }
  } catch (err: any) {
    error.value = 'Failed to load tables: ' + err.message
    console.error('🟡 [TableModal] ❌ Error:', err)
    tables.value = []
  }
}

// Load Waiters from Backend
const loadWaiters = async () => {
  try {
    console.log('🟡 [TableModal] Loading waiters...')
    const response = await api.get('/manager/waiters')
    console.log('🟡 [TableModal] API response:', response.data)
    
    const data = response.data.data || response.data
    
    if (Array.isArray(data)) {
      waiters.value = data
      console.log('🟡 [TableModal] ✅ Loaded', data.length, 'waiters')
    } else {
      waiters.value = []
      console.warn('🟡 [TableModal] ❌ Data not array:', data)
    }
  } catch (err: any) {
    error.value = 'Failed to load waiters: ' + err.message
    console.error('🟡 [TableModal] ❌ Error:', err)
    waiters.value = []
  }
}

// Load Shifts from Backend
const loadShifts = async () => {
  try {
    console.log('🟡 [TableModal] Loading shifts...')
    const response = await api.get('/manager/shifts', {
      params: { status: 'active' }
    })
    console.log('🟡 [TableModal] API response:', response.data)
    
    const data = response.data.data || response.data
    
    if (Array.isArray(data)) {
      shifts.value = data
      console.log('🟡 [TableModal] ✅ Loaded', data.length, 'shifts')
    } else {
      shifts.value = []
      console.warn('🟡 [TableModal] ❌ Data not array:', data)
    }
  } catch (err: any) {
    console.warn('🟡 [TableModal] Warning:', err.message)
    shifts.value = []
  }
}

// Send Assignment to Backend
const handleAssign = async () => {
  if (!isFormValid.value) {
    error.value = 'Please select a table, waiter, and shift'
    return
  }

  isSubmitting.value = true
  error.value = null

  try {
    const tableObj = tables.value.find(t => t.id === selectedTable.value)
    const waiterObj = waiters.value.find(w => w.id === Number(selectedWaiter.value) || String(w.id) === String(selectedWaiter.value))
    const shiftObj = shifts.value.find(s => s.id === selectedShift.value)
    
    if (!tableObj) {
      error.value = `Table not found (looking for ID: ${selectedTable.value})`
      console.error('🟡 [TableModal] ❌ Table lookup failed')
      isSubmitting.value = false
      return
    }
    if (!waiterObj) {
      error.value = `Waiter not found (looking for ID: ${selectedWaiter.value})`
      console.error('🟡 [TableModal] ❌ Waiter lookup failed')
      isSubmitting.value = false
      return
    }
    if (!shiftObj) {
      error.value = 'Shift not found'
      console.error('🟡 [TableModal] ❌ Shift lookup failed')
      isSubmitting.value = false
      return
    }
    
    console.log('🟡 [TableModal] Building assignment data...')
    
    // Ensure waiter_id is a NUMBER
    const waiter_id = Number(waiterObj.id)
    
    if (!Number.isInteger(waiter_id) || waiter_id <= 0) {
      error.value = `Invalid waiter ID format: ${waiter_id}`
      console.error('🟡 [TableModal] ❌ Invalid waiter_id type')
      isSubmitting.value = false
      return
    }
    
    const assignmentData = {
      assignments: [{
        waiter_id: waiter_id,
        table_id: selectedTable.value,
        shift_id: selectedShift.value,
        assignment_date: new Date().toISOString().split('T')[0],
        priority: selectedPriority.value,
      }]
    }
    
    console.log('🟡 [TableModal] ✅ Assignment payload:', JSON.stringify(assignmentData, null, 2))
    console.log('🟡 [TableModal] Sending to POST /manager/table-assignments')
    
    // Send to Store
    const response = await tableAssignmentStore.assignWaitersToTables(assignmentData.assignments)
    
    console.log('🟡 [TableModal] ✅ Store Response:', response)
    
    successMessage.value = `${waiterObj.user?.name || 'Waiter'} assigned to ${tableObj.table_number} successfully!`
    console.log('🟡 [TableModal] ✅ Success! Assignment completed')
    
    // Clear form
    selectedTable.value = ''
    selectedWaiter.value = ''
    selectedShift.value = ''
    selectedPriority.value = 'primary'
    
    emit('assigned')
    
    // Clear success message after 3 seconds
    setTimeout(() => {
      successMessage.value = null
    }, 3000)
    
  } catch (err: any) {
    console.error('🟡 [TableModal] ❌ API Error:', err)
    
    const errorData = err.response?.data
    
    if (errorData?.errors && Array.isArray(errorData.errors)) {
      const errorDetails = errorData.errors
        .map((e: any) => `Assignment Error: ${e.error}`)
        .join('\n')
      error.value = errorDetails
    } else if (errorData?.error) {
      error.value = errorData.error
    } else if (err.response?.data?.message) {
      error.value = err.response.data.message
    } else {
      error.value = err.message || 'Failed to assign waiter to table'
    }
    
    console.error('🟡 [TableModal] Error details:', {
      status: err.response?.status,
      message: error.value,
      fullError: err.response?.data
    })
  } finally {
    isSubmitting.value = false
  }
}

const handleClose = () => {
  selectedTable.value = ''
  selectedWaiter.value = ''
  selectedShift.value = ''
  selectedPriority.value = 'primary'
  error.value = null
  emit('close')
}

// Format time from ISO string or HH:MM:SS to readable format
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
    console.error('Error formatting time:', err)
    return timeString
  }
}

// Initialize - Load data on mount
onMounted(() => {
  console.log('🟡 [TableModal] Modal mounted, isOpen=', props.isOpen)
  if (props.isOpen) {
    isLoading.value = true
    console.log('🟡 [TableModal] Starting data load...')
    Promise.all([loadTables(), loadWaiters(), loadShifts()]).then(() => {
      isLoading.value = false
      console.log('🟡 [TableModal] ✅ All data loaded')
    }).catch(err => {
      console.error('🟡 [TableModal] ❌ Error:', err)
      isLoading.value = false
    })
  }
})

// Watch for modal open/close
watch(() => props.isOpen, (newVal) => {
  console.log('🟡 [TableModal] Watch: isOpen changed to', newVal)
  if (newVal && (tables.value.length === 0 || waiters.value.length === 0 || shifts.value.length === 0)) {
    isLoading.value = true
    console.log('🟡 [TableModal] Watch: Reloading data...')
    Promise.all([loadTables(), loadWaiters(), loadShifts()]).then(() => {
      isLoading.value = false
    }).catch(err => {
      console.error('🟡 [TableModal] Watch: Error:', err)
      isLoading.value = false
    })
  }
})
</script>

<template>
  <Teleport to="body">
    <transition name="modal">
      <div v-if="isOpen" class="fixed inset-0 z-9999 flex items-center justify-center">
        <!-- Backdrop -->
        <div 
          class="absolute inset-0 bg-black/60 backdrop-blur-sm"
          @click="handleClose"
        ></div>
        
        <!-- Modal Content -->
        <div class="relative bg-white rounded-xl shadow-2xl max-w-3xl w-full mx-4 max-h-[90vh] overflow-y-auto">
          <!-- Header -->
          <div class="flex items-center justify-between p-6 border-b border-slate-200 sticky top-0 bg-white rounded-t-xl z-10">
            <div>
              <h2 class="text-2xl font-bold text-slate-900">Assign Waiter to Table</h2>
              <p class="text-sm text-slate-500 mt-1">Select table, waiter, and shift for walk-in customer service</p>
            </div>
            <button
              type="button"
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
                <p class="text-slate-600 font-medium">Loading data...</p>
              </div>
            </div>

            <!-- Main Form -->
            <div v-else class="space-y-6">
              <!-- Table Select -->
              <div>
                <label class="block text-sm font-semibold text-slate-700 mb-3">
                  <div class="flex items-center gap-2">
                    <MapPin class="w-4 h-4 text-blue-600" />
                    Select Restaurant Table
                    <span class="text-red-500">*</span>
                  </div>
                </label>
                
                <!-- No Tables Warning -->
                <div v-if="tables.length === 0" class="mb-3 p-4 bg-amber-50 border-2 border-amber-300 rounded-lg">
                  <div class="flex items-start gap-3">
                    <span class="text-2xl">⚠️</span>
                    <div class="flex-1">
                      <p class="text-sm font-bold text-amber-900 mb-2">No Restaurant Tables Found</p>
                      <p class="text-xs text-amber-800 mb-3">You need to create restaurant tables first before you can assign waiters.</p>
                      <div class="flex gap-2">
                        <router-link
                          to="/manager/restaurant-tables"
                          class="inline-flex items-center gap-2 px-3 py-1.5 bg-amber-600 hover:bg-amber-700 text-white text-xs font-semibold rounded-lg transition"
                        >
                          <MapPin class="w-3.5 h-3.5" />
                          Go to Restaurant Tables
                        </router-link>
                        <button
                          type="button"
                          @click="loadTables"
                          class="inline-flex items-center gap-2 px-3 py-1.5 border-2 border-amber-600 text-amber-900 hover:bg-amber-100 text-xs font-semibold rounded-lg transition"
                        >
                          <RefreshCw class="w-3.5 h-3.5" />
                          Refresh
                        </button>
                      </div>
                      <details class="mt-3">
                        <summary class="text-xs text-amber-700 font-medium cursor-pointer hover:text-amber-900">
                          Developer: Run Database Seeder
                        </summary>
                        <code class="block mt-2 text-xs bg-amber-100 p-2 rounded border border-amber-300 font-mono">
                          php artisan db:seed --class=RestaurantTableSeeder
                        </code>
                      </details>
                    </div>
                  </div>
                </div>
                
                <select
                  v-model="selectedTable"
                  :disabled="tables.length === 0"
                  class="w-full px-4 py-3 border-2 border-slate-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-slate-900 font-medium transition disabled:opacity-50 disabled:cursor-not-allowed"
                >
                  <option value="">
                    {{ tables.length === 0 ? 'No tables available - Create tables first' : '-- Choose a table --' }}
                  </option>
                  <option
                    v-for="table in tables"
                    :key="table.id"
                    :value="table.id"
                  >
                    Table {{ table.table_number }}{{ table.table_name ? ` - ${table.table_name}` : '' }} ({{ table.location || 'No location' }})
                  </option>
                </select>
              </div>

              <!-- Selected Table Card -->
              <transition name="slide-up">
                <div v-if="selectedTableData" class="bg-gradient-to-br from-purple-50 to-pink-50 rounded-xl border-2 border-purple-200 p-6 space-y-3">
                  <div class="flex items-start justify-between">
                    <div class="flex items-center gap-3">
                      <div class="w-12 h-12 rounded-lg bg-gradient-to-br from-purple-500 to-pink-600 flex items-center justify-center text-white font-bold text-lg shadow-lg">
                        {{ selectedTableData.table_number }}
                      </div>
                      <div>
                        <h3 class="font-bold text-lg text-slate-900">{{ selectedTableData.table_name || 'Table ' + selectedTableData.table_number }}</h3>
                        <p class="text-sm text-slate-600">{{ selectedTableData.location || 'Main Hall' }}</p>
                      </div>
                    </div>
                    <span class="px-3 py-1 bg-purple-600 text-white text-xs font-bold rounded-full shadow-md">SELECTED</span>
                  </div>

                  <!-- Table Details Grid -->
                  <div class="grid grid-cols-3 gap-3 mt-4">
                    <div class="bg-white rounded-lg p-3 text-center border border-purple-100">
                      <Users class="w-5 h-5 text-purple-600 mx-auto mb-1" />
                      <p class="text-xs text-slate-500 mb-1">Capacity</p>
                      <p class="font-bold text-slate-900">{{ selectedTableData.capacity || 'N/A' }}</p>
                    </div>
                    <div class="bg-white rounded-lg p-3 text-center border border-purple-100">
                      <p class="text-xs text-slate-500 mb-1">Section</p>
                      <p class="font-bold text-slate-900 text-sm">{{ selectedTableData.section || 'N/A' }}</p>
                    </div>
                    <div class="bg-white rounded-lg p-3 text-center border border-purple-100">
                      <p class="text-xs text-slate-500 mb-1">Status</p>
                      <p :class="[
                        'font-bold capitalize text-sm',
                        selectedTableData.status === 'available' ? 'text-emerald-600' : 'text-slate-600'
                      ]">
                        {{ selectedTableData.status || 'N/A' }}
                      </p>
                    </div>
                  </div>
                </div>
              </transition>

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
                    {{ waiter.user?.name || `Waiter #${waiter.id}` }} - {{ waiter.status }}
                  </option>
                </select>
              </div>

              <!-- Selected Waiter Card -->
              <transition name="slide-up">
                <div v-if="selectedWaiterData" class="bg-gradient-to-br from-blue-50 to-indigo-50 rounded-xl border-2 border-blue-200 p-6 space-y-4">
                  <div class="flex items-start justify-between">
                    <div class="flex items-center gap-4">
                      <div class="w-14 h-14 rounded-full bg-gradient-to-br from-blue-500 to-indigo-600 flex items-center justify-center text-white font-bold text-xl shadow-lg">
                        {{ selectedWaiterData.user?.name?.charAt(0).toUpperCase() }}
                      </div>
                      <div>
                        <h3 class="font-bold text-xl text-slate-900">{{ selectedWaiterData.user?.name }}</h3>
                        <p class="text-sm text-slate-600 font-medium">{{ selectedWaiterData.employment_type?.replace(/_/g, ' ').toUpperCase() }}</p>
                      </div>
                    </div>
                    <span class="px-3 py-1 bg-blue-600 text-white text-xs font-bold rounded-full shadow-md">SELECTED</span>
                  </div>

                  <!-- Waiter Info -->
                  <div class="grid grid-cols-2 gap-3">
                    <div class="bg-white rounded-lg p-3 border border-blue-100">
                      <p class="text-xs text-slate-500 mb-1 font-semibold">Email</p>
                      <p class="text-sm text-slate-900 font-medium break-all">{{ selectedWaiterData.user?.email || 'No email' }}</p>
                    </div>
                    <div class="bg-white rounded-lg p-3 border border-blue-100">
                      <p class="text-xs text-slate-500 mb-1 font-semibold">Experience</p>
                      <p class="text-sm text-slate-900 font-bold capitalize">{{ selectedWaiterData.experience_level || 'N/A' }}</p>
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
                    {{ shift.name }} ({{ formatTime(shift.start_time) }} - {{ formatTime(shift.end_time) }})
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
              type="button"
              @click="handleClose"
              class="flex-1 px-4 py-3 border-2 border-slate-300 rounded-lg text-slate-700 font-semibold hover:bg-slate-100 transition"
            >
              Cancel
            </button>
            <button
              type="button"
              @click="handleAssign"
              :disabled="!isFormValid || isSubmitting"
              class="flex-1 px-4 py-3 bg-gradient-to-r from-blue-600 to-blue-700 hover:from-blue-700 hover:to-blue-800 disabled:opacity-50 text-white font-semibold rounded-lg transition flex items-center justify-center gap-2 shadow-lg"
            >
              <Loader2 v-if="isSubmitting" class="w-5 h-5 animate-spin" />
              <CheckCircle2 v-else class="w-5 h-5" />
              {{ isSubmitting ? 'Assigning...' : 'Assign Waiter' }}
            </button>
          </div>
        </div>
      </div>
    </transition>
  </Teleport>
</template>

<style scoped>
.modal-enter-active, .modal-leave-active {
  transition: opacity 0.3s ease;
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

.slide-up-enter-active, .slide-up-leave-active {
  transition: all 0.3s ease;
}

.slide-up-enter-from, .slide-up-leave-to {
  opacity: 0;
  transform: translateY(10px);
}

.z-9999 {
  z-index: 9999;
}
</style>

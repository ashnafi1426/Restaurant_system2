<script setup lang="ts">
import { ref, onMounted, computed, watch } from 'vue'
import {
  X,
  Loader2,
  CheckCircle2,
  Building2,
  Users,
  Search,
  Check,
  AlertCircle,
  ToggleLeft,
  ToggleRight,
  UserCheck
} from 'lucide-vue-next'
import api from '@/api/auth'

interface Props {
  isOpen?: boolean
  floorId: string
  floorName?: string
  floors?: any[]
}

interface Emits {
  (e: 'close'): void
  (e: 'assigned'): void
  (e: 'success'): void
}

const props = withDefaults(defineProps<Props>(), {
  isOpen: true,
  floorName: '',
  floors: () => [],
})

const emit = defineEmits<Emits>()

// State
const availableFloors = ref<any[]>([])
const selectedFloorId = ref<string>(props.floorId || '')
const waiters = ref<any[]>([])
const selectedWaiterIds = ref<Array<string | number>>([])
const isActive = ref<boolean>(true)
const waiterSearch = ref<string>('')

const isLoading = ref<boolean>(false)
const isSubmitting = ref<boolean>(false)
const error = ref<string | null>(null)
const successMessage = ref<string | null>(null)

// Computed
const currentFloor = computed(() => {
  return availableFloors.value.find(f => String(f.id) === String(selectedFloorId.value)) || {
    id: props.floorId,
    name: props.floorName || 'Selected Floor',
    floor_number: ''
  }
})

const filteredWaiters = computed(() => {
  let list = waiters.value
  if (waiterSearch.value.trim()) {
    const q = waiterSearch.value.toLowerCase().trim()
    list = list.filter(w => {
      const name = (w.user?.name || w.name || '').toLowerCase()
      const email = (w.user?.email || w.email || '').toLowerCase()
      const section = (w.section || '').toLowerCase()
      return name.includes(q) || email.includes(q) || section.includes(q)
    })
  }
  return list
})

const selectedWaitersList = computed(() => {
  return waiters.value.filter(w => selectedWaiterIds.value.includes(w.id))
})

const isFormValid = computed(() => {
  return Boolean(selectedFloorId.value) && selectedWaiterIds.value.length > 0
})

// Methods
const loadFloors = async () => {
  if (props.floors && props.floors.length > 0) {
    availableFloors.value = [...props.floors]
  }
  try {
    const response = await api.get('/manager/floors')
    const data = response.data?.data || response.data
    if (Array.isArray(data) && data.length > 0) {
      availableFloors.value = data
    }
  } catch (err: any) {
    console.warn('[AddStaffToFloorModal] Fallback to props floors:', err?.message)
  }

  if (props.floorId && !availableFloors.value.some(f => String(f.id) === String(props.floorId))) {
    availableFloors.value.unshift({
      id: props.floorId,
      name: props.floorName || 'Selected Floor',
      floor_number: '',
    })
  }
}

const loadWaiters = async () => {
  try {
    const response = await api.get('/manager/waiters')
    const data = response.data?.data || response.data
    const all = Array.isArray(data) ? data : []
    const activeOnly = all.filter((w: any) => (w.status || 'active').toLowerCase() === 'active')
    waiters.value = activeOnly.length > 0 ? activeOnly : all
  } catch (err: any) {
    console.error('[AddStaffToFloorModal] Error loading waiters:', err)
    error.value = 'Failed to load waiters: ' + (err.response?.data?.message || err.message)
    waiters.value = []
  }
}

const toggleWaiter = (waiterId: string | number) => {
  const index = selectedWaiterIds.value.indexOf(waiterId)
  if (index >= 0) {
    selectedWaiterIds.value.splice(index, 1)
  } else {
    selectedWaiterIds.value.push(waiterId)
  }
}

const removeWaiter = (waiterId: string | number) => {
  selectedWaiterIds.value = selectedWaiterIds.value.filter(id => id !== waiterId)
}

const selectAllWaiters = () => {
  selectedWaiterIds.value = filteredWaiters.value.map(w => w.id)
}

const clearAllWaiters = () => {
  selectedWaiterIds.value = []
}

const handleAssign = async () => {
  if (!isFormValid.value) {
    error.value = 'Please select a floor and at least one waiter'
    return
  }

  isSubmitting.value = true
  error.value = null

  try {
    const today = new Date().toISOString().split('T')[0]
    const targetFloorId = String(selectedFloorId.value).trim()
    const targetStatus = isActive.value ? 'active' : 'inactive'

    // Build assignment array for all selected waiters
    const assignmentsPayload = selectedWaiterIds.value.map(waiterId => ({
      waiter_id: Number(waiterId),
      floor_id: targetFloorId,
      shift_id: null,
      assignment_date: today,
      status: targetStatus,
      priority: 'primary',
    }))

    const response = await api.post('/manager/floors/assignments', {
      assignments: assignmentsPayload,
    })

    if (response.data?.errors && response.data.errors.length > 0) {
      error.value = response.data.errors.map((e: any) => e.error || JSON.stringify(e)).join('\n')
      isSubmitting.value = false
      return
    }

    const floorDisplayName = currentFloor.value.name || `Floor #${currentFloor.value.floor_number || ''}`
    const count = selectedWaiterIds.value.length
    successMessage.value = `${count} waiter${count > 1 ? 's' : ''} assigned to ${floorDisplayName} successfully!`

    emit('assigned')
    emit('success')

    setTimeout(() => {
      handleClose()
    }, 900)
  } catch (err: any) {
    console.error('[AddStaffToFloorModal] Error assigning staff:', err)
    const errorData = err.response?.data
    if (errorData?.errors && Array.isArray(errorData.errors)) {
      error.value = errorData.errors.map((e: any) => `Assignment Error: ${e.error || e}`).join('\n')
    } else if (errorData?.message) {
      error.value = errorData.message
    } else {
      error.value = err.message || 'Failed to assign waiters to floor'
    }
  } finally {
    isSubmitting.value = false
  }
}

const handleClose = () => {
  selectedWaiterIds.value = []
  error.value = null
  successMessage.value = null
  emit('close')
}

// Watchers
watch(() => props.floorId, (newFloorId) => {
  if (newFloorId) {
    selectedFloorId.value = newFloorId
  }
})

watch(() => props.floors, (newFloors) => {
  if (newFloors && newFloors.length > 0) {
    availableFloors.value = newFloors
  }
}, { immediate: true })

onMounted(async () => {
  isLoading.value = true
  selectedFloorId.value = props.floorId || ''
  await Promise.all([loadFloors(), loadWaiters()])
  isLoading.value = false
})
</script>

<template>
  <div
    v-if="isOpen !== false"
    class="fixed inset-0 bg-slate-900/60 backdrop-blur-xs flex items-center justify-center z-50 p-3 sm:p-6 overflow-hidden animate-in fade-in duration-200"
    @click.self="handleClose"
  >
    <div
      class="bg-white dark:bg-slate-900 rounded-2xl sm:rounded-3xl shadow-2xl max-w-lg w-full max-h-[90vh] flex flex-col border border-slate-200 dark:border-slate-800 overflow-hidden font-sans"
    >
      <!-- Modal Header -->
      <div class="p-5 sm:p-6 border-b border-slate-100 dark:border-slate-800 flex items-center justify-between flex-shrink-0 bg-white dark:bg-slate-900">
        <div class="flex items-center gap-3">
          <div class="w-10 h-10 rounded-2xl bg-blue-500/10 text-blue-600 dark:text-blue-400 border border-blue-500/20 flex items-center justify-center font-bold flex-shrink-0">
            <Building2 class="w-5 h-5" />
          </div>
          <div>
            <h2 class="text-base sm:text-lg font-black text-slate-900 dark:text-white">
              Assign Room-Service Waiters
            </h2>
            <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">
              Select floor and active staff for automated room delivery
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

      <!-- Modal Body -->
      <div class="p-5 sm:p-6 space-y-5 overflow-y-auto flex-1">
        <!-- Success Alert -->
        <div
          v-if="successMessage"
          class="p-4 bg-emerald-500/10 border border-emerald-500/20 text-emerald-700 dark:text-emerald-300 rounded-2xl flex items-center gap-3 text-xs font-bold"
        >
          <CheckCircle2 class="w-4 h-4 text-emerald-500 flex-shrink-0" />
          <p>{{ successMessage }}</p>
        </div>

        <!-- Error Alert -->
        <div
          v-if="error"
          class="p-4 bg-rose-500/10 border border-rose-500/20 text-rose-700 dark:text-rose-300 rounded-2xl flex items-start gap-2.5 text-xs font-bold"
        >
          <AlertCircle class="w-4 h-4 text-rose-500 flex-shrink-0 mt-0.5" />
          <p class="whitespace-pre-line">{{ error }}</p>
        </div>

        <!-- Loading Spinner -->
        <div v-if="isLoading" class="flex flex-col items-center justify-center py-12 gap-3">
          <Loader2 class="w-8 h-8 text-blue-600 dark:text-blue-400 animate-spin" />
          <p class="text-xs font-bold text-slate-500 dark:text-slate-400">Loading floors & staff...</p>
        </div>

        <div v-else class="space-y-5">
          <!-- 1. FLOOR REQUIRED -->
          <div>
            <label class="block text-[11px] font-extrabold uppercase tracking-wider text-slate-600 dark:text-slate-400 mb-1.5">
              Floor <span class="text-rose-500">*</span>
            </label>
            <div class="relative">
              <select
                v-model="selectedFloorId"
                class="w-full appearance-none px-3.5 py-2.5 rounded-xl border border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-950 text-slate-900 dark:text-white text-xs sm:text-sm font-bold focus:outline-none focus:border-blue-500 dark:focus:border-blue-400 transition cursor-pointer pr-10"
              >
                <option value="" disabled>-- Select Floor --</option>
                <option
                  v-for="floor in availableFloors"
                  :key="floor.id"
                  :value="floor.id"
                >
                  {{ floor.name || `Floor ${floor.floor_number}` }} (Floor #{{ floor.floor_number }})
                </option>
              </select>
              <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-3 text-slate-400">
                <span class="text-xs font-bold">▼</span>
              </div>
            </div>
            <p class="text-[10px] text-slate-400 dark:text-slate-500 mt-1">
              Room service requests on this floor will dispatch to these assigned waiters
            </p>
          </div>

          <!-- 2. WAITERS REQUIRED -->
          <div>
            <div class="flex items-center justify-between mb-1.5">
              <label class="block text-[11px] font-extrabold uppercase tracking-wider text-slate-600 dark:text-slate-400">
                Waiters <span class="text-rose-500">*</span>
              </label>
              <div class="flex items-center gap-2 text-[10px]">
                <button
                  type="button"
                  @click="selectAllWaiters"
                  class="font-bold text-blue-600 dark:text-blue-400 hover:underline cursor-pointer"
                >
                  Select All
                </button>
                <span class="text-slate-300 dark:text-slate-700">|</span>
                <button
                  type="button"
                  @click="clearAllWaiters"
                  class="font-bold text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 cursor-pointer"
                >
                  Clear
                </button>
              </div>
            </div>

            <!-- Selected Waiter Chips (e.g. [ Dawit × ] [ Hana × ]) -->
            <div
              v-if="selectedWaitersList.length > 0"
              class="flex flex-wrap items-center gap-1.5 p-2 rounded-xl bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 mb-2.5 min-h-[42px]"
            >
              <span
                v-for="waiter in selectedWaitersList"
                :key="waiter.id"
                class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg bg-blue-500/10 text-blue-700 dark:text-blue-300 border border-blue-500/20 text-xs font-bold animate-in fade-in"
              >
                <span>{{ waiter.user?.name || waiter.name || `Waiter #${waiter.id}` }}</span>
                <button
                  type="button"
                  @click.stop="removeWaiter(waiter.id)"
                  class="text-blue-400 hover:text-rose-600 rounded-full p-0.5 transition cursor-pointer"
                  title="Remove"
                >
                  <X class="w-3 h-3 stroke-[3]" />
                </button>
              </span>
            </div>

            <!-- Waiter Search & Selection Box -->
            <div class="border border-slate-200 dark:border-slate-800 rounded-xl overflow-hidden bg-white dark:bg-slate-950">
              <div class="relative border-b border-slate-100 dark:border-slate-800 px-3 py-2 bg-slate-50/50 dark:bg-slate-900/50">
                <Search class="w-3.5 h-3.5 text-slate-400 absolute left-3.5 top-1/2 -translate-y-1/2 pointer-events-none" />
                <input
                  v-model="waiterSearch"
                  type="text"
                  placeholder="Search available waiters..."
                  class="w-full bg-transparent pl-6 pr-2 py-0.5 text-xs text-slate-900 dark:text-white placeholder:text-slate-400 focus:outline-none font-medium"
                />
              </div>

              <!-- Scrollable Waiter Checkbox List -->
              <div class="max-h-48 overflow-y-auto p-1.5 divide-y divide-slate-100 dark:divide-slate-850">
                <div v-if="filteredWaiters.length === 0" class="py-6 text-center text-xs text-slate-400 italic">
                  No active waiters found
                </div>

                <div
                  v-for="waiter in filteredWaiters"
                  :key="waiter.id"
                  @click="toggleWaiter(waiter.id)"
                  class="flex items-center justify-between p-2 rounded-lg hover:bg-slate-100 dark:hover:bg-slate-900 cursor-pointer transition select-none"
                  :class="{ 'bg-blue-50/50 dark:bg-blue-950/20': selectedWaiterIds.includes(waiter.id) }"
                >
                  <div class="flex items-center gap-2.5 min-w-0">
                    <!-- Checkbox -->
                    <div
                      class="w-4 h-4 rounded-md border flex items-center justify-center transition flex-shrink-0"
                      :class="[
                        selectedWaiterIds.includes(waiter.id)
                          ? 'bg-blue-600 border-blue-600 text-white'
                          : 'border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-900'
                      ]"
                    >
                      <Check v-if="selectedWaiterIds.includes(waiter.id)" class="w-3 h-3 stroke-[3]" />
                    </div>

                    <!-- Waiter Avatar & Details -->
                    <div class="w-6 h-6 rounded-full bg-blue-500/10 text-blue-600 font-bold text-[10px] flex items-center justify-center flex-shrink-0">
                      {{ (waiter.user?.name || waiter.name || 'W').charAt(0).toUpperCase() }}
                    </div>

                    <div class="truncate">
                      <div class="text-xs font-bold text-slate-900 dark:text-white truncate">
                        {{ waiter.user?.name || waiter.name || `Waiter #${waiter.id}` }}
                      </div>
                      <div class="text-[10px] text-slate-400 truncate">
                        {{ waiter.user?.email || waiter.email || 'Staff' }}
                      </div>
                    </div>
                  </div>

                  <div class="flex items-center gap-1.5 flex-shrink-0 ml-2">
                    <span class="px-1.5 py-0.5 rounded-full text-[9px] font-extrabold uppercase tracking-wider bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 border border-emerald-500/20">
                      Active
                    </span>
                  </div>
                </div>
              </div>
            </div>
            <p class="text-[10px] text-slate-400 dark:text-slate-500 mt-1">
              Select multiple waiters. Workload balancing will auto-select the lowest workload waiter.
            </p>
          </div>

          <!-- 3. ACTIVE REQUIRED -->
          <div class="p-3.5 rounded-2xl bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 flex items-center justify-between">
            <div>
              <div class="text-xs font-extrabold text-slate-900 dark:text-white uppercase tracking-wider flex items-center gap-1.5">
                <span>Active Status</span>
                <span
                  class="px-2 py-0.5 rounded-full text-[10px] font-bold"
                  :class="isActive ? 'bg-emerald-500/10 text-emerald-600 dark:text-emerald-400' : 'bg-slate-200 text-slate-600 dark:bg-slate-800 dark:text-slate-400'"
                >
                  {{ isActive ? 'ON' : 'OFF' }}
                </span>
              </div>
              <p class="text-[10px] text-slate-500 dark:text-slate-400 mt-0.5">
                The automatic engine only assigns room-service orders to active assignments
              </p>
            </div>

            <!-- Toggle switch -->
            <button
              type="button"
              @click="isActive = !isActive"
              class="relative inline-flex h-6 w-11 items-center rounded-full transition-colors focus:outline-none cursor-pointer"
              :class="isActive ? 'bg-blue-600' : 'bg-slate-300 dark:bg-slate-700'"
            >
              <span
                class="inline-block h-4 w-4 transform rounded-full bg-white transition-transform shadow-xs"
                :class="isActive ? 'translate-x-6' : 'translate-x-1'"
              />
            </button>
          </div>
        </div>
      </div>

      <!-- Modal Footer -->
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
          class="px-5 py-2.5 rounded-xl bg-blue-600 hover:bg-blue-700 text-white font-black text-xs shadow-md shadow-blue-600/20 transition flex items-center justify-center gap-2 cursor-pointer disabled:opacity-40 disabled:cursor-not-allowed"
        >
          <Loader2 v-if="isSubmitting" class="w-4 h-4 animate-spin" />
          <UserCheck v-else class="w-4 h-4" />
          <span>
            {{ isSubmitting ? 'Assigning...' : (selectedWaiterIds.length > 1 ? `Assign ${selectedWaiterIds.length} Waiters` : 'Assign Waiter') }}
          </span>
        </button>
      </div>
    </div>
  </div>
</template>

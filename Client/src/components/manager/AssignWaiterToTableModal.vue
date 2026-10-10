<script setup lang="ts">
import { ref, onMounted, computed, watch } from 'vue'
import {
  X,
  Loader2,
  CheckCircle2,
  UtensilsCrossed,
  AlertCircle,
  Search,
  Check,
  ToggleLeft,
  ToggleRight,
  Users,
  MapPin,
  Sparkles,
} from 'lucide-vue-next'
import api from '@/api/auth'
import { useTableAssignmentStore } from '@/stores/manager/tableAssignmentStore'
import { useHotelStore } from '@/stores/hotelStore'

interface Props {
  isOpen?: boolean
  initialTableId?: string
  hotelId?: string
}

interface Emits {
  (e: 'close'): void
  (e: 'assigned'): void
  (e: 'success'): void
}

const props = withDefaults(defineProps<Props>(), {
  isOpen: true,
  initialTableId: '',
  hotelId: '',
})

const emit = defineEmits<Emits>()

const tableAssignmentStore = useTableAssignmentStore()
const hotelStore = useHotelStore()

const tables = ref<any[]>([])
const waiters = ref<any[]>([])
const selectedTable = ref<string>(props.initialTableId || '')
const selectedWaiterIds = ref<Array<number | string>>([])
const isActive = ref<boolean>(true)
const waiterSearch = ref<string>('')

const isLoading = ref<boolean>(false)
const isSubmitting = ref<boolean>(false)
const error = ref<string | null>(null)
const successMessage = ref<string | null>(null)

const selectedTableData = computed(() => {
  return tables.value.find((t) => String(t.id) === String(selectedTable.value)) || null
})

const activeWaiters = computed(() => {
  return waiters.value
})

const filteredWaiters = computed(() => {
  let list = activeWaiters.value
  if (waiterSearch.value.trim()) {
    const q = waiterSearch.value.toLowerCase().trim()
    list = list.filter((w: any) => {
      const name = (
        w.user?.name ||
        w.name ||
        `${w.user?.first_name || ''} ${w.user?.last_name || ''}`
      ).toLowerCase()
      const email = (w.user?.email || w.email || '').toLowerCase()
      const empNum = String(w.employee_number || '').toLowerCase()
      const section = (w.section || '').toLowerCase()
      return name.includes(q) || email.includes(q) || empNum.includes(q) || section.includes(q)
    })
  }
  return list
})

const selectedWaitersList = computed(() => {
  return activeWaiters.value.filter((w: any) => selectedWaiterIds.value.includes(w.id))
})

const isFormValid = computed(() => {
  return Boolean(selectedTable.value) && selectedWaiterIds.value.length > 0
})

const getWaiterDisplayName = (waiter: any): string => {
  if (!waiter) return 'Unknown Staff'
  if (waiter.user?.name && waiter.user.name.trim()) return waiter.user.name.trim()
  if (waiter.name && waiter.name.trim() && !waiter.name.startsWith('Waiter #'))
    return waiter.name.trim()
  const fullName = `${waiter.user?.first_name || ''} ${waiter.user?.last_name || ''}`.trim()
  if (fullName) return fullName
  if (waiter.user?.email) return waiter.user.email
  if (waiter.employee_number) return `Waiter #${waiter.employee_number}`
  return `Waiter #${waiter.id}`
}

// Data loaders
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
    console.error('[AssignWaiterToTableModal] Error loading tables:', err)
  }
}

const loadWaiters = async () => {
  try {
    const targetHotelId =
      props.hotelId || hotelStore.hotelId || localStorage.getItem('hotel_id') || ''
    const response = await api.get('/manager/waiters', {
      params: targetHotelId ? { hotel_id: targetHotelId } : {},
    })
    let list: any[] = []
    if (Array.isArray(response.data?.data)) {
      list = response.data.data
    } else if (Array.isArray(response.data)) {
      list = response.data
    }
    waiters.value = list
  } catch (err: any) {
    console.error('[AssignWaiterToTableModal] Error loading waiters:', err)
  }
}

const loadInitialData = async () => {
  isLoading.value = true
  error.value = null
  try {
    await Promise.all([loadTables(), loadWaiters()])
    if (props.initialTableId) {
      selectedTable.value = props.initialTableId
    }
  } finally {
    isLoading.value = false
  }
}

// Waiter selection toggles
const toggleWaiter = (waiterId: number | string) => {
  const index = selectedWaiterIds.value.findIndex((id) => String(id) === String(waiterId))
  if (index >= 0) {
    selectedWaiterIds.value.splice(index, 1)
  } else {
    selectedWaiterIds.value.push(waiterId)
  }
}

const removeWaiter = (waiterId: number | string) => {
  selectedWaiterIds.value = selectedWaiterIds.value.filter((id) => String(id) !== String(waiterId))
}

const selectAllWaiters = () => {
  const ids = filteredWaiters.value.map((w: any) => w.id)
  const set = new Set([...selectedWaiterIds.value, ...ids])
  selectedWaiterIds.value = Array.from(set)
}

const clearAllWaiters = () => {
  selectedWaiterIds.value = []
}

// Submission
const handleSubmit = async () => {
  if (!isFormValid.value) {
    error.value = 'Please select a restaurant table and at least one active waiter.'
    return
  }

  isSubmitting.value = true
  error.value = null
  successMessage.value = null

  try {
    const payload = {
      table_id: selectedTable.value,
      waiter_ids: selectedWaiterIds.value.map((id) => Number(id)),
      status: isActive.value ? 'active' : 'inactive',
      is_active: isActive.value,
    }

    if (tableAssignmentStore.assignWaitersToTables) {
      await tableAssignmentStore.assignWaitersToTables(payload)
    } else {
      await api.post('/manager/table-assignments', payload)
    }

    const tableInfo = selectedTableData.value
      ? `Table ${selectedTableData.value.table_number}`
      : 'Table'
    const waiterCount = selectedWaiterIds.value.length

    successMessage.value = `Successfully assigned ${waiterCount} waiter${waiterCount > 1 ? 's' : ''} to ${tableInfo}!`

    emit('assigned')
    emit('success')

    setTimeout(() => {
      handleClose()
    }, 1000)
  } catch (err: any) {
    console.error('[AssignWaiterToTableModal] Error assigning waiters to table:', err)
    error.value =
      err.response?.data?.message ||
      err.response?.data?.error ||
      err.message ||
      'Failed to assign waiters to table. Please verify selection.'
  } finally {
    isSubmitting.value = false
  }
}

const handleClose = () => {
  selectedTable.value = props.initialTableId || ''
  selectedWaiterIds.value = []
  isActive.value = true
  waiterSearch.value = ''
  error.value = null
  successMessage.value = null
  emit('close')
}

onMounted(() => {
  loadInitialData()
})

watch(
  () => props.isOpen,
  (open) => {
    if (open) {
      loadInitialData()
    }
  },
)

watch(
  () => props.initialTableId,
  (newId) => {
    if (newId) {
      selectedTable.value = newId
    }
  },
)
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
        <div
          class="flex items-center justify-between px-6 py-5 border-b border-slate-200 dark:border-slate-800 flex-shrink-0"
        >
          <div class="flex items-center gap-3">
            <div
              class="w-10 h-10 rounded-2xl bg-blue-500/10 text-blue-600 dark:text-blue-400 flex items-center justify-center border border-blue-500/20"
            >
              <UtensilsCrossed class="w-5 h-5" />
            </div>
            <div>
              <h2 class="text-lg font-black text-slate-900 dark:text-white">
                Assign Table to Waiters
              </h2>
              <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">
                Designate active waitstaff coverage for dining table
              </p>
            </div>
          </div>

          <button
            type="button"
            @click="handleClose"
            class="p-2 text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 hover:bg-slate-100 dark:hover:bg-slate-800 rounded-xl transition cursor-pointer"
            aria-label="Close modal"
          >
            <X class="w-5 h-5" />
          </button>
        </div>

        <div class="p-6 overflow-y-auto space-y-5 flex-1">
          <div v-if="isLoading" class="py-12 text-center space-y-3">
            <Loader2 class="w-8 h-8 text-blue-600 animate-spin mx-auto" />
            <p class="text-xs font-bold text-slate-500">Loading tables & staff roster...</p>
          </div>

          <template v-else>
            <div
              v-if="error"
              class="p-3.5 bg-rose-500/10 border border-rose-500/20 text-rose-600 dark:text-rose-400 rounded-2xl text-xs font-bold flex items-center gap-2.5"
            >
              <AlertCircle class="w-4 h-4 flex-shrink-0" />
              <span>{{ error }}</span>
            </div>

            <div
              v-if="successMessage"
              class="p-3.5 bg-emerald-500/10 border border-emerald-500/20 text-emerald-600 dark:text-emerald-400 rounded-2xl text-xs font-bold flex items-center gap-2.5"
            >
              <CheckCircle2 class="w-4 h-4 flex-shrink-0" />
              <span>{{ successMessage }}</span>
            </div>

            <div>
              <div class="flex items-center justify-between mb-2">
                <label
                  class="block text-xs font-black text-slate-700 dark:text-slate-300 uppercase tracking-wider"
                >
                  Restaurant Table <span class="text-rose-500">*</span>
                </label>
                <span
                  v-if="selectedTableData"
                  class="text-[11px] font-semibold text-blue-600 dark:text-blue-400"
                >
                  {{ selectedTableData.capacity || 2 }} Seats
                </span>
              </div>

              <select
                v-model="selectedTable"
                class="w-full rounded-xl border border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-950 text-slate-900 dark:text-white px-3.5 py-2.5 text-xs sm:text-sm font-medium outline-none focus:ring-2 focus:ring-blue-500/40 focus:border-blue-500 transition cursor-pointer"
              >
                <option value="" disabled>Select a dining table...</option>
                <option v-for="table in tables" :key="table.id" :value="table.id">
                  Table {{ table.table_number }}
                  {{ table.table_name ? `(${table.table_name})` : '' }} —
                  {{ table.section || table.location || 'Main Section' }} ({{ table.capacity }}
                  Seats)
                </option>
              </select>

              <div
                v-if="selectedTableData"
                class="mt-2.5 p-3 rounded-2xl border border-blue-100 dark:border-blue-900/40 bg-blue-50/60 dark:bg-blue-950/20 flex items-center justify-between gap-3 text-xs"
              >
                <div class="flex items-center gap-2.5 min-w-0">
                  <div
                    class="w-8 h-8 rounded-xl bg-blue-600/10 text-blue-600 dark:text-blue-400 flex items-center justify-center flex-shrink-0 font-bold"
                  >
                    {{ selectedTableData.table_number }}
                  </div>
                  <div class="truncate">
                    <div
                      class="font-extrabold text-slate-900 dark:text-white flex items-center gap-1.5"
                    >
                      <span>Table {{ selectedTableData.table_number }}</span>
                      <span
                        v-if="selectedTableData.table_name"
                        class="font-medium text-slate-500 dark:text-slate-400"
                        >({{ selectedTableData.table_name }})</span
                      >
                    </div>

                    <div
                      class="flex items-center gap-1 text-[11px] text-blue-700 dark:text-blue-300 font-semibold mt-0.5"
                    >
                      <MapPin class="w-3.5 h-3.5 flex-shrink-0 text-blue-500" />
                      <span
                        >Section:
                        {{
                          selectedTableData.section ||
                          selectedTableData.location ||
                          'Main Dining Room'
                        }}</span
                      >
                    </div>
                  </div>
                </div>

                <div class="flex items-center gap-1.5 flex-shrink-0">
                  <span
                    class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold bg-white dark:bg-slate-900 text-slate-600 dark:text-slate-300 border border-slate-200 dark:border-slate-800"
                  >
                    {{ selectedTableData.capacity }} seats
                  </span>
                  <span
                    class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold capitalize"
                    :class="
                      selectedTableData.status === 'occupied'
                        ? 'bg-amber-500/10 text-amber-600'
                        : 'bg-emerald-500/10 text-emerald-600'
                    "
                  >
                    {{ selectedTableData.status || 'available' }}
                  </span>
                </div>
              </div>
            </div>

            <div>
              <div class="flex items-center justify-between mb-2">
                <label
                  class="block text-xs font-black text-slate-700 dark:text-slate-300 uppercase tracking-wider"
                >
                  Assigned Waiter(s) <span class="text-rose-500">*</span>
                </label>
                <div class="flex items-center gap-2">
                  <span class="text-[11px] font-bold text-slate-400">
                    ({{ selectedWaiterIds.length }} selected)
                  </span>
                  <button
                    v-if="
                      filteredWaiters.length > 0 &&
                      selectedWaiterIds.length < filteredWaiters.length
                    "
                    type="button"
                    @click="selectAllWaiters"
                    class="text-[11px] font-bold text-blue-600 dark:text-blue-400 hover:underline cursor-pointer"
                  >
                    Select All
                  </button>
                  <button
                    v-if="selectedWaiterIds.length > 0"
                    type="button"
                    @click="clearAllWaiters"
                    class="text-[11px] font-bold text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 cursor-pointer"
                  >
                    Clear
                  </button>
                </div>
              </div>

              <div
                v-if="selectedWaitersList.length > 0"
                class="flex flex-wrap gap-1.5 p-2.5 rounded-2xl border border-slate-200 dark:border-slate-800 bg-slate-50/70 dark:bg-slate-950/70 mb-2.5 max-h-24 overflow-y-auto"
              >
                <span
                  v-for="waiter in selectedWaitersList"
                  :key="waiter.id"
                  class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-xl bg-blue-500/10 dark:bg-blue-900/30 text-blue-700 dark:text-blue-300 text-xs font-bold border border-blue-500/20"
                >
                  <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                  <span>{{ getWaiterDisplayName(waiter) }}</span>
                  <button
                    type="button"
                    @click.stop="removeWaiter(waiter.id)"
                    class="hover:text-rose-500 transition cursor-pointer"
                    aria-label="Remove waiter"
                  >
                    <X class="w-3.5 h-3.5" />
                  </button>
                </span>
              </div>

              <div class="relative mb-2">
                <Search class="w-4 h-4 absolute left-3 top-1/2 -translate-y-1/2 text-slate-400" />
                <input
                  v-model="waiterSearch"
                  type="text"
                  placeholder="Search active waiters by name or email..."
                  class="w-full rounded-xl border border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-950 text-slate-900 dark:text-white pl-9 pr-4 py-2 text-xs outline-none focus:ring-2 focus:ring-blue-500/40 focus:border-blue-500 transition"
                />
              </div>

              <div
                class="border border-slate-200 dark:border-slate-800 rounded-2xl overflow-hidden max-h-52 overflow-y-auto divide-y divide-slate-100 dark:divide-slate-800/60 bg-white dark:bg-slate-950/50"
              >
                <div
                  v-if="filteredWaiters.length === 0"
                  class="p-4 text-center text-xs font-medium text-slate-400"
                >
                  No active waiters found for this hotel.
                </div>

                <div
                  v-for="waiter in filteredWaiters"
                  :key="waiter.id"
                  @click="toggleWaiter(waiter.id)"
                  class="p-3 flex items-center justify-between gap-3 hover:bg-slate-50 dark:hover:bg-slate-900/80 transition cursor-pointer select-none"
                  :class="{
                    'bg-blue-50/60 dark:bg-blue-950/20': selectedWaiterIds.includes(waiter.id),
                  }"
                >
                  <div class="flex items-center gap-2.5 min-w-0">
                    <div
                      class="w-5 h-5 rounded-lg flex items-center justify-center border transition flex-shrink-0"
                      :class="
                        selectedWaiterIds.includes(waiter.id)
                          ? 'bg-blue-600 border-blue-600 text-white'
                          : 'border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-900'
                      "
                    >
                      <Check
                        v-if="selectedWaiterIds.includes(waiter.id)"
                        class="w-3.5 h-3.5 stroke-[3]"
                      />
                    </div>

                    <div
                      class="w-7 h-7 rounded-full bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 font-bold flex items-center justify-center text-xs flex-shrink-0"
                    >
                      {{ getWaiterDisplayName(waiter).charAt(0).toUpperCase() }}
                    </div>

                    <div class="min-w-0">
                      <div class="text-xs font-bold text-slate-900 dark:text-white truncate">
                        {{ getWaiterDisplayName(waiter) }}
                      </div>
                      <div class="text-[10px] text-slate-400 truncate">
                        {{
                          waiter.user?.email ||
                          waiter.email ||
                          (waiter.section ? `Section: ${waiter.section}` : 'Active Waiter')
                        }}
                      </div>
                    </div>
                  </div>

                  <div class="flex items-center gap-1.5 flex-shrink-0">
                    <span
                      v-if="typeof waiter.current_orders === 'number'"
                      class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300"
                      :title="`${waiter.current_orders} active orders assigned`"
                    >
                      ⚡ {{ waiter.current_orders }} orders
                    </span>
                    <span
                      class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-500/10 text-emerald-600 border border-emerald-500/20"
                    >
                      <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                      Active
                    </span>
                  </div>
                </div>
              </div>
            </div>

            <div
              class="p-4 rounded-2xl border border-slate-200 dark:border-slate-800 bg-slate-50/50 dark:bg-slate-950/50 flex items-center justify-between gap-4"
            >
              <div>
                <label
                  class="block text-xs font-black text-slate-900 dark:text-white uppercase tracking-wider"
                >
                  Active Assignment <span class="text-rose-500">*</span>
                </label>
                <p class="text-[11px] text-slate-500 dark:text-slate-400 mt-0.5">
                  Enable coverage for immediate automatic table order routing
                </p>
              </div>

              <button
                type="button"
                @click="isActive = !isActive"
                class="flex items-center gap-2 px-3 py-1.5 rounded-xl border transition cursor-pointer"
                :class="
                  isActive
                    ? 'border-emerald-500/40 bg-emerald-500/10 text-emerald-600 dark:text-emerald-400'
                    : 'border-slate-200 dark:border-slate-800 bg-slate-100 dark:bg-slate-900 text-slate-500'
                "
              >
                <component :is="isActive ? ToggleRight : ToggleLeft" class="w-5 h-5" />
                <span class="text-xs font-extrabold">{{ isActive ? 'Active' : 'Inactive' }}</span>
              </button>
            </div>
          </template>
        </div>

        <div
          class="flex items-center justify-end gap-3 px-6 py-4 border-t border-slate-200 dark:border-slate-800 bg-slate-50/50 dark:bg-slate-950/50 flex-shrink-0"
        >
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

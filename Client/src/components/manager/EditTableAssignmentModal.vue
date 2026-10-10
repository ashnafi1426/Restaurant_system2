<script setup lang="ts">
import { ref, watch, computed, onMounted } from 'vue'
import {
  X,
  Loader2,
  CheckCircle2,
  UtensilsCrossed,
  AlertCircle,
  Edit3,
  ToggleLeft,
  ToggleRight,
  MapPin,
} from 'lucide-vue-next'
import api from '@/api/auth'
import { useTableAssignmentStore } from '@/stores/manager/tableAssignmentStore'

interface Props {
  isOpen?: boolean
  assignment: any
}

interface Emits {
  (e: 'close'): void
  (e: 'updated'): void
  (e: 'success'): void
}

const props = withDefaults(defineProps<Props>(), {
  isOpen: true,
})

const emit = defineEmits<Emits>()

const tableAssignmentStore = useTableAssignmentStore()

const tables = ref<any[]>([])
const waiters = ref<any[]>([])

const selectedTable = ref<string>('')
const selectedWaiter = ref<number | string>('')
const isActive = ref<boolean>(true)

const isLoading = ref(false)
const isSubmitting = ref(false)
const error = ref<string | null>(null)
const successMessage = ref<string | null>(null)

const selectedTableData = computed(() => {
  return tables.value.find((t) => String(t.id) === String(selectedTable.value)) || null
})

const activeWaiters = computed(() => {
  return waiters.value.filter((w: any) => {
    const status = (w.status || 'active').toLowerCase()

    return status === 'active' || String(w.id) === String(selectedWaiter.value)
  })
})

const isFormValid = computed(() => {
  return Boolean(selectedTable.value) && Boolean(selectedWaiter.value)
})

const getWaiterDisplayName = (waiter: any): string => {
  if (!waiter) return 'Unknown Staff'
  if (waiter.user?.name) return waiter.user.name
  if (waiter.name) return waiter.name
  if (waiter.user?.first_name)
    return `${waiter.user.first_name} ${waiter.user.last_name || ''}`.trim()
  if (waiter.user?.email) return waiter.user.email.split('@')[0]
  if (waiter.employee_number) return `Waiter #${waiter.employee_number}`
  return `Waiter #${waiter.id}`
}

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
    tables.value = list
  } catch (err: any) {
    console.error('[EditTableAssignmentModal] Error loading tables:', err)
  }
}

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
    console.error('[EditTableAssignmentModal] Error loading waiters:', err)
  }
}

const populateForm = () => {
  if (!props.assignment) return
  selectedTable.value = props.assignment.table_id || props.assignment.table?.id || ''
  selectedWaiter.value = props.assignment.waiter_id || props.assignment.waiter?.id || ''
  isActive.value = props.assignment.status === 'active' || props.assignment.is_active !== false
}

const loadData = async () => {
  isLoading.value = true
  error.value = null
  try {
    await Promise.all([loadTables(), loadWaiters()])
    populateForm()
  } finally {
    isLoading.value = false
  }
}

watch(
  () => props.assignment,
  () => {
    populateForm()
  },
  { immediate: true },
)

onMounted(() => {
  loadData()
})

watch(
  () => props.isOpen,
  (open) => {
    if (open) {
      loadData()
      populateForm()
    }
  },
)

const handleUpdate = async () => {
  if (!props.assignment || !isFormValid.value) return

  isSubmitting.value = true
  error.value = null

  try {
    const payload = {
      waiter_id: Number(selectedWaiter.value),
      table_id: selectedTable.value,
      status: isActive.value ? 'active' : 'inactive',
      is_active: isActive.value,
    }

    await tableAssignmentStore.updateAssignment(props.assignment.id, payload)

    successMessage.value = 'Table assignment updated successfully!'
    emit('updated')
    emit('success')

    setTimeout(() => {
      handleClose()
    }, 1000)
  } catch (err: any) {
    console.error('[EditTableAssignmentModal] Error updating table assignment:', err)
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
              <Edit3 class="w-5 h-5" />
            </div>
            <div>
              <h2 class="text-lg font-black text-slate-900 dark:text-white">
                Edit Table Assignment
              </h2>
              <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">
                Modify assigned table, waitstaff, or active status
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
            <p class="text-xs font-bold text-slate-500">Loading assignment details...</p>
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
              <label
                class="block text-xs font-black text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-2"
              >
                Restaurant Table <span class="text-rose-500">*</span>
              </label>
              <select
                v-model="selectedTable"
                class="w-full rounded-xl border border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-950 text-slate-900 dark:text-white px-3.5 py-2.5 text-xs sm:text-sm font-medium outline-none focus:ring-2 focus:ring-blue-500/40 focus:border-blue-500 transition cursor-pointer"
              >
                <option value="" disabled>Choose a table...</option>
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
                <div class="flex items-center gap-2">
                  <MapPin class="w-4 h-4 text-blue-500 flex-shrink-0" />
                  <span class="font-bold text-slate-900 dark:text-white">
                    Section:
                    {{
                      selectedTableData.section || selectedTableData.location || 'Main Dining Room'
                    }}
                  </span>
                </div>
                <span class="text-[11px] font-bold text-slate-500 dark:text-slate-400">
                  {{ selectedTableData.capacity }} seats
                </span>
              </div>
            </div>

            <div>
              <label
                class="block text-xs font-black text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-2"
              >
                Assigned Waitstaff <span class="text-rose-500">*</span>
              </label>
              <select
                v-model="selectedWaiter"
                class="w-full rounded-xl border border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-950 text-slate-900 dark:text-white px-3.5 py-2.5 text-xs sm:text-sm font-medium outline-none focus:ring-2 focus:ring-blue-500/40 focus:border-blue-500 transition cursor-pointer"
              >
                <option value="" disabled>Choose a waiter...</option>
                <option v-for="waiter in activeWaiters" :key="waiter.id" :value="waiter.id">
                  {{ getWaiterDisplayName(waiter) }}
                  {{ waiter.section ? `(${waiter.section})` : '' }}
                </option>
              </select>
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
                  Set whether this waiter is currently active for table orders
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
            @click="handleUpdate"
            :disabled="!isFormValid || isSubmitting"
            class="px-5 py-2.5 bg-blue-600 hover:bg-blue-700 dark:bg-[#0066FF] dark:hover:bg-[#0055DD] text-white rounded-xl font-bold text-xs shadow-md shadow-blue-600/20 transition cursor-pointer flex items-center gap-2 disabled:opacity-40 disabled:cursor-not-allowed"
          >
            <Loader2 v-if="isSubmitting" class="w-4 h-4 animate-spin" />
            <span>{{ isSubmitting ? 'Saving...' : 'Save Changes' }}</span>
          </button>
        </div>
      </div>
    </div>
  </Teleport>
</template>

<script setup lang="ts">
import { onMounted, onUnmounted, ref, computed } from 'vue'
import { useManagerWaiterStore } from '@/stores/manager/waiterStore'
import WaiterFormModal from '@/components/manager/WaiterFormModal.vue'
import DashboardLayout from '../../Layouts/DashboardLayout.vue'
import {
  Users2,
  UserPlus,
  Search,
  Filter,
  X,
  RefreshCw,
  Maximize2,
  Minimize2,
  RotateCcw,
  Download,
  Edit3,
  Trash2,
  CheckCircle2,
  Clock,
  AlertCircle,
  ChevronLeft,
  ChevronRight,
  MoreVertical,
  Loader2,
} from 'lucide-vue-next'

const waiterStore = useManagerWaiterStore()

const showModal = ref(false)
const showSuccessAlert = ref(false)
const successMessage = ref('')
const isEditMode = ref(false)
const selectedWaiter = ref<any>(null)
const activeMenuId = ref<string | null>(null)

const isFilterOpen = ref(false)
const isFullscreen = ref(false)

const currentPage = ref(1)
const itemsPerPage = ref(10)
const searchQuery = ref('')
const filterStatus = ref<'all' | 'active' | 'inactive' | 'on_break'>('all')
const filterShift = ref('all')
const filterSection = ref('all')

const filteredWaiters = computed(() => {
  let result = waiterStore.normalizedWaiters || []

  if (filterStatus.value !== 'all') {
    result = result.filter((w: any) => w.status === filterStatus.value)
  }

  if (filterShift.value !== 'all') {
    result = result.filter(
      (w: any) => (w.shift || '').toLowerCase() === filterShift.value.toLowerCase(),
    )
  }

  if (filterSection.value !== 'all') {
    result = result.filter(
      (w: any) => (w.section || '').toLowerCase() === filterSection.value.toLowerCase(),
    )
  }

  if (searchQuery.value.trim()) {
    const searchLower = searchQuery.value.toLowerCase().trim()
    result = result.filter((waiter: any) => {
      const name = waiter.name || ''
      const section = waiter.section || ''
      const email = waiter.email || ''
      return (
        name.toLowerCase().includes(searchLower) ||
        section.toLowerCase().includes(searchLower) ||
        email.toLowerCase().includes(searchLower)
      )
    })
  }

  return result
})

const total = computed(() => filteredWaiters.value.length)
const totalPages = computed(() => Math.ceil(total.value / itemsPerPage.value) || 1)

const paginatedWaiters = computed(() => {
  const start = (currentPage.value - 1) * itemsPerPage.value
  const end = start + itemsPerPage.value
  return filteredWaiters.value.slice(start, end)
})

const showingFrom = computed(() => {
  if (total.value === 0) return 0
  return (currentPage.value - 1) * itemsPerPage.value + 1
})

const showingTo = computed(() => {
  return Math.min(currentPage.value * itemsPerPage.value, total.value)
})

const paginationPages = computed(() => {
  const pages: number[] = []
  const max = totalPages.value
  const cur = currentPage.value

  for (let i = Math.max(1, cur - 2); i <= Math.min(max, cur + 2); i++) {
    pages.push(i)
  }
  return pages
})

const changeItemsPerPage = (event: Event) => {
  const target = event.target as HTMLSelectElement
  itemsPerPage.value = Number(target.value)
  currentPage.value = 1
}

const goToPage = (p: number) => {
  if (p >= 1 && p <= totalPages.value) {
    currentPage.value = p
  }
}

const prevPage = () => {
  if (currentPage.value > 1) {
    currentPage.value--
  }
}

const nextPage = () => {
  if (currentPage.value < totalPages.value) {
    currentPage.value++
  }
}

const resetFilters = () => {
  searchQuery.value = ''
  filterStatus.value = 'all'
  filterShift.value = 'all'
  filterSection.value = 'all'
  currentPage.value = 1
}

const toggleFilter = () => {
  isFilterOpen.value = !isFilterOpen.value
}

const toggleFullscreen = () => {
  isFullscreen.value = !isFullscreen.value
}

const totalWaiters = computed(() => waiterStore.normalizedWaiters?.length || 0)
const activeCount = computed(
  () =>
    waiterStore.waiterStats?.active ||
    (waiterStore.normalizedWaiters || []).filter((w: any) => w.status === 'active').length ||
    0,
)
const busyCount = computed(
  () =>
    (waiterStore.normalizedWaiters || []).filter((w: any) => w.status === 'on_break').length || 0,
)
const inactiveCount = computed(
  () =>
    waiterStore.waiterStats?.inactive ||
    (waiterStore.normalizedWaiters || []).filter((w: any) => w.status === 'inactive').length ||
    0,
)

const toggleMenu = (waiterId: string) => {
  activeMenuId.value = activeMenuId.value === waiterId ? null : waiterId
}

const handleOutsideClick = () => {
  activeMenuId.value = null
}

const openAddModal = () => {
  isEditMode.value = false
  selectedWaiter.value = null
  showModal.value = true
}

const openEditModal = (waiter: any) => {
  isEditMode.value = true
  selectedWaiter.value = waiter
  showModal.value = true
}

const closeModal = () => {
  showModal.value = false
  isEditMode.value = false
  selectedWaiter.value = null
}

const handleSubmitWaiter = async (formData: any) => {
  try {
    if (isEditMode.value) {
      const updateData: any = {
        first_name: formData.first_name,
        last_name: formData.last_name,
        email: formData.email,
        phone: formData.phone,
        section: formData.section,
        shift: formData.shift,
        experience_level: formData.experience_level,
        status: formData.status,
        maximum_orders: formData.maximum_orders,
        employee_number: formData.employee_number || null,
        floor_assignments: formData.floor_assignments,
      }
      if (typeof waiterStore.update === 'function') {
        await waiterStore.update(selectedWaiter.value.id, updateData)
      } else if (typeof (waiterStore as any).updateWaiter === 'function') {
        await (waiterStore as any).updateWaiter(selectedWaiter.value.id, updateData)
      }
      successMessage.value = 'Waiter record updated successfully!'
    } else {
      if (typeof waiterStore.create === 'function') {
        await waiterStore.create(formData)
      } else if (typeof (waiterStore as any).createWaiter === 'function') {
        await (waiterStore as any).createWaiter(formData)
      }
      successMessage.value = 'New waiter registered successfully!'
    }

    closeModal()
    showSuccessAlert.value = true
    setTimeout(() => {
      showSuccessAlert.value = false
    }, 4000)

    await refreshData()
  } catch (err: any) {
    console.error('Operation failed:', err)
    const errors = err.response?.data?.errors
    let msg = err.response?.data?.message || err.message || 'Operation failed. Please try again.'
    if (errors && typeof errors === 'object') {
      const details = Object.values(errors).flat().join('\n')
      if (details) msg += `:\n${details}`
    }
    alert(msg)
  }
}

const handleDeleteWaiter = async (waiterId: string) => {
  if (confirm('Are you sure you want to remove this waiter record?')) {
    try {
      if (typeof waiterStore.delete_ === 'function') {
        await waiterStore.delete_(waiterId)
      } else if (typeof (waiterStore as any).deleteWaiter === 'function') {
        await (waiterStore as any).deleteWaiter(waiterId)
      }
      successMessage.value = 'Waiter removed from system.'
      showSuccessAlert.value = true
      setTimeout(() => {
        showSuccessAlert.value = false
      }, 4000)
      await refreshData()
    } catch (err: any) {
      console.error('[ManagerWaiters] Failed to delete waiter:', err)
      alert(err.message || 'Failed to delete waiter.')
    }
  }
}

const handleToggleStatus = async (waiter: any, newStatus: string) => {
  try {
    if (typeof waiterStore.updateStatus === 'function') {
      await waiterStore.updateStatus(waiter.id, newStatus)
    } else if (typeof waiterStore.update === 'function') {
      await waiterStore.update(waiter.id, { status: newStatus })
    }
    waiter.status = newStatus
    successMessage.value = `Waiter status updated to ${newStatus.replace('_', ' ')}!`
    showSuccessAlert.value = true
    setTimeout(() => {
      showSuccessAlert.value = false
    }, 4000)
    await refreshData()
  } catch (err: any) {
    console.error('[ManagerWaiters] Failed to update status:', err)
    alert(err.message || 'Failed to update status')
  }
}

const exportToCSV = () => {
  const rows = filteredWaiters.value.map((w: any) => ({
    Name: w.name,
    Email: w.email,
    Phone: w.phone,
    Status: w.status,
    Section: w.section,
    Shift: w.shift,
    Experience: w.experience_level,
  }))

  if (!rows.length) {
    alert('No data available to export')
    return
  }

  const headers = Object.keys(rows[0]).join(',')
  const values = rows.map((r: any) => Object.values(r).join(',')).join('\n')
  const csvContent = 'data:text/csv;charset=utf-8,' + headers + '\n' + values

  const encodedUri = encodeURI(csvContent)
  const link = document.createElement('a')
  link.setAttribute('href', encodedUri)
  link.setAttribute('download', `waiters_export_${new Date().toISOString().split('T')[0]}.csv`)
  document.body.appendChild(link)
  link.click()
  document.body.removeChild(link)
}

const refreshData = async () => {
  if (typeof waiterStore.load === 'function') {
    await waiterStore.load()
  } else if (typeof (waiterStore as any).fetchWaiters === 'function') {
    await (waiterStore as any).fetchWaiters()
  }
}

onMounted(async () => {
  await refreshData()
  window.addEventListener('click', handleOutsideClick)
})

onUnmounted(() => {
  window.removeEventListener('click', handleOutsideClick)
})
</script>

<template>
  <DashboardLayout>
    <div
      class="space-y-6 bg-slate-50 dark:bg-slate-950 min-h-screen p-4 sm:p-6 max-w-full overflow-hidden font-sans"
      :class="{ 'fixed inset-0 z-50 p-6 overflow-y-auto bg-white dark:bg-slate-950': isFullscreen }"
    >
      <!-- Toast Notification -->
      <div
        v-if="showSuccessAlert"
        class="p-4 bg-emerald-50 dark:bg-emerald-500/10 border border-emerald-200 dark:border-emerald-500/20 text-emerald-700 dark:text-emerald-400 rounded-2xl flex items-center gap-3 shadow-xs"
      >
        <CheckCircle2 class="w-5 h-5 flex-shrink-0" />
        <p class="text-xs font-bold">{{ successMessage }}</p>
      </div>

      <!-- Header -->
      <div
        class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 p-5 sm:p-6 rounded-2xl shadow-xs flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4"
      >
        <div class="flex items-center gap-3">
          <div
            class="w-10 h-10 rounded-xl bg-gradient-to-br from-blue-600 to-blue-700 flex items-center justify-center shadow-md flex-shrink-0 text-white"
          >
            <Users2 class="w-5 h-5 stroke-[2.2]" />
          </div>
          <div>
            <h1 class="text-xl sm:text-2xl font-black text-slate-900 dark:text-white">
              Waiter Management
            </h1>
            <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">
              Manage service staff, shift rosters, and floor assignments.
            </p>
          </div>
        </div>
      </div>

      <!-- KPI Stats Cards Grid -->
      <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
        <div
          class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-5 shadow-xs flex items-center justify-between"
        >
          <div>
            <span
              class="text-[10px] font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider"
              >Total Staff</span
            >
            <h3 class="text-2xl font-black text-slate-900 dark:text-white mt-1">
              {{ totalWaiters }}
            </h3>
          </div>
          <div class="p-3 rounded-2xl bg-blue-500/10 text-blue-600 dark:text-blue-400">
            <Users2 class="w-5 h-5" />
          </div>
        </div>

        <div
          class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-5 shadow-xs flex items-center justify-between"
        >
          <div>
            <span
              class="text-[10px] font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider"
              >Active</span
            >
            <h3 class="text-2xl font-black text-emerald-600 dark:text-emerald-400 mt-1">
              {{ activeCount }}
            </h3>
          </div>
          <div class="p-3 rounded-2xl bg-emerald-500/10 text-emerald-600 dark:text-emerald-400">
            <CheckCircle2 class="w-5 h-5" />
          </div>
        </div>

        <div
          class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-5 shadow-xs flex items-center justify-between"
        >
          <div>
            <span
              class="text-[10px] font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider"
              >On Break</span
            >
            <h3 class="text-2xl font-black text-amber-600 dark:text-amber-400 mt-1">
              {{ busyCount }}
            </h3>
          </div>
          <div class="p-3 rounded-2xl bg-amber-500/10 text-amber-600 dark:text-amber-400">
            <Clock class="w-5 h-5" />
          </div>
        </div>

        <div
          class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-5 shadow-xs flex items-center justify-between"
        >
          <div>
            <span
              class="text-[10px] font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider"
              >Inactive</span
            >
            <h3 class="text-2xl font-black text-slate-600 dark:text-slate-400 mt-1">
              {{ inactiveCount }}
            </h3>
          </div>
          <div
            class="p-3 rounded-2xl bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-400"
          >
            <AlertCircle class="w-5 h-5" />
          </div>
        </div>
      </div>

      <!-- Top Bar Toolbar -->
      <div
        class="flex flex-wrap items-center justify-between gap-3 rounded-2xl border border-slate-200 dark:border-slate-800/80 bg-white dark:bg-[#0b1527] p-3 sm:p-4 shadow-xs transition-all"
      >
        <!-- Left: Search & Filter Toggle -->
        <div class="flex flex-1 items-center gap-2.5 min-w-[280px] max-w-2xl">
          <!-- Search Input -->
          <div class="relative flex-1">
            <Search
              class="w-4 h-4 absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400 dark:text-slate-500"
            />
            <input
              v-model="searchQuery"
              type="text"
              placeholder="Search staff by name, section, or email..."
              class="w-full rounded-xl border border-slate-200 dark:border-[#1e3455] bg-slate-50/80 dark:bg-[#13233c] text-slate-900 dark:text-white placeholder-slate-400 dark:placeholder-slate-500 pl-10 pr-4 py-2.5 text-xs sm:text-sm focus:ring-2 focus:ring-blue-500/40 focus:border-blue-500 dark:focus:border-blue-400 transition outline-none"
            />
          </div>

          <!-- Filter Toggle Button -->
          <button
            type="button"
            @click="toggleFilter"
            class="inline-flex items-center gap-2 rounded-xl px-4 py-2.5 text-xs sm:text-sm font-semibold transition cursor-pointer flex-shrink-0"
            :class="[
              isFilterOpen
                ? 'bg-blue-600/10 dark:bg-blue-900/30 text-blue-600 dark:text-blue-400 border border-blue-500/40'
                : 'border border-slate-200 dark:border-[#1e3455] bg-white dark:bg-[#13233c]/80 text-slate-700 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-[#1c3356]',
            ]"
          >
            <component :is="isFilterOpen ? X : Filter" class="w-4 h-4" />
            <span>{{ isFilterOpen ? 'Hide Filter' : 'Filter' }}</span>
          </button>
        </div>

        <!-- Right: Action Buttons -->
        <div class="flex items-center gap-2 sm:gap-2.5">
          <!-- Export CSV -->
          <button
            type="button"
            @click="exportToCSV"
            class="inline-flex items-center gap-1.5 rounded-xl border border-slate-200 dark:border-[#1e3455] bg-white dark:bg-[#13233c]/80 px-3 py-2 text-xs font-bold text-slate-700 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-[#1c3356] transition cursor-pointer"
          >
            <Download class="w-3.5 h-3.5" />
            <span>Export CSV</span>
          </button>

          <!-- Refresh Button -->
          <button
            type="button"
            @click="refreshData"
            :disabled="waiterStore.loading"
            title="Refresh"
            class="inline-flex items-center justify-center h-9 w-9 sm:h-10 sm:w-10 rounded-xl border border-slate-200 dark:border-[#1e3455] bg-white dark:bg-[#13233c]/80 text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-[#1c3356] hover:text-slate-900 dark:hover:text-white transition disabled:opacity-50 cursor-pointer"
          >
            <RefreshCw class="w-4 h-4" :class="{ 'animate-spin': waiterStore.loading }" />
          </button>

          <!-- Fullscreen Toggle -->
          <button
            type="button"
            @click="toggleFullscreen"
            title="Toggle Fullscreen"
            class="inline-flex items-center justify-center h-9 w-9 sm:h-10 sm:w-10 rounded-xl border border-slate-200 dark:border-[#1e3455] bg-white dark:bg-[#13233c]/80 text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-[#1c3356] hover:text-slate-900 dark:hover:text-white transition cursor-pointer"
          >
            <component :is="isFullscreen ? Minimize2 : Maximize2" class="w-4 h-4" />
          </button>

          <!-- Add Waiter Button -->
          <button
            type="button"
            @click="openAddModal"
            class="inline-flex items-center gap-1.5 rounded-xl bg-blue-600 hover:bg-blue-700 text-white px-3.5 sm:px-4 py-2.5 text-xs sm:text-sm font-bold shadow-md shadow-blue-600/25 transition active:scale-98 cursor-pointer flex-shrink-0"
          >
            <UserPlus class="w-4 h-4 text-white" />
            <span class="text-white">Add Waiter</span>
          </button>
        </div>
      </div>

      <!-- Expandable Filter Panel -->
      <Transition
        enter-active-class="transition duration-200 ease-out"
        enter-from-class="transform -translate-y-2 opacity-0 scale-98"
        enter-to-class="transform translate-y-0 opacity-100 scale-100"
        leave-active-class="transition duration-150 ease-in"
        leave-from-class="transform translate-y-0 opacity-100 scale-100"
        leave-to-class="transform -translate-y-2 opacity-0 scale-98"
      >
        <div
          v-if="isFilterOpen"
          class="rounded-2xl border border-slate-200 dark:border-slate-800/80 bg-white dark:bg-[#0b1527] p-4 sm:p-5 shadow-sm space-y-4"
        >
          <div class="grid grid-cols-1 sm:grid-cols-4 gap-3.5 sm:gap-4">
            <!-- Status Filter -->
            <div>
              <label class="mb-1.5 block text-xs font-semibold text-slate-600 dark:text-slate-300">
                Staff Status
              </label>
              <select
                v-model="filterStatus"
                class="w-full rounded-xl border border-slate-200 dark:border-[#1e3455] bg-slate-50/80 dark:bg-[#13233c] text-slate-900 dark:text-white px-3.5 py-2 text-xs sm:text-sm focus:ring-2 focus:ring-blue-500/40 focus:border-blue-500 dark:focus:border-blue-400 transition cursor-pointer font-medium outline-none"
              >
                <option value="all">All Statuses</option>
                <option value="active">Active</option>
                <option value="on_break">On Break</option>
                <option value="inactive">Inactive</option>
              </select>
            </div>

            <!-- Shift Filter -->
            <div>
              <label class="mb-1.5 block text-xs font-semibold text-slate-600 dark:text-slate-300">
                Work Shift
              </label>
              <select
                v-model="filterShift"
                class="w-full rounded-xl border border-slate-200 dark:border-[#1e3455] bg-slate-50/80 dark:bg-[#13233c] text-slate-900 dark:text-white px-3.5 py-2 text-xs sm:text-sm focus:ring-2 focus:ring-blue-500/40 focus:border-blue-500 dark:focus:border-blue-400 transition cursor-pointer font-medium outline-none"
              >
                <option value="all">All Shifts</option>
                <option value="morning">Morning Shift</option>
                <option value="afternoon">Afternoon Shift</option>
                <option value="evening">Evening Shift</option>
                <option value="night">Night Shift</option>
              </select>
            </div>

            <!-- Section Filter -->
            <div>
              <label class="mb-1.5 block text-xs font-semibold text-slate-600 dark:text-slate-300">
                Assigned Section
              </label>
              <select
                v-model="filterSection"
                class="w-full rounded-xl border border-slate-200 dark:border-[#1e3455] bg-slate-50/80 dark:bg-[#13233c] text-slate-900 dark:text-white px-3.5 py-2 text-xs sm:text-sm focus:ring-2 focus:ring-blue-500/40 focus:border-blue-500 dark:focus:border-blue-400 transition cursor-pointer font-medium outline-none"
              >
                <option value="all">All Sections</option>
                <option value="Main Dining">Main Dining</option>
                <option value="VIP Lounge">VIP Lounge</option>
                <option value="Terrace">Terrace</option>
                <option value="Poolside">Poolside</option>
                <option value="Room Service">Room Service</option>
              </select>
            </div>

            <!-- Reset Filters -->
            <div class="flex items-end">
              <button
                type="button"
                @click="resetFilters"
                class="w-full inline-flex items-center justify-center gap-1.5 rounded-xl border border-slate-200 dark:border-[#1e3455] bg-slate-100/70 dark:bg-[#13233c] px-3.5 py-2 text-xs font-semibold text-slate-700 dark:text-slate-300 hover:bg-slate-200 dark:hover:bg-[#1c3356] transition cursor-pointer"
              >
                <RotateCcw class="w-3.5 h-3.5" />
                <span>Reset Filters</span>
              </button>
            </div>
          </div>
        </div>
      </Transition>

      <!-- Waiters Table Container -->
      <div
        class="rounded-2xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 shadow-xs overflow-hidden font-sans w-full"
      >
        <!-- Desktop Table View -->
        <div class="hidden md:block overflow-x-auto w-full min-h-[220px]">
          <table class="w-full text-left border-collapse">
            <thead
              class="bg-slate-50/90 dark:bg-[#0c182c] border-b border-slate-200 dark:border-[#1e3455]"
            >
              <tr class="text-[11px] font-bold text-slate-500 dark:text-slate-400 select-none">
                <th class="py-3 px-4 pl-5 whitespace-nowrap">Staff Member</th>
                <th class="py-3 px-4 text-center whitespace-nowrap">Status</th>
                <th class="py-3 px-4 whitespace-nowrap">Section</th>
                <th class="py-3 px-4 whitespace-nowrap">Shift</th>
                <th class="py-3 px-4 whitespace-nowrap">Experience</th>
                <th class="py-3 px-4 text-right pr-5 whitespace-nowrap">Actions</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-100 dark:divide-[#1e3455]/60 text-xs">
              <!-- Loading Spinner State -->
              <tr v-if="waiterStore.loading">
                <td colspan="6" class="px-6 py-20 text-center">
                  <div class="flex flex-col items-center justify-center gap-3">
                    <Loader2 class="w-8 h-8 text-blue-600 dark:text-blue-400 animate-spin" />
                    <span class="text-xs font-bold text-slate-600 dark:text-slate-400"
                      >Loading waiters...</span
                    >
                  </div>
                </td>
              </tr>

              <!-- Data Rows -->
              <template v-else>
                <tr
                  v-for="(waiter, index) in paginatedWaiters"
                  :key="waiter.id"
                  class="hover:bg-slate-50/80 dark:hover:bg-[#13233c]/60 transition-colors duration-150 group"
                >
                  <!-- Staff Avatar & Name -->
                  <td class="py-3 px-4 pl-5 whitespace-nowrap">
                    <div class="flex items-center gap-3">
                      <div
                        class="w-8 h-8 rounded-full bg-blue-500/10 text-blue-600 dark:text-blue-400 flex items-center justify-center font-bold text-xs border border-blue-500/20"
                      >
                        {{ (waiter.name || 'W').charAt(0).toUpperCase() }}
                      </div>
                      <div>
                        <div class="font-bold text-slate-900 dark:text-white">
                          {{ waiter.name }}
                        </div>
                        <div class="text-[10px] text-slate-400">
                          {{
                            waiter.user?.email ||
                            (waiter as any).email ||
                            waiter.phone ||
                            'No contact info'
                          }}
                        </div>
                      </div>
                    </div>
                  </td>

                  <!-- Status -->
                  <td class="py-3 px-4 text-center whitespace-nowrap">
                    <span
                      class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[10px] font-extrabold border uppercase tracking-wider"
                      :class="[
                        waiter.status === 'active'
                          ? 'bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 border-emerald-500/20'
                          : waiter.status === 'on_break'
                            ? 'bg-amber-500/10 text-amber-600 dark:text-amber-400 border-amber-500/20'
                            : 'bg-slate-500/10 text-slate-600 dark:text-slate-400 border-slate-500/20',
                      ]"
                    >
                      {{ (waiter.status || 'active').replace('_', ' ') }}
                    </span>
                  </td>

                  <!-- Section -->
                  <td
                    class="py-3 px-4 whitespace-nowrap font-medium text-slate-700 dark:text-slate-300"
                  >
                    {{ waiter.section || 'General Floor' }}
                  </td>

                  <!-- Shift -->
                  <td
                    class="py-3 px-4 whitespace-nowrap text-slate-600 dark:text-slate-400 font-semibold capitalize"
                  >
                    {{ waiter.shift || 'Morning' }}
                  </td>

                  <!-- Experience -->
                  <td
                    class="py-3 px-4 whitespace-nowrap text-slate-600 dark:text-slate-400 capitalize"
                  >
                    {{ waiter.experience_level || 'Intermediate' }}
                  </td>

                  <!-- Actions -->
                  <td class="py-3 px-4 text-right pr-5 whitespace-nowrap">
                    <div class="relative inline-block text-left">
                      <button
                        type="button"
                        @click.stop="toggleMenu(waiter.id)"
                        class="inline-flex items-center justify-center w-8 h-8 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-[#13233c] text-slate-600 dark:text-slate-300 hover:text-blue-600 dark:hover:text-blue-400 hover:border-blue-300 dark:hover:border-blue-500/50 hover:bg-blue-50/50 dark:hover:bg-blue-950/30 shadow-xs transition cursor-pointer"
                        :class="{
                          'bg-blue-50 dark:bg-blue-950/40 text-blue-600 dark:text-blue-400 border-blue-400 dark:border-blue-500 ring-2 ring-blue-500/20':
                            activeMenuId === waiter.id,
                        }"
                        title="Actions"
                      >
                        <MoreVertical class="w-4 h-4 stroke-[2.2]" />
                      </button>

                      <!-- Dropdown Menu -->
                      <Transition
                        enter-active-class="transition duration-100 ease-out"
                        enter-from-class="transform scale-95 opacity-0"
                        enter-to-class="transform scale-100 opacity-100"
                        leave-active-class="transition duration-75 ease-in"
                        leave-from-class="transform scale-100 opacity-100"
                        leave-to-class="transform scale-95 opacity-0"
                      >
                        <div
                          v-if="activeMenuId === waiter.id"
                          class="absolute right-0 z-50 w-44 rounded-2xl bg-white dark:bg-[#0f1d32] border border-slate-200 dark:border-slate-800 shadow-xl shadow-slate-900/15 dark:shadow-slate-950/60 py-1.5 focus:outline-none"
                          :class="[
                            index >= paginatedWaiters.length - 2 && paginatedWaiters.length > 2
                              ? 'bottom-full mb-1.5'
                              : 'top-full mt-1.5',
                          ]"
                        >
                          <!-- Edit Staff -->
                          <button
                            type="button"
                            @click="
                              openEditModal(waiter)
                              activeMenuId = null
                            "
                            class="w-full flex items-center gap-2.5 px-3.5 py-2 text-xs font-semibold text-slate-700 dark:text-slate-200 hover:bg-blue-50 dark:hover:bg-blue-950/40 hover:text-blue-600 dark:hover:text-blue-400 transition cursor-pointer text-left"
                          >
                            <Edit3 class="w-4 h-4 text-blue-500 flex-shrink-0" />
                            <span>Edit Staff</span>
                          </button>

                          <!-- Change Status (Quick Action) -->
                          <button
                            v-if="waiter.status !== 'active'"
                            type="button"
                            @click="
                              handleToggleStatus(waiter, 'active')
                              activeMenuId = null
                            "
                            class="w-full flex items-center gap-2.5 px-3.5 py-2 text-xs font-semibold text-slate-700 dark:text-slate-200 hover:bg-emerald-50 dark:hover:bg-emerald-950/40 hover:text-emerald-600 dark:hover:text-emerald-400 transition cursor-pointer text-left"
                          >
                            <CheckCircle2 class="w-4 h-4 text-emerald-500 flex-shrink-0" />
                            <span>Set Active</span>
                          </button>

                          <button
                            v-if="waiter.status === 'active'"
                            type="button"
                            @click="
                              handleToggleStatus(waiter, 'on_break')
                              activeMenuId = null
                            "
                            class="w-full flex items-center gap-2.5 px-3.5 py-2 text-xs font-semibold text-slate-700 dark:text-slate-200 hover:bg-amber-50 dark:hover:bg-amber-950/40 hover:text-amber-600 dark:hover:text-amber-400 transition cursor-pointer text-left"
                          >
                            <Clock class="w-4 h-4 text-amber-500 flex-shrink-0" />
                            <span>Set On Break</span>
                          </button>

                          <div class="my-1 border-t border-slate-100 dark:border-slate-800"></div>

                          <!-- Delete Staff -->
                          <button
                            type="button"
                            @click="
                              handleDeleteWaiter(waiter.id)
                              activeMenuId = null
                            "
                            class="w-full flex items-center gap-2.5 px-3.5 py-2 text-xs font-semibold text-rose-600 dark:text-rose-400 hover:bg-rose-50 dark:hover:bg-rose-950/40 transition cursor-pointer text-left"
                          >
                            <Trash2 class="w-4 h-4 text-rose-500 flex-shrink-0" />
                            <span>Delete Staff</span>
                          </button>
                        </div>
                      </Transition>
                    </div>
                  </td>
                </tr>

                <!-- Empty State -->
                <tr v-if="paginatedWaiters.length === 0">
                  <td
                    colspan="6"
                    class="px-6 py-12 text-center text-slate-500 dark:text-slate-400 text-xs font-bold"
                  >
                    No waiter staff found matching your filters.
                  </td>
                </tr>
              </template>
            </tbody>
          </table>
        </div>

        <!-- Mobile Card View -->
        <div class="md:hidden divide-y divide-slate-100 dark:divide-slate-800">
          <div
            v-if="waiterStore.loading"
            class="py-16 text-center flex flex-col items-center justify-center gap-3"
          >
            <Loader2 class="w-8 h-8 text-blue-600 dark:text-blue-400 animate-spin" />
            <span class="text-xs font-bold text-slate-600 dark:text-slate-400"
              >Loading waiters...</span
            >
          </div>
          <template v-else>
            <div
              v-for="waiter in paginatedWaiters"
              :key="waiter.id"
              class="p-4 space-y-3 hover:bg-slate-50/60 dark:hover:bg-slate-800/40 transition"
            >
              <div class="flex items-center justify-between">
                <span class="font-bold text-slate-900 dark:text-white text-sm">
                  {{ waiter.name }}
                </span>
                <span
                  class="px-2 py-0.5 rounded-full text-[10px] font-bold border uppercase"
                  :class="[
                    waiter.status === 'active'
                      ? 'bg-emerald-500/10 text-emerald-600 border-emerald-500/20'
                      : waiter.status === 'on_break'
                        ? 'bg-amber-500/10 text-amber-600 border-amber-500/20'
                        : 'bg-slate-500/10 text-slate-600 border-slate-500/20',
                  ]"
                >
                  {{ waiter.status }}
                </span>
              </div>
              <div
                class="flex items-center justify-between text-xs text-slate-600 dark:text-slate-400 pt-1 border-t border-slate-100 dark:border-slate-800/60"
              >
                <span
                  >Section:
                  <strong class="text-slate-800 dark:text-slate-200">{{
                    waiter.section || 'General'
                  }}</strong></span
                >
                <div class="relative inline-block text-left">
                  <button
                    type="button"
                    @click.stop="toggleMenu('mobile-' + waiter.id)"
                    class="inline-flex items-center justify-center w-7 h-7 rounded-lg border border-slate-200 dark:border-slate-700 bg-white dark:bg-[#13233c] text-slate-600 dark:text-slate-300 hover:text-blue-600 transition cursor-pointer"
                    :class="{
                      'bg-blue-50 dark:bg-blue-950/40 text-blue-600 dark:text-blue-400 border-blue-400':
                        activeMenuId === 'mobile-' + waiter.id,
                    }"
                  >
                    <MoreVertical class="w-3.5 h-3.5" />
                  </button>

                  <div
                    v-if="activeMenuId === 'mobile-' + waiter.id"
                    class="absolute right-0 bottom-full mb-1.5 z-50 w-44 rounded-2xl bg-white dark:bg-[#0f1d32] border border-slate-200 dark:border-slate-800 shadow-xl py-1.5 focus:outline-none"
                  >
                    <button
                      type="button"
                      @click="
                        openEditModal(waiter)
                        activeMenuId = null
                      "
                      class="w-full flex items-center gap-2.5 px-3.5 py-2 text-xs font-semibold text-slate-700 dark:text-slate-200 hover:bg-blue-50 dark:hover:bg-blue-950/40 text-left cursor-pointer"
                    >
                      <Edit3 class="w-4 h-4 text-blue-500" />
                      <span>Edit Staff</span>
                    </button>
                    <button
                      v-if="waiter.status !== 'active'"
                      type="button"
                      @click="
                        handleToggleStatus(waiter, 'active')
                        activeMenuId = null
                      "
                      class="w-full flex items-center gap-2.5 px-3.5 py-2 text-xs font-semibold text-emerald-600 dark:text-emerald-400 hover:bg-emerald-50 dark:hover:bg-emerald-950/40 text-left cursor-pointer"
                    >
                      <CheckCircle2 class="w-4 h-4 text-emerald-500" />
                      <span>Set Active</span>
                    </button>
                    <button
                      v-if="waiter.status === 'active'"
                      type="button"
                      @click="
                        handleToggleStatus(waiter, 'on_break')
                        activeMenuId = null
                      "
                      class="w-full flex items-center gap-2.5 px-3.5 py-2 text-xs font-semibold text-amber-600 dark:text-amber-400 hover:bg-amber-50 dark:hover:bg-amber-950/40 text-left cursor-pointer"
                    >
                      <Clock class="w-4 h-4 text-amber-500" />
                      <span>Set On Break</span>
                    </button>
                    <div class="my-1 border-t border-slate-100 dark:border-slate-800"></div>
                    <button
                      type="button"
                      @click="
                        handleDeleteWaiter(waiter.id)
                        activeMenuId = null
                      "
                      class="w-full flex items-center gap-2.5 px-3.5 py-2 text-xs font-semibold text-rose-600 dark:text-rose-400 hover:bg-rose-50 dark:hover:bg-rose-950/40 text-left cursor-pointer"
                    >
                      <Trash2 class="w-4 h-4 text-rose-500" />
                      <span>Delete Staff</span>
                    </button>
                  </div>
                </div>
              </div>
            </div>
          </template>
        </div>

        <!-- Pagination Footer -->
        <div
          v-if="total > 0"
          class="border-t border-slate-200 dark:border-slate-800/80 px-4 sm:px-6 py-3 sm:py-4 bg-slate-50/50 dark:bg-[#0c182c] flex flex-col sm:flex-row items-center justify-between gap-3 text-xs"
        >
          <div class="text-slate-500 dark:text-slate-400 font-medium">
            Showing
            <span class="font-bold text-slate-900 dark:text-white">{{ showingFrom }}</span> to
            <span class="font-bold text-slate-900 dark:text-white">{{ showingTo }}</span> of
            <span class="font-bold text-slate-900 dark:text-white">{{ total }}</span> staff
          </div>

          <div class="flex items-center gap-2 sm:gap-3">
            <div class="flex items-center gap-1.5">
              <span class="text-slate-500 dark:text-slate-400 font-medium">Per page:</span>
              <select
                :value="itemsPerPage"
                @change="changeItemsPerPage"
                class="rounded-lg border border-slate-200 dark:border-slate-700 bg-white dark:bg-[#13233c] text-slate-900 dark:text-white px-2 py-1 text-xs outline-none"
              >
                <option :value="10">10</option>
                <option :value="20">20</option>
                <option :value="50">50</option>
              </select>
            </div>

            <div class="flex items-center gap-1">
              <button
                @click="prevPage"
                :disabled="currentPage === 1"
                class="p-1 rounded-lg border border-slate-200 dark:border-slate-700 text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800 disabled:opacity-40 transition cursor-pointer"
              >
                <ChevronLeft class="w-4 h-4" />
              </button>

              <button
                v-for="page in paginationPages"
                :key="page"
                @click="goToPage(page)"
                class="px-2.5 py-1 rounded-lg text-xs font-bold transition cursor-pointer"
                :class="[
                  currentPage === page
                    ? 'bg-blue-600 text-white'
                    : 'text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800',
                ]"
              >
                {{ page }}
              </button>

              <button
                @click="nextPage"
                :disabled="currentPage === totalPages"
                class="p-1 rounded-lg border border-slate-200 dark:border-slate-700 text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800 disabled:opacity-40 transition cursor-pointer"
              >
                <ChevronRight class="w-4 h-4" />
              </button>
            </div>
          </div>
        </div>
      </div>

      <!-- Waiter Registration/Edit Modal -->
      <WaiterFormModal
        v-if="showModal"
        :is-open="showModal"
        :is-edit-mode="isEditMode"
        :waiter-data="selectedWaiter"
        :initial-data="selectedWaiter"
        @close="closeModal"
        @submit="handleSubmitWaiter"
      />
    </div>
  </DashboardLayout>
</template>

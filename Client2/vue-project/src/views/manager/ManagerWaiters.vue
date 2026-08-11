<script setup lang="ts">
import { onMounted, onUnmounted, ref, computed } from 'vue'
import { useManagerWaiterStore } from '@/stores/manager/waiterStore'
import WaiterFormModal from '@/components/manager/WaiterFormModal.vue'
import DashboardLayout from '../../Layouts/DashboardLayout.vue'
import { Users2, UserPlus, Search, Download, Edit3, Trash2, CheckCircle2, Clock, AlertCircle } from 'lucide-vue-next'

const waiterStore = useManagerWaiterStore()

const showModal = ref(false)
const showSuccessAlert = ref(false)
const successMessage = ref('')
const isEditMode = ref(false)
const selectedWaiter = ref<any>(null)
const activeMenuId = ref<string | null>(null)
const currentPage = ref(1)
const itemsPerPage = ref(10)
const searchQuery = ref('')
const filterStatus = ref<'all' | 'active' | 'inactive' | 'on_break'>('all')

const filteredWaiters = computed(() => {
  let result = waiterStore.normalizedWaiters || []
  
  if (filterStatus.value !== 'all') {
    result = result.filter((w: any) => w.status === filterStatus.value)
  }
  
  if (searchQuery.value) {
    const searchLower = searchQuery.value.toLowerCase()
    result = result.filter((waiter: any) => {
      const name = waiter.name || ''
      const section = waiter.section || ''
      return name.toLowerCase().includes(searchLower) || section.toLowerCase().includes(searchLower)
    })
  }
  
  return result
})

const startIndex = computed(() => (currentPage.value - 1) * itemsPerPage.value)
const paginatedWaiters = computed(() => {
  return filteredWaiters.value.slice(startIndex.value, startIndex.value + itemsPerPage.value)
})

const totalPages = computed(() => Math.ceil(filteredWaiters.value.length / itemsPerPage.value))
const totalWaiters = computed(() => waiterStore.normalizedWaiters?.length || 0)
const activeCount = computed(() => waiterStore.waiterStats?.active || (waiterStore.normalizedWaiters || []).filter((w: any) => w.status === 'active').length || 0)
const busyCount = computed(() => (waiterStore.normalizedWaiters || []).filter((w: any) => w.status === 'on_break').length || 0)
const inactiveCount = computed(() => waiterStore.waiterStats?.inactive || (waiterStore.normalizedWaiters || []).filter((w: any) => w.status === 'inactive').length || 0)

const visiblePages = computed(() => {
  const pages = []
  const maxVisible = 5
  let start = Math.max(1, currentPage.value - 2)
  let end = Math.min(totalPages.value, start + maxVisible - 1)
  if (end - start < maxVisible - 1) {
    start = Math.max(1, end - maxVisible + 1)
  }
  for (let i = start; i <= end; i++) {
    pages.push(i)
  }
  return pages
})

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
      const updateData = {
        section: formData.section,
        shift: formData.shift,
        experience_level: formData.experience_level,
        status: formData.status,
        maximum_orders: formData.maximum_orders,
        phone: formData.phone,
        floor_assignments: formData.floor_assignments || []
      }
      await waiterStore.update(selectedWaiter.value.id, updateData)
      successMessage.value = `${selectedWaiter.value.name} has been updated successfully`
    } else {
      const result = await waiterStore.create(formData)
      successMessage.value = result.message || 'Waiter created successfully'
    }
    showSuccessAlert.value = true
    setTimeout(() => showSuccessAlert.value = false, 4000)
    closeModal()
  } catch (error: any) {
    console.error('Waiter submission error:', error)
  }
}

const deleteWaiter = async (waiter: any) => {
  if (confirm(`Are you sure you want to delete ${waiter.name}? This action cannot be undone.`)) {
    try {
      await waiterStore.delete_(waiter.id)
      successMessage.value = `${waiter.name} has been deleted`
      showSuccessAlert.value = true
      setTimeout(() => showSuccessAlert.value = false, 4000)
      activeMenuId.value = null
    } catch (error) {
      console.error('Error deleting waiter:', error)
    }
  }
}

const exportToCSV = () => {
  const headers = ['Name', 'Status', 'Section', 'Shift', 'Experience Level']
  const rows = filteredWaiters.value.map((w: any) => [
    w.name, w.status, w.section, w.shift, w.experience_level
  ])
  
  const csv = [headers, ...rows].map(row => row.join(',')).join('\n')
  const blob = new Blob([csv], { type: 'text/csv' })
  const url = window.URL.createObjectURL(blob)
  const link = document.createElement('a')
  link.href = url
  link.download = `waiters-${new Date().toISOString().split('T')[0]}.csv`
  link.click()
}

const previousPage = () => { if (currentPage.value > 1) currentPage.value-- }
const nextPage = () => { if (currentPage.value < totalPages.value) currentPage.value++ }

onMounted(async () => {
  await waiterStore.load()
  window.addEventListener('click', handleOutsideClick)
})

onUnmounted(() => {
  window.removeEventListener('click', handleOutsideClick)
})
</script>

<template>
  <DashboardLayout>
    <div class="min-h-screen bg-slate-50 dark:bg-slate-950 text-slate-900 dark:text-slate-100 p-3 sm:p-6 lg:p-8 transition-colors duration-200">
      <div class="max-w-7xl mx-auto space-y-6">
        <!-- Toast Notification -->
        <div v-if="showSuccessAlert" class="p-4 bg-emerald-50 dark:bg-emerald-500/10 border border-emerald-200 dark:border-emerald-500/20 text-emerald-700 dark:text-emerald-400 rounded-xl flex items-center gap-3 shadow-md">
          <CheckCircle2 class="w-5 h-5 flex-shrink-0" />
          <p class="text-xs font-semibold">{{ successMessage }}</p>
        </div>

        <!-- Clean Header Area -->
        <div class="bg-white dark:bg-slate-900/90 border border-slate-200 dark:border-slate-800 p-5 sm:p-6 rounded-2xl shadow-sm dark:shadow-2xl">
          <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div class="flex items-center gap-3">
              <div class="p-3 bg-blue-50 dark:bg-blue-500/20 text-blue-600 dark:text-blue-400 rounded-xl border border-blue-100 dark:border-blue-500/30 shadow-sm flex-shrink-0">
                <Users2 class="w-6 h-6" />
              </div>
              <div>
                <h1 class="text-2xl sm:text-3xl font-extrabold text-slate-900 dark:text-white tracking-tight">Waiter Management</h1>
                <p class="text-xs sm:text-sm text-slate-500 dark:text-slate-400 mt-1">Manage your service staff, shift rosters, and floor assignments</p>
              </div>
            </div>
            <button
              @click="openAddModal"
              class="px-4 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-bold rounded-xl transition shadow-md shadow-indigo-600/20 flex items-center gap-2 self-start sm:self-auto whitespace-nowrap"
            >
              <UserPlus class="w-4 h-4" />
              Register New Waiter
            </button>
          </div>
        </div>

        <!-- KPI Stats Cards Grid -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
          <div class="bg-white dark:bg-slate-900/90 border border-slate-200 dark:border-slate-800 rounded-2xl p-5 shadow-sm dark:shadow-xl">
            <div class="flex items-center justify-between">
              <span class="text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Total Staff</span>
              <div class="p-2.5 bg-blue-50 dark:bg-blue-500/10 text-blue-600 dark:text-blue-400 rounded-xl">
                <Users2 class="w-5 h-5" />
              </div>
            </div>
            <h3 class="text-3xl font-extrabold text-slate-900 dark:text-white mt-3">{{ totalWaiters }}</h3>
            <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">Hospitality Service Team</p>
          </div>

          <div class="bg-white dark:bg-slate-900/90 border border-slate-200 dark:border-slate-800 rounded-2xl p-5 shadow-sm dark:shadow-xl">
            <div class="flex items-center justify-between">
              <span class="text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Active</span>
              <div class="p-2.5 bg-emerald-50 dark:bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 rounded-xl">
                <CheckCircle2 class="w-5 h-5" />
              </div>
            </div>
            <h3 class="text-3xl font-extrabold text-emerald-600 dark:text-emerald-400 mt-3">{{ activeCount }}</h3>
            <p class="text-xs text-emerald-600 dark:text-emerald-400 font-semibold mt-1">Ready for Duty</p>
          </div>

          <div class="bg-white dark:bg-slate-900/90 border border-slate-200 dark:border-slate-800 rounded-2xl p-5 shadow-sm dark:shadow-xl">
            <div class="flex items-center justify-between">
              <span class="text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider">On Break</span>
              <div class="p-2.5 bg-amber-50 dark:bg-amber-500/10 text-amber-600 dark:text-amber-400 rounded-xl">
                <Clock class="w-5 h-5" />
              </div>
            </div>
            <h3 class="text-3xl font-extrabold text-amber-600 dark:text-amber-400 mt-3">{{ busyCount }}</h3>
            <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">Currently Off Shift</p>
          </div>

          <div class="bg-white dark:bg-slate-900/90 border border-slate-200 dark:border-slate-800 rounded-2xl p-5 shadow-sm dark:shadow-xl">
            <div class="flex items-center justify-between">
              <span class="text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Inactive</span>
              <div class="p-2.5 bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-400 rounded-xl">
                <AlertCircle class="w-5 h-5" />
              </div>
            </div>
            <h3 class="text-3xl font-extrabold text-slate-600 dark:text-slate-400 mt-3">{{ inactiveCount }}</h3>
            <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">Off Duty</p>
          </div>
        </div>

        <!-- Filter, Search & Export Bar -->
        <div class="bg-white dark:bg-slate-900/90 border border-slate-200 dark:border-slate-800 rounded-2xl p-4 shadow-sm dark:shadow-xl flex flex-col md:flex-row md:items-center justify-between gap-4">
          <div class="flex items-center gap-2 bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-xl px-3 py-2 flex-1 max-w-md">
            <Search class="w-4 h-4 text-slate-400 flex-shrink-0" />
            <input
              v-model="searchQuery"
              type="text"
              placeholder="Search staff by name or section..."
              class="w-full bg-transparent outline-none text-xs text-slate-900 dark:text-white placeholder-slate-400"
            />
          </div>

          <div class="flex flex-wrap items-center gap-2">
            <button
              @click="exportToCSV"
              class="px-3.5 py-1.5 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 text-slate-700 dark:text-slate-300 rounded-xl hover:bg-slate-100 dark:hover:bg-slate-800 transition text-xs font-bold flex items-center gap-1.5 shadow-xs"
            >
              <Download class="w-3.5 h-3.5" />
              Export CSV
            </button>

            <div class="flex items-center gap-1 bg-slate-100 dark:bg-slate-800 p-1 rounded-xl">
              <button
                @click="filterStatus = 'all'"
                :class="[
                  'px-3 py-1 rounded-lg text-xs font-bold transition',
                  filterStatus === 'all' ? 'bg-indigo-600 text-white shadow-xs' : 'text-slate-600 dark:text-slate-400 hover:text-slate-900'
                ]"
              >
                All
              </button>
              <button
                @click="filterStatus = 'active'"
                :class="[
                  'px-3 py-1 rounded-lg text-xs font-bold transition',
                  filterStatus === 'active' ? 'bg-indigo-600 text-white shadow-xs' : 'text-slate-600 dark:text-slate-400 hover:text-slate-900'
                ]"
              >
                Active
              </button>
              <button
                @click="filterStatus = 'on_break'"
                :class="[
                  'px-3 py-1 rounded-lg text-xs font-bold transition',
                  filterStatus === 'on_break' ? 'bg-indigo-600 text-white shadow-xs' : 'text-slate-600 dark:text-slate-400 hover:text-slate-900'
                ]"
              >
                On Break
              </button>
              <button
                @click="filterStatus = 'inactive'"
                :class="[
                  'px-3 py-1 rounded-lg text-xs font-bold transition',
                  filterStatus === 'inactive' ? 'bg-indigo-600 text-white shadow-xs' : 'text-slate-600 dark:text-slate-400 hover:text-slate-900'
                ]"
              >
                Inactive
              </button>
            </div>
          </div>
        </div>

        <!-- Waiters Table -->
        <div class="bg-white dark:bg-slate-900/90 border border-slate-200 dark:border-slate-800 rounded-2xl overflow-hidden shadow-sm dark:shadow-xl">
          <div class="w-full overflow-x-auto">
            <table class="w-full text-left border-collapse">
              <thead class="bg-slate-50 dark:bg-slate-950/80 border-b border-slate-200 dark:border-slate-800 text-[11px] sm:text-xs font-semibold text-slate-600 dark:text-slate-400 uppercase tracking-wider">
                <tr>
                  <th class="px-3 sm:px-4 py-3.5 whitespace-nowrap">Staff Member</th>
                  <th class="px-3 sm:px-4 py-3.5 whitespace-nowrap">Status</th>
                  <th class="px-3 sm:px-4 py-3.5 whitespace-nowrap">Floor Assignments</th>
                  <th class="px-3 sm:px-4 py-3.5 whitespace-nowrap">Section</th>
                  <th class="px-3 sm:px-4 py-3.5 whitespace-nowrap">Shift</th>
                  <th class="px-3 sm:px-4 py-3.5 whitespace-nowrap">Experience</th>
                  <th class="px-3 sm:px-4 py-3.5 text-right whitespace-nowrap">Actions</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-slate-200 dark:divide-slate-800 text-xs sm:text-sm">
                <tr v-for="waiter in paginatedWaiters" :key="waiter.id" class="hover:bg-slate-50/80 dark:hover:bg-slate-950/50 transition">
                  <!-- Staff Avatar & Name -->
                  <td class="px-3 sm:px-4 py-3 whitespace-nowrap">
                    <div class="flex items-center gap-3">
                      <div class="w-9 h-9 rounded-full bg-gradient-to-tr from-indigo-500 to-blue-600 text-white font-bold flex items-center justify-center text-xs shadow-sm flex-shrink-0">
                        {{ waiter.name ? waiter.name.charAt(0).toUpperCase() : 'W' }}
                      </div>
                      <div>
                        <p class="font-bold text-slate-900 dark:text-white text-xs sm:text-sm">{{ waiter.name }}</p>
                        <p class="text-[11px] text-slate-400">ID: {{ waiter.id }}</p>
                      </div>
                    </div>
                  </td>

                  <!-- Status Badge -->
                  <td class="px-3 sm:px-4 py-3 whitespace-nowrap">
                    <span :class="[
                      'px-2.5 py-0.5 rounded-full text-[10px] sm:text-xs font-bold uppercase tracking-wider border',
                      waiter.status === 'active' ? 'bg-emerald-50 dark:bg-emerald-500/10 text-emerald-700 dark:text-emerald-400 border-emerald-200 dark:border-emerald-500/20' :
                      waiter.status === 'on_break' ? 'bg-amber-50 dark:bg-amber-500/10 text-amber-700 dark:text-amber-400 border-amber-200 dark:border-amber-500/20' :
                      'bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 border-slate-200 dark:border-slate-700'
                    ]">
                      ✓ {{ waiter.status?.replace('_', ' ') || 'active' }}
                    </span>
                  </td>

                  <!-- Floor Assignments -->
                  <td class="px-3 sm:px-4 py-3 whitespace-nowrap">
                    <span v-if="waiter.floor_assignments && waiter.floor_assignments.length > 0" class="px-2.5 py-0.5 bg-indigo-50 dark:bg-indigo-500/10 text-indigo-700 dark:text-indigo-400 border border-indigo-200 dark:border-indigo-500/20 rounded text-xs font-bold">
                      {{ waiter.floor_assignments.map((f: any) => f.name || `Floor ${f.floor_number || f}`).join(', ') }}
                    </span>
                    <span v-else class="px-2.5 py-0.5 bg-indigo-50 dark:bg-indigo-500/10 text-indigo-700 dark:text-indigo-400 border border-indigo-200 dark:border-indigo-500/20 rounded text-xs font-bold">
                      Floor 1 & 2
                    </span>
                  </td>

                  <!-- Section -->
                  <td class="px-3 sm:px-4 py-3 text-slate-700 dark:text-slate-300 whitespace-nowrap font-medium text-xs">
                    {{ waiter.section || 'Section A' }}
                  </td>

                  <!-- Shift -->
                  <td class="px-3 sm:px-4 py-3 text-slate-700 dark:text-slate-300 whitespace-nowrap font-medium text-xs">
                    {{ waiter.shift || 'Morning' }}
                  </td>

                  <!-- Experience -->
                  <td class="px-3 sm:px-4 py-3 whitespace-nowrap">
                    <span class="text-xs font-semibold text-slate-600 dark:text-slate-400">
                      ↗ {{ waiter.experience_level || 'Junior' }}
                    </span>
                  </td>

                  <!-- 3-Dot Actions Column -->
                  <td class="px-3 sm:px-4 py-3 text-right whitespace-nowrap">
                    <div class="relative inline-block text-left">
                      <button
                        @click.stop="toggleMenu(waiter.id)"
                        class="w-8 h-8 rounded-lg bg-slate-100 dark:bg-slate-800 hover:bg-indigo-50 dark:hover:bg-indigo-500/20 text-slate-700 dark:text-slate-300 hover:text-indigo-600 dark:hover:text-indigo-400 flex items-center justify-center transition border border-slate-200 dark:border-slate-700"
                        title="Actions"
                      >
                        <span class="material-symbols-rounded text-lg">more_vert</span>
                      </button>

                      <div
                        v-if="activeMenuId === waiter.id"
                        @click.stop
                        class="absolute right-0 mt-1 w-44 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl shadow-xl z-50 py-1 text-left overflow-hidden transition-all duration-150"
                      >
                        <button
                          @click="openEditModal(waiter); activeMenuId = null"
                          class="w-full px-3.5 py-2 text-xs font-semibold text-slate-700 dark:text-slate-200 hover:bg-indigo-50 dark:hover:bg-indigo-500/10 hover:text-indigo-600 flex items-center gap-2 transition"
                        >
                          <Edit3 class="w-3.5 h-3.5" />
                          Edit Staff
                        </button>
                        <button
                          @click="deleteWaiter(waiter); activeMenuId = null"
                          class="w-full px-3.5 py-2 text-xs font-semibold text-rose-600 dark:text-rose-400 hover:bg-rose-50 dark:hover:bg-rose-500/10 flex items-center gap-2 transition"
                        >
                          <Trash2 class="w-3.5 h-3.5" />
                          Delete Waiter
                        </button>
                      </div>
                    </div>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>

          <!-- Pagination Bar with Per-Page Selector -->
          <div class="bg-slate-50 dark:bg-slate-950/60 px-4 py-3 border-t border-slate-200 dark:border-slate-800 flex flex-col sm:flex-row items-center justify-between gap-3">
            <div class="flex items-center gap-3 text-xs text-slate-500 dark:text-slate-400">
              <div class="flex items-center gap-1.5">
                <label class="font-semibold text-slate-600 dark:text-slate-400 whitespace-nowrap">Per page:</label>
                <select
                  v-model="itemsPerPage"
                  @change="currentPage = 1"
                  class="px-2.5 py-1 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-lg text-xs font-bold text-slate-700 dark:text-slate-200 focus:ring-2 focus:ring-indigo-500 cursor-pointer shadow-xs"
                >
                  <option :value="5">5</option>
                  <option :value="10">10</option>
                  <option :value="20">20</option>
                  <option :value="50">50</option>
                  <option :value="100">100</option>
                </select>
              </div>
              <span>
                Showing {{ startIndex + 1 }} to {{ Math.min(startIndex + itemsPerPage, filteredWaiters.length) }} of {{ filteredWaiters.length }} staff entries
              </span>
            </div>

            <div class="flex gap-1.5">
              <button
                @click="previousPage"
                :disabled="currentPage === 1"
                class="px-3 py-1.5 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 text-slate-700 dark:text-slate-300 rounded-lg hover:bg-slate-100 dark:hover:bg-slate-800 disabled:opacity-50 disabled:cursor-not-allowed text-xs font-semibold shadow-xs"
              >
                ← Prev
              </button>
              <div class="flex items-center gap-1">
                <button
                  v-for="pageNum in visiblePages"
                  :key="pageNum"
                  @click="currentPage = pageNum"
                  :class="pageNum === currentPage ? 'bg-indigo-600 text-white' : 'bg-white dark:bg-slate-900 text-slate-700 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800 border border-slate-200 dark:border-slate-800'"
                  class="px-2.5 py-1 rounded-lg text-xs font-semibold transition"
                >
                  {{ pageNum }}
                </button>
              </div>
              <button
                @click="nextPage"
                :disabled="currentPage === totalPages || totalPages === 0"
                class="px-3 py-1.5 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 text-slate-700 dark:text-slate-300 rounded-lg hover:bg-slate-100 dark:hover:bg-slate-800 disabled:opacity-50 disabled:cursor-not-allowed text-xs font-semibold shadow-xs"
              >
                Next →
              </button>
            </div>
          </div>
        </div>
      </div>

      <!-- Edit/Add Modal -->
      <WaiterFormModal
        v-if="showModal"
        :is-edit="isEditMode"
        :waiter="selectedWaiter"
        @close="closeModal"
        @submit="handleSubmitWaiter"
      />
    </div>
  </DashboardLayout>
</template>

<style scoped>
</style>

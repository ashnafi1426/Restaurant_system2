<script setup lang="ts">
import { onMounted, ref, computed } from 'vue'
import { useManagerWaiterStore } from '@/stores/manager/waiterStore'
import WaiterFormModal from '@/components/manager/WaiterFormModal.vue'
import DashboardLayout from '../../Layouts/DashboardLayout.vue'
import { Users2, UserPlus, Search, MoreVertical, Download, Edit3, Trash2, CheckCircle2, Clock, AlertCircle, TrendingUp } from 'lucide-vue-next'

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

const paginatedWaiters = computed(() => {
  const start = (currentPage.value - 1) * itemsPerPage.value
  const end = start + itemsPerPage.value
  return filteredWaiters.value.slice(start, end)
})

const totalPages = computed(() => Math.ceil(filteredWaiters.value.length / itemsPerPage.value))
const totalWaiters = computed(() => waiterStore.normalizedWaiters?.length || 0)
const activeCount = computed(() => waiterStore.waiterStats?.active || 0)
const busyCount = computed(() => (waiterStore.normalizedWaiters || []).filter((w: any) => w.status === 'on_break').length)
const inactiveCount = computed(() => waiterStore.waiterStats?.inactive || 0)

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

const toggleMenu = (waiterId: string) => {
  activeMenuId.value = activeMenuId.value === waiterId ? null : waiterId
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

const getStatusColor = (status: string) => {
  switch (status) {
    case 'active': return 'bg-emerald-100 text-emerald-700 border-emerald-300'
    case 'on_break': return 'bg-amber-100 text-amber-700 border-amber-300'
    case 'inactive': return 'bg-slate-100 text-slate-700 border-slate-300'
    default: return 'bg-slate-100 text-slate-700 border-slate-300'
  }
}

const getStatusIcon = (status: string) => {
  switch (status) {
    case 'active': return '✓'
    case 'on_break': return '⏸'
    case 'inactive': return '✕'
    default: return '•'
  }
}

const getAvailabilityStatus = (status: string) => {
  switch (status) {
    case 'active': return { label: 'Active', color: 'text-emerald-600', dot: '🟢' }
    case 'on_break': return { label: 'On Break', color: 'text-amber-600', dot: '🟡' }
    case 'inactive': return { label: 'Inactive', color: 'text-slate-600', dot: '⚪' }
    default: return { label: 'Offline', color: 'text-slate-600', dot: '⚪' }
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

onMounted(async () => {
  await waiterStore.load()
})
</script>

<template>
  <DashboardLayout>
    <div class="min-h-screen bg-gradient-to-br from-slate-50 via-white to-blue-50/30 pb-12">
      <!-- Fixed Header -->
      <div class="fixed top-0 right-0 left-0 z-40 bg-white border-b border-slate-200">
        <div class="px-8 py-6">
          <div class="flex items-center justify-between">
            <div>
              <h1 class="text-3xl font-bold text-slate-900 flex items-center gap-3">
                <Users2 class="w-9 h-9 text-blue-600" />
                Waiter Management
              </h1>
              <p class="text-slate-600 text-sm mt-1">Manage your service staff, shifts, and floor assignments</p>
            </div>
            <button
              @click="openAddModal"
              class="flex items-center gap-2 px-6 py-2.5 bg-gradient-to-r from-blue-600 to-blue-700 hover:from-blue-700 hover:to-blue-800 text-white font-semibold rounded-lg transition shadow-lg flex-shrink-0"
            >
              <UserPlus class="w-5 h-5" />
              Register New Waiter
            </button>
          </div>
        </div>
      </div>

      <!-- Page Content - Starts below fixed header -->
      <div class="pt-40">
        <!-- Success Alert -->
        <transition name="slide-down">
          <div v-if="showSuccessAlert" class="fixed top-32 right-8 z-50 p-4 bg-emerald-50 border-l-4 border-emerald-600 rounded-lg flex items-center gap-3 shadow-lg max-w-md">
            <CheckCircle2 class="w-6 h-6 text-emerald-600 flex-shrink-0" />
            <p class="text-sm font-medium text-emerald-800">{{ successMessage }}</p>
          </div>
        </transition>

        <div class="px-8 py-8 space-y-8">
          <!-- Stats Cards -->
          <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
            <div class="bg-white rounded-xl border-2 border-blue-200 p-6 hover:shadow-lg transition">
              <div class="flex items-start justify-between">
                <div>
                  <p class="text-xs font-bold text-slate-500 uppercase tracking-widest">Total Staff</p>
                  <h3 class="text-4xl font-bold text-slate-900 mt-2">{{ totalWaiters }}</h3>
                  <p class="text-sm text-slate-600 mt-2">Hospitality Staff</p>
                </div>
                <div class="p-3 bg-blue-100 rounded-lg"><Users2 class="w-6 h-6 text-blue-600" /></div>
              </div>
            </div>

            <div class="bg-white rounded-xl border-2 border-emerald-200 p-6 hover:shadow-lg transition">
              <div class="flex items-start justify-between">
                <div>
                  <p class="text-xs font-bold text-slate-500 uppercase tracking-widest">Active</p>
                  <h3 class="text-4xl font-bold text-emerald-600 mt-2">{{ activeCount }}</h3>
                  <p class="text-sm text-slate-600 mt-2">Ready for Duty</p>
                </div>
                <div class="p-3 bg-emerald-100 rounded-lg"><CheckCircle2 class="w-6 h-6 text-emerald-600" /></div>
              </div>
            </div>

            <div class="bg-white rounded-xl border-2 border-amber-200 p-6 hover:shadow-lg transition">
              <div class="flex items-start justify-between">
                <div>
                  <p class="text-xs font-bold text-slate-500 uppercase tracking-widest">On Break</p>
                  <h3 class="text-4xl font-bold text-amber-600 mt-2">{{ busyCount }}</h3>
                  <p class="text-sm text-slate-600 mt-2">Currently Off</p>
                </div>
                <div class="p-3 bg-amber-100 rounded-lg"><Clock class="w-6 h-6 text-amber-600" /></div>
              </div>
            </div>

            <div class="bg-white rounded-xl border-2 border-slate-200 p-6 hover:shadow-lg transition">
              <div class="flex items-start justify-between">
                <div>
                  <p class="text-xs font-bold text-slate-500 uppercase tracking-widest">Inactive</p>
                  <h3 class="text-4xl font-bold text-slate-600 mt-2">{{ inactiveCount }}</h3>
                  <p class="text-sm text-slate-600 mt-2">Off Duty</p>
                </div>
                <div class="p-3 bg-slate-100 rounded-lg"><AlertCircle class="w-6 h-6 text-slate-600" /></div>
              </div>
            </div>
          </div>

          <!-- Controls -->
          <div class="flex flex-col gap-4">
            <div class="flex items-center gap-3 bg-white border border-slate-200 rounded-lg px-4 py-3">
              <Search class="w-5 h-5 text-slate-400 flex-shrink-0" />
              <input
                v-model="searchQuery"
                type="text"
                placeholder="Search by name or section..."
                class="flex-1 outline-none text-slate-900 placeholder-slate-400 bg-transparent"
              />
            </div>

            <div class="flex gap-3">
              <button
                @click="exportToCSV"
                class="flex items-center gap-2 px-4 py-2 border border-slate-300 rounded-lg text-slate-700 hover:bg-slate-50 font-medium transition"
              >
                <Download class="w-4 h-4" />
                Export CSV
              </button>

              <div class="flex gap-2 ml-auto">
                <button
                  @click="filterStatus = 'all'"
                  :class="[
                    'px-4 py-2 rounded-lg font-medium transition',
                    filterStatus === 'all'
                      ? 'bg-blue-600 text-white'
                      : 'border border-slate-300 text-slate-700 hover:bg-slate-50'
                  ]"
                >
                  All
                </button>
                <button
                  @click="filterStatus = 'active'"
                  :class="[
                    'px-4 py-2 rounded-lg font-medium transition',
                    filterStatus === 'active'
                      ? 'bg-blue-600 text-white'
                      : 'border border-slate-300 text-slate-700 hover:bg-slate-50'
                  ]"
                >
                  Active
                </button>
                <button
                  @click="filterStatus = 'on_break'"
                  :class="[
                    'px-4 py-2 rounded-lg font-medium transition',
                    filterStatus === 'on_break'
                      ? 'bg-blue-600 text-white'
                      : 'border border-slate-300 text-slate-700 hover:bg-slate-50'
                  ]"
                >
                  On Break
                </button>
                <button
                  @click="filterStatus = 'inactive'"
                  :class="[
                    'px-4 py-2 rounded-lg font-medium transition',
                    filterStatus === 'inactive'
                      ? 'bg-blue-600 text-white'
                      : 'border border-slate-300 text-slate-700 hover:bg-slate-50'
                  ]"
                >
                  Inactive
                </button>
              </div>
            </div>
          </div>

          <!-- Loading State -->
          <div v-if="waiterStore.loading" class="flex justify-center items-center py-32">
            <div class="text-center">
              <div class="animate-spin rounded-full h-12 w-12 border-4 border-slate-200 border-t-blue-600 mx-auto mb-4"></div>
              <p class="text-slate-600 font-medium">Loading waiters...</p>
            </div>
          </div>

          <!-- Empty State -->
          <div v-else-if="filteredWaiters.length === 0" class="bg-white rounded-lg border border-slate-200 p-12 text-center">
            <Users2 class="w-12 h-12 text-slate-400 mx-auto mb-4" />
            <p class="text-slate-600 text-lg font-medium mb-2">No waiters found</p>
            <p class="text-slate-500 mb-6">{{ searchQuery ? 'Try adjusting your search' : 'Register your first waiter to get started' }}</p>
            <button
              @click="openAddModal"
              class="inline-flex items-center gap-2 px-6 py-2 bg-blue-600 hover:bg-blue-700 text-white font-semibold rounded-lg transition"
            >
              <UserPlus class="w-4 h-4" />
              Register Waiter
            </button>
          </div>

          <!-- Staff Table -->
          <div v-else class="bg-white rounded-xl border border-slate-200 overflow-hidden shadow-lg">
            <div class="overflow-x-auto">
              <table class="w-full">
                <thead>
                  <tr class="bg-gradient-to-r from-slate-50 to-slate-100 border-b border-slate-200">
                    <th class="px-6 py-4 text-left text-xs font-bold text-slate-700 uppercase tracking-wider">Staff Member</th>
                    <th class="px-6 py-4 text-left text-xs font-bold text-slate-700 uppercase tracking-wider">Status</th>
                    <th class="px-6 py-4 text-left text-xs font-bold text-slate-700 uppercase tracking-wider">Section</th>
                    <th class="px-6 py-4 text-left text-xs font-bold text-slate-700 uppercase tracking-wider">Shift</th>
                    <th class="px-6 py-4 text-left text-xs font-bold text-slate-700 uppercase tracking-wider">Experience</th>
                    <th class="px-6 py-4 text-left text-xs font-bold text-slate-700 uppercase tracking-wider">Phone</th>
                    <th class="px-6 py-4 text-center text-xs font-bold text-slate-700 uppercase tracking-wider">Actions</th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-for="waiter in paginatedWaiters" :key="waiter.id" class="border-b border-slate-200 hover:bg-slate-50/50 transition group">
                    <td class="px-6 py-4">
                      <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-full bg-gradient-to-br from-blue-500 to-indigo-600 flex items-center justify-center text-white font-bold text-sm flex-shrink-0">
                          {{ (waiter.name || 'U').charAt(0).toUpperCase() }}
                        </div>
                        <div>
                          <p class="font-semibold text-slate-900">{{ waiter.name }}</p>
                          <p class="text-xs text-slate-500">ID: {{ waiter.id }}</p>
                        </div>
                      </div>
                    </td>
                    <td class="px-6 py-4">
                      <span :class="['inline-flex items-center gap-2 px-3 py-1 rounded-full text-xs font-bold border', getStatusColor(waiter.status)]">
                        {{ getStatusIcon(waiter.status) }}
                        {{ getAvailabilityStatus(waiter.status).label }}
                      </span>
                    </td>
                    <td class="px-6 py-4">
                      <span class="inline-flex items-center gap-2 px-3 py-1 bg-slate-100 text-slate-700 rounded-lg text-sm font-medium">
                        {{ waiter.section }}
                      </span>
                    </td>
                    <td class="px-6 py-4 text-sm text-slate-700 font-medium capitalize">
                      {{ waiter.shift }}
                    </td>
                    <td class="px-6 py-4">
                      <span class="inline-flex items-center gap-1 text-sm font-medium text-slate-700">
                        <TrendingUp class="w-4 h-4 text-blue-600" />
                        {{ waiter.experience_level ? waiter.experience_level.charAt(0).toUpperCase() + waiter.experience_level.slice(1) : 'N/A' }}
                      </span>
                    </td>
                    <td class="px-6 py-4 text-sm text-slate-600">
                      {{ waiter.phone || 'N/A' }}
                    </td>
                    <td class="px-6 py-4 text-center relative">
                      <div class="relative inline-block">
                        <button
                          @click="toggleMenu(waiter.id)"
                          class="p-2 text-slate-500 hover:text-slate-700 hover:bg-slate-100 rounded-lg transition"
                        >
                          <MoreVertical class="w-5 h-5" />
                        </button>
                        <transition name="fade">
                          <div
                            v-if="activeMenuId === waiter.id"
                            class="absolute right-0 top-full mt-2 w-52 bg-white border border-slate-200 rounded-lg shadow-xl z-20"
                          >
                            <button
                              @click="() => { openEditModal(waiter); activeMenuId = null }"
                              class="w-full text-left px-4 py-3 text-slate-700 hover:bg-blue-50 text-sm border-b border-slate-100 transition flex items-center gap-2"
                            >
                              <Edit3 class="w-4 h-4 text-blue-600" />
                              Edit Details
                            </button>
                            <button
                              @click="() => { deleteWaiter(waiter); activeMenuId = null }"
                              class="w-full text-left px-4 py-3 text-red-600 hover:bg-red-50 text-sm transition flex items-center gap-2"
                            >
                              <Trash2 class="w-4 h-4" />
                              Delete
                            </button>
                          </div>
                        </transition>
                      </div>
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>

            <!-- Pagination -->
            <div class="flex items-center justify-between px-6 py-4 bg-slate-50 border-t border-slate-200">
              <p class="text-sm text-slate-600 font-medium">
                Showing <span class="text-slate-900 font-bold">{{ (currentPage - 1) * itemsPerPage + 1 }}</span> to <span class="text-slate-900 font-bold">{{ Math.min(currentPage * itemsPerPage, filteredWaiters.length) }}</span> of <span class="text-slate-900 font-bold">{{ filteredWaiters.length }}</span> waiters
              </p>
              <div class="flex items-center gap-1">
                <button
                  @click="currentPage = Math.max(1, currentPage - 1)"
                  :disabled="currentPage === 1"
                  class="px-3 py-1 border border-slate-300 rounded-lg text-slate-700 hover:bg-slate-100 disabled:opacity-50 disabled:cursor-not-allowed transition font-medium"
                >
                  ← Prev
                </button>
                <div class="flex items-center gap-1 mx-2">
                  <button
                    v-for="page in totalPages"
                    :key="page"
                    @click="currentPage = page"
                    v-show="page <= 5 || page === totalPages || (page >= currentPage - 1 && page <= currentPage + 1)"
                    :class="[
                      'px-3 py-1 rounded-lg font-medium transition',
                      currentPage === page
                        ? 'bg-blue-600 text-white'
                        : 'border border-slate-300 text-slate-700 hover:bg-slate-100'
                    ]"
                  >
                    {{ page }}
                  </button>
                </div>
                <button
                  @click="currentPage = Math.min(totalPages, currentPage + 1)"
                  :disabled="currentPage === totalPages"
                  class="px-3 py-1 border border-slate-300 rounded-lg text-slate-700 hover:bg-slate-100 disabled:opacity-50 disabled:cursor-not-allowed transition font-medium"
                >
                  Next →
                </button>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Modal -->
    <WaiterFormModal
      :is-open="showModal"
      :is-edit-mode="isEditMode"
      :waiter-data="selectedWaiter"
      @close="closeModal"
      @submit="handleSubmitWaiter"
    />
  </DashboardLayout>
</template>

<style scoped>
.fade-enter-active, .fade-leave-active {
  transition: opacity 0.3s ease;
}
.fade-enter-from, .fade-leave-to {
  opacity: 0;
}
.slide-down-enter-active, .slide-down-leave-active {
  transition: all 0.3s ease;
}
.slide-down-enter-from, .slide-down-leave-to {
  opacity: 0;
  transform: translateY(-10px);
}
</style>

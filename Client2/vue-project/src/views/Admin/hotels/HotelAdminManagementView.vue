<script setup lang="ts">
import { ref, onMounted, onUnmounted, computed, watch } from 'vue'
import DashboardLayout from '@/Layouts/DashboardLayout.vue'
import SearchableSelect from '@/components/common/SearchableSelect.vue'
import { platformService, type Hotel } from '@/services/platformService'
import {
  UserCheck,
  Plus,
  Search,
  RefreshCw,
  KeyRound,
  Shield,
  Building2,
  Mail,
  Phone,
  Power,
  CheckCircle2,
  AlertCircle,
  X,
  Copy,
  ExternalLink,
  Send,
  Filter,
  Minimize2,
  Maximize2,
  RotateCcw,
  Loader2,
  MoreVertical,
  ChevronLeft,
  ChevronRight,
  ChevronsLeft,
  ChevronsRight
} from 'lucide-vue-next'

interface AdminItem {
  id: string
  hotel_id: string
  user_id: string
  role: string
  is_active: boolean
  created_at: string
  user?: {
    id: string
    first_name: string
    last_name: string
    email: string
    phone?: string
    is_active: boolean
  }
  hotel?: {
    id: string
    name: string
    slug: string
  }
}

const admins = ref<AdminItem[]>([])
const hotels = ref<Hotel[]>([])
const loading = ref(true)
const saving = ref(false)
const isFilterOpen = ref(false)
const isFullscreen = ref(false)
const searchQuery = ref('')
const selectedHotelId = ref('all')
const selectedStatus = ref('all')
const successMessage = ref('')
const errorMessage = ref('')
const copied = ref(false)

const toggleFilter = () => {
  isFilterOpen.value = !isFilterOpen.value
}

const toggleFullscreen = () => {
  isFullscreen.value = !isFullscreen.value
}

const resetFilters = () => {
  searchQuery.value = ''
  selectedHotelId.value = 'all'
  selectedStatus.value = 'all'
}

// Pagination
const currentPage = ref(1)
const totalPages = ref(1)
const totalItems = ref(0)
const itemsPerPage = ref(10)
const jumpPage = ref('')
const itemsPerPageOptions = [5, 10, 20, 50]

// Computed properties for pagination
const paginationInfo = computed(() => {
  const start = ((currentPage.value - 1) * itemsPerPage.value) + 1
  const end = Math.min(currentPage.value * itemsPerPage.value, totalItems.value)
  return { start, end }
})

const visiblePages = computed(() => {
  const pages = []
  const maxVisible = 5
  
  if (totalPages.value <= maxVisible) {
    for (let i = 1; i <= totalPages.value; i++) {
      pages.push(i)
    }
  } else {
    const start = Math.max(1, currentPage.value - Math.floor(maxVisible / 2))
    const end = Math.min(totalPages.value, start + maxVisible - 1)
    
    for (let i = start; i <= end; i++) {
      pages.push(i)
    }
  }
  
  return pages
})

// Modals
const showCreateModal = ref(false)
const showResetModal = ref(false)
const createSuccessInfo = ref<{
  name: string
  email: string
  hotel_name: string
} | null>(null)
const selectedAdminUser = ref<any>(null)

const adminForm = ref({
  first_name: '',
  last_name: '',
  email: '',
  phone: '',
  hotel_id: '',
  role: 'admin',
  status: 'active' as 'active' | 'inactive',
})

const loadAdmins = async () => {
  loading.value = true
  errorMessage.value = ''
  try {
    const res = await platformService.getAllAdmins({
      search: searchQuery.value || undefined,
      hotel_id: selectedHotelId.value !== 'all' ? selectedHotelId.value : undefined,
      status: selectedStatus.value !== 'all' ? selectedStatus.value : undefined,
      page: currentPage.value,
      per_page: itemsPerPage.value,
    })
    admins.value = res.data || []
    currentPage.value = res.current_page || 1
    totalPages.value = res.last_page || 1
    totalItems.value = res.total || 0
  } catch (err: any) {
    console.error('[HotelAdminManagement] Load admins error:', err)
    errorMessage.value = err?.response?.data?.message || 'Failed to load hotel administrators.'
  } finally {
    loading.value = false
  }
}

// Pagination functions
const goToPage = (page: number) => {
  if (page >= 1 && page <= totalPages.value && page !== currentPage.value) {
    currentPage.value = page
    loadAdmins()
  }
}

const goToFirstPage = () => {
  goToPage(1)
}

const goToLastPage = () => {
  goToPage(totalPages.value)
}

const nextPage = () => {
  if (currentPage.value < totalPages.value) {
    goToPage(currentPage.value + 1)
  }
}

const prevPage = () => {
  if (currentPage.value > 1) {
    goToPage(currentPage.value - 1)
  }
}

const changeItemsPerPage = () => {
  currentPage.value = 1
  loadAdmins()
}

const jumpToPage = () => {
  const page = parseInt(jumpPage.value)
  if (page && page >= 1 && page <= totalPages.value) {
    goToPage(page)
    jumpPage.value = ''
  }
}

const activeMenu = ref<string | null>(null)

const toggleMenu = (id: string, event?: Event) => {
  if (event) event.stopPropagation()
  activeMenu.value = activeMenu.value === id ? null : id
}

const handleOutsideClick = () => {
  activeMenu.value = null
}

const loadHotels = async () => {
  try {
    const res = await platformService.getHotels({ per_page: 100 })
    hotels.value = res.data || []
  } catch (e) {
    console.warn('Could not load hotels list for dropdown:', e)
  }
}

onMounted(() => {
  loadAdmins()
  loadHotels()
  window.addEventListener('click', handleOutsideClick)
})

onUnmounted(() => {
  window.removeEventListener('click', handleOutsideClick)
})

watch([searchQuery, selectedHotelId, selectedStatus, itemsPerPage], () => {
  currentPage.value = 1
  loadAdmins()
})

const openCreateModal = () => {
  adminForm.value = {
    first_name: '',
    last_name: '',
    email: '',
    phone: '',
    hotel_id: hotels.value[0]?.id || '',
    role: 'admin',
    status: 'active',
  }
  createSuccessInfo.value = null
  showCreateModal.value = true
}

const handleCreateAdmin = async () => {
  if (!adminForm.value.first_name || !adminForm.value.last_name || !adminForm.value.email || !adminForm.value.hotel_id) {
    errorMessage.value = 'Please fill all required fields.'
    return
  }

  saving.value = true
  errorMessage.value = ''
  try {
    const res = await platformService.createHotelAdmin(adminForm.value)
    const assignedHotel = hotels.value.find(h => h.id === adminForm.value.hotel_id)
    createSuccessInfo.value = {
      name: `${adminForm.value.first_name} ${adminForm.value.last_name}`,
      email: adminForm.value.email,
      hotel_name: assignedHotel?.name || 'Assigned Property',
    }
    successMessage.value = res.message || 'Hotel Admin created and login credentials securely sent via email.'
    await loadAdmins()
    setTimeout(() => { successMessage.value = '' }, 5000)
  } catch (err: any) {
    console.error('[HotelAdminManagement] Create admin error:', err)
    errorMessage.value = err?.response?.data?.message || 'Failed to create hotel administrator.'
  } finally {
    saving.value = false
  }
}

const handleResetPassword = async (admin: AdminItem) => {
  if (!admin.user) return
  selectedAdminUser.value = admin.user
  saving.value = true
  try {
    const res = await platformService.resetAdminPassword(admin.user.id)
    showResetModal.value = true
    successMessage.value = res.message || `New system temporary password generated and emailed to ${admin.user.email}.`
    setTimeout(() => { successMessage.value = '' }, 5000)
  } catch (err: any) {
    console.error('[HotelAdminManagement] Reset password error:', err)
    errorMessage.value = err?.response?.data?.message || 'Failed to generate temporary password.'
  } finally {
    saving.value = false
  }
}

const handleResendPasswordByEmail = async (admin: AdminItem) => {
  if (!admin.user) return
  selectedAdminUser.value = admin.user
  saving.value = true
  try {
    const res = await platformService.resendAdminPassword(admin.user.id)
    showResetModal.value = true
    successMessage.value = res.message || `Temporary password sent to ${admin.user.email}!`
    setTimeout(() => { successMessage.value = '' }, 5000)
  } catch (err: any) {
    console.error('[HotelAdminManagement] Resend password error:', err)
    errorMessage.value = err?.response?.data?.message || 'Failed to resend password by email.'
  } finally {
    saving.value = false
  }
}

const handleToggleStatus = async (admin: AdminItem) => {
  try {
    const res = await platformService.toggleAdminStatus(admin.id)
    admin.is_active = res.data.is_active
    successMessage.value = res.message || 'Status updated successfully.'
    setTimeout(() => { successMessage.value = '' }, 3000)
  } catch (err: any) {
    console.error('[HotelAdminManagement] Toggle status error:', err)
    errorMessage.value = err?.response?.data?.message || 'Failed to toggle status.'
  }
}

const copyToClipboard = (text: string) => {
  navigator.clipboard.writeText(text)
  copied.value = true
  setTimeout(() => { copied.value = false }, 3000)
}
</script>

<template>
  <DashboardLayout>
    <div class="space-y-6 bg-slate-50 dark:bg-slate-950 min-h-screen p-4 sm:p-6 max-w-full font-sans">
      <!-- Header Banner -->
      <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 p-6 rounded-3xl shadow-xs flex flex-col md:flex-row md:items-center md:justify-between gap-4">
        <div class="flex items-center gap-3">
          <div class="p-3 rounded-2xl bg-indigo-500/10 text-indigo-600 dark:text-indigo-400 border border-indigo-500/20">
            <UserCheck class="w-6 h-6" />
          </div>
          <div>
            <div class="flex items-center gap-2">
              <h1 class="text-2xl font-black text-slate-900 dark:text-white tracking-tight">
                Hotel Admins
              </h1>
              <span class="px-2.5 py-0.5 rounded-full text-xs font-black bg-indigo-500/10 text-indigo-600 dark:text-indigo-400 border border-indigo-500/20">
                {{ totalItems }} Admins
              </span>
            </div>
            <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">
              Super Admin directory to provision, oversee, reset credentials, and govern administrators across all hotels.
            </p>
          </div>
        </div>

      </div>

      <!-- Alerts -->
      <div v-if="successMessage" class="p-4 rounded-2xl bg-emerald-500/10 border border-emerald-500/20 text-emerald-600 dark:text-emerald-400 text-xs font-bold flex items-center gap-2">
        <CheckCircle2 class="w-4 h-4 flex-shrink-0" />
        <span>{{ successMessage }}</span>
      </div>

      <div v-if="errorMessage" class="p-4 rounded-2xl bg-rose-500/10 border border-rose-500/20 text-rose-600 dark:text-rose-400 text-xs font-bold flex items-center gap-2">
        <AlertCircle class="w-4 h-4 flex-shrink-0" />
        <span>{{ errorMessage }}</span>
      </div>

      <!-- Top Bar Toolbar -->
      <div
        class="flex flex-wrap items-center justify-between gap-3 rounded-2xl border border-slate-200 dark:border-slate-800/80 bg-white dark:bg-[#0b1527] p-3 sm:p-4 shadow-xs transition-all"
      >
        <!-- Left: Search & Filter Toggle -->
        <div class="flex flex-1 items-center gap-2.5 min-w-[280px] max-w-2xl">
          <!-- Search Input -->
          <div class="relative flex-1">
            <Search class="w-4 h-4 absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400 dark:text-slate-500" />
            <input
              v-model="searchQuery"
              type="text"
              placeholder="Search by admin name, email, or hotel..."
              class="w-full rounded-xl border border-slate-200 dark:border-[#1e3455] bg-slate-50/80 dark:bg-[#13233c] text-slate-900 dark:text-white placeholder-slate-400 dark:placeholder-slate-500 pl-10 pr-4 py-2.5 text-xs sm:text-sm focus:ring-2 focus:ring-blue-500/40 focus:border-blue-500 dark:focus:border-blue-400 transition outline-none font-medium"
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
                : 'border border-slate-200 dark:border-[#1e3455] bg-white dark:bg-[#13233c]/80 text-slate-700 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-[#1c3356]'
            ]"
          >
            <component :is="isFilterOpen ? X : Filter" class="w-4 h-4" />
            <span>{{ isFilterOpen ? 'Hide Filter' : 'Filter' }}</span>
          </button>
        </div>

        <!-- Right: Action Buttons -->
        <div class="flex items-center gap-2 sm:gap-2.5">
          <!-- Refresh Button -->
          <button
            type="button"
            @click="loadAdmins"
            :disabled="loading"
            title="Refresh"
            class="inline-flex items-center justify-center h-9 w-9 sm:h-10 sm:w-10 rounded-xl border border-slate-200 dark:border-[#1e3455] bg-white dark:bg-[#13233c]/80 text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-[#1c3356] hover:text-slate-900 dark:hover:text-white transition disabled:opacity-50 cursor-pointer"
          >
            <RefreshCw class="w-4 h-4" :class="{ 'animate-spin': loading }" />
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

          <!-- Add Hotel Admin Primary Button -->
          <button
            type="button"
            @click="openCreateModal"
            class="inline-flex items-center gap-1.5 rounded-xl bg-blue-600 hover:bg-blue-700 text-white px-3.5 sm:px-4 py-2.5 text-xs sm:text-sm font-bold shadow-md shadow-blue-600/25 transition active:scale-98 cursor-pointer flex-shrink-0"
          >
            <Plus class="w-4 h-4 text-white" />
            <span class="text-white">Create Admin</span>
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
          <div class="grid grid-cols-1 sm:grid-cols-3 gap-3.5 sm:gap-4">
            <!-- Hotel Filter -->
            <div>
              <label class="mb-1.5 block text-xs font-semibold text-slate-600 dark:text-slate-300">
                Assigned Hotel
              </label>
              <SearchableSelect
                v-model="selectedHotelId"
                :options="[{ id: 'all', name: 'All Hotels', city: '' }, ...hotels]"
                label-key="name"
                value-key="id"
                sublabel-key="city"
                placeholder="All Hotels"
                search-placeholder="Filter by hotel..."
                :format-option-label="(opt) => opt.id === 'all' ? 'All Hotels' : `${opt.name}${opt.city ? ` (${opt.city})` : ''}`"
              />
            </div>

            <!-- Status Filter -->
            <div>
              <label class="mb-1.5 block text-xs font-semibold text-slate-600 dark:text-slate-300">
                Account Status
              </label>
              <select
                v-model="selectedStatus"
                class="w-full rounded-xl border border-slate-200 dark:border-[#1e3455] bg-slate-50/80 dark:bg-[#13233c] text-slate-900 dark:text-white px-3.5 py-2 text-xs sm:text-sm focus:ring-2 focus:ring-blue-500/40 focus:border-blue-500 dark:focus:border-blue-400 transition cursor-pointer font-medium outline-none h-[38px]"
              >
                <option value="all">All Statuses</option>
                <option value="active">Active</option>
                <option value="inactive">Inactive</option>
              </select>
            </div>

            <!-- Reset Filters -->
            <div class="flex items-end">
              <button
                type="button"
                @click="resetFilters"
                class="w-full inline-flex items-center justify-center gap-1.5 rounded-xl border border-slate-200 dark:border-[#1e3455] bg-slate-100/70 dark:bg-[#13233c] px-3.5 py-2 text-xs font-semibold text-slate-700 dark:text-slate-300 hover:bg-slate-200 dark:hover:bg-[#1c3356] transition cursor-pointer h-[38px]"
              >
                <RotateCcw class="w-3.5 h-3.5" />
                <span>Reset Filters</span>
              </button>
            </div>
          </div>
        </div>
      </Transition>

      <!-- Table -->
      <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-3xl shadow-xs overflow-hidden">
        <div class="overflow-x-auto min-h-[240px]">
          <table class="w-full text-left text-xs border-collapse">
            <thead class="bg-slate-100/70 dark:bg-slate-800/80 text-slate-600 dark:text-slate-300 font-bold border-b border-slate-200 dark:border-slate-700/80">
              <tr>
                <th class="py-4 px-5">Name</th>
                <th class="py-4 px-4">Email</th>
                <th class="py-4 px-4">Hotel</th>
                <th class="py-4 px-4">Role</th>
                <th class="py-4 px-4">Status</th>
                <th class="py-4 px-4">Created Date</th>
                <th class="py-4 px-5 text-right">Actions</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-100 dark:divide-slate-800 text-slate-700 dark:text-slate-300 font-medium">
              <tr v-if="loading">
                <td colspan="7" class="p-12 text-center text-xs text-slate-400">
                  <RefreshCw class="w-6 h-6 text-indigo-500 animate-spin mx-auto mb-2" />
                  <span>Loading administrators...</span>
                </td>
              </tr>
              <tr v-else-if="admins.length === 0">
                <td colspan="7" class="p-12 text-center text-xs text-slate-400">
                  <UserCheck class="w-8 h-8 text-slate-300 dark:text-slate-600 mx-auto mb-2" />
                  <p class="font-bold">No hotel administrators found.</p>
                </td>
              </tr>
              <tr
                v-else
                v-for="adm in admins"
                :key="adm.id"
                class="hover:bg-slate-50/70 dark:hover:bg-slate-800/50 transition"
              >
                <!-- Name -->
                <td class="py-4 px-5">
                  <div class="flex items-center gap-3">
                    <div class="w-9 h-9 rounded-2xl bg-indigo-500/10 border border-indigo-500/20 text-indigo-600 dark:text-indigo-400 flex items-center justify-center font-black text-xs">
                      {{ (adm.user?.first_name || 'A').charAt(0) }}{{ (adm.user?.last_name || 'D').charAt(0) }}
                    </div>
                    <div>
                      <span class="font-bold text-slate-900 dark:text-white block">
                        {{ adm.user?.first_name }} {{ adm.user?.last_name }}
                      </span>
                      <span v-if="adm.user?.phone" class="text-[11px] text-slate-400 block font-mono">
                        {{ adm.user?.phone }}
                      </span>
                    </div>
                  </div>
                </td>

                <!-- Email -->
                <td class="py-4 px-4 font-mono text-slate-600 dark:text-slate-300">
                  {{ adm.user?.email || 'N/A' }}
                </td>

                <!-- Hotel -->
                <td class="py-4 px-4">
                  <div class="flex items-center gap-1.5 font-bold text-slate-800 dark:text-slate-200">
                    <Building2 class="w-3.5 h-3.5 text-indigo-500 flex-shrink-0" />
                    <span>{{ adm.hotel?.name || 'Unassigned' }}</span>
                  </div>
                  <span class="text-[10px] text-slate-400 block ml-5 font-mono">
                    {{ adm.hotel?.slug }}
                  </span>
                </td>

                <!-- Role -->
                <td class="py-4 px-4">
                  <span class="px-2.5 py-1 rounded-lg bg-indigo-500/10 text-indigo-600 dark:text-indigo-400 border border-indigo-500/20 font-black text-[11px]">
                    Admin
                  </span>
                </td>

                <!-- Status -->
                <td class="py-4 px-4">
                  <button
                    @click="handleToggleStatus(adm)"
                    class="px-2.5 py-1 rounded-full text-xs font-black transition cursor-pointer border flex items-center gap-1"
                    :class="adm.is_active ? 'bg-emerald-500/10 text-emerald-600 border-emerald-500/20' : 'bg-slate-500/10 text-slate-500 border-slate-500/20'"
                    :title="adm.is_active ? 'Click to deactivate' : 'Click to activate'"
                  >
                    <Power class="w-3 h-3" />
                    <span>{{ adm.is_active ? 'Active' : 'Inactive' }}</span>
                  </button>
                </td>

                <!-- Created Date -->
                <td class="py-4 px-4 text-slate-400 text-[11px]">
                  {{ new Date(adm.created_at).toLocaleDateString() }}
                </td>

                <!-- Actions -->
                <td class="py-4 px-5 text-right whitespace-nowrap pr-5 relative" @click.stop>
                  <div class="relative inline-block text-left">
                    <button
                      type="button"
                      @click="toggleMenu(String(adm.id), $event)"
                      class="p-1.5 rounded-xl text-slate-400 hover:text-slate-700 dark:hover:text-white hover:bg-slate-100 dark:hover:bg-slate-800 transition cursor-pointer"
                      :class="{ 'bg-slate-100 dark:bg-slate-800 text-slate-900 dark:text-white': activeMenu === String(adm.id) }"
                      title="Actions"
                    >
                      <MoreVertical class="w-4 h-4" />
                    </button>

                    <transition
                      enter-active-class="transition duration-100 ease-out"
                      leave-active-class="transition duration-75 ease-in"
                      enter-from-class="opacity-0 scale-95 -translate-y-2"
                      enter-to-class="opacity-100 scale-100 translate-y-0"
                      leave-from-class="opacity-100 scale-100 translate-y-0"
                      leave-to-class="opacity-0 scale-95 -translate-y-2"
                    >
                      <div
                        v-if="activeMenu === String(adm.id)"
                        class="absolute right-0 top-8 z-50 w-48 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl shadow-2xl p-1.5 space-y-1 text-left"
                      >
                        <button
                          type="button"
                          @click="handleResendPasswordByEmail(adm); activeMenu = null"
                          class="flex w-full items-center gap-2.5 px-3 py-2 rounded-xl text-xs font-bold text-indigo-600 dark:text-indigo-400 hover:bg-indigo-50 dark:hover:bg-indigo-950/40 transition cursor-pointer"
                        >
                          <Send class="w-3.5 h-3.5" />
                          <span>Send by Email</span>
                        </button>

                        <button
                          type="button"
                          @click="handleResetPassword(adm); activeMenu = null"
                          class="flex w-full items-center gap-2.5 px-3 py-2 rounded-xl text-xs font-bold text-slate-700 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800 transition cursor-pointer"
                        >
                          <KeyRound class="w-3.5 h-3.5 text-amber-500" />
                          <span>Reset Password</span>
                        </button>

                        <button
                          type="button"
                          @click="handleToggleStatus(adm); activeMenu = null"
                          class="flex w-full items-center gap-2.5 px-3 py-2 rounded-xl text-xs font-bold transition cursor-pointer"
                          :class="adm.is_active ? 'text-rose-600 dark:text-rose-400 hover:bg-rose-50 dark:hover:bg-rose-950/40' : 'text-emerald-600 dark:text-emerald-400 hover:bg-emerald-50 dark:hover:bg-emerald-950/40'"
                        >
                          <Power class="w-3.5 h-3.5" />
                          <span>{{ adm.is_active ? 'Deactivate Admin' : 'Activate Admin' }}</span>
                        </button>

                        <div v-if="adm.user?.email" class="border-t border-slate-100 dark:border-slate-800 my-1"></div>

                        <button
                          v-if="adm.user?.email"
                          type="button"
                          @click="copyToClipboard(adm.user.email); activeMenu = null"
                          class="flex w-full items-center gap-2.5 px-3 py-2 rounded-xl text-xs font-bold text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800 transition cursor-pointer"
                        >
                          <Copy class="w-3.5 h-3.5 text-slate-400" />
                          <span>Copy Email</span>
                        </button>
                      </div>
                    </transition>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <!-- Enhanced Pagination -->
        <div v-if="totalItems > 0" class="p-4 border-t border-slate-200 dark:border-slate-800">
          <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
            <!-- Left: Pagination Info & Per Page Selector -->
            <div class="flex flex-col sm:flex-row sm:items-center gap-3">
              <div class="text-xs text-slate-500 dark:text-slate-400">
                Showing {{ paginationInfo.start }}-{{ paginationInfo.end }} of {{ totalItems }} admins
              </div>
              
              <div class="flex items-center gap-2">
                <label class="text-xs font-semibold text-slate-600 dark:text-slate-300">
                  Show:
                </label>
                <select
                  v-model="itemsPerPage"
                  @change="changeItemsPerPage"
                  class="px-2.5 py-1 rounded-lg border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-xs font-semibold text-slate-700 dark:text-slate-300 focus:ring-2 focus:ring-blue-500/40 focus:border-blue-500 dark:focus:border-blue-400 transition outline-none cursor-pointer"
                >
                  <option v-for="option in itemsPerPageOptions" :key="option" :value="option">
                    {{ option }} per page
                  </option>
                </select>
              </div>
            </div>

            <!-- Center: Page Navigation -->
            <div v-if="totalPages > 1" class="flex items-center justify-center">
              <nav class="flex items-center gap-1" role="navigation" aria-label="Pagination Navigation">
                <!-- First Page -->
                <button
                  type="button"
                  @click="goToFirstPage"
                  :disabled="currentPage <= 1"
                  class="inline-flex items-center justify-center w-8 h-8 rounded-lg text-slate-400 hover:text-slate-600 dark:hover:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800 disabled:opacity-40 disabled:cursor-not-allowed transition"
                  title="First page"
                >
                  <ChevronsLeft class="w-4 h-4" />
                </button>

                <!-- Previous Page -->
                <button
                  type="button"
                  @click="prevPage"
                  :disabled="currentPage <= 1"
                  class="inline-flex items-center justify-center w-8 h-8 rounded-lg text-slate-400 hover:text-slate-600 dark:hover:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800 disabled:opacity-40 disabled:cursor-not-allowed transition"
                  title="Previous page"
                >
                  <ChevronLeft class="w-4 h-4" />
                </button>

                <!-- Page Numbers -->
                <div class="flex items-center gap-1 mx-2">
                  <button
                    v-for="page in visiblePages"
                    :key="page"
                    type="button"
                    @click="goToPage(page)"
                    class="inline-flex items-center justify-center w-8 h-8 rounded-lg text-xs font-semibold transition"
                    :class="[
                      page === currentPage
                        ? 'bg-blue-600 text-white shadow-sm'
                        : 'text-slate-600 dark:text-slate-300 hover:text-slate-800 dark:hover:text-white hover:bg-slate-100 dark:hover:bg-slate-800'
                    ]"
                  >
                    {{ page }}
                  </button>
                </div>

                <!-- Next Page -->
                <button
                  type="button"
                  @click="nextPage"
                  :disabled="currentPage >= totalPages"
                  class="inline-flex items-center justify-center w-8 h-8 rounded-lg text-slate-400 hover:text-slate-600 dark:hover:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800 disabled:opacity-40 disabled:cursor-not-allowed transition"
                  title="Next page"
                >
                  <ChevronRight class="w-4 h-4" />
                </button>

                <!-- Last Page -->
                <button
                  type="button"
                  @click="goToLastPage"
                  :disabled="currentPage >= totalPages"
                  class="inline-flex items-center justify-center w-8 h-8 rounded-lg text-slate-400 hover:text-slate-600 dark:hover:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800 disabled:opacity-40 disabled:cursor-not-allowed transition"
                  title="Last page"
                >
                  <ChevronsRight class="w-4 h-4" />
                </button>
              </nav>
            </div>

            <!-- Right: Jump to Page -->
            <div v-if="totalPages > 1" class="flex items-center gap-2">
              <label class="text-xs font-semibold text-slate-600 dark:text-slate-300 whitespace-nowrap">
                Go to:
              </label>
              <div class="flex items-center gap-1">
                <input
                  v-model="jumpPage"
                  type="number"
                  :min="1"
                  :max="totalPages"
                  placeholder="Page"
                  class="w-16 px-2 py-1 rounded-lg border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-xs font-semibold text-slate-700 dark:text-slate-300 placeholder-slate-400 dark:placeholder-slate-500 focus:ring-2 focus:ring-blue-500/40 focus:border-blue-500 dark:focus:border-blue-400 transition outline-none text-center"
                  @keyup.enter="jumpToPage"
                />
                <button
                  type="button"
                  @click="jumpToPage"
                  :disabled="!jumpPage || parseInt(jumpPage) < 1 || parseInt(jumpPage) > totalPages"
                  class="px-2.5 py-1 rounded-lg bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 dark:hover:bg-slate-700 text-slate-600 dark:text-slate-300 text-xs font-semibold disabled:opacity-40 disabled:cursor-not-allowed transition"
                >
                  Go
                </button>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- CREATE HOTEL ADMIN MODAL (Security-First Activation Flow) -->
      <Teleport to="body">
        <div v-if="showCreateModal" class="fixed inset-0 z-[9999] flex items-center justify-center p-4">
          <div class="fixed inset-0 bg-slate-950/80 backdrop-blur-xs" @click="showCreateModal = false"></div>
          <div class="relative z-10 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-3xl max-w-lg w-full p-6 shadow-2xl space-y-4 text-xs">
            <div class="flex items-center justify-between border-b border-slate-200 dark:border-slate-800 pb-4">
              <div class="flex items-center gap-3">
                <div class="p-2.5 rounded-xl bg-indigo-500/10 text-indigo-600">
                  <UserCheck class="w-5 h-5" />
                </div>
                <div>
                  <h2 class="text-base font-black text-slate-900 dark:text-white">Create Hotel Admin</h2>
                  <p class="text-[11px] text-slate-400">Security-First: Generates activation link without manual password entry</p>
                </div>
              </div>
              <button @click="showCreateModal = false" class="p-2 text-slate-400 hover:text-slate-600 rounded-xl cursor-pointer">
                <X class="w-5 h-5" />
              </button>
            </div>

            <!-- If Hotel Admin successfully created -->
            <div v-if="createSuccessInfo" class="p-5 rounded-2xl bg-emerald-500/10 border border-emerald-500/20 space-y-4">
              <div class="flex items-center gap-2 text-emerald-600 dark:text-emerald-400 font-black text-sm">
                <CheckCircle2 class="w-5 h-5 flex-shrink-0" />
                <span>Hotel Admin Account Ready!</span>
              </div>

              <!-- Information Card -->
              <div class="p-4 bg-white dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-2xl space-y-2.5 shadow-xs text-xs">
                <div class="flex items-center justify-between">
                  <span class="text-slate-400 font-bold text-[11px] uppercase tracking-wide">Administrator:</span>
                  <span class="font-bold text-slate-800 dark:text-slate-200">{{ createSuccessInfo.name }}</span>
                </div>
                <div class="h-px bg-slate-100 dark:bg-slate-800"></div>
                <div class="flex items-center justify-between">
                  <span class="text-slate-400 font-bold text-[11px] uppercase tracking-wide">Email:</span>
                  <span class="font-mono font-bold text-slate-800 dark:text-slate-200 select-all">{{ createSuccessInfo.email }}</span>
                </div>
                <div class="h-px bg-slate-100 dark:bg-slate-800"></div>
                <div class="flex items-center justify-between">
                  <span class="text-slate-400 font-bold text-[11px] uppercase tracking-wide">Hotel:</span>
                  <span class="font-bold text-indigo-600 dark:text-indigo-400">{{ createSuccessInfo.hotel_name }}</span>
                </div>
                <div class="h-px bg-slate-100 dark:bg-slate-800"></div>
                <div class="flex items-center justify-between">
                  <span class="text-slate-400 font-bold text-[11px] uppercase tracking-wide">Password Status:</span>
                  <span class="inline-flex items-center gap-1 font-bold text-emerald-600 dark:text-emerald-400 text-[11px]">
                    <CheckCircle2 class="w-3.5 h-3.5" />
                    Sent Directly to Admin Email
                  </span>
                </div>
              </div>

              <div class="p-3 rounded-xl bg-indigo-50 dark:bg-indigo-950/30 border border-indigo-200 dark:border-indigo-800/40 text-[11px] text-indigo-700 dark:text-indigo-300 leading-relaxed">
                🔒 <strong>Zero-Knowledge Security:</strong> The system generated a secure temporary password and delivered it directly to <strong>{{ createSuccessInfo.email }}</strong>. For privacy and security, platform administrators cannot view this password. The admin logs in with it and updates their password on first login.
              </div>

              <div class="flex items-center justify-end pt-1">
                <button
                  @click="showCreateModal = false; createSuccessInfo = null"
                  class="px-5 py-2 rounded-xl bg-indigo-600 text-white font-extrabold hover:bg-indigo-500 shadow-md transition cursor-pointer"
                >
                  Done
                </button>
              </div>
            </div>

            <!-- Form -->
            <div v-else class="space-y-3">
              <div class="grid grid-cols-2 gap-3">
                <div>
                  <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">First Name *</label>
                  <input
                    v-model="adminForm.first_name"
                    type="text"
                    placeholder="e.g. Abebe"
                    class="w-full px-3 py-2 bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl text-slate-900 dark:text-white"
                  />
                </div>
                <div>
                  <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">Last Name *</label>
                  <input
                    v-model="adminForm.last_name"
                    type="text"
                    placeholder="e.g. Bikila"
                    class="w-full px-3 py-2 bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl text-slate-900 dark:text-white"
                  />
                </div>
              </div>

              <div>
                <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">Email Address *</label>
                <input
                  v-model="adminForm.email"
                  type="email"
                  placeholder="admin@hotel.com"
                  class="w-full px-3 py-2 bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl text-slate-900 dark:text-white"
                />
              </div>

              <div>
                <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">Phone (Optional)</label>
                <input
                  v-model="adminForm.phone"
                  type="text"
                  placeholder="+251 91 123 4567"
                  class="w-full px-3 py-2 bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl text-slate-900 dark:text-white"
                />
              </div>

              <div>
                <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">Assign to Hotel *</label>
                <SearchableSelect
                  v-model="adminForm.hotel_id"
                  :options="hotels"
                  label-key="name"
                  value-key="id"
                  sublabel-key="city"
                  placeholder="Select a hotel..."
                  search-placeholder="Search hotel by name or city..."
                  :show-hotel-icon="true"
                />
              </div>

              <div class="p-3 rounded-xl bg-indigo-50 dark:bg-indigo-950/30 border border-indigo-200 dark:border-indigo-800/40 text-[11px] text-indigo-700 dark:text-indigo-300 leading-relaxed">
                🔒 <strong>System-Generated Password:</strong> The system generates a temporary password and securely emails it to the admin. Super Admin cannot see this password.
              </div>

              <div class="flex items-center justify-end gap-2.5 pt-3">
                <button
                  type="button"
                  @click="showCreateModal = false"
                  class="px-4 py-2 rounded-xl bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 font-bold"
                >
                  Cancel
                </button>
                <button
                  type="button"
                  :disabled="saving"
                  @click="handleCreateAdmin"
                  class="px-5 py-2 rounded-xl bg-indigo-600 hover:bg-indigo-500 text-white font-extrabold shadow-md transition disabled:opacity-50 flex items-center gap-1.5 cursor-pointer"
                >
                  <RefreshCw v-if="saving" class="w-3.5 h-3.5 animate-spin" />
                  <span>{{ saving ? 'Creating...' : 'Create & Send Email' }}</span>
                </button>
              </div>
            </div>
          </div>
        </div>
      </Teleport>

      <!-- RESET / RESEND PASSWORD CONFIRMATION MODAL -->
      <Teleport to="body">
        <div v-if="showResetModal" class="fixed inset-0 z-[9999] flex items-center justify-center p-4">
          <div class="fixed inset-0 bg-slate-950/80 backdrop-blur-xs" @click="showResetModal = false"></div>
          <div class="relative z-10 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-3xl max-w-md w-full p-6 shadow-2xl space-y-4 text-xs">
            <div class="flex items-center gap-3">
              <div class="p-3 rounded-2xl bg-emerald-500/10 text-emerald-600">
                <Send class="w-6 h-6" />
              </div>
              <div>
                <h3 class="text-base font-black text-slate-900 dark:text-white">Temporary Password Emailed</h3>
                <p class="text-slate-400">{{ selectedAdminUser?.email }}</p>
              </div>
            </div>

            <p class="text-slate-600 dark:text-slate-300 leading-relaxed text-xs">
              A new <strong>system-generated temporary password</strong> has been created and securely delivered to <strong>{{ selectedAdminUser?.email }}</strong>.
            </p>

            <div class="p-3.5 rounded-2xl bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 space-y-1.5 text-[11px]">
              <div class="flex items-center justify-between">
                <span class="text-slate-400 font-bold uppercase">Recipient:</span>
                <span class="font-mono font-bold text-slate-800 dark:text-slate-200">{{ selectedAdminUser?.email }}</span>
              </div>
              <div class="flex items-center justify-between">
                <span class="text-slate-400 font-bold uppercase">Delivery:</span>
                <span class="font-bold text-emerald-600 dark:text-emerald-400 flex items-center gap-1">
                  <CheckCircle2 class="w-3.5 h-3.5" /> Sent via Email
                </span>
              </div>
              <div class="flex items-center justify-between">
                <span class="text-slate-400 font-bold uppercase">Password Visibility:</span>
                <span class="font-bold text-slate-500 italic">Concealed (Zero-Knowledge)</span>
              </div>
            </div>

            <div class="p-3 rounded-xl bg-amber-50 dark:bg-amber-950/20 border border-amber-200 dark:border-amber-800/40 text-[11px] text-amber-800 dark:text-amber-300 leading-relaxed">
              🔒 <strong>Security Policy:</strong> Platform administrators cannot view passwords. When the Hotel Admin signs in with this temporary password, they will be required to choose a new permanent password.
            </div>

            <div class="flex items-center justify-end pt-2">
              <button
                @click="showResetModal = false"
                class="px-5 py-2 rounded-xl bg-indigo-600 hover:bg-indigo-500 text-white font-extrabold cursor-pointer shadow-md transition"
              >
                Close
              </button>
            </div>
          </div>
        </div>
      </Teleport>
    </div>
  </DashboardLayout>
</template>

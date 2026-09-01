<script setup lang="ts">
import { ref, computed, onMounted, watch } from 'vue'
import { useRouter } from 'vue-router'
import DashboardLayout from '@/Layouts/DashboardLayout.vue'
import { platformService, type Hotel, type CreateHotelPayload } from '@/services/platformService'
import { useHotelStore } from '@/stores/hotelStore'
import {
  Building2,
  Plus,
  Search,
  Filter,
  RefreshCw,
  Eye,
  Edit,
  Power,
  ShieldAlert,
  Archive,
  Trash2,
  MoreVertical,
  CheckCircle2,
  AlertCircle,
  X,
  Mail,
  Phone,
  MapPin,
  Globe,
  Clock,
  Coins,
  UserCheck,
  BedDouble,
  CalendarCheck,
  Utensils,
  DollarSign,
  Users,
  LayoutDashboard
} from 'lucide-vue-next'

const router = useRouter()
const hotelStore = useHotelStore()

// State
const hotels = ref<Hotel[]>([])
const loading = ref(true)
const saving = ref(false)
const searchQuery = ref('')
const selectedStatus = ref('all')
const selectedCity = ref('all')
const successMessage = ref('')
const errorMessage = ref('')

// Pagination
const currentPage = ref(1)
const lastPage = ref(1)
const totalHotels = ref(0)

// Active action dropdown ID
const activeDropdownId = ref<string | null>(null)

// Modals
const showCreateModal = ref(false)
const showEditModal = ref(false)
const showDetailsModal = ref(false)
const showSuspendModal = ref(false)
const showArchiveModal = ref(false)
const showDeleteModal = ref(false)

const selectedHotel = ref<Hotel | null>(null)
const deleteConfirmName = ref('')

// Form State
const createInitialAdmin = ref(true)
const hotelForm = ref<CreateHotelPayload>({
  name: '',
  slug: '',
  email: '',
  phone: '',
  address: '',
  city: '',
  country: 'Ethiopia',
  timezone: 'Africa/Addis_Ababa',
  currency: 'ETB',
  status: 'active',
  admin_first_name: '',
  admin_last_name: '',
  admin_email: '',
  admin_password: '',
})

const editForm = ref({
  id: '',
  name: '',
  slug: '',
  email: '',
  phone: '',
  address: '',
  city: '',
  country: '',
  timezone: '',
  currency: '',
  status: 'active' as const,
})

// Auto-generate slug when typing hotel name
watch(
  () => hotelForm.value.name,
  (newName) => {
    if (newName && !hotelForm.value.slug) {
      hotelForm.value.slug = newName
        .toLowerCase()
        .trim()
        .replace(/[^a-z0-9]+/g, '-')
        .replace(/^-+|-+$/g, '')
    }
  }
)

const loadHotels = async () => {
  loading.value = true
  errorMessage.value = ''
  try {
    const res = await platformService.getHotels({
      search: searchQuery.value || undefined,
      status: selectedStatus.value !== 'all' ? selectedStatus.value : undefined,
      city: selectedCity.value !== 'all' ? selectedCity.value : undefined,
      page: currentPage.value,
    })
    hotels.value = res.data || []
    currentPage.value = res.current_page || 1
    lastPage.value = res.last_page || 1
    totalHotels.value = res.total || 0
  } catch (err: any) {
    console.error('Failed to load hotels:', err)
    errorMessage.value = err?.response?.data?.message || 'Failed to load hotels catalog.'
  } finally {
    loading.value = false
  }
}

onMounted(loadHotels)

watch([searchQuery, selectedStatus, selectedCity], () => {
  currentPage.value = 1
  loadHotels()
})

// Available Cities
const availableCities = computed(() => {
  const cities = new Set<string>()
  hotels.value.forEach((h) => {
    if (h.city) cities.add(h.city)
  })
  return Array.from(cities).sort()
})

const toggleDropdown = (id: string) => {
  activeDropdownId.value = activeDropdownId.value === id ? null : id
}

// Modal Triggers
const openCreateModal = () => {
  hotelForm.value = {
    name: '',
    slug: '',
    email: '',
    phone: '',
    address: '',
    city: '',
    country: 'Ethiopia',
    timezone: 'Africa/Addis_Ababa',
    currency: 'ETB',
    status: 'active',
    admin_first_name: '',
    admin_last_name: '',
    admin_email: '',
    admin_password: '',
  }
  createInitialAdmin.value = true
  showCreateModal.value = true
}

const openDetailsModal = async (hotel: Hotel) => {
  activeDropdownId.value = null
  selectedHotel.value = hotel
  showDetailsModal.value = true
  try {
    const detailed = await platformService.getHotel(hotel.id)
    selectedHotel.value = detailed
  } catch (e) {
    console.warn('Could not fetch full hotel stats:', e)
  }
}

const openEditModal = (hotel: Hotel) => {
  activeDropdownId.value = null
  selectedHotel.value = hotel
  editForm.value = {
    id: hotel.id,
    name: hotel.name,
    slug: hotel.slug,
    email: hotel.email || '',
    phone: hotel.phone || '',
    address: hotel.address || '',
    city: hotel.city || '',
    country: hotel.country || '',
    timezone: hotel.timezone || 'Africa/Addis_Ababa',
    currency: hotel.currency || 'ETB',
    status: hotel.status,
  }
  showEditModal.value = true
}

const triggerSuspendModal = (hotel: Hotel) => {
  activeDropdownId.value = null
  selectedHotel.value = hotel
  showSuspendModal.value = true
}

const triggerArchiveModal = (hotel: Hotel) => {
  activeDropdownId.value = null
  selectedHotel.value = hotel
  showArchiveModal.value = true
}

const triggerDeleteModal = (hotel: Hotel) => {
  activeDropdownId.value = null
  selectedHotel.value = hotel
  deleteConfirmName.value = ''
  showDeleteModal.value = true
}

// Actions
const handleCreateHotel = async () => {
  if (!hotelForm.value.name.trim() || !hotelForm.value.slug.trim()) {
    errorMessage.value = 'Hotel Name and Slug are required.'
    return
  }

  saving.value = true
  errorMessage.value = ''
  try {
    const payload: CreateHotelPayload = { ...hotelForm.value }
    if (!createInitialAdmin.value) {
      delete payload.admin_first_name
      delete payload.admin_last_name
      delete payload.admin_email
      delete payload.admin_password
    }

    const created = await platformService.createHotel(payload)
    successMessage.value = `Hotel "${created.name}" onboarded successfully!`
    showCreateModal.value = false
    await loadHotels()
    setTimeout(() => { successMessage.value = '' }, 4000)
  } catch (err: any) {
    errorMessage.value = err?.response?.data?.message || 'Failed to create hotel.'
  } finally {
    saving.value = false
  }
}

const handleUpdateHotel = async () => {
  if (!selectedHotel.value) return
  saving.value = true
  errorMessage.value = ''
  try {
    await platformService.updateHotel(selectedHotel.value.id, editForm.value)
    successMessage.value = `Hotel "${editForm.value.name}" updated successfully!`
    showEditModal.value = false
    await loadHotels()
    setTimeout(() => { successMessage.value = '' }, 4000)
  } catch (err: any) {
    errorMessage.value = err?.response?.data?.message || 'Failed to update hotel.'
  } finally {
    saving.value = false
  }
}

const handleEnterHotelViewMode = async (hotel: Hotel) => {
  activeDropdownId.value = null
  try {
    await platformService.enterHotelViewMode(hotel.id)
    hotelStore.enterPlatformViewMode({
      id: hotel.id,
      name: hotel.name,
      slug: hotel.slug,
      logo: hotel.logo,
      currency: hotel.currency,
      role: 'admin',
    })
    router.push('/admin')
  } catch (err: any) {
    errorMessage.value = err?.response?.data?.message || 'Failed to enter hotel view mode.'
  }
}

const handleActivateHotel = async (hotel: Hotel) => {
  activeDropdownId.value = null
  try {
    await platformService.updateHotelStatus(hotel.id, 'active')
    successMessage.value = `Hotel "${hotel.name}" has been activated. Operations restored.`
    await loadHotels()
    setTimeout(() => { successMessage.value = '' }, 4000)
  } catch (err: any) {
    errorMessage.value = err?.response?.data?.message || 'Failed to activate hotel.'
  }
}

const handleDeactivateHotel = async (hotel: Hotel) => {
  activeDropdownId.value = null
  try {
    await platformService.updateHotelStatus(hotel.id, 'inactive')
    successMessage.value = `Hotel "${hotel.name}" has been set to inactive.`
    await loadHotels()
    setTimeout(() => { successMessage.value = '' }, 4000)
  } catch (err: any) {
    errorMessage.value = err?.response?.data?.message || 'Failed to deactivate hotel.'
  }
}

const handleConfirmSuspend = async () => {
  if (!selectedHotel.value) return
  saving.value = true
  try {
    await platformService.updateHotelStatus(selectedHotel.value.id, 'suspended')
    successMessage.value = `Hotel "${selectedHotel.value.name}" is now SUSPENDED. Data is safely preserved.`
    showSuspendModal.value = false
    await loadHotels()
    setTimeout(() => { successMessage.value = '' }, 4000)
  } catch (err: any) {
    errorMessage.value = err?.response?.data?.message || 'Failed to suspend hotel.'
  } finally {
    saving.value = false
  }
}

const handleConfirmArchive = async () => {
  if (!selectedHotel.value) return
  saving.value = true
  try {
    await platformService.archiveHotel(selectedHotel.value.id)
    successMessage.value = `Hotel "${selectedHotel.value.name}" has been ARCHIVED. All records are preserved.`
    showArchiveModal.value = false
    await loadHotels()
    setTimeout(() => { successMessage.value = '' }, 4000)
  } catch (err: any) {
    errorMessage.value = err?.response?.data?.message || 'Failed to archive hotel.'
  } finally {
    saving.value = false
  }
}

const handleConfirmPermanentDelete = async () => {
  if (!selectedHotel.value) return
  if (deleteConfirmName.value.trim() !== selectedHotel.value.name.trim()) {
    errorMessage.value = 'Typed hotel name does not match.'
    return
  }

  saving.value = true
  try {
    await platformService.deleteHotel(selectedHotel.value.id, deleteConfirmName.value.trim())
    successMessage.value = `Hotel "${selectedHotel.value.name}" deleted permanently.`
    showDeleteModal.value = false
    await loadHotels()
    setTimeout(() => { successMessage.value = '' }, 4000)
  } catch (err: any) {
    errorMessage.value = err?.response?.data?.message || 'Failed to permanently delete hotel.'
  } finally {
    saving.value = false
  }
}

const formatCurrency = (val?: number, currency = 'ETB') => {
  if (!val) return `${currency} 0`
  return `${currency} ${Number(val).toLocaleString()}`
}
</script>

<template>
  <DashboardLayout>
    <div class="space-y-6 bg-slate-50 dark:bg-slate-950 min-h-screen p-4 sm:p-6 max-w-full font-sans">
      <!-- Header Banner Section -->
      <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 p-6 rounded-3xl shadow-xs flex flex-col md:flex-row md:items-center md:justify-between gap-4">
        <div>
          <div class="flex items-center gap-3">
            <div class="p-3 rounded-2xl bg-indigo-500/10 text-indigo-600 dark:text-indigo-400 border border-indigo-500/20">
              <Building2 class="w-6 h-6" />
            </div>
            <div>
              <div class="flex items-center gap-2">
                <h1 class="text-2xl font-black text-slate-900 dark:text-white tracking-tight">
                  Hotels Management
                </h1>
                <span class="px-2.5 py-0.5 rounded-full text-xs font-black bg-indigo-500/10 text-indigo-600 dark:text-indigo-400 border border-indigo-500/20">
                  {{ totalHotels }} Hotels
                </span>
              </div>
              <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">
                Centralized platform governance to onboard, configure, suspend, and supervise multi-tenant hotel properties.
              </p>
            </div>
          </div>
        </div>

        <div class="flex items-center gap-2.5">
          <button
            @click="loadHotels"
            :disabled="loading"
            class="p-2.5 rounded-xl bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-300 transition cursor-pointer border border-slate-200 dark:border-slate-700 font-bold text-xs flex items-center gap-1.5"
          >
            <RefreshCw :class="['w-4 h-4', loading && 'animate-spin']" />
            <span>Refresh</span>
          </button>

          <button
            @click="openCreateModal"
            class="px-4 py-2.5 rounded-xl bg-indigo-600 hover:bg-indigo-500 text-white font-extrabold text-xs shadow-md shadow-indigo-600/20 transition flex items-center gap-2 cursor-pointer"
          >
            <Plus class="w-4 h-4" />
            <span>Add Hotel</span>
          </button>
        </div>
      </div>

      <!-- Feedback Alerts -->
      <div v-if="successMessage" class="p-4 rounded-2xl bg-emerald-500/10 border border-emerald-500/20 text-emerald-600 dark:text-emerald-400 text-xs font-bold flex items-center gap-2">
        <CheckCircle2 class="w-4 h-4 flex-shrink-0" />
        <span>{{ successMessage }}</span>
      </div>

      <div v-if="errorMessage" class="p-4 rounded-2xl bg-rose-500/10 border border-rose-500/20 text-rose-600 dark:text-rose-400 text-xs font-bold flex items-center gap-2">
        <AlertCircle class="w-4 h-4 flex-shrink-0" />
        <span>{{ errorMessage }}</span>
      </div>

      <!-- Search & Filters Toolbar -->
      <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 p-4 rounded-2xl shadow-xs flex flex-col md:flex-row items-center justify-between gap-3">
        <!-- Search -->
        <div class="relative w-full md:w-80">
          <Search class="w-4 h-4 text-slate-400 absolute left-3.5 top-3" />
          <input
            v-model="searchQuery"
            type="text"
            placeholder="Search hotel, city, or email..."
            class="w-full pl-9 pr-3 py-2 bg-slate-100 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl text-xs text-slate-900 dark:text-white placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-indigo-500 font-medium"
          />
        </div>

        <!-- Filter Selects -->
        <div class="flex flex-wrap items-center gap-2.5 w-full md:w-auto">
          <!-- Status filter -->
          <div class="flex items-center gap-1.5">
            <span class="text-xs font-bold text-slate-400">Status:</span>
            <select
              v-model="selectedStatus"
              class="px-3 py-2 bg-slate-100 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl text-xs text-slate-900 dark:text-white font-medium focus:outline-none"
            >
              <option value="all">All Statuses</option>
              <option value="active">Active</option>
              <option value="inactive">Inactive</option>
              <option value="suspended">Suspended</option>
              <option value="archived">Archived</option>
            </select>
          </div>

          <!-- City filter -->
          <div class="flex items-center gap-1.5">
            <span class="text-xs font-bold text-slate-400">City:</span>
            <select
              v-model="selectedCity"
              class="px-3 py-2 bg-slate-100 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl text-xs text-slate-900 dark:text-white font-medium focus:outline-none"
            >
              <option value="all">All Cities</option>
              <option v-for="city in availableCities" :key="city" :value="city">
                {{ city }}
              </option>
            </select>
          </div>
        </div>
      </div>

      <!-- HOTELS TABLE -->
      <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-3xl shadow-xs overflow-hidden">
        <div class="overflow-x-auto">
          <table class="w-full text-left text-xs border-collapse">
            <thead class="bg-slate-100/70 dark:bg-slate-800/80 text-slate-600 dark:text-slate-300 font-bold border-b border-slate-200 dark:border-slate-700/80">
              <tr>
                <th class="py-4 px-5">Hotel</th>
                <th class="py-4 px-4">Location</th>
                <th class="py-4 px-4">Hotel Admin</th>
                <th class="py-4 px-4">Rooms</th>
                <th class="py-4 px-4">Status</th>
                <th class="py-4 px-5 text-right">Actions</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-100 dark:divide-slate-800 text-slate-700 dark:text-slate-300 font-medium">
              <!-- Loading Skeleton -->
              <tr v-if="loading">
                <td colspan="6" class="p-12 text-center text-xs text-slate-400">
                  <RefreshCw class="w-6 h-6 text-indigo-500 animate-spin mx-auto mb-2" />
                  <span>Loading platform hotels...</span>
                </td>
              </tr>

              <!-- Empty -->
              <tr v-else-if="hotels.length === 0">
                <td colspan="6" class="p-12 text-center text-xs text-slate-400">
                  <Building2 class="w-8 h-8 text-slate-300 dark:text-slate-600 mx-auto mb-2" />
                  <p class="font-bold">No hotels found.</p>
                  <p class="text-[11px] text-slate-400 mt-0.5">Try refining your search or add a new hotel.</p>
                </td>
              </tr>

              <!-- Hotel Rows -->
              <tr
                v-else
                v-for="hotel in hotels"
                :key="hotel.id"
                class="hover:bg-slate-50/70 dark:hover:bg-slate-800/50 transition"
              >
                <!-- Hotel Name & Slug -->
                <td class="py-4 px-5">
                  <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-2xl bg-indigo-500/10 border border-indigo-500/20 text-indigo-600 dark:text-indigo-400 flex items-center justify-center font-black text-sm flex-shrink-0">
                      {{ hotel.name.slice(0, 2).toUpperCase() }}
                    </div>
                    <div>
                      <span class="font-black text-sm text-slate-900 dark:text-white block">
                        {{ hotel.name }}
                      </span>
                      <span class="text-[11px] font-mono text-slate-400 dark:text-slate-500">
                        {{ hotel.slug }}
                      </span>
                    </div>
                  </div>
                </td>

                <!-- Location -->
                <td class="py-4 px-4">
                  <div class="flex items-center gap-1.5 text-xs text-slate-800 dark:text-slate-200 font-semibold">
                    <MapPin class="w-3.5 h-3.5 text-slate-400 flex-shrink-0" />
                    <span>{{ hotel.city || 'N/A' }}</span>
                  </div>
                  <span class="text-[11px] text-slate-400 block ml-5">
                    {{ hotel.country || 'Ethiopia' }}
                  </span>
                </td>

                <!-- Hotel Admin -->
                <td class="py-4 px-4">
                  <div class="flex items-center gap-1.5 text-xs text-slate-800 dark:text-slate-200 font-semibold">
                    <UserCheck class="w-3.5 h-3.5 text-indigo-500 flex-shrink-0" />
                    <span>{{ hotel.admin_name || 'No Admin Assigned' }}</span>
                  </div>
                  <span v-if="hotel.admin_email" class="text-[10px] text-slate-400 block ml-5 font-mono">
                    {{ hotel.admin_email }}
                  </span>
                </td>

                <!-- Rooms -->
                <td class="py-4 px-4">
                  <span class="px-2.5 py-1 rounded-lg bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 font-bold text-xs">
                    {{ hotel.rooms_count ?? 0 }} rooms
                  </span>
                </td>

                <!-- Status Badge -->
                <td class="py-4 px-4">
                  <span
                    v-if="hotel.status === 'active'"
                    class="px-2.5 py-1 rounded-full text-xs font-black bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 border border-emerald-500/20"
                  >
                    Active
                  </span>
                  <span
                    v-else-if="hotel.status === 'suspended'"
                    class="px-2.5 py-1 rounded-full text-xs font-black bg-amber-500/10 text-amber-600 dark:text-amber-400 border border-amber-500/20"
                  >
                    Suspended
                  </span>
                  <span
                    v-else-if="hotel.status === 'archived'"
                    class="px-2.5 py-1 rounded-full text-xs font-black bg-purple-500/10 text-purple-600 dark:text-purple-400 border border-purple-500/20"
                  >
                    Archived
                  </span>
                  <span
                    v-else
                    class="px-2.5 py-1 rounded-full text-xs font-black bg-slate-500/10 text-slate-500 dark:text-slate-400 border border-slate-500/20"
                  >
                    Inactive
                  </span>
                </td>

                <!-- Actions Dropdown -->
                <td class="py-4 px-5 text-right relative">
                  <button
                    @click.stop="toggleDropdown(hotel.id)"
                    class="p-2 rounded-xl hover:bg-slate-100 dark:hover:bg-slate-800 text-slate-500 dark:text-slate-400 transition cursor-pointer"
                  >
                    <MoreVertical class="w-4 h-4" />
                  </button>

                  <!-- Popover Menu -->
                  <div
                    v-if="activeDropdownId === hotel.id"
                    class="absolute right-5 mt-1 w-48 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl shadow-xl z-20 py-1.5 text-xs text-left animate-in fade-in zoom-in-95 duration-100"
                  >
                    <button
                      @click="openDetailsModal(hotel)"
                      class="w-full px-3.5 py-2 hover:bg-slate-100 dark:hover:bg-slate-800 text-slate-700 dark:text-slate-200 flex items-center gap-2.5 font-bold transition cursor-pointer"
                    >
                      <Eye class="w-3.5 h-3.5 text-indigo-500" />
                      <span>View Details</span>
                    </button>

                    <button
                      @click="handleEnterHotelViewMode(hotel)"
                      class="w-full px-3.5 py-2 hover:bg-indigo-50 dark:hover:bg-indigo-950/40 text-indigo-600 dark:text-indigo-400 flex items-center gap-2.5 font-bold transition cursor-pointer"
                    >
                      <LayoutDashboard class="w-3.5 h-3.5 text-indigo-500" />
                      <span>View Hotel Dashboard</span>
                    </button>

                    <button
                      @click="openEditModal(hotel)"
                      class="w-full px-3.5 py-2 hover:bg-slate-100 dark:hover:bg-slate-800 text-slate-700 dark:text-slate-200 flex items-center gap-2.5 font-bold transition cursor-pointer"
                    >
                      <Edit class="w-3.5 h-3.5 text-blue-500" />
                      <span>Edit Hotel</span>
                    </button>

                    <div class="h-px bg-slate-100 dark:bg-slate-800 my-1"></div>

                    <!-- Activate if not active -->
                    <button
                      v-if="hotel.status !== 'active'"
                      @click="handleActivateHotel(hotel)"
                      class="w-full px-3.5 py-2 hover:bg-emerald-50 dark:hover:bg-emerald-950/40 text-emerald-600 dark:text-emerald-400 flex items-center gap-2.5 font-bold transition cursor-pointer"
                    >
                      <Power class="w-3.5 h-3.5 text-emerald-500" />
                      <span>Activate Hotel</span>
                    </button>

                    <!-- Deactivate if active -->
                    <button
                      v-if="hotel.status === 'active'"
                      @click="handleDeactivateHotel(hotel)"
                      class="w-full px-3.5 py-2 hover:bg-slate-100 dark:hover:bg-slate-800 text-slate-600 dark:text-slate-300 flex items-center gap-2.5 font-bold transition cursor-pointer"
                    >
                      <Power class="w-3.5 h-3.5 text-slate-400" />
                      <span>Deactivate Hotel</span>
                    </button>

                    <!-- Suspend -->
                    <button
                      v-if="hotel.status !== 'suspended'"
                      @click="triggerSuspendModal(hotel)"
                      class="w-full px-3.5 py-2 hover:bg-amber-50 dark:hover:bg-amber-950/40 text-amber-600 dark:text-amber-400 flex items-center gap-2.5 font-bold transition cursor-pointer"
                    >
                      <ShieldAlert class="w-3.5 h-3.5 text-amber-500" />
                      <span>Suspend Hotel</span>
                    </button>

                    <!-- Archive -->
                    <button
                      v-if="hotel.status !== 'archived'"
                      @click="triggerArchiveModal(hotel)"
                      class="w-full px-3.5 py-2 hover:bg-purple-50 dark:hover:bg-purple-950/40 text-purple-600 dark:text-purple-400 flex items-center gap-2.5 font-bold transition cursor-pointer"
                    >
                      <Archive class="w-3.5 h-3.5 text-purple-500" />
                      <span>Archive Hotel</span>
                    </button>

                    <div class="h-px bg-slate-100 dark:bg-slate-800 my-1"></div>

                    <!-- Permanent Delete -->
                    <button
                      @click="triggerDeleteModal(hotel)"
                      class="w-full px-3.5 py-2 hover:bg-rose-50 dark:hover:bg-rose-950/40 text-rose-600 dark:text-rose-400 flex items-center gap-2.5 font-bold transition cursor-pointer"
                    >
                      <Trash2 class="w-3.5 h-3.5 text-rose-500" />
                      <span>Permanent Delete</span>
                    </button>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <!-- Pagination Bar -->
        <div v-if="lastPage > 1" class="p-4 border-t border-slate-200 dark:border-slate-800 flex items-center justify-between">
          <span class="text-xs text-slate-500">
            Page {{ currentPage }} of {{ lastPage }} ({{ totalHotels }} total)
          </span>
          <div class="flex items-center gap-2">
            <button
              :disabled="currentPage <= 1"
              @click="currentPage--; loadHotels()"
              class="px-3 py-1.5 rounded-lg bg-slate-100 dark:bg-slate-800 text-xs font-bold disabled:opacity-40"
            >
              Previous
            </button>
            <button
              :disabled="currentPage >= lastPage"
              @click="currentPage++; loadHotels()"
              class="px-3 py-1.5 rounded-lg bg-slate-100 dark:bg-slate-800 text-xs font-bold disabled:opacity-40"
            >
              Next
            </button>
          </div>
        </div>
      </div>

      <!-- ========================================================================= -->
      <!-- 1. ADD NEW HOTEL MODAL -->
      <!-- ========================================================================= -->
      <Teleport to="body">
        <div v-if="showCreateModal" class="fixed inset-0 z-[9999] flex items-center justify-center p-4">
          <div class="fixed inset-0 bg-slate-950/80 backdrop-blur-xs" @click="showCreateModal = false"></div>
          <div class="relative z-10 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-3xl max-w-2xl w-full max-h-[90vh] flex flex-col shadow-2xl overflow-hidden animate-in fade-in zoom-in duration-150">
            <!-- Header -->
            <div class="flex items-center justify-between px-6 py-5 border-b border-slate-200 dark:border-slate-800">
              <div class="flex items-center gap-3">
                <div class="p-2.5 rounded-xl bg-indigo-500/10 text-indigo-600 dark:text-indigo-400">
                  <Building2 class="w-5 h-5" />
                </div>
                <div>
                  <h2 class="text-lg font-black text-slate-900 dark:text-white">Create New Hotel</h2>
                  <p class="text-xs text-slate-500">Add a new tenant property to the platform</p>
                </div>
              </div>
              <button @click="showCreateModal = false" class="p-2 text-slate-400 hover:text-slate-600 rounded-xl cursor-pointer">
                <X class="w-5 h-5" />
              </button>
            </div>

            <!-- Body -->
            <div class="flex-1 overflow-y-auto p-6 space-y-4 text-xs">
              <div class="grid grid-cols-1 sm:grid-cols-2 gap-3.5">
                <div>
                  <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">Hotel Name *</label>
                  <input
                    v-model="hotelForm.name"
                    type="text"
                    placeholder="e.g. Addis Grand Luxury Hotel"
                    class="w-full px-3 py-2 bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl text-slate-900 dark:text-white font-medium"
                  />
                </div>
                <div>
                  <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">Hotel Slug *</label>
                  <input
                    v-model="hotelForm.slug"
                    type="text"
                    placeholder="e.g. addis-grand"
                    class="w-full px-3 py-2 bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl text-slate-900 dark:text-white font-mono"
                  />
                </div>
                <div>
                  <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">Email *</label>
                  <input
                    v-model="hotelForm.email"
                    type="email"
                    placeholder="contact@hotel.com"
                    class="w-full px-3 py-2 bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl text-slate-900 dark:text-white font-medium"
                  />
                </div>
                <div>
                  <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">Phone *</label>
                  <input
                    v-model="hotelForm.phone"
                    type="text"
                    placeholder="+251 91 123 4567"
                    class="w-full px-3 py-2 bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl text-slate-900 dark:text-white font-medium"
                  />
                </div>
                <div class="sm:col-span-2">
                  <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">Address *</label>
                  <input
                    v-model="hotelForm.address"
                    type="text"
                    placeholder="Bole Road, Sub-city, Street 12"
                    class="w-full px-3 py-2 bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl text-slate-900 dark:text-white font-medium"
                  />
                </div>
                <div>
                  <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">City *</label>
                  <input
                    v-model="hotelForm.city"
                    type="text"
                    placeholder="Addis Ababa"
                    class="w-full px-3 py-2 bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl text-slate-900 dark:text-white font-medium"
                  />
                </div>
                <div>
                  <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">Country *</label>
                  <input
                    v-model="hotelForm.country"
                    type="text"
                    placeholder="Ethiopia"
                    class="w-full px-3 py-2 bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl text-slate-900 dark:text-white font-medium"
                  />
                </div>
                <div>
                  <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">Timezone *</label>
                  <input
                    v-model="hotelForm.timezone"
                    type="text"
                    class="w-full px-3 py-2 bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl text-slate-900 dark:text-white font-medium"
                  />
                </div>
                <div>
                  <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">Currency *</label>
                  <input
                    v-model="hotelForm.currency"
                    type="text"
                    class="w-full px-3 py-2 bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl text-slate-900 dark:text-white font-medium"
                  />
                </div>
                <div class="sm:col-span-2">
                  <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">Initial Status</label>
                  <select
                    v-model="hotelForm.status"
                    class="w-full px-3 py-2 bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl text-slate-900 dark:text-white font-medium"
                  >
                    <option value="active">Active (Fully operational immediately)</option>
                    <option value="inactive">Inactive (Disabled until configured)</option>
                    <option value="suspended">Suspended</option>
                  </select>
                </div>
              </div>

              <!-- Initial Hotel Admin Section -->
              <div class="pt-4 border-t border-slate-200 dark:border-slate-800">
                <label class="flex items-center gap-2 cursor-pointer mb-3">
                  <input type="checkbox" v-model="createInitialAdmin" class="rounded text-indigo-600 accent-indigo-600" />
                  <span class="font-extrabold text-slate-900 dark:text-white">Create Initial Hotel Admin Account</span>
                </label>

                <div v-if="createInitialAdmin" class="grid grid-cols-1 sm:grid-cols-2 gap-3.5 p-4 rounded-2xl bg-indigo-50/50 dark:bg-indigo-950/20 border border-indigo-500/20">
                  <div>
                    <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">Admin First Name *</label>
                    <input
                      v-model="hotelForm.admin_first_name"
                      type="text"
                      placeholder="e.g. Abebe"
                      class="w-full px-3 py-2 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-700 rounded-xl text-slate-900 dark:text-white font-medium"
                    />
                  </div>
                  <div>
                    <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">Admin Last Name *</label>
                    <input
                      v-model="hotelForm.admin_last_name"
                      type="text"
                      placeholder="e.g. Kebede"
                      class="w-full px-3 py-2 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-700 rounded-xl text-slate-900 dark:text-white font-medium"
                    />
                  </div>
                  <div>
                    <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">Admin Email *</label>
                    <input
                      v-model="hotelForm.admin_email"
                      type="email"
                      placeholder="admin@hotel.com"
                      class="w-full px-3 py-2 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-700 rounded-xl text-slate-900 dark:text-white font-medium"
                    />
                  </div>
                  <div>
                    <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">Password (Default: HotelAdmin123@)</label>
                    <input
                      v-model="hotelForm.admin_password"
                      type="password"
                      placeholder="Leave blank for default"
                      class="w-full px-3 py-2 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-700 rounded-xl text-slate-900 dark:text-white font-medium"
                    />
                  </div>
                </div>
              </div>
            </div>

            <!-- Footer -->
            <div class="px-6 py-4 border-t border-slate-200 dark:border-slate-800 flex items-center justify-end gap-2.5">
              <button
                type="button"
                @click="showCreateModal = false"
                class="px-4 py-2 rounded-xl bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 font-bold text-xs"
              >
                Cancel
              </button>
              <button
                type="button"
                :disabled="saving"
                @click="handleCreateHotel"
                class="px-5 py-2 rounded-xl bg-indigo-600 hover:bg-indigo-500 text-white font-extrabold text-xs shadow-md transition disabled:opacity-50 flex items-center gap-1.5"
              >
                <RefreshCw v-if="saving" class="w-3.5 h-3.5 animate-spin" />
                <span>{{ saving ? 'Creating...' : 'Create Hotel' }}</span>
              </button>
            </div>
          </div>
        </div>
      </Teleport>

      <!-- ========================================================================= -->
      <!-- 2. HOTEL DETAILS MODAL (With Statistics) -->
      <!-- ========================================================================= -->
      <Teleport to="body">
        <div v-if="showDetailsModal && selectedHotel" class="fixed inset-0 z-[9999] flex items-center justify-center p-4">
          <div class="fixed inset-0 bg-slate-950/80 backdrop-blur-xs" @click="showDetailsModal = false"></div>
          <div class="relative z-10 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-3xl max-w-3xl w-full max-h-[90vh] flex flex-col shadow-2xl overflow-hidden animate-in fade-in zoom-in duration-150">
            <!-- Header -->
            <div class="flex items-center justify-between px-6 py-5 border-b border-slate-200 dark:border-slate-800 bg-slate-50/50 dark:bg-slate-800/40">
              <div class="flex items-center gap-3">
                <div class="w-12 h-12 rounded-2xl bg-indigo-600 text-white flex items-center justify-center font-black text-lg">
                  {{ selectedHotel.name.slice(0, 2).toUpperCase() }}
                </div>
                <div>
                  <h2 class="text-xl font-black text-slate-900 dark:text-white">{{ selectedHotel.name }}</h2>
                  <p class="text-xs text-slate-400 font-mono">{{ selectedHotel.slug }} · {{ selectedHotel.city }}, {{ selectedHotel.country }}</p>
                </div>
              </div>
              <button @click="showDetailsModal = false" class="p-2 text-slate-400 hover:text-slate-600 rounded-xl cursor-pointer">
                <X class="w-5 h-5" />
              </button>
            </div>

            <!-- Body -->
            <div class="flex-1 overflow-y-auto p-6 space-y-6 text-xs">
              <!-- Statistics Cards Grid -->
              <div>
                <h3 class="font-extrabold text-slate-900 dark:text-white text-xs uppercase tracking-wider mb-3">
                  Hotel Statistics & Overview
                </h3>
                <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-3">
                  <div class="p-3 rounded-2xl bg-slate-100 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-center">
                    <BedDouble class="w-5 h-5 text-indigo-500 mx-auto mb-1" />
                    <span class="block text-base font-black text-slate-900 dark:text-white">{{ selectedHotel.rooms_count ?? 0 }}</span>
                    <span class="text-[10px] text-slate-400 font-bold uppercase">Rooms</span>
                  </div>
                  <div class="p-3 rounded-2xl bg-slate-100 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-center">
                    <Users class="w-5 h-5 text-blue-500 mx-auto mb-1" />
                    <span class="block text-base font-black text-slate-900 dark:text-white">{{ selectedHotel.staff_count ?? 0 }}</span>
                    <span class="text-[10px] text-slate-400 font-bold uppercase">Staff</span>
                  </div>
                  <div class="p-3 rounded-2xl bg-slate-100 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-center">
                    <UserCheck class="w-5 h-5 text-teal-500 mx-auto mb-1" />
                    <span class="block text-base font-black text-slate-900 dark:text-white">{{ selectedHotel.guests_count ?? 0 }}</span>
                    <span class="text-[10px] text-slate-400 font-bold uppercase">Guests</span>
                  </div>
                  <div class="p-3 rounded-2xl bg-slate-100 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-center">
                    <CalendarCheck class="w-5 h-5 text-amber-500 mx-auto mb-1" />
                    <span class="block text-base font-black text-slate-900 dark:text-white">{{ selectedHotel.reservations_count ?? 0 }}</span>
                    <span class="text-[10px] text-slate-400 font-bold uppercase">Bookings</span>
                  </div>
                  <div class="p-3 rounded-2xl bg-slate-100 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-center">
                    <Utensils class="w-5 h-5 text-orange-500 mx-auto mb-1" />
                    <span class="block text-base font-black text-slate-900 dark:text-white">{{ selectedHotel.orders_count ?? 0 }}</span>
                    <span class="text-[10px] text-slate-400 font-bold uppercase">Orders</span>
                  </div>
                  <div class="p-3 rounded-2xl bg-slate-100 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-center">
                    <DollarSign class="w-5 h-5 text-emerald-500 mx-auto mb-1" />
                    <span class="block text-base font-black text-slate-900 dark:text-white">{{ formatCurrency(selectedHotel.revenue_total, selectedHotel.currency) }}</span>
                    <span class="text-[10px] text-slate-400 font-bold uppercase">Revenue</span>
                  </div>
                </div>
              </div>

              <!-- Information Grid -->
              <div class="p-5 rounded-2xl bg-slate-50 dark:bg-slate-800/40 border border-slate-200 dark:border-slate-800 space-y-3">
                <h3 class="font-extrabold text-slate-900 dark:text-white text-xs uppercase tracking-wider">
                  Hotel Details
                </h3>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                  <div class="flex items-center gap-2">
                    <Mail class="w-4 h-4 text-slate-400" />
                    <div>
                      <span class="text-slate-400 block text-[10px]">Email</span>
                      <span class="font-bold text-slate-800 dark:text-slate-200">{{ selectedHotel.email || 'N/A' }}</span>
                    </div>
                  </div>
                  <div class="flex items-center gap-2">
                    <Phone class="w-4 h-4 text-slate-400" />
                    <div>
                      <span class="text-slate-400 block text-[10px]">Phone</span>
                      <span class="font-bold text-slate-800 dark:text-slate-200">{{ selectedHotel.phone || 'N/A' }}</span>
                    </div>
                  </div>
                  <div class="flex items-center gap-2">
                    <MapPin class="w-4 h-4 text-slate-400" />
                    <div>
                      <span class="text-slate-400 block text-[10px]">Address</span>
                      <span class="font-bold text-slate-800 dark:text-slate-200">{{ selectedHotel.address || 'N/A' }}</span>
                    </div>
                  </div>
                  <div class="flex items-center gap-2">
                    <Globe class="w-4 h-4 text-slate-400" />
                    <div>
                      <span class="text-slate-400 block text-[10px]">Location</span>
                      <span class="font-bold text-slate-800 dark:text-slate-200">{{ selectedHotel.city }}, {{ selectedHotel.country }}</span>
                    </div>
                  </div>
                  <div class="flex items-center gap-2">
                    <Clock class="w-4 h-4 text-slate-400" />
                    <div>
                      <span class="text-slate-400 block text-[10px]">Timezone</span>
                      <span class="font-bold text-slate-800 dark:text-slate-200">{{ selectedHotel.timezone || 'Africa/Addis_Ababa' }}</span>
                    </div>
                  </div>
                  <div class="flex items-center gap-2">
                    <Coins class="w-4 h-4 text-slate-400" />
                    <div>
                      <span class="text-slate-400 block text-[10px]">Currency</span>
                      <span class="font-bold text-slate-800 dark:text-slate-200">{{ selectedHotel.currency || 'ETB' }}</span>
                    </div>
                  </div>
                </div>
              </div>

              <!-- Admins List -->
              <div>
                <h3 class="font-extrabold text-slate-900 dark:text-white text-xs uppercase tracking-wider mb-2">
                  Assigned Hotel Administrators
                </h3>
                <div v-if="selectedHotel.admins && selectedHotel.admins.length > 0" class="space-y-2">
                  <div
                    v-for="adm in selectedHotel.admins"
                    :key="adm.id"
                    class="p-3 rounded-xl bg-slate-100 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 flex items-center justify-between"
                  >
                    <div>
                      <span class="font-bold text-slate-900 dark:text-white">{{ adm.name }}</span>
                      <span class="text-slate-400 block text-[11px] font-mono">{{ adm.email }}</span>
                    </div>
                    <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-indigo-500/10 text-indigo-500">
                      Hotel Admin
                    </span>
                  </div>
                </div>
                <div v-else class="p-4 rounded-xl bg-slate-100 dark:bg-slate-800 text-center text-slate-400">
                  No hotel administrators assigned yet.
                </div>
              </div>
            </div>

            <!-- Footer -->
            <div class="px-6 py-4 border-t border-slate-200 dark:border-slate-800 flex items-center justify-between">
              <div class="flex items-center gap-2">
                <span class="text-[11px] text-slate-400">Status:</span>
                <span class="font-bold uppercase text-xs">{{ selectedHotel.status }}</span>
              </div>
              <button
                @click="showDetailsModal = false"
                class="px-4 py-2 rounded-xl bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 font-bold text-xs cursor-pointer"
              >
                Close
              </button>
            </div>
          </div>
        </div>
      </Teleport>

      <!-- ========================================================================= -->
      <!-- 3. EDIT HOTEL MODAL -->
      <!-- ========================================================================= -->
      <Teleport to="body">
        <div v-if="showEditModal && selectedHotel" class="fixed inset-0 z-[9999] flex items-center justify-center p-4">
          <div class="fixed inset-0 bg-slate-950/80 backdrop-blur-xs" @click="showEditModal = false"></div>
          <div class="relative z-10 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-3xl max-w-xl w-full max-h-[90vh] flex flex-col shadow-2xl overflow-hidden animate-in fade-in zoom-in duration-150">
            <div class="flex items-center justify-between px-6 py-5 border-b border-slate-200 dark:border-slate-800">
              <div class="flex items-center gap-3">
                <div class="p-2.5 rounded-xl bg-blue-500/10 text-blue-600">
                  <Edit class="w-5 h-5" />
                </div>
                <div>
                  <h2 class="text-lg font-black text-slate-900 dark:text-white">Edit Hotel</h2>
                  <p class="text-xs text-slate-500">Update hotel profile and contact information</p>
                </div>
              </div>
              <button @click="showEditModal = false" class="p-2 text-slate-400 hover:text-slate-600 rounded-xl cursor-pointer">
                <X class="w-5 h-5" />
              </button>
            </div>

            <div class="flex-1 overflow-y-auto p-6 space-y-4 text-xs">
              <div class="grid grid-cols-1 sm:grid-cols-2 gap-3.5">
                <div class="sm:col-span-2">
                  <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">Hotel Name *</label>
                  <input
                    v-model="editForm.name"
                    type="text"
                    class="w-full px-3 py-2 bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl text-slate-900 dark:text-white font-medium"
                  />
                </div>
                <div>
                  <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">Email</label>
                  <input
                    v-model="editForm.email"
                    type="email"
                    class="w-full px-3 py-2 bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl text-slate-900 dark:text-white font-medium"
                  />
                </div>
                <div>
                  <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">Phone</label>
                  <input
                    v-model="editForm.phone"
                    type="text"
                    class="w-full px-3 py-2 bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl text-slate-900 dark:text-white font-medium"
                  />
                </div>
                <div class="sm:col-span-2">
                  <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">Address</label>
                  <input
                    v-model="editForm.address"
                    type="text"
                    class="w-full px-3 py-2 bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl text-slate-900 dark:text-white font-medium"
                  />
                </div>
                <div>
                  <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">City</label>
                  <input
                    v-model="editForm.city"
                    type="text"
                    class="w-full px-3 py-2 bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl text-slate-900 dark:text-white font-medium"
                  />
                </div>
                <div>
                  <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">Country</label>
                  <input
                    v-model="editForm.country"
                    type="text"
                    class="w-full px-3 py-2 bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl text-slate-900 dark:text-white font-medium"
                  />
                </div>
                <div>
                  <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">Timezone</label>
                  <input
                    v-model="editForm.timezone"
                    type="text"
                    class="w-full px-3 py-2 bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl text-slate-900 dark:text-white font-medium"
                  />
                </div>
                <div>
                  <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">Currency</label>
                  <input
                    v-model="editForm.currency"
                    type="text"
                    class="w-full px-3 py-2 bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl text-slate-900 dark:text-white font-medium"
                  />
                </div>
              </div>
            </div>

            <div class="px-6 py-4 border-t border-slate-200 dark:border-slate-800 flex items-center justify-end gap-2.5">
              <button
                @click="showEditModal = false"
                class="px-4 py-2 rounded-xl bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 font-bold text-xs"
              >
                Cancel
              </button>
              <button
                :disabled="saving"
                @click="handleUpdateHotel"
                class="px-5 py-2 rounded-xl bg-blue-600 hover:bg-blue-500 text-white font-extrabold text-xs shadow-md transition disabled:opacity-50 flex items-center gap-1.5"
              >
                <RefreshCw v-if="saving" class="w-3.5 h-3.5 animate-spin" />
                <span>{{ saving ? 'Saving...' : 'Save Changes' }}</span>
              </button>
            </div>
          </div>
        </div>
      </Teleport>

      <!-- ========================================================================= -->
      <!-- 4. SUSPEND HOTEL MODAL -->
      <!-- ========================================================================= -->
      <Teleport to="body">
        <div v-if="showSuspendModal && selectedHotel" class="fixed inset-0 z-[9999] flex items-center justify-center p-4">
          <div class="fixed inset-0 bg-slate-950/80 backdrop-blur-xs" @click="showSuspendModal = false"></div>
          <div class="relative z-10 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-3xl max-w-md w-full p-6 shadow-2xl text-xs space-y-4">
            <div class="flex items-center gap-3">
              <div class="p-3 rounded-2xl bg-amber-500/10 text-amber-600 dark:text-amber-400">
                <ShieldAlert class="w-6 h-6" />
              </div>
              <div>
                <h3 class="text-base font-black text-slate-900 dark:text-white">Suspend Hotel Property</h3>
                <p class="text-slate-400">Restricts tenant access while preserving all data</p>
              </div>
            </div>

            <p class="text-slate-600 dark:text-slate-300 leading-relaxed">
              Are you sure you want to suspend <strong>{{ selectedHotel.name }}</strong>?
              Hotel staff and administrators will immediately be unable to operate the system.
              <span class="text-emerald-600 dark:text-emerald-400 block font-bold mt-1">
                ✓ No historical data, bookings, or payments will be deleted.
              </span>
            </p>

            <div class="flex items-center justify-end gap-2.5 pt-2">
              <button
                @click="showSuspendModal = false"
                class="px-4 py-2 rounded-xl bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 font-bold"
              >
                Cancel
              </button>
              <button
                :disabled="saving"
                @click="handleConfirmSuspend"
                class="px-5 py-2 rounded-xl bg-amber-600 hover:bg-amber-500 text-white font-extrabold shadow-md transition disabled:opacity-50"
              >
                {{ saving ? 'Suspending...' : 'Confirm Suspension' }}
              </button>
            </div>
          </div>
        </div>
      </Teleport>

      <!-- ========================================================================= -->
      <!-- 5. ARCHIVE HOTEL MODAL -->
      <!-- ========================================================================= -->
      <Teleport to="body">
        <div v-if="showArchiveModal && selectedHotel" class="fixed inset-0 z-[9999] flex items-center justify-center p-4">
          <div class="fixed inset-0 bg-slate-950/80 backdrop-blur-xs" @click="showArchiveModal = false"></div>
          <div class="relative z-10 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-3xl max-w-md w-full p-6 shadow-2xl text-xs space-y-4">
            <div class="flex items-center gap-3">
              <div class="p-3 rounded-2xl bg-purple-500/10 text-purple-600 dark:text-purple-400">
                <Archive class="w-6 h-6" />
              </div>
              <div>
                <h3 class="text-base font-black text-slate-900 dark:text-white">Archive Hotel Property</h3>
                <p class="text-slate-400">Safe storage without accidental permanent deletion</p>
              </div>
            </div>

            <p class="text-slate-600 dark:text-slate-300 leading-relaxed">
              Archiving <strong>{{ selectedHotel.name }}</strong> safely marks it as archived while retaining all 
              reservations, guest profiles, financial payments, and order histories indefinitely.
            </p>

            <div class="flex items-center justify-end gap-2.5 pt-2">
              <button
                @click="showArchiveModal = false"
                class="px-4 py-2 rounded-xl bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 font-bold"
              >
                Cancel
              </button>
              <button
                :disabled="saving"
                @click="handleConfirmArchive"
                class="px-5 py-2 rounded-xl bg-purple-600 hover:bg-purple-500 text-white font-extrabold shadow-md transition disabled:opacity-50"
              >
                {{ saving ? 'Archiving...' : 'Archive Property' }}
              </button>
            </div>
          </div>
        </div>
      </Teleport>

      <!-- ========================================================================= -->
      <!-- 6. PERMANENT DELETE MODAL -->
      <!-- ========================================================================= -->
      <Teleport to="body">
        <div v-if="showDeleteModal && selectedHotel" class="fixed inset-0 z-[9999] flex items-center justify-center p-4">
          <div class="fixed inset-0 bg-slate-950/80 backdrop-blur-xs" @click="showDeleteModal = false"></div>
          <div class="relative z-10 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-3xl max-w-md w-full p-6 shadow-2xl text-xs space-y-4">
            <div class="flex items-center gap-3">
              <div class="p-3 rounded-2xl bg-rose-500/10 text-rose-600">
                <Trash2 class="w-6 h-6" />
              </div>
              <div>
                <h3 class="text-base font-black text-rose-600 dark:text-rose-400">Permanent Destruction</h3>
                <p class="text-slate-400">High-risk action</p>
              </div>
            </div>

            <p class="text-slate-600 dark:text-slate-300 leading-relaxed">
              Permanent deletion will completely remove <strong>{{ selectedHotel.name }}</strong>. 
              To confirm, please type the exact hotel name below:
            </p>

            <div>
              <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">
                Type <span class="font-mono text-rose-600 font-bold">{{ selectedHotel.name }}</span> to confirm:
              </label>
              <input
                v-model="deleteConfirmName"
                type="text"
                class="w-full px-3 py-2 bg-slate-50 dark:bg-slate-800 border border-rose-300 dark:border-rose-800 rounded-xl text-slate-900 dark:text-white font-medium"
              />
            </div>

            <div class="flex items-center justify-end gap-2.5 pt-2">
              <button
                @click="showDeleteModal = false"
                class="px-4 py-2 rounded-xl bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 font-bold"
              >
                Cancel
              </button>
              <button
                :disabled="saving || deleteConfirmName.trim() !== selectedHotel.name.trim()"
                @click="handleConfirmPermanentDelete"
                class="px-5 py-2 rounded-xl bg-rose-600 hover:bg-rose-500 disabled:opacity-40 text-white font-extrabold shadow-md transition"
              >
                {{ saving ? 'Deleting...' : 'Delete Permanently' }}
              </button>
            </div>
          </div>
        </div>
      </Teleport>
    </div>
  </DashboardLayout>
</template>

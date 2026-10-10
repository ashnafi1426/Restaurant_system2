<script setup lang="ts">
import { ref, computed, onMounted, onUnmounted, watch } from 'vue'
import { useRouter } from 'vue-router'
import DashboardLayout from '@/Layouts/DashboardLayout.vue'
import { platformService, type Hotel, type CreateHotelPayload } from '@/services/platformService'
import { useHotelStore } from '@/stores/hotelStore'
import { useLanguageStore } from '@/stores/language'
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
  RotateCcw,
  Loader2,
  Maximize2,
  Minimize2,
  BedDouble,
  Users,
  UserCheck,
  CalendarCheck,
  Utensils,
  DollarSign,
} from 'lucide-vue-next'

const router = useRouter()
const hotelStore = useHotelStore()
const languageStore = useLanguageStore()

// State
const hotels = ref<Hotel[]>([])
const loading = ref(true)
const saving = ref(false)
const isFilterOpen = ref(false)
const searchQuery = ref('')
const selectedStatus = ref('all')
const selectedCity = ref('all')
const successMessage = ref('')
const errorMessage = ref('')
let messageTimeout: ReturnType<typeof setTimeout> | null = null

const notify = (type: 'success' | 'error', msg: string) => {
  if (messageTimeout) clearTimeout(messageTimeout)
  if (type === 'success') {
    successMessage.value = msg
    errorMessage.value = ''
  } else {
    errorMessage.value = msg
    successMessage.value = ''
  }
  messageTimeout = setTimeout(() => {
    successMessage.value = ''
    errorMessage.value = ''
  }, 4000)
}

const toggleFilter = () => {
  isFilterOpen.value = !isFilterOpen.value
}

const resetFilters = () => {
  searchQuery.value = ''
  selectedStatus.value = 'all'
  selectedCity.value = 'all'
}

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

const defaultHotelForm = (): CreateHotelPayload => ({
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

// Form State
const createInitialAdmin = ref(true)
const hotelForm = ref<CreateHotelPayload>(defaultHotelForm())

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
  status: 'active' as 'active' | 'inactive' | 'suspended' | 'archived',
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
    notify('error', err?.response?.data?.message || 'Failed to load hotels catalog.')
  } finally {
    loading.value = false
  }
}

const handleOutsideClick = () => {
  activeDropdownId.value = null
}

onMounted(() => {
  loadHotels()
  window.addEventListener('click', handleOutsideClick)
})

onUnmounted(() => {
  if (messageTimeout) clearTimeout(messageTimeout)
  window.removeEventListener('click', handleOutsideClick)
})

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
  hotelForm.value = defaultHotelForm()
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
    notify('error', 'Hotel Name and Slug are required.')
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
    notify('success', `Hotel "${created.name}" onboarded successfully!`)
    showCreateModal.value = false
    await loadHotels()
  } catch (err: any) {
    console.error('[HotelManagement] Create hotel error:', err)
    notify('error', err?.response?.data?.message || 'Failed to create hotel.')
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
    notify('success', `Hotel "${editForm.value.name}" updated successfully!`)
    showEditModal.value = false
    await loadHotels()
  } catch (err: any) {
    console.error('[HotelManagement] Update hotel error:', err)
    notify('error', err?.response?.data?.message || 'Failed to update hotel.')
  } finally {
    saving.value = false
  }
}

const enterHotelViewMode = async (hotel: Hotel) => {
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
    console.error('[HotelManagement] Enter hotel view mode error:', err)
    notify('error', err?.response?.data?.message || 'Failed to enter hotel view mode.')
  }
}

const handleEnterHotelViewMode = enterHotelViewMode

const updateStatus = async (hotelId: string, status: 'active' | 'inactive' | 'suspended', successText: string) => {
  saving.value = true
  try {
    await platformService.updateHotelStatus(hotelId, status)
    notify('success', successText)
    await loadHotels()
  } catch (err: any) {
    console.error(`[HotelManagement] Set status ${status} error:`, err)
    notify('error', err?.response?.data?.message || 'Failed to update hotel status.')
  } finally {
    saving.value = false
  }
}

const activateHotel = (hotel: Hotel) => {
  activeDropdownId.value = null
  updateStatus(hotel.id, 'active', `Hotel "${hotel.name}" has been activated. Operations restored.`)
}

const handleActivateHotel = activateHotel

const deactivateHotel = (hotel: Hotel) => {
  activeDropdownId.value = null
  updateStatus(hotel.id, 'inactive', `Hotel "${hotel.name}" has been set to inactive.`)
}

const handleDeactivateHotel = deactivateHotel

const confirmSuspend = async () => {
  if (!selectedHotel.value) return
  await updateStatus(selectedHotel.value.id, 'suspended', `Hotel "${selectedHotel.value.name}" is now SUSPENDED. Data is safely preserved.`)
  showSuspendModal.value = false
}

const handleConfirmSuspend = confirmSuspend

const confirmArchive = async () => {
  if (!selectedHotel.value) return
  saving.value = true
  try {
    await platformService.archiveHotel(selectedHotel.value.id)
    notify('success', `Hotel "${selectedHotel.value.name}" has been ARCHIVED. All records are preserved.`)
    showArchiveModal.value = false
    await loadHotels()
  } catch (err: any) {
    console.error('[HotelManagement] Archive hotel error:', err)
    notify('error', err?.response?.data?.message || 'Failed to archive hotel.')
  } finally {
    saving.value = false
  }
}

const handleConfirmArchive = confirmArchive

const confirmDelete = async () => {
  if (!selectedHotel.value) return
  if (deleteConfirmName.value.trim() !== selectedHotel.value.name.trim()) {
    notify('error', 'Typed hotel name does not match.')
    return
  }

  saving.value = true
  try {
    await platformService.deleteHotel(selectedHotel.value.id, deleteConfirmName.value.trim())
    notify('success', `Hotel "${selectedHotel.value.name}" deleted permanently.`)
    showDeleteModal.value = false
    await loadHotels()
  } catch (err: any) {
    console.error('[HotelManagement] Delete hotel error:', err)
    notify('error', err?.response?.data?.message || 'Failed to permanently delete hotel.')
  } finally {
    saving.value = false
  }
}

const handleConfirmPermanentDelete = confirmDelete
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
                  {{ languageStore.t('hotels_management', 'Hotels Management') }}
                </h1>
                <span class="px-2.5 py-0.5 rounded-full text-xs font-black bg-indigo-500/10 text-indigo-600 dark:text-indigo-400 border border-indigo-500/20">
                  {{ totalHotels }} {{ languageStore.t('hotels_count', 'Hotels') }}
                </span>
              </div>
              <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">
                {{ languageStore.t('hotels_governance_desc', 'Centralized platform governance to onboard, configure, suspend, and supervise multi-tenant hotel properties.') }}
              </p>
            </div>
          </div>
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
              :placeholder="languageStore.t('search_hotel_placeholder', 'Search hotel by name, city, email, code...')"
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
            <span>{{ isFilterOpen ? languageStore.t('hide_filter', 'Hide Filter') : languageStore.t('filter', 'Filter') }}</span>
          </button>
        </div>

        <!-- Right: Action Buttons -->
        <div class="flex items-center gap-2 sm:gap-2.5">
          <!-- Refresh Button -->
          <button
            type="button"
            @click="loadHotels"
            :disabled="loading"
            :title="languageStore.t('refresh', 'Refresh')"
            class="inline-flex items-center justify-center h-9 w-9 sm:h-10 sm:w-10 rounded-xl border border-slate-200 dark:border-[#1e3455] bg-white dark:bg-[#13233c]/80 text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-[#1c3356] hover:text-slate-900 dark:hover:text-white transition disabled:opacity-50 cursor-pointer"
          >
            <RefreshCw class="w-4 h-4" :class="{ 'animate-spin': loading }" />
          </button>

          <!-- Fullscreen Toggle -->
          <button
            type="button"
            @click="toggleFullscreen"
            :title="languageStore.t('fullscreen', 'Toggle Fullscreen')"
            class="inline-flex items-center justify-center h-9 w-9 sm:h-10 sm:w-10 rounded-xl border border-slate-200 dark:border-[#1e3455] bg-white dark:bg-[#13233c]/80 text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-[#1c3356] hover:text-slate-900 dark:hover:text-white transition cursor-pointer"
          >
            <component :is="isFullscreen ? Minimize2 : Maximize2" class="w-4 h-4" />
          </button>

          <!-- Add Hotel Primary Button -->
          <button
            type="button"
            @click="openCreateModal"
            class="inline-flex items-center gap-1.5 rounded-xl bg-blue-600 hover:bg-blue-700 text-white px-3.5 sm:px-4 py-2.5 text-xs sm:text-sm font-bold shadow-md shadow-blue-600/25 transition active:scale-98 cursor-pointer flex-shrink-0"
          >
            <Plus class="w-4 h-4 text-white" />
            <span class="text-white">{{ languageStore.t('create_hotel', 'Create Hotel') }}</span>
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
            <!-- Status Filter -->
            <div>
              <label class="mb-1.5 block text-xs font-semibold text-slate-600 dark:text-slate-300">
                {{ languageStore.t('hotel_status', 'Hotel Status') }}
              </label>
              <select
                v-model="selectedStatus"
                class="w-full rounded-xl border border-slate-200 dark:border-[#1e3455] bg-slate-50/80 dark:bg-[#13233c] text-slate-900 dark:text-white px-3.5 py-2 text-xs sm:text-sm focus:ring-2 focus:ring-blue-500/40 focus:border-blue-500 dark:focus:border-blue-400 transition cursor-pointer font-medium outline-none"
              >
                <option value="all">{{ languageStore.t('all_statuses', 'All Statuses') }}</option>
                <option value="active">{{ languageStore.t('active', 'Active') }}</option>
                <option value="inactive">{{ languageStore.t('inactive', 'Inactive') }}</option>
                <option value="suspended">{{ languageStore.t('suspended', 'Suspended') }}</option>
                <option value="archived">{{ languageStore.t('archived', 'Archived') }}</option>
              </select>
            </div>

            <!-- City Filter -->
            <div>
              <label class="mb-1.5 block text-xs font-semibold text-slate-600 dark:text-slate-300">
                {{ languageStore.t('location_city', 'Location City') }}
              </label>
              <select
                v-model="selectedCity"
                class="w-full rounded-xl border border-slate-200 dark:border-[#1e3455] bg-slate-50/80 dark:bg-[#13233c] text-slate-900 dark:text-white px-3.5 py-2 text-xs sm:text-sm focus:ring-2 focus:ring-blue-500/40 focus:border-blue-500 dark:focus:border-blue-400 transition cursor-pointer font-medium outline-none"
              >
                <option value="all">{{ languageStore.t('all_cities', 'All Cities') }}</option>
                <option v-for="city in availableCities" :key="city" :value="city">
                  {{ city }}
                </option>
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
                <span>{{ languageStore.t('reset_filters', 'Reset Filters') }}</span>
              </button>
            </div>
          </div>
        </div>
      </Transition>

      <!-- HOTELS TABLE -->
      <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-3xl shadow-xs overflow-hidden">
        <div class="overflow-x-auto">
          <table class="w-full text-left text-xs border-collapse">
            <thead class="bg-slate-100/70 dark:bg-slate-800/80 text-slate-600 dark:text-slate-300 font-bold border-b border-slate-200 dark:border-slate-700/80">
              <tr>
                <th class="py-4 px-5">{{ languageStore.t('hotel', 'Hotel') }}</th>
                <th class="py-4 px-4">{{ languageStore.t('location', 'Location') }}</th>
                <th class="py-4 px-4">{{ languageStore.t('hotel_admin', 'Hotel Admin') }}</th>
                <th class="py-4 px-4">{{ languageStore.t('rooms', 'Rooms') }}</th>
                <th class="py-4 px-4">{{ languageStore.t('status', 'Status') }}</th>
                <th class="py-4 px-5 text-right">{{ languageStore.t('actions', 'Actions') }}</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-100 dark:divide-slate-800 text-slate-700 dark:text-slate-300 font-medium">
              <!-- Loading Skeleton -->
              <tr v-if="loading">
                <td colspan="6" class="p-12 text-center text-xs text-slate-400">
                  <RefreshCw class="w-6 h-6 text-indigo-500 animate-spin mx-auto mb-2" />
                  <span>{{ languageStore.t('loading_hotels', 'Loading platform hotels...') }}</span>
                </td>
              </tr>

              <!-- Empty -->
              <tr v-else-if="hotels.length === 0">
                <td colspan="6" class="p-12 text-center text-xs text-slate-400">
                  <Building2 class="w-8 h-8 text-slate-300 dark:text-slate-600 mx-auto mb-2" />
                  <p class="font-bold">{{ languageStore.t('no_hotels_found', 'No hotels found.') }}</p>
                  <p class="text-[11px] text-slate-400 mt-0.5">{{ languageStore.t('refine_hotel_search', 'Try refining your search or add a new hotel.') }}</p>
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
                    <span>{{ hotel.admin_name || languageStore.t('no_admin_assigned', 'No Admin Assigned') }}</span>
                  </div>
                  <span v-if="hotel.admin_email" class="text-[10px] text-slate-400 block ml-5 font-mono">
                    {{ hotel.admin_email }}
                  </span>
                </td>

                <!-- Rooms -->
                <td class="py-4 px-4">
                  <span class="px-2.5 py-1 rounded-lg bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 font-bold text-xs">
                    {{ hotel.rooms_count ?? 0 }} {{ languageStore.t('rooms', 'rooms') }}
                  </span>
                </td>

                <!-- Status Badge -->
                <td class="py-4 px-4">
                  <span
                    v-if="hotel.status === 'active'"
                    class="px-2.5 py-1 rounded-full text-xs font-black bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 border border-emerald-500/20"
                  >
                    {{ languageStore.t('active', 'Active') }}
                  </span>
                  <span
                    v-else-if="hotel.status === 'suspended'"
                    class="px-2.5 py-1 rounded-full text-xs font-black bg-amber-500/10 text-amber-600 dark:text-amber-400 border border-amber-500/20"
                  >
                    {{ languageStore.t('suspended', 'Suspended') }}
                  </span>
                  <span
                    v-else-if="hotel.status === 'archived'"
                    class="px-2.5 py-1 rounded-full text-xs font-black bg-purple-500/10 text-purple-600 dark:text-purple-400 border border-purple-500/20"
                  >
                    {{ languageStore.t('archived', 'Archived') }}
                  </span>
                  <span
                    v-else
                    class="px-2.5 py-1 rounded-full text-xs font-black bg-slate-500/10 text-slate-500 dark:text-slate-400 border border-slate-500/20"
                  >
                    {{ languageStore.t('inactive', 'Inactive') }}
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
                      <span>{{ languageStore.t('view_details', 'View Details') }}</span>
                    </button>

                    <button
                      @click="handleEnterHotelViewMode(hotel)"
                      class="w-full px-3.5 py-2 hover:bg-indigo-50 dark:hover:bg-indigo-950/40 text-indigo-600 dark:text-indigo-400 flex items-center gap-2.5 font-bold transition cursor-pointer"
                    >
                      <LayoutDashboard class="w-3.5 h-3.5 text-indigo-500" />
                      <span>{{ languageStore.t('view_hotel_dashboard', 'View Hotel Dashboard') }}</span>
                    </button>

                    <button
                      @click="openEditModal(hotel)"
                      class="w-full px-3.5 py-2 hover:bg-slate-100 dark:hover:bg-slate-800 text-slate-700 dark:text-slate-200 flex items-center gap-2.5 font-bold transition cursor-pointer"
                    >
                      <Edit class="w-3.5 h-3.5 text-blue-500" />
                      <span>{{ languageStore.t('edit_hotel', 'Edit Hotel') }}</span>
                    </button>

                    <div class="h-px bg-slate-100 dark:bg-slate-800 my-1"></div>

                    <!-- Activate if not active -->
                    <button
                      v-if="hotel.status !== 'active'"
                      @click="handleActivateHotel(hotel)"
                      class="w-full px-3.5 py-2 hover:bg-emerald-50 dark:hover:bg-emerald-950/40 text-emerald-600 dark:text-emerald-400 flex items-center gap-2.5 font-bold transition cursor-pointer"
                    >
                      <Power class="w-3.5 h-3.5 text-emerald-500" />
                      <span>{{ languageStore.t('activate_hotel', 'Activate Hotel') }}</span>
                    </button>

                    <!-- Deactivate if active -->
                    <button
                      v-if="hotel.status === 'active'"
                      @click="handleDeactivateHotel(hotel)"
                      class="w-full px-3.5 py-2 hover:bg-slate-100 dark:hover:bg-slate-800 text-slate-600 dark:text-slate-300 flex items-center gap-2.5 font-bold transition cursor-pointer"
                    >
                      <Power class="w-3.5 h-3.5 text-slate-400" />
                      <span>{{ languageStore.t('deactivate_hotel', 'Deactivate Hotel') }}</span>
                    </button>

                    <!-- Suspend -->
                    <button
                      v-if="hotel.status !== 'suspended'"
                      @click="triggerSuspendModal(hotel)"
                      class="w-full px-3.5 py-2 hover:bg-amber-50 dark:hover:bg-amber-950/40 text-amber-600 dark:text-amber-400 flex items-center gap-2.5 font-bold transition cursor-pointer"
                    >
                      <ShieldAlert class="w-3.5 h-3.5 text-amber-500" />
                      <span>{{ languageStore.t('suspend_hotel', 'Suspend Hotel') }}</span>
                    </button>

                    <!-- Archive -->
                    <button
                      v-if="hotel.status !== 'archived'"
                      @click="triggerArchiveModal(hotel)"
                      class="w-full px-3.5 py-2 hover:bg-purple-50 dark:hover:bg-purple-950/40 text-purple-600 dark:text-purple-400 flex items-center gap-2.5 font-bold transition cursor-pointer"
                    >
                      <Archive class="w-3.5 h-3.5 text-purple-500" />
                      <span>{{ languageStore.t('archive_hotel', 'Archive Hotel') }}</span>
                    </button>

                    <div class="h-px bg-slate-100 dark:bg-slate-800 my-1"></div>

                    <!-- Permanent Delete -->
                    <button
                      @click="triggerDeleteModal(hotel)"
                      class="w-full px-3.5 py-2 hover:bg-rose-50 dark:hover:bg-rose-950/40 text-rose-600 dark:text-rose-400 flex items-center gap-2.5 font-bold transition cursor-pointer"
                    >
                      <Trash2 class="w-3.5 h-3.5 text-rose-500" />
                      <span>{{ languageStore.t('permanent_delete', 'Permanent Delete') }}</span>
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
            {{ languageStore.t('page', 'Page') }} {{ currentPage }} {{ languageStore.t('of', 'of') }} {{ lastPage }} ({{ totalHotels }} {{ languageStore.t('total', 'total') }})
          </span>
          <div class="flex items-center gap-2">
            <button
              :disabled="currentPage <= 1"
              @click="currentPage--; loadHotels()"
              class="px-3 py-1.5 rounded-lg bg-slate-100 dark:bg-slate-800 text-xs font-bold disabled:opacity-40"
            >
              {{ languageStore.t('previous', 'Previous') }}
            </button>
            <button
              :disabled="currentPage >= lastPage"
              @click="currentPage++; loadHotels()"
              class="px-3 py-1.5 rounded-lg bg-slate-100 dark:bg-slate-800 text-xs font-bold disabled:opacity-40"
            >
              {{ languageStore.t('next', 'Next') }}
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
                  <h2 class="text-lg font-black text-slate-900 dark:text-white">{{ languageStore.t('create_new_hotel', 'Create New Hotel') }}</h2>
                  <p class="text-xs text-slate-500">{{ languageStore.t('add_tenant_desc', 'Add a new tenant property to the platform') }}</p>
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
                  <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">{{ languageStore.t('hotel_name', 'Hotel Name') }} *</label>
                  <input
                    v-model="hotelForm.name"
                    type="text"
                    placeholder="e.g. Addis Grand Luxury Hotel"
                    class="w-full px-3 py-2 bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl text-slate-900 dark:text-white font-medium"
                  />
                </div>
                <div>
                  <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">{{ languageStore.t('hotel_slug', 'Hotel Slug') }} *</label>
                  <input
                    v-model="hotelForm.slug"
                    type="text"
                    placeholder="e.g. addis-grand"
                    class="w-full px-3 py-2 bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl text-slate-900 dark:text-white font-mono"
                  />
                </div>
                <div>
                  <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">{{ languageStore.t('email', 'Email') }} *</label>
                  <input
                    v-model="hotelForm.email"
                    type="email"
                    placeholder="contact@hotel.com"
                    class="w-full px-3 py-2 bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl text-slate-900 dark:text-white font-medium"
                  />
                </div>
                <div>
                  <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">{{ languageStore.t('phone', 'Phone') }} *</label>
                  <input
                    v-model="hotelForm.phone"
                    type="text"
                    placeholder="+251 91 123 4567"
                    class="w-full px-3 py-2 bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl text-slate-900 dark:text-white font-medium"
                  />
                </div>
                <div class="sm:col-span-2">
                  <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">{{ languageStore.t('address', 'Address') }} *</label>
                  <input
                    v-model="hotelForm.address"
                    type="text"
                    placeholder="Bole Road, Sub-city, Street 12"
                    class="w-full px-3 py-2 bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl text-slate-900 dark:text-white font-medium"
                  />
                </div>
                <div>
                  <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">{{ languageStore.t('city', 'City') }} *</label>
                  <input
                    v-model="hotelForm.city"
                    type="text"
                    placeholder="Addis Ababa"
                    class="w-full px-3 py-2 bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl text-slate-900 dark:text-white font-medium"
                  />
                </div>
                <div>
                  <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">{{ languageStore.t('country', 'Country') }} *</label>
                  <input
                    v-model="hotelForm.country"
                    type="text"
                    placeholder="Ethiopia"
                    class="w-full px-3 py-2 bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl text-slate-900 dark:text-white font-medium"
                  />
                </div>
                <div>
                  <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">{{ languageStore.t('timezone', 'Timezone') }} *</label>
                  <input
                    v-model="hotelForm.timezone"
                    type="text"
                    class="w-full px-3 py-2 bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl text-slate-900 dark:text-white font-medium"
                  />
                </div>
                <div>
                  <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">{{ languageStore.t('currency', 'Currency') }} *</label>
                  <input
                    v-model="hotelForm.currency"
                    type="text"
                    class="w-full px-3 py-2 bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl text-slate-900 dark:text-white font-medium"
                  />
                </div>
                <div class="sm:col-span-2">
                  <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">{{ languageStore.t('initial_status', 'Initial Status') }}</label>
                  <select
                    v-model="hotelForm.status"
                    class="w-full px-3 py-2 bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl text-slate-900 dark:text-white font-medium"
                  >
                    <option value="active">{{ languageStore.t('operational_immediately', 'Active (Fully operational immediately)') }}</option>
                    <option value="inactive">{{ languageStore.t('disabled_until_configured', 'Inactive (Disabled until configured)') }}</option>
                    <option value="suspended">{{ languageStore.t('suspended', 'Suspended') }}</option>
                  </select>
                </div>
              </div>

              <!-- Initial Hotel Admin Section -->
              <div class="pt-4 border-t border-slate-200 dark:border-slate-800">
                <label class="flex items-center gap-2 cursor-pointer mb-3">
                  <input type="checkbox" v-model="createInitialAdmin" class="rounded text-indigo-600 accent-indigo-600" />
                  <span class="font-extrabold text-slate-900 dark:text-white">{{ languageStore.t('create_initial_admin', 'Create Initial Hotel Admin Account') }}</span>
                </label>

                <div v-if="createInitialAdmin" class="grid grid-cols-1 sm:grid-cols-2 gap-3.5 p-4 rounded-2xl bg-indigo-50/50 dark:bg-indigo-950/20 border border-indigo-500/20">
                  <div>
                    <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">{{ languageStore.t('admin_first_name', 'Admin First Name') }} *</label>
                    <input
                      v-model="hotelForm.admin_first_name"
                      type="text"
                      placeholder="e.g. Abebe"
                      class="w-full px-3 py-2 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-700 rounded-xl text-slate-900 dark:text-white font-medium"
                    />
                  </div>
                  <div>
                    <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">{{ languageStore.t('admin_last_name', 'Admin Last Name') }} *</label>
                    <input
                      v-model="hotelForm.admin_last_name"
                      type="text"
                      placeholder="e.g. Kebede"
                      class="w-full px-3 py-2 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-700 rounded-xl text-slate-900 dark:text-white font-medium"
                    />
                  </div>
                  <div>
                    <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">{{ languageStore.t('admin_email', 'Admin Email') }} *</label>
                    <input
                      v-model="hotelForm.admin_email"
                      type="email"
                      placeholder="admin@hotel.com"
                      class="w-full px-3 py-2 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-700 rounded-xl text-slate-900 dark:text-white font-medium"
                    />
                  </div>
                  <div>
                    <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">{{ languageStore.t('admin_password_hint', 'Password (Default: HotelAdmin123@)') }}</label>
                    <input
                      v-model="hotelForm.admin_password"
                      type="password"
                      :placeholder="languageStore.t('leave_blank_default', 'Leave blank for default')"
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
                {{ languageStore.t('cancel', 'Cancel') }}
              </button>
              <button
                type="button"
                :disabled="saving"
                @click="handleCreateHotel"
                class="px-5 py-2 rounded-xl bg-indigo-600 hover:bg-indigo-500 text-white font-extrabold text-xs shadow-md transition disabled:opacity-50 flex items-center gap-1.5"
              >
                <RefreshCw v-if="saving" class="w-3.5 h-3.5 animate-spin" />
                <span>{{ saving ? languageStore.t('creating', 'Creating...') : languageStore.t('create_hotel', 'Create Hotel') }}</span>
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
                  {{ languageStore.t('hotel_statistics', 'Hotel Statistics & Overview') }}
                </h3>
                <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-3">
                  <div class="p-3 rounded-2xl bg-slate-100 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-center">
                    <BedDouble class="w-5 h-5 text-indigo-500 mx-auto mb-1" />
                    <span class="block text-base font-black text-slate-900 dark:text-white">{{ selectedHotel.rooms_count ?? 0 }}</span>
                    <span class="text-[10px] text-slate-400 font-bold uppercase">{{ languageStore.t('rooms', 'Rooms') }}</span>
                  </div>
                  <div class="p-3 rounded-2xl bg-slate-100 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-center">
                    <Users class="w-5 h-5 text-blue-500 mx-auto mb-1" />
                    <span class="block text-base font-black text-slate-900 dark:text-white">{{ selectedHotel.staff_count ?? 0 }}</span>
                    <span class="text-[10px] text-slate-400 font-bold uppercase">{{ languageStore.t('staff', 'Staff') }}</span>
                  </div>
                  <div class="p-3 rounded-2xl bg-slate-100 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-center">
                    <UserCheck class="w-5 h-5 text-teal-500 mx-auto mb-1" />
                    <span class="block text-base font-black text-slate-900 dark:text-white">{{ selectedHotel.guests_count ?? 0 }}</span>
                    <span class="text-[10px] text-slate-400 font-bold uppercase">{{ languageStore.t('guests', 'Guests') }}</span>
                  </div>
                  <div class="p-3 rounded-2xl bg-slate-100 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-center">
                    <CalendarCheck class="w-5 h-5 text-amber-500 mx-auto mb-1" />
                    <span class="block text-base font-black text-slate-900 dark:text-white">{{ selectedHotel.reservations_count ?? 0 }}</span>
                    <span class="text-[10px] text-slate-400 font-bold uppercase">{{ languageStore.t('bookings', 'Bookings') }}</span>
                  </div>
                  <div class="p-3 rounded-2xl bg-slate-100 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-center">
                    <Utensils class="w-5 h-5 text-orange-500 mx-auto mb-1" />
                    <span class="block text-base font-black text-slate-900 dark:text-white">{{ selectedHotel.orders_count ?? 0 }}</span>
                    <span class="text-[10px] text-slate-400 font-bold uppercase">{{ languageStore.t('orders', 'Orders') }}</span>
                  </div>
                  <div class="p-3 rounded-2xl bg-slate-100 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-center">
                    <DollarSign class="w-5 h-5 text-emerald-500 mx-auto mb-1" />
                    <span class="block text-base font-black text-slate-900 dark:text-white">{{ formatCurrency(selectedHotel.revenue_total, selectedHotel.currency) }}</span>
                    <span class="text-[10px] text-slate-400 font-bold uppercase">{{ languageStore.t('revenue', 'Revenue') }}</span>
                  </div>
                </div>
              </div>

              <!-- Information Grid -->
              <div class="p-5 rounded-2xl bg-slate-50 dark:bg-slate-800/40 border border-slate-200 dark:border-slate-800 space-y-3">
                <h3 class="font-extrabold text-slate-900 dark:text-white text-xs uppercase tracking-wider">
                  {{ languageStore.t('hotel_details', 'Hotel Details') }}
                </h3>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                  <div class="flex items-center gap-2">
                    <Mail class="w-4 h-4 text-slate-400" />
                    <div>
                      <span class="text-slate-400 block text-[10px]">{{ languageStore.t('email', 'Email') }}</span>
                      <span class="font-bold text-slate-800 dark:text-slate-200">{{ selectedHotel.email || 'N/A' }}</span>
                    </div>
                  </div>
                  <div class="flex items-center gap-2">
                    <Phone class="w-4 h-4 text-slate-400" />
                    <div>
                      <span class="text-slate-400 block text-[10px]">{{ languageStore.t('phone', 'Phone') }}</span>
                      <span class="font-bold text-slate-800 dark:text-slate-200">{{ selectedHotel.phone || 'N/A' }}</span>
                    </div>
                  </div>
                  <div class="flex items-center gap-2">
                    <MapPin class="w-4 h-4 text-slate-400" />
                    <div>
                      <span class="text-slate-400 block text-[10px]">{{ languageStore.t('address', 'Address') }}</span>
                      <span class="font-bold text-slate-800 dark:text-slate-200">{{ selectedHotel.address || 'N/A' }}</span>
                    </div>
                  </div>
                  <div class="flex items-center gap-2">
                    <Globe class="w-4 h-4 text-slate-400" />
                    <div>
                      <span class="text-slate-400 block text-[10px]">{{ languageStore.t('location', 'Location') }}</span>
                      <span class="font-bold text-slate-800 dark:text-slate-200">{{ selectedHotel.city }}, {{ selectedHotel.country }}</span>
                    </div>
                  </div>
                  <div class="flex items-center gap-2">
                    <Clock class="w-4 h-4 text-slate-400" />
                    <div>
                      <span class="text-slate-400 block text-[10px]">{{ languageStore.t('timezone', 'Timezone') }}</span>
                      <span class="font-bold text-slate-800 dark:text-slate-200">{{ selectedHotel.timezone || 'Africa/Addis_Ababa' }}</span>
                    </div>
                  </div>
                  <div class="flex items-center gap-2">
                    <Coins class="w-4 h-4 text-slate-400" />
                    <div>
                      <span class="text-slate-400 block text-[10px]">{{ languageStore.t('currency', 'Currency') }}</span>
                      <span class="font-bold text-slate-800 dark:text-slate-200">{{ selectedHotel.currency || 'ETB' }}</span>
                    </div>
                  </div>
                </div>
              </div>

              <!-- Admins List -->
              <div>
                <h3 class="font-extrabold text-slate-900 dark:text-white text-xs uppercase tracking-wider mb-2">
                  {{ languageStore.t('assigned_hotel_admins', 'Assigned Hotel Administrators') }}
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
                      {{ languageStore.t('hotel_admin', 'Hotel Admin') }}
                    </span>
                  </div>
                </div>
                <div v-else class="p-4 rounded-xl bg-slate-100 dark:bg-slate-800 text-center text-slate-400">
                  {{ languageStore.t('no_admins_yet', 'No hotel administrators assigned yet.') }}
                </div>
              </div>
            </div>

            <!-- Footer -->
            <div class="px-6 py-4 border-t border-slate-200 dark:border-slate-800 flex items-center justify-between">
              <div class="flex items-center gap-2">
                <span class="text-[11px] text-slate-400">{{ languageStore.t('status', 'Status') }}:</span>
                <span class="font-bold uppercase text-xs">{{ selectedHotel.status }}</span>
              </div>
              <button
                @click="showDetailsModal = false"
                class="px-4 py-2 rounded-xl bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 font-bold text-xs cursor-pointer"
              >
                {{ languageStore.t('close', 'Close') }}
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
                  <h2 class="text-lg font-black text-slate-900 dark:text-white">{{ languageStore.t('edit_hotel', 'Edit Hotel') }}</h2>
                  <p class="text-xs text-slate-500">{{ languageStore.t('update_hotel_desc', 'Update hotel profile and contact information') }}</p>
                </div>
              </div>
              <button @click="showEditModal = false" class="p-2 text-slate-400 hover:text-slate-600 rounded-xl cursor-pointer">
                <X class="w-5 h-5" />
              </button>
            </div>

            <div class="flex-1 overflow-y-auto p-6 space-y-4 text-xs">
              <div class="grid grid-cols-1 sm:grid-cols-2 gap-3.5">
                <div class="sm:col-span-2">
                  <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">{{ languageStore.t('hotel_name', 'Hotel Name') }} *</label>
                  <input
                    v-model="editForm.name"
                    type="text"
                    class="w-full px-3 py-2 bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl text-slate-900 dark:text-white font-medium"
                  />
                </div>
                <div>
                  <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">{{ languageStore.t('email', 'Email') }}</label>
                  <input
                    v-model="editForm.email"
                    type="email"
                    class="w-full px-3 py-2 bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl text-slate-900 dark:text-white font-medium"
                  />
                </div>
                <div>
                  <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">{{ languageStore.t('phone', 'Phone') }}</label>
                  <input
                    v-model="editForm.phone"
                    type="text"
                    class="w-full px-3 py-2 bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl text-slate-900 dark:text-white font-medium"
                  />
                </div>
                <div class="sm:col-span-2">
                  <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">{{ languageStore.t('address', 'Address') }}</label>
                  <input
                    v-model="editForm.address"
                    type="text"
                    class="w-full px-3 py-2 bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl text-slate-900 dark:text-white font-medium"
                  />
                </div>
                <div>
                  <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">{{ languageStore.t('city', 'City') }}</label>
                  <input
                    v-model="editForm.city"
                    type="text"
                    class="w-full px-3 py-2 bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl text-slate-900 dark:text-white font-medium"
                  />
                </div>
                <div>
                  <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">{{ languageStore.t('country', 'Country') }}</label>
                  <input
                    v-model="editForm.country"
                    type="text"
                    class="w-full px-3 py-2 bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl text-slate-900 dark:text-white font-medium"
                  />
                </div>
                <div>
                  <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">{{ languageStore.t('timezone', 'Timezone') }}</label>
                  <input
                    v-model="editForm.timezone"
                    type="text"
                    class="w-full px-3 py-2 bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl text-slate-900 dark:text-white font-medium"
                  />
                </div>
                <div>
                  <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">{{ languageStore.t('currency', 'Currency') }}</label>
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
                {{ languageStore.t('cancel', 'Cancel') }}
              </button>
              <button
                :disabled="saving"
                @click="handleUpdateHotel"
                class="px-5 py-2 rounded-xl bg-blue-600 hover:bg-blue-500 text-white font-extrabold text-xs shadow-md transition disabled:opacity-50 flex items-center gap-1.5"
              >
                <RefreshCw v-if="saving" class="w-3.5 h-3.5 animate-spin" />
                <span>{{ saving ? languageStore.t('saving', 'Saving...') : languageStore.t('save_changes', 'Save Changes') }}</span>
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
                <h3 class="text-base font-black text-slate-900 dark:text-white">{{ languageStore.t('suspend_hotel_title', 'Suspend Hotel Property') }}</h3>
                <p class="text-slate-400">{{ languageStore.t('suspend_hotel_desc', 'Restricts tenant access while preserving all data') }}</p>
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
                {{ languageStore.t('cancel', 'Cancel') }}
              </button>
              <button
                :disabled="saving"
                @click="handleConfirmSuspend"
                class="px-5 py-2 rounded-xl bg-amber-600 hover:bg-amber-500 text-white font-extrabold shadow-md transition disabled:opacity-50"
              >
                {{ saving ? languageStore.t('suspending', 'Suspending...') : languageStore.t('confirm_suspension', 'Confirm Suspension') }}
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
                <h3 class="text-base font-black text-slate-900 dark:text-white">{{ languageStore.t('archive_hotel_title', 'Archive Hotel Property') }}</h3>
                <p class="text-slate-400">{{ languageStore.t('archive_hotel_desc', 'Safe storage without accidental permanent deletion') }}</p>
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
                {{ languageStore.t('cancel', 'Cancel') }}
              </button>
              <button
                :disabled="saving"
                @click="handleConfirmArchive"
                class="px-5 py-2 rounded-xl bg-purple-600 hover:bg-purple-500 text-white font-extrabold shadow-md transition disabled:opacity-50"
              >
                {{ saving ? languageStore.t('archiving', 'Archiving...') : languageStore.t('archive_property', 'Archive Property') }}
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
                <h3 class="text-base font-black text-rose-600 dark:text-rose-400">{{ languageStore.t('permanent_destruction', 'Permanent Destruction') }}</h3>
                <p class="text-slate-400">{{ languageStore.t('high_risk_action', 'High-risk action') }}</p>
              </div>
            </div>

            <p class="text-slate-600 dark:text-slate-300 leading-relaxed">
              Permanent deletion will completely remove <strong>{{ selectedHotel.name }}</strong>. 
              To confirm, please type the exact hotel name below:
            </p>

            <div>
              <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">
                Type <span class="font-mono text-rose-600 font-bold">{{ selectedHotel.name }}</span> {{ languageStore.t('type_to_confirm', 'to confirm:') }}
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
                {{ languageStore.t('cancel', 'Cancel') }}
              </button>
              <button
                :disabled="saving || deleteConfirmName.trim() !== selectedHotel.name.trim()"
                @click="handleConfirmPermanentDelete"
                class="px-5 py-2 rounded-xl bg-rose-600 hover:bg-rose-500 disabled:opacity-40 text-white font-extrabold shadow-md transition"
              >
                {{ saving ? languageStore.t('deleting', 'Deleting...') : languageStore.t('delete_permanently', 'Delete Permanently') }}
              </button>
            </div>
          </div>
        </div>
      </Teleport>
    </div>
  </DashboardLayout>
</template>

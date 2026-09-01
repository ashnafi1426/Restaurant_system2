<script setup lang="ts">
import { ref, onMounted, computed, watch } from 'vue'
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
  Send
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
const searchQuery = ref('')
const selectedHotelId = ref('all')
const successMessage = ref('')
const errorMessage = ref('')

// Pagination
const currentPage = ref(1)
const lastPage = ref(1)
const totalAdmins = ref(0)

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
      page: currentPage.value,
    })
    admins.value = res.data || []
    currentPage.value = res.current_page || 1
    lastPage.value = res.last_page || 1
    totalAdmins.value = res.total || 0
  } catch (err: any) {
    errorMessage.value = err?.response?.data?.message || 'Failed to load hotel administrators.'
  } finally {
    loading.value = false
  }
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
})

watch([searchQuery, selectedHotelId], () => {
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
                {{ totalAdmins }} Admins
              </span>
            </div>
            <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">
              Super Admin directory to provision, oversee, reset credentials, and govern administrators across all hotels.
            </p>
          </div>
        </div>

        <div class="flex items-center gap-2.5">
          <button
            @click="loadAdmins"
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
            <span>Add Hotel Admin</span>
          </button>
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

      <!-- Toolbar -->
      <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 p-4 rounded-2xl shadow-xs flex flex-col md:flex-row items-center justify-between gap-3">
        <div class="relative w-full md:w-80">
          <Search class="w-4 h-4 text-slate-400 absolute left-3.5 top-3" />
          <input
            v-model="searchQuery"
            type="text"
            placeholder="Search by admin name or email..."
            class="w-full pl-9 pr-3 py-2 bg-slate-100 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl text-xs text-slate-900 dark:text-white placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-indigo-500 font-medium"
          />
        </div>

        <div class="flex items-center gap-2 w-full md:w-auto">
          <span class="text-xs font-bold text-slate-400">Hotel:</span>
          <div class="w-56">
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
        </div>
      </div>

      <!-- Table -->
      <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-3xl shadow-xs overflow-hidden">
        <div class="overflow-x-auto">
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
                <td class="py-4 px-5 text-right">
                  <div class="flex items-center justify-end gap-2">
                    <button
                      @click="handleResendPasswordByEmail(adm)"
                      class="px-3 py-1.5 rounded-xl bg-indigo-50 dark:bg-indigo-950/40 hover:bg-indigo-100 dark:hover:bg-indigo-900/50 text-indigo-600 dark:text-indigo-400 border border-indigo-200 dark:border-indigo-800/60 text-xs font-bold flex items-center gap-1.5 transition cursor-pointer"
                      title="Generate new password and send directly via email"
                    >
                      <Send class="w-3.5 h-3.5" />
                      <span>Send by Email</span>
                    </button>

                    <button
                      @click="handleResetPassword(adm)"
                      class="px-3 py-1.5 rounded-xl bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-300 border border-slate-200 dark:border-slate-700 text-xs font-bold flex items-center gap-1.5 transition cursor-pointer"
                      title="Generate new temporary password"
                    >
                      <KeyRound class="w-3.5 h-3.5" />
                      <span>Reset</span>
                    </button>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <!-- Pagination -->
        <div v-if="lastPage > 1" class="p-4 border-t border-slate-200 dark:border-slate-800 flex items-center justify-between">
          <span class="text-xs text-slate-500">
            Page {{ currentPage }} of {{ lastPage }} ({{ totalAdmins }} total)
          </span>
          <div class="flex items-center gap-2">
            <button
              :disabled="currentPage <= 1"
              @click="currentPage--; loadAdmins()"
              class="px-3 py-1.5 rounded-lg bg-slate-100 dark:bg-slate-800 text-xs font-bold disabled:opacity-40"
            >
              Previous
            </button>
            <button
              :disabled="currentPage >= lastPage"
              @click="currentPage++; loadAdmins()"
              class="px-3 py-1.5 rounded-lg bg-slate-100 dark:bg-slate-800 text-xs font-bold disabled:opacity-40"
            >
              Next
            </button>
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

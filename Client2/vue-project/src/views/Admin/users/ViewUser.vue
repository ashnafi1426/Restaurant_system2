<script setup lang="ts">
import { ref, computed, onMounted, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import DashboardLayout from '@/Layouts/DashboardLayout.vue'
import { useUserStore } from '@/stores/user'
import { useHotelStore } from '@/stores/hotelStore'
import { useLanguageStore } from '@/stores/language'
import userService from '@/services/userService'
import type { User } from '@/types/user'
import {
  ArrowLeft,
  Mail,
  Phone,
  Shield,
  Calendar,
  Clock,
  UserCheck,
  UserX,
  Edit,
  Trash2,
  Building2,
  Copy,
  Check,
  User as UserIcon,
  Sparkles,
  RefreshCw,
  ExternalLink,
  ShieldCheck,
  KeyRound,
  CheckCircle2,
  AlertCircle,
} from 'lucide-vue-next'

const route = useRoute()
const router = useRouter()
const userStore = useUserStore()
const hotelStore = useHotelStore()
const languageStore = useLanguageStore()

const userId = String(route.params.id)
const loading = ref(true)
const isTogglingStatus = ref(false)
const isDeleting = ref(false)
const copiedField = ref<string | null>(null)
const user = ref<User | null>(null)

const extractUserData = (raw: any): User | null => {
  if (!raw) return null
  return raw.first_name ? raw : raw.data?.first_name ? raw.data : raw.data?.data || raw.data || raw
}

const loadUser = async () => {
  loading.value = true
  try {
    const res = await userStore.fetchUser(userId)
    const extracted = extractUserData(res) || extractUserData(userStore.user)
    if (extracted) {
      user.value = extracted
    }
  } catch (error) {
    console.error('[ViewUser] Failed to load user:', error)
  } finally {
    loading.value = false
  }
}

onMounted(loadUser)
watch(() => hotelStore.hotelId, loadUser)

const fullName = computed(() => {
  if (!user.value) return 'User Profile'
  return `${user.value.first_name || ''} ${user.value.last_name || ''}`.trim() || 'Staff Member'
})

const userInitial = computed(() => {
  if (!user.value?.first_name) return 'U'
  return user.value.first_name.charAt(0).toUpperCase()
})

const getRoleBadgeClass = (role?: string): string => {
  const r = (role || '').toLowerCase()
  if (r.includes('admin')) return 'bg-purple-500/10 text-purple-600 dark:text-purple-400 border-purple-500/30'
  if (r.includes('manager')) return 'bg-blue-500/10 text-blue-600 dark:text-blue-400 border-blue-500/30'
  if (r.includes('reception')) return 'bg-sky-500/10 text-sky-600 dark:text-sky-400 border-sky-500/30'
  if (r.includes('cashier')) return 'bg-slate-600/10 text-slate-700 dark:text-slate-300 border-slate-500/30'
  if (r.includes('waiter') || r.includes('staff')) return 'bg-amber-500/10 text-amber-600 dark:text-amber-400 border-amber-500/30'
  if (r.includes('chef') || r.includes('kitchen')) return 'bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 border-emerald-500/30'
  return 'bg-slate-500/10 text-slate-600 dark:text-slate-400 border-slate-500/30'
}

const getRoleDescription = (role?: string): string => {
  const r = (role || '').toLowerCase()
  if (r.includes('admin')) return 'Full administrative authority across system settings, staff management, and hotel properties.'
  if (r.includes('manager')) return 'Supervises daily hotel and restaurant operations, floor shifts, waiter assignments, and performance.'
  if (r.includes('reception')) return 'Handles front desk check-ins, guest reservations, room assignments, and guest hospitality.'
  if (r.includes('cashier')) return 'Processes customer dining payments, billing settlements, table clearing, and financial reports.'
  if (r.includes('chef') || r.includes('kitchen')) return 'Manages real-time kitchen order preparation, menu cooking statuses, and food fulfillment.'
  if (r.includes('waiter')) return 'Takes dining orders, serves food to tables and rooms, and coordinates direct guest service.'
  return 'Standard staff member with departmental operational privileges.'
}

const copyToClipboard = async (text: string, fieldName: string) => {
  if (!text) return
  try {
    await navigator.clipboard.writeText(text)
    copiedField.value = fieldName
    setTimeout(() => {
      copiedField.value = null
    }, 2000)
  } catch (err) {
    console.error('Failed to copy:', err)
  }
}

const toggleStatus = async () => {
  if (!user.value) return
  isTogglingStatus.value = true
  try {
    await userService.toggleStatus(user.value.id)
    user.value.is_active = !user.value.is_active
  } catch (error) {
    console.error('[ViewUser] Error toggling status:', error)
    alert('Failed to update user status.')
  } finally {
    isTogglingStatus.value = false
  }
}

const navigateEdit = () => {
  if (!user.value) return
  router.push(`/users/${user.value.id}/edit`)
}

const deleteUser = async () => {
  if (!user.value) return
  const confirmMsg = languageStore.t(
    'delete_user_confirm',
    `Are you sure you want to delete ${fullName.value}? This action cannot be undone.`
  )
  if (confirm(confirmMsg)) {
    isDeleting.value = true
    try {
      await userStore.deleteUser(user.value.id)
      router.push('/users')
    } catch (error) {
      console.error('[ViewUser] Error deleting user:', error)
      alert('Failed to delete user.')
    } finally {
      isDeleting.value = false
    }
  }
}

const formatDate = (dateStr?: string | null): string => {
  if (!dateStr) return 'Never'
  try {
    return new Date(dateStr).toLocaleString(undefined, {
      year: 'numeric',
      month: 'short',
      day: 'numeric',
      hour: '2-digit',
      minute: '2-digit',
    })
  } catch {
    return dateStr
  }
}
</script>

<template>
  <DashboardLayout>
    <div class="space-y-6 bg-slate-50 dark:bg-slate-950 min-h-screen p-4 sm:p-6 max-w-full font-sans transition-colors">
      
      <!-- Top Action Bar -->
      <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 p-4 sm:p-5 rounded-2xl shadow-xs">
        <div class="flex items-center gap-3">
          <button
            @click="router.push('/users')"
            class="inline-flex items-center justify-center w-9 h-9 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-700 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-700 transition cursor-pointer"
            title="Back to User List"
          >
            <ArrowLeft class="w-4 h-4" />
          </button>
          <div>
            <div class="flex items-center gap-2">
              <h1 class="text-xl sm:text-2xl font-black text-slate-900 dark:text-white tracking-tight">
                {{ fullName }}
              </h1>
              <span
                v-if="user"
                class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-xs font-bold border capitalize"
                :class="getRoleBadgeClass(user.role)"
              >
                <Shield class="w-3 h-3" />
                {{ user.role }}
              </span>
            </div>
            <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">
              {{ languageStore.t('staff_profile_details', 'Staff account overview and operational access credentials.') }}
            </p>
          </div>
        </div>

        <!-- Header Actions -->
        <div v-if="user" class="flex items-center gap-2 flex-wrap">
          <button
            @click="toggleStatus"
            :disabled="isTogglingStatus"
            class="inline-flex items-center gap-1.5 px-3 py-2 rounded-xl text-xs font-bold border transition cursor-pointer disabled:opacity-50"
            :class="user.is_active
              ? 'border-amber-200 dark:border-amber-900/50 bg-amber-50 dark:bg-amber-950/30 text-amber-700 dark:text-amber-400 hover:bg-amber-100'
              : 'border-emerald-200 dark:border-emerald-900/50 bg-emerald-50 dark:bg-emerald-950/30 text-emerald-700 dark:text-emerald-400 hover:bg-emerald-100'"
          >
            <RefreshCw v-if="isTogglingStatus" class="w-3.5 h-3.5 animate-spin" />
            <UserX v-else-if="user.is_active" class="w-3.5 h-3.5" />
            <UserCheck v-else class="w-3.5 h-3.5" />
            <span>{{ user.is_active ? 'Deactivate' : 'Activate' }}</span>
          </button>

          <button
            @click="navigateEdit"
            class="inline-flex items-center gap-1.5 px-4 py-2 rounded-xl text-xs font-bold bg-blue-600 hover:bg-blue-700 text-white shadow-xs transition cursor-pointer"
          >
            <Edit class="w-3.5 h-3.5" />
            <span>{{ languageStore.t('Edit', 'Edit User') }}</span>
          </button>

          <button
            @click="deleteUser"
            :disabled="isDeleting"
            class="inline-flex items-center gap-1.5 px-3 py-2 rounded-xl text-xs font-bold bg-rose-50 dark:bg-rose-950/40 hover:bg-rose-100 text-rose-600 dark:text-rose-400 border border-rose-200 dark:border-rose-900/40 transition cursor-pointer disabled:opacity-50"
          >
            <Trash2 class="w-3.5 h-3.5" />
            <span>{{ languageStore.t('Delete', 'Delete') }}</span>
          </button>
        </div>
      </div>

      <!-- Loading State -->
      <div v-if="loading" class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-800 p-12 text-center">
        <div class="inline-flex items-center justify-center w-12 h-12 rounded-2xl bg-blue-500/10 text-blue-600 dark:text-blue-400 mb-3 animate-spin">
          <RefreshCw class="w-6 h-6" />
        </div>
        <p class="text-sm font-bold text-slate-700 dark:text-slate-300">Loading user profile...</p>
        <p class="text-xs text-slate-400 mt-1">Please wait while staff details are retrieved.</p>
      </div>

      <!-- User Not Found State -->
      <div v-else-if="!user" class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-800 p-12 text-center">
        <div class="inline-flex items-center justify-center w-12 h-12 rounded-2xl bg-rose-500/10 text-rose-600 dark:text-rose-400 mb-3">
          <AlertCircle class="w-6 h-6" />
        </div>
        <h2 class="text-lg font-bold text-slate-900 dark:text-white">User Not Found</h2>
        <p class="text-xs text-slate-500 dark:text-slate-400 mt-1 mb-4">
          The requested staff account could not be found or has been removed.
        </p>
        <button
          @click="router.push('/users')"
          class="inline-flex items-center gap-2 px-4 py-2 rounded-xl bg-blue-600 text-white text-xs font-bold shadow-xs hover:bg-blue-700 transition"
        >
          <ArrowLeft class="w-3.5 h-3.5" />
          <span>Return to User List</span>
        </button>
      </div>

      <!-- User Content -->
      <div v-else class="space-y-6">
        
        <!-- Hero Identity Banner Card -->
        <div class="relative overflow-hidden bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-800 shadow-xs p-6">
          <div class="absolute top-0 right-0 w-96 h-96 bg-gradient-to-br from-blue-500/10 via-purple-500/5 to-transparent rounded-full blur-3xl pointer-events-none"></div>

          <div class="flex flex-col sm:flex-row items-start sm:items-center gap-5 relative z-10">
            <!-- Avatar -->
            <div class="w-20 h-20 sm:w-24 sm:h-24 rounded-2xl bg-gradient-to-br from-blue-600 via-indigo-600 to-purple-600 flex items-center justify-center text-white text-3xl sm:text-4xl font-black shadow-md flex-shrink-0">
              {{ userInitial }}
            </div>

            <!-- Profile Info -->
            <div class="space-y-2 flex-1 min-w-0">
              <div class="flex flex-wrap items-center gap-2.5">
                <h2 class="text-2xl font-black text-slate-900 dark:text-white tracking-tight">
                  {{ fullName }}
                </h2>
                <!-- Status Pill -->
                <span
                  class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-black uppercase tracking-wider"
                  :class="user.is_active
                    ? 'bg-emerald-100 dark:bg-emerald-950/60 text-emerald-700 dark:text-emerald-400 border border-emerald-300 dark:border-emerald-800'
                    : 'bg-rose-100 dark:bg-rose-950/60 text-rose-700 dark:text-rose-400 border border-rose-300 dark:border-rose-800'"
                >
                  <span class="w-2 h-2 rounded-full" :class="user.is_active ? 'bg-emerald-500' : 'bg-rose-500'"></span>
                  {{ user.is_active ? 'Active' : 'Inactive' }}
                </span>
                <!-- Hotel Tag -->
                <span v-if="hotelStore.hotelName" class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-bold bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 border border-slate-200 dark:border-slate-700">
                  <Building2 class="w-3 h-3 text-blue-500" />
                  {{ hotelStore.hotelName }}
                </span>
              </div>

              <!-- Quick Contact Row -->
              <div class="flex flex-wrap items-center gap-4 text-xs text-slate-500 dark:text-slate-400 pt-1">
                <div class="flex items-center gap-1.5">
                  <Mail class="w-3.5 h-3.5 text-blue-500 flex-shrink-0" />
                  <span class="font-medium text-slate-700 dark:text-slate-300">{{ user.email }}</span>
                  <button
                    @click="copyToClipboard(user.email, 'email')"
                    class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 ml-1 transition"
                    title="Copy Email"
                  >
                    <Check v-if="copiedField === 'email'" class="w-3 h-3 text-emerald-500" />
                    <Copy v-else class="w-3 h-3" />
                  </button>
                </div>

                <div class="flex items-center gap-1.5">
                  <Phone class="w-3.5 h-3.5 text-emerald-500 flex-shrink-0" />
                  <span class="font-medium text-slate-700 dark:text-slate-300">{{ user.phone || 'No phone recorded' }}</span>
                  <button
                    v-if="user.phone"
                    @click="copyToClipboard(user.phone, 'phone')"
                    class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 ml-1 transition"
                    title="Copy Phone"
                  >
                    <Check v-if="copiedField === 'phone'" class="w-3 h-3 text-emerald-500" />
                    <Copy v-else class="w-3 h-3" />
                  </button>
                </div>

                <div class="flex items-center gap-1.5">
                  <KeyRound class="w-3.5 h-3.5 text-purple-500 flex-shrink-0" />
                  <span class="font-mono text-[11px] text-slate-400 truncate max-w-[140px]">{{ user.id }}</span>
                  <button
                    @click="copyToClipboard(user.id, 'id')"
                    class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 ml-1 transition"
                    title="Copy User UUID"
                  >
                    <Check v-if="copiedField === 'id'" class="w-3 h-3 text-emerald-500" />
                    <Copy v-else class="w-3 h-3" />
                  </button>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- 3-Column Detailed Information Grid -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

          <!-- Card 1: Account Information -->
          <div class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-800 p-5 shadow-xs space-y-4">
            <div class="flex items-center gap-2 pb-3 border-b border-slate-100 dark:border-slate-800">
              <div class="w-8 h-8 rounded-xl bg-blue-500/10 flex items-center justify-center text-blue-600 dark:text-blue-400">
                <UserIcon class="w-4 h-4" />
              </div>
              <h3 class="font-bold text-slate-900 dark:text-white text-sm">Account Information</h3>
            </div>

            <div class="space-y-3.5 text-xs">
              <div>
                <span class="text-slate-400 dark:text-slate-500 block text-[10px] uppercase font-bold tracking-wider mb-0.5">First Name</span>
                <span class="font-bold text-slate-800 dark:text-slate-200 text-sm">{{ user.first_name || 'N/A' }}</span>
              </div>

              <div>
                <span class="text-slate-400 dark:text-slate-500 block text-[10px] uppercase font-bold tracking-wider mb-0.5">Last Name</span>
                <span class="font-bold text-slate-800 dark:text-slate-200 text-sm">{{ user.last_name || 'N/A' }}</span>
              </div>

              <div>
                <span class="text-slate-400 dark:text-slate-500 block text-[10px] uppercase font-bold tracking-wider mb-0.5">Work Email Address</span>
                <span class="font-bold text-slate-800 dark:text-slate-200 text-sm">{{ user.email }}</span>
              </div>

              <div>
                <span class="text-slate-400 dark:text-slate-500 block text-[10px] uppercase font-bold tracking-wider mb-0.5">Phone Number</span>
                <span class="font-bold text-slate-800 dark:text-slate-200 text-sm">{{ user.phone || 'None provided' }}</span>
              </div>

              <div>
                <span class="text-slate-400 dark:text-slate-500 block text-[10px] uppercase font-bold tracking-wider mb-0.5">Account Status</span>
                <span
                  class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md text-[11px] font-bold"
                  :class="user.is_active ? 'bg-emerald-100 text-emerald-800 dark:bg-emerald-950 dark:text-emerald-300' : 'bg-rose-100 text-rose-800 dark:bg-rose-950 dark:text-rose-300'"
                >
                  <CheckCircle2 v-if="user.is_active" class="w-3 h-3 text-emerald-600" />
                  <AlertCircle v-else class="w-3 h-3 text-rose-600" />
                  <span>{{ user.is_active ? 'Active & Authorized' : 'Suspended / Inactive' }}</span>
                </span>
              </div>
            </div>
          </div>

          <!-- Card 2: Role & System Privileges -->
          <div class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-800 p-5 shadow-xs space-y-4">
            <div class="flex items-center gap-2 pb-3 border-b border-slate-100 dark:border-slate-800">
              <div class="w-8 h-8 rounded-xl bg-purple-500/10 flex items-center justify-center text-purple-600 dark:text-purple-400">
                <ShieldCheck class="w-4 h-4" />
              </div>
              <h3 class="font-bold text-slate-900 dark:text-white text-sm">Role & Department</h3>
            </div>

            <div class="space-y-3.5 text-xs">
              <div>
                <span class="text-slate-400 dark:text-slate-500 block text-[10px] uppercase font-bold tracking-wider mb-0.5">Assigned System Role</span>
                <div class="mt-1">
                  <span
                    class="inline-flex items-center gap-1.5 px-3 py-1 rounded-xl text-xs font-black uppercase tracking-wider border shadow-2xs"
                    :class="getRoleBadgeClass(user.role)"
                  >
                    <Shield class="w-3.5 h-3.5" />
                    {{ user.role }}
                  </span>
                </div>
              </div>

              <div>
                <span class="text-slate-400 dark:text-slate-500 block text-[10px] uppercase font-bold tracking-wider mb-0.5">Role Description</span>
                <p class="text-slate-600 dark:text-slate-400 text-xs leading-relaxed mt-0.5 bg-slate-50 dark:bg-slate-800/50 p-2.5 rounded-xl border border-slate-100 dark:border-slate-800">
                  {{ getRoleDescription(user.role) }}
                </p>
              </div>

              <div>
                <span class="text-slate-400 dark:text-slate-500 block text-[10px] uppercase font-bold tracking-wider mb-0.5">Property Membership</span>
                <span class="font-bold text-slate-800 dark:text-slate-200 text-xs flex items-center gap-1.5 mt-0.5">
                  <Building2 class="w-3.5 h-3.5 text-blue-500" />
                  {{ hotelStore.hotelName || 'Default Property' }}
                </span>
              </div>

              <div class="pt-2">
                <button
                  @click="router.push('/roles')"
                  class="w-full inline-flex items-center justify-center gap-1.5 py-2 px-3 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-700 dark:text-slate-200 text-xs font-bold hover:bg-slate-100 dark:hover:bg-slate-700 transition cursor-pointer"
                >
                  <Shield class="w-3.5 h-3.5 text-purple-500" />
                  <span>Manage Role Permissions</span>
                </button>
              </div>
            </div>
          </div>

          <!-- Card 3: Activity & Security -->
          <div class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-800 p-5 shadow-xs space-y-4">
            <div class="flex items-center gap-2 pb-3 border-b border-slate-100 dark:border-slate-800">
              <div class="w-8 h-8 rounded-xl bg-emerald-500/10 flex items-center justify-center text-emerald-600 dark:text-emerald-400">
                <Clock class="w-4 h-4" />
              </div>
              <h3 class="font-bold text-slate-900 dark:text-white text-sm">Security & Activity</h3>
            </div>

            <div class="space-y-3.5 text-xs">
              <div>
                <span class="text-slate-400 dark:text-slate-500 block text-[10px] uppercase font-bold tracking-wider mb-0.5">Last Login Session</span>
                <span class="font-bold text-slate-800 dark:text-slate-200 text-xs flex items-center gap-1.5 mt-0.5">
                  <Clock class="w-3.5 h-3.5 text-amber-500" />
                  {{ formatDate(user.last_login) }}
                </span>
              </div>

              <div>
                <span class="text-slate-400 dark:text-slate-500 block text-[10px] uppercase font-bold tracking-wider mb-0.5">Account Created</span>
                <span class="font-bold text-slate-800 dark:text-slate-200 text-xs flex items-center gap-1.5 mt-0.5">
                  <Calendar class="w-3.5 h-3.5 text-blue-500" />
                  {{ formatDate(user.created_at) }}
                </span>
              </div>

              <div>
                <span class="text-slate-400 dark:text-slate-500 block text-[10px] uppercase font-bold tracking-wider mb-0.5">Last Profile Modification</span>
                <span class="font-bold text-slate-800 dark:text-slate-200 text-xs flex items-center gap-1.5 mt-0.5">
                  <RefreshCw class="w-3.5 h-3.5 text-teal-500" />
                  {{ formatDate(user.updated_at) }}
                </span>
              </div>

              <div>
                <span class="text-slate-400 dark:text-slate-500 block text-[10px] uppercase font-bold tracking-wider mb-0.5">Password Authentication</span>
                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md text-[11px] font-bold bg-slate-100 text-slate-700 dark:bg-slate-800 dark:text-slate-300">
                  <KeyRound class="w-3 h-3 text-slate-500" />
                  <span>Bcrypt Encrypted</span>
                </span>
              </div>
            </div>
          </div>
        </div>

      </div>
    </div>
  </DashboardLayout>
</template>

<script setup lang="ts">
import { ref, onMounted, computed } from 'vue'
import { useRouter } from 'vue-router'
import DashboardLayout from '@/Layouts/DashboardLayout.vue'
import ConfigureRoleModal from '@/components/rbac/ConfigureRoleModal.vue'
import ViewRoleUsersModal from '@/components/rbac/ViewRoleUsersModal.vue'
import ConfirmDeleteModal from '@/components/rbac/ConfirmDeleteModal.vue'
import { rbacService } from '@/services/rbacService'
import { useAuthStore } from '@/stores/auth'
import type { Role, Permission, RbacUserSummary } from '@/types/rbacTypes'
import {
  ShieldCheck,
  Plus,
  Edit2,
  Trash2,
  CheckCircle2,
  Users,
  Key,
  RefreshCw,
  AlertCircle,
  Search,
  SlidersHorizontal,
  Copy,
  Layers,
  Sparkles,
  Check,
  X,
  ExternalLink,
  Shield,
  Activity,
  ArrowUpDown,
  Table,
  Crown,
  Briefcase,
  Contact,
  ChefHat,
  Utensils,
  Wallet,
  User
} from 'lucide-vue-next'

const router = useRouter()
const authStore = useAuthStore()

const roles = ref<Role[]>([])
const permissions = ref<Permission[]>([])
const userSummaries = ref<RbacUserSummary[]>([])
const loading = ref(true)
const saving = ref(false)
const loadingPermissions = ref(false)
const errorMessage = ref('')
const successMessage = ref('')

// View mode: 'cards' | 'matrix'
const viewMode = ref<'cards' | 'matrix'>('cards')

// Search & Filtering
const searchQuery = ref('')
const filterCategory = ref<'all' | 'system' | 'custom' | 'active' | 'inactive'>('all')
const sortBy = ref<'name' | 'users' | 'permissions'>('name')

// Configure Modal state
const showModal = ref(false)
const editingRole = ref<Role | null>(null)
const initialPermissionIds = ref<number[]>([])

// View Users Modal state
const showUsersModal = ref(false)
const selectedRoleForUsers = ref<Role | null>(null)

// Delete Confirm Modal state
const showDeleteModal = ref(false)
const roleToDelete = ref<Role | null>(null)
const deleting = ref(false)

const getRoleIcon = (slugOrName: string) => {
  const s = String(slugOrName || '').toLowerCase()
  if (s.includes('admin')) return Crown
  if (s.includes('manager')) return Briefcase
  if (s.includes('reception')) return Contact
  if (s.includes('chef') || s.includes('kitchen')) return ChefHat
  if (s.includes('waiter')) return Utensils
  if (s.includes('cashier')) return Wallet
  return User
}

const fetchRolesAndPermissions = async () => {
  loading.value = true
  errorMessage.value = ''
  try {
    const [rolesData, permsData, usersData] = await Promise.all([
      rbacService.getRoles(),
      rbacService.getPermissions(),
      rbacService.getUserRoleSummaries().catch(() => [])
    ])
    roles.value = rolesData || []
    permissions.value = permsData.data || []
    userSummaries.value = usersData || []
  } catch (err: any) {
    console.error('[RoleManagement] Fetch error:', err)
    errorMessage.value = err?.response?.data?.message || 'Failed to load system roles and permissions.'
  } finally {
    loading.value = false
  }
}

onMounted(() => {
  fetchRolesAndPermissions()
})

// Metrics summary calculations
const totalRolesCount = computed(() => roles.value.length)
const activeRolesCount = computed(() => roles.value.filter(r => r.is_active ?? true).length)
const systemRolesCount = computed(() => roles.value.filter(r => r.is_system).length)
const customRolesCount = computed(() => roles.value.filter(r => !r.is_system).length)
const totalPermissionsCount = computed(() => permissions.value.length)

// Filtered & Sorted Roles
const filteredRoles = computed(() => {
  let list = roles.value

  // Category filter
  if (filterCategory.value === 'system') {
    list = list.filter(r => r.is_system)
  } else if (filterCategory.value === 'custom') {
    list = list.filter(r => !r.is_system)
  } else if (filterCategory.value === 'active') {
    list = list.filter(r => (r.is_active ?? true))
  } else if (filterCategory.value === 'inactive') {
    list = list.filter(r => !(r.is_active ?? true))
  }

  // Search filter
  if (searchQuery.value.trim()) {
    const q = searchQuery.value.toLowerCase().trim()
    list = list.filter(r =>
      r.name.toLowerCase().includes(q) ||
      r.slug.toLowerCase().includes(q) ||
      (r.description && r.description.toLowerCase().includes(q))
    )
  }

  // Sorting
  return [...list].sort((a, b) => {
    if (sortBy.value === 'name') {
      return a.name.localeCompare(b.name)
    } else if (sortBy.value === 'users') {
      return (b.users_count ?? 0) - (a.users_count ?? 0)
    } else if (sortBy.value === 'permissions') {
      const aPerms = a.permissions_count ?? a.permissions?.length ?? 0
      const bPerms = b.permissions_count ?? b.permissions?.length ?? 0
      return bPerms - aPerms
    }
    return 0
  })
})

// Extract top permission modules for role card tags
const getRoleModuleTags = (role: Role) => {
  if (!role.permissions || role.permissions.length === 0) return []
  const modules = new Set<string>()
  role.permissions.forEach((p: any) => {
    const mod = p.module || (typeof p === 'string' ? p.split('.')[0] : '')
    if (mod) modules.add(mod.toLowerCase().replace(/_/g, ' '))
  })
  return Array.from(modules).slice(0, 3)
}

const openCreateModal = () => {
  editingRole.value = null
  initialPermissionIds.value = []
  loadingPermissions.value = false
  showModal.value = true
}

const openEditModal = async (role: Role) => {
  editingRole.value = role
  initialPermissionIds.value = []
  loadingPermissions.value = true
  showModal.value = true

  try {
    const permData = await rbacService.getRolePermissions(role.id)
    if (permData && Array.isArray(permData.permission_ids)) {
      initialPermissionIds.value = [...permData.permission_ids]
    } else if (permData && Array.isArray(permData.data)) {
      initialPermissionIds.value = permData.data.map((p: any) => p.id)
    }
  } catch (err) {
    console.error('Failed to load role permissions:', err)
  } finally {
    loadingPermissions.value = false
  }
}

const openCloneModal = async (role: Role) => {
  // Pre-fill a new role based on existing role
  editingRole.value = null
  initialPermissionIds.value = []
  loadingPermissions.value = true
  showModal.value = true

  try {
    const permData = await rbacService.getRolePermissions(role.id)
    let permIds: number[] = []
    if (permData && Array.isArray(permData.permission_ids)) {
      permIds = [...permData.permission_ids]
    } else if (permData && Array.isArray(permData.data)) {
      permIds = permData.data.map((p: any) => p.id)
    }
    initialPermissionIds.value = permIds

    // Create placeholder role for modal form initialization
    editingRole.value = null
  } catch (err) {
    console.error('Failed to clone role permissions:', err)
  } finally {
    loadingPermissions.value = false
  }
}

const openUsersModal = (role: Role) => {
  selectedRoleForUsers.value = role
  showUsersModal.value = true
}

const toggleRoleActive = async (role: Role) => {
  if (role.slug === 'admin') {
    errorMessage.value = 'System Administrator role status cannot be altered.'
    setTimeout(() => { errorMessage.value = '' }, 3500)
    return
  }

  const targetState = !(role.is_active ?? true)
  try {
    await rbacService.updateRole(role.id, { is_active: targetState })
    role.is_active = targetState
    successMessage.value = `Role "${role.name}" is now ${targetState ? 'active' : 'inactive'}.`
    setTimeout(() => { successMessage.value = '' }, 3500)
  } catch (err: any) {
    errorMessage.value = err?.response?.data?.message || 'Failed to update role active status.'
    setTimeout(() => { errorMessage.value = '' }, 3500)
  }
}

const handleSaveRole = async (payload: { name: string; description: string; is_active: boolean; permissions: number[] }) => {
  saving.value = true
  errorMessage.value = ''
  try {
    if (editingRole.value) {
      await rbacService.updateRole(editingRole.value.id, {
        name: payload.name,
        description: payload.description,
        is_active: payload.is_active,
      })

      await rbacService.syncRolePermissions(editingRole.value.id, payload.permissions)
      successMessage.value = `Role "${payload.name}" updated successfully!`
    } else {
      await rbacService.createRole({
        name: payload.name,
        description: payload.description,
        is_active: payload.is_active,
        permissions: payload.permissions,
      })
      successMessage.value = `Role "${payload.name}" created successfully!`
    }

    try {
      await authStore.fetchCurrentUser()
    } catch (e) {
      console.warn('[ROLES] Auth refresh error:', e)
    }

    showModal.value = false
    await fetchRolesAndPermissions()
    setTimeout(() => { successMessage.value = '' }, 4000)
  } catch (err: any) {
    errorMessage.value = err?.response?.data?.message || 'Failed to save role configuration.'
  } finally {
    saving.value = false
  }
}

const triggerDeleteConfirmation = (role: Role) => {
  if (role.is_system) {
    errorMessage.value = 'Core system roles cannot be deleted.'
    setTimeout(() => { errorMessage.value = '' }, 3500)
    return
  }
  roleToDelete.value = role
  showDeleteModal.value = true
}

const confirmDeleteRole = async () => {
  if (!roleToDelete.value) return
  deleting.value = true
  errorMessage.value = ''
  try {
    await rbacService.deleteRole(roleToDelete.value.id)
    successMessage.value = `Role "${roleToDelete.value.name}" deleted successfully.`
    showDeleteModal.value = false
    roleToDelete.value = null
    await fetchRolesAndPermissions()
    setTimeout(() => { successMessage.value = '' }, 3500)
  } catch (err: any) {
    errorMessage.value = err?.response?.data?.message || 'Failed to delete role.'
  } finally {
    deleting.value = false
  }
}

const navigateToUserAssignments = () => {
  showUsersModal.value = false
  router.push('/admin/user-roles')
}
</script>

<template>
  <DashboardLayout>
    <div class="min-h-screen bg-slate-50 dark:bg-slate-950 p-4 sm:p-6 space-y-6">
      
      <!-- HEADER BANNER -->
      <div class="flex flex-col xl:flex-row xl:items-center justify-between gap-4 p-6 bg-white dark:bg-slate-900 rounded-3xl border border-slate-200 dark:border-slate-800 shadow-xs">
        <div class="flex items-start sm:items-center gap-3.5 flex-wrap sm:flex-nowrap">
          <div class="p-3 rounded-2xl bg-amber-500/10 text-amber-600 dark:text-amber-400 border border-amber-500/20 shadow-xs flex-shrink-0">
            <ShieldCheck class="w-7 h-7" />
          </div>
          <div>
            <div class="flex flex-wrap items-center gap-2.5">
              <h1 class="text-xl sm:text-2xl font-black tracking-tight text-slate-900 dark:text-white">
                Role & Access Management
              </h1>
              <span class="px-2.5 py-0.5 rounded-full bg-amber-500/10 text-amber-600 dark:text-amber-400 font-extrabold text-[10px] uppercase border border-amber-500/20 whitespace-nowrap flex-shrink-0">
                Staff Positions & Permissions
              </span>
            </div>
            <p class="text-xs sm:text-sm text-slate-500 dark:text-slate-400 font-medium mt-1">
              Create staff positions, manage access privileges, and control what your hotel team can view and edit.
            </p>
          </div>
        </div>

        <div class="flex items-center gap-2.5 flex-wrap sm:flex-nowrap flex-shrink-0 self-start xl:self-auto">
          <!-- View Switcher -->
          <div class="flex items-center p-1 rounded-2xl bg-slate-100 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 flex-shrink-0">
            <button
              @click="viewMode = 'cards'"
              :class="[
                'px-3.5 py-2 rounded-xl text-xs font-black transition cursor-pointer flex items-center gap-1.5 whitespace-nowrap',
                viewMode === 'cards'
                  ? 'bg-white dark:bg-slate-900 text-amber-600 dark:text-amber-400 shadow-xs'
                  : 'text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white'
              ]"
            >
              <Layers class="w-4 h-4" />
              <span>Cards</span>
            </button>
            <button
              @click="viewMode = 'matrix'"
              :class="[
                'px-3.5 py-2 rounded-xl text-xs font-black transition cursor-pointer flex items-center gap-1.5 whitespace-nowrap',
                viewMode === 'matrix'
                  ? 'bg-white dark:bg-slate-900 text-amber-600 dark:text-amber-400 shadow-xs'
                  : 'text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white'
              ]"
            >
              <Table class="w-4 h-4" />
              <span>Permission Matrix</span>
            </button>
          </div>

          <button
            @click="fetchRolesAndPermissions"
            class="p-2.5 rounded-2xl border border-slate-200 dark:border-slate-800 hover:bg-slate-100 dark:hover:bg-slate-800 text-slate-600 dark:text-slate-300 transition cursor-pointer flex-shrink-0"
            title="Refresh System Roles"
          >
            <RefreshCw class="w-4 h-4" :class="{ 'animate-spin': loading }" />
          </button>

          <button
            @click="openCreateModal"
            class="inline-flex items-center gap-2 px-5 py-2.5 rounded-2xl bg-amber-500 hover:bg-amber-600 text-slate-950 font-black text-xs shadow-md shadow-amber-500/20 transition cursor-pointer whitespace-nowrap flex-shrink-0"
          >
            <Plus class="w-4 h-4 stroke-[3]" />
            <span>Create New Role</span>
          </button>
        </div>
      </div>

      <!-- METRICS STATS BAR -->
      <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
        <!-- Total Roles -->
        <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-3xl p-5 shadow-xs flex items-center justify-between">
          <div class="space-y-1">
            <p class="text-[11px] font-extrabold uppercase tracking-wider text-slate-400 dark:text-slate-500">Total Staff Roles</p>
            <h3 class="text-2xl font-black text-slate-900 dark:text-white">{{ totalRolesCount }}</h3>
            <p class="text-[10px] text-slate-500 font-medium">Active job positions</p>
          </div>
          <div class="p-3.5 rounded-2xl bg-indigo-500/10 text-indigo-500 border border-indigo-500/20">
            <Shield class="w-6 h-6" />
          </div>
        </div>

        <!-- Active Roles -->
        <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-3xl p-5 shadow-xs flex items-center justify-between">
          <div class="space-y-1">
            <p class="text-[11px] font-extrabold uppercase tracking-wider text-slate-400 dark:text-slate-500">Active Positions</p>
            <h3 class="text-2xl font-black text-emerald-600 dark:text-emerald-400">{{ activeRolesCount }}</h3>
            <p class="text-[10px] text-slate-500 font-medium">Available for staff</p>
          </div>
          <div class="p-3.5 rounded-2xl bg-emerald-500/10 text-emerald-500 border border-emerald-500/20">
            <Activity class="w-6 h-6" />
          </div>
        </div>

        <!-- Total System Permissions -->
        <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-3xl p-5 shadow-xs flex items-center justify-between">
          <div class="space-y-1">
            <p class="text-[11px] font-extrabold uppercase tracking-wider text-slate-400 dark:text-slate-500">System Permissions</p>
            <h3 class="text-2xl font-black text-amber-500">{{ totalPermissionsCount }}</h3>
            <p class="text-[10px] text-slate-500 font-medium">Access privilege controls</p>
          </div>
          <div class="p-3.5 rounded-2xl bg-amber-500/10 text-amber-500 border border-amber-500/20">
            <Key class="w-6 h-6" />
          </div>
        </div>

        <!-- User Role Assignments Quick Link -->
        <div
          @click="router.push('/admin/user-roles')"
          class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-3xl p-5 shadow-xs flex items-center justify-between hover:border-amber-500/40 transition cursor-pointer group"
        >
          <div class="space-y-1">
            <p class="text-[11px] font-extrabold uppercase tracking-wider text-slate-400 dark:text-slate-500">Staff Assignments</p>
            <h3 class="text-2xl font-black text-indigo-600 dark:text-indigo-400 group-hover:underline flex items-center gap-1.5">
              <span>Assign Staff</span>
              <ExternalLink class="w-4 h-4 opacity-70" />
            </h3>
            <p class="text-[10px] text-slate-500 font-medium">Manage user role mappings</p>
          </div>
          <div class="p-3.5 rounded-2xl bg-purple-500/10 text-purple-500 border border-purple-500/20">
            <Users class="w-6 h-6" />
          </div>
        </div>
      </div>

      <!-- NOTIFICATION BANNERS -->
      <transition enter-active-class="transition duration-200" enter-from-class="opacity-0 -translate-y-2" enter-to-class="opacity-100 translate-y-0">
        <div v-if="successMessage" class="p-4 rounded-2xl bg-emerald-500/10 border border-emerald-500/30 text-emerald-700 dark:text-emerald-300 text-xs font-extrabold flex items-center justify-between">
          <div class="flex items-center gap-2.5">
            <CheckCircle2 class="w-5 h-5 text-emerald-500 flex-shrink-0" />
            <span>{{ successMessage }}</span>
          </div>
          <button @click="successMessage = ''" class="text-emerald-500 hover:text-emerald-700 cursor-pointer">
            <X class="w-4 h-4" />
          </button>
        </div>
      </transition>

      <transition enter-active-class="transition duration-200" enter-from-class="opacity-0 -translate-y-2" enter-to-class="opacity-100 translate-y-0">
        <div v-if="errorMessage" class="p-4 rounded-2xl bg-rose-500/10 border border-rose-500/30 text-rose-700 dark:text-rose-300 text-xs font-extrabold flex items-center justify-between">
          <div class="flex items-center gap-2.5">
            <AlertCircle class="w-5 h-5 text-rose-500 flex-shrink-0" />
            <span>{{ errorMessage }}</span>
          </div>
          <button @click="errorMessage = ''" class="text-rose-500 hover:text-rose-700 cursor-pointer">
            <X class="w-4 h-4" />
          </button>
        </div>
      </transition>

      <!-- SEARCH, FILTERS & SORTING BAR -->
      <div class="flex flex-col sm:flex-row items-center justify-between gap-4 bg-white dark:bg-slate-900 p-4 rounded-3xl border border-slate-200 dark:border-slate-800 shadow-xs">
        <!-- Search Box -->
        <div class="relative w-full sm:w-80">
          <Search class="w-4 h-4 absolute left-3.5 top-3 text-slate-400" />
          <input
            v-model="searchQuery"
            type="text"
            placeholder="Search roles by name or description..."
            class="w-full pl-10 pr-4 py-2.5 text-xs bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-xl text-slate-900 dark:text-white focus:outline-none focus:border-amber-500 font-medium"
          />
        </div>

        <!-- Filter Category Tabs -->
        <div class="flex items-center gap-1 overflow-x-auto w-full sm:w-auto pb-1 sm:pb-0">
          <button
            @click="filterCategory = 'all'"
            :class="[
              'px-3.5 py-2 rounded-xl text-xs font-bold transition whitespace-nowrap cursor-pointer',
              filterCategory === 'all'
                ? 'bg-slate-900 text-white dark:bg-white dark:text-slate-950 shadow-xs'
                : 'bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-400 hover:bg-slate-200 dark:hover:bg-slate-700'
            ]"
          >
            All Roles ({{ roles.length }})
          </button>
          <button
            @click="filterCategory = 'system'"
            :class="[
              'px-3.5 py-2 rounded-xl text-xs font-bold transition whitespace-nowrap cursor-pointer',
              filterCategory === 'system'
                ? 'bg-amber-500 text-slate-950 font-black shadow-xs'
                : 'bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-400 hover:bg-slate-200 dark:hover:bg-slate-700'
            ]"
          >
            System Roles
          </button>
          <button
            @click="filterCategory = 'custom'"
            :class="[
              'px-3.5 py-2 rounded-xl text-xs font-bold transition whitespace-nowrap cursor-pointer',
              filterCategory === 'custom'
                ? 'bg-amber-500 text-slate-950 font-black shadow-xs'
                : 'bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-400 hover:bg-slate-200 dark:hover:bg-slate-700'
            ]"
          >
            Custom Roles
          </button>
        </div>

        <!-- Sorting selector -->
        <div class="flex items-center gap-2 self-end sm:self-auto">
          <ArrowUpDown class="w-3.5 h-3.5 text-slate-400" />
          <select
            v-model="sortBy"
            class="px-3 py-2 text-xs bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-xl text-slate-700 dark:text-slate-300 font-bold focus:outline-none focus:border-amber-500 cursor-pointer"
          >
            <option value="name">Sort by Name</option>
            <option value="users">Sort by User Count</option>
            <option value="permissions">Sort by Permission Count</option>
          </select>
        </div>
      </div>

      <!-- LOADING INITIAL STATE -->
      <div v-if="loading && roles.length === 0" class="py-24 text-center bg-white dark:bg-slate-900 rounded-3xl border border-slate-200 dark:border-slate-800">
        <div class="animate-spin rounded-full h-10 w-10 border-b-2 border-amber-500 mx-auto mb-3"></div>
        <p class="text-sm font-semibold text-slate-500 dark:text-slate-400">Loading system roles & permission schemas...</p>
      </div>

      <!-- EMPTY SEARCH STATE -->
      <div v-else-if="filteredRoles.length === 0" class="py-20 text-center bg-white dark:bg-slate-900 rounded-3xl border border-slate-200 dark:border-slate-800 space-y-3">
        <ShieldCheck class="w-12 h-12 text-slate-400 mx-auto opacity-50" />
        <h3 class="text-base font-bold text-slate-800 dark:text-slate-200">No system roles match your filters</h3>
        <p class="text-xs text-slate-500 dark:text-slate-400 max-w-sm mx-auto">
          Try clearing your search query or switching category filters.
        </p>
      </div>

      <!-- VIEW MODE: CARDS GRID -->
      <div v-else-if="viewMode === 'cards'" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        <div
          v-for="role in filteredRoles"
          :key="role.id"
          class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-3xl p-6 shadow-xs hover:shadow-lg hover:border-slate-300 dark:hover:border-slate-700 transition-all flex flex-col justify-between space-y-5 group"
        >
          <div class="space-y-4">
            <!-- Header Row -->
            <div class="flex items-start justify-between gap-3">
              <div class="flex items-start gap-3.5">
                <div class="w-12 h-12 rounded-2xl bg-amber-500/10 dark:bg-amber-500/10 border border-amber-500/20 flex items-center justify-center text-amber-600 dark:text-amber-400 flex-shrink-0 shadow-xs group-hover:scale-105 transition-transform">
                  <component :is="getRoleIcon(role.slug || role.name)" class="w-6 h-6" />
                </div>
                <div>
                  <div class="flex items-center gap-2 flex-wrap">
                    <h3 class="text-lg font-black text-slate-900 dark:text-white capitalize tracking-tight">
                      {{ role.name }}
                    </h3>
                    <span
                      v-if="role.is_system"
                      class="text-[9px] uppercase font-extrabold px-2 py-0.5 rounded-full bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-400 border border-slate-200 dark:border-slate-700"
                    >
                      System
                    </span>
                    <span
                      v-else
                      class="text-[9px] uppercase font-extrabold px-2 py-0.5 rounded-full bg-amber-500/10 text-amber-600 dark:text-amber-400 border border-amber-500/20"
                    >
                      Custom
                    </span>
                  </div>
                  <p class="text-xs text-slate-500 dark:text-slate-400 line-clamp-2 mt-1 leading-relaxed font-medium">
                    {{ role.description || 'System access role' }}
                  </p>
                </div>
              </div>

              <!-- Quick Active Switch Toggle -->
              <button
                @click="toggleRoleActive(role)"
                :disabled="role.slug === 'admin'"
                :class="[
                  'w-9 h-5 rounded-full flex items-center p-0.5 transition cursor-pointer flex-shrink-0',
                  (role.is_active ?? true) ? 'bg-emerald-500' : 'bg-slate-300 dark:bg-slate-700',
                  role.slug === 'admin' ? 'opacity-50 cursor-not-allowed' : ''
                ]"
                :title="(role.is_active ?? true) ? 'Click to deactivate role' : 'Click to activate role'"
              >
                <div
                  :class="[
                    'w-4 h-4 bg-white rounded-full shadow-md transform transition',
                    (role.is_active ?? true) ? 'translate-x-4' : 'translate-x-0'
                  ]"
                ></div>
              </button>
            </div>

            <!-- Permission Modules Covered Tags -->
            <div v-if="getRoleModuleTags(role).length > 0" class="flex flex-wrap items-center gap-1.5 pt-1">
              <span
                v-for="tag in getRoleModuleTags(role)"
                :key="tag"
                class="px-2.5 py-0.5 rounded-lg bg-slate-100 dark:bg-slate-800/80 text-slate-600 dark:text-slate-400 font-bold text-[10px] uppercase border border-slate-200/60 dark:border-slate-700/60"
              >
                {{ tag }}
              </span>
              <span v-if="(role.permissions?.length || 0) > 3" class="text-[10px] text-slate-400 font-bold">
                +more
              </span>
            </div>

            <!-- Stats Pills -->
            <div class="grid grid-cols-2 gap-3 pt-2">
              <button
                @click="openEditModal(role)"
                class="flex items-center justify-between px-3 py-2 rounded-2xl bg-slate-50 dark:bg-slate-800/60 border border-slate-100 dark:border-slate-800 text-xs font-semibold text-slate-700 dark:text-slate-300 hover:border-amber-500/30 transition cursor-pointer text-left"
              >
                <div class="flex items-center gap-2 truncate">
                  <Key class="w-4 h-4 text-amber-500 flex-shrink-0" />
                  <span class="truncate font-bold">{{ role.permissions_count ?? role.permissions?.length ?? 0 }} Permissions</span>
                </div>
              </button>

              <button
                @click="openUsersModal(role)"
                class="flex items-center justify-between px-3 py-2 rounded-2xl bg-slate-50 dark:bg-slate-800/60 border border-slate-100 dark:border-slate-800 text-xs font-semibold text-slate-700 dark:text-slate-300 hover:border-indigo-500/30 transition cursor-pointer text-left"
              >
                <div class="flex items-center gap-2 truncate">
                  <Users class="w-4 h-4 text-indigo-500 flex-shrink-0" />
                  <span class="truncate font-bold">{{ role.users_count ?? 0 }} Staff</span>
                </div>
              </button>
            </div>
          </div>

          <!-- Card Action Buttons -->
          <div class="pt-4 border-t border-slate-100 dark:border-slate-800/80 flex items-center justify-between gap-2">
            <button
              @click="openEditModal(role)"
              class="flex-1 inline-flex items-center justify-center gap-2 px-4 py-2.5 rounded-2xl bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 dark:hover:bg-slate-700 text-slate-800 dark:text-slate-200 text-xs font-extrabold transition cursor-pointer"
            >
              <Edit2 class="w-3.5 h-3.5 text-amber-500" />
              <span>Configure Role</span>
            </button>

            <button
              @click="openCloneModal(role)"
              class="p-2.5 rounded-2xl border border-slate-200 dark:border-slate-800 hover:bg-slate-100 dark:hover:bg-slate-800 text-slate-500 hover:text-slate-800 dark:hover:text-white transition cursor-pointer"
              title="Duplicate / Clone Role"
            >
              <Copy class="w-4 h-4" />
            </button>

            <button
              v-if="!role.is_system"
              @click="triggerDeleteConfirmation(role)"
              class="p-2.5 rounded-2xl text-rose-500 hover:bg-rose-500/10 transition cursor-pointer"
              title="Delete Custom Role"
            >
              <Trash2 class="w-4 h-4" />
            </button>
          </div>
        </div>
      </div>

      <!-- VIEW MODE: PERMISSION MATRIX VIEW -->
      <div v-else-if="viewMode === 'matrix'" class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-3xl p-6 shadow-xs overflow-x-auto">
        <table class="w-full text-left border-collapse">
          <thead>
            <tr class="border-b border-slate-200 dark:border-slate-800">
              <th class="py-3 px-4 text-xs font-black text-slate-900 dark:text-white uppercase tracking-wider min-w-[200px]">
                Permission Capability
              </th>
              <th
                v-for="r in roles"
                :key="r.id"
                class="py-3 px-4 text-xs font-black text-center text-slate-900 dark:text-white capitalize min-w-[120px]"
              >
                {{ r.name }}
              </th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-100 dark:divide-slate-800 text-xs">
            <tr v-for="p in permissions.slice(0, 25)" :key="p.id" class="hover:bg-slate-50/50 dark:hover:bg-slate-950/50">
              <td class="py-3 px-4">
                <p class="font-bold text-slate-900 dark:text-white">{{ p.name }}</p>
                <p class="text-[10px] text-slate-400 font-mono">{{ p.slug }}</p>
              </td>
              <td v-for="r in roles" :key="r.id" class="py-3 px-4 text-center">
                <div class="flex items-center justify-center">
                  <span
                    v-if="r.permissions && r.permissions.some((perm: any) => (perm.id || perm) === p.id)"
                    class="w-6 h-6 rounded-full bg-emerald-500/10 text-emerald-500 border border-emerald-500/30 flex items-center justify-center"
                  >
                    <Check class="w-3.5 h-3.5 stroke-[3]" />
                  </span>
                  <span v-else class="text-slate-300 dark:text-slate-700 font-mono">–</span>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
        <p class="text-center text-xs text-slate-400 mt-4">Showing top capabilities preview. Click "Configure Role" on any role card to view full permission matrix.</p>
      </div>

      <!-- MODALS INTEGRATION -->
      <ConfigureRoleModal
        :show="showModal"
        :editing-role="editingRole"
        :initial-permission-ids="initialPermissionIds"
        :permissions="permissions"
        :loading="saving"
        :loading-permissions="loadingPermissions"
        @close="showModal = false"
        @save="handleSaveRole"
      />

      <ViewRoleUsersModal
        :show="showUsersModal"
        :role="selectedRoleForUsers"
        :users="userSummaries"
        @close="showUsersModal = false"
        @navigate-user-roles="navigateToUserAssignments"
      />

      <ConfirmDeleteModal
        :show="showDeleteModal"
        title="Delete Custom Role?"
        :message="`Are you sure you want to delete custom role '${roleToDelete?.name}'? Assigned staff will lose permissions associated with this role.`"
        :loading="deleting"
        @close="showDeleteModal = false"
        @confirm="confirmDeleteRole"
      />
    </div>
  </DashboardLayout>
</template>
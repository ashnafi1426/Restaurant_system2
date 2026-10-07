<script setup lang="ts">
import { ref, onMounted, onUnmounted, computed, watch } from 'vue'
import { useRouter } from 'vue-router'
import DashboardLayout from '@/Layouts/DashboardLayout.vue'
import ConfigureRoleModal from '@/components/rbac/ConfigureRoleModal.vue'
import ViewRoleUsersModal from '@/components/rbac/ViewRoleUsersModal.vue'
import ConfirmDeleteModal from '@/components/rbac/ConfirmDeleteModal.vue'
import { rbacService } from '@/services/rbacService'
import { useAuthStore } from '@/stores/auth'
import { useHotelStore } from '@/stores/hotelStore'
import { useLanguageStore } from '@/stores/language'
import type { Role, Permission, RbacUserSummary } from '@/types/rbacTypes'
import {
  ShieldCheck,
  Plus,
  Edit2,
  Trash2,
  CheckCircle2,
  Users,
  RefreshCw,
  AlertCircle,
  Search,
  Copy,
  Sparkles,
  X,
  Shield,
  Activity,
  Crown,
  Briefcase,
  Contact,
  ChefHat,
  Utensils,
  Wallet,
  User,
  MoreVertical,
  Filter,
  Columns,
  RotateCcw,
  ArrowRight,
  Monitor,
  ChevronRight,
  Grid
} from 'lucide-vue-next'

const router = useRouter()
const authStore = useAuthStore()
const hotelStore = useHotelStore()
const languageStore = useLanguageStore()

// Main Data State
const roles = ref<Role[]>([])
const permissions = ref<Permission[]>([])
const userSummaries = ref<RbacUserSummary[]>([])

// Loading States
const loading = ref(true)
const isRefreshing = ref(false)
const saving = ref(false)
const loadingPermissions = ref(false)
const loadingUsers = ref(false)

// Feedback Messages
const errorMessage = ref('')
const successMessage = ref('')
let messageTimeout: ReturnType<typeof setTimeout> | null = null

// View & Filter States
const viewMode = ref<'table' | 'cards'>('table')
const isFilterOpen = ref(false)
const searchQuery = ref('')
const filterCategory = ref<'all' | 'system' | 'custom' | 'active' | 'inactive'>('all')
const sortBy = ref<'name' | 'users' | 'permissions'>('name')

// Selection & Dropdown States
const selectedRoleIds = ref<(string | number)[]>([])
const activeDropdownRoleId = ref<string | number | null>(null)

// Configure Role Modal
const showModal = ref(false)
const editingRole = ref<Role | null>(null)
const initialPermissionIds = ref<number[]>([])

// View Users Modal
const showUsersModal = ref(false)
const selectedRoleForUsers = ref<Role | null>(null)

// Delete Confirm Modal
const showDeleteModal = ref(false)
const roleToDelete = ref<Role | null>(null)
const deleting = ref(false)

// Helper Functions
const isRoleActive = (role: Role): boolean => role.is_active ?? true

const getPermissionCount = (role: Role): number => {
  return role.permissions_count ?? role.permissions?.length ?? 0
}

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

const notify = (type: 'success' | 'error', text: string) => {
  if (messageTimeout) clearTimeout(messageTimeout)
  if (type === 'success') {
    successMessage.value = text
    errorMessage.value = ''
  } else {
    errorMessage.value = text
    successMessage.value = ''
  }
  messageTimeout = setTimeout(() => {
    successMessage.value = ''
    errorMessage.value = ''
  }, 3500)
}

// Local Storage Cache
const getClientCacheKey = () => `rbac_roles_cache_${hotelStore.hotelId || 'default'}`

const loadFromClientCache = (): boolean => {
  try {
    const raw = localStorage.getItem(getClientCacheKey())
    if (raw) {
      const parsed = JSON.parse(raw)
      if (Array.isArray(parsed) && parsed.length > 0) {
        roles.value = parsed
        loading.value = false
        return true
      }
    }
  } catch (e) {
    // Ignore JSON parse error
  }
  return false
}

const saveToClientCache = (data: Role[]) => {
  try {
    localStorage.setItem(getClientCacheKey(), JSON.stringify(data))
  } catch (e) {
    // Ignore storage quota error
  }
}

// Data Fetching
const ensurePermissionsLoaded = async (forceRefresh = false) => {
  if (permissions.value.length > 0 && !forceRefresh) return
  try {
    const permsData = await rbacService.getPermissions({ refresh: forceRefresh })
    if (permsData?.data) permissions.value = permsData.data
  } catch (err) {
    console.warn('[RoleManagement] Permissions fetch error:', err)
  }
}

const loadRolePermissionIds = async (roleId: string | number): Promise<number[]> => {
  try {
    const permData = await rbacService.getRolePermissions(roleId)
    if (permData && Array.isArray(permData.permission_ids)) {
      return [...permData.permission_ids]
    }
    if (permData && Array.isArray(permData.data)) {
      return permData.data.map((p: any) => p.id)
    }
  } catch (err) {
    console.error('[RoleManagement] Failed to load role permissions:', err)
  }
  return []
}

const fetchRolesAndPermissions = async (silent = false, forceServerRefresh = false) => {
  if (!silent && roles.value.length === 0) {
    loading.value = true
  } else {
    isRefreshing.value = true
  }
  errorMessage.value = ''

  try {
    const rolesData = await rbacService.getRoles({ refresh: forceServerRefresh })
    if (Array.isArray(rolesData)) {
      roles.value = rolesData
      saveToClientCache(rolesData)
    }

    loading.value = false
    isRefreshing.value = false

    // Pre-fetch permissions in the background so modals & filters are snappy
    ensurePermissionsLoaded(forceServerRefresh)
  } catch (err: any) {
    console.error('[RoleManagement] Fetch error:', err)
    notify('error', err?.response?.data?.message || 'Failed to load system roles.')
  } finally {
    loading.value = false
    isRefreshing.value = false
  }
}

// Dropdown & Selection Handlers
const handleGlobalClick = (event: MouseEvent) => {
  const target = event.target as HTMLElement
  if (!target.closest('[data-role-dropdown]')) {
    activeDropdownRoleId.value = null
  }
}

const toggleDropdown = (roleId: string | number, event: MouseEvent) => {
  event.stopPropagation()
  activeDropdownRoleId.value = activeDropdownRoleId.value === roleId ? null : roleId
}

const closeDropdown = () => {
  activeDropdownRoleId.value = null
}

const toggleSelectAll = (e: Event) => {
  const checked = (e.target as HTMLInputElement).checked
  selectedRoleIds.value = checked ? filteredRoles.value.map(r => r.id) : []
}

const toggleSelectRole = (id: string | number) => {
  const idx = selectedRoleIds.value.indexOf(id)
  if (idx >= 0) {
    selectedRoleIds.value.splice(idx, 1)
  } else {
    selectedRoleIds.value.push(id)
  }
}

const toggleFilter = () => {
  isFilterOpen.value = !isFilterOpen.value
}

const resetFilters = () => {
  searchQuery.value = ''
  filterCategory.value = 'all'
  sortBy.value = 'name'
}

// Lifecycle & Hotel Watcher
onMounted(async () => {
  document.addEventListener('click', handleGlobalClick)
  const hasCached = loadFromClientCache()
  await fetchRolesAndPermissions(hasCached, false)
})

onUnmounted(() => {
  document.removeEventListener('click', handleGlobalClick)
  if (messageTimeout) clearTimeout(messageTimeout)
})

watch(
  () => hotelStore.hotelId,
  async (newVal, oldVal) => {
    if (newVal && newVal !== oldVal && oldVal !== undefined) {
      userSummaries.value = []
      const hasCached = loadFromClientCache()
      await fetchRolesAndPermissions(hasCached, false)
    }
  }
)

// Summary Metrics
const allRolesCount = computed(() => roles.value.length)
const activeRolesCount = computed(() => roles.value.filter(isRoleActive).length)
const totalAssignedUsersCount = computed(() => roles.value.reduce((acc, r) => acc + (r.users_count ?? 0), 0))
const unassignedRolesCount = computed(() => roles.value.filter(r => (r.users_count ?? 0) === 0).length)

// Filtered & Sorted Roles
const filteredRoles = computed(() => {
  let list = roles.value

  // Classification filter
  if (filterCategory.value === 'system') {
    list = list.filter(r => r.is_system)
  } else if (filterCategory.value === 'custom') {
    list = list.filter(r => !r.is_system)
  } else if (filterCategory.value === 'active') {
    list = list.filter(r => isRoleActive(r))
  } else if (filterCategory.value === 'inactive') {
    list = list.filter(r => !isRoleActive(r))
  }

  // Search filter
  const q = searchQuery.value.trim().toLowerCase()
  if (q) {
    list = list.filter(r =>
      r.name.toLowerCase().includes(q) ||
      r.slug.toLowerCase().includes(q) ||
      (r.description && r.description.toLowerCase().includes(q))
    )
  }

  // Sorting
  return [...list].sort((a, b) => {
    if (sortBy.value === 'users') {
      return (b.users_count ?? 0) - (a.users_count ?? 0)
    }
    if (sortBy.value === 'permissions') {
      return getPermissionCount(b) - getPermissionCount(a)
    }
    return a.name.localeCompare(b.name)
  })
})

const isAllSelected = computed(() => {
  return filteredRoles.value.length > 0 && selectedRoleIds.value.length === filteredRoles.value.length
})

// Modal Open Handlers
const openCreateModal = async () => {
  editingRole.value = null
  initialPermissionIds.value = []
  showModal.value = true

  if (permissions.value.length === 0) {
    loadingPermissions.value = true
    await ensurePermissionsLoaded()
    loadingPermissions.value = false
  }
}

const openEditModal = async (role: Role) => {
  editingRole.value = role
  initialPermissionIds.value = []
  loadingPermissions.value = true
  showModal.value = true

  ensurePermissionsLoaded()
  initialPermissionIds.value = await loadRolePermissionIds(role.id)
  loadingPermissions.value = false
}

const openCloneModal = async (role: Role) => {
  editingRole.value = null
  initialPermissionIds.value = []
  loadingPermissions.value = true
  showModal.value = true

  ensurePermissionsLoaded()
  initialPermissionIds.value = await loadRolePermissionIds(role.id)
  loadingPermissions.value = false
}

const openUsersModal = async (role: Role) => {
  selectedRoleForUsers.value = role
  showUsersModal.value = true

  if (userSummaries.value.length === 0) {
    loadingUsers.value = true
    try {
      const usersData = await rbacService.getUserRoleSummaries()
      if (usersData) userSummaries.value = usersData
    } catch (err) {
      console.error('[RoleManagement] Error loading staff for modal:', err)
    } finally {
      loadingUsers.value = false
    }
  }
}

// Role Mutation Handlers
const toggleRoleActive = async (role: Role) => {
  if (role.slug === 'admin') {
    notify('error', 'System Administrator role status cannot be altered.')
    return
  }

  const targetState = !isRoleActive(role)
  try {
    await rbacService.updateRole(role.id, { is_active: targetState })
    role.is_active = targetState
    saveToClientCache(roles.value)
    notify('success', `Role "${role.name}" is now ${targetState ? 'active' : 'inactive'}.`)
  } catch (err: any) {
    console.error('[RoleManagement] Toggle active error:', err)
    notify('error', err?.response?.data?.message || 'Failed to update role active status.')
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
      notify('success', `Role "${payload.name}" updated successfully!`)
    } else {
      await rbacService.createRole(payload)
      notify('success', `Role "${payload.name}" created successfully!`)
    }

    try {
      await authStore.fetchCurrentUser()
      window.dispatchEvent(new CustomEvent('permissions-updated'))
    } catch (e) {
      console.warn('[ROLES] Auth refresh error:', e)
    }

    showModal.value = false
    await fetchRolesAndPermissions(true, true)
  } catch (err: any) {
    console.error('[RoleManagement] Save role error:', err)
    notify('error', err?.response?.data?.message || 'Failed to save role configuration.')
  } finally {
    saving.value = false
  }
}

const triggerDeleteConfirmation = (role: Role) => {
  if (role.is_system) {
    notify('error', 'Core system roles cannot be deleted.')
    return
  }
  roleToDelete.value = role
  showDeleteModal.value = true
}

const confirmDeleteRole = async () => {
  if (!roleToDelete.value) return
  deleting.value = true
  try {
    await rbacService.deleteRole(roleToDelete.value.id)
    notify('success', `Role "${roleToDelete.value.name}" deleted successfully.`)
    showDeleteModal.value = false
    roleToDelete.value = null
    await fetchRolesAndPermissions(true, true)
  } catch (err: any) {
    console.error('[RoleManagement] Delete role error:', err)
    notify('error', err?.response?.data?.message || 'Failed to delete role.')
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
    <div class="min-h-screen bg-slate-50 dark:bg-slate-950 text-slate-900 dark:text-white p-4 sm:p-6 space-y-6 font-sans transition-colors duration-200">
      <!-- TOP BREADCRUMB -->
      <div class="flex items-center gap-2 text-xs text-slate-500 dark:text-slate-400 font-medium">
        <Monitor class="w-4 h-4 text-slate-400 dark:text-slate-500" />
        <ChevronRight class="w-3.5 h-3.5 text-slate-400 dark:text-slate-600" />
        <span class="text-slate-700 dark:text-slate-200 font-semibold">{{ languageStore.t('role', 'Role') }}</span>
      </div>

      <!-- 4 STAT CARDS -->
      <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <!-- Card 1: All Roles -->
        <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-5 flex items-center justify-between shadow-xs transition hover:shadow-md">
          <div class="space-y-1">
            <p class="text-xs text-slate-500 dark:text-slate-400 font-bold uppercase tracking-wider">{{ languageStore.t('all_roles', 'All Roles') }}</p>
            <h3 class="text-3xl font-black text-slate-900 dark:text-white tracking-tight">{{ allRolesCount }}</h3>
          </div>
          <div class="w-12 h-12 rounded-xl bg-blue-500/10 text-blue-600 dark:text-blue-400 border border-blue-500/20 flex items-center justify-center shadow-xs">
            <Shield class="w-6 h-6" />
          </div>
        </div>

        <!-- Card 2: Active Roles -->
        <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-5 flex items-center justify-between shadow-xs transition hover:shadow-md">
          <div class="space-y-1">
            <p class="text-xs text-slate-500 dark:text-slate-400 font-bold uppercase tracking-wider">{{ languageStore.t('active_roles', 'Active Roles') }}</p>
            <h3 class="text-3xl font-black text-slate-900 dark:text-white tracking-tight">{{ activeRolesCount }}</h3>
          </div>
          <div class="w-12 h-12 rounded-xl bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 border border-emerald-500/20 flex items-center justify-center shadow-xs">
            <CheckCircle2 class="w-6 h-6" />
          </div>
        </div>

        <!-- Card 3: Assigned Users -->
        <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-5 flex items-center justify-between shadow-xs transition hover:shadow-md">
          <div class="space-y-1">
            <p class="text-xs text-slate-500 dark:text-slate-400 font-bold uppercase tracking-wider">{{ languageStore.t('assigned_users', 'Assigned Users') }}</p>
            <h3 class="text-3xl font-black text-slate-900 dark:text-white tracking-tight">{{ totalAssignedUsersCount }}</h3>
          </div>
          <div class="w-12 h-12 rounded-xl bg-indigo-500/10 text-indigo-600 dark:text-indigo-400 border border-indigo-500/20 flex items-center justify-center shadow-xs">
            <Users class="w-6 h-6" />
          </div>
        </div>

        <!-- Card 4: Unassigned Roles -->
        <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-5 flex items-center justify-between shadow-xs transition hover:shadow-md">
          <div class="space-y-1">
            <p class="text-xs text-slate-500 dark:text-slate-400 font-bold uppercase tracking-wider">{{ languageStore.t('unassigned_roles', 'Unassigned Roles') }}</p>
            <h3 class="text-3xl font-black text-slate-900 dark:text-white tracking-tight">{{ unassignedRolesCount }}</h3>
          </div>
          <div class="w-12 h-12 rounded-xl bg-amber-500/10 text-amber-600 dark:text-amber-400 border border-amber-500/20 flex items-center justify-center shadow-xs">
            <AlertCircle class="w-6 h-6" />
          </div>
        </div>
      </div>

      <!-- TITLE HEADER -->
      <div class="space-y-1">
        <h2 class="text-2xl font-black text-slate-900 dark:text-white tracking-tight">{{ languageStore.t('manage_roles', 'Manage Roles') }}</h2>
        <p class="text-xs text-slate-500 dark:text-slate-400">{{ languageStore.t('role_management_desc', 'Configure staff roles, access levels, and granular permissions.') }}</p>
      </div>

      <!-- NOTIFICATION BANNERS -->
      <transition enter-active-class="transition duration-200" enter-from-class="opacity-0 -translate-y-2" enter-to-class="opacity-100 translate-y-0">
        <div v-if="successMessage" class="p-3.5 rounded-xl bg-emerald-500/10 border border-emerald-500/30 text-emerald-700 dark:text-emerald-400 text-xs font-bold flex items-center justify-between shadow-xs">
          <div class="flex items-center gap-2">
            <CheckCircle2 class="w-4 h-4 text-emerald-600 dark:text-emerald-400 flex-shrink-0" />
            <span>{{ successMessage }}</span>
          </div>
          <button @click="successMessage = ''" class="text-emerald-700 dark:text-emerald-400 hover:text-slate-900 dark:hover:text-white cursor-pointer">
            <X class="w-4 h-4" />
          </button>
        </div>
      </transition>

      <transition enter-active-class="transition duration-200" enter-from-class="opacity-0 -translate-y-2" enter-to-class="opacity-100 translate-y-0">
        <div v-if="errorMessage" class="p-3.5 rounded-xl bg-rose-500/10 border border-rose-500/30 text-rose-700 dark:text-rose-400 text-xs font-bold flex items-center justify-between shadow-xs">
          <div class="flex items-center gap-2">
            <AlertCircle class="w-4 h-4 text-rose-600 dark:text-rose-400 flex-shrink-0" />
            <span>{{ errorMessage }}</span>
          </div>
          <button @click="errorMessage = ''" class="text-rose-700 dark:text-rose-400 hover:text-slate-900 dark:hover:text-white cursor-pointer">
            <X class="w-4 h-4" />
          </button>
        </div>
      </transition>

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
              :placeholder="languageStore.t('search_roles_placeholder', 'Search roles by title, key, permissions...')"
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
            <span>{{ isFilterOpen ? languageStore.t('hide_filters', 'Hide Filter') : languageStore.t('filter', 'Filter') }}</span>
          </button>
        </div>

        <!-- Right: Action Buttons -->
        <div class="flex items-center gap-2 sm:gap-2.5">
          <!-- Refresh Button -->
          <button
            type="button"
            @click="fetchRolesAndPermissions(false, true)"
            :disabled="loading || isRefreshing"
            :title="languageStore.t('refresh', 'Refresh')"
            class="inline-flex items-center justify-center h-9 w-9 sm:h-10 sm:w-10 rounded-xl border border-slate-200 dark:border-[#1e3455] bg-white dark:bg-[#13233c]/80 text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-[#1c3356] hover:text-slate-900 dark:hover:text-white transition disabled:opacity-50 cursor-pointer"
          >
            <RefreshCw class="w-4 h-4" :class="{ 'animate-spin': loading || isRefreshing }" />
          </button>

          <!-- Columns view toggle -->
          <button
            type="button"
            @click="viewMode = viewMode === 'table' ? 'cards' : 'table'"
            class="inline-flex items-center justify-center h-9 w-9 sm:h-10 sm:w-10 rounded-xl border border-slate-200 dark:border-[#1e3455] bg-white dark:bg-[#13233c]/80 text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-[#1c3356] hover:text-slate-900 dark:hover:text-white transition cursor-pointer"
            :title="viewMode === 'table' ? languageStore.t('switch_cards', 'Switch to Card view') : languageStore.t('switch_table', 'Switch to Table view')"
          >
            <Columns class="w-4 h-4" />
          </button>

          <!-- Permission Matrix quick link -->
          <button
            type="button"
            @click="router.push('/admin/permission-matrix')"
            class="inline-flex items-center justify-center h-9 w-9 sm:h-10 sm:w-10 rounded-xl border border-slate-200 dark:border-[#1e3455] bg-white dark:bg-[#13233c]/80 text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-[#1c3356] hover:text-slate-900 dark:hover:text-white transition cursor-pointer"
            :title="languageStore.t('permission_matrix', 'Permission Matrix')"
          >
            <Grid class="w-4 h-4" />
          </button>

          <!-- Create Role Primary Button -->
          <button
            type="button"
            @click="openCreateModal"
            class="inline-flex items-center gap-1.5 rounded-xl bg-blue-600 hover:bg-blue-700 text-white px-3.5 sm:px-4 py-2.5 text-xs sm:text-sm font-bold shadow-md shadow-blue-600/25 transition active:scale-98 cursor-pointer flex-shrink-0"
          >
            <Plus class="w-4 h-4 text-white" />
            <span class="text-white">{{ languageStore.t('create_role', 'Create Role') }}</span>
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
            <!-- Category Filter -->
            <div>
              <label class="mb-1.5 block text-xs font-semibold text-slate-600 dark:text-slate-300">
                {{ languageStore.t('role_classification', 'Role Classification') }}
              </label>
              <select
                v-model="filterCategory"
                class="w-full rounded-xl border border-slate-200 dark:border-[#1e3455] bg-slate-50/80 dark:bg-[#13233c] text-slate-900 dark:text-white px-3.5 py-2 text-xs sm:text-sm focus:ring-2 focus:ring-blue-500/40 focus:border-blue-500 dark:focus:border-blue-400 transition cursor-pointer font-medium outline-none"
              >
                <option value="all">{{ languageStore.t('all_roles', 'All Roles') }}</option>
                <option value="system">{{ languageStore.t('system_core_roles', 'System Core Roles') }}</option>
                <option value="custom">{{ languageStore.t('custom_property_roles', 'Custom Property Roles') }}</option>
                <option value="active">{{ languageStore.t('active_roles_only', 'Active Roles Only') }}</option>
                <option value="inactive">{{ languageStore.t('inactive_roles_only', 'Inactive Roles Only') }}</option>
              </select>
            </div>

            <!-- Sort By -->
            <div>
              <label class="mb-1.5 block text-xs font-semibold text-slate-600 dark:text-slate-300">
                {{ languageStore.t('sort_order', 'Sort Order') }}
              </label>
              <select
                v-model="sortBy"
                class="w-full rounded-xl border border-slate-200 dark:border-[#1e3455] bg-slate-50/80 dark:bg-[#13233c] text-slate-900 dark:text-white px-3.5 py-2 text-xs sm:text-sm focus:ring-2 focus:ring-blue-500/40 focus:border-blue-500 dark:focus:border-blue-400 transition cursor-pointer font-medium outline-none"
              >
                <option value="name">{{ languageStore.t('sort_alpha', 'Alphabetical (Role Name)') }}</option>
                <option value="users">{{ languageStore.t('sort_users', 'Most Assigned Users') }}</option>
                <option value="permissions">{{ languageStore.t('sort_permissions', 'Most Permissions') }}</option>
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

      <!-- LOADING SKELETON STATE -->
      <div v-if="loading && roles.length === 0" class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-12 text-center shadow-xs">
        <div class="animate-spin rounded-full h-9 w-9 border-b-2 border-blue-500 mx-auto mb-3"></div>
        <p class="text-xs font-semibold text-slate-500 dark:text-slate-400">{{ languageStore.t('loading_roles_perms', 'Loading hotel positions & permissions...') }}</p>
      </div>

      <!-- EMPTY SEARCH STATE -->
      <div v-else-if="filteredRoles.length === 0" class="py-16 text-center bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl space-y-2 shadow-xs">
        <ShieldCheck class="w-10 h-10 text-slate-400 dark:text-slate-500 mx-auto opacity-40" />
        <h3 class="text-sm font-bold text-slate-800 dark:text-slate-200">{{ languageStore.t('no_roles_match', 'No roles match your search filters') }}</h3>
        <p class="text-xs text-slate-500 max-w-sm mx-auto">
          {{ languageStore.t('try_clearing_filters', 'Try clearing your search query or switching your active filter.') }}
        </p>
      </div>

      <!-- MAIN DATA TABLE -->
      <div v-else-if="viewMode === 'table'" class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl shadow-xs overflow-hidden">
        <div class="overflow-x-auto min-h-[350px]">
          <table class="w-full text-left border-collapse text-xs">
            <thead>
              <tr class="bg-slate-50/90 dark:bg-slate-950/80 border-b border-slate-200 dark:border-slate-800 text-[11px] font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider">
                <!-- Checkbox -->
                <th class="py-3.5 px-4 w-10">
                  <input
                    type="checkbox"
                    :checked="isAllSelected"
                    @change="toggleSelectAll"
                    class="w-4 h-4 rounded bg-slate-100 dark:bg-slate-900 border-slate-300 dark:border-slate-700 text-blue-600 focus:ring-0 cursor-pointer"
                  />
                </th>
                <th class="py-3.5 px-4 font-bold">{{ languageStore.t('role_name', 'Role Name') }}</th>
                <th class="py-3.5 px-4 font-bold">{{ languageStore.t('description', 'Description') }}</th>
                <th class="py-3.5 px-4 font-bold">{{ languageStore.t('entity_type', 'Entity Type') }}</th>
                <th class="py-3.5 px-4 font-bold">{{ languageStore.t('entity', 'Entity') }}</th>
                <th class="py-3.5 px-4 font-bold">{{ languageStore.t('permissions', 'Permissions') }}</th>
                <th class="py-3.5 px-4 font-bold text-center">{{ languageStore.t('assigned_users', 'Assigned Users') }}</th>
                <th class="py-3.5 px-4 font-bold text-center">{{ languageStore.t('status', 'Status') }}</th>
                <th class="py-3.5 px-4 font-bold text-right pr-6">{{ languageStore.t('actions', 'Actions') }}</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-200/80 dark:divide-slate-800/60 text-xs">
              <tr
                v-for="role in filteredRoles"
                :key="role.id"
                class="hover:bg-slate-50/70 dark:hover:bg-slate-800/40 transition-colors group"
              >
                <!-- Checkbox -->
                <td class="py-4 px-4 w-10">
                  <input
                    type="checkbox"
                    :checked="selectedRoleIds.includes(role.id)"
                    @change="toggleSelectRole(role.id)"
                    class="w-4 h-4 rounded bg-slate-100 dark:bg-slate-900 border-slate-300 dark:border-slate-700 text-blue-600 focus:ring-0 cursor-pointer"
                  />
                </td>

                <!-- Role Name (Crisp & High Contrast with Icon) -->
                <td class="py-4 px-4 whitespace-nowrap">
                  <div class="flex items-center gap-3">
                    <div class="w-8 h-8 rounded-lg bg-blue-500/10 text-blue-600 dark:text-blue-400 border border-blue-500/20 flex items-center justify-center flex-shrink-0">
                      <component :is="getRoleIcon(role.slug || role.name)" class="w-4 h-4" />
                    </div>
                    <div>
                      <div class="font-bold text-slate-900 dark:text-white capitalize text-xs flex items-center gap-1.5">
                        <span>{{ role.name }}</span>
                        <span v-if="role.slug === 'admin'" class="px-1.5 py-0.2 rounded text-[9px] font-black bg-amber-500/10 text-amber-600 dark:text-amber-400 border border-amber-500/20 uppercase tracking-wider">ROOT</span>
                      </div>
                      <div class="text-[10px] text-slate-400 font-mono mt-0.5">
                        {{ role.slug }}
                      </div>
                    </div>
                  </div>
                </td>

                <!-- Description -->
                <td class="py-4 px-4 text-slate-600 dark:text-slate-400 max-w-xs truncate font-medium">
                  {{ role.description || 'Standard operational role privileges' }}
                </td>

                <!-- Entity type -->
                <td class="py-4 px-4 whitespace-nowrap">
                  <span
                    :class="[
                      'px-2.5 py-1 rounded-md text-[11px] font-bold border inline-flex items-center gap-1',
                      role.is_system
                        ? 'bg-indigo-50 text-indigo-700 border-indigo-200 dark:bg-indigo-950/40 dark:text-indigo-300 dark:border-indigo-800/40'
                        : 'bg-emerald-50 text-emerald-700 border-emerald-200 dark:bg-emerald-950/40 dark:text-emerald-300 dark:border-emerald-800/40'
                    ]"
                  >
                    <Shield v-if="role.is_system" class="w-3 h-3 text-indigo-500" />
                    <Sparkles v-else class="w-3 h-3 text-emerald-500" />
                    <span>{{ role.is_system ? languageStore.t('system_role', 'System Role') : languageStore.t('custom_role', 'Custom Role') }}</span>
                  </span>
                </td>

                <!-- Entity -->
                <td class="py-4 px-4 text-slate-700 dark:text-slate-300 whitespace-nowrap font-medium text-xs">
                  {{ hotelStore.hotelName || 'Active Property' }}
                </td>

                <!-- Permission (Pill Button with Arrow ->) -->
                <td class="py-4 px-4 whitespace-nowrap">
                  <button
                    @click="openEditModal(role)"
                    class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-blue-50 hover:bg-blue-100 text-blue-700 border border-blue-200 dark:bg-blue-600/15 dark:hover:bg-blue-600/25 dark:text-blue-400 dark:border-blue-500/30 text-xs font-bold transition cursor-pointer"
                    :title="languageStore.t('configure_role', 'Configure role permissions')"
                  >
                    <span>{{ getPermissionCount(role) }} {{ languageStore.t('permissions', 'Permissions') }}</span>
                    <ArrowRight class="w-3 h-3" />
                  </button>
                </td>

                <!-- Assigned Users (Icon + Count Badge) -->
                <td class="py-4 px-4 text-center whitespace-nowrap">
                  <button
                    @click="openUsersModal(role)"
                    class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-300 text-xs font-bold transition cursor-pointer border border-slate-200 dark:border-slate-700"
                    :title="languageStore.t('view_staff', 'View assigned users')"
                  >
                    <User class="w-3.5 h-3.5 text-slate-500 dark:text-slate-400" />
                    <span>{{ role.users_count ?? 0 }}</span>
                  </button>
                </td>

                <!-- State (Active / Inactive Badge) -->
                <td class="py-4 px-4 text-center whitespace-nowrap">
                  <span
                    :class="[
                      'px-2.5 py-0.5 rounded-full text-[11px] font-bold border inline-block',
                      isRoleActive(role)
                        ? 'bg-emerald-50 text-emerald-700 border-emerald-200 dark:bg-emerald-500/10 dark:text-emerald-400 dark:border-emerald-500/30'
                        : 'bg-slate-100 text-slate-600 border-slate-200 dark:bg-slate-800 dark:text-slate-400 dark:border-slate-700'
                    ]"
                  >
                    {{ isRoleActive(role) ? languageStore.t('active', 'Active') : languageStore.t('inactive', 'Inactive') }}
                  </span>
                </td>

                <!-- Action (Three-Dot Menu) -->
                <td class="py-4 px-4 text-right pr-6 whitespace-nowrap relative" data-role-dropdown>
                  <button
                    @click.stop="toggleDropdown(role.id, $event)"
                    class="p-1.5 rounded-lg hover:bg-slate-100 dark:hover:bg-slate-800 text-slate-500 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white transition cursor-pointer"
                    :title="languageStore.t('actions', 'Actions')"
                  >
                    <MoreVertical class="w-4 h-4" />
                  </button>

                  <!-- Floating Three-Dot Dropdown -->
                  <div
                    v-if="activeDropdownRoleId === role.id"
                    class="absolute right-6 top-11 z-50 w-48 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl shadow-xl py-1.5 text-left text-xs text-slate-700 dark:text-slate-300"
                    @click.stop
                  >
                    <button
                      @click="openEditModal(role); closeDropdown()"
                      class="w-full px-3.5 py-2 text-left hover:bg-slate-50 dark:hover:bg-slate-800 flex items-center gap-2.5 transition cursor-pointer font-medium"
                    >
                      <Edit2 class="w-3.5 h-3.5 text-blue-500" />
                      <span>{{ languageStore.t('configure_role', 'Configure Role') }}</span>
                    </button>

                    <button
                      @click="openUsersModal(role); closeDropdown()"
                      class="w-full px-3.5 py-2 text-left hover:bg-slate-50 dark:hover:bg-slate-800 flex items-center gap-2.5 transition cursor-pointer font-medium"
                    >
                      <Users class="w-3.5 h-3.5 text-indigo-500" />
                      <span>{{ languageStore.t('view_staff', 'View Staff') }}</span>
                    </button>

                    <button
                      @click="openCloneModal(role); closeDropdown()"
                      class="w-full px-3.5 py-2 text-left hover:bg-slate-50 dark:hover:bg-slate-800 flex items-center gap-2.5 transition cursor-pointer font-medium"
                    >
                      <Copy class="w-3.5 h-3.5 text-amber-500" />
                      <span>{{ languageStore.t('duplicate_role', 'Duplicate Role') }}</span>
                    </button>

                    <button
                      v-if="role.slug !== 'admin'"
                      @click="toggleRoleActive(role); closeDropdown()"
                      class="w-full px-3.5 py-2 text-left hover:bg-slate-50 dark:hover:bg-slate-800 flex items-center gap-2.5 transition cursor-pointer font-medium"
                    >
                      <Activity class="w-3.5 h-3.5 text-emerald-500" />
                      <span>{{ isRoleActive(role) ? languageStore.t('deactivate_role', 'Deactivate Role') : languageStore.t('activate_role', 'Activate Role') }}</span>
                    </button>

                    <div v-if="!role.is_system" class="my-1 border-t border-slate-200 dark:border-slate-800"></div>

                    <button
                      v-if="!role.is_system"
                      @click="triggerDeleteConfirmation(role); closeDropdown()"
                      class="w-full px-3.5 py-2 text-rose-600 dark:text-rose-400 hover:bg-rose-50 dark:hover:bg-rose-500/10 flex items-center gap-2.5 transition text-left cursor-pointer font-medium"
                    >
                      <Trash2 class="w-3.5 h-3.5 text-rose-500" />
                      <span>{{ languageStore.t('delete_role', 'Delete Role') }}</span>
                    </button>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <!-- Table Footer -->
        <div class="py-3 px-4 bg-slate-50/80 dark:bg-slate-950/50 border-t border-slate-200 dark:border-slate-800 flex items-center justify-between text-xs text-slate-500 dark:text-slate-400">
          <span>{{ languageStore.t('showing', 'Showing') }} <strong>{{ filteredRoles.length }}</strong> {{ languageStore.t('of', 'of') }} <strong>{{ roles.length }}</strong> {{ languageStore.t('roles', 'roles') }}</span>
          <span class="text-[11px]">Tenant: {{ hotelStore.hotelName || 'Active Hotel' }}</span>
        </div>
      </div>

      <!-- VIEW MODE: CARDS GRID (ALTERNATIVE) -->
      <div v-else class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-5">
        <div
          v-for="role in filteredRoles"
          :key="role.id"
          class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-5 shadow-xs hover:shadow-md hover:border-blue-500/40 transition flex flex-col justify-between space-y-4"
        >
          <div class="space-y-3">
            <div class="flex items-start justify-between gap-3">
              <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl bg-blue-500/10 border border-blue-500/20 flex items-center justify-center text-blue-600 dark:text-blue-400">
                  <component :is="getRoleIcon(role.slug || role.name)" class="w-5 h-5" />
                </div>
                <div>
                  <h3 class="text-base font-black text-slate-900 dark:text-white capitalize">{{ role.name }}</h3>
                  <p class="text-xs text-slate-500 dark:text-slate-400 line-clamp-1">{{ role.description || 'Hotel staff position' }}</p>
                </div>
              </div>
              <span
                :class="[
                  'px-2 py-0.5 rounded-full text-[10px] font-bold border',
                  isRoleActive(role)
                    ? 'bg-emerald-50 text-emerald-700 border-emerald-200 dark:bg-emerald-500/10 dark:text-emerald-400 dark:border-emerald-500/30'
                    : 'bg-slate-100 text-slate-600 border-slate-200 dark:bg-slate-800 dark:text-slate-400 dark:border-slate-700'
                ]"
              >
                {{ isRoleActive(role) ? languageStore.t('active', 'Active') : languageStore.t('inactive', 'Inactive') }}
              </span>
            </div>

            <div class="flex items-center justify-between pt-2 text-xs text-slate-500 dark:text-slate-400 border-t border-slate-100 dark:border-slate-800">
              <span class="font-medium">{{ languageStore.t('permissions', 'Permissions') }}: <strong class="text-blue-600 dark:text-blue-400">{{ getPermissionCount(role) }}</strong></span>
              <span class="font-medium">{{ languageStore.t('staff', 'Staff') }}: <strong class="text-slate-900 dark:text-white">{{ role.users_count ?? 0 }}</strong></span>
            </div>
          </div>

          <div class="pt-3 border-t border-slate-100 dark:border-slate-800 flex items-center justify-between gap-2">
            <button
              @click="openEditModal(role)"
              class="flex-1 py-2 px-3 rounded-xl bg-blue-50 hover:bg-blue-100 text-blue-700 border border-blue-200 dark:bg-blue-600/15 dark:hover:bg-blue-600/25 dark:text-blue-400 dark:border-blue-500/30 text-xs font-bold transition cursor-pointer flex items-center justify-center gap-1.5"
            >
              <Edit2 class="w-3.5 h-3.5" />
              <span>{{ languageStore.t('configure', 'Configure') }}</span>
            </button>
            <button
              @click="openUsersModal(role)"
              class="p-2 rounded-xl bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-300 transition cursor-pointer border border-slate-200 dark:border-slate-700"
              :title="languageStore.t('view_staff', 'View Staff')"
            >
              <Users class="w-3.5 h-3.5" />
            </button>
            <button
              @click="openCloneModal(role)"
              class="p-2 rounded-xl bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-300 transition cursor-pointer border border-slate-200 dark:border-slate-700"
              :title="languageStore.t('duplicate_role', 'Clone Role')"
            >
              <Copy class="w-3.5 h-3.5" />
            </button>
          </div>
        </div>
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
        :loading="loadingUsers"
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
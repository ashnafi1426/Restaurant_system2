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
  User,
  MoreVertical,
  Filter,
  Columns,
  Maximize2,
  ArrowRight,
  Monitor,
  ChevronRight,
  AlertTriangle,
  Grid
} from 'lucide-vue-next'

const router = useRouter()
const authStore = useAuthStore()
const hotelStore = useHotelStore()

const roles = ref<Role[]>([])
const permissions = ref<Permission[]>([])
const userSummaries = ref<RbacUserSummary[]>([])
const loading = ref(true)
const isRefreshing = ref(false)
const saving = ref(false)
const loadingPermissions = ref(false)
const errorMessage = ref('')
const successMessage = ref('')

// View mode: 'table' | 'cards' | 'matrix'
const viewMode = ref<'table' | 'cards' | 'matrix'>('table')

// Checkbox selection
const selectedRoleIds = ref<(string | number)[]>([])

// Three-dot action dropdown state
const activeDropdownRoleId = ref<string | number | null>(null)

// Filter menu state
const showFilterMenu = ref(false)

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

const fetchRolesAndPermissions = async (silent = false) => {
  if (!silent && roles.value.length === 0) {
    loading.value = true
  } else {
    isRefreshing.value = true
  }
  errorMessage.value = ''
  try {
    const rolesPromise = rbacService.getRoles()
    const permsPromise = permissions.value.length === 0 ? rbacService.getPermissions() : Promise.resolve({ data: permissions.value })
    const usersPromise = userSummaries.value.length === 0 ? rbacService.getUserRoleSummaries().catch(() => []) : Promise.resolve(userSummaries.value)

    const [rolesData, permsData, usersData] = await Promise.all([
      rolesPromise,
      permsPromise,
      usersPromise
    ])
    roles.value = rolesData || []
    if (permsData?.data) permissions.value = permsData.data
    if (usersData) userSummaries.value = usersData
  } catch (err: any) {
    console.error('[RoleManagement] Fetch error:', err)
    errorMessage.value = err?.response?.data?.message || 'Failed to load system roles and permissions.'
  } finally {
    loading.value = false
    isRefreshing.value = false
  }
}

// Dropdown click outside handler
const handleGlobalClick = () => {
  activeDropdownRoleId.value = null
  showFilterMenu.value = false
}

const toggleDropdown = (roleId: string | number, event: Event) => {
  event.stopPropagation()
  if (activeDropdownRoleId.value === roleId) {
    activeDropdownRoleId.value = null
  } else {
    activeDropdownRoleId.value = roleId
  }
}

const closeDropdown = () => {
  activeDropdownRoleId.value = null
}

const toggleFilterMenu = (event: Event) => {
  event.stopPropagation()
  showFilterMenu.value = !showFilterMenu.value
}

// Checkbox select all & single
const isAllSelected = computed(() => {
  return filteredRoles.value.length > 0 && filteredRoles.value.every(r => selectedRoleIds.value.includes(r.id))
})

const toggleSelectAll = () => {
  if (isAllSelected.value) {
    selectedRoleIds.value = []
  } else {
    selectedRoleIds.value = filteredRoles.value.map(r => r.id)
  }
}

const toggleSelectRole = (id: string | number) => {
  const idx = selectedRoleIds.value.indexOf(id)
  if (idx >= 0) {
    selectedRoleIds.value.splice(idx, 1)
  } else {
    selectedRoleIds.value.push(id)
  }
}

onMounted(() => {
  fetchRolesAndPermissions()
  window.addEventListener('click', handleGlobalClick)
})

onUnmounted(() => {
  window.removeEventListener('click', handleGlobalClick)
})

watch(() => hotelStore.hotelId, () => {
  fetchRolesAndPermissions(true)
})

// Metrics summary calculations (matching the screenshot cards)
const allRolesCount = computed(() => roles.value.length)
const activeRolesCount = computed(() => roles.value.filter(r => r.is_active ?? true).length)
const totalAssignedUsersCount = computed(() => roles.value.reduce((acc, r) => acc + (r.users_count ?? 0), 0))
const unassignedRolesCount = computed(() => roles.value.filter(r => (r.users_count ?? 0) === 0).length)

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
    <div class="min-h-screen bg-[#070D1B] dark:bg-[#070D1B] text-slate-100 p-4 sm:p-6 space-y-6 font-sans">

      <!-- TOP BREADCRUMB -->
      <div class="flex items-center gap-2 text-xs text-slate-400 font-medium">
        <Monitor class="w-4 h-4 text-slate-400" />
        <ChevronRight class="w-3.5 h-3.5 text-slate-600" />
        <span class="text-slate-200 font-semibold">Role</span>
      </div>

      <!-- 4 STAT CARDS (EXACT SCREENSHOT LAYOUT) -->
      <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <!-- Card 1: All Roles -->
        <div class="bg-[#0D1527] border border-slate-800/80 rounded-2xl p-5 flex items-center justify-between shadow-xs">
          <div class="space-y-1">
            <p class="text-xs text-slate-400 font-medium">All Roles</p>
            <h3 class="text-3xl font-extrabold text-white tracking-tight">{{ allRolesCount }}</h3>
          </div>
          <div class="w-12 h-12 rounded-xl bg-blue-500/10 text-blue-400 border border-blue-500/20 flex items-center justify-center shadow-xs">
            <Shield class="w-6 h-6" />
          </div>
        </div>

        <!-- Card 2: Active Roles -->
        <div class="bg-[#0D1527] border border-slate-800/80 rounded-2xl p-5 flex items-center justify-between shadow-xs">
          <div class="space-y-1">
            <p class="text-xs text-slate-400 font-medium">Active Roles</p>
            <h3 class="text-3xl font-extrabold text-white tracking-tight">{{ activeRolesCount }}</h3>
          </div>
          <div class="w-12 h-12 rounded-xl bg-emerald-500/10 text-emerald-400 border border-emerald-500/20 flex items-center justify-center shadow-xs">
            <CheckCircle2 class="w-6 h-6" />
          </div>
        </div>

        <!-- Card 3: Assigned Users -->
        <div class="bg-[#0D1527] border border-slate-800/80 rounded-2xl p-5 flex items-center justify-between shadow-xs">
          <div class="space-y-1">
            <p class="text-xs text-slate-400 font-medium">Assigned Users</p>
            <h3 class="text-3xl font-extrabold text-white tracking-tight">{{ totalAssignedUsersCount }}</h3>
          </div>
          <div class="w-12 h-12 rounded-xl bg-blue-600/15 text-blue-400 border border-blue-600/20 flex items-center justify-center shadow-xs">
            <Users class="w-6 h-6" />
          </div>
        </div>

        <!-- Card 4: Unassigned Roles -->
        <div class="bg-[#0D1527] border border-slate-800/80 rounded-2xl p-5 flex items-center justify-between shadow-xs">
          <div class="space-y-1">
            <p class="text-xs text-slate-400 font-medium">Unassigned Roles</p>
            <h3 class="text-3xl font-extrabold text-white tracking-tight">{{ unassignedRolesCount }}</h3>
          </div>
          <div class="w-12 h-12 rounded-xl bg-amber-500/10 text-amber-500 border border-amber-500/20 flex items-center justify-center shadow-xs">
            <AlertCircle class="w-6 h-6" />
          </div>
        </div>
      </div>

      <!-- TITLE HEADER -->
      <div class="space-y-1">
        <h2 class="text-2xl font-bold text-white tracking-tight">Manage Role</h2>
        <p class="text-xs text-slate-400">Configure and manage roles</p>
      </div>

      <!-- NOTIFICATION BANNERS -->
      <transition enter-active-class="transition duration-200" enter-from-class="opacity-0 -translate-y-2" enter-to-class="opacity-100 translate-y-0">
        <div v-if="successMessage" class="p-3.5 rounded-xl bg-emerald-500/10 border border-emerald-500/30 text-emerald-400 text-xs font-bold flex items-center justify-between">
          <div class="flex items-center gap-2">
            <CheckCircle2 class="w-4 h-4 text-emerald-400 flex-shrink-0" />
            <span>{{ successMessage }}</span>
          </div>
          <button @click="successMessage = ''" class="text-emerald-400 hover:text-white cursor-pointer">
            <X class="w-4 h-4" />
          </button>
        </div>
      </transition>

      <transition enter-active-class="transition duration-200" enter-from-class="opacity-0 -translate-y-2" enter-to-class="opacity-100 translate-y-0">
        <div v-if="errorMessage" class="p-3.5 rounded-xl bg-rose-500/10 border border-rose-500/30 text-rose-400 text-xs font-bold flex items-center justify-between">
          <div class="flex items-center gap-2">
            <AlertCircle class="w-4 h-4 text-rose-400 flex-shrink-0" />
            <span>{{ errorMessage }}</span>
          </div>
          <button @click="errorMessage = ''" class="text-rose-400 hover:text-white cursor-pointer">
            <X class="w-4 h-4" />
          </button>
        </div>
      </transition>

      <!-- ACTION TOOLBAR (MATCHING SCREENSHOT) -->
      <div class="flex flex-col sm:flex-row items-center justify-between gap-3 pt-1">
        <div class="flex items-center gap-3 w-full sm:w-auto">
          <!-- Search input -->
          <div class="relative w-full sm:w-80">
            <Search class="w-4 h-4 absolute left-3.5 top-3 text-slate-500" />
            <input
              v-model="searchQuery"
              type="text"
              placeholder="Search roles..."
              class="w-full pl-10 pr-4 py-2 text-xs bg-[#0D1527] border border-slate-800 rounded-xl text-white placeholder-slate-500 focus:outline-none focus:border-blue-500 transition font-medium"
            />
          </div>

          <!-- Filter button with dropdown menu -->
          <div class="relative">
            <button
              @click.stop="toggleFilterMenu($event)"
              :class="[
                'px-4 py-2 rounded-xl text-xs font-semibold border transition cursor-pointer flex items-center gap-2',
                filterCategory !== 'all' || showFilterMenu
                  ? 'bg-blue-600/20 text-blue-400 border-blue-500/40'
                  : 'bg-[#0D1527] text-slate-300 border-slate-800 hover:bg-slate-800'
              ]"
            >
              <Filter class="w-3.5 h-3.5" />
              <span>Filter</span>
              <span v-if="filterCategory !== 'all'" class="w-2 h-2 rounded-full bg-blue-500"></span>
            </button>

            <!-- Filter popup menu -->
            <div
              v-if="showFilterMenu"
              class="absolute left-0 mt-2 z-50 w-44 bg-[#0D1527] border border-slate-800 rounded-xl shadow-2xl py-1 text-xs"
              @click.stop
            >
              <button
                @click="filterCategory = 'all'; showFilterMenu = false"
                class="w-full px-3.5 py-2 text-left hover:bg-slate-800 text-slate-300 flex items-center justify-between"
              >
                <span>All Roles</span>
                <Check v-if="filterCategory === 'all'" class="w-3.5 h-3.5 text-blue-400" />
              </button>
              <button
                @click="filterCategory = 'system'; showFilterMenu = false"
                class="w-full px-3.5 py-2 text-left hover:bg-slate-800 text-slate-300 flex items-center justify-between"
              >
                <span>System Roles</span>
                <Check v-if="filterCategory === 'system'" class="w-3.5 h-3.5 text-blue-400" />
              </button>
              <button
                @click="filterCategory = 'custom'; showFilterMenu = false"
                class="w-full px-3.5 py-2 text-left hover:bg-slate-800 text-slate-300 flex items-center justify-between"
              >
                <span>Custom Roles</span>
                <Check v-if="filterCategory === 'custom'" class="w-3.5 h-3.5 text-blue-400" />
              </button>
              <button
                @click="filterCategory = 'active'; showFilterMenu = false"
                class="w-full px-3.5 py-2 text-left hover:bg-slate-800 text-slate-300 flex items-center justify-between"
              >
                <span>Active Only</span>
                <Check v-if="filterCategory === 'active'" class="w-3.5 h-3.5 text-blue-400" />
              </button>
              <button
                @click="filterCategory = 'inactive'; showFilterMenu = false"
                class="w-full px-3.5 py-2 text-left hover:bg-slate-800 text-slate-300 flex items-center justify-between"
              >
                <span>Inactive Only</span>
                <Check v-if="filterCategory === 'inactive'" class="w-3.5 h-3.5 text-blue-400" />
              </button>
            </div>
          </div>
        </div>

        <!-- Right Toolbar items -->
        <div class="flex items-center gap-2 w-full sm:w-auto justify-end">
          <!-- Refresh -->
          <button
            @click="fetchRolesAndPermissions(false)"
            class="p-2 rounded-xl bg-[#0D1527] border border-slate-800 hover:bg-slate-800 text-slate-400 hover:text-white transition cursor-pointer"
            title="Refresh"
          >
            <RefreshCw class="w-4 h-4" :class="{ 'animate-spin': loading || isRefreshing }" />
          </button>

          <!-- Columns view toggle -->
          <button
            @click="viewMode = viewMode === 'table' ? 'cards' : 'table'"
            class="p-2 rounded-xl bg-[#0D1527] border border-slate-800 hover:bg-slate-800 text-slate-400 hover:text-white transition cursor-pointer"
            :title="viewMode === 'table' ? 'Switch to Card view' : 'Switch to Table view'"
          >
            <Columns class="w-4 h-4" />
          </button>

          <!-- Permission Matrix quick link -->
          <button
            @click="router.push('/admin/permission-matrix')"
            class="p-2 rounded-xl bg-[#0D1527] border border-slate-800 hover:bg-slate-800 text-slate-400 hover:text-white transition cursor-pointer"
            title="Permission Matrix"
          >
            <Grid class="w-4 h-4" />
          </button>

          <!-- Create New button (Blue) -->
          <button
            @click="openCreateModal"
            class="inline-flex items-center gap-2 px-4 py-2 rounded-xl bg-blue-600 hover:bg-blue-500 text-white font-bold text-xs shadow-md shadow-blue-600/20 transition cursor-pointer whitespace-nowrap"
          >
            <Plus class="w-4 h-4 stroke-[3]" />
            <span>Create New</span>
          </button>
        </div>
      </div>

      <!-- LOADING SKELETON STATE -->
      <div v-if="loading && roles.length === 0" class="bg-[#0D1527] border border-slate-800/80 rounded-2xl p-12 text-center">
        <div class="animate-spin rounded-full h-9 w-9 border-b-2 border-blue-500 mx-auto mb-3"></div>
        <p class="text-xs font-semibold text-slate-400">Loading hotel positions & permissions...</p>
      </div>

      <!-- EMPTY SEARCH STATE -->
      <div v-else-if="filteredRoles.length === 0" class="py-16 text-center bg-[#0D1527] border border-slate-800/80 rounded-2xl space-y-2">
        <ShieldCheck class="w-10 h-10 text-slate-500 mx-auto opacity-40" />
        <h3 class="text-sm font-bold text-slate-200">No roles match your search filters</h3>
        <p class="text-xs text-slate-500 max-w-sm mx-auto">
          Try clearing your search query or switching your active filter.
        </p>
      </div>

      <!-- MAIN DATA TABLE (EXACT SCREENSHOT COLUMNS & THREE-DOT ACTION) -->
      <div v-else-if="viewMode === 'table'" class="bg-[#0D1527] border border-slate-800/80 rounded-2xl shadow-lg overflow-hidden">
        <div class="overflow-x-auto min-h-[350px]">
          <table class="w-full text-left border-collapse text-xs">
            <thead>
              <tr class="bg-[#111C33]/70 border-b border-slate-800 text-[11px] font-semibold text-slate-400 uppercase tracking-wider">
                <!-- Checkbox -->
                <th class="py-3.5 px-4 w-10">
                  <input
                    type="checkbox"
                    :checked="isAllSelected"
                    @change="toggleSelectAll"
                    class="w-4 h-4 rounded bg-slate-900 border-slate-700 text-blue-600 focus:ring-0 cursor-pointer"
                  />
                </th>
                <th class="py-3.5 px-4 font-semibold">Role Name</th>
                <th class="py-3.5 px-4 font-semibold">Description</th>
                <th class="py-3.5 px-4 font-semibold">Entity type</th>
                <th class="py-3.5 px-4 font-semibold">Entity</th>
                <th class="py-3.5 px-4 font-semibold">Permission</th>
                <th class="py-3.5 px-4 font-semibold text-center">Assigned Users</th>
                <th class="py-3.5 px-4 font-semibold text-center">State</th>
                <th class="py-3.5 px-4 font-semibold text-right pr-6">Action</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-800/60 text-xs">
              <tr
                v-for="role in filteredRoles"
                :key="role.id"
                class="hover:bg-slate-800/30 transition-colors group"
              >
                <!-- Checkbox -->
                <td class="py-4 px-4 w-10">
                  <input
                    type="checkbox"
                    :checked="selectedRoleIds.includes(role.id)"
                    @change="toggleSelectRole(role.id)"
                    class="w-4 h-4 rounded bg-slate-900 border-slate-700 text-blue-600 focus:ring-0 cursor-pointer"
                  />
                </td>

                <!-- Role Name -->
                <td class="py-4 px-4 font-semibold text-white whitespace-nowrap">
                  {{ role.name }}
                </td>

                <!-- Description -->
                <td class="py-4 px-4 text-slate-400 max-w-xs truncate font-medium">
                  {{ role.description || 'Hotel management role' }}
                </td>

                <!-- Entity type -->
                <td class="py-4 px-4 whitespace-nowrap">
                  <span class="px-2.5 py-1 rounded-md text-[11px] font-semibold bg-[#122347] text-blue-300 border border-blue-800/40">
                    {{ role.is_system ? 'System Role' : 'Custom Role' }}
                  </span>
                </td>

                <!-- Entity -->
                <td class="py-4 px-4 text-slate-300 whitespace-nowrap font-medium">
                  {{ hotelStore.hotelName || 'Hotel Group' }}
                </td>

                <!-- Permission (Pill Button with Arrow ->) -->
                <td class="py-4 px-4 whitespace-nowrap">
                  <button
                    @click="openEditModal(role)"
                    class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-blue-600/15 hover:bg-blue-600/25 text-blue-400 border border-blue-500/30 text-xs font-bold transition cursor-pointer"
                    title="Configure role permissions"
                  >
                    <span>{{ role.permissions_count ?? role.permissions?.length ?? 0 }} Permissions</span>
                    <ArrowRight class="w-3 h-3" />
                  </button>
                </td>

                <!-- Assigned Users (Icon + Count Badge) -->
                <td class="py-4 px-4 text-center whitespace-nowrap">
                  <button
                    @click="openUsersModal(role)"
                    class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg bg-slate-800/80 hover:bg-slate-700 text-slate-300 text-xs font-semibold transition cursor-pointer"
                    title="View assigned users"
                  >
                    <User class="w-3.5 h-3.5 text-slate-400" />
                    <span>{{ role.users_count ?? 0 }}</span>
                  </button>
                </td>

                <!-- State (Active / Inactive Badge) -->
                <td class="py-4 px-4 text-center whitespace-nowrap">
                  <span
                    :class="[
                      'px-2.5 py-0.5 rounded-full text-xs font-bold border inline-block',
                      (role.is_active ?? true)
                        ? 'bg-emerald-500/10 text-emerald-400 border-emerald-500/30'
                        : 'bg-slate-800 text-slate-400 border-slate-700'
                    ]"
                  >
                    {{ (role.is_active ?? true) ? 'Active' : 'Inactive' }}
                  </span>
                </td>

                <!-- Action (Three-Dot Menu) -->
                <td class="py-4 px-4 text-right pr-6 whitespace-nowrap relative">
                  <button
                    @click.stop="toggleDropdown(role.id, $event)"
                    class="p-1.5 rounded-lg hover:bg-slate-800 text-slate-400 hover:text-white transition cursor-pointer"
                    title="Actions"
                  >
                    <MoreVertical class="w-4 h-4" />
                  </button>

                  <!-- Floating Three-Dot Dropdown -->
                  <div
                    v-if="activeDropdownRoleId === role.id"
                    class="absolute right-6 top-11 z-50 w-48 bg-[#0D1527] border border-slate-800 rounded-xl shadow-2xl py-1.5 text-left"
                    @click.stop
                  >
                    <button
                      @click="openEditModal(role); closeDropdown()"
                      class="w-full px-3.5 py-2 text-xs text-slate-200 hover:bg-slate-800 flex items-center gap-2.5 transition text-left cursor-pointer font-medium"
                    >
                      <Edit2 class="w-3.5 h-3.5 text-blue-400" />
                      <span>Configure Role</span>
                    </button>

                    <button
                      @click="openUsersModal(role); closeDropdown()"
                      class="w-full px-3.5 py-2 text-xs text-slate-200 hover:bg-slate-800 flex items-center gap-2.5 transition text-left cursor-pointer font-medium"
                    >
                      <Users class="w-3.5 h-3.5 text-indigo-400" />
                      <span>View Staff</span>
                    </button>

                    <button
                      @click="openCloneModal(role); closeDropdown()"
                      class="w-full px-3.5 py-2 text-xs text-slate-200 hover:bg-slate-800 flex items-center gap-2.5 transition text-left cursor-pointer font-medium"
                    >
                      <Copy class="w-3.5 h-3.5 text-amber-400" />
                      <span>Duplicate Role</span>
                    </button>

                    <button
                      v-if="role.slug !== 'admin'"
                      @click="toggleRoleActive(role); closeDropdown()"
                      class="w-full px-3.5 py-2 text-xs text-slate-200 hover:bg-slate-800 flex items-center gap-2.5 transition text-left cursor-pointer font-medium"
                    >
                      <Activity class="w-3.5 h-3.5 text-emerald-400" />
                      <span>{{ (role.is_active ?? true) ? 'Deactivate Role' : 'Activate Role' }}</span>
                    </button>

                    <div v-if="!role.is_system" class="my-1 border-t border-slate-800"></div>

                    <button
                      v-if="!role.is_system"
                      @click="triggerDeleteConfirmation(role); closeDropdown()"
                      class="w-full px-3.5 py-2 text-xs text-rose-400 hover:bg-rose-500/10 flex items-center gap-2.5 transition text-left cursor-pointer font-medium"
                    >
                      <Trash2 class="w-3.5 h-3.5 text-rose-400" />
                      <span>Delete Role</span>
                    </button>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <!-- Table Footer -->
        <div class="py-3 px-4 bg-[#111C33]/50 border-t border-slate-800 flex items-center justify-between text-xs text-slate-400">
          <span>Showing <strong>{{ filteredRoles.length }}</strong> of <strong>{{ roles.length }}</strong> roles</span>
          <span class="text-[11px] text-slate-500">Tenant: {{ hotelStore.hotelName || 'Active Hotel' }}</span>
        </div>
      </div>

      <!-- VIEW MODE: CARDS GRID (ALTERNATIVE) -->
      <div v-else-if="viewMode === 'cards'" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-5">
        <div
          v-for="role in filteredRoles"
          :key="role.id"
          class="bg-[#0D1527] border border-slate-800/80 rounded-2xl p-5 shadow-xs hover:border-slate-700 transition flex flex-col justify-between space-y-4"
        >
          <div class="space-y-3">
            <div class="flex items-start justify-between gap-3">
              <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl bg-blue-500/10 border border-blue-500/20 flex items-center justify-center text-blue-400">
                  <component :is="getRoleIcon(role.slug || role.name)" class="w-5 h-5" />
                </div>
                <div>
                  <h3 class="text-base font-bold text-white capitalize">{{ role.name }}</h3>
                  <p class="text-xs text-slate-400 line-clamp-1">{{ role.description || 'Hotel staff position' }}</p>
                </div>
              </div>
              <span
                :class="[
                  'px-2 py-0.5 rounded-full text-[10px] font-bold border',
                  (role.is_active ?? true) ? 'bg-emerald-500/10 text-emerald-400 border-emerald-500/30' : 'bg-slate-800 text-slate-400 border-slate-700'
                ]"
              >
                {{ (role.is_active ?? true) ? 'Active' : 'Inactive' }}
              </span>
            </div>

            <div class="flex items-center justify-between pt-2 text-xs text-slate-400 border-t border-slate-800">
              <span class="font-medium">Permissions: <strong class="text-blue-400">{{ role.permissions_count ?? role.permissions?.length ?? 0 }}</strong></span>
              <span class="font-medium">Staff: <strong class="text-white">{{ role.users_count ?? 0 }}</strong></span>
            </div>
          </div>

          <div class="pt-3 border-t border-slate-800 flex items-center justify-between gap-2">
            <button
              @click="openEditModal(role)"
              class="flex-1 py-2 px-3 rounded-xl bg-blue-600/15 hover:bg-blue-600/25 text-blue-400 border border-blue-500/30 text-xs font-bold transition cursor-pointer flex items-center justify-center gap-1.5"
            >
              <Edit2 class="w-3.5 h-3.5" />
              <span>Configure</span>
            </button>
            <button
              @click="openUsersModal(role)"
              class="p-2 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-300 transition cursor-pointer"
              title="View Staff"
            >
              <Users class="w-3.5 h-3.5" />
            </button>
            <button
              @click="openCloneModal(role)"
              class="p-2 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-300 transition cursor-pointer"
              title="Clone Role"
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
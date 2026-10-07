<script setup lang="ts">
import { ref, onMounted, computed, watch } from 'vue'
import DashboardLayout from '../../../Layouts/DashboardLayout.vue'
import { rbacService } from '../../../services/rbacService'
import { useHotelStore } from '../../../stores/hotelStore'
import { useAuthStore } from '@/stores/auth'
import type { RbacUserSummary, Role } from '../../../types/rbacTypes'
import {
  Users,
  ShieldCheck,
  RefreshCw,
  Edit2,
  CheckCircle2,
  AlertCircle,
  XCircle,
  Clock,
  Search,
  Key,
  Layers,
  Sparkles,
  SlidersHorizontal,
  Check,
  Zap,
  Filter,
  ChevronLeft,
  ChevronRight,
  MoreVertical,
  Loader2,
  Minimize2,
  Maximize2,
  RotateCcw,
  X
} from 'lucide-vue-next'

const userSummaries = ref<RbacUserSummary[]>([])
const roles = ref<Role[]>([])
const loading = ref(true)
const isFilterOpen = ref(false)
const isFullscreen = ref(false)
const errorMessage = ref('')
const successMessage = ref('')
const searchFilter = ref('')
const roleFilter = ref('all')
const openActionMenuId = ref<number | null>(null)

const toggleFilter = () => {
  isFilterOpen.value = !isFilterOpen.value
}

const toggleFullscreen = () => {
  isFullscreen.value = !isFullscreen.value
}

const resetFilters = () => {
  searchFilter.value = ''
  roleFilter.value = 'all'
}

// Pagination state
const currentPage = ref(1)
const perPage = ref(10)

// Assign Roles Modal State
const showAssignModal = ref(false)
const selectedUserForRoles = ref<RbacUserSummary | null>(null)
const selectedRoleIds = ref<number[]>([])
const primaryRoleId = ref<number | null>(null)

// Manage Access (Direct Permissions) Modal State
const showAccessModal = ref(false)
const accessLoading = ref(false)
const selectedUserForAccess = ref<RbacUserSummary | null>(null)
const accessData = ref<{
  user: any
  primary_role: any
  role_permissions: any[]
  direct_permissions: any[]
  effective_permissions: string[]
  system_permissions_grouped: any[]
} | null>(null)

const selectedDirectPermissionIds = ref<number[]>([])
const modalPermissionSearch = ref('')
const startsAt = ref<string>('')
const expiresAt = ref<string>('')

const fetchData = async () => {
  loading.value = true
  errorMessage.value = ''
  try {
    const [usersData, rolesData] = await Promise.all([
      rbacService.getUserRoleSummaries(),
      rbacService.getRoles()
    ])
    userSummaries.value = usersData
    roles.value = rolesData
  } catch (err: any) {
    console.error('[UserRoleAssignment] Fetch data error:', err)
    errorMessage.value = err?.response?.data?.message || 'Failed to load user access data.'
  } finally {
    loading.value = false
  }
}

const hotelStore = useHotelStore()

onMounted(() => {
  fetchData()
})

// Re-fetch users whenever the selected hotel in the Navbar changes
watch(() => hotelStore.hotelId, () => {
  fetchData()
})

const filteredUsers = computed(() => {
  let list = userSummaries.value

  if (roleFilter.value !== 'all') {
    list = list.filter(u =>
      (u.primary_role_slug || '').toLowerCase() === roleFilter.value.toLowerCase() ||
      (u.legacy_role || '').toLowerCase() === roleFilter.value.toLowerCase()
    )
  }

  if (!searchFilter.value) return list
  const q = searchFilter.value.toLowerCase()
  return list.filter(u =>
    u.full_name.toLowerCase().includes(q) ||
    u.email.toLowerCase().includes(q) ||
    (u.primary_role_name && u.primary_role_name.toLowerCase().includes(q))
  )
})

const total = computed(() => filteredUsers.value.length)
const lastPage = computed(() => Math.ceil(total.value / perPage.value) || 1)

const paginatedUsers = computed(() => {
  const start = (currentPage.value - 1) * perPage.value
  const end = start + perPage.value
  return filteredUsers.value.slice(start, end)
})

const showingFrom = computed(() => {
  if (total.value === 0) return 0
  return (currentPage.value - 1) * perPage.value + 1
})

const showingTo = computed(() => {
  return Math.min(currentPage.value * perPage.value, total.value)
})

const paginationPages = computed(() => {
  const pages: number[] = []
  const max = lastPage.value
  const cur = currentPage.value

  for (let i = Math.max(1, cur - 2); i <= Math.min(max, cur + 2); i++) {
    pages.push(i)
  }
  return pages
})

watch([searchFilter, roleFilter], () => {
  currentPage.value = 1
})

const changePerPage = (event: Event) => {
  const target = event.target as HTMLSelectElement
  perPage.value = Number(target.value)
  currentPage.value = 1
}

const goToPage = (p: number) => {
  if (p >= 1 && p <= lastPage.value) {
    currentPage.value = p
  }
}

const prevPage = () => {
  if (currentPage.value > 1) {
    currentPage.value--
  }
}

const nextPage = () => {
  if (currentPage.value < lastPage.value) {
    currentPage.value++
  }
}

// Role Assignment Modal logic
const openAssignModal = (user: RbacUserSummary) => {
  selectedUserForRoles.value = user
  selectedRoleIds.value = user.roles.map(r => r.id)
  const primary = user.roles.find(r => r.is_primary) || user.roles[0]
  primaryRoleId.value = primary ? primary.id : null
  showAssignModal.value = true
}

const toggleRoleSelection = (roleId: number) => {
  const index = selectedRoleIds.value.indexOf(roleId)
  if (index === -1) {
    selectedRoleIds.value.push(roleId)
    if (!primaryRoleId.value) {
      primaryRoleId.value = roleId
    }
  } else {
    selectedRoleIds.value.splice(index, 1)
    if (primaryRoleId.value === roleId) {
      primaryRoleId.value = selectedRoleIds.value[0] || null
    }
  }
}

const saveUserRoles = async () => {
  if (!selectedUserForRoles.value || selectedRoleIds.value.length === 0) return
  loading.value = true
  errorMessage.value = ''
  try {
    await rbacService.assignUserRoles(
      selectedUserForRoles.value.id,
      selectedRoleIds.value,
      primaryRoleId.value || selectedRoleIds.value[0]
    )
    successMessage.value = `Roles updated successfully for ${selectedUserForRoles.value.full_name}`
    showAssignModal.value = false
    await fetchData()
    setTimeout(() => { successMessage.value = '' }, 3500)
  } catch (err: any) {
    console.error('[UserRoleAssignment] Save roles error:', err)
    errorMessage.value = err?.response?.data?.message || 'Failed to assign user roles.'
  } finally {
    loading.value = false
  }
}

// Manage Access (Direct Permissions) Modal logic
const openAccessModal = async (user: RbacUserSummary) => {
  selectedUserForAccess.value = user
  showAccessModal.value = true
  accessLoading.value = true
  modalPermissionSearch.value = ''
  startsAt.value = ''
  expiresAt.value = ''

  try {
    const data = await rbacService.getUserDirectPermissions(user.id)
    accessData.value = data
    // Initialize selected direct permission IDs
    selectedDirectPermissionIds.value = data.direct_permissions.map((dp: any) => dp.permission_id)
  } catch (err: any) {
    console.error('[UserRoleAssignment] Open access modal error:', err)
    errorMessage.value = err?.response?.data?.message || 'Failed to load user access details.'
    showAccessModal.value = false
  } finally {
    accessLoading.value = false
  }
}

const inheritedPermissionSlugs = computed(() => {
  if (!accessData.value) return new Set<string>()
  return new Set(accessData.value.role_permissions.map((p: any) => p.slug.toLowerCase()))
})

const isPermissionInherited = (slug: string) => {
  return inheritedPermissionSlugs.value.has(slug.toLowerCase())
}

const toggleDirectPermission = (permissionId: number, slug: string) => {
  if (isPermissionInherited(slug)) return // Role permissions inherited automatically
  const idx = selectedDirectPermissionIds.value.indexOf(permissionId)
  if (idx === -1) {
    selectedDirectPermissionIds.value.push(permissionId)
  } else {
    selectedDirectPermissionIds.value.splice(idx, 1)
  }
}

const selectAllInModule = (permissions: any[]) => {
  permissions.forEach(p => {
    if (!isPermissionInherited(p.slug) && !selectedDirectPermissionIds.value.includes(p.id)) {
      selectedDirectPermissionIds.value.push(p.id)
    }
  })
}

const clearModulePermissions = (permissions: any[]) => {
  const idsToRemove = new Set(permissions.map(p => p.id))
  selectedDirectPermissionIds.value = selectedDirectPermissionIds.value.filter(id => !idsToRemove.has(id))
}

const moduleDisplayNames: Record<string, { title: string; color: string }> = {
  rooms: { title: 'ROOM MANAGEMENT', color: 'bg-emerald-500' },
  reservations: { title: 'RESERVATIONS', color: 'bg-blue-500' },
  orders: { title: 'ORDERS MANAGEMENT', color: 'bg-amber-500' },
  menu: { title: 'MENU MANAGEMENT', color: 'bg-indigo-500' },
  payments: { title: 'PAYMENTS & BILLING', color: 'bg-purple-500' },
  users: { title: 'USERS MANAGEMENT', color: 'bg-rose-500' },
  guests: { title: 'GUESTS MANAGEMENT', color: 'bg-teal-500' },
  tables: { title: 'RESTAURANT TABLES', color: 'bg-cyan-500' },
  kitchen: { title: 'KITCHEN OPERATIONS', color: 'bg-orange-500' },
  delivery: { title: 'DELIVERY & ROOM SERVICE', color: 'bg-sky-500' },
  reports: { title: 'REPORTS & ANALYTICS', color: 'bg-violet-500' },
  roles: { title: 'ROLES MANAGEMENT', color: 'bg-yellow-500' },
  permissions: { title: 'PERMISSIONS CATALOG', color: 'bg-pink-500' },
  checkin: { title: 'CHECK-IN MANAGEMENT', color: 'bg-emerald-600' },
  checkout: { title: 'CHECK-OUT MANAGEMENT', color: 'bg-rose-600' },
  notifications: { title: 'NOTIFICATIONS', color: 'bg-blue-600' },
  audit_logs: { title: 'SECURITY AUDIT LOGS', color: 'bg-slate-600' },
}

const getModuleInfo = (key: string) => {
  const norm = String(key || '').toLowerCase().trim()
  if (moduleDisplayNames[norm]) {
    return moduleDisplayNames[norm]
  }
  const cleanName = norm.replace(/_/g, ' ').toUpperCase()
  const title = cleanName.includes('MANAGEMENT') ? cleanName : `${cleanName} MANAGEMENT`
  return { title, color: 'bg-amber-500' }
}

const applyPresetPackage = (packageName: string) => {
  if (!accessData.value) return

  const allPerms: any[] = []
  accessData.value.system_permissions_grouped.forEach(g => {
    allPerms.push(...g.permissions)
  })

  let targetSlugs: string[] = []
  if (packageName === 'waiter') {
    targetSlugs = ['orders.view', 'orders.accept', 'orders.deliver', 'delivery.view', 'delivery.accept', 'delivery.deliver', 'tables.view', 'menu.view']
  } else if (packageName === 'kitchen') {
    targetSlugs = ['kitchen.view', 'kitchen.accept', 'kitchen.prepare', 'kitchen.mark_ready', 'orders.view', 'orders.update_status', 'menu.view']
  } else if (packageName === 'cashier') {
    targetSlugs = ['payments.view', 'payments.create', 'payments.refund', 'orders.view', 'invoices.view', 'invoices.create']
  }

  targetSlugs.forEach(slug => {
    const perm = allPerms.find(p => p.slug.toLowerCase() === slug.toLowerCase())
    if (perm && !isPermissionInherited(perm.slug) && !selectedDirectPermissionIds.value.includes(perm.id)) {
      selectedDirectPermissionIds.value.push(perm.id)
    }
  })
}

const filteredGroupedPermissions = computed(() => {
  if (!accessData.value) return []
  if (!modalPermissionSearch.value) return accessData.value.system_permissions_grouped

  const q = modalPermissionSearch.value.toLowerCase()
  return accessData.value.system_permissions_grouped.map(group => {
    const matchingPerms = group.permissions.filter((p: any) =>
      p.name.toLowerCase().includes(q) ||
      p.slug.toLowerCase().includes(q) ||
      (p.description && p.description.toLowerCase().includes(q))
    )
    return {
      ...group,
      permissions: matchingPerms
    }
  }).filter(group => group.permissions.length > 0)
})

const saveAccessPermissions = async () => {
  if (!selectedUserForAccess.value) return
  accessLoading.value = true
  errorMessage.value = ''
  try {
    await rbacService.saveUserDirectPermissions(
      selectedUserForAccess.value.id,
      selectedDirectPermissionIds.value,
      {
        starts_at: startsAt.value || null,
        expires_at: expiresAt.value || null,
      }
    )

    successMessage.value = `Permissions successfully saved for ${selectedUserForAccess.value.full_name}. Primary role remains ${selectedUserForAccess.value.primary_role_name || 'unchanged'}.`
    showAccessModal.value = false
    const authStore = useAuthStore()
    await authStore.fetchCurrentUser()
    window.dispatchEvent(new CustomEvent('permissions-updated'))
    await fetchData()
    setTimeout(() => { successMessage.value = '' }, 4000)
  } catch (err: any) {
    console.error('[UserRoleAssignment] Save permissions error:', err)
    errorMessage.value = err?.response?.data?.message || 'Failed to save direct permissions.'
  } finally {
    accessLoading.value = false
  }
}
</script>

<template>
  <DashboardLayout>
    <template #header>
      <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 py-4 border-b border-slate-200 dark:border-slate-800">
        <div class="space-y-1">
          <div class="flex items-center gap-2">
            <span class="p-2 rounded-xl bg-amber-500/10 text-amber-600 dark:text-amber-400">
              <ShieldCheck class="w-5 h-5" />
            </span>
            <h1 class="text-xl sm:text-2xl font-black tracking-tight text-slate-900 dark:text-white">
              Staff Access & Permissions Management
            </h1>
          </div>
          <p class="text-xs sm:text-sm text-slate-500 dark:text-slate-400 font-medium">
            Manage primary staff roles and assign direct additional permissions without changing their job responsibilities.
          </p>
        </div>

        <div class="flex items-center gap-2">
          <button
            @click="fetchData"
            class="p-2.5 rounded-xl border border-slate-200 dark:border-slate-800 hover:bg-slate-100 dark:hover:bg-slate-800 text-slate-600 dark:text-slate-300 transition"
            title="Refresh Data"
          >
            <RefreshCw class="w-4 h-4" :class="{ 'animate-spin': loading }" />
          </button>
        </div>
      </div>
    </template>

    <div class="py-6 space-y-6">
      <!-- Banners -->
      <div v-if="successMessage" class="p-4 rounded-2xl bg-emerald-500/10 border border-emerald-500/30 text-emerald-700 dark:text-emerald-300 text-xs font-semibold flex items-center gap-2">
        <CheckCircle2 class="w-4 h-4 text-emerald-500 flex-shrink-0" />
        <span>{{ successMessage }}</span>
      </div>

      <div v-if="errorMessage" class="p-4 rounded-2xl bg-rose-500/10 border border-rose-500/30 text-rose-700 dark:text-rose-300 text-xs font-semibold flex items-center gap-2">
        <AlertCircle class="w-4 h-4 text-rose-500 flex-shrink-0" />
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
              v-model="searchFilter"
              type="text"
              placeholder="Search staff by name, email, phone, role..."
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
            @click="fetchData"
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
          <div class="grid grid-cols-1 sm:grid-cols-2 gap-3.5 sm:gap-4">
            <!-- Role Filter -->
            <div>
              <label class="mb-1.5 block text-xs font-semibold text-slate-600 dark:text-slate-300">
                Staff Role
              </label>
              <select
                v-model="roleFilter"
                class="w-full rounded-xl border border-slate-200 dark:border-[#1e3455] bg-slate-50/80 dark:bg-[#13233c] text-slate-900 dark:text-white px-3.5 py-2 text-xs sm:text-sm focus:ring-2 focus:ring-blue-500/40 focus:border-blue-500 dark:focus:border-blue-400 transition cursor-pointer font-medium outline-none"
              >
                <option value="all">All Roles</option>
                <option v-for="r in roles" :key="r.id" :value="r.slug || r.name">
                  {{ r.name }}
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
                <span>Reset Filters</span>
              </button>
            </div>
          </div>
        </div>
      </Transition>

      <!-- Staff Table -->
      <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-3xl overflow-hidden shadow-xs">
        <div class="overflow-x-auto w-full">
          <table class="w-full text-left border-collapse">
            <thead>
              <tr class="bg-slate-50 dark:bg-slate-950/80 border-b border-slate-200 dark:border-slate-800 text-[10px] font-black uppercase text-slate-500 dark:text-slate-400 tracking-wider">
                <th class="px-3 py-2.5 whitespace-nowrap">Staff Member</th>
                <th class="px-3 py-2.5 whitespace-nowrap">Primary Role</th>
                <th class="px-3 py-2.5 whitespace-nowrap">Direct Permissions</th>
                <th class="px-3 py-2.5 text-center whitespace-nowrap">Capabilities</th>
                <th class="px-3 py-2.5 text-right whitespace-nowrap pr-4">Actions</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-100 dark:divide-slate-800/60 text-xs">
              <!-- Loading Spinner State -->
              <tr v-if="loading">
                <td colspan="5" class="px-6 py-20 text-center">
                  <div class="flex flex-col items-center justify-center gap-3">
                    <Loader2 class="w-8 h-8 text-amber-600 dark:text-amber-400 animate-spin" />
                    <div class="space-y-0.5">
                      <p class="text-xs sm:text-sm font-extrabold text-slate-800 dark:text-slate-200">Loading Staff Roles...</p>
                      <p class="text-[11px] text-slate-500 dark:text-slate-400">Fetching permissions and access configurations</p>
                    </div>
                  </div>
                </td>
              </tr>

              <!-- Data Rows -->
              <template v-else>
                <tr v-for="user in paginatedUsers" :key="user.id" class="hover:bg-slate-50/70 dark:hover:bg-slate-800/40 transition">
                  <!-- Staff Member -->
                  <td class="px-3 py-2.5 whitespace-nowrap">
                    <div class="flex items-center gap-2 max-w-[180px]">
                      <div class="w-6 h-6 rounded-full bg-amber-500/10 text-amber-600 dark:bg-amber-500/20 dark:text-amber-400 border border-amber-500/20 font-black text-[10px] flex items-center justify-center flex-shrink-0">
                        {{ (user.full_name?.[0] || 'S').toUpperCase() }}
                      </div>
                      <div class="min-w-0 flex-1">
                        <div class="font-extrabold text-slate-900 dark:text-white text-xs truncate">
                          {{ user.full_name }}
                        </div>
                        <div class="text-[9px] text-slate-500 dark:text-slate-400 truncate">
                          {{ user.email }}
                        </div>
                      </div>
                    </div>
                  </td>

                  <!-- Primary Role Badge -->
                  <td class="px-3 py-2.5 whitespace-nowrap">
                    <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-lg text-[10px] font-black bg-amber-500/10 border border-amber-500/30 text-amber-700 dark:text-amber-300 uppercase tracking-wider">
                      <Users class="w-3 h-3 text-amber-500" />
                      {{ user.primary_role_name || user.legacy_role || 'Staff' }}
                    </span>
                  </td>

                  <!-- Direct Permissions Badge -->
                  <td class="px-3 py-2.5 whitespace-nowrap">
                    <div v-if="user.direct_permissions_count && user.direct_permissions_count > 0" class="inline-flex items-center gap-1 px-2 py-0.5 rounded-lg bg-blue-500/10 border border-blue-500/30 text-blue-700 dark:text-blue-300 font-extrabold text-[10px]">
                      <Sparkles class="w-3 h-3 text-blue-500" />
                      <span>+{{ user.direct_permissions_count }} direct</span>
                    </div>
                    <span v-else class="text-[10px] text-slate-400 font-medium">Standard role</span>
                  </td>

                  <!-- Effective Permissions Count -->
                  <td class="px-3 py-2.5 text-center whitespace-nowrap font-bold text-emerald-600 dark:text-emerald-400">
                    <span class="px-2 py-0.5 rounded-lg bg-emerald-500/10 border border-emerald-500/20 text-[10px] font-extrabold">
                      {{ user.effective_permissions_count }} permissions
                    </span>
                  </td>

                  <!-- Actions -->
                  <td class="px-3 py-2.5 text-right whitespace-nowrap pr-4">
                    <div class="flex items-center justify-end gap-1.5">
                      <button
                        @click="openAccessModal(user)"
                        class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg bg-blue-600 hover:bg-blue-700 text-white font-bold text-[11px] shadow-sm transition cursor-pointer"
                        title="Manage Direct Permissions"
                      >
                        <Key class="w-3 h-3" />
                        <span>Manage Access</span>
                      </button>

                      <button
                        @click="openAssignModal(user)"
                        class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 dark:hover:bg-slate-700 text-slate-800 dark:text-slate-200 font-bold text-[11px] transition cursor-pointer border border-slate-200 dark:border-slate-700"
                        title="Change Primary/Secondary Role"
                      >
                        <Edit2 class="w-3 h-3" />
                        <span>Roles</span>
                      </button>
                    </div>
                  </td>
                </tr>

                <!-- Empty State -->
                <tr v-if="filteredUsers.length === 0">
                  <td colspan="5" class="p-12 text-center text-slate-500 dark:text-slate-400 text-xs font-bold">
                    No staff members match your search query or role filter.
                  </td>
                </tr>
              </template>
            </tbody>
          </table>
        </div>

        <!-- Pagination Bar with 5, 10, 20, 50 Options -->
        <div
          v-if="filteredUsers.length > 0"
          class="flex flex-col sm:flex-row items-center justify-between gap-4 border-t border-slate-200 dark:border-slate-800 bg-slate-50/80 dark:bg-slate-950 p-4 text-xs font-sans"
        >
          <!-- Left Side: Per Page Selector & Showing Count -->
          <div class="flex flex-wrap items-center gap-4 text-slate-600 dark:text-slate-400">
            <div class="flex items-center gap-2">
              <span class="font-bold text-slate-700 dark:text-slate-300">Items per page:</span>
              <select
                :value="perPage"
                @change="changePerPage"
                class="px-2.5 py-1.5 rounded-xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 text-slate-900 dark:text-white font-black focus:outline-none focus:border-amber-500 cursor-pointer shadow-2xs"
              >
                <option :value="5">5</option>
                <option :value="10">10</option>
                <option :value="20">20</option>
                <option :value="50">50</option>
              </select>
            </div>

            <div class="text-xs font-medium">
              Showing <span class="font-extrabold text-slate-900 dark:text-white">{{ showingFrom }}</span> to
              <span class="font-extrabold text-slate-900 dark:text-white">{{ showingTo }}</span> of
              <span class="font-extrabold text-slate-900 dark:text-white">{{ total }}</span> staff members
            </div>
          </div>

          <!-- Right Side: Page Controls -->
          <div class="flex items-center gap-1.5">
            <button
              @click="prevPage"
              :disabled="currentPage <= 1"
              class="p-2 rounded-xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 text-slate-700 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800 disabled:opacity-40 disabled:cursor-not-allowed transition cursor-pointer flex items-center gap-1 font-bold"
              title="Previous Page"
            >
              <ChevronLeft class="w-4 h-4" />
              <span class="hidden sm:inline">Prev</span>
            </button>

            <div class="flex items-center gap-1">
              <button
                v-for="p in paginationPages"
                :key="p"
                @click="goToPage(p)"
                :class="[
                  'w-8 h-8 rounded-xl font-black text-xs transition cursor-pointer flex items-center justify-center border',
                  currentPage === p
                    ? 'bg-amber-500 border-amber-500 text-slate-950 font-black shadow-md shadow-amber-500/20'
                    : 'bg-white dark:bg-slate-900 border-slate-200 dark:border-slate-800 text-slate-700 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800'
                ]"
              >
                {{ p }}
              </button>
            </div>

            <button
              @click="nextPage"
              :disabled="currentPage >= lastPage"
              class="p-2 rounded-xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 text-slate-700 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800 disabled:opacity-40 disabled:cursor-not-allowed transition cursor-pointer flex items-center gap-1 font-bold"
              title="Next Page"
            >
              <span class="hidden sm:inline">Next</span>
              <ChevronRight class="w-4 h-4" />
            </button>
          </div>
        </div>
      </div>

      <!-- ========================================================================= -->
      <!-- MANAGE ACCESS (DIRECT PERMISSIONS) MODAL -->
      <!-- ========================================================================= -->
      <div v-if="showAccessModal" class="fixed inset-0 z-50 flex items-center justify-center p-3 sm:p-6 bg-black/60 backdrop-blur-xs">
        <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-3xl max-w-4xl w-full max-h-[92vh] flex flex-col shadow-2xl overflow-hidden">
          
          <!-- Modal Header -->
          <div class="flex items-center justify-between p-5 border-b border-slate-200 dark:border-slate-800 bg-slate-50/50 dark:bg-slate-950/50">
            <div class="space-y-1">
              <div class="flex items-center gap-2">
                <span class="p-2 rounded-xl bg-blue-500/10 text-blue-600 dark:text-blue-400">
                  <Key class="w-5 h-5" />
                </span>
                <h2 class="text-lg font-black text-slate-900 dark:text-white">
                  Manage Access: {{ selectedUserForAccess?.full_name }}
                </h2>
              </div>
              <div class="flex items-center gap-2 text-xs font-semibold text-slate-500">
                <span>Primary Role:</span>
                <span class="px-2 py-0.5 rounded-lg bg-amber-500/10 text-amber-700 dark:text-amber-300 font-black uppercase">
                  {{ selectedUserForAccess?.primary_role_name || 'Staff' }}
                </span>
                <span class="text-slate-400">• Employee main job responsibility remains unchanged</span>
              </div>
            </div>

            <button @click="showAccessModal = false" class="p-2 text-slate-400 hover:text-slate-600 dark:hover:text-slate-200">
              <XCircle class="w-6 h-6" />
            </button>
          </div>

          <!-- Loading Indicator -->
          <div v-if="accessLoading" class="p-12 text-center text-slate-400 space-y-3">
            <RefreshCw class="w-8 h-8 animate-spin mx-auto text-blue-500" />
            <p class="text-xs font-bold">Loading permission structure...</p>
          </div>

          <!-- Modal Body -->
          <div v-else class="flex-1 overflow-y-auto p-5 sm:p-6 space-y-6">
            
            <!-- Information Banner -->
            <div class="p-4 rounded-2xl bg-blue-500/10 border border-blue-500/30 text-blue-800 dark:text-blue-300 text-xs space-y-1">
              <div class="font-extrabold flex items-center gap-2">
                <Zap class="w-4 h-4 text-blue-500" />
                <span>Effective Permission Principle</span>
              </div>
              <p class="font-medium text-slate-600 dark:text-slate-300">
                Permissions checked in green are inherited from {{ selectedUserForAccess?.full_name }}'s primary role (<strong>{{ selectedUserForAccess?.primary_role_name }}</strong>). Select additional permissions below to extend responsibilities (e.g. allowing a Receptionist to deliver food orders).
              </p>
            </div>

            <!-- Toolbar: Search & Presets -->
            <div class="flex flex-col sm:flex-row items-center justify-between gap-3">
              <div class="relative w-full sm:w-72">
                <Search class="w-4 h-4 absolute left-3 top-2.5 text-slate-400" />
                <input
                  v-model="modalPermissionSearch"
                  type="text"
                  placeholder="Filter permissions..."
                  class="w-full pl-9 pr-3 py-1.5 text-xs bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-xl text-slate-900 dark:text-white focus:outline-none focus:border-blue-500 font-medium"
                />
              </div>

              <!-- Package Presets -->
              <div class="flex flex-wrap items-center gap-1.5 w-full sm:w-auto">
                <span class="text-[10px] font-extrabold text-slate-400 uppercase tracking-wider">Quick Presets:</span>
                <button
                  @click="applyPresetPackage('waiter')"
                  class="px-2.5 py-1 rounded-xl bg-emerald-500/10 border border-emerald-500/30 hover:bg-emerald-500/20 text-emerald-700 dark:text-emerald-300 text-[10px] font-black transition cursor-pointer"
                >
                  + Waiter Operations
                </button>
                <button
                  @click="applyPresetPackage('kitchen')"
                  class="px-2.5 py-1 rounded-xl bg-orange-500/10 border border-orange-500/30 hover:bg-orange-500/20 text-orange-700 dark:text-orange-300 text-[10px] font-black transition cursor-pointer"
                >
                  + Kitchen Operations
                </button>
                <button
                  @click="applyPresetPackage('cashier')"
                  class="px-2.5 py-1 rounded-xl bg-purple-500/10 border border-purple-500/30 hover:bg-purple-500/20 text-purple-700 dark:text-purple-300 text-[10px] font-black transition cursor-pointer"
                >
                  + Cashier Billing
                </button>
              </div>
            </div>

            <!-- Grouped System Permissions List -->
            <div class="space-y-6">
              <div
                v-for="group in filteredGroupedPermissions"
                :key="group.module_key"
                class="bg-slate-50/60 dark:bg-slate-950/60 border border-slate-200/80 dark:border-slate-800 rounded-2xl p-4 space-y-3 shadow-2xs"
              >
                <!-- Group Header -->
                <div class="flex items-center justify-between pb-2.5 border-b border-slate-200 dark:border-slate-800">
                  <div class="flex items-center gap-2.5">
                    <span :class="['w-3 h-3 rounded-full', getModuleInfo(group.module_key).color]"></span>
                    <h3 class="font-black text-xs sm:text-sm text-slate-900 dark:text-white uppercase tracking-wider">
                      {{ getModuleInfo(group.module_key).title }}
                    </h3>
                    <span class="px-2 py-0.5 rounded-md bg-slate-200 dark:bg-slate-800 text-slate-600 dark:text-slate-400 font-bold text-[10px]">
                      {{ group.permissions.length }} permissions
                    </span>
                  </div>

                  <div class="flex items-center gap-2">
                    <button
                      @click="selectAllInModule(group.permissions)"
                      class="px-2.5 py-1 rounded-lg text-[10px] font-black text-blue-600 dark:text-blue-400 bg-blue-500/10 hover:bg-blue-500/20 transition cursor-pointer"
                    >
                      + Select All
                    </button>
                    <span class="text-slate-300 dark:text-slate-700">•</span>
                    <button
                      @click="clearModulePermissions(group.permissions)"
                      class="px-2.5 py-1 rounded-lg text-[10px] font-black text-rose-600 dark:text-rose-400 bg-rose-500/10 hover:bg-rose-500/20 transition cursor-pointer"
                    >
                      Clear Group
                    </button>
                  </div>
                </div>

                <!-- Group Permissions Grid -->
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-2.5">
                  <div
                    v-for="perm in group.permissions"
                    :key="perm.id"
                    @click="toggleDirectPermission(perm.id, perm.slug)"
                    :class="[
                      'p-2.5 rounded-xl border transition text-xs select-none',
                      isPermissionInherited(perm.slug)
                        ? 'bg-emerald-500/10 border-emerald-500/30 text-emerald-800 dark:text-emerald-300 cursor-not-allowed opacity-90'
                        : selectedDirectPermissionIds.includes(perm.id)
                          ? 'bg-blue-500/10 border-blue-500/40 text-blue-800 dark:text-blue-300 cursor-pointer shadow-xs'
                          : 'bg-white dark:bg-slate-900 border-slate-200 dark:border-slate-800 text-slate-700 dark:text-slate-300 hover:border-blue-400 cursor-pointer'
                    ]"
                  >
                    <div class="flex items-start gap-2">
                      <!-- Checkbox / Inherited Icon -->
                      <div class="mt-0.5 flex-shrink-0">
                        <CheckCircle2
                          v-if="isPermissionInherited(perm.slug)"
                          class="w-4 h-4 text-emerald-500"
                        />
                        <input
                          v-else
                          type="checkbox"
                          :checked="selectedDirectPermissionIds.includes(perm.id)"
                          class="w-4 h-4 rounded border-slate-300 text-blue-600 focus:ring-blue-500 pointer-events-none"
                        />
                      </div>

                      <div class="space-y-0.5 min-w-0 flex-1">
                        <div class="flex items-center justify-between gap-1">
                          <p class="font-extrabold truncate text-slate-900 dark:text-white">
                            {{ perm.name }}
                          </p>
                          <span
                            v-if="isPermissionInherited(perm.slug)"
                            class="px-1.5 py-0.5 text-[9px] font-black rounded-md bg-emerald-500/20 text-emerald-700 dark:text-emerald-300 uppercase flex-shrink-0"
                          >
                            Role
                          </span>
                        </div>
                        <p class="text-[10px] text-slate-400 font-mono truncate">
                          {{ perm.slug }}
                        </p>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- Modal Footer -->
          <div class="flex items-center justify-between p-4 border-t border-slate-200 dark:border-slate-800 bg-slate-50/50 dark:bg-slate-950/50">
            <div class="text-xs font-extrabold text-slate-600 dark:text-slate-300">
              Selected Direct Permissions:
              <span class="text-blue-600 dark:text-blue-400 font-black text-sm ml-1">
                {{ selectedDirectPermissionIds.length }}
              </span>
            </div>

            <div class="flex items-center gap-3">
              <button
                @click="showAccessModal = false"
                class="px-4 py-2 rounded-xl text-xs font-bold text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800 transition"
              >
                Cancel
              </button>
              <button
                @click="saveAccessPermissions"
                :disabled="accessLoading"
                class="px-5 py-2 rounded-xl bg-blue-600 hover:bg-blue-700 text-white text-xs font-black shadow-md shadow-blue-600/20 transition cursor-pointer flex items-center gap-2"
              >
                <Check class="w-4 h-4" />
                <span>Save Permissions</span>
              </button>
            </div>
          </div>
        </div>
      </div>

      <!-- ========================================================================= -->
      <!-- ASSIGN ROLES MODAL -->
      <!-- ========================================================================= -->
      <div v-if="showAssignModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/60 backdrop-blur-xs">
        <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-3xl p-6 sm:p-8 max-w-lg w-full space-y-6 shadow-2xl">
          <div class="flex items-center justify-between pb-3 border-b border-slate-200 dark:border-slate-800">
            <div class="space-y-0.5">
              <h2 class="text-lg font-black text-slate-900 dark:text-white">
                Assign Roles: {{ selectedUserForRoles?.full_name }}
              </h2>
              <p class="text-xs text-slate-500 dark:text-slate-400">Select one primary role and optional secondary roles.</p>
            </div>
            <button @click="showAssignModal = false" class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-200">
              <XCircle class="w-5 h-5" />
            </button>
          </div>

          <div class="space-y-3 max-h-96 overflow-y-auto pr-1">
            <div
              v-for="role in roles"
              :key="role.id"
              @click="toggleRoleSelection(role.id)"
              :class="[
                'flex items-center justify-between p-3 rounded-2xl border text-xs font-bold cursor-pointer transition',
                selectedRoleIds.includes(role.id)
                  ? 'bg-amber-500/10 border-amber-500/40 text-amber-700 dark:text-amber-300'
                  : 'bg-slate-50 dark:bg-slate-950 border-slate-200 dark:border-slate-800 text-slate-700 dark:text-slate-300'
              ]"
            >
              <div class="flex items-center gap-3">
                <input
                  type="checkbox"
                  :checked="selectedRoleIds.includes(role.id)"
                  class="w-4 h-4 rounded border-slate-300 text-amber-500 pointer-events-none"
                />
                <div>
                  <p class="font-black text-slate-900 dark:text-white">{{ role.name }}</p>
                  <p class="text-[10px] text-slate-500 font-normal">{{ role.description }}</p>
                </div>
              </div>

              <!-- Primary radio selection -->
              <div v-if="selectedRoleIds.includes(role.id)" @click.stop="primaryRoleId = role.id" class="flex items-center gap-1 bg-amber-500/20 px-2 py-1 rounded-lg">
                <input
                  type="radio"
                  :name="'primary_role'"
                  :checked="primaryRoleId === role.id"
                  class="w-3 h-3 text-amber-500"
                />
                <span class="text-[10px] font-bold">Primary</span>
              </div>
            </div>
          </div>

          <div class="flex items-center justify-end gap-3 pt-3 border-t border-slate-200 dark:border-slate-800">
            <button
              @click="showAssignModal = false"
              class="px-4 py-2 rounded-xl text-xs font-bold text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800 transition"
            >
              Cancel
            </button>
            <button
              @click="saveUserRoles"
              :disabled="loading || selectedRoleIds.length === 0"
              class="px-5 py-2 rounded-xl bg-amber-500 hover:bg-amber-600 text-slate-950 text-xs font-black shadow-md shadow-amber-500/20 transition cursor-pointer"
            >
              Save Roles
            </button>
          </div>
        </div>
      </div>
    </div>
  </DashboardLayout>
</template>

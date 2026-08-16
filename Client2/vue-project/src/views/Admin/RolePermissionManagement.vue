<script setup lang="ts">
import { ref, onMounted, computed } from 'vue'
import DashboardLayout from '@/Layouts/DashboardLayout.vue'
import { rbacService } from '@/services/rbacService'
import type { Role, Permission } from '@/types/rbacTypes'
import { useAuthStore } from '@/stores/auth'
import { Shield, Users, Search, ChevronRight, Save, AlertCircle, Info, FileText } from 'lucide-vue-next'

const authStore = useAuthStore()
const roles = ref<Role[]>([])
const permissions = ref<Permission[]>([])
const loading = ref(false)
const saving = ref(false)
const error = ref<string | null>(null)
const searchQuery = ref('')
const selectedRole = ref<Role | null>(null)
const rolePermissions = ref<Record<string | number, boolean>>({})
const originalPermissions = ref<Record<string | number, boolean>>({})

const permissionActions = ['View', 'Create', 'Edit', 'Delete', 'Approve', 'Export', 'Other']

const filteredRoles = computed(() => {
  if (!searchQuery.value) return roles.value
  return roles.value.filter(role =>
    role.name.toLowerCase().includes(searchQuery.value.toLowerCase()) ||
    (role.display_name && role.display_name.toLowerCase().includes(searchQuery.value.toLowerCase()))
  )
})

const groupedPermissions = computed(() => {
  const grouped: Record<string, Permission[]> = {}
  permissions.value.forEach(permission => {
    const mod = permission.module || 'General'
    if (!grouped[mod]) grouped[mod] = []
    grouped[mod].push(permission)
  })
  return grouped
})

const permissionMatrix = computed(() => {
  const matrix: Array<{ module: string; icon: string; permissions: Record<string, Permission | null> }> = []
  
  Object.keys(groupedPermissions.value).forEach(module => {
    const modulePerms = groupedPermissions.value[module]
    const row: Record<string, Permission | null> = {}
    
    permissionActions.forEach(action => {
      const perm = modulePerms.find(p => 
        p.name.toLowerCase().includes(action.toLowerCase()) ||
        p.slug.includes(action.toLowerCase())
      )
      row[action] = perm || null
    })

    matrix.push({ module, icon: getModuleIcon(module), permissions: row })
  })

  return matrix
})

const totalRoles = computed(() => roles.value.length)
const totalPermissions = computed(() => permissions.value.length)
const totalModules = computed(() => Object.keys(groupedPermissions.value).length)
const usersAssigned = computed(() => roles.value.reduce((sum, role) => sum + (role.users_count || 0), 0))
const hasChanges = computed(() => JSON.stringify(rolePermissions.value) !== JSON.stringify(originalPermissions.value))

async function loadRoles() {
  try {
    const data = await rbacService.getRoles()
    roles.value = data || []
  } catch (err: any) {
    console.error('Failed to load roles:', err)
    error.value = 'Failed to load roles'
  }
}

async function loadPermissions() {
  try {
    const res = await rbacService.getPermissions()
    permissions.value = res.data || []
  } catch (err: any) {
    console.error('Failed to load permissions:', err)
    error.value = 'Failed to load permissions'
  }
}

async function loadData() {
  loading.value = true
  error.value = null
  try {
    await Promise.all([loadRoles(), loadPermissions()])
    if (roles.value.length > 0 && !selectedRole.value) selectRole(roles.value[0])
  } finally {
    loading.value = false
  }
}

async function selectRole(role: Role) {
  selectedRole.value = role
  loading.value = true
  try {
    const res = await rbacService.getRolePermissions(role.id)
    const activePermIds = res.permission_ids || (res.data ? res.data.map(p => p.id) : [])
    const permMap: Record<string | number, boolean> = {}
    permissions.value.forEach(perm => {
      permMap[perm.id] = activePermIds.includes(perm.id)
    })
    rolePermissions.value = { ...permMap }
    originalPermissions.value = { ...permMap }
  } catch (err: any) {
    console.error('Failed to load role permissions:', err)
    error.value = 'Failed to load role permissions'
  } finally {
    loading.value = false
  }
}

function togglePermission(permissionId: string | number | undefined) {
  if (!permissionId) return
  rolePermissions.value[permissionId] = !rolePermissions.value[permissionId]
}

function expandAll() {
  permissions.value.forEach(p => { rolePermissions.value[p.id] = true })
}

function collapseAll() {
  permissions.value.forEach(p => { rolePermissions.value[p.id] = false })
}

async function saveChanges() {
  if (!selectedRole.value) return
  saving.value = true
  error.value = null
  try {
    const selectedPermissionIds = Object.entries(rolePermissions.value)
      .filter(([_, selected]) => selected)
      .map(([id]) => Number(id))
    await rbacService.syncRolePermissions(selectedRole.value.id, selectedPermissionIds)
    originalPermissions.value = { ...rolePermissions.value }
    const successMsg = document.createElement('div')
    successMsg.className = 'fixed top-4 right-4 bg-green-500 text-white px-6 py-3 rounded-lg shadow-lg z-50 font-bold text-xs'
    successMsg.textContent = '✓ Permissions saved successfully!'
    document.body.appendChild(successMsg)
    setTimeout(() => successMsg.remove(), 3000)
    await loadRoles()
    await authStore.fetchCurrentUser()
  } catch (err: any) {
    console.error('Failed to save permissions:', err)
    error.value = err.response?.data?.message || 'Failed to save permissions'
  } finally {
    saving.value = false
  }
}

function getRoleIcon(roleName: string) {
  const icons: Record<string, string> = {
    'admin': '🛡️', 'manager': '👔', 'receptionist': '🧑‍💼',
    'cashier': '💰', 'chef': '👨‍🍳', 'waiter': '🍽️', 'guest': '👤'
  }
  return icons[roleName.toLowerCase()] || '👤'
}

function getModuleIcon(module: string) {
  const icons: Record<string, string> = {
    'dashboard': '📊', 'users': '👥', 'roles': '🎭', 'permissions': '🔐',
    'rooms': '🏠', 'room-types': '🏨', 'reservations': '📅',
    'checkin': '✅', 'checkout': '🚪', 'payments': '💳', 'reports': '📈'
  }
  return icons[module.toLowerCase()] || '📦'
}

function isPermissionChecked(permission: Permission | null): boolean {
  if (!permission) return false
  return rolePermissions.value[permission.id] || false
}

onMounted(() => { loadData() })
</script>

<template>
  <DashboardLayout>
    <div class="min-h-screen bg-white dark:bg-slate-900 py-4 px-6 space-y-6">
      <div>
        <h1 class="text-2xl font-black text-slate-900 dark:text-slate-100">Role & Permission Matrix</h1>
        <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">Configure role permissions side-by-side across all system modules.</p>
      </div>

      <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
        <div class="bg-slate-50 dark:bg-slate-800/60 rounded-2xl border border-slate-200 dark:border-slate-800 p-4 flex items-center gap-3">
          <Users class="w-7 h-7 text-purple-500" />
          <div>
            <p class="text-xl font-black text-slate-900 dark:text-slate-100">{{ totalRoles }}</p>
            <p class="text-xs font-medium text-slate-500 dark:text-slate-400">Total Roles</p>
          </div>
        </div>

        <div class="bg-slate-50 dark:bg-slate-800/60 rounded-2xl border border-slate-200 dark:border-slate-800 p-4 flex items-center gap-3">
          <Shield class="w-7 h-7 text-emerald-500" />
          <div>
            <p class="text-xl font-black text-slate-900 dark:text-slate-100">{{ totalPermissions }}</p>
            <p class="text-xs font-medium text-slate-500 dark:text-slate-400">Total Permissions</p>
          </div>
        </div>

        <div class="bg-slate-50 dark:bg-slate-800/60 rounded-2xl border border-slate-200 dark:border-slate-800 p-4 flex items-center gap-3">
          <Users class="w-7 h-7 text-blue-500" />
          <div>
            <p class="text-xl font-black text-slate-900 dark:text-slate-100">{{ usersAssigned }}</p>
            <p class="text-xs font-medium text-slate-500 dark:text-slate-400">Users Assigned</p>
          </div>
        </div>

        <div class="bg-slate-50 dark:bg-slate-800/60 rounded-2xl border border-slate-200 dark:border-slate-800 p-4 flex items-center gap-3">
          <FileText class="w-7 h-7 text-amber-500" />
          <div>
            <p class="text-xl font-black text-slate-900 dark:text-slate-100">{{ totalModules }}</p>
            <p class="text-xs font-medium text-slate-500 dark:text-slate-400">Modules</p>
          </div>
        </div>
      </div>

      <div v-if="error" class="bg-rose-50 dark:bg-rose-900/20 border border-rose-200 dark:border-rose-800 rounded-2xl p-4 flex items-center gap-2">
        <AlertCircle class="w-5 h-5 text-rose-600 dark:text-rose-400 flex-shrink-0" />
        <p class="text-xs font-bold text-rose-600 dark:text-rose-400">{{ error }}</p>
      </div>

      <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
        <div class="lg:col-span-4">
          <div class="bg-white dark:bg-slate-800 rounded-2xl border border-slate-200 dark:border-slate-700 shadow-xs">
            <div class="border-b border-slate-200 dark:border-slate-700 p-4">
              <h3 class="font-bold text-slate-900 dark:text-slate-100 text-base">System Roles</h3>
              <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">Select a role to configure matrix permissions</p>
            </div>

            <div class="p-3 border-b border-slate-200 dark:border-slate-700">
              <div class="relative">
                <Search class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400" />
                <input
                  v-model="searchQuery"
                  type="text"
                  placeholder="Search roles..."
                  class="w-full pl-9 pr-3 py-2 text-xs border border-slate-200 dark:border-slate-700 rounded-xl bg-slate-50 dark:bg-slate-900 text-slate-900 dark:text-slate-100 focus:outline-none focus:ring-2 focus:ring-blue-500/50"
                />
              </div>
            </div>

            <div class="max-h-[550px] overflow-y-auto divide-y divide-slate-100 dark:divide-slate-700/60">
              <div v-if="loading && roles.length === 0" class="p-8 text-center">
                <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600 mx-auto"></div>
                <p class="text-xs text-slate-600 dark:text-slate-400 mt-2">Loading...</p>
              </div>

              <button
                v-for="role in filteredRoles"
                :key="role.id"
                @click="selectRole(role)"
                :class="[
                  'w-full px-4 py-3.5 flex items-center gap-3 text-left transition',
                  selectedRole?.id === role.id 
                    ? 'bg-blue-50/80 dark:bg-blue-950/40 border-l-4 border-l-blue-600 text-blue-900 dark:text-blue-200 font-medium' 
                    : 'hover:bg-slate-50 dark:hover:bg-slate-700/40 text-slate-700 dark:text-slate-300'
                ]"
              >
                <span class="text-xl flex-shrink-0">{{ getRoleIcon(role.name) }}</span>
                <div class="flex-1 min-w-0">
                  <div class="flex items-center gap-2">
                    <p class="font-bold text-sm text-slate-900 dark:text-slate-100 truncate">{{ role.display_name || role.name }}</p>
                    <span v-if="role.is_system" class="text-[9px] uppercase font-extrabold px-1.5 py-0.5 rounded bg-slate-100 dark:bg-slate-700 text-slate-500 dark:text-slate-400">System</span>
                  </div>
                  <p class="text-[11px] text-slate-500 dark:text-slate-400 leading-snug mt-0.5 line-clamp-2">{{ role.description || 'System role access control' }}</p>
                </div>
                <span :class="['px-2 py-0.5 text-[10px] uppercase font-extrabold rounded-full flex-shrink-0', (role.is_active ?? true) ? 'bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 border border-emerald-500/20' : 'bg-rose-500/10 text-rose-600 dark:text-rose-400 border border-rose-500/20']">
                  {{ (role.is_active ?? true) ? 'active' : 'inactive' }}
                </span>
                <ChevronRight class="w-4 h-4 text-slate-400 flex-shrink-0" />
              </button>
            </div>

            <div class="border-t border-slate-200 dark:border-slate-700 p-3 text-xs font-semibold text-slate-500 dark:text-slate-400">
              Showing {{ filteredRoles.length }} of {{ roles.length }} roles
            </div>
          </div>
        </div>

        <div class="lg:col-span-8">
          <div class="bg-white dark:bg-slate-800 rounded-2xl border border-slate-200 dark:border-slate-700 shadow-xs overflow-hidden">
            <div class="border-b border-slate-200 dark:border-slate-700 p-4">
              <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                <div>
                  <h3 v-if="selectedRole" class="font-bold text-slate-900 dark:text-slate-100 flex items-center gap-2">
                    <span>Permissions for: {{ selectedRole.display_name || selectedRole.name }}</span>
                  </h3>
                  <h3 v-else class="font-bold text-slate-900 dark:text-slate-100">Select a role</h3>
                  <p v-if="selectedRole" class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">Assign permissions to define what this role can access and manage.</p>
                </div>
                
                <div v-if="selectedRole" class="flex items-center gap-2">
                  <button @click="expandAll" class="px-3 py-1.5 text-xs font-bold border border-blue-300 dark:border-blue-700 text-blue-700 dark:text-blue-300 rounded-xl hover:bg-blue-50 dark:hover:bg-blue-900/20 transition">⚡ Expand All</button>
                  <button @click="collapseAll" class="px-3 py-1.5 text-xs font-bold border border-slate-300 dark:border-slate-600 rounded-xl hover:bg-slate-50 dark:hover:bg-slate-700 transition">✕ Collapse All</button>
                  <button v-if="hasChanges" @click="saveChanges" :disabled="saving" class="px-4 py-1.5 text-xs font-extrabold bg-blue-600 hover:bg-blue-700 text-white rounded-xl transition disabled:opacity-50 flex items-center gap-1 shadow-md shadow-blue-500/20">
                    <Save class="w-3.5 h-3.5" />
                    {{ saving ? 'Saving...' : 'Save Changes' }}
                  </button>
                </div>
              </div>
            </div>

            <div v-if="selectedRole" class="overflow-x-auto">
              <div v-if="loading" class="p-12 text-center">
                <div class="animate-spin rounded-full h-10 w-10 border-b-2 border-blue-600 mx-auto"></div>
                <p class="text-xs text-slate-500 dark:text-slate-400 mt-3">Loading permissions...</p>
              </div>

              <table v-else class="w-full text-xs">
                <thead class="bg-slate-50 dark:bg-slate-950 border-b border-slate-200 dark:border-slate-700">
                  <tr>
                    <th class="px-4 py-3 text-left font-bold text-slate-700 dark:text-slate-300 uppercase tracking-wider">Module</th>
                    <th v-for="action in permissionActions" :key="action" class="px-3 py-3 text-center font-bold text-slate-700 dark:text-slate-300 uppercase tracking-wider">{{ action }}</th>
                  </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-700/60">
                  <tr v-for="row in permissionMatrix" :key="row.module" class="hover:bg-slate-50 dark:hover:bg-slate-700/30 transition">
                    <td class="px-4 py-3 font-semibold text-slate-900 dark:text-slate-100">
                      <div class="flex items-center gap-2">
                        <span class="text-base">{{ row.icon }}</span>
                        <span class="capitalize">{{ row.module.replace('-', ' ') }}</span>
                      </div>
                    </td>
                    <td v-for="action in permissionActions" :key="action" class="px-3 py-3 text-center">
                      <label v-if="row.permissions[action]" class="inline-flex cursor-pointer">
                        <input
                          type="checkbox"
                          :checked="isPermissionChecked(row.permissions[action])"
                          @change="togglePermission(row.permissions[action]?.id)"
                          class="w-4 h-4 text-blue-600 border-slate-300 rounded focus:ring-blue-500 cursor-pointer"
                        />
                      </label>
                      <span v-else class="text-slate-300 dark:text-slate-600">—</span>
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>

            <div v-else class="p-12 text-center">
              <Shield class="w-12 h-12 text-slate-300 dark:text-slate-600 mx-auto mb-3" />
              <p class="text-xs text-slate-500 dark:text-slate-400">Select a role from the left list to configure matrix permissions.</p>
            </div>
          </div>
        </div>
      </div>
    </div>
  </DashboardLayout>
</template>

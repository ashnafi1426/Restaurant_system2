<script setup lang="ts">
import { ref, onMounted, computed } from 'vue'
import DashboardLayout from '@/Layouts/DashboardLayout.vue'
import { roleService } from '@/services/roleService'
import { permissionService } from '@/services/permissionService'
import type { Role, Permission } from '@/types/role'
import { Shield, Users, Search, ChevronRight, Save, AlertCircle, Info, FileText } from 'lucide-vue-next'

const roles = ref<Role[]>([])
const permissions = ref<Permission[]>([])
const loading = ref(false)
const saving = ref(false)
const error = ref<string | null>(null)
const searchQuery = ref('')
const selectedRole = ref<Role | null>(null)
const rolePermissions = ref<Record<string, boolean>>({})
const originalPermissions = ref<Record<string, boolean>>({})

const permissionActions = ['View', 'Create', 'Edit', 'Delete', 'Approve', 'Export', 'Other']

const filteredRoles = computed(() => {
  if (!searchQuery.value) return roles.value
  return roles.value.filter(role =>
    role.name.toLowerCase().includes(searchQuery.value.toLowerCase()) ||
    role.display_name.toLowerCase().includes(searchQuery.value.toLowerCase())
  )
})

const groupedPermissions = computed(() => {
  const grouped: Record<string, Permission[]> = {}
  permissions.value.forEach(permission => {
    if (!grouped[permission.module]) grouped[permission.module] = []
    grouped[permission.module].push(permission)
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
const selectedPermissionsCount = computed(() => Object.values(rolePermissions.value).filter(Boolean).length)

async function loadRoles() {
  try {
    const response = await roleService.getRoles()
    roles.value = response.data || []
  } catch (err: any) {
    console.error('Failed to load roles:', err)
    error.value = 'Failed to load roles'
  }
}

async function loadPermissions() {
  try {
    const response = await permissionService.getAllPermissions()
    permissions.value = response.data || []
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
    const response = await roleService.getRole(role.id)
    const roleData = response.data
    const permMap: Record<string, boolean> = {}
    permissions.value.forEach(perm => {
      permMap[perm.id] = roleData.permissions?.some((p: Permission) => p.id === perm.id) || false
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

function togglePermission(permissionId: string | undefined) {
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
      .map(([id]) => id)
    await roleService.syncPermissions(selectedRole.value.id, selectedPermissionIds)
    originalPermissions.value = { ...rolePermissions.value }
    const successMsg = document.createElement('div')
    successMsg.className = 'fixed top-4 right-4 bg-green-500 text-white px-6 py-3 rounded-lg shadow-lg z-50'
    successMsg.textContent = '✓ Permissions saved successfully!'
    document.body.appendChild(successMsg)
    setTimeout(() => successMsg.remove(), 3000)
    await loadRoles()
  } catch (err: any) {
    console.error('Failed to save permissions:', err)
    error.value = err.response?.data?.message || 'Failed to save permissions'
  } finally {
    saving.value = false
  }
}

function getRoleIcon(roleName: string) {
  const icons: Record<string, string> = {
    'super_admin': '👑', 'admin': '🛡️', 'manager': '👔', 'receptionist': '🧑‍💼',
    'cashier': '💰', 'chef': '👨‍🍳', 'waiter': '🍽️', 'housekeeping': '🧹',
    'accountant': '📊', 'guest': '👤'
  }
  return icons[roleName] || '👤'
}

function getModuleIcon(module: string) {
  const icons: Record<string, string> = {
    'dashboard': '📊', 'users': '👥', 'roles': '🎭', 'permissions': '🔐',
    'customers': '👥', 'rooms': '🏠', 'room-types': '🏨', 'reservations': '📅',
    'checkin': '✅', 'checkout': '🚪', 'payments': '💳', 'invoices': '🧾',
    'housekeeping': '🧹', 'staff': '👔', 'reports': '📈', 'settings': '⚙️'
  }
  return icons[module] || '📦'
}

function isPermissionChecked(permission: Permission | null): boolean {
  if (!permission) return false
  return rolePermissions.value[permission.id] || false
}

onMounted(() => { loadData() })
</script>

<template>
  <DashboardLayout>
    <div class="min-h-screen bg-white dark:bg-slate-900 py-4 px-6">
      <div class="mb-6">
        <h1 class="text-2xl font-bold text-slate-900 dark:text-slate-100">Role & Permission Management</h1>
        <p class="text-sm text-slate-600 dark:text-slate-400 mt-1">Manage roles and assign permissions to control access across the system.</p>
      </div>

      <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
        <div class="bg-white dark:bg-slate-800 rounded-lg border border-slate-200 dark:border-slate-700 p-4 flex items-center gap-3">
          <Users class="w-8 h-8 text-purple-600 dark:text-purple-400" />
          <div>
            <p class="text-2xl font-bold text-slate-900 dark:text-slate-100">{{ totalRoles }}</p>
            <p class="text-xs text-slate-600 dark:text-slate-400">Total Roles</p>
          </div>
        </div>

        <div class="bg-white dark:bg-slate-800 rounded-lg border border-slate-200 dark:border-slate-700 p-4 flex items-center gap-3">
          <Shield class="w-8 h-8 text-green-600 dark:text-green-400" />
          <div>
            <p class="text-2xl font-bold text-slate-900 dark:text-slate-100">{{ totalPermissions }}</p>
            <p class="text-xs text-slate-600 dark:text-slate-400">Total Permissions</p>
          </div>
        </div>

        <div class="bg-white dark:bg-slate-800 rounded-lg border border-slate-200 dark:border-slate-700 p-4 flex items-center gap-3">
          <Users class="w-8 h-8 text-blue-600 dark:text-blue-400" />
          <div>
            <p class="text-2xl font-bold text-slate-900 dark:text-slate-100">{{ usersAssigned }}</p>
            <p class="text-xs text-slate-600 dark:text-slate-400">Users Assigned</p>
          </div>
        </div>

        <div class="bg-white dark:bg-slate-800 rounded-lg border border-slate-200 dark:border-slate-700 p-4 flex items-center gap-3">
          <FileText class="w-8 h-8 text-orange-600 dark:text-orange-400" />
          <div>
            <p class="text-2xl font-bold text-slate-900 dark:text-slate-100">{{ totalModules }}</p>
            <p class="text-xs text-slate-600 dark:text-slate-400">Modules</p>
          </div>
        </div>
      </div>

      <div v-if="error" class="bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-lg p-3 mb-6 flex items-center gap-2">
        <AlertCircle class="w-5 h-5 text-red-600 dark:text-red-400 flex-shrink-0" />
        <p class="text-sm text-red-600 dark:text-red-400">{{ error }}</p>
      </div>

      <div class="grid grid-cols-1 lg:grid-cols-4 gap-6">
        <div class="lg:col-span-1">
          <div class="bg-white dark:bg-slate-800 rounded-lg border border-slate-200 dark:border-slate-700">
            <div class="border-b border-slate-200 dark:border-slate-700 p-4">
              <h3 class="font-semibold text-slate-900 dark:text-slate-100">Roles</h3>
              <p class="text-xs text-slate-600 dark:text-slate-400 mt-1">Click a role to manage its permissions</p>
            </div>

            <div class="p-3 border-b border-slate-200 dark:border-slate-700">
              <div class="relative">
                <Search class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400" />
                <input
                  v-model="searchQuery"
                  type="text"
                  placeholder="Search roles..."
                  class="w-full pl-9 pr-3 py-2 text-sm border border-slate-300 dark:border-slate-600 rounded-md bg-white dark:bg-slate-900 text-slate-900 dark:text-slate-100"
                />
              </div>
            </div>

            <div class="max-h-[500px] overflow-y-auto">
              <div v-if="loading && roles.length === 0" class="p-8 text-center">
                <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600 mx-auto"></div>
                <p class="text-sm text-slate-600 dark:text-slate-400 mt-2">Loading...</p>
              </div>

              <button
                v-for="role in filteredRoles"
                :key="role.id"
                @click="selectRole(role)"
                :class="[
                  'w-full px-4 py-3 flex items-center gap-3 text-left transition border-b border-slate-100 dark:border-slate-700',
                  selectedRole?.id === role.id ? 'bg-purple-50 dark:bg-purple-900/20 border-l-4 border-l-purple-600' : 'hover:bg-slate-50 dark:hover:bg-slate-700/50'
                ]"
              >
                <span class="text-2xl">{{ getRoleIcon(role.name) }}</span>
                <div class="flex-1 min-w-0">
                  <p class="font-medium text-sm text-slate-900 dark:text-slate-100 truncate">{{ role.display_name }}</p>
                  <p class="text-xs text-slate-500 dark:text-slate-400">{{ role.description || 'Manage role' }}</p>
                </div>
                <span :class="['px-2 py-1 text-xs rounded font-medium', role.status === 'active' ? 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400' : 'bg-gray-100 text-gray-700 dark:bg-gray-800 dark:text-gray-400']">
                  {{ role.status }}
                </span>
                <ChevronRight class="w-4 h-4 text-slate-400" />
              </button>
            </div>

            <div class="border-t border-slate-200 dark:border-slate-700 p-3 text-xs text-slate-500 dark:text-slate-400">
              Showing {{ filteredRoles.length }} of {{ roles.length }} roles
            </div>
          </div>
        </div>

        <div class="lg:col-span-3">
          <div class="bg-white dark:bg-slate-800 rounded-lg border border-slate-200 dark:border-slate-700">
            <div class="border-b border-slate-200 dark:border-slate-700 p-4">
              <div class="flex items-center justify-between">
                <div>
                  <h3 v-if="selectedRole" class="font-semibold text-slate-900 dark:text-slate-100 flex items-center gap-2">
                    <span>Permissions for: {{ selectedRole.display_name }}</span>
                    <span v-if="selectedRole.name === 'super_admin'" class="px-2 py-1 bg-purple-100 dark:bg-purple-900/30 text-purple-700 dark:text-purple-400 rounded text-xs font-medium">Active</span>
                  </h3>
                  <h3 v-else class="font-semibold text-slate-900 dark:text-slate-100">Select a role</h3>
                  <p v-if="selectedRole" class="text-xs text-slate-600 dark:text-slate-400 mt-1">Assign permissions to define what this role can access and manage.</p>
                </div>
                
                <div v-if="selectedRole && selectedRole.name !== 'super_admin'" class="flex items-center gap-2">
                  <button @click="expandAll" class="px-3 py-1.5 text-xs border border-blue-300 dark:border-blue-700 text-blue-700 dark:text-blue-300 rounded hover:bg-blue-50 dark:hover:bg-blue-900/20 transition">⚡ Expand All</button>
                  <button @click="collapseAll" class="px-3 py-1.5 text-xs border border-slate-300 dark:border-slate-600 rounded hover:bg-slate-50 dark:hover:bg-slate-700 transition">✕ Collapse All</button>
                  <button v-if="hasChanges" @click="saveChanges" :disabled="saving" class="px-4 py-1.5 text-xs bg-blue-600 hover:bg-blue-700 text-white rounded transition disabled:opacity-50 flex items-center gap-1">
                    <Save class="w-3 h-3" />
                    {{ saving ? 'Saving...' : 'Save Changes' }}
                  </button>
                </div>
              </div>
            </div>

            <div v-if="selectedRole && selectedRole.name === 'super_admin'" class="bg-purple-50 dark:bg-purple-900/20 border-b border-purple-200 dark:border-purple-800 p-3 flex items-center gap-2">
              <Info class="w-4 h-4 text-purple-600 dark:text-purple-400 flex-shrink-0" />
              <p class="text-xs text-purple-700 dark:text-purple-400">Super Admin has all permissions with full access to the system</p>
            </div>

            <div v-if="selectedRole" class="overflow-x-auto">
              <div v-if="loading" class="p-12 text-center">
                <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-600 mx-auto"></div>
                <p class="text-slate-600 dark:text-slate-400 mt-4">Loading permissions...</p>
              </div>

              <table v-else class="w-full text-sm">
                <thead class="bg-slate-50 dark:bg-slate-900 border-b border-slate-200 dark:border-slate-700">
                  <tr>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-slate-700 dark:text-slate-300">Module</th>
                    <th v-for="action in permissionActions" :key="action" class="px-3 py-3 text-center text-xs font-semibold text-slate-700 dark:text-slate-300">{{ action }}</th>
                  </tr>
                </thead>
                <tbody class="divide-y divide-slate-200 dark:divide-slate-700">
                  <tr v-for="row in permissionMatrix" :key="row.module" class="hover:bg-slate-50 dark:hover:bg-slate-700/30 transition">
                    <td class="px-4 py-3 font-medium text-slate-900 dark:text-slate-100">
                      <div class="flex items-center gap-2">
                        <span class="text-lg">{{ row.icon }}</span>
                        <span class="capitalize">{{ row.module.replace('-', ' ') }}</span>
                      </div>
                    </td>
                    <td v-for="action in permissionActions" :key="action" class="px-3 py-3 text-center">
                      <label v-if="row.permissions[action]" class="inline-flex cursor-pointer">
                        <input
                          type="checkbox"
                          :checked="isPermissionChecked(row.permissions[action])"
                          @change="togglePermission(row.permissions[action]?.id)"
                          :disabled="selectedRole.name === 'super_admin'"
                          class="w-4 h-4 text-blue-600 border-slate-300 rounded focus:ring-blue-500 disabled:opacity-50 cursor-pointer"
                        />
                      </label>
                      <span v-else class="text-slate-300 dark:text-slate-600">—</span>
                    </td>
                  </tr>
                </tbody>
              </table>

              <div class="border-t border-slate-200 dark:border-slate-700 px-4 py-3 bg-slate-50 dark:bg-slate-900 flex items-center gap-6 text-xs">
                <div class="font-semibold text-slate-700 dark:text-slate-300">Legend:</div>
                <div class="flex items-center gap-2">
                  <input type="checkbox" checked disabled class="w-3 h-3" />
                  <span class="text-slate-600 dark:text-slate-400">Allowed</span>
                </div>
                <div class="flex items-center gap-2">
                  <input type="checkbox" disabled class="w-3 h-3" />
                  <span class="text-slate-600 dark:text-slate-400">Not Allowed</span>
                </div>
                <div class="flex items-center gap-2">
                  <span class="text-slate-400">—</span>
                  <span class="text-slate-600 dark:text-slate-400">Not Applicable</span>
                </div>
              </div>

              <div v-if="hasChanges" class="border-t border-blue-200 dark:border-blue-800 bg-blue-50 dark:bg-blue-900/20 px-4 py-3 flex items-center gap-2 text-xs text-blue-700 dark:text-blue-300">
                <Info class="w-4 h-4" />
                <span>⚠️ Changes to permissions will be applied immediately. Users with this role will inherit these permissions.</span>
              </div>
            </div>

            <div v-else class="p-12 text-center">
              <Shield class="w-16 h-16 text-slate-300 dark:text-slate-600 mx-auto mb-4" />
              <p class="text-slate-600 dark:text-slate-400">Select a role to manage its permissions</p>
            </div>
          </div>
        </div>
      </div>
    </div>
  </DashboardLayout>
</template>

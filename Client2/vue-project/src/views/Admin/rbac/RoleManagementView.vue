<script setup lang="ts">
import { ref, onMounted } from 'vue'
import DashboardLayout from '@/Layouts/DashboardLayout.vue'
import ConfigureRoleModal from '@/components/rbac/ConfigureRoleModal.vue'
import { rbacService } from '@/services/rbacService'
import { useAuthStore } from '@/stores/auth'
import type { Role, Permission } from '@/types/rbacTypes'
import {
  ShieldCheck,
  Plus,
  Edit2,
  Trash2,
  CheckCircle2,
  Users,
  Key,
  RefreshCw,
  AlertCircle
} from 'lucide-vue-next'

const authStore = useAuthStore()

const roles = ref<Role[]>([])
const permissions = ref<Permission[]>([])
const loading = ref(true)
const saving = ref(false)
const errorMessage = ref('')
const successMessage = ref('')

// Modal state
const showModal = ref(false)
const editingRole = ref<Role | null>(null)
const initialPermissionIds = ref<number[]>([])

const getRoleEmoji = (slugOrName: string) => {
  const s = String(slugOrName || '').toLowerCase()
  if (s.includes('admin')) return '👑'
  if (s.includes('manager')) return '👔'
  if (s.includes('reception')) return '🧑‍💼'
  if (s.includes('chef') || s.includes('kitchen')) return '👨‍🍳'
  if (s.includes('waiter')) return '🍽️'
  if (s.includes('cashier')) return '💰'
  return '👤'
}

const fetchRolesAndPermissions = async () => {
  loading.value = true
  errorMessage.value = ''
  try {
    const [rolesData, permsData] = await Promise.all([
      rbacService.getRoles(),
      rbacService.getPermissions()
    ])
    roles.value = rolesData || []
    permissions.value = permsData.data || []
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

const openCreateModal = () => {
  editingRole.value = null
  initialPermissionIds.value = []
  showModal.value = true
}

const openEditModal = async (role: Role) => {
  editingRole.value = role
  initialPermissionIds.value = []
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

const handleDeleteRole = async (role: Role) => {
  if (role.is_system) {
    alert('System roles cannot be deleted.')
    return
  }
  if (!confirm(`Are you sure you want to delete custom role "${role.name}"?`)) return
  
  loading.value = true
  errorMessage.value = ''
  try {
    await rbacService.deleteRole(role.id)
    successMessage.value = `Role "${role.name}" deleted.`
    await fetchRolesAndPermissions()
    setTimeout(() => { successMessage.value = '' }, 3500)
  } catch (err: any) {
    errorMessage.value = err?.response?.data?.message || 'Failed to delete role.'
  } finally {
    loading.value = false
  }
}
</script>

<template>
  <DashboardLayout>
    <div class="min-h-screen bg-slate-50 dark:bg-slate-950 p-6 space-y-6">
      <!-- HEADER BAR -->
      <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 p-6 bg-white dark:bg-slate-900 rounded-3xl border border-slate-200 dark:border-slate-800 shadow-xs">
        <div class="space-y-1">
          <div class="flex items-center gap-3">
            <div class="p-2.5 rounded-2xl bg-amber-500/10 text-amber-600 dark:text-amber-400 border border-amber-500/20">
              <ShieldCheck class="w-6 h-6" />
            </div>
            <div>
              <h1 class="text-2xl font-black tracking-tight text-slate-900 dark:text-white">
                Role Management
              </h1>
              <p class="text-xs sm:text-sm text-slate-500 dark:text-slate-400 font-medium mt-0.5">
                Manage system roles, view user counts, and configure access permissions in modal dialogs.
              </p>
            </div>
          </div>
        </div>

        <div class="flex items-center gap-3">
          <button
            @click="fetchRolesAndPermissions"
            class="p-3 rounded-2xl border border-slate-200 dark:border-slate-800 hover:bg-slate-100 dark:hover:bg-slate-800 text-slate-600 dark:text-slate-300 transition cursor-pointer"
            title="Refresh Roles"
          >
            <RefreshCw class="w-4 h-4" :class="{ 'animate-spin': loading }" />
          </button>

          <button
            @click="openCreateModal"
            class="inline-flex items-center gap-2 px-5 py-3 rounded-2xl bg-amber-500 hover:bg-amber-600 text-slate-950 font-extrabold text-xs shadow-md shadow-amber-500/20 transition cursor-pointer"
          >
            <Plus class="w-4 h-4" />
            <span>Create New Role</span>
          </button>
        </div>
      </div>

      <!-- NOTIFICATION BANNERS -->
      <div v-if="successMessage" class="p-4 rounded-2xl bg-emerald-500/10 border border-emerald-500/30 text-emerald-700 dark:text-emerald-300 text-xs font-bold flex items-center gap-2">
        <CheckCircle2 class="w-4.5 h-4.5 text-emerald-500 flex-shrink-0" />
        <span>{{ successMessage }}</span>
      </div>

      <div v-if="errorMessage" class="p-4 rounded-2xl bg-rose-500/10 border border-rose-500/30 text-rose-700 dark:text-rose-300 text-xs font-bold flex items-center gap-2">
        <AlertCircle class="w-4.5 h-4.5 text-rose-500 flex-shrink-0" />
        <span>{{ errorMessage }}</span>
      </div>

      <!-- LOADING STATE -->
      <div v-if="loading && roles.length === 0" class="py-20 text-center">
        <div class="animate-spin rounded-full h-10 w-10 border-b-2 border-amber-500 mx-auto mb-3"></div>
        <p class="text-sm font-semibold text-slate-500 dark:text-slate-400">Loading system roles...</p>
      </div>

      <!-- CARDS GRID -->
      <div v-else class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        <div
          v-for="role in roles"
          :key="role.id"
          class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-3xl p-6 shadow-xs hover:shadow-md transition-all flex flex-col justify-between space-y-5"
        >
          <div class="space-y-4">
            <div class="flex items-start justify-between gap-3">
              <div class="flex items-center gap-3">
                <div class="w-12 h-12 rounded-2xl bg-slate-100 dark:bg-slate-800/80 border border-slate-200 dark:border-slate-700 flex items-center justify-center text-2xl flex-shrink-0">
                  {{ getRoleEmoji(role.slug || role.name) }}
                </div>
                <div>
                  <div class="flex items-center gap-2">
                    <h3 class="text-lg font-black text-slate-900 dark:text-white capitalize tracking-tight">
                      {{ role.name }}
                    </h3>
                    <span v-if="role.is_system" class="text-[9px] uppercase font-extrabold px-2 py-0.5 rounded-full bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-400 border border-slate-200 dark:border-slate-700">
                      System
                    </span>
                  </div>
                  <p class="text-xs text-slate-500 dark:text-slate-400 line-clamp-2 mt-1 leading-relaxed">
                    {{ role.description || 'System access role' }}
                  </p>
                </div>
              </div>

              <span
                :class="[
                  'w-3 h-3 rounded-full flex-shrink-0 mt-1',
                  (role.is_active ?? true) ? 'bg-emerald-500' : 'bg-rose-500'
                ]"
                :title="(role.is_active ?? true) ? 'Active Role' : 'Inactive Role'"
              ></span>
            </div>

            <!-- STATS PILLS -->
            <div class="grid grid-cols-2 gap-3 pt-2">
              <div class="flex items-center gap-2 px-3 py-2 rounded-2xl bg-slate-50 dark:bg-slate-800/60 border border-slate-100 dark:border-slate-800 text-xs font-semibold text-slate-700 dark:text-slate-300">
                <Key class="w-4 h-4 text-amber-500 flex-shrink-0" />
                <span class="truncate">{{ role.permissions_count ?? role.permissions?.length ?? 0 }} perms</span>
              </div>

              <div class="flex items-center gap-2 px-3 py-2 rounded-2xl bg-slate-50 dark:bg-slate-800/60 border border-slate-100 dark:border-slate-800 text-xs font-semibold text-slate-700 dark:text-slate-300">
                <Users class="w-4 h-4 text-indigo-500 flex-shrink-0" />
                <span class="truncate">{{ role.users_count ?? 0 }} users</span>
              </div>
            </div>
          </div>

          <!-- ACTION BUTTONS -->
          <div class="pt-4 border-t border-slate-100 dark:border-slate-800/80 flex items-center justify-between">
            <button
              @click="openEditModal(role)"
              class="inline-flex items-center gap-2 px-4 py-2.5 rounded-2xl bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 dark:hover:bg-slate-700 text-slate-800 dark:text-slate-200 text-xs font-extrabold transition cursor-pointer"
            >
              <Edit2 class="w-3.5 h-3.5 text-amber-500" />
              <span>Configure Role</span>
            </button>

            <button
              v-if="!role.is_system"
              @click="handleDeleteRole(role)"
              class="p-2.5 rounded-2xl text-rose-500 hover:bg-rose-500/10 transition cursor-pointer"
              title="Delete Custom Role"
            >
              <Trash2 class="w-4 h-4" />
            </button>
          </div>
        </div>
      </div>

      <!-- INTEGRATED CONCONFIGURE ROLE MODAL -->
      <ConfigureRoleModal
        :show="showModal"
        :editing-role="editingRole"
        :initial-permission-ids="initialPermissionIds"
        :permissions="permissions"
        :loading="saving"
        @close="showModal = false"
        @save="handleSaveRole"
      />
    </div>
  </DashboardLayout>
</template>
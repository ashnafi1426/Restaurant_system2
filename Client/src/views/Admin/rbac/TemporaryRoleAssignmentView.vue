<script setup lang="ts">
import { ref, onMounted, watch } from 'vue'
import DashboardLayout from '../../../Layouts/DashboardLayout.vue'
import { rbacService } from '../../../services/rbacService'
import type { TemporaryRoleAssignment, Role, RbacUserSummary } from '../../../types/rbacTypes'
import {
  Clock,
  Plus,
  RefreshCw,
  CheckCircle2,
  AlertCircle,
  XCircle,
  UserCheck,
  Trash2,
  Loader2,
} from 'lucide-vue-next'

const tempAssignments = ref<TemporaryRoleAssignment[]>([])
const users = ref<RbacUserSummary[]>([])
const roles = ref<Role[]>([])
const loading = ref(true)
const errorMessage = ref('')
const successMessage = ref('')

const showCreateModal = ref(false)
const form = ref({
  user_id: '',
  role_id: 0,
  starts_at: new Date().toISOString().slice(0, 16),
  expires_at: new Date(Date.now() + 86400000 * 7).toISOString().slice(0, 16),
  reason: '',
})

const fetchData = async () => {
  loading.value = true
  errorMessage.value = ''
  try {
    const [assignmentsData, usersData, rolesData] = await Promise.all([
      rbacService.getTemporaryRoles(),
      rbacService.getUserRoleSummaries(),
      rbacService.getRoles(),
    ])
    tempAssignments.value = assignmentsData
    users.value = usersData
    roles.value = rolesData
    if (usersData.length > 0 && !form.value.user_id) {
      form.value.user_id = usersData[0].id
    }
    if (rolesData.length > 0 && !form.value.role_id) {
      form.value.role_id = rolesData[0].id
    }
  } catch (err: any) {
    console.error('[TemporaryRoleAssignment] Fetch data error:', err)
    errorMessage.value =
      err?.response?.data?.message || 'Failed to load temporary role assignments.'
  } finally {
    loading.value = false
  }
}

import { useHotelStore } from '../../../stores/hotelStore'
const hotelStore = useHotelStore()

onMounted(() => {
  fetchData()
})

watch(
  () => hotelStore.hotelId,
  () => {
    fetchData()
  },
)

const handleCreateTemporaryRole = async () => {
  if (!form.value.user_id || !form.value.role_id || !form.value.reason.trim()) return
  loading.value = true
  errorMessage.value = ''
  try {
    await rbacService.createTemporaryRole({
      user_id: form.value.user_id,
      role_id: form.value.role_id,
      starts_at: form.value.starts_at,
      expires_at: form.value.expires_at,
      reason: form.value.reason,
    })
    successMessage.value = 'Temporary role assignment delegated successfully!'
    showCreateModal.value = false
    await fetchData()
    setTimeout(() => {
      successMessage.value = ''
    }, 3500)
  } catch (err: any) {
    console.error('[TemporaryRoleAssignment] Create error:', err)
    errorMessage.value = err?.response?.data?.message || 'Failed to delegate temporary role.'
  } finally {
    loading.value = false
  }
}

const handleRevoke = async (assignment: TemporaryRoleAssignment) => {
  if (!confirm(`Revoke temporary role delegation for ${assignment.user?.full_name}?`)) return
  loading.value = true
  errorMessage.value = ''
  try {
    await rbacService.revokeTemporaryRole(assignment.id)
    successMessage.value = 'Temporary role revoked.'
    await fetchData()
    setTimeout(() => {
      successMessage.value = ''
    }, 3500)
  } catch (err: any) {
    console.error('[TemporaryRoleAssignment] Revoke error:', err)
    errorMessage.value = err?.response?.data?.message || 'Failed to revoke temporary role.'
  } finally {
    loading.value = false
  }
}
</script>

<template>
  <DashboardLayout>
    <template #header>
      <div
        class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 py-4 border-b border-slate-200 dark:border-slate-800"
      >
        <div class="space-y-1">
          <div class="flex items-center gap-2">
            <span class="p-2 rounded-xl bg-purple-500/10 text-purple-600 dark:text-purple-400">
              <Clock class="w-5 h-5" />
            </span>
            <h1
              class="text-xl sm:text-2xl font-black tracking-tight text-slate-900 dark:text-white"
            >
              Temporary Role Delegations
            </h1>
          </div>
          <p class="text-xs sm:text-sm text-slate-500 dark:text-slate-400 font-medium">
            Grant temporary role elevations or delegations (e.g. Acting Manager during leave) with
            automatic expiration.
          </p>
        </div>

        <div class="flex items-center gap-2">
          <button
            @click="fetchData"
            class="p-2.5 rounded-xl border border-slate-200 dark:border-slate-800 hover:bg-slate-100 dark:hover:bg-slate-800 text-slate-600 dark:text-slate-300 transition"
            title="Refresh"
          >
            <RefreshCw class="w-4 h-4" :class="{ 'animate-spin': loading }" />
          </button>

          <button
            @click="showCreateModal = true"
            class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl bg-purple-600 hover:bg-purple-700 text-white font-extrabold text-xs shadow-md shadow-purple-500/20 transition cursor-pointer"
          >
            <Plus class="w-4 h-4" />
            <span>Delegate Temporary Role</span>
          </button>
        </div>
      </div>
    </template>

    <div class="py-6 space-y-6">
      <div
        v-if="successMessage"
        class="p-4 rounded-2xl bg-emerald-500/10 border border-emerald-500/30 text-emerald-700 dark:text-emerald-300 text-xs font-semibold flex items-center gap-2"
      >
        <CheckCircle2 class="w-4 h-4 text-emerald-500 flex-shrink-0" />
        <span>{{ successMessage }}</span>
      </div>

      <div
        v-if="errorMessage"
        class="p-4 rounded-2xl bg-rose-500/10 border border-rose-500/30 text-rose-700 dark:text-rose-300 text-xs font-semibold flex items-center gap-2"
      >
        <AlertCircle class="w-4 h-4 text-rose-500 flex-shrink-0" />
        <span>{{ errorMessage }}</span>
      </div>

      <div
        class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-3xl overflow-hidden shadow-sm"
      >
        <table class="w-full text-left border-collapse">
          <thead>
            <tr
              class="bg-slate-50 dark:bg-slate-950/80 border-b border-slate-200 dark:border-slate-800 text-xs font-black uppercase text-slate-700 dark:text-slate-300"
            >
              <th class="p-4">Target User</th>
              <th class="p-4">Delegated Role</th>
              <th class="p-4">Starts At</th>
              <th class="p-4">Expires At</th>
              <th class="p-4">Reason / Notes</th>
              <th class="p-4 text-right">Actions</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-100 dark:divide-slate-800/60 text-xs">
            <tr v-if="loading">
              <td colspan="6" class="px-6 py-16 text-center">
                <div class="flex flex-col items-center justify-center gap-3">
                  <Loader2 class="w-8 h-8 text-purple-600 dark:text-purple-400 animate-spin" />
                  <span class="text-xs font-bold text-slate-600 dark:text-slate-400"
                    >Loading temporary delegations...</span
                  >
                </div>
              </td>
            </tr>

            <template v-else>
              <tr
                v-for="ta in tempAssignments"
                :key="ta.id"
                class="hover:bg-slate-50/50 dark:hover:bg-slate-800/30 transition"
              >
                <td class="p-4 font-extrabold text-slate-900 dark:text-white">
                  {{ ta.user?.full_name || 'N/A' }}
                </td>

                <td class="p-4">
                  <span
                    class="px-2.5 py-1 rounded-xl bg-purple-500/10 border border-purple-500/30 text-purple-700 dark:text-purple-300 font-extrabold"
                  >
                    {{ ta.role?.name || ta.role_name || 'Role' }}
                  </span>
                </td>

                <td class="p-4 font-mono text-slate-600 dark:text-slate-400">
                  {{ new Date(ta.starts_at).toLocaleString() }}
                </td>

                <td class="p-4 font-mono font-bold text-amber-600 dark:text-amber-400">
                  {{ new Date(ta.expires_at).toLocaleString() }}
                </td>

                <td class="p-4 text-slate-600 dark:text-slate-400">
                  {{ ta.reason || 'No reason provided' }}
                </td>

                <td class="p-4 text-right">
                  <button
                    @click="handleRevoke(ta)"
                    class="p-2 rounded-xl text-rose-500 hover:bg-rose-500/10 transition cursor-pointer"
                    title="Revoke Delegation"
                  >
                    <Trash2 class="w-4 h-4" />
                  </button>
                </td>
              </tr>

              <tr v-if="tempAssignments.length === 0">
                <td colspan="6" class="p-8 text-center text-slate-400 font-medium">
                  No active temporary role delegations.
                </td>
              </tr>
            </template>
          </tbody>
        </table>
      </div>

      <div
        v-if="showCreateModal"
        class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/60 backdrop-blur-xs"
      >
        <div
          class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-3xl p-6 sm:p-8 max-w-md w-full space-y-5 shadow-2xl"
        >
          <div
            class="flex items-center justify-between pb-3 border-b border-slate-200 dark:border-slate-800"
          >
            <h2 class="text-lg font-black text-slate-900 dark:text-white">
              Delegate Temporary Role
            </h2>
            <button
              @click="showCreateModal = false"
              class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-200"
            >
              <XCircle class="w-5 h-5" />
            </button>
          </div>

          <div class="space-y-3">
            <div class="space-y-1">
              <label class="block text-xs font-bold text-slate-700 dark:text-slate-300"
                >Target User</label
              >
              <select
                v-model="form.user_id"
                class="w-full px-3 py-2 text-xs bg-slate-50 dark:bg-slate-950 border border-slate-300 dark:border-slate-800 rounded-xl text-slate-900 dark:text-white focus:outline-none font-bold"
              >
                <option v-for="u in users" :key="u.id" :value="u.id">
                  {{ u.full_name }} ({{ u.email }})
                </option>
              </select>
            </div>

            <div class="space-y-1">
              <label class="block text-xs font-bold text-slate-700 dark:text-slate-300"
                >Temporary Role to Grant</label
              >
              <select
                v-model="form.role_id"
                class="w-full px-3 py-2 text-xs bg-slate-50 dark:bg-slate-950 border border-slate-300 dark:border-slate-800 rounded-xl text-slate-900 dark:text-white focus:outline-none font-bold"
              >
                <option v-for="r in roles" :key="r.id" :value="r.id">
                  {{ r.name }}
                </option>
              </select>
            </div>

            <div class="grid grid-cols-2 gap-2">
              <div class="space-y-1">
                <label class="block text-xs font-bold text-slate-700 dark:text-slate-300"
                  >Start Date</label
                >
                <input
                  v-model="form.starts_at"
                  type="datetime-local"
                  class="w-full px-2 py-2 text-xs bg-slate-50 dark:bg-slate-950 border border-slate-300 dark:border-slate-800 rounded-xl text-slate-900 dark:text-white font-medium"
                />
              </div>

              <div class="space-y-1">
                <label class="block text-xs font-bold text-slate-700 dark:text-slate-300"
                  >Expiration Date</label
                >
                <input
                  v-model="form.expires_at"
                  type="datetime-local"
                  class="w-full px-2 py-2 text-xs bg-slate-50 dark:bg-slate-950 border border-slate-300 dark:border-slate-800 rounded-xl text-slate-900 dark:text-white font-medium"
                />
              </div>
            </div>

            <div class="space-y-1">
              <label class="block text-xs font-bold text-slate-700 dark:text-slate-300"
                >Delegation Reason / Context</label
              >
              <textarea
                v-model="form.reason"
                rows="2"
                placeholder="e.g. Acting Manager during annual leave"
                class="w-full px-3 py-2 text-xs bg-slate-50 dark:bg-slate-950 border border-slate-300 dark:border-slate-800 rounded-xl text-slate-900 dark:text-white focus:outline-none font-medium"
              ></textarea>
            </div>
          </div>

          <div
            class="flex items-center justify-end gap-3 pt-3 border-t border-slate-200 dark:border-slate-800"
          >
            <button
              @click="showCreateModal = false"
              class="px-4 py-2 rounded-xl text-xs font-bold text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800 transition"
            >
              Cancel
            </button>
            <button
              @click="handleCreateTemporaryRole"
              :disabled="loading"
              class="px-5 py-2 rounded-xl bg-purple-600 hover:bg-purple-700 text-white text-xs font-black shadow-md shadow-purple-500/20 transition cursor-pointer"
            >
              Delegate Role
            </button>
          </div>
        </div>
      </div>
    </div>
  </DashboardLayout>
</template>

<script setup lang="ts">
import { ref, computed, onMounted, watch } from 'vue'
import DashboardLayout from '@/Layouts/DashboardLayout.vue'
import { rbacService } from '@/services/rbacService'
import { useHotelStore } from '@/stores/hotelStore'
import type { Role, Permission } from '@/types/rbacTypes'
import {
  ShieldCheck,
  Search,
  RefreshCw,
  Save,
  Check,
  CheckCircle2,
  AlertCircle,
  Filter,
  Layers,
  Crown
} from 'lucide-vue-next'

const roles = ref<Role[]>([])
const permissions = ref<Permission[]>([])
const loading = ref(true)
const saving = ref(false)
const searchQuery = ref('')
const selectedModule = ref('all')
const successMessage = ref('')
const errorMessage = ref('')

// Matrix state: roleId -> Set of permissionIds
const matrix = ref<Record<number, Set<number>>>({})
const initialMatrix = ref<Record<number, Set<number>>>({})

const loadData = async () => {
  loading.value = true
  errorMessage.value = ''
  try {
    const [rolesData, permsRes] = await Promise.all([
      rbacService.getRoles(),
      rbacService.getPermissions(),
    ])

    roles.value = rolesData || []
    permissions.value = permsRes.data || []

    const m: Record<number, Set<number>> = {}
    const initM: Record<number, Set<number>> = {}

    roles.value.forEach(r => {
      const permIds = r.permissions
        ? r.permissions.map((p: any) => (typeof p === 'number' ? p : p.id))
        : []
      m[r.id] = new Set(permIds)
      initM[r.id] = new Set(permIds)
    })

    matrix.value = m
    initialMatrix.value = initM
  } catch (err: any) {
    console.error('Failed to load matrix data:', err)
    errorMessage.value = err?.response?.data?.message || 'Failed to load roles and permissions matrix.'
  } finally {
    loading.value = false
  }
}

const hotelStore = useHotelStore()

onMounted(loadData)

watch(() => hotelStore.hotelId, () => {
  loadData()
})

// Available modules
const modules = computed(() => {
  const s = new Set<string>()
  permissions.value.forEach(p => {
    if (p.module) s.add(p.module)
  })
  return Array.from(s).sort()
})

// Filtered permissions
const filteredPermissions = computed(() => {
  let list = permissions.value

  if (selectedModule.value !== 'all') {
    list = list.filter(p => p.module === selectedModule.value)
  }

  if (searchQuery.value.trim()) {
    const q = searchQuery.value.toLowerCase().trim()
    list = list.filter(
      p =>
        p.name.toLowerCase().includes(q) ||
        p.slug.toLowerCase().includes(q) ||
        (p.description && p.description.toLowerCase().includes(q))
    )
  }

  return list
})

// Check if a cell is checked
const hasPermission = (roleId: number, permissionId: number): boolean => {
  return matrix.value[roleId]?.has(permissionId) ?? false
}

// Toggle permission for a role
const togglePermission = (roleId: number, permissionId: number) => {
  if (!matrix.value[roleId]) {
    matrix.value[roleId] = new Set()
  }
  const set = matrix.value[roleId]
  if (set.has(permissionId)) {
    set.delete(permissionId)
  } else {
    set.add(permissionId)
  }
}

// Toggle all filtered permissions for a role
const toggleAllForRole = (roleId: number) => {
  if (!matrix.value[roleId]) {
    matrix.value[roleId] = new Set()
  }
  const set = matrix.value[roleId]
  const currentFilteredIds = filteredPermissions.value.map(p => p.id)
  const allChecked = currentFilteredIds.every(id => set.has(id))

  if (allChecked) {
    currentFilteredIds.forEach(id => set.delete(id))
  } else {
    currentFilteredIds.forEach(id => set.add(id))
  }
}

// Check if matrix has unsaved changes
const hasChanges = computed(() => {
  for (const r of roles.value) {
    const currentSet = matrix.value[r.id] || new Set()
    const initialSet = initialMatrix.value[r.id] || new Set()

    if (currentSet.size !== initialSet.size) return true
    for (const id of currentSet) {
      if (!initialSet.has(id)) return true
    }
  }
  return false
})

// Save & Sync Matrix
const saveMatrix = async () => {
  saving.value = true
  errorMessage.value = ''
  successMessage.value = ''

  try {
    const promises = roles.value.map(r => {
      const permissionIds = Array.from(matrix.value[r.id] || [])
      return rbacService.syncRolePermissions(r.id, permissionIds)
    })

    await Promise.all(promises)
    successMessage.value = 'All role permissions updated and synced successfully across the platform!'
    await loadData()
    setTimeout(() => {
      successMessage.value = ''
    }, 4000)
  } catch (err: any) {
    console.error('Failed to sync matrix:', err)
    errorMessage.value = err?.response?.data?.message || 'Failed to save role permission matrix.'
  } finally {
    saving.value = false
  }
}

// Reset changes
const resetMatrix = () => {
  const m: Record<number, Set<number>> = {}
  roles.value.forEach(r => {
    m[r.id] = new Set(Array.from(initialMatrix.value[r.id] || []))
  })
  matrix.value = m
}
</script>

<template>
  <DashboardLayout>
    <div class="space-y-6 bg-slate-50 dark:bg-slate-950 min-h-screen p-4 sm:p-6 max-w-full font-sans">
      <!-- Header Banner Section -->
      <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 p-6 rounded-3xl shadow-xs flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
        <div>
          <div class="flex items-center gap-2.5">
            <h1 class="text-2xl font-black text-slate-900 dark:text-white tracking-tight">
              Role & Permission Security Matrix
            </h1>
            <span class="px-2.5 py-0.5 rounded-full text-xs font-black bg-indigo-500/10 text-indigo-600 dark:text-indigo-400 border border-indigo-500/20">
              Super Admin Governance
            </span>
          </div>
          <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">
            Dynamic access matrix to inspect, toggle, and sync permissions across all platform roles in real time.
          </p>
        </div>

        <div class="flex flex-wrap items-center gap-2">
          <button
            @click="loadData"
            :disabled="loading"
            class="p-2.5 rounded-xl bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-300 transition cursor-pointer border border-slate-200 dark:border-slate-700 font-bold text-xs flex items-center gap-1.5"
          >
            <RefreshCw :class="['w-4 h-4', loading && 'animate-spin']" />
            <span>Refresh</span>
          </button>

          <button
            v-if="hasChanges"
            @click="resetMatrix"
            class="px-3.5 py-2 rounded-xl bg-slate-200 dark:bg-slate-800 hover:bg-slate-300 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-200 font-bold text-xs transition border border-slate-300 dark:border-slate-700"
          >
            Reset
          </button>

          <button
            @click="saveMatrix"
            :disabled="saving || !hasChanges"
            class="px-4 py-2 rounded-xl bg-indigo-600 hover:bg-indigo-500 disabled:opacity-50 text-white font-extrabold text-xs shadow-md shadow-indigo-600/20 transition flex items-center gap-1.5"
          >
            <Save :class="['w-3.5 h-3.5', saving && 'animate-spin']" />
            <span>{{ saving ? 'Saving Changes...' : 'Save & Sync Matrix' }}</span>
          </button>
        </div>
      </div>

      <!-- Feedback Alerts -->
      <div v-if="successMessage" class="p-4 rounded-2xl bg-emerald-500/10 border border-emerald-500/20 text-emerald-600 dark:text-emerald-400 text-xs font-bold flex items-center gap-2">
        <CheckCircle2 class="w-4 h-4 flex-shrink-0" />
        <span>{{ successMessage }}</span>
      </div>

      <div v-if="errorMessage" class="p-4 rounded-2xl bg-rose-500/10 border border-rose-500/20 text-rose-600 dark:text-rose-400 text-xs font-bold flex items-center gap-2">
        <AlertCircle class="w-4 h-4 flex-shrink-0" />
        <span>{{ errorMessage }}</span>
      </div>

      <!-- Search & Filters Toolbar -->
      <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 p-4 rounded-2xl shadow-xs flex flex-col sm:flex-row items-center justify-between gap-3">
        <div class="relative w-full sm:w-80">
          <Search class="w-4 h-4 text-slate-400 absolute left-3 top-2.5" />
          <input
            v-model="searchQuery"
            type="text"
            placeholder="Search permissions or slug..."
            class="w-full pl-9 pr-3 py-2 bg-slate-100 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl text-xs text-slate-900 dark:text-white placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-indigo-500"
          />
        </div>

        <div class="flex items-center gap-2 w-full sm:w-auto">
          <Filter class="w-4 h-4 text-slate-400" />
          <select
            v-model="selectedModule"
            class="px-3 py-2 bg-slate-100 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl text-xs text-slate-900 dark:text-white font-medium focus:outline-none"
          >
            <option value="all">All Modules ({{ permissions.length }})</option>
            <option v-for="m in modules" :key="m" :value="m">
              {{ m.toUpperCase() }}
            </option>
          </select>
        </div>
      </div>

      <!-- Loading State -->
      <div v-if="loading" class="p-16 text-center bg-white dark:bg-slate-900 rounded-3xl border border-slate-200 dark:border-slate-800">
        <RefreshCw class="w-8 h-8 text-indigo-500 animate-spin mx-auto mb-3" />
        <p class="text-xs font-bold text-slate-400">Loading security matrix...</p>
      </div>

      <!-- MATRIX TABLE -->
      <div v-else class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-3xl shadow-xs overflow-hidden">
        <div class="overflow-x-auto">
          <table class="w-full text-left text-xs border-collapse">
            <thead class="bg-slate-100/70 dark:bg-slate-800/80 text-slate-600 dark:text-slate-300 font-bold border-b border-slate-200 dark:border-slate-700/80 sticky top-0 z-10">
              <tr>
                <th class="py-3.5 px-4 min-w-[280px]">
                  Permission & Domain
                </th>
                <th
                  v-for="role in roles"
                  :key="role.id"
                  class="py-3.5 px-3 text-center min-w-[120px]"
                >
                  <div class="flex flex-col items-center gap-1">
                    <span class="text-xs font-black text-slate-900 dark:text-white flex items-center gap-1">
                      <Crown v-if="role.slug === 'admin'" class="w-3 h-3 text-amber-500 inline" />
                      {{ role.name }}
                    </span>
                    <button
                      @click="toggleAllForRole(role.id)"
                      class="text-[10px] text-indigo-600 dark:text-indigo-400 hover:underline font-semibold"
                    >
                      Toggle All
                    </button>
                  </div>
                </th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-100 dark:divide-slate-800 text-slate-700 dark:text-slate-300">
              <tr
                v-for="perm in filteredPermissions"
                :key="perm.id"
                class="hover:bg-slate-50/60 dark:hover:bg-slate-800/40 transition"
              >
                <!-- Permission Info -->
                <td class="py-3 px-4">
                  <div class="font-bold text-slate-900 dark:text-white">
                    {{ perm.name }}
                  </div>
                  <div class="flex items-center gap-1.5 mt-0.5">
                    <span class="px-1.5 py-0.5 rounded text-[10px] font-mono bg-slate-100 dark:bg-slate-800 text-slate-500 dark:text-slate-400">
                      {{ perm.slug }}
                    </span>
                    <span class="text-[10px] uppercase font-bold text-indigo-500 dark:text-indigo-400">
                      {{ perm.module }}
                    </span>
                  </div>
                </td>

                <!-- Checkbox for each role -->
                <td
                  v-for="role in roles"
                  :key="role.id"
                  class="py-3 px-3 text-center"
                >
                  <label class="inline-flex items-center justify-center cursor-pointer p-1 rounded-lg hover:bg-slate-100 dark:hover:bg-slate-800 transition">
                    <input
                      type="checkbox"
                      :checked="hasPermission(role.id, perm.id)"
                      @change="togglePermission(role.id, perm.id)"
                      class="w-4 h-4 rounded text-indigo-600 focus:ring-indigo-500 cursor-pointer accent-indigo-600"
                    />
                  </label>
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <div v-if="filteredPermissions.length === 0" class="p-12 text-center text-xs text-slate-400">
          No permissions match the selected filter or search query.
        </div>
      </div>
    </div>
  </DashboardLayout>
</template>

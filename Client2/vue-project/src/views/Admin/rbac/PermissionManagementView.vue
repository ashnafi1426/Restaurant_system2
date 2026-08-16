<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import DashboardLayout from '../../../Layouts/DashboardLayout.vue'
import { rbacService } from '../../../services/rbacService'
import type { Permission } from '../../../types/rbacTypes'
import {
  Key,
  Plus,
  Search,
  Filter,
  RefreshCw,
  CheckCircle2,
  AlertCircle,
  XCircle,
  FolderTree
} from 'lucide-vue-next'

const permissions = ref<Permission[]>([])
const groupedPermissions = ref<Record<string, Permission[]>>({})
const loading = ref(true)
const errorMessage = ref('')
const successMessage = ref('')
const searchFilter = ref('')
const selectedModule = ref('all')

// Modal state
const showCreateModal = ref(false)
const permForm = ref({
  name: '',
  module: 'orders',
  action: 'create',
  description: '',
})

const fetchPermissions = async () => {
  loading.value = true
  errorMessage.value = ''
  try {
    const res = await rbacService.getPermissions()
    permissions.value = res.data
    groupedPermissions.value = res.grouped
  } catch (err: any) {
    errorMessage.value = err?.response?.data?.message || 'Failed to load permissions catalog.'
  } finally {
    loading.value = false
  }
}

onMounted(() => {
  fetchPermissions()
})

const availableModules = computed(() => {
  return Object.keys(groupedPermissions.value)
})

const filteredPermissions = computed(() => {
  return permissions.value.filter(p => {
    const matchesSearch = p.name.toLowerCase().includes(searchFilter.value.toLowerCase()) ||
                          p.slug.toLowerCase().includes(searchFilter.value.toLowerCase()) ||
                          p.module.toLowerCase().includes(searchFilter.value.toLowerCase())

    const matchesModule = selectedModule.value === 'all' || p.module === selectedModule.value

    return matchesSearch && matchesModule
  })
})

const filteredGroupedPermissions = computed(() => {
  const groups: Record<string, Permission[]> = {}
  filteredPermissions.value.forEach(p => {
    if (!groups[p.module]) {
      groups[p.module] = []
    }
    groups[p.module].push(p)
  })
  return groups
})

const handleCreatePermission = async () => {
  if (!permForm.value.name.trim() || !permForm.value.module.trim() || !permForm.value.action.trim()) return
  loading.value = true
  errorMessage.value = ''
  try {
    await rbacService.createPermission({
      name: permForm.value.name,
      module: permForm.value.module,
      action: permForm.value.action,
      description: permForm.value.description,
    })
    successMessage.value = `Permission "${permForm.value.name}" created successfully!`
    showCreateModal.value = false
    permForm.value = { name: '', module: 'orders', action: 'create', description: '' }
    await fetchPermissions()
    setTimeout(() => { successMessage.value = '' }, 3500)
  } catch (err: any) {
    errorMessage.value = err?.response?.data?.message || 'Failed to create permission.'
  } finally {
    loading.value = false
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
              <Key class="w-5 h-5" />
            </span>
            <h1 class="text-xl sm:text-2xl font-black tracking-tight text-slate-900 dark:text-white">
              Dynamic Permission Catalog
            </h1>
          </div>
          <p class="text-xs sm:text-sm text-slate-500 dark:text-slate-400 font-medium">
            Browse and dynamically register permission keys grouped automatically by module.
          </p>
        </div>

        <div class="flex items-center gap-2">
          <button
            @click="fetchPermissions"
            class="p-2.5 rounded-xl border border-slate-200 dark:border-slate-800 hover:bg-slate-100 dark:hover:bg-slate-800 text-slate-600 dark:text-slate-300 transition"
            title="Refresh Permissions"
          >
            <RefreshCw class="w-4 h-4" :class="{ 'animate-spin': loading }" />
          </button>

          <button
            @click="showCreateModal = true"
            class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl bg-amber-500 hover:bg-amber-600 text-slate-950 font-extrabold text-xs shadow-md shadow-amber-500/20 transition cursor-pointer"
          >
            <Plus class="w-4 h-4" />
            <span>Create New Permission</span>
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

      <!-- Filters Header -->
      <div class="flex flex-col sm:flex-row items-center justify-between gap-3 bg-white dark:bg-slate-900 p-4 rounded-2xl border border-slate-200 dark:border-slate-800 shadow-xs">
        <div class="relative w-full sm:w-80">
          <Search class="w-4 h-4 absolute left-3.5 top-3 text-slate-400" />
          <input
            v-model="searchFilter"
            type="text"
            placeholder="Search permissions or modules..."
            class="w-full pl-10 pr-4 py-2 text-xs bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-xl text-slate-900 dark:text-white focus:outline-none focus:border-amber-500 font-medium"
          />
        </div>

        <div class="flex items-center gap-2 w-full sm:w-auto">
          <Filter class="w-4 h-4 text-slate-400" />
          <select
            v-model="selectedModule"
            class="px-3 py-2 text-xs bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-xl text-slate-900 dark:text-white focus:outline-none focus:border-amber-500 font-bold"
          >
            <option value="all">All Modules ({{ permissions.length }})</option>
            <option v-for="mod in availableModules" :key="mod" :value="mod">
              {{ mod.toUpperCase() }} ({{ groupedPermissions[mod]?.length || 0 }})
            </option>
          </select>
        </div>
      </div>

      <!-- Grouped Permission List -->
      <div v-for="(perms, moduleName) in filteredGroupedPermissions" :key="moduleName" class="space-y-3">
        <div class="flex items-center gap-2 pt-2">
          <FolderTree class="w-4 h-4 text-amber-500" />
          <h3 class="text-sm font-black uppercase tracking-wider text-slate-900 dark:text-white">
            {{ String(moduleName).toUpperCase() }} MANAGEMENT
          </h3>
          <span class="text-xs text-slate-400 font-mono">({{ perms.length }} permissions)</span>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3">
          <div
            v-for="perm in perms"
            :key="perm.id"
            class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-4 shadow-2xs hover:border-amber-500/40 transition space-y-2"
          >
            <div class="flex items-start justify-between gap-2">
              <h4 class="text-xs font-extrabold text-slate-900 dark:text-white">
                {{ perm.name }}
              </h4>
              <span class="text-[10px] font-mono px-2 py-0.5 rounded-full bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-400 border border-slate-200 dark:border-slate-700">
                {{ perm.action }}
              </span>
            </div>

            <p class="text-[11px] font-mono text-amber-600 dark:text-amber-400 font-bold">
              {{ perm.slug }}
            </p>

            <p class="text-[11px] text-slate-500 dark:text-slate-400 line-clamp-2">
              {{ perm.description || 'System capability' }}
            </p>
          </div>
        </div>
      </div>

      <!-- Create Modal -->
      <div v-if="showCreateModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/60 backdrop-blur-xs">
        <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-3xl p-6 sm:p-8 max-w-md w-full space-y-5 shadow-2xl">
          <div class="flex items-center justify-between pb-3 border-b border-slate-200 dark:border-slate-800">
            <h2 class="text-lg font-black text-slate-900 dark:text-white">
              Create Custom Permission
            </h2>
            <button @click="showCreateModal = false" class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-200">
              <XCircle class="w-5 h-5" />
            </button>
          </div>

          <div class="space-y-3">
            <div class="space-y-1">
              <label class="block text-xs font-bold text-slate-700 dark:text-slate-300">Permission Name</label>
              <input
                v-model="permForm.name"
                type="text"
                placeholder="e.g. Export Daily Revenue"
                class="w-full px-4 py-2.5 text-sm bg-slate-50 dark:bg-slate-950 border border-slate-300 dark:border-slate-800 rounded-xl text-slate-900 dark:text-white focus:outline-none focus:border-amber-500 font-medium"
              />
            </div>

            <div class="grid grid-cols-2 gap-2">
              <div class="space-y-1">
                <label class="block text-xs font-bold text-slate-700 dark:text-slate-300">Module</label>
                <input
                  v-model="permForm.module"
                  type="text"
                  placeholder="e.g. reports"
                  class="w-full px-3 py-2 text-xs bg-slate-50 dark:bg-slate-950 border border-slate-300 dark:border-slate-800 rounded-xl text-slate-900 dark:text-white focus:outline-none font-medium"
                />
              </div>

              <div class="space-y-1">
                <label class="block text-xs font-bold text-slate-700 dark:text-slate-300">Action</label>
                <input
                  v-model="permForm.action"
                  type="text"
                  placeholder="e.g. export"
                  class="w-full px-3 py-2 text-xs bg-slate-50 dark:bg-slate-950 border border-slate-300 dark:border-slate-800 rounded-xl text-slate-900 dark:text-white focus:outline-none font-medium"
                />
              </div>
            </div>

            <div class="space-y-1">
              <label class="block text-xs font-bold text-slate-700 dark:text-slate-300">Description</label>
              <textarea
                v-model="permForm.description"
                rows="2"
                placeholder="Capability description..."
                class="w-full px-4 py-2 text-xs bg-slate-50 dark:bg-slate-950 border border-slate-300 dark:border-slate-800 rounded-xl text-slate-900 dark:text-white focus:outline-none font-medium"
              ></textarea>
            </div>
          </div>

          <div class="flex items-center justify-end gap-3 pt-3 border-t border-slate-200 dark:border-slate-800">
            <button
              @click="showCreateModal = false"
              class="px-4 py-2 rounded-xl text-xs font-bold text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800 transition"
            >
              Cancel
            </button>
            <button
              @click="handleCreatePermission"
              :disabled="loading"
              class="px-5 py-2 rounded-xl bg-amber-500 hover:bg-amber-600 text-slate-950 text-xs font-black shadow-md shadow-amber-500/20 transition cursor-pointer"
            >
              Create Permission
            </button>
          </div>
        </div>
      </div>
    </div>
  </DashboardLayout>
</template>

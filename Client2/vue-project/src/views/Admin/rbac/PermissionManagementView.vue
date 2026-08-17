<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import DashboardLayout from '@/Layouts/DashboardLayout.vue'
import { rbacService } from '@/services/rbacService'
import type { Permission } from '@/types/rbacTypes'
import {
  Key,
  Plus,
  Search,
  Filter,
  RefreshCw,
  CheckCircle2,
  AlertCircle,
  XCircle,
  FolderTree,
  Edit,
  Trash2,
  Shield
} from 'lucide-vue-next'

const permissions = ref<Permission[]>([])
const groupedPermissions = ref<Record<string, Permission[]>>({})
const loading = ref(true)
const saving = ref(false)
const errorMessage = ref('')
const successMessage = ref('')
const searchFilter = ref('')
const selectedModule = ref('all')

// Modal state
const showCreateModal = ref(false)
const editingPermission = ref<Permission | null>(null)
const permForm = ref({
  name: '',
  module: '',
  action: '',
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

const openCreateModal = () => {
  console.log('🔵 Opening create modal...')
  editingPermission.value = null
  permForm.value = {
    name: '',
    module: '',
    action: '',
    description: '',
  }
  showCreateModal.value = true
  console.log('🔵 Modal state:', showCreateModal.value)
}

const openEditModal = (perm: Permission) => {
  editingPermission.value = perm
  permForm.value = {
    name: perm.name,
    module: perm.module,
    action: perm.action,
    description: perm.description || '',
  }
  showCreateModal.value = true
}

const handleSavePermission = async () => {
  if (!permForm.value.name.trim() || !permForm.value.module.trim() || !permForm.value.action.trim()) {
    errorMessage.value = 'Please fill in all required fields.'
    return
  }
  
  saving.value = true
  errorMessage.value = ''
  try {
    if (editingPermission.value) {
      await rbacService.updatePermission(editingPermission.value.id, {
        name: permForm.value.name,
        module: permForm.value.module,
        action: permForm.value.action,
        description: permForm.value.description,
      })
      successMessage.value = `Permission "${permForm.value.name}" updated successfully!`
    } else {
      await rbacService.createPermission({
        name: permForm.value.name,
        module: permForm.value.module,
        action: permForm.value.action,
        description: permForm.value.description,
      })
      successMessage.value = `Permission "${permForm.value.name}" created successfully!`
    }
    
    showCreateModal.value = false
    permForm.value = { name: '', module: '', action: '', description: '' }
    await fetchPermissions()
    setTimeout(() => { successMessage.value = '' }, 3500)
  } catch (err: any) {
    errorMessage.value = err?.response?.data?.message || 'Failed to save permission.'
  } finally {
    saving.value = false
  }
}

const handleDeletePermission = async (perm: Permission) => {
  if (!confirm(`Are you sure you want to delete permission "${perm.name}"?`)) return
  
  loading.value = true
  errorMessage.value = ''
  try {
    await rbacService.deletePermission(perm.id)
    successMessage.value = `Permission "${perm.name}" deleted successfully.`
    await fetchPermissions()
    setTimeout(() => { successMessage.value = '' }, 3500)
  } catch (err: any) {
    errorMessage.value = err?.response?.data?.message || 'Failed to delete permission.'
  } finally {
    loading.value = false
  }
}
</script>

<template>
  <DashboardLayout>
    <div class="min-h-screen bg-slate-50 dark:bg-slate-950 p-6 space-y-6">
      <!-- HEADER -->
      <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 py-4 border-b border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 px-6 rounded-2xl">
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
            type="button"
            class="p-2.5 rounded-xl border border-slate-200 dark:border-slate-800 hover:bg-slate-100 dark:hover:bg-slate-800 text-slate-600 dark:text-slate-300 transition"
            title="Refresh Permissions"
          >
            <RefreshCw class="w-4 h-4" :class="{ 'animate-spin': loading }" />
          </button>

          <button
            @click="openCreateModal"
            type="button"
            class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl bg-amber-500 hover:bg-amber-600 text-slate-950 font-extrabold text-xs shadow-md shadow-amber-500/20 transition cursor-pointer"
          >
            <Plus class="w-4 h-4" />
            <span>Create New Permission</span>
          </button>
        </div>
      </div>

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
            class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-4 shadow-2xs hover:border-amber-500/40 transition space-y-3"
          >
            <div class="flex items-start justify-between gap-2">
              <div class="flex-1">
                <h4 class="text-xs font-extrabold text-slate-900 dark:text-white">
                  {{ perm.name }}
                </h4>
                <p class="text-[11px] font-mono text-amber-600 dark:text-amber-400 font-bold mt-1">
                  {{ perm.slug }}
                </p>
              </div>
              <span class="text-[10px] font-mono px-2 py-0.5 rounded-full bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-400 border border-slate-200 dark:border-slate-700">
                {{ perm.action }}
              </span>
            </div>

            <p class="text-[11px] text-slate-500 dark:text-slate-400 line-clamp-2">
              {{ perm.description || 'System capability' }}
            </p>

            <div class="flex items-center gap-2 pt-2 border-t border-slate-100 dark:border-slate-800">
              <button
                @click="openEditModal(perm)"
                type="button"
                class="flex-1 inline-flex items-center justify-center gap-1.5 px-3 py-2 rounded-xl bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 dark:hover:bg-slate-700 text-slate-800 dark:text-slate-200 text-xs font-bold transition"
              >
                <Edit class="w-3.5 h-3.5" />
                <span>Edit</span>
              </button>
              <button
                @click="handleDeletePermission(perm)"
                type="button"
                class="p-2 rounded-xl text-rose-500 hover:bg-rose-500/10 transition"
                title="Delete Permission"
              >
                <Trash2 class="w-4 h-4" />
              </button>
            </div>
          </div>
        </div>
      </div>

      <!-- Create/Edit Modal -->
      <Teleport to="body">
        <Transition name="modal">
          <div 
            v-if="showCreateModal" 
            class="fixed inset-0 z-[9999] flex items-center justify-center p-4"
            @click.self="showCreateModal = false"
          >
            <!-- Backdrop -->
            <div class="absolute inset-0 bg-black/60 backdrop-blur-sm"></div>
            
            <!-- Modal Content -->
            <div class="relative bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-3xl p-6 sm:p-8 max-w-lg w-full space-y-5 shadow-2xl">
              <div class="flex items-center justify-between pb-3 border-b border-slate-200 dark:border-slate-800">
                <div class="flex items-center gap-3">
                  <div class="p-2 rounded-xl bg-amber-500/10 text-amber-600 dark:text-amber-400">
                    <Shield class="w-5 h-5" />
                  </div>
                  <h2 class="text-lg font-black text-slate-900 dark:text-white">
                    {{ editingPermission ? 'Edit Permission' : 'Create New Permission' }}
                  </h2>
                </div>
                <button 
                  @click="showCreateModal = false"
                  type="button" 
                  class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 transition"
                >
                  <XCircle class="w-5 h-5" />
                </button>
              </div>

              <div class="space-y-4">
                <div class="space-y-1.5">
                  <label class="block text-xs font-bold text-slate-700 dark:text-slate-300">
                    Permission Name <span class="text-rose-500">*</span>
                  </label>
                  <input
                    v-model="permForm.name"
                    type="text"
                    placeholder="e.g. View User Profile"
                    class="w-full px-4 py-2.5 text-sm bg-slate-50 dark:bg-slate-950 border border-slate-300 dark:border-slate-700 rounded-xl text-slate-900 dark:text-white focus:outline-none focus:border-amber-500 focus:ring-2 focus:ring-amber-500/20 font-medium"
                    :class="{ 'border-rose-500': !permForm.name.trim() && saving }"
                  />
                </div>

                <div class="grid grid-cols-2 gap-3">
                  <div class="space-y-1.5">
                    <label class="block text-xs font-bold text-slate-700 dark:text-slate-300">
                      Module <span class="text-rose-500">*</span>
                    </label>
                    <input
                      v-model="permForm.module"
                      type="text"
                      placeholder="e.g. users"
                      class="w-full px-3 py-2.5 text-sm bg-slate-50 dark:bg-slate-950 border border-slate-300 dark:border-slate-700 rounded-xl text-slate-900 dark:text-white focus:outline-none focus:border-amber-500 focus:ring-2 focus:ring-amber-500/20 font-medium"
                      :class="{ 'border-rose-500': !permForm.module.trim() && saving }"
                    />
                    <p class="text-[10px] text-slate-500 dark:text-slate-400 mt-0.5">lowercase, no spaces</p>
                  </div>

                  <div class="space-y-1.5">
                    <label class="block text-xs font-bold text-slate-700 dark:text-slate-300">
                      Action <span class="text-rose-500">*</span>
                    </label>
                    <input
                      v-model="permForm.action"
                      type="text"
                      placeholder="e.g. view"
                      class="w-full px-3 py-2.5 text-sm bg-slate-50 dark:bg-slate-950 border border-slate-300 dark:border-slate-700 rounded-xl text-slate-900 dark:text-white focus:outline-none focus:border-amber-500 focus:ring-2 focus:ring-amber-500/20 font-medium"
                      :class="{ 'border-rose-500': !permForm.action.trim() && saving }"
                    />
                    <p class="text-[10px] text-slate-500 dark:text-slate-400 mt-0.5">e.g. view, create, edit</p>
                  </div>
                </div>

                <div class="space-y-1.5">
                  <label class="block text-xs font-bold text-slate-700 dark:text-slate-300">
                    Description
                  </label>
                  <textarea
                    v-model="permForm.description"
                    rows="3"
                    placeholder="Brief description of what this permission allows..."
                    class="w-full px-4 py-2.5 text-sm bg-slate-50 dark:bg-slate-950 border border-slate-300 dark:border-slate-700 rounded-xl text-slate-900 dark:text-white focus:outline-none focus:border-amber-500 focus:ring-2 focus:ring-amber-500/20 font-medium resize-none"
                  ></textarea>
                </div>

                <div class="p-3 rounded-xl bg-blue-50 dark:bg-blue-950/30 border border-blue-200 dark:border-blue-800">
                  <p class="text-xs text-blue-700 dark:text-blue-300 font-medium">
                    <span class="font-bold">Slug will be auto-generated:</span> 
                    <code class="ml-1 font-mono">{{ permForm.module.toLowerCase() }}.{{ permForm.action.toLowerCase() }}</code>
                  </p>
                </div>
              </div>

              <div class="flex items-center justify-end gap-3 pt-3 border-t border-slate-200 dark:border-slate-800">
                <button
                  @click="showCreateModal = false"
                  type="button"
                  class="px-4 py-2.5 rounded-xl text-xs font-bold text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800 transition"
                >
                  Cancel
                </button>
                <button
                  @click="handleSavePermission"
                  type="button"
                  :disabled="saving || !permForm.name.trim() || !permForm.module.trim() || !permForm.action.trim()"
                  class="px-6 py-2.5 rounded-xl bg-amber-500 hover:bg-amber-600 text-slate-950 text-xs font-black shadow-md shadow-amber-500/20 transition disabled:opacity-50 disabled:cursor-not-allowed"
                >
                  {{ saving ? 'Saving...' : (editingPermission ? 'Update Permission' : 'Create Permission') }}
                </button>
              </div>
            </div>
          </div>
        </Transition>
      </Teleport>
    </div>
  </DashboardLayout>
</template>

<style scoped>
.modal-enter-active,
.modal-leave-active {
  transition: opacity 0.3s ease;
}

.modal-enter-from,
.modal-leave-to {
  opacity: 0;
}

.modal-enter-active .relative,
.modal-leave-active .relative {
  transition: transform 0.3s ease, opacity 0.3s ease;
}

.modal-enter-from .relative,
.modal-leave-to .relative {
  transform: scale(0.95);
  opacity: 0;
}
</style>

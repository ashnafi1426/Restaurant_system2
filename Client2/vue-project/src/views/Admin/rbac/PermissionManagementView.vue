<script setup lang="ts">
import { ref, computed, onMounted, watch } from 'vue'
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
  Edit,
  Trash2,
  Shield,
  MoreVertical,
  Check,
  ChevronDown,
  Maximize2,
  ChevronLeft,
  ChevronRight
} from 'lucide-vue-next'

const permissions = ref<Permission[]>([])
const loading = ref(true)
const saving = ref(false)
const errorMessage = ref('')
const successMessage = ref('')

// Selected Group (Left panel)
const selectedGroupKey = ref<string>('all')

// Search & Filter
const groupSearchQuery = ref('')
const permissionSearchQuery = ref('')
const selectedActionFilter = ref<string>('all')

// Selected Checkboxes
const selectedPermissionIds = ref<number[]>([])

// Group Sidebar Pagination State
const groupPage = ref(1)
const groupPerPage = ref(6)

// Permissions Grid Pagination State
const permPage = ref(1)
const permPerPage = ref(6)

// Modal state
const showCreateModal = ref(false)
const modalMode = ref<'group' | 'permission'>('permission')
const isGroupCheckbox = ref(false)
const showFullForm = ref(false)
const showGroupDropdown = ref(false)
const groupDropdownSearch = ref('')

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
    permissions.value = res.data || []
  } catch (err: any) {
    console.error('[Permissions] Fetch error:', err)
    errorMessage.value = err?.response?.data?.message || 'Failed to load permissions catalog.'
  } finally {
    loading.value = false
  }
}

onMounted(() => {
  fetchPermissions()
})

// Group permissions by module
const groupedPermissionsMap = computed(() => {
  const map: Record<string, Permission[]> = {}
  permissions.value.forEach(p => {
    const mod = p.module || 'general'
    if (!map[mod]) map[mod] = []
    map[mod].push(p)
  })
  return map
})

// Left Panel Group List
const groupList = computed(() => {
  const keys = Object.keys(groupedPermissionsMap.value)
  let list = keys.map(modKey => ({
    key: modKey,
    title: formatModuleName(modKey),
    count: groupedPermissionsMap.value[modKey].length,
    initials: getGroupInitials(formatModuleName(modKey))
  }))

  if (groupSearchQuery.value.trim()) {
    const q = groupSearchQuery.value.toLowerCase().trim()
    list = list.filter(g => g.title.toLowerCase().includes(q) || g.key.toLowerCase().includes(q))
  }

  return list
})

// Active group header info
const activeGroupInfo = computed(() => {
  if (selectedGroupKey.value === 'all') {
    return {
      key: 'all',
      title: 'All Permission Groups',
      count: permissions.value.length,
      initials: 'ALL'
    }
  }
  const title = formatModuleName(selectedGroupKey.value)
  const count = groupedPermissionsMap.value[selectedGroupKey.value]?.length || 0
  return {
    key: selectedGroupKey.value,
    title,
    count,
    initials: getGroupInitials(title)
  }
})

// Displayed Permissions on Right Panel Grid
const displayedPermissions = computed(() => {
  let list = permissions.value

  if (selectedGroupKey.value !== 'all') {
    list = list.filter(p => p.module === selectedGroupKey.value)
  }

  if (permissionSearchQuery.value.trim()) {
    const q = permissionSearchQuery.value.toLowerCase().trim()
    list = list.filter(p =>
      p.name.toLowerCase().includes(q) ||
      p.slug.toLowerCase().includes(q) ||
      p.module.toLowerCase().includes(q) ||
      (p.description && p.description.toLowerCase().includes(q))
    )
  }

  if (selectedActionFilter.value !== 'all') {
    list = list.filter(p => (p.action || '').toLowerCase() === selectedActionFilter.value.toLowerCase())
  }

  return list
})

// Paginated Group List for Left Panel Sidebar
const paginatedGroupList = computed(() => {
  const start = (groupPage.value - 1) * groupPerPage.value
  const end = start + groupPerPage.value
  return groupList.value.slice(start, end)
})
const groupLastPage = computed(() => Math.ceil(groupList.value.length / groupPerPage.value) || 1)

watch(groupSearchQuery, () => {
  groupPage.value = 1
})

// Paginated Permissions Grid for Right Panel
const totalPerms = computed(() => displayedPermissions.value.length)
const permLastPage = computed(() => Math.ceil(totalPerms.value / permPerPage.value) || 1)

const paginatedDisplayedPermissions = computed(() => {
  const start = (permPage.value - 1) * permPerPage.value
  const end = start + permPerPage.value
  return displayedPermissions.value.slice(start, end)
})

const permShowingFrom = computed(() => {
  if (totalPerms.value === 0) return 0
  return (permPage.value - 1) * permPerPage.value + 1
})

const permShowingTo = computed(() => {
  return Math.min(permPage.value * permPerPage.value, totalPerms.value)
})

const permPaginationPages = computed(() => {
  const pages: number[] = []
  const max = permLastPage.value
  const cur = permPage.value

  for (let i = Math.max(1, cur - 2); i <= Math.min(max, cur + 2); i++) {
    pages.push(i)
  }
  return pages
})

watch([selectedGroupKey, permissionSearchQuery, selectedActionFilter], () => {
  permPage.value = 1
})

const changePermPerPage = (event: Event) => {
  const target = event.target as HTMLSelectElement
  permPerPage.value = Number(target.value)
  permPage.value = 1
}

const goToPermPage = (p: number) => {
  if (p >= 1 && p <= permLastPage.value) {
    permPage.value = p
  }
}

const prevPermPage = () => {
  if (permPage.value > 1) {
    permPage.value--
  }
}

const nextPermPage = () => {
  if (permPage.value < permLastPage.value) {
    permPage.value++
  }
}

// Available Action options
const availableActions = computed(() => {
  const actions = new Set<string>()
  permissions.value.forEach(p => {
    if (p.action) actions.add(p.action.toLowerCase())
  })
  return Array.from(actions)
})

// Modal Group Dropdown filter
const filteredDropdownGroups = computed(() => {
  if (!groupDropdownSearch.value.trim()) return groupList.value
  const q = groupDropdownSearch.value.toLowerCase().trim()
  return groupList.value.filter(g => g.title.toLowerCase().includes(q) || g.key.toLowerCase().includes(q))
})

// Checkbox select all logic
const isAllSelected = computed(() => {
  if (displayedPermissions.value.length === 0) return false
  return displayedPermissions.value.every(p => selectedPermissionIds.value.includes(p.id))
})

const toggleSelectAll = () => {
  if (isAllSelected.value) {
    const currentIds = new Set(displayedPermissions.value.map(p => p.id))
    selectedPermissionIds.value = selectedPermissionIds.value.filter(id => !currentIds.has(id))
  } else {
    const currentIds = displayedPermissions.value.map(p => p.id)
    const set = new Set([...selectedPermissionIds.value, ...currentIds])
    selectedPermissionIds.value = Array.from(set)
  }
}

const togglePermissionSelect = (id: number) => {
  const index = selectedPermissionIds.value.indexOf(id)
  if (index === -1) {
    selectedPermissionIds.value.push(id)
  } else {
    selectedPermissionIds.value.splice(index, 1)
  }
}

// Helpers
function formatModuleName(mod: string) {
  if (!mod) return 'General'
  const formatted = mod.replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase())
  if (formatted.toLowerCase().includes('management') || formatted.toLowerCase().includes('operations') || formatted.toLowerCase().includes('catalog')) {
    return formatted
  }
  return `${formatted} Management`
}

function getGroupInitials(name: string) {
  const words = name.trim().split(/\s+/)
  if (words.length >= 2) {
    return (words[0][0] + words[1][0]).toUpperCase()
  }
  return name.slice(0, 2).toUpperCase()
}

// Modal actions
const openCreateGroupModal = () => {
  editingPermission.value = null
  modalMode.value = 'group'
  isGroupCheckbox.value = true
  showFullForm.value = false
  permForm.value = {
    name: '',
    module: '',
    action: 'access',
    description: '',
  }
  showGroupDropdown.value = false
  groupDropdownSearch.value = ''
  showCreateModal.value = true
}

const openCreatePermissionModal = (defaultModule?: string) => {
  editingPermission.value = null
  modalMode.value = 'permission'
  isGroupCheckbox.value = false
  showFullForm.value = true
  permForm.value = {
    name: '',
    module: defaultModule || (selectedGroupKey.value !== 'all' ? selectedGroupKey.value : ''),
    action: 'see',
    description: '',
  }
  showGroupDropdown.value = false
  groupDropdownSearch.value = ''
  showCreateModal.value = true
}

const openEditModal = (perm: Permission) => {
  editingPermission.value = perm
  modalMode.value = 'permission'
  isGroupCheckbox.value = false
  showFullForm.value = true
  permForm.value = {
    name: perm.name,
    module: perm.module,
    action: perm.action,
    description: perm.description || '',
  }
  showGroupDropdown.value = false
  groupDropdownSearch.value = ''
  showCreateModal.value = true
}

const handleIsGroupChange = () => {
  if (isGroupCheckbox.value) {
    modalMode.value = 'group'
    if (!permForm.value.action) permForm.value.action = 'access'
  } else {
    modalMode.value = 'permission'
    if (permForm.value.action === 'access') permForm.value.action = 'see'
  }
}

const selectDropdownGroup = (groupKey: string) => {
  permForm.value.module = groupKey
  showGroupDropdown.value = false
}

const selectCustomDropdownGroup = (customName: string) => {
  const slugified = customName.toLowerCase().trim().replace(/[^a-z0-9]+/g, '_').replace(/^_+|_+$/g, '')
  permForm.value.module = slugified
  showGroupDropdown.value = false
}

const savePermission = async () => {
  const rawName = permForm.value.name.trim()
  if (!rawName) {
    errorMessage.value = 'Please enter a name.'
    return
  }

  let moduleKey = permForm.value.module.trim()
  if (!moduleKey) {
    moduleKey = rawName.toLowerCase().replace(/[^a-z0-9]+/g, '_').replace(/^_+|_+$/g, '')
    permForm.value.module = moduleKey
  }

  let actionKey = permForm.value.action.trim()
  if (!actionKey) {
    actionKey = (modalMode.value === 'group' || isGroupCheckbox.value) ? 'access' : 'see'
    permForm.value.action = actionKey
  }

  saving.value = true
  errorMessage.value = ''
  try {
    if (editingPermission.value) {
      await rbacService.updatePermission(editingPermission.value.id, {
        name: rawName,
        module: moduleKey.toLowerCase(),
        action: actionKey.toLowerCase(),
        description: permForm.value.description,
      })
      successMessage.value = `Permission "${rawName}" updated successfully!`
    } else {
      await rbacService.createPermission({
        name: rawName,
        module: moduleKey.toLowerCase(),
        action: actionKey.toLowerCase(),
        description: permForm.value.description || ((modalMode.value === 'group' || isGroupCheckbox.value) ? `Access group for ${rawName}` : undefined),
      })
      successMessage.value = (modalMode.value === 'group' || isGroupCheckbox.value)
        ? `Permission Group "${rawName}" created successfully!`
        : `Permission "${rawName}" created successfully!`
    }

    showCreateModal.value = false
    selectedGroupKey.value = moduleKey.toLowerCase()
    permForm.value = { name: '', module: '', action: '', description: '' }
    await fetchPermissions()
    setTimeout(() => { successMessage.value = '' }, 3500)
  } catch (err: any) {
    console.error('[Permissions] Save error:', err)
    errorMessage.value = err?.response?.data?.message || err?.message || 'Failed to save permission.'
  } finally {
    saving.value = false
  }
}

const handleSavePermission = savePermission

const deletePermission = async (perm: Permission) => {
  if (!confirm(`Are you sure you want to delete permission "${perm.name}"?`)) return

  loading.value = true
  errorMessage.value = ''
  try {
    await rbacService.deletePermission(perm.id)
    successMessage.value = `Permission "${perm.name}" deleted successfully.`
    await fetchPermissions()
    setTimeout(() => { successMessage.value = '' }, 3500)
  } catch (err: any) {
    console.error('[Permissions] Delete error:', err)
    errorMessage.value = err?.response?.data?.message || 'Failed to delete permission.'
  } finally {
    loading.value = false
  }
}
</script>

<template>
  <DashboardLayout>
    <div class="min-h-screen bg-slate-50 dark:bg-slate-950 text-slate-900 dark:text-white p-4 sm:p-6 space-y-6 font-sans">
      
      <!-- TOP BANNER HEADER -->
      <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 p-6 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-3xl shadow-xs">
        <div class="space-y-1">
          <div class="flex items-center gap-3">
            <div class="p-3 rounded-2xl bg-blue-500/10 text-blue-600 dark:text-blue-400 border border-blue-500/20 shadow-xs">
              <Key class="w-6 h-6" />
            </div>
            <div>
              <h1 class="text-2xl font-black tracking-tight text-slate-900 dark:text-white">
                Manage Permissions
              </h1>
              <p class="text-xs sm:text-sm text-slate-500 dark:text-slate-400 font-medium mt-0.5">
                Configure and manage permission groups and permissions
              </p>
            </div>
          </div>
        </div>

        <div class="flex items-center gap-3">
          <button
            @click="fetchPermissions"
            type="button"
            class="p-3 rounded-2xl border border-slate-200 dark:border-slate-800 hover:bg-slate-100 dark:hover:bg-slate-800 text-slate-600 dark:text-slate-300 transition cursor-pointer"
            title="Refresh Permissions"
          >
            <RefreshCw class="w-4.5 h-4.5" :class="{ 'animate-spin': loading }" />
          </button>

          <button
            @click="openCreateGroupModal()"
            type="button"
            class="inline-flex items-center gap-2 px-5 py-3 rounded-2xl bg-blue-600 hover:bg-blue-500 text-white font-extrabold text-xs shadow-md shadow-blue-600/20 transition cursor-pointer"
          >
            <Plus class="w-4 h-4 stroke-[3]" />
            <span>Add New Group</span>
          </button>
        </div>
      </div>

      <!-- NOTIFICATION BANNERS -->
      <Transition enter-active-class="transition duration-200" enter-from-class="opacity-0 -translate-y-2" enter-to-class="opacity-100 translate-y-0">
        <div v-if="successMessage" class="p-4 rounded-2xl bg-emerald-500/10 border border-emerald-500/30 text-emerald-700 dark:text-emerald-300 text-xs font-semibold flex items-center justify-between">
          <div class="flex items-center gap-2.5">
            <CheckCircle2 class="w-4 h-4 text-emerald-500 flex-shrink-0" />
            <span>{{ successMessage }}</span>
          </div>
          <button @click="successMessage = ''" class="text-emerald-500 hover:text-emerald-700 dark:hover:text-white cursor-pointer">
            <XCircle class="w-4 h-4" />
          </button>
        </div>
      </Transition>

      <Transition enter-active-class="transition duration-200" enter-from-class="opacity-0 -translate-y-2" enter-to-class="opacity-100 translate-y-0">
        <div v-if="errorMessage" class="p-4 rounded-2xl bg-rose-500/10 border border-rose-500/30 text-rose-700 dark:text-rose-300 text-xs font-semibold flex items-center justify-between">
          <div class="flex items-center gap-2.5">
            <AlertCircle class="w-4 h-4 text-rose-500 flex-shrink-0" />
            <span>{{ errorMessage }}</span>
          </div>
          <button @click="errorMessage = ''" class="text-rose-500 hover:text-rose-700 dark:hover:text-white cursor-pointer">
            <XCircle class="w-4 h-4" />
          </button>
        </div>
      </Transition>

      <!-- MAIN SPLIT CONTENT GRID -->
      <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
        
        <!-- LEFT PANEL: PERMISSION GROUP SIDEBAR -->
        <div class="lg:col-span-4 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-3xl p-5 space-y-4 shadow-xs flex flex-col min-h-[500px]">
          <!-- Group Header -->
          <div class="flex items-center justify-between pb-1">
            <h2 class="text-base font-extrabold text-slate-900 dark:text-white tracking-tight">Permission Group</h2>
            <button
              @click="openCreateGroupModal()"
              class="px-3 py-1.5 rounded-xl bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 dark:hover:bg-slate-700 text-blue-600 dark:text-blue-400 text-xs font-bold transition flex items-center gap-1.5 cursor-pointer border border-slate-200 dark:border-slate-700"
            >
              <Plus class="w-3.5 h-3.5 stroke-[3]" />
              <span>Add New Group</span>
            </button>
          </div>

          <!-- Group Search -->
          <div class="relative">
            <Search class="w-4 h-4 absolute left-3.5 top-3 text-slate-400 dark:text-slate-500" />
            <input
              v-model="groupSearchQuery"
              type="text"
              placeholder="Search groups..."
              class="w-full pl-10 pr-4 py-2.5 text-xs bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-2xl text-slate-900 dark:text-white placeholder-slate-400 dark:placeholder-slate-500 focus:outline-none focus:border-blue-500 font-medium transition"
            />
          </div>

          <!-- Group Scrollable List -->
          <div class="space-y-2 overflow-y-auto max-h-[620px] pr-1 flex-1">
            <!-- 'All Groups' Item -->
            <button
              @click="selectedGroupKey = 'all'"
              :class="[
                'w-full flex items-center justify-between p-3.5 rounded-2xl text-left transition cursor-pointer border',
                selectedGroupKey === 'all'
                  ? 'bg-blue-50 dark:bg-blue-600/20 border-blue-500 text-blue-900 dark:text-white font-black shadow-xs'
                  : 'bg-slate-50/60 dark:bg-slate-950/40 border-slate-200/80 dark:border-slate-800/60 text-slate-700 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800/60 hover:text-slate-900 dark:hover:text-white'
              ]"
            >
              <div class="flex items-center gap-3 min-w-0">
                <div class="w-8 h-8 rounded-xl bg-blue-500/20 text-blue-600 dark:text-blue-400 font-black text-xs flex items-center justify-center flex-shrink-0">
                  ALL
                </div>
                <span class="text-xs truncate font-extrabold">All Permission Groups</span>
              </div>
              <span class="px-2.5 py-0.5 rounded-full text-[11px] font-bold bg-slate-200 dark:bg-slate-800 text-slate-700 dark:text-slate-300 border border-slate-300/60 dark:border-slate-700">
                {{ permissions.length }}
              </span>
            </button>

            <!-- Dynamic Groups List -->
            <div
              v-for="group in paginatedGroupList"
              :key="group.key"
              @click="selectedGroupKey = group.key"
              :class="[
                'w-full flex items-center justify-between p-3.5 rounded-2xl text-left transition cursor-pointer border group',
                selectedGroupKey === group.key
                  ? 'bg-blue-50 dark:bg-blue-600/20 border-blue-500 text-blue-900 dark:text-white font-black shadow-xs'
                  : 'bg-slate-50/60 dark:bg-slate-950/40 border-slate-200/80 dark:border-slate-800/60 text-slate-700 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800/60 hover:text-slate-900 dark:hover:text-white'
              ]"
            >
              <div class="flex items-center gap-3 min-w-0">
                <div
                  :class="[
                    'w-8 h-8 rounded-xl text-xs font-black flex items-center justify-center flex-shrink-0 transition-colors',
                    selectedGroupKey === group.key ? 'bg-blue-600 text-white shadow-xs' : 'bg-slate-200 dark:bg-slate-800 text-slate-600 dark:text-slate-400 group-hover:bg-slate-300 dark:group-hover:bg-slate-700 group-hover:text-slate-900 dark:group-hover:text-white'
                  ]"
                >
                  {{ group.initials }}
                </div>
                <span class="text-xs truncate font-bold">{{ group.title }}</span>
              </div>

              <div class="flex items-center gap-2 flex-shrink-0">
                <span
                  :class="[
                    'px-2.5 py-0.5 rounded-full text-[11px] font-bold border',
                    selectedGroupKey === group.key
                      ? 'bg-blue-500/20 dark:bg-blue-500/30 text-blue-700 dark:text-blue-200 border-blue-400/30'
                      : 'bg-slate-200/70 dark:bg-slate-800/80 text-slate-600 dark:text-slate-400 border-slate-300/60 dark:border-slate-700'
                  ]"
                >
                  {{ group.count }}
                </span>
                <button
                  @click.stop="openCreatePermissionModal(group.key)"
                  class="p-1 rounded-lg text-slate-400 hover:text-slate-700 dark:hover:text-white hover:bg-slate-200 dark:hover:bg-slate-700 transition cursor-pointer"
                  title="Add permission to this group"
                >
                  <MoreVertical class="w-3.5 h-3.5" />
                </button>
              </div>
            </div>
          </div>

          <!-- Group Sidebar Pagination Bar -->
          <div v-if="groupList.length > groupPerPage" class="flex items-center justify-between pt-3 border-t border-slate-200 dark:border-slate-800 text-xs font-bold text-slate-500 dark:text-slate-400">
            <button
              @click="groupPage--"
              :disabled="groupPage <= 1"
              class="p-1.5 rounded-xl border border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-950 hover:bg-slate-100 dark:hover:bg-slate-800 disabled:opacity-40 disabled:cursor-not-allowed transition cursor-pointer"
              title="Previous Group Page"
            >
              <ChevronLeft class="w-3.5 h-3.5" />
            </button>
            <span class="text-[11px] font-black text-slate-700 dark:text-slate-300">Group {{ groupPage }} / {{ groupLastPage }}</span>
            <button
              @click="groupPage++"
              :disabled="groupPage >= groupLastPage"
              class="p-1.5 rounded-xl border border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-950 hover:bg-slate-100 dark:hover:bg-slate-800 disabled:opacity-40 disabled:cursor-not-allowed transition cursor-pointer"
              title="Next Group Page"
            >
              <ChevronRight class="w-3.5 h-3.5" />
            </button>
          </div>
        </div>

        <!-- RIGHT PANEL: GROUP PERMISSIONS & CONTROLS -->
        <div class="lg:col-span-8 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-3xl p-6 space-y-6 shadow-xs flex flex-col min-h-[500px]">
          
          <!-- Group Title Banner Header -->
          <div class="flex items-center justify-between pb-4 border-b border-slate-200 dark:border-slate-800">
            <div class="flex items-center gap-4">
              <div class="w-12 h-12 rounded-2xl bg-blue-600 text-white font-black text-base flex items-center justify-center shadow-md shadow-blue-600/30">
                {{ activeGroupInfo.initials }}
              </div>
              <div>
                <h2 class="text-xl font-black text-slate-900 dark:text-white tracking-tight">
                  {{ activeGroupInfo.title }}
                </h2>
                <p class="text-xs text-slate-500 dark:text-slate-400 font-medium mt-0.5">
                  {{ activeGroupInfo.count }} Permission Created
                </p>
              </div>
            </div>

            <button
              @click="openCreatePermissionModal(selectedGroupKey !== 'all' ? selectedGroupKey : '')"
              class="inline-flex items-center gap-1.5 px-4 py-2.5 rounded-xl bg-blue-600 hover:bg-blue-500 text-white font-extrabold text-xs shadow-md transition cursor-pointer"
            >
              <Plus class="w-4 h-4 stroke-[2.5]" />
              <span>Add New Permission</span>
            </button>
          </div>

          <!-- Controls Toolbar Bar (Select All, Search, Filter) -->
          <div class="flex flex-col sm:flex-row items-center justify-between gap-3 bg-slate-50 dark:bg-slate-950/70 p-3.5 rounded-2xl border border-slate-200 dark:border-slate-800">
            
            <!-- Select All Checkbox Toggle -->
            <button
              @click="toggleSelectAll"
              class="flex items-center gap-2.5 px-3 py-2 rounded-xl text-xs font-bold text-slate-700 dark:text-slate-300 hover:text-slate-900 dark:hover:text-white transition cursor-pointer select-none"
            >
              <div
                :class="[
                  'w-4.5 h-4.5 rounded-md flex items-center justify-center transition border',
                  isAllSelected ? 'bg-blue-500 border-blue-500 text-white' : 'border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-900'
                ]"
              >
                <Check v-if="isAllSelected" class="w-3.5 h-3.5 stroke-[3]" />
              </div>
              <span>Select All</span>
            </button>

            <!-- Search & Action Filter Inputs -->
            <div class="flex items-center gap-2.5 w-full sm:w-auto">
              <!-- Search Permissions Input -->
              <div class="relative flex-1 sm:w-64">
                <Search class="w-4 h-4 absolute left-3.5 top-2.5 text-slate-400" />
                <input
                  v-model="permissionSearchQuery"
                  type="text"
                  placeholder="Search permissions..."
                  class="w-full pl-9 pr-4 py-2 text-xs bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl text-slate-900 dark:text-white placeholder-slate-400 dark:placeholder-slate-500 focus:outline-none focus:border-blue-500 font-medium"
                />
              </div>

              <!-- Filter Action Dropdown -->
              <div class="relative">
                <select
                  v-model="selectedActionFilter"
                  class="px-3.5 py-2 text-xs bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl text-slate-700 dark:text-slate-300 font-bold focus:outline-none focus:border-blue-500 cursor-pointer pr-8"
                >
                  <option value="all">Filter (All)</option>
                  <option v-for="act in availableActions" :key="act" :value="act">
                    {{ act.toUpperCase() }}
                  </option>
                </select>
                <Filter class="w-3.5 h-3.5 absolute right-2.5 top-2.5 text-slate-400 pointer-events-none" />
              </div>
            </div>
          </div>

          <!-- Permissions 2-Column Grid -->
          <div v-if="loading && permissions.length === 0" class="py-20 text-center">
            <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-500 mx-auto mb-3"></div>
            <p class="text-xs text-slate-500 dark:text-slate-400 font-medium">Loading permissions catalog...</p>
          </div>

          <div v-else-if="displayedPermissions.length === 0" class="py-20 text-center space-y-2 bg-slate-50/50 dark:bg-slate-950/30 rounded-2xl border border-slate-200 dark:border-slate-800/60">
            <Shield class="w-10 h-10 text-slate-400 dark:text-slate-600 mx-auto opacity-50" />
            <h3 class="text-sm font-bold text-slate-700 dark:text-slate-300">No permissions found in this group</h3>
            <p class="text-xs text-slate-500 dark:text-slate-400">Try adjusting your search criteria or add a new permission.</p>
          </div>

          <div v-else class="flex-1 flex flex-col justify-between space-y-4">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-3 overflow-y-auto max-h-[540px] pr-1">
              <div
                v-for="perm in paginatedDisplayedPermissions"
                :key="perm.id"
                @click="togglePermissionSelect(perm.id)"
                :class="[
                  'flex items-center justify-between p-4 rounded-2xl border transition-all cursor-pointer select-none group',
                  selectedPermissionIds.includes(perm.id)
                    ? 'bg-blue-50/70 dark:bg-blue-600/15 border-blue-500/60 text-slate-900 dark:text-white shadow-xs'
                    : 'bg-slate-50/60 dark:bg-slate-950/40 border-slate-200/80 dark:border-slate-800/80 text-slate-700 dark:text-slate-300 hover:bg-slate-100/80 dark:hover:bg-slate-800/50 hover:border-slate-300 dark:hover:border-slate-700'
                ]"
              >
                <div class="flex items-center gap-3 min-w-0 flex-1">
                  <!-- Checkbox -->
                  <div
                    :class="[
                      'w-4.5 h-4.5 rounded-md flex items-center justify-center flex-shrink-0 transition border',
                      selectedPermissionIds.includes(perm.id)
                        ? 'bg-blue-600 border-blue-600 text-white'
                        : 'border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-900 group-hover:border-slate-400 dark:group-hover:border-slate-500'
                    ]"
                  >
                    <Check v-if="selectedPermissionIds.includes(perm.id)" class="w-3.5 h-3.5 stroke-[3]" />
                  </div>

                  <!-- Permission Title & Key -->
                  <div class="min-w-0 flex-1">
                    <h4 class="text-xs font-extrabold text-slate-900 dark:text-white truncate">
                      {{ perm.name }}
                    </h4>
                    <p class="text-[10px] font-mono text-slate-500 dark:text-slate-400 truncate mt-0.5">
                      {{ perm.slug }}
                    </p>
                  </div>
                </div>

                <!-- Active Status Pill & Actions -->
                <div class="flex items-center gap-2 flex-shrink-0 ml-2">
                  <span class="px-2.5 py-0.5 rounded-full text-[10px] font-extrabold bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 border border-emerald-500/20">
                    Active
                  </span>

                  <div class="opacity-0 group-hover:opacity-100 transition-opacity flex items-center gap-1">
                    <button
                      @click.stop="openEditModal(perm)"
                      class="p-1.5 rounded-lg text-slate-400 hover:text-amber-600 dark:hover:text-amber-400 hover:bg-slate-200 dark:hover:bg-slate-800 transition cursor-pointer"
                      title="Edit Permission"
                    >
                      <Edit class="w-3.5 h-3.5" />
                    </button>
                    <button
                      @click.stop="deletePermission(perm)"
                      class="p-1.5 rounded-lg text-slate-400 hover:text-rose-600 dark:hover:text-rose-400 hover:bg-slate-200 dark:hover:bg-slate-800 transition cursor-pointer"
                      title="Delete Permission"
                    >
                      <Trash2 class="w-3.5 h-3.5" />
                    </button>
                  </div>
                </div>
              </div>
            </div>

            <!-- Right Panel Permissions Pagination Bar -->
            <div
              v-if="displayedPermissions.length > 0"
              class="flex flex-col sm:flex-row items-center justify-between gap-3 pt-4 border-t border-slate-200 dark:border-slate-800 text-xs font-sans mt-auto"
            >
              <!-- Items per page & count -->
              <div class="flex flex-wrap items-center gap-3 text-slate-600 dark:text-slate-400">
                <div class="flex items-center gap-2">
                  <span class="font-bold text-slate-700 dark:text-slate-300">Items per page:</span>
                  <select
                    :value="permPerPage"
                    @change="changePermPerPage"
                    class="px-2.5 py-1 rounded-xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 text-slate-900 dark:text-white font-black focus:outline-none focus:border-blue-500 cursor-pointer shadow-2xs"
                  >
                    <option :value="6">6</option>
                    <option :value="12">12</option>
                    <option :value="24">24</option>
                    <option :value="48">48</option>
                  </select>
                </div>

                <div class="text-[11px] font-medium">
                  Showing <span class="font-extrabold text-slate-900 dark:text-white">{{ permShowingFrom }}</span> to
                  <span class="font-extrabold text-slate-900 dark:text-white">{{ permShowingTo }}</span> of
                  <span class="font-extrabold text-slate-900 dark:text-white">{{ totalPerms }}</span> permissions
                </div>
              </div>

              <!-- Page navigation buttons -->
              <div class="flex items-center gap-1.5">
                <button
                  @click="prevPermPage"
                  :disabled="permPage <= 1"
                  class="p-1.5 rounded-xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 text-slate-700 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800 disabled:opacity-40 disabled:cursor-not-allowed transition cursor-pointer flex items-center gap-1 font-bold text-xs"
                  title="Previous Page"
                >
                  <ChevronLeft class="w-3.5 h-3.5" />
                  <span class="hidden sm:inline">Prev</span>
                </button>

                <div class="flex items-center gap-1">
                  <button
                    v-for="p in permPaginationPages"
                    :key="p"
                    @click="goToPermPage(p)"
                    :class="[
                      'w-7 h-7 rounded-xl font-black text-xs transition cursor-pointer flex items-center justify-center border',
                      permPage === p
                        ? 'bg-blue-600 border-blue-600 text-white shadow-md shadow-blue-600/20'
                        : 'bg-white dark:bg-slate-900 border-slate-200 dark:border-slate-800 text-slate-700 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800'
                    ]"
                  >
                    {{ p }}
                  </button>
                </div>

                <button
                  @click="nextPermPage"
                  :disabled="permPage >= permLastPage"
                  class="p-1.5 rounded-xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 text-slate-700 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800 disabled:opacity-40 disabled:cursor-not-allowed transition cursor-pointer flex items-center gap-1 font-bold text-xs"
                  title="Next Page"
                >
                  <span class="hidden sm:inline">Next</span>
                  <ChevronRight class="w-3.5 h-3.5" />
                </button>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- CREATE PERMISSION / GROUP MODAL (MATCHING REFERENCE UI SCREENSHOT) -->
      <Teleport to="body">
        <Transition name="modal">
          <div
            v-if="showCreateModal"
            class="fixed inset-0 z-[9999] flex items-center justify-center p-3 sm:p-6 overflow-hidden"
          >
            <!-- Backdrop -->
            <div
              class="fixed inset-0 bg-slate-950/80 backdrop-blur-md transition-opacity"
              @click="showCreateModal = false"
            ></div>

            <!-- Modal Window -->
            <div class="relative z-10 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-3xl max-w-xl w-full p-6 space-y-6 shadow-2xl overflow-visible my-auto animate-in fade-in zoom-in duration-150">
              <!-- Header -->
              <div class="flex items-center justify-between pb-3 border-b border-slate-200 dark:border-slate-800">
                <h2 class="text-xl font-black text-slate-900 dark:text-white">
                  {{ editingPermission ? 'Edit Permission' : (modalMode === 'group' ? 'Create Permission Group' : 'Create Permission') }}
                </h2>
                <button
                  @click="showCreateModal = false"
                  type="button"
                  class="text-slate-400 hover:text-slate-600 dark:hover:text-white transition cursor-pointer"
                >
                  <XCircle class="w-5 h-5" />
                </button>
              </div>

              <!-- Form Body -->
              <div class="space-y-4">
                <!-- Top Inputs: Name & Permission Group -->
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                  <!-- Name Input -->
                  <div class="space-y-1.5">
                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 dark:text-slate-300">
                      Name <span class="text-rose-500">*</span>
                    </label>
                    <input
                      v-model="permForm.name"
                      type="text"
                      placeholder="Enter Name"
                      class="w-full px-4 py-2.5 text-xs bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-xl text-slate-900 dark:text-white placeholder-slate-400 dark:placeholder-slate-500 focus:outline-none focus:border-blue-500 font-medium"
                    />
                  </div>

                  <!-- Permission Group Custom Searchable Dropdown -->
                  <div class="space-y-1.5 relative">
                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 dark:text-slate-300">
                      {{ modalMode === 'group' ? 'Group Code (Auto)' : 'Permission Group' }}
                    </label>

                    <div class="relative">
                      <button
                        type="button"
                        @click="showGroupDropdown = !showGroupDropdown"
                        class="w-full px-4 py-2.5 text-xs bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-xl text-slate-900 dark:text-white font-medium text-left flex items-center justify-between cursor-pointer focus:outline-none focus:border-blue-500"
                      >
                        <span class="truncate">
                          {{ permForm.module ? formatModuleName(permForm.module) : 'Select Permission Group' }}
                        </span>
                        <ChevronDown class="w-4 h-4 text-slate-400" />
                      </button>

                      <!-- Dropdown List -->
                      <div
                        v-if="showGroupDropdown"
                        class="absolute left-0 right-0 top-full mt-1 z-50 bg-white dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-2xl shadow-2xl p-2 space-y-2 max-h-60 overflow-y-auto"
                      >
                        <div class="relative">
                          <Search class="w-3.5 h-3.5 absolute left-3 top-2.5 text-slate-400" />
                          <input
                            v-model="groupDropdownSearch"
                            type="text"
                            placeholder="Search or enter group name..."
                            class="w-full pl-8 pr-3 py-1.5 text-xs bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-lg text-slate-900 dark:text-white placeholder-slate-400 focus:outline-none focus:border-blue-500"
                          />
                        </div>

                        <div class="space-y-1">
                          <button
                            v-for="group in filteredDropdownGroups"
                            :key="group.key"
                            type="button"
                            @click="selectDropdownGroup(group.key)"
                            class="w-full text-left px-3 py-2 text-xs font-medium rounded-lg text-slate-700 dark:text-slate-300 hover:text-slate-900 dark:hover:text-white hover:bg-slate-100 dark:hover:bg-slate-900 transition cursor-pointer"
                          >
                            {{ group.title }}
                          </button>
                          <div
                            v-if="groupDropdownSearch.trim() && !filteredDropdownGroups.some(g => g.title.toLowerCase() === groupDropdownSearch.toLowerCase())"
                            @click="selectCustomDropdownGroup(groupDropdownSearch)"
                            class="px-3 py-2 text-xs font-bold text-blue-600 dark:text-blue-400 hover:bg-slate-100 dark:hover:bg-slate-900 rounded-lg cursor-pointer flex items-center gap-1.5"
                          >
                            <Plus class="w-3.5 h-3.5" />
                            <span>Create group "{{ groupDropdownSearch.trim() }}"</span>
                          </div>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>

                <!-- Checkbox: Is group -->
                <div class="flex items-center gap-2 pt-1">
                  <label class="flex items-center gap-2 cursor-pointer select-none">
                    <input
                      v-model="isGroupCheckbox"
                      type="checkbox"
                      @change="handleIsGroupChange"
                      class="w-4 h-4 rounded bg-slate-50 dark:bg-slate-950 border-slate-300 dark:border-slate-800 text-blue-600 focus:ring-0 cursor-pointer"
                    />
                    <span class="text-xs font-bold text-slate-700 dark:text-slate-300">Is group</span>
                  </label>
                </div>

                <!-- Full Form Expandable Section (Action & Description) -->
                <div v-if="showFullForm || modalMode === 'permission'" class="space-y-3 pt-2 border-t border-slate-100 dark:border-slate-800/80 animate-in fade-in duration-150">
                  <div class="space-y-1.5">
                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 dark:text-slate-300">
                      Action Key <span class="text-rose-500">*</span>
                    </label>
                    <input
                      v-model="permForm.action"
                      type="text"
                      placeholder="e.g. see, create, update, delete"
                      class="w-full px-4 py-2.5 text-xs bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-xl text-slate-900 dark:text-white placeholder-slate-400 dark:placeholder-slate-500 focus:outline-none focus:border-blue-500 font-medium"
                    />
                    <p class="text-[10px] text-slate-500 dark:text-slate-400">e.g. see, view, create, edit, delete</p>
                  </div>

                  <div class="space-y-1.5">
                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 dark:text-slate-300">
                      Description
                    </label>
                    <textarea
                      v-model="permForm.description"
                      rows="2"
                      placeholder="Brief description of permission scope..."
                      class="w-full px-4 py-2.5 text-xs bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-xl text-slate-900 dark:text-white placeholder-slate-400 dark:placeholder-slate-500 focus:outline-none focus:border-blue-500 font-medium resize-none"
                    ></textarea>
                  </div>

                  <div class="p-3 rounded-xl bg-blue-50 dark:bg-blue-500/10 border border-blue-200 dark:border-blue-500/20 text-xs text-blue-700 dark:text-blue-300">
                    <span class="font-bold">Auto-generated Key Slug:</span>
                    <code class="ml-1 font-mono text-amber-600 dark:text-amber-400">
                      {{ (permForm.module || 'group').toLowerCase() }}.{{ (permForm.action || 'see').toLowerCase() }}
                    </code>
                  </div>
                </div>
              </div>

              <!-- Footer Buttons -->
              <div class="flex items-center justify-between pt-4 border-t border-slate-200 dark:border-slate-800">
                <button
                  type="button"
                  @click="showFullForm = !showFullForm"
                  class="px-3.5 py-2 rounded-xl text-xs font-bold text-slate-700 dark:text-slate-300 bg-slate-100 dark:bg-slate-950 hover:bg-slate-200 dark:hover:bg-slate-800 border border-slate-200 dark:border-slate-800 transition cursor-pointer flex items-center gap-1.5"
                >
                  <span>{{ showFullForm ? 'Collapse Form' : 'Expand Full Form' }}</span>
                  <Maximize2 class="w-3.5 h-3.5" />
                </button>

                <div class="flex items-center gap-3">
                  <button
                    @click="showCreateModal = false"
                    type="button"
                    class="px-4 py-2.5 rounded-xl text-xs font-bold text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white transition cursor-pointer"
                  >
                    Cancel
                  </button>
                  <button
                    @click="savePermission"
                    type="button"
                    :disabled="saving || !permForm.name.trim()"
                    class="px-6 py-2.5 rounded-xl bg-blue-600 hover:bg-blue-500 text-white text-xs font-black shadow-md shadow-blue-600/20 transition cursor-pointer disabled:opacity-50"
                  >
                    {{ saving ? 'Saving...' : (editingPermission ? 'Update Permission' : (modalMode === 'group' ? 'Create Group' : 'Create Permission')) }}
                  </button>
                </div>
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
  transition: opacity 0.25s ease;
}

.modal-enter-from,
.modal-leave-to {
  opacity: 0;
}

.modal-enter-active .relative,
.modal-leave-active .relative {
  transition: transform 0.25s ease, opacity 0.25s ease;
}

.modal-enter-from .relative,
.modal-leave-to .relative {
  transform: scale(0.95);
  opacity: 0;
}
</style>

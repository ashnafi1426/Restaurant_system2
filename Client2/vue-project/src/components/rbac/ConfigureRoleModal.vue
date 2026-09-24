<script setup lang="ts">
import { ref, computed, watch } from 'vue'
import type { Role, Permission } from '../../types/rbacTypes'
import {
  X,
  Key,
  Search,
  Check,
  ShieldCheck,
  ShieldAlert,
  Loader2,
  CheckSquare,
  Square,
  Sparkles,
  Filter
} from 'lucide-vue-next'

const props = defineProps<{
  show: boolean
  editingRole: Role | null
  initialPermissionIds?: number[]
  permissions: Permission[]
  loading: boolean
  loadingPermissions?: boolean
}>()

const emit = defineEmits<{
  (e: 'close'): void
  (e: 'save', payload: { name: string; description: string; is_active: boolean; permissions: number[] }): void
}>()

const roleForm = ref({
  name: '',
  description: '',
  is_active: true,
  selectedPermissions: [] as number[],
})

const permissionSearch = ref('')
const selectedActionFilter = ref<string>('all')

watch(
  () => [props.show, props.editingRole, props.initialPermissionIds],
  () => {
    if (props.show) {
      if (props.editingRole) {
        const idsToUse = props.initialPermissionIds !== undefined
          ? props.initialPermissionIds
          : (props.editingRole.permissions ? props.editingRole.permissions.map((p: any) => typeof p === 'number' ? p : p.id) : [])

        roleForm.value = {
          name: props.editingRole.name,
          description: props.editingRole.description || '',
          is_active: props.editingRole.is_active ?? true,
          selectedPermissions: [...idsToUse],
        }
      } else {
        roleForm.value = {
          name: '',
          description: '',
          is_active: true,
          selectedPermissions: [],
        }
      }
      permissionSearch.value = ''
      selectedActionFilter.value = 'all'
    }
  },
  { immediate: true, deep: true }
)

const moduleDisplayNames: Record<string, { title: string; color: string; iconBg: string }> = {
  rooms: { title: 'ROOM MANAGEMENT', color: 'bg-emerald-500', iconBg: 'bg-emerald-500/10 text-emerald-500' },
  reservations: { title: 'RESERVATIONS & BOOKINGS', color: 'bg-blue-500', iconBg: 'bg-blue-500/10 text-blue-500' },
  orders: { title: 'ORDERS & POS', color: 'bg-amber-500', iconBg: 'bg-amber-500/10 text-amber-500' },
  menu: { title: 'MENU & INVENTORY', color: 'bg-indigo-500', iconBg: 'bg-indigo-500/10 text-indigo-500' },
  payments: { title: 'PAYMENTS & BILLING', color: 'bg-purple-500', iconBg: 'bg-purple-500/10 text-purple-500' },
  users: { title: 'USER MANAGEMENT', color: 'bg-rose-500', iconBg: 'bg-rose-500/10 text-rose-500' },
  guests: { title: 'GUEST MANAGEMENT', color: 'bg-teal-500', iconBg: 'bg-teal-500/10 text-teal-500' },
  tables: { title: 'RESTAURANT TABLES', color: 'bg-cyan-500', iconBg: 'bg-cyan-500/10 text-cyan-500' },
  kitchen: { title: 'KITCHEN DISPLAY (KDS)', color: 'bg-orange-500', iconBg: 'bg-orange-500/10 text-orange-500' },
  delivery: { title: 'DELIVERY & ROOM SERVICE', color: 'bg-sky-500', iconBg: 'bg-sky-500/10 text-sky-500' },
  reports: { title: 'REPORTS & ANALYTICS', color: 'bg-violet-500', iconBg: 'bg-violet-500/10 text-violet-500' },
  roles: { title: 'ROLE MANAGEMENT (RBAC)', color: 'bg-yellow-500', iconBg: 'bg-yellow-500/10 text-yellow-500' },
  permissions: { title: 'PERMISSIONS CATALOG', color: 'bg-pink-500', iconBg: 'bg-pink-500/10 text-pink-500' },
  checkin: { title: 'CHECK-IN MANAGEMENT', color: 'bg-emerald-600', iconBg: 'bg-emerald-600/10 text-emerald-600' },
  checkout: { title: 'CHECK-OUT MANAGEMENT', color: 'bg-rose-600', iconBg: 'bg-rose-600/10 text-rose-600' },
  notifications: { title: 'SYSTEM NOTIFICATIONS', color: 'bg-blue-600', iconBg: 'bg-blue-600/10 text-blue-600' },
  audit_logs: { title: 'SECURITY AUDIT LOGS', color: 'bg-slate-600', iconBg: 'bg-slate-600/10 text-slate-400' },
  waiters: { title: 'WAITER MANAGEMENT', color: 'bg-emerald-600', iconBg: 'bg-emerald-600/10 text-emerald-600' },
  floors: { title: 'FLOOR PLAN & LAYOUT', color: 'bg-indigo-600', iconBg: 'bg-indigo-600/10 text-indigo-600' },
}

const getModuleInfo = (mod: string) => {
  const norm = String(mod || '').toLowerCase().trim()
  if (moduleDisplayNames[norm]) {
    return moduleDisplayNames[norm]
  }
  const cleanName = norm.replace(/_/g, ' ').toUpperCase()
  const title = cleanName.includes('MANAGEMENT') ? cleanName : `${cleanName} MANAGEMENT`
  return { title, color: 'bg-amber-500', iconBg: 'bg-amber-500/10 text-amber-500' }
}

const availableActions = computed(() => {
  const actions = new Set<string>()
  props.permissions.forEach(p => {
    if (p.action) actions.add(p.action.toLowerCase())
  })
  return Array.from(actions)
})

const filteredPermissions = computed(() => {
  let list = props.permissions
  if (permissionSearch.value.trim()) {
    const q = permissionSearch.value.toLowerCase().trim()
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

const groupedPermissions = computed(() => {
  const groups: Record<string, Permission[]> = {}
  filteredPermissions.value.forEach(p => {
    const mod = p.module || 'other'
    if (!groups[mod]) groups[mod] = []
    groups[mod].push(p)
  })
  return Object.entries(groups).map(([modKey, perms]) => {
    const selectedCount = perms.filter(p => roleForm.value.selectedPermissions.includes(p.id)).length
    return {
      moduleKey: modKey,
      info: getModuleInfo(modKey),
      permissions: perms,
      selectedCount,
      totalCount: perms.length,
      isAllSelected: perms.length > 0 && selectedCount === perms.length,
      isSomeSelected: selectedCount > 0 && selectedCount < perms.length,
    }
  })
})

const togglePermission = (id: number) => {
  const index = roleForm.value.selectedPermissions.indexOf(id)
  if (index === -1) {
    roleForm.value.selectedPermissions.push(id)
  } else {
    roleForm.value.selectedPermissions.splice(index, 1)
  }
}

const toggleModulePermissions = (groupPerms: Permission[], isAllSelected: boolean) => {
  const permIds = groupPerms.map(p => p.id)
  if (isAllSelected) {
    const idsToRemove = new Set(permIds)
    roleForm.value.selectedPermissions = roleForm.value.selectedPermissions.filter(id => !idsToRemove.has(id))
  } else {
    permIds.forEach(id => {
      if (!roleForm.value.selectedPermissions.includes(id)) {
        roleForm.value.selectedPermissions.push(id)
      }
    })
  }
}

const selectAllGlobal = () => {
  const allIds = filteredPermissions.value.map(p => p.id)
  const set = new Set([...roleForm.value.selectedPermissions, ...allIds])
  roleForm.value.selectedPermissions = Array.from(set)
}

const deselectAllGlobal = () => {
  if (permissionSearch.value.trim() || selectedActionFilter.value !== 'all') {
    const filteredIds = new Set(filteredPermissions.value.map(p => p.id))
    roleForm.value.selectedPermissions = roleForm.value.selectedPermissions.filter(id => !filteredIds.has(id))
  } else {
    roleForm.value.selectedPermissions = []
  }
}

const handleSave = () => {
  if (!roleForm.value.name.trim()) return
  emit('save', {
    name: roleForm.value.name.trim(),
    description: roleForm.value.description.trim(),
    is_active: roleForm.value.is_active,
    permissions: roleForm.value.selectedPermissions,
  })
}
</script>

<template>
  <Teleport to="body">
    <div v-if="show" class="fixed inset-0 z-[9999] flex items-center justify-center p-3 sm:p-6 overflow-hidden">
      <div class="fixed inset-0 bg-slate-950/80 backdrop-blur-md transition-opacity" @click="emit('close')"></div>

      <div class="relative z-10 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-3xl max-w-4xl w-full max-h-[88vh] flex flex-col shadow-2xl overflow-hidden my-auto animate-in fade-in zoom-in duration-150">
        
        <div class="flex items-center justify-between px-6 py-5 border-b border-slate-200 dark:border-slate-800 bg-slate-50/80 dark:bg-slate-900/80 backdrop-blur-xs">
          <div class="flex items-center gap-3.5">
            <div class="p-3 rounded-2xl bg-amber-500/10 text-amber-500 border border-amber-500/20 shadow-xs">
              <ShieldCheck class="w-6 h-6" />
            </div>
            <div>
              <div class="flex items-center gap-2">
                <h2 class="text-xl font-black tracking-tight text-slate-900 dark:text-white">
                  {{ editingRole ? `Configure Role: ${editingRole.name}` : 'Create New System Role' }}
                </h2>
                <span v-if="editingRole?.is_system" class="px-2.5 py-0.5 rounded-full text-[10px] font-black uppercase bg-amber-500/10 text-amber-500 border border-amber-500/20">
                  System Role
                </span>
              </div>
              <p class="text-xs text-slate-500 dark:text-slate-400 font-medium mt-0.5">
                Set role title, description, and select feature access privileges for your staff members.
              </p>
            </div>
          </div>

          <button
            @click="emit('close')"
            class="p-2.5 rounded-2xl text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 hover:bg-slate-100 dark:hover:bg-slate-800 transition cursor-pointer"
          >
            <X class="w-5 h-5" />
          </button>
        </div>

      <div class="flex-1 overflow-y-auto p-6 space-y-6">
        
        <div v-if="editingRole?.slug === 'admin'" class="p-4 rounded-2xl bg-amber-500/10 border border-amber-500/30 text-amber-800 dark:text-amber-300 text-xs font-semibold flex items-start gap-3">
          <ShieldAlert class="w-5 h-5 text-amber-500 flex-shrink-0 mt-0.5" />
          <div>
            <strong class="font-extrabold">System Administrator Protection:</strong> This is a core system role with global access rights across the platform. Modifications affect all root administrator accounts.
          </div>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 bg-slate-50/50 dark:bg-slate-950/40 p-5 rounded-2xl border border-slate-200/80 dark:border-slate-800/80">
          <div class="space-y-1.5 sm:col-span-2">
            <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 dark:text-slate-300">
              Role Name <span class="text-rose-500">*</span>
            </label>
            <input
              v-model="roleForm.name"
              type="text"
              placeholder="e.g. Head Receptionist, Senior Cashier, Kitchen Supervisor..."
              class="w-full px-4 py-3 text-xs bg-white dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-xl text-slate-900 dark:text-white focus:outline-none focus:border-amber-500 focus:ring-1 focus:ring-amber-500 font-semibold transition"
            />
          </div>

          <div class="space-y-1.5 sm:col-span-2">
            <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 dark:text-slate-300">
              Description & Operational Scope
            </label>
            <textarea
              v-model="roleForm.description"
              rows="2"
              placeholder="Specify duties, key responsibilities, and operational scope for this role..."
              class="w-full px-4 py-2.5 text-xs bg-white dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-xl text-slate-900 dark:text-white focus:outline-none focus:border-amber-500 focus:ring-1 focus:ring-amber-500 font-medium transition"
            ></textarea>
          </div>

          <div class="flex items-center justify-between sm:col-span-2 pt-1">
            <label class="flex items-center gap-3 cursor-pointer">
              <div class="relative">
                <input
                  v-model="roleForm.is_active"
                  type="checkbox"
                  class="sr-only peer"
                  :disabled="editingRole?.slug === 'admin'"
                />
                <div class="w-11 h-6 bg-slate-200 dark:bg-slate-800 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-slate-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-amber-500"></div>
              </div>
              <div>
                <span class="text-xs font-bold text-slate-900 dark:text-white">Active Status</span>
                <p class="text-[11px] text-slate-500 dark:text-slate-400">Inactivating a role temporarily suspends capability inheritance for assigned users.</p>
              </div>
            </label>
          </div>
        </div>

        <div class="space-y-4">
          <div class="flex flex-col md:flex-row md:items-center justify-between gap-3 bg-white dark:bg-slate-900 p-4 rounded-2xl border border-slate-200 dark:border-slate-800">
            <div class="flex items-center gap-2.5">
              <div class="p-2 rounded-xl bg-amber-500/10 text-amber-500">
                <Key class="w-4.5 h-4.5" />
              </div>
              <div>
                <h3 class="text-xs font-black uppercase tracking-wider text-slate-900 dark:text-white flex items-center gap-2">
                  Module Permissions Catalog
                  <span class="px-2.5 py-0.5 rounded-full bg-amber-500/10 border border-amber-500/30 text-amber-600 dark:text-amber-400 font-extrabold text-[11px]">
                    {{ roleForm.selectedPermissions.length }} selected
                  </span>
                </h3>
              </div>
            </div>

            <div class="flex items-center gap-2">
              <button
                type="button"
                @click="selectAllGlobal"
                class="px-3 py-1.5 rounded-xl text-xs font-bold text-blue-600 dark:text-blue-400 bg-blue-500/10 hover:bg-blue-500/20 border border-blue-500/20 transition cursor-pointer flex items-center gap-1.5"
              >
                <CheckSquare class="w-3.5 h-3.5" />
                <span>Select All</span>
              </button>
              <button
                type="button"
                @click="deselectAllGlobal"
                class="px-3 py-1.5 rounded-xl text-xs font-bold text-rose-600 dark:text-rose-400 bg-rose-500/10 hover:bg-rose-500/20 border border-rose-500/20 transition cursor-pointer flex items-center gap-1.5"
              >
                <Square class="w-3.5 h-3.5" />
                <span>Clear</span>
              </button>
            </div>
          </div>

          <div class="flex flex-col sm:flex-row items-center gap-3">
            <div class="relative flex-1 w-full">
              <Search class="w-4 h-4 absolute left-3.5 top-3 text-slate-400" />
              <input
                v-model="permissionSearch"
                type="text"
                placeholder="Search capabilities by name, slug, or module..."
                class="w-full pl-10 pr-4 py-2.5 text-xs bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-xl text-slate-900 dark:text-white focus:outline-none focus:border-amber-500 font-medium"
              />
            </div>

            <div class="flex items-center gap-1 overflow-x-auto w-full sm:w-auto pb-1 sm:pb-0">
              <button
                @click="selectedActionFilter = 'all'"
                :class="[
                  'px-3 py-2 rounded-xl text-[11px] font-bold transition whitespace-nowrap cursor-pointer',
                  selectedActionFilter === 'all'
                    ? 'bg-slate-900 text-white dark:bg-white dark:text-slate-950 shadow-xs'
                    : 'bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-400 hover:bg-slate-200 dark:hover:bg-slate-700'
                ]"
              >
                All Actions
              </button>
              <button
                v-for="act in availableActions"
                :key="act"
                @click="selectedActionFilter = act"
                :class="[
                  'px-3 py-2 rounded-xl text-[11px] font-bold uppercase transition whitespace-nowrap cursor-pointer',
                  selectedActionFilter === act
                    ? 'bg-amber-500 text-slate-950 font-black shadow-xs'
                    : 'bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-400 hover:bg-slate-200 dark:hover:bg-slate-700'
                ]"
              >
                {{ act }}
              </button>
            </div>
          </div>

          <div v-if="loadingPermissions" class="py-12 text-center bg-slate-50 dark:bg-slate-950/50 rounded-2xl border border-slate-200 dark:border-slate-800">
            <Loader2 class="w-8 h-8 text-amber-500 animate-spin mx-auto mb-2" />
            <p class="text-xs font-bold text-slate-500 dark:text-slate-400">Loading role permissions matrix...</p>
          </div>

          <div v-else-if="groupedPermissions.length === 0" class="py-12 text-center bg-slate-50 dark:bg-slate-950/50 rounded-2xl border border-slate-200 dark:border-slate-800">
            <Key class="w-8 h-8 text-slate-400 mx-auto mb-2 opacity-50" />
            <p class="text-xs font-bold text-slate-600 dark:text-slate-300">No permissions match your filter criteria.</p>
            <p class="text-[11px] text-slate-400 mt-0.5">Try adjusting your search query or action filter.</p>
          </div>

          <div v-else class="space-y-4 max-h-[380px] overflow-y-auto pr-1">
            <div
              v-for="group in groupedPermissions"
              :key="group.moduleKey"
              class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-4 space-y-3 shadow-xs hover:border-slate-300 dark:hover:border-slate-700 transition"
            >
              <div class="flex items-center justify-between pb-2.5 border-b border-slate-100 dark:border-slate-800">
                <div class="flex items-center gap-2.5">
                  <span :class="['w-3 h-3 rounded-full flex-shrink-0', group.info.color]"></span>
                  <h4 class="text-xs font-black uppercase tracking-wider text-slate-900 dark:text-white">
                    {{ group.info.title }}
                  </h4>
                  <span class="px-2 py-0.5 rounded-md bg-slate-100 dark:bg-slate-800 text-slate-500 dark:text-slate-400 font-bold text-[10px]">
                    {{ group.selectedCount }} / {{ group.totalCount }}
                  </span>
                </div>

                <button
                  type="button"
                  @click="toggleModulePermissions(group.permissions, group.isAllSelected)"
                  :class="[
                    'px-2.5 py-1 rounded-lg text-[11px] font-bold transition cursor-pointer flex items-center gap-1',
                    group.isAllSelected
                      ? 'bg-rose-500/10 text-rose-600 dark:text-rose-400 hover:bg-rose-500/20'
                      : 'bg-amber-500/10 text-amber-700 dark:text-amber-400 hover:bg-amber-500/20'
                  ]"
                >
                  <CheckSquare v-if="!group.isAllSelected" class="w-3 h-3" />
                  <Square v-else class="w-3 h-3" />
                  <span>{{ group.isAllSelected ? 'Deselect Module' : 'Select Module' }}</span>
                </button>
              </div>

              <div class="grid grid-cols-1 sm:grid-cols-2 gap-2.5">
                <div
                  v-for="perm in group.permissions"
                  :key="perm.id"
                  @click="togglePermission(perm.id)"
                  :class="[
                    'flex items-center gap-3 p-3 rounded-xl border text-xs font-medium cursor-pointer transition-all select-none',
                    roleForm.selectedPermissions.includes(perm.id)
                      ? 'bg-amber-500/10 border-amber-500/40 text-slate-900 dark:text-amber-200 shadow-2xs'
                      : 'bg-slate-50/60 dark:bg-slate-950/60 border-slate-200/80 dark:border-slate-800/80 text-slate-600 dark:text-slate-400 hover:border-slate-300 dark:hover:border-slate-700'
                  ]"
                >
                  <div :class="[
                    'w-4 h-4 rounded flex items-center justify-center flex-shrink-0 transition border',
                    roleForm.selectedPermissions.includes(perm.id)
                      ? 'bg-amber-500 border-amber-500 text-slate-950'
                      : 'border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-900'
                  ]">
                    <Check v-if="roleForm.selectedPermissions.includes(perm.id)" class="w-3 h-3 stroke-[3]" />
                  </div>

                  <div class="min-w-0 flex-1">
                    <div class="flex items-center justify-between gap-1">
                      <p class="font-bold text-slate-900 dark:text-slate-100 truncate text-xs">{{ perm.name }}</p>
                      <span v-if="perm.action" class="text-[9px] font-extrabold uppercase px-1.5 py-0.5 rounded bg-slate-100 dark:bg-slate-800 text-slate-500 dark:text-slate-400">
                        {{ perm.action }}
                      </span>
                    </div>
                    <p class="text-[10px] text-slate-500 dark:text-slate-400 font-mono truncate mt-0.5 opacity-80">{{ perm.slug }}</p>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <div class="flex items-center justify-between px-6 py-4 border-t border-slate-200 dark:border-slate-800 bg-slate-50/80 dark:bg-slate-900/80 backdrop-blur-xs">
        <div class="text-xs font-bold text-slate-500 dark:text-slate-400">
          Selected Permissions: <strong class="text-amber-600 dark:text-amber-400 font-black">{{ roleForm.selectedPermissions.length }}</strong>
        </div>

        <div class="flex items-center gap-3">
          <button
            type="button"
            @click="emit('close')"
            class="px-5 py-2.5 rounded-xl text-xs font-bold text-slate-600 dark:text-slate-400 hover:bg-slate-200/60 dark:hover:bg-slate-800 transition cursor-pointer"
          >
            Cancel
          </button>
          <button
            type="button"
            @click="handleSave"
            :disabled="loading || !roleForm.name.trim()"
            class="px-6 py-2.5 rounded-xl bg-amber-500 hover:bg-amber-600 disabled:opacity-50 text-slate-950 text-xs font-black shadow-md shadow-amber-500/20 transition cursor-pointer flex items-center gap-2"
          >
            <Loader2 v-if="loading" class="w-4 h-4 animate-spin" />
            <Check v-else class="w-4 h-4 stroke-[2.5]" />
            <span>{{ loading ? 'Saving Role...' : (editingRole ? 'Save Changes' : 'Create Role') }}</span>
          </button>
        </div>
        </div>
      </div>
    </div>
  </Teleport>
</template>

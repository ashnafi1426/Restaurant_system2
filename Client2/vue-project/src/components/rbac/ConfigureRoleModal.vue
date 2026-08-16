<script setup lang="ts">
import { ref, computed, watch } from 'vue'
import type { Role, Permission } from '../../types/rbacTypes'
import {
  XCircle,
  Key,
  Search,
  Check,
  ShieldCheck
} from 'lucide-vue-next'

const props = defineProps<{
  show: boolean
  editingRole: Role | null
  initialPermissionIds?: number[]
  permissions: Permission[]
  loading: boolean
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
watch(
  () => props.initialPermissionIds,
  (newIds) => {
    if (newIds && Array.isArray(newIds) && newIds.length > 0) {
      roleForm.value.selectedPermissions = [...newIds]
    }
  },
  { immediate: true, deep: true }
)

watch(
  () => [props.editingRole, props.show],
  () => {
    if (props.show) {
      if (props.editingRole) {
        const rolePerms = props.editingRole.permissions ? props.editingRole.permissions.map((p: any) => p.id || p) : []
        const idsToUse = props.initialPermissionIds && props.initialPermissionIds.length > 0
          ? props.initialPermissionIds
          : rolePerms

        roleForm.value = {
          name: props.editingRole.name,
          description: props.editingRole.description || '',
          is_active: props.editingRole.is_active,
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
    }
  },
  { immediate: true, deep: true }
)

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
  waiters: { title: 'WAITER MANAGEMENT', color: 'bg-emerald-600' },
  floors: { title: 'FLOOR MANAGEMENT', color: 'bg-indigo-600' },
}

const getModuleInfo = (mod: string) => {
  const norm = String(mod || '').toLowerCase().trim()
  if (moduleDisplayNames[norm]) {
    return moduleDisplayNames[norm]
  }
  const cleanName = norm.replace(/_/g, ' ').toUpperCase()
  const title = cleanName.includes('MANAGEMENT') ? cleanName : `${cleanName} MANAGEMENT`
  return { title, color: 'bg-amber-500' }
}
const filteredPermissions = computed(() => {
  if (!permissionSearch.value.trim()) return props.permissions
  const q = permissionSearch.value.toLowerCase().trim()
  return props.permissions.filter(p =>
    p.name.toLowerCase().includes(q) ||
    p.slug.toLowerCase().includes(q) ||
    p.module.toLowerCase().includes(q)
  )
})
const groupedPermissions = computed(() => {
  const groups: Record<string, Permission[]> = {}
  filteredPermissions.value.forEach(p => {
    const mod = p.module || 'other'
    if (!groups[mod]) groups[mod] = []
    groups[mod].push(p)
  })
  return Object.entries(groups).map(([modKey, perms]) => ({
    moduleKey: modKey,
    info: getModuleInfo(modKey),
    permissions: perms,
  }))
})

const togglePermission = (id: number) => {
  const index = roleForm.value.selectedPermissions.indexOf(id)
  if (index === -1) {
    roleForm.value.selectedPermissions.push(id)
  } else {
    roleForm.value.selectedPermissions.splice(index, 1)
  }
}

const selectAllInModule = (groupPerms: Permission[]) => {
  groupPerms.forEach(p => {
    if (!roleForm.value.selectedPermissions.includes(p.id)) {
      roleForm.value.selectedPermissions.push(p.id)
    }
  })
}

const deselectAllInModule = (groupPerms: Permission[]) => {
  const idsToRemove = new Set(groupPerms.map(p => p.id))
  roleForm.value.selectedPermissions = roleForm.value.selectedPermissions.filter(id => !idsToRemove.has(id))
}

const handleSave = () => {
  if (!roleForm.value.name.trim()) return
  emit('save', {
    name: roleForm.value.name,
    description: roleForm.value.description,
    is_active: roleForm.value.is_active,
    permissions: roleForm.value.selectedPermissions,
  })
}
</script>

<template>
  <div v-if="show" class="fixed inset-0 z-50 flex items-center justify-center p-3 sm:p-6 bg-black/60 backdrop-blur-xs">
    <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-3xl max-w-3xl w-full max-h-[92vh] flex flex-col shadow-2xl overflow-hidden">
      
      <!-- Modal Header -->
      <div class="flex items-center justify-between p-5 border-b border-slate-200 dark:border-slate-800 bg-slate-50/50 dark:bg-slate-950/50">
        <div class="flex items-center gap-3">
          <span class="p-2.5 rounded-2xl bg-amber-500/10 text-amber-600 dark:text-amber-400 border border-amber-500/20">
            <ShieldCheck class="w-6 h-6" />
          </span>
          <div>
            <h2 class="text-lg font-black text-slate-900 dark:text-white">
              {{ editingRole ? `Configure Role: ${editingRole.name}` : 'Create New System Role' }}
            </h2>
            <p class="text-xs text-slate-500 dark:text-slate-400 font-medium">
              Assign role capabilities grouped strictly by business module.
            </p>
          </div>
        </div>

        <button @click="emit('close')" class="p-2 text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 transition">
          <XCircle class="w-6 h-6" />
        </button>
      </div>

      <!-- Modal Body -->
      <div class="flex-1 overflow-y-auto p-5 sm:p-6 space-y-6">
        
        <!-- Role Details Section -->
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
          <div class="space-y-1 sm:col-span-2">
            <label class="block text-xs font-bold text-slate-700 dark:text-slate-300">
              Role Name <span class="text-rose-500">*</span>
            </label>
            <input
              v-model="roleForm.name"
              type="text"
              placeholder="e.g. Front Desk Supervisor, Waiter Lead..."
              class="w-full px-4 py-2.5 text-xs bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-xl text-slate-900 dark:text-white focus:outline-none focus:border-amber-500 font-semibold"
            />
          </div>

          <div class="space-y-1 sm:col-span-2">
            <label class="block text-xs font-bold text-slate-700 dark:text-slate-300">Description</label>
            <textarea
              v-model="roleForm.description"
              rows="2"
              placeholder="Primary responsibilities and scope of this role..."
              class="w-full px-4 py-2.5 text-xs bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-xl text-slate-900 dark:text-white focus:outline-none focus:border-amber-500 font-medium"
            ></textarea>
          </div>

          <div class="flex items-center gap-2 pt-1 sm:col-span-2">
            <input
              v-model="roleForm.is_active"
              type="checkbox"
              id="role_is_active"
              class="w-4 h-4 rounded border-slate-300 text-amber-500 focus:ring-amber-500"
            />
            <label for="role_is_active" class="text-xs font-bold text-slate-700 dark:text-slate-300 cursor-pointer">
              Active Status
            </label>
          </div>
        </div>

        <!-- Permission Selection Header & Search -->
        <div class="space-y-3 pt-2 border-t border-slate-100 dark:border-slate-800">
          <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
            <div class="flex items-center gap-2">
              <Key class="w-4 h-4 text-amber-500" />
              <h3 class="text-xs font-black uppercase tracking-wider text-slate-900 dark:text-white">
                Assign Permissions (Grouped by Module)
              </h3>
            </div>

            <div class="flex items-center gap-2">
              <div class="relative w-full sm:w-60">
                <Search class="w-3.5 h-3.5 absolute left-3 top-2.5 text-slate-400" />
                <input
                  v-model="permissionSearch"
                  type="text"
                  placeholder="Search capabilities..."
                  class="w-full pl-8 pr-3 py-1.5 text-xs bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-xl text-slate-900 dark:text-white focus:outline-none focus:border-amber-500 font-medium"
                />
              </div>

              <span class="px-2.5 py-1.5 rounded-xl bg-amber-500/10 border border-amber-500/30 text-amber-700 dark:text-amber-300 font-black text-xs whitespace-nowrap">
                {{ roleForm.selectedPermissions.length }} selected
              </span>
            </div>
          </div>
          <!-- Grouped Module Cards Container -->
          <div class="space-y-4 max-h-80 overflow-y-auto p-3 bg-slate-50/70 dark:bg-slate-950/70 rounded-2xl border border-slate-200 dark:border-slate-800">
            <div
              v-for="group in groupedPermissions"
              :key="group.moduleKey"
              class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-3.5 space-y-2.5 shadow-2xs"
            >
              <!-- Module Header Banner -->
              <div class="flex items-center justify-between pb-2 border-b border-slate-100 dark:border-slate-800">
                <div class="flex items-center gap-2">
                  <span :class="['w-3 h-3 rounded-full', group.info.color]"></span>
                  <h4 class="text-xs font-black uppercase tracking-wider text-slate-900 dark:text-white">
                    {{ group.info.title }}
                  </h4>
                  <span class="px-2 py-0.5 rounded-md bg-slate-100 dark:bg-slate-800 text-slate-500 dark:text-slate-400 font-bold text-[10px]">
                    {{ group.permissions.length }} permissions
                  </span>
                </div>

                <div class="flex items-center gap-1.5">
                  <button
                    type="button"
                    @click="selectAllInModule(group.permissions)"
                    class="px-2 py-0.5 rounded-md text-[10px] font-bold text-blue-600 dark:text-blue-400 bg-blue-500/10 hover:bg-blue-500/20 transition cursor-pointer"
                  >
                    + Select Module
                  </button>
                  <button
                    type="button"
                    @click="deselectAllInModule(group.permissions)"
                    class="px-2 py-0.5 rounded-md text-[10px] font-bold text-rose-600 dark:text-rose-400 bg-rose-500/10 hover:bg-rose-500/20 transition cursor-pointer"
                  >
                    Clear
                  </button>
                </div>
              </div>

              <!-- Module Permissions Grid -->
              <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                <div
                  v-for="perm in group.permissions"
                  :key="perm.id"
                  @click="togglePermission(perm.id)"
                  :class="[
                    'flex items-center gap-2.5 p-2.5 rounded-xl border text-xs font-medium cursor-pointer transition-all',
                    roleForm.selectedPermissions.includes(perm.id)
                      ? 'bg-amber-500/10 border-amber-500/40 text-amber-700 dark:text-amber-300 shadow-2xs'
                      : 'bg-slate-50/50 dark:bg-slate-950/50 border-slate-200 dark:border-slate-800 text-slate-600 dark:text-slate-400 hover:border-slate-300'
                  ]"
                >
                  <input
                    type="checkbox"
                    :checked="roleForm.selectedPermissions.includes(perm.id)"
                    class="w-3.5 h-3.5 rounded border-slate-300 text-amber-500 pointer-events-none"
                  />
                  <div class="truncate">
                    <p class="font-bold text-slate-900 dark:text-slate-100 truncate">{{ perm.name }}</p>
                    <p class="text-[10px] opacity-75 font-mono">{{ perm.slug }}</p>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Modal Footer -->
      <div class="flex items-center justify-between p-4 border-t border-slate-200 dark:border-slate-800 bg-slate-50/50 dark:bg-slate-950/50">
        <div class="text-xs font-bold text-slate-500">
          Total Selected: <strong class="text-amber-600 dark:text-amber-400">{{ roleForm.selectedPermissions.length }}</strong>
        </div>

        <div class="flex items-center gap-3">
          <button
            @click="emit('close')"
            class="px-4 py-2 rounded-xl text-xs font-bold text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800 transition"
          >
            Cancel
          </button>
          <button
            @click="handleSave"
            :disabled="loading || !roleForm.name.trim()"
            class="px-5 py-2 rounded-xl bg-amber-500 hover:bg-amber-600 text-slate-950 text-xs font-black shadow-md shadow-amber-500/20 transition cursor-pointer flex items-center gap-2"
          >
            <Check class="w-4 h-4" />
            <span>{{ editingRole ? 'Save Changes' : 'Create Role' }}</span>
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

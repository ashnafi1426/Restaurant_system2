<script setup lang="ts">
import { ref, computed, watch } from 'vue'
import type { Permission } from '../../types/rbacTypes'
import { useLanguageStore } from '../../stores/language'
import {
  X,
  PlusCircle,
  Key,
  Search,
  Check,
  CheckSquare,
  Square,
  Loader2,
  ShieldCheck,
  Info,
} from 'lucide-vue-next'

const props = defineProps<{
  show: boolean
  permissions: Permission[]
  loading?: boolean
  loadingPermissions?: boolean
  initialPermissionIds?: number[]
  initialName?: string
  initialDescription?: string
}>()

const emit = defineEmits<{
  (e: 'close'): void
  (
    e: 'save',
    payload: {
      name: string
      slug?: string
      description: string
      is_active: boolean
      permissions: number[]
    },
  ): void
}>()

const languageStore = useLanguageStore()

const name = ref('')
const slug = ref('')
const description = ref('')
const isActive = ref(true)
const selectedPermissionIds = ref<number[]>([])

const searchQuery = ref('')
const selectedModuleFilter = ref<string>('all')

const autoSlug = computed(() => {
  return name.value
    .toLowerCase()
    .trim()
    .replace(/[^a-z0-9]+/g, '-')
    .replace(/^-+|-+$/g, '')
})

const selectedSet = computed(() => new Set(selectedPermissionIds.value))
const isSelected = (id: number) => selectedSet.value.has(id)

watch(
  () => props.show,
  (isOpen) => {
    if (isOpen) {
      name.value = props.initialName || ''
      slug.value = ''
      description.value = props.initialDescription || ''
      isActive.value = true
      selectedPermissionIds.value = props.initialPermissionIds
        ? [...props.initialPermissionIds]
        : []
      searchQuery.value = ''
      selectedModuleFilter.value = 'all'
    }
  },
  { immediate: true },
)

const moduleList = computed(() => {
  const set = new Set<string>()
  ;(props.permissions || []).forEach((p) => {
    if (p.module) set.add(p.module)
  })
  return Array.from(set).sort()
})

const filteredPermissions = computed(() => {
  let list = props.permissions || []

  if (selectedModuleFilter.value !== 'all') {
    list = list.filter((p) => p.module === selectedModuleFilter.value)
  }

  const q = searchQuery.value.trim().toLowerCase()
  if (q) {
    list = list.filter(
      (p) =>
        p.name.toLowerCase().includes(q) ||
        p.slug.toLowerCase().includes(q) ||
        (p.module && p.module.toLowerCase().includes(q)) ||
        (p.description && p.description.toLowerCase().includes(q)),
    )
  }

  return list
})

const groupedPermissions = computed(() => {
  const groups: Record<string, Permission[]> = {}
  filteredPermissions.value.forEach((p) => {
    const mod = p.module || 'general'
    if (!groups[mod]) groups[mod] = []
    groups[mod].push(p)
  })

  return Object.entries(groups).map(([mod, perms]) => {
    const selectedCount = perms.filter((p) => isSelected(p.id)).length
    return {
      module: mod,
      permissions: perms,
      selectedCount,
      totalCount: perms.length,
      isAllSelected: perms.length > 0 && selectedCount === perms.length,
    }
  })
})

const togglePermission = (id: number) => {
  const idx = selectedPermissionIds.value.indexOf(id)
  if (idx === -1) {
    selectedPermissionIds.value.push(id)
  } else {
    selectedPermissionIds.value.splice(idx, 1)
  }
}

const toggleModule = (groupPerms: Permission[], isAllSelected: boolean) => {
  const ids = groupPerms.map((p) => p.id)
  if (isAllSelected) {
    const removeSet = new Set(ids)
    selectedPermissionIds.value = selectedPermissionIds.value.filter((id) => !removeSet.has(id))
  } else {
    const currentSet = new Set(selectedPermissionIds.value)
    ids.forEach((id) => currentSet.add(id))
    selectedPermissionIds.value = Array.from(currentSet)
  }
}

const selectAllFiltered = () => {
  const filteredIds = filteredPermissions.value.map((p) => p.id)
  const merged = new Set([...selectedPermissionIds.value, ...filteredIds])
  selectedPermissionIds.value = Array.from(merged)
}

const deselectAllFiltered = () => {
  if (searchQuery.value.trim() || selectedModuleFilter.value !== 'all') {
    const toRemove = new Set(filteredPermissions.value.map((p) => p.id))
    selectedPermissionIds.value = selectedPermissionIds.value.filter((id) => !toRemove.has(id))
  } else {
    selectedPermissionIds.value = []
  }
}

const handleSubmit = () => {
  if (!name.value.trim() || props.loading) return
  emit('save', {
    name: name.value.trim(),
    slug: slug.value.trim() || autoSlug.value,
    description: description.value.trim(),
    is_active: isActive.value,
    permissions: selectedPermissionIds.value,
  })
}
</script>

<template>
  <Teleport to="body">
    <div
      v-if="show"
      class="fixed inset-0 z-[9999] flex items-center justify-center p-3 sm:p-5 overflow-hidden"
    >
      <div
        class="fixed inset-0 bg-slate-950/70 backdrop-blur-xs transition-opacity"
        @click="emit('close')"
      ></div>

      <div
        class="relative z-10 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl sm:rounded-3xl max-w-4xl w-full max-h-[90vh] flex flex-col shadow-2xl overflow-hidden my-auto"
      >
        <div
          class="flex items-center justify-between px-5 sm:px-6 py-4 sm:py-5 border-b border-slate-200 dark:border-slate-800 bg-slate-50/80 dark:bg-slate-900/80 backdrop-blur-xs flex-shrink-0"
        >
          <div class="flex items-center gap-3">
            <div
              class="w-10 h-10 rounded-xl bg-blue-500/10 text-blue-600 dark:text-blue-400 border border-blue-500/20 flex items-center justify-center flex-shrink-0"
            >
              <PlusCircle class="w-5 h-5" />
            </div>
            <div>
              <h2
                class="text-lg sm:text-xl font-black tracking-tight text-slate-900 dark:text-white"
              >
                {{ languageStore.t('create_new_role', 'Create New Role') }}
              </h2>
              <p class="text-xs text-slate-500 dark:text-slate-400 font-medium">
                {{
                  languageStore.t(
                    'create_role_subtitle',
                    'Define role title, scope of authority, and granular feature permissions.',
                  )
                }}
              </p>
            </div>
          </div>

          <button
            type="button"
            @click="emit('close')"
            class="p-2 rounded-xl text-slate-400 hover:text-slate-700 dark:hover:text-slate-200 hover:bg-slate-100 dark:hover:bg-slate-800 transition cursor-pointer"
            :title="languageStore.t('close', 'Close')"
          >
            <X class="w-5 h-5" />
          </button>
        </div>

        <div class="flex-1 overflow-y-auto px-5 sm:px-6 py-5 space-y-5">
          <div
            class="bg-slate-50/70 dark:bg-slate-950/40 p-4 sm:p-5 rounded-2xl border border-slate-200/80 dark:border-slate-800/80 space-y-4"
          >
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
              <div class="space-y-1.5">
                <label
                  class="block text-xs font-bold uppercase tracking-wider text-slate-700 dark:text-slate-300"
                >
                  {{ languageStore.t('role_name', 'Role Name') }}
                  <span class="text-rose-500">*</span>
                </label>
                <input
                  v-model="name"
                  type="text"
                  :placeholder="
                    languageStore.t(
                      'role_name_placeholder',
                      'e.g. Front Desk Lead, Senior Cashier, Head Chef...',
                    )
                  "
                  class="w-full px-3.5 py-2.5 text-xs sm:text-sm bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl text-slate-900 dark:text-white placeholder-slate-400 focus:outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20 font-semibold transition"
                  autofocus
                />
              </div>

              <div class="space-y-1.5">
                <div class="flex items-center justify-between">
                  <label
                    class="block text-xs font-bold uppercase tracking-wider text-slate-700 dark:text-slate-300"
                  >
                    {{ languageStore.t('role_key', 'Role Key / Slug') }}
                  </label>
                  <span class="text-[10px] text-slate-400 font-medium">
                    {{ languageStore.t('auto_generated', 'Auto-generated') }}
                  </span>
                </div>
                <input
                  v-model="slug"
                  type="text"
                  :placeholder="autoSlug || 'e.g. front-desk-lead'"
                  class="w-full px-3.5 py-2.5 text-xs sm:text-sm bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl text-slate-900 dark:text-white placeholder-slate-400 focus:outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20 font-mono text-xs transition"
                />
              </div>

              <div class="space-y-1.5 sm:col-span-2">
                <label
                  class="block text-xs font-bold uppercase tracking-wider text-slate-700 dark:text-slate-300"
                >
                  {{ languageStore.t('description', 'Description & Responsibilities') }}
                </label>
                <textarea
                  v-model="description"
                  rows="2"
                  :placeholder="
                    languageStore.t(
                      'description_placeholder',
                      'Describe the operational duties, responsibilities, and department scope...',
                    )
                  "
                  class="w-full px-3.5 py-2 text-xs sm:text-sm bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl text-slate-900 dark:text-white placeholder-slate-400 focus:outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20 font-medium transition resize-none"
                ></textarea>
              </div>

              <div class="sm:col-span-2 pt-1 flex items-center justify-between">
                <label class="flex items-center gap-3 cursor-pointer select-none">
                  <input
                    v-model="isActive"
                    type="checkbox"
                    class="w-4 h-4 rounded text-blue-600 focus:ring-blue-500 border-slate-300 cursor-pointer"
                  />
                  <div>
                    <span class="text-xs font-bold text-slate-900 dark:text-white">
                      {{ languageStore.t('active_status', 'Active Status') }}
                    </span>
                    <span class="text-[11px] text-slate-500 dark:text-slate-400 ml-2">
                      {{
                        languageStore.t(
                          'active_status_hint',
                          'Staff can immediately be assigned to this role once created.',
                        )
                      }}
                    </span>
                  </div>
                </label>
              </div>
            </div>
          </div>

          <div class="space-y-3">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2.5 pb-1">
              <div class="flex items-center gap-2">
                <Key class="w-4 h-4 text-blue-600 dark:text-blue-400" />
                <h3
                  class="text-xs font-black uppercase tracking-wider text-slate-900 dark:text-white"
                >
                  {{ languageStore.t('assign_permissions', 'Assign Permissions') }}
                </h3>
                <span
                  class="px-2 py-0.5 rounded-full bg-blue-500/10 border border-blue-500/30 text-blue-600 dark:text-blue-400 font-extrabold text-[11px]"
                >
                  {{ selectedPermissionIds.length }} {{ languageStore.t('selected', 'selected') }}
                </span>
              </div>

              <div class="flex items-center gap-2">
                <button
                  type="button"
                  @click="selectAllFiltered"
                  class="px-2.5 py-1 rounded-lg text-xs font-bold text-blue-600 dark:text-blue-400 bg-blue-50 hover:bg-blue-100 dark:bg-blue-900/30 dark:hover:bg-blue-900/50 border border-blue-200 dark:border-blue-800 transition cursor-pointer flex items-center gap-1"
                >
                  <CheckSquare class="w-3.5 h-3.5" />
                  <span>{{ languageStore.t('select_all', 'Select All') }}</span>
                </button>
                <button
                  type="button"
                  @click="deselectAllFiltered"
                  class="px-2.5 py-1 rounded-lg text-xs font-bold text-slate-600 dark:text-slate-400 bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 transition cursor-pointer flex items-center gap-1"
                >
                  <Square class="w-3.5 h-3.5" />
                  <span>{{ languageStore.t('clear', 'Clear') }}</span>
                </button>
              </div>
            </div>

            <div class="flex flex-col sm:flex-row items-center gap-2.5">
              <div class="relative flex-1 w-full">
                <Search
                  class="w-3.5 h-3.5 absolute left-3 top-1/2 -translate-y-1/2 text-slate-400"
                />
                <input
                  v-model="searchQuery"
                  type="text"
                  :placeholder="
                    languageStore.t(
                      'search_permissions',
                      'Search capabilities by name, action, or module...',
                    )
                  "
                  class="w-full pl-9 pr-3 py-2 text-xs bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-xl text-slate-900 dark:text-white placeholder-slate-400 focus:outline-none focus:border-blue-500 focus:ring-1 focus:ring-blue-500 font-medium transition"
                />
              </div>

              <div class="w-full sm:w-auto">
                <select
                  v-model="selectedModuleFilter"
                  class="w-full sm:w-48 px-3 py-2 text-xs bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-xl text-slate-900 dark:text-white focus:outline-none focus:border-blue-500 font-semibold cursor-pointer transition"
                >
                  <option value="all">
                    {{ languageStore.t('all_modules', 'All Modules') }} ({{ permissions.length }})
                  </option>
                  <option v-for="mod in moduleList" :key="mod" :value="mod">
                    {{ mod.toUpperCase() }}
                  </option>
                </select>
              </div>
            </div>

            <div
              v-if="loadingPermissions"
              class="py-12 text-center bg-slate-50/60 dark:bg-slate-950/40 rounded-2xl border border-slate-200 dark:border-slate-800"
            >
              <Loader2 class="w-7 h-7 text-blue-600 animate-spin mx-auto mb-2" />
              <p class="text-xs font-bold text-slate-500 dark:text-slate-400">
                {{ languageStore.t('loading_permissions', 'Loading system permissions matrix...') }}
              </p>
            </div>

            <div
              v-else-if="groupedPermissions.length === 0"
              class="py-12 text-center bg-slate-50/60 dark:bg-slate-950/40 rounded-2xl border border-slate-200 dark:border-slate-800"
            >
              <Info class="w-7 h-7 text-slate-400 mx-auto mb-2 opacity-60" />
              <p class="text-xs font-bold text-slate-600 dark:text-slate-300">
                {{ languageStore.t('no_permissions_match', 'No permissions match your filter.') }}
              </p>
            </div>

            <div v-else class="space-y-3 max-h-[320px] overflow-y-auto pr-1">
              <div
                v-for="group in groupedPermissions"
                :key="group.module"
                class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-3.5 space-y-2.5 shadow-xs"
              >
                <div
                  class="flex items-center justify-between pb-2 border-b border-slate-100 dark:border-slate-800"
                >
                  <div class="flex items-center gap-2">
                    <span class="w-2.5 h-2.5 rounded-full bg-blue-500 flex-shrink-0"></span>
                    <h4
                      class="text-xs font-black uppercase tracking-wider text-slate-900 dark:text-white"
                    >
                      {{ group.module.replace(/_/g, ' ') }}
                    </h4>
                    <span
                      class="px-2 py-0.5 rounded-md bg-slate-100 dark:bg-slate-800 text-slate-500 dark:text-slate-400 font-bold text-[10px]"
                    >
                      {{ group.selectedCount }} / {{ group.totalCount }}
                    </span>
                  </div>

                  <button
                    type="button"
                    @click="toggleModule(group.permissions, group.isAllSelected)"
                    class="px-2 py-0.5 rounded-md text-[11px] font-bold transition cursor-pointer flex items-center gap-1"
                    :class="[
                      group.isAllSelected
                        ? 'bg-rose-50 text-rose-600 dark:bg-rose-950/40 dark:text-rose-400 hover:bg-rose-100'
                        : 'bg-blue-50 text-blue-600 dark:bg-blue-950/40 dark:text-blue-400 hover:bg-blue-100',
                    ]"
                  >
                    <CheckSquare v-if="!group.isAllSelected" class="w-3 h-3" />
                    <Square v-else class="w-3 h-3" />
                    <span>{{
                      group.isAllSelected
                        ? languageStore.t('deselect_module', 'Deselect Module')
                        : languageStore.t('select_module', 'Select Module')
                    }}</span>
                  </button>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                  <div
                    v-for="perm in group.permissions"
                    :key="perm.id"
                    @click="togglePermission(perm.id)"
                    class="flex items-center gap-2.5 p-2.5 rounded-xl border text-xs font-medium cursor-pointer transition select-none"
                    :class="[
                      isSelected(perm.id)
                        ? 'bg-blue-50/70 border-blue-400 text-slate-900 dark:bg-blue-950/40 dark:border-blue-600 dark:text-blue-100'
                        : 'bg-slate-50/50 border-slate-200/80 text-slate-600 dark:bg-slate-950/50 dark:border-slate-800/80 dark:text-slate-400 hover:border-slate-300 dark:hover:border-slate-700',
                    ]"
                  >
                    <div
                      class="w-4 h-4 rounded flex items-center justify-center flex-shrink-0 transition border"
                      :class="[
                        isSelected(perm.id)
                          ? 'bg-blue-600 border-blue-600 text-white'
                          : 'border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-900',
                      ]"
                    >
                      <Check v-if="isSelected(perm.id)" class="w-3 h-3 stroke-[3]" />
                    </div>

                    <div class="min-w-0 flex-1">
                      <div class="flex items-center justify-between gap-1">
                        <p class="font-bold text-slate-900 dark:text-slate-100 truncate text-xs">
                          {{ perm.name }}
                        </p>
                        <span
                          v-if="perm.action"
                          class="text-[9px] font-extrabold uppercase px-1.5 py-0.5 rounded bg-slate-100 dark:bg-slate-800 text-slate-500 dark:text-slate-400 flex-shrink-0"
                        >
                          {{ perm.action }}
                        </span>
                      </div>
                      <p class="text-[10px] text-slate-400 font-mono truncate mt-0.5">
                        {{ perm.slug }}
                      </p>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>

        <div
          class="flex items-center justify-between px-5 sm:px-6 py-3.5 sm:py-4 border-t border-slate-200 dark:border-slate-800 bg-slate-50/90 dark:bg-slate-900/90 backdrop-blur-xs flex-shrink-0"
        >
          <div class="text-xs font-bold text-slate-600 dark:text-slate-400">
            {{ languageStore.t('selected_permissions', 'Selected Permissions') }}:
            <strong class="text-blue-600 dark:text-blue-400 font-black ml-1">{{
              selectedPermissionIds.length
            }}</strong>
          </div>

          <div class="flex items-center gap-2.5">
            <button
              type="button"
              @click="emit('close')"
              class="px-4 py-2 rounded-xl text-xs font-bold text-slate-600 dark:text-slate-400 hover:bg-slate-200/70 dark:hover:bg-slate-800 transition cursor-pointer"
            >
              {{ languageStore.t('cancel', 'Cancel') }}
            </button>

            <button
              type="button"
              @click="handleSubmit"
              :disabled="loading || !name.trim()"
              class="px-5 py-2 rounded-xl bg-blue-600 hover:bg-blue-700 disabled:opacity-50 text-white text-xs font-bold shadow-md shadow-blue-600/20 transition cursor-pointer flex items-center gap-1.5"
            >
              <Loader2 v-if="loading" class="w-4 h-4 animate-spin" />
              <Check v-else class="w-4 h-4" />
              <span>{{
                loading
                  ? languageStore.t('creating', 'Creating...')
                  : languageStore.t('create_role', 'Create Role')
              }}</span>
            </button>
          </div>
        </div>
      </div>
    </div>
  </Teleport>
</template>

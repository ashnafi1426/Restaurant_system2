<script setup lang="ts">
import { ref, computed } from 'vue'
import type { Role, RbacUserSummary } from '../../types/rbacTypes'
import {
  X,
  Users,
  Search,
  Mail,
  Phone,
  ShieldCheck,
  UserCheck,
  UserX,
  ExternalLink,
  Loader2
} from 'lucide-vue-next'

const props = defineProps<{
  show: boolean
  role: Role | null
  users: RbacUserSummary[]
  loading?: boolean
}>()

const emit = defineEmits<{
  (e: 'close'): void
  (e: 'navigate-user-roles'): void
}>()

const searchQuery = ref('')

const roleUsers = computed(() => {
  if (!props.role) return []
  const roleSlug = (props.role.slug || props.role.name).toLowerCase()
  const roleId = props.role.id

  return props.users.filter(user => {
    // Check if user has role in their roles array or primary role
    const matchesRoleId = user.roles && user.roles.some(r => r.id === roleId)
    const matchesSlug = (user.primary_role_slug || '').toLowerCase() === roleSlug ||
      (user.legacy_role || '').toLowerCase() === roleSlug

    return matchesRoleId || matchesSlug
  })
})

const filteredUsers = computed(() => {
  if (!searchQuery.value.trim()) return roleUsers.value
  const q = searchQuery.value.toLowerCase().trim()
  return roleUsers.value.filter(u =>
    u.full_name.toLowerCase().includes(q) ||
    u.email.toLowerCase().includes(q) ||
    (u.phone && u.phone.toLowerCase().includes(q))
  )
})
</script>

<template>
  <Teleport to="body">
    <div v-if="show" class="fixed inset-0 z-[9999] flex items-center justify-center p-3 sm:p-6 overflow-hidden">
      <!-- Backdrop -->
      <div class="fixed inset-0 bg-slate-950/80 backdrop-blur-md transition-opacity" @click="emit('close')"></div>

      <!-- Modal Window -->
      <div class="relative z-10 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-3xl max-w-2xl w-full max-h-[85vh] flex flex-col shadow-2xl overflow-hidden my-auto animate-in fade-in zoom-in duration-150">
      
      <!-- Modal Header -->
      <div class="flex items-center justify-between px-6 py-5 border-b border-slate-200 dark:border-slate-800 bg-slate-50/80 dark:bg-slate-900/80 backdrop-blur-xs">
        <div class="flex items-center gap-3.5">
          <div class="p-3 rounded-2xl bg-indigo-500/10 text-indigo-500 border border-indigo-500/20 shadow-xs">
            <Users class="w-6 h-6" />
          </div>
          <div>
            <h2 class="text-lg font-black tracking-tight text-slate-900 dark:text-white flex items-center gap-2">
              Assigned Staff: <span class="capitalize text-indigo-600 dark:text-indigo-400">{{ role?.name }}</span>
            </h2>
            <p class="text-xs text-slate-500 dark:text-slate-400 font-medium mt-0.5">
              List of system accounts inheriting access privileges from this role.
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

      <!-- Modal Body -->
      <div class="flex-1 overflow-y-auto p-6 space-y-4">
        <!-- Search & Info Bar -->
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
          <div class="relative flex-1">
            <Search class="w-4 h-4 absolute left-3.5 top-3 text-slate-400" />
            <input
              v-model="searchQuery"
              type="text"
              placeholder="Search assigned users by name or email..."
              class="w-full pl-10 pr-4 py-2.5 text-xs bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-xl text-slate-900 dark:text-white focus:outline-none focus:border-indigo-500 font-medium"
            />
          </div>

          <div class="px-3 py-2 rounded-xl bg-indigo-500/10 border border-indigo-500/20 text-indigo-700 dark:text-indigo-300 font-black text-xs whitespace-nowrap self-start sm:self-auto">
            {{ roleUsers.length }} Users Assigned
          </div>
        </div>

        <!-- Loading State -->
        <div v-if="loading" class="py-12 text-center">
          <Loader2 class="w-8 h-8 text-indigo-500 animate-spin mx-auto mb-2" />
          <p class="text-xs font-bold text-slate-500 dark:text-slate-400">Fetching assigned user accounts...</p>
        </div>

        <!-- Empty State -->
        <div v-else-if="filteredUsers.length === 0" class="py-12 text-center bg-slate-50/50 dark:bg-slate-950/50 rounded-2xl border border-slate-200 dark:border-slate-800">
          <UserX class="w-10 h-10 text-slate-400 mx-auto mb-2 opacity-50" />
          <p class="text-sm font-bold text-slate-700 dark:text-slate-300">
            {{ roleUsers.length === 0 ? 'No users are currently assigned to this role.' : 'No users match your search.' }}
          </p>
          <p class="text-xs text-slate-500 dark:text-slate-400 mt-1 max-w-sm mx-auto">
            You can assign users to this role anytime from the User Role Assignments page.
          </p>
        </div>

        <!-- Users List -->
        <div v-else class="space-y-2.5">
          <div
            v-for="u in filteredUsers"
            :key="u.id"
            class="flex items-center justify-between p-3.5 rounded-2xl bg-slate-50 dark:bg-slate-950/60 border border-slate-200/80 dark:border-slate-800/80 hover:border-indigo-500/30 transition group"
          >
            <div class="flex items-center gap-3.5">
              <div class="w-10 h-10 rounded-xl bg-indigo-500/10 text-indigo-600 dark:text-indigo-400 border border-indigo-500/20 flex items-center justify-center font-black text-sm uppercase">
                {{ u.full_name.charAt(0) }}
              </div>

              <div>
                <div class="flex items-center gap-2">
                  <h4 class="text-xs font-extrabold text-slate-900 dark:text-white">
                    {{ u.full_name }}
                  </h4>
                  <span
                    :class="[
                      'w-2 h-2 rounded-full',
                      (u.is_active ?? true) ? 'bg-emerald-500' : 'bg-rose-500'
                    ]"
                  ></span>
                </div>

                <div class="flex items-center gap-4 text-[11px] text-slate-500 dark:text-slate-400 mt-0.5">
                  <span class="flex items-center gap-1">
                    <Mail class="w-3 h-3 text-slate-400" />
                    {{ u.email }}
                  </span>
                  <span v-if="u.phone" class="hidden sm:flex items-center gap-1">
                    <Phone class="w-3 h-3 text-slate-400" />
                    {{ u.phone }}
                  </span>
                </div>
              </div>
            </div>

            <div class="flex items-center gap-2">
              <span v-if="u.roles && u.roles.some(r => r.id === role?.id && r.is_primary)" class="px-2 py-1 rounded-lg bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 font-extrabold text-[10px] uppercase border border-emerald-500/20">
                Primary Role
              </span>
            </div>
          </div>
        </div>
      </div>

      <!-- Modal Footer -->
      <div class="flex items-center justify-between px-6 py-4 border-t border-slate-200 dark:border-slate-800 bg-slate-50/80 dark:bg-slate-900/80 backdrop-blur-xs">
        <button
          @click="emit('navigate-user-roles')"
          class="inline-flex items-center gap-1.5 text-xs font-extrabold text-indigo-600 dark:text-indigo-400 hover:underline cursor-pointer"
        >
          <span>Manage User Role Assignments</span>
          <ExternalLink class="w-3.5 h-3.5" />
        </button>

        <button
          @click="emit('close')"
          class="px-5 py-2 rounded-xl bg-slate-200 dark:bg-slate-800 text-slate-800 dark:text-slate-200 text-xs font-bold hover:bg-slate-300 dark:hover:bg-slate-700 transition cursor-pointer"
        >
          Close
        </button>
      </div>
    </div>
  </div>
</Teleport>
</template>

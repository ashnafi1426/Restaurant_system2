<script setup lang="ts">
import { onMounted, computed, watch, ref } from 'vue'
import { useRouter } from 'vue-router'
import DashboardLayout from '@/Layouts/DashboardLayout.vue'
import UserTable from '../../../components/user/UserTable.vue'
import { useUserStore } from '../../../stores/user'
import { useHotelStore } from '../../../stores/hotelStore'
import { useLanguageStore } from '../../../stores/language'
import { Users, UserCheck, UserX, Building2 } from 'lucide-vue-next'
import type { User } from '@/types/user'

const router = useRouter()
const userStore = useUserStore()
const hotelStore = useHotelStore()
const languageStore = useLanguageStore()

const getCacheKey = () => `users_cache_${hotelStore.hotelId || 'default'}`

const loadFromCache = (): boolean => {
  try {
    const raw = localStorage.getItem(getCacheKey())
    if (raw) {
      const parsed = JSON.parse(raw)
      if (Array.isArray(parsed) && parsed.length > 0) {
        userStore.users = parsed
        return true
      }
    }
  } catch (e) {}
  return false
}

const saveToCache = (users: User[]) => {
  try {
    localStorage.setItem(getCacheKey(), JSON.stringify(users))
  } catch (e) {}
}

const isInitialLoading = ref(true);
const isDeletingUser = ref(false);

const loadUsers = async (forceRefresh = false) => {
  if (!forceRefresh) loadFromCache()
  try {
    await userStore.fetchUsers({}, forceRefresh)
    saveToCache(userStore.users || [])
  } finally {
    isInitialLoading.value = false
  }
}

onMounted(() => {
  const hasCache = loadFromCache()
  if (hasCache) {
    isInitialLoading.value = false
  }
  loadUsers(false)
})

watch(
  () => hotelStore.hotelId,
  () => loadUsers(false),
)

// Computed metrics
const usersList = computed(() => userStore.users || [])
const totalUsers = computed(() => usersList.value.length)
const activeUsers = computed(() => usersList.value.filter((u) => u.is_active).length)
const inactiveUsers = computed(() => totalUsers.value - activeUsers.value)

// Navigation & Actions
const createUser = () => router.push('/users/create')
const viewUser = (user: User) => router.push(`/users/${user.id}`)
const editUser = (user: User) => router.push(`/users/${user.id}/edit`)

const deleteUser = async (user: User) => {
  const confirmMsg = languageStore.t(
    'delete_user_confirm',
    `Are you sure you want to delete ${user.first_name || 'this user'}?`,
  )
  if (confirm(confirmMsg)) {
    isDeletingUser.value = true
    try {
      await userStore.deleteUser(String(user.id))
      saveToCache(userStore.users || [])
    } finally {
      isDeletingUser.value = false
    }
  }
}

const refresh = () => loadUsers(true)
</script>

<template>
  <DashboardLayout>
    <div
      class="space-y-4 sm:space-y-6 bg-slate-50 dark:bg-slate-950 min-h-screen p-3 sm:p-6 max-w-full font-sans"
    >
      <div
        class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 sm:gap-4 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 p-4 sm:p-6 rounded-2xl shadow-xs"
      >
        <div>
          <div class="flex flex-wrap items-center gap-2">
            <h1
              class="text-lg sm:text-2xl font-black text-slate-900 dark:text-white tracking-tight"
            >
              {{ languageStore.t('user_staff_management', 'User & Staff Management') }}
            </h1>
            <span
              v-if="hotelStore.hotelName"
              class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-[11px] sm:text-xs font-bold bg-blue-100 dark:bg-blue-900/30 text-blue-800 dark:text-blue-300 border border-blue-200 dark:border-blue-700/50"
            >
              <Building2 class="w-3 h-3" />
              {{ hotelStore.hotelName }}
            </span>
          </div>
          <p class="text-[11px] sm:text-xs text-slate-500 dark:text-slate-400 mt-1">
            {{
              languageStore.t(
                'user_staff_desc',
                'Manage system accounts, staff roles, and department access.',
              )
            }}
          </p>
        </div>
      </div>

      <!-- Compact 3-Column Responsive KPI Cards -->
      <div class="grid grid-cols-3 gap-2 sm:gap-6">
        <div
          class="bg-white dark:bg-slate-900 rounded-xl sm:rounded-2xl border border-slate-200 dark:border-slate-800 p-3 sm:p-5 shadow-xs flex items-center justify-between min-w-0"
        >
          <div class="min-w-0">
            <p class="text-[10px] sm:text-xs font-bold text-slate-500 dark:text-slate-400 truncate">
              {{ languageStore.t('total_staff', 'Total Staff') }}
            </p>
            <h2
              class="text-lg sm:text-3xl font-black text-slate-900 dark:text-white mt-0.5 sm:mt-1 truncate"
            >
              {{ totalUsers }}
            </h2>
          </div>
          <div
            class="p-2 sm:p-3 rounded-xl sm:rounded-2xl bg-blue-500/10 text-blue-600 dark:text-blue-400 shrink-0 hidden xs:flex"
          >
            <Users class="w-4 h-4 sm:w-6 sm:h-6" />
          </div>
        </div>

        <div
          class="bg-white dark:bg-slate-900 rounded-xl sm:rounded-2xl border border-slate-200 dark:border-slate-800 p-3 sm:p-5 shadow-xs flex items-center justify-between min-w-0"
        >
          <div class="min-w-0">
            <p class="text-[10px] sm:text-xs font-bold text-slate-500 dark:text-slate-400 truncate">
              {{ languageStore.t('active_accounts', 'Active') }}
            </p>
            <h2
              class="text-lg sm:text-3xl font-black text-emerald-600 dark:text-emerald-400 mt-0.5 sm:mt-1 truncate"
            >
              {{ activeUsers }}
            </h2>
          </div>
          <div
            class="p-2 sm:p-3 rounded-xl sm:rounded-2xl bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 shrink-0 hidden xs:flex"
          >
            <UserCheck class="w-4 h-4 sm:w-6 sm:h-6" />
          </div>
        </div>

        <div
          class="bg-white dark:bg-slate-900 rounded-xl sm:rounded-2xl border border-slate-200 dark:border-slate-800 p-3 sm:p-5 shadow-xs flex items-center justify-between min-w-0"
        >
          <div class="min-w-0">
            <p class="text-[10px] sm:text-xs font-bold text-slate-500 dark:text-slate-400 truncate">
              {{ languageStore.t('inactive_accounts', 'Inactive') }}
            </p>
            <h2
              class="text-lg sm:text-3xl font-black text-rose-600 dark:text-rose-400 mt-0.5 sm:mt-1 truncate"
            >
              {{ inactiveUsers }}
            </h2>
          </div>
          <div
            class="p-2 sm:p-3 rounded-xl sm:rounded-2xl bg-rose-500/10 text-rose-600 dark:text-rose-400 shrink-0 hidden xs:flex"
          >
            <UserX class="w-4 h-4 sm:w-6 sm:h-6" />
          </div>
        </div>
      </div>

      <UserTable
        :users="usersList"
        :loading="userStore.loading || isInitialLoading || isDeletingUser"
        @view="viewUser"
        @edit="editUser"
        @delete="deleteUser"
        @create="createUser"
        @refresh="refresh"
      />
    </div>
  </DashboardLayout>
</template>

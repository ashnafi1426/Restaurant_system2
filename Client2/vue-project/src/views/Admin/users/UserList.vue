<script setup lang="ts">
import { onMounted, computed, watch } from 'vue'
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

const loadUsers = async (forceRefresh = false) => {
  if (!forceRefresh) loadFromCache()
  await userStore.fetchUsers({}, forceRefresh)
  saveToCache(userStore.users || [])
}

onMounted(() => loadUsers(false))

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
    await userStore.deleteUser(String(user.id))
    saveToCache(userStore.users || [])
  }
}

const refresh = () => loadUsers(true)
</script>

<template>
  <DashboardLayout>
    <div
      class="space-y-6 bg-slate-50 dark:bg-slate-950 min-h-screen p-4 sm:p-6 max-w-full overflow-hidden font-sans"
    >
      <!-- Header -->
      <div
        class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 p-5 sm:p-6 rounded-2xl shadow-xs"
      >
        <div>
          <div class="flex items-center gap-2">
            <h1 class="text-xl sm:text-2xl font-black text-slate-900 dark:text-white">
              {{ languageStore.t('user_staff_management', 'User & Staff Management') }}
            </h1>
            <span
              v-if="hotelStore.hotelName"
              class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-xs font-bold bg-blue-100 dark:bg-blue-900/30 text-blue-800 dark:text-blue-300 border border-blue-200 dark:border-blue-700/50"
            >
              <Building2 class="w-3 h-3" />
              {{ hotelStore.hotelName }}
            </span>
          </div>
          <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">
            {{
              languageStore.t(
                'user_staff_desc',
                'Manage system accounts, staff roles, and department access.',
              )
            }}
          </p>
        </div>
      </div>

      <!-- Stats Cards -->
      <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 sm:gap-6">
        <!-- Total Users -->
        <div
          class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-800 p-5 shadow-xs flex items-center justify-between"
        >
          <div>
            <p class="text-xs font-bold text-slate-500 dark:text-slate-400">
              {{ languageStore.t('total_staff', 'Total Staff') }}
            </p>
            <h2 class="text-2xl sm:text-3xl font-black text-slate-900 dark:text-white mt-1">
              {{ totalUsers }}
            </h2>
          </div>
          <div class="p-3 rounded-2xl bg-blue-500/10 text-blue-600 dark:text-blue-400">
            <Users class="w-6 h-6" />
          </div>
        </div>

        <!-- Active Users -->
        <div
          class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-800 p-5 shadow-xs flex items-center justify-between"
        >
          <div>
            <p class="text-xs font-bold text-slate-500 dark:text-slate-400">
              {{ languageStore.t('active_accounts', 'Active Accounts') }}
            </p>
            <h2 class="text-2xl sm:text-3xl font-black text-emerald-600 dark:text-emerald-400 mt-1">
              {{ activeUsers }}
            </h2>
          </div>
          <div class="p-3 rounded-2xl bg-emerald-500/10 text-emerald-600 dark:text-emerald-400">
            <UserCheck class="w-6 h-6" />
          </div>
        </div>

        <!-- Inactive Users -->
        <div
          class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-800 p-5 shadow-xs flex items-center justify-between"
        >
          <div>
            <p class="text-xs font-bold text-slate-500 dark:text-slate-400">
              {{ languageStore.t('inactive_accounts', 'Inactive Accounts') }}
            </p>
            <h2 class="text-2xl sm:text-3xl font-black text-rose-600 dark:text-rose-400 mt-1">
              {{ inactiveUsers }}
            </h2>
          </div>
          <div class="p-3 rounded-2xl bg-rose-500/10 text-rose-600 dark:text-rose-400">
            <UserX class="w-6 h-6" />
          </div>
        </div>
      </div>

      <!-- Table Component With Integrated Toolbar & Filter -->
      <UserTable
        :users="usersList"
        :loading="userStore.loading"
        @view="viewUser"
        @edit="editUser"
        @delete="deleteUser"
        @create="createUser"
        @refresh="refresh"
      />
    </div>
  </DashboardLayout>
</template>

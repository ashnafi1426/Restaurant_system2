<script setup lang="ts">
import { onMounted, computed } from 'vue'
import { useRouter } from 'vue-router'
import DashboardLayout from '../../../layouts/DashboardLayout.vue'
import UserTable from '../../../components/user/UserTable.vue'
import { useUserStore } from '../../../stores/user'
import { Plus, Users, UserCheck, UserX } from 'lucide-vue-next'
import type { User } from '../../../type/user'

const router = useRouter()
const userStore = useUserStore()

onMounted(async () => {
  await userStore.fetchUsers()
})

const totalUsers = computed(() => (userStore.users || []).length)
const activeUsers = computed(() => (userStore.users || []).filter((user) => user.is_active).length)
const inactiveUsers = computed(() => (userStore.users || []).filter((user) => !user.is_active).length)

const createUser = () => {
  router.push('/users/create')
}

const viewUser = (user: User) => {
  router.push(`/users/${user.id}`)
}

const editUser = (user: User) => {
  router.push(`/users/${user.id}/edit`)
}

const deleteUser = async (user: User) => {
  if (confirm(`Are you sure you want to delete ${user.first_name || 'this user'}?`)) {
    await userStore.deleteUser(String(user.id))
  }
}

const refresh = async () => {
  await userStore.fetchUsers()
}
</script>

<template>
  <DashboardLayout>
    <div class="space-y-6 bg-slate-50 dark:bg-slate-950 min-h-screen p-4 sm:p-6 max-w-full overflow-hidden font-sans">
      <!-- Header -->
      <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 p-5 sm:p-6 rounded-2xl shadow-xs">
        <div>
          <h1 class="text-xl sm:text-2xl font-black text-slate-900 dark:text-white">User & Staff Management</h1>
          <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">Manage system accounts, staff roles, and department access.</p>
        </div>

        <button
          @click="createUser"
          class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2.5 rounded-xl font-bold text-xs sm:text-sm shadow-md shadow-blue-600/20 transition cursor-pointer inline-flex items-center justify-center gap-2"
        >
          <Plus class="w-4 h-4 stroke-[3]" />
          <span>Create User</span>
        </button>
      </div>

      <!-- Stats Cards -->
      <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 sm:gap-6">
        <!-- Total Users -->
        <div class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-800 p-5 shadow-xs flex items-center justify-between">
          <div>
            <p class="text-xs font-bold text-slate-500 dark:text-slate-400">Total Staff</p>
            <h2 class="text-2xl sm:text-3xl font-black text-slate-900 dark:text-white mt-1">{{ totalUsers }}</h2>
          </div>
          <div class="p-3 rounded-2xl bg-blue-500/10 text-blue-600 dark:text-blue-400">
            <Users class="w-6 h-6" />
          </div>
        </div>

        <!-- Active Users -->
        <div class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-800 p-5 shadow-xs flex items-center justify-between">
          <div>
            <p class="text-xs font-bold text-slate-500 dark:text-slate-400">Active Accounts</p>
            <h2 class="text-2xl sm:text-3xl font-black text-emerald-600 dark:text-emerald-400 mt-1">{{ activeUsers }}</h2>
          </div>
          <div class="p-3 rounded-2xl bg-emerald-500/10 text-emerald-600 dark:text-emerald-400">
            <UserCheck class="w-6 h-6" />
          </div>
        </div>

        <!-- Inactive Users -->
        <div class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-800 p-5 shadow-xs flex items-center justify-between">
          <div>
            <p class="text-xs font-bold text-slate-500 dark:text-slate-400">Inactive Accounts</p>
            <h2 class="text-2xl sm:text-3xl font-black text-rose-600 dark:text-rose-400 mt-1">{{ inactiveUsers }}</h2>
          </div>
          <div class="p-3 rounded-2xl bg-rose-500/10 text-rose-600 dark:text-rose-400">
            <UserX class="w-6 h-6" />
          </div>
        </div>
      </div>

      <!-- Table Component With Integrated Toolbar & Filter -->
      <UserTable
        :users="userStore.users || []"
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

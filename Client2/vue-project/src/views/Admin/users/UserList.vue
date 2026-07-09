<script setup lang="ts">
import { onMounted, computed, ref, watch } from 'vue'
import { useRouter } from 'vue-router'

import DashboardLayout from '../../../layouts/DashboardLayout.vue'
import UserTable from '../../../components/user/UserTable.vue'

import { useUserStore } from '../../../stores/user'

const router = useRouter()
const userStore = useUserStore()

const search = ref('')
const currentPage = ref(1)
const perPage = ref(5)

onMounted(async () => {
  await userStore.fetchUsers()
  currentPage.value = 1
})

const filteredUsersBase = computed(() => {
  if (!search.value) return userStore.users

  const keyword = search.value.toLowerCase()

  return userStore.users.filter((user) => {
    return (
      user.first_name.toLowerCase().includes(keyword) ||
      user.last_name.toLowerCase().includes(keyword) ||
      user.email.toLowerCase().includes(keyword)
    )
  })
})

const totalPages = computed(() => Math.ceil(filteredUsersBase.value.length / perPage.value))

const filteredUsers = computed(() => {
  const start = (currentPage.value - 1) * perPage.value
  const end = start + perPage.value

  return filteredUsersBase.value.slice(start, end)
})

watch(search, () => {
  currentPage.value = 1
})

const totalUsers = computed(() => userStore.users.length)

const activeUsers = computed(() => userStore.users.filter((user) => user.is_active).length)

const inactiveUsers = computed(() => userStore.users.filter((user) => !user.is_active).length)

const createUser = () => {
  router.push('/users/create')
}

const goToPage = (page: number) => {
  currentPage.value = page
}
</script>

<template>
  <DashboardLayout>
    <!-- Header -->
    <div class="flex items-center justify-between mb-8">
      <div>
        <h1 class="text-3xl font-bold text-slate-800">User Management</h1>
        <p class="text-gray-500 mt-2">Manage hotel system users.</p>
      </div>

      <button
        @click="createUser"
        class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-3 rounded-lg"
      >
        + Create User
      </button>
    </div>

    <!-- Stats -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
      <div class="bg-white rounded-xl shadow p-6">
        <p class="text-gray-500">Total Users</p>
        <h2 class="text-4xl font-bold mt-3">{{ totalUsers }}</h2>
      </div>

      <div class="bg-white rounded-xl shadow p-6">
        <p class="text-gray-500">Active Users</p>
        <h2 class="text-4xl font-bold text-green-600 mt-3">
          {{ activeUsers }}
        </h2>
      </div>

      <div class="bg-white rounded-xl shadow p-6">
        <p class="text-gray-500">Inactive Users</p>
        <h2 class="text-4xl font-bold text-red-600 mt-3">
          {{ inactiveUsers }}
        </h2>
      </div>
    </div>

    <!-- Search -->
    <div class="bg-white rounded-xl shadow p-6 mb-6">
      <input
        v-model="search"
        type="text"
        placeholder="Search by name or email..."
        class="w-full border rounded-lg px-4 py-3 focus:ring-2 focus:ring-blue-500 outline-none"
      />
    </div>

    <!-- Loading -->
    <div v-if="userStore.loading" class="bg-white rounded-xl shadow p-10 text-center">
      <p class="text-gray-500">Loading users...</p>
    </div>

    <!-- Table -->
    <div v-else class="bg-white rounded-xl shadow">
      <UserTable :users="filteredUsers" />
    </div>

    <!-- Empty -->
    <div v-if="!userStore.loading && filteredUsers.length === 0" class="text-center py-12">
      <h2 class="text-2xl font-semibold text-gray-600">No users found.</h2>
      <p class="text-gray-400 mt-2">Create your first user to get started.</p>
    </div>

    <!-- Pagination -->
    <div
      v-if="totalPages > 1"
      class="flex items-center justify-between mt-6 px-4 py-3 bg-white rounded-xl shadow"
    >
      <p class="text-gray-600">Page {{ currentPage }} of {{ totalPages }}</p>

      <div class="flex gap-2">
        <button
          class="px-4 py-2 border rounded disabled:opacity-50"
          :disabled="currentPage === 1"
          @click="currentPage--"
        >
          Prev
        </button>

        <button
          v-for="page in totalPages"
          :key="page"
          @click="goToPage(page)"
          class="px-4 py-2 border rounded"
          :class="currentPage === page ? 'bg-blue-600 text-white' : ''"
        >
          {{ page }}
        </button>

        <button
          class="px-4 py-2 border rounded disabled:opacity-50"
          :disabled="currentPage === totalPages"
          @click="currentPage++"
        >
          Next
        </button>
      </div>
    </div>
  </DashboardLayout>
</template>

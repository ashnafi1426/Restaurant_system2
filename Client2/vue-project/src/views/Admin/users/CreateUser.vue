<script setup lang="ts">
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import DashboardLayout from '../../../layouts/DashboardLayout.vue'
import UserForm from '../../../components/user/UserForm.vue'

import { useUserStore } from '../../../stores/user'

import type { User } from '../../../types/user'

const router = useRouter()

const userStore = useUserStore()

const successMessage = ref<string>('')

const createUser = async (data: User) => {
  successMessage.value = ''

  try {
    const result = await userStore.createUser(data)

    successMessage.value = 'User created successfully!'

    setTimeout(() => {
      router.push('/users')
    }, 1000)
  } catch (error: any) {}
}
</script>

<template>
  <DashboardLayout>
    <div class="w-full px-4 sm:px-0">
      <!-- Header -->
      <div
        class="flex flex-col sm:flex-row sm:items-center sm:justify-between mb-4 sm:mb-6 gap-3 sm:gap-0"
      >
        <div>
          <h1 class="text-2xl sm:text-3xl font-bold text-slate-800">Create User</h1>
          <p class="text-gray-500 text-sm sm:text-base mt-1">Add a new system user.</p>
        </div>

        <button
          @click="$router.back()"
          class="px-4 sm:px-5 py-2 rounded-lg bg-gray-200 hover:bg-gray-300 text-sm sm:text-base font-medium"
        >
          Cancel
        </button>
      </div>

      <!-- Card -->
      <div class="bg-white rounded-xl shadow overflow-hidden">
        <div class="p-4 sm:p-6 lg:p-8">
          <!-- Success Message -->
          <div
            v-if="successMessage"
            class="mb-6 p-4 bg-green-50 border border-green-200 rounded-lg"
          >
            <p class="text-green-800 font-semibold text-sm sm:text-base">✓ {{ successMessage }}</p>
          </div>

          <!-- Error Summary -->
          <div
            v-if="Object.keys(userStore.errors).length > 0"
            class="mb-6 p-4 bg-red-50 border border-red-200 rounded-lg"
          >
            <h3 class="text-red-800 font-semibold mb-2 text-sm sm:text-base">
              Please fix the following errors:
            </h3>
            <ul class="list-disc list-inside text-red-600 text-xs sm:text-sm space-y-1">
              <li v-for="(messages, field) in userStore.errors" :key="field">
                <strong>{{ field }}:</strong> {{ messages[0] }}
              </li>
            </ul>
          </div>

          <UserForm :loading="userStore.loading" :errors="userStore.errors" @submit="createUser" />
        </div>
      </div>
    </div>
  </DashboardLayout>
</template>

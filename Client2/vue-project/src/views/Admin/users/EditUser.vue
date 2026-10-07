<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import DashboardLayout from '@/Layouts/DashboardLayout.vue'
import UserForm from '../../../components/user/UserForm.vue'
import { useUserStore } from '../../../stores/user'
import type { User } from '../../../types/user'

const route = useRoute()
const router = useRouter()
const userStore = useUserStore()

const id = route.params.id as string
const loadingUser = ref(true)
const successMessage = ref('')
const userData = ref<any>(null)

const hasErrors = computed(() => Object.keys(userStore.errors).length > 0)

const extractUserData = (raw: any) => {
  if (!raw) return null
  return raw.first_name ? raw : raw.data?.first_name ? raw.data : raw.data?.data || raw.data || raw
}

const loadUser = async () => {
  loadingUser.value = true
  try {
    const res = await userStore.fetchUser(id)
    const extracted = extractUserData(res) || extractUserData(userStore.user)

    if (extracted) {
      userData.value = {
        first_name: extracted.first_name || '',
        last_name: extracted.last_name || '',
        email: extracted.email || '',
        phone: extracted.phone || '',
        role: extracted.role || 'receptionist',
        is_active: extracted.is_active ?? true,
      }
    }
  } catch (error) {
    console.error('[EditUser] Failed to load user:', error)
    alert('Failed to load user.')
    router.push('/users')
  } finally {
    loadingUser.value = false
  }
}

const updateUser = async (data: User) => {
  successMessage.value = ''
  try {
    await userStore.updateUser(id, data)
    successMessage.value = 'User updated successfully.'
    setTimeout(() => {
      router.push('/users')
    }, 1000)
  } catch (error: any) {
    console.error('[EditUser] Error updating user:', error)
  }
}

onMounted(loadUser)
</script>

<template>
  <DashboardLayout>
    <div class="max-w-5xl mx-auto px-4 py-6">
      <div class="flex items-center justify-between mb-6">
        <div>
          <h1 class="text-3xl font-bold text-slate-800">Edit User</h1>
          <p class="text-gray-500 mt-1">Update an existing system user.</p>
        </div>

        <button @click="$router.back()" class="px-5 py-2 rounded-lg bg-gray-200 hover:bg-gray-300 font-medium text-sm transition cursor-pointer">
          Cancel
        </button>
      </div>

      <div class="bg-white rounded-xl shadow p-8">
        <div v-if="loadingUser" class="text-center py-12">
          <p class="text-gray-500">Loading user details...</p>
        </div>

        <template v-else>
          <div
            v-if="successMessage"
            class="mb-6 rounded-lg border border-green-200 bg-green-50 p-4"
          >
            <p class="font-semibold text-green-700">✓ {{ successMessage }}</p>
          </div>

          <div
            v-if="hasErrors"
            class="mb-6 rounded-lg border border-red-200 bg-red-50 p-4"
          >
            <h3 class="mb-2 font-semibold text-red-700">Please fix the following errors</h3>
            <ul class="list-disc list-inside text-sm text-red-600">
              <li v-for="(messages, field) in userStore.errors" :key="field">
                <strong class="capitalize">{{ String(field).replace('_', ' ') }}</strong>: {{ messages[0] }}
              </li>
            </ul>
          </div>

          <UserForm
            v-if="userData"
            :initialData="userData"
            :loading="userStore.loading"
            :errors="userStore.errors"
            :is-edit-mode="true"
            @submit="updateUser"
          />
        </template>
      </div>
    </div>
  </DashboardLayout>
</template>

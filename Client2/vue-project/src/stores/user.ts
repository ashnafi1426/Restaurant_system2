import { defineStore } from 'pinia'
import { ref } from 'vue'
import userService from '@/services/userService'
import type { User } from '@/types/user'

export const useUserStore = defineStore('user', () => {
  const users = ref<User[]>([])
  const user = ref<User | null>(null)
  const loading = ref(false)
  const errors = ref<Record<string, string[]>>({})

  async function fetchUsers(params: any = {}, forceRefresh = false) {
    if (users.value.length === 0) {
      loading.value = true
    }

    try {
      const queryParams = { ...params }
      if (forceRefresh) {
        queryParams.refresh = 1
      }
      const response = await userService.getUsers(queryParams)
      users.value = response.data.data || []
      return response.data
    } catch (error) {
      console.error('[UserStore] Error fetching users:', error)
      throw error
    } finally {
      loading.value = false
    }
  }

  async function fetchUser(id: string) {
    loading.value = true
    try {
      const response = await userService.getUser(id)
      user.value = response.data.data
      return response.data
    } catch (error) {
      console.error('[UserStore] Error fetching user:', error)
      throw error
    } finally {
      loading.value = false
    }
  }

  async function createUser(userData: User) {
    loading.value = true
    errors.value = {}

    try {
      const response = await userService.createUser(userData)
      return response.data
    } catch (error: any) {
      console.error('[UserStore] Error creating user:', error)
      if (error.response?.status === 422 && error.response?.data?.errors) {
        errors.value = error.response.data.errors
      }
      throw error
    } finally {
      loading.value = false
    }
  }

  async function updateUser(id: string, userData: User) {
    loading.value = true
    errors.value = {}

    try {
      const payload = { ...userData }
      if (!payload.password) {
        delete payload.password
        delete payload.password_confirmation
      }

      const response = await userService.updateUser(id, payload)
      const updated = response.data.data
      user.value = updated

      const index = users.value.findIndex((u) => u.id === id)
      if (index !== -1) {
        users.value[index] = updated
      }

      return response.data
    } catch (error: any) {
      console.error('[UserStore] Error updating user:', error)
      if (error.response?.status === 422 && error.response?.data?.errors) {
        errors.value = error.response.data.errors
      }
      throw error
    } finally {
      loading.value = false
    }
  }

  async function deleteUser(id: string) {
    loading.value = true
    try {
      await userService.deleteUser(id)
      users.value = users.value.filter((u) => u.id !== id)
    } catch (error) {
      console.error('[UserStore] Error deleting user:', error)
      throw error
    } finally {
      loading.value = false
    }
  }

  async function toggleStatus(id: string) {
    try {
      const response = await userService.toggleStatus(id)
      const updatedUser = response.data.data

      if (user.value?.id === id) {
        user.value = updatedUser
      }

      const index = users.value.findIndex((u) => u.id === id)
      if (index !== -1) {
        users.value[index] = updatedUser
      }

      return updatedUser
    } catch (error) {
      console.error('[UserStore] Error toggling user status:', error)
      throw error
    }
  }

  function clearErrors() {
    errors.value = {}
  }

  function resetUser() {
    user.value = null
  }

  return {
    users,
    user,
    loading,
    errors,

    fetchUsers,
    fetchUser,
    createUser,
    updateUser,
    deleteUser,
    toggleStatus,
    clearErrors,
    resetUser,
  }
})

import { defineStore } from 'pinia'
import userService from '../services/userService'
import type { User } from '../types/user'

export const useUserStore = defineStore('user', {
  state: () => ({
    users: [] as User[],
    user: null as User | null,
    loading: false,
    errors: {} as Record<string, string[]>,
  }),

  actions: {
    async fetchUsers(params: any = {}, forceRefresh = false) {
      // If we already have users loaded, don't show full blocking spinner for background refresh
      if (this.users.length === 0) {
        this.loading = true
      }

      try {
        const queryParams = { ...params }
        if (forceRefresh) {
          queryParams.refresh = 1
        }
        const response = await userService.getUsers(queryParams)
        this.users = response.data.data
        return response.data
      } catch (error) {
        console.error('[UserStore] Error fetching users:', error)
        throw error
      } finally {
        this.loading = false
      }
    },

    async fetchUser(id: string) {
      this.loading = true

      try {
        const response = await userService.getUser(id)
        this.user = response.data.data
        return response.data
      } catch (error) {
        console.error('[UserStore] Error fetching user:', error)
        throw error
      } finally {
        this.loading = false
      }
    },

    async createUser(user: User) {
      this.loading = true
      this.errors = {}

      try {
        const response = await userService.createUser(user)
        return response.data
      } catch (error: any) {
        console.error('[UserStore] Error creating user:', error)
        if (error.response?.status === 422) {
          this.errors = error.response.data.errors
        }
        throw error
      } finally {
        this.loading = false
      }
    },

    async updateUser(id: string, user: User) {
      this.loading = true
      this.errors = {}

      try {
        const userData = { ...user }
        if (!userData.password) {
          delete userData.password
          delete userData.password_confirmation
        }

        const response = await userService.updateUser(id, userData)
        this.user = response.data.data

        const index = this.users.findIndex((u) => u.id === id)
        if (index !== -1) {
          this.users[index] = response.data.data
        }

        return response.data
      } catch (error: any) {
        console.error('[UserStore] Error updating user:', error)
        if (error.response?.status === 422) {
          this.errors = error.response.data.errors
        }
        throw error
      } finally {
        this.loading = false
      }
    },

    async deleteUser(id: string) {
      this.loading = true

      try {
        await userService.deleteUser(id)
        this.users = this.users.filter((user) => user.id !== id)
      } catch (error) {
        console.error('[UserStore] Error deleting user:', error)
        throw error
      } finally {
        this.loading = false
      }
    },

    async toggleStatus(id: string) {
      try {
        const response = await userService.toggleStatus(id)
        const updatedUser = response.data.data

        if (this.user?.id === id) {
          this.user = updatedUser
        }

        const index = this.users.findIndex((user) => user.id === id)
        if (index !== -1) {
          this.users[index] = updatedUser
        }

        return updatedUser
      } catch (error) {
        console.error('[UserStore] Error toggling user status:', error)
        throw error
      }
    },

    clearErrors() {
      this.errors = {}
    },

    resetUser() {
      this.user = null
    },
  },
})

import { defineStore } from 'pinia'
import api from '../api/auth'

export interface UserRoleInfo {
  id: number
  name: string
  slug: string
  is_system: boolean
}

export interface UserState {
  id: string
  first_name: string
  last_name: string
  full_name: string
  email: string
  phone?: string
  role: string
  is_active: boolean
  roles?: UserRoleInfo[]
  permissions?: string[]
  temporary_roles?: any[]
}
export const useAuthStore = defineStore('auth', {
  state: () => ({
    token: localStorage.getItem('token') || '',
    user: JSON.parse(localStorage.getItem('user') || 'null') as UserState | null,
    isInitialized: false,
  }),

  getters: {
    userPermissions(state): string[] {
      if (state.user?.permissions && Array.isArray(state.user.permissions)) {
        return state.user.permissions.map(p => String(p).toLowerCase())
      }
      return []
    },

    userRoles(state): string[] {
      const roles: string[] = []
      if (state.user?.role) {
        roles.push(String(state.user.role).toLowerCase())
      }
      if (state.user?.roles && Array.isArray(state.user.roles)) {
        state.user.roles.forEach(r => {
          const s = String(r.slug || r.name || '').toLowerCase()
          if (s && !roles.includes(s)) {
            roles.push(s)
          }
        })
      }
      return roles
    },

    isAdmin(state): boolean {
      if (!state.user) return false
      const mainRole = state.user.role ? String(state.user.role).toLowerCase() : ''
      if (mainRole === 'admin') return true
      if (state.user.roles && Array.isArray(state.user.roles)) {
        return state.user.roles.some(r => {
          const slug = String(r.slug || r.name || '').toLowerCase()
          return slug === 'admin'
        })
      }
      return false
    },

    isManager(state): boolean {
      if (!state.user) return false
      const mainRole = state.user.role ? String(state.user.role).toLowerCase() : ''
      if (mainRole === 'manager') return true
      if (state.user.roles && Array.isArray(state.user.roles)) {
        return state.user.roles.some(r => {
          const slug = String(r.slug || r.name || '').toLowerCase()
          return slug === 'manager'
        })
      }
      return false
    },

    isReceptionist(state): boolean {
      if (!state.user) return false
      const mainRole = state.user.role ? String(state.user.role).toLowerCase() : ''
      if (mainRole === 'receptionist') return true
      if (state.user.roles && Array.isArray(state.user.roles)) {
        return state.user.roles.some(r => {
          const slug = String(r.slug || r.name || '').toLowerCase()
          return slug === 'receptionist'
        })
      }
      return false
    },

    isCashier(state): boolean {
      if (!state.user) return false
      const mainRole = state.user.role ? String(state.user.role).toLowerCase() : ''
      if (mainRole === 'cashier') return true
      if (state.user.roles && Array.isArray(state.user.roles)) {
        return state.user.roles.some(r => {
          const slug = String(r.slug || r.name || '').toLowerCase()
          return slug === 'cashier'
        })
      }
      return false
    },

    isWaiter(state): boolean {
      if (!state.user) return false
      const mainRole = state.user.role ? String(state.user.role).toLowerCase() : ''
      if (mainRole === 'waiter') return true
      if (state.user.roles && Array.isArray(state.user.roles)) {
        return state.user.roles.some(r => {
          const slug = String(r.slug || r.name || '').toLowerCase()
          return slug === 'waiter'
        })
      }
      return false
    },

    isChef(state): boolean {
      if (!state.user) return false
      const mainRole = state.user.role ? String(state.user.role).toLowerCase() : ''
      if (mainRole === 'chef') return true
      if (state.user.roles && Array.isArray(state.user.roles)) {
        return state.user.roles.some(r => {
          const slug = String(r.slug || r.name || '').toLowerCase()
          return slug === 'chef'
        })
      }
      return false
    }
  },

  actions: {
    can(permissionSlug: string): boolean {
      if (!this.user) return false

      const target = String(permissionSlug).toLowerCase().trim()

      // 1. Admin superuser access
      if (this.isAdmin) return true

      // 2. Dashboard access (every authenticated user can view their role dashboard)
      if (target === 'dashboard.view' || target === 'dashboard') return true

      // 3. Dynamic match in effective user permissions granted by Database
      if (this.userPermissions.includes(target)) {
        return true
      }

      // 4. Role domain fallback for standard system roles
      const role = String(this.user.role || '').toLowerCase().trim()
      // if (role === 'waiter' && (target.startsWith('delivery.') || target.startsWith('orders.') || target.startsWith('notifications.'))) {
      //   return true
      // }
      // if (role === 'chef' && (target.startsWith('kitchen.') || target.startsWith('orders.') || target.startsWith('notifications.'))) {
      //   return true
      // }
      // if (role === 'receptionist' && (target.startsWith('reservations.') || target.startsWith('guests.') || target.startsWith('rooms.') || target.startsWith('checkin.') || target.startsWith('checkout.') || target.startsWith('notifications.'))) {
      //   return true
      // }
      // if (role === 'cashier' && (target.startsWith('payments.') || target.startsWith('orders.') || target.startsWith('reports.') || target.startsWith('notifications.'))) {
      //   return true
      // }
      // if (role === 'manager') {
      //   return true
      // }

      // 5. Exact synonym checks for legacy permissions
      if (target === 'checkin.view') {
        return this.userPermissions.includes('checkin.view') || this.userPermissions.includes('reservations.checkin')
      }

      if (target === 'checkout.view') {
        return this.userPermissions.includes('checkout.view') || this.userPermissions.includes('reservations.checkout')
      }

      if (target === 'kitchen.view' || target === 'kitchen.accept') {
        return this.userPermissions.includes('kitchen.view') ||
          this.userPermissions.includes('kitchen.accept') ||
          this.userPermissions.includes('kitchen.prepare') ||
          this.userPermissions.includes('orders.view')
      }

      return false
    },

    hasPermission(permissionSlug: string): boolean {
      return this.can(permissionSlug)
    },

    canAny(permissionSlugs: string[]): boolean {
      if (!this.user) return false
      return permissionSlugs.some(slug => this.can(slug))
    },

    hasAnyPermission(permissionSlugs: string[]): boolean {
      return this.canAny(permissionSlugs)
    },

    canAll(permissionSlugs: string[]): boolean {
      if (!this.user) return false
      return permissionSlugs.every(slug => this.can(slug))
    },

    hasAllPermissions(permissionSlugs: string[]): boolean {
      return this.canAll(permissionSlugs)
    },

    hasRole(roleSlug: string): boolean {
      if (!this.user) return false
      if (this.isAdmin) return true // Admin possesses all role access

      const target = String(roleSlug).toLowerCase()
      return this.userRoles.includes(target)
    },

    async login(email: string, password: string) {
      try {
        const response = await api.post('/login', {
          email,
          password,
        })
        if (response.data.token && response.data.user) {
          this.token = response.data.token
          this.user = response.data.user
          this.isInitialized = true
          localStorage.setItem('token', response.data.token)
          localStorage.setItem('user', JSON.stringify(response.data.user))
          return { success: true, user: response.data.user }
        } else {
          throw new Error('Invalid response from server')
        }
      } catch (error: any) {
        console.error('[AUTH] Login error:', error)

        let errorMessage = 'Login failed. Please try again.'

        if (error.code === 'ECONNABORTED') {
          errorMessage = 'Connection timeout. Server may be down.'
        } else if (error.response?.status === 401) {
          errorMessage = error.response?.data?.message || 'Invalid email or password'
        } else if (error.response?.status === 403) {
          if (error.response?.data?.needs_activation) {
            errorMessage = 'Account not activated. Please check your email for the activation link.'
          } else {
            errorMessage = error.response?.data?.message || 'Account is disabled'
          }
        } else if (error.response?.status === 422) {
          errorMessage = 'Invalid input. Check email and password.'
        } else if (!error.response) {
          errorMessage = 'Cannot connect to server. Check if it is running.'
        }

        throw new Error(errorMessage)
      }
    },

    async fetchCurrentUser() {
      if (!this.token) return
      try {
        const response = await api.get('/me')
        if (response.data.user) {
          this.user = response.data.user
          this.isInitialized = true
          localStorage.setItem('user', JSON.stringify(response.data.user))
        }
      } catch (error) {
        console.error('[AUTH] Failed to refresh current user permissions:', error)
      }
    },

    async initializeAuth(forceRefresh = false) {
      if (!this.token) return
      if (!forceRefresh && this.isInitialized) {
        return
      }

      await this.fetchCurrentUser()
    },

    logout() {
      this.token = ''
      this.user = null
      this.isInitialized = false

      localStorage.removeItem('token')
      localStorage.removeItem('user')
    },
  },
})

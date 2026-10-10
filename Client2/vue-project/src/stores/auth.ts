import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import api from '@/api/auth'
import type { User, LoginResponse } from '@/types/auth'

export interface RoleInfo {
  id?: string | number
  name?: string
  slug?: string
  is_system?: boolean
}

export interface CurrentHotelContext {
  id: string
  name: string
  slug: string
  logo?: string | null
  currency?: string
  role?: string
}

export interface AuthState {
  token: string | null
  user: any | null
  currentHotel: CurrentHotelContext | null
  isInitialized: boolean
}

const loadStorage = <T>(key: string, fallback: T): T => {
  try {
    const raw = localStorage.getItem(key)
    return raw ? JSON.parse(raw) : fallback
  } catch {
    return fallback
  }
}

export const useAuthStore = defineStore('auth', () => {
  const token = ref<string | null>(localStorage.getItem('token'))
  const user = ref<any>(loadStorage<any>('user', null))
  const currentHotel = ref<CurrentHotelContext | null>(
    loadStorage<CurrentHotelContext | null>('current_hotel', null),
  )
  const isInitialized = ref<boolean>(false)

  const isAuthenticated = computed<boolean>(() => Boolean(token.value && user.value))
  const mustChangePassword = computed<boolean>(() => Boolean(user.value?.must_change_password))

  const isPlatformAdmin = computed<boolean>(() => {
    if (!user.value) return false
    return Boolean(
      user.value.is_platform_admin === true ||
      user.value.is_platform_admin === 1 ||
      user.value.email?.toLowerCase() === 'admin@hotel.com',
    )
  })

  const userRoles = computed<string[]>(() => {
    const rolesSet = new Set<string>()

    if (currentHotel.value?.role) {
      rolesSet.add(String(currentHotel.value.role).toLowerCase().trim())
    }

    if (user.value?.roles && Array.isArray(user.value.roles)) {
      user.value.roles.forEach((r: any) => {
        const slug = String(r?.slug || r?.name || '')
          .toLowerCase()
          .trim()
        if (slug) rolesSet.add(slug)
      })
    }

    if (user.value?.role) {
      rolesSet.add(String(user.value.role).toLowerCase().trim())
    }

    return Array.from(rolesSet)
  })

  const currentRole = computed<string>(() => {
    if (isPlatformAdmin.value) return 'admin'
    if (currentHotel.value?.role) return String(currentHotel.value.role).toLowerCase().trim()
    if (userRoles.value.length > 0) return userRoles.value[0]
    return String(user.value?.role || 'guest')
      .toLowerCase()
      .trim()
  })

  const userPermissions = computed<string[]>(() => {
    const permsSet = new Set<string>()

    if (user.value?.permissions && Array.isArray(user.value.permissions)) {
      user.value.permissions.forEach((p: any) => {
        const slug = typeof p === 'string' ? p : p?.slug || p?.name
        if (slug) permsSet.add(String(slug).toLowerCase().trim())
      })
    }
    if (user.value?.roles && Array.isArray(user.value.roles)) {
      user.value.roles.forEach((role: any) => {
        if (role.permissions && Array.isArray(role.permissions)) {
          role.permissions.forEach((p: any) => {
            const slug = typeof p === 'string' ? p : p?.slug || p?.name
            if (slug) permsSet.add(String(slug).toLowerCase().trim())
          })
        }
      })
    }

    return Array.from(permsSet)
  })

  const can = (permissionSlug: string): boolean => {
    if (!permissionSlug) return true
    if (isPlatformAdmin.value || hasRole('admin')) return true

    const target = permissionSlug.toLowerCase().trim()
    if (userPermissions.value.includes(target)) return true

    const prefix = target.split('.')[0]
    if (prefix && userPermissions.value.includes(`${prefix}.*`)) {
      return true
    }

    return false
  }

  const hasPermission = can

  const canAny = (permissionSlugs: string[]): boolean => {
    if (!permissionSlugs || permissionSlugs.length === 0) return true
    if (isPlatformAdmin.value) return true
    return permissionSlugs.some((perm) => can(perm))
  }

  const hasAnyPermission = canAny

  const canAll = (permissionSlugs: string[]): boolean => {
    if (!permissionSlugs || permissionSlugs.length === 0) return true
    if (isPlatformAdmin.value) return true
    return permissionSlugs.every((perm) => can(perm))
  }

  const hasAllPermissions = canAll

  const hasRole = (roleSlug: string): boolean => {
    if (!roleSlug) return false
    if (isPlatformAdmin.value) return true
    const target = roleSlug.toLowerCase().trim()
    return userRoles.value.includes(target)
  }

  const hasAnyRole = (roleSlugs: string[]): boolean => {
    if (!roleSlugs || roleSlugs.length === 0) return true
    if (isPlatformAdmin.value) return true
    return roleSlugs.some((r) => hasRole(r))
  }

  const hasAllRoles = (roleSlugs: string[]): boolean => {
    if (!roleSlugs || roleSlugs.length === 0) return true
    if (isPlatformAdmin.value) return true
    return roleSlugs.every((r) => hasRole(r))
  }

  const setToken = (newToken: string | null) => {
    token.value = newToken
    if (newToken) {
      localStorage.setItem('token', newToken)
    } else {
      localStorage.removeItem('token')
    }
  }

  const setUser = (newUser: any | null) => {
    user.value = newUser
    if (newUser) {
      localStorage.setItem('user', JSON.stringify(newUser))
    } else {
      localStorage.removeItem('user')
    }
  }

  const setCurrentHotel = (hotel: CurrentHotelContext | null) => {
    currentHotel.value = hotel
    if (hotel) {
      localStorage.setItem('current_hotel', JSON.stringify(hotel))
    } else {
      localStorage.removeItem('current_hotel')
    }
  }

  const login = async (email: string, password: string): Promise<any> => {
    const response = await api.post('/auth/login', { email, password })
    const data = response.data

    if (data?.token) {
      setToken(data.token)
    }

    if (data?.user) {
      setUser(data.user)
    }

    if (data?.current_hotel) {
      setCurrentHotel(data.current_hotel)
    }

    if (data?.hotels && Array.isArray(data.hotels)) {
      localStorage.setItem('available_hotels', JSON.stringify(data.hotels))
    }

    try {
      const { useHotelStore } = await import('./hotelStore')
      const hotelStore = useHotelStore()
      hotelStore.setHotels(data?.hotels || [], data?.current_hotel || null)
    } catch (err: any) {
      console.error('[AuthStore] Error syncing hotelStore:', err)
    }

    isInitialized.value = true
    return data
  }

  const logout = async (): Promise<void> => {
    try {
      // Set a flag to indicate logout is in progress
      isInitialized.value = false

      // Capture current token before clearing local state
      const currentToken = token.value || localStorage.getItem('token')

      // Clear auth data immediately for instant UI response
      setToken(null)
      setUser(null)
      setCurrentHotel(null)

      // Stop notification polling on logout
      try {
        const { useNotificationStore } = await import('./notificationStore')
        const notificationStore = useNotificationStore()
        notificationStore.stopPolling()
      } catch (err: any) {
        console.error('[AuthStore] Error stopping notification polling during logout:', err)
      }

      // Disconnect WebSocket if active
      if (typeof window !== 'undefined' && (window as any).Echo?.disconnect) {
        try {
          ;(window as any).Echo.disconnect()
        } catch {
          // Ignore echo disconnect errors
        }
      }

      // Make logout API call in background with the captured token
      if (currentToken) {
        api
          .post('/logout', null, {
            headers: {
              Authorization: `Bearer ${currentToken}`,
            },
          })
          .catch((err: any) => {
            // If status is 401, the token was already expired or revoked on the server; ignore silently
            if (err?.response?.status !== 401) {
              console.warn('[AuthStore] Logout API call warning:', err?.message || err)
            }
          })
      }
    } catch (err: any) {
      console.error('[AuthStore] Error during logout:', err)
    }
  }
  const fetchCurrentUser = async (): Promise<any> => {
    try {
      const response = await api.get('/auth/me')
      if (response.data?.user) {
        setUser(response.data.user)
      }
      return response.data
    } catch (err) {
      console.error('[AuthStore] Failed to fetch current user:', err)
      throw err
    }
  }
  const initializeAuth = async (): Promise<void> => {
    const storedToken = localStorage.getItem('token')
    if (storedToken) {
      token.value = storedToken
      try {
        await fetchCurrentUser()
      } catch (err) {
        console.error('[AuthStore] Failed to initialize current user session:', err)
        logout()
      }
    }
    isInitialized.value = true
  }

  return {
    token,
    user,
    currentHotel,
    isInitialized,

    isAuthenticated,
    mustChangePassword,
    isPlatformAdmin,
    userRoles,
    currentRole,
    userPermissions,

    can,
    hasPermission,
    canAny,
    hasAnyPermission,
    canAll,
    hasAllPermissions,
    hasRole,
    hasAnyRole,
    hasAllRoles,

    setToken,
    setUser,
    setCurrentHotel,
    login,
    logout,
    fetchCurrentUser,
    initializeAuth,
  }
})

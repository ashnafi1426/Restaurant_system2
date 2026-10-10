import { defineStore } from 'pinia'
import { axiosInstance } from '@/services/axios'

export interface HotelItem {
  id: string
  name: string
  slug: string
  logo?: string
  role?: string
  currency?: string
  is_viewing_as_platform_admin?: boolean
}

const loadStorage = <T>(key: string, fallback: T): T => {
  try {
    const raw = localStorage.getItem(key)
    return raw ? JSON.parse(raw) : fallback
  } catch {
    return fallback
  }
}

export const useHotelStore = defineStore('hotel', {
  state: () => ({
    currentHotel: loadStorage<HotelItem | null>('current_hotel', null),
    availableHotels: loadStorage<HotelItem[]>('available_hotels', []),
    isLoading: false,
  }),

  getters: {
    hotelId: (state): string => state.currentHotel?.id || '',
    hotelName: (state): string => state.currentHotel?.name || 'Hotel Management',
    currency: (state): string => state.currentHotel?.currency || 'ETB',
    hasMultipleHotels: (state): boolean => state.availableHotels.length > 1,
    isViewingAsPlatformAdmin: (state): boolean =>
      Boolean(state.currentHotel?.is_viewing_as_platform_admin),
  },

  actions: {
    enterPlatformViewMode(hotel: HotelItem) {
      this.currentHotel = { ...hotel, is_viewing_as_platform_admin: true }
      localStorage.setItem('current_hotel', JSON.stringify(this.currentHotel))
    },

    exitPlatformViewMode() {
      if (this.currentHotel) {
        delete this.currentHotel.is_viewing_as_platform_admin
        localStorage.setItem('current_hotel', JSON.stringify(this.currentHotel))
      }
    },

    setHotels(hotels: HotelItem[], current: HotelItem | null) {
      this.availableHotels = hotels || []
      this.currentHotel = current || (hotels.length > 0 ? hotels[0] : null)

      localStorage.setItem('available_hotels', JSON.stringify(this.availableHotels))
      if (this.currentHotel) {
        localStorage.setItem('current_hotel', JSON.stringify(this.currentHotel))
      } else {
        localStorage.removeItem('current_hotel')
      }
    },

    async switchHotel(hotelId: string): Promise<boolean> {
      if (this.currentHotel?.id === hotelId) return true

      this.isLoading = true
      try {
        const response = await axiosInstance.post('/auth/switch-hotel', { hotel_id: hotelId })
        if (response.data?.success && response.data?.current_hotel) {
          const hotelData = response.data.current_hotel
          this.currentHotel = hotelData
          localStorage.setItem('current_hotel', JSON.stringify(hotelData))

          try {
            const { useAuthStore } = await import('./auth')
            const authStore = useAuthStore()

            authStore.setCurrentHotel(hotelData)

            if (response.data?.user) {
              authStore.setUser(response.data.user)
            } else {
              const existingUser = authStore.user || {}
              const effectiveRole =
                hotelData.role || response.data.roles?.[0]?.slug || existingUser.role
              const updatedUser = {
                ...existingUser,
                role: effectiveRole,
                roles: response.data.roles || existingUser.roles || [],
                permissions: response.data.permissions || existingUser.permissions || [],
              }
              authStore.setUser(updatedUser)
            }

            try {
              await authStore.fetchCurrentUser()
            } catch (err: any) {
              console.error('[hotelStore] Failed to fetch current user after hotel switch:', err)
            }

            window.dispatchEvent(
              new CustomEvent('hotel-switched', {
                detail: { hotelId, role: hotelData.role },
              }),
            )
          } catch (err: any) {
            console.error('[hotelStore] Failed to sync auth state during hotel switch:', err)
          }

          return true
        }
        return false
      } catch (err: any) {
        console.error('[hotelStore] Failed to switch hotel:', err)
        return false
      } finally {
        this.isLoading = false
      }
    },

    async fetchMyHotels() {
      try {
        const response = await axiosInstance.get('/auth/my-hotels')
        if (response.data?.success && Array.isArray(response.data?.hotels)) {
          this.availableHotels = response.data.hotels
          localStorage.setItem('available_hotels', JSON.stringify(this.availableHotels))

          if (!this.currentHotel && this.availableHotels.length > 0) {
            this.currentHotel = this.availableHotels[0]
            localStorage.setItem('current_hotel', JSON.stringify(this.currentHotel))
          }
        }
      } catch (err: any) {
        console.error('[hotelStore] Failed to fetch my hotels:', err)
      }
    },

    clearHotel() {
      this.currentHotel = null
      this.availableHotels = []
      localStorage.removeItem('current_hotel')
      localStorage.removeItem('available_hotels')
    },

    async loadHotels() {
      return this.fetchMyHotels()
    },
  },
})

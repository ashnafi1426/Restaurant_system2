import { defineStore } from 'pinia'
import { axiosInstance } from '../services/axios'

export interface HotelItem {
  id: string
  name: string
  slug: string
  logo?: string
  role?: string
  currency?: string
  is_viewing_as_platform_admin?: boolean
}

export const useHotelStore = defineStore('hotel', {
  state: () => ({
    currentHotel: JSON.parse(localStorage.getItem('current_hotel') || 'null') as HotelItem | null,
    availableHotels: JSON.parse(localStorage.getItem('available_hotels') || '[]') as HotelItem[],
    isLoading: false,
  }),

  getters: {
    hotelId: (state): string => state.currentHotel?.id || '',
    hotelName: (state): string => state.currentHotel?.name || 'Hotel Management',
    currency: (state): string => state.currentHotel?.currency || 'ETB',
    hasMultipleHotels: (state): boolean => state.availableHotels.length > 1,
    isViewingAsPlatformAdmin: (state): boolean => Boolean(state.currentHotel?.is_viewing_as_platform_admin),
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
          this.currentHotel = response.data.current_hotel
          localStorage.setItem('current_hotel', JSON.stringify(this.currentHotel))

          // Update authStore user roles and permissions with new hotel context
          try {
            const { useAuthStore } = await import('./auth')
            const authStore = useAuthStore()
            if (authStore.user) {
              if (response.data.permissions) {
                authStore.user.permissions = response.data.permissions
              }
              if (response.data.roles) {
                authStore.user.roles = response.data.roles
              }
              if (response.data.current_hotel?.role) {
                authStore.user.role = response.data.current_hotel.role
              }
              localStorage.setItem('user', JSON.stringify(authStore.user))
            }
          } catch (e) {
            console.warn('[HOTEL] Failed to sync authStore permissions on switch:', e)
          }

          return true
        }
        return false
      } catch (error) {
        console.error('Failed to switch hotel:', error)
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
      } catch (error) {
        console.error('Failed to fetch hotels:', error)
      }
    },

    clearHotel() {
      this.currentHotel = null
      this.availableHotels = []
      localStorage.removeItem('current_hotel')
      localStorage.removeItem('available_hotels')
    },
  },
})

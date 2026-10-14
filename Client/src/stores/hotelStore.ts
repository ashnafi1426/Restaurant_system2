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

const loadInitialHotelItem = (): HotelItem | null => {
  for (const key of ['current_hotel', 'guest_current_hotel']) {
    try {
      const raw = localStorage.getItem(key)
      if (raw) {
        const parsed = JSON.parse(raw)
        if (parsed && (parsed.id || parsed.name)) {
          return {
            id: String(parsed.id || ''),
            name: parsed.name || 'Grand Horizon Hotel',
            slug: parsed.slug || '',
            logo: parsed.logo || undefined,
            role: parsed.role || 'admin',
            currency: parsed.currency || 'ETB',
            is_viewing_as_platform_admin: parsed.is_viewing_as_platform_admin,
          }
        }
      }
    } catch (e) {
      console.error(`[hotelStore] Error reading initial hotel from ${key}:`, e)
    }
  }
  return null
}

const persistHotelContext = (hotel: HotelItem | null) => {
  if (hotel && hotel.id) {
    const json = JSON.stringify(hotel)
    localStorage.setItem('current_hotel', json)
    localStorage.setItem('guest_current_hotel', json)
    localStorage.setItem('hotel_id', String(hotel.id))
    localStorage.setItem('guest_hotel_id', String(hotel.id))
    localStorage.setItem('active_hotel_id', String(hotel.id))
  } else {
    localStorage.removeItem('current_hotel')
    localStorage.removeItem('guest_current_hotel')
    localStorage.removeItem('hotel_id')
    localStorage.removeItem('guest_hotel_id')
    localStorage.removeItem('active_hotel_id')
  }
}

export const useHotelStore = defineStore('hotel', {
  state: () => ({
    currentHotel: loadInitialHotelItem(),
    availableHotels: loadStorage<HotelItem[]>('available_hotels', []),
    isLoading: false,
  }),

  getters: {
    hotelId: (state): string => state.currentHotel?.id || '',
    hotelName: (state): string => state.currentHotel?.name || 'Grand Horizon Hotel',
    currency: (state): string => state.currentHotel?.currency || 'ETB',
    hasMultipleHotels: (state): boolean => state.availableHotels.length > 1,
    isViewingAsPlatformAdmin: (state): boolean =>
      Boolean(state.currentHotel?.is_viewing_as_platform_admin),
  },

  actions: {
    enterPlatformViewMode(hotel: HotelItem) {
      this.currentHotel = { ...hotel, is_viewing_as_platform_admin: true }
      persistHotelContext(this.currentHotel)
    },

    exitPlatformViewMode() {
      if (this.currentHotel) {
        delete this.currentHotel.is_viewing_as_platform_admin
        persistHotelContext(this.currentHotel)
      }
    },

    setHotels(hotels: HotelItem[], current: HotelItem | null) {
      this.availableHotels = hotels || []
      this.currentHotel = current || (hotels.length > 0 ? hotels[0] : null)

      localStorage.setItem('available_hotels', JSON.stringify(this.availableHotels))
      if (this.currentHotel) {
        persistHotelContext(this.currentHotel)
        import('./theme')
          .then(({ useThemeStore }) => {
            useThemeStore().syncWithHotel(this.currentHotel)
          })
          .catch(() => {})
      } else {
        persistHotelContext(null)
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
          persistHotelContext(hotelData)

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

            try {
              const { useThemeStore } = await import('./theme')
              const themeStore = useThemeStore()
              themeStore.syncWithHotel(hotelData)
            } catch (themeErr) {
              console.warn('[hotelStore] Theme sync error on switch:', themeErr)
            }

            try {
              const { useGuestHotelStore } = await import('./guestHotelStore')
              const guestHotelStore = useGuestHotelStore()
              guestHotelStore.currentHotel = {
                ...guestHotelStore.currentHotel,
                ...hotelData,
                id: String(hotelData.id),
                name: hotelData.name,
                slug: hotelData.slug,
                logo: hotelData.logo || null,
                currency: hotelData.currency || 'ETB',
                status: 'active',
              }
            } catch (guestErr) {
              console.warn('[hotelStore] Guest store sync error on switch:', guestErr)
            }

            window.dispatchEvent(
              new CustomEvent('hotel-switched', {
                detail: { hotelId, role: hotelData.role, hotel: hotelData },
              }),
            )
            window.dispatchEvent(
              new CustomEvent('guest-hotel-selected', {
                detail: { hotel: hotelData },
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

          const savedId =
            this.currentHotel?.id ||
            localStorage.getItem('hotel_id') ||
            localStorage.getItem('guest_hotel_id') ||
            localStorage.getItem('active_hotel_id')

          if (savedId) {
            const matched = this.availableHotels.find(
              (h) =>
                String(h.id).trim().toLowerCase() === String(savedId).trim().toLowerCase() ||
                (h.slug &&
                  String(h.slug).trim().toLowerCase() === String(savedId).trim().toLowerCase()),
            )
            if (matched) {
              this.currentHotel = matched
              persistHotelContext(this.currentHotel)
              return
            }
          }

          if (!this.currentHotel && this.availableHotels.length > 0) {
            this.currentHotel = this.availableHotels[0]
            persistHotelContext(this.currentHotel)
          }
        }
      } catch (err: any) {
        console.error('[hotelStore] Failed to fetch my hotels:', err)
      }
    },

    clearHotel() {
      this.currentHotel = null
      this.availableHotels = []
      persistHotelContext(null)
      localStorage.removeItem('available_hotels')
      import('./theme')
        .then(({ useThemeStore }) => {
          useThemeStore().setHotelTheme('luxury-gold')
        })
        .catch(() => {})
    },

    async loadHotels() {
      return this.fetchMyHotels()
    },
  },
})

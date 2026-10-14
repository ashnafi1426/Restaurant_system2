import { defineStore } from 'pinia'
import { publicAxios } from '@/services/axios'
import { useThemeStore } from '@/stores/theme'

export interface PublicHotel {
  id: string
  name: string
  slug: string
  logo: string | null
  description?: string | null
  address: string | null
  city?: string | null
  country?: string | null
  phone: string | null
  email: string | null
  reservation_email?: string | null
  reservation_phone?: string | null
  front_desk_hours?: string | null
  dining_hours?: string | null
  currency: string
  status: string
}

function loadInitialHotel(): PublicHotel | null {
  try {
    const raw = localStorage.getItem('guest_current_hotel') || localStorage.getItem('current_hotel')
    if (raw) {
      const parsed = JSON.parse(raw)
      if (parsed && (parsed.id || parsed.name)) {
        return parsed
      }
    }
  } catch (e) {
    console.error('[guestHotelStore] Failed to parse guest_current_hotel from localStorage:', e)
  }
  return null
}

const isSameId = (a: any, b: any): boolean => {
  if (a == null || b == null) return false
  const strA = String(a).trim().toLowerCase()
  const strB = String(b).trim().toLowerCase()
  return Boolean(strA && strB && strA === strB)
}

export const useGuestHotelStore = defineStore('guestHotel', {
  state: () => ({
    currentHotel: loadInitialHotel(),
    availableHotels: [] as PublicHotel[],
    isLoading: false,
    error: null as string | null,
    isSelectorOpen: false,
  }),

  getters: {
    hotelId: (state): string => state.currentHotel?.id || '',
    hotelName: (state): string => state.currentHotel?.name || 'Grand Horizon Hotel',
    hotelCity: (state): string => state.currentHotel?.city || 'Addis Ababa',
    hotelCountry: (state): string => state.currentHotel?.country || 'Ethiopia',
    hotelAddress: (state): string => {
      if (state.currentHotel?.address) return state.currentHotel.address
      const city = state.currentHotel?.city || 'Addis Ababa'
      const country = state.currentHotel?.country || 'Ethiopia'
      return `${city}, ${country}`
    },
    hotelPhone: (state): string => state.currentHotel?.phone || '+251 11 555 1234',
    reservationPhone: (state): string =>
      state.currentHotel?.reservation_phone || state.currentHotel?.phone || '+251 91 100 2200',
    hotelEmail: (state): string => state.currentHotel?.email || 'concierge@grandhorizon.com',
    reservationEmail: (state): string =>
      state.currentHotel?.reservation_email ||
      state.currentHotel?.email ||
      `reservations@${state.currentHotel?.slug || 'hotel'}.com`,
    frontDeskHours: (state): string =>
      state.currentHotel?.front_desk_hours || 'Open 24/7 / 365 Days',
    diningHours: (state): string => state.currentHotel?.dining_hours || '06:00 AM – 11:00 PM Daily',
    currency: (state): string => state.currentHotel?.currency || 'ETB',
    hasSelectedHotel: (state): boolean =>
      Boolean(state.currentHotel && (state.currentHotel.id || state.currentHotel.name)),
  },

  actions: {
    async fetchAvailableHotels() {
      this.isLoading = true
      this.error = null
      try {
        const response = await publicAxios.get('/guest/hotels')
        if (response.data?.success && Array.isArray(response.data?.data)) {
          this.availableHotels = response.data.data

          // Persist user selection across page refresh
          const savedId =
            this.currentHotel?.id ||
            localStorage.getItem('guest_hotel_id') ||
            localStorage.getItem('hotel_id') ||
            localStorage.getItem('active_hotel_id')

          const savedSlug = this.currentHotel?.slug || localStorage.getItem('guest_hotel_slug')
          const savedName = this.currentHotel?.name

          if (savedId || savedSlug || savedName) {
            const matched = this.availableHotels.find((h) => {
              return (
                isSameId(h.id, savedId) ||
                isSameId(h.slug, savedSlug) ||
                isSameId(h.slug, savedId) ||
                (savedName &&
                  h.name &&
                  h.name.trim().toLowerCase() === savedName.trim().toLowerCase())
              )
            })

            if (matched) {
              this.selectHotel(matched)
              return
            }
          }

          // If currentHotel is already loaded and valid from storage, keep it
          if (this.currentHotel && (this.currentHotel.id || this.currentHotel.name)) {
            return
          }

          // Fallback to first available hotel
          if (this.availableHotels.length > 0) {
            this.selectHotel(this.availableHotels[0])
          }
        }
      } catch (error: any) {
        console.error('[guestHotelStore] Failed to load hotels:', error)
        this.error = error.response?.data?.message || 'Failed to load hotels'
      } finally {
        this.isLoading = false
      }
    },

    openHotelSelector() {
      this.isSelectorOpen = true
      if (this.availableHotels.length === 0) {
        this.fetchAvailableHotels()
      }
    },

    closeHotelSelector() {
      if (this.hasSelectedHotel) {
        this.isSelectorOpen = false
      }
    },

    async selectHotel(hotel: PublicHotel) {
      this.currentHotel = hotel
      this.isSelectorOpen = false
      const hotelJson = JSON.stringify(hotel)
      localStorage.setItem('guest_current_hotel', hotelJson)
      localStorage.setItem('current_hotel', hotelJson)
      localStorage.setItem('guest_hotel_id', String(hotel.id))
      localStorage.setItem('hotel_id', String(hotel.id))
      localStorage.setItem('active_hotel_id', String(hotel.id))
      if (hotel.slug) {
        localStorage.setItem('guest_hotel_slug', String(hotel.slug))
      }

      try {
        const themeStore = useThemeStore()
        themeStore.syncWithHotel(hotel)
      } catch (e) {
        console.warn('[guestHotelStore] Could not auto-sync theme:', e)
      }

      // Synchronize dashboard hotelStore so sidebar has the exact same hotel
      try {
        const { useHotelStore } = await import('./hotelStore')
        const hotelStore = useHotelStore()
        hotelStore.currentHotel = {
          id: String(hotel.id),
          name: hotel.name,
          slug: hotel.slug,
          logo: hotel.logo || undefined,
          currency: hotel.currency,
          role: hotelStore.currentHotel?.role || 'admin',
        }
      } catch (e) {
        console.warn('[guestHotelStore] Could not sync hotelStore:', e)
      }

      // Synchronize authStore if user is logged in
      try {
        const { useAuthStore } = await import('./auth')
        const authStore = useAuthStore()
        if (authStore.currentHotel) {
          authStore.currentHotel = {
            ...authStore.currentHotel,
            id: String(hotel.id),
            name: hotel.name,
            slug: hotel.slug,
            logo: hotel.logo,
            currency: hotel.currency,
          }
        }
      } catch (e) {
        console.warn('[guestHotelStore] Could not sync authStore:', e)
      }

      window.dispatchEvent(new CustomEvent('guest-hotel-selected', { detail: { hotel } }))
      window.dispatchEvent(
        new CustomEvent('hotel-switched', { detail: { hotelId: String(hotel.id), hotel } }),
      )
    },

    async selectHotelById(hotelId: string) {
      if (!hotelId) return
      if (this.currentHotel?.id === hotelId) return

      if (this.availableHotels.length === 0) {
        await this.fetchAvailableHotels()
      }

      const matched = this.availableHotels.find(
        (h) => isSameId(h.id, hotelId) || isSameId(h.slug, hotelId),
      )
      if (matched) {
        this.selectHotel(matched)
        return
      }

      try {
        const response = await publicAxios.get(`/guest/hotels/${hotelId}`)
        if (response.data?.success && response.data?.data) {
          this.selectHotel(response.data.data)
        }
      } catch (e) {
        console.warn('[guestHotelStore] Failed to fetch hotel by ID:', hotelId, e)
      }
    },

    clearSelectedHotel() {
      this.currentHotel = null
      localStorage.removeItem('guest_current_hotel')
      localStorage.removeItem('current_hotel')
      localStorage.removeItem('guest_hotel_id')
      localStorage.removeItem('hotel_id')
      localStorage.removeItem('active_hotel_id')
      localStorage.removeItem('guest_hotel_slug')
      this.openHotelSelector()
    },
  },
})

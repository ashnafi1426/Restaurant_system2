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
  currency: string
  status: string
}

function loadInitialHotel(): PublicHotel | null {
  try {
    const raw = localStorage.getItem('guest_current_hotel')
    return raw ? JSON.parse(raw) : null
  } catch (e) {
    console.error('[guestHotelStore] Failed to parse guest_current_hotel from localStorage:', e)
    return null
  }
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
    hotelAddress: (state): string =>
      state.currentHotel?.address || 'Bolé Road, Addis Ababa, Ethiopia',
    hotelPhone: (state): string => state.currentHotel?.phone || '+251 11 555 1234',
    hotelEmail: (state): string => state.currentHotel?.email || 'concierge@grandhorizon.com',
    currency: (state): string => state.currentHotel?.currency || 'ETB',
    hasSelectedHotel: (state): boolean => !!state.currentHotel,
  },

  actions: {
    async fetchAvailableHotels() {
      this.isLoading = true
      this.error = null
      try {
        const response = await publicAxios.get('/guest/hotels')
        if (response.data?.success) {
          this.availableHotels = response.data.data
          // Auto-select first hotel if no hotel is currently selected
          if (!this.currentHotel && this.availableHotels.length > 0) {
            this.selectHotel(this.availableHotels[0])
          } else if (this.currentHotel && this.availableHotels.length > 0) {
            // Update current hotel with fresh details from backend
            const matched = this.availableHotels.find((h) => h.id === this.currentHotel?.id)
            if (matched) {
              this.currentHotel = matched
              localStorage.setItem('guest_current_hotel', JSON.stringify(matched))
            }
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

    selectHotel(hotel: PublicHotel) {
      this.currentHotel = hotel
      this.isSelectorOpen = false
      localStorage.setItem('guest_current_hotel', JSON.stringify(hotel))
      localStorage.setItem('guest_hotel_id', hotel.id)
      localStorage.setItem('hotel_id', hotel.id)
      localStorage.setItem('active_hotel_id', hotel.id)

      // Dynamically adapt platform brand theme to the selected hotel property
      try {
        const themeStore = useThemeStore()
        themeStore.syncWithHotel(hotel)
      } catch (e) {
        console.warn('[guestHotelStore] Could not auto-sync theme:', e)
      }

      window.dispatchEvent(new CustomEvent('guest-hotel-selected', { detail: { hotel } }))
    },

    async selectHotelById(hotelId: string) {
      if (!hotelId) return
      if (this.currentHotel?.id === hotelId) return

      if (this.availableHotels.length === 0) {
        await this.fetchAvailableHotels()
      }

      const matched = this.availableHotels.find((h) => h.id === hotelId || h.slug === hotelId)
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
      this.openHotelSelector()
    },
  },
})

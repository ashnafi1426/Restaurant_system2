import { defineStore } from 'pinia'
import { publicAxios } from '../services/axios'

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

export const useGuestHotelStore = defineStore('guestHotel', {
  state: () => ({
    currentHotel: JSON.parse(localStorage.getItem('guest_current_hotel') || 'null') as PublicHotel | null,
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
    hotelAddress: (state): string => state.currentHotel?.address || 'Bolé Road, Addis Ababa, Ethiopia',
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
            const matched = this.availableHotels.find(h => h.id === this.currentHotel?.id)
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
      window.dispatchEvent(new CustomEvent('guest-hotel-selected', { detail: { hotel } }))
    },

    clearSelectedHotel() {
      this.currentHotel = null
      localStorage.removeItem('guest_current_hotel')
      this.openHotelSelector()
    }
  },
})

import { defineStore } from 'pinia'
import { ref } from 'vue'
import reservationService from '../services/reservationService'
import type {
  Reservation,
  ReservationFilter,
  ReservationFormData,
  Pagination,
} from '../types/reservation'

interface CachedResponse<T> {
  data: T
  timestamp: number
  params: string
}

const CACHE_TTL = 3 * 60 * 1000 // 3 minutes cache

export const useReservationStore = defineStore('reservation', () => {
  const reservations = ref<Reservation[]>([])
  const reservation = ref<Reservation | null>(null)
  const loading = ref(false)
  const cache = ref<CachedResponse<Reservation[]> | null>(null)
  
  const pagination = ref<Pagination>({
    current_page: 1,
    last_page: 1,
    per_page: 10,
    total: 0,
    from: 0,
    to: 0,
  })

  const isCacheValid = (filters?: ReservationFilter): boolean => {
    if (!cache.value) return false
    
    const paramsKey = JSON.stringify(filters || {})
    const now = Date.now()
    const cacheAge = now - cache.value.timestamp
    
    return cache.value.params === paramsKey && cacheAge < CACHE_TTL
  }

  const invalidateCache = () => {
    cache.value = null
  }

  const fetchReservations = async (filters?: ReservationFilter) => {
    // Check cache first
    if (isCacheValid(filters)) {
      reservations.value = cache.value!.data
      return
    }

    loading.value = true

    try {
      const response = await reservationService.getReservations(filters)

      const data = response.data || response
      reservations.value = data

      // Cache the response
      cache.value = {
        data: data,
        timestamp: Date.now(),
        params: JSON.stringify(filters || {}),
      }

      const meta = response.meta || {
        current_page: 1,
        total: Array.isArray(data) ? data.length : 0,
        per_page: 10,
        last_page: 1,
      }

      pagination.value = {
        ...meta,
        total:
          typeof meta.total === 'number'
            ? meta.total
            : Array.isArray(meta.total)
              ? meta.total[0]
              : 0,
      }
    } catch (error: any) {
      console.error('[reservationStore] Failed to fetch reservations:', error)
      reservations.value = []
    } finally {
      loading.value = false
    }
  }

  const fetchReservation = async (id: string) => {
    loading.value = true

    try {
      const response = await reservationService.getReservation(id)
      reservation.value = response.data
    } catch (error: any) {
      console.error('[reservationStore] Failed to fetch reservation:', error)
      reservation.value = null
    } finally {
      loading.value = false
    }
  }

  const createReservation = async (data: ReservationFormData) => {
    loading.value = true

    try {
      const response = await reservationService.createReservation(data)
      invalidateCache() // Clear cache on create
      await fetchReservations()
      return response
    } catch (error: any) {
      console.error('[reservationStore] Failed to create reservation:', error)
      throw error
    } finally {
      loading.value = false
    }
  }

  const updateReservation = async (id: string, data: Partial<ReservationFormData>) => {
    loading.value = true

    try {
      const response = await reservationService.updateReservation(id, data)
      invalidateCache() // Clear cache on update
      await fetchReservations()
      return response
    } catch (error: any) {
      console.error('[reservationStore] Failed to update reservation:', error)
      throw error
    } finally {
      loading.value = false
    }
  }

  const deleteReservation = async (id: string) => {
    loading.value = true

    try {
      await reservationService.deleteReservation(id)
      invalidateCache() // Clear cache on delete
      await fetchReservations()
    } catch (error: any) {
      console.error('[reservationStore] Failed to delete reservation:', error)
      throw error
    } finally {
      loading.value = false
    }
  }

  return {
    reservations,
    reservation,
    loading,
    pagination,
    fetchReservations,
    fetchReservation,
    createReservation,
    updateReservation,
    deleteReservation,
    invalidateCache,
  }
})
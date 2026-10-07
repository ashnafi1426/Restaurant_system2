import { defineStore } from 'pinia'
import { ref } from 'vue'
import type { Guest, GuestForm, GuestFilter, GuestListResponse } from '@/types/guest'
import {
  getGuests,
  getGuest,
  createGuest,
  updateGuest,
  deleteGuest,
} from '@/services/guestService'

export interface GuestPagination {
  current_page: number
  last_page: number
  per_page: number
  total: number
}

const DEFAULT_PAGINATION: GuestPagination = {
  current_page: 1,
  last_page: 1,
  per_page: 10,
  total: 0,
}

function parseMetaNumber(val: any, fallback: number): number {
  if (typeof val === 'number') return val
  if (Array.isArray(val) && typeof val[0] === 'number') return val[0]
  return fallback
}

export const useGuestStore = defineStore('guest', () => {
  const guests = ref<Guest[]>([])
  const guest = ref<Guest | null>(null)
  const loading = ref(false)
  const error = ref('')
  const pagination = ref<GuestPagination>({ ...DEFAULT_PAGINATION })

  const fetchGuests = async (filters: GuestFilter = {}) => {
    loading.value = true
    error.value = ''

    try {
      const response: GuestListResponse = await getGuests(filters)
      guests.value = response.data || []

      const meta = response.meta || {
        current_page: 1,
        last_page: 1,
        per_page: 10,
        total: response.data?.length || 0,
      }

      pagination.value = {
        current_page: parseMetaNumber(meta.current_page, 1),
        last_page: parseMetaNumber(meta.last_page, 1),
        per_page: parseMetaNumber(meta.per_page, 10),
        total: parseMetaNumber(meta.total, 0),
      }
    } catch (err: any) {
      console.error('[guestStore] Failed to load guests:', err)
      error.value = err.response?.data?.message ?? 'Failed to load guests.'
    } finally {
      loading.value = false
    }
  }

  const fetchGuest = async (id: string) => {
    loading.value = true
    error.value = ''

    try {
      const response = await getGuest(id)
      guest.value = response.data
      return response.data
    } catch (err: any) {
      console.error('[guestStore] Failed to fetch guest:', err)
      error.value = err.response?.data?.message ?? 'Guest not found.'
      throw err
    } finally {
      loading.value = false
    }
  }

  const addGuest = async (form: GuestForm) => {
    loading.value = true
    error.value = ''

    try {
      const result = await createGuest(form)
      await fetchGuests()
      return result
    } catch (err: any) {
      console.error('[guestStore] Failed to create guest:', err)
      error.value = err.response?.data?.message ?? 'Failed to create guest.'
      throw err
    } finally {
      loading.value = false
    }
  }

  const editGuest = async (id: string, form: GuestForm) => {
    loading.value = true
    error.value = ''

    try {
      const result = await updateGuest(id, form)
      await fetchGuests()
      return result
    } catch (err: any) {
      console.error('[guestStore] Failed to update guest:', err)
      error.value = err.response?.data?.message ?? 'Failed to update guest.'
      throw err
    } finally {
      loading.value = false
    }
  }

  const removeGuest = async (id: string) => {
    loading.value = true
    error.value = ''

    try {
      await deleteGuest(id)
      guests.value = guests.value.filter((g) => g.id !== String(id))
    } catch (err: any) {
      console.error('[guestStore] Failed to delete guest:', err)
      error.value = err.response?.data?.message ?? 'Failed to delete guest.'
      throw err
    } finally {
      loading.value = false
    }
  }

  return {
    guests,
    guest,
    loading,
    error,
    pagination,
    fetchGuests,
    fetchGuest,
    addGuest,
    editGuest,
    removeGuest,
  }
})

import { defineStore } from 'pinia'
import { ref } from 'vue'
import type { Guest, GuestForm, GuestFilter, GuestListResponse } from '../types/guest'
import {
  getGuests,
  getGuest,
  createGuest,
  updateGuest,
  deleteGuest,
} from '../services/guestService.ts'

export const useGuestStore = defineStore('guest', () => {
  const guests = ref<Guest[]>([])
  const guest = ref<Guest | null>(null)
  const loading = ref(false)
  const error = ref('')
  const pagination = ref({
    current_page: 1,
    last_page: 1,
    per_page: 10,
    total: 0,
  })

  const fetchGuests = async (filters: GuestFilter = {}) => {
    loading.value = true
    error.value = ''

    try {
      const response: GuestListResponse = await getGuests(filters)
      guests.value = response.data

      // Ensure all pagination values are numbers, not arrays
      const meta = response.meta || {
        current_page: 1,
        last_page: 1,
        per_page: 10,
        total: response.data?.length || 0,
      }

      pagination.value = {
        current_page:
          typeof meta.current_page === 'number'
            ? meta.current_page
            : Array.isArray(meta.current_page)
              ? meta.current_page[0]
              : 1,
        last_page:
          typeof meta.last_page === 'number'
            ? meta.last_page
            : Array.isArray(meta.last_page)
              ? meta.last_page[0]
              : 1,
        per_page:
          typeof meta.per_page === 'number'
            ? meta.per_page
            : Array.isArray(meta.per_page)
              ? meta.per_page[0]
              : 10,
        total:
          typeof meta.total === 'number'
            ? meta.total
            : Array.isArray(meta.total)
              ? meta.total[0]
              : 0,
      }
    } catch (err: any) {
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
    } catch (err: any) {
      error.value = err.response?.data?.message ?? 'Guest not found.'
    } finally {
      loading.value = false
    }
  }

  const addGuest = async (form: GuestForm) => {
    loading.value = true
    error.value = ''

    try {
      await createGuest(form)
      await fetchGuests()
    } catch (err: any) {
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
      await updateGuest(id, form)
      await fetchGuests()
    } catch (err: any) {
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
      guests.value = guests.value.filter((guest) => guest.id !== String(id))
    } catch (err: any) {
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

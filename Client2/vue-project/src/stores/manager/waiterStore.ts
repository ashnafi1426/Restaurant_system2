import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import managerService from '@/services/managerService'
import type { Waiter } from '@/types/manager'

export const useManagerWaiterStore = defineStore('managerWaiter', () => {
  /*
  |--------------------------------------------------------------------------
  | STATE
  |--------------------------------------------------------------------------
  */

  const waiters = ref<Waiter[]>([])
  const loading = ref(false)
  const error = ref<string | null>(null)

  /*
  |--------------------------------------------------------------------------
  | COMPUTED
  |--------------------------------------------------------------------------
  */

  const waiterStats = computed(() => {
    const allWaiters = waiters.value
    return {
      total: allWaiters.length,
      active: allWaiters.filter((w) => w.status === 'active').length,
      onBreak: allWaiters.filter((w) => w.status === 'on_break').length,
      inactive: allWaiters.filter((w) => w.status === 'inactive').length,
    }
  })

  const normalizedWaiters = computed(() => {
    return waiters.value.map((waiter: any) => ({
      id: waiter.id,
      userId: waiter.user_id || waiter.userId,
      name: waiter.user?.name || waiter.user?.first_name 
        ? `${waiter.user?.first_name || ''} ${waiter.user?.last_name || ''}`.trim()
        : waiter.name || 'Unknown',
      section: waiter.section || 'Unassigned',
      status: waiter.status || 'inactive',
      shift: waiter.shift || 'N/A',
      experience_level: waiter.experience_level || 'N/A',
      phone: waiter.phone || waiter.user?.phone || 'N/A',
      user: waiter.user,
      user_id: waiter.user_id || waiter.userId,
      employment_type: waiter.employment_type || 'full_time',
      availability: waiter.availability || 'offline',
      current_orders: waiter.current_orders || 0,
      maximum_orders: waiter.maximum_orders || 10,
      employee_number: waiter.employee_number,
      hire_date: waiter.hire_date,
    }))
  })
  async function load() {
    try {
      loading.value = true
      error.value = null
      console.log('[WaiterStore] Starting load()...')
      const data = await managerService.getWaiters()
      console.log('[WaiterStore] API response received:', data)
      console.log('[WaiterStore] Data is array:', Array.isArray(data))
      console.log('[WaiterStore] Data length:', data?.length)
      
      waiters.value = data || []
      console.log('[WaiterStore] Set waiters to:', waiters.value.length, 'items')
      console.log('[WaiterStore] First waiter sample:', waiters.value[0])
    } catch (err: any) {
      console.error('[WaiterStore] Error loading waiters:', err)
      error.value = err.message
    } finally {
      loading.value = false
    }
  }

  async function create(data: {
    user_id?: string,
    section: string
    shift: string
    status: string
    experience_level: string
    first_name?: string
    last_name?: string
    email?: string
    phone?: string
    password?: string
  }) {
    try {
      console.log('[WaiterStore] Creating waiter with data:', data)
      const response = await managerService.createWaiter(data)
      console.log('[WaiterStore] Got response from backend:', response)
      
      // Normalize the response to match our display format
      const normalizedWaiter = {
        id: response.id,
        userId: response.user_id,
        name: response.user ? `${response.user.first_name} ${response.user.last_name}` : 'Unknown',
        section: response.section,
        status: response.status,
        shift: response.shift,
        experienceLevel: response.experience_level,
        phone: response.user?.phone || 'N/A',
        user: response.user,
        // Keep original fields for compatibility
        user_id: response.user_id,
        experience_level: response.experience_level,
      }
      
      console.log('[WaiterStore] Normalized waiter:', normalizedWaiter)
      waiters.value.push(normalizedWaiter)
      
      // Return both the normalized waiter and success details for display
      return {
        waiter: normalizedWaiter,
        message: `${normalizedWaiter.name} has been created as a waiter in ${normalizedWaiter.section} section (${normalizedWaiter.shift} shift)`,
        details: {
          name: normalizedWaiter.name,
          section: normalizedWaiter.section,
          shift: normalizedWaiter.shift,
          experience: normalizedWaiter.experienceLevel,
          status: normalizedWaiter.status,
        }
      }
    } catch (err: any) {
      console.error('[WaiterStore] Error creating waiter:', err)
      // Check if it's a validation error with detailed errors
      if (err.response?.status === 422 && err.response?.data?.errors) {
        const errors = err.response.data.errors
        const errorMessages = Object.values(errors)
          .flat()
          .join(', ')
        error.value = errorMessages
      } else {
        error.value = err.message || 'Failed to create waiter'
      }
      throw err
    }
  }

  async function updateStatus(waiterId: string, status: string) {
    try {
      await managerService.updateWaiterStatus(waiterId, status)
      const waiter = waiters.value.find((w) => w.id === waiterId)
      if (waiter) {
        waiter.status = status
      }
    } catch (err: any) {
      error.value = err.message
      throw err
    }
  }

  async function update(waiterId: string, data: any) {
    try {
      const response = await managerService.updateWaiter(waiterId, data)
      const index = waiters.value.findIndex((w) => w.id === waiterId)
      if (index !== -1) {
        const waiter = waiters.value[index]
        Object.assign(waiter, {
          ...waiter,
          ...data,
        })
      }
      return response
    } catch (err: any) {
      error.value = err.message
      throw err
    }
  }

  async function getPerformance(waiterId: string) {
    try {
      return await managerService.getWaiterPerformance(waiterId)
    } catch (err: any) {
      error.value = err.message
      throw err
    }
  }

  async function getAssignments(waiterId: string) {
    try {
      return await managerService.getWaiterAssignments(waiterId)
    } catch (err: any) {
      error.value = err.message
      throw err
    }
  }

  async function delete_(waiterId: string) {
    try {
      await managerService.deleteWaiter(waiterId)
      waiters.value = waiters.value.filter((w) => w.id !== waiterId)
    } catch (err: any) {
      error.value = err.message
      throw err
    }
  }

  function reset() {
    waiters.value = []
    error.value = null
  }

  return {
    waiters,
    loading,
    error,
    waiterStats,
    normalizedWaiters,
    load,
    create,
    updateStatus,
    update,
    getPerformance,
    getAssignments,
    delete_,
    reset,
  }
})

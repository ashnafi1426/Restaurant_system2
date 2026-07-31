import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import managerService from '@/services/managerService'
import type { StaffSummary } from '@/types/manager'

export const useManagerStaffStore = defineStore('managerStaff', () => {
  /*
  |--------------------------------------------------------------------------
  | STATE
  |--------------------------------------------------------------------------
  */

  const staff = ref<StaffSummary[]>([])
  const loading = ref(false)
  const error = ref<string | null>(null)

  /*
  |--------------------------------------------------------------------------
  | COMPUTED
  |--------------------------------------------------------------------------
  */

  const availableStaffCount = computed(
    () => staff.value.filter((employee) => employee.status === 'active').length,
  )

  const staffStats = computed(() => ({
    total: staff.value.length,
    active: availableStaffCount.value,
    inactive: staff.value.filter((s) => s.status === 'inactive').length,
  }))

  /*
  |--------------------------------------------------------------------------
  | ACTIONS
  |--------------------------------------------------------------------------
  */

  async function load() {
    try {
      loading.value = true
      error.value = null
      staff.value = await managerService.getStaff()
    } catch (err: any) {
      error.value = err.message
    } finally {
      loading.value = false
    }
  }

  function reset() {
    staff.value = []
    error.value = null
  }

  return {
    staff,
    loading,
    error,
    availableStaffCount,
    staffStats,
    load,
    reset,
  }
})

import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import tableAssignmentService, { type TableAssignment, type TableAssignmentStats } from '@/services/manager/tableAssignmentService'
import { getErrorMessage } from '@/utils/error'

export const useTableAssignmentStore = defineStore('tableAssignment', () => {
  const assignments = ref<TableAssignment[]>([])
  const todayAssignments = ref<TableAssignment[]>([])
  const stats = ref<TableAssignmentStats>({
    total_assignments: 0,
    total_tables: 0,
    total_waiters: 0,
    primary_assignments: 0,
    secondary_assignments: 0,
    backup_assignments: 0,
    by_shift: {},
  })
  
  const loading = ref(false)
  const error = ref<string | null>(null)
  const pagination = ref({
    current_page: 1,
    per_page: 15,
    total: 0,
    last_page: 1,
  })

  const hasAssignments = computed(() => assignments.value.length > 0)
  const primaryAssignments = computed(() => 
    assignments.value.filter(a => a.priority === 'primary')
  )
  const secondaryAssignments = computed(() => 
    assignments.value.filter(a => a.priority === 'secondary')
  )
  const backupAssignments = computed(() => 
    assignments.value.filter(a => a.priority === 'backup')
  )

  async function loadAssignments(filters?: {
    date?: string
    waiter_id?: number
    table_id?: string
    shift_id?: string
    status?: string
    priority?: string
    per_page?: number
    page?: number
  }) {
    loading.value = true
    error.value = null

    try {
      const response = await tableAssignmentService.getAssignments(filters)
      
      assignments.value = response.data || []
      
      if (response.pagination) {
        pagination.value = response.pagination
      }

      return response
    } catch (err: any) {
      console.error('[tableAssignmentStore] Failed to load assignments:', err)
      error.value = getErrorMessage(err, 'Failed to load assignments')
      throw err
    } finally {
      loading.value = false
    }
  }

  async function loadTodayAssignments() {
    loading.value = true
    error.value = null

    try {
      const data = await tableAssignmentService.getTodayAssignments()
      todayAssignments.value = data
      return data
    } catch (err: any) {
      console.error('[tableAssignmentStore] Failed to load today assignments:', err)
      error.value = getErrorMessage(err, 'Failed to load today assignments')
      todayAssignments.value = []
    } finally {
      loading.value = false
    }
  }

  async function assignWaitersToTables(assignments: any) {
    loading.value = true
    error.value = null

    try {
      const response = await tableAssignmentService.assignWaitersToTables(assignments)
      
      await loadAssignments()
      await loadTodayAssignments()
      await loadStats()
      
      return response
    } catch (err: any) {
      console.error('[tableAssignmentStore] Failed to assign waiters:', err)
      error.value = getErrorMessage(err, 'Failed to assign waiters')
      throw err
    } finally {
      loading.value = false
    }
  }

  async function updateAssignment(
    assignmentId: string,
    data: {
      waiter_id?: number
      table_id?: string
      shift_id?: string
      priority?: 'primary' | 'secondary' | 'backup'
      status?: 'active' | 'inactive' | 'completed'
    }
  ) {
    loading.value = true
    error.value = null

    try {
      const updated = await tableAssignmentService.updateAssignment(assignmentId, data)
      
      await loadAssignments()
      await loadTodayAssignments()
      await loadStats()
      
      return updated
    } catch (err: any) {
      console.error('[tableAssignmentStore] Failed to update assignment:', err)
      error.value = getErrorMessage(err, 'Failed to update assignment')
      throw err
    } finally {
      loading.value = false
    }
  }

  async function deleteAssignment(assignmentId: string) {
    loading.value = true
    error.value = null

    try {
      await tableAssignmentService.deleteAssignment(assignmentId)
      
      assignments.value = assignments.value.filter(a => a.id !== assignmentId)
      todayAssignments.value = todayAssignments.value.filter(a => a.id !== assignmentId)
      
      await loadStats()
      return true
    } catch (err: any) {
      console.error('[tableAssignmentStore] Failed to delete assignment:', err)
      error.value = getErrorMessage(err, 'Failed to delete assignment')
      throw err
    } finally {
      loading.value = false
    }
  }

  async function loadStats(date?: string) {
    try {
      const data = await tableAssignmentService.getAssignmentStats(date)
      stats.value = data
      return data
    } catch (err: any) {
      console.error('[TableAssignmentStore] Error loading stats:', err)
    }
  }

  async function getAssignedWaiterForTable(tableId: string) {
    try {
      const assignment = await tableAssignmentService.getAssignedWaiterForTable(tableId)
      return assignment
    } catch (err: any) {
      console.error('[TableAssignmentStore] Error getting assigned waiter for table:', err)
      return null
    }
  }

  async function getWaiterTables(waiterId: number, date?: string) {
    try {
      const tables = await tableAssignmentService.getWaiterTables(waiterId, date)
      return tables
    } catch (err: any) {
      console.error('[tableAssignmentStore] Error getting waiter tables:', err)
      return []
    }
  }

  function clearError() {
    error.value = null
  }

  function $reset() {
    assignments.value = []
    todayAssignments.value = []
    stats.value = {
      total_assignments: 0,
      total_tables: 0,
      total_waiters: 0,
      primary_assignments: 0,
      secondary_assignments: 0,
      backup_assignments: 0,
      by_shift: {},
    }
    loading.value = false
    error.value = null
    pagination.value = {
      current_page: 1,
      per_page: 15,
      total: 0,
      last_page: 1,
    }
  }

  return {
    assignments,
    todayAssignments,
    stats,
    loading,
    error,
    pagination,
    
    hasAssignments,
    primaryAssignments,
    secondaryAssignments,
    backupAssignments,
    
    loadAssignments,
    fetchAssignments: loadAssignments,
    loadTodayAssignments,
    fetchTodayAssignments: loadTodayAssignments,
    assignWaitersToTables,
    assignWaiters: assignWaitersToTables,
    createAssignment: assignWaitersToTables,
    updateAssignment,
    deleteAssignment,
    removeAssignment: deleteAssignment,
    loadStats,
    fetchStats: loadStats,
    getAssignedWaiterForTable,
    getWaiterTables,
    clearError,
    $reset,
  }
})

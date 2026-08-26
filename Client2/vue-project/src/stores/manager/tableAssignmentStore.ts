import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import tableAssignmentService, { type TableAssignment, type TableAssignmentStats } from '@/services/manager/tableAssignmentService'

export const useTableAssignmentStore = defineStore('tableAssignment', () => {
  // State
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

  // Computed
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

  // Actions

  /**
   * Load assignments with filters
   */
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

      console.log('[TableAssignmentStore] Loaded', assignments.value.length, 'assignments')
      return response
    } catch (err: any) {
      error.value = err.response?.data?.message || err.message || 'Failed to load assignments'
      console.error('[TableAssignmentStore] Load error:', error.value)
      throw err
    } finally {
      loading.value = false
    }
  }

  /**
   * Load today's assignments
   */
  async function loadTodayAssignments() {
    loading.value = true
    error.value = null

    try {
      const data = await tableAssignmentService.getTodayAssignments()
      todayAssignments.value = data
      console.log('[TableAssignmentStore] Loaded today assignments:', data.length)
      return data
    } catch (err: any) {
      error.value = err.response?.data?.message || err.message || 'Failed to load today assignments'
      console.error('[TableAssignmentStore] Today load error:', error.value)
      todayAssignments.value = []
    } finally {
      loading.value = false
    }
  }

  /**
   * Assign waiters to tables
   */
  async function assignWaitersToTables(assignments: Array<{
    waiter_id: number
    table_id: string
    shift_id: string
    assignment_date: string
    priority: 'primary' | 'secondary' | 'backup'
  }>) {
    loading.value = true
    error.value = null

    try {
      const response = await tableAssignmentService.assignWaitersToTables(assignments)
      
      console.log('[TableAssignmentStore] Assignment result:', response)
      
      // Reload assignments after creating
      await loadAssignments()
      await loadTodayAssignments()
      await loadStats()
      
      return response
    } catch (err: any) {
      error.value = err.response?.data?.message || err.message || 'Failed to assign waiters'
      console.error('[TableAssignmentStore] Assignment error:', error.value)
      throw err
    } finally {
      loading.value = false
    }
  }

  /**
   * Update assignment
   */
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
      
      // Reload assignments to get populated relations
      await loadAssignments()
      await loadTodayAssignments()
      await loadStats()
      
      console.log('[TableAssignmentStore] Updated assignment:', assignmentId)
      return updated
    } catch (err: any) {
      error.value = err.response?.data?.message || err.message || 'Failed to update assignment'
      console.error('[TableAssignmentStore] Update error:', error.value)
      throw err
    } finally {
      loading.value = false
    }
  }

  /**
   * Delete assignment
   */
  async function deleteAssignment(assignmentId: string) {
    loading.value = true
    error.value = null

    try {
      await tableAssignmentService.deleteAssignment(assignmentId)
      
      // Remove from local state
      assignments.value = assignments.value.filter(a => a.id !== assignmentId)
      todayAssignments.value = todayAssignments.value.filter(a => a.id !== assignmentId)
      
      // Reload stats
      await loadStats()
      
      console.log('[TableAssignmentStore] Deleted assignment:', assignmentId)
    } catch (err: any) {
      error.value = err.response?.data?.message || err.message || 'Failed to delete assignment'
      console.error('[TableAssignmentStore] Delete error:', error.value)
      throw err
    } finally {
      loading.value = false
    }
  }

  /**
   * Load statistics
   */
  async function loadStats(date?: string) {
    try {
      const data = await tableAssignmentService.getAssignmentStats(date)
      stats.value = data
      console.log('[TableAssignmentStore] Loaded stats:', data)
      return data
    } catch (err: any) {
      console.error('[TableAssignmentStore] Stats error:', err.message)
      // Keep existing stats on error
    }
  }

  /**
   * Get assigned waiter for a table
   */
  async function getAssignedWaiterForTable(tableId: string) {
    try {
      const assignment = await tableAssignmentService.getAssignedWaiterForTable(tableId)
      return assignment
    } catch (err: any) {
      console.error('[TableAssignmentStore] Get waiter error:', err.message)
      return null
    }
  }

  /**
   * Get tables assigned to a waiter
   */
  async function getWaiterTables(waiterId: number, date?: string) {
    try {
      const tables = await tableAssignmentService.getWaiterTables(waiterId, date)
      return tables
    } catch (err: any) {
      console.error('[TableAssignmentStore] Get waiter tables error:', err.message)
      return []
    }
  }

  /**
   * Clear error
   */
  function clearError() {
    error.value = null
  }

  /**
   * Reset store
   */
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
    // State
    assignments,
    todayAssignments,
    stats,
    loading,
    error,
    pagination,
    
    // Computed
    hasAssignments,
    primaryAssignments,
    secondaryAssignments,
    backupAssignments,
    
    // Actions
    loadAssignments,
    loadTodayAssignments,
    assignWaitersToTables,
    updateAssignment,
    deleteAssignment,
    loadStats,
    getAssignedWaiterForTable,
    getWaiterTables,
    clearError,
    $reset,
  }
})

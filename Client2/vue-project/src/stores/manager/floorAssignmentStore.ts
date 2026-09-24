import { defineStore } from 'pinia'
import { ref } from 'vue'
import floorAssignmentService, {
  type FloorAssignment,
  type BulkAssignmentPayload,
  type FloorAssignmentStats,
} from '@/services/manager/floorAssignmentService'

export const useFloorAssignmentStore = defineStore('floorAssignment', () => {
  const assignments = ref<FloorAssignment[]>([])
  const stats = ref<FloorAssignmentStats>({
    total_assignments: 0,
    total_floors: 0,
    total_waiters: 0,
    primary_assignments: 0,
    secondary_assignments: 0,
    backup_assignments: 0,
  })
  const loading = ref(false)
  const error = ref<string | null>(null)
  const successMessage = ref<string | null>(null)

  const fetchAssignments = async (date?: string) => {
    loading.value = true
    error.value = null
    try {
      const data = await floorAssignmentService.getAssignments(date)
      assignments.value = data
      return data
    } catch (err: any) {
      console.error('[FloorAssignmentStore] Error fetching assignments:', err)
      error.value = err.message || 'Failed to load assignments'
      assignments.value = []
      return []
    } finally {
      loading.value = false
    }
  }

  const fetchTodayAssignments = async () => {
    loading.value = true
    error.value = null
    try {
      const data = await floorAssignmentService.getTodayAssignments()
      assignments.value = data
      successMessage.value = data.length > 0 ? `${data.length} assignment(s) loaded` : 'No assignments for today'
      setTimeout(() => successMessage.value = null, 3000)
      return data
    } catch (err: any) {
      console.error('[FloorAssignmentStore] Error fetching today assignments:', err)
      error.value = err.message || 'Failed to load assignments'
      assignments.value = []
      return []
    } finally {
      loading.value = false
    }
  }

  const fetchStats = async (date?: string) => {
    try {
      const data = await floorAssignmentService.getAssignmentStats(date)
      stats.value = data
      return data
    } catch (err: any) {
      console.error('[FloorAssignmentStore] Error fetching assignment stats:', err)
      return {
        total_assignments: 0,
        total_floors: 0,
        total_waiters: 0,
        primary_assignments: 0,
        secondary_assignments: 0,
        backup_assignments: 0,
      }
    }
  }

  const saveAssignments = async (payload: BulkAssignmentPayload) => {
    loading.value = true
    error.value = null
    try {
      const data = await floorAssignmentService.bulkAssign(payload)
      assignments.value = data
      successMessage.value = `${data.length} assignment(s) saved successfully`
      setTimeout(() => successMessage.value = null, 3000)
      
      await fetchStats()
      
      return data
    } catch (err: any) {
      console.error('[FloorAssignmentStore] Error saving assignments:', err)
      error.value = err.message || 'Failed to save assignments'
      throw err
    } finally {
      loading.value = false
    }
  }

  const updateAssignment = async (
    assignmentId: string,
    priority: 'primary' | 'secondary' | 'backup'
  ) => {
    try {
      const updated = await floorAssignmentService.updateAssignmentPriority(
        assignmentId,
        priority
      )
      
      const index = assignments.value.findIndex(a => a.id === assignmentId)
      if (index >= 0) {
        assignments.value[index] = updated
      }
      
      successMessage.value = 'Assignment updated successfully'
      setTimeout(() => successMessage.value = null, 3000)
      
      return updated
    } catch (err: any) {
      console.error('[FloorAssignmentStore] Error updating assignment:', err)
      error.value = err.message || 'Failed to update assignment'
      throw err
    }
  }

  const deleteAssignment = async (assignmentId: string) => {
    try {
      await floorAssignmentService.deleteAssignment(assignmentId)
      
      assignments.value = assignments.value.filter(a => a.id !== assignmentId)
      
      successMessage.value = 'Assignment deleted successfully'
      setTimeout(() => successMessage.value = null, 3000)
      
      await fetchStats()
      
      return true
    } catch (err: any) {
      console.error('[FloorAssignmentStore] Error deleting assignment:', err)
      error.value = err.message || 'Failed to delete assignment'
      throw err
    }
  }

  const clearError = () => {
    error.value = null
  }

  const clearSuccess = () => {
    successMessage.value = null
  }

  return {
    assignments,
    stats,
    loading,
    error,
    successMessage,
    fetchAssignments,
    fetchTodayAssignments,
    fetchStats,
    saveAssignments,
    updateAssignment,
    deleteAssignment,
    clearError,
    clearSuccess,
  }
})

export default useFloorAssignmentStore

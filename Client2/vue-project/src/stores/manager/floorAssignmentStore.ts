import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import floorAssignmentService, {
  type FloorAssignment,
  type AssignmentStats
} from '@/services/manager/floorAssignmentService'

export const useFloorAssignmentStore = defineStore('floorAssignment', () => {
  // State
  const assignments = ref<FloorAssignment[]>([])
  const stats = ref<AssignmentStats | null>(null)
  const loading = ref(false)
  const error = ref<string | null>(null)
  const successMessage = ref<string | null>(null)

  // Computed
  const floors = computed(() => {
    const uniqueFloors = new Map()
    assignments.value.forEach(assignment => {
      if (!uniqueFloors.has(assignment.floor.id)) {
        uniqueFloors.set(assignment.floor.id, {
          id: assignment.floor.id,
          name: assignment.floor.name,
          floor_number: assignment.floor.floor_number,
          zone: assignment.floor.description || 'N/A',
          assignments: []
        })
      }
      uniqueFloors.get(assignment.floor.id).assignments.push(assignment)
    })
    return Array.from(uniqueFloors.values())
  })

  const groupedByFloor = computed(() => {
    const grouped: Record<string, FloorAssignment[]> = {}
    assignments.value.forEach(assignment => {
      const floorId = assignment.floor.id
      if (!grouped[floorId]) {
        grouped[floorId] = []
      }
      grouped[floorId].push(assignment)
    })
    return grouped
  })

  // Methods
  const fetchTodayAssignments = async () => {
    loading.value = true
    error.value = null
    try {
      console.log('[FloorAssignmentStore] Fetching today assignments...')
      const data = await floorAssignmentService.getTodayAssignments()
      console.log('[FloorAssignmentStore] Fetched assignments:', data.length, 'records')
      console.log('[FloorAssignmentStore] Assignment data:', data)
      
      assignments.value = data
      successMessage.value = data.length > 0 ? `${data.length} assignment(s) loaded` : 'No assignments for today'
      setTimeout(() => successMessage.value = null, 3000)
      return data
    } catch (err: any) {
      error.value = err.message || 'Failed to load assignments'
      console.error('[FloorAssignmentStore] Error fetching assignments:', err)
      assignments.value = [] // Clear on error
      return []
    } finally {
      loading.value = false
    }
  }

  const fetchStats = async (date?: string) => {
    try {
      console.log('[FloorAssignmentStore] Fetching stats...')
      const data = await floorAssignmentService.getAssignmentStats(date)
      console.log('[FloorAssignmentStore] Stats fetched:', data)
      stats.value = data
      return data
    } catch (err: any) {
      console.warn('[FloorAssignmentStore] Error fetching stats (non-critical):', err.message)
      // Stats are optional - return default on error
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

  const saveAssignments = async (assignments_data: Array<{
    waiter_id: string
    floor_id: string
    shift_id: string
    assignment_date: string
    priority: 'primary' | 'secondary' | 'backup'
  }>) => {
    loading.value = true
    error.value = null
    try {
      const data = await floorAssignmentService.assignWaitersToFloors(assignments_data)
      assignments.value = data
      successMessage.value = `${data.length} assignment(s) saved successfully`
      setTimeout(() => successMessage.value = null, 3000)
      
      // Refresh stats
      await fetchStats()
      
      return data
    } catch (err: any) {
      error.value = err.message || 'Failed to save assignments'
      console.error('Error saving assignments:', err)
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
      
      // Update in local state
      const index = assignments.value.findIndex(a => a.id === assignmentId)
      if (index >= 0) {
        assignments.value[index] = updated
      }
      
      successMessage.value = 'Assignment updated successfully'
      setTimeout(() => successMessage.value = null, 3000)
      
      return updated
    } catch (err: any) {
      error.value = err.message || 'Failed to update assignment'
      console.error('Error updating assignment:', err)
      throw err
    }
  }

  const deleteAssignment = async (assignmentId: string) => {
    try {
      await floorAssignmentService.deleteAssignment(assignmentId)
      
      // Remove from local state
      assignments.value = assignments.value.filter(a => a.id !== assignmentId)
      
      successMessage.value = 'Assignment deleted successfully'
      setTimeout(() => successMessage.value = null, 3000)
      
      // Refresh stats
      await fetchStats()
      
      return true
    } catch (err: any) {
      error.value = err.message || 'Failed to delete assignment'
      console.error('Error deleting assignment:', err)
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
    // State
    assignments,
    stats,
    loading,
    error,
    successMessage,

    // Computed
    floors,
    groupedByFloor,

    // Methods
    fetchTodayAssignments,
    fetchStats,
    saveAssignments,
    updateAssignment,
    deleteAssignment,
    clearError,
    clearSuccess,
  }
})

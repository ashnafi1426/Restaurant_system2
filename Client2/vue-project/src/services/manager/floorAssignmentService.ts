import api from '@/api/auth'

export interface FloorAssignment {
  id: string
  waiter: {
    id: string
    user: { name: string; email: string }
    employment_type: string
    status: string
    availability: string
  }
  floor: {
    id: string
    floor_number: number
    name: string
    description: string
  }
  shift: {
    id: string
    name: string
    start_time: string
    end_time: string
  }
  assignment_date: string
  status: string
  priority: 'primary' | 'secondary' | 'backup'
  created_at: string
}

export interface AssignmentStats {
  total_assignments: number
  total_floors: number
  total_waiters: number
  primary_assignments: number
  secondary_assignments: number
  backup_assignments: number
}

class FloorAssignmentService {
  /**
   * Get today's floor assignments
   */
  async getTodayAssignments(): Promise<FloorAssignment[]> {
    try {
      const response = await api.get('/manager/floors/assignments/today')
      return response.data.data || response.data
    } catch (error: any) {
      // If endpoint fails, log and return empty array
      console.warn('Failed to fetch today assignments:', error.message)
      return []
    }
  }

  /**
   * Get all assignments with filters
   */
  async getAssignments(params?: {
    page?: number
    per_page?: number
    date?: string
    floor_id?: string
    waiter_id?: string
    status?: string
  }): Promise<any> {
    const response = await api.get('/manager/floors/assignments', { params })
    return response.data
  }

  /**
   * Assign waiters to floors (batch)
   */
  async assignWaitersToFloors(assignments: Array<{
    waiter_id: string
    floor_id: string
    shift_id: string
    assignment_date: string
    priority: 'primary' | 'secondary' | 'backup'
  }>): Promise<FloorAssignment[]> {
    try {
      console.log('[FloorAssignmentService] Assigning waiters to floors:', assignments)
      
      const response = await api.post('/manager/floors/assignments', {
        assignments,
      })
      
      console.log('[FloorAssignmentService] API Response:', response.data)
      
      const data = response.data.data || response.data
      return Array.isArray(data) ? data : [data]
    } catch (error: any) {
      console.error('[FloorAssignmentService] Assignment error:', error.response?.data || error.message)
      throw error
    }
  }

  /**
   * Update assignment priority
   */
  async updateAssignmentPriority(
    assignmentId: string,
    priority: 'primary' | 'secondary' | 'backup'
  ): Promise<FloorAssignment> {
    const response = await api.patch(`/manager/floors/assignments/${assignmentId}`, {
      priority,
    })
    return response.data.data
  }

  /**
   * Delete assignment
   */
  async deleteAssignment(assignmentId: string): Promise<void> {
    await api.delete(`/manager/floors/assignments/${assignmentId}`)
  }

  /**
   * Get assignment statistics
   */
  async getAssignmentStats(date?: string): Promise<AssignmentStats> {
    try {
      console.log('[FloorAssignmentService] Fetching stats for date:', date)
      const response = await api.get('/manager/floors/assignments/stats', {
        params: date ? { date } : {},
      })
      console.log('[FloorAssignmentService] Stats response:', response.data)
      return response.data.data || response.data
    } catch (error: any) {
      // If stats endpoint fails, log the URL and return default stats
      console.warn('[FloorAssignmentService] Stats endpoint failed:', {
        message: error.message,
        status: error.response?.status,
        url: error.config?.url,
        fullError: error
      })
      // Return default stats - this is non-critical
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

  /**
   * Get all shifts (for assignment modal)
   */
  async getShifts(): Promise<any[]> {
    try {
      console.log('[FloorAssignmentService] Fetching shifts...')
      const response = await api.get('/manager/shifts', { params: { status: 'active' } })
      const shifts = response.data.data || response.data
      
      // Ensure we return an array
      if (Array.isArray(shifts)) {
        console.log('[FloorAssignmentService] Shifts loaded:', shifts.length)
        return shifts
      } else if (shifts.data && Array.isArray(shifts.data)) {
        console.log('[FloorAssignmentService] Shifts loaded (from data property):', shifts.data.length)
        return shifts.data
      }
      
      console.warn('[FloorAssignmentService] Unexpected shifts format:', shifts)
      return []
    } catch (error: any) {
      console.error('[FloorAssignmentService] Error fetching shifts:', error.message)
      return []
    }
  }
}

export default new FloorAssignmentService()

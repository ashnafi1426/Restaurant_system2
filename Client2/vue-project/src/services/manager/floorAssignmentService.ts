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
  async getTodayAssignments(): Promise<FloorAssignment[]> {
    try {
      const response = await api.get('/manager/floors/assignments/today')
      return response.data.data || response.data
    } catch (error: any) {
      console.error('[FloorAssignmentService] Error fetching today assignments:', error)
      return []
    }
  }

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

  async assignWaitersToFloors(assignments: Array<{
    waiter_id: string
    floor_id: string
    shift_id: string
    assignment_date: string
    priority: 'primary' | 'secondary' | 'backup'
  }>): Promise<FloorAssignment[]> {
    try {
      const response = await api.post('/manager/floors/assignments', {
        assignments,
      })
      const data = response.data.data || response.data
      return Array.isArray(data) ? data : [data]
    } catch (error: any) {
      console.error('[FloorAssignmentService] Error assigning waiters to floors:', error)
      throw error
    }
  }

  async updateAssignmentPriority(
    assignmentId: string,
    priority: 'primary' | 'secondary' | 'backup'
  ): Promise<FloorAssignment> {
    const response = await api.patch(`/manager/floors/assignments/${assignmentId}`, {
      priority,
    })
    return response.data.data
  }

  async deleteAssignment(assignmentId: string): Promise<void> {
    await api.delete(`/manager/floors/assignments/${assignmentId}`)
  }

  async getAssignmentStats(date?: string): Promise<AssignmentStats> {
    try {
      const response = await api.get('/manager/floors/assignments/stats', {
        params: date ? { date } : {},
      })
      return response.data.data || response.data
    } catch (error: any) {
      console.error('[FloorAssignmentService] Error fetching assignment stats:', error)
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

  async getShifts(): Promise<any[]> {
    try {
      const response = await api.get('/manager/shifts', { params: { status: 'active' } })
      const shifts = response.data.data || response.data
      
      if (Array.isArray(shifts)) {
        return shifts
      } else if (shifts.data && Array.isArray(shifts.data)) {
        return shifts.data
      }
      return []
    } catch (error: any) {
      console.error('[FloorAssignmentService] Error fetching shifts:', error)
      return []
    }
  }
}

export default new FloorAssignmentService()

import api from '@/api/auth'

export interface FloorAssignment {
  id: string
  hotel_id?: string
  waiter_id?: string | number
  waiter_name?: string
  waiter?: {
    id?: string | number
    name?: string
    user?: { name: string; email?: string }
    employment_type?: string
    status?: string
    availability?: string
  }
  floor?: {
    id: string
    floor_number: number
    name: string
    description?: string
  }
  shift?: {
    id: string
    name: string
    start_time: string
    end_time: string
  }
  assignment_date: string
  status: string
  priority: 'primary' | 'secondary' | 'backup'
  is_active?: boolean
  assigned_at?: string
  assigned_by?: {
    id?: string
    name?: string
  }
  created_at?: string
  updated_at?: string
}

export interface AssignmentStats {
  total_assignments: number
  total_floors: number
  total_waiters: number
  primary_assignments: number
  secondary_assignments: number
  backup_assignments: number
}

export type FloorAssignmentStats = AssignmentStats

export interface AssignmentItem {
  waiter_id: string | number
  floor_id: string
  shift_id?: string | null
  assignment_date?: string
  priority?: 'primary' | 'secondary' | 'backup'
  status?: string
  is_active?: boolean
}

export type BulkAssignmentPayload = AssignmentItem[] | { assignments: AssignmentItem[] }

class FloorAssignmentService {
  async getTodayAssignments(): Promise<FloorAssignment[]> {
    try {
      const response = await api.get('/manager/floors/assignments/today')
      return response.data.data || response.data
    } catch (error: unknown) {
      console.error('[FloorAssignmentService] Error fetching today assignments:', error)
      return []
    }
  }

  async getAssignments(
    params?:
      | string
      | {
          page?: number
          per_page?: number
          date?: string
          floor_id?: string
          waiter_id?: string | number
          status?: string
        },
  ): Promise<{ data: FloorAssignment[]; pagination?: unknown }> {
    const queryParams = typeof params === 'string' ? { date: params } : params
    const response = await api.get('/manager/floors/assignments', { params: queryParams })
    return response.data
  }

  async assignWaitersToFloors(assignments: AssignmentItem[]): Promise<FloorAssignment[]> {
    try {
      const response = await api.post('/manager/floors/assignments', {
        assignments,
      })
      const data = response.data.data || response.data
      return Array.isArray(data) ? data : [data]
    } catch (error: unknown) {
      console.error('[FloorAssignmentService] Error assigning waiters to floors:', error)
      throw error
    }
  }

  async bulkAssign(payload: BulkAssignmentPayload): Promise<FloorAssignment[]> {
    const list = Array.isArray(payload) ? payload : payload?.assignments || []
    return this.assignWaitersToFloors(list)
  }

  async updateAssignmentPriority(
    assignmentId: string,
    priority: 'primary' | 'secondary' | 'backup',
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
    } catch (error: unknown) {
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

  async getShifts(): Promise<
    Array<{ id: string; name: string; start_time: string; end_time: string; is_active: boolean }>
  > {
    try {
      const response = await api.get('/manager/shifts', { params: { status: 'active' } })
      const shifts = response.data.data || response.data

      if (Array.isArray(shifts)) {
        return shifts
      } else if (shifts.data && Array.isArray(shifts.data)) {
        return shifts.data
      }
      return []
    } catch (error: unknown) {
      console.error('[FloorAssignmentService] Error fetching shifts:', error)
      return []
    }
  }
}

export default new FloorAssignmentService()

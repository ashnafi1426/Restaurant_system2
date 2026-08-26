import api from '@/api/auth'

export interface TableAssignment {
  id: string
  waiter_id: number
  table_id: string
  shift_id: string
  assignment_date: string
  priority: 'primary' | 'secondary' | 'backup'
  status: 'active' | 'inactive' | 'completed'
  assigned_by: string | null
  waiter: {
    id: number
    user: {
      name: string
      email: string
    }
    status: string
    experience_level: string
  }
  table: {
    id: string
    table_number: string
    table_name: string | null
    location: string | null
    section: string | null
    status: string
  }
  shift: {
    id: string
    name: string
    start_time: string
    end_time: string
  }
  created_at: string
  updated_at: string
}

export interface TableAssignmentStats {
  total_assignments: number
  total_tables: number
  total_waiters: number
  primary_assignments: number
  secondary_assignments: number
  backup_assignments: number
  by_shift: Record<string, number>
}

class TableAssignmentService {
  /**
   * Get all table assignments with filters
   */
  async getAssignments(params?: {
    date?: string
    waiter_id?: number
    table_id?: string
    shift_id?: string
    status?: string
    priority?: string
    per_page?: number
    page?: number
  }): Promise<any> {
    const response = await api.get('/manager/table-assignments', { params })
    return response.data
  }

  /**
   * Get today's active assignments
   */
  async getTodayAssignments(): Promise<TableAssignment[]> {
    try {
      const response = await api.get('/manager/table-assignments/today')
      return response.data.data || []
    } catch (error: any) {
      console.warn('Failed to fetch today assignments:', error.message)
      return []
    }
  }

  /**
   * Assign waiters to tables (batch)
   */
  async assignWaitersToTables(assignments: Array<{
    waiter_id: number
    table_id: string
    shift_id: string
    assignment_date: string
    priority: 'primary' | 'secondary' | 'backup'
  }>): Promise<any> {
    console.log('[TableAssignmentService] Assigning waiters to tables:', assignments)
    
    const response = await api.post('/manager/table-assignments', {
      assignments,
    })
    
    console.log('[TableAssignmentService] API Response:', response.data)
    return response.data
  }

  /**
   * Update assignment
   */
  async updateAssignment(
    assignmentId: string,
    data: {
      waiter_id?: number
      table_id?: string
      shift_id?: string
      priority?: 'primary' | 'secondary' | 'backup'
      status?: 'active' | 'inactive' | 'completed'
    }
  ): Promise<TableAssignment> {
    const response = await api.patch(`/manager/table-assignments/${assignmentId}`, data)
    return response.data.data
  }

  /**
   * Delete assignment
   */
  async deleteAssignment(assignmentId: string): Promise<void> {
    await api.delete(`/manager/table-assignments/${assignmentId}`)
  }

  /**
   * Get assignment statistics
   */
  async getAssignmentStats(date?: string): Promise<TableAssignmentStats> {
    try {
      const response = await api.get('/manager/table-assignments/stats', {
        params: date ? { date } : {},
      })
      return response.data.data
    } catch (error: any) {
      console.warn('[TableAssignmentService] Stats endpoint failed:', error.message)
      return {
        total_assignments: 0,
        total_tables: 0,
        total_waiters: 0,
        primary_assignments: 0,
        secondary_assignments: 0,
        backup_assignments: 0,
        by_shift: {},
      }
    }
  }

  /**
   * Get assigned waiter for a table
   */
  async getAssignedWaiterForTable(tableId: string): Promise<TableAssignment | null> {
    try {
      const response = await api.get(`/manager/table-assignments/table/${tableId}/assigned-waiter`)
      return response.data.data
    } catch (error: any) {
      if (error.response?.status === 404) {
        return null
      }
      throw error
    }
  }

  /**
   * Get tables assigned to a waiter
   */
  async getWaiterTables(waiterId: number, date?: string): Promise<TableAssignment[]> {
    try {
      const response = await api.get(`/manager/table-assignments/waiter/${waiterId}/tables`, {
        params: date ? { date } : {},
      })
      return response.data.data || []
    } catch (error: any) {
      console.error('[TableAssignmentService] Error fetching waiter tables:', error.message)
      return []
    }
  }
}

export default new TableAssignmentService()

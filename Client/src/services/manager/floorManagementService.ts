import api from '@/api/auth'

export interface FloorRoom {
  id: string
  room_number: string
  room_type: string
  status: 'available' | 'occupied' | 'maintenance' | 'cleaning' | string
  is_active: boolean
  price_per_night: number
  capacity?: number
}

export interface FloorWaiterAssignment {
  id: string
  waiter_id: string | number
  waiter_name: string
  waiter?: {
    id?: string | number
    name?: string
    user?: { name: string; email?: string }
  }
  first_name?: string
  last_name?: string
  email?: string
  phone?: string
  section?: string
  shift?: {
    id: string
    name: string
    start_time: string
    end_time: string
  } | null
  priority: 'primary' | 'secondary' | 'backup'
  status: string
  assignment_date?: string
  is_active?: boolean
}

export interface Floor {
  id: string
  hotel_id?: string
  floor_number: number
  name: string
  description?: string
  is_active: boolean
  total_rooms?: number
  room_count?: number
  rooms?: FloorRoom[]
  waiter_assignments?: FloorWaiterAssignment[]
  created_at?: string
  updated_at?: string
}

export interface FloorStats {
  floor_id?: string
  floor_number?: number
  name?: string
  is_active?: boolean
  total_rooms: number
  occupied_rooms: number
  available_rooms: number
  total_assignments: number
  active_waiters: number
  assigned_waiters?: number
  total_deliveries?: number
  completed_deliveries?: number
  pending_deliveries?: number
  cancelled_deliveries?: number
  average_delivery_time?: number
}

export interface FloorPagination {
  total: number
  per_page: number
  current_page: number
  last_page: number
}

export interface FloorListResponse {
  success: boolean
  message: string
  data: Floor[]
  pagination?: FloorPagination
}

class FloorManagementService {
  async getFloors(params?: {
    page?: number
    per_page?: number
    is_active?: boolean
    search?: string
  }): Promise<FloorListResponse> {
    const response = await api.get('/manager/floors', { params })
    return response.data
  }

  async createFloor(data: {
    floor_number: number
    name: string
    description?: string
    total_rooms?: number
  }): Promise<Floor> {
    const response = await api.post('/manager/floors', data)
    return response.data.data
  }

  async getFloor(floorId: string): Promise<Floor> {
    const response = await api.get(`/manager/floors/${floorId}`)
    return response.data.data
  }

  async updateFloor(
    floorId: string,
    data: {
      floor_number?: number
      name?: string
      description?: string
      is_active?: boolean
      total_rooms?: number
    },
  ): Promise<Floor> {
    const response = await api.put(`/manager/floors/${floorId}`, data)
    return response.data.data
  }

  async deleteFloor(floorId: string): Promise<void> {
    await api.delete(`/manager/floors/${floorId}`)
  }

  async activateFloor(floorId: string): Promise<Floor> {
    const response = await api.patch(`/manager/floors/${floorId}/activate`)
    return response.data.data
  }

  async deactivateFloor(floorId: string): Promise<Floor> {
    const response = await api.patch(`/manager/floors/${floorId}/deactivate`)
    return response.data.data
  }

  async getFloorStats(floorId: string): Promise<FloorStats> {
    try {
      const response = await api.get(`/manager/floors/${floorId}/stats`)
      return response.data.data
    } catch (err: unknown) {
      console.error('[FloorManagementService] Error fetching floor stats:', err)
      return {
        total_rooms: 0,
        occupied_rooms: 0,
        available_rooms: 0,
        total_assignments: 0,
        active_waiters: 0,
      }
    }
  }

  async validateFloorNumber(floorNumber: number): Promise<boolean> {
    try {
      const response = await api.get('/manager/floors', {
        params: { search: String(floorNumber) },
      })
      const floors: Floor[] = response.data.data || []
      const exists = floors.some((f: Floor) => f.floor_number === floorNumber)
      return !exists
    } catch (err: unknown) {
      console.error('[FloorManagementService] Error validating floor number:', err)
      return true
    }
  }

  async getAvailableWaiters(): Promise<unknown[]> {
    try {
      try {
        const response = await api.get('/manager/waiters/available')
        return response.data.data || []
      } catch (error: any) {
        if (error.response?.status === 404) {
          const response = await api.get('/manager/waiters')
          return (response.data.data || []).filter((w: { is_active?: boolean }) => w.is_active)
        }
        throw error
      }
    } catch (err: unknown) {
      console.error('[FloorManagementService] Error fetching available waiters:', err)
      return []
    }
  }
}

export default new FloorManagementService()

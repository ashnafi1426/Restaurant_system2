import api from '@/api/auth'

export interface Floor {
  id: string
  floor_number: number
  name: string
  description?: string
  is_active: boolean
  created_at?: string
  updated_at?: string
}

export interface FloorStats {
  total_rooms: number
  occupied_rooms: number
  available_rooms: number
  total_assignments: number
  active_waiters: number
}

class FloorManagementService {
  async getFloors(params?: {
    page?: number
    per_page?: number
    is_active?: boolean
    search?: string
  }): Promise<any> {
    const response = await api.get('/manager/floors', { params })
    return response.data
  }

  async createFloor(data: {
    floor_number: number
    name: string
    description?: string
  }): Promise<Floor> {
    try {
      const response = await api.post('/manager/floors', data)
      return response.data.data
    } catch (error: any) {
      console.error('[FloorManagementService] Error creating floor:', error)
      throw error
    }
  }

  async getFloor(floorId: string): Promise<Floor> {
    const response = await api.get(`/manager/floors/${floorId}`)
    return response.data.data
  }

  async updateFloor(
    floorId: string,
    data: {
      name?: string
      description?: string
      is_active?: boolean
    }
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
    } catch (err: any) {
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
      const exists = response.data.data.some((f: Floor) => f.floor_number === floorNumber)
      return !exists
    } catch (err: any) {
      console.error('[FloorManagementService] Error validating floor number:', err)
      return true
    }
  }

  async getAvailableWaiters(): Promise<any[]> {
    try {
      try {
        const response = await api.get('/manager/waiters/available')
        return response.data.data || []
      } catch (error: any) {
        console.warn('[FloorManagementService] /manager/waiters/available failed, trying fallback:', error)
        if (error.response?.status === 404) {
          const response = await api.get('/manager/waiters')
          return (response.data.data || []).filter((w: any) => w.is_active)
        }
        throw error
      }
    } catch (err: any) {
      console.error('[FloorManagementService] Error fetching available waiters:', err)
      return []
    }
  }
}

export default new FloorManagementService()

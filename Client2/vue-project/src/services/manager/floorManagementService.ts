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
  /**
   * Get all floors with optional filters
   */
  async getFloors(params?: {
    page?: number
    per_page?: number
    is_active?: boolean
    search?: string
  }): Promise<any> {
    const response = await api.get('/manager/floors', { params })
    return response.data
  }

  /**
   * Create new floor
   */
  async createFloor(data: {
    floor_number: number
    name: string
    description?: string
  }): Promise<Floor> {
    try {
      const response = await api.post('/manager/floors', data)
      return response.data.data
    } catch (error: any) {
      console.error('Error creating floor:', error.response?.data || error.message)
      throw error
    }
  }

  /**
   * Get single floor details
   */
  async getFloor(floorId: string): Promise<Floor> {
    const response = await api.get(`/manager/floors/${floorId}`)
    return response.data.data
  }

  /**
   * Update floor
   */
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

  /**
   * Delete floor
   */
  async deleteFloor(floorId: string): Promise<void> {
    await api.delete(`/manager/floors/${floorId}`)
  }

  /**
   * Activate floor
   */
  async activateFloor(floorId: string): Promise<Floor> {
    const response = await api.patch(`/manager/floors/${floorId}/activate`)
    return response.data.data
  }

  /**
   * Deactivate floor
   */
  async deactivateFloor(floorId: string): Promise<Floor> {
    const response = await api.patch(`/manager/floors/${floorId}/deactivate`)
    return response.data.data
  }

  /**
   * Get floor statistics
   */
  async getFloorStats(floorId: string): Promise<FloorStats> {
    try {
      const response = await api.get(`/manager/floors/${floorId}/stats`)
      return response.data.data
    } catch (error) {
      console.warn('Failed to fetch floor stats')
      return {
        total_rooms: 0,
        occupied_rooms: 0,
        available_rooms: 0,
        total_assignments: 0,
        active_waiters: 0,
      }
    }
  }

  /**
   * Validate floor number uniqueness
   */
  async validateFloorNumber(floorNumber: number): Promise<boolean> {
    try {
      const response = await api.get('/manager/floors', {
        params: { search: String(floorNumber) },
      })
      const exists = response.data.data.some((f: Floor) => f.floor_number === floorNumber)
      return !exists // Return true if unique
    } catch (error) {
      return true // Assume unique on error
    }
  }

  /**
   * Get available waiters for assignment
   */
  async getAvailableWaiters(): Promise<any[]> {
    try {
      // Try the newer endpoint first
      try {
        const response = await api.get('/manager/waiters/available')
        return response.data.data || []
      } catch (error: any) {
        // Fallback: if endpoint doesn't exist, get all active waiters
        if (error.response?.status === 404) {
          console.warn('Waiters endpoint not found, trying alternative...')
          const response = await api.get('/manager/waiters')
          return (response.data.data || []).filter((w: any) => w.is_active)
        }
        throw error
      }
    } catch (error) {
      console.warn('Failed to fetch available waiters', error)
      // Return empty array as fallback - UI will handle it gracefully
      return []
    }
  }
}

export default new FloorManagementService()

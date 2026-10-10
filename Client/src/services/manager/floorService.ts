import axios from '@/services/axios'

export interface Floor {
  id: string
  floor_number: number
  name: string
  description?: string
  is_active: boolean
  room_count?: number
}
export interface FloorResponse {
  success: boolean
  data: Floor[]
  message?: string
}

export const floorService = {
  async getFloors(): Promise<FloorResponse> {
    try {
      const response = await axios.get('/manager/floors')
      return response.data
    } catch (error: any) {
      console.error('[FloorService] Error fetching floors:', error)
      throw error
    }
  },

  async getActiveFloors(): Promise<FloorResponse> {
    try {
      const response = await axios.get('/manager/floors', {
        params: { is_active: true },
      })
      return response.data
    } catch (error: any) {
      console.error('[FloorService] Error fetching active floors:', error)
      throw error
    }
  },

  async getFloor(floorId: string): Promise<{ success: boolean; data: Floor }> {
    try {
      const response = await axios.get(`/manager/floors/${floorId}`)
      return response.data
    } catch (error: any) {
      console.error('[FloorService] Error fetching floor:', error)
      throw error
    }
  },
}

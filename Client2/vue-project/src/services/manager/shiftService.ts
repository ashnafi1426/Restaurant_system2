import axios from '@/services/axios'

export interface Shift {
  id: string
  name: string
  start_time: string
  end_time: string
  is_active: boolean
  description?: string
}

export interface ShiftResponse {
  success: boolean
  data: Shift[]
  message?: string
}

export const shiftService = {
  /**
   * Get all active shifts
   */
  async getShifts(): Promise<ShiftResponse> {
    try {
      const response = await axios.get('/api/manager/shifts')
      return response.data
    } catch (error: any) {
      console.error('Error fetching shifts:', error)
      throw error
    }
  }
}

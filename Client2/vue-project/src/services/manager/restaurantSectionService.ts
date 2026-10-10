import axios from '../axios'
import type {
  RestaurantSection,
  CreateSectionRequest,
  UpdateSectionRequest,
} from '@/types/restaurantSection'

export const restaurantSectionService = {
  async getSections(params?: { is_active?: boolean }): Promise<RestaurantSection[]> {
    try {
      const response = await axios.get('/manager/restaurant-sections', { params })
      if (response.data?.success && response.data?.data) {
        return response.data.data
      }
      return Array.isArray(response.data) ? response.data : []
    } catch (error) {
      console.error('[RestaurantSectionService] Error fetching sections:', error)
      throw error
    }
  },

  async getSection(id: string): Promise<RestaurantSection> {
    const response = await axios.get(`/manager/restaurant-sections/${id}`)
    return response.data?.data || response.data
  },

  async createSection(
    data: CreateSectionRequest,
  ): Promise<{ success: boolean; message: string; data: RestaurantSection }> {
    try {
      const response = await axios.post('/manager/restaurant-sections', data)
      return response.data
    } catch (error: any) {
      console.error('[RestaurantSectionService] Error creating section:', error)
      if (error.response?.data?.errors) {
        const err: any = new Error(error.response.data.message || 'Validation failed')
        err.errors = error.response.data.errors
        err.response = error.response
        throw err
      }
      throw error
    }
  },

  async updateSection(
    id: string,
    data: UpdateSectionRequest,
  ): Promise<{ success: boolean; message: string; data: RestaurantSection }> {
    try {
      const response = await axios.put(`/manager/restaurant-sections/${id}`, data)
      return response.data
    } catch (error: any) {
      console.error('[RestaurantSectionService] Error updating section:', error)
      if (error.response?.data?.errors) {
        const err: any = new Error(error.response.data.message || 'Validation failed')
        err.errors = error.response.data.errors
        err.response = error.response
        throw err
      }
      throw error
    }
  },

  async deleteSection(id: string): Promise<{ success: boolean; message: string }> {
    const response = await axios.delete(`/manager/restaurant-sections/${id}`)
    return response.data
  },
}

export default restaurantSectionService

import { axiosInstance as axios } from '../axios'
import type {
  RestaurantTable,
  CreateTableRequest,
  UpdateTableRequest,
  TableFilters,
  TableStatistics,
  PaginatedTablesResponse
} from '@/types/restaurantTable'

/**
 * Restaurant Table Service - Manager API calls for table management
 */
export const restaurantTableService = {
  /**
   * Get list of restaurant tables with pagination and filtering
   */
  async getTables(filters?: TableFilters): Promise<{ success: boolean; data: PaginatedTablesResponse }> {
    console.log('🔴 [SERVICE] getTables START')
    console.log('🔴 [SERVICE] axios imported:', typeof axios, axios.name)
    console.log('🔴 [SERVICE] axios.get:', typeof axios.get)
    
    try {
      console.log('🔴 [SERVICE] Calling axios.get with URL: /manager/restaurant-tables')
      console.log('🔴 [SERVICE] With filters:', filters)
      
      const response = await axios.get('/manager/restaurant-tables', { params: filters })
      
      console.log('🔴 [SERVICE] Response received!')
      console.log('🔴 [SERVICE] Response status:', response.status)
      console.log('🔴 [SERVICE] Response data:', response.data)
      
      return response.data
    } catch (error) {
      console.error('🔴 [SERVICE] Error caught:', error)
      throw error
    }
  },

  /**
   * Get single restaurant table by ID
   */
  async getTableById(id: string): Promise<{ success: boolean; data: RestaurantTable }> {
    const response = await axios.get(`/manager/restaurant-tables/${id}`)
    return response.data
  },

  /**
   * Create new restaurant table
   */
  async createTable(data: CreateTableRequest): Promise<{ success: boolean; message: string; data: RestaurantTable }> {
    try {
      const response = await axios.post('/manager/restaurant-tables', data)
      return response.data
    } catch (error: any) {
      // Re-throw with validation errors attached
      if (error.response?.data?.errors) {
        const err: any = new Error(error.response.data.message || 'Validation failed')
        err.errors = error.response.data.errors
        err.response = error.response
        throw err
      }
      throw error
    }
  },

  /**
   * Update existing restaurant table
   */
  async updateTable(
    id: string,
    data: UpdateTableRequest
  ): Promise<{ success: boolean; message: string; data: RestaurantTable }> {
    const response = await axios.put(`/manager/restaurant-tables/${id}`, data)
    return response.data
  },

  /**
   * Delete restaurant table (soft delete)
   */
  async deleteTable(id: string): Promise<{ success: boolean; message: string }> {
    const response = await axios.delete(`/manager/restaurant-tables/${id}`)
    return response.data
  },

  /**
   * Regenerate QR code for a table
   */
  async regenerateQR(id: string): Promise<{
    success: boolean
    message: string
    data: {
      qr_token: string
      qr_image_path: string
      qr_code_url: string
      qr_generated_at: string
    }
  }> {
    const response = await axios.post(`/manager/restaurant-tables/${id}/regenerate-qr`)
    return response.data
  },

  /**
   * Get table statistics
   */
  async getStatistics(): Promise<{ success: boolean; data: TableStatistics }> {
    const response = await axios.get('/manager/restaurant-tables/statistics')
    return response.data
  },

  /**
   * Download QR code image
   */
  downloadQRCode(qrCodeUrl: string, tableNumber: string): void {
    const link = document.createElement('a')
    link.href = qrCodeUrl
    link.download = `table_${tableNumber}_qr.png`
    link.target = '_blank'
    document.body.appendChild(link)
    link.click()
    document.body.removeChild(link)
  }
}

export default restaurantTableService

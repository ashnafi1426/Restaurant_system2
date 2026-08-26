import axios from '../axios'
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
   * Get all restaurant tables without pagination (for dropdowns)
   */
  async getAllTables(): Promise<RestaurantTable[]> {
    console.log('🔴 [SERVICE] getAllTables START')
    
    try {
      // Request with high per_page to get all tables
      const response = await axios.get('/manager/restaurant-tables', { 
        params: { 
          per_page: 1000,
          is_active: true 
        } 
      })
      
      console.log('🔴 [SERVICE] Full Response:', JSON.stringify(response, null, 2))
      console.log('🔴 [SERVICE] Response data:', response.data)
      console.log('🔴 [SERVICE] Response data.data:', response.data?.data)
      console.log('🔴 [SERVICE] Response data.data.data:', response.data?.data?.data)
      
      // Extract tables array from paginated response
      if (response.data?.success && response.data?.data) {
        // Paginated response: data.data is pagination object with data.data.data being array
        if (response.data.data.data && Array.isArray(response.data.data.data)) {
          console.log('🔴 [SERVICE] ✅ Found tables in data.data.data:', response.data.data.data.length)
          return response.data.data.data
        }
        // Direct array
        else if (Array.isArray(response.data.data)) {
          console.log('🔴 [SERVICE] ✅ Found tables in data.data (array):', response.data.data.length)
          return response.data.data
        }
      }
      
      console.warn('🔴 [SERVICE] ⚠️ Unexpected response structure - returning empty array')
      return []
    } catch (error: any) {
      console.error('🔴 [SERVICE] ❌ Error caught:', error)
      console.error('🔴 [SERVICE] Error response:', error.response)
      console.error('🔴 [SERVICE] Error response data:', error.response?.data)
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
   * Download QR code image as PNG file attachment using backend endpoint
   */
  async downloadQRCode(table: any): Promise<void> {
    if (!table || !table.id) return
    const fileName = `Table_${table.table_number}_QR.png`

    try {
      console.log('📥 Downloading QR code for table:', table.table_number, table.id)
      // Primary Method: Call backend endpoint via authorized axios client
      const response = await axios.get(`/manager/restaurant-tables/${table.id}/download-qr`, {
        responseType: 'blob',
      })

      const blob = new Blob([response.data], { type: 'image/png' })
      const blobUrl = window.URL.createObjectURL(blob)
      const link = document.createElement('a')
      link.href = blobUrl
      link.download = fileName
      document.body.appendChild(link)
      link.click()
      document.body.removeChild(link)
      setTimeout(() => window.URL.revokeObjectURL(blobUrl), 10000)
    } catch (err) {
      console.warn('API download endpoint failed, attempting canvas fallback download:', err)
      if (table.qr_code_url) {
        const img = new Image()
        img.crossOrigin = 'anonymous'
        img.onload = () => {
          const canvas = document.createElement('canvas')
          canvas.width = img.naturalWidth || 500
          canvas.height = img.naturalHeight || 500
          const ctx = canvas.getContext('2d')
          if (ctx) {
            ctx.fillStyle = '#FFFFFF'
            ctx.fillRect(0, 0, canvas.width, canvas.height)
            ctx.drawImage(img, 0, 0)
            canvas.toBlob((blob) => {
              if (blob) {
                const blobUrl = URL.createObjectURL(blob)
                const link = document.createElement('a')
                link.href = blobUrl
                link.download = fileName
                document.body.appendChild(link)
                link.click()
                document.body.removeChild(link)
                setTimeout(() => URL.revokeObjectURL(blobUrl), 10000)
              }
            }, 'image/png')
          }
        }
        img.src = table.qr_code_url
      }
    }
  }
}

export default restaurantTableService

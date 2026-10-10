import axios from '../axios'
import type {
  RestaurantTable,
  CreateTableRequest,
  UpdateTableRequest,
  TableFilters,
  TableStatistics,
  PaginatedTablesResponse,
} from '@/types/restaurantTable'

export const restaurantTableService = {
  async getTables(
    filters?: TableFilters,
  ): Promise<{ success: boolean; data: PaginatedTablesResponse }> {
    try {
      const response = await axios.get('/manager/restaurant-tables', { params: filters })
      return response.data
    } catch (error) {
      console.error('[RestaurantTableService] Error fetching tables:', error)
      throw error
    }
  },

  async getAllTables(): Promise<RestaurantTable[]> {
    try {
      const response = await axios.get('/manager/restaurant-tables', {
        params: {
          per_page: 1000,
          is_active: true,
        },
      })

      if (response.data?.success && response.data?.data) {
        if (response.data.data.data && Array.isArray(response.data.data.data)) {
          return response.data.data.data
        } else if (Array.isArray(response.data.data)) {
          return response.data.data
        }
      }

      return []
    } catch (error: any) {
      console.error('[RestaurantTableService] Error fetching all tables:', error)
      throw error
    }
  },

  async getTableById(id: string): Promise<{ success: boolean; data: RestaurantTable }> {
    const response = await axios.get(`/manager/restaurant-tables/${id}`)
    return response.data
  },

  async createTable(
    data: CreateTableRequest,
  ): Promise<{ success: boolean; message: string; data: RestaurantTable }> {
    try {
      const response = await axios.post('/manager/restaurant-tables', data)
      return response.data
    } catch (error: any) {
      console.error('[RestaurantTableService] Error creating table:', error)
      if (error.response?.data?.errors) {
        const err: any = new Error(error.response.data.message || 'Validation failed')
        err.errors = error.response.data.errors
        err.response = error.response
        throw err
      }
      throw error
    }
  },

  async updateTable(
    id: string,
    data: UpdateTableRequest,
  ): Promise<{ success: boolean; message: string; data: RestaurantTable }> {
    const response = await axios.put(`/manager/restaurant-tables/${id}`, data)
    return response.data
  },

  async deleteTable(id: string): Promise<{ success: boolean; message: string }> {
    const response = await axios.delete(`/manager/restaurant-tables/${id}`)
    return response.data
  },

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

  async getStatistics(): Promise<{ success: boolean; data: TableStatistics }> {
    const response = await axios.get('/manager/restaurant-tables/statistics')
    return response.data
  },

  async downloadQRCode(table: any): Promise<void> {
    if (!table || !table.id) return
    const fileName = `Table_${table.table_number}_QR.png`

    try {
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
      console.error(
        '[RestaurantTableService] Download QR API failed, attempting canvas fallback:',
        err,
      )
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
  },
}

export default restaurantTableService

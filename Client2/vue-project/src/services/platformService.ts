import api from './axios'

export interface Hotel {
  id: string
  name: string
  slug: string
  email?: string
  phone?: string
  address?: string
  city?: string
  country?: string
  logo?: string
  timezone?: string
  currency?: string
  status: 'active' | 'inactive' | 'suspended' | 'archived'
  created_at: string
  updated_at: string
  rooms_count?: number
  reservations_count?: number
  guests_count?: number
  orders_count?: number
  staff_count?: number
  revenue_total?: number
  admin_name?: string
  admin_email?: string
  admins?: Array<{
    id: string
    name: string
    email?: string
    phone?: string
    is_active: boolean
  }>
}

export interface PlatformStatistics {
  total_hotels: number
  active_hotels: number
  inactive_hotels: number
  suspended_hotels: number
  total_users: number
  total_hotel_admins: number
  total_staff: number
  total_guests: number
  total_rooms: number
  total_reservations: number
  total_orders: number
  total_payments: number
  total_revenue: number
  recent_hotels: Array<any>
  top_hotels: Array<any>
}

export interface HotelFilterParams {
  search?: string
  status?: string
  city?: string
  page?: number
  per_page?: number
}

export interface CreateHotelPayload {
  name: string
  slug: string
  email?: string
  phone?: string
  address?: string
  city?: string
  country?: string
  logo?: string
  timezone?: string
  currency?: string
  status?: 'active' | 'inactive' | 'suspended' | 'archived'
  admin_user_id?: string
  admin_email?: string
  admin_first_name?: string
  admin_last_name?: string
  admin_password?: string
}

export const platformService = {
  async getStatistics(): Promise<PlatformStatistics> {
    const response = await api.get('/platform/statistics')
    return response.data.data
  },

  async getHotels(params?: HotelFilterParams): Promise<{ data: Hotel[]; current_page: number; last_page: number; total: number }> {
    const response = await api.get('/platform/hotels', { params })
    return response.data.data
  },

  async getHotel(id: string): Promise<Hotel> {
    const response = await api.get(`/platform/hotels/${id}`)
    return response.data.data
  },

  async createHotel(payload: CreateHotelPayload): Promise<Hotel> {
    const response = await api.post('/platform/hotels', payload)
    return response.data.data
  },

  async updateHotel(id: string, payload: Partial<CreateHotelPayload>): Promise<Hotel> {
    const response = await api.put(`/platform/hotels/${id}`, payload)
    return response.data.data
  },

  async updateHotelStatus(id: string, status: 'active' | 'inactive' | 'suspended' | 'archived'): Promise<Hotel> {
    const response = await api.patch(`/platform/hotels/${id}/status`, { status })
    return response.data.data
  },

  async archiveHotel(id: string): Promise<Hotel> {
    const response = await api.post(`/platform/hotels/${id}/archive`)
    return response.data.data
  },

  async deleteHotel(id: string, confirmName: string): Promise<{ success: boolean; message: string }> {
    const response = await api.delete(`/platform/hotels/${id}`, {
      data: { confirm_name: confirmName }
    })
    return response.data
  },

  async getHotelAdmins(id: string): Promise<any[]> {
    const response = await api.get(`/platform/hotels/${id}/admins`)
    return response.data.data
  },

  async assignHotelAdmin(id: string, payload: any): Promise<any> {
    const response = await api.post(`/platform/hotels/${id}/admins`, payload)
    return response.data
  },

  async removeHotelAdmin(id: string, userId: string): Promise<any> {
    const response = await api.delete(`/platform/hotels/${id}/admins/${userId}`)
    return response.data
  },

  async getAllAdmins(params?: any): Promise<any> {
    const response = await api.get('/platform/admins', { params })
    return response.data.data
  },

  async createHotelAdmin(payload: {
    first_name: string
    last_name: string
    email: string
    phone?: string
    hotel_id: string
    role?: string
    status?: 'active' | 'inactive'
  }): Promise<any> {
    const response = await api.post('/platform/hotel-admins', payload)
    return response.data
  },

  async resetAdminPassword(userId: string): Promise<any> {
    const response = await api.post(`/platform/hotel-admins/${userId}/reset-password`)
    return response.data
  },

  async resendAdminPassword(userId: string): Promise<any> {
    const response = await api.post(`/platform/hotel-admins/${userId}/resend-password`)
    return response.data
  },

  async toggleAdminStatus(membershipId: string): Promise<any> {
    const response = await api.patch(`/platform/hotel-admins/${membershipId}/toggle-status`)
    return response.data
  },

  async enterHotelViewMode(hotelId: string): Promise<any> {
    const response = await api.post(`/platform/hotels/${hotelId}/enter-view`)
    return response.data
  },

  async exitHotelViewMode(): Promise<any> {
    const response = await api.post('/platform/hotels/exit-view')
    return response.data
  },

  async getAllUsers(params?: any): Promise<any> {
    const response = await api.get('/platform/users', { params })
    return response.data.data
  },

  async getAuditLogs(params?: any): Promise<any> {
    const response = await api.get('/platform/audit-logs', { params })
    return response.data.data
  }
}

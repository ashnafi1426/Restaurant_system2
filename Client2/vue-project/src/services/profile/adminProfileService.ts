import axios from '../axios'

export interface AdminProfile {
  id: string
  first_name: string
  last_name: string
  full_name: string
  email: string
  phone: string | null
  role: string
  is_active: boolean
  administrator: {
    id: string
    employee_code: string | null
    department: string | null
    bio: string | null
    profile_photo: string | null
    hire_date: string | null
    status: string
    created_at: string
    updated_at: string
  } | null
  created_at: string
  updated_at: string
}

export interface AdminStats {
  total_users: number
  total_orders: number
  total_revenue: number
  active_reservations: number
  total_rooms: number
}

export interface UpdateProfileData {
  first_name?: string
  last_name?: string
  phone?: string
  department?: string
  bio?: string
}

export interface ChangePasswordData {
  current_password: string
  new_password: string
  new_password_confirmation: string
}

export const adminProfileService = {
  async getProfile(): Promise<AdminProfile> {
    const response = await axios.get('/admin/profile')
    return response.data.data
  },

  async updateProfile(data: UpdateProfileData): Promise<AdminProfile> {
    const response = await axios.put('/admin/profile', data)
    return response.data.data
  },

  async uploadPhoto(file: File): Promise<{ profile_photo: string; photo_url: string }> {
    const formData = new FormData()
    formData.append('photo', file)
    const response = await axios.post('/admin/profile/photo', formData, {
      headers: { 'Content-Type': 'multipart/form-data' }
    })
    return response.data.data
  },

  async changePassword(data: ChangePasswordData): Promise<void> {
    await axios.post('/admin/profile/change-password', data)
  },

  async getStats(): Promise<AdminStats> {
    const response = await axios.get('/admin/profile/stats')
    return response.data.data
  }
}

import axios from '../axios'

export interface ManagerProfile {
  id: string
  first_name: string
  last_name: string
  full_name: string
  email: string
  phone: string | null
  role: string
  is_active: boolean
  manager: {
    id: string
    employee_code: string | null
    department: string | null
    bio: string | null
    profile_photo: string | null
    hire_date: string | null
    status: string
    permissions: string[] | null
    created_at: string
    updated_at: string
  } | null
  created_at: string
  updated_at: string
}

export interface ManagerStats {
  total_employees_managed: number
  total_orders_today: number
  total_revenue_today: number
  pending_complaints: number
  active_reservations: number
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

export const managerProfileService = {
  async getProfile(): Promise<ManagerProfile> {
    const response = await axios.get('/manager/profile')
    return response.data.data
  },

  async updateProfile(data: UpdateProfileData): Promise<ManagerProfile> {
    const response = await axios.put('/manager/profile', data)
    return response.data.data
  },

  async uploadPhoto(file: File): Promise<{ profile_photo: string; photo_url: string }> {
    const formData = new FormData()
    formData.append('photo', file)
    const response = await axios.post('/manager/profile/photo', formData, {
      headers: { 'Content-Type': 'multipart/form-data' }
    })
    return response.data.data
  },

  async changePassword(data: ChangePasswordData): Promise<void> {
    await axios.post('/manager/profile/change-password', data)
  },

  async getStats(): Promise<ManagerStats> {
    const response = await axios.get('/manager/profile/stats')
    return response.data.data
  }
}

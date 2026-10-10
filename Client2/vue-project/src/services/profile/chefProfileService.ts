import axios from '../axios'

export interface ChefProfile {
  id: string
  first_name: string
  last_name: string
  full_name: string
  email: string
  phone: string | null
  role: string
  is_active: boolean
  chef: {
    id: string
    employee_code: string | null
    specialization: string | null
    shift: string | null
    experience_years: number | null
    rank: string
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

export interface ChefStats {
  total_orders_prepared: number
  orders_today: number
  orders_in_progress: number
  completed_orders_today: number
}

export interface UpdateProfileData {
  first_name?: string
  last_name?: string
  phone?: string
  specialization?: string
  shift?: string
  experience_years?: number
  bio?: string
}

export interface ChangePasswordData {
  current_password: string
  new_password: string
  new_password_confirmation: string
}

export const chefProfileService = {
  async getProfile(): Promise<ChefProfile> {
    const response = await axios.get('/chef/profile')
    return response.data.data
  },

  async updateProfile(data: UpdateProfileData): Promise<ChefProfile> {
    const response = await axios.put('/chef/profile', data)
    return response.data.data
  },

  async uploadPhoto(file: File): Promise<{ profile_photo: string; photo_url: string }> {
    const formData = new FormData()
    formData.append('photo', file)
    const response = await axios.post('/chef/profile/photo', formData, {
      headers: { 'Content-Type': 'multipart/form-data' },
    })
    return response.data.data
  },

  async changePassword(data: ChangePasswordData): Promise<void> {
    await axios.post('/chef/profile/change-password', data)
  },

  async getStats(): Promise<ChefStats> {
    const response = await axios.get('/chef/profile/stats')
    return response.data.data
  },

  async updateStatus(status: 'active' | 'on_break' | 'off_duty'): Promise<void> {
    await axios.post('/chef/profile/status', { status })
  },
}

import axios from '../axios'

export interface WaiterProfile {
  id: string
  name: string
  email: string
  phone: string | null
  role: string
  waiter: {
    id: string
    employee_code: string | null
    manager_id: string | null
    phone: string | null
    shift: string
    status: string
    profile_photo: string | null
    bio: string | null
    created_at: string
    updated_at: string
  } | null
  created_at: string
  updated_at: string
}

export interface WaiterStats {
  total_deliveries_today: number
  total_deliveries_week: number
  average_rating: number
  pending_assignments: number
  completed_today: number
}

export interface UpdateProfileData {
  name?: string
  phone?: string
  shift?: string
  bio?: string
}

export interface ChangePasswordData {
  current_password: string
  new_password: string
  new_password_confirmation: string
}

export const waiterProfileService = {
  async getProfile(): Promise<WaiterProfile> {
    const response = await axios.get('/waiter/profile')
    return response.data.data
  },

  async updateProfile(data: UpdateProfileData): Promise<WaiterProfile> {
    const response = await axios.put('/waiter/profile', data)
    return response.data.data
  },

  async uploadPhoto(file: File): Promise<{ profile_photo: string; photo_url: string }> {
    const formData = new FormData()
    formData.append('photo', file)
    const response = await axios.post('/waiter/profile/photo', formData, {
      headers: { 'Content-Type': 'multipart/form-data' },
    })
    return response.data.data
  },

  async changePassword(data: ChangePasswordData): Promise<void> {
    await axios.post('/waiter/profile/change-password', data)
  },

  async getStats(): Promise<WaiterStats> {
    const response = await axios.get('/waiter/profile/stats')
    return response.data.data
  },
}

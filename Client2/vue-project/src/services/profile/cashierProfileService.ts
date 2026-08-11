import axios from '../axios'

export interface CashierProfile {
  id: string
  first_name: string
  last_name: string
  full_name: string
  email: string
  phone: string | null
  role: string
  is_active: boolean
  cashier: {
    id: string
    employee_code: string | null
    shift: string | null
    register_number: string | null
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

export interface CashierStats {
  total_transactions_today: number
  total_amount_collected_today: number
  pending_payments: number
  completed_payments: number
}

export interface UpdateProfileData {
  first_name?: string
  last_name?: string
  phone?: string
  shift?: string
  register_number?: string
  bio?: string
}

export interface ChangePasswordData {
  current_password: string
  new_password: string
  new_password_confirmation: string
}

export const cashierProfileService = {
  async getProfile(): Promise<CashierProfile> {
    const response = await axios.get('/cashier/profile')
    return response.data.data
  },

  async updateProfile(data: UpdateProfileData): Promise<CashierProfile> {
    const response = await axios.put('/cashier/profile', data)
    return response.data.data
  },

  async uploadPhoto(file: File): Promise<{ profile_photo: string; photo_url: string }> {
    const formData = new FormData()
    formData.append('photo', file)
    const response = await axios.post('/cashier/profile/photo', formData, {
      headers: { 'Content-Type': 'multipart/form-data' }
    })
    return response.data.data
  },

  async changePassword(data: ChangePasswordData): Promise<void> {
    await axios.post('/cashier/profile/change-password', data)
  },

  async getStats(): Promise<CashierStats> {
    const response = await axios.get('/cashier/profile/stats')
    return response.data.data
  },

  async updateStatus(status: 'active' | 'on_break' | 'off_duty'): Promise<void> {
    await axios.post('/cashier/profile/status', { status })
  }
}

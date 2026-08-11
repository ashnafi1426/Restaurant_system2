import axios from '../axios'

export interface ReceptionistProfile {
  id: string
  first_name: string
  last_name: string
  full_name: string
  email: string
  phone: string | null
  role: string
  is_active: boolean
  receptionist: {
    id: string
    employee_code: string | null
    shift: string | null
    desk_number: string | null
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

export interface ReceptionistStats {
  total_check_ins_today: number
  total_check_outs_today: number
  active_guests: number
  pending_reservations: number
  confirmed_reservations: number
  available_rooms: number
}

export interface UpdateProfileData {
  first_name?: string
  last_name?: string
  phone?: string
  shift?: string
  desk_number?: string
  bio?: string
}

export interface ChangePasswordData {
  current_password: string
  new_password: string
  new_password_confirmation: string
}

export const receptionistProfileService = {
  async getProfile(): Promise<ReceptionistProfile> {
    const response = await axios.get('/receptionist/profile')
    return response.data.data
  },

  async updateProfile(data: UpdateProfileData): Promise<ReceptionistProfile> {
    const response = await axios.put('/receptionist/profile', data)
    return response.data.data
  },

  async uploadPhoto(file: File): Promise<{ profile_photo: string; photo_url: string }> {
    const formData = new FormData()
    formData.append('photo', file)
    const response = await axios.post('/receptionist/profile/photo', formData, {
      headers: { 'Content-Type': 'multipart/form-data' }
    })
    return response.data.data
  },

  async changePassword(data: ChangePasswordData): Promise<void> {
    await axios.post('/receptionist/profile/change-password', data)
  },

  async getStats(): Promise<ReceptionistStats> {
    const response = await axios.get('/receptionist/profile/stats')
    return response.data.data
  },

  async updateStatus(status: 'active' | 'on_break' | 'off_duty'): Promise<void> {
    await axios.post('/receptionist/profile/status', { status })
  }
}

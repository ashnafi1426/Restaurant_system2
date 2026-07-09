import type {
  Guest,
  GuestForm,
  GuestListResponse,
  GuestResponse,
  GuestFilter,
} from '../types/guest'
import api from '../api/auth'

api.interceptors.request.use((config) => {
  const token = localStorage.getItem('token')
  if (token) {
    config.headers.Authorization = `Bearer ${token}`
  }
  return config
})

export const getGuests = async (filters: GuestFilter = {}): Promise<GuestListResponse> => {
  const response = await api.get('/guests', {
    params: filters,
  })
  return response.data
}

export const getGuest = async (id: string): Promise<GuestResponse> => {
  const response = await api.get(`/guests/${id}`)
  return response.data
}

export const createGuest = async (data: GuestForm): Promise<GuestResponse> => {
  const response = await api.post('/guests', data)
  return response.data
}

export const updateGuest = async (id: string, data: GuestForm): Promise<GuestResponse> => {
  const response = await api.put(`/guests/${id}`, data)
  return response.data
}

export const deleteGuest = async (id: string): Promise<void> => {
  await api.delete(`/guests/${id}`)
}

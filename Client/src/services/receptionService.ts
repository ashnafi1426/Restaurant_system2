import api from '@/api/auth'

export async function getReceptionDashboard() {
  try {
    const token = localStorage.getItem('token')
    const user = localStorage.getItem('user')

    if (!token || !user) {
      throw new Error('Not authenticated - no token or user found in localStorage')
    }

    const response = await api.get('/reception/dashboard')
    return response.data
  } catch (error: any) {
    console.error('[ReceptionService] Error fetching reception dashboard:', error)
    throw error
  }
}

import api from '@/api/auth'

export async function getReceptionDashboard() {
  try {
    console.log(' [RECEPTION SERVICE] Fetching reception dashboard')
    const token = localStorage.getItem('token')
    const user = localStorage.getItem('user')

    console.log(' [RECEPTION SERVICE] Token present:', !!token)
    console.log(' [RECEPTION SERVICE] User present:', !!user)

    if (!token || !user) {
      throw new Error('Not authenticated - no token or user found in localStorage')
    }

    const response = await api.get('/reception/dashboard')
    console.log(' [RECEPTION SERVICE] Dashboard fetched successfully')
    return response.data
  } catch (error: any) {
    console.error(' [RECEPTION SERVICE] Failed to fetch reception dashboard:', error)
    console.error('   - Error message:', error.message)
    console.error('   - Error status:', error.response?.status)
    console.error('   - Error data:', error.response?.data)
    throw error
  }
}

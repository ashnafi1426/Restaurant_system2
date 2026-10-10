import api from '../api/auth'

export const getDashboard = async (params: Record<string, any> = {}) => {
  const response = await api.get(`/dashboard`, {
    params,
    headers: {
      Authorization: `Bearer ${localStorage.getItem('token')}`,
    },
  })

  return response.data
}

export const getUnifiedDashboard = getDashboard

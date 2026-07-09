import api from '../api/auth'

export const getDashboard = async () => {
  const response = await api.get(`/admin/dashboard`, {
    headers: {
      Authorization: `Bearer ${localStorage.getItem('token')}`,
    },
  })

  return response.data
}

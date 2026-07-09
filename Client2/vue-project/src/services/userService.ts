import api from '../api/auth'
import type { User } from '../types/user'

export default {
  getUsers(params?: any) {
    return api.get('/users', {
      params,
    })
  },

  getUser(id: string) {
    return api.get(`/users/${id}`)
  },

  createUser(user: User) {
    return api.post('/users', user)
  },

  updateUser(id: string, user: User) {
    return api.put(`/users/${id}`, user)
  },

  deleteUser(id: string) {
    return api.delete(`/users/${id}`)
  },

  toggleStatus(id: string) {
    return api.patch(`/users/${id}/toggle-status`)
  },
}

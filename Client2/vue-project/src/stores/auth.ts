import { defineStore } from 'pinia'
import api from '../api/auth'

export const useAuthStore = defineStore('auth', {
  state: () => ({
    token: localStorage.getItem('token') || '',
    user: JSON.parse(localStorage.getItem('user') || 'null'),
  }),

  actions: {
    async login(email: string, password: string) {
      const response = await api.post('/login', {
        email,
        password,
      })
      this.token = response.data.token
      this.user = response.data.user

      localStorage.setItem('token', response.data.token)
      localStorage.setItem('user', JSON.stringify(response.data.user))
    },

    logout() {
      this.token = ''
      this.user = null

      localStorage.removeItem('token')
      localStorage.removeItem('user')
    },
  },
})

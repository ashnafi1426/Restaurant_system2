import { defineStore } from 'pinia'
import api from '../api/auth'

export const useAuthStore = defineStore('auth', {
  state: () => ({
    token: localStorage.getItem('token') || '',
    user: JSON.parse(localStorage.getItem('user') || 'null'),
  }),

  actions: {
    async login(email: string, password: string) {
      try {
        const response = await api.post('/login', {
          email,
          password,
        })

        if (response.data.token && response.data.user) {
          this.token = response.data.token
          this.user = response.data.user
          localStorage.setItem('token', response.data.token)
          localStorage.setItem('user', JSON.stringify(response.data.user))
          return { success: true, user: response.data.user }
        } else {
          throw new Error('Invalid response from server')
        }
      } catch (error: any) {
        console.error('[AUTH] Login error:', error)

        let errorMessage = 'Login failed. Please try again.'

        if (error.code === 'ECONNABORTED') {
          errorMessage = 'Connection timeout. Server may be down.'
        } else if (error.response?.status === 401) {
          errorMessage = error.response?.data?.message || 'Invalid email or password'
        } else if (error.response?.status === 403) {
          errorMessage = 'Account is disabled'
        } else if (error.response?.status === 422) {
          errorMessage = 'Invalid input. Check email and password.'
        } else if (!error.response) {
          errorMessage = 'Cannot connect to server. Check if it is running.'
        }

        throw new Error(errorMessage)
      }
    },

    logout() {
      this.token = ''
      this.user = null

      localStorage.removeItem('token')
      localStorage.removeItem('user')
    },
  },
})

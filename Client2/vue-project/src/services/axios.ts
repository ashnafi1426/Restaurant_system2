import axios from 'axios'

// Create axios instance for authenticated requests
export const axiosInstance = axios.create({
  baseURL: 'http://127.0.0.1:8000/api',
  headers: {
    'Content-Type': 'application/json',
  },
  timeout: 30000,
})

// Add request interceptor for authenticated requests
axiosInstance.interceptors.request.use(
  (config) => {
    const token = localStorage.getItem('token')
    if (token) {
      config.headers.Authorization = `Bearer ${token}`
    }
    return config
  },
  (error) => {
    return Promise.reject(error)
  }
)

// Add response interceptor
axiosInstance.interceptors.response.use(
  (response) => response,
  (error) => {
    // Don't redirect to login for guest-reviews endpoint (guests can't login)
    if (error.response?.status === 401) {
      const url = error.config?.url || ''
      // Only redirect for authenticated endpoints, not guest endpoints
      if (!url.includes('/guest-reviews') && !url.includes('/review-stats')) {
        localStorage.removeItem('token')
        localStorage.removeItem('user')
        window.location.href = '/login'
      }
    }
    return Promise.reject(error)
  }
)

// Create axios instance for public requests (no auth needed)
export const publicAxios = axios.create({
  baseURL: 'http://127.0.0.1:8000/api',
  headers: {
    'Content-Type': 'application/json',
  },
  timeout: 30000,
})

export default axiosInstance

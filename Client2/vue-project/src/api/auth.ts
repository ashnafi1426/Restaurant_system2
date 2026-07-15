import axios from 'axios'

const api = axios.create({
  baseURL: 'http://127.0.0.1:8000/api',
  headers: {
    'Content-Type': 'application/json',
  },
  timeout: 60000, // Increased to 60 seconds for email operations
})

api.interceptors.request.use(
  (config) => {
    const token = localStorage.getItem('token')
    const user = localStorage.getItem('user')

    console.log(' [API INTERCEPTOR] Token from localStorage:', token ? '✓ Present' : '✗ Missing')
    console.log(' [API INTERCEPTOR] User from localStorage:', user ? '✓ Present' : '✗ Missing')

    if (token) {
      config.headers.Authorization = `Bearer ${token}`
      console.log(
        ' [API INTERCEPTOR] Authorization header set:',
        `Bearer ${token.substring(0, 20)}...`,
      )
    } else {
      console.warn(' [API INTERCEPTOR] No token found - request will fail with 401')
    }

    if (user) {
      const userData = JSON.parse(user)
      console.log(' [API INTERCEPTOR] Current User Role:', userData.role)
    }

    console.log(' [API INTERCEPTOR] Request to:', config.url)
    console.log(' [API INTERCEPTOR] Request headers:', config.headers)

    return config
  },
  (error) => {
    console.error('[API INTERCEPTOR] Request error:', error)
    return Promise.reject(error)
  },
)

api.interceptors.response.use(
  (response) => {
    console.log('[API INTERCEPTOR] Response received:', response.status)
    return response
  },
  (error) => {
    if (error.code === 'ECONNABORTED') {
      console.error('[API INTERCEPTOR] Request timeout after 30s')
    } else if (!error.response) {
      console.error('[API INTERCEPTOR] No response from server:', error.message)
    } else {
      console.error('[API INTERCEPTOR] Response error:', error.response.status)
    }
    return Promise.reject(error)
  },
)

export default api

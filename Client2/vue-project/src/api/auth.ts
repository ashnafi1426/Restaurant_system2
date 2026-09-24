import axios from 'axios'
const api = axios.create({
  baseURL: 'http://127.0.0.1:8000/api',
  headers: {
    'Content-Type': 'application/json',
  },
  timeout: 60000,
})

api.interceptors.request.use(
  (config) => {
    const token = localStorage.getItem('token')
    if (token) {
      config.headers.Authorization = `Bearer ${token}`
    }

    const currentHotelRaw = localStorage.getItem('current_hotel')
    if (currentHotelRaw) {
      try {
        const currentHotel = JSON.parse(currentHotelRaw)
        if (currentHotel?.id) {
          config.headers['X-Hotel-ID'] = currentHotel.id
        }
      } catch (e) {
        console.error('[API] Error parsing current_hotel from storage:', e)
      }
    }

    if (config.data instanceof FormData) {
      delete config.headers['Content-Type']
    } else {
      config.headers['Content-Type'] = 'application/json'
    }

    return config
  },
  (error) => {
    return Promise.reject(error)
  },
)

api.interceptors.response.use(
  (response) => {
    return response
  },
  (error) => {
    if (error.response?.status === 401) {
      localStorage.removeItem('token')
      localStorage.removeItem('user')
    }
    return Promise.reject(error)
  },
)

export default api

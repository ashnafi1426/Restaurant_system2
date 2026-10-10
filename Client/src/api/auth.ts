import axios from 'axios'

const rawApiBase = import.meta.env.VITE_API_BASE_URL || 'http://127.0.0.1:8000'
const apiBaseUrl = rawApiBase.endsWith('/api') ? rawApiBase : `${rawApiBase}/api`

const api = axios.create({
  baseURL: apiBaseUrl,
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

    // Resolve Hotel ID with fallbacks
    let hotelId: string | null = null
    const currentHotelRaw = localStorage.getItem('current_hotel')
    if (currentHotelRaw) {
      try {
        const currentHotel = JSON.parse(currentHotelRaw)
        if (currentHotel?.id) hotelId = String(currentHotel.id)
        else if (currentHotel?.hotel_id) hotelId = String(currentHotel.hotel_id)
      } catch (e) {
        console.error('[API] Error parsing current_hotel from storage:', e)
      }
    }
    if (!hotelId) {
      const hotelIdRaw = localStorage.getItem('hotel_id')
      if (hotelIdRaw) hotelId = String(hotelIdRaw)
    }
    if (!hotelId) {
      const userRaw = localStorage.getItem('user')
      if (userRaw) {
        try {
          const user = JSON.parse(userRaw)
          if (user?.hotel_id) hotelId = String(user.hotel_id)
        } catch (e) {
          console.error('[API] Error parsing user from storage:', e)
        }
      }
    }
    if (!config.headers['X-Hotel-ID'] && !config.headers['x-hotel-id'] && hotelId) {
      config.headers['X-Hotel-ID'] = hotelId
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

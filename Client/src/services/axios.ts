import axios from 'axios'

const rawApiBase = import.meta.env.VITE_API_BASE_URL || 'http://127.0.0.1:8000'
const apiBaseUrl = rawApiBase.endsWith('/api') ? rawApiBase : `${rawApiBase}/api`

export const axiosInstance = axios.create({
  baseURL: apiBaseUrl,
  headers: {
    'Content-Type': 'application/json',
  },
  timeout: 30000,
})

const resolveHotelId = (): string | null => {
  for (const key of ['current_hotel', 'guest_current_hotel']) {
    const raw = localStorage.getItem(key)
    if (raw) {
      try {
        const currentHotel = JSON.parse(raw)
        if (currentHotel?.id) return String(currentHotel.id)
      } catch (e) {
        console.error(`[Axios] Error parsing ${key} from storage:`, e)
      }
    }
  }

  for (const key of ['hotel_id', 'guest_hotel_id', 'active_hotel_id']) {
    const raw = localStorage.getItem(key)
    if (raw && raw !== 'undefined' && raw !== 'null') return String(raw)
  }

  const userRaw = localStorage.getItem('user')
  if (userRaw) {
    try {
      const user = JSON.parse(userRaw)
      if (user?.hotel_id) return String(user.hotel_id)
    } catch (e) {
      console.error('[Axios] Error parsing user from storage:', e)
    }
  }
  return null
}

axiosInstance.interceptors.request.use(
  (config) => {
    const token = localStorage.getItem('token')
    if (token) {
      config.headers.Authorization = `Bearer ${token}`
    }

    const hotelId = resolveHotelId()
    if (hotelId) {
      config.headers['X-Hotel-ID'] = hotelId
    }

    return config
  },
  (error) => {
    return Promise.reject(error)
  },
)

axiosInstance.interceptors.response.use(
  (response) => response,
  (error) => {
    if (error.response?.status === 401) {
      const currentPath = window.location.pathname
      const isPublicPath =
        currentPath.startsWith('/guest') ||
        currentPath.startsWith('/order') ||
        currentPath.startsWith('/payment') ||
        currentPath === '/login'

      if (!isPublicPath) {
        localStorage.removeItem('token')
        localStorage.removeItem('user')
        window.location.href = '/login'
      }
    }
    return Promise.reject(error)
  },
)

export const publicAxios = axios.create({
  baseURL: apiBaseUrl,
  headers: {
    'Content-Type': 'application/json',
  },
  timeout: 30000,
})

publicAxios.interceptors.request.use(
  (config) => {
    const hotelId = resolveHotelId()
    if (hotelId) {
      config.headers['X-Hotel-ID'] = hotelId
    }
    return config
  },
  (error) => {
    return Promise.reject(error)
  },
)

export default axiosInstance

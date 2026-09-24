import api from '../api/auth'
import { publicAxios } from './axios'

function getGuestHotelHeaders() {
  try {
    const hotelData = localStorage.getItem('guest_current_hotel')
    if (hotelData) {
      const hotel = JSON.parse(hotelData)
      if (hotel && hotel.id) {
        return { 'X-Hotel-ID': String(hotel.id) }
      }
    }
  } catch (err: any) {
    console.error('[MenuService] Error parsing guest_current_hotel:', err)
  }
  return {}
}

export default {
  getMenus(params: any = {}) {
    return api.get('/menu-items', { params })
  },

  getMenuItems(params: any = {}) {
    return publicAxios.get('/guest/menu/items', { params, headers: getGuestHotelHeaders() })
  },

  getPublicMenus(params: any = {}) {
    return publicAxios.get('/guest/menu/items', { params, headers: getGuestHotelHeaders() })
  },

  createMenu(data: any) {
    if (data instanceof FormData) {
      return api.post('/menu-items', data, {
        headers: {
          'Content-Type': 'multipart/form-data',
        },
        transformRequest: [(d) => d],
      })
    }

    if (data?.image_url) {
      return api.post('/menu-items', data)
    }

    return api.post('/menu-items', data)
  },

  updateMenu(id: string, data: any) {
    if (data instanceof FormData) {
      return api.post(`/menu-items/${id}?_method=PUT`, data, {
        headers: {
          'Content-Type': 'multipart/form-data',
        },
        transformRequest: [(d) => d],
      })
    }
    if (data?.image_url) {
      return api.put(`/menu-items/${id}`, data)
    }

    return api.put(`/menu-items/${id}`, data)
  },

  deleteMenu(id: string) {
    const endpoint = `/menu-items/${id}`
    return api.delete(endpoint)
  },

  toggleStatus(id: string) {
    const endpoint = `/menu-items/${id}/toggle-availability`
    return api.patch(endpoint)
  },

  statistics() {
    return api.get('/menu-items/statistics')
  },

  categories() {
    return api.get('/menu-categories')
  },
}

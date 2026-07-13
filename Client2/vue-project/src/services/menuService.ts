import api from '../api/auth'

export default {
  getMenus(params: any) {
    return api.get('/menu-items', { params })
  },

  createMenu(data: any) {
    console.log('createMenu called with:', data instanceof FormData ? 'FormData' : 'JSON')
    console.log('Payload:', data)

    // FormData for file uploads
    if (data instanceof FormData) {
      console.log('Sending as FormData with multipart/form-data')
      return api.post('/menu-items', data, {
        headers: {
          'Content-Type': 'multipart/form-data',
        },
        transformRequest: [(d) => d], // Don't transform FormData
      })
    }

    // Check if image_url is present (URL-based image)
    if (data?.image_url) {
      console.log('🔗 Sending with image_url:', data.image_url)
      return api.post('/menu-items', data)
    }

    // Regular JSON submission
    console.log('ℹ️ Sending as JSON')
    return api.post('/menu-items', data)
  },

  updateMenu(id: string, data: any) {
    console.log('updateMenu called with ID:', id)
    console.log('Payload:', data instanceof FormData ? 'FormData' : 'JSON')

    // FormData for file uploads
    if (data instanceof FormData) {
      console.log('✅ Sending PUT as FormData (POST with _method=PUT)')
      return api.post(`/menu-items/${id}?_method=PUT`, data, {
        headers: {
          'Content-Type': 'multipart/form-data',
        },
        transformRequest: [(d) => d], // Don't transform FormData
      })
    }
    // Check if image_url is present (URL-based image)
    if (data?.image_url) {
      console.log('🔗 Updating with image_url:', data.image_url)
      return api.put(`/menu-items/${id}`, data)
    }

    // Regular JSON submission
    console.log('ℹ️ Sending PUT as JSON')
    return api.put(`/menu-items/${id}`, data)
  },

  deleteMenu(id: string) {
    const endpoint = `/menu-items/${id}`
    console.log('🗑️ deleteMenu called for ID:', id)
    console.log('🔗 Endpoint:', endpoint)
    return api.delete(endpoint)
  },

  toggleStatus(id: string) {
    const endpoint = `/menu-items/${id}/toggle-availability`
    console.log('🔄 toggleStatus called for ID:', id)
    console.log('🔗 Endpoint:', endpoint)
    return api.patch(endpoint)
  },

  statistics() {
    return api.get('/menu-items/statistics')
  },

  categories() {
    return api.get('/menu-categories')
  },
}

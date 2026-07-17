import api from '../api/auth'

export interface Category {
  id: string
  name: string
  slug: string
  description?: string
  icon?: string
  display_order: number
  is_active: boolean
  menu_items_count?: number
}

export interface CreateCategoryData {
  name: string
  description?: string
  icon?: string
  display_order?: number
  is_active?: boolean
}

export interface UpdateCategoryData extends Partial<CreateCategoryData> {}

export default {
  /**
   * Get all categories
   */
  getCategories(params: any = {}) {
    return api.get('/categories', { params })
  },
  getCategory(id: string) {
    return api.get(`/categories/${id}`)
  },
  createCategory(data: CreateCategoryData) {
    return api.post('/categories', data)
  },
  updateCategory(id: string, data: UpdateCategoryData) {
    return api.put(`/categories/${id}`, data)
  },
  deleteCategory(id: string) {
    return api.delete(`/categories/${id}`)
  },
  toggleCategory(id: string) {
    return api.patch(`/categories/${id}/toggle`)
  },
  reorderCategories(categories: Array<{ id: string; display_order: number }>) {
    return api.post('/categories/reorder', { categories })
  },
}

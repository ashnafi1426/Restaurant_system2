import { defineStore } from 'pinia'
import { ref } from 'vue'
import { restaurantSectionService } from '@/services/manager/restaurantSectionService'
import type {
  RestaurantSection,
  CreateSectionRequest,
  UpdateSectionRequest
} from '@/types/restaurantSection'

export const useRestaurantSectionStore = defineStore('restaurantSection', () => {
  const sections = ref<RestaurantSection[]>([])
  const loading = ref(false)
  const error = ref<string | null>(null)

  const fetchSections = async (activeOnly = false) => {
    loading.value = true
    error.value = null
    try {
      const data = await restaurantSectionService.getSections(activeOnly ? { is_active: true } : undefined)
      sections.value = data || []
      return data
    } catch (err: any) {
      console.error('[RestaurantSectionStore] Error fetching sections:', err)
      error.value = err.message || 'Failed to fetch restaurant sections'
      return []
    } finally {
      loading.value = false
    }
  }

  async function withSectionMutation<T>(action: () => Promise<T>, fallbackMessage: string): Promise<T> {
    loading.value = true
    error.value = null
    try {
      const result = await action()
      await fetchSections()
      return result
    } catch (err: any) {
      console.error(`[RestaurantSectionStore] ${fallbackMessage}:`, err)
      error.value = err.response?.data?.message || err.message || fallbackMessage
      throw err
    } finally {
      loading.value = false
    }
  }

  const createSection = async (data: CreateSectionRequest) => {
    const response = await withSectionMutation(
      () => restaurantSectionService.createSection(data),
      'Failed to create section'
    )
    return response.data
  }

  const updateSection = async (id: string, data: UpdateSectionRequest) => {
    const response = await withSectionMutation(
      () => restaurantSectionService.updateSection(id, data),
      'Failed to update section'
    )
    return response.data
  }

  const deleteSection = async (id: string) => {
    return withSectionMutation(
      () => restaurantSectionService.deleteSection(id),
      'Failed to delete section'
    )
  }

  return {
    sections,
    loading,
    error,
    fetchSections,
    createSection,
    updateSection,
    deleteSection,
  }
})

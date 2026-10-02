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
      sections.value = data
      return data
    } catch (err: any) {
      console.error('[RestaurantSectionStore] Error fetching sections:', err)
      error.value = err.message || 'Failed to fetch restaurant sections'
      return []
    } finally {
      loading.value = false
    }
  }

  const createSection = async (data: CreateSectionRequest) => {
    loading.value = true
    error.value = null
    try {
      const response = await restaurantSectionService.createSection(data)
      await fetchSections()
      return response.data
    } catch (err: any) {
      console.error('[RestaurantSectionStore] Error creating section:', err)
      error.value = err.response?.data?.message || err.message || 'Failed to create section'
      throw err
    } finally {
      loading.value = false
    }
  }

  const updateSection = async (id: string, data: UpdateSectionRequest) => {
    loading.value = true
    error.value = null
    try {
      const response = await restaurantSectionService.updateSection(id, data)
      await fetchSections()
      return response.data
    } catch (err: any) {
      console.error('[RestaurantSectionStore] Error updating section:', err)
      error.value = err.response?.data?.message || err.message || 'Failed to update section'
      throw err
    } finally {
      loading.value = false
    }
  }

  const deleteSection = async (id: string) => {
    loading.value = true
    error.value = null
    try {
      const response = await restaurantSectionService.deleteSection(id)
      await fetchSections()
      return response
    } catch (err: any) {
      console.error('[RestaurantSectionStore] Error deleting section:', err)
      error.value = err.response?.data?.message || err.message || 'Failed to delete section'
      throw err
    } finally {
      loading.value = false
    }
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

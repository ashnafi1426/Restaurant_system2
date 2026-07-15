import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import menuService from '@/services/menuService'
import type { MenuItem, MenuStatistics, MenuPagination } from '@/types/menu'

export const useMenuStore = defineStore('menu', () => {
  /* ----------------------------------------
   * State - ZERO DEFAULTS, All from Backend
   * -------------------------------------- */
  // No hardcoded data - all from backend API
  const menuItems = ref<MenuItem[]>([])
  const statistics = ref<MenuStatistics>({
    total_items: 0,
    available_items: 0,
    unavailable_items: 0,
    breakfast_items: 0,
    lunch_items: 0,
    dinner_items: 0,
    drink_items: 0,
    dessert_items: 0,
  })

  const pagination = ref<MenuPagination>({
    current_page: 1,
    last_page: 1,
    per_page: 10,
    total: 0,
  })
  const loading = ref(false)
  const saving = ref(false)
  const hasMenuItems = computed(() => menuItems.value.length > 0)

  /* ----------------------------------------
   * Fetch Menu Items
   * -------------------------------------- */
  async function fetchMenuItems(filters: any = {}) {
    loading.value = true
    try {
      const response = await menuService.getMenus(filters)
      console.log('Menu API Response:', response.data)
      if (response.data.data && response.data.meta) {
        menuItems.value = Array.isArray(response.data.data) ? response.data.data : []
        // Store pagination metadata
        pagination.value = {
          current_page: response.data.meta.current_page,
          last_page: response.data.meta.last_page,
          per_page: response.data.meta.per_page,
          total: response.data.meta.total,
        }
        console.log('Pagination:', pagination.value)
      } else if (response.data.data) {
        menuItems.value = Array.isArray(response.data.data) ? response.data.data : []
      } else if (Array.isArray(response.data)) {
        // Format: [...] direct array
        menuItems.value = response.data
      } else {
        console.warn('⚠️ Unexpected response format:', response.data)
        menuItems.value = []
      }

      console.log('✅ Menu Items Loaded:', menuItems.value.length, 'items')
    } catch (error) {
      console.error('❌ Error fetching menu items:', error)
      menuItems.value = []
      throw error
    } finally {
      loading.value = false
    }
  }
  async function fetchStatistics() {
    try {
      const response = await menuService.statistics()
      console.log('📊 Statistics Response:', response.data)

      if (response.data.data) {
        statistics.value = response.data.data
      } else if (response.data.statistics) {
        statistics.value = response.data.statistics
      } else {
        statistics.value = response.data
      }

      console.log('✅ Statistics Loaded:', statistics.value)
    } catch (error) {
      console.error('❌ Error fetching statistics:', error)
      statistics.value = {
        total_items: 0,
        available_items: 0,
        unavailable_items: 0,
      }
    }
  }

  /* ----------------------------------------
   * Create Menu Item
   * -------------------------------------- */
  async function createMenuItem(payload: any) {
    saving.value = true
    try {
      await menuService.createMenu(payload)
    } catch (error) {
      console.error('Error creating menu item:', error)
      throw error
    } finally {
      saving.value = false
    }
  }

  /* ----------------------------------------
   * Update Menu Item
   * -------------------------------------- */
  async function updateMenuItem(id: string, payload: any) {
    saving.value = true
    try {
      await menuService.updateMenu(id, payload)
    } catch (error) {
      console.error('Error updating menu item:', error)
      throw error
    } finally {
      saving.value = false
    }
  }

  /* ----------------------------------------
   * Delete Menu Item
   * -------------------------------------- */
  async function deleteMenuItem(id: string) {
    try {
      await menuService.deleteMenu(id)
    } catch (error) {
      console.error('Error deleting menu item:', error)
      throw error
    }
  }

  /* ----------------------------------------
   * Toggle Availability
   * -------------------------------------- */
  async function toggleAvailability(id: string) {
    try {
      await menuService.toggleStatus(id)
    } catch (error) {
      console.error('Error toggling availability:', error)
      throw error
    }
  }

  /* ----------------------------------------
   * Expose
   * -------------------------------------- */
  return {
    // State
    menuItems,
    statistics,
    pagination,
    loading,
    saving,

    // Computed
    hasMenuItems,

    // Actions
    fetchMenuItems,
    fetchStatistics,
    createMenuItem,
    updateMenuItem,
    deleteMenuItem,
    toggleAvailability,
  }
})

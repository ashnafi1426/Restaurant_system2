import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import menuService from '@/services/menuService'
import type { MenuItem, MenuStatistics, MenuPagination } from '@/types/menu'

export const useMenuStore = defineStore('menu', () => {
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

  async function fetchMenuItems(filters: any = {}) {
    loading.value = true
    try {
      const response = await menuService.getMenus(filters)

      const raw = response.data
      if (raw.data && Array.isArray(raw.data)) {
        menuItems.value = raw.data
        const meta = raw.meta || raw.pagination || raw
        pagination.value = {
          current_page: meta.current_page || 1,
          last_page: meta.last_page || 1,
          per_page: meta.per_page || 10,
          total: meta.total !== undefined ? meta.total : menuItems.value.length,
          from: meta.from !== undefined ? meta.from : (menuItems.value.length > 0 ? 1 : 0),
          to: meta.to !== undefined ? meta.to : menuItems.value.length,
        }
      } else if (Array.isArray(raw)) {
        menuItems.value = raw
        pagination.value = {
          current_page: 1,
          last_page: 1,
          per_page: raw.length || 10,
          total: raw.length,
          from: raw.length > 0 ? 1 : 0,
          to: raw.length,
        }
      } else {
        menuItems.value = []
      }
    } catch (error) {
      console.error('[menuStore] Failed to fetch menu items:', error)
      menuItems.value = []
      throw error
    } finally {
      loading.value = false
    }
  }
  async function fetchStatistics() {
    try {
      const response = await menuService.statistics()

      if (response.data.data) {
        statistics.value = response.data.data
      } else if (response.data.statistics) {
        statistics.value = response.data.statistics
      } else {
        statistics.value = response.data
      }
    } catch (error) {
      console.error('[menuStore] Failed to fetch menu statistics:', error)
      statistics.value = {
        total_items: 0,
        available_items: 0,
        unavailable_items: 0,
      }
    }
  }

  async function createMenuItem(payload: any) {
    saving.value = true
    try {
      await menuService.createMenu(payload)
    } catch (error) {
      console.error('[menuStore] Failed to create menu item:', error)
      throw error
    } finally {
      saving.value = false
    }
  }

  async function updateMenuItem(id: string, payload: any) {
    saving.value = true
    try {
      await menuService.updateMenu(id, payload)
    } catch (error) {
      console.error('[menuStore] Failed to update menu item:', error)
      throw error
    } finally {
      saving.value = false
    }
  }

  async function deleteMenuItem(id: string) {
    saving.value = true
    try {
      await menuService.deleteMenu(id)
    } catch (error) {
      console.error('[menuStore] Failed to delete menu item:', error)
      throw error
    } finally {
      saving.value = false
    }
  }

  async function toggleAvailability(id: string) {
    try {
      await menuService.toggleStatus(id)
    } catch (error) {
      console.error('[menuStore] Failed to toggle menu item availability:', error)
      throw error
    }
  }

  return {
    menuItems,
    statistics,
    pagination,
    loading,
    saving,

    hasMenuItems,

    fetchMenuItems,
    fetchStatistics,
    createMenuItem,
    updateMenuItem,
    deleteMenuItem,
    toggleAvailability,
  }
})

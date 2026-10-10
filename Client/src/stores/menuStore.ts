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

  async function withSaving<T>(action: () => Promise<T>, errorMessage: string): Promise<T> {
    saving.value = true
    try {
      return await action()
    } catch (error) {
      console.error(`[menuStore] ${errorMessage}:`, error)
      throw error
    } finally {
      saving.value = false
    }
  }

  async function fetchMenuItems(filters: any = {}) {
    loading.value = true
    try {
      const response = await menuService.getMenus(filters)
      const raw = response.data

      if (raw?.data && Array.isArray(raw.data)) {
        menuItems.value = raw.data
        const meta = raw.meta || raw.pagination || raw
        pagination.value = {
          current_page: meta.current_page || 1,
          last_page: meta.last_page || 1,
          per_page: meta.per_page || 10,
          total: meta.total !== undefined ? meta.total : menuItems.value.length,
          from: meta.from !== undefined ? meta.from : menuItems.value.length > 0 ? 1 : 0,
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
      const data = response?.data
      if (data?.data) {
        statistics.value = data.data
      } else if (data?.statistics) {
        statistics.value = data.statistics
      } else if (data) {
        statistics.value = data
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

  function createMenuItem(payload: any) {
    return withSaving(() => menuService.createMenu(payload), 'Failed to create menu item')
  }

  function updateMenuItem(id: string | number, payload: any) {
    return withSaving(() => menuService.updateMenu(id, payload), 'Failed to update menu item')
  }

  function deleteMenuItem(id: string | number) {
    return withSaving(async () => {
      const res = await menuService.deleteMenu(id)
      menuItems.value = menuItems.value.filter((m) => m.id !== id)
      return res
    }, 'Failed to delete menu item')
  }

  async function toggleAvailability(id: string | number) {
    const item = menuItems.value.find((m) => m.id === id)
    if (item) {
      item.is_available = !item.is_available
    }
    try {
      return await menuService.toggleStatus(id)
    } catch (error) {
      if (item) {
        item.is_available = !item.is_available
      }
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

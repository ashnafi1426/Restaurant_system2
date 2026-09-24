import { defineStore } from 'pinia'
import { ref } from 'vue'
import { restaurantTableService } from '@/services/manager/restaurantTableService'
import type {
  RestaurantTable,
  CreateTableRequest,
  UpdateTableRequest,
  TableFilters,
  TableStatistics,
  PaginatedTablesResponse,
} from '@/types/restaurantTable'

export const useRestaurantTableStore = defineStore('restaurantTable', () => {
  const tables = ref<RestaurantTable[]>([])
  const currentTable = ref<RestaurantTable | null>(null)
  const statistics = ref<TableStatistics | null>(null)
  const pagination = ref<Omit<PaginatedTablesResponse, 'data'> | null>(null)
  const filters = ref<TableFilters>({
    search: '',
    status: '',
    location: '',
    is_active: null,
    sort_by: 'table_number',
    sort_order: 'asc',
    per_page: 10,
    page: 1,
  })
  const loading = ref(false)
  const error = ref<string | null>(null)

  const fetchTables = async () => {
    loading.value = true
    error.value = null

    try {
      const serviceResponse = await restaurantTableService.getTables(filters.value)
      const paginatedData = serviceResponse.data

      if (paginatedData && typeof paginatedData === 'object') {
        const tablesArray = Array.isArray(paginatedData.data) ? paginatedData.data : []
        tables.value = tablesArray.filter((t: any) => t != null)

        if (paginatedData.current_page) {
          pagination.value = {
            current_page: paginatedData.current_page || 1,
            first_page_url: paginatedData.first_page_url || '',
            from: paginatedData.from || 0,
            last_page: paginatedData.last_page || 1,
            last_page_url: paginatedData.last_page_url || '',
            links: paginatedData.links || [],
            next_page_url: paginatedData.next_page_url || null,
            path: paginatedData.path || '',
            per_page: paginatedData.per_page || 15,
            prev_page_url: paginatedData.prev_page_url || null,
            to: paginatedData.to || 0,
            total: paginatedData.total || 0,
          }
        }
      } else {
        tables.value = []
      }
    } catch (err: any) {
      console.error('[RestaurantTableStore] Error fetching tables:', err)
      error.value = err.message || 'Failed to fetch tables'
      tables.value = []
    } finally {
      loading.value = false
    }
  }

  const fetchTableById = async (id: string) => {
    loading.value = true
    error.value = null
    try {
      const table = await restaurantTableService.getTableById(id)
      currentTable.value = table
      return table
    } catch (err: any) {
      console.error('[RestaurantTableStore] Error fetching table by ID:', err)
      error.value = err.message || 'Failed to fetch table'
      throw err
    } finally {
      loading.value = false
    }
  }

  const createTable = async (data: CreateTableRequest) => {
    loading.value = true
    error.value = null

    try {
      const newTable = await restaurantTableService.createTable(data)
      await fetchTables()
      await fetchStatistics()
      return newTable
    } catch (err: any) {
      console.error('[RestaurantTableStore] Error creating table:', err)
      error.value = err.response?.data?.message || err.message || 'Failed to create table'

      if (err.response?.data?.errors) {
        const validationError: any = new Error(err.response.data.message || 'Validation failed')
        validationError.errors = err.response.data.errors
        throw validationError
      }

      throw err
    } finally {
      loading.value = false
    }
  }

  const updateTable = async (id: string, data: UpdateTableRequest) => {
    loading.value = true
    error.value = null

    try {
      const updatedTable = await restaurantTableService.updateTable(id, data)
      await fetchTables()
      await fetchStatistics()
      return updatedTable
    } catch (err: any) {
      console.error('[RestaurantTableStore] Error updating table:', err)
      error.value = err.message || 'Failed to update table'
      throw err
    } finally {
      loading.value = false
    }
  }

  const deleteTable = async (id: string) => {
    loading.value = true
    error.value = null

    try {
      await restaurantTableService.deleteTable(id)
      await fetchTables()
      await fetchStatistics()
    } catch (err: any) {
      console.error('[RestaurantTableStore] Error deleting table:', err)
      error.value = err.message || 'Failed to delete table'
      throw err
    } finally {
      loading.value = false
    }
  }

  const regenerateQR = async (id: string) => {
    loading.value = true
    error.value = null

    try {
      const updatedTable = await restaurantTableService.regenerateQR(id)
      await fetchTables()
      return updatedTable
    } catch (err: any) {
      console.error('[RestaurantTableStore] Error regenerating QR:', err)
      error.value = err.message || 'Failed to regenerate QR code'
      throw err
    } finally {
      loading.value = false
    }
  }

  const fetchStatistics = async () => {
    try {
      const response = await restaurantTableService.getStatistics()

      if (response && response.data) {
        statistics.value = response.data
      }
    } catch (err: any) {
      console.error('[RestaurantTableStore] Error fetching statistics:', err)
    }
  }

  const setFilters = (newFilters: Partial<TableFilters>) => {
    filters.value = { ...filters.value, ...newFilters }
  }

  const resetFilters = () => {
    filters.value = {
      search: '',
      status: '',
      location: '',
      is_active: null,
      sort_by: 'table_number',
      sort_order: 'asc',
      per_page: 10,
      page: 1,
    }
  }

  const downloadQRCode = async (table: RestaurantTable) => {
    if (table) {
      await restaurantTableService.downloadQRCode(table)
    }
  }

  return {
    tables,
    currentTable,
    statistics,
    pagination,
    filters,
    loading,
    error,

    fetchTables,
    fetchTableById,
    createTable,
    updateTable,
    deleteTable,
    regenerateQR,
    fetchStatistics,
    setFilters,
    resetFilters,
    downloadQRCode,
  }
})

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
  // State
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

  // Actions
  const fetchTables = async () => {
    console.log('🚀 [STORE] fetchTables called!')
    console.log('🚀 [STORE] Current filters:', filters.value)
    
    loading.value = true
    error.value = null

    try {
      console.log('🚀 [STORE] About to call service...')
      const serviceResponse = await restaurantTableService.getTables(filters.value)
      console.log('🔧 [STORE] Service response received')
      console.log('🔧 [STORE] Full serviceResponse:', JSON.stringify(serviceResponse, null, 2))
      console.log('🔧 [STORE] serviceResponse structure:', {
        hasSuccess: 'success' in serviceResponse,
        hasData: 'data' in serviceResponse,
        dataType: typeof serviceResponse.data
      })
      
      //Backend returns: {success: true, data: {data: [...], current_page: 1, ...}}
      const paginatedData = serviceResponse.data
      console.log('🔧 [STORE] paginatedData:', JSON.stringify(paginatedData, null, 2))
      console.log('🔧 [STORE] paginatedData.data type:', typeof paginatedData?.data)
      console.log('🔧 [STORE] paginatedData.data isArray:', Array.isArray(paginatedData?.data))
      console.log('🔧 [STORE] paginatedData.data length:', paginatedData?.data?.length)
      
      if (paginatedData && typeof paginatedData === 'object') {
        // Extract the tables array from paginatedData.data
        const tablesArray = Array.isArray(paginatedData.data) ? paginatedData.data : []
        console.log('🔧 [STORE] tablesArray extracted, length:', tablesArray.length)
        tables.value = tablesArray.filter((t: any) => t != null)
        
        console.log(`✅ [STORE] Loaded ${tables.value.length} tables`)
        
        // Set pagination
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
        console.error('❌ [STORE] Invalid response structure', serviceResponse)
        tables.value = []
      }
    } catch (err: any) {
      error.value = err.message || 'Failed to fetch tables'
      console.error('❌ [STORE] Error:', err)
      tables.value = []
    } finally {
      loading.value = false
      console.log('🏁 [STORE] fetchTables completed')
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
      error.value = err.message || 'Failed to fetch table'
      console.error('Error fetching table:', err)
      throw err
    } finally {
      loading.value = false
    }
  }

  const createTable = async (data: CreateTableRequest) => {
    loading.value = true
    error.value = null

    try {
      console.log('🔵 Creating table with data:', data)
      const newTable = await restaurantTableService.createTable(data)
      console.log('✅ Table created successfully:', newTable)
      await fetchTables() // Refresh list
      await fetchStatistics() // Refresh stats
      return newTable
    } catch (err: any) {
      console.error('❌ Error creating table:', err)
      console.error('❌ Error response data:', err.response?.data)
      console.error('❌ Error response status:', err.response?.status)
      console.error('❌ Request data that was sent:', data)
      console.error('❌ Validation errors:', err.response?.data?.errors)
      
      error.value = err.response?.data?.message || err.message || 'Failed to create table'
      
      // Re-throw with more details
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
      await fetchTables() // Refresh list
      await fetchStatistics() // Refresh stats
      return updatedTable
    } catch (err: any) {
      error.value = err.message || 'Failed to update table'
      console.error('Error updating table:', err)
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
      await fetchTables() // Refresh list
      await fetchStatistics() // Refresh stats
    } catch (err: any) {
      error.value = err.message || 'Failed to delete table'
      console.error('Error deleting table:', err)
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
      await fetchTables() // Refresh list
      return updatedTable
    } catch (err: any) {
      error.value = err.message || 'Failed to regenerate QR code'
      console.error('Error regenerating QR code:', err)
      throw err
    } finally {
      loading.value = false
    }
  }

  const fetchStatistics = async () => {
    try {
      console.log('📊 [STORE] Fetching statistics...')
      const response = await restaurantTableService.getStatistics()
      console.log('📊 [STORE] Statistics response:', response)
      
      // Extract data from response wrapper
      if (response && response.data) {
        statistics.value = response.data
        console.log('📊 [STORE] Statistics loaded:', statistics.value)
      } else {
        console.warn('📊 [STORE] Invalid statistics response structure')
      }
    } catch (err: any) {
      console.error('📊 [STORE] Error fetching statistics:', err)
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
    // State
    tables,
    currentTable,
    statistics,
    pagination,
    filters,
    loading,
    error,

    // Actions
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

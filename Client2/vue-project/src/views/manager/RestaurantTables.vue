<template>
  <DashboardLayout>
    <div class="restaurant-tables-page p-6 bg-gray-50 min-h-screen">
    <!-- Page Header -->
    <div class="mb-6">
      <h1 class="text-3xl font-bold text-gray-800 mb-2">Restaurant Tables</h1>
      <p class="text-gray-600">Manage restaurant tables and QR codes</p>
    </div>

    <!-- Statistics Cards -->
    <div v-if="statistics" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-6 gap-4 mb-6">
      <div class="bg-white rounded-lg shadow p-4">
        <div class="text-sm text-gray-600 mb-1">Total Tables</div>
        <div class="text-2xl font-bold text-gray-800">{{ statistics.total }}</div>
      </div>
      <div class="bg-white rounded-lg shadow p-4">
        <div class="text-sm text-gray-600 mb-1">Active</div>
        <div class="text-2xl font-bold text-blue-600">{{ statistics.active }}</div>
      </div>
      <div class="bg-white rounded-lg shadow p-4">
        <div class="text-sm text-gray-600 mb-1">Available</div>
        <div class="text-2xl font-bold text-green-600">{{ statistics.available }}</div>
      </div>
      <div class="bg-white rounded-lg shadow p-4">
        <div class="text-sm text-gray-600 mb-1">Occupied</div>
        <div class="text-2xl font-bold text-amber-600">{{ statistics.occupied }}</div>
      </div>
      <div class="bg-white rounded-lg shadow p-4">
        <div class="text-sm text-gray-600 mb-1">Cleaning</div>
        <div class="text-2xl font-bold text-orange-600">{{ statistics.cleaning || 0 }}</div>
      </div>
      <div class="bg-white rounded-lg shadow p-4">
        <div class="text-sm text-gray-600 mb-1">Out of Service</div>
        <div class="text-2xl font-bold text-red-600">{{ statistics.out_of_service || 0 }}</div>
      </div>
    </div>

    <!-- Filters and Actions -->
    <div class="bg-white rounded-lg shadow p-4 mb-6">
      <div class="flex flex-wrap gap-4 items-center">
        <!-- Search -->
        <div class="flex-1 min-w-[200px]">
          <input
            v-model="searchQuery"
            type="text"
            placeholder="Search by table number, name, or location..."
            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-amber-500 focus:border-transparent"
            @input="handleSearchChange"
          />
        </div>

        <!-- Status Filter -->
        <select
          v-model="statusFilter"
          class="px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-amber-500"
          @change="handleFilterChange"
        >
          <option value="">All Status</option>
          <option value="available">Available</option>
          <option value="occupied">Occupied</option>
          <option value="reserved">Reserved</option>
          <option value="cleaning">Cleaning</option>
          <option value="out_of_service">Out of Service</option>
        </select>

        <!-- Active Filter -->
        <select
          v-model="activeFilter"
          class="px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-amber-500"
          @change="handleFilterChange"
        >
          <option :value="null">All Tables</option>
          <option :value="true">Active Only</option>
          <option :value="false">Inactive Only</option>
        </select>

        <!-- Create Button -->
        <button
          @click="openCreateModal"
          class="px-4 py-2 bg-amber-600 text-white rounded-lg font-medium hover:bg-amber-700 transition-colors flex items-center gap-2"
        >
          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path
              stroke-linecap="round"
              stroke-linejoin="round"
              stroke-width="2"
              d="M12 4v16m8-8H4"
            ></path>
          </svg>
          Create Table
        </button>
      </div>
    </div>

    <!-- Loading State -->
    <div v-if="loading" class="bg-white rounded-lg shadow p-8 text-center">
      <div class="animate-spin w-12 h-12 border-4 border-amber-500 border-t-transparent rounded-full mx-auto mb-4"></div>
      <p class="text-gray-600">Loading tables...</p>
    </div>

    <!-- Error State -->
    <div v-else-if="error" class="bg-red-50 border border-red-200 rounded-lg p-4 text-red-700">
      <strong>Error:</strong> {{ error }}
    </div>

    <!-- Tables List -->
    <div v-else class="bg-white rounded-lg shadow overflow-hidden">
      <!-- Table -->
      <div class="overflow-x-auto">
        <table class="w-full">
          <thead class="bg-gray-50 border-b border-gray-200">
            <tr>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                Table
              </th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                Capacity
              </th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                Location
              </th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                Status
              </th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                Active
              </th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                QR Code
              </th>
              <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                Actions
              </th>
            </tr>
          </thead>
          <tbody class="bg-white divide-y divide-gray-200">
            <tr v-if="tables.length === 0">
              <td colspan="7" class="px-6 py-12 text-center text-gray-500">
                No tables found. Create your first table to get started.
              </td>
            </tr>
            <tr v-for="table in tables" :key="table.id" class="hover:bg-gray-50 transition-colors">
              <td class="px-6 py-4 whitespace-nowrap">
                <div class="font-medium text-gray-900">{{ table.table_number }}</div>
                <div v-if="table.table_name" class="text-sm text-gray-500">{{ table.table_name }}</div>
              </td>
              <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700">
                {{ table.capacity }} guests
              </td>
              <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700">
                {{ table.location || '-' }}
              </td>
              <td class="px-6 py-4 whitespace-nowrap">
                <span
                  :class="getStatusBadgeClass(table.status)"
                  class="px-3 py-1 text-xs font-semibold rounded-full"
                >
                  {{ table.status }}
                </span>
              </td>
              <td class="px-6 py-4 whitespace-nowrap">
                <span
                  :class="table.is_active ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-700'"
                  class="px-3 py-1 text-xs font-semibold rounded-full"
                >
                  {{ table.is_active ? 'Active' : 'Inactive' }}
                </span>
              </td>
              <td class="px-6 py-4 whitespace-nowrap">
                <button
                  @click="viewQRCode(table)"
                  class="text-blue-600 hover:text-blue-800 font-medium text-sm flex items-center gap-1"
                  title="View QR Code"
                >
                  <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path
                      stroke-linecap="round"
                      stroke-linejoin="round"
                      stroke-width="2"
                      d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm12 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z"
                    ></path>
                  </svg>
                  View
                </button>
              </td>
              <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                <div class="flex items-center justify-end gap-2">
                  <button
                    @click="editTable(table)"
                    class="text-amber-600 hover:text-amber-900 transition-colors"
                    title="Edit"
                  >
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        stroke-width="2"
                        d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"
                      ></path>
                    </svg>
                  </button>
                  <button
                    @click="confirmDelete(table)"
                    class="text-red-600 hover:text-red-900 transition-colors"
                    title="Delete"
                  >
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        stroke-width="2"
                        d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"
                      ></path>
                    </svg>
                  </button>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- Pagination -->
      <div
        v-if="pagination && pagination.last_page && pagination.last_page > 1"
        class="bg-gray-50 px-6 py-4 flex items-center justify-between border-t border-gray-200"
      >
        <div class="text-sm text-gray-700">
          Showing {{ pagination.from || 0 }} to {{ pagination.to || 0 }} of {{ pagination.total || 0 }} results
        </div>
        <div class="flex gap-2">
          <button
            :disabled="!pagination.prev_page_url"
            @click="goToPage(pagination.current_page - 1)"
            class="px-3 py-1 border border-gray-300 rounded-md text-sm font-medium text-gray-700 hover:bg-gray-100 disabled:opacity-50 disabled:cursor-not-allowed"
          >
            Previous
          </button>
          <button
            v-for="page in getPageNumbers()"
            :key="page"
            :class="page === pagination.current_page ? 'bg-amber-600 text-white' : 'bg-white text-gray-700 hover:bg-gray-100'"
            @click="goToPage(page)"
            class="px-3 py-1 border border-gray-300 rounded-md text-sm font-medium"
          >
            {{ page }}
          </button>
          <button
            :disabled="!pagination.next_page_url"
            @click="goToPage(pagination.current_page + 1)"
            class="px-3 py-1 border border-gray-300 rounded-md text-sm font-medium text-gray-700 hover:bg-gray-100 disabled:opacity-50 disabled:cursor-not-allowed"
          >
            Next
          </button>
        </div>
      </div>
    </div>

    <!-- Create/Edit Modal -->
    <RestaurantTableFormModal
      v-if="showFormModal"
      :table="selectedTable"
      @close="closeFormModal"
      @success="handleFormSuccess"
    />

    <!-- QR Code Modal -->
    <Teleport to="body">
      <Transition name="fade">
        <div
          v-if="showQRModal && selectedTable"
          class="fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4"
          @click.self="closeQRModal"
        >
          <div class="bg-white rounded-xl shadow-2xl max-w-md w-full p-6">
            <div class="flex items-center justify-between mb-4">
              <h3 class="text-xl font-bold text-gray-800">QR Code - {{ selectedTable.table_number }}</h3>
              <button
                @click="closeQRModal"
                class="text-gray-400 hover:text-gray-600 transition-colors"
              >
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    stroke-width="2"
                    d="M6 18L18 6M6 6l12 12"
                  ></path>
                </svg>
              </button>
            </div>

            <div v-if="selectedTable.qr_code_url" class="space-y-4">
              <img
                :src="selectedTable.qr_code_url"
                :alt="`QR Code for ${selectedTable.table_number}`"
                class="w-full rounded-lg border border-gray-200"
              />
              <div class="text-center text-sm text-gray-600">
                <p>Token: <code class="bg-gray-100 px-2 py-1 rounded">{{ selectedTable.qr_token }}</code></p>
                <p v-if="selectedTable.table_name" class="mt-1">{{ selectedTable.table_name }}</p>
              </div>
              <div class="flex gap-3">
                <button
                  @click="downloadQR(selectedTable)"
                  class="flex-1 px-4 py-2 bg-blue-600 text-white rounded-lg font-medium hover:bg-blue-700 transition-colors flex items-center justify-center gap-2"
                >
                  <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path
                      stroke-linecap="round"
                      stroke-linejoin="round"
                      stroke-width="2"
                      d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"
                    ></path>
                  </svg>
                  Download
                </button>
                <button
                  @click="regenerateQRCode(selectedTable.id)"
                  class="flex-1 px-4 py-2 bg-amber-600 text-white rounded-lg font-medium hover:bg-amber-700 transition-colors flex items-center justify-center gap-2"
                >
                  <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path
                      stroke-linecap="round"
                      stroke-linejoin="round"
                      stroke-width="2"
                      d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"
                    ></path>
                  </svg>
                  Regenerate
                </button>
              </div>
            </div>
            <div v-else class="text-center text-gray-500 py-8">
              No QR code available
            </div>
          </div>
        </div>
      </Transition>
    </Teleport>

    <!-- Delete Confirmation Modal -->
    <Teleport to="body">
      <Transition name="fade">
        <div
          v-if="showDeleteModal"
          class="fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4"
          @click.self="closeDeleteModal"
        >
          <div class="bg-white rounded-xl shadow-2xl max-w-md w-full p-6">
            <div class="text-center mb-4">
              <div class="w-12 h-12 bg-red-100 rounded-full flex items-center justify-center mx-auto mb-4">
                <svg class="w-6 h-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    stroke-width="2"
                    d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"
                  ></path>
                </svg>
              </div>
              <h3 class="text-xl font-bold text-gray-800 mb-2">Delete Table?</h3>
              <p class="text-gray-600">
                Are you sure you want to delete <strong>{{ selectedTable?.table_number }}</strong>?
                This action cannot be undone.
              </p>
            </div>
            <div class="flex gap-3">
              <button
                @click="closeDeleteModal"
                class="flex-1 px-4 py-2 border border-gray-300 rounded-lg font-medium text-gray-700 hover:bg-gray-50 transition-colors"
              >
                Cancel
              </button>
              <button
                @click="handleDelete"
                class="flex-1 px-4 py-2 bg-red-600 text-white rounded-lg font-medium hover:bg-red-700 transition-colors"
              >
                Delete
              </button>
            </div>
          </div>
        </div>
      </Transition>
    </Teleport>
  </div>
  </DashboardLayout>
</template>

<script setup lang="ts">
import { ref, computed, onMounted, watch } from 'vue'
import { useRestaurantTableStore } from '@/stores/restaurantTableStore'
import { storeToRefs } from 'pinia'
import DashboardLayout from '@/Layouts/DashboardLayout.vue'
import RestaurantTableFormModal from '@/components/manager/RestaurantTableFormModal.vue'
import type { RestaurantTable } from '@/types/restaurantTable'

// Store
const tableStore = useRestaurantTableStore()
const { tables, statistics, pagination, loading, error } = storeToRefs(tableStore)

// Local state
const searchQuery = ref('')
const statusFilter = ref('')
const activeFilter = ref<boolean | null>(null)
const showFormModal = ref(false)
const showQRModal = ref(false)
const showDeleteModal = ref(false)
const selectedTable = ref<RestaurantTable | null>(null)
let searchTimeout: ReturnType<typeof setTimeout> | null = null

// Methods
const getStatusBadgeClass = (status: string) => {
  const classes: Record<string, string> = {
    available: 'bg-green-100 text-green-700',
    occupied: 'bg-amber-100 text-amber-700',
    reserved: 'bg-blue-100 text-blue-700',
    cleaning: 'bg-orange-100 text-orange-700',
    out_of_service: 'bg-red-100 text-red-700',
  }
  return classes[status] || 'bg-gray-100 text-gray-700'
}

const handleSearchChange = () => {
  if (searchTimeout) clearTimeout(searchTimeout)
  searchTimeout = setTimeout(() => {
    tableStore.setFilters({ search: searchQuery.value, page: 1 })
    tableStore.fetchTables()
  }, 500)
}

const handleFilterChange = () => {
  tableStore.setFilters({
    status: statusFilter.value as any,
    is_active: activeFilter.value,
    page: 1,
  })
  tableStore.fetchTables()
}

const openCreateModal = () => {
  selectedTable.value = null
  showFormModal.value = true
}

const editTable = (table: RestaurantTable) => {
  selectedTable.value = table
  showFormModal.value = true
}

const closeFormModal = () => {
  showFormModal.value = false
  selectedTable.value = null
}

const handleFormSuccess = () => {
  closeFormModal()
  tableStore.fetchTables()
  tableStore.fetchStatistics()
}

const viewQRCode = (table: RestaurantTable) => {
  selectedTable.value = table
  showQRModal.value = true
}

const closeQRModal = () => {
  showQRModal.value = false
  selectedTable.value = null
}

const downloadQR = (table: RestaurantTable) => {
  tableStore.downloadQRCode(table)
}

const regenerateQRCode = async (tableId: string) => {
  if (confirm('Are you sure you want to regenerate the QR code? The old QR code will no longer work.')) {
    try {
      await tableStore.regenerateQR(tableId)
      const updatedTable = await tableStore.fetchTableById(tableId)
      selectedTable.value = updatedTable
      alert('QR code regenerated successfully!')
    } catch (err) {
      alert('Failed to regenerate QR code')
    }
  }
}

const confirmDelete = (table: RestaurantTable) => {
  selectedTable.value = table
  showDeleteModal.value = true
}

const closeDeleteModal = () => {
  showDeleteModal.value = false
  selectedTable.value = null
}

const handleDelete = async () => {
  if (!selectedTable.value) return

  try {
    await tableStore.deleteTable(selectedTable.value.id)
    closeDeleteModal()
    alert('Table deleted successfully!')
  } catch (err) {
    alert('Failed to delete table')
  }
}

const goToPage = (page: number) => {
  tableStore.setFilters({ page })
  tableStore.fetchTables()
}

const getPageNumbers = () => {
  if (!pagination.value || !pagination.value.current_page || !pagination.value.last_page) return []
  const current = pagination.value.current_page
  const last = pagination.value.last_page
  const pages: number[] = []

  // Show first page
  pages.push(1)

  // Show pages around current
  for (let i = Math.max(2, current - 2); i <= Math.min(last - 1, current + 2); i++) {
    pages.push(i)
  }

  // Show last page
  if (last > 1) pages.push(last)

  return [...new Set(pages)].sort((a, b) => a - b)
}

// Lifecycle
onMounted(() => {
  tableStore.fetchTables()
  tableStore.fetchStatistics()
})
</script>

<style scoped>
.fade-enter-active,
.fade-leave-active {
  transition: opacity 0.3s ease;
}

.fade-enter-from,
.fade-leave-to {
  opacity: 0;
}
@keyframes spin {
  to {
    transform: rotate(360deg);
  }
}

.animate-spin {
  animation: spin 1s linear infinite;
}
</style>

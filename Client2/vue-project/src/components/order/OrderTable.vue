<script setup lang="ts">
import { ref } from 'vue'
import type { Order } from '@/types/order'

defineProps<{
  orders: Order[]
  loading: boolean
  currentPage: number
  lastPage: number
  perPage: number
  total: number
}>()
const emit = defineEmits<{
  (e: 'view', order: Order): void
  (e: 'edit', order: Order): void
  (e: 'status', order: Order): void
  (e: 'delete', order: Order): void
  (e: 'page-change', page: number): void
  (e: 'per-page-change', value: number): void
}>()

// Track which dropdown is open
const openDropdown = ref<string | null>(null)

// Toggle dropdown
function toggleDropdown(orderId: string | number, event: Event) {
  event.stopPropagation()
  const key = String(orderId)
  openDropdown.value = openDropdown.value === key ? null : key
}

// Close dropdown
function closeDropdown() {
  openDropdown.value = null
}

/*
|--------------------------------------------------------------------------
| Actions
|--------------------------------------------------------------------------
*/

function viewOrder(order: Order): void {
  emit('view', order)
  closeDropdown()
}

function editOrder(order: Order): void {
  emit('edit', order)
  closeDropdown()
}

function changeStatus(order: Order): void {
  emit('status', order)
  closeDropdown()
}

function deleteOrder(order: Order): void {
  emit('delete', order)
  closeDropdown()
}

function changePage(page: number): void {
  emit('page-change', page)
}

function changePerPage(event: Event): void {
  const target = event.target as HTMLSelectElement
  emit('per-page-change', Number(target.value))
}

/*
|--------------------------------------------------------------------------
| Helpers
|--------------------------------------------------------------------------
*/

function formatCurrency(amount: number): string {
  return new Intl.NumberFormat('en-US', {
    style: 'currency',
    currency: 'USD',
  }).format(amount)
}

function formatDate(value: string): string {
  return new Intl.DateTimeFormat('en-US', {
    year: 'numeric',
    month: 'short',
    day: 'numeric',
    hour: '2-digit',
    minute: '2-digit',
  }).format(new Date(value))
}

/*
|--------------------------------------------------------------------------
| Status Badge
|--------------------------------------------------------------------------
*/

function statusClass(status: Order['status']): string {
  const classes: Record<string, string> = {
    pending: 'bg-yellow-50 text-yellow-700 ring-1 ring-yellow-600/20',
    preparing: 'bg-blue-50 text-blue-700 ring-1 ring-blue-600/20',
    ready: 'bg-green-50 text-green-700 ring-1 ring-green-600/20',
    served: 'bg-indigo-50 text-indigo-700 ring-1 ring-indigo-600/20',
    cancelled: 'bg-red-50 text-red-700 ring-1 ring-red-600/20',
  }
  return classes[status] || 'bg-gray-50 text-gray-700 ring-1 ring-gray-600/20'
}

function statusDot(status: Order['status']): string {
  const dots: Record<string, string> = {
    pending: 'bg-yellow-400',
    preparing: 'bg-blue-400',
    ready: 'bg-green-400',
    served: 'bg-indigo-400',
    cancelled: 'bg-red-400',
  }
  return dots[status] || 'bg-gray-400'
}

function statusLabel(status: Order['status']): string {
  const labels: Record<string, string> = {
    pending: 'Pending',
    preparing: 'Preparing',
    ready: 'Ready',
    served: 'Served',
    cancelled: 'Cancelled',
  }
  return labels[status] || status
}

/*
|--------------------------------------------------------------------------
| Payment Badge
|--------------------------------------------------------------------------
*/

function paymentClass(payment: Order['payment_type']): string {
  const classes: Record<string, string> = {
    room_charge: 'bg-purple-50 text-purple-700 ring-1 ring-purple-600/20',
    cash: 'bg-green-50 text-green-700 ring-1 ring-green-600/20',
    card: 'bg-sky-50 text-sky-700 ring-1 ring-sky-600/20',
  }
  return classes[payment] || 'bg-gray-50 text-gray-700 ring-1 ring-gray-600/20'
}

function paymentLabel(payment: Order['payment_type']): string {
  const labels: Record<string, string> = {
    room_charge: 'Room Charge',
    cash: 'Cash',
    card: 'Card',
  }
  return labels[payment] || payment
}

function previousPage(currentPage: number): void {
  if (currentPage > 1) {
    changePage(currentPage - 1)
  }
}

function nextPage(currentPage: number, lastPage: number): void {
  if (currentPage < lastPage) {
    changePage(currentPage + 1)
  }
}

// Close dropdown when clicking outside
function handleClickOutside() {
  if (openDropdown.value) {
    openDropdown.value = null
  }
}
</script>

<template>
  <div class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
    <!-- ===================================================== -->
    <!-- Responsive Table -->
    <!-- ===================================================== -->

    <div class="overflow-x-auto -mx-4 sm:mx-0" @click="handleClickOutside">
      <table class="min-w-full divide-y divide-gray-200">
        <!-- ================================================= -->
        <!-- Table Header -->
        <!-- ================================================= -->

        <thead class="bg-gray-50/80">
          <tr>
            <th
              class="px-3 sm:px-4 md:px-6 py-2 sm:py-3 md:py-3.5 text-left text-xs font-semibold uppercase tracking-wider text-gray-500"
            >
              <div class="flex items-center gap-1 sm:gap-2">
                <span class="truncate">Order</span>
                <svg
                  class="w-3 h-3 sm:w-3.5 sm:h-3.5 md:w-4 md:h-4 text-gray-400 flex-shrink-0"
                  fill="none"
                  stroke="currentColor"
                  viewBox="0 0 24 24"
                >
                  <path
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    stroke-width="2"
                    d="M7 16V4m0 0L3 8m4-4l4 4m6 0v12m0 0l4-4m-4 4l-4-4"
                  />
                </svg>
              </div>
            </th>

            <th
              class="hidden sm:table-cell px-3 sm:px-4 md:px-6 py-2 sm:py-3 md:py-3.5 text-left text-xs font-semibold uppercase tracking-wider text-gray-500"
            >
              Guest
            </th>

            <th
              class="hidden md:table-cell px-3 sm:px-4 md:px-6 py-2 sm:py-3 md:py-3.5 text-left text-xs font-semibold uppercase tracking-wider text-gray-500"
            >
              Room
            </th>

            <th
              class="hidden lg:table-cell px-3 sm:px-4 md:px-6 py-2 sm:py-3 md:py-3.5 text-left text-xs font-semibold uppercase tracking-wider text-gray-500"
            >
              Payment
            </th>

            <th
              class="px-3 sm:px-4 md:px-6 py-2 sm:py-3 md:py-3.5 text-left text-xs font-semibold uppercase tracking-wider text-gray-500"
            >
              Status
            </th>

            <th
              class="hidden sm:table-cell px-3 sm:px-4 md:px-6 py-2 sm:py-3 md:py-3.5 text-right text-xs font-semibold uppercase tracking-wider text-gray-500"
            >
              Total
            </th>

            <th
              class="hidden lg:table-cell px-3 sm:px-4 md:px-6 py-2 sm:py-3 md:py-3.5 text-left text-xs font-semibold uppercase tracking-wider text-gray-500"
            >
              Date
            </th>

            <th
              class="px-3 sm:px-4 md:px-6 py-2 sm:py-3 md:py-3.5 text-center text-xs font-semibold uppercase tracking-wider text-gray-500"
            >
              Actions
            </th>
          </tr>
        </thead>

        <!-- ================================================= -->
        <!-- Table Body -->
        <!-- ================================================= -->

        <tbody class="divide-y divide-gray-100 bg-white">
          <!-- =============================================== -->
          <!-- Loading -->
          <!-- =============================================== -->

          <tr v-if="loading">
            <td colspan="8" class="px-4 py-16 text-center">
              <div class="flex flex-col items-center justify-center">
                <div class="relative">
                  <div class="h-12 w-12 rounded-full border-4 border-gray-200"></div>
                  <div
                    class="absolute top-0 left-0 h-12 w-12 animate-spin rounded-full border-4 border-blue-600 border-t-transparent"
                  ></div>
                </div>
                <p class="mt-4 text-xs sm:text-sm font-medium text-gray-500">Loading orders...</p>
              </div>
            </td>
          </tr>

          <!-- =============================================== -->
          <!-- Empty State -->
          <!-- =============================================== -->

          <tr v-else-if="orders.length === 0">
            <td colspan="8" class="px-4 py-16 text-center">
              <div class="flex flex-col items-center justify-center">
                <div
                  class="flex h-16 w-16 sm:h-20 sm:w-20 items-center justify-center rounded-full bg-gray-100"
                >
                  <svg
                    class="w-8 h-8 sm:w-10 sm:h-10 text-gray-400"
                    fill="none"
                    stroke="currentColor"
                    viewBox="0 0 24 24"
                  >
                    <path
                      stroke-linecap="round"
                      stroke-linejoin="round"
                      stroke-width="1.5"
                      d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"
                    />
                  </svg>
                </div>
                <h3 class="mt-3 sm:mt-4 text-base sm:text-lg font-semibold text-gray-900">
                  No Orders Found
                </h3>
                <p class="mt-1 text-xs sm:text-sm text-gray-500">
                  No orders match your current filters.
                </p>
              </div>
            </td>
          </tr>

          <!-- =============================================== -->
          <!-- Order Rows -->
          <!-- =============================================== -->

          <tr
            v-for="order in orders"
            v-else
            :key="order.id"
            class="group transition-all duration-150 hover:bg-gray-50/80"
          >
            <!-- Order -->
            <td class="px-3 sm:px-4 md:px-6 py-3 sm:py-4 md:py-5 align-top">
              <div class="font-semibold text-xs sm:text-sm md:text-base text-gray-900 truncate">
                {{ order.order_number || `ORD-${String(order.id).padStart(6, '0')}` }}
              </div>
              <div class="mt-0.5 text-xs text-gray-400 truncate">ID: #{{ order.id }}</div>
            </td>

            <!-- Guest -->
            <td class="hidden sm:table-cell px-3 sm:px-4 md:px-6 py-3 sm:py-4 md:py-5 align-top">
              <div class="font-medium text-xs sm:text-sm md:text-base text-gray-900 truncate">
                {{ order.guest?.first_name || 'N/A' }}
                <span class="hidden md:inline">{{ order.guest?.last_name || '' }}</span>
              </div>
              <div class="mt-0.5 text-xs text-gray-500 truncate">
                {{ order.guest?.phone || 'No phone' }}
              </div>
            </td>

            <!-- Room -->
            <td class="hidden md:table-cell px-3 sm:px-4 md:px-6 py-3 sm:py-4 md:py-5 align-top">
              <div class="font-medium text-xs sm:text-sm md:text-base text-gray-900">
                {{ order.room?.room_number || 'N/A' }}
              </div>
              <div class="mt-0.5 text-xs text-gray-500">
                {{ order.room?.room_type?.name || 'Standard' }}
              </div>
            </td>

            <!-- Payment -->
            <td class="hidden lg:table-cell px-3 sm:px-4 md:px-6 py-3 sm:py-4 md:py-5 align-top">
              <span
                class="inline-flex rounded-full px-2 sm:px-2.5 py-1 text-xs font-medium truncate"
                :class="paymentClass(order.payment_type)"
              >
                {{ paymentLabel(order.payment_type) }}
              </span>
            </td>

            <!-- Status -->
            <td class="px-3 sm:px-4 md:px-6 py-3 sm:py-4 md:py-5 align-top">
              <span
                class="inline-flex items-center gap-1 rounded-full px-2 sm:px-2.5 py-1 text-xs font-medium"
                :class="statusClass(order.status)"
              >
                <span
                  class="h-1.5 w-1.5 rounded-full flex-shrink-0"
                  :class="statusDot(order.status)"
                ></span>
                <span class="truncate">{{ statusLabel(order.status) }}</span>
              </span>
            </td>

            <!-- Total -->
            <td
              class="hidden sm:table-cell px-3 sm:px-4 md:px-6 py-3 sm:py-4 md:py-5 text-right align-top"
            >
              <span class="font-semibold text-xs sm:text-sm md:text-base text-gray-900">
                {{ formatCurrency(order.total) }}
              </span>
            </td>

            <!-- Ordered At -->
            <td class="hidden lg:table-cell px-3 sm:px-4 md:px-6 py-3 sm:py-4 md:py-5 align-top">
              <div class="text-xs sm:text-sm text-gray-600">
                {{ formatDate(order.order_time) }}
              </div>
            </td>

            <!-- Actions Dropdown -->
            <td class="px-3 sm:px-4 md:px-6 py-3 sm:py-4 md:py-5 align-top text-center">
              <div class="relative inline-block">
                <button
                  @click="toggleDropdown(order.id, $event)"
                  class="inline-flex h-8 w-8 sm:h-9 sm:w-9 md:h-10 md:w-10 items-center justify-center rounded-lg text-gray-400 transition-all duration-200 hover:bg-gray-100 hover:text-gray-600 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 min-h-10 min-w-10"
                >
                  <svg class="h-4 w-4 sm:h-5 sm:w-5" fill="currentColor" viewBox="0 0 20 20">
                    <path
                      d="M10 6a2 2 0 110-4 2 2 0 010 4zM10 12a2 2 0 110-4 2 2 0 010 4zM10 18a2 2 0 110-4 2 2 0 010 4z"
                    />
                  </svg>
                </button>

                <!-- Dropdown Menu -->
                <div
                  v-if="openDropdown === String(order.id)"
                  class="absolute right-0 z-10 mt-2 w-44 sm:w-48 origin-top-right rounded-xl bg-white py-1.5 shadow-lg ring-1 ring-black/5 focus:outline-none"
                >
                  <!-- View -->
                  <button
                    @click="viewOrder(order)"
                    class="flex w-full items-center gap-2 sm:gap-3 px-3 sm:px-4 py-2 sm:py-2.5 text-xs sm:text-sm text-gray-700 transition-colors hover:bg-blue-50 hover:text-blue-700"
                  >
                    <svg
                      class="h-3 w-3 sm:h-4 sm:w-4 flex-shrink-0"
                      fill="none"
                      stroke="currentColor"
                      viewBox="0 0 24 24"
                    >
                      <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        stroke-width="2"
                        d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"
                      />
                      <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        stroke-width="2"
                        d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"
                      />
                    </svg>
                    <span class="truncate">View</span>
                  </button>

                  <!-- Edit -->
                  <button
                    @click="editOrder(order)"
                    class="flex w-full items-center gap-2 sm:gap-3 px-3 sm:px-4 py-2 sm:py-2.5 text-xs sm:text-sm text-gray-700 transition-colors hover:bg-amber-50 hover:text-amber-700"
                  >
                    <svg
                      class="h-3 w-3 sm:h-4 sm:w-4 flex-shrink-0"
                      fill="none"
                      stroke="currentColor"
                      viewBox="0 0 24 24"
                    >
                      <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        stroke-width="2"
                        d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"
                      />
                    </svg>
                    <span class="truncate">Edit</span>
                  </button>

                  <!-- Status -->
                  <button
                    @click="changeStatus(order)"
                    class="flex w-full items-center gap-2 sm:gap-3 px-3 sm:px-4 py-2 sm:py-2.5 text-xs sm:text-sm text-gray-700 transition-colors hover:bg-green-50 hover:text-green-700"
                  >
                    <svg
                      class="h-3 w-3 sm:h-4 sm:w-4 flex-shrink-0"
                      fill="none"
                      stroke="currentColor"
                      viewBox="0 0 24 24"
                    >
                      <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        stroke-width="2"
                        d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"
                      />
                    </svg>
                    <span class="truncate">Status</span>
                  </button>

                  <!-- Divider -->
                  <div class="my-1 border-t border-gray-100"></div>

                  <!-- Delete -->
                  <button
                    @click="deleteOrder(order)"
                    class="flex w-full items-center gap-2 sm:gap-3 px-3 sm:px-4 py-2 sm:py-2.5 text-xs sm:text-sm text-red-600 transition-colors hover:bg-red-50 hover:text-red-700"
                  >
                    <svg
                      class="h-3 w-3 sm:h-4 sm:w-4 flex-shrink-0"
                      fill="none"
                      stroke="currentColor"
                      viewBox="0 0 24 24"
                    >
                      <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        stroke-width="2"
                        d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"
                      />
                    </svg>
                    <span class="truncate">Delete</span>
                  </button>
                </div>
              </div>
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <!-- ===================================================== -->
    <!-- Pagination -->
    <!-- ===================================================== -->

    <div
      class="flex flex-col gap-3 sm:gap-4 md:gap-6 border-t border-gray-200 bg-gray-50/80 px-4 sm:px-6 md:px-8 py-3 sm:py-4 md:py-5 md:flex-row md:items-center md:justify-between"
    >
      <!-- Left -->
      <div class="flex flex-col gap-2 sm:flex-row sm:flex-wrap sm:items-center sm:gap-3 md:gap-4">
        <div class="text-xs sm:text-sm text-gray-600">
          Showing
          <span class="font-semibold text-gray-900">{{ orders.length }}</span>
          of
          <span class="font-semibold text-gray-900">{{ total }}</span>
          orders
        </div>

        <div class="flex items-center gap-2 sm:gap-3">
          <label class="text-xs sm:text-sm text-gray-600">Rows</label>
          <select
            :value="perPage"
            class="rounded-lg border border-gray-200 bg-white px-2 sm:px-3 py-1.5 text-xs sm:text-sm text-gray-700 focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/20 min-h-10"
            @change="changePerPage"
          >
            <option :value="10">10</option>
            <option :value="15">15</option>
            <option :value="25">25</option>
            <option :value="50">50</option>
            <option :value="100">100</option>
          </select>
        </div>
      </div>

      <!-- Right -->
      <div class="flex flex-wrap items-center gap-1 sm:gap-2 md:gap-3">
        <!-- Previous -->
        <button
          type="button"
          class="inline-flex items-center gap-1 rounded-lg border border-gray-200 bg-white px-2 sm:px-3.5 py-2 text-xs sm:text-sm font-medium text-gray-600 transition-all duration-200 hover:bg-gray-50 hover:text-gray-900 disabled:cursor-not-allowed disabled:opacity-40 min-h-10"
          :disabled="currentPage === 1"
          @click="previousPage(currentPage)"
        >
          <svg
            class="h-3 w-3 sm:h-4 sm:w-4 flex-shrink-0"
            fill="none"
            stroke="currentColor"
            viewBox="0 0 24 24"
          >
            <path
              stroke-linecap="round"
              stroke-linejoin="round"
              stroke-width="2"
              d="M15 19l-7-7 7-7"
            />
          </svg>
          <span class="hidden sm:inline">Previous</span>
        </button>

        <!-- Page Info -->
        <div class="flex items-center gap-1 sm:gap-1.5">
          <span
            class="rounded-lg bg-blue-600 px-2 sm:px-4 py-2 text-xs sm:text-sm font-semibold text-white shadow-sm min-h-10 inline-flex items-center"
          >
            {{ currentPage }}
          </span>
          <span class="text-xs sm:text-sm text-gray-500">of</span>
          <span
            class="rounded-lg border border-gray-200 px-2 sm:px-4 py-2 text-xs sm:text-sm font-medium text-gray-600 min-h-10 inline-flex items-center"
          >
            {{ lastPage }}
          </span>
        </div>

        <!-- Next -->
        <button
          type="button"
          class="inline-flex items-center gap-1 rounded-lg border border-gray-200 bg-white px-2 sm:px-3.5 py-2 text-xs sm:text-sm font-medium text-gray-600 transition-all duration-200 hover:bg-gray-50 hover:text-gray-900 disabled:cursor-not-allowed disabled:opacity-40 min-h-10"
          :disabled="currentPage >= lastPage"
          @click="nextPage(currentPage, lastPage)"
        >
          <span class="hidden sm:inline">Next</span>
          <svg
            class="h-3 w-3 sm:h-4 sm:w-4 flex-shrink-0"
            fill="none"
            stroke="currentColor"
            viewBox="0 0 24 24"
          >
            <path
              stroke-linecap="round"
              stroke-linejoin="round"
              stroke-width="2"
              d="M9 5l7 7-7 7"
            />
          </svg>
        </button>
      </div>
    </div>
  </div>
</template>

<style scoped>
/* Custom scrollbar for dropdown */
.overflow-y-auto::-webkit-scrollbar {
  width: 4px;
}

.overflow-y-auto::-webkit-scrollbar-track {
  background: transparent;
}

.overflow-y-auto::-webkit-scrollbar-thumb {
  background: #d1d5db;
  border-radius: 2px;
}

.overflow-y-auto::-webkit-scrollbar-thumb:hover {
  background: #9ca3af;
}
</style>

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

function toggleDropdown(orderId: string | number, event: Event) {
  event.stopPropagation()
  const key = String(orderId)
  openDropdown.value = openDropdown.value === key ? null : key
}

function closeDropdown() {
  openDropdown.value = null
}

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

function formatCurrency(amount: number): string {
  return new Intl.NumberFormat('en-US', {
    style: 'currency',
    currency: 'USD',
  }).format(amount)
}

function formatDate(value: string): string {
  if (!value) return 'N/A'
  try {
    return new Intl.DateTimeFormat('en-US', {
      month: 'short',
      day: 'numeric',
      year: 'numeric',
      hour: '2-digit',
      minute: '2-digit',
    }).format(new Date(value))
  } catch {
    return value
  }
}

function getGuestDisplayName(guest?: Order['guest']): string {
  if (!guest) return 'N/A'
  if (guest.first_name || guest.last_name) {
    const fullName = `${guest.first_name || ''} ${guest.last_name || ''}`.trim()
    if (fullName) return fullName
  }
  if (guest.full_name && guest.full_name.trim()) return guest.full_name.trim()
  if (guest.name && guest.name.trim()) return guest.name.trim()
  return 'N/A'
}

function getShortId(id: string): string {
  if (!id) return ''
  return id.length > 8 ? id.substring(0, 8) : id
}

function statusClass(status: Order['status']): string {
  const classes: Record<string, string> = {
    pending: 'bg-amber-50 text-amber-700 border border-amber-200/80 dark:bg-amber-950/40 dark:text-amber-300 dark:border-amber-900/50',
    preparing: 'bg-sky-50 text-sky-700 border border-sky-200/80 dark:bg-sky-950/40 dark:text-sky-300 dark:border-sky-900/50',
    ready: 'bg-emerald-50 text-emerald-700 border border-emerald-200/80 dark:bg-emerald-950/40 dark:text-emerald-300 dark:border-emerald-900/50',
    served: 'bg-purple-50 text-purple-700 border border-purple-200/80 dark:bg-purple-950/40 dark:text-purple-300 dark:border-purple-900/50',
    cancelled: 'bg-rose-50 text-rose-700 border border-rose-200/80 dark:bg-rose-950/40 dark:text-rose-300 dark:border-rose-900/50',
  }
  return classes[status] || 'bg-slate-50 text-slate-700 border border-slate-200 dark:bg-slate-800 dark:text-slate-300'
}

function statusDot(status: Order['status']): string {
  const dots: Record<string, string> = {
    pending: 'bg-amber-500 animate-pulse',
    preparing: 'bg-sky-500',
    ready: 'bg-emerald-500',
    served: 'bg-purple-500',
    cancelled: 'bg-rose-500',
  }
  return dots[status] || 'bg-slate-400'
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

function paymentClass(payment: Order['payment_type']): string {
  const classes: Record<string, string> = {
    room_charge: 'bg-indigo-50 text-indigo-700 border border-indigo-200/60 dark:bg-indigo-950/30 dark:text-indigo-300',
    cash: 'bg-emerald-50 text-emerald-700 border border-emerald-200/60 dark:bg-emerald-950/30 dark:text-emerald-300',
    card: 'bg-sky-50 text-sky-700 border border-sky-200/60 dark:bg-sky-950/30 dark:text-sky-300',
  }
  return classes[payment] || 'bg-slate-50 text-slate-700 border border-slate-200'
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

function handleClickOutside() {
  if (openDropdown.value) {
    openDropdown.value = null
  }
}
</script>

<template>
  <div class="overflow-hidden rounded-2xl border border-slate-200/80 dark:border-slate-800 bg-white dark:bg-slate-900 shadow-sm" @click="handleClickOutside">
    <!-- ===================================================== -->
    <!-- Loading State -->
    <!-- ===================================================== -->
    <div v-if="loading" class="p-12 text-center">
      <div class="flex flex-col items-center justify-center">
        <div class="relative w-12 h-12">
          <svg class="absolute inset-0 w-full h-full text-indigo-200 dark:text-indigo-900" viewBox="0 0 100 100">
            <circle cx="50" cy="50" r="40" fill="none" stroke="currentColor" stroke-width="6" opacity="0.4" />
          </svg>
          <div class="absolute inset-0 animate-spin">
            <svg viewBox="0 0 100 100" class="w-full h-full text-indigo-600 dark:text-indigo-400">
              <circle cx="50" cy="50" r="40" fill="none" stroke="currentColor" stroke-width="6" stroke-linecap="round" stroke-dasharray="60 240" />
            </svg>
          </div>
        </div>
        <p class="mt-4 text-sm font-medium text-slate-500 dark:text-slate-400">Loading orders...</p>
      </div>
    </div>

    <!-- ===================================================== -->
    <!-- Empty State -->
    <!-- ===================================================== -->
    <div v-else-if="orders.length === 0" class="p-12 text-center">
      <div class="flex flex-col items-center justify-center">
        <div class="flex h-16 w-16 items-center justify-center rounded-2xl bg-indigo-50 dark:bg-indigo-950/40 text-indigo-500">
          <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
          </svg>
        </div>
        <h3 class="mt-4 text-base font-semibold text-slate-900 dark:text-white">No Orders Found</h3>
        <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">No orders match your search filters.</p>
      </div>
    </div>

    <template v-else>
      <!-- ===================================================== -->
      <!-- MOBILE CARD VIEW (sm and smaller) -->
      <!-- ===================================================== -->
      <div class="block sm:hidden divide-y divide-slate-100 dark:divide-slate-800">
        <div
          v-for="order in orders"
          :key="'card-' + order.id"
          class="p-4 space-y-3 hover:bg-slate-50/60 dark:hover:bg-slate-800/40 transition-colors"
        >
          <!-- Top Row: Order # + Status -->
          <div class="flex items-center justify-between">
            <div>
              <span class="font-bold text-sm text-slate-900 dark:text-white">
                {{ order.order_number || `ORD-${String(order.id).padStart(6, '0')}` }}
              </span>
              <span class="ml-2 text-[10px] font-mono px-1.5 py-0.5 rounded bg-slate-100 dark:bg-slate-800 text-slate-500">
                #{{ getShortId(order.id) }}
              </span>
            </div>
            <span
              class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-0.5 text-xs font-semibold"
              :class="statusClass(order.status)"
            >
              <span class="h-1.5 w-1.5 rounded-full" :class="statusDot(order.status)"></span>
              {{ statusLabel(order.status) }}
            </span>
          </div>

          <!-- Middle Row: Guest & Room Info -->
          <div class="grid grid-cols-2 gap-2 text-xs text-slate-600 dark:text-slate-300 bg-slate-50/70 dark:bg-slate-800/50 p-2.5 rounded-xl border border-slate-100 dark:border-slate-800">
            <div>
              <span class="text-[10px] text-slate-400 block uppercase font-medium">Guest</span>
              <span class="font-medium text-slate-900 dark:text-white truncate block">
                {{ getGuestDisplayName(order.guest) }}
              </span>
              <span class="text-[10px] text-slate-400 block">{{ order.guest?.phone || 'No phone' }}</span>
            </div>
            <div>
              <span class="text-[10px] text-slate-400 block uppercase font-medium">Room</span>
              <span class="font-medium text-slate-900 dark:text-white block">
                {{ order.room?.room_number ? 'Room ' + order.room.room_number : 'N/A' }}
              </span>
              <span class="text-[10px] text-slate-400 block">{{ order.room?.room_type?.name || 'Standard' }}</span>
            </div>
          </div>

          <!-- Bottom Row: Payment, Total & Date -->
          <div class="flex items-center justify-between text-xs pt-1">
            <div class="flex items-center gap-2">
              <span class="px-2 py-0.5 rounded-md text-[11px] font-medium" :class="paymentClass(order.payment_type)">
                {{ paymentLabel(order.payment_type) }}
              </span>
              <span class="text-[11px] text-slate-400">{{ formatDate(order.order_time) }}</span>
            </div>
            <span class="font-extrabold text-base text-slate-900 dark:text-white">
              {{ formatCurrency(order.total) }}
            </span>
          </div>

          <!-- Actions Bar for Mobile Card -->
          <div class="flex items-center justify-end gap-2 pt-2 border-t border-slate-100 dark:border-slate-800">
            <button
              @click="viewOrder(order)"
              class="px-3 py-1.5 text-xs font-medium rounded-lg text-slate-700 dark:text-slate-200 bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 transition"
            >
              View
            </button>
            <button
              @click="changeStatus(order)"
              class="px-3 py-1.5 text-xs font-medium rounded-lg text-indigo-700 bg-indigo-50 dark:bg-indigo-950/40 dark:text-indigo-300 hover:bg-indigo-100 transition"
            >
              Status
            </button>
            <button
              @click="editOrder(order)"
              class="px-3 py-1.5 text-xs font-medium rounded-lg text-amber-700 bg-amber-50 dark:bg-amber-950/40 dark:text-amber-300 hover:bg-amber-100 transition"
            >
              Edit
            </button>
            <button
              @click="deleteOrder(order)"
              class="px-2.5 py-1.5 text-xs font-medium rounded-lg text-rose-600 bg-rose-50 dark:bg-rose-950/40 dark:text-rose-400 hover:bg-rose-100 transition"
            >
              Delete
            </button>
          </div>
        </div>
      </div>

      <!-- ===================================================== -->
      <!-- DESKTOP TABLE VIEW (sm and larger) -->
      <!-- ===================================================== -->
      <div class="hidden sm:block overflow-x-auto">
        <table class="w-full text-left border-collapse">
          <thead>
            <tr class="border-b border-slate-200/80 dark:border-slate-800 bg-slate-50/60 dark:bg-slate-800/40 text-[11px] font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400">
              <th class="px-5 py-3.5">Order</th>
              <th class="px-5 py-3.5">Guest</th>
              <th class="px-5 py-3.5">Room</th>
              <th class="px-5 py-3.5">Payment</th>
              <th class="px-5 py-3.5">Status</th>
              <th class="px-5 py-3.5 text-right">Total</th>
              <th class="px-5 py-3.5">Date</th>
              <th class="px-5 py-3.5 text-center w-28">Actions</th>
            </tr>
          </thead>

          <tbody class="divide-y divide-slate-100 dark:divide-slate-800 text-xs sm:text-sm">
            <tr
              v-for="order in orders"
              :key="order.id"
              class="group transition-colors hover:bg-slate-50/80 dark:hover:bg-slate-800/40"
            >
              <!-- Order -->
              <td class="px-5 py-4 align-middle">
                <div class="font-bold text-slate-900 dark:text-white">
                  {{ order.order_number || `ORD-${String(order.id).padStart(6, '0')}` }}
                </div>
                <div class="text-[11px] font-mono text-slate-400">
                  #{{ getShortId(order.id) }}
                </div>
              </td>

              <!-- Guest -->
              <td class="px-5 py-4 align-middle">
                <div class="font-medium text-slate-900 dark:text-white">
                  {{ getGuestDisplayName(order.guest) }}
                </div>
                <div class="text-xs text-slate-400">
                  {{ order.guest?.phone || 'No phone' }}
                </div>
              </td>

              <!-- Room -->
              <td class="px-5 py-4 align-middle">
                <div class="font-medium text-slate-900 dark:text-white">
                  {{ order.room?.room_number ? 'Room ' + order.room.room_number : 'N/A' }}
                </div>
                <div class="text-xs text-slate-400">
                  {{ order.room?.room_type?.name || 'Standard' }}
                </div>
              </td>

              <!-- Payment -->
              <td class="px-5 py-4 align-middle">
                <span
                  class="inline-flex rounded-md px-2.5 py-1 text-xs font-semibold whitespace-nowrap"
                  :class="paymentClass(order.payment_type)"
                >
                  {{ paymentLabel(order.payment_type) }}
                </span>
              </td>

              <!-- Status -->
              <td class="px-5 py-4 align-middle">
                <span
                  class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-xs font-semibold whitespace-nowrap"
                  :class="statusClass(order.status)"
                >
                  <span class="h-1.5 w-1.5 rounded-full flex-shrink-0" :class="statusDot(order.status)"></span>
                  <span>{{ statusLabel(order.status) }}</span>
                </span>
              </td>

              <!-- Total -->
              <td class="px-5 py-4 text-right align-middle font-extrabold text-slate-900 dark:text-white">
                {{ formatCurrency(order.total) }}
              </td>

              <!-- Date -->
              <td class="px-5 py-4 align-middle text-xs text-slate-500 whitespace-nowrap">
                {{ formatDate(order.order_time) }}
              </td>

              <!-- Actions -->
              <td class="px-5 py-4 align-middle text-center relative">
                <div class="flex items-center justify-center gap-1.5">
                  <!-- Quick View Button -->
                  <button
                    @click="viewOrder(order)"
                    title="View Order"
                    class="p-1.5 rounded-lg text-slate-400 hover:text-indigo-600 hover:bg-indigo-50 dark:hover:bg-indigo-950/40 transition"
                  >
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                    </svg>
                  </button>

                  <!-- Quick Status Button -->
                  <button
                    @click="changeStatus(order)"
                    title="Update Status"
                    class="p-1.5 rounded-lg text-slate-400 hover:text-emerald-600 hover:bg-emerald-50 dark:hover:bg-emerald-950/40 transition"
                  >
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                    </svg>
                  </button>

                  <!-- 3-Dots Dropdown Toggle -->
                  <div class="relative inline-block text-left">
                    <button
                      @click="toggleDropdown(order.id, $event)"
                      class="p-1.5 rounded-lg text-slate-400 hover:text-slate-600 hover:bg-slate-100 dark:hover:bg-slate-800 transition"
                    >
                      <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                        <path d="M10 6a2 2 0 110-4 2 2 0 010 4zM10 12a2 2 0 110-4 2 2 0 010 4zM10 18a2 2 0 110-4 2 2 0 010 4z" />
                      </svg>
                    </button>

                    <!-- Dropdown Menu -->
                    <div
                      v-if="openDropdown === String(order.id)"
                      class="absolute right-0 z-30 mt-1 w-40 rounded-xl bg-white dark:bg-slate-900 py-1.5 shadow-xl border border-slate-200/80 dark:border-slate-800 focus:outline-none"
                    >
                      <button
                        @click="viewOrder(order)"
                        class="flex w-full items-center gap-2.5 px-3 py-2 text-xs font-medium text-slate-700 dark:text-slate-200 hover:bg-indigo-50 dark:hover:bg-indigo-950/40 hover:text-indigo-600 transition"
                      >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                        </svg>
                        View Order
                      </button>

                      <button
                        @click="editOrder(order)"
                        class="flex w-full items-center gap-2.5 px-3 py-2 text-xs font-medium text-slate-700 dark:text-slate-200 hover:bg-amber-50 dark:hover:bg-amber-950/40 hover:text-amber-600 transition"
                      >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                        </svg>
                        Edit Order
                      </button>

                      <button
                        @click="changeStatus(order)"
                        class="flex w-full items-center gap-2.5 px-3 py-2 text-xs font-medium text-slate-700 dark:text-slate-200 hover:bg-emerald-50 dark:hover:bg-emerald-950/40 hover:text-emerald-600 transition"
                      >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                        </svg>
                        Change Status
                      </button>

                      <div class="my-1 border-t border-slate-100 dark:border-slate-800"></div>

                      <button
                        @click="deleteOrder(order)"
                        class="flex w-full items-center gap-2.5 px-3 py-2 text-xs font-medium text-rose-600 hover:bg-rose-50 dark:hover:bg-rose-950/40 transition"
                      >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                        </svg>
                        Delete Order
                      </button>
                    </div>
                  </div>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </template>

    <!-- ===================================================== -->
    <!-- Pagination -->
    <!-- ===================================================== -->
    <div
      class="flex flex-col sm:flex-row items-center justify-between gap-4 border-t border-slate-200/80 dark:border-slate-800 bg-slate-50/60 dark:bg-slate-800/40 px-5 py-3.5"
    >
      <div class="flex items-center gap-4 text-xs text-slate-600 dark:text-slate-400">
        <div>
          Showing <span class="font-semibold text-slate-900 dark:text-white">{{ orders.length }}</span> of
          <span class="font-semibold text-slate-900 dark:text-white">{{ total }}</span> orders
        </div>

        <div class="flex items-center gap-2">
          <label class="text-slate-500">Rows:</label>
          <select
            :value="perPage"
            class="rounded-lg border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 px-2.5 py-1 text-xs font-medium text-slate-700 dark:text-slate-200 focus:outline-none focus:ring-2 focus:ring-indigo-500/20"
            @change="changePerPage"
          >
            <option :value="10">10</option>
            <option :value="15">15</option>
            <option :value="25">25</option>
            <option :value="50">50</option>
          </select>
        </div>
      </div>

      <div class="flex items-center gap-2">
        <button
          type="button"
          class="inline-flex items-center gap-1 px-3 py-1.5 rounded-lg border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-xs font-medium text-slate-600 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-700 transition disabled:opacity-40 disabled:cursor-not-allowed"
          :disabled="currentPage === 1"
          @click="previousPage(currentPage)"
        >
          <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
          </svg>
          Previous
        </button>

        <div class="flex items-center gap-1 text-xs font-semibold">
          <span class="px-2.5 py-1 rounded-lg bg-indigo-600 text-white shadow-xs">
            {{ currentPage }}
          </span>
          <span class="text-slate-400">/</span>
          <span class="px-2.5 py-1 rounded-lg border border-slate-200 dark:border-slate-700 text-slate-600 dark:text-slate-400 bg-white dark:bg-slate-800">
            {{ lastPage }}
          </span>
        </div>

        <button
          type="button"
          class="inline-flex items-center gap-1 px-3 py-1.5 rounded-lg border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-xs font-medium text-slate-600 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-700 transition disabled:opacity-40 disabled:cursor-not-allowed"
          :disabled="currentPage >= lastPage"
          @click="nextPage(currentPage, lastPage)"
        >
          Next
          <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
          </svg>
        </button>
      </div>
    </div>
  </div>
</template>

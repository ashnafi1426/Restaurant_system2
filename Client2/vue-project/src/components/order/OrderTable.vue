<script setup lang="ts">
import { ref } from 'vue'
import type { Order } from '@/types/order'
import {
  BedDouble,
  MoreVertical,
  Eye,
  Edit,
  Trash2,
  RefreshCw,
  Loader2,
  ShoppingBag,
  ChevronLeft,
  ChevronRight
} from 'lucide-vue-next'

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

function statusClass(status: Order['status']): string {
  const classes: Record<string, string> = {
    pending: 'bg-amber-500/10 text-amber-600 dark:text-amber-400 border-amber-500/20',
    preparing: 'bg-sky-500/10 text-sky-600 dark:text-sky-400 border-sky-500/20',
    ready: 'bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 border-emerald-500/20',
    served: 'bg-purple-500/10 text-purple-600 dark:text-purple-400 border-purple-500/20',
    cancelled: 'bg-rose-500/10 text-rose-600 dark:text-rose-400 border-rose-500/20',
  }
  return classes[status] || 'bg-slate-500/10 text-slate-600 dark:text-slate-400 border-slate-500/20'
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
    room_charge: 'bg-indigo-500/10 text-indigo-600 dark:text-indigo-400 border-indigo-500/20',
    cash: 'bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 border-emerald-500/20',
    card: 'bg-sky-500/10 text-sky-600 dark:text-sky-400 border-sky-500/20',
  }
  return classes[payment] || 'bg-slate-500/10 text-slate-600 dark:text-slate-400 border-slate-500/20'
}

function paymentLabel(payment: Order['payment_type']): string {
  const labels: Record<string, string> = {
    room_charge: 'Room Charge',
    cash: 'Cash',
    card: 'Card',
  }
  return labels[payment] || payment
}

function handleClickOutside() {
  if (openDropdown.value) {
    openDropdown.value = null
  }
}
</script>

<template>
  <div class="overflow-hidden rounded-2xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 shadow-xs font-sans w-full" @click="handleClickOutside">
    <!-- Loading State -->
    <div v-if="loading" class="p-10 text-center flex flex-col items-center justify-center space-y-2">
      <Loader2 class="w-7 h-7 text-blue-600 dark:text-blue-400 animate-spin" />
      <p class="text-xs font-bold text-slate-500 dark:text-slate-400">Loading order records...</p>
    </div>

    <!-- Empty State -->
    <div v-else-if="orders.length === 0" class="p-10 text-center space-y-2">
      <div class="w-10 h-10 rounded-xl bg-slate-100 dark:bg-slate-800 text-slate-400 flex items-center justify-center mx-auto">
        <ShoppingBag class="w-5 h-5" />
      </div>
      <h3 class="text-xs font-black text-slate-900 dark:text-white">No Orders Found</h3>
      <p class="text-[11px] text-slate-500 dark:text-slate-400">No orders match your current search filters.</p>
    </div>

    <template v-else>
      <!-- Mobile Cards View -->
      <div class="block sm:hidden divide-y divide-slate-100 dark:divide-slate-800">
        <div
          v-for="order in orders"
          :key="'card-' + order.id"
          class="p-3.5 space-y-2.5 hover:bg-slate-50/60 dark:hover:bg-slate-800/40 transition-colors"
        >
          <div class="flex items-center justify-between">
            <span class="font-mono font-extrabold text-xs text-blue-600 dark:text-blue-400">
              {{ order.order_number || `ORD-${String(order.id).padStart(6, '0')}` }}
            </span>
            <span
              class="inline-flex items-center gap-1 rounded-full px-2 py-0.5 text-[10px] font-black border"
              :class="statusClass(order.status)"
            >
              <span class="h-1.5 w-1.5 rounded-full" :class="statusDot(order.status)"></span>
              {{ statusLabel(order.status) }}
            </span>
          </div>

          <div class="grid grid-cols-2 gap-2 text-xs text-slate-600 dark:text-slate-300 bg-slate-50/70 dark:bg-slate-950 p-2 rounded-xl border border-slate-200 dark:border-slate-800">
            <div>
              <span class="text-[9px] text-slate-400 block font-bold uppercase">Guest</span>
              <span class="font-bold text-slate-900 dark:text-white truncate block text-xs">
                {{ getGuestDisplayName(order.guest) }}
              </span>
            </div>
            <div>
              <span class="text-[9px] text-slate-400 block font-bold uppercase">Room</span>
              <span class="font-bold text-slate-900 dark:text-white block text-xs">
                {{ order.room?.room_number ? 'Room ' + order.room.room_number : 'N/A' }}
              </span>
            </div>
          </div>

          <div class="flex items-center justify-between text-xs pt-0.5">
            <span class="px-2 py-0.5 rounded-md text-[10px] font-extrabold border" :class="paymentClass(order.payment_type)">
              {{ paymentLabel(order.payment_type) }}
            </span>
            <span class="font-black text-xs text-slate-900 dark:text-white">
              {{ formatCurrency(order.total) }}
            </span>
          </div>
        </div>
      </div>

      <!-- Desktop Ultra-Compact Table View -->
      <div class="hidden sm:block overflow-x-auto w-full">
        <table class="w-full text-left border-collapse">
          <thead class="bg-slate-50/90 dark:bg-slate-950 border-b border-slate-200 dark:border-slate-800">
            <tr class="text-[10px] font-black text-slate-500 dark:text-slate-400 uppercase tracking-wider select-none">
              <th class="px-2.5 py-2.5 whitespace-nowrap">Order Ref</th>
              <th class="px-2.5 py-2.5 whitespace-nowrap">Guest</th>
              <th class="px-2.5 py-2.5 whitespace-nowrap">Room</th>
              <th class="px-2.5 py-2.5 whitespace-nowrap">Payment</th>
              <th class="px-2.5 py-2.5 text-center whitespace-nowrap">Status</th>
              <th class="px-2.5 py-2.5 text-right whitespace-nowrap">Total</th>
              <th class="px-2.5 py-2.5 whitespace-nowrap">Date</th>
              <th class="px-2.5 py-2.5 text-right whitespace-nowrap pr-3">Actions</th>
            </tr>
          </thead>

          <tbody class="divide-y divide-slate-100 dark:divide-slate-800/60 text-xs">
            <tr
              v-for="order in orders"
              :key="order.id"
              class="hover:bg-slate-50/70 dark:hover:bg-slate-800/40 transition-colors duration-150 group"
            >
              <!-- Order Ref -->
              <td class="px-2.5 py-2.5 whitespace-nowrap">
                <span class="font-mono font-extrabold text-blue-600 dark:text-blue-400 text-xs">
                  {{ order.order_number || `ORD-${String(order.id).padStart(6, '0')}` }}
                </span>
              </td>

              <!-- Guest -->
              <td class="px-2.5 py-2.5">
                <div class="flex items-center gap-1.5 max-w-[140px]">
                  <div class="w-5 h-5 rounded-full bg-slate-100 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-slate-700 dark:text-slate-300 font-black text-[9px] flex items-center justify-center flex-shrink-0">
                    {{ (getGuestDisplayName(order.guest)?.[0] || 'G').toUpperCase() }}
                  </div>
                  <div class="min-w-0 flex-1">
                    <div class="font-bold text-slate-900 dark:text-white truncate text-xs">
                      {{ getGuestDisplayName(order.guest) }}
                    </div>
                    <div class="text-[9px] text-slate-500 dark:text-slate-400 font-medium truncate">
                      {{ order.guest?.phone || '-' }}
                    </div>
                  </div>
                </div>
              </td>

              <!-- Room -->
              <td class="px-2.5 py-2.5 whitespace-nowrap">
                <span v-if="order.room?.room_number" class="inline-flex items-center gap-1 px-2 py-0.5 rounded-lg bg-slate-100 dark:bg-slate-800 text-slate-800 dark:text-slate-200 font-bold text-xs border border-slate-200 dark:border-slate-700">
                  <BedDouble class="w-3 h-3 text-slate-400" />
                  Room {{ order.room.room_number }}
                </span>
                <span v-else class="text-slate-400 text-xs italic">N/A</span>
              </td>

              <!-- Payment -->
              <td class="px-2.5 py-2.5 whitespace-nowrap">
                <span
                  class="inline-flex items-center gap-1 rounded-lg px-2 py-0.5 text-[10px] font-extrabold border"
                  :class="paymentClass(order.payment_type)"
                >
                  {{ paymentLabel(order.payment_type) }}
                </span>
              </td>

              <!-- Status -->
              <td class="px-2.5 py-2.5 text-center whitespace-nowrap">
                <span
                  class="inline-flex items-center gap-1 rounded-full px-2 py-0.5 text-[10px] font-extrabold border"
                  :class="statusClass(order.status)"
                >
                  <span class="h-1.5 w-1.5 rounded-full flex-shrink-0" :class="statusDot(order.status)"></span>
                  <span>{{ statusLabel(order.status) }}</span>
                </span>
              </td>

              <!-- Total -->
              <td class="px-2.5 py-2.5 text-right whitespace-nowrap font-extrabold text-slate-900 dark:text-white text-xs">
                {{ formatCurrency(order.total) }}
              </td>

              <!-- Date -->
              <td class="px-2.5 py-2.5 whitespace-nowrap text-xs text-slate-500 dark:text-slate-400 font-medium">
                {{ formatDate(order.order_time) }}
              </td>

              <!-- Actions Dropdown Popup -->
              <td class="px-2.5 py-2.5 text-right whitespace-nowrap pr-3 relative">
                <div class="relative inline-block">
                  <button
                    @click="toggleDropdown(order.id, $event)"
                    class="p-1 rounded-lg text-slate-400 hover:text-slate-700 dark:hover:text-white hover:bg-slate-100 dark:hover:bg-slate-800 transition cursor-pointer"
                    :class="{ 'bg-slate-100 dark:bg-slate-800 text-slate-900 dark:text-white': openDropdown === String(order.id) }"
                    title="Actions"
                  >
                    <MoreVertical class="w-3.5 h-3.5" />
                  </button>

                  <!-- Popup Menu Card -->
                  <div
                    v-if="openDropdown === String(order.id)"
                    class="absolute right-0 top-7 z-50 w-36 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl shadow-2xl p-1 space-y-0.5 text-left"
                    @click.stop
                  >
                    <button
                      @click="viewOrder(order)"
                      class="w-full flex items-center gap-2 px-2 py-1.5 rounded-lg text-xs font-bold text-slate-700 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800 transition cursor-pointer"
                    >
                      <Eye class="w-3.5 h-3.5 text-blue-500" />
                      <span>View</span>
                    </button>

                    <button
                      @click="changeStatus(order)"
                      class="w-full flex items-center gap-2 px-2 py-1.5 rounded-lg text-xs font-bold text-emerald-600 dark:text-emerald-400 hover:bg-emerald-50 dark:hover:bg-emerald-500/10 transition cursor-pointer"
                    >
                      <RefreshCw class="w-3.5 h-3.5 text-emerald-500" />
                      <span>Status</span>
                    </button>

                    <button
                      @click="editOrder(order)"
                      class="w-full flex items-center gap-2 px-2 py-1.5 rounded-lg text-xs font-bold text-amber-600 dark:text-amber-400 hover:bg-amber-50 dark:hover:bg-amber-500/10 transition cursor-pointer"
                    >
                      <Edit class="w-3.5 h-3.5 text-amber-500" />
                      <span>Edit</span>
                    </button>

                    <div class="border-t border-slate-100 dark:border-slate-800 my-0.5"></div>

                    <button
                      @click="deleteOrder(order)"
                      class="w-full flex items-center gap-2 px-2 py-1.5 rounded-lg text-xs font-bold text-rose-600 dark:text-rose-400 hover:bg-rose-50 dark:hover:bg-rose-500/10 transition cursor-pointer"
                    >
                      <Trash2 class="w-3.5 h-3.5 text-rose-500" />
                      <span>Delete</span>
                    </button>
                  </div>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- Compact Pagination Bar -->
      <div
        class="flex flex-col sm:flex-row items-center justify-between gap-3 border-t border-slate-200 dark:border-slate-800 bg-slate-50/80 dark:bg-slate-950 p-3 text-xs font-sans"
      >
        <div class="flex flex-wrap items-center gap-3 text-slate-600 dark:text-slate-400">
          <div class="flex items-center gap-1.5">
            <span class="font-bold text-slate-700 dark:text-slate-300">Items per page:</span>
            <select
              :value="perPage"
              @change="changePerPage"
              class="px-2 py-0.5 rounded-lg border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 text-slate-900 dark:text-white font-black focus:outline-none focus:border-blue-500 cursor-pointer shadow-2xs"
            >
              <option :value="5">5</option>
              <option :value="10">10</option>
              <option :value="20">20</option>
              <option :value="50">50</option>
            </select>
          </div>

          <div class="text-[11px] font-medium">
            Showing <span class="font-extrabold text-slate-900 dark:text-white">{{ orders.length }}</span> of
            <span class="font-extrabold text-slate-900 dark:text-white">{{ total }}</span> orders
          </div>
        </div>

        <div class="flex items-center gap-1">
          <button
            type="button"
            class="p-1.5 rounded-lg border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 text-slate-700 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800 disabled:opacity-40 disabled:cursor-not-allowed transition cursor-pointer flex items-center gap-1 font-bold text-xs"
            :disabled="currentPage === 1"
            @click="previousPage(currentPage)"
          >
            <ChevronLeft class="w-3.5 h-3.5" />
            <span class="hidden sm:inline">Prev</span>
          </button>

          <div class="flex items-center gap-1 px-2 font-black text-xs">
            <span class="text-blue-600 dark:text-blue-400">Page {{ currentPage }}</span>
            <span class="text-slate-400">/</span>
            <span class="text-slate-600 dark:text-slate-400">{{ lastPage }}</span>
          </div>

          <button
            type="button"
            class="p-1.5 rounded-lg border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 text-slate-700 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800 disabled:opacity-40 disabled:cursor-not-allowed transition cursor-pointer flex items-center gap-1 font-bold text-xs"
            :disabled="currentPage >= lastPage"
            @click="nextPage(currentPage, lastPage)"
          >
            <span class="hidden sm:inline">Next</span>
            <ChevronRight class="w-3.5 h-3.5" />
          </button>
        </div>
      </div>
    </template>
  </div>
</template>

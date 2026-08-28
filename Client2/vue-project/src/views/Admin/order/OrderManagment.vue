<script setup lang="ts">
import { ref, onMounted, computed } from 'vue'
import { useRouter } from 'vue-router'

import DashboardLayout from '@/Layouts/DashboardLayout.vue'
import OrderHeader from '@/components/order/OrderHeader.vue'
import OrderStatistics from '@/components/order/OrderStatistics.vue'
import OrderFilters from '@/components/order/OrderFilters.vue'
import OrderTable from '@/components/order/OrderTable.vue'

import OrderDetailsDialog from '@/components/order/OrderDetailsDialog.vue'
import ChangeStatusDialog from '@/components/order/ChangeStatusDialog.vue'
import DeleteOrderDialog from '@/components/order/DeleteOrderDialog.vue'

import { useOrderStore } from '@/stores/orderStore'

import type { Order, OrderFilters as OrderFilterType } from '@/types/order'

/*
|--------------------------------------------------------------------------
| Router
|--------------------------------------------------------------------------
*/

const router = useRouter()

/*
|--------------------------------------------------------------------------
| Store
|--------------------------------------------------------------------------
*/

const orderStore = useOrderStore()

/*
|--------------------------------------------------------------------------
| Dialog State
|--------------------------------------------------------------------------
*/

const showDetailsDialog = ref(false)
const showStatusDialog = ref(false)
const showDeleteDialog = ref(false)
const selectedOrder = ref<Order | null>(null)

const filters = ref<OrderFilterType>({
  search: '',
  status: '',
  payment_type: '',
  date_from: '',
  date_to: '',
  page: 1,
  per_page: 15,
})

/*
|--------------------------------------------------------------------------
| Computed Properties with Default Values
|--------------------------------------------------------------------------
*/

const currentPage = computed(() => orderStore.currentPage || 1)
const lastPage = computed(() => orderStore.lastPage || 1)
const total = computed(() => orderStore.total || 0)
const orders = computed(() => orderStore.orders || [])

onMounted(() => {
  loadOrders()
})

async function loadOrders() {
  await orderStore.fetchOrders(filters.value)
}

function updateFilters(value: OrderFilterType) {
  filters.value = value
}

function searchOrders() {
  filters.value.page = 1
  loadOrders()
}

function resetFilters() {
  filters.value = {
    search: '',
    status: '',
    payment_type: '',
    date_from: '',
    date_to: '',
    page: 1,
    per_page: 15,
  }
  loadOrders()
}

function changePage(page: number) {
  filters.value.page = page
  loadOrders()
}

function changePerPage(value: number) {
  filters.value.per_page = value
  filters.value.page = 1
  loadOrders()
}

/*
|--------------------------------------------------------------------------
| Navigation to Add Order Page
|--------------------------------------------------------------------------
*/

function openCreate() {
  // Navigate to create order page
  router.push('/orders/create')
}

function openEdit(order: Order) {
  // Navigate to edit order page
  router.push(`/orders/${order.id}/edit`)
}

function openView(order: Order) {
  // Use details dialog for view
  selectedOrder.value = order
  showDetailsDialog.value = true
}

function openStatus(order: Order) {
  selectedOrder.value = order
  showStatusDialog.value = true
}

function openDelete(order: Order) {
  selectedOrder.value = order
  showDeleteDialog.value = true
}

/*
|--------------------------------------------------------------------------
| Dialog Close
|--------------------------------------------------------------------------
*/

function closeDialogs() {
  showDetailsDialog.value = false
  showStatusDialog.value = false
  showDeleteDialog.value = false
  selectedOrder.value = null
}

/*
|--------------------------------------------------------------------------
| Change Status
|--------------------------------------------------------------------------
*/

async function updateStatus(status: string) {
  if (!selectedOrder.value) return

  await orderStore.updateStatus(selectedOrder.value.id, status)

  closeDialogs()
  loadOrders()
}

/*
|--------------------------------------------------------------------------
| Delete Order
|--------------------------------------------------------------------------
*/

async function deleteOrder() {
  if (!selectedOrder.value) return

  await orderStore.deleteOrder(selectedOrder.value.id)

  closeDialogs()
  loadOrders()
}

const isFullscreen = ref(false)

function toggleFullscreen() {
  isFullscreen.value = !isFullscreen.value
}
</script>

<template>
  <DashboardLayout>
    <div
      class="space-y-6 bg-slate-50 dark:bg-slate-950 min-h-screen p-4 sm:p-6 max-w-full overflow-hidden font-sans"
      :class="{ 'fixed inset-0 z-50 p-6 overflow-y-auto bg-white dark:bg-slate-950': isFullscreen }"
    >
      <!-- Header -->
      <OrderHeader @create="openCreate" />

      <!-- Statistics -->
      <OrderStatistics :statistics="orderStore.statistics" :loading="orderStore.loading" />

      <!-- Filters & Toolbar -->
      <OrderFilters
        :filters="filters"
        :loading="orderStore.loading"
        :is-fullscreen="isFullscreen"
        @update:filters="updateFilters"
        @search="searchOrders"
        @reset="resetFilters"
        @refresh="loadOrders"
        @create="openCreate"
        @toggle-fullscreen="toggleFullscreen"
      />

      <!-- Table -->
      <OrderTable
        :orders="orders"
        :loading="orderStore.loading"
        :current-page="currentPage"
        :last-page="lastPage"
        :per-page="filters.per_page"
        :total="total"
        @view="openView"
        @edit="openEdit"
        @status="openStatus"
        @delete="openDelete"
        @page-change="changePage"
        @per-page-change="changePerPage"
      />

      <!-- Details Dialog (View only) -->
      <OrderDetailsDialog v-model="showDetailsDialog" :order="selectedOrder" />

      <!-- Status Dialog -->
      <ChangeStatusDialog
        v-model="showStatusDialog"
        :order="selectedOrder"
        @update="updateStatus"
      />

      <!-- Delete Dialog -->
      <DeleteOrderDialog v-model="showDeleteDialog" :order="selectedOrder" @confirm="deleteOrder" />
    </div>
  </DashboardLayout>
</template>

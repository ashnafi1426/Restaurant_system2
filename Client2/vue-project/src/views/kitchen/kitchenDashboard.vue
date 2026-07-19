<script setup lang="ts">
import { ref, computed, onMounted, onBeforeUnmount } from 'vue'
import { storeToRefs } from 'pinia'
import { useKitchenStore } from '@/stores/kitchenStore'
import DashboardLayout from '@/layouts/DashboardLayout.vue'
import KitchenHeader from '@/components/kitchen/KitchenHeader.vue'
import KitchenStats from '@/components/kitchen/KitchenStats.vue'
import KitchenQueue from '@/components/kitchen/KitchenQueue.vue'
import RecentOrdersActivity from '@/components/kitchen/RecentOrdersActivity.vue'
import PopularMenu from '@/components/kitchen/PopularMenu.vue'
import KitchenEfficiency from '@/components/kitchen/KitchenEfficiency.vue'
import KitchenFooterBar from '@/components/kitchen/KitchenFooterBar.vue'
import KitchenOrderDetailsDialog from '@/components/kitchen/KitchenOrderDetailsDialog.vue'

import type { KitchenOrder } from '@/types/kitchen'

const kitchenStore = useKitchenStore()

const {
  pendingOrders,
  preparingOrders,
  readyOrders,
  completedOrders,
  statistics,
  loading,
  actionLoading,
} = storeToRefs(kitchenStore)

const detailsDialogRef = ref<InstanceType<typeof KitchenOrderDetailsDialog> | null>(null)
const autoRefresh = ref(true)
const refreshing = ref(false)
let refreshTimer: number | undefined

// Combine all orders for recent activity feed
const allOrders = computed(() => [
  ...(pendingOrders.value || []),
  ...(preparingOrders.value || []),
  ...(readyOrders.value || []),
  ...(completedOrders.value || []),
])

async function refreshDashboard() {
  refreshing.value = true
  try {
    await kitchenStore.refreshDashboard()
  } finally {
    refreshing.value = false
  }
}

async function startPreparing(order: KitchenOrder) {
  try {
    await kitchenStore.startPreparing(order.id)
  } catch (error: any) {
    console.error('Failed to start preparing:', error)
  }
}

async function markReady(order: KitchenOrder) {
  try {
    await kitchenStore.markReady(order.id)
  } catch (error: any) {
    console.error('Failed to mark ready:', error)
  }
}

async function markServed(order: KitchenOrder) {
  try {
    await kitchenStore.markServed(order.id)
  } catch (error: any) {
    console.error('Failed to mark served:', error)
  }
}

function openOrder(order: KitchenOrder) {
  if (detailsDialogRef.value) {
    detailsDialogRef.value.open(order)
  }
}

function startAutoRefresh() {
  stopAutoRefresh()
  refreshTimer = window.setInterval(async () => {
    if (!autoRefresh.value) return
    await kitchenStore.refreshDashboard()
  }, 10000)
}

function stopAutoRefresh() {
  if (refreshTimer) {
    clearInterval(refreshTimer)
    refreshTimer = undefined
  }
}

onMounted(async () => {
  await kitchenStore.refreshDashboard()
  startAutoRefresh()
})

onBeforeUnmount(() => {
  stopAutoRefresh()
})
</script>

<template>
  <DashboardLayout>
    <div class="min-h-screen bg-slate-50">
      <!-- Header -->
      <KitchenHeader
        :loading="loading"
        :auto-refresh="autoRefresh"
        @refresh="refreshDashboard"
        @toggle-auto-refresh="autoRefresh = !autoRefresh"
        @update-menu="() => {}"
        @new-ticket="() => {}"
      />

      <!-- Statistics -->
      <div class="px-8 pt-6">
        <KitchenStats :statistics="statistics" :loading="loading" />
      </div>

      <!-- Main Content -->
      <div class="grid grid-cols-1 gap-6 px-8 py-6 xl:grid-cols-12">
        <!-- Kitchen Queue -->
        <div class="xl:col-span-9">
          <KitchenQueue
            :pending-orders="pendingOrders"
            :preparing-orders="preparingOrders"
            :ready-orders="readyOrders"
            :completed-orders="completedOrders"
            :processing="actionLoading !== null"
            @view="openOrder"
            @start="startPreparing"
            @ready="markReady"
            @served="markServed"
          />
        </div>

        <!-- Right Sidebar -->
        <div class="space-y-6 xl:col-span-3">
          <RecentOrdersActivity :orders="allOrders" />
          <PopularMenu />
          <KitchenEfficiency :statistics="statistics" />
        </div>
      </div>

      <!-- Footer -->
      <KitchenFooterBar :statistics="statistics" />

      <!-- Order Details Dialog -->
      <KitchenOrderDetailsDialog ref="detailsDialogRef" />
    </div>
  </DashboardLayout>
</template>

<script setup lang="ts">
import { ref, onMounted, onBeforeUnmount } from 'vue'
import { storeToRefs } from 'pinia'
import { useKitchenStore } from '@/stores/kitchenStore'
import DashboardLayout from '@/layouts/DashboardLayout.vue'
import KitchenHeader from '@/components/kitchen/KitchenHeader.vue'
import KitchenStats from '@/components/kitchen/KitchenStats.vue'
import KitchenQueue from '@/components/kitchen/KitchenQueue.vue'
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

async function refreshDashboard() {
  refreshing.value = true
  try {
    await kitchenStore.refreshDashboard()
  } finally {
    refreshing.value = false
  }
}

async function startPreparing(order: KitchenOrder) {
  await kitchenStore.startPreparing(order.id)
  await refreshDashboard()
}

async function markReady(order: KitchenOrder) {
  await kitchenStore.markReady(order.id)
  await refreshDashboard()
}

async function markServed(order: KitchenOrder) {
  await kitchenStore.markServed(order.id)
  await refreshDashboard()
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

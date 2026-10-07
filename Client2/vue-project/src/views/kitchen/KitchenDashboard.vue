<script setup lang="ts">
import { ref, computed, onMounted, onBeforeUnmount, watch } from 'vue'
import { useRouter } from 'vue-router'
import { storeToRefs } from 'pinia'
import { useKitchenStore } from '@/stores/kitchenStore'
import { useHotelStore } from '@/stores/hotelStore'
import { useLanguageStore } from '@/stores/language'
import DashboardLayout from '@/Layouts/DashboardLayout.vue'
import KitchenStats from '@/components/kitchen/KitchenStats.vue'
import KitchenQueue from '@/components/kitchen/KitchenQueue.vue'
import RecentOrdersActivity from '@/components/kitchen/RecentOrdersActivity.vue'
import PopularMenu from '@/components/kitchen/PopularMenu.vue'
import KitchenEfficiency from '@/components/kitchen/KitchenEfficiency.vue'
import KitchenFooterBar from '@/components/kitchen/KitchenFooterBar.vue'
import KitchenOrderDetailsDialog from '@/components/kitchen/KitchenOrderDetailsDialog.vue'
import {
  CookingPot,
  RefreshCw,
  Building2,
  AlertCircle,
  Clock,
  Sparkles,
} from 'lucide-vue-next'

import type { KitchenOrder } from '@/types/kitchen'

const router = useRouter()
const kitchenStore = useKitchenStore()
const hotelStore = useHotelStore()
const languageStore = useLanguageStore()

const {
  pendingOrders,
  preparingOrders,
  readyOrders,
  completedOrders,
  statistics,
  loading,
  actionLoading,
  error,
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

function handleVisibilityChange() {
  if (document.hidden) {
    stopAutoRefresh()
  } else if (autoRefresh.value) {
    startAutoRefresh()
    refreshDashboard()
  }
}

onMounted(async () => {
  await kitchenStore.refreshDashboard()
  startAutoRefresh()
  document.addEventListener('visibilitychange', handleVisibilityChange)
})

watch(() => hotelStore.hotelId, async () => {
  await kitchenStore.refreshDashboard()
})

onBeforeUnmount(() => {
  stopAutoRefresh()
  document.removeEventListener('visibilitychange', handleVisibilityChange)
})
</script>

<template>
  <DashboardLayout>
    <div class="min-h-screen bg-slate-50 dark:bg-slate-950 py-4 md:py-6 transition-colors duration-300">
      <!-- Header Banner -->
      <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 p-5 sm:p-6 rounded-3xl shadow-xs flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
        <div class="flex items-center gap-3.5">
          <div class="w-12 h-12 rounded-2xl bg-gradient-to-br from-amber-500 to-orange-600 flex items-center justify-center shadow-lg shadow-amber-500/20 text-white flex-shrink-0">
            <CookingPot class="w-6 h-6 stroke-[2.2]" />
          </div>
          <div>
            <div class="flex items-center gap-2 flex-wrap">
              <h1 class="text-xl sm:text-2xl font-black text-slate-900 dark:text-white tracking-tight">{{ languageStore.t('Kitchen Dashboard', 'Kitchen Operations Dashboard') }}</h1>
              <span v-if="hotelStore.hotelName" class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-xs font-bold bg-blue-100 dark:bg-blue-900/30 text-blue-800 dark:text-blue-300 border border-blue-200 dark:border-blue-700/50">
                <Building2 class="w-3 h-3" />
                {{ hotelStore.hotelName }}
              </span>
            </div>
            <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">{{ languageStore.t('kitchen_dashboard_desc', 'Real-time culinary order pipeline, prep queue, and line efficiency monitor.') }}</p>
          </div>
        </div>

        <!-- Right Actions -->
        <div class="flex items-center gap-3">
          <div class="flex items-center gap-2 text-xs font-bold text-slate-600 dark:text-slate-400 bg-slate-100 dark:bg-slate-800/60 px-3 py-1.5 rounded-xl border border-slate-200/50 dark:border-slate-700/50">
            <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
            <span class="hidden sm:inline">{{ languageStore.t('live_queue', 'Live Queue (10s sync)') }}</span>
            <span class="sm:hidden">{{ languageStore.t('live', 'Live') }}</span>
          </div>

          <button
            @click="refreshDashboard"
            :disabled="refreshing || loading"
            class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-xl bg-amber-500 hover:bg-amber-600 text-slate-950 text-xs font-black transition cursor-pointer shadow-sm shadow-amber-500/20 disabled:opacity-50"
          >
            <RefreshCw :class="['w-3.5 h-3.5', { 'animate-spin': refreshing || loading }]" />
            <span>{{ languageStore.t('refresh', 'Refresh') }}</span>
          </button>
        </div>
      </div>

      <!-- Error Alert -->
      <div v-if="error" class="mb-6 p-4 rounded-2xl bg-rose-50 dark:bg-rose-950/40 border border-rose-200 dark:border-rose-900/50 flex items-center justify-between">
        <div class="flex items-center gap-3 text-rose-800 dark:text-rose-200 text-xs font-bold">
          <AlertCircle class="w-5 h-5 text-rose-500 flex-shrink-0" />
          <span>{{ error }}</span>
        </div>
        <button
          @click="refreshDashboard"
          class="px-3 py-1 rounded-lg bg-rose-600 hover:bg-rose-700 text-white text-xs font-bold transition cursor-pointer"
        >
          {{ languageStore.t('retry', 'Retry') }}
        </button>
      </div>

      <!-- Statistics -->
      <div class="mb-6">
        <KitchenStats :statistics="statistics" :loading="loading" />
      </div>

      <!-- Main Content -->
      <div class="grid grid-cols-1 gap-6 py-2 md:py-4 lg:grid-cols-12">
        <!-- Kitchen Queue -->
        <div class="lg:col-span-7 xl:col-span-8">
          <KitchenQueue
            :pending-orders="pendingOrders"
            :preparing-orders="preparingOrders"
            :ready-orders="readyOrders"
            :completed-orders="completedOrders"
            :processing="actionLoading !== null"
            :loading="loading"
            @view="openOrder"
            @start="startPreparing"
            @ready="markReady"
            @served="markServed"
          />
        </div>

        <!-- Right Sidebar -->
        <div class="space-y-6 lg:col-span-5 xl:col-span-4">
          <RecentOrdersActivity :orders="allOrders" />
          <PopularMenu />
          <KitchenEfficiency :statistics="statistics" />
        </div>
      </div>

      <!-- Footer -->
      <div class="mt-6">
        <KitchenFooterBar :statistics="statistics" />
      </div>

      <!-- Order Details Dialog -->
      <KitchenOrderDetailsDialog ref="detailsDialogRef" />
    </div>
  </DashboardLayout>
</template>

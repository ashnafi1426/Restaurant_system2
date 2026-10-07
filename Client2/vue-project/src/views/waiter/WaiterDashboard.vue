<template>
  <DashboardLayout>
    <div class="min-h-screen bg-slate-50 dark:bg-slate-950 text-slate-900 dark:text-slate-100 p-3 sm:p-6 lg:p-8 transition-colors duration-200">
      <div class="max-w-7xl mx-auto space-y-6">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
          <div>
            <div class="flex items-center gap-3">
              <h1 class="text-2xl sm:text-3xl font-extrabold text-slate-900 dark:text-white tracking-tight">{{ languageStore.t('waiter_dashboard', 'Waiter Dashboard') }}</h1>
              <span v-if="hotelStore.hotelName" class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-bold bg-blue-50 dark:bg-blue-500/10 text-blue-700 dark:text-blue-400 border border-blue-200 dark:border-blue-500/20">
                <Building2 class="w-3.5 h-3.5" />
                {{ hotelStore.hotelName }}
              </span>
              <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-bold bg-emerald-50 dark:bg-emerald-500/10 text-emerald-700 dark:text-emerald-400 border border-emerald-200 dark:border-emerald-500/20">
                <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
                {{ languageStore.t('shift_active', 'Shift Active') }}
              </span>
            </div>
            <p class="text-xs sm:text-sm text-slate-500 dark:text-slate-400 mt-1">{{ languageStore.t('waiter_dashboard_desc', 'Real-time room delivery management and performance overview') }}</p>
          </div>

          <div class="flex items-center gap-2">
            <button 
              type="button"
              @click="loadDashboard(true)"
              :disabled="loading"
              class="px-3.5 py-2 bg-white dark:bg-slate-900 hover:bg-slate-100 dark:hover:bg-slate-800 text-slate-700 dark:text-slate-200 rounded-xl text-xs font-semibold flex items-center gap-1.5 transition border border-slate-200 dark:border-slate-800 shadow-xs disabled:opacity-50 cursor-pointer"
            >
              <RefreshCw class="w-3.5 h-3.5" :class="{ 'animate-spin': loading }" />
              {{ languageStore.t('refresh', 'Refresh') }}
            </button>
          </div>
        </div>

        <!-- Skeleton Loader - Shows immediately only if no cached data exists -->
        <div v-if="loading && !recentAssignments.length && !activeDelivery && !stats.todayDeliveries" class="space-y-6">
          <!-- Stats Cards Skeleton -->
          <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
            <SkeletonLoaders v-for="i in 4" :key="i" type="stat-card" />
          </div>
          
          <!-- Recent Assignments Skeleton -->
          <div class="bg-white dark:bg-slate-900/90 border border-slate-200 dark:border-slate-800 rounded-3xl p-6 shadow-xs">
            <div class="h-6 bg-slate-200 dark:bg-slate-700 rounded w-48 mb-6 animate-pulse"></div>
            <SkeletonLoaders type="list-items" :item-count="3" />
          </div>
        </div>

        <div v-else-if="error" class="bg-rose-50 dark:bg-rose-950/40 border border-rose-200 dark:border-rose-800 rounded-2xl p-6">
          <p class="text-rose-700 dark:text-rose-400 font-semibold text-sm">Error loading dashboard</p>
          <p class="text-rose-600 dark:text-rose-300 text-xs mt-1">{{ error }}</p>
          <button 
            @click="loadDashboard"
            class="mt-4 px-4 py-2 bg-rose-600 text-white rounded-xl text-xs font-semibold hover:bg-rose-700 transition"
          >
            Retry
          </button>
        </div>

        <div v-else class="space-y-6">
          <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
            <!-- Completed Deliveries -->
            <div class="stat-card" v-memo="[stats.todayDeliveries]">
              <div class="stat-header">
                <span class="stat-label">{{ languageStore.t('completed', 'Completed') }}</span>
                <CheckCircle2 class="stat-icon stat-icon-emerald" />
              </div>
              <div class="stat-value">{{ stats.todayDeliveries }}</div>
              <p class="stat-desc">{{ languageStore.t('successfully_delivered', 'Successfully delivered') }}</p>
            </div>

            <!-- Ready for Pickup -->
            <div class="stat-card" v-memo="[stats.pendingDeliveries]">
              <div class="stat-header">
                <span class="stat-label">{{ languageStore.t('ready_for_pickup', 'Ready for Pickup') }}</span>
                <Clock class="stat-icon stat-icon-amber" />
              </div>
              <div class="stat-value">{{ stats.pendingDeliveries }}</div>
              <p class="stat-desc">{{ languageStore.t('awaiting_waiter_pickup', 'Awaiting waiter pickup') }}</p>
            </div>

            <!-- On Delivery -->
            <div class="stat-card" v-memo="[stats.onDelivery]">
              <div class="stat-header">
                <span class="stat-label">{{ languageStore.t('on_delivery', 'On Delivery') }}</span>
                <Truck class="stat-icon stat-icon-teal" />
              </div>
              <div class="stat-value">{{ stats.onDelivery }}</div>
              <p class="stat-desc">{{ languageStore.t('active_room_deliveries', 'Active room deliveries') }}</p>
            </div>

            <!-- Avg Delivery Time -->
            <div class="stat-card" v-memo="[stats.avgDeliveryTime]">
              <div class="stat-header">
                <span class="stat-label">{{ languageStore.t('avg_delivery_time', 'Avg Delivery Time') }}</span>
                <Timer class="stat-icon stat-icon-indigo" />
              </div>
              <div class="stat-value">
                {{ stats.avgDeliveryTime }}<span class="stat-unit">min</span>
              </div>
              <p class="stat-desc">{{ languageStore.t('target_delivery_time', 'Target: < 25 mins') }}</p>
            </div>
          </div>

          <div v-if="activeDelivery" class="active-delivery-banner">
            <div class="active-delivery-content">
              <div class="active-delivery-info">
                <div class="active-delivery-badge">
                  <span class="pulse-dot"></span>
                  Active Delivery In Progress
                </div>
                <h3 class="active-delivery-title">Order #{{ activeDelivery.order_number || activeDelivery.id }}</h3>
                <p class="active-delivery-details">
                  Delivering to Room <span class="room-badge">{{ activeDelivery.room_number || 'N/A' }}</span> 
                  • Guest: <span class="guest-name">{{ activeDelivery.guest_name || 'Guest' }}</span>
                </p>
              </div>
              <router-link to="/waiter/on-delivery" class="active-delivery-btn">
                View Delivery Tracker
              </router-link>
            </div>
          </div>
          <div class="bg-white dark:bg-slate-900/90 border border-slate-200 dark:border-slate-800 rounded-2xl overflow-hidden shadow-sm dark:shadow-xl">
            <div class="p-5 border-b border-slate-200 dark:border-slate-800 flex items-center justify-between">
              <div>
                <h2 class="text-lg font-bold text-slate-900 dark:text-white">Recent Assignments</h2>
                <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">Orders assigned to you for delivery</p>
              </div>
              <router-link
                to="/waiter/assigned-orders"
                class="text-xs font-bold text-indigo-600 dark:text-indigo-400 hover:underline flex items-center gap-1"
              >
                View All
                <ArrowRight class="w-3.5 h-3.5" />
              </router-link>
            </div>

            <div v-if="loading" class="text-center py-16 flex flex-col items-center justify-center gap-3">
              <Loader2 class="w-8 h-8 text-blue-600 dark:text-blue-400 animate-spin" />
              <p class="text-slate-600 dark:text-slate-400 text-xs font-bold">Loading recent assignments...</p>
            </div>

            <div v-else-if="recentAssignments.length === 0" class="text-center py-12">
              <Inbox class="w-10 h-10 text-slate-400 dark:text-slate-600 mx-auto mb-2" />
              <p class="text-slate-700 dark:text-slate-300 text-sm font-semibold">No recent assignments</p>
              <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">Check back later for new orders</p>
            </div>

            <div v-else class="w-full overflow-x-auto">
              <table class="w-full text-left border-collapse">
                <thead class="bg-slate-50 dark:bg-slate-950/80 border-b border-slate-200 dark:border-slate-800 text-[11px] sm:text-xs font-semibold text-slate-600 dark:text-slate-400 uppercase tracking-wider">
                  <tr>
                    <th class="px-4 py-3.5 whitespace-nowrap">Order #</th>
                    <th class="px-4 py-3.5 whitespace-nowrap">Room</th>
                    <th class="px-4 py-3.5 whitespace-nowrap">Guest</th>
                    <th class="px-4 py-3.5 whitespace-nowrap">Items</th>
                    <th class="px-4 py-3.5 whitespace-nowrap">Status</th>
                    <th class="px-4 py-3.5 text-right whitespace-nowrap">Actions</th>
                  </tr>
                </thead>
                <tbody class="divide-y divide-slate-200 dark:divide-slate-800 text-xs sm:text-sm">
                  <tr v-for="assignment in recentAssignments" :key="assignment.id" v-memo="[assignment.id, assignment.status]" class="hover:bg-slate-50/80 dark:hover:bg-slate-950/50 transition">
                    <td class="px-4 py-3 font-bold text-slate-900 dark:text-white max-w-[140px] truncate" :title="assignment.order_number || assignment.order_id">
                      #{{ assignment.order_number || assignment.order_id || assignment.id.substring(0,8) }}
                    </td>
                    <td class="px-4 py-3 whitespace-nowrap">
                      <span class="px-2.5 py-0.5 bg-indigo-50 dark:bg-indigo-500/10 text-indigo-700 dark:text-indigo-400 border border-indigo-200 dark:border-indigo-500/20 rounded text-xs font-bold">
                        {{ assignment.room_number || 'N/A' }}
                      </span>
                    </td>
                    <td class="px-4 py-3 font-medium text-slate-700 dark:text-slate-300 max-w-[130px] truncate" :title="assignment.guest_name">
                      {{ assignment.guest_name || 'Guest' }}
                    </td>
                    <td class="px-4 py-3 whitespace-nowrap">
                      <span class="px-2 py-0.5 bg-blue-50 dark:bg-blue-500/10 text-blue-700 dark:text-blue-400 border border-blue-200 dark:border-blue-500/20 rounded text-xs font-bold">
                        {{ typeof assignment.items === 'number' ? assignment.items : (Array.isArray(assignment.items) ? assignment.items.length : 1) }} items
                      </span>
                    </td>
                    <td class="px-4 py-3 whitespace-nowrap">
                      <span :class="[
                        'px-2.5 py-0.5 rounded-full text-[10px] sm:text-xs font-bold uppercase tracking-wider border',
                        assignment.status === 'delivered' ? 'bg-emerald-50 dark:bg-emerald-500/10 text-emerald-700 dark:text-emerald-400 border-emerald-200 dark:border-emerald-500/20' :
                        assignment.status === 'on_delivery' ? 'bg-teal-50 dark:bg-teal-500/10 text-teal-700 dark:text-teal-400 border-teal-200 dark:border-teal-500/20' :
                        assignment.status === 'picked_up' ? 'bg-purple-50 dark:bg-purple-500/10 text-purple-700 dark:text-purple-400 border-purple-200 dark:border-purple-500/20' :
                        'bg-blue-50 dark:bg-blue-500/10 text-blue-700 dark:text-blue-400 border-blue-200 dark:border-blue-500/20'
                      ]">
                        {{ assignment.status || 'assigned' }}
                      </span>
                    </td>
                    <td class="px-4 py-3 text-right whitespace-nowrap">
                      <div class="relative inline-block text-left">
                        <button
                          type="button"
                          @click.stop="toggleMenu(assignment.id)"
                          class="w-8 h-8 rounded-lg bg-slate-100 dark:bg-slate-800 hover:bg-indigo-50 dark:hover:bg-indigo-500/20 text-slate-700 dark:text-slate-300 hover:text-indigo-600 dark:hover:text-indigo-400 flex items-center justify-center transition border border-slate-200 dark:border-slate-700 cursor-pointer"
                          :class="{ 'bg-indigo-50 dark:bg-indigo-900/30 text-indigo-600 dark:text-indigo-400 border-indigo-300': activeMenuId === String(assignment.id) }"
                          title="Actions"
                        >
                          <MoreVertical class="w-4 h-4" />
                        </button>

                        <div
                          v-if="activeMenuId === String(assignment.id)"
                          @click.stop
                          class="absolute right-0 top-9 w-48 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl shadow-2xl z-50 p-1 text-left space-y-0.5"
                        >
                          <button
                            type="button"
                            @click="openDetailsModal(assignment)"
                            class="w-full px-3 py-2 text-xs font-semibold text-slate-700 dark:text-slate-200 hover:bg-slate-100 dark:hover:bg-slate-800 hover:text-blue-600 dark:hover:text-blue-400 flex items-center gap-2 rounded-lg transition cursor-pointer"
                          >
                            <Eye class="w-3.5 h-3.5 text-blue-500" />
                            <span>{{ languageStore.t('quick_view', 'Quick View') }}</span>
                          </button>

                          <router-link
                            to="/waiter/assigned-orders"
                            class="w-full px-3 py-2 text-xs font-semibold text-slate-700 dark:text-slate-200 hover:bg-slate-100 dark:hover:bg-slate-800 hover:text-indigo-600 dark:hover:text-indigo-400 flex items-center gap-2 rounded-lg transition"
                          >
                            <ExternalLink class="w-3.5 h-3.5 text-indigo-500" />
                            <span>{{ languageStore.t('all_assigned_orders', 'Assigned Orders') }}</span>
                          </router-link>

                          <router-link
                            v-if="assignment.status === 'on_delivery' || assignment.status === 'picked_up'"
                            to="/waiter/on-delivery"
                            class="w-full px-3 py-2 text-xs font-semibold text-emerald-700 dark:text-emerald-400 hover:bg-emerald-50 dark:hover:bg-emerald-950/40 flex items-center gap-2 rounded-lg transition"
                          >
                            <Truck class="w-3.5 h-3.5 text-emerald-500" />
                            <span>{{ languageStore.t('delivery_tracker', 'Track Delivery') }}</span>
                          </router-link>
                        </div>
                      </div>
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Quick Order Details Modal -->
    <div
      v-if="showDetailModal && selectedOrder"
      class="fixed inset-0 z-50 bg-black/60 backdrop-blur-xs flex items-center justify-center p-4"
      @click.self="showDetailModal = false"
    >
      <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl max-w-md w-full p-6 space-y-4 shadow-2xl">
        <div class="flex justify-between items-center border-b border-slate-100 dark:border-slate-800 pb-3">
          <div class="flex items-center gap-2">
            <h3 class="text-base font-extrabold text-slate-900 dark:text-white">{{ languageStore.t('order_details', 'Order Details') }}</h3>
            <span class="text-xs font-mono font-bold text-blue-600 dark:text-blue-400">#{{ selectedOrder.order_number || selectedOrder.order_id || selectedOrder.id }}</span>
          </div>
          <button @click="showDetailModal = false" class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 p-1 rounded-lg transition cursor-pointer">
            <X class="w-4 h-4" />
          </button>
        </div>

        <div class="space-y-3 text-xs">
          <div class="flex justify-between py-1.5 border-b border-slate-100 dark:border-slate-800">
            <span class="text-slate-500 dark:text-slate-400">{{ languageStore.t('guest', 'Guest') }}:</span>
            <span class="font-bold text-slate-900 dark:text-white">{{ selectedOrder.guest_name || 'Guest' }}</span>
          </div>
          <div class="flex justify-between py-1.5 border-b border-slate-100 dark:border-slate-800">
            <span class="text-slate-500 dark:text-slate-400">{{ languageStore.t('room_table', 'Room / Table') }}:</span>
            <span class="font-bold text-slate-900 dark:text-white">{{ selectedOrder.room_number ? 'Room ' + selectedOrder.room_number : 'N/A' }}</span>
          </div>
          <div class="flex justify-between py-1.5 border-b border-slate-100 dark:border-slate-800">
            <span class="text-slate-500 dark:text-slate-400">{{ languageStore.t('status', 'Status') }}:</span>
            <span class="font-bold uppercase tracking-wider text-indigo-600 dark:text-indigo-400">{{ selectedOrder.status || 'Assigned' }}</span>
          </div>
          <div class="flex justify-between py-1.5 border-b border-slate-100 dark:border-slate-800">
            <span class="text-slate-500 dark:text-slate-400">{{ languageStore.t('items', 'Items') }}:</span>
            <span class="font-bold text-slate-900 dark:text-white">{{ typeof selectedOrder.items === 'number' ? selectedOrder.items : (Array.isArray(selectedOrder.items) ? selectedOrder.items.length : 1) }} items</span>
          </div>
          <div v-if="selectedOrder.delivery_address || selectedOrder.address" class="flex justify-between py-1.5 border-b border-slate-100 dark:border-slate-800">
            <span class="text-slate-500 dark:text-slate-400">{{ languageStore.t('address', 'Address') }}:</span>
            <span class="font-bold text-slate-900 dark:text-white">{{ selectedOrder.delivery_address || selectedOrder.address }}</span>
          </div>
          <div v-if="selectedOrder.notes || selectedOrder.special_instructions" class="py-1.5 bg-slate-50 dark:bg-slate-800/60 p-2.5 rounded-xl border border-slate-200 dark:border-slate-800">
            <span class="text-[10px] uppercase font-bold text-slate-400 block mb-1">{{ languageStore.t('notes', 'Notes') }}</span>
            <p class="text-xs text-slate-700 dark:text-slate-300 font-medium">{{ selectedOrder.notes || selectedOrder.special_instructions }}</p>
          </div>
        </div>

        <div class="pt-2 flex items-center justify-end gap-2">
          <router-link
            to="/waiter/assigned-orders"
            class="px-4 py-2 bg-indigo-600 text-white rounded-xl text-xs font-bold hover:bg-indigo-700 transition"
          >
            {{ languageStore.t('view_in_assigned', 'Full Management') }}
          </router-link>
        </div>
      </div>
    </div>
  </DashboardLayout>
</template>

<script setup lang="ts">
import { ref, onMounted, onUnmounted, watch } from 'vue'
import DashboardLayout from '@/Layouts/DashboardLayout.vue'
import SkeletonLoaders from '@/components/waiter/SkeletonLoaders.vue'
import waiterService from '@/services/waiterService'
import { useHotelStore } from '@/stores/hotelStore'
import { useLanguageStore } from '@/stores/language'
import {
  Building2,
  Loader2,
  MoreVertical,
  Eye,
  Truck,
  ExternalLink,
  X,
  CheckCircle2,
  Clock,
  Timer,
  RefreshCw,
  ArrowRight,
  Inbox
} from 'lucide-vue-next'

const hotelStore = useHotelStore()
const languageStore = useLanguageStore()

const loading = ref(true)
const error = ref<string | null>(null)
const stats = ref({
  todayDeliveries: 0,
  pendingDeliveries: 0,
  onDelivery: 0,
  avgDeliveryTime: 0,
})
const recentAssignments = ref<any[]>([])
const activeDelivery = ref<any | null>(null)
const activeMenuId = ref<string | null>(null)
const showDetailModal = ref(false)
const selectedOrder = ref<any | null>(null)
const showBelowFold = ref(false)

const getCacheKey = () => `waiter_dashboard_cache_${hotelStore.hotelId || 'default'}`

const restoreCachedData = () => {
  try {
    const cached = localStorage.getItem(getCacheKey())
    if (cached) {
      const parsed = JSON.parse(cached)
      if (parsed && typeof parsed === 'object') {
        if (parsed.today_stats) {
          const ts = parsed.today_stats
          stats.value = {
            todayDeliveries: ts.completed_deliveries ?? 0,
            pendingDeliveries: ts.pending_assignments ?? 0,
            onDelivery: ts.on_delivery_count ?? 0,
            avgDeliveryTime: Math.round(ts.average_delivery_time ?? 0),
          }
        }
        if (parsed.active_delivery) {
          activeDelivery.value = parsed.active_delivery
        } else if (Array.isArray(parsed.recent_assignments)) {
          const currentOnDelivery = parsed.recent_assignments.find((a: any) => a.status === 'on_delivery' || a.status === 'picked_up')
          if (currentOnDelivery) {
            activeDelivery.value = currentOnDelivery
          }
        }
        // Cached data allows instant paint with no blocking skeleton
        loading.value = false
      }
    }
  } catch (e) {
    // Ignore cache parse error
  }
}

const toggleMenu = (id: string | number) => {
  const key = String(id)
  activeMenuId.value = activeMenuId.value === key ? null : key
}

const openDetailsModal = (order: any) => {
  selectedOrder.value = order
  showDetailModal.value = true
  activeMenuId.value = null
}

const handleOutsideClick = () => {
  activeMenuId.value = null
}

const loadDashboard = async (isManualRefresh = false) => {
  try {
    // Only show skeleton if we don't have any cached or current data
    if (!recentAssignments.value.length && !activeDelivery.value && !stats.value.todayDeliveries) {
      loading.value = true
    }
    error.value = null

    const dashboardData = await waiterService.getDashboard({
      hotel_id: hotelStore.hotelId,
      ...(isManualRefresh ? { refresh: 'true' } : {})
    })

    if (dashboardData && dashboardData.today_stats) {
      const ts = dashboardData.today_stats
      stats.value = {
        todayDeliveries: ts.completed_deliveries ?? 0,
        pendingDeliveries: ts.pending_assignments ?? 0,
        onDelivery: ts.on_delivery_count ?? 0,
        avgDeliveryTime: Math.round(ts.average_delivery_time ?? 0),
      }
    }

    // Use recent_assignments and active_delivery from single dashboard response
    recentAssignments.value = dashboardData.recent_assignments || []

    activeDelivery.value = dashboardData.active_delivery ||
      recentAssignments.value.find((a: any) => a.status === 'on_delivery' || a.status === 'picked_up') ||
      null

    // Cache latest dashboard response for instant paint on next visit
    try {
      localStorage.setItem(getCacheKey(), JSON.stringify(dashboardData))
    } catch (e) {}

    showBelowFold.value = true
  } catch (err: any) {
    console.error('[WaiterDashboard] Error loading dashboard:', err)
    if (!recentAssignments.value.length) {
      error.value = err.message || 'Failed to load dashboard'
    }
  } finally {
    loading.value = false
  }
}

watch(() => hotelStore.hotelId, () => {
  restoreCachedData()
  loadDashboard()
})

onMounted(() => {
  restoreCachedData()
  loadDashboard()
  window.addEventListener('click', handleOutsideClick)
})

onUnmounted(() => {
  window.removeEventListener('click', handleOutsideClick)
})
</script>

<style scoped>
/* Optimized stat card styles - reduces render complexity */
.stat-card {
  @apply bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-5;
  will-change: auto;
}

.stat-header {
  @apply flex items-center justify-between mb-3;
}

.stat-label {
  @apply text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider;
}

.stat-icon {
  @apply w-4 h-4;
}

.stat-icon-emerald {
  @apply text-emerald-600 dark:text-emerald-400;
}

.stat-icon-amber {
  @apply text-amber-600 dark:text-amber-400;
}

.stat-icon-teal {
  @apply text-teal-600 dark:text-teal-400;
}

.stat-icon-indigo {
  @apply text-indigo-600 dark:text-indigo-400;
}

.stat-value {
  @apply text-3xl font-extrabold text-slate-900 dark:text-white;
}

.stat-unit {
  @apply text-sm font-bold text-slate-500 dark:text-slate-400 ml-1;
}

.stat-desc {
  @apply text-xs text-slate-500 dark:text-slate-400 mt-1;
}

/* Optimized active delivery banner - simpler gradient */
.active-delivery-banner {
  @apply bg-emerald-600 text-white rounded-2xl p-6;
  background: linear-gradient(135deg, #059669 0%, #0d9488 100%);
}

.active-delivery-content {
  @apply flex flex-col md:flex-row md:items-center justify-between gap-4;
}

.active-delivery-info {
  @apply space-y-1;
}

.active-delivery-badge {
  @apply inline-flex items-center gap-2 px-3 py-1 bg-white bg-opacity-20 rounded-full text-xs font-bold uppercase tracking-wider;
}

.pulse-dot {
  @apply w-2 h-2 rounded-full bg-white;
  animation: pulse 2s cubic-bezier(0.4, 0, 0.6, 1) infinite;
}

@keyframes pulse {
  0%, 100% {
    opacity: 1;
  }
  50% {
    opacity: 0.5;
  }
}

.active-delivery-title {
  @apply text-xl font-bold mt-2;
}

.active-delivery-details {
  @apply text-xs text-emerald-100;
}

.room-badge {
  @apply font-extrabold text-white text-sm bg-white bg-opacity-20 px-2 py-0.5 rounded;
}

.guest-name {
  @apply font-semibold text-white;
}

.active-delivery-btn {
  @apply px-5 py-2.5 bg-white text-emerald-700 hover:bg-emerald-50 rounded-xl text-xs font-bold transition whitespace-nowrap inline-block;
}
</style>

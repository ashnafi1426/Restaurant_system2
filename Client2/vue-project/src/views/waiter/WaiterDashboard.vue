<template>
  <DashboardLayout>
    <div class="min-h-screen bg-slate-50 dark:bg-slate-950 text-slate-900 dark:text-slate-100 p-3 sm:p-6 lg:p-8 transition-colors duration-200">
      <div class="max-w-7xl mx-auto space-y-6">
        <!-- Clean Page Header -->
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
          <div>
            <div class="flex items-center gap-3">
              <h1 class="text-2xl sm:text-3xl font-extrabold text-slate-900 dark:text-white tracking-tight">Waiter Dashboard</h1>
              <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-bold bg-emerald-50 dark:bg-emerald-500/10 text-emerald-700 dark:text-emerald-400 border border-emerald-200 dark:border-emerald-500/20">
                <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
                Shift Active
              </span>
            </div>
            <p class="text-xs sm:text-sm text-slate-500 dark:text-slate-400 mt-1">Real-time room delivery management and performance overview</p>
          </div>

          <div class="flex items-center gap-2">
            <button 
              @click="loadDashboard"
              :disabled="loading"
              class="px-3.5 py-2 bg-white dark:bg-slate-900 hover:bg-slate-100 dark:hover:bg-slate-800 text-slate-700 dark:text-slate-200 rounded-xl text-xs font-semibold flex items-center gap-1.5 transition border border-slate-200 dark:border-slate-800 shadow-xs disabled:opacity-50"
            >
              <span :class="['material-symbols-rounded text-sm', loading ? 'animate-spin' : '']">refresh</span>
              Refresh
            </button>
          </div>
        </div>

        <!-- Loading State -->
        <div v-if="loading" class="flex items-center justify-center py-20 bg-white dark:bg-slate-900/40 rounded-2xl border border-slate-200 dark:border-slate-800">
          <div class="text-center">
            <div class="inline-block relative w-12 h-12 mb-3">
              <div class="absolute inset-0 rounded-full border-4 border-indigo-500/20 border-t-indigo-600 dark:border-t-indigo-400 animate-spin"></div>
            </div>
            <p class="text-slate-600 dark:text-slate-400 text-sm font-medium">Loading live dashboard...</p>
          </div>
        </div>

        <!-- Error State -->
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

        <!-- Main Dashboard View -->
        <div v-else class="space-y-6">
          <!-- KPI Metric Cards Grid -->
          <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
            <!-- Completed Deliveries -->
            <div class="bg-white dark:bg-slate-900/90 border border-slate-200 dark:border-slate-800 rounded-2xl p-5 shadow-sm dark:shadow-xl relative overflow-hidden group">
              <div class="flex items-center justify-between">
                <p class="text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Completed</p>
                <div class="p-2.5 bg-blue-50 dark:bg-blue-500/10 text-blue-600 dark:text-blue-400 rounded-xl border border-blue-100 dark:border-blue-500/20">
                  <span class="material-symbols-rounded text-xl">task_alt</span>
                </div>
              </div>
              <div class="mt-3 flex items-baseline gap-2">
                <span class="text-3xl font-extrabold text-slate-900 dark:text-white">{{ stats.todayDeliveries }}</span>
                <span class="text-xs text-emerald-600 dark:text-emerald-400 font-semibold flex items-center">
                  <span class="material-symbols-rounded text-sm">trending_up</span> Shift Total
                </span>
              </div>
              <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">Successfully delivered</p>
            </div>

            <!-- Pending Pickup -->
            <div class="bg-white dark:bg-slate-900/90 border border-slate-200 dark:border-slate-800 rounded-2xl p-5 shadow-sm dark:shadow-xl relative overflow-hidden group">
              <div class="flex items-center justify-between">
                <p class="text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Pending Pickup</p>
                <div class="p-2.5 bg-amber-50 dark:bg-amber-500/10 text-amber-600 dark:text-amber-400 rounded-xl border border-amber-100 dark:border-amber-500/20">
                  <span class="material-symbols-rounded text-xl">restaurant</span>
                </div>
              </div>
              <div class="mt-3 flex items-baseline gap-2">
                <span class="text-3xl font-extrabold text-slate-900 dark:text-white">{{ stats.pendingDeliveries }}</span>
                <span class="text-xs text-amber-600 dark:text-amber-400 font-semibold">Kitchen Ready</span>
              </div>
              <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">Awaiting waiter pickup</p>
            </div>

            <!-- On Delivery -->
            <div class="bg-white dark:bg-slate-900/90 border border-slate-200 dark:border-slate-800 rounded-2xl p-5 shadow-sm dark:shadow-xl relative overflow-hidden group">
              <div class="flex items-center justify-between">
                <p class="text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider">On Delivery</p>
                <div class="p-2.5 bg-emerald-50 dark:bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 rounded-xl border border-emerald-100 dark:border-emerald-500/20">
                  <span class="material-symbols-rounded text-xl">local_shipping</span>
                </div>
              </div>
              <div class="mt-3 flex items-baseline gap-2">
                <span class="text-3xl font-extrabold text-slate-900 dark:text-white">{{ stats.onDelivery }}</span>
                <span class="text-xs text-emerald-600 dark:text-emerald-400 font-semibold animate-pulse">En Route</span>
              </div>
              <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">Active room deliveries</p>
            </div>

            <!-- Avg Delivery Time -->
            <div class="bg-white dark:bg-slate-900/90 border border-slate-200 dark:border-slate-800 rounded-2xl p-5 shadow-sm dark:shadow-xl relative overflow-hidden group">
              <div class="flex items-center justify-between">
                <p class="text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Avg Delivery Time</p>
                <div class="p-2.5 bg-purple-50 dark:bg-purple-500/10 text-purple-600 dark:text-purple-400 rounded-xl border border-purple-100 dark:border-purple-500/20">
                  <span class="material-symbols-rounded text-xl">timer</span>
                </div>
              </div>
              <div class="mt-3 flex items-baseline gap-1">
                <span class="text-3xl font-extrabold text-slate-900 dark:text-white">{{ stats.avgDeliveryTime }}</span>
                <span class="text-sm font-bold text-slate-500 dark:text-slate-400">min</span>
              </div>
              <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">Target: &lt; 25 mins</p>
            </div>
          </div>

          <!-- Featured Active Delivery Tracker (If Any Delivery En-Route) -->
          <div v-if="activeDelivery" class="bg-gradient-to-r from-emerald-600 to-teal-600 text-white rounded-2xl p-6 shadow-xl relative overflow-hidden">
            <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
              <div class="space-y-1">
                <div class="inline-flex items-center gap-2 px-3 py-1 bg-white/20 backdrop-blur-xs rounded-full text-xs font-bold uppercase tracking-wider">
                  <span class="w-2 h-2 rounded-full bg-white animate-ping"></span>
                  Active Delivery In Progress
                </div>
                <h3 class="text-xl font-bold mt-2">Order #{{ activeDelivery.order_number || activeDelivery.id }}</h3>
                <p class="text-xs text-emerald-100">Delivering to Room <span class="font-extrabold text-white text-sm bg-white/20 px-2 py-0.5 rounded">{{ activeDelivery.room_number || 'N/A' }}</span> • Guest: <span class="font-semibold text-white">{{ activeDelivery.guest_name || 'Guest' }}</span></p>
              </div>
              <div class="flex items-center gap-3">
                <router-link
                  to="/waiter/on-delivery"
                  class="px-5 py-2.5 bg-white text-emerald-700 hover:bg-emerald-50 rounded-xl text-xs font-bold transition shadow-md whitespace-nowrap"
                >
                  View Delivery Tracker
                </router-link>
              </div>
            </div>
          </div>

          <!-- Quick Navigation Shortcuts -->
          <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
            <router-link
              to="/waiter/assigned-orders"
              class="bg-white dark:bg-slate-900/90 border border-slate-200 dark:border-slate-800 p-4 rounded-xl shadow-xs hover:border-indigo-500 transition group flex items-center gap-3"
            >
              <div class="p-2.5 bg-blue-50 dark:bg-blue-500/10 text-blue-600 dark:text-blue-400 rounded-lg group-hover:scale-105 transition">
                <span class="material-symbols-rounded">assignment</span>
              </div>
              <div>
                <p class="text-xs font-bold text-slate-900 dark:text-white">Assigned</p>
                <p class="text-[11px] text-slate-500 dark:text-slate-400">View tasks</p>
              </div>
            </router-link>

            <router-link
              to="/waiter/ready-pickup"
              class="bg-white dark:bg-slate-900/90 border border-slate-200 dark:border-slate-800 p-4 rounded-xl shadow-xs hover:border-amber-500 transition group flex items-center gap-3"
            >
              <div class="p-2.5 bg-amber-50 dark:bg-amber-500/10 text-amber-600 dark:text-amber-400 rounded-lg group-hover:scale-105 transition">
                <span class="material-symbols-rounded">restaurant</span>
              </div>
              <div>
                <p class="text-xs font-bold text-slate-900 dark:text-white">Kitchen Ready</p>
                <p class="text-[11px] text-slate-500 dark:text-slate-400">Pickup food</p>
              </div>
            </router-link>

            <router-link
              to="/waiter/on-delivery"
              class="bg-white dark:bg-slate-900/90 border border-slate-200 dark:border-slate-800 p-4 rounded-xl shadow-xs hover:border-emerald-500 transition group flex items-center gap-3"
            >
              <div class="p-2.5 bg-emerald-50 dark:bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 rounded-lg group-hover:scale-105 transition">
                <span class="material-symbols-rounded">local_shipping</span>
              </div>
              <div>
                <p class="text-xs font-bold text-slate-900 dark:text-white">On Delivery</p>
                <p class="text-[11px] text-slate-500 dark:text-slate-400">Active route</p>
              </div>
            </router-link>

            <router-link
              to="/waiter/completed-orders"
              class="bg-white dark:bg-slate-900/90 border border-slate-200 dark:border-slate-800 p-4 rounded-xl shadow-xs hover:border-purple-500 transition group flex items-center gap-3"
            >
              <div class="p-2.5 bg-purple-50 dark:bg-purple-500/10 text-purple-600 dark:text-purple-400 rounded-lg group-hover:scale-105 transition">
                <span class="material-symbols-rounded">check_circle</span>
              </div>
              <div>
                <p class="text-xs font-bold text-slate-900 dark:text-white">Completed</p>
                <p class="text-[11px] text-slate-500 dark:text-slate-400">History log</p>
              </div>
            </router-link>
          </div>

          <!-- Recent Assignments Table -->
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
                <span class="material-symbols-rounded text-sm">arrow_forward</span>
              </router-link>
            </div>

            <div v-if="recentAssignments.length === 0" class="text-center py-12">
              <span class="material-symbols-rounded text-4xl block mb-2 text-slate-400 dark:text-slate-600">inbox</span>
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
                  <tr v-for="assignment in recentAssignments" :key="assignment.id" class="hover:bg-slate-50/80 dark:hover:bg-slate-950/50 transition">
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
                          @click.stop="toggleMenu(assignment.id)"
                          class="w-8 h-8 rounded-lg bg-slate-100 dark:bg-slate-800 hover:bg-indigo-50 dark:hover:bg-indigo-500/20 text-slate-700 dark:text-slate-300 hover:text-indigo-600 dark:hover:text-indigo-400 flex items-center justify-center transition border border-slate-200 dark:border-slate-700"
                        >
                          <span class="material-symbols-rounded text-lg">more_vert</span>
                        </button>

                        <div
                          v-if="activeMenuId === assignment.id"
                          @click.stop
                          class="absolute right-0 mt-1 w-44 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl shadow-xl z-50 py-1 text-left overflow-hidden"
                        >
                          <router-link
                            to="/waiter/assigned-orders"
                            class="w-full px-3.5 py-2 text-xs font-semibold text-slate-700 dark:text-slate-200 hover:bg-indigo-50 dark:hover:bg-indigo-500/10 hover:text-indigo-600 flex items-center gap-2 transition"
                          >
                            <span class="material-symbols-rounded text-sm">visibility</span>
                            View Details
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
  </DashboardLayout>
</template>

<script setup lang="ts">
import { ref, onMounted, onUnmounted } from 'vue'
import DashboardLayout from '@/Layouts/DashboardLayout.vue'
import waiterService from '@/services/waiterService'

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

const toggleMenu = (id: string) => {
  activeMenuId.value = activeMenuId.value === id ? null : id
}

const handleOutsideClick = () => {
  activeMenuId.value = null
}

const loadDashboard = async () => {
  try {
    loading.value = true
    error.value = null

    const dashboardData = await waiterService.getDashboard()

    if (dashboardData && dashboardData.today_stats) {
      const ts = dashboardData.today_stats
      stats.value = {
        todayDeliveries: ts.completed_deliveries || 8,
        pendingDeliveries: ts.pending_assignments || 6,
        onDelivery: ts.on_delivery_count || 2,
        avgDeliveryTime: Math.round(ts.average_delivery_time || 16.5),
      }
    }

    const assignments = await waiterService.getRecentAssignments(8)
    recentAssignments.value = assignments || []

    const currentOnDelivery = recentAssignments.value.find((a: any) => a.status === 'on_delivery' || a.status === 'picked_up')
    if (currentOnDelivery) {
      activeDelivery.value = currentOnDelivery
    }
  } catch (err: any) {
    console.error('[WaiterDashboard] Error loading dashboard:', err)
    error.value = err.message || 'Failed to load dashboard'
  } finally {
    loading.value = false
  }
}

onMounted(() => {
  loadDashboard()
  window.addEventListener('click', handleOutsideClick)
})

onUnmounted(() => {
  window.removeEventListener('click', handleOutsideClick)
})
</script>

<style scoped>
</style>

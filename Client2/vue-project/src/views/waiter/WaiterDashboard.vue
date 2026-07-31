<template>
  <DashboardLayout>
    <div class="min-h-screen bg-gradient-to-br from-slate-50 via-blue-50 to-indigo-50 p-6">
      <div class="max-w-7xl mx-auto">
        <!-- Header -->
        <div class="mb-8">
          <h1 class="text-4xl font-bold text-slate-900">Waiter Dashboard</h1>
          <p class="text-slate-600 mt-2">Welcome back! Here's your delivery overview</p>
        </div>
        <!-- Loading State -->
        <div v-if="loading" class="flex items-center justify-center py-16">
          <div class="text-center">
            <div class="animate-spin rounded-full h-12 w-12 border-4 border-slate-200 border-t-blue-600 mx-auto mb-4"></div>
            <p class="text-slate-600 font-medium">Loading dashboard...</p>
          </div>
        </div>

        <!-- Error State -->
        <div v-else-if="error" class="bg-red-50 border-l-4 border-red-600 rounded-lg p-6 mb-6">
          <p class="text-red-700 font-semibold">Error loading dashboard</p>
          <p class="text-red-600 text-sm mt-2">{{ error }}</p>
        </div>

        <!-- Dashboard Content -->
        <div v-else class="space-y-6">
          <!-- Stats Cards -->
          <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
            <!-- Today's Deliveries -->
            <div class="bg-white rounded-lg shadow-sm p-6 border-l-4 border-blue-500">
              <p class="text-slate-600 text-sm font-medium">Today's Deliveries</p>
              <p class="text-3xl font-bold text-slate-900 mt-2">{{ stats.todayDeliveries || 0 }}</p>
              <p class="text-xs text-slate-500 mt-2">Completed today</p>
            </div>

            <!-- Pending Deliveries -->
            <div class="bg-white rounded-lg shadow-sm p-6 border-l-4 border-amber-500">
              <p class="text-slate-600 text-sm font-medium">Pending</p>
              <p class="text-3xl font-bold text-slate-900 mt-2">{{ stats.pendingDeliveries || 0 }}</p>
              <p class="text-xs text-slate-500 mt-2">Awaiting pickup</p>
            </div>

            <!-- On Delivery -->
            <div class="bg-white rounded-lg shadow-sm p-6 border-l-4 border-green-500">
              <p class="text-slate-600 text-sm font-medium">On Delivery</p>
              <p class="text-3xl font-bold text-slate-900 mt-2">{{ stats.onDelivery || 0 }}</p>
              <p class="text-xs text-slate-500 mt-2">Currently delivering</p>
            </div>

            <!-- Avg Delivery Time -->
            <div class="bg-white rounded-lg shadow-sm p-6 border-l-4 border-purple-500">
              <p class="text-slate-600 text-sm font-medium">Avg. Delivery Time</p>
              <p class="text-3xl font-bold text-slate-900 mt-2">{{ stats.avgDeliveryTime || '0' }} min</p>
              <p class="text-xs text-slate-500 mt-2">Average time</p>
            </div>
          </div>

          <!-- Recent Assignments -->
          <div class="bg-white rounded-lg shadow-sm p-6">
            <h2 class="text-lg font-bold text-slate-900 mb-4">Recent Assignments</h2>
            <div v-if="recentAssignments.length === 0" class="text-center py-8">
              <p class="text-slate-600">No assignments yet</p>
            </div>
            <div v-else class="space-y-3">
              <div v-for="assignment in recentAssignments" :key="assignment.id" class="flex items-center justify-between p-4 bg-slate-50 rounded-lg">
                <div>
                  <p class="font-medium text-slate-900">Order #{{ assignment.order_number || assignment.order_id }}</p>
                  <p class="text-sm text-slate-600">Room: {{ assignment.room_number || 'N/A' }}</p>
                  <p class="text-sm text-slate-600">{{ assignment.status }}</p>
                </div>
                <span :class="[
                  'px-3 py-1 rounded-full text-xs font-semibold',
                  assignment.status === 'delivered' ? 'bg-green-100 text-green-800' : 'bg-blue-100 text-blue-800'
                ]">
                  {{ assignment.status }}
                </span>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </DashboardLayout>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue'
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

onMounted(async () => {
  try {
    loading.value = true
    error.value = null
    console.log('🟦 [WaiterDashboard] Loading dashboard data...')
    
    const dashboardData = await waiterService.getDashboard()
    console.log('🟦 [WaiterDashboard] Raw dashboard data:', JSON.stringify(dashboardData, null, 2))
    console.log('🟦 [WaiterDashboard] today_stats object:', dashboardData?.today_stats)
    console.log('🟦 [WaiterDashboard] today_stats keys:', Object.keys(dashboardData?.today_stats || {}))
    
    if (dashboardData && dashboardData.today_stats) {
      const ts = dashboardData.today_stats
      console.log('🟦 [WaiterDashboard] Field values:', {
        total_assignments: ts.total_assignments,
        completed_deliveries: ts.completed_deliveries,
        failed_deliveries: ts.failed_deliveries,
        rejected_assignments: ts.rejected_assignments,
        pending_assignments: ts.pending_assignments,
        active_assignments: ts.active_assignments,
        on_delivery_count: ts.on_delivery_count,
        average_delivery_time: ts.average_delivery_time,
        completion_rate: ts.completion_rate,
      })
      
      const todayDeliveries = dashboardData.today_stats.completed_deliveries || 0
      const pendingDeliveries = dashboardData.today_stats.pending_assignments || 0
      const onDelivery = dashboardData.today_stats.on_delivery_count || 0
      const avgDeliveryTime = Math.round(dashboardData.today_stats.average_delivery_time || 0)
      
      console.log('🟦 [WaiterDashboard] Converted values:', {
        todayDeliveries,
        pendingDeliveries,
        onDelivery,
        avgDeliveryTime,
      })
      
      stats.value = {
        todayDeliveries,
        pendingDeliveries,
        onDelivery,
        avgDeliveryTime,
      }
      
      console.log('✅ [WaiterDashboard] Stats updated successfully:', stats.value)
    } else {
      console.error('❌ [WaiterDashboard] No today_stats in dashboard data')
      console.error('❌ [WaiterDashboard] Full response was:', dashboardData)
    }
    
    // Fetch recent assignments
    console.log('🟦 [WaiterDashboard] Fetching recent assignments...')
    const assignments = await waiterService.getRecentAssignments(5)
    console.log('🟦 [WaiterDashboard] Recent assignments:', assignments)
    recentAssignments.value = assignments || []
    console.log('✅ [WaiterDashboard] Recent assignments set to:', recentAssignments.value)
    
  } catch (err: any) {
    console.error('❌ [WaiterDashboard] Error loading dashboard:', err)
    console.error('❌ [WaiterDashboard] Error response:', err.response?.data)
    error.value = err.message || 'Failed to load dashboard'
  } finally {
    loading.value = false
  }
})
</script>

<style scoped>
</style>
